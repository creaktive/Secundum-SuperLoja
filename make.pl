#!/usr/bin/perl -w
use strict;
use Compress::Zlib;
use Digest::MD5 qw(md5_hex);
use MIME::Base64;

open (INST, '>', shift @ARGV) or die "erro: $!\n";
binmode INST;

############ PHP CODE ############
print INST<<HEADER
<?php
error_reporting(0);

\$base = rtrim(dirname(\$_SERVER['SCRIPT_NAME']), DIRECTORY_SEPARATOR);
\$CPF = preg_replace('%[^0-9]%', '', \$_POST['id']);

\$pkg = array(
HEADER
;

undeploy('a/.htaccess');
undeploy('a/index.php');
undeploy('b/.htaccess');
undeploy('c/.htaccess');
undeploy('c/index.var');
undeploy('d/.htaccess');
undeploy('index.php');

############ PHP CODE ############
print INST<<BODY
);

function deploy(\$file, \$out, \$gz){
	global \$base;
	global \$CPF;
	global \$pkg;

	\$md5 = \$pkg[\$file][0];
	\$buf = gzuncompress(base64_decode(\$pkg[\$file][1]));
	\$out = empty(\$out) ? \$file : \$out;

	if(md5(\$buf) == \$md5) {
		\$buf = preg_replace('%\@CPF\@%s', \$CPF, \$buf);

		\$dir = ltrim(rtrim(dirname(\$out), DIRECTORY_SEPARATOR), '.');
		\$dir = "\$base/\$dir";
		if(\$dir != '/') {
			\$dir = rtrim(\$dir, '/');
		}
		\$buf = preg_replace('%\@base\@%s', \$dir, \$buf);

		\$buf = preg_replace('%\@root\@%s', preg_quote(dirname(\$_SERVER['SCRIPT_FILENAME'])), \$buf);

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

		chmod(\$out, 0444);
	} else {
		echo "Instalador corrompido! Refa&ccedil;a o upload.";
		exit();
	}
}

function mkdir_chmod(\$pathname, \$mode) {
	\@mkdir(\$pathname, \$mode);
	\@chmod(\$pathname, \$mode);
}

if(empty(\$CPF)) {
	\$where = dirname(\$_SERVER['SCRIPT_FILENAME']);
	if (is_writable(\$where)) {
		mkdir_chmod('a', 0777);
		deploy('a/.htaccess');
		deploy('a/index.php');

		mkdir_chmod('b', 0777);
		deploy('b/.htaccess');
		deploy('a/index.php', 'b/index.php');

		mkdir_chmod('c', 0777);
		mkdir_chmod('c/cache', 0777);
		deploy('c/.htaccess');
		deploy('c/index.var');
		deploy('a/index.php', 'c/cache/teste.html', 1);

		mkdir_chmod('d', 0777);
		mkdir_chmod('d/cache', 0777);
		deploy('d/.htaccess');
		deploy('c/index.var', 'd/index.var');
		deploy('a/index.php', 'd/cache/teste.html', 1);

		echo <<<FORM
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 3.2//EN">
<html><head>
<title>Instala&ccedil;&atilde;o da Loja Secundum</title>
<script type="text/javascript">
var cap=0;
function report(id) {cap|=id}
</script>
</head><body>
<h1>Instala&ccedil;&atilde;o da Loja Secundum</h1>
Verificando as capacidades do servidor...&nbsp;
<iframe frameborder="1" height="8" width="8" scrolling="no" src="a/teste.html?1"></iframe>
<iframe frameborder="1" height="8" width="8" scrolling="no" src="b/teste.html?2"></iframe>
<iframe frameborder="1" height="8" width="8" scrolling="no" src="c/teste.html?4"></iframe>
<iframe frameborder="1" height="8" width="8" scrolling="no" src="d/teste.html?8"></iframe>
&nbsp;(<b>alguns</b> quadrados verdes s&atilde;o suficientes para uma instala&ccedil;&atilde;o funcionar perfeitamente!)
<form method="post" name="instalar">
<ol>
<li><a href="http://tinyurl.com/SuperLojaSecundum" target="_blank">Cadastre</a> a sua superloja</li>
<li>Insira o seu CPF (o mesmo do cadastro):<br>
<input type="text" value="" maxlength="11" size="11" name="id" onkeyup="this.value=this.value.replace(/\\D/g,'')"></li>
<li>
<input type="hidden" value="0" name="params">
<input type="submit" value="Instalar!" onclick="document.instalar.params.value=cap">
</li>
<li><b>BOAS VENDAS :D</b></li>
</ol>
</form>
FORM;
	} else {
		echo "<font color='#a00000'>Aten&ccedil;&atilde;o: a pasta <b>\$where</b> deve ter permiss&atilde;o 0777 ('rwxrwxrwx')!</font>";
	}

	echo "</body></html>";
} else {
	\$params = \$_POST['params'];
	if(\$params) {
		rename('index.php','index.php.BACKUP');
		rename('.htaccess','.htaccess.BACKUP');

		unlink('.cache');
		unlink('index.var');
		unlink('index.html.var');
		unlink('imagem/.htaccess');
		rmdir('imagem');

		if(\$params & 8) {
			mkdir_chmod('cache', 0777);
			deploy('d/.htaccess', '.htaccess');
			deploy('c/index.var', 'index.var');
			header("Location: \$base/\$CPF");
		} elseif(\$params & 4) {
			mkdir_chmod('cache', 0777);
			deploy('c/.htaccess', '.htaccess');
			deploy('c/index.var', 'index.var');
			header("Location: \$base/\$CPF");
		} elseif(\$params & 2) {
			deploy('b/.htaccess', '.htaccess');
			header("Location: \$base");
		} else {
			deploy('a/.htaccess', '.htaccess');
			header("Location: \$base");
		}

		deploy('index.php');
BODY
;

############ PHP CODE ############
print INST<<FOOTER
		unlink(\$_SERVER['SCRIPT_FILENAME']);
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
		unlink(\$dir.'/.htaccess');
		unlink(\$dir.'/index.php');
		unlink(\$dir.'/index.var');
		unlink(\$dir.'/cache/teste.html');
		rmdir(\$dir.'/cache');
		rmdir(\$dir);
	}

	clean('a');
	clean('b');
	clean('c');
	clean('d');
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
