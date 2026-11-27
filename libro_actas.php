<?php
/**
 * VISTA LIBRO DE ACTAS — REGISTRO RADIOLÓGICO ONLINE (SyR)
 * Central Nuclear Embalse (NA-SA)
 * Optimizado con consultas acotadas (WHERE IN), agrupación O(1) en memoria,
 * carga progresiva AJAX ("Cargar 15 / 30 más") y diseño HMI sincronizado.
 * Compatible con PHP 5.5.7, Apache 2.4 y MySQL (mysql_*), 100% Offline.
 */
$t_inicio_carga = microtime(true);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$es_ajax = (isset($_GET['ajax']) && (string)$_GET['ajax'] === '1');
$db_connected = false;
$miconex = null;

if (isset($_SERVER["DOCUMENT_ROOT"]) && file_exists($_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/miconex.php")) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/miconex.php");
    if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/ptr/Functions/funciones.php")) {
        require_once($_SERVER["DOCUMENT_ROOT"] . "/ptr/Functions/funciones.php");
    }
    if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/niveles.php")) {
        require_once($_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/niveles.php");
    }

    if (!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true)) {
        if ($es_ajax) {
            if (function_exists('http_response_code')) http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('ok' => false, 'error' => 'Sesión expirada. Vuelva a iniciar sesión.'));
            exit;
        }
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Acceso Restringido - SyR</title>';
        echo '<style>body{background:#061412;color:#edfcf6;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}</style></head>';
        echo '<body><div style="text-align:center;padding:28px 32px;border:1px solid #1f4d43;border-left:4px solid #ef4444;border-radius:10px;background:#0e2722;max-width:440px;">';
        echo '<h2 style="margin:0 0 10px;font-size:18px;color:#f87171;">⛔ Sesión No Iniciada</h2>';
        echo '<p style="color:#8ec7b9;font-size:13px;margin:0 0 18px;line-height:1.5;">Esta página es solo para usuarios registrados del Portal SyR.</p>';
        echo '<a href="/ptr/" style="display:inline-block;padding:9px 16px;border-radius:7px;background:#10b981;color:#04120f;font-weight:800;font-size:12px;text-decoration:none;">Volver al Ingreso</a>';
        echo '</div></body></html>';
        exit;
    }

    if (isset($miconex) && $miconex) {
        @mysql_set_charset('utf8', $miconex);
        if (isset($database_miconex)) {
            @mysql_select_db($database_miconex, $miconex);
        }
        $db_connected = true;
    }
}

$tipo_user = isset($_SESSION['portal_nivel']) ? $_SESSION['portal_nivel'] : 1;
$id_user = isset($_SESSION['id_user']) ? (string)$_SESSION['id_user'] : '';
$of_rad_registro = $id_user;
$nombre_usuario_raw = !empty($_SESSION['nombre_apellido']) ? trim((string)$_SESSION['nombre_apellido']) : 'USUARIO CONECTADO';
$nombre_usuario = function_exists('mb_convert_case')
    ? mb_convert_case($nombre_usuario_raw, MB_CASE_TITLE, 'UTF-8')
    : ucwords(strtolower($nombre_usuario_raw));

// Tamaño de lote (15, 30 o 50) y desplazamiento para carga progresiva
$limite_req = isset($_GET['limite']) ? intval($_GET['limite']) : 15;
if (!in_array($limite_req, array(15, 30, 50), true)) {
    $limite_req = 15;
}
$offset_req = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;

// Helpers seguros para PHP 5.5.7 y UTF-8
function acta_safe_utf8($val) {
    if (!is_string($val)) return (string)$val;
    if (function_exists('mb_check_encoding') && !mb_check_encoding($val, 'UTF-8')) {
        return utf8_encode($val);
    }
    return $val;
}
function acta_h($val) {
    return htmlspecialchars(acta_safe_utf8($val), ENT_QUOTES, 'UTF-8');
}
function acta_fecha_latam($f) {
    if (empty($f)) return '--/--/----';
    $ts = strtotime($f);
    return $ts ? date('d/m/Y', $ts) : $f;
}
function acta_upper($val) {
    $s = acta_safe_utf8($val);
    return function_exists('mb_strtoupper') ? mb_strtoupper($s, 'UTF-8') : strtoupper($s);
}
function formatear_numero_acta($val) {
    if ($val === null || $val === '') return '-';
    $n = floatval($val);
    if ($n == 0.0) return '0';
    $abs = abs($n);
    $dec = ($abs < 0.01) ? 4 : (($abs < 1) ? 3 : 2);
    $formatted = number_format($n, $dec, ',', '.');
    if (strpos($formatted, ',') !== false) {
        $formatted = rtrim(rtrim($formatted, '0'), ',');
    }
    return $formatted;
}

/**
 * Carga un lote acotado de guardias (offset, limite) + todas sus tablas hijas usando WHERE IN.
 * Pide (limite + 1) filas para saber en O(1) si quedan más guardias en el historial.
 */
function libro_actas_cargar_lote($miconex, $db_connected, $offset, $limite) {
    $out = array(
        'planillas' => array(),
        'hay_mas' => false,
        'map_novedades' => array(),
        'map_tritio' => array(),
        'map_c14' => array(),
        'map_dedo_frio' => array(),
        'map_tareas_diarias' => array(),
        'map_tareas_minizonas' => array(),
        'map_actas_minizonas' => array(),
        'stats' => array(
            'guardias' => 0,
            'tareas_diarias' => 0,
            'tareas_mz' => 0,
            'novedades' => 0
        ),
        'error' => ''
    );

    if (!$db_connected || !$miconex) {
        return $out;
    }

    $offset_int = max(0, (int)$offset);
    $limite_int = max(1, min(50, (int)$limite));
    $fetch_limit = $limite_int + 1;

    // PASO 1: Obtener únicamente las N+1 planillas de este lote
    $q_planillas = "SELECT a.id_planilla_diaria,
                           a.fecha,
                           a.fecha_hora_abierto,
                           a.fecha_hora_cerrado,
                           b.horarios,
                           c.nombre_estado,
                           d.of_apellido,
                           d.of_nombre
                    FROM regrad_planilla_diaria_nueva a
                    LEFT JOIN regrad_turnos b ON b.id_turnos = a.turno
                    LEFT JOIN regrad_estados_planta c ON c.id_estado_planta = a.estado_planta
                    LEFT JOIN regrad_of_rad d ON d.id_of_rad = a.of_rad
                    WHERE a.id_planilla_diaria >= 34
                    ORDER BY a.fecha DESC, a.fecha_hora_abierto DESC
                    LIMIT $offset_int, $fetch_limit";

    $res_pl = @mysql_query($q_planillas, $miconex);
    if (!$res_pl) {
        $out['error'] = mysql_error($miconex);
        return $out;
    }

    $planilla_ids = array();
    while ($row = mysql_fetch_assoc($res_pl)) {
        if (count($out['planillas']) >= $limite_int) {
            $out['hay_mas'] = true;
            break;
        }
        $pid = (int)$row['id_planilla_diaria'];
        if ($pid <= 0) continue;
        $out['planillas'][] = $row;
        $planilla_ids[$pid] = $pid;
    }

    $out['stats']['guardias'] = count($out['planillas']);
    if (empty($planilla_ids)) {
        return $out;
    }

    // PASO 2: Consultar tablas hijas únicamente para los IDs de este lote
    $ids_csv = implode(',', $planilla_ids);

    // 2a. Novedades generales
    $r_nov = @mysql_query("SELECT id_planilla_diaria, novedades FROM regrad_libro_actas WHERE id_planilla_diaria IN ($ids_csv)", $miconex);
    if ($r_nov) {
        while ($rw = mysql_fetch_assoc($r_nov)) {
            $pid = (int)$rw['id_planilla_diaria'];
            $txt = trim(acta_safe_utf8($rw['novedades']));
            if ($txt !== '') {
                if (!isset($out['map_novedades'][$pid])) {
                    $out['map_novedades'][$pid] = $txt;
                } else {
                    $out['map_novedades'][$pid] .= "\n\n" . $txt;
                }
            }
        }
    }

    // 2b. Tritio
    $r_tri = @mysql_query("SELECT id_planilla, vent_gral, vent_reactor FROM regrad_tritio WHERE id_planilla IN ($ids_csv)", $miconex);
    if ($r_tri) {
        while ($rw = mysql_fetch_assoc($r_tri)) {
            $out['map_tritio'][(int)$rw['id_planilla']] = $rw;
        }
    }

    // 2c. Carbono 14
    $r_c14 = @mysql_query("SELECT id_planilla, medicion FROM regrad_carbono14 WHERE id_planilla IN ($ids_csv)", $miconex);
    if ($r_c14) {
        while ($rw = mysql_fetch_assoc($r_c14)) {
            $out['map_c14'][(int)$rw['id_planilla']] = $rw;
        }
    }

    // 2d. Dedo Frío 73120
    $r_df = @mysql_query("SELECT id_planilla, ppm, menor_que FROM regrad_dedo_frio WHERE id_planilla IN ($ids_csv)", $miconex);
    if ($r_df) {
        while ($rw = mysql_fetch_assoc($r_df)) {
            $out['map_dedo_frio'][(int)$rw['id_planilla']] = $rw;
        }
    }

    // 2e. Tareas del Registro Diario común
    $q_td = "SELECT t.id_tarea, t.id_planilla_diaria, t.id_ptr, t.texto_tarea,
                    r.recinto AS nombre_recinto,
                    CONCAT(o2.apellido, ', ', o2.nombre) AS oficial_tarea,
                    CONCAT(o3.apellido, ', ', o3.nombre) AS ayudante_tarea
             FROM regrad_actas_tareas_diarias t
             LEFT JOIN graf_tritio_recintos r ON r.id = t.id_recinto
             LEFT JOIN vista_personal_radioproteccion o2 ON o2.id = t.id_oficial_asociado
             LEFT JOIN vista_personal_radioproteccion o3 ON o3.id = t.id_ayudante
             WHERE t.id_planilla_diaria IN ($ids_csv)
             ORDER BY t.id_tarea ASC";
    $r_td = @mysql_query($q_td, $miconex);
    if ($r_td) {
        while ($rw = mysql_fetch_assoc($r_td)) {
            $pid = (int)$rw['id_planilla_diaria'];
            if (!isset($out['map_tareas_diarias'][$pid])) $out['map_tareas_diarias'][$pid] = array();
            $out['map_tareas_diarias'][$pid][] = $rw;
            $out['stats']['tareas_diarias']++;
        }
    }

    // 2f. Tareas de Minizonas
    $q_tm = "SELECT t.id_registro, t.id_planilla_diaria, t.id_minizonas, t.id_ptr, t.texto_acta,
                    r.recinto AS nombre_recinto,
                    m.nombre_minizonas,
                    CONCAT(o2.of_apellido, ', ', o2.of_nombre) AS oficial_tarea,
                    CONCAT(o3.of_apellido, ', ', o3.of_nombre) AS ayudante_tarea
             FROM regrad_minizonas_registros t
             LEFT JOIN graf_tritio_recintos r ON r.id = t.id_recinto
             LEFT JOIN regrad_minizonas m ON m.id_minizonas = t.id_minizonas
             LEFT JOIN regrad_of_rad o2 ON o2.id_of_rad = t.id_oficial
             LEFT JOIN regrad_of_rad o3 ON o3.id_of_rad = t.id_ayudante
             WHERE t.id_planilla_diaria IN ($ids_csv)
             ORDER BY t.id_registro ASC";
    $r_tm = @mysql_query($q_tm, $miconex);
    if ($r_tm) {
        while ($rw = mysql_fetch_assoc($r_tm)) {
            $pid = (int)$rw['id_planilla_diaria'];
            if (!isset($out['map_tareas_minizonas'][$pid])) $out['map_tareas_minizonas'][$pid] = array();
            $out['map_tareas_minizonas'][$pid][] = $rw;
            $out['stats']['tareas_mz']++;
        }
    }

    // 2g. Actas históricas de Minizonas (vista filtrada estrictamente por WHERE IN)
    $q_am = "SELECT id_minizonas_actas, id_planilla_diaria, id_minizonas, nombre_minizonas,
                    of_apellido, of_nombre, fecha_hora, fecha_hora_agregado, texto_acta
             FROM `reg_rad_resumen_minizonas`
             WHERE id_planilla_diaria IN ($ids_csv)
             ORDER BY id_planilla_diaria DESC, id_minizonas ASC, fecha_hora_agregado DESC";
    $r_am = @mysql_query($q_am, $miconex);
    if ($r_am) {
        $vistos_acta = array();
        while ($rw = mysql_fetch_assoc($r_am)) {
            $pid = (int)$rw['id_planilla_diaria'];
            $aid = isset($rw['id_minizonas_actas']) ? (string)$rw['id_minizonas_actas'] : '';
            $dedup_key = $pid . '|' . $rw['id_minizonas'] . '|' . $aid;
            if ($aid !== '' && isset($vistos_acta[$dedup_key])) continue;
            $vistos_acta[$dedup_key] = true;

            if (!isset($out['map_actas_minizonas'][$pid])) $out['map_actas_minizonas'][$pid] = array();
            $mz_key = (string)$rw['id_minizonas'];
            if (!isset($out['map_actas_minizonas'][$pid][$mz_key])) {
                $out['map_actas_minizonas'][$pid][$mz_key] = array(
                    'nombre' => !empty($rw['nombre_minizonas']) ? acta_safe_utf8($rw['nombre_minizonas']) : ('Minizona ' . $mz_key),
                    'items' => array()
                );
            }
            $out['map_actas_minizonas'][$pid][$mz_key]['items'][] = $rw;
            $out['stats']['tareas_mz']++;
        }
    }

    $out['stats']['novedades'] = count($out['map_novedades']);
    return $out;
}

/**
 * Renderiza el HTML de las tarjetas de guardia de un lote.
 */
function libro_actas_render_tarjetas($lote) {
    if (empty($lote['planillas'])) return '';
    ob_start();
    foreach ($lote['planillas'] as $pl) {
        $pid = (int)$pl['id_planilla_diaria'];
        $es_abierta = empty($pl['fecha_hora_cerrado']);
        $fecha_lbl = acta_fecha_latam($pl['fecha']);
        $turno_lbl = !empty($pl['horarios']) ? acta_safe_utf8($pl['horarios']) : 'Sin turno';
        $oficial_lbl = acta_upper(trim(acta_safe_utf8($pl['of_apellido']) . ', ' . acta_safe_utf8($pl['of_nombre']), ', '));
        if ($oficial_lbl === '') $oficial_lbl = 'SIN ASIGNAR';
        $estado_lbl = !empty($pl['nombre_estado']) ? acta_upper($pl['nombre_estado']) : '';

        $tri = isset($lote['map_tritio'][$pid]) ? $lote['map_tritio'][$pid] : null;
        $vent_gral_str = ($tri && $tri['vent_gral'] !== null && $tri['vent_gral'] !== '') ? formatear_numero_acta(floatval($tri['vent_gral']) / 1000000000.0) : '-';
        $vent_reac_str = ($tri && $tri['vent_reactor'] !== null && $tri['vent_reactor'] !== '') ? formatear_numero_acta(floatval($tri['vent_reactor']) / 1000000000.0) : '-';

        $c14 = isset($lote['map_c14'][$pid]) ? $lote['map_c14'][$pid] : null;
        $c14_str = ($c14 && $c14['medicion'] !== null && $c14['medicion'] !== '') ? formatear_numero_acta(floatval($c14['medicion']) / 1000000.0) : '-';

        $df = isset($lote['map_dedo_frio'][$pid]) ? $lote['map_dedo_frio'][$pid] : null;
        if ($df && $df['ppm'] !== null && $df['ppm'] !== '') {
            $pref_df = (!empty($df['menor_que']) && (int)$df['menor_que'] !== 0) ? '< ' : '';
            $df_str = $pref_df . acta_safe_utf8($df['ppm']);
        } else {
            $df_str = '-';
        }

        $novedad_txt = isset($lote['map_novedades'][$pid]) ? $lote['map_novedades'][$pid] : '';
        $tareas_d = isset($lote['map_tareas_diarias'][$pid]) ? $lote['map_tareas_diarias'][$pid] : array();
        $tareas_m = isset($lote['map_tareas_minizonas'][$pid]) ? $lote['map_tareas_minizonas'][$pid] : array();
        $actas_m  = isset($lote['map_actas_minizonas'][$pid]) ? $lote['map_actas_minizonas'][$pid] : array();

        $tiene_diario = (!empty($tareas_d) || $novedad_txt !== '');
        $tiene_mz = (!empty($tareas_m) || !empty($actas_m));
        $tiene_algo = ($tiene_diario || $tiene_mz);
        ?>
      <article class="guardia-card <?php echo $es_abierta ? 'en-curso' : ''; ?>"
               data-planilla-id="<?php echo $pid; ?>"
               data-has-content="<?php echo $tiene_algo ? '1' : '0'; ?>"
               data-has-diario="<?php echo $tiene_diario ? '1' : '0'; ?>"
               data-has-minizona="<?php echo $tiene_mz ? '1' : '0'; ?>">

        <div class="guardia-head">
          <div class="guardia-head-left">
            <span class="badge-estado <?php echo $es_abierta ? 'badge-open' : 'badge-closed'; ?>">
              <?php echo $es_abierta ? '● EN CURSO' : '🗂️ CERRADA'; ?>
            </span>
            <span class="guardia-fecha">📅 <?php echo acta_h($fecha_lbl); ?></span>
            <span class="guardia-turno">🕒 <?php echo acta_h($turno_lbl); ?></span>
            <span class="guardia-oficial"><span>Oficial:</span><?php echo acta_h($oficial_lbl); ?></span>
            <?php if ($estado_lbl !== '') { ?>
              <span class="tag tag-sky">🏭 <?php echo acta_h($estado_lbl); ?></span>
            <?php } ?>
          </div>
          <div class="guardia-head-right">
            <span class="planilla-id-tag">#<?php echo $pid; ?></span>
            <a href="planilla_en_curso_visualizacion.php?id_planilla=<?php echo $pid; ?>" target="_blank" class="btn-mini" title="Abrir planilla completa en nueva pestaña">
              👁️ Ver Planilla
            </a>
          </div>
        </div>

        <div class="params-bar">
          <div class="param-cell">
            <span class="param-lbl">Ventilación General</span>
            <span class="param-val"><?php echo acta_h($vent_gral_str); ?><span class="param-unit">GBq</span></span>
          </div>
          <div class="param-cell">
            <span class="param-lbl">Ventilación Reactor</span>
            <span class="param-val"><?php echo acta_h($vent_reac_str); ?><span class="param-unit">GBq</span></span>
          </div>
          <div class="param-cell">
            <span class="param-lbl">Emisión C-14</span>
            <span class="param-val"><?php echo acta_h($c14_str); ?><span class="param-unit">MBq</span></span>
          </div>
          <div class="param-cell">
            <span class="param-lbl">Dedo Frío 73120</span>
            <span class="param-val"><?php echo acta_h($df_str); ?><span class="param-unit">ppm</span></span>
          </div>
        </div>

        <div class="guardia-body">
          <?php if (!$tiene_algo) { ?>
            <div class="empty-guardia">— Sin novedades ni tareas asentadas en el Libro de Actas para este turno.</div>
          <?php } ?>

          <?php if ($novedad_txt !== '') { ?>
          <div class="entry-card tipo-novedad">
            <div class="entry-top">
              <div class="entry-title">
                <span>📝 Novedades Generales / Históricas</span>
                <span class="tag tag-sky">Libro de Actas</span>
              </div>
            </div>
            <div class="entry-text"><?php echo nl2br(acta_h($novedad_txt)); ?></div>
          </div>
          <?php } ?>

          <?php foreach ($tareas_d as $td) {
            $recinto_lbl = !empty($td['nombre_recinto']) ? acta_safe_utf8($td['nombre_recinto']) : 'S/E';
            $ptr_val = !empty($td['id_ptr']) ? trim((string)$td['id_ptr']) : '';
            $of_asoc = !empty($td['oficial_tarea']) ? acta_upper($td['oficial_tarea']) : $oficial_lbl;
            $ayu_asoc = !empty($td['ayudante_tarea']) ? acta_upper($td['ayudante_tarea']) : '';
          ?>
          <div class="entry-card tipo-diario">
            <div class="entry-top">
              <div class="entry-title">
                <span>📍 Recinto: <?php echo acta_h($recinto_lbl); ?></span>
                <?php if ($ptr_val !== '') { ?>
                  <span class="tag tag-ptr">📋 PTR: <?php echo acta_h($ptr_val); ?></span>
                <?php } ?>
                <span class="tag tag-green">📋 Oficial Turno Registro Diario</span>
              </div>
            </div>
            <div class="entry-meta">
              <span class="tag tag-green">👷 Oficial: <?php echo acta_h($of_asoc); ?></span>
              <?php if ($ayu_asoc !== '') { ?>
                <span class="tag tag-sky">🤝 Asistió: <?php echo acta_h($ayu_asoc); ?></span>
              <?php } ?>
            </div>
            <div class="entry-text">
              <span class="entry-text-lbl">Observaciones:</span><?php echo nl2br(acta_h($td['texto_tarea'])); ?>
            </div>
          </div>
          <?php } ?>

          <?php foreach ($tareas_m as $tm) {
            $nombre_mz = !empty($tm['nombre_minizonas']) ? acta_safe_utf8($tm['nombre_minizonas']) : ('#' . $tm['id_minizonas']);
            $recinto_lbl = !empty($tm['nombre_recinto']) ? acta_safe_utf8($tm['nombre_recinto']) : 'S/E';
            $ptr_val = !empty($tm['id_ptr']) ? trim((string)$tm['id_ptr']) : '';
            $of_asoc = !empty($tm['oficial_tarea']) ? acta_upper($tm['oficial_tarea']) : $oficial_lbl;
            $ayu_asoc = !empty($tm['ayudante_tarea']) ? acta_upper($tm['ayudante_tarea']) : '';
          ?>
          <div class="entry-card tipo-minizona">
            <div class="entry-top">
              <div class="entry-title">
                <span>📍 Recinto: <?php echo acta_h($recinto_lbl); ?></span>
                <?php if ($ptr_val !== '') { ?>
                  <span class="tag tag-ptr">📋 PTR: <?php echo acta_h($ptr_val); ?></span>
                <?php } ?>
                <span class="tag tag-amber">📍 Minizona <?php echo acta_h($nombre_mz); ?></span>
              </div>
            </div>
            <div class="entry-meta">
              <span class="tag tag-green">👷 Oficial: <?php echo acta_h($of_asoc); ?></span>
              <?php if ($ayu_asoc !== '') { ?>
                <span class="tag tag-sky">🤝 Asistió: <?php echo acta_h($ayu_asoc); ?></span>
              <?php } ?>
            </div>
            <div class="entry-text">
              <span class="entry-text-lbl">Observaciones:</span><?php echo nl2br(acta_h($tm['texto_acta'])); ?>
            </div>
          </div>
          <?php } ?>

          <?php foreach ($actas_m as $mz_grp) { ?>
          <div class="entry-card tipo-acta-mz">
            <div class="entry-top">
              <div class="entry-title">
                <span>📄 Acta Minizona <?php echo acta_h($mz_grp['nombre']); ?></span>
                <span class="tag tag-violet">Resumen Minizona</span>
              </div>
            </div>
            <?php foreach ($mz_grp['items'] as $am_item) {
              $of_am = acta_upper(trim(acta_safe_utf8($am_item['of_apellido']) . ', ' . acta_safe_utf8($am_item['of_nombre']), ', '));
              $fh_am = !empty($am_item['fecha_hora']) ? acta_safe_utf8($am_item['fecha_hora']) : '';
            ?>
            <div class="acta-mz-item">
              <div class="acta-mz-subhead">
                <span>👷 Oficial: <strong><?php echo acta_h($of_am !== '' ? $of_am : $oficial_lbl); ?></strong></span>
                <?php if ($fh_am !== '') { ?>
                  <span class="acta-mz-time">🕒 <?php echo acta_h($fh_am); ?></span>
                <?php } ?>
              </div>
              <div class="entry-text"><?php echo nl2br(acta_h($am_item['texto_acta'])); ?></div>
            </div>
            <?php } ?>
          </div>
          <?php } ?>
        </div>
      </article>
        <?php
    }
    return ob_get_clean();
}

// Ejecutar carga del lote actual
$lote_actual = libro_actas_cargar_lote($miconex, $db_connected, $offset_req, $limite_req);
$html_lote = libro_actas_render_tarjetas($lote_actual);
$tiempo_ms = (int)round((microtime(true) - $t_inicio_carga) * 1000);

// Respuesta AJAX para carga progresiva ("Cargar 15 / 30 más")
if ($es_ajax) {
    header('Content-Type: application/json; charset=utf-8');
    if ($lote_actual['error'] !== '') {
        echo json_encode(array('ok' => false, 'error' => acta_safe_utf8($lote_actual['error'])));
        exit;
    }
    echo json_encode(array(
        'ok' => true,
        'html' => $html_lote,
        'cargadas' => (int)$lote_actual['stats']['guardias'],
        'hay_mas' => (bool)$lote_actual['hay_mas'],
        'siguiente_offset' => $offset_req + (int)$lote_actual['stats']['guardias'],
        'stats' => $lote_actual['stats'],
        'ms' => $tiempo_ms
    ));
    exit;
}

$db_error = $lote_actual['error'];
$cargadas_iniciales = (int)$lote_actual['stats']['guardias'];
$hay_mas_inicial = !empty($lote_actual['hay_mas']);
?>
<!DOCTYPE html>
<html lang="es" data-theme="petrol">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Libro de Actas &bull; Registro Radiológico Online (SyR)</title>
  <script>
    (function() {
      try {
        var saved = localStorage.getItem('regrad_active_theme');
        if (saved) document.documentElement.setAttribute('data-theme', saved);
      } catch (e) {}
    })();
  </script>
  <style>
    :root, [data-theme="petrol"], [data-theme="default"] {
      --bg-page: #061412;
      --bg-card: #0e2722;
      --bg-card-subtle: #163630;
      --bg-input: #081a16;
      --bg-entry: #193e37;
      --border-entry: #2a6358;
      --border-card: #1f4d43;
      --border-subtle: #12332c;
      --text-main: #edfcf6;
      --text-muted: #8ec7b9;
      --text-dim: #52857a;
      --accent-blue: #059669;
      --accent-cyan: #10b981;
      --accent-green: #34d399;
      --accent-sky: #38bdf8;
      --accent-amber: #f59e0b;
      --accent-violet: #a78bfa;
      --accent-red: #ef4444;
      --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }
    [data-theme="navy"] {
      --bg-page: #07111e; --bg-card: #10223b; --bg-card-subtle: #172f50; --bg-input: #091626;
      --bg-entry: #1d3961; --border-entry: #2e558c;
      --border-card: #20416b; --border-subtle: #142a46; --text-main: #f0f6fc;
      --text-muted: #93b0d4; --text-dim: #5b7b9e; --accent-blue: #3b82f6;
      --accent-cyan: #38bdf8; --accent-green: #22c55e; --accent-amber: #f59e0b; --accent-red: #ef4444;
    }
    [data-theme="plum"] {
      --bg-page: #110b17; --bg-card: #21162d; --bg-card-subtle: #2d1f3d; --bg-input: #150e1e;
      --bg-entry: #342447; --border-entry: #503a6b;
      --border-card: #433157; --border-subtle: #271c33; --text-main: #f5f3ff;
      --text-muted: #c4b5fd; --text-dim: #8572a8; --accent-blue: #8b5cf6;
      --accent-cyan: #38bdf8; --accent-green: #34d399; --accent-amber: #fbbf24; --accent-red: #ef4444;
    }
    [data-theme="titanio"] {
      --bg-page: #0f1319; --bg-card: #1b212c; --bg-card-subtle: #242c3a; --bg-input: #12161f;
      --bg-entry: #2c3545; --border-entry: #414f66;
      --border-card: #2e3849; --border-subtle: #202733; --text-main: #f1f5f9;
      --text-muted: #94a3b8; --text-dim: #64748b; --accent-blue: #4f86f7;
      --accent-cyan: #38bdf8; --accent-green: #22c55e; --accent-amber: #f59e0b; --accent-red: #ef4444;
    }
    [data-theme="ambar"] {
      --bg-page: #0a0b0d; --bg-card: #16181f; --bg-card-subtle: #22252e; --bg-input: #0e1014;
      --bg-entry: #212631; --border-entry: #3a404f;
      --border-card: #343744; --border-subtle: #20222a; --text-main: #f8fafc;
      --text-muted: #b4b8c2; --text-dim: #838896; --accent-blue: #d97706;
      --accent-cyan: #f59e0b; --accent-green: #10b981; --accent-amber: #f59e0b; --accent-red: #ef4444;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: var(--bg-page);
      color: var(--text-main);
      font-family: var(--font-sans);
      font-size: 13px;
      line-height: 1.45;
      min-height: 100vh;
    }

    .wrap {
      max-width: 1240px;
      margin: 0 auto;
      padding: 18px 18px 40px;
    }

    /* Topbar HMI */
    .topbar {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 10px;
      padding: 13px 18px;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 14px;
    }
    .brand { display: flex; align-items: center; gap: 12px; }
    .brand-logo-wrap {
      height: 40px;
      min-width: 44px;
      padding: 4px 8px;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid var(--border-card);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .brand-logo-wrap img { max-height: 30px; max-width: 88px; display: block; }
    .brand-fallback { display: none; font-family: var(--font-mono); font-weight: 900; font-size: 11px; color: var(--accent-green); }
    .brand h1 { font-size: 16px; font-weight: 800; color: var(--text-main); }
    .brand-sub { font-size: 11.5px; color: var(--text-muted); margin-top: 1px; }

    .topbar-right { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .user-pill {
      padding: 6px 11px;
      border-radius: 7px;
      background: var(--bg-input);
      border: 1px solid var(--border-subtle);
      font-size: 11.5px;
      color: var(--text-muted);
      font-weight: 600;
    }
    .user-pill strong { color: var(--text-main); }

    .btn-hmi {
      padding: 7px 12px;
      border-radius: 7px;
      border: 1px solid var(--border-card);
      background: var(--bg-card-subtle);
      color: var(--text-main);
      font-weight: 700;
      font-size: 11.5px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      text-decoration: none;
      transition: all 0.14s;
    }
    .btn-hmi:hover { border-color: var(--accent-cyan); background: var(--bg-card); }

    /* Selector de tema */
    .theme-dropdown { position: relative; }
    .theme-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent-cyan); display: inline-block; }
    .theme-menu {
      position: absolute;
      top: calc(100% + 6px);
      right: 0;
      min-width: 155px;
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 8px;
      padding: 5px;
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.55);
      display: none;
      z-index: 200;
    }
    .theme-dropdown.open .theme-menu { display: block; }
    .theme-item {
      width: 100%;
      text-align: left;
      padding: 7px 10px;
      border: none;
      border-radius: 6px;
      background: transparent;
      color: var(--text-muted);
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .theme-item:hover, .theme-item.active { background: var(--bg-card-subtle); color: var(--text-main); }

    /* Barra de herramientas y filtros */
    .toolbar {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 10px;
      padding: 12px 14px;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 14px;
    }
    .search-box { flex: 1 1 280px; position: relative; display: flex; align-items: center; }
    .search-box input {
      width: 100%;
      padding: 8px 34px 8px 32px;
      border-radius: 7px;
      border: 1px solid var(--border-card);
      background: var(--bg-input);
      color: var(--text-main);
      font-size: 12.5px;
      outline: none;
    }
    .search-box input:focus { border-color: var(--accent-cyan); }
    .search-box .search-ico { position: absolute; left: 10px; font-size: 13px; opacity: 0.7; pointer-events: none; }
    .search-box .search-clear {
      position: absolute;
      right: 7px;
      border: none;
      background: transparent;
      color: var(--text-muted);
      cursor: pointer;
      font-size: 14px;
      padding: 2px 5px;
      display: none;
    }

    .filter-chips { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }
    .chip {
      padding: 6px 10px;
      border-radius: 6px;
      border: 1px solid var(--border-card);
      background: var(--bg-input);
      color: var(--text-muted);
      font-size: 11.5px;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.14s;
    }
    .chip:hover { border-color: var(--accent-cyan); color: var(--text-main); }
    .chip.active { background: var(--accent-cyan); border-color: var(--accent-cyan); color: #04120f; }

    .limit-group {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding-left: 8px;
      border-left: 1px solid var(--border-subtle);
    }
    .limit-label { font-size: 11px; color: var(--text-dim); font-weight: 700; margin-right: 3px; }

    /* Resumen rápido */
    .summary-strip {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 10px;
      margin-bottom: 16px;
    }
    .sum-card {
      background: var(--bg-card);
      border: 1px solid var(--border-subtle);
      border-radius: 8px;
      padding: 10px 14px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .sum-lbl { font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-dim); font-weight: 700; }
    .sum-val { font-size: 16px; font-weight: 800; font-family: var(--font-mono); color: var(--text-main); margin-top: 2px; }

    /* Tarjeta de Guardia */
    .guardia-card {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 10px;
      margin-bottom: 16px;
      overflow: hidden;
      transition: border-color 0.15s;
    }
    .guardia-card.en-curso {
      border-color: var(--accent-green);
      box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.2);
    }
    .guardia-card.hidden { display: none; }

    .guardia-head {
      background: var(--bg-card-subtle);
      border-bottom: 1px solid var(--border-card);
      padding: 12px 16px;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }
    .guardia-head-left { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
    .badge-estado {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 3px 8px;
      border-radius: 999px;
      font-size: 10px;
      font-weight: 800;
      letter-spacing: 0.4px;
      text-transform: uppercase;
    }
    .badge-open { background: rgba(16, 185, 129, 0.18); color: var(--accent-green); border: 1px solid rgba(16, 185, 129, 0.45); }
    .badge-closed { background: rgba(148, 163, 184, 0.12); color: var(--text-muted); border: 1px solid var(--border-card); }
    .guardia-fecha { font-size: 14.5px; font-weight: 800; color: var(--text-main); font-family: var(--font-mono); }
    .guardia-turno {
      font-size: 12px;
      font-weight: 700;
      color: var(--accent-cyan);
      padding: 2px 8px;
      border-radius: 5px;
      background: var(--bg-input);
      border: 1px solid var(--border-subtle);
      font-family: var(--font-mono);
    }
    .guardia-oficial { font-size: 12.5px; font-weight: 700; color: var(--text-main); }
    .guardia-oficial span { color: var(--text-dim); font-weight: 600; margin-right: 4px; }

    .guardia-head-right { display: flex; align-items: center; gap: 8px; }
    .planilla-id-tag { font-family: var(--font-mono); font-size: 11px; color: var(--text-dim); font-weight: 700; }
    .btn-mini {
      padding: 5px 10px;
      border-radius: 6px;
      border: 1px solid var(--border-card);
      background: var(--bg-input);
      color: var(--text-main);
      font-size: 11px;
      font-weight: 700;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all 0.14s;
    }
    .btn-mini:hover { border-color: var(--accent-cyan); color: var(--accent-green); }

    /* Parámetros radiológicos */
    .params-bar {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      border-bottom: 1px solid var(--border-subtle);
      background: rgba(0, 0, 0, 0.14);
    }
    .param-cell {
      padding: 9px 14px;
      border-right: 1px solid var(--border-subtle);
      display: flex;
      flex-direction: column;
      gap: 2px;
    }
    .param-cell:last-child { border-right: none; }
    .param-lbl { font-size: 10px; text-transform: uppercase; letter-spacing: 0.45px; color: var(--text-dim); font-weight: 700; }
    .param-val { font-family: var(--font-mono); font-size: 13.5px; font-weight: 800; color: var(--text-main); }
    .param-unit { font-size: 11px; font-weight: 600; color: var(--text-muted); margin-left: 3px; }

    /* Cuerpo de entradas */
    .guardia-body { padding: 13px 16px; display: flex; flex-direction: column; gap: 10px; }

    .entry-card {
      background: var(--bg-entry);
      border: 1px solid var(--border-entry);
      border-left: 4px solid var(--accent-sky);
      border-radius: 7px;
      padding: 11px 14px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.22);
    }
    .entry-card.tipo-novedad  { border-left-color: #38bdf8; }
    .entry-card.tipo-diario   { border-left-color: #10b981; }
    .entry-card.tipo-minizona { border-left-color: #f59e0b; }
    .entry-card.tipo-acta-mz  { border-left-color: #a78bfa; }

    .entry-top { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 6px; }
    .entry-title { font-size: 13px; font-weight: 800; color: var(--text-main); display: flex; flex-wrap: wrap; align-items: center; gap: 7px; }
    .tag {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 2px 7px;
      border-radius: 4px;
      font-size: 10.5px;
      font-weight: 800;
      letter-spacing: 0.2px;
    }
    .tag-sky    { background: rgba(56, 189, 248, 0.14); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.32); }
    .tag-green  { background: rgba(16, 185, 129, 0.14); color: var(--accent-green); border: 1px solid rgba(16, 185, 129, 0.32); }
    .tag-amber  { background: rgba(245, 158, 11, 0.14); color: var(--accent-amber); border: 1px solid rgba(245, 158, 11, 0.35); }
    .tag-violet { background: rgba(167, 139, 250, 0.14); color: #c4b5fd; border: 1px solid rgba(167, 139, 250, 0.35); }
    .tag-ptr    { background: rgba(255, 255, 255, 0.06); color: var(--text-main); border: 1px solid var(--border-card); font-family: var(--font-mono); }

    .entry-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 7px; margin-bottom: 6px; font-size: 11.5px; }
    .entry-text { font-size: 13px; color: var(--text-main); line-height: 1.5; word-break: break-word; }
    .entry-text-lbl { color: var(--text-muted); font-weight: 700; margin-right: 4px; }

    .acta-mz-item { padding: 8px 0; border-top: 1px dashed var(--border-subtle); }
    .acta-mz-item:first-of-type { border-top: none; padding-top: 2px; }
    .acta-mz-subhead { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 6px; font-size: 11.5px; margin-bottom: 4px; color: var(--text-muted); }
    .acta-mz-subhead strong { color: var(--text-main); }
    .acta-mz-time { font-family: var(--font-mono); font-size: 11px; color: var(--text-dim); }

    .empty-guardia { font-size: 12px; color: var(--text-dim); font-style: italic; padding: 4px 2px; }

    /* Barra de paginación progresiva ("Cargar más") */
    .load-more-bar {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 10px;
      padding: 16px 20px;
      margin-top: 18px;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .load-more-info {
      font-size: 12px;
      color: var(--text-muted);
      font-family: var(--font-mono);
    }
    .load-more-info strong { color: var(--text-main); }
    .load-more-actions {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 8px;
    }
    .btn-load-more {
      padding: 10px 18px;
      border-radius: 8px;
      border: none;
      background: var(--accent-cyan);
      color: #04120f;
      font-weight: 900;
      font-size: 12.5px;
      letter-spacing: 0.25px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      transition: transform 0.12s, filter 0.12s, opacity 0.15s;
    }
    .btn-load-more:hover:not(:disabled) { filter: brightness(1.1); transform: translateY(-1px); }
    .btn-load-more:disabled { opacity: 0.6; cursor: wait; }
    .btn-load-more-alt {
      padding: 10px 13px;
      border-radius: 8px;
      border: 1px solid var(--border-card);
      background: var(--bg-card-subtle);
      color: var(--text-main);
      font-weight: 800;
      font-size: 12px;
      cursor: pointer;
      transition: all 0.14s;
    }
    .btn-load-more-alt:hover:not(:disabled) { border-color: var(--accent-cyan); color: var(--accent-green); }
    .btn-load-more-alt:disabled { opacity: 0.55; cursor: wait; }

    .load-more-status {
      width: 100%;
      font-size: 11.5px;
      padding: 8px 11px;
      border-radius: 6px;
      display: none;
    }
    .load-more-status.err {
      display: block;
      background: rgba(239, 68, 68, 0.14);
      border: 1px solid rgba(239, 68, 68, 0.4);
      color: #fca5a5;
    }
    .load-more-done {
      font-size: 12px;
      font-weight: 700;
      color: var(--accent-green);
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .empty-state {
      background: var(--bg-card);
      border: 1px dashed var(--border-card);
      border-radius: 10px;
      padding: 36px 20px;
      text-align: center;
      color: var(--text-muted);
    }
    .empty-state h3 { color: var(--text-main); margin-bottom: 6px; font-size: 15px; }

    .err-banner {
      background: rgba(239, 68, 68, 0.14);
      border: 1px solid rgba(239, 68, 68, 0.45);
      color: #fca5a5;
      border-radius: 8px;
      padding: 12px 15px;
      margin-bottom: 14px;
      font-size: 12.5px;
    }

    .footer-note {
      margin-top: 22px;
      padding-top: 12px;
      border-top: 1px solid var(--border-subtle);
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 8px;
      font-size: 11px;
      color: var(--text-dim);
      font-family: var(--font-mono);
    }

    @media (max-width: 780px) {
      .params-bar { grid-template-columns: repeat(2, 1fr); }
      .param-cell { border-bottom: 1px solid var(--border-subtle); }
      .limit-group { border-left: none; padding-left: 0; }
    }

    @media print {
      body { background: #fff !important; color: #111 !important; }
      .topbar-right, .toolbar, .btn-mini, .load-more-bar, .footer-note { display: none !important; }
      .guardia-card, .entry-card, .topbar {
        background: #fff !important;
        color: #111 !important;
        border-color: #bbb !important;
        break-inside: avoid;
      }
      .guardia-head, .params-bar { background: #f3f4f6 !important; }
      .guardia-fecha, .guardia-oficial, .param-val, .entry-title, .entry-text { color: #111 !important; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <!-- TOPBAR -->
    <header class="topbar">
      <div class="brand">
        <div class="brand-logo-wrap">
          <img src="../images/logo_nasa.png" alt="NA-SA" onerror="this.style.display='none'; document.getElementById('logo-fb').style.display='inline';">
          <span id="logo-fb" class="brand-fallback">NA-SA</span>
        </div>
        <div>
          <h1>Vista Libro de Actas &mdash; <span id="hdr-total-cargadas"><?php echo $cargadas_iniciales; ?></span> registros cargados</h1>
          <div class="brand-sub">Registro Radiológico Online &bull; Central Nuclear Embalse (SyR)</div>
        </div>
      </div>

      <div class="topbar-right">
        <div class="user-pill">👤 <strong><?php echo acta_h($nombre_usuario); ?></strong></div>

        <div class="theme-dropdown" id="theme-dropdown">
          <button type="button" class="btn-hmi" onclick="toggleThemeMenu(event)" title="Cambiar tema">
            <span class="theme-dot" id="theme-dot-cur"></span>
            <span id="theme-label-cur">Verde</span>
            <span style="opacity:0.65;">▾</span>
          </button>
          <div class="theme-menu" id="theme-menu">
            <button type="button" class="theme-item" data-theme="petrol" data-label="Verde" data-color="#10b981" onclick="applyTheme('petrol')"><span class="theme-dot" style="background:#10b981;"></span> Verde Petróleo</button>
            <button type="button" class="theme-item" data-theme="navy" data-label="Azul" data-color="#38bdf8" onclick="applyTheme('navy')"><span class="theme-dot" style="background:#38bdf8;"></span> Azul Abisal</button>
            <button type="button" class="theme-item" data-theme="plum" data-label="Ciruela" data-color="#a78bfa" onclick="applyTheme('plum')"><span class="theme-dot" style="background:#a78bfa;"></span> Ciruela Obsidiana</button>
            <button type="button" class="theme-item" data-theme="titanio" data-label="Titanio" data-color="#64748b" onclick="applyTheme('titanio')"><span class="theme-dot" style="background:#64748b;"></span> Titanio Pizarra</button>
            <button type="button" class="theme-item" data-theme="ambar" data-label="Ámbar" data-color="#f59e0b" onclick="applyTheme('ambar')"><span class="theme-dot" style="background:#f59e0b;"></span> Ámbar SCADA</button>
          </div>
        </div>

        <button type="button" class="btn-hmi" onclick="window.print()">🖨️ Imprimir</button>
        <a href="inicio.php" class="btn-hmi">← Inicio</a>
      </div>
    </header>

    <?php if ($db_error !== '') { ?>
      <div class="err-banner">❌ Error al consultar MySQL: <?php echo acta_h($db_error); ?></div>
    <?php } ?>

    <!-- RESUMEN -->
    <section class="summary-strip">
      <div class="sum-card">
        <div>
          <div class="sum-lbl">Guardias Visibles</div>
          <div class="sum-val" id="kpi-visibles"><?php echo $cargadas_iniciales; ?></div>
        </div>
        <span style="font-size:18px;">🗂️</span>
      </div>
      <div class="sum-card">
        <div>
          <div class="sum-lbl">Tareas Reg. Diario</div>
          <div class="sum-val" id="kpi-diario" style="color:var(--accent-green);"><?php echo (int)$lote_actual['stats']['tareas_diarias']; ?></div>
        </div>
        <span style="font-size:18px;">📋</span>
      </div>
      <div class="sum-card">
        <div>
          <div class="sum-lbl">Tareas / Actas Minizonas</div>
          <div class="sum-val" id="kpi-minizonas" style="color:var(--accent-amber);"><?php echo (int)$lote_actual['stats']['tareas_mz']; ?></div>
        </div>
        <span style="font-size:18px;">📍</span>
      </div>
      <div class="sum-card">
        <div>
          <div class="sum-lbl">Novedades Generales</div>
          <div class="sum-val" id="kpi-novedades" style="color:var(--accent-sky);"><?php echo (int)$lote_actual['stats']['novedades']; ?></div>
        </div>
        <span style="font-size:18px;">📝</span>
      </div>
    </section>

    <!-- BARRA DE FILTROS INSTANTÁNEOS -->
    <section class="toolbar">
      <div class="search-box">
        <span class="search-ico">🔍</span>
        <input type="text" id="inp-busqueda" placeholder="Filtrar por recinto, PTR, oficial, minizona, fecha o palabra clave..." oninput="aplicarFiltros()">
        <button type="button" class="search-clear" id="btn-clear-search" onclick="limpiarBusqueda()" title="Limpiar">&times;</button>
      </div>

      <div class="filter-chips">
        <button type="button" class="chip active" data-filter="all" onclick="setModoFiltro('all', this)">Todas</button>
        <button type="button" class="chip" data-filter="con_contenido" onclick="setModoFiltro('con_contenido', this)">Con Novedades / Tareas</button>
        <button type="button" class="chip" data-filter="diario" onclick="setModoFiltro('diario', this)">Reg. Diario</button>
        <button type="button" class="chip" data-filter="minizona" onclick="setModoFiltro('minizona', this)">Minizonas</button>

        <div class="limit-group">
          <span class="limit-label">Inicio:</span>
          <a href="?limite=15" class="chip <?php echo $limite_req === 15 ? 'active' : ''; ?>">15</a>
          <a href="?limite=30" class="chip <?php echo $limite_req === 30 ? 'active' : ''; ?>">30</a>
          <a href="?limite=50" class="chip <?php echo $limite_req === 50 ? 'active' : ''; ?>">50</a>
        </div>
      </div>
    </section>

    <!-- LISTADO DE GUARDIAS -->
    <main id="lista-guardias">
      <?php if ($cargadas_iniciales === 0) { ?>
        <div class="empty-state">
          <h3>No existen registros en el Libro de Actas</h3>
          <p>No se encontraron planillas radiológicas disponibles para mostrar.</p>
        </div>
      <?php } else { ?>
        <div id="guardias-container"><?php echo $html_lote; ?></div>
      <?php } ?>

      <div class="empty-state" id="no-matches-box" style="display: none;">
        <h3>Sin coincidencias con el filtro aplicado</h3>
        <p>Probá con otra palabra clave, cambiá la categoría o cargá más guardias anteriores debajo.</p>
      </div>
    </main>

    <!-- PAGINACIÓN PROGRESIVA AL FINAL -->
    <?php if ($cargadas_iniciales > 0) { ?>
    <section class="load-more-bar" id="paginacion-actas">
      <div class="load-more-info">
        Mostrando <strong id="lbl-cargadas-total"><?php echo $cargadas_iniciales; ?></strong> guardias en memoria
        <span id="lbl-filtro-extra" style="display:none;"></span>
      </div>

      <div class="load-more-actions" id="load-more-actions">
        <?php if ($hay_mas_inicial) { ?>
          <button type="button" class="btn-load-more" id="btn-cargar-mas" onclick="cargarMasGuardias(<?php echo (int)$limite_req; ?>)">
            ➕ Cargar <?php echo (int)$limite_req; ?> más
          </button>
          <?php if ($limite_req === 15) { ?>
            <button type="button" class="btn-load-more-alt" id="btn-cargar-mas-alt" onclick="cargarMasGuardias(30)">
              +30 más
            </button>
          <?php } else { ?>
            <button type="button" class="btn-load-more-alt" id="btn-cargar-mas-alt" onclick="cargarMasGuardias(15)">
              +15 más
            </button>
          <?php } ?>
        <?php } else { ?>
          <span class="load-more-done">✔️ Fin del historial de Libro de Actas</span>
        <?php } ?>
        <button type="button" class="btn-hmi" onclick="window.scrollTo({ top: 0, behavior: 'smooth' });" title="Volver arriba">↑ Arriba</button>
      </div>

      <div class="load-more-status" id="load-more-status"></div>
    </section>
    <?php } ?>

    <footer class="footer-note">
      <span>NA-SA &bull; Central Nuclear Embalse &bull; Libro de Actas Radioprotección (SyR)</span>
      <span id="lbl-tiempo-consulta">⚡ Consulta optimizada: <?php echo (int)$tiempo_ms; ?> ms</span>
    </footer>
  </div>

  <script>
    var modoFiltroActual = 'all';
    var currentOffset = <?php echo (int)$cargadas_iniciales; ?>;
    var defaultBatchSize = <?php echo (int)$limite_req; ?>;
    var hayMasRegistros = <?php echo $hay_mas_inicial ? 'true' : 'false'; ?>;
    var cargandoMas = false;

    var totalDiario = <?php echo (int)$lote_actual['stats']['tareas_diarias']; ?>;
    var totalMinizonas = <?php echo (int)$lote_actual['stats']['tareas_mz']; ?>;
    var totalNovedades = <?php echo (int)$lote_actual['stats']['novedades']; ?>;

    function normalizarTexto(s) {
      return (s || '').toLowerCase()
        .replace(/[áàä]/g, 'a').replace(/[éèë]/g, 'e').replace(/[íìï]/g, 'i')
        .replace(/[óòö]/g, 'o').replace(/[úùü]/g, 'u').replace(/ñ/g, 'n');
    }

    function setModoFiltro(modo, btn) {
      modoFiltroActual = modo;
      var btns = document.querySelectorAll('.filter-chips button.chip[data-filter]');
      for (var i = 0; i < btns.length; i++) {
        btns[i].classList.remove('active');
      }
      if (btn) btn.classList.add('active');
      aplicarFiltros();
    }

    function limpiarBusqueda() {
      var inp = document.getElementById('inp-busqueda');
      if (inp) {
        inp.value = '';
        inp.focus();
      }
      aplicarFiltros();
    }

    function aplicarFiltros() {
      var inp = document.getElementById('inp-busqueda');
      var qRaw = inp ? inp.value.trim() : '';
      var q = normalizarTexto(qRaw);
      var btnClear = document.getElementById('btn-clear-search');
      if (btnClear) btnClear.style.display = qRaw.length > 0 ? 'block' : 'none';

      var cards = document.querySelectorAll('.guardia-card');
      var visibles = 0;
      for (var i = 0; i < cards.length; i++) {
        var c = cards[i];
        var okModo = true;
        if (modoFiltroActual === 'con_contenido') {
          okModo = (c.getAttribute('data-has-content') === '1');
        } else if (modoFiltroActual === 'diario') {
          okModo = (c.getAttribute('data-has-diario') === '1');
        } else if (modoFiltroActual === 'minizona') {
          okModo = (c.getAttribute('data-has-minizona') === '1');
        }

        var okTexto = true;
        if (q !== '') {
          var txt = normalizarTexto(c.textContent || c.innerText || '');
          okTexto = (txt.indexOf(q) !== -1);
        }

        var show = okModo && okTexto;
        c.classList.toggle('hidden', !show);
        if (show) visibles++;
      }

      var kpiEl = document.getElementById('kpi-visibles');
      if (kpiEl) kpiEl.textContent = String(visibles);

      var extraEl = document.getElementById('lbl-filtro-extra');
      if (extraEl) {
        if (visibles !== cards.length) {
          extraEl.textContent = ' (' + visibles + ' coinciden con el filtro)';
          extraEl.style.display = 'inline';
        } else {
          extraEl.style.display = 'none';
        }
      }

      var noMatch = document.getElementById('no-matches-box');
      if (noMatch && cards.length > 0) {
        noMatch.style.display = (visibles === 0) ? 'block' : 'none';
      }
    }

    function cargarMasGuardias(cantidad) {
      if (cargandoMas || !hayMasRegistros) return;
      var cant = parseInt(cantidad, 10) || defaultBatchSize || 15;
      cargandoMas = true;

      var btnMain = document.getElementById('btn-cargar-mas');
      var btnAlt = document.getElementById('btn-cargar-mas-alt');
      var statusEl = document.getElementById('load-more-status');
      if (statusEl) { statusEl.className = 'load-more-status'; statusEl.textContent = ''; }
      if (btnMain) { btnMain.disabled = true; btnMain.textContent = '⏳ Cargando ' + cant + ' guardias...'; }
      if (btnAlt) { btnAlt.disabled = true; }

      var url = 'libro_actas.php?ajax=1&offset=' + encodeURIComponent(currentOffset) + '&limite=' + encodeURIComponent(cant);
      fetch(url)
        .then(function(resp) { return resp.json(); })
        .then(function(data) {
          cargandoMas = false;
          if (!data || !data.ok) {
            if (statusEl) {
              statusEl.className = 'load-more-status err';
              statusEl.textContent = '❌ ' + (data && data.error ? data.error : 'No se pudieron cargar más guardias.');
            }
            if (btnMain) { btnMain.disabled = false; btnMain.textContent = '➕ Cargar ' + defaultBatchSize + ' más'; }
            if (btnAlt) { btnAlt.disabled = false; }
            return;
          }

          var container = document.getElementById('guardias-container');
          if (container && data.html) {
            var tmp = document.createElement('div');
            tmp.innerHTML = data.html;
            while (tmp.firstChild) {
              container.appendChild(tmp.firstChild);
            }
          }

          currentOffset = typeof data.siguiente_offset === 'number' ? data.siguiente_offset : (currentOffset + (data.cargadas || 0));
          hayMasRegistros = !!data.hay_mas;

          if (data.stats) {
            totalDiario += (data.stats.tareas_diarias || 0);
            totalMinizonas += (data.stats.tareas_mz || 0);
            totalNovedades += (data.stats.novedades || 0);
          }

          var elTot = document.getElementById('lbl-cargadas-total');
          if (elTot) elTot.textContent = String(currentOffset);
          var elHdr = document.getElementById('hdr-total-cargadas');
          if (elHdr) elHdr.textContent = String(currentOffset);
          var elD = document.getElementById('kpi-diario');
          if (elD) elD.textContent = String(totalDiario);
          var elM = document.getElementById('kpi-minizonas');
          if (elM) elM.textContent = String(totalMinizonas);
          var elN = document.getElementById('kpi-novedades');
          if (elN) elN.textContent = String(totalNovedades);

          var elMs = document.getElementById('lbl-tiempo-consulta');
          if (elMs && typeof data.ms === 'number') {
            elMs.textContent = '⚡ Último lote (+' + (data.cargadas || 0) + ' guardias): ' + data.ms + ' ms';
          }

          if (!hayMasRegistros) {
            var actionsEl = document.getElementById('load-more-actions');
            if (actionsEl) {
              actionsEl.innerHTML = '<span class="load-more-done">✔️ Fin del historial de Libro de Actas</span>'
                + '<button type="button" class="btn-hmi" onclick="window.scrollTo({ top: 0, behavior: \'smooth\' });">↑ Arriba</button>';
            }
          } else {
            if (btnMain) { btnMain.disabled = false; btnMain.textContent = '➕ Cargar ' + defaultBatchSize + ' más'; }
            if (btnAlt) { btnAlt.disabled = false; }
          }

          aplicarFiltros();
        })
        .catch(function() {
          cargandoMas = false;
          if (statusEl) {
            statusEl.className = 'load-more-status err';
            statusEl.textContent = '❌ Error de conexión al consultar más registros. Intentá nuevamente.';
          }
          if (btnMain) { btnMain.disabled = false; btnMain.textContent = '➕ Cargar ' + defaultBatchSize + ' más'; }
          if (btnAlt) { btnAlt.disabled = false; }
        });
    }

    // Sincronización de tema con demo_planilla.php e inicio.php
    function syncThemeUI(themeName) {
      var items = document.querySelectorAll('#theme-menu .theme-item');
      var labelEl = document.getElementById('theme-label-cur');
      var dotEl = document.getElementById('theme-dot-cur');
      for (var i = 0; i < items.length; i++) {
        var it = items[i];
        var match = it.getAttribute('data-theme') === themeName;
        it.classList.toggle('active', match);
        if (match) {
          if (labelEl) labelEl.textContent = it.getAttribute('data-label') || 'Verde';
          if (dotEl) dotEl.style.background = it.getAttribute('data-color') || '#10b981';
        }
      }
    }

    function applyTheme(themeName) {
      document.documentElement.setAttribute('data-theme', themeName);
      try { localStorage.setItem('regrad_active_theme', themeName); } catch (e) {}
      syncThemeUI(themeName);
      var dd = document.getElementById('theme-dropdown');
      if (dd) dd.classList.remove('open');
    }

    function toggleThemeMenu(e) {
      if (e) e.stopPropagation();
      var dd = document.getElementById('theme-dropdown');
      if (dd) dd.classList.toggle('open');
    }

    document.addEventListener('click', function(e) {
      var dd = document.getElementById('theme-dropdown');
      if (dd && dd.classList.contains('open') && !dd.contains(e.target)) {
        dd.classList.remove('open');
      }
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        var dd = document.getElementById('theme-dropdown');
        if (dd) dd.classList.remove('open');
      }
    });

    syncThemeUI(document.documentElement.getAttribute('data-theme') || 'petrol');
  </script>
</body>
</html>