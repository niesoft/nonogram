<?php

// new Nonogram(
// 	string $filename, - Файл для преобразования в кроссворд
// 	private int $limit = 254 - Порог определения черного пикселя, от 0 до 254
// )
$game = new Nonogram("test.png");

print_r($game->list);
// draw(
// 	string $filename, Куда сохранить кросворд
// 	int $cellsize = 28, Размер одной ячейки сетки в px
// 	bool $test = false, Сохранить с закрашенными ячейками
// )
$game->draw("result.png", 24, true);

// http://niesoft.ru

Class Nonogram {

	private GdImage $img;
	private array $size = ['width' => 0, 'height' => 0];
	public string $fontpath = './vera.ttf';
	public array $color = [];
	public array $list = [];

	public function __construct(string $filename, private int $limit = 254)
	{
		$this->img = imagecreatefrompng($filename);
		[$this->size['width'], $this->size['height']] = getimagesize($filename);
		$this->color = [
			'foreground' => imagecolorallocate($this->img, 0, 0, 0),
			'background' => imagecolorallocate($this->img, 255, 255, 255)
		];
		$this->list = [
			'left' => $this->get_gorizontal(),
			'top' => $this->get_vertical()
		];
	}

	public function __destruct()
	{
		imagedestroy($this->img);
	}

// Получем координаты клеток по оси X
	private function get_gorizontal() : array
	{
		$array = [];
		for ($y=0; $y < $this->size['height']; $y++) {
			$array[$y] = []; $cnt = 0;

			for ($x=0; $x < $this->size['width']; $x++) {
				[$array[$y], $cnt] = $this->append($array[$y], $cnt, $this->is_black($x, $y));
			}
			[$array[$y], $cnt] = $this->append($array[$y], $cnt, false);
		}
		return $array;
	}

// Получем координаты клеток по оси Y
	private function get_vertical() : array
	{
		$array = [];
		for ($x=0; $x < $this->size['width']; $x++) {
			$array[$x] = []; $cnt = 0;

			for ($y=0; $y < $this->size['height']; $y++) {
				[$array[$x], $cnt] = $this->append($array[$x], $cnt, $this->is_black($x, $y));
			}
			[$array[$x], $cnt] = $this->append($array[$x], $cnt, false);
		}
		return $array;
	}

// Проверяет цвет пикселя и в зависисмости от limit указывает закрашен пиксель или нет
	private function is_black(int $x, int $y) : bool
	{
		$rgb = imagecolorsforindex($this->img, imagecolorat($this->img, $x, $y));
		return (array_sum($rgb) / 3 <= $this->limit) ? true : false;
	}

// Рисуем сам кросворд
	public function draw(string $filename, int $cellsize = 28, bool $test = false) : bool
	{
		$img_top = $this->draw_top($cellsize);
		$img_left = $this->draw_left($cellsize);
		$img_cells = $this->draw_cells($cellsize);

		$size = [
			'width' => imagesx($img_left) + imagesx($img_cells),
			'height' => imagesy($img_top) + imagesy($img_cells),
		];

		$img = imagecreatetruecolor ($size['width'], $size['height']);
		imagefill($img, 0, 0, $this->color['background']);

		if ($test) {
			$this->draw_test($img_cells, $cellsize);
		}

		imagecopy ($img, $img_top, $size['width'] - imagesx($img_top), 0, 0, 0, imagesx($img_top), imagesy($img_top));
		imagecopy ($img, $img_left, 0, imagesy($img_top), 0, 0, imagesx($img_left), imagesy($img_left));
		imagecopy ($img, $img_cells, imagesx($img_left), imagesy($img_top), 0, 0, imagesx($img_cells), imagesy($img_cells));

		imagepng($img, $filename);
		imagedestroy($img);
		imagedestroy($img_top);
		imagedestroy($img_left);
		imagedestroy($img_cells);

		return true;

	}

// Рисуем разгаданный кросворд
	private function draw_test(GdImage $img, int $cellsize) : GdImage
	{
		$left = 0;
		for ($x=0; $x < $this->size['width']; $x++) {
			$top = 0;
			if ($x % 5 == 0 && $x != 0 && $x != $this->size['width']) $left += 1;
			for ($y=0; $y < $this->size['height']; $y++) {
				if ($y % 5 == 0 && $y != 0 && $y != $this->size['height']) $top += 1;
				if ($this->is_black($x, $y)) {
					imagefilledrectangle($img, $left, $top, $left + $cellsize, $top + $cellsize, $this->color['foreground']);
				}
				$top += $cellsize;
			}
			$left += $cellsize;
		}
		return $img;
	}


// Рисуем верхние аннотации
	private function draw_top(int $cellsize) : GdImage
	{
		$list = $this->list['top'];
		$maximumcell = $this->get_maximux_cell($list);
		$size = [
			'width' => $this->size['width'] * $cellsize + floor($this->size['width'] / 5),
			'height' => $maximumcell * $cellsize
		];
		$img = imagecreatetruecolor ($size['width'], $size['height']);
		imagefill($img, 0, 0, $this->color['background']);
		imagesetthickness($img, 1);

		$left = 0;
		$fontsize = floor($cellsize / 2);
		foreach ($list as $key => $value) {
			$top = $size['height'];
			if ($key % 5 == 0 && $key != 0 && $key != $this->size['width']) $left += 1;
			foreach (array_reverse($value) as $cells) {
				imagerectangle($img, $left, $top - $cellsize, $left + $cellsize, $top, $this->color['foreground']);
				$box = imagettfbbox($fontsize, 0, $this->fontpath, $cells);
				imagettftext($img, $fontsize, 0, round(($left + $cellsize / 2) - round(($box[2]-$box[0])/2)), round($top - (($cellsize - $fontsize) / 2) + 1), $this->color['foreground'], $this->fontpath, $cells);
				$top -= $cellsize;
			}
			$left += $cellsize;
		}
		return $img;
	}

// Рисуем аннотации слева
	private function draw_left(int $cellsize) : GdImage
	{
		$list = $list = $this->list['left'];
		$maximumcell = $this->get_maximux_cell($list);
		$size = [
			'width' => $maximumcell * $cellsize,
			'height' => $this->size['height'] * $cellsize + floor($this->size['height'] / 5)
		];
		$img = imagecreatetruecolor ($size['width'], $size['height']);
		imagefill($img, 0, 0, $this->color['background']);
		imagesetthickness($img, 1);

		$top = 0;
		$fontsize = floor($cellsize / 2);
		foreach ($list as $key => $value) {
			$left = $size['width'];
			if ($key % 5 == 0 && $key != 0 && $key != $this->size['height']) $top += 1;
			foreach (array_reverse($value) as $cells) {
				imagerectangle($img, $left - $cellsize, $top, $left, $top + $cellsize, $this->color['foreground']);
				$box = imagettfbbox($fontsize, 0, $this->fontpath, $cells);
				imagettftext($img, $fontsize, 0, ($left - $cellsize / 2) - round(($box[2]-$box[0])/2), round($top + (($cellsize + $fontsize) / 2) + 1), $this->color['foreground'], $this->fontpath, $cells);
				$left -= $cellsize;
			}
			$top += $cellsize;
		}
		return $img;
	}

// Рисуем сетку
	private function draw_cells(int $cellsize) : GdImage
	{
		$size = [
			'width' => $this->size['width'] * $cellsize + floor($this->size['width'] / 5),
			'height' => $this->size['height'] * $cellsize + floor($this->size['height'] / 5)
		];
		$img = imagecreatetruecolor ($size['width'], $size['height']);
		imagefill($img, 0, 0, $this->color['background']);
		$left = $top = 0;
		for ($x=0; $x <= $this->size['width']; $x++) {
			[$left, $weight] = ($x % 5 == 0 && $x != 0 && $x != $this->size['width']) ? [$left + 1, 2] : [$left, 1];
			imagesetthickness($img, $weight);
			imageline ($img, $left, 0, $left, $size['height'], $this->color['foreground']);
			$left += $cellsize;
		}
		for ($y=0; $y <= $this->size['height']; $y++) { 
			[$top, $weight] = ($y % 5 == 0 && $y != 0 && $y != $this->size['height']) ? [$top + 1, 2] : [$top, 1];
			imagesetthickness($img, $weight);
			imageline ($img, 0, $top, $size['width'], $top, $this->color['foreground']);
			$top += $cellsize;
		}

		return $img;
	}

// Подсчитываем максимальное кол-во ячеек
	private function get_maximux_cell(array $cells) : int
	{
		$max = 0;
		foreach ($cells as $key => $value) {
			$cnt = count($value);
			$max = ($cnt > $max) ? $cnt : $max;
		}
		return $max;
	}

// Сокращение кода для функций get_vertical и get_gorizontal
	private function append(array $array, int $cnt, bool $black) : array
	{
		if ($black) {
			$cnt++;
		}else if (!empty($cnt)) {
			$array[] = $cnt;
			$cnt = 0;
		}
		return [$array, $cnt];
	}

}

?> 
