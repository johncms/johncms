<?php
/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                             Content Management System              //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

session_name('SESID');
session_start();
if (!isset($_SESSION['code']))
    exit;
header("Content-Type: image/gif");

// Задаем размеры изображения
$imwidth = 85;
$imheight = 26;

$im = ImageCreate($imwidth, $imheight);
$background_color = ImageColorAllocate($im, 255, 255, 255);
$text_color = ImageColorAllocate($im, 0, 0, 0);
$border_color = ImageColorAllocate($im, 154, 154, 154);

// Генерируем помехи в виде линий
$g1 = imagecolorallocate($im, 152, 152, 152); // Задаем цвет линий
for ($i = 0; $i <= 100; $i += 6)
    imageline($im, $i, 0, $i, 25, $g1); // Горизонтальные линии
for ($i = 0; $i <= 25; $i += 5)
    imageline($im, 0, $i, 100, $i, $g1); // Вертикальные линии

// Генерируем цифровой код на основе данных сессии
$code = substr($_SESSION["code"], 0, 4);
$x = 0;
$stringlength = strlen($code);
for ($i = 0; $i < $stringlength; $i++)
{
    $x = $x + (rand(8, 21));
    $y = rand(2, 10);
    $font = rand(4, 25);
    $single_char = substr($code, $i, 1);
    imagechar($im, $font, $x, $y, $single_char, $text_color);
}

// Передача изображения в Браузер
ImageGif($im);
ImageDestroy($im);

?>