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

define('VERSAO',	'5.3');
define('CLIQUE',	'http://clique.secundum.com.br/');
define('SISTEMA',	'http://sistema.secundum.com.br/');

define('ADMIN',		'admin');
define('CLEANUP',	'cleanup');
define('HIST',		'hist.dat');
define('LAYOUT',	'layout');
define('TEMPLATE',	LAYOUT . DIRECTORY_SEPARATOR . 'alldefs.ini');

if (empty($CPDEFS)) {
	$CPDEFS = 'cpdefs.ini';
	$LOCAL = true;
} else {
	$LOCAL = false;
}

define('CACHE',			'cache');
define('CACHE_AUTO',	CACHE . DIRECTORY_SEPARATOR . 'auto');
define('CACHE_LOCK',	CACHE . DIRECTORY_SEPARATOR . 'lock');

$CFG = parse_ini(TEMPLATE);
$CFG = array_merge($CFG, parse_ini($CPDEFS));

setlocale(LC_ALL, $CFG['LOCALE']);

$CFG['BASE'] = dirname(empty($_SERVER['PHP_SELF']) ? $_SERVER['SCRIPT_NAME'] : $_SERVER['PHP_SELF']);
$CFG['BASE'] = str_replace(DIRECTORY_SEPARATOR, '/', $CFG['BASE']);
$CFG['BASE'] = rtrim($CFG['BASE'], '/');
$CFG['LOJA_URL'] = 'http://' . $_SERVER['SERVER_NAME'] . $CFG['BASE'];

$CFG['IS_BOT'] = false;
foreach (explode('|', preg_replace('%\r?\n%s', '', $CFG['BOT_USER_AGENT'])) as $bot) {
	if (preg_match('%' . preg_quote($bot) . '%i', $_SERVER['HTTP_USER_AGENT'])) {
		$CFG['IS_BOT'] = true;
		break;
	}
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
	if (function_exists('readgzfile')) {
		function gzdecode($data) {
			$len = strlen($data);
			if ($len < 18 || strcmp(substr($data,0,2),"\x1f\x8b"))
				return null;	// Not GZIP format (See RFC 1952)

			$g = tempnam(sys_get_temp_dir(), 'secundum_superloja' . VERSAO);
			@file_put_contents($g, $data);
			ob_start();
			readgzfile($g);
			@unlink($g);
			$d = ob_get_clean();
			return $d;
		}
	}
}
if (!function_exists('sys_get_temp_dir')) {
	function sys_get_temp_dir() {
		if (!empty($_ENV['TMP']))		{ return realpath($_ENV['TMP']); }
		if (!empty($_ENV['TMPDIR']))	{ return realpath($_ENV['TMPDIR']); }
		if (!empty($_ENV['TEMP']))		{ return realpath($_ENV['TEMP']); }
		$tempfile = tempnam(uniqid(rand(), TRUE), '');
		if (file_exists($tempfile)) {
			@unlink($tempfile);
			return realpath(dirname($tempfile));
		}
	}
}

if ($query = $_GET['query']) {
	if (preg_match('%busca=(.+)%', $query, $q)) {
		header('Location: ' . $CFG['LOJA_URL'] . '/' . preg_replace('%\s+%', '-', $q[1]));
	} else {
		header('Location: ' . $CFG['LOJA_URL'] . '/');
	}
	exit();
}

$params = array();
foreach (preg_split('%/%', normalize($_GET['uri']), -1, PREG_SPLIT_NO_EMPTY) as $param) {
	array_push($params, preg_replace('%^\.+%', '', $param));
}
$localpath = implode(DIRECTORY_SEPARATOR, $params);

if ($LOCAL) {
	my_mkdir_chmod(LAYOUT, 0755);
	$TPLstat = @stat(TEMPLATE);
}

if ($LOCAL && ($params[0] == LAYOUT) && !empty($params[1])) {
	$file = LAYOUT . DIRECTORY_SEPARATOR . $params[1];
	$filestat = @stat($file);

	if (($TPLstat !== false) && ($filestat !== false) && ($filestat['size'] > 0) && ($TPLstat['mtime'] <= $filestat['mtime'])) {
		$data = @file_get_contents($file);
	} else {
		$data = secundum_fetch(SISTEMA . $file);
		if (!empty($data)) {
			@file_put_contents($file, $data);
			@chmod($file, 0644);
		}
	}

	fix_header($params[1]);
	echo $data;
} elseif ($LOCAL && ($params[0] == CLEANUP)) {
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
			rmdir_r(CACHE, $CFG['CACHE_DIAS'] * 3600 * 24);
		}
	}
} elseif ($params[0] == ADMIN) {
	@ob_start('ob_gzhandler');
	session_start();

	$now = time();

	if ((!empty($_SESSION['START']) && is_numeric($_SESSION['START']) && ($now - $_SESSION['START'] < 1800)) || ($_POST['_pwd'] == $CFG['SENHA_ADMIN'])) {
		$_SESSION['START']		= $now;
		$_SESSION['LOJA_URL']	= $CFG['LOJA_URL'];

		if ($_POST['_save']) {
			$ch = @fopen($CPDEFS, 'w');
			foreach ($_POST as $key => $val) {
				if ($key[0] != '_') {
					if (!(($_POST['_default'] == 'on') && !in_array($key, explode('|', preg_replace('%\r?\n%s', '', $CFG['CUSTOM']))))) {
						$buf = utf8_decode($_POST[$key]);
						$buf = preg_replace('%\\\"%', '"', $buf);
						$buf = preg_replace("%\\\'%", "'", $buf);
						fwrite($ch, sprintf("[%s]\r\n%s\r\n\r\n", $key, $buf));
						$CFG[$key] = $buf;
					} else {
						unset($CFG[$key]);
					}
				}
			}
			fclose($ch);
		}

		if ($LOCAL && ($_POST['_cache_reset'] == 'on')) {
			rmdir_r(CACHE, -1);
		}

		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\">
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
<title>$CFG[ADMIN_TITULO]</title>
<script type=\"text/javascript\" src=\"controle/swfobject.js\"></script>
<script type=\"text/javascript\">
function fnum(x) {
	return x.replace(/\D/g, '');
}
function furl(x) {
	if (x.search(/^http:\/\//) == -1) {
		x = 'http://' + x;
	}
	x = x.replace(/\/+$/, '');
	x = x.replace(/\s/g, '');
	return x;
}

swfobject.embedSWF(
\"controle/open-flash-chart.swf\", \"stats\",
\"635\", \"500\", \"9.0.0\", \"controle/expressInstall.swf\",
{\"data-file\":\"chart.php\",\"loading\":\"Carregando...\"} );
</script>
<script language=\"javascript\" type=\"text/javascript\" src=\"controle/niceforms.js\"></script>
<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"controle/niceforms.css\" />
<style type=\"text/css\"> 
.sunken {
border-color: #666661 #FFFFFF #FFFFFF #666661;
border-style: solid;
border-width: 1px;
width: 635px;
height: 500px;
margin: 0px;
padding: 0px;
}
</style>
</head>
<body>
<div id=\"container\">
<form method=\"post\" action=\"" . ADMIN . "\" name=\"cpanel\" id=\"cpanel\" class=\"niceform\">
<fieldset>
<legend>$CFG[ADMIN_TITULO]</legend>
<input type=\"hidden\" value=\"1\" name=\"_save\" />
<input type=\"hidden\" value=\"$CFG[SENHA_ADMIN]\" name=\"_pwd\" />
";
		
		if ($LOCAL) {
			update_template();
		}

		foreach ($CFG as $key => $title) {
			if (substr($key, 0, 3) == 'CP_') {
				if ($end = strpos($key, '.')) {
					$type = substr($key, $end + 1);
					$key = substr($key, 3, $end - 3);
				} else {
					$type = 's';
					$key = substr($key, 3);
				}

				$len = substr($type, 1);
				if (!$len) {
					$len = 50;
				}

				switch ($type[0]) {
					case 'n':
						$filter = 'onkeyup="this.value=fnum(this.value);"';
						$len = 12;
						break;
					case 'u':
						$filter = 'onkeyup="this.value=furl(this.value);"';
						break;
					default:
						$filter = '';
				}

				$val = htmlentities($CFG[$key]);

				echo "<dl>
<dt><label for=\"$key\">$title</label></dt>
<dd>
";
				if ($type[0] == 'a') {
					echo "<textarea name=\"$key\" cols=\"80\" rows=\"20\">$val</textarea>\n";
				} else {
					echo "<input type=\"text\" name=\"$key\" value=\"$val\" size=\"60\" maxlength=\"$len\" $filter />\n";
				}
				echo "</dd></dl>\n";
			}
		}

		echo "
</fieldset>
<fieldset>
<legend>$CFG[ADMIN_ABA]</legend>
";

		if ($LOCAL) {
			echo "
<dl>
<dt><label for=\"_cache_reset\">$CFG[ADMIN_CACHE]</label></dt>
<dd><input type=\"checkbox\" name=\"_cache_reset\" /></dd>
</dl>";
		}

		$google	= floor(googleidx($CFG['LOJA_URL']) / 1000) . 'k';
		$end	= hist(false, 1);
		$begin	= hist(false, 2);

		$since	= utf8_encode(strftime($CFG['STRFTIME'], $begin[2]));

		$update = '';
		if (VERSAO != $CFG['UPDATE']) {
			$update = $CFG['ADMIN_DOWNLOAD'];
		}

		echo "
<dl>
<dt><label for=\"_default\">$CFG[ADMIN_RESTAURA]</label></dt>
<dd><input type=\"checkbox\" name=\"_default\" /></dd>
</dl>
</fieldset>
<fieldset class=\"action\">
<input type=\"submit\" value=\"$CFG[ADMIN_SALVA]\" />
<button type=\"button\" onclick=\"location.href='$CFG[LOJA_URL]/'\">$CFG[ADMIN_ROOT]" . VERSAO . "</button>
$update
</fieldset>
</form>
<fieldset>
<legend>$CFG[ADMIN_STATS]</legend>
<div class=\"sunken\">
<div id=\"stats\"></div>
</div>
<dl>
<dt><label>$CFG[ADMIN_ONLINE]</label></dt>
<dd>$since</dd>
</dl>
<dl>
<dt><label>$CFG[ADMIN_INDEX]</label></dt>
<dd>$google</dd>
</dl>
<dl>
<dt><label>$CFG[ADMIN_HITS]</label></dt>
<dd>$end[0]</dd>
</dl>
<dl>
<dt><label>$CFG[ADMIN_CLICKS]</label></dt>
<dd>$end[1]</dd>
</dl>
$CFG[ADMIN_OBS]
</fieldset>
$CFG[SECUNDUM_CPANEL]
</div>
</body>
</html>
";
	} else {
		foreach ($CFG as $k => $v) {
			if (substr($k, 0, 14) == 'CP_SENHA_ADMIN') {
				$senha = $v;
				break;
			}
		}

		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\">
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
<title>$CFG[ADMIN_TITULO]</title>
<script language=\"javascript\" type=\"text/javascript\" src=\"controle/niceforms.js\"></script>
<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"controle/niceforms.css\" />
</head>
<body>
<div id=\"container\">
<form method=\"post\" action=\"" . ADMIN . "\" name=\"cpanel\" id=\"cpanel\" class=\"niceform\">
<fieldset>
<legend>$CFG[ADMIN_TITULO]</legend>
<dl>
<dt><label for=\"_pwd\">$senha</label></dt>
<dd><input type=\"password\" name=\"_pwd\" /><input type=\"submit\" value=\"Login\" /></dd>
</dl>
</fieldset>
</form>
</div>
</body>
</html>
";
	}
} elseif ($params[0] == 'clique') {
	hist(true);

	header(sprintf('Location: %s%s', CLIQUE, implode('/', array_slice($params, 1))));
} elseif ($LOCAL && !empty($localpath) && file_exists($localpath) && (substr($localpath, -4) != '.ini')) {
	$data = @file_get_contents($localpath);
	fix_header($localpath);
	echo $data;
} else {
	hist();

	/*
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
	header('Pragma: no-cache');
	*/
	header('Content-Type: text/html; charset=utf-8');
	@ob_start('ob_gzhandler');

	$CFG['BUSCA_URL'] = $params[0];
	if (empty($CFG['BUSCA_URL']) && $CFG['DEFAULT_SEARCH']) {
		$CFG['BUSCA_URL'] = preg_replace('%\s+%s', '-', strtolower($CFG['DEFAULT_SEARCH']));
	}
	$CFG['BUSCA_STR'] = str_replace('-', ' ', $CFG['BUSCA_URL']);

	if ($LOCAL) {
		if (!empty($CFG['BUSCA_URL'])) {
			$cached_file = my_cached($CFG['BUSCA_URL']);
			if (file_exists($cached_file)) {
				$page = @file_get_contents($cached_file);
				if ($tmp = gzdecode($page))
					$page = $tmp;
			}
		}

		if (empty($page)) {
			$page = secundum_fetch(SISTEMA . sprintf('sistema.php?p1=%s&p2=%s', $CFG['LOJA_URL'], $CFG['BUSCA_URL']));

			if (substr($page, 0, 4) != 'SEC5') {
				echo($page);
				exit();
			} else {
				if (!empty($cached_file) && !$CFG['IS_BOT']) {
					my_mkdir_recursive(dirname($cached_file), 0777);

					@file_put_contents($cached_file, $page);
					@chmod($cached_file, 0644);
				}
			}
		}

		preg_match('%^SEC5\s+(.+?)\r?\n%s', $page, $m);
		if (($TPLstat === false) || !$TPLstat['size'] || ($TPLstat['mtime'] < strtotime($m[1]))) {
			update_template(true);
		}
	} else {
		include_once 'busca.php';
		$page = busca($CFG['LOJA_URL'], $CFG['BUSCA_URL']);
	}
	$page = preg_replace('%^.*?(\r?\n)+%', '', $page);

	foreach ($CFG as $key => $val) {
		$buf = '';
		foreach (preg_split('%\r?\n%s', $val, -1, PREG_SPLIT_NO_EMPTY) as $line) {
			if (preg_match('%^\@include\s+([\w\.\-]+)%i', $line, $inc)) {
				$buf .= get_include_contents($inc[1]);
			} else {
				$buf .= $line;
			}
			$buf .= "\n";
		}
		$page = str_replace("%${key}%", $buf, $page);
	}
	$page = preg_replace('%="(.+?)"%eis', '"=\"" . condense("\1") . "\""', $page);

	echo $page;
}

exit();


function condense($str) {
	return preg_replace('%\r?\n%', '', $str);
}

function normalize($str, $human = false) {
	$str = urldecode($str);
	$str = utf8_decode($str);
	$str = html_entity_decode($str);
	$str = strtolower($str);

	$str = strtr($str, "∫™`¥«Á—Ò√’„ı¬ Œ‘€‚ÍÓÙ˚¿»Ã“Ÿ‡ËÏÚ˘¡…Õ”⁄·ÈÌÛ˙ƒÀœ÷‹‰ÎÔˆ¸", "oa''ccnnaoaoaeiouaeiouaeiouaeiouaeiouaeiouaeiouaeiou");

	if ($human) {
		$str = preg_replace('%\W+%', ' ', $str);
	}

	return $str;
}

function parse_ini($file, $trim = true) {
	$array = array();
	if ($dh = @fopen($file, 'r')) {
		while (!feof($dh)) {
			$line = fgets($dh, 4096);

			if (preg_match('%^\[([\w\.]+)\]\s+$%', $line, $match)) {
				if (!empty($key) && $trim) {
					$array[$key] = trim($array[$key]);
				}

				$key = $match[1];
				$array[$key] = '';
			} elseif (!preg_match('%^\s+$%', $line)) {
				$array[$key] .= $line;
			}
		}
		if (!empty($key) && $trim) {
			$array[$key] = trim($array[$key]);
		}
		fclose($dh);
	}
	return $array;
}

function update_template($cache = false) {
	global $CFG;
	$TPL = secundum_fetch(SISTEMA . TEMPLATE);
	if (!empty($TPL)) {
		@file_put_contents(TEMPLATE, $TPL);
		@chmod(TEMPLATE, 0644);

		if ($cache) {
			@touch(CACHE_AUTO);
		}

		$CFG = array_merge(parse_ini(TEMPLATE), $CFG);
	}
}

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

function my_mkdir_chmod($pathname, $mode) {
	@mkdir($pathname, $mode);
	@chmod($pathname, $mode);
}

function my_mkdir_recursive($pathname, $mode) {
    is_dir(dirname($pathname)) || my_mkdir_recursive(dirname($pathname), $mode);
    return is_dir($pathname) || my_mkdir_chmod($pathname, $mode);
}

function my_cached($busca) {
	$cached = array();
	array_push($cached, CACHE);
	for ($i = 1; $i <= 3; $i++) {
		$tree = substr($busca, 0, $i);
		array_push($cached, $tree);
	}
	array_push($cached, $busca);
	return implode(DIRECTORY_SEPARATOR, $cached);
}

function secundum_fetch($url, $ref = '') {
	$gzip		= function_exists('gzdecode');

	for ($try = 0; $try < 5; $try++) {
		$p			= parse_url($url);
		$host		= $p['host'];
		$port		= empty($p['port']) ? 80 : $p['port'];
		$file		= str_replace(DIRECTORY_SEPARATOR, '/', $p['path']) . (empty($p['query']) ? '' : '?' . $p['query']);
		$ips		= gethostbynamel($host);

		$req		= "GET $file HTTP/1.0\r\n";
		$req		.= "Host: $host\r\n";
		$req		.= 'User-Agent: secundum superloja v' . VERSAO . "\r\n";
		if ($gzip)
			$req	.= "Accept-Encoding: gzip\r\n";
		if ($ref)
			$req	.= "Referer: $ref\r\n";
		$req		.= "\r\n";

		$res		= '';
		$hdr		= array();
		$buf		= '';

		if (false != ($fs = @fsockopen($ips[rand(0, count($ips) - 1)], 80, $errno, $errstr, 15))) {
			fwrite($fs, $req);
			while (!feof($fs) && (strlen($res) < 0x19000))	// 100 KB limit
				$res .= fgets($fs, 1160);					// One TCP-IP packet
			fclose($fs);

			list($tmp, $res) = preg_split('%\r?\n\r?\n%', $res, 2);

			$tmp = preg_split('%\r?\n%', $tmp, -1, PREG_SPLIT_NO_EMPTY);
			$cod = array_shift($tmp);
			if (preg_match('%^HTTP/(1\.[01])\s+([0-9]{3})\s+(.+)$%i', $cod, $match)) {
				$cod = $match[2];

				foreach ($tmp as $line)
					if (preg_match('%^([A-Z-a-z\-]+):\s*(.+)$%', $line, $match))
						$hdr[strtolower($match[1])] = $match[2];

				if ($cod == 200) {
					$buf = ($gzip && ($hdr['content-encoding'] == 'gzip')) ? gzdecode($res) : $res;
					break;
				} else if (($cod >= 301) && ($cod <= 303) && !empty($hdr['location']))
					$url = $hdr['location'];
			}
		}
	}

	return $buf;
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

function get_include_contents($filename) {
	if (is_file($filename)) {
		ob_start();
		include $filename;
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}
	return false;
}

function hist($is_click = false, $mode = 0) {
	$now	= ceil(time() / 3600) * 3600;
	$since	= $now;
	$pgs	= 0;
	$clk	= 0;

	@touch(HIST);

	// waiting until file will be locked for writing (1000 milliseconds as timeout)
	if ($fp = fopen(HIST, $mode ? 'rb' : 'r+b')) {
		$startTime = microtime();
		do {
			$canWrite = flock($fp, LOCK_EX);
			// If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
			if (!$canWrite) {
				usleep(round(rand(0, 100) * 1000));
			}
		} while (!$canWrite && ((microtime() - $startTime) < 1000));

		// file was locked so now we can store information
		if ($canWrite) {
			if ($mode != 2) {
				fseek($fp, -12, SEEK_END);
			}

			$buf = fread($fp, 12);
			if ($buf) {
				$row = unpack('Nstamp/Npgs/Nclk', $buf);

				if ($row['stamp'] == $now) {
					fseek($fp, -12, SEEK_END);
				} else {
					$since = $row['stamp'];
					fseek($fp, 0, SEEK_END);
				}

				$pgs = $row['pgs'];
				$clk = $row['clk'];
			}

			if ($mode == 0) {
				if ($is_click) {
					++$clk;
				} else {
					++$pgs;
				}

				fwrite($fp, pack('N*', $now, $pgs, $clk));
			}
		}

		fclose($fp);
	}

	return array($pgs, $clk, $since);
}

function googleidx($site) {
	$buf = secundum_fetch("http://www.google.com/search?safe=off&num=1&q=site:$site", "http://$site/");

	$site = preg_quote($site);
	if (preg_match('%(aproximadamente|about) <b>([0-9,\.]+)</b> (de|from)%is', $buf, $m)) {
		return preg_replace('%[^0-9]%', '', $m[2]);
	} else {
		return 0;
	}
}

?>