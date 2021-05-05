<?php

require_once("nonograms.php");

if (isset($argv[1]) && file_exists($argv[1])) {
	$game = new Nonogram($argv[1], 190); // 190 в данном случае определяет пород входа для темного пикселя
	$game->draw("{$argv[1]}_result.png", 24, true);
}
