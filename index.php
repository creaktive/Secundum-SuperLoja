<?php

define('CPF',		'@CPF@');
define('BASE',		'@base@');

define('VERSAO',	'4.1');
define('CLIQUE',	'http://clique.secundum.com.br/');
define('API',		'sistema.php');

define('BUSCA',		'busca');
define('CACHE',		'cache');
define('CACHE_AUTO',CACHE . DIRECTORY_SEPARATOR . 'auto');
define('CACHE_EXP',	3600*24*5);
define('CACHE_LOCK',CACHE . DIRECTORY_SEPARATOR . 'lock');
define('CLEANUP',	'cleanup');
define('IMAGEM',	'imagem');
define('LAYOUT',	'layout');
define('TEMPLATE',	LAYOUT . DIRECTORY_SEPARATOR . 'template.html');

$SUFFIX = array('ven','vis','car','bar','mel');

if (CPF == '123456789') {
	error_reporting(1);
	define('SERVIDOR', 'http://localhost/sis4/');
} else {
	error_reporting(0);
	$ips = gethostbynamel('secundum.com.br');
	define('SERVIDOR', sprintf('http://%s/sis4/', $ips[rand(0, count($ips) - 1)]));
}

if (!function_exists('file_put_contents')) {
	function file_put_contents($filename, $data) {
		$f = @fopen($filename, 'w');
		if (!$f) {
			return false;
		} else {
			$bytes = fwrite($f, $data);
			fclose($f);
			return $bytes;
		}
	}
}
if (!function_exists('gzdecode')) {
	function gzdecode($data) {
		$len = strlen($data);
		if ($len < 18 || strcmp(substr($data,0,2),"\x1f\x8b")) {
			return null;	// Not GZIP format (See RFC 1952)
		}

		$g = tempnam(sys_get_temp_dir(), 'ff');
		@file_put_contents($g,$data);
		ob_start();
		readgzfile($g);
		@unlink($g);
		$d = ob_get_clean();
		return $d;
	}
}
if (!function_exists('sys_get_temp_dir')) {
	function sys_get_temp_dir() {
		if (!empty($_ENV['TMP']))		{ return realpath($_ENV['TMP']); }
		if (!empty($_ENV['TMPDIR']))	{ return realpath( $_ENV['TMPDIR']); }
		if (!empty($_ENV['TEMP']))		{ return realpath( $_ENV['TEMP']); }
		$tempfile = tempnam(uniqid(rand(), TRUE), '');
		if (file_exists($tempfile)) {
			@unlink($tempfile);
			return realpath(dirname($tempfile));
		}
	}
}

$query	= $_GET['query'];
$uri	= $_GET['uri'];

if ($query) {
	$query = do_query($query);
	if (empty($query)) {
		header('Location: ' . BASE);
	} else {
		$query = str_replace(' ', '_', $query);
		header('Location: ' . BASE . $query . '/');
	}
	exit();
}

$uri = normalize($uri);
$uri = preg_replace('%^' . preg_quote(substr(BASE, 1)) . '%i', '', $uri);
$uri = preg_replace('%\?.*$%', '', $uri);
$params = array();
foreach (preg_split('%/%', $uri, 3, PREG_SPLIT_NO_EMPTY) as $param) {
	preg_replace('%^\.%', '', $param);
	array_push($params, $param);
}
$localpath = implode(DIRECTORY_SEPARATOR, $params);

srand(crc32(implode('|', array(dirname(__FILE__), $localpath, CPF))));

mkdir_chmod(LAYOUT, 0755);
$TPLstat = @stat(TEMPLATE);

if (($params[0] == LAYOUT) && !empty($params[1])) {
	$file = LAYOUT . DIRECTORY_SEPARATOR . $params[1];
	$filestat = @stat($file);

	if (($TPLstat !== false) && ($filestat !== false) && ($filestat['size'] > 0) && ($TPLstat['mtime'] <= $filestat['mtime'])) {
		$data = @file_get_contents($file);
	} else {
		$data = fetch(SERVIDOR . $file);
		if (!empty($data)) {
			@file_put_contents($file, $data);
			@chmod($file, 0644);
		}
	}

	fix_header($params[1]);
	echo $data;
} elseif (($params[0] == IMAGEM) && !empty($params[1]) && !empty($params[2])) {
	header('Content-Type: image/jpeg');

	$MLsrv = $params[1];
	$MLimg = $params[2];
	$local = IMAGEM . DIRECTORY_SEPARATOR . $MLsrv . '_' . $MLimg;

	if (file_exists($local)) {
		$jpg = @file_get_contents($local);
	} else {	
		$jpg = fetch("http://${MLsrv}.mlapps.com/jm/img?s=MLB&f=${MLimg}.jpg&v=I");
		if (!empty($jpg)) {
			mkdir_chmod(IMAGEM, 0755);

			@file_put_contents($local, $jpg);
			@chmod($local, 0644);
		}
	}
	echo $jpg;
} elseif ($params[0] == CLEANUP) {
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
	header('Pragma: no-cache');
	header('Content-Type: image/gif');
	printf('%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%', 71,73,70,56,57,97,1,0,1,0,128,255,0,192,192,192,0,0,0,33,249,4,1,0,0,0,0,44,0,0,0,0,1,0,1,0,0,2,2,68,1,0,59);

	if (file_exists(CACHE_AUTO)) {
		@unlink(CACHE_AUTO);
		@touch(CACHE_LOCK);
		rmdir_r(CACHE, -1);
	} else {
		$stamp = @filemtime(CACHE_LOCK);
		if (!$stamp || ((time() - $stamp) > 3600)) {
			@touch(CACHE_LOCK);
			rmdir_r(CACHE, CACHE_EXP);
			rmdir_r(IMAGEM, CACHE_EXP);
		}
	}
} elseif ($params[0] == CPF) {
	if (!empty($_POST['force'])) {
		rmdir_r(CACHE, -1);
		rmdir_r(IMAGEM, -1);

		header('Location: ' . CPF);
	} else {
		$VERSAO	= VERSAO;
		$BASE	= BASE;
		echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 3.2//EN">
<html>
<head>
<title>Controle da loja</title>
</head>
<body>
<h2>Grave este endere&ccedil;o para poder realizar a manuten&ccedil;&atilde;o da sua loja!</h2>
<p><a href="$BASE">Ir para a loja V<!-- VER -->$VERSAO<!-- VER --></a></p>
Estat&iacute;sticas do servidor central:
<pre>
HTML;

		fetch(SERVIDOR . API . '?p1=' . CPF, 1);

		echo <<<FORM
</pre>
<form method='post'><input type="hidden" name="force" value="y">
<input type="submit" value="Limpar cache"></form>
</body></html>
FORM;
	}
} elseif (in_array($params[0], array('busca', 'cache'))) {
	array_shift($params);
	header('Location: ' . implode('/', $params));
} elseif (!empty($localpath) && file_exists($localpath)) {
	$data = @file_get_contents($localpath);
	fix_header($localpath);
	echo $data;
} else {
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
	header('Pragma: no-cache');
	header('Content-Type: text/html; charset=utf-8');
	@ob_start('ob_gzhandler');

	$busca = $params[0];
	$filtro = $params[1];

	if (empty($filtro) || !in_array($filtro, $SUFFIX)) {
		$filtro = 'vis';
	}

	if (!empty($busca)) {
		$cached_file = cached();
		if (file_exists($cached_file)) {
			$xml = @file_get_contents($cached_file);

			$tmp = gzdecode($xml);
			if (!$tmp) {
				$tmp = $xml;
			}
		}
	}

	if (empty($xml)) {
		$xml = fetch(sprintf('%s%s?p1=%s&p2=%s&p3=%s', SERVIDOR, API, CPF, $busca, $filtro));

		$tmp = gzdecode($xml);
		if (!$tmp) {
			$tmp = $xml;
		}

		if (!$tmp || (substr($tmp, 0, 5) != '<?xml')) {
			echo($xml);
			exit();
		} else {
			$botlist = array(
				'3D_SEARCH','AbachoBOT','accoona','AcoiRobot','afinar','AideRSS','AISearchBot',
				'alexa','AltaVista','ANTFresco','appie','Ask Jeeves','ASPSeek','asterias',
				'Avant','BabalooSpider','BaiduImagespider','Baiduspider','BDFetch',
				'BlogPulseLive','BobCrawl','Caliperbot','CazoodleBot','CCBot','CFNetwork',
				'Cooliris','CoverScout','crawler','CrocCrawler','CyberPatrol','DoCoMo',
				'Dumbot','envolk','eStyle','facebookexternalhit','FAST-WebCrawler','FDM',
				'feedzero.com','fetch','Firefly','freshmeat.net','froogle','FyberSpider',
				'Gaisbot','GbPlugin','GeonaBot','Gigabot','girafabot','Google','GrubNG',
				'GurujiBot','holmes','ia_archiver','IDBot','InfoSeek','inktomi','Jakarta',
				'Java','kalooga','Knight','KumKie','librabot','libwww-perl','looksmart',
				'Lycos','Mail.Ru','Melomania','MLBot','Moreoverbot','MSFrontPage','msnbot',
				'MSR-ISRCCrawler','MSRBOT','NationalDirectory','NSPlayer','NV32ts','Ocelli',
				'OOZBOT','Page2RSS','PHP','phpbbcom','Plonebot','psbot','PuxaRapido','PycURL',
				'Pylciet','Python-urllib','rabaz','radian','Rambler','Rankivabot',
				'REAP-crawler','SapphireWebCrawler','Scooter','Scrubby','SeznamScreenshotator',
				'SiteBar','Sleipnir','Slurp','Sogou','Spade','StackRambler','SurveyBot',
				'SZN-Image-Resizer','TailsweepBot','TechnoratiSnoop','TECNOSEEK','Teoma',
				'thumbshots-de-bot','URL_Spider_SQL','VLC','voyager','WebAlta','WebBug',
				'webcollage','WebFindBot','WebReaper','Wget','WHttpTest','wikiwix','WordPress',
				'galaxy','wwwster','xqrobot','Yahoo','Yandex','Yanga','YebolBot','Yeti',
				'Z-Add','ZyBorg'
			);

			$is_bot = false;
			foreach ($botlist as $bot) {
				if (preg_match('%' . preg_quote($bot) . '%i', $_SERVER['HTTP_USER_AGENT'])) {
					$is_bot = true;
					break;
				}
			}

			if (!empty($cached_file) && !$is_bot) {
				mkdir_recursive(dirname($cached_file), 0777);

				@file_put_contents($cached_file, $xml);
				@chmod($cached_file, 0644);
			}
		}
	}

	$page = xml2array($tmp);
	$page = $page['page'];

	if (($TPLstat !== false) && $TPLstat['size'] && ($TPLstat['mtime'] >= strtotime($page['modif']))) {
		$TPL = @file_get_contents(TEMPLATE);
	} else {
		$TPL = fetch(SERVIDOR . TEMPLATE);
		if (!empty($TPL)) {
			@file_put_contents(TEMPLATE, $TPL);
			@chmod(TEMPLATE, 0644);
			@touch(CACHE_AUTO);
		}
	}

	preg_match('%@ITEM_BEGIN@(.+?)@ITEM_END@%s', $TPL, $tmp);
	$itemTPL = $tmp[0];
	$itemTPL = preg_replace('%^@ITEM_BEGIN@%s', '', $itemTPL);
	$itemTPL = preg_replace('%@ITEM_END@$%s', '', $itemTPL);
	$TPL = preg_replace('%@ITEM_BEGIN@.+?@ITEM_END@%s', '', $TPL);

	$TPL = preg_replace('%@DESCR@%',	$page['descr'], $TPL);
	$TPL = preg_replace('%@TITLE@%',	substr($page['descr'], 0, 60), $TPL);

	$chave = do_query(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY));
	$TPL = preg_replace('%@SEARCH@%',	"value='" . ($chave ? $chave : 'Escreva aqui sua busca, n&atilde;o use plural\' onfocus=\'this.value=""') . "'", $TPL);

	$logo = sprintf('src="%s" width="%d" height="%d" alt="%s"', $page['partner']['logo']['src'], $page['partner']['logo']['width'], $page['partner']['logo']['height'], $page['partner']['logo']['alt'] ? $page['partner']['logo']['alt'] : '');
	$TPL = preg_replace('%@LOGO@%',		$logo, $TPL);

	if (empty($busca)) {
		$busca	= $page['search'];
		$robots	= 'NOINDEX,FOLLOW';
	} else {
		$robots = 'INDEX,FOLLOW';
	}
	$descr		= $page['descr'];

	$TPL = preg_replace('%@RELATED_LINK@%', CLIQUE . $page['partner']['IDML'] . '/' . str_replace(' ', '_', $busca), $TPL);

	$footer		= "<p>\n";
	$tmp = $page['footer']['intern']['link'];
	if (is_string($tmp)) { $tmp = array($tmp); }
	$footer		.= implode('', array_map('externlink', $tmp));
	$footer		.= "\n</p>\n";
	$footer		.= "<p>\n";
	$tmp = $page['footer']['extern']['link'];
	if (is_string($tmp)) { $tmp = array($tmp); }
	$footer		.= implode('', array_map('externlink', $tmp));
	$footer		.= "</p>\n";
	$TPL = preg_replace('%@FOOTER@%',	$footer, $TPL);

	$cols = preg_match_all('%@ITEM_COL@%', $TPL, $tmp);

	$keywords = array();
	$items = $page['items']['item'];
	if (!empty($items['link'])) { $items = array($items); }

	if (count($items)) {
		$COL = array();
		for ($i = 0; $i < $cols; $i++) {
			$COL[$i] = array();
		}
		for ($i = 0; $i < count($items); $i++) {
			array_push($COL[$i % $cols], $items[$i]);
		}
		$i = 0;
		foreach ($COL as $column) {
			$tmp = '';
			foreach ($column as $item) {
				permuta($keywords, implode(' ', array($item['title'],$item['descr'],$item['imgtitle'],$item['similar'])));

				$item_descr = $item['descr'];
				/*
				if ($i < 3) {
					$item_descr = '<h1>' . $item['descr'] . '</h1>';
				} elseif ($i < 8) {
					$item_descr = '<h2>' . $item['descr'] . '</h2>';
				}
				*/
				++$i;

				$itemHTML = $itemTPL;
				$itemHTML = preg_replace('%@ITEM_TITLE@%',	$item['title'], $itemHTML);
				$itemHTML = preg_replace('%@ITEM_DESCR@%',	$item_descr, $itemHTML);
				$itemHTML = preg_replace('%@ITEM_IMG@%',	$page['partner']['store'] . '/' . IMAGEM . '/' . $item['img'], $itemHTML);
				$itemHTML = preg_replace('%@ITEM_ALT@%',	$item['imgtitle'], $itemHTML);
				$itemHTML = preg_replace('%@ITEM_LINK@%',	CLIQUE . $page['partner']['IDML'] . '/' . $item['link'], $itemHTML);
				$itemHTML = preg_replace('%@ITEM_PRICE@%',	$item['price'], $itemHTML);
				$itemHTML = preg_replace('%@ITEM_SIMILAR@%',CLIQUE . $page['partner']['IDML'] . '/' . $item['similar'], $itemHTML);

				$tmp .= $itemHTML;
			}
			$TPL = preg_replace('%@ITEM_COL@%',	$tmp, $TPL, 1);
		}

		arsort($keywords, SORT_NUMERIC);
		$keywords = array_keys($keywords);
		array_splice($keywords, 9);
		array_push($keywords, 'comprar');
		$TPL = preg_replace('%@KEYWORDS@%',	implode(', ', $keywords), $TPL);
	} else {
		@unlink($cached_file);
		$TPL = preg_replace('%@ITEM_COL@%',	'Nenhum produto encontrado', $TPL, 1);
		$TPL = preg_replace('%@ITEM_COL@%',	'', $TPL);
		$robots = 'NOINDEX,FOLLOW';
	}

	
	$TPL = preg_replace('%@SEARCH_TEXT@%', $busca, $TPL);

	/*
	$TPL = preg_replace('%<p\s+class="item_texto">(.+?)</p>%s', '<h1>$1</h1>', $TPL, 3);
	$TPL = preg_replace('%<p\s+class="item_texto">(.+?)</p>%s', '<h2>$1</h2>', $TPL, 5);
	*/

	$TPL = preg_replace('%@ROBOTS@%',	$robots, $TPL);

	$TPL = preg_replace('%@ADSENSE@%',	$page['partner']['adsense'], $TPL);
	$TPL = preg_replace('%@ANALYTICS@%',$page['partner']['analytics'] ? $page['partner']['analytics'] : 0, $TPL);
	$TPL = preg_replace('%@IDML@%',		$page['partner']['IDML'], $TPL);
	$TPL = preg_replace('%@LOJA@%',		$page['partner']['store'], $TPL);

	echo $TPL;
}

exit();


function rmdir_r($directory, $age) {
	if (function_exists('set_time_limit')) {
		set_time_limit(0);
	}

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

function mkdir_chmod($pathname, $mode) {
	@mkdir($pathname, $mode);
	@chmod($pathname, $mode);
}

function mkdir_recursive($pathname, $mode) {
    is_dir(dirname($pathname)) || mkdir_recursive(dirname($pathname), $mode);
    return is_dir($pathname) || mkdir_chmod($pathname, $mode);
}

function cached() {
	global $busca, $filtro;

	$cached = array();
	array_push($cached, CACHE);
	array_push($cached, $filtro);
	for ($i = 1; $i <= 3; $i++) {
		$tree = substr($busca, 0, $i);
		array_push($cached, $tree);
	}
	array_push($cached, $busca);
	return implode(DIRECTORY_SEPARATOR, $cached);
}

function normalize($str, $human = false) {
	$str = urldecode($str);
	$str = utf8_decode($str);
	$str = html_entity_decode($str);
	$str = strtolower($str);

	$str = strtr($str, "ºª`´ÇçÑñÃÕãõÂÊÎÔÛâêîôûÀÈÌÒÙàèìòùÁÉÍÓÚáéíóúÄËÏÖÜäëïöü", "oa''ccnnaoaoaeiouaeiouaeiouaeiouaeiouaeiouaeiouaeiou");

	if ($human) {
		$str = preg_replace('%\W+%', ' ', $str);
	}

	return $str;
}

function fix_header($filename) {
	preg_match('%\.[\w]+?$%', basename($filename), $type);
	switch ($type[0]) {
		case '.css':	header('Content-Type: text/css; charset=utf-8'); $gzip = true; break;
		case '.js':		header('Content-Type: text/javascript; charset=utf-8'); $gzip = true; break;
		case '.gif':	header('Content-Type: image/gif'); break;
		case '.png':	header('Content-Type: image/png'); break;
		case '.jpg':	header('Content-Type: image/jpeg'); break;
		default:		header('Content-Type: text/plain');
	}
	if ($gzip) {
		@ob_start('ob_gzhandler');
	}
}

function fetch($url, $verbose) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, str_replace(DIRECTORY_SEPARATOR, '/', $url));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
	$buf = curl_exec($ch);
	if ($verbose) {
		echo "[$url]\n";
		if ($buf === false) {
			echo "ERRO: " . curl_error($ch) . "\n";
		} else {
			$info = curl_getinfo($ch);
			echo $info['total_time']." segundos para completar\n";
		}
		echo "\n";
	}
	curl_close($ch);

	return $buf;
}

function externlink($link) {
	global $page, $SUFFIX;

	$word = preg_replace('%^.*/%', '', rtrim($link, '/'));
	$word = str_replace('_', ' ', $word);

	if (!preg_match('%^http://%i', $link)) {
		$link = $page['partner']['store'] . '/' . $link;
	}

	$any = rand(0, count($SUFFIX));
	$sfx = $SUFFIX[$any];

	return "<a href=\"$link/$sfx\">$word</a>\n";
}

function permuta(&$keywords, $input) {
	$window = array();
	foreach (preg_split('%\W+%', $input, -1, PREG_SPLIT_NO_EMPTY) as $word) {
		if (strlen($word) >= 3) {
			array_push($window, $word);
			if (count($window) > 3) {
				array_shift($window);
			}
			for ($i = 1; $i <= count($window); $i++) {
				$tmp = $window;
				array_splice($tmp, $i);
				++$keywords[implode(' ', $tmp)];
			}
		}
	}
}

function do_query($query) {
	$chave = array();
	foreach (explode('&', $query) as $pair) {
		$x = explode('=', $pair);
		if (in_array($x[0],
			array(
				'q','query','qry','keyword','search','keywords','key','s','Keywords',
				'qkw','word','qt','k','kw','words','searchfor','w','qs','ask','Terms',
				'srchText','qq','term','terms','KERESES','qr','string','qry_str','busca',
				'KEYWORDS','str','begriff','text','keys','mt','p','querytext','MT','Q',
				'su','search_query','qu','QueryString'
			))) {
			array_push($chave, normalize($x[1], true));
		}
	}
	return implode(' ', $chave);
}

function xml2array($contents, $get_attributes=1, $priority = 'tag')	{
	if(!$contents) return array();

	if(!function_exists('xml_parser_create')) {
		//print	"'xml_parser_create()' function	not	found!";
		return array();
	}

	//Get the XML parser of	PHP	- PHP must have	this module	for	the	parser to work
	$parser	= xml_parser_create('');
	xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING,	0);
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct($parser, trim($contents),	$xml_values);
	xml_parser_free($parser);

	if(!$xml_values) return;//Hmm...

	//Initializations
	$xml_array = array();
	$parents = array();
	$opened_tags = array();
	$arr = array();

	$current = &$xml_array;	//Refference

	//Go through the tags.
	$repeated_tag_index	= array();//Multiple tags with same	name will be turned	into an	array
	foreach($xml_values	as $data) {
		unset($attributes,$value);//Remove existing	values,	or there will be trouble

		//This command will	extract	these variables	into the foreach scope
		// tag(string),	type(string), level(int), attributes(array).
		extract($data);//We	could use the array	by itself, but this	cooler.

		$result	= array();
		$attributes_data = array();

		if(isset($value)) {
			if($priority ==	'tag') $result = $value;
			else $result['value'] =	$value;	//Put the value	in a assoc array if	we are in the 'Attribute' mode
		}

		//Set the attributes too.
		if(isset($attributes) and $get_attributes) {
			foreach($attributes	as $attr =>	$val) {
				if($priority ==	'tag') $attributes_data[$attr] = $val;
				else $result['attr'][$attr]	= $val;	//Set all the attributes in	a array	called 'attr'
			}
		}

		//See tag status and do	the	needed.
		if($type ==	"open")	{//The starting	of the tag '<tag>'
			$parent[$level-1] =	&$current;
			if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
				$current[$tag] = $result;
				if($attributes_data) $current[$tag.	'_attr'] = $attributes_data;
				$repeated_tag_index[$tag.'_'.$level] = 1;

				$current = &$current[$tag];

			} else { //There was another element with the same tag name

				if(isset($current[$tag][0])) {//If there is	a 0th element it is	already	an array
					$current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
					$repeated_tag_index[$tag.'_'.$level]++;
				} else {//This section will	make the value an array	if multiple	tags with the same name	appear together
					$current[$tag] = array($current[$tag],$result);//This will combine the existing	item and the new item together to make an array
					$repeated_tag_index[$tag.'_'.$level] = 2;

					if(isset($current[$tag.'_attr'])) {	//The attribute	of the last(0th) tag must be moved as well
						$current[$tag]['0_attr'] = $current[$tag.'_attr'];
						unset($current[$tag.'_attr']);
					}

				}
				$last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
				$current = &$current[$tag][$last_item_index];
			}

		} elseif($type == "complete") {	//Tags that	ends in	1 line '<tag />'
			//See if the key is	already	taken.
			if(!isset($current[$tag])) { //New Key
				$current[$tag] = $result;
				$repeated_tag_index[$tag.'_'.$level] = 1;
				if($priority ==	'tag' and $attributes_data)	$current[$tag. '_attr']	= $attributes_data;

			} else { //If taken, put all things	inside a list(array)
				if(isset($current[$tag][0])	and	is_array($current[$tag])) {//If	it is already an array...

					// ...push the new element into	that array.
					$current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;

					if($priority ==	'tag' and $get_attributes and $attributes_data)	{
						$current[$tag][$repeated_tag_index[$tag.'_'.$level]	. '_attr'] = $attributes_data;
					}
					$repeated_tag_index[$tag.'_'.$level]++;

				} else { //If it is	not	an array...
					$current[$tag] = array($current[$tag],$result);	//...Make it an	array using	using the existing value and the new value
					$repeated_tag_index[$tag.'_'.$level] = 1;
					if($priority ==	'tag' and $get_attributes) {
						if(isset($current[$tag.'_attr'])) {	//The attribute	of the last(0th) tag must be moved as well

							$current[$tag]['0_attr'] = $current[$tag.'_attr'];
							unset($current[$tag.'_attr']);
						}

						if($attributes_data) {
							$current[$tag][$repeated_tag_index[$tag.'_'.$level]	. '_attr'] = $attributes_data;
						}
					}
					$repeated_tag_index[$tag.'_'.$level]++;	//0	and	1 index	is already taken
				}
			}

		} elseif($type == 'close') { //End of tag '</tag>'
			$current = &$parent[$level-1];
		}
	}

	return($xml_array);
}

?>