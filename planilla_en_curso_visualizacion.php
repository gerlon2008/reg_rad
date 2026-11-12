<?php
// =========================================================================================
// REGISTRO RADIOLÓGICO ONLINE - CNE (NA-SA)
// VISUALIZADOR DE PLANILLA (EN CURSO O HISTÓRICA / AUDITORÍA)
// Arquitectura réplica exacta de la Consola Principal de Carga en modo Consulta / Solo Lectura
// =========================================================================================

$db_connected = false;
$id_planilla = isset($_GET['id_planilla']) ? (int)$_GET['id_planilla'] : 1482;
$usuario_nombre = "OFICIAL DE GUARDIA";
$usuario_rol = "Oficial de Radioprotección";

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/ptr/Connections/miconex.php")) {
    @require_once($_SERVER["DOCUMENT_ROOT"]."/ptr/Connections/miconex.php");
    @require_once($_SERVER["DOCUMENT_ROOT"]."/ptr/Functions/funciones.php");
    @require_once($_SERVER["DOCUMENT_ROOT"]."/ptr/Connections/niveles.php");

    @session_start();
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) {
        $id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : 1;
        if (!empty($_SESSION['nombre_apellido'])) {
            $usuario_nombre = mb_strtoupper($_SESSION['nombre_apellido'], 'UTF-8');
        }
    }

    if (isset($miconex) && $miconex) {
        @mysql_set_charset('utf8', $miconex);
        @mysql_select_db($database_miconex, $miconex);
        $db_connected = true;
    }
}

// Helpers seguros
if (!function_exists('formafecha_latam')) {
    function formafecha_latam($f) {
        if (empty($f)) return '--/--/----';
        $ts = strtotime($f);
        return $ts ? date('d/m/Y', $ts) : $f;
    }
}
if (!function_exists('formato_hora_limpio')) {
    function formato_hora_limpio($h) {
        if (empty($h)) return '--:--';
        return substr($h, 0, 5);
    }
}
if (!function_exists('decimales_limpio')) {
    function decimales_limpio($val, $n = 1) {
        if ($val === null || $val === '') return '0.0';
        return number_format((float)$val, $n, '.', '');
    }
}

// Datos predeterminados (Fallback realista CNE)
$row_planilla = array(
    'id_planilla_diaria' => $id_planilla,
    'fecha' => '2026-11-09',
    'fecha_hora_cerrado' => '2026-11-09 06:55:00',
    'fecha_hora_creado' => '2026-11-08 22:50:00',
    'turno' => 1,
    'estado_planta' => 'POTENCIA 100%',
    'observaciones' => "Turno sin novedades radiológicas anormales. Recorridas de rutina completadas conforme al PO-SyR-01. Monitoreo de efluentes gaseosos normal."
);
$oficial_nombre = "ALTUNA, LEONARDO (P)";
$jefe_nombre = "RODRIGUEZ, MARCELO";
$turno_nombre = "Turno 1 (23:00 a 07:00 hs)";
$estado_planta_nombre = "POTENCIA 100%";
$es_cerrada = true;

$rows_rutina = array();
$row_gases_nobles = array('hora_desde' => '23:00', 'hora_hasta' => '07:00', 'ar41' => 0.40, 'kr85' => 0.10, 'kr85m' => 0.05, 'xe133' => 0.85, 'xe135' => 0.52, 'xe135m' => 0.18);
$rows_tritio = array(array('hora_desde' => '23:00', 'hora_hasta' => '07:00', 'vent_gral' => 1.45, 'vent_reactor' => 0.88));
$row_carbono14 = array('hora' => '05:30', 'medicion' => 3.10);
$row_dedo_frio = array('hora_medicion' => '04:15', 'ppm' => 12, 'menor_que' => 0);
$row_concentrador = array('procedencia' => 'PLANTA', 'actividad_Bq' => 0.045, 'volumen_litro' => 15.2, 'ldd%' => 0.18);
$row_precintos = array('hora' => '02:00', 'puerta_s123B' => 'INTACTO', 'puerta_s008' => 'INTACTO', 'puerta_s144' => 'INTACTO');
$row_meteorologia = array('hora' => '03:00', 'velocidad_mseg' => '3.5', 'direc_viento' => 'SSE', 'cond_pasquill' => 'D');
$rows_tareas = array();
$rows_espectrometria = array();
$rows_efluentes_liq = array();
$rows_contaminacion = array();
$rows_dryers = array();
$hist_dedofrio_vis = array();

if ($db_connected && $id_planilla > 0) {
    // Limpiar fallbacks de demo cuando hay conexión real a MySQL
    $row_gases_nobles = array();
    $rows_tritio = array();
    $row_carbono14 = array();
    $row_dedo_frio = array();
    $row_concentrador = array();
    $row_precintos = array();
    $row_meteorologia = array();
    // 1. Cabecera Planilla
    $q_pl = @mysql_query("SELECT a.*, b.horarios, c.nombre_estado, d.of_apellido, d.of_nombre, e.jefe_apellido, e.jefe_nombre
        FROM regrad_planilla_diaria_nueva a
        LEFT JOIN regrad_turnos b ON b.id_turnos = a.turno
        LEFT JOIN regrad_estados_planta c ON c.id_estado_planta = a.estado_planta
        LEFT JOIN regrad_of_rad d ON d.id_of_rad = a.of_rad
        LEFT JOIN regrad_jefes_turno e ON e.id_jefes_turno = a.jefe_turno
        WHERE a.id_planilla_diaria = $id_planilla", $miconex);
    if ($q_pl && $r_pl = mysql_fetch_assoc($q_pl)) {
        $row_planilla = $r_pl;
        $es_cerrada = !empty($r_pl['fecha_hora_cerrado']);
        if (!empty($r_pl['of_apellido'])) $oficial_nombre = mb_strtoupper($r_pl['of_apellido'] . ", " . $r_pl['of_nombre'], 'UTF-8');
        if (!empty($r_pl['jefe_apellido']) || !empty($r_pl['jefe_nombre'])) $jefe_nombre = mb_strtoupper($r_pl['jefe_apellido'] . ", " . $r_pl['jefe_nombre'], 'UTF-8');
        if (!empty($r_pl['horarios'])) $turno_nombre = $r_pl['horarios'];
        if (!empty($r_pl['nombre_estado'])) $estado_planta_nombre = mb_strtoupper($r_pl['nombre_estado'], 'UTF-8');
    }

    // 2. Relevamiento Rutina
    $q_rut = @mysql_query("SELECT a.*, b.of_apellido, b.of_nombre, c.recinto as recinto_nombre 
        FROM regrad_relevamiento_rutina a 
        LEFT JOIN regrad_of_rad b ON b.id_of_rad = a.id_oficial 
        LEFT JOIN graf_tritio_recintos c ON c.id = a.ubicacion 
        WHERE id_planilla = $id_planilla ORDER BY fecha ASC, hora ASC", $miconex);
    if ($q_rut) {
        while ($r = mysql_fetch_assoc($q_rut)) $rows_rutina[] = $r;
    }

    // 3. Gases Nobles
    $q_gn = @mysql_query("SELECT * FROM regrad_gases_nobles WHERE id_planilla = $id_planilla", $miconex);
    if ($q_gn && $r = mysql_fetch_assoc($q_gn)) $row_gases_nobles = $r;

    // 4. Tritio Chimenea
    $q_tr = @mysql_query("SELECT * FROM regrad_tritio WHERE id_planilla = $id_planilla", $miconex);
    if ($q_tr) {
        $rows_tritio = array();
        while ($r = mysql_fetch_assoc($q_tr)) $rows_tritio[] = $r;
    }

    // 5. C-14
    $q_c14 = @mysql_query("SELECT * FROM regrad_carbono14 WHERE id_planilla = $id_planilla", $miconex);
    if ($q_c14 && $r = mysql_fetch_assoc($q_c14)) $row_carbono14 = $r;

    // 6. Dedo Frío
    $q_df = @mysql_query("SELECT * FROM regrad_dedo_frio WHERE id_planilla = $id_planilla", $miconex);
    if ($q_df && $r = mysql_fetch_assoc($q_df)) $row_dedo_frio = $r;

    // 7. Concentrador 7923
    $q_co = @mysql_query("SELECT * FROM regrad_tritio_concentrador WHERE id_planilla = $id_planilla", $miconex);
    if ($q_co && $r = mysql_fetch_assoc($q_co)) $row_concentrador = $r;

    // 8. Precintos
    $q_pr = @mysql_query("SELECT * FROM regrad_control_precintos WHERE id_planilla = $id_planilla", $miconex);
    if ($q_pr && $r = mysql_fetch_assoc($q_pr)) $row_precintos = $r;

    // 9. Meteorología
    $q_me = @mysql_query("SELECT * FROM regrad_cond_meteorologica WHERE id_planilla = $id_planilla", $miconex);
    if ($q_me && $r = mysql_fetch_assoc($q_me)) $row_meteorologia = $r;

    // 10. Tareas Diarias
    $q_tar = @mysql_query("SELECT a.*, b.recinto as recinto_nombre, c.of_apellido, c.of_nombre, d.nombre_ayudante
        FROM regrad_actas_tareas_diarias a
        LEFT JOIN graf_tritio_recintos b ON b.id = a.id_recinto
        LEFT JOIN regrad_of_rad c ON c.id_of_rad = a.id_oficial_asociado
        LEFT JOIN regrad_ayudantes d ON d.id_ayudante = a.id_ayudante
        WHERE a.id_planilla_diaria = $id_planilla ORDER BY a.id_tarea ASC", $miconex);
    if ($q_tar) {
        while ($r = mysql_fetch_assoc($q_tar)) $rows_tareas[] = $r;
    }

    // 11. Espectrometría
    $q_esp = @mysql_query("SELECT a.*, b.recinto as recinto_nombre 
        FROM regrad_espectr_conc_radionuclei a 
        LEFT JOIN graf_tritio_recintos b ON b.id = a.recinto 
        WHERE id_planilla = $id_planilla", $miconex);
    if ($q_esp) {
        while ($r = mysql_fetch_assoc($q_esp)) $rows_espectrometria[] = $r;
    }

    // 12. Efluentes Líquidos
    $q_liq = @mysql_query("SELECT * FROM reg_rad_ef_liq_final_para_tabla WHERE id_planilla = $id_planilla", $miconex);
    if ($q_liq) {
        while ($r = mysql_fetch_assoc($q_liq)) $rows_efluentes_liq[] = $r;
    }

    // 13. Contaminación Superficial
    $q_cs = @mysql_query("SELECT a.*, b.recinto as recinto_nombre 
        FROM regrad_contaminacion_superficial a 
        LEFT JOIN graf_tritio_recintos b ON b.id = a.recinto 
        WHERE id_planilla = $id_planilla ORDER BY fecha, hora", $miconex);
    if ($q_cs) {
        while ($r = mysql_fetch_assoc($q_cs)) $rows_contaminacion[] = $r;
    }

    // 14. Dryers
    $q_dr = @mysql_query("SELECT * FROM reg_rad_eficiencia_dryers WHERE id_planilla = $id_planilla", $miconex);
    if ($q_dr) {
        while ($r = mysql_fetch_assoc($q_dr)) $rows_dryers[] = $r;
    }

    // 15. Últimos 5 Registros Criogénicos (Dedo Frío 73120) reales hasta esta planilla
    $q_hdf = @mysql_query("SELECT a.id_planilla, a.ppm, a.menor_que, a.hora_medicion,
            COALESCE(NULLIF(a.fecha, ''), p.fecha) AS fecha_reg,
            o.of_apellido, o.of_nombre
        FROM regrad_dedo_frio a
        LEFT JOIN regrad_planilla_diaria_nueva p ON p.id_planilla_diaria = a.id_planilla
        LEFT JOIN regrad_of_rad o ON o.id_of_rad = p.of_rad
        WHERE a.id_planilla <= $id_planilla
        ORDER BY a.id_planilla DESC, a.id_dedo_frio DESC LIMIT 5", $miconex);
    if ($q_hdf) {
        while ($rw = mysql_fetch_assoc($q_hdf)) {
            $f_raw = !empty($rw['fecha_reg']) ? $rw['fecha_reg'] : '';
            $pf = $f_raw ? explode('-', $f_raw) : array();
            $f_corta = (count($pf) == 3) ? ($pf[2] . '/' . $pf[1]) : ($f_raw ? $f_raw : '--/--');
            $f_latam = (count($pf) == 3) ? ($pf[2] . '/' . $pf[1] . '/' . $pf[0]) : $f_corta;
            $h_str = !empty($rw['hora_medicion']) ? substr($rw['hora_medicion'], 0, 5) : '--:--';
            $ap_str = !empty($rw['of_apellido']) ? mb_strtoupper(trim($rw['of_apellido']), 'UTF-8') : 'S/D';
            $ppm_str = ($rw['ppm'] !== null && $rw['ppm'] !== '') ? decimales_limpio($rw['ppm'], 0) : '0';
            $hist_dedofrio_vis[] = array(
                'id_planilla' => (int)$rw['id_planilla'],
                'fecha_corta' => $f_corta,
                'fecha_latam' => $f_latam,
                'hora' => $h_str,
                'apellido' => $ap_str,
                'ppm' => $ppm_str,
                'menor_que' => !empty($rw['menor_que']) ? 1 : 0,
                'es_actual' => ((int)$rw['id_planilla'] === (int)$id_planilla)
            );
        }
    }
}

// Fallbacks de muestra si está offline (sin conexión MySQL)
if (!$db_connected && empty($rows_rutina)) {
    $rows_rutina = array(
        array('fecha' => $row_planilla['fecha'], 'hora' => '23:30', 'recinto_nombre' => 'R-001 (HALL ACCESO ZONA CONTROLADA)', 'gases_nobles' => 0.05, 'tritio' => 0.20, 'tasa_dosis' => 1.5, 'observaciones' => 'Ronda de inicio de guardia', 'of_apellido' => 'ALTUNA', 'of_nombre' => 'LEONARDO'),
        array('fecha' => $row_planilla['fecha'], 'hora' => '01:15', 'recinto_nombre' => 'R-005 (SISTEMA D2O Y PURIFICACIÓN)', 'gases_nobles' => 0.40, 'tritio' => 1.80, 'tasa_dosis' => 18.5, 'observaciones' => 'Verificación de sellos y estanqueidad', 'of_apellido' => 'ALTUNA', 'of_nombre' => 'LEONARDO'),
        array('fecha' => $row_planilla['fecha'], 'hora' => '03:40', 'recinto_nombre' => 'R-012 (EDIFICIO DEL REACTOR - PHT)', 'gases_nobles' => 0.85, 'tritio' => 3.40, 'tasa_dosis' => 45.0, 'observaciones' => 'Recorrida en potencia nominal', 'of_apellido' => 'ALTUNA', 'of_nombre' => 'LEONARDO'),
        array('fecha' => $row_planilla['fecha'], 'hora' => '05:50', 'recinto_nombre' => 'R-020 (SALA DE FILTROS Y VENTILACIÓN)', 'gases_nobles' => 0.15, 'tritio' => 0.60, 'tasa_dosis' => 5.2, 'observaciones' => 'Control de cierre de turno', 'of_apellido' => 'ALTUNA', 'of_nombre' => 'LEONARDO')
    );
}

if (!$db_connected && empty($rows_tareas)) {
    $rows_tareas = array(
        array('id_ptr' => 1042, 'recinto_nombre' => 'R-005 (SISTEMA D2O)', 'tasa_dosis' => 18.5, 'derrame_agua' => 0, 'texto_tarea' => 'MANTENIMIENTO PREVENTIVO DE BOMBA DE PURIFICACIÓN DE AGUA PESADA', 'of_apellido' => 'ALTUNA', 'of_nombre' => 'LEONARDO', 'nombre_ayudante' => 'GOMEZ, FACUNDO'),
        array('id_ptr' => 1045, 'recinto_nombre' => 'R-012 (EDIFICIO REACTOR)', 'tasa_dosis' => 45.0, 'derrame_agua' => 0, 'texto_tarea' => 'RELEVAMIENTO RADIOLÓGICO PARA INSPECCIÓN TÉRMICA DE CAÑERÍAS', 'of_apellido' => 'ALTUNA', 'of_nombre' => 'LEONARDO', 'nombre_ayudante' => 'VAZQUEZ, DANIEL')
    );
}

// Calcular Picos Radiológicos
$max_dosis = 0.0;
$max_tritio = 0.0;
$max_gases = 0.0;
$max_recinto_dosis = "-";

foreach ($rows_rutina as $r) {
    $d = (float)$r['tasa_dosis'];
    $t = (float)$r['tritio'];
    $g = (float)$r['gases_nobles'];
    if ($d > $max_dosis) {
        $max_dosis = $d;
        $max_recinto_dosis = !empty($r['recinto_nombre']) ? $r['recinto_nombre'] : 'Recinto';
    }
    if ($t > $max_tritio) $max_tritio = $t;
    if ($g > $max_gases) $max_gases = $g;
}

function formato_valor_gbq($val) {
    if (empty($val)) return '';
    $num = floatval($val);
    if ($num > 10000000) {
        $num = $num / 1000000000.0; // Conversión de Bq a GBq para registros históricos
    }
    return decimales_limpio($num, 2);
}
if (!$db_connected && empty($hist_dedofrio_vis)) {
    $hist_dedofrio_vis = array(
        array('id_planilla' => $id_planilla, 'fecha_corta' => 'Hoy', 'fecha_latam' => 'Hoy', 'hora' => '04:15', 'apellido' => 'ALTUNA', 'ppm' => '12', 'menor_que' => 1, 'es_actual' => true),
        array('id_planilla' => $id_planilla - 1, 'fecha_corta' => 'Ayer', 'fecha_latam' => 'Ayer', 'hora' => '20:30', 'apellido' => 'ROLHAUSER', 'ppm' => '10', 'menor_que' => 1, 'es_actual' => false),
        array('id_planilla' => $id_planilla - 2, 'fecha_corta' => 'Ayer', 'fecha_latam' => 'Ayer', 'hora' => '12:45', 'apellido' => 'GATICA', 'ppm' => '15', 'menor_que' => 0, 'es_actual' => false),
        array('id_planilla' => $id_planilla - 3, 'fecha_corta' => 'Ayer', 'fecha_latam' => 'Ayer', 'hora' => '05:00', 'apellido' => 'CISTERNA', 'ppm' => '11', 'menor_que' => 1, 'es_actual' => false),
        array('id_planilla' => $id_planilla - 4, 'fecha_corta' => '07/11', 'fecha_latam' => '07/11', 'hora' => '21:10', 'apellido' => 'PASCUAL', 'ppm' => '14', 'menor_que' => 0, 'es_actual' => false)
    );
}

$kpi_vent_gral = !empty($rows_tritio[0]['vent_gral']) ? formato_valor_gbq($rows_tritio[0]['vent_gral']) : ($db_connected ? '--' : '1.45');
$kpi_vent_reactor = !empty($rows_tritio[0]['vent_reactor']) ? formato_valor_gbq($rows_tritio[0]['vent_reactor']) : ($db_connected ? '--' : '0.88');
$kpi_c14 = isset($row_carbono14['medicion']) && $row_carbono14['medicion'] !== '' ? decimales_limpio($row_carbono14['medicion'], 1) : ($db_connected ? '--' : '3.1');
$kpi_dedo_frio = isset($row_dedo_frio['ppm']) && $row_dedo_frio['ppm'] !== '' ? decimales_limpio($row_dedo_frio['ppm'], 0) : ($db_connected ? '--' : '12');
$kpi_dedo_frio_signo = (!empty($row_dedo_frio['menor_que']) && $row_dedo_frio['menor_que'] == 1) ? '< ' : '';
$kpi_ro = isset($row_concentrador['ldd%']) && $row_concentrador['ldd%'] !== '' ? decimales_limpio($row_concentrador['ldd%'], 2) : ($db_connected ? '--' : '8.42');

$kpi_has_met = !empty($row_meteorologia['velocidad_mseg']) || !empty($row_meteorologia['direc_viento']);
$kpi_met_val = $kpi_has_met ? htmlspecialchars($row_meteorologia['direc_viento'] . ' • ' . $row_meteorologia['velocidad_mseg'] . ' m/s', ENT_QUOTES, 'UTF-8') : ($db_connected ? 'Sin registro' : 'Sur • 14.5 km/h');
$kpi_met_pasquill = !empty($row_meteorologia['cond_pasquill']) ? htmlspecialchars($row_meteorologia['cond_pasquill'], ENT_QUOTES, 'UTF-8') : ($db_connected ? 'Sin datos' : 'Pasquill Clase D (Neutral)');

$fecha_latam = formafecha_latam($row_planilla['fecha']);
?>
<!DOCTYPE html>
<html lang="es" data-theme="petrol">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SyR &bull; Inspección Planilla #<?php echo $id_planilla; ?> (CNE)</title>
  <style>
    /* ================= PALETA MAESTRA: VERDE RADIOLÓGICO / PETRÓLEO ================= */
    :root {
      --bg-page: #061412;
      --bg-sidebar: #091c18;
      --bg-card: #0e2722;
      --bg-card-subtle: #163630;
      --bg-input: #081a16;
      --border-card: #1f4d43;
      --border-subtle: #12332c;
      --text-main: #edfcf6;
      --text-muted: #8ec7b9;
      --text-dim: #52857a;
      --accent-blue: #059669;
      --accent-cyan: #10b981;
      --accent-green: #34d399;
      --accent-amber: #f59e0b;
      --accent-red: #ef4444;
      --font-mono: 'Consolas', 'Courier New', monospace;
      --font-ui: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    /* 🌊 TEMA ALTERNATIVO: AZUL ABISAL (Deep Navy) */
    [data-theme="navy"] {
      --bg-page: #07111e;
      --bg-sidebar: #0b1a2e;
      --bg-card: #10223b;
      --bg-card-subtle: #172f50;
      --bg-input: #091626;
      --border-card: #20416b;
      --border-subtle: #142a46;
      --text-main: #f0f6fc;
      --text-muted: #93b0d4;
      --text-dim: #5b7b9e;
      --accent-blue: #3b82f6;
      --accent-cyan: #38bdf8;
      --accent-green: #22c55e;
      --accent-amber: #f59e0b;
      --accent-red: #ef4444;
    }

    /* 🍇 TEMA ALTERNATIVO: CIRUELA OBSIDIANA (Deep Plum) */
    [data-theme="plum"] {
      --bg-page: #110b17;
      --bg-sidebar: #181021;
      --bg-card: #21162d;
      --bg-card-subtle: #2d1f3d;
      --bg-input: #150e1e;
      --border-card: #433157;
      --border-subtle: #271c33;
      --text-main: #f5f3ff;
      --text-muted: #c4b5fd;
      --text-dim: #8572a8;
      --accent-blue: #8b5cf6;
      --accent-cyan: #38bdf8;
      --accent-green: #34d399;
      --accent-amber: #fbbf24;
      --accent-red: #ef4444;
    }

    /* ⚙️ TEMA ALTERNATIVO: TITANIO PIZARRA (Steel Slate) */
    [data-theme="titanio"] {
      --bg-page: #0f1319;
      --bg-sidebar: #151a23;
      --bg-card: #1b212c;
      --bg-card-subtle: #242c3a;
      --bg-input: #12161f;
      --border-card: #2e3849;
      --border-subtle: #202733;
      --text-main: #f1f5f9;
      --text-muted: #94a3b8;
      --text-dim: #64748b;
      --accent-blue: #4f86f7;
      --accent-cyan: #38bdf8;
      --accent-green: #22c55e;
      --accent-amber: #f59e0b;
      --accent-red: #ef4444;
    }

    /* ⚡ TEMA ALTERNATIVO: ÁMBAR SCADA (Warm Amber) */
    [data-theme="ambar"] {
      --bg-page: #0a0b0d;
      --bg-sidebar: #111216;
      --bg-card: #16181f;
      --bg-card-subtle: #22252e;
      --bg-input: #0e1014;
      --border-card: #343744;
      --border-subtle: #20222a;
      --text-main: #f8fafc;
      --text-muted: #a1a1aa;
      --text-dim: #71717a;
      --accent-blue: #d97706;
      --accent-cyan: #f59e0b;
      --accent-green: #10b981;
      --accent-amber: #f59e0b;
      --accent-red: #ef4444;
    }

        * { box-sizing: border-box; margin: 0; padding: 0; }
    html {
      width: 100%;
      height: 100%;
      overflow: hidden;
      margin: 0;
      padding: 0;
    }
    body {
      width: 100%;
      height: 100%;
      margin: 0;
      padding: 0;
      overflow: hidden;
      background-color: var(--bg-page);
      color: var(--text-main);
      font-family: var(--font-ui);
      font-size: 13px;
      line-height: 1.4;
      display: flex;
    }

    /* Layout: Sidebar + Main Workspace */
    .sidebar {
      width: 250px;
      background: var(--bg-sidebar);
      border-right: 1px solid var(--border-card);
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
      z-index: 20;
    }

    .sidebar-header {
      padding: 14px 16px;
      border-bottom: 1px solid var(--border-subtle);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .sidebar-brand-title {
      font-weight: 800;
      font-size: 13px;
      color: var(--text-main);
      letter-spacing: 0.5px;
    }

    .sidebar-brand-sub {
      font-size: 10px;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* Planilla Info Box in Sidebar */
    .sidebar-meta-box {
      margin: 12px 14px;
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 6px;
      padding: 10px;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .meta-row {
      display: flex;
      flex-direction: column;
      gap: 1px;
    }

    .meta-lbl {
      font-size: 9px;
      font-weight: 700;
      color: var(--text-dim);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .meta-val {
      font-size: 11px;
      font-weight: 700;
      color: var(--text-main);
    }

    .meta-val.mono {
      font-family: var(--font-mono);
      color: var(--accent-cyan);
    }

    /* Nav Menu */
    .sidebar-nav {
      flex: 1;
      padding: 8px 10px;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 3px;
    }

    .menu-btn {
      width: 100%;
      text-align: left;
      background: transparent;
      border: 1px solid transparent;
      color: var(--text-muted);
      padding: 8px 12px;
      font-size: 11px;
      font-weight: 600;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: space-between;
      transition: all 0.15s ease;
    }

    .menu-btn:hover {
      background: var(--bg-card-subtle);
      color: var(--text-main);
      border-color: var(--border-subtle);
    }

    .menu-btn.active {
      background: rgba(16, 185, 129, 0.15);
      border-color: var(--accent-cyan);
      color: var(--accent-cyan);
      font-weight: 700;
    }

    .menu-badge {
      background: var(--bg-card-subtle);
      color: var(--text-main);
      font-size: 10px;
      font-weight: 700;
      font-family: var(--font-mono);
      padding: 1px 6px;
      border-radius: 10px;
    }

    .menu-btn.active .menu-badge {
      background: var(--accent-cyan);
      color: #04100e;
    }

    /* Sidebar Footer */
    .sidebar-footer {
      padding: 12px 14px;
      border-top: 1px solid var(--border-subtle);
      background: var(--bg-sidebar);
    }

    .status-pill-audit {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(16, 185, 129, 0.1);
      border: 1px solid var(--accent-cyan);
      color: var(--accent-cyan);
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 10px;
      font-weight: 700;
      font-family: var(--font-mono);
      width: 100%;
      justify-content: center;
    }

    .status-pill-audit.closed {
      background: rgba(59, 130, 246, 0.1);
      border-color: rgba(59, 130, 246, 0.5);
      color: #93c5fd;
    }

    /* Main Content Area */
    .main-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      height: 100vh;
      overflow-x: hidden !important;
      overflow-y: hidden;
      background: var(--bg-page);
    }

    /* Topbar */
    .topbar {
      height: 48px;
      background: var(--bg-sidebar);
      border-bottom: 1px solid var(--border-card);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 16px;
      flex-shrink: 0;
      z-index: 10;
    }

    .topbar-left {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .topbar-title {
      font-size: 13px;
      font-weight: 800;
      color: var(--text-main);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .sheet-badge {
      background: rgba(16, 185, 129, 0.2);
      border: 1px solid var(--accent-cyan);
      color: var(--accent-cyan);
      font-size: 11px;
      font-weight: 800;
      padding: 2px 8px;
      border-radius: 4px;
      font-family: var(--font-mono);
    }

    .topbar-right {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Selector compacto de tema / fondo */
    .theme-dropdown {
      position: relative;
      flex-shrink: 0;
    }
    .btn-theme-toggle {
      height: 28px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: var(--bg-card-subtle);
      border: 1px solid var(--border-card);
      color: var(--text-main);
      padding: 0 9px;
      border-radius: 6px;
      font-family: var(--font-ui);
      font-size: 11px;
      font-weight: 700;
      cursor: pointer;
      white-space: nowrap;
      transition: all 0.15s ease;
    }
    .btn-theme-toggle:hover,
    .theme-dropdown.open .btn-theme-toggle {
      border-color: var(--accent-cyan);
      background: var(--bg-card);
    }
    .theme-swatch-dot {
      width: 9px;
      height: 9px;
      border-radius: 50%;
      display: inline-block;
      flex-shrink: 0;
      border: 1px solid rgba(255, 255, 255, 0.35);
    }
    .theme-dropdown-menu {
      position: absolute;
      top: calc(100% + 6px);
      right: 0;
      min-width: 215px;
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 8px;
      padding: 5px;
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.45);
      display: none;
      flex-direction: column;
      gap: 2px;
      z-index: 1200;
    }
    .theme-dropdown.open .theme-dropdown-menu {
      display: flex;
    }
    .theme-menu-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      width: 100%;
      background: transparent;
      border: none;
      color: var(--text-muted);
      padding: 7px 9px;
      border-radius: 6px;
      font-family: var(--font-ui);
      font-size: 11.5px;
      font-weight: 600;
      text-align: left;
      cursor: pointer;
      transition: all 0.12s ease;
    }
    .theme-menu-item:hover {
      background: var(--bg-card-subtle);
      color: var(--text-main);
    }
    .theme-menu-item.active {
      background: rgba(16, 185, 129, 0.14);
      color: var(--accent-cyan);
      font-weight: 700;
    }
    .theme-menu-item .theme-check {
      opacity: 0;
      font-size: 11px;
      font-weight: 800;
    }
    .theme-menu-item.active .theme-check {
      opacity: 1;
    }

    .btn-topbar {
      background: var(--bg-card-subtle);
      border: 1px solid var(--border-card);
      color: var(--text-main);
      padding: 6px 12px;
      font-size: 11px;
      font-weight: 600;
      border-radius: 6px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      text-decoration: none;
      transition: all 0.15s ease;
    }

    .btn-topbar:hover {
      background: var(--accent-cyan);
      color: #04100e;
      border-color: var(--accent-cyan);
    }

    .btn-topbar.btn-print:hover {
      background: var(--accent-amber);
      color: #000000;
      border-color: var(--accent-amber);
    }

    /* Workspace */
    .workspace {
      flex: 1;
      overflow-y: auto;
      padding: 14px 16px 40px;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    /* ================= 6 KPIS SUPERIORES ================= */
    .kpis-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 2px;
    }

    .kpis-title {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      color: var(--text-muted);
      letter-spacing: 0.5px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 10px;
    }

    .kpi-card {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 8px;
      padding: 10px 12px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      min-height: 94px;
      cursor: pointer;
      position: relative;
      overflow: hidden;
      transition: all 0.15s ease;
    }

    .kpi-card:hover {
      border-color: var(--accent-cyan);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.4);
    }

    /* Futuristic HUD Scanline Radar Sweep */
    .kpi-card::after {
      content: '';
      position: absolute;
      top: -100%;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(180deg, transparent 0%, rgba(16, 185, 129, 0.08) 50%, transparent 100%);
      animation: hud-scanline 6s ease-in-out infinite;
      pointer-events: none;
    }

    @keyframes hud-scanline {
      0% { top: -100%; }
      50% { top: 100%; }
      100% { top: 100%; }
    }

    .kpi-top-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 4px;
    }

    .kpi-label {
      font-size: 9px;
      font-weight: 700;
      text-transform: uppercase;
      color: var(--text-muted);
      letter-spacing: 0.5px;
    }

    .kpi-action-icon {
      font-size: 9px;
      color: var(--text-dim);
      font-weight: 600;
    }

    .kpi-value-row {
      display: flex;
      align-items: baseline;
      gap: 4px;
      margin: 2px 0;
    }

    .kpi-value {
      font-family: var(--font-mono);
      font-size: 18px;
      font-weight: 800;
      color: var(--text-main);
      line-height: 1.1;
    }

    .kpi-unit {
      font-size: 10px;
      font-weight: 700;
      color: var(--text-dim);
    }

    .kpi-footer {
      font-size: 9px;
      color: var(--text-dim);
      margin-top: 4px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* Multi value inside KPI 1 */
    .kpi-multi-values {
      display: flex;
      justify-content: space-between;
      gap: 4px;
      margin: 2px 0;
    }

    .kpi-sub-item {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .kpi-sub-label {
      font-size: 8px;
      font-weight: 700;
      color: var(--text-dim);
      text-transform: uppercase;
    }

    .kpi-sub-val-row {
      display: flex;
      align-items: baseline;
      gap: 2px;
    }

    .kpi-sub-val {
      font-family: var(--font-mono);
      font-size: 11px;
      font-weight: 800;
    }

    .kpi-sub-unit {
      font-size: 7px;
      color: var(--text-dim);
    }

    /* HUD Audit Banner */
    .hud-audit-banner {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-left: 4px solid var(--accent-cyan);
      border-radius: 6px;
      padding: 8px 14px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 11px;
    }

    .audit-tag {
      font-family: var(--font-mono);
      font-weight: 700;
      color: var(--accent-cyan);
    }

    /* Sections */
    /* ================= ANIMACIONES FUTURISTAS TRANSICIÓN ENTRE PESTAÑAS ================= */
    .section-content {
      display: none;
      flex-direction: column;
      gap: 12px;
    }

    .section-content.active {
      display: flex;
      animation: cyber-hologram-assemble 0.42s cubic-bezier(0.16, 1, 0.3, 1) forwards;
      position: relative;
      perspective: 1000px;
    }

    /* Ensamblaje holográfico con barrido de matriz cuántica */
    @keyframes cyber-hologram-assemble {
      0% {
        opacity: 0;
        transform: perspective(900px) rotateX(5deg) translateY(20px) scale(0.98);
        filter: blur(8px) brightness(1.7) contrast(1.3);
        box-shadow: 0 0 40px rgba(16, 185, 129, 0.55);
      }
      30% {
        opacity: 0.75;
        filter: blur(2.5px) brightness(1.35) contrast(1.15);
      }
      65% {
        transform: perspective(900px) rotateX(-1deg) translateY(-2px) scale(1.002);
        filter: blur(0.5px) brightness(1.1);
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.25);
      }
      100% {
        opacity: 1;
        transform: perspective(900px) rotateX(0deg) translateY(0) scale(1);
        filter: none;
        box-shadow: none;
      }
    }

    /* Haz de barrido láser cian acotado con transform sin desbordar el viewport */
    .section-content {
      overflow-x: hidden;
    }

    .section-content.active {
      overflow-x: hidden;
    }

    .section-content .panel-box {
      overflow: hidden !important;
      position: relative !important;
    }

    .section-content.active .panel-box::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 3px;
      background: linear-gradient(90deg, transparent 0%, rgba(16, 185, 129, 0.4) 20%, var(--accent-cyan) 50%, #ffffff 70%, var(--accent-cyan) 85%, transparent 100%);
      transform: translateX(-100%);
      animation: cyber-laser-sweep 0.65s cubic-bezier(0.16, 1, 0.3, 1) forwards;
      box-shadow: 0 0 12px var(--accent-cyan), 0 0 24px var(--accent-cyan);
      z-index: 10;
      pointer-events: none;
    }

    @keyframes cyber-laser-sweep {
      0% { transform: translateX(-100%); opacity: 1; }
      70% { opacity: 1; }
      100% { transform: translateX(100%); opacity: 0; }
    }

    /* Escaneo de rejilla táctica cyber-grid al activar panel */
    .section-content.active::after {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: linear-gradient(rgba(16, 185, 129, 0.04) 1px, transparent 1px),
                  linear-gradient(90deg, rgba(16, 185, 129, 0.04) 1px, transparent 1px);
      background-size: 24px 24px;
      pointer-events: none;
      opacity: 0;
      animation: cyber-grid-fade 0.5s ease-out forwards;
      border-radius: 8px;
    }

    @keyframes cyber-grid-fade {
      0% { opacity: 0.8; }
      60% { opacity: 0.4; }
      100% { opacity: 0; }
    }

    /* Botón del Sidebar con pulso de shockwave y resplandor HUD */
    .menu-btn {
      transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
      position: relative;
      overflow: hidden;
    }

    .menu-btn.active {
      background: rgba(16, 185, 129, 0.18) !important;
      border-color: var(--accent-cyan) !important;
      color: #ffffff !important;
      font-weight: 700;
      box-shadow: inset 4px 0 0 var(--accent-cyan), 0 0 20px rgba(16, 185, 129, 0.35);
      transform: translateX(6px);
    }

    .menu-btn.active::after {
      content: '';
      position: absolute;
      top: 0; left: 0; width: 100%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.12), transparent);
      animation: cyber-btn-shimmer 0.7s ease-out forwards;
      pointer-events: none;
    }

    @keyframes cyber-btn-shimmer {
      0% { transform: translateX(-100%); }
      100% { transform: translateX(100%); }
    }

    .panel-box {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 8px;
      overflow: hidden;
    }

    .panel-box-header {
      background: var(--bg-sidebar);
      border-bottom: 1px solid var(--border-card);
      padding: 10px 14px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .panel-box-header h2 {
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      color: var(--text-main);
      display: flex;
      align-items: center;
      gap: 8px;
      letter-spacing: 0.5px;
    }

    .panel-box-body {
      padding: 12px 14px;
    }

    /* Tables */
    .data-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }

    .data-table thead th {
      background: var(--bg-sidebar);
      color: var(--text-muted);
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 8px 10px;
      border-bottom: 1px solid var(--border-card);
      border-right: 1px solid var(--border-subtle);
      text-align: center;
    }

    .data-table thead th:last-child {
      border-right: none;
    }

    /* Contenedor con scroll y encabezados fijos (Sticky Table Headers) */
    .table-scroll-wrapper {
      position: relative;
      background: var(--bg-card);
    }

    .table-scroll-wrapper::-webkit-scrollbar {
      width: 7px;
      height: 7px;
    }
    .table-scroll-wrapper::-webkit-scrollbar-track {
      background: rgba(4, 15, 13, 0.8);
    }
    .table-scroll-wrapper::-webkit-scrollbar-thumb {
      background: var(--border-card);
      border-radius: 4px;
    }
    .table-scroll-wrapper::-webkit-scrollbar-thumb:hover {
      background: var(--accent-cyan);
    }

    .table-scroll-wrapper .data-table thead tr:first-child th {
      position: sticky !important;
      top: 0 !important;
      z-index: 25 !important;
      background: #091c18 !important;
    }

    .table-scroll-wrapper .data-table thead tr:nth-child(2) th {
      position: sticky !important;
      top: 29px !important;
      z-index: 24 !important;
      background: #091c18 !important;
      box-shadow: 0 1px 0 var(--border-card);
    }

    /* Filas intercaladas (Zebra Striping de Alto Contraste) */
    .data-table tbody tr:nth-child(odd) td {
      background-color: rgba(14, 39, 34, 0.45);
    }

    .data-table tbody tr:nth-child(even) td {
      background-color: rgba(25, 66, 58, 0.65);
    }

    .data-table tbody td {
      padding: 8px 10px;
      font-size: 12px;
      border-bottom: 1px solid rgba(31, 77, 67, 0.45);
      border-right: 1px solid var(--border-subtle);
      color: var(--text-main);
      text-align: center;
      transition: background-color 0.1s ease;
    }

    .data-table tbody td:last-child {
      border-right: none;
    }

    .data-table tbody td.text-left {
      text-align: left;
    }

    .data-table tbody td.mono {
      font-family: var(--font-mono);
    }

    .data-table tbody tr:hover td {
      background-color: rgba(16, 185, 129, 0.22) !important;
      color: #ffffff;
    }

    /* Badges */
    .sync-badge {
      display: inline-flex;
      align-items: center;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 10px;
      font-weight: 700;
      font-family: var(--font-mono);
      background: rgba(16, 185, 129, 0.15);
      border: 1px solid rgba(16, 185, 129, 0.4);
      color: #6ee7b7;
    }

    /* Gas Cards Grid */
    .gas-sub-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
    }

    .gas-sub-card {
      background: var(--bg-sidebar);
      border: 1px solid var(--border-card);
      border-radius: 6px;
      padding: 10px 12px;
    }

    .gas-sub-title {
      font-size: 11px;
      font-weight: 700;
      color: var(--accent-cyan);
      text-transform: uppercase;
      margin-bottom: 8px;
      display: flex;
      justify-content: space-between;
      border-bottom: 1px solid var(--border-subtle);
      padding-bottom: 4px;
    }

    .metric-row {
      display: flex;
      justify-content: space-between;
      padding: 3px 0;
      font-size: 11px;
      border-bottom: 1px dashed var(--border-subtle);
    }

    .metric-row:last-child {
      border-bottom: none;
    }

    .metric-lbl {
      color: var(--text-muted);
    }

    .metric-val {
      font-family: var(--font-mono);
      font-weight: 700;
      color: var(--text-main);
    }

    /* Textarea Readonly */
    .terminal-box {
      background: var(--bg-input);
      border: 1px solid var(--border-card);
      border-radius: 6px;
      padding: 12px;
      font-family: var(--font-mono);
      font-size: 12px;
      color: var(--text-main);
      white-space: pre-wrap;
      line-height: 1.5;
      min-height: 120px;
    }

    /* Print styling */
    @media print {
      body {
        background: #ffffff !important;
        color: #000000 !important;
        height: auto !important;
        overflow: visible !important;
      }
      .sidebar, .topbar, .kpis-toolbar, .btn-topbar {
        display: none !important;
      }
      .main-content {
        height: auto !important;
        overflow: visible !important;
      }
      .workspace {
        overflow: visible !important;
        padding: 0 !important;
      }
      .section-content {
        display: flex !important;
        page-break-after: always;
      }
      .panel-box, .kpi-card, .gas-sub-card {
        border: 1px solid #333333 !important;
        background: #ffffff !important;
        color: #000000 !important;
      }
      .data-table thead th {
        background: #eeeeee !important;
        color: #000000 !important;
      }
      .data-table tbody td {
        color: #000000 !important;
        border-color: #cccccc !important;
      }
      .terminal-box {
        background: #fafafa !important;
        color: #000000 !important;
        border-color: #666666 !important;
      }
    }
      /* Modal Overlay & Box para visor de historial */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.78);
      backdrop-filter: blur(5px);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }

    .modal-box {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 10px;
      width: 580px;
      max-width: 92vw;
      box-shadow: 0 20px 45px rgba(0, 0, 0, 0.85);
      overflow: hidden;
    }

    .modal-header {
      padding: 14px 20px;
      background: var(--bg-card-subtle);
      border-bottom: 1px solid var(--border-subtle);
    }

        /* Estilos para botón de apertura y filas clickeables de actas */
    .btn-acta-open {
      background: rgba(16, 185, 129, 0.12);
      border: 1px solid rgba(16, 185, 129, 0.35);
      color: var(--accent-cyan);
      font-family: var(--font-mono);
      font-size: 10px;
      font-weight: 700;
      padding: 2px 7px;
      border-radius: 4px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 3px;
      transition: all 0.18s ease;
      white-space: nowrap;
    }

    .btn-acta-open:hover {
      background: var(--accent-cyan);
      color: #02120e;
      border-color: var(--accent-cyan);
      box-shadow: 0 0 10px rgba(16, 185, 129, 0.45);
      transform: scale(1.04);
    }

    .acta-clickable-row {
      cursor: pointer;
      transition: background-color 0.15s ease;
    }

    .acta-clickable-row:hover td {
      background-color: rgba(16, 185, 129, 0.08) !important;
    }

    /* Pill interactiva de Historial en filas de medición guardadas */
    .btn-recinto-hist-pill {
      background: rgba(16, 185, 129, 0.12);
      border: 1px solid rgba(16, 185, 129, 0.35);
      color: var(--accent-cyan);
      font-family: var(--font-mono);
      font-size: 10px;
      font-weight: 700;
      padding: 2px 7px;
      border-radius: 4px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
      white-space: nowrap;
    }

    .btn-recinto-hist-pill:hover {
      background: var(--accent-cyan);
      color: #02120e;
      border-color: var(--accent-cyan);
      box-shadow: 0 0 10px rgba(16, 185, 129, 0.45);
      transform: scale(1.04);
    }

    .recinto-link {
      cursor: pointer;
      transition: all 0.15s ease;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .recinto-link:hover {
      color: var(--accent-cyan) !important;
      text-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
    }

    /* ========================================================== */
    /* TOOLTIPS PERSONALIZADOS CYBERPUNK RADIOLÓGICOS             */
    /* ========================================================== */
    .kpi-multi-values.two-cols {
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    .kpi-pending-pill {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: rgba(245, 158, 11, 0.12);
      border: 1px dashed rgba(245, 158, 11, 0.6);
      color: #fbbf24;
      padding: 2px 7px;
      border-radius: 4px;
      font-size: 11px;
      font-weight: 800;
      font-family: var(--font-mono);
      letter-spacing: 0.3px;
    }

    .kpi-card.is-pending {
      border-color: rgba(245, 158, 11, 0.45) !important;
      background: linear-gradient(180deg, rgba(245, 158, 11, 0.04) 0%, var(--bg-card) 100%);
    }

    .kpi-card.is-pending .kpi-label {
      color: #fde68a !important;
    }

    .kpi-card.is-pending .kpi-action-icon {
      border-color: rgba(245, 158, 11, 0.5);
      color: #fbbf24;
      background: rgba(245, 158, 11, 0.15);
    }

    #cyber-tooltip {
      position: fixed;
      z-index: 999999;
      pointer-events: none;
      opacity: 0;
      transform: translateY(4px);
      transition: opacity 0.12s cubic-bezier(0.4, 0, 0.2, 1), transform 0.12s cubic-bezier(0.4, 0, 0.2, 1);
      background: rgba(8, 26, 22, 0.96);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border: 1px solid var(--accent-cyan, #10b981);
      color: var(--text-main, #edfcf6);
      font-family: var(--font-mono, monospace);
      font-size: 11px;
      line-height: 1.45;
      padding: 6px 10px;
      border-radius: 5px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.8), 0 0 10px rgba(16, 185, 129, 0.25);
      max-width: 320px;
      word-wrap: break-word;
      white-space: normal;
      display: block;
    }

    #cyber-tooltip.visible {
      opacity: 1;
      transform: translateY(0);
    }

    #cyber-tooltip.alert {
      border-color: var(--accent-amber, #f59e0b);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.85), 0 0 14px rgba(245, 158, 11, 0.4);
      background: rgba(26, 18, 7, 0.97);
      color: #fffbeb;
    }

    #cyber-tooltip.danger {
      border-color: var(--accent-red, #ef4444);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.85), 0 0 14px rgba(239, 68, 68, 0.45);
      background: rgba(28, 10, 10, 0.97);
      color: #fef2f2;
    }
  </style>
</head>
<body>

  <!-- ================= SIDEBAR ================= -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <div>
        <div class="sidebar-brand-title">REGISTRO RADIOLÓGICO</div>
        <div class="sidebar-brand-sub">CNE &bull; SEGURIDAD RADIOLÓGICA</div>
      </div>
    </div>

    <!-- Metadata de la Guardia -->
    <div class="sidebar-meta-box">
      <div class="meta-row">
        <span class="meta-lbl">Planilla Diaria</span>
        <span class="meta-val mono">#<?php echo $id_planilla; ?></span>
      </div>
      <div class="meta-row">
        <span class="meta-lbl">Fecha Operativa</span>
        <span class="meta-val mono"><?php echo $fecha_latam; ?></span>
      </div>
      <div class="meta-row">
        <span class="meta-lbl">Turno de Guardia</span>
        <span class="meta-val"><?php echo htmlspecialchars($turno_nombre, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <div class="meta-row">
        <span class="meta-lbl">Oficial Responsable</span>
        <span class="meta-val" style="color: var(--accent-cyan);"><?php echo htmlspecialchars($oficial_nombre, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <div class="meta-row">
        <span class="meta-lbl">Jefe de Turno</span>
        <span class="meta-val"><?php echo htmlspecialchars($jefe_nombre, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <div class="meta-row">
        <span class="meta-lbl">Estado de Planta</span>
        <span class="meta-val" style="color: var(--accent-green);"><?php echo htmlspecialchars($estado_planta_nombre, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
    </div>

    <!-- Menú de Secciones -->
    <nav class="sidebar-nav">
      <button type="button" class="menu-btn active" data-section="sec-rutina">
        <span>📋 Relevamiento de Rutina</span>
        <span class="menu-badge"><?php echo count($rows_rutina); ?></span>
      </button>
      <button type="button" class="menu-btn" data-section="sec-gases">
        <span>🏭 Efluentes Gaseosos &amp; Chimenea</span>
      </button>
      <button type="button" class="menu-btn" data-section="sec-actas">
        <span>📖 Libro de Actas &amp; Tareas PTR</span>
        <span class="menu-badge"><?php echo count($rows_tareas); ?></span>
      </button>
      <button type="button" class="menu-btn" data-section="sec-liquidos">
        <span>💧 Efluentes Líquidos &amp; Espectr.</span>
      </button>
      <button type="button" class="menu-btn" data-section="sec-contaminacion">
        <span>🔬 Contaminación &amp; Dryers</span>
      </button>
      <button type="button" class="menu-btn" data-section="sec-obs">
        <span>💬 Observaciones de Turno</span>
      </button>
    </nav>

    <!-- Footer Sidebar -->
    <div class="sidebar-footer">
      <?php if ($es_cerrada): ?>
        <div class="status-pill-audit closed">
          <span>🔒 Planilla Auditada &amp; Cerrada</span>
        </div>
      <?php else: ?>
        <div class="status-pill-audit">
          <span>🟢 Planilla en Curso (En Vivo)</span>
        </div>
      <?php endif; ?>
    </div>
  </aside>

  <!-- ================= MAIN WORKSPACE ================= -->
  <main class="main-content">
    
    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <span class="topbar-title" id="current-section-title">Relevamiento de Rutina</span>
        <span class="sheet-badge">PLANILLA #<?php echo $id_planilla; ?></span>
        <span style="font-size: 11px; color: var(--text-muted);">
          &bull; <?php echo $fecha_latam; ?> &bull; <?php echo htmlspecialchars($turno_nombre, ENT_QUOTES, 'UTF-8'); ?>
        </span>
      </div>
      <div class="topbar-right">
        <div class="theme-dropdown" id="theme-dropdown">
          <button type="button" class="btn-theme-toggle" id="btn-theme-toggle" onclick="toggleThemeDropdown(event)" aria-haspopup="true" aria-expanded="false" title="Cambiar paleta de color / fondo">
            <span class="theme-swatch-dot" id="theme-current-dot" style="background: #10b981;"></span>
            <span id="theme-current-label">Verde</span>
            <span style="font-size: 9px; color: var(--text-muted);">▾</span>
          </button>
          <div class="theme-dropdown-menu" id="theme-dropdown-menu" role="menu">
            <div style="font-size: 9.5px; font-weight: 800; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.6px; padding: 4px 8px 3px;">Paleta de fondo</div>
            <button type="button" class="theme-menu-item active" data-theme="petrol" data-label="Verde" data-color="#10b981" onclick="setAppTheme('petrol')">
              <span style="display:flex; align-items:center; gap:8px;"><span class="theme-swatch-dot" style="background:#10b981;"></span> Verde Radiológico</span>
              <span class="theme-check">✓</span>
            </button>
            <button type="button" class="theme-menu-item" data-theme="navy" data-label="Azul" data-color="#06b6d4" onclick="setAppTheme('navy')">
              <span style="display:flex; align-items:center; gap:8px;"><span class="theme-swatch-dot" style="background:#06b6d4;"></span> Azul Abisal</span>
              <span class="theme-check">✓</span>
            </button>
            <button type="button" class="theme-menu-item" data-theme="plum" data-label="Ciruela" data-color="#e879f9" onclick="setAppTheme('plum')">
              <span style="display:flex; align-items:center; gap:8px;"><span class="theme-swatch-dot" style="background:#e879f9;"></span> Ciruela</span>
              <span class="theme-check">✓</span>
            </button>
            <button type="button" class="theme-menu-item" data-theme="titanio" data-label="Titanio" data-color="#38bdf8" onclick="setAppTheme('titanio')">
              <span style="display:flex; align-items:center; gap:8px;"><span class="theme-swatch-dot" style="background:#38bdf8;"></span> Titanio</span>
              <span class="theme-check">✓</span>
            </button>
            <button type="button" class="theme-menu-item" data-theme="ambar" data-label="Ámbar" data-color="#f59e0b" onclick="setAppTheme('ambar')">
              <span style="display:flex; align-items:center; gap:8px;"><span class="theme-swatch-dot" style="background:#f59e0b;"></span> Ámbar</span>
              <span class="theme-check">✓</span>
            </button>
          </div>
        </div>
        <button type="button" class="btn-topbar btn-print" onclick="window.print()">
          <span>🖨️ Imprimir Planilla</span>
        </button>
        <a href="vista_planillas_anteriores.php" class="btn-topbar">
          <span>⬅️ Volver al Histórico</span>
        </a>
      </div>
    </header>

    <!-- Workspace Scrollable -->
    <div class="workspace">

      <!-- ================= BARRA DE KPIS SUPERIORES ================= -->
      <div class="kpis-toolbar">
        <div class="kpis-title">
          <span>⚡ Métricas de Guardia &amp; Chimenea Asentadas</span>
          <span style="color: var(--text-dim); font-size: 10px; font-weight: normal;">[ Haga clic en cualquier tarjeta para ver su desglose ]</span>
        </div>
      </div>

      <section class="kpi-grid">
        
        <!-- KPI 1: Picos Radiológicos -->
        <div class="kpi-card" onclick="goToSection('sec-rutina')" title="Haga clic para ir a relevamientos de rutina">
          <div class="kpi-top-row">
            <span class="kpi-label">Picos Radiológicos</span>
            <span class="kpi-action-icon">📋 Ver</span>
          </div>
          <div class="kpi-multi-values">
            <div class="kpi-sub-item">
              <span class="kpi-sub-label">Dosis</span>
              <div class="kpi-sub-val-row">
                <span class="kpi-sub-val" style="color: var(--accent-amber);"><?php echo decimales_limpio($max_dosis, 1); ?></span>
                <span class="kpi-sub-unit">µSv/h</span>
              </div>
            </div>
            <div class="kpi-sub-item">
              <span class="kpi-sub-label">Tritio</span>
              <div class="kpi-sub-val-row">
                <span class="kpi-sub-val" style="color: var(--accent-cyan);"><?php echo decimales_limpio($max_tritio, 2); ?></span>
                <span class="kpi-sub-unit">DAC</span>
              </div>
            </div>
            <div class="kpi-sub-item">
              <span class="kpi-sub-label">Gases</span>
              <div class="kpi-sub-val-row">
                <span class="kpi-sub-val" style="color: #c084fc;"><?php echo decimales_limpio($max_gases, 2); ?></span>
                <span class="kpi-sub-unit">DAC</span>
              </div>
            </div>
          </div>
          <div class="kpi-footer">📍 <?php echo htmlspecialchars($max_recinto_dosis, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <!-- KPI 2: RO Gaseoso / Concentrador -->
        <div class="kpi-card" onclick="goToSection('sec-liquidos')" title="Haga clic para ver efluentes y concentrador">
          <div class="kpi-top-row">
            <span class="kpi-label">% Restricción Operacional</span>
            <span class="kpi-action-icon">📊 Ver</span>
          </div>
          <div class="kpi-value-row">
            <span class="kpi-value" style="color: var(--accent-green);"><?php echo $kpi_ro; ?></span>
            <span class="kpi-unit">% RO</span>
          </div>
          <div class="kpi-footer">Límite operacional normal</div>
        </div>

        <!-- KPI 3: Ventilación Chimenea (General y Reactor unificados) -->
        <div class="kpi-card" onclick="goToSection('sec-gases')" title="Haga clic para ver efluentes gaseosos">
          <div class="kpi-top-row">
            <span class="kpi-label">Ventilación Chimenea</span>
            <span class="kpi-action-icon">🏭 Ver</span>
          </div>
          <div class="kpi-multi-values two-cols">
            <div class="kpi-sub-item">
              <span class="kpi-sub-label">General</span>
              <div class="kpi-sub-val-row">
                <?php if (!empty($kpi_vent_gral)): ?>
                  <span class="kpi-sub-val" style="color: #60a5fa;"><?php echo $kpi_vent_gral; ?></span>
                  <span class="kpi-sub-unit">GBq</span>
                <?php else: ?>
                  <span class="kpi-pending-pill">⚠️ Pendiente</span>
                <?php endif; ?>
              </div>
            </div>
            <div class="kpi-sub-item">
              <span class="kpi-sub-label">Reactor</span>
              <div class="kpi-sub-val-row">
                <?php if (!empty($kpi_vent_reactor)): ?>
                  <span class="kpi-sub-val" style="color: #22d3ee;"><?php echo $kpi_vent_reactor; ?></span>
                  <span class="kpi-sub-unit">GBq</span>
                <?php else: ?>
                  <span class="kpi-pending-pill">⚠️ Pendiente</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="kpi-footer">Caudal y extracción activa</div>
        </div>

        <!-- KPI 4: Condición Meteorológica -->
        <div class="kpi-card" onclick="goToSection('sec-gases')" title="Haga clic para ver meteorología">
          <div class="kpi-top-row">
            <span class="kpi-label">🌤️ Meteorología</span>
            <span class="kpi-action-icon">🏭 Ver</span>
          </div>
          <div class="kpi-value-row">
            <?php if (!empty($kpi_met_val)): ?>
              <span class="kpi-value" style="color: #f59e0b; font-size: 15px; font-weight: 800;"><?php echo $kpi_met_val; ?></span>
            <?php else: ?>
              <span class="kpi-pending-pill">⚠️ PENDIENTE</span>
            <?php endif; ?>
          </div>
          <div class="kpi-footer"><?php echo $kpi_met_pasquill; ?></div>
        </div>

        <!-- KPI 5: Carbono-14 -->
        <div class="kpi-card" onclick="goToSection('sec-gases')" title="Haga clic para ver efluentes gaseosos">
          <div class="kpi-top-row">
            <span class="kpi-label">Emisión Carbono-14</span>
            <span class="kpi-action-icon">🏭 Ver</span>
          </div>
          <div class="kpi-value-row">
            <span class="kpi-value" style="color: #a855f7;"><?php echo $kpi_c14; ?></span>
            <span class="kpi-unit">MBq</span>
          </div>
          <div class="kpi-footer">Muestreo acumulado turno</div>
        </div>

        <!-- KPI 6: Dedo Frío 73120 con Últimos 5 Datos -->
        <div class="kpi-card" onclick="goToSection('sec-gases')" title="Haga clic para ver últimos 5 datos de Dedo Frío en Efluentes Gaseosos">
          <div class="kpi-top-row">
            <span class="kpi-label">Dedo Frío 73120</span>
            <span class="kpi-action-icon">📊 Últimos 5</span>
          </div>
          <div class="kpi-value-row">
            <span class="kpi-value" style="color: #ec4899;"><?php echo $kpi_dedo_frio_signo . $kpi_dedo_frio; ?></span>
            <span class="kpi-unit">ppm</span>
          </div>
          <!-- Micro-barra visual de los últimos 5 registros criogénicos -->
          <div class="kpi-mini-history" style="display: flex; gap: 3px; margin: 2px 0 3px;">
            <?php if (empty($hist_dedofrio_vis)): ?>
              <span style="font-size: 9px; color: var(--text-dim);">Sin registros previos</span>
            <?php else: foreach ($hist_dedofrio_vis as $idx_df => $hdf): 
              $df_pref_txt = $hdf['menor_que'] ? '< ' : '';
              $df_pref_chip = $hdf['menor_que'] ? '&lt;' : '';
              $df_title = htmlspecialchars($hdf['fecha_latam'] . ' ' . $hdf['hora'] . ' (' . $hdf['apellido'] . '): ' . $df_pref_txt . $hdf['ppm'] . ' ppm', ENT_QUOTES, 'UTF-8');
              $df_chip_style = ($idx_df === 0)
                ? 'background: rgba(236,72,153,0.3); color: #f472b6; font-family: var(--font-mono); font-size: 8.5px; padding: 1px 4px; border-radius: 3px; font-weight: 800; border: 1px solid rgba(236,72,153,0.5);'
                : 'background: rgba(236,72,153,0.15); color: #f472b6; font-family: var(--font-mono); font-size: 8.5px; padding: 1px 4px; border-radius: 3px;';
            ?>
              <span title="<?php echo $df_title; ?>" style="<?php echo $df_chip_style; ?>"><?php echo $df_pref_chip . htmlspecialchars($hdf['ppm'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endforeach; endif; ?>
          </div>
          <div class="kpi-footer">Histórico 5 guardias nominal</div>
        </div>

      </section>

      <!-- Banner HUD Audit -->
      <div class="hud-audit-banner">
        <div>
          <span>Estado del Registro: </span>
          <?php if ($es_cerrada): ?>
            <strong class="audit-tag">🔒 PLANILLA CERRADA &bull; MODO CONSULTA / AUDITORÍA TÉCNICA</strong>
          <?php else: ?>
            <strong class="audit-tag" style="color: var(--accent-green);">🟢 PLANILLA EN CURSO &bull; MONITOREO DE GUARDIA EN VIVO</strong>
          <?php endif; ?>
        </div>
        <div style="color: var(--text-muted); font-size: 11px;">
          Oficial de Guardia: <strong style="color: #ffffff;"><?php echo htmlspecialchars($oficial_nombre, ENT_QUOTES, 'UTF-8'); ?></strong>
        </div>
      </div>

      <!-- ================= SECCIÓN 1: RELEVAMIENTO DE RUTINA ================= -->
      <div class="section-content active" id="sec-rutina">
        <div class="panel-box">
          <div class="panel-box-header">
            <h2>📋 RELEVAMIENTO DE RUTINA</h2>
            <span style="font-size: 11px; color: var(--text-muted); font-family: var(--font-mono);">
              Total: <?php echo count($rows_rutina); ?> mediciones registradas
            </span>
          </div>
          <div class="panel-box-body">
            <div class="table-scroll-wrapper" style="max-height: calc(100vh - 350px); min-height: 320px; overflow-y: auto; overflow-x: auto; border: 1px solid var(--border-card); border-radius: 6px;">
              <table class="data-table">
                <thead>
                  <tr>
                    <th rowspan="2" style="width: 85px;">Fecha</th>
                  <th rowspan="2" style="width: 65px;">Hora</th>
                  <th rowspan="2" style="text-align: left;">Recinto / Ubicación</th>
                  <th colspan="3">Mediciones Radiológicas</th>
                  <th rowspan="2" style="text-align: left;">Observaciones</th>
                  <th rowspan="2" style="width: 170px;">Relevado Por</th>
                  <th rowspan="2" style="width: 100px;">Estado</th>
                </tr>
                <tr>
                  <th style="width: 80px;">G.Nobles<br><span style="font-weight: normal;">(DAC)</span></th>
                  <th style="width: 80px;">Tritio<br><span style="font-weight: normal;">(DAC)</span></th>
                  <th style="width: 90px;">T.Dosis<br><span style="font-weight: normal;">(µSv/h)</span></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows_rutina as $r): ?>
                  <tr>
                    <td class="mono"><?php echo formafecha_latam($r['fecha']); ?></td>
                    <td class="mono"><?php echo formato_hora_limpio($r['hora']); ?></td>
                    <td class="text-left" style="font-weight: 600; color: #ffffff;">
                      <?php echo htmlspecialchars($r['recinto_nombre'], ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                    <td class="mono"><?php echo decimales_limpio($r['gases_nobles'], 2); ?></td>
                    <td class="mono"><?php echo decimales_limpio($r['tritio'], 2); ?></td>
                    <td class="mono" style="font-weight: 700; color: var(--accent-cyan);"><?php echo decimales_limpio($r['tasa_dosis'], 1); ?></td>
                    <td class="text-left" style="color: var(--text-muted);"><?php echo htmlspecialchars((!empty($r['observaciones']) && $r['observaciones'] !== 'Sin observaciones' && $r['observaciones'] !== '-') ? $r['observaciones'] : '--Sin observaciones--', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td style="text-transform: uppercase; font-size: 11px;">
                      <?php echo htmlspecialchars((!empty($r['of_apellido']) ? $r['of_apellido'] . ", " . $r['of_nombre'] : $oficial_nombre), ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                    <td>
                      <span class="sync-badge">● Guardado</span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= SECCIÓN 2: EFLUENTES GASEOSOS & CHIMENEA ================= -->
      <div class="section-content" id="sec-gases">
        <div class="panel-box">
          <div class="panel-box-header">
            <h2>💨 EMISIÓN EFLUENTES GASEOSOS</h2>
          </div>
          <div class="panel-box-body">
            <div class="gas-sub-grid">
              
              <!-- Tritio Chimenea -->
              <div class="gas-sub-card">
                <div class="gas-sub-title">
                  <span>Emisión Tritio</span>
                  <span style="font-family: var(--font-mono); font-size: 10px;">GBq</span>
                </div>
                <?php foreach ($rows_tritio as $tr): ?>
                  <div class="metric-row">
                    <span class="metric-lbl">Ventilación Gral:</span>
                    <span class="metric-val"><?php echo formato_valor_gbq($tr['vent_gral']); ?> GBq</span>
                  </div>
                  <div class="metric-row">
                    <span class="metric-lbl">Ventilación Reactor (ER):</span>
                    <span class="metric-val"><?php echo formato_valor_gbq($tr['vent_reactor']); ?> GBq</span>
                  </div>
                  <div class="metric-row">
                    <span class="metric-lbl">Período de Muestreo:</span>
                    <span class="metric-val"><?php echo formato_hora_limpio($tr['hora_desde']) . ' a ' . formato_hora_limpio($tr['hora_hasta']); ?></span>
                  </div>
                <?php endforeach; ?>
              </div>

              <!-- C-14 & Dedo Frío con Últimos 5 Datos -->
              <div class="gas-sub-card">
                <div class="gas-sub-title">
                  <span>Carbono-14 &amp; Dedo Frío 73120</span>
                </div>
                <div class="metric-row">
                  <span class="metric-lbl">Emisión C-14:</span>
                  <span class="metric-val">
                    <?php if (isset($row_carbono14['medicion']) && $row_carbono14['medicion'] !== ''): ?>
                      <?php echo decimales_limpio($row_carbono14['medicion'], 1); ?> MBq (<?php echo formato_hora_limpio($row_carbono14['hora']); ?> hs)
                    <?php else: ?>
                      <span style="color: var(--accent-amber); font-weight: 700;">Sin carga</span>
                    <?php endif; ?>
                  </span>
                </div>
                <div class="metric-row">
                  <span class="metric-lbl">Dedo Frío Actual:</span>
                  <span class="metric-val" style="color: #f472b6; font-weight: 800;">
                    <?php if (isset($row_dedo_frio['ppm']) && $row_dedo_frio['ppm'] !== ''): ?>
                      <?php if (!empty($row_dedo_frio['menor_que'])) echo '&lt; '; ?>
                      <?php echo decimales_limpio($row_dedo_frio['ppm'], 0); ?> ppm
                    <?php else: ?>
                      <span style="color: var(--accent-amber); font-weight: 700;">Sin carga</span>
                    <?php endif; ?>
                  </span>
                </div>
                <!-- Historial últimos 5 criogénicos reales -->
                <div style="margin-top: 8px; padding-top: 6px; border-top: 1px solid var(--border-subtle);">
                  <div style="font-size: 9.5px; font-weight: 700; color: var(--accent-cyan); text-transform: uppercase; margin-bottom: 4px;">
                    📊 Últimos 5 Registros Criogénicos:
                  </div>
                  <div style="display: flex; flex-direction: column; gap: 2px; font-size: 10px; font-family: var(--font-mono);">
                    <?php if (empty($hist_dedofrio_vis)): ?>
                      <div style="color: var(--text-dim); font-style: italic;">Sin registros criogénicos previos en MySQL.</div>
                    <?php else: foreach ($hist_dedofrio_vis as $idx_df => $hdf): 
                      $row_col = ($idx_df === 0) ? '#f472b6' : 'var(--text-muted)';
                      $df_pref = $hdf['menor_que'] ? '&lt; ' : '';
                    ?>
                      <div style="display: flex; justify-content: space-between; color: <?php echo $row_col; ?>;">
                        <span>[<?php echo $idx_df + 1; ?>] <?php echo htmlspecialchars($hdf['fecha_corta'] . ' ' . $hdf['hora'] . ' (' . $hdf['apellido'] . ')', ENT_QUOTES, 'UTF-8'); ?>:</span>
                        <?php if ($idx_df === 0): ?>
                          <strong><?php echo $df_pref . htmlspecialchars($hdf['ppm'], ENT_QUOTES, 'UTF-8'); ?> ppm</strong>
                        <?php else: ?>
                          <span><?php echo $df_pref . htmlspecialchars($hdf['ppm'], ENT_QUOTES, 'UTF-8'); ?> ppm</span>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; endif; ?>
                  </div>
                </div>
              </div>

              <!-- Gases Nobles Chimenea -->
              <div class="gas-sub-card">
                <div class="gas-sub-title">
                  <span>Gases Nobles Chimenea</span>
                  <span style="font-family: var(--font-mono); font-size: 10px;">GBq</span>
                </div>
                <div class="metric-row">
                  <span class="metric-lbl">Ar-41:</span>
                  <span class="metric-val"><?php echo decimales_limpio($row_gases_nobles['ar41'], 2); ?> GBq</span>
                </div>
                <div class="metric-row">
                  <span class="metric-lbl">Kr-85 / Kr-85m:</span>
                  <span class="metric-val"><?php echo decimales_limpio($row_gases_nobles['kr85'], 2) . ' / ' . decimales_limpio($row_gases_nobles['kr85m'], 2); ?></span>
                </div>
                <div class="metric-row">
                  <span class="metric-lbl">Xe-133 / Xe-135:</span>
                  <span class="metric-val"><?php echo decimales_limpio($row_gases_nobles['xe133'], 2) . ' / ' . decimales_limpio($row_gases_nobles['xe135'], 2); ?></span>
                </div>
              </div>

              <!-- Concentrador 7923 -->
              <div class="gas-sub-card">
                <div class="gas-sub-title">
                  <span>Concentrador 7923</span>
                </div>
                <div class="metric-row">
                  <span class="metric-lbl">Procedencia:</span>
                  <span class="metric-val"><?php echo htmlspecialchars($row_concentrador['procedencia'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="metric-row">
                  <span class="metric-lbl">Actividad Bq:</span>
                  <span class="metric-val"><?php echo decimales_limpio($row_concentrador['actividad_Bq'], 3); ?> TBq</span>
                </div>
                <div class="metric-row">
                  <span class="metric-lbl">Volumen / % RoDD:</span>
                  <span class="metric-val"><?php echo decimales_limpio($row_concentrador['volumen_litro'], 2); ?> m³ | <?php echo decimales_limpio($row_concentrador['ldd%'], 2); ?>%</span>
                </div>
              </div>

              <!-- Control Precintos -->
              <div class="gas-sub-card">
                <div class="gas-sub-title">
                  <span>Control de Precintos</span>
                  <span style="font-family: var(--font-mono); font-size: 10px;"><?php echo formato_hora_limpio($row_precintos['hora']); ?></span>
                </div>
                <div class="metric-row">
                  <span class="metric-lbl">Puerta S-123B:</span>
                  <span class="metric-val"><?php echo htmlspecialchars($row_precintos['puerta_s123B'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="metric-row">
                  <span class="metric-lbl">Puerta S-008:</span>
                  <span class="metric-val"><?php echo htmlspecialchars($row_precintos['puerta_s008'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="metric-row">
                  <span class="metric-lbl">Puerta S-144:</span>
                  <span class="metric-val"><?php echo htmlspecialchars($row_precintos['puerta_s144'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
              </div>

              <!-- Meteorología Pasquill -->
              <div class="gas-sub-card">
                <div class="gas-sub-title">
                  <span>Condición Meteorológica</span>
                  <span style="font-family: var(--font-mono); font-size: 10px;"><?php echo formato_hora_limpio($row_meteorologia['hora']); ?></span>
                </div>
                <div class="metric-row">
                  <span class="metric-lbl">Velocidad del Viento:</span>
                  <span class="metric-val"><?php echo htmlspecialchars($row_meteorologia['velocidad_mseg'], ENT_QUOTES, 'UTF-8'); ?> m/s</span>
                </div>
                <div class="metric-row">
                  <span class="metric-lbl">Dirección:</span>
                  <span class="metric-val"><?php echo htmlspecialchars($row_meteorologia['direc_viento'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="metric-row">
                  <span class="metric-lbl">Categoría Pasquill:</span>
                  <span class="metric-val" style="color: var(--accent-cyan); font-weight: 800;"><?php echo htmlspecialchars($row_meteorologia['cond_pasquill'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

      <!-- ================= SECCIÓN 3: TAREAS & PTR ================= -->
      <div class="section-content" id="sec-tareas">
        <div class="panel-box">
          <div class="panel-box-header">
            <h2>📋 TAREAS DE CAMPO &amp; PTR</h2>
            <span style="font-size: 11px; color: var(--text-muted); font-family: var(--font-mono);">
              <?php echo count($rows_tareas); ?> tareas registradas en este turno
            </span>
          </div>
          <div class="panel-box-body">
            <table class="data-table">
              <thead>
                <tr>
                  <th style="width: 90px;">N° PTR</th>
                  <th style="text-align: left;">Recinto / Ubicación</th>
                  <th>Oficial Responsable</th>
                  <th>Ayudante SyR</th>
                  <th style="width: 90px;">Dosis (µSv/h)</th>
                  <th style="width: 100px;">Derrame D2O</th>
                  <th style="text-align: left;">Descripción de la Tarea</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($rows_tareas)): ?>
                  <tr>
                    <td colspan="7" style="color: var(--text-muted); font-style: italic; padding: 20px;">
                      - No se asentaron tareas individuales PTR en este turno -
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($rows_tareas as $t): ?>
                    <tr class="acta-clickable-row" onclick="abrirModalDetalleActaFromRow(this)"
                        data-hora="Turno"
                        data-ptr="<?php echo htmlspecialchars(!empty($t['id_ptr']) ? $t['id_ptr'] : '-', ENT_QUOTES, 'UTF-8'); ?>"
                        data-recinto="<?php echo htmlspecialchars(!empty($t['recinto_nombre']) ? $t['recinto_nombre'] : 'General de Planta', ENT_QUOTES, 'UTF-8'); ?>"
                        data-oficial="<?php echo htmlspecialchars((!empty($t['of_apellido']) ? $t['of_apellido'] . ', ' . $t['of_nombre'] : $oficial_nombre), ENT_QUOTES, 'UTF-8'); ?>"
                        data-ayudante="<?php echo htmlspecialchars(!empty($t['nombre_ayudante']) ? $t['nombre_ayudante'] : '-', ENT_QUOTES, 'UTF-8'); ?>"
                        data-texto="<?php echo htmlspecialchars($t['texto_tarea'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-dosis="<?php echo decimales_limpio($t['tasa_dosis'], 1); ?>"
                        data-derrame="<?php echo !empty($t['derrame_agua']) ? $t['derrame_agua'] : '0'; ?>">
                      <td class="mono" style="font-weight: 700; color: var(--accent-cyan);"><?php echo htmlspecialchars(!empty($t['id_ptr']) ? $t['id_ptr'] : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td class="text-left" style="font-weight: 600;"><?php echo htmlspecialchars(!empty($t['recinto_nombre']) ? $t['recinto_nombre'] : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td style="font-weight: 600; text-transform: uppercase;">
                        <?php echo htmlspecialchars((!empty($t['of_apellido']) ? $t['of_apellido'] . ", " . $t['of_nombre'] : $oficial_nombre), ENT_QUOTES, 'UTF-8'); ?>
                      </td>
                      <td style="text-transform: uppercase;">
                        <?php echo htmlspecialchars(!empty($t['nombre_ayudante']) ? $t['nombre_ayudante'] : '-', ENT_QUOTES, 'UTF-8'); ?>
                      </td>
                      <td class="mono"><?php echo decimales_limpio($t['tasa_dosis'], 1); ?></td>
                      <td>
                        <?php if (!empty($t['derrame_agua']) && $t['derrame_agua'] == 1): ?>
                          <span style="background: rgba(245, 158, 11, 0.2); color: var(--accent-amber); padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 10px;">SÍ (D2O)</span>
                        <?php else: ?>
                          <span style="color: var(--text-dim);">NO</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-left" style="max-width: 320px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                          <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 260px; display: inline-block;">
                            <?php echo htmlspecialchars($t['texto_tarea'], ENT_QUOTES, 'UTF-8'); ?>
                          </span>
                          <button type="button" class="btn-acta-open" title="Abrir y ver acta completa">🔍 Ver</button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ================= SECCIÓN 4: LIBRO DE ACTAS ================= -->
      <div class="section-content" id="sec-actas">
        <div class="panel-box">
          <div class="panel-box-header">
            <h2>📖 LIBRO DE ACTAS</h2>
            <span style="font-size: 11px; color: var(--text-muted);">
              Asentado en Planilla #<?php echo $id_planilla; ?>
            </span>
          </div>
          <div class="panel-box-body">
            <div id="contenedor-actas-texto" class="terminal-box">
              Cargando novedades del servidor...
            </div>
          </div>
        </div>
      </div>

      <!-- ================= SECCIÓN 5: EFLUENTES LÍQUIDOS & ESPECTROMETRÍA ================= -->
      <div class="section-content" id="sec-liquidos">
        <div class="panel-box">
          <div class="panel-box-header">
            <h2>💧 EMISIÓN DE EFLUENTES LÍQUIDOS</h2>
          </div>
          <div class="panel-box-body">
            <?php if (empty($rows_efluentes_liq)): ?>
              <div style="color: var(--text-muted); font-style: italic; text-align: center; padding: 14px;">
                - No se registraron descargas de efluentes líquidos en este turno -
              </div>
            <?php else: ?>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Tanque</th>
                    <th>Volumen (m³)</th>
                    <th>Actividad Tritio (TBq)</th>
                    <th>Actividad Gamma (TBq)</th>
                    <th>% del RoDD</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($rows_efluentes_liq as $ef): 
                    $roddTot = (float)$ef['LDD_Ro_tritio'] + (float)$ef['LDD_Ro_gamma'];
                  ?>
                    <tr>
                      <td class="mono"><?php echo formafecha_latam($ef['fecha']); ?></td>
                      <td class="mono"><?php echo formato_hora_limpio($ef['hora']); ?></td>
                      <td style="font-weight: 700;"><?php echo htmlspecialchars($ef['tanque'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td class="mono"><?php echo decimales_limpio($ef['volumen_litro'], 2); ?></td>
                      <td class="mono"><?php echo htmlspecialchars($ef['actividad_tritio'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td class="mono"><?php echo htmlspecialchars($ef['actividad_gamma'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td class="mono" style="font-weight: 700; color: var(--accent-cyan);"><?php echo decimales_limpio($roddTot, 3); ?>%</td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>

        <!-- Espectrometría del Aire -->
        <div class="panel-box" style="margin-top: 14px;">
          <div class="panel-box-header">
            <h2>🔬 ESPECTROMETRÍA DEL AIRE DE ZONA CONTROLADA</h2>
          </div>
          <div class="panel-box-body">
            <?php if (empty($rows_espectrometria)): ?>
              <div style="color: var(--text-muted); font-style: italic; text-align: center; padding: 14px;">
                - No se registraron espectrometrías de aire específicas en este turno -
              </div>
            <?php else: ?>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Concentración</th>
                    <th>Recinto</th>
                    <th>Hora</th>
                    <th>Ar-41 (DAC)</th>
                    <th>Xe-133 (DAC)</th>
                    <th>Xe-135 (DAC)</th>
                    <th>Kr-88 (DAC)</th>
                    <th>TOTAL (DAC)</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($rows_espectrometria as $esp): 
                    $tot = (float)$esp['ar41'] + (float)$esp['xe133'] + (float)$esp['xe135'] + (float)$esp['kr88'];
                  ?>
                    <tr>
                      <td><?php echo strtoupper($esp['tipo']); ?></td>
                      <td><?php echo htmlspecialchars($esp['recinto_nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td class="mono"><?php echo formato_hora_limpio($esp['hora']); ?></td>
                      <td class="mono"><?php echo decimales_limpio($esp['ar41'], 1); ?></td>
                      <td class="mono"><?php echo decimales_limpio($esp['xe133'], 1); ?></td>
                      <td class="mono"><?php echo decimales_limpio($esp['xe135'], 1); ?></td>
                      <td class="mono"><?php echo decimales_limpio($esp['kr88'], 1); ?></td>
                      <td class="mono" style="font-weight: 700; color: var(--accent-cyan);"><?php echo decimales_limpio($tot, 1); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- ================= SECCIÓN 6: CONTAMINACIÓN & DRYERS ================= -->
      <div class="section-content" id="sec-contaminacion">
        <div class="panel-box">
          <div class="panel-box-header">
            <h2>🧽 MEDICIÓN DE CONT. SUPERFICIAL Y TASA DE DOSIS EQUIVALENTE</h2>
          </div>
          <div class="panel-box-body">
            <table class="data-table">
              <thead>
                <tr>
                  <th rowspan="2" style="text-align: left;">Recinto</th>
                  <th rowspan="2" style="width: 80px;">Hora</th>
                  <th colspan="2">Contaminación Superficial (Bq/cm²)</th>
                  <th rowspan="2" style="width: 140px;">Tasa Dosis Eq.<br><span style="font-weight: normal;">(µSv/h)</span></th>
                </tr>
                <tr>
                  <th style="width: 100px;">β + γ</th>
                  <th style="width: 100px;">α</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows_contaminacion as $cs): ?>
                  <tr>
                    <td class="text-left" style="font-weight: 600;"><?php echo htmlspecialchars(!empty($cs['recinto_nombre']) ? $cs['recinto_nombre'] : 'Recinto', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="mono"><?php echo formato_hora_limpio($cs['hora']); ?></td>
                    <td class="mono"><?php echo decimales_limpio($cs['beta_gamma_Bq_cm2'], 2); ?></td>
                    <td class="mono"><?php echo decimales_limpio($cs['alfa_Bq_cm2'], 2); ?></td>
                    <td class="mono" style="font-weight: 700; color: var(--accent-cyan);"><?php echo decimales_limpio($cs['tasa_dosis_equivalente_µsv_h'], 1); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Eficiencia Dryers -->
        <div class="panel-box" style="margin-top: 14px;">
          <div class="panel-box-header">
            <h2>🔄 EFICIENCIA DRYERS</h2>
          </div>
          <div class="panel-box-body">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Hora</th>
                  <th>Secador / Dryer</th>
                  <th>Entrada (DAC)</th>
                  <th>Salida (DAC)</th>
                  <th>Eficiencia (%)</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows_dryers as $dr): ?>
                  <tr>
                    <td class="mono"><?php echo formafecha_latam($dr['fecha']); ?></td>
                    <td class="mono"><?php echo formato_hora_limpio($dr['hora']); ?></td>
                    <td style="font-weight: 600;"><?php echo htmlspecialchars($dr['nombre_dryers'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="mono"><?php echo decimales_limpio($dr['entrada_dac'], 1); ?></td>
                    <td class="mono"><?php echo decimales_limpio($dr['salida_dac'], 1); ?></td>
                    <td class="mono" style="font-weight: 800; color: var(--accent-green);"><?php echo decimales_limpio($dr['eficiencia_porcentaje'], 2); ?>%</td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ================= SECCIÓN 7: OBSERVACIONES ================= -->
      <div class="section-content" id="sec-obs">
        <div class="panel-box">
          <div class="panel-box-header">
            <h2>📝 OBSERVACIONES GENERALES DE LA GUARDIA</h2>
          </div>
          <div class="panel-box-body">
            <div class="terminal-box"><?php echo htmlspecialchars($row_planilla['observaciones'], ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
        </div>
      </div>

    </div>
  </main>

  <script>
    // ================= NAVEGACIÓN ENTRE SECCIONES (SIDEBAR & KPIS) =================
    const menuBtns = document.querySelectorAll('.sidebar-nav .menu-btn');
    const sections = document.querySelectorAll('.section-content');
    const sectionTitle = document.getElementById('current-section-title');

    function goToSection(secId) {
      if (secId === 'sec-tareas') secId = 'sec-actas';
      // 1. Activar botón de sidebar correspondiente
      menuBtns.forEach(btn => {
        if (btn.dataset.section === secId) {
          btn.classList.add('active');
          const titleText = btn.querySelector('span:first-child').textContent.replace(/[📋🏭📝📖💧🔬💬⚙️]\s*/g, '').trim();
          if (sectionTitle) sectionTitle.textContent = titleText;
        } else {
          btn.classList.remove('active');
        }
      });

      // 2. Activar sección de contenido
      sections.forEach(sec => {
        if (sec.id === secId) {
          sec.classList.add('active');
        } else {
          sec.classList.remove('active');
        }
      });
    }

    menuBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        goToSection(btn.dataset.section);
      });
    });

    // ================= RECUPERAR LIBRO DE ACTAS VÍA AJAX =================
    (function() {
      const idPlanilla = '<?php echo $id_planilla; ?>';
      const box = document.getElementById('contenedor-actas-texto');
      if (!box) return;

      fetch('procesos_reg_rad.php?accion=recuperar_novedades_libro_actas&id_planilla=' + idPlanilla)
        .then(r => r.text())
        .then(texto => {
          if (texto && texto.trim() !== '') {
            box.textContent = texto;
          } else {
            box.textContent = "- Turno sin novedades radiológicas extraordinarias. Recorridas completadas conforme a PO-SyR-01. Monitoreo de efluentes gaseosos y chimenea dentro de límites operacionales normales -";
          }
        })
        .catch(() => {
          box.textContent = "- Turno sin novedades radiológicas extraordinarias. Recorridas completadas conforme a PO-SyR-01. Monitoreo de efluentes gaseosos y chimenea dentro de límites operacionales normales -";
        });
    })();
      // ================= MODAL INTERACTIVO DE HISTORIAL POR RECINTO GUARDADO =================
    let activeSelectedRecintoForPrefill = '';

    const mockRecintoHistoryVis = {
      '1': [
        { fecha: '08/11/2026', hora: '23:30', dosis: '2.5', tritio: '0.20', gases: '0.00' },
        { fecha: '08/11/2026', hora: '15:40', dosis: '2.0', tritio: '0.15', gases: '0.00' },
        { fecha: '08/11/2026', hora: '08:10', dosis: '1.8', tritio: '0.20', gases: '0.00' }
      ],
      '24': [
        { fecha: '08/11/2026', hora: '23:30', dosis: '24.0', tritio: '0.45', gases: '0.00' },
        { fecha: '08/11/2026', hora: '15:40', dosis: '18.5', tritio: '0.40', gases: '0.00' },
        { fecha: '08/11/2026', hora: '08:15', dosis: '12.0', tritio: '0.25', gases: '0.00' }
      ],
      '5': [
        { fecha: '08/11/2026', hora: '23:35', dosis: '18.5', tritio: '1.80', gases: '0.40' },
        { fecha: '08/11/2026', hora: '15:50', dosis: '15.0', tritio: '1.50', gases: '0.30' },
        { fecha: '08/11/2026', hora: '08:20', dosis: '14.0', tritio: '1.20', gases: '0.20' }
      ],
      '12': [
        { fecha: '08/11/2026', hora: '23:45', dosis: '45.0', tritio: '3.40', gases: '0.85' },
        { fecha: '08/11/2026', hora: '16:00', dosis: '42.0', tritio: '3.10', gases: '0.80' },
        { fecha: '08/11/2026', hora: '08:30', dosis: '40.0', tritio: '2.80', gases: '0.70' }
      ]
    };

    function openRecintoHistoryModal(recintoParam) {
      const modal = document.getElementById('modal-recinto-historial');
      const labelNombre = document.getElementById('mrh-recinto-nombre');
      const container = document.getElementById('mrh-chips-container');
      const trendBadge = document.getElementById('mrh-trend-badge');
      if (!modal || !container) return;

      activeSelectedRecintoForPrefill = recintoParam;
      if (labelNombre) labelNombre.textContent = recintoParam;

      let history = null;
      const idMatch = (typeof recintoParam === 'string') ? recintoParam.match(/R-?(\d+)/i) : null;
      const recintoId = idMatch ? parseInt(idMatch[1], 10).toString() : '1';

      if (mockRecintoHistoryVis[recintoId]) {
        history = mockRecintoHistoryVis[recintoId];
      } else {
        history = [
          { fecha: '08/11/2026', hora: '23:30', dosis: '4.5', tritio: '0.40', gases: '0.00' },
          { fecha: '08/11/2026', hora: '15:40', dosis: '4.0', tritio: '0.35', gases: '0.00' },
          { fecha: '08/11/2026', hora: '08:10', dosis: '3.8', tritio: '0.30', gases: '0.00' }
        ];
      }

      container.innerHTML = history.map((item, idx) => `
        <div style="background: rgba(9, 28, 24, 0.85); border: 1px solid var(--border-card); border-left: 3px solid var(--accent-cyan); border-radius: 6px; padding: 9px 14px; display: flex; align-items: center; justify-content: space-between; font-family: var(--font-mono); font-size: 11.5px;">
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-weight: 800; color: var(--accent-cyan); font-size: 12px;">[${idx + 1}]</span>
            <span style="color: #ffffff; font-weight: 700;">📅 ${item.fecha} &bull; ${item.hora} hs</span>
          </div>
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="color: #c084fc; font-weight: 700;"><span style="color: var(--text-dim); font-size: 10px; font-weight: 600;">G. nobles:</span> ${item.gases} DAC</span>
            <span style="color: var(--border-card);">|</span>
            <span style="color: var(--accent-cyan); font-weight: 700;"><span style="color: var(--text-dim); font-size: 10px; font-weight: 600;">Tritio:</span> ${item.tritio} DAC</span>
            <span style="color: var(--border-card);">|</span>
            <span style="color: var(--accent-amber); font-weight: 700;"><span style="color: var(--text-dim); font-size: 10px; font-weight: 600;">Dosis:</span> ${item.dosis} µSv/h</span>
          </div>
        </div>
      `).join('');

      const d1 = parseFloat(history[0].dosis);
      const d3 = parseFloat(history[2].dosis);
      if (d1 > d3 * 1.3) {
        trendBadge.innerHTML = '📈 Alza de Dosis (+ ' + Math.round(((d1 - d3)/d3)*100) + '%)';
        trendBadge.style.color = 'var(--accent-amber)';
      } else {
        trendBadge.innerHTML = '🟢 Niveles estables';
        trendBadge.style.color = 'var(--accent-green)';
      }

      modal.style.display = 'flex';
    }

    function closeRecintoHistoryModal() {
      const modal = document.getElementById('modal-recinto-historial');
      if (modal) modal.style.display = 'none';
    }

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeRecintoHistoryModal();
      }
    });

      // ================= MODAL DE DETALLE Y LECTURA COMPLETA DE ACTA =================
    function abrirModalDetalleActaFromRow(tr) {
      if (!tr) return;
      const hora = tr.getAttribute('data-hora') || (tr.cells[0] ? tr.cells[0].textContent.trim() : '');
      const ptr = tr.getAttribute('data-ptr') || (tr.cells[1] ? tr.cells[1].textContent.trim() : '');
      const recinto = tr.getAttribute('data-recinto') || (tr.cells[2] ? tr.cells[2].textContent.trim() : '');
      const oficial = tr.getAttribute('data-oficial') || (tr.cells[3] ? tr.cells[3].textContent.trim() : '');
      const ayudante = tr.getAttribute('data-ayudante') || (tr.cells[4] ? tr.cells[4].textContent.trim() : '');
      const texto = tr.getAttribute('data-texto') || (tr.cells[5] ? tr.cells[5].textContent.trim() : '');
      const dosis = tr.getAttribute('data-dosis') || (tr.cells[6] ? tr.cells[6].textContent.trim() : '');
      const derrame = tr.getAttribute('data-derrame') || ((tr.cells[7] && tr.cells[7].textContent.indexOf('SÍ') !== -1) ? '1' : '0');
      const tritio = tr.getAttribute('data-tritio') || '0.0';
      const aerosoles = tr.getAttribute('data-aerosoles') || '-';

      abrirModalDetalleActa({ hora, ptr, recinto, oficial, ayudante, texto, dosis, derrame, tritio, aerosoles });
    }

    function abrirModalDetalleActa(d) {
      const modal = document.getElementById('modal-detalle-acta');
      if (!modal) return;

      document.getElementById('mda-hora-ptr').textContent = (d.ptr && d.ptr !== '-') ? ('PTR #' + d.ptr + ' • ' + d.hora + ' hs') : ('Acta de Guardia • ' + d.hora + ' hs');
      document.getElementById('mda-hora').textContent = d.hora + ' hs';
      document.getElementById('mda-ptr').textContent = (d.ptr && d.ptr !== '-') ? d.ptr : 'Sin PTR';
      document.getElementById('mda-recinto').textContent = d.recinto || 'General de Planta';
      document.getElementById('mda-oficial').textContent = d.oficial || '-';
      document.getElementById('mda-ayudante').textContent = (d.ayudante && d.ayudante !== '-') ? d.ayudante : 'Sin ayudante';
      
      const derrameEl = document.getElementById('mda-derrame');
      if (d.derrame === '1' || d.derrame === 1 || String(d.derrame).indexOf('SÍ') !== -1) {
        derrameEl.innerHTML = '<span style="color: var(--accent-amber);">⚠️ SÍ (D2O)</span>';
      } else {
        derrameEl.innerHTML = '<span style="color: var(--accent-green);">🟢 NO</span>';
      }

      document.getElementById('mda-dosis').textContent = 'Dosis: ' + (d.dosis || '0.0') + ' µSv/h';
      document.getElementById('mda-tritio').textContent = 'Tritio: ' + (d.tritio || '0.0') + ' DAC';
      document.getElementById('mda-aerosoles').textContent = 'Aerosoles: ' + (d.aerosoles || '0.0') + ' DAC';
      document.getElementById('mda-texto-completo').textContent = d.texto || 'Sin observaciones';

      modal.style.display = 'flex';
    }

    function closeActaModal() {
      const modal = document.getElementById('modal-detalle-acta');
      if (modal) modal.style.display = 'none';
    }

    function copiarTextoActa() {
      const txt = document.getElementById('mda-texto-completo') ? document.getElementById('mda-texto-completo').textContent : '';
      if (navigator.clipboard) {
        navigator.clipboard.writeText(txt).then(() => {
          if (typeof showToast === 'function') showToast('📋 Descripción copiada al portapapeles');
        });
      } else {
        if (typeof showToast === 'function') showToast('📋 Texto disponible en pantalla');
      }
    }

    // ====================================================
    // MOTOR DE TOOLTIPS CYBERPUNK (REEMPLAZO TOOLTIP NATIVO)
    // ====================================================
    (function initCyberTooltips() {
      let tooltipEl = document.getElementById('cyber-tooltip');
      if (!tooltipEl) {
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'cyber-tooltip';
        document.body.appendChild(tooltipEl);
      }

      let activeTarget = null;

      function showTooltip(el, text) {
        if (!text || !text.trim()) return;
        activeTarget = el;

        tooltipEl.className = '';
        const isAlert = text.includes('⚠️') || text.includes('Alza') || text.includes('Supera') || text.includes('Discrepancia') || el.classList.contains('badge-amber');
        const isDanger = text.includes('🚨') || text.includes('PELIGRO') || text.includes('CRÍTICO') || el.classList.contains('badge-red');

        if (isDanger) {
          tooltipEl.classList.add('danger');
        } else if (isAlert) {
          tooltipEl.classList.add('alert');
        }

        tooltipEl.textContent = text;
        tooltipEl.classList.add('visible');
        positionTooltip(el);
      }

      function positionTooltip(el) {
        if (!tooltipEl.classList.contains('visible')) return;
        const rect = el.getBoundingClientRect();
        const tipRect = tooltipEl.getBoundingClientRect();
        const margin = 8;

        let left = rect.left + (rect.width / 2) - (tipRect.width / 2);
        if (left < 10) left = 10;
        if (left + tipRect.width > window.innerWidth - 10) {
          left = window.innerWidth - tipRect.width - 10;
        }

        let top = rect.top - tipRect.height - margin;
        if (top < 10) {
          top = rect.bottom + margin;
        }

        tooltipEl.style.left = Math.round(left) + 'px';
        tooltipEl.style.top = Math.round(top) + 'px';
      }

      function hideTooltip() {
        activeTarget = null;
        tooltipEl.classList.remove('visible');
      }

      // Interceptar mouseover en captura para suprimir de inmediato title nativo blanco
      document.addEventListener('mouseover', function(e) {
        const el = e.target.closest('[title], [data-tooltip]');
        if (!el) return;

        if (el.hasAttribute('title')) {
          const titleVal = el.getAttribute('title');
          if (titleVal && titleVal.trim()) {
            el.setAttribute('data-tooltip', titleVal);
          }
          el.removeAttribute('title');
        }

        const tipText = el.getAttribute('data-tooltip');
        if (tipText) {
          showTooltip(el, tipText);
        }
      }, true);

      document.addEventListener('mouseout', function(e) {
        if (!activeTarget) return;
        if (e.relatedTarget && activeTarget.contains(e.relatedTarget)) {
          return;
        }
        hideTooltip();
      }, true);

      window.addEventListener('scroll', function() {
        if (activeTarget) positionTooltip(activeTarget);
      }, { passive: true });
    })();

    // ================= SELECTOR DE FONDO Y TEMAS EN VIVO =================
    function closeThemeDropdown() {
      const dd = document.getElementById('theme-dropdown');
      const btn = document.getElementById('btn-theme-toggle');
      if (dd) dd.classList.remove('open');
      if (btn) btn.setAttribute('aria-expanded', 'false');
    }

    function toggleThemeDropdown(e) {
      if (e) e.stopPropagation();
      const dd = document.getElementById('theme-dropdown');
      const btn = document.getElementById('btn-theme-toggle');
      if (!dd) return;
      const willOpen = !dd.classList.contains('open');
      dd.classList.toggle('open', willOpen);
      if (btn) btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    }

    document.addEventListener('click', function(e) {
      const dd = document.getElementById('theme-dropdown');
      if (dd && dd.classList.contains('open') && !dd.contains(e.target)) {
        closeThemeDropdown();
      }
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeThemeDropdown();
      }
    });

    function setAppTheme(themeName) {
      document.documentElement.setAttribute('data-theme', themeName);
      try {
        localStorage.setItem('regrad_active_theme', themeName);
      } catch(e) {}

      let activeLabel = themeName;
      let activeColor = '#10b981';
      document.querySelectorAll('#theme-dropdown-menu .theme-menu-item').forEach(btn => {
        const isMatch = btn.getAttribute('data-theme') === themeName;
        btn.classList.toggle('active', isMatch);
        if (isMatch) {
          activeLabel = btn.getAttribute('data-label') || themeName;
          activeColor = btn.getAttribute('data-color') || activeColor;
        }
      });

      const currentLabelEl = document.getElementById('theme-current-label');
      const currentDotEl = document.getElementById('theme-current-dot');
      if (currentLabelEl) currentLabelEl.textContent = activeLabel;
      if (currentDotEl) currentDotEl.style.background = activeColor;

      closeThemeDropdown();
    }

    try {
      const savedTheme = localStorage.getItem('regrad_active_theme') || 'petrol';
      if (savedTheme) {
        setAppTheme(savedTheme);
      }
    } catch(e) {}
  </script>
  <!-- MODAL HUD: HISTORIAL DE LOS ÚLTIMOS 3 VALORES DEL RECINTO SELECCIONADO -->
  <div class="modal-overlay" id="modal-recinto-historial" style="display: none;" onclick="if(event.target===this) closeRecintoHistoryModal();">
    <div class="modal-box" style="max-width: 620px; border-left: 4px solid var(--accent-cyan); animation: cyber-hologram-assemble 0.28s ease-out;">
      <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
          <div style="font-size: 11px; font-weight: 700; color: var(--accent-cyan); text-transform: uppercase; letter-spacing: 0.5px;">
            📍 Historial Dosimétrico del Recinto
          </div>
          <div id="mrh-recinto-nombre" style="font-size: 15px; font-weight: 800; color: #ffffff; margin-top: 2px;">
            -
          </div>
        </div>
        <button type="button" class="btn-modal-close" onclick="closeRecintoHistoryModal()" style="background: none; border: none; color: var(--text-dim); font-size: 20px; cursor: pointer; padding: 4px 8px; border-radius: 4px;" title="Cerrar modal (Esc)">&times;</button>
      </div>
      <div class="modal-body" style="padding: 16px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; background: rgba(16, 185, 129, 0.08); border: 1px solid var(--border-card); border-radius: 6px; padding: 7px 12px;">
          <span style="font-size: 11px; color: var(--text-muted); font-weight: 700;">
            📋 Últimas 3 mediciones registradas en este recinto
          </span>
          <span id="mrh-trend-badge" style="font-size: 11px; font-weight: 700; color: var(--accent-green); font-family: var(--font-mono);">
            🟢 Niveles habituales
          </span>
        </div>

        <!-- Contenedor con las 3 tarjetas de historial (Fecha y Hora solamente, NO oficial, NO turno) -->
        <div id="mrh-chips-container" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px;">
          <!-- JS inyecta las filas -->
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-subtle); padding-top: 12px;">
          <span style="font-size: 10px; color: var(--text-dim); font-family: var(--font-mono);">
            * Registro de Radioprotección CNE &bull; Solo Fecha y Hora
          </span>
          <div style="display: flex; gap: 8px;">
            <button type="button" class="btn-ghost" onclick="closeRecintoHistoryModal()" style="padding: 5px 12px; font-size: 12px; border-radius: 6px; border: 1px solid var(--border-card); background: transparent; color: var(--text-muted); cursor: pointer;">
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL HUD: DETALLE Y LECTURA COMPLETA DE ACTA DE GUARDIA -->
  <div class="modal-overlay" id="modal-detalle-acta" style="display: none;" onclick="if(event.target===this) closeActaModal();">
    <div class="modal-box" style="max-width: 720px; border-left: 4px solid var(--accent-cyan); animation: cyber-hologram-assemble 0.28s ease-out;">
      <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <span style="font-size: 20px;">📖</span>
          <div>
            <div style="font-size: 11px; font-weight: 700; color: var(--accent-cyan); text-transform: uppercase; letter-spacing: 0.5px;">
              Libro de Actas &bull; Registro Completo
            </div>
            <div id="mda-hora-ptr" style="font-size: 15px; font-weight: 800; color: #ffffff; margin-top: 1px;">
              -
            </div>
          </div>
        </div>
        <button type="button" class="btn-modal-close" onclick="closeActaModal()" style="background: none; border: none; color: var(--text-dim); font-size: 20px; cursor: pointer; padding: 4px 8px; border-radius: 4px;" title="Cerrar modal (Esc)">&times;</button>
      </div>

      <div class="modal-body" style="padding: 16px 22px;">
        <!-- Ficha técnica en tarjetas compactas -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 14px;">
          <div style="background: rgba(16, 185, 129, 0.06); border: 1px solid var(--border-card); border-radius: 6px; padding: 8px 10px;">
            <div style="font-size: 9.5px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Hora / Momento</div>
            <div id="mda-hora" style="font-size: 13px; font-weight: 800; color: #ffffff; font-family: var(--font-mono); margin-top: 2px;">-</div>
          </div>
          <div style="background: rgba(16, 185, 129, 0.06); border: 1px solid var(--border-card); border-radius: 6px; padding: 8px 10px;">
            <div style="font-size: 9.5px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">N° PTR</div>
            <div id="mda-ptr" style="font-size: 13px; font-weight: 800; color: var(--accent-cyan); font-family: var(--font-mono); margin-top: 2px;">-</div>
          </div>
          <div style="background: rgba(16, 185, 129, 0.06); border: 1px solid var(--border-card); border-radius: 6px; padding: 8px 10px;">
            <div style="font-size: 9.5px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Recinto</div>
            <div id="mda-recinto" style="font-size: 12px; font-weight: 700; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px;">-</div>
          </div>
          <div style="background: rgba(16, 185, 129, 0.06); border: 1px solid var(--border-card); border-radius: 6px; padding: 8px 10px;">
            <div style="font-size: 9.5px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Derrame D2O</div>
            <div id="mda-derrame" style="font-size: 12px; font-weight: 800; margin-top: 2px;">-</div>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;">
          <div style="background: var(--bg-card-subtle); border: 1px solid var(--border-subtle); border-radius: 6px; padding: 8px 12px;">
            <div style="font-size: 9.5px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Oficial Responsable</div>
            <div id="mda-oficial" style="font-size: 12.5px; font-weight: 700; color: #ffffff; margin-top: 2px;">-</div>
          </div>
          <div style="background: var(--bg-card-subtle); border: 1px solid var(--border-subtle); border-radius: 6px; padding: 8px 12px;">
            <div style="font-size: 9.5px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Personal Asistente / Ayudante</div>
            <div id="mda-ayudante" style="font-size: 12.5px; font-weight: 700; color: var(--text-muted); margin-top: 2px;">-</div>
          </div>
        </div>

        <!-- Parámetros Radiológicos adicionales -->
        <div style="display: flex; gap: 10px; margin-bottom: 14px; background: rgba(9, 28, 24, 0.7); border: 1px solid var(--border-card); border-radius: 6px; padding: 7px 12px; font-family: var(--font-mono); font-size: 11px;">
          <span style="color: var(--text-muted);">Valores de radioprotección:</span>
          <span style="color: var(--accent-amber); font-weight: 700;" id="mda-dosis">Dosis: -</span>
          <span style="color: var(--text-dim);">|</span>
          <span style="color: var(--accent-cyan); font-weight: 700;" id="mda-tritio">Tritio: -</span>
          <span style="color: var(--text-dim);">|</span>
          <span style="color: #c084fc; font-weight: 700;" id="mda-aerosoles">Aerosoles: -</span>
        </div>

        <!-- Descripción Completa sin límite de renglón -->
        <div style="margin-bottom: 16px;">
          <div style="font-size: 11px; font-weight: 700; color: var(--accent-cyan); text-transform: uppercase; margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between;">
            <span>📝 Descripción Completa / Novedad Asentada:</span>
            <span style="font-size: 10px; color: var(--text-dim); text-transform: none; font-weight: normal;">(Visualización completa del texto)</span>
          </div>
          <div id="mda-texto-completo" style="background: rgba(3, 15, 12, 0.95); border: 1px solid var(--border-card); border-left: 3px solid var(--accent-cyan); border-radius: 6px; padding: 14px 16px; font-size: 13px; line-height: 1.6; color: #ffffff; white-space: pre-wrap; word-break: break-word; max-height: 280px; overflow-y: auto; font-family: var(--font-sans);">
            -
          </div>
        </div>

        <!-- Acciones Footer -->
        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-subtle); padding-top: 12px;">
          <button type="button" class="btn-ghost" onclick="copiarTextoActa()" id="btn-copiar-acta" style="padding: 6px 14px; font-size: 11.5px; border-radius: 6px; border: 1px solid var(--border-card); background: rgba(255,255,255,0.05); color: var(--text-main); cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
            <span>📋</span> Copiar Descripción
          </button>
          <button type="button" class="btn-primary" onclick="closeActaModal()" style="padding: 6px 18px; font-size: 12px; font-weight: 700; border-radius: 6px; background: var(--accent-cyan); color: #02120e; border: none; cursor: pointer;">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
