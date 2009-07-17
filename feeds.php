<?php
global $CFG;
global $CPDEFS;
require_once('rsslib.php');

foreach (preg_split('%\r?\n%s', $CFG['FEEDS'], -1, PREG_SPLIT_NO_EMPTY) as $line) {
	if (preg_match('%^(http://.+?)\s+([\w\.\-]+)(?:\s+(\d*)(?:\s+(\d*))?)?%', $line, $p)) {
		if (preg_match('/%BUSCA%/', $p[1])) {
			$p[2] = my_cached($CFG['BUSCA_URL'] . '-' . md5($p[1]) . '.html');
			$p[1] = str_replace('%BUSCA%', urlencode($CFG['BUSCA_STR']), $p[1]);
		} else {
			$p[2] = dirname($CPDEFS) . DIRECTORY_SEPARATOR . 'feeds' . DIRECTORY_SEPARATOR . $p[2];
		}
		echo fetch_rss($p[1], $p[2], $p[3], $p[4], $CFG['IS_BOT']);
	} else {
		echo $line;
	}
	echo "\n";
}

function fetch_rss($url, $file, $num, $min, $is_bot = false) {
	if (empty($num)) {
		$num = 5;
	}

	if (empty($min)) {
		$min = 30;
	}

	if (file_exists($file)) {
		$stat = stat($file);
		if ((time() - $stat[9]) <= ($min * 60)) {
			$html = @file_get_contents($file);
		}
	}

	if (empty($html)) {
		$html = RSS_Display($url, $num);
		if (!$is_bot) {
			my_mkdir_recursive(dirname($file), 0777);
			@file_put_contents($file, $html);
		}
	}

	return $html;
}

?>