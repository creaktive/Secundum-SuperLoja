<?php
require_once('rsslib.php');
global $CFG;
define('FEEDS',	'feeds'); 

mkdir_chmod(FEEDS, 0755);

foreach (preg_split('%\r?\n%s', $CFG['FEEDS'], -1, PREG_SPLIT_NO_EMPTY) as $line) {
	if (preg_match('%^(http://.+?)\s+([\w\.\-]+)(?:\s+(\d*)(?:\s+(\d*))?)?%', $line, $p)) {
		echo fetch_rss($p[1], $p[2], $p[3], $p[4]);
	} else {
		echo $line;
	}
	echo "\n";
}

function fetch_rss($url, $file, $num, $min) {
	$file = FEEDS . DIRECTORY_SEPARATOR . $file;
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
		@file_put_contents($file, $html);
	}

	return $html;
}

?>
