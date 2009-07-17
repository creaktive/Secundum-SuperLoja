#!/usr/bin/perl -w
use strict;
use Compress::Zlib;
use Digest::MD5 qw(md5_hex);
use MIME::Base64;

use constant OUTPUT => 'loja4.php';


open (PHP, 'index.php') or die "erro: $!\n";
my $php = '';
while (<PHP>) {
	chomp;
	s%//.*$%%;
	s%^\s+%%;
	s%\s+$%%;
	$php .= $_;
}
close PHP;
$php =~ s%/\*.*?\*/%%gs;
my %f;
while ($php =~ m%<\?php(.*?)\?>%gis) {
	my $block = $1;
	++$f{lc $1} while $block =~ m%\b([A-Z_0-9]+)\s*\(([^\(\)]*?)\)%gis;
	delete $f{lc $1} while $block =~ m%\bfunction\s+([A-Z_0-9]+)\s*\(%gis;
}
delete $f{$_} foreach qw(
	if elseif switch
	and not or
	do for foreach return while
	array empty isset unset
	echo exit
	set_time_limit
);
my $check = "array('" . join ("','", sort keys %f) . "')";


unlink(OUTPUT);
open (INST, '>', OUTPUT) or die "erro: $!\n";
binmode INST;

############ PHP CODE ############
print INST<<HEADER
<?php
error_reporting(0);

function fix_url(\$URL) {
	if(!empty(\$URL)) {
		\$URL = rtrim(\$URL, '/');
		if(substr(\$URL, 0, 7) != 'http://') {
			\$URL = 'http://' . \$URL;
		}
	}
	return \$URL;
}

\$_POST['URL'] = fix_url(\$_POST['URL']);
\$_POST['logo'] = fix_url(\$_POST['logo']);

\$pkg = array(
HEADER
;

undeploy('a/.htaccess');
undeploy('a/index.php');
undeploy('b/.htaccess');
undeploy('index.php');

############ PHP CODE ############
print INST<<BODY
);

function deploy(\$file, \$out, \$gz){
	global \$pkg;

	\$md5 = \$pkg[\$file][0];
	\$buf = gzuncompress(base64_decode(\$pkg[\$file][1]));
	\$out = empty(\$out) ? \$file : \$out;

	if(md5(\$buf) == \$md5) {
		\$base = dirname(empty(\$_SERVER['PHP_SELF']) ? \$_SERVER['SCRIPT_NAME'] : \$_SERVER['PHP_SELF']);
		\$base = str_replace(DIRECTORY_SEPARATOR, '/', \$base);
		\$base = trim(\$base, '/');
		\$dir = dirname(\$out);
		\$dir = trim(\$dir, DIRECTORY_SEPARATOR);
		\$dir = ltrim(\$dir, '.');
		\$dir = empty(\$base) ? "/\$dir" : "/\$base/\$dir";
		if(substr(\$dir, -1, 1) != '/') {
			\$dir .= '/';
		}
		\$buf = preg_replace('%\@URI\@%s',	\$dir, \$buf);

		\$buf = preg_replace('%\@IDML\@%s',	\$_POST['IDML'], \$buf);
		\$buf = preg_replace('%\@IMG\@%s',	\$_POST['logo'], \$buf);
		\$buf = preg_replace('%\@PVT\@%s',	\$_POST['pvt'], \$buf);
		\$buf = preg_replace('%\@URL\@%s',	\$_POST['URL'], \$buf);

		if(\$fh = fopen(\$out, 'wb')) {
			if(!fwrite(\$fh, empty(\$gz) ? \$buf : gzencode(\$buf))) {
				echo "Falha ao gravar dados em \$out: \$php_errormsg";
				exit;
			}
		} else {
			echo "Falha ao abrir o arquivo \$out: \$php_errormsg";
			exit;
		}
		fclose(\$fh);

		chmod(\$out, 0644);
	} else {
		echo "Instalador corrompido! Refa&ccedil;a o upload.";
		exit();
	}
}

function mkdir_chmod(\$pathname, \$mode) {
	\@mkdir(\$pathname, \$mode);
	\@chmod(\$pathname, \$mode);
}

if(!\$_POST['URL'] || !\$_POST['logo'] || !\$_POST['IDML'] || !\$_POST['pvt']) {
	if (is_writable(dirname(__FILE__))) {
		mkdir_chmod('a', 0755);
		deploy('a/.htaccess');
		deploy('a/index.php');

		mkdir_chmod('b', 0755);
		deploy('b/.htaccess');
		deploy('a/index.php', 'b/index.php');

		echo <<<HEAD
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 3.2//EN">
<html><head>
<title>Instala&ccedil;&atilde;o da Loja Secundum</title>
<script type="text/javascript">
var cap=0;
var php=0;
function report(id){cap|=id}
</script>
</head>
<body>
<iframe frameborder="0" height="1" width="1" scrolling="no" src="a/teste/teste?1"></iframe>
<iframe frameborder="0" height="1" width="1" scrolling="no" src="b/teste/teste?2"></iframe>
<h1>Instala&ccedil;&atilde;o da Loja Secundum</h1>
<h2>Preencha as informa&ccedil;&otilde;es abaixo:</h2>
HEAD;

		foreach($check as \$f) {
			if(!function_exists(\$f)) {
				echo "<script type='text/javascript'>--php</script><b>Falha do PHP:</b> fun&ccedil;&atilde;o <code>\$f()</code> indefinida!<br>";
			}
		}

		echo <<<FORM
<form action="" method="post" name="instalar">
<table border="0" summary="">
	<tr>
		<td align="right">
			Endere&ccedil;o da sua SuperLoja:
		</td>
		<td>
			<input type="text" value="\$_POST[URL]" maxlength="60" size="30" name="URL" onkeyup="this.value=this.value.replace(/[^\\w\\-\\:\\/\\.]/g,'')">
			<i>(ex.: "http://loja.minhaloja.com/")</i>
		</td>
	</tr>
	<tr>
		<td align="right">
			Logotipo da sua SuperLoja (780x120):
		</td>
		<td>
			<input type="text" value="\$_POST[logo]" maxlength="100" size="30" name="logo" onkeyup="this.value=this.value.replace(/[^\\w\\-\\:\\/\\.]/g,'')">
			<i>(ex.: "http://loja.minhaloja.com/logo.jpg")</i>
		</td>
	</tr>
	<tr>
		<td align="right">
			Seu identificador de MercadoS&oacute;cio:
		</td>
		<td>
			<input type="text" value="\$_POST[IDML]" maxlength="8" size="8" name="IDML" onkeyup="this.value=this.value.replace(/\\D/g,'')">
			<i>(ex.: "5261879")</i>
		</td>
	</tr>
	<tr>
		<td align="right">
			Subpasta do painel de controle:<br>
			<small>
				(o endere&ccedil;o pelo qual voc&ecirc; poder&aacute;<br>
				realizar a manuten&ccedil;&atilde;o da sua loja)
			</small>
		</td>
		<td valign="top">
			<input type="text" value="\$_POST[pvt]" maxlength="30" size="30" name="pvt" onkeyup="this.value=this.value.replace(/[^\\w]/g,'')">
			<i>(ex.: "controle_minhaloja")</i>
		</td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<input type="hidden" value="0" name="params">
			<br>
			<input type="submit" value="Instalar!" onclick="document.instalar.params.value=php?0:cap">
		</td>
	</tr>
</table>
</form>
FORM;
	} else {
		echo "<font color='#a00000'>Aten&ccedil;&atilde;o: a pasta <b>\$where</b> deve ter permiss&atilde;o 0777 ('rwxrwxrwx') <u>durante a instala&ccedil;&atilde;o</u>!</font>";
	}

	echo "</body></html>";
} else {
	\$params = \$_POST['params'];
	if(\$params) {
		\@rename('index.php','index.php.BACKUP');
		\@rename('.htaccess','.htaccess.BACKUP');

		\@unlink('.cache');
		\@unlink('index.var');
		\@unlink('index.html.var');
		\@unlink('imagem/.htaccess');
		\@rmdir('imagem');

		mkdir_chmod('cache', 0755);
		mkdir_chmod('layout', 0755);

		if(\$params & 2) {
			deploy('b/.htaccess', '.htaccess');
			header('Location: ' . \$_POST['pvt']);
		} elseif(\$params & 1) {
			deploy('a/.htaccess', '.htaccess');
			header('Location: ' . \$_POST['pvt']);
		}

		deploy('index.php');
BODY
;

############ PHP CODE ############
print INST<<FOOTER
		\@unlink(__FILE__);
	} else {
		echo<<<WRONG
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 3.2//EN">
<html><head>
<title>Foi imposs&iacute;vel instalar a loja!</title>
</head><body>
<h1>Foi imposs&iacute;vel instalar a loja!</h1>
Verifique as permiss&otilde;es no seu servidor!
</body></html>
WRONG
;
	}

	function clean(\$dir) {
		\@unlink(\$dir.'/.htaccess');
		\@unlink(\$dir.'/index.php');
		\@rmdir(\$dir);
	}

	clean('a');
	clean('b');
}
?>
FOOTER
;

close INST;
exit;

sub undeploy {
	my $file = shift;

	local $/ = undef;
	open (FH, $file) or die "erro abrindo $file: $!\n";
	my $buf = <FH>;
	my $md5 = md5_hex ($buf);
	$buf = encode_base64 (compress ($buf), '');
	close FH;

	print INST "\t'$file' => array('$md5', '$buf'),\n";
}
