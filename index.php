<?php

error_reporting(0);

// preencha com seu cpf de 11 dígitos, apenas com os numeros no lugar dos zeros

	$CPF	=	'@CPF@';

/*

Código original desenvolvido por Secundum ( http://tenhasualoja.secundum.com.br ) com aprimoramento
para cache local compactado implementado por Stanislaw Pusep ( http://neuromanticos.sysd.org/ )

####### Fale Conosco #######

Secundum		- admin@secundum.com.br
Stanislaw Pusep		- d.sign@sysd.org

*/




// Daqui pra baixo não mexa que provoca estragos
$VERSAO = '3.9';
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), DIRECTORY_SEPARATOR);
$cache = file_exists('index.var');

function fetch($url, $verbose) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
	$buf = curl_exec($ch);
	if($verbose) {
		echo "[$url]\n";
		if($buf === false) {
			echo "ERRO: ".curl_error($ch)."\n";
		} else {
			$info = curl_getinfo($ch);
			echo $info['total_time']." segundos para completar\n";
		}
		echo "\n";
	}
	curl_close($ch);
	return $buf;
}

function mkdir_chmod($pathname, $mode) {
	@mkdir($pathname, $mode);
	@chmod($pathname, $mode);
}

function mkdir_recursive($pathname, $mode) {
    is_dir(dirname($pathname)) || mkdir_recursive(dirname($pathname), $mode);
    return is_dir($pathname) || mkdir_chmod($pathname, $mode);
}

function rmdir_r($directory, $age)
{
	if(substr($directory,-1) == DIRECTORY_SEPARATOR) {
		$directory = substr($directory,0,-1);
	}

	$stat = stat($directory);
	$mtime = $stat[9];
	if(!file_exists($directory) || !is_dir($directory) || ((time() - $mtime) <= $age)) {
		return FALSE;
	} elseif(is_readable($directory)) {
		$handle = opendir($directory);
		while (FALSE !== ($item = readdir($handle))) {
			if($item != '.' && $item != '..') {
				$path = $directory.DIRECTORY_SEPARATOR.$item;
				if(is_dir($path)) {
					rmdir_r($path, $age);
				} else {
					$stat = stat($path);
					$mtime = $stat[9];
					if((time() - $mtime) > $age) {
						unlink($path);
					}
				}
			}
		}
		closedir($handle);
		if(!rmdir($directory)) {
			return FALSE;
		}
	}
	return TRUE;
}

function cache($data, $name) {
	global $base;
	ereg("^$base(/.+)", $_SERVER['REQUEST_URI'], $matches);
	$uri = 'cache'.rtrim($matches[1], '/');
	if (!empty($name)) {
		$path = $uri;
	} else {
		$path = dirname($uri);
	}

	if(($uri != 'cache') && strpos($path, '..') == false) {
		mkdir_recursive($path, 0777);

		$fh = fopen($uri.$name, 'wb');
		fwrite($fh, $data);
		fclose($fh);
		chmod($uri.$name, 0666);
	}
}

if($cache && ereg("^$base/cleanup", $_SERVER['REQUEST_URI'])) {
	header('Content-Type: image/gif');
	header('Expires: Wed, 11 Nov 1998 11:11:11 GMT');
	header('Cache-Control: no-cache');
	header('Cache-Control: must-revalidate');
	printf('%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%', 71,73,70,56,57,97,1,0,1,0,128,255,0,192,192,192,0,0,0,33,249,4,1,0,0,0,0,44,0,0,0,0,1,0,1,0,0,2,2,68,1,0,59);

	$stamp = filemtime('cache/cache');
	if(!$stamp || ((time() - $stamp) > 3600)) {
		touch('cache/cache');
		rmdir_r('cache', 3600*24*5);
	}
} elseif(ereg("^$base/imagem/([a-zA-Z0-9]*)/?([a-zA-Z0-9_]*)", $_SERVER['REQUEST_URI'], $matches)) {
	header('Content-Type: image/jpeg');
	$jpg = fetch('http://'.$matches[1].'.mlapps.com/jm/img?s=MLB&f='.$matches[2].'.jpg&v=I');

	echo $jpg;
	if($cache) {
		cache($jpg);
	}
} elseif($cache && ereg("^$base/supersis/(.+)", $_SERVER['REQUEST_URI'], $matches)) {
	header('Content-Type: text/plain');
	$dat = fetch('http://www2.secundum.com.br/supersis/'.$matches[1]);

	echo $dat;
	cache($dat);
} elseif(ereg("^($base/$CPF)/?(\?force=y)?\$", $_SERVER['REQUEST_URI'], $matches)) {
	set_time_limit(0);
	if (!empty($_POST['force']) || !empty($_GET['force'])) {
		rmdir_r('cache', -1);
		header('Location: '.$matches[1]);
	} else {
		echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 3.2//EN">
<html>
<head>
<title>Controle da loja</title>
</head>
<body>
<h2>Grave este endere&ccedil;o para poder realizar a manuten&ccedil;&atilde;o da sua loja!</h2>
<p><a href="$base/">Ir para a loja V<!-- VER -->$VERSAO<!-- VER --></a></p>
Estat&iacute;sticas do servidor central:
<pre>
HTML;

		$servidor = fetch("http://secundum.com.br/superloja/parceiro.php?p1=".$CPF, 1);
		$file_contents = fetch($servidor."?p1=".$CPF."&p2=&p3=&p4=&p5=", 1);
		echo "</pre>";

		if($cache) {
			echo <<<FORM
<form method='post'><input type="hidden" name="force" value="y">
<input type="submit" value="Limpar cache"></form>
FORM;
		}
		echo "</body></html>";
	}
} else {
	preg_match("%^$base/([^/]*)/?([^/]*)/?([^/]*)%", $_SERVER['REQUEST_URI'], $matches);

	$busca = $matches[1];
	$filtro = $matches[2];
	$completa = $matches[3];

	if(!empty($_GET['busca'])) {
		$busca = $_GET['busca'];
	}

	/*
	if(empty($busca)) {
		$busca = 'ipod';
		$filtro = 'ven';
	}
	*/

	$chave = array();
	foreach (explode('&', parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY)) as $pair) {
		$x = explode('=', $pair);
		if(in_array($x[0],
			array('q','query','qry','keyword','search','keywords','key','s','Keywords',
				'qkw','word','qt','k','kw','words','searchfor','w','qs','ask','Terms',
				'srchText','qq','term','terms','KERESES','qr','string','qry_str','busca',
				'KEYWORDS','str','begriff','text','keys','mt','p','querytext','MT','Q',
				'su','search_query','qu','QueryString'))) {
			$c = $x[1];
			$c = str_replace("+", "_", $c);
			$c = str_replace(" ", "_", $c);
			array_push($chave, $c);
		}
	}

	$servidor = fetch("http://secundum.com.br/superloja/parceiro.php?p1=".$CPF);
	if(empty($servidor)) {
		exit();
	}

	$file_contents = fetch($servidor."?p1=".$CPF."&p2=".$busca."&p3=".$filtro."&p4=".$completa."&p5=".implode('_', $chave));
	if(empty($file_contents)) {
		exit();
	}

	$botlist = array('Teoma','alexa','froogle','inktomi','ia_archiver',
		'looksmart','URL_Spider_SQL','Firefly','NationalDirectory','Gigabot',
		'Ask Jeeves','TECNOSEEK','InfoSeek','WebFindBot','girafabot','CCBot',
		'crawler','www.galaxy.com','Googlebot','Scooter','Slurp','kalooga',
		'msnbot','appie','FAST','WebBug','Spade','ZyBorg','rabaz','Page2RSS',
		'Baiduspider','Feedfetcher-Google','TechnoratiSnoop','Rankivabot',
		'Mediapartners-Google','Sogou web spider','WebAlta Crawler');

	foreach($botlist as $bot) {
		if(ereg($bot, $_SERVER['HTTP_USER_AGENT'])) {
			$cache = 0;
			break;
		}
	}

	if($cache) {
		$file_contents = preg_replace('%</body>%i', "<img src='$base/cleanup' width='1' height='1' border='0' alt=''></body>", $file_contents);
		$file_contents = preg_replace('%\bhttp://(www[\d]?\.)?secundum.com.br/(supersis/.+?/)%i', "$base/$2", $file_contents);
	}

	//include 'filters.php';

	header('Content-Encoding: gzip');
	header('Content-Type: text/html');
	$gzdata = gzencode($file_contents, 9);
	echo $gzdata;
	if($cache && !ereg("^$base/clique", $_SERVER['REQUEST_URI']) && empty($_SERVER['QUERY_STRING'])) {
		cache($gzdata, '/index.html');
	}
}
?>