<?php
/**
 * MOTOR CENTRALIZADO DE AUDITORÍA, PERMISOS Y SEGURIDAD OPERATIVA
 * Registro Radiológico Online - Central Nuclear Embalse (NA-SA)
 * Compatible con PHP 5.5.7, Apache 2.4 y MySQL (extensión mysql_*)
 *
 * Garantías:
 *  - Registro append-only en `regrad_auditoria_log` (nunca UPDATE/DELETE).
 *  - Identidad resuelta del lado del servidor desde $_SESSION (nunca confiar en IDs GET/POST).
 *  - Modo Fail-Closed: operaciones sobre planillas cerradas o correcciones de fecha/turno
 *    se abortan si el log no está operativo o falla su escritura.
 *  - Permisos granulares administrados únicamente por Usuario 0 (`regrad_permisos_usuarios`).
 */

if (!defined('AUDITORIA_REG_RAD_LOADED')) {
    define('AUDITORIA_REG_RAD_LOADED', true);

    if (session_status() == PHP_SESSION_NONE) {
        @session_start();
    }

    /**
     * Escapa un string para MySQL de forma segura en PHP 5.5.
     */
    function auditoria_esc($miconex, $val) {
        if ($val === null) return '';
        $str = (string)$val;
        if ($miconex) {
            return mysql_real_escape_string($str, $miconex);
        }
        return addslashes($str);
    }

    /**
     * Convierte recursivamente valores a UTF-8 válido para evitar fallos en json_encode (PHP 5.5).
     */
    function auditoria_utf8_seguro($dato) {
        if (is_array($dato)) {
            $out = array();
            foreach ($dato as $k => $v) {
                $out[auditoria_utf8_seguro($k)] = auditoria_utf8_seguro($v);
            }
            return $out;
        }
        if (is_string($dato)) {
            if (function_exists('mb_check_encoding') && !mb_check_encoding($dato, 'UTF-8')) {
                if (function_exists('mb_convert_encoding')) {
                    return mb_convert_encoding($dato, 'UTF-8', 'ISO-8859-1');
                }
                return utf8_encode($dato);
            }
            return $dato;
        }
        return $dato;
    }

    /**
     * Serializa a JSON seguro en UTF-8.
     */
    function auditoria_json($dato) {
        if ($dato === null) return null;
        $limpio = auditoria_utf8_seguro($dato);
        $opts = defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 0;
        $json = @json_encode($limpio, $opts);
        if ($json === false) {
            $json = @json_encode(array('raw_fallback' => print_r($dato, true)));
        }
        return $json;
    }

    /**
     * Obtiene la IP de origen real del cliente.
     */
    function auditoria_ip_cliente() {
        $claves = array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        foreach ($claves as $c) {
            if (!empty($_SERVER[$c])) {
                $partes = explode(',', $_SERVER[$c]);
                return substr(trim($partes[0]), 0, 45);
            }
        }
        return '127.0.0.1';
    }

    /**
     * Resuelve el usuario autenticado real desde la sesión del servidor.
     * Preserva '0' como string válido (sin tratarlo como falsy).
     */
    function auditoria_obtener_contexto($miconex = null, $fallback_uid = null) {
        $uid = null;
        $desde_sesion = false;

        if (isset($_SESSION['id_user']) && $_SESSION['id_user'] !== '' && $_SESSION['id_user'] !== null) {
            $uid = (string)$_SESSION['id_user'];
            $desde_sesion = true;
        } elseif ($fallback_uid !== null && $fallback_uid !== '') {
            $uid = (string)$fallback_uid;
        } else {
            $uid = 'desconocido';
        }

        $nombre = '';
        if ($desde_sesion && !empty($_SESSION['nombre_apellido'])) {
            $nombre = trim((string)$_SESSION['nombre_apellido']);
        }
        if ($nombre === '' && $uid !== 'desconocido' && function_exists('nombre_usuario_web')) {
            $res_nom = @nombre_usuario_web($uid);
            if (!empty($res_nom) && is_string($res_nom)) {
                $nombre = trim($res_nom);
            }
        }
        if ($nombre === '') {
            $nombre = ($uid === '0') ? 'ADMINISTRADOR (USUARIO 0)' : ('USUARIO #' . $uid);
        }

        $es_u0 = ($desde_sesion && $uid === '0');

        return array(
            'usuario_id' => $uid,
            'usuario_nombre' => function_exists('mb_strtoupper') ? mb_strtoupper($nombre, 'UTF-8') : strtoupper($nombre),
            'es_u0' => $es_u0,
            'desde_sesion' => $desde_sesion,
            'ip_origen' => auditoria_ip_cliente()
        );
    }

    /**
     * Obtiene el motivo de auditoría enviado por POST o GET.
     */
    function auditoria_obtener_motivo_request() {
        if (isset($_POST['motivo_auditoria']) && trim($_POST['motivo_auditoria']) !== '') {
            return trim((string)$_POST['motivo_auditoria']);
        }
        if (isset($_GET['motivo_auditoria']) && trim($_GET['motivo_auditoria']) !== '') {
            return trim((string)$_GET['motivo_auditoria']);
        }
        if (isset($_POST['motivo']) && trim($_POST['motivo']) !== '') {
            return trim((string)$_POST['motivo']);
        }
        if (isset($_GET['motivo']) && trim($_GET['motivo']) !== '') {
            return trim((string)$_GET['motivo']);
        }
        return '';
    }

    /**
     * Asegura que las tablas de auditoría, permisos y configuración de seguridad existan.
     * Devuelve true si la tabla de log está disponible y operativa.
     */
    function auditoria_asegurar_tablas($miconex) {
        static $verificado = false;
        static $estado_ok = false;

        if (!$miconex) {
            return false;
        }
        if ($verificado) {
            return $estado_ok;
        }

        $sql_log = "CREATE TABLE IF NOT EXISTS `regrad_auditoria_log` (
            `id_log` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `fecha_hora` DATETIME NOT NULL,
            `id_planilla` INT(11) DEFAULT NULL,
            `planilla_cerrada` TINYINT(1) NOT NULL DEFAULT 0,
            `usuario_id` VARCHAR(32) NOT NULL,
            `usuario_nombre` VARCHAR(160) NOT NULL,
            `ip_origen` VARCHAR(45) DEFAULT NULL,
            `modulo` VARCHAR(40) NOT NULL,
            `accion` VARCHAR(40) NOT NULL,
            `tabla_afectada` VARCHAR(80) DEFAULT NULL,
            `id_registro` VARCHAR(64) DEFAULT NULL,
            `datos_antes` MEDIUMTEXT DEFAULT NULL,
            `datos_despues` MEDIUMTEXT DEFAULT NULL,
            `motivo` TEXT DEFAULT NULL,
            `origen_archivo` VARCHAR(80) DEFAULT NULL,
            PRIMARY KEY (`id_log`),
            KEY `idx_planilla_fecha` (`id_planilla`, `fecha_hora`),
            KEY `idx_usuario_fecha` (`usuario_id`, `fecha_hora`),
            KEY `idx_modulo_accion` (`modulo`, `accion`),
            KEY `idx_post_cierre` (`planilla_cerrada`, `fecha_hora`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        $sql_perm = "CREATE TABLE IF NOT EXISTS `regrad_permisos_usuarios` (
            `usuario_id` VARCHAR(32) NOT NULL,
            `usuario_nombre` VARCHAR(160) NOT NULL,
            `editar_planillas_cerradas` TINYINT(1) NOT NULL DEFAULT 0,
            `corregir_fecha_turno` TINYINT(1) NOT NULL DEFAULT 0,
            `ver_auditoria` TINYINT(1) NOT NULL DEFAULT 0,
            `activo` TINYINT(1) NOT NULL DEFAULT 1,
            `actualizado_el` DATETIME NOT NULL,
            `actualizado_por` VARCHAR(32) NOT NULL,
            PRIMARY KEY (`usuario_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        $sql_cfg = "CREATE TABLE IF NOT EXISTS `regrad_config_seguridad` (
            `clave` VARCHAR(64) NOT NULL,
            `valor_json` TEXT NOT NULL,
            `actualizado_el` DATETIME NOT NULL,
            `actualizado_por` VARCHAR(32) NOT NULL,
            PRIMARY KEY (`clave`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        $ok1 = @mysql_query($sql_log, $miconex);
        @mysql_query($sql_perm, $miconex);
        @mysql_query($sql_cfg, $miconex);

        // Verificar que realmente podemos consultar regrad_auditoria_log
        $chk = @mysql_query("SELECT id_log FROM `regrad_auditoria_log` LIMIT 1", $miconex);
        $verificado = true;
        $estado_ok = ($ok1 !== false && $chk !== false);
        return $estado_ok;
    }

    /**
     * Comprueba salud real del subsistema de auditoría (Fail-Closed).
     */
    function auditoria_verificar_salud($miconex) {
        if (!$miconex) {
            return array('ok' => false, 'mensaje' => 'Sin conexión activa a MySQL.');
        }
        if (!auditoria_asegurar_tablas($miconex)) {
            $err = function_exists('mysql_error') ? @mysql_error($miconex) : 'Error de tabla';
            return array('ok' => false, 'mensaje' => 'Tabla de auditoría no operativa: ' . $err);
        }
        return array('ok' => true, 'mensaje' => 'Log de auditoría operativo.');
    }

    /**
     * Obtiene configuración global de seguridad (ej. auto-destrabe de emergencia y mails de alerta).
     */
    function auditoria_obtener_config_seguridad($miconex = null) {
        $default = array(
            'permitir_autodestrabe_emergencia' => false,
            'ventana_horas_autodestrabe' => 24,
            'alertas_mail_activas' => true,
            'destinatarios_alertas' => array('gloncar@na-sa.com.ar')
        );

        $ruta_json = __DIR__ . '/config_seguridad.json';
        if (file_exists($ruta_json)) {
            $raw = @file_get_contents($ruta_json);
            if ($raw) {
                $parsed = @json_decode($raw, true);
                if (is_array($parsed)) {
                    $default = array_merge($default, $parsed);
                }
            }
        }

        if ($miconex && auditoria_asegurar_tablas($miconex)) {
            $res = @mysql_query("SELECT valor_json FROM `regrad_config_seguridad` WHERE clave = 'seguridad_general' LIMIT 1", $miconex);
            if ($res && mysql_num_rows($res) > 0) {
                $row = mysql_fetch_assoc($res);
                $db_cfg = @json_decode($row['valor_json'], true);
                if (is_array($db_cfg)) {
                    $default = array_merge($default, $db_cfg);
                }
            }
        }

        return $default;
    }

    /**
     * Guarda la configuración global de seguridad en MySQL y JSON.
     */
    function auditoria_guardar_config_seguridad($miconex, $nueva_cfg) {
        $ctx = auditoria_obtener_contexto($miconex);
        $antes = auditoria_obtener_config_seguridad($miconex);
        $json_str = auditoria_json($nueva_cfg);
        $ahora = date('Y-m-d H:i:s');

        $ok_db = false;
        if ($miconex && auditoria_asegurar_tablas($miconex)) {
            $j_esc = auditoria_esc($miconex, $json_str);
            $u_esc = auditoria_esc($miconex, $ctx['usuario_id']);
            $q = "INSERT INTO `regrad_config_seguridad` (`clave`, `valor_json`, `actualizado_el`, `actualizado_por`)
                  VALUES ('seguridad_general', '$j_esc', '$ahora', '$u_esc')
                  ON DUPLICATE KEY UPDATE `valor_json` = '$j_esc', `actualizado_el` = '$ahora', `actualizado_por` = '$u_esc'";
            $ok_db = (@mysql_query($q, $miconex) !== false);
        }
        $opts = (defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0) | (defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 0);
        @file_put_contents(__DIR__ . '/config_seguridad.json', @json_encode(auditoria_utf8_seguro($nueva_cfg), $opts));

        if ($ok_db) {
            auditoria_registrar($miconex, array(
                'modulo' => 'PERMISOS',
                'accion' => 'CONFIG_SEGURIDAD',
                'tabla_afectada' => 'regrad_config_seguridad',
                'id_registro' => 'seguridad_general',
                'datos_antes' => $antes,
                'datos_despues' => $nueva_cfg,
                'motivo' => 'Actualización de parámetros de seguridad operativa'
            ));
        }

        return $ok_db;
    }

    /**
     * Obtiene los permisos de un usuario. Usuario 0 tiene todos los permisos.
     */
    function auditoria_obtener_permisos_usuario($miconex, $uid = null) {
        $ctx = auditoria_obtener_contexto($miconex);
        $target_uid = ($uid === null) ? $ctx['usuario_id'] : (string)$uid;

        if ($target_uid === '0' && ($uid !== null || $ctx['es_u0'])) {
            return array(
                'usuario_id' => '0',
                'es_u0' => true,
                'editar_planillas_cerradas' => true,
                'corregir_fecha_turno' => true,
                'ver_auditoria' => true
            );
        }

        $permisos = array(
            'usuario_id' => $target_uid,
            'es_u0' => false,
            'editar_planillas_cerradas' => false,
            'corregir_fecha_turno' => false,
            'ver_auditoria' => false
        );

        if ($miconex && $target_uid !== 'desconocido' && auditoria_asegurar_tablas($miconex)) {
            $u_esc = auditoria_esc($miconex, $target_uid);
            $res = @mysql_query("SELECT * FROM `regrad_permisos_usuarios` WHERE usuario_id = '$u_esc' AND activo = 1 LIMIT 1", $miconex);
            if ($res && mysql_num_rows($res) > 0) {
                $row = mysql_fetch_assoc($res);
                $permisos['editar_planillas_cerradas'] = !empty($row['editar_planillas_cerradas']);
                $permisos['corregir_fecha_turno'] = !empty($row['corregir_fecha_turno']);
                $permisos['ver_auditoria'] = !empty($row['ver_auditoria']);
            }
        }

        return $permisos;
    }

    /**
     * Verifica si el usuario actual posee un permiso específico.
     */
    function auditoria_usuario_tiene_permiso($miconex, $permiso) {
        $p = auditoria_obtener_permisos_usuario($miconex);
        return !empty($p[$permiso]);
    }

    /**
     * Obtiene cabecera completa de una planilla.
     */
    function auditoria_obtener_planilla($miconex, $id_planilla) {
        $id = intval($id_planilla);
        if (!$miconex || $id <= 0) return null;
        $q = "SELECT a.*, b.horarios, d.of_apellido, d.of_nombre, e.jefe_apellido, e.jefe_nombre
              FROM `regrad_planilla_diaria_nueva` a
              LEFT JOIN `regrad_turnos` b ON b.id_turnos = a.turno
              LEFT JOIN `regrad_of_rad` d ON d.id_of_rad = a.of_rad
              LEFT JOIN `regrad_jefes_turno` e ON e.id_jefes_turno = a.jefe_turno
              WHERE a.id_planilla_diaria = $id LIMIT 1";
        $res = @mysql_query($q, $miconex);
        if ($res && mysql_num_rows($res) > 0) {
            return mysql_fetch_assoc($res);
        }
        return null;
    }

    /**
     * Indica si la planilla especificada está cerrada (`fecha_hora_cerrado IS NOT NULL`).
     */
    function auditoria_planilla_esta_cerrada($miconex, $id_planilla) {
        $id = intval($id_planilla);
        if (!$miconex || $id <= 0) return false;
        $res = @mysql_query("SELECT fecha_hora_cerrado FROM `regrad_planilla_diaria_nueva` WHERE id_planilla_diaria = $id LIMIT 1", $miconex);
        if ($res && mysql_num_rows($res) > 0) {
            $row = mysql_fetch_assoc($res);
            return (!empty($row['fecha_hora_cerrado']) && $row['fecha_hora_cerrado'] !== '0000-00-00 00:00:00');
        }
        return false;
    }

    /**
     * Captura una fila completa antes de editarla o eliminarla.
     */
    function auditoria_snapshot_fila($miconex, $tabla, $campo_id, $valor_id) {
        if (!$miconex || empty($tabla) || empty($campo_id)) return null;
        $t = preg_replace('/[^a-zA-Z0-9_]/', '', $tabla);
        $c = preg_replace('/[^a-zA-Z0-9_]/', '', $campo_id);
        $v = auditoria_esc($miconex, $valor_id);
        $res = @mysql_query("SELECT * FROM `$t` WHERE `$c` = '$v' LIMIT 1", $miconex);
        if ($res && mysql_num_rows($res) > 0) {
            return mysql_fetch_assoc($res);
        }
        return null;
    }

    /**
     * Lista blanca de tablas permitidas en `eliminar_registro_generico` y metadatos de módulo.
     */
    function auditoria_tabla_generica_info($tabla, $campo_id) {
        $mapa = array(
            'regrad_relevamiento_rutina' => array('pk' => 'id_relev_rutina', 'modulo' => 'RUTINA', 'col_planilla' => 'id_planilla'),
            'regrad_tritio' => array('pk' => 'id_tritio', 'modulo' => 'TRITIO', 'col_planilla' => 'id_planilla'),
            'regrad_gases_nobles' => array('pk' => 'id_gases_nobles', 'modulo' => 'GASES_NOBLES', 'col_planilla' => 'id_planilla'),
            'regrad_carbono14' => array('pk' => 'id_carbono14', 'modulo' => 'C14', 'col_planilla' => 'id_planilla'),
            'regrad_dedo_frio' => array('pk' => 'id_dedo_frio', 'modulo' => 'DEDO_FRIO', 'col_planilla' => 'id_planilla'),
            'regrad_tritio_concentrador' => array('pk' => 'id_tritio_conc', 'modulo' => 'CONCENTRADOR_7923', 'col_planilla' => 'id_planilla'),
            'regrad_control_precintos' => array('pk' => 'id_control_precintos', 'modulo' => 'PRECINTOS', 'col_planilla' => 'id_planilla'),
            'regrad_cond_meteorologica' => array('pk' => 'id_cond_meteorologica', 'modulo' => 'METEOROLOGIA', 'col_planilla' => 'id_planilla'),
            'regrad_espectr_conc_radionuclei' => array('pk' => 'id_conc_rad', 'modulo' => 'ESPECTROMETRIA', 'col_planilla' => 'id_planilla'),
            'regrad_efluentes_liquidos_nueva' => array('pk' => 'id_efluentes', 'modulo' => 'LIQUIDOS', 'col_planilla' => 'id_planilla'),
            'regrad_efluentes_liquidos_radionucleidos' => array('pk' => 'id', 'modulo' => 'LIQUIDOS', 'col_planilla' => null),
            'regrad_contaminacion_superficial' => array('pk' => 'id_contaminacion_superficial', 'modulo' => 'CONT_SUPERFICIAL', 'col_planilla' => 'id_planilla'),
            'regrad_eficiencia_dryers' => array('pk' => 'id_eficiencia_dryers', 'modulo' => 'DRYERS', 'col_planilla' => 'id_planilla'),
            'regrad_actas_tareas_diarias' => array('pk' => 'id_tarea', 'modulo' => 'ACTAS', 'col_planilla' => 'id_planilla_diaria')
        );

        if (!isset($mapa[$tabla])) {
            return null;
        }
        $info = $mapa[$tabla];
        if ($info['pk'] !== $campo_id) {
            return null;
        }
        return $info;
    }

    /**
     * Valida permisos y salud del log (fail-closed) antes de permitir mutaciones sobre una planilla.
     * - Si la planilla está ABIERTA: permite operar normalmente.
     * - Si la planilla está CERRADA: exige permiso `editar_planillas_cerradas` (o U0),
     *   log saludable (fail-closed) y motivo obligatorio.
     */
    function auditoria_exigir_permiso_escritura_planilla($miconex, $id_planilla, $motivo = '') {
        $id = intval($id_planilla);
        if ($id <= 0) {
            return array('ok' => true, 'cerrada' => false, 'error' => '');
        }

        $cerrada = auditoria_planilla_esta_cerrada($miconex, $id);
        if (!$cerrada) {
            return array('ok' => true, 'cerrada' => false, 'error' => '');
        }

        // Planilla cerrada: modo estricto fail-closed
        $salud = auditoria_verificar_salud($miconex);
        if (!$salud['ok']) {
            return array(
                'ok' => false,
                'cerrada' => true,
                'codigo' => 503,
                'error' => 'Operación bloqueada: el log de auditoría no está operativo (' . $salud['mensaje'] . '). No se permiten cambios post-cierre sin registro.'
            );
        }

        if (!auditoria_usuario_tiene_permiso($miconex, 'editar_planillas_cerradas')) {
            return array(
                'ok' => false,
                'cerrada' => true,
                'codigo' => 403,
                'error' => 'Acceso denegado: la planilla #' . $id . ' está cerrada y su usuario no posee permiso de edición histórica.'
            );
        }

        $mot = trim((string)$motivo);
        if (strlen($mot) < 4) {
            return array(
                'ok' => false,
                'cerrada' => true,
                'codigo' => 400,
                'error' => 'Debe indicar un motivo válido (mínimo 4 caracteres) para modificar la planilla cerrada #' . $id . '.'
            );
        }

        return array('ok' => true, 'cerrada' => true, 'error' => '');
    }

    /**
     * Inserta un evento inmutable en `regrad_auditoria_log`.
     * Retorna el id_log insertado o false si falló.
     */
    function auditoria_registrar($miconex, $params) {
        if (!$miconex || !auditoria_asegurar_tablas($miconex)) {
            return false;
        }

        $fallback_uid = isset($params['fallback_uid']) ? $params['fallback_uid'] : null;
        $ctx = auditoria_obtener_contexto($miconex, $fallback_uid);

        $ahora = date('Y-m-d H:i:s');
        $id_planilla = (isset($params['id_planilla']) && intval($params['id_planilla']) > 0) ? intval($params['id_planilla']) : null;

        if (isset($params['planilla_cerrada'])) {
            $cerrada = $params['planilla_cerrada'] ? 1 : 0;
        } else {
            $cerrada = ($id_planilla !== null && auditoria_planilla_esta_cerrada($miconex, $id_planilla)) ? 1 : 0;
        }

        $uid_esc = auditoria_esc($miconex, $ctx['usuario_id']);
        $unom_esc = auditoria_esc($miconex, $ctx['usuario_nombre']);
        $ip_esc = auditoria_esc($miconex, $ctx['ip_origen']);
        $mod_esc = auditoria_esc($miconex, isset($params['modulo']) ? strtoupper($params['modulo']) : 'GENERAL');
        $acc_esc = auditoria_esc($miconex, isset($params['accion']) ? strtoupper($params['accion']) : 'EVENTO');
        $tab_esc = isset($params['tabla_afectada']) ? ("'" . auditoria_esc($miconex, $params['tabla_afectada']) . "'") : 'NULL';
        $reg_esc = (isset($params['id_registro']) && $params['id_registro'] !== '' && $params['id_registro'] !== null)
            ? ("'" . auditoria_esc($miconex, $params['id_registro']) . "'")
            : 'NULL';

        $antes_json = isset($params['datos_antes']) ? auditoria_json($params['datos_antes']) : null;
        $desp_json = isset($params['datos_despues']) ? auditoria_json($params['datos_despues']) : null;

        $antes_sql = ($antes_json !== null) ? ("'" . auditoria_esc($miconex, $antes_json) . "'") : 'NULL';
        $desp_sql = ($desp_json !== null) ? ("'" . auditoria_esc($miconex, $desp_json) . "'") : 'NULL';

        $motivo_raw = isset($params['motivo']) ? trim((string)$params['motivo']) : auditoria_obtener_motivo_request();
        $motivo_sql = ($motivo_raw !== '') ? ("'" . auditoria_esc($miconex, $motivo_raw) . "'") : 'NULL';

        $origen = isset($params['origen_archivo']) ? $params['origen_archivo'] : basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : 'desconocido');
        $orig_esc = auditoria_esc($miconex, $origen);
        $plan_sql = ($id_planilla !== null) ? (string)$id_planilla : 'NULL';

        $q = "INSERT INTO `regrad_auditoria_log`
            (`fecha_hora`, `id_planilla`, `planilla_cerrada`, `usuario_id`, `usuario_nombre`, `ip_origen`, `modulo`, `accion`, `tabla_afectada`, `id_registro`, `datos_antes`, `datos_despues`, `motivo`, `origen_archivo`)
            VALUES
            ('$ahora', $plan_sql, $cerrada, '$uid_esc', '$unom_esc', '$ip_esc', '$mod_esc', '$acc_esc', $tab_esc, $reg_esc, $antes_sql, $desp_sql, $motivo_sql, '$orig_esc')";

        $res = @mysql_query($q, $miconex);
        if (!$res) {
            return false;
        }
        $id_log = @mysql_insert_id($miconex);
        return $id_log ? $id_log : true;
    }

    /**
     * Envía un correo liviano de alerta de seguridad/auditoría vía Exchange (sin adjuntos).
     * Nunca bloquea ni rompe la respuesta principal si Exchange no está accesible.
     */
    function auditoria_enviar_alerta_correo($miconex, $asunto, $cuerpo_html) {
        $cfg_seg = auditoria_obtener_config_seguridad($miconex);
        if (empty($cfg_seg['alertas_mail_activas'])) {
            return array('enviado' => false, 'motivo' => 'Alertas por correo desactivadas');
        }

        $destinatarios = !empty($cfg_seg['destinatarios_alertas']) ? (array)$cfg_seg['destinatarios_alertas'] : array('gloncar@na-sa.com.ar');
        if (empty($destinatarios)) {
            return array('enviado' => false, 'motivo' => 'Sin destinatarios configurados');
        }

        $servidor_smtp = 'correo.na-sa.com.ar';
        $puerto_smtp = 25;
        $remitente = 'registro_radiologico@na-sa.com.ar';
        $nombre_de = 'Auditoría SyR - Registro Radiológico';

        if (file_exists(__DIR__ . '/enviar_mail_planilla.php')) {
            @include_once(__DIR__ . '/enviar_mail_planilla.php');
            if (function_exists('obtener_configuracion_correo')) {
                $cfg_mail = obtener_configuracion_correo($miconex);
                if (!empty($cfg_mail['servidor_smtp'])) $servidor_smtp = $cfg_mail['servidor_smtp'];
                if (!empty($cfg_mail['puerto_smtp'])) $puerto_smtp = intval($cfg_mail['puerto_smtp']);
                if (!empty($cfg_mail['remitente'])) $remitente = $cfg_mail['remitente'];
            }
        }

        $sock = @fsockopen($servidor_smtp, $puerto_smtp, $errno, $errstr, 4);
        if (!$sock) {
            return array('enviado' => false, 'motivo' => 'Sin conexión SMTP: ' . $errstr);
        }
        @stream_set_timeout($sock, 4);

        $leer = function($s) {
            $resp = '';
            while ($line = @fgets($s, 515)) {
                $resp .= $line;
                if (strlen($line) >= 4 && substr($line, 3, 1) === ' ') break;
            }
            return $resp;
        };

        $leer($sock);
        $host = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
        @fwrite($sock, "EHLO $host\r\n");
        $leer($sock);
        @fwrite($sock, "MAIL FROM:<$remitente>\r\n");
        $leer($sock);
        foreach ($destinatarios as $dest) {
            $dest = trim($dest);
            if ($dest !== '') {
                @fwrite($sock, "RCPT TO:<$dest>\r\n");
                $leer($sock);
            }
        }
        @fwrite($sock, "DATA\r\n");
        $leer($sock);

        $asunto_enc = '=?UTF-8?B?' . base64_encode($asunto) . '?=';
        $headers  = "From: $nombre_de <$remitente>\r\n";
        $headers .= "To: " . implode(', ', $destinatarios) . "\r\n";
        $headers .= "Subject: $asunto_enc\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Date: " . date('r') . "\r\n\r\n";

        $msg = $headers . $cuerpo_html . "\r\n.\r\n";
        @fwrite($sock, $msg);
        $resp_data = $leer($sock);
        @fwrite($sock, "QUIT\r\n");
        @fclose($sock);

        $ok = (strpos($resp_data, '250') === 0);
        return array('enviado' => $ok, 'respuesta' => trim($resp_data));
    }

    /**
     * Calcula la secuencia correlativa esperada respecto a la última planilla registrada.
     * Soporta turnos de 8h (1, 2, 3) o 12h según la tabla `regrad_turnos`.
     */
    function auditoria_calcular_turno_siguiente($miconex, $ultima_fecha, $ultimo_turno_id) {
        $turnos_ids = array();
        $turnos_map = array();
        if ($miconex) {
            $rt = @mysql_query("SELECT id_turnos, horarios FROM `regrad_turnos` ORDER BY id_turnos ASC", $miconex);
            if ($rt && mysql_num_rows($rt) > 0) {
                while ($row = mysql_fetch_assoc($rt)) {
                    $tid = intval($row['id_turnos']);
                    $turnos_ids[] = $tid;
                    $turnos_map[$tid] = $row['horarios'];
                }
            }
        }
        if (empty($turnos_ids)) {
            $turnos_ids = array(1, 2, 3);
            $turnos_map = array(1 => '07 hs a 15 hs', 2 => '15 hs a 23 hs', 3 => '23 hs a 07 hs');
        }

        // Determinar orden lógico diario según los IDs presentes (1->2->3 estándar; si el último es turno 4/5 de 12hs, ciclar 4->5)
        $u_tid = intval($ultimo_turno_id);
        $ciclo = array(1, 2, 3);
        if (!in_array($u_tid, $ciclo)) {
            $ciclo = $turnos_ids;
        } else {
            $ciclo_real = array();
            foreach ($ciclo as $cid) {
                if (in_array($cid, $turnos_ids)) $ciclo_real[] = $cid;
            }
            if (!empty($ciclo_real)) $ciclo = $ciclo_real;
        }

        $idx = array_search($u_tid, $ciclo);
        $ts = strtotime($ultima_fecha);
        if ($idx === false || $ts === false) {
            return null;
        }

        if ($idx + 1 < count($ciclo)) {
            $sig_turno = $ciclo[$idx + 1];
            $sig_fecha = date('Y-m-d', $ts);
        } else {
            $sig_turno = $ciclo[0];
            $sig_fecha = date('Y-m-d', strtotime('+1 day', $ts));
        }

        return array(
            'fecha' => $sig_fecha,
            'turno' => $sig_turno,
            'horarios' => isset($turnos_map[$sig_turno]) ? $turnos_map[$sig_turno] : ('Turno #' . $sig_turno)
        );
    }

    /**
     * Evalúa si el usuario actual puede corregir la fecha/turno de una planilla dada.
     * Retorna array('permitido' => bool, 'modo' => string, 'motivo_bloqueo' => string).
     */
    function auditoria_puede_corregir_fecha_turno($miconex, $planilla_row) {
        if (!$planilla_row) {
            return array('permitido' => false, 'modo' => 'ninguno', 'motivo_bloqueo' => 'Planilla inexistente.');
        }

        $salud = auditoria_verificar_salud($miconex);
        if (!$salud['ok']) {
            return array('permitido' => false, 'modo' => 'ninguno', 'motivo_bloqueo' => 'Log de auditoría no operativo (bloqueo fail-closed).');
        }

        $ctx = auditoria_obtener_contexto($miconex);
        $cerrada = (!empty($planilla_row['fecha_hora_cerrado']) && $planilla_row['fecha_hora_cerrado'] !== '0000-00-00 00:00:00');

        // 1. Usuario 0 o permiso explícito `corregir_fecha_turno`
        if ($ctx['es_u0'] || auditoria_usuario_tiene_permiso($miconex, 'corregir_fecha_turno')) {
            return array(
                'permitido' => true,
                'modo' => $ctx['es_u0'] ? 'u0' : 'permiso_delegado',
                'motivo_bloqueo' => ''
            );
        }

        // 2. Planilla aún ABIERTA: el propio usuario que la abrió (o su oficial) puede corregirla en caliente
        if (!$cerrada) {
            $mismo_user = ($ctx['desde_sesion'] && isset($planilla_row['usuario_portal_abierto']) && (string)$planilla_row['usuario_portal_abierto'] === (string)$ctx['usuario_id']);
            $of_nom = trim((isset($planilla_row['of_apellido']) ? $planilla_row['of_apellido'] : '') . ', ' . (isset($planilla_row['of_nombre']) ? $planilla_row['of_nombre'] : ''));
            $of_nom_up = function_exists('mb_strtoupper') ? mb_strtoupper($of_nom, 'UTF-8') : strtoupper($of_nom);
            $mismo_oficial = ($ctx['desde_sesion'] && $of_nom_up !== ',' && $of_nom_up === $ctx['usuario_nombre']);

            if ($mismo_user || $mismo_oficial) {
                return array(
                    'permitido' => true,
                    'modo' => 'dueno_planilla_abierta',
                    'motivo_bloqueo' => ''
                );
            }
            return array('permitido' => false, 'modo' => 'ninguno', 'motivo_bloqueo' => 'Solo el oficial que abrió la planilla o un usuario autorizado puede corregir fecha/turno.');
        }

        // 3. Planilla CERRADA: evaluar si está habilitado el auto-destrabe de emergencia (< 24h y última planilla cerrada)
        $cfg_seg = auditoria_obtener_config_seguridad($miconex);
        if (!empty($cfg_seg['permitir_autodestrabe_emergencia'])) {
            $ventana_h = !empty($cfg_seg['ventana_horas_autodestrabe']) ? intval($cfg_seg['ventana_horas_autodestrabe']) : 24;
            $res_ult = @mysql_query("SELECT id_planilla_diaria FROM `regrad_planilla_diaria_nueva` WHERE fecha_hora_cerrado IS NOT NULL ORDER BY id_planilla_diaria DESC LIMIT 1", $miconex);
            $es_ultima_cerrada = false;
            if ($res_ult && mysql_num_rows($res_ult) > 0) {
                $r_ult = mysql_fetch_assoc($res_ult);
                $es_ultima_cerrada = (intval($r_ult['id_planilla_diaria']) === intval($planilla_row['id_planilla_diaria']));
            }
            $ts_cierre = strtotime($planilla_row['fecha_hora_cerrado']);
            $horas_desde_cierre = ($ts_cierre !== false) ? ((time() - $ts_cierre) / 3600.0) : 9999;

            if ($es_ultima_cerrada && $horas_desde_cierre <= $ventana_h) {
                return array(
                    'permitido' => true,
                    'modo' => 'autodestrabe_emergencia',
                    'motivo_bloqueo' => ''
                );
            }
        }

        return array(
            'permitido' => false,
            'modo' => 'ninguno',
            'motivo_bloqueo' => 'No posee permiso para modificar fecha/turno de una planilla cerrada. Solicite corrección a un supervisor habilitado o al Administrador (U0).'
        );
    }
}
?>
