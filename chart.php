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

define('HIST',		'hist.dat');
setlocale(LC_ALL,	'pt_BR');

session_start();

if (!empty($_SESSION['START']) && is_numeric($_SESSION['START']) && (time() - $_SESSION['START'] < 1800)) {
	$pgs = 0;
	$clk = 0;
	$plotA = array();
	$plotB = array();
	$max = 0;
	if ($fp = fopen(HIST, 'rb')) {
		while (!feof($fp)) {
			$buf = fread($fp, 12);
			if ($buf) {
				$row = unpack('Nstamp/Npgs/Nclk', $buf);
				$day = strftime('%d %b %Y', $row['stamp']);
				$plotA[$day] += $row['pgs'] - $pgs;
				$plotB[$day] += $row['clk'] - $clk;

				$max = max($plotA[$day], $plotB[$day], $max);

				$pgs = $row['pgs'];
				$clk = $row['clk'];
			}
		}
		fclose($fp);
	}
	$timeline	= implode('","', array_keys($plotA));
	$plotA		= implode(',', array_values($plotA));
	$plotB		= implode(',', array_values($plotB));
	$max		= ceil($max / 1000) * 1000;
}

header('Content-type: text/plain');
@ob_start('ob_gzhandler');

?>
{
	"elements": [
		{
			"type":		"bar_cylinder_outline",
			"text":		"Acessos",
			"colour":	"#0000ff",
			"values":	[<?php echo $plotA ?>]
		},
		{
			"type":		"bar_cylinder_outline",
			"text":		"Cliques",
			"colour":	"#ff0000",
			"values":	[<?php echo $plotB ?>]
		}
	],

	"x_axis":{
		"labels": {
			"labels":	["<?php echo $timeline ?>"],
			"rotate":	270
		}
	},

	"y_axis": {
		"min":	0,
		"max":	<?php echo $max ?>,
		"steps":1000
	}
}
