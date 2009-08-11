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
	if (empty($num))
		$num = 5;

	if (empty($min))
		$min = 30;

	if (file_exists($file)) {
		$stat = stat($file);
		if ((time() - $stat[9]) <= ($min * 60))
			$html = @file_get_contents($file);
	}

	if (empty($html)) {
		$html = RSS_Display($url, $num);
		if (substr($html, 0, 4) == '<ul>') {
			if (!$is_bot) {
				my_mkdir_recursive(dirname($file), 0777);
				@file_put_contents($file, $html);
			}
		} else {
			$html = @file_get_contents($file);
		}
	}

	return $html;
}

?>