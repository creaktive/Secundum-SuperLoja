#!/usr/bin/perl -w
use strict;
use Compress::Zlib;
use Digest::MD5 qw(md5_hex);
use MIME::Base64;

use constant OUTPUT => 'loja5.php';


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
	++$f{lc $1} while $block =~ m%\b([A-Z_0-9\.]+)\s*\(([^\(\)]*?)\)%gis;
	delete $f{lc $1} while $block =~ m%\bfunction\s+([A-Z_0-9\.]+)\s*\(%gis;
}
delete $f{$_} foreach qw(
	if elseif switch
	and not or
	do for foreach return while
	array empty isset unset
	echo exit
	set_time_limit
	busca location.href.substr x.replace
);
my $check = "array('" . join ("','", sort keys %f) . "')";


unlink(OUTPUT);
open (INST, '>', OUTPUT) or die "erro: $!\n";
binmode INST;

############ PHP CODE ############
print INST<<HEADER
<?php
error_reporting(0);

\$pkg = array(
HEADER
;

undeploy ('a/.htaccess');
undeploy ('a/index.php');
undeploy ('b/.htaccess');
undeploy ('index.php');
undeploy ('rsslib.php');
undeploy ('feeds.php');

for (1..10) {
	undeploy (sprintf ('imagens/%03d.jpg', $_));
}

############ PHP CODE ############
print INST<<BODY
);

\$base = dirname(empty(\$_SERVER['PHP_SELF']) ? \$_SERVER['SCRIPT_NAME'] : \$_SERVER['PHP_SELF']);
\$base = str_replace(DIRECTORY_SEPARATOR, '/', \$base);
\$base = rtrim(\$base, '/');
\$url = 'http://' . \$_SERVER['SERVER_NAME'] . \$base;

function deploy(\$file, \$out, \$gz){
	global \$pkg, \$base, \$url;

	\$md5 = \$pkg[\$file][0];
	\$buf = gzuncompress(base64_decode(\$pkg[\$file][1]));
	\$out = empty(\$out) ? \$file : \$out;

	if (md5(\$buf) == \$md5) {
		\$uri = dirname(\$out);
		\$uri = trim(\$uri, DIRECTORY_SEPARATOR);
		\$uri = ltrim(\$uri, '.');
		\$uri = empty(\$base) ? "/\$uri" : "/\$base/\$uri";
		if (substr(\$uri, -1, 1) != '/') {
			\$uri .= '/';
		}

		\$buf = preg_replace('%\@URI\@%s', \$uri, \$buf);
		\$buf = preg_replace('%\@URL\@%s', \$url, \$buf);

		if (\$fh = fopen(\$out, 'wb')) {
			if (!fwrite(\$fh, empty(\$gz) ? \$buf : gzencode(\$buf))) {
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

function fetch(\$addr) {
	\$ch = curl_init();
	curl_setopt(\$ch, CURLOPT_URL, \$addr);
	curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt(\$ch, CURLOPT_CONNECTTIMEOUT, 15);
	\$buf = curl_exec(\$ch);
	curl_close(\$ch);
	return \$buf;
}

\$params = \$_POST['params'];
if (!\$params) {
	echo <<<HEAD
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 3.2//EN">
<html><head>
<title>Instala&ccedil;&atilde;o da SuperLoja Secundum</title>
</head>
<body>
<h1>Instala&ccedil;&atilde;o da SuperLoja Secundum</h1>
HEAD;

	if (is_writable(dirname(__FILE__))) {
		mkdir_chmod('a', 0755);
		deploy('a/.htaccess');
		deploy('a/index.php');

		mkdir_chmod('b', 0755);
		deploy('b/.htaccess');
		deploy('a/index.php', 'b/index.php');

		if (substr(fetch(\$url . '/a/teste/teste?1'), 0, 4) == 'SEC5') {
			\$params |= 1;
		}
		if (substr(fetch(\$url . '/b/teste/teste?2'), 0, 4) == 'SEC5') {
			\$params |= 2;
		}

		function clean(\$dir) {
			\@unlink(\$dir . '/.htaccess');
			\@unlink(\$dir . '/index.php');
			\@rmdir(\$dir);
		}
		clean('a');
		clean('b');

		if (!\$params) {
			echo "<font color='#a00000'>Foi imposs&iacute;vel utilizar <code>.htaccess</code>!</font><br>";
		}

		\$php = true;
		foreach ($check as \$f) {
			if (!function_exists(\$f)) {
				echo "<font color='#a00000'><b>Falha do PHP:</b> fun&ccedil;&atilde;o <code>\$f()</code> indefinida!</font><br>";
				\$php = false;
			}
		}

		if (\$params && \$php) {
			echo <<<FORM
<form action="" method="post" name="instalar">
<input type="hidden" value="\$params" name="params">
<input type="submit" value="Instalar!">
</form>
FORM;
		} else {
			echo "<h2>Verifique a instala&ccedil;&atilde;o de Apache/PHP do seu servidor:</h2>";
			phpinfo();
		}
	} else {
		echo "<font color='#a00000'>Aten&ccedil;&atilde;o: a pasta <b>\$base</b> deve ter permiss&atilde;o 0777 ('rwxrwxrwx') <u>durante a instala&ccedil;&atilde;o</u>!</font>";
	}

	echo "</body></html>";
} else {
	\@rename('index.php','index.php.BACKUP');
	\@rename('.htaccess','.htaccess.BACKUP');

	mkdir_chmod('cache', 0755);
	mkdir_chmod('feeds', 0755);
	mkdir_chmod('layout', 0755);

	if (\$params & 2) {
		deploy('b/.htaccess', '.htaccess');
	} elseif(\$params & 1) {
		deploy('a/.htaccess', '.htaccess');
	}

	deploy('index.php');
	deploy('rsslib.php');
	deploy('feeds.php');

	mkdir_chmod('imagens', 0755);
	for (\$i = 1; \$i <= 10; \$i++) {
		deploy(sprintf('imagens/%03d.jpg', \$i));
	}

	header('Location: admin');
	\@unlink(__FILE__);
}

?>
BODY
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
