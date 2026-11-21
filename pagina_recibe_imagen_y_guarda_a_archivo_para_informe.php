<?php
if (!isset($_POST['coche'])) {
    exit;
}

$img = $_POST['coche'];
$img = str_replace('data:image/png;base64,', '', $img);
$img = str_replace(' ', '+', $img);
$fileData = base64_decode($img);

if (!$fileData) {
    exit;
}

// Cargar la imagen directamente desde la memoria
if (function_exists('imagecreatefromstring')) {
    $image = @imagecreatefromstring($fileData);
} else {
    $tmp_png = __DIR__ . '/Graficos/temp_chart.png';
    file_put_contents($tmp_png, $fileData);
    $image = @imagecreatefrompng($tmp_png);
    @unlink($tmp_png);
}

if (!$image) {
    exit;
}

$ancho = imagesx($image);
$alto  = imagesy($image);

// 1. Creamos un lienzo con fondo blanco puro sólido
$tmp = imagecreatetruecolor($ancho, $alto);
$blanco = imagecolorallocate($tmp, 255, 255, 255);
imagefilledrectangle($tmp, 0, 0, $ancho, $alto, $blanco);

// 2. Copiamos el gráfico sobre el fondo blanco
imagealphablending($tmp, true);
imagecopy($tmp, $image, 0, 0, 0, 0, $ancho, $alto);

// 3. Rotamos 90 grados con fondo BLANCO puro
$rotate = imagerotate($tmp, 90, 0xFFFFFF);

// 4. Guardamos en Graficos/graf.jpg (tanto en ruta absoluta como relativa)
$dirGraficos = isset($_SERVER["DOCUMENT_ROOT"]) 
    ? ($_SERVER["DOCUMENT_ROOT"] . '/ptr/registro_rad5/Graficos') 
    : (__DIR__ . '/Graficos');

if (!is_dir($dirGraficos)) {
    @mkdir($dirGraficos, 0777, true);
}

$fileNameAbs = $dirGraficos . '/graf.jpg';
$fileNameRel = __DIR__ . '/Graficos/graf.jpg';

imagejpeg($rotate, $fileNameAbs, 100);
if ($fileNameAbs !== $fileNameRel) {
    @imagejpeg($rotate, $fileNameRel, 100);
}

// 5. Liberar memoria
imagedestroy($tmp);
imagedestroy($image);
imagedestroy($rotate);
?>