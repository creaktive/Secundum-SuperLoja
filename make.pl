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
	array empty isset list unset
	echo exit
	set_time_limit
	readgzfile
	busca location.href.substr x.replace acessos cliques swfobject.embedswf
);
my $check = "array('" . join ("','", sort keys %f) . "')";


unlink(OUTPUT);
open (INST, '>', OUTPUT) or die "erro: $!\n";
binmode INST;

############ PHP CODE ############
print INST<<HEADER
<?php
/*
	Secundum SuperLoja - loja online utilizando o sistema Secundum
	em parceria com o Mercado Livre para rentabilizar blogs/sites.
	Copyright (C) 2009  Stanislaw Pusep

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

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
undeploy ('robots.txt');

for (1..10) {
	undeploy (sprintf ('imagens/%03d.jpg', $_));
}

foreach (qw(niceforms.css niceforms.js 0.png button.png button-left.png button-right.png checkbox.png file.png input.png input-left.png input-right.png radio.png select-left.png select-right.png textarea-bl.png textarea-br.png textarea-l-off.png textarea-l-over.png textarea-r-off.png textarea-r-over.png textarea-tl.png textarea-tr.png)) {
	undeploy ("controle/$_");
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
		\$buf = preg_replace('%sistema\.secundum\.com\.br%s', \$_POST['sis'], \$buf);

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

function fetch(\$uri) {
	\$req = "GET \$uri HTTP/1.0\\r\\n";
	\$req .= 'Host: ' . \$_SERVER['SERVER_NAME'] . "\\r\\n";
	\$req .= "\\r\\n";
	\$res = '';

	if (false != (\$fs = \@fsockopen(\$_SERVER['SERVER_ADDR'], \$_SERVER['SERVER_PORT'], \$errno, \$errstr, 15))) {
		fwrite(\$fs, \$req);
		while (!feof(\$fs) && (strlen(\$res) < 0x2800))
			\$res .= fgets(\$fs, 1160);
		fclose(\$fs);

		list(\$tmp, \$res) = preg_split('%\\r?\\n\\r?\\n%', \$res, 2);
	}

	return \$res;
}

\$params = \$_POST['params'];
if (!\$params) {
	echo <<<HEAD
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 3.2//EN">
<html><head>
<title>Instala&ccedil;&atilde;o da SuperLoja Secundum</title>
</head>
<body style="background-color:#cccccc;font-family: Trebuchet MS; font-size: 11px; color:#333333; padding:20px;">
<table align="center" summary="" style="background-color:#f2f2e6; border:1px solid #ffffff; border-color:#ffffff #666661 #666661 #ffffff; text-align:center; padding:10px 10px 0px 10px;">
<tr><th>Instala&ccedil;&atilde;o da SuperLoja Secundum</th></tr>
<tr><td><br>
HEAD;

	if (is_writable(dirname(__FILE__))) {
		mkdir_chmod('a', 0755);
		deploy('a/.htaccess');
		deploy('a/index.php');

		mkdir_chmod('b', 0755);
		deploy('b/.htaccess');
		deploy('a/index.php', 'b/index.php');

		if (substr(fetch(\$base . '/a/teste/teste?1'), 0, 4) == 'SEC5') {
			\$params |= 1;
		}
		if (substr(fetch(\$base . '/b/teste/teste?2'), 0, 4) == 'SEC5') {
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
<input type="hidden" value="sistema.secundum.com.br" name="sis">
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

	echo "</td></tr></table></body></html>";
} else {
	foreach (array('hist.dat','chart.php','control/expressInstall.swf','control/swfobject.js','control/open-flash-chart.swf') as \$old)
		\@unlink(\$old);

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
	deploy('robots.txt');

	mkdir_chmod('imagens', 0755);
	mkdir_chmod('controle', 0755);
	foreach (array_keys(\$pkg) as \$file) {
		\$dir = substr(\$file, 0, 8);
		if ((\$dir == 'controle') || (\$dir == 'imagens/')) {
			deploy(\$file);
		}
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
	binmode FH;
	my $buf = <FH>;
	my $md5 = md5_hex ($buf);
	$buf = encode_base64 (compress ($buf), '');
	close FH;

	print INST "\t'$file' => array('$md5', '$buf'),\n";
}
