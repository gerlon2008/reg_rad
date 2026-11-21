<?php
/**
 * Módulo de envío de correo para Registro Radiológico (Nucleoeléctrica Argentina S.A.)
 * Envío mediante socket directo a Microsoft Exchange 2019 (correo.na-sa.com.ar:25)
 * Compatible con PHP 5.5+ (no requiere OpenSSL ni librerías externas)
 */

function obtener_configuracion_correo($miconex = null) {
    // Valores predeterminados
    $config = array(
        'modo' => 'produccion',
        'destinatarios_produccion' => array('oficiales-rp-cne@na-sa.com.ar', 'ayudantejefedeturno@na-sa.com.ar'),
        'destinatarios_pruebas' => array('gloncar@na-sa.com.ar'),
        'servidor_smtp' => 'correo.na-sa.com.ar',
        'puerto_smtp' => 25,
        'remitente' => 'registro_radiologico@na-sa.com.ar',
        'nombre_remitente' => 'Planilla Registro Radiologico'
    );

    // 1. Cargar desde config_correo.json si existe
    $ruta_json = __DIR__ . '/config_correo.json';
    if (file_exists($ruta_json)) {
        $raw = @file_get_contents($ruta_json);
        if ($raw) {
            $parsed = @json_decode($raw, true);
            if (is_array($parsed)) {
                $config = array_merge($config, $parsed);
            }
        }
    }

    // 2. Si hay conexión a MySQL, intentar cargar de la tabla `regrad_config_correo`
    if ($miconex) {
        $res = @mysql_query("SELECT valor_json FROM `regrad_config_correo` WHERE clave = 'correo_exchange' LIMIT 1", $miconex);
        if ($res && mysql_num_rows($res) > 0) {
            $row = mysql_fetch_assoc($res);
            $db_cfg = @json_decode($row['valor_json'], true);
            if (is_array($db_cfg)) {
                $config = array_merge($config, $db_cfg);
            }
        }
    }

    return $config;
}

function enviar_planilla_por_correo($id_planilla, $ruta_pdf, $nombre_archivo, $datos_planilla = array(), $miconex = null, $pdf_contenido_directo = null, $forzar_reenvio = false) {
    // -------------------------------------------------------------
    // 1. CONFIGURACIÓN DINÁMICA DEL SERVIDOR Y DESTINATARIOS
    // -------------------------------------------------------------
    $cfg = obtener_configuracion_correo($miconex);

    $servidor_smtp = !empty($cfg['servidor_smtp']) ? $cfg['servidor_smtp'] : 'correo.na-sa.com.ar';
    $puerto_smtp   = !empty($cfg['puerto_smtp']) ? intval($cfg['puerto_smtp']) : 25;
    $remitente     = !empty($cfg['remitente']) ? $cfg['remitente'] : 'registro_radiologico@na-sa.com.ar';
    $nombre_de     = !empty($cfg['nombre_remitente']) ? $cfg['nombre_remitente'] : 'Planilla Registro Radiologico';

    $modo = isset($cfg['modo']) ? $cfg['modo'] : 'produccion';
    if ($modo === 'pruebas') {
        $destinatarios = !empty($cfg['destinatarios_pruebas']) ? (array)$cfg['destinatarios_pruebas'] : array('gloncar@na-sa.com.ar');
    } else {
        $destinatarios = !empty($cfg['destinatarios_produccion']) ? (array)$cfg['destinatarios_produccion'] : array('oficiales-rp-cne@na-sa.com.ar', 'ayudantejefedeturno@na-sa.com.ar');
    }

    // -------------------------------------------------------------
    // 2. CONTROL DE DUPLICADOS (Evitar reenviar accidentalmente)
    // -------------------------------------------------------------
    $id_planilla = intval($id_planilla);
    if ($id_planilla <= 0) {
        return array('exito' => false, 'mensaje' => 'ID de planilla inválido');
    }

    $dir_registros = (!empty($ruta_pdf) && is_string($ruta_pdf)) 
        ? dirname($ruta_pdf) 
        : ((isset($_SERVER["DOCUMENT_ROOT"]) && is_dir($_SERVER["DOCUMENT_ROOT"] . '/ptr/registro_rad5/Registros_pdfs')) 
            ? ($_SERVER["DOCUMENT_ROOT"] . '/ptr/registro_rad5/Registros_pdfs') 
            : (__DIR__ . '/Registros_pdfs'));
    if (!is_dir($dir_registros)) {
        @mkdir($dir_registros, 0777, true);
    }
    $archivo_lock = $dir_registros . '/.mail_enviado_' . $id_planilla . '.lock';

    if (!$forzar_reenvio) {
        // A) Control por base de datos (si la conexión está disponible)
        if ($miconex) {
            @mysql_query("CREATE TABLE IF NOT EXISTS `regrad_planillas_enviadas` (
                `id_planilla` INT(11) NOT NULL PRIMARY KEY,
                `fecha_envio` DATETIME NOT NULL,
                `archivo` VARCHAR(255) DEFAULT NULL,
                `destinatarios` TEXT DEFAULT NULL
            )", $miconex);

            $res_check = @mysql_query("SELECT id_planilla FROM `regrad_planillas_enviadas` WHERE id_planilla = $id_planilla LIMIT 1", $miconex);
            if ($res_check && mysql_num_rows($res_check) > 0) {
                return array('exito' => false, 'ya_enviada' => true, 'mensaje' => 'Esta planilla ya fue despachada anteriormente por correo a Exchange (DB).');
            }
        }

        // B) Control por archivo testigo en disco (doble seguridad)
        if (file_exists($archivo_lock)) {
            return array('exito' => false, 'ya_enviada' => true, 'mensaje' => 'Esta planilla ya fue despachada anteriormente por correo (archivo lock).');
        }
    }

    // Obtener el contenido del PDF (directo en memoria o desde disco)
    $pdf_contenido = '';
    if (!empty($pdf_contenido_directo)) {
        $pdf_contenido = $pdf_contenido_directo;
    } else if (!empty($ruta_pdf) && file_exists($ruta_pdf) && filesize($ruta_pdf) > 0) {
        $pdf_contenido = file_get_contents($ruta_pdf);
    }

    if (empty($pdf_contenido)) {
        return array('exito' => false, 'mensaje' => 'El contenido del PDF está vacío o no se encontró el archivo: ' . $ruta_pdf);
    }

    // -------------------------------------------------------------
    // 3. CONSTRUCCIÓN DEL CORREO (ASUNTO Y CUERPO)
    // -------------------------------------------------------------
    $fecha_str   = isset($datos_planilla['fecha']) ? $datos_planilla['fecha'] : date('d/m/Y');
    $horario_str = isset($datos_planilla['horarios']) ? $datos_planilla['horarios'] : 'Turno';
    $oficial_str = (isset($datos_planilla['of_apellido']) && isset($datos_planilla['of_nombre']))
                    ? strtoupper($datos_planilla['of_apellido'] . ', ' . $datos_planilla['of_nombre'])
                    : 'No especificado';
    $jefe_str    = (isset($datos_planilla['jefe_apellido']) && isset($datos_planilla['jefe_nombre']))
                    ? strtoupper($datos_planilla['jefe_apellido'] . ', ' . $datos_planilla['jefe_nombre'])
                    : 'No especificado';

    // Asunto del correo (codificado en Base64 para caracteres especiales/tildes)
    $asunto_texto = "Planilla Diaria Registro Radiológico - $fecha_str - $horario_str";
    $asunto_header = '=?UTF-8?B?' . base64_encode($asunto_texto) . '?=';

    // Mensaje en formato texto plano y HTML
    $mensaje_html = "
    <html>
    <head><meta charset='UTF-8'></head>
    <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.5;'>
        <div style='background-color: #005a9c; color: white; padding: 12px 20px; border-radius: 4px 4px 0 0;'>
            <h2 style='margin: 0; font-size: 18px;'>Planilla Diaria de Registro Radiológico</h2>
        </div>
        <div style='border: 1px solid #ddd; border-top: none; padding: 20px; background-color: #fafafa; border-radius: 0 0 4px 4px;'>
            <p>Estimados,</p>
            <p>Se adjunta la <b>Planilla Diaria de Registro Radiológico</b> correspondiente al turno indicado a continuación:</p>
            
            <table style='width: 100%; max-width: 500px; border-collapse: collapse; margin: 15px 0; background: white; border: 1px solid #ccc;'>
                <tr style='background: #f0f0f0;'>
                    <td style='padding: 8px; border: 1px solid #ccc;'><b>Fecha:</b></td>
                    <td style='padding: 8px; border: 1px solid #ccc;'>$fecha_str</td>
                </tr>
                <tr>
                    <td style='padding: 8px; border: 1px solid #ccc;'><b>Turno / Horario:</b></td>
                    <td style='padding: 8px; border: 1px solid #ccc;'>$horario_str</td>
                </tr>
                <tr style='background: #f0f0f0;'>
                    <td style='padding: 8px; border: 1px solid #ccc;'><b>Oficial de Radioprotección:</b></td>
                    <td style='padding: 8px; border: 1px solid #ccc;'>$oficial_str</td>
                </tr>
                <tr>
                    <td style='padding: 8px; border: 1px solid #ccc;'><b>Jefe de Turno:</b></td>
                    <td style='padding: 8px; border: 1px solid #ccc;'>$jefe_str</td>
                </tr>
                <tr style='background: #f0f0f0;'>
                    <td style='padding: 8px; border: 1px solid #ccc;'><b>Archivo Adjunto:</b></td>
                    <td style='padding: 8px; border: 1px solid #ccc;'>$nombre_archivo</td>
                </tr>
            </table>

            <p style='color: #666; font-size: 12px; margin-top: 25px;'>
                <i>Este es un mensaje generado automáticamente por el Sistema de Registro Radiológico de Nucleoeléctrica Argentina S.A.</i>
            </p>
        </div>
    </body>
    </html>
    ";

    // -------------------------------------------------------------
    // 4. PREPARACIÓN MIME MULTIPART CON ADJUNTO PDF
    // -------------------------------------------------------------
    $boundary = "==_MIME_BOUNDARY_" . md5(time() . $id_planilla);

    // Codificar el PDF en base64
    if (empty($pdf_contenido) && !empty($ruta_pdf) && file_exists($ruta_pdf)) {
        $pdf_contenido = file_get_contents($ruta_pdf);
    }
    $pdf_adjunto_b64 = chunk_split(base64_encode($pdf_contenido));

    $cuerpo_mime  = "From: $nombre_de <$remitente>\r\n";
    $cuerpo_mime .= "To: " . implode(', ', $destinatarios) . "\r\n";
    $cuerpo_mime .= "Subject: $asunto_header\r\n";
    $cuerpo_mime .= "Date: " . date('r') . "\r\n";
    $cuerpo_mime .= "MIME-Version: 1.0\r\n";
    $cuerpo_mime .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
    $cuerpo_mime .= "\r\n";

    // Parte HTML
    $cuerpo_mime .= "--$boundary\r\n";
    $cuerpo_mime .= "Content-Type: text/html; charset=UTF-8\r\n";
    $cuerpo_mime .= "Content-Transfer-Encoding: 8bit\r\n";
    $cuerpo_mime .= "\r\n";
    $cuerpo_mime .= $mensaje_html . "\r\n";

    // Parte Adjunto PDF
    $cuerpo_mime .= "--$boundary\r\n";
    $cuerpo_mime .= "Content-Type: application/pdf; name=\"$nombre_archivo\"\r\n";
    $cuerpo_mime .= "Content-Transfer-Encoding: base64\r\n";
    $cuerpo_mime .= "Content-Disposition: attachment; filename=\"$nombre_archivo\"\r\n";
    $cuerpo_mime .= "\r\n";
    $cuerpo_mime .= $pdf_adjunto_b64 . "\r\n";

    // Cierre MIME
    $cuerpo_mime .= "--$boundary--\r\n";
    $cuerpo_mime .= ".";

    // -------------------------------------------------------------
    // 5. ENVÍO POR SOCKET SMTP A EXCHANGE 2019
    // -------------------------------------------------------------
    $socket = @fsockopen($servidor_smtp, $puerto_smtp, $errno, $errstr, 10);
    if (!$socket) {
        return array('exito' => false, 'mensaje' => "No se pudo conectar a $servidor_smtp:$puerto_smtp: $errstr ($errno)");
    }

    $fn_leer = function($s) {
        $res = '';
        while ($linea = @fgets($s, 512)) {
            $res .= $linea;
            if (substr($linea, 3, 1) == ' ') break;
        }
        return $res;
    };

    $fn_cmd = function($s, $cmd) use ($fn_leer) {
        @fwrite($s, $cmd . "\r\n");
        return $fn_leer($s);
    };

    // Saludo inicial de Exchange
    $saludo_servidor = $fn_leer($socket);
    if (empty($saludo_servidor) || substr($saludo_servidor, 0, 3) !== '220') {
        @fclose($socket);
        $detalle_banner = !empty($saludo_servidor) ? trim($saludo_servidor) : 'Sin respuesta del servidor';
        return array('exito' => false, 'mensaje' => "El servidor SMTP no respondió con saludo 220 listo: $detalle_banner");
    }

    // Protocolo SMTP
    $res_ehlo = $fn_cmd($socket, "EHLO central.nasa");
    if (strpos($res_ehlo, '250') === false) {
        $res_helo = $fn_cmd($socket, "HELO central.nasa");
    }

    $res_from = $fn_cmd($socket, "MAIL FROM:<$remitente>");
    if (strpos($res_from, '250') === false) {
        $fn_cmd($socket, "QUIT");
        @fclose($socket);
        return array('exito' => false, 'mensaje' => "El servidor SMTP rechazó el remitente <$remitente>: " . trim($res_from));
    }

    $al_menos_uno = false;
    $detalle_rechazo = '';
    foreach ($destinatarios as $dest) {
        $dest = trim($dest);
        if (!empty($dest)) {
            $rcpt_res = $fn_cmd($socket, "RCPT TO:<$dest>");
            if (strpos($rcpt_res, '250') !== false) {
                $al_menos_uno = true;
            } else {
                $detalle_rechazo .= " [$dest: " . trim($rcpt_res) . "]";
            }
        }
    }

    if (!$al_menos_uno) {
        $fn_cmd($socket, "QUIT");
        @fclose($socket);
        return array('exito' => false, 'mensaje' => 'El servidor Exchange rechazó los destinatarios.' . (!empty($detalle_rechazo) ? (' Detalles:' . $detalle_rechazo) : ''));
    }

    $fn_cmd($socket, "DATA");
    $data_res = $fn_cmd($socket, $cuerpo_mime);
    $fn_cmd($socket, "QUIT");
    fclose($socket);

    // -------------------------------------------------------------
    // 6. MARCAR COMO ENVIADA
    // -------------------------------------------------------------
    if (strpos($data_res, '250') !== false) {
        // Guardar lock en archivo
        @file_put_contents($archivo_lock, date('Y-m-d H:i:s') . " - Enviado a: " . implode(', ', $destinatarios));

        // Guardar registro en BD si hay conexion
        if ($miconex) {
            $dest_str = mysql_real_escape_string(implode(', ', $destinatarios));
            $nom_str  = mysql_real_escape_string($nombre_archivo);
            @mysql_query("INSERT INTO `regrad_planillas_enviadas` (`id_planilla`, `fecha_envio`, `archivo`, `destinatarios`) 
                          VALUES ($id_planilla, NOW(), '$nom_str', '$dest_str')", $miconex);
        }

        return array('exito' => true, 'mensaje' => 'Correo enviado exitosamente.');
    } else {
        return array('exito' => false, 'mensaje' => 'Error al transferir contenido DATA: ' . $data_res);
    }
}
