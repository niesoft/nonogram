# nonogram
Генератор японских кроссвордов

Работает только на версии php 8 и выше.

$game = new Nonogram("test.png");

$game->list['top']; - Ячейки по вертикали

$game->list['left']; - Ячейки по горизонтали

$game->fontpath = "путь_до_собственного_шрифта";
