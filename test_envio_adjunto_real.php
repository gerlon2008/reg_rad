<?php
/**
 * Script de prueba de envío de correo con PDF adjunto
 * Ejecutar desde el navegador para verificar la recepción del correo en Outlook con el PDF legible.
 */
header('Content-Type: text/html; charset=utf-8');
require_once(__DIR__ . '/enviar_mail_planilla.php');

echo "<h2>Prueba de Envío de Correo con PDF Adjunto a Exchange</h2>";

$carpetas_posibles = array(
    $_SERVER["DOCUMENT_ROOT"] . '/ptr/TCPDF-master/examples/Registros_pdfs',
    __DIR__ . '/Registros_pdfs'
);

$ruta_pdf_prueba = '';
$nombre_pdf_prueba = '';

foreach ($carpetas_posibles as $dir_pdf) {
    if (is_dir($dir_pdf)) {
        $archivos = glob($dir_pdf . '/*.pdf');
        if (!empty($archivos)) {
            $ruta_pdf_prueba = end($archivos); // Tomar el más reciente
            $nombre_pdf_prueba = basename($ruta_pdf_prueba);
            echo "<p>Encontrado PDF real histórico en <b>$dir_pdf</b>: <b>$nombre_pdf_prueba</b></p>";
            break;
        }
    }
}

if (empty($ruta_pdf_prueba)) {
    // Si no se encontró ningún PDF real, generamos uno de prueba con texto visible
    $dir_fallback = __DIR__ . '/Registros_pdfs';
    if (!is_dir($dir_fallback)) {
        @mkdir($dir_fallback, 0777, true);
    }
    $ruta_pdf_prueba = $dir_fallback . '/Prueba_Planilla_Radiologica.pdf';
    $nombre_pdf_prueba = 'Prueba_Planilla_Radiologica.pdf';

    $stream_txt = "BT\n/F1 20 Tf\n50 720 Td\n(NUCLEOELECTRICA ARGENTINA S.A.) Tj\n/F1 14 Tf\n0 -40 Td\n(SISTEMA DE REGISTRO RADIOLOGICO) Tj\n/F1 11 Tf\n0 -30 Td\n(Prueba de envio automatico con adjunto PDF a Exchange.) Tj\n0 -20 Td\n(Fecha de prueba: " . date('d/m/Y H:i:s') . ") Tj\nET";
    $stream_len = strlen($stream_txt);
    $contenido_pdf = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R >> >> /MediaBox [0 0 612 792] /Contents 5 0 R >>\nendobj\n4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n5 0 obj\n<< /Length $stream_len >>\nstream\n$stream_txt\nendstream\nendobj\nxref\n0 6\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000244 00000 n \n0000000318 00000 n \ntrailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n540\n%%EOF";
    file_put_contents($ruta_pdf_prueba, $contenido_pdf);
    echo "<p>Generando archivo de prueba con texto visible: <b>$nombre_pdf_prueba</b></p>";
}

// Datos simulados de la planilla
$datos_ejemplo = array(
    'fecha'         => date('d/m/Y'),
    'horarios'      => '06:00 a 14:00 (Turno Prueba)',
    'of_apellido'   => 'Loncar',
    'of_nombre'     => 'Gerardo',
    'jefe_apellido' => 'Prueba',
    'jefe_nombre'   => 'Jefe Turno'
);

// Usamos un ID ficticio para la prueba (ej: 999999) y borramos el lock si existía de una prueba previa
$id_prueba = 999999;
$lock_prueba = $dir_pdf . '/.mail_enviado_' . $id_prueba . '.lock';
if (file_exists($lock_prueba)) {
    @unlink($lock_prueba);
}

echo "<p>Iniciando envío a través de <code>enviar_planilla_por_correo()</code>...</p>";

$resultado = enviar_planilla_por_correo($id_prueba, $ruta_pdf_prueba, $nombre_pdf_prueba, $datos_ejemplo);

if ($resultado['exito']) {
    echo "<h3 style='color:green;'>¡Éxito! " . htmlspecialchars($resultado['mensaje']) . "</h3>";
    echo "<p>Revisá tu bandeja de entrada en Outlook (<b>gloncar@na-sa.com.ar</b>). Deberías recibir el correo con el diseño institucional y el PDF adjunto.</p>";
} else {
    echo "<h3 style='color:red;'>Error en el envío: " . htmlspecialchars($resultado['mensaje']) . "</h3>";
}
?>
