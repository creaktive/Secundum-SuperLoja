<?php

error_reporting(0);

define('VERSAO',	'5.1');
define('CLIQUE',	'http://clique.secundum.com.br');

define('ADMIN',		'admin');
define('CLEANUP',	'cleanup');
define('LAYOUT',	'layout');
define('TEMPLATE',	LAYOUT . DIRECTORY_SEPARATOR . 'alldefs.ini');

if (empty($CPDEFS)) {
	$CPDEFS = 'cpdefs.ini';
	$LOCAL = true;
} else {
	$LOCAL = false;
}

define('CACHE',		'cache');
define('CACHE_AUTO',CACHE . DIRECTORY_SEPARATOR . 'auto');
define('CACHE_LOCK',CACHE . DIRECTORY_SEPARATOR . 'lock');

$CFG = parse_ini(TEMPLATE);
$CFG = array_merge($CFG, parse_ini($CPDEFS));

$CFG['BASE'] = dirname(empty($_SERVER['PHP_SELF']) ? $_SERVER['SCRIPT_NAME'] : $_SERVER['PHP_SELF']);
$CFG['BASE'] = str_replace(DIRECTORY_SEPARATOR, '/', $CFG['BASE']);
$CFG['BASE'] = rtrim($CFG['BASE'], '/');
$CFG['LOJA_URL'] = 'http://' . $_SERVER['SERVER_NAME'] . $CFG['BASE'];

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

		$g = tempnam(sys_get_temp_dir(), 'secundum_superloja' . VERSAO);
		@file_put_contents($g, $data);
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
		$data = fetch($file);
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
	if ($_POST['_pwd'] == $CFG['SENHA_ADMIN']) {
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

		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 3.2//EN\">
<html>
<head>
<title>Controle da loja</title>
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
</script>
</head>
<body>
<h1>Controle da loja</h1>
<form method=\"post\" action=\"" . ADMIN . "\" name=\"cpanel\" id=\"cpanel\">
<input type=\"hidden\" value=\"1\" name=\"_save\">
<input type=\"hidden\" value=\"$CFG[SENHA_ADMIN]\" name=\"_pwd\">
<table border=\"0\" summary=\"\">
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

				echo "<tr>
	<td align=\"right\" valign=\"top\"><b>$title</b></td>
	<td>
";
				if ($type[0] == 'a') {
					echo "<textarea name=\"$key\" cols=\"80\" rows=\"20\">$val</textarea>\n";
				} else {
					echo "<input type=\"text\" name=\"$key\" value=\"$val\" size=\"60\" maxlength=\"$len\" $filter>\n";
				}
				echo "</td></tr>\n";
			}
		}

		if ($LOCAL) {
			echo "
<tr>
	<td align=\"right\"><b>Limpar cache:</b></td>
	<td><input type=\"checkbox\" name=\"_cache_reset\"></td>
</tr>";
		}

		echo "
<tr>
	<td align=\"right\"><b>Restaurar configura&ccedil;&otilde;es originais:</b></td>
	<td><input type=\"checkbox\" name=\"_default\"></td>
</tr>
<tr>
	<td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"Salvar\"></td>
</tr>
</table>
</form>
<a href=\"$CFG[LOJA_URL]/\">Ir para a loja V" . VERSAO . "</a>
$CFG[SECUNDUM_CPANEL]
</body></html>
";
	} else {
		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 3.2//EN\">
<html>
<head>
<title>Controle da loja</title>
</head>
<body>
<center>
<h1>Controle da loja</h1>
<form method=\"post\" action=\"" . ADMIN . "\" name=\"cpanel\" id=\"cpanel\">
Senha: <input type=\"password\" name=\"_pwd\">
<input type=\"submit\" value=\"Entrar\">
</form>
</center>
</body>
</html>
";
	}
} elseif ($params[0] == 'clique') {
	header(sprintf('Location: %s/%s', CLIQUE, implode('/', array_slice($params, 1))));
} elseif ($LOCAL && !empty($localpath) && file_exists($localpath) && (substr($localpath, -4) != '.ini')) {
	$data = @file_get_contents($localpath);
	fix_header($localpath);
	echo $data;
} else {
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
	header('Pragma: no-cache');
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

				$tmp = gzdecode($page);
				if (!$tmp) {
					$tmp = $page;
				}
			}
		}

		if (empty($page)) {
			$page = fetch(sprintf('sistema.php?p1=%s&p2=%s', $CFG['LOJA_URL'], $CFG['BUSCA_URL']));

			$tmp = gzdecode($page);
			if (!$tmp) {
				$tmp = $page;
			}

			if (!$tmp || (substr($tmp, 0, 4) != 'SEC5')) {
				echo($page);
				exit();
			} else {
				$CFG['IS_BOT'] = false;
				foreach (explode('|', preg_replace('%\r?\n%s', '', $CFG['BOT_USER_AGENT'])) as $bot) {
					if (preg_match('%' . preg_quote($bot) . '%i', $_SERVER['HTTP_USER_AGENT'])) {
						$CFG['IS_BOT'] = true;
						break;
					}
				}

				if (!empty($cached_file) && !$CFG['IS_BOT']) {
					my_mkdir_recursive(dirname($cached_file), 0777);

					@file_put_contents($cached_file, $page);
					@chmod($cached_file, 0644);
				}
			}
		}

		preg_match('%^SEC5\s+(.+?)\r?\n%s', $tmp, $m);
		if (($TPLstat === false) || !$TPLstat['size'] || ($TPLstat['mtime'] < strtotime($m[1]))) {
			update_template();
		}
		$page = $tmp;
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

function update_template($modif) {
	global $CFG;
	$TPL = fetch(TEMPLATE);
	if (!empty($TPL)) {
		@file_put_contents(TEMPLATE, $TPL);
		@chmod(TEMPLATE, 0644);
		@touch(CACHE_AUTO);

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

function fetch($url) {
	$ips = gethostbynamel('secundum.com.br');
	$url = sprintf('http://%s/sis5/%s', $ips[rand(0, count($ips) - 1)], str_replace(DIRECTORY_SEPARATOR, '/', $url));

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
	$buf = curl_exec($ch);
	curl_close($ch);

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

?>