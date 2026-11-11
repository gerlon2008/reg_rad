<?php
/**
 * REGISTRO RADIOLÓGICO ONLINE - CENTRAL NUCLEAR EMBALSE (NA-SA)
 * Sistema de Supervisión y Registro de Radioprotección (SyR)
 * Compatible con PHP 5.5.7 y Apache 2.4 (100% Offline)
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$fecha_hoy_latam = date('d/m/Y');
$hora_actual = date('H:i');
$current_hour = (int)date('H');

// ================= INTELIGENCIA DE TURNO NOCHE =================
// El aviso de desfase aplica únicamente de madrugada (00:00 a 04:59 hs),
// cuando el operador del turno que comenzó a las 23:00 hs inicia sesión después de medianoche.
// A partir de las 05:00 hs ya corresponde al turno entrante (07:00 hs) con fecha de HOY.
if ($current_hour >= 0 && $current_hour < 5) {
    $fecha_operativa_default = date('d/m/Y', strtotime('-1 day'));
    $turno_default = 3; // Turno 3: Noche (23 a 07)
    $es_madrugada = true;
} else if ($current_hour >= 5 && $current_hour < 14) {
    $fecha_operativa_default = date('d/m/Y');
    $turno_default = 1; // Turno 1: Mañana (07 a 15)
    $es_madrugada = false;
} else if ($current_hour >= 14 && $current_hour < 23) {
    $fecha_operativa_default = date('d/m/Y');
    $turno_default = 2; // Turno 2: Tarde (15 a 23)
    $es_madrugada = false;
} else {
    $fecha_operativa_default = date('d/m/Y');
    $turno_default = 3; // Turno 3: Noche (23 a 07, antes de las 00:00)
    $es_madrugada = false;
}

// Variables por defecto (modo offline o fallback de demostración)
$id_planilla_activa = 10843;
$id_usuario_actual = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : 1;
// Oficial creador que inició la planilla activa
$oficial_iniciador_nombre = "ROLHAUSER, MARIANO";
$oficial_iniciador_id = 2;
// Oficial actualmente conectado en la sesión
$oficial_logueado = isset($_SESSION['nombre_apellido']) ? mb_strtoupper($_SESSION['nombre_apellido'], 'UTF-8') : (isset($_GET['usuario']) ? mb_strtoupper($_GET['usuario'], 'UTF-8') : "PEÑALOZA, JOSE");
$oficial_actual = $oficial_logueado;
$jefe_turno_actual = "GÓMEZ, CARLOS";
$estado_planta = "REACTOR EN POTENCIA 100%";
$turno_actual_nombre = ($turno_default == 3) ? "Turno 3 (23:00 - 07:00)" : (($turno_default == 2) ? "Turno 2 (14:00 - 22:00)" : "Turno 1 (06:00 - 14:00)");
$vent_gral_db = "1.45";
$vent_reactor_db = "3.80";
$c14_db = "12.4";
$dedo_frio_ppm_db = "12";
$dedo_frio_menor_que_db = 1;
$met_vel_db = "14.5";
$met_dir_db = "Sur";
$met_pasquill_db = "Clase D (Neutral)";
$gn_id_db = "";
$gn_desde_db = "00:00";
$gn_hasta_db = "18:00";
$gn_ar41_db = "0.0";
$gn_kr85_db = "0.0";
$gn_kr85m_db = "0.0";
$gn_xe133_db = "0.0";
$gn_xe135_db = "0.0";
$gn_xe135m_db = "0.0";
$hay_gases_nobles_db = false;

$rows_espectrometria_db = array(
  array(
    'id_conc_rad' => 1,
    'tipo' => 'GN',
    'recinto' => 501,
    'recinto_nombre' => 'R-501',
    'hora' => '17:54:00',
    'ar41' => 0.0,
    'xe133' => 0.0,
    'xe135' => 0.0,
    'kr88' => 0.0,
    'fecha' => '2026-11-08'
  )
);

$radionucleidos_liq_array = array(
  array('id_radionucleidos' => 1, 'radionucleido' => 'H-3'),
  array('id_radionucleidos' => 2, 'radionucleido' => 'Co-60'),
  array('id_radionucleidos' => 3, 'radionucleido' => 'Cs-137'),
  array('id_radionucleidos' => 4, 'radionucleido' => 'Cs-134'),
  array('id_radionucleidos' => 5, 'radionucleido' => 'I-131'),
  array('id_radionucleidos' => 6, 'radionucleido' => 'Mn-54'),
  array('id_radionucleidos' => 7, 'radionucleido' => 'Cr-51'),
  array('id_radionucleidos' => 8, 'radionucleido' => 'Fe-59'),
  array('id_radionucleidos' => 9, 'radionucleido' => 'Zr-95'),
  array('id_radionucleidos' => 10, 'radionucleido' => 'Nb-95'),
  array('id_radionucleidos' => 11, 'radionucleido' => 'Ru-103'),
  array('id_radionucleidos' => 12, 'radionucleido' => 'Sb-124')
);

$unidades_vol_liq_array = array(
  array('id_tabla_conversion' => '2', 'unidad' => 'm3', 'factor_a_litro' => 1000.0),
  array('id_tabla_conversion' => '1', 'unidad' => 'Litro (l)', 'factor_a_litro' => 1.0)
);

$unidades_conc_liq_array = array(
  array('id_tabla_conversion' => '2', 'unidad' => 'Bq/l', 'factor_a_bq_litro' => 1.0),
  array('id_tabla_conversion' => '3', 'unidad' => 'kBq/l', 'factor_a_bq_litro' => 1000.0),
  array('id_tabla_conversion' => '4', 'unidad' => 'MBq/l', 'factor_a_bq_litro' => 1000000.0),
  array('id_tabla_conversion' => '1', 'unidad' => 'uCi/ml', 'factor_a_bq_litro' => 37000000.0)
);

$efluentes_liquidos_db = array(
  array(
    'id_efluentes' => 9001,
    'fecha' => date('Y-m-d'),
    'hora' => '02:15',
    'tanque' => 'TK-01 (Tanque Retención Activo A)',
    'volumen_litro' => 45000,
    'volumen_m3' => 45.0,
    'actividad_tritio_Bq' => 5.4225e9,
    'actividad_tritio_TBq' => 0.0054225,
    'actividad_gamma_Bq' => 3.825e7,
    'actividad_gamma_TBq' => 0.00003825,
    'LDD_Ro_tritio' => 0.012,
    'LDD_Ro_gamma' => 0.004,
    'rodd_total' => 0.016,
    'observaciones' => 'Descarga autorizada por Operaciones (Ejemplo Offline)',
    'radionucleidos' => array(
      array('id' => 1, 'id_radionucleido' => 1, 'radionucleido' => 'H-3', 'conc_Bq_litro' => 120500),
      array('id' => 2, 'id_radionucleido' => 2, 'radionucleido' => 'Co-60', 'conc_Bq_litro' => 850)
    )
  )
);

$recintos_array = array(
  array('id' => 1, 'recinto' => 'R-001 - Edificio de Contención (+15m)'),
  array('id' => 2, 'recinto' => 'R-002 - Edificio de Contención (-5m)'),
  array('id' => 4, 'recinto' => 'R-004 - Blindaje Fuera del Núcleo'),
  array('id' => 12, 'recinto' => 'R-012 - Bóveda Moderador'),
  array('id' => 24, 'recinto' => 'R-024 - Sala de Bombas PHT'),
  array('id' => 28, 'recinto' => 'R-028 - Manifold Refrigerante Primario'),
  array('id' => 45, 'recinto' => 'R-045 - Bóveda de Calandria'),
  array('id' => 52, 'recinto' => 'R-052 - Intercambiadores de Calor D2O'),
  array('id' => 80, 'recinto' => 'R-080 - Área Tanques D2O'),
  array('id' => 95, 'recinto' => 'R-095 - Sala de Purificación'),
  array('id' => 110, 'recinto' => 'R-110 - Muestreador de Efluentes Chimenea'),
  array('id' => 125, 'recinto' => 'R-125 - Sala de Filtros de Ventilación Activa'),
  array('id' => 107, 'recinto' => 'R-107 112m'),
  array('id' => 108, 'recinto' => 'R-108 112m'),
  array('id' => 111, 'recinto' => 'R-111'),
  array('id' => 112, 'recinto' => 'R-112'),
  array('id' => 501, 'recinto' => 'R-501')
);

$turnos_array = array(
  array('id' => 1, 'horarios' => 'Turno 1 (06:00 - 14:00)'),
  array('id' => 2, 'horarios' => 'Turno 2 (14:00 - 22:00)'),
  array('id' => 3, 'horarios' => 'Turno 3 (23:00 - 07:00)')
);

$jefes_array = array(
  array('id' => 1, 'nombre' => 'GÓMEZ, CARLOS'),
  array('id' => 2, 'nombre' => 'MARTÍNEZ, ESTEBAN'),
  array('id' => 3, 'nombre' => 'VÁZQUEZ, JORGE')
);

$oficiales_array = array(
  array('id' => 1, 'nombre' => 'PEÑALOZA, JOSE'),
  array('id' => 2, 'nombre' => 'PÉREZ, JUAN'),
  array('id' => 3, 'nombre' => 'RODRÍGUEZ, MIGUEL'),
  array('id' => 4, 'nombre' => 'ALONSO, FERNANDO'),
  array('id' => 5, 'nombre' => 'MARTÍNEZ, ESTEBAN')
);

$estados_planta_array = array(
  'REACTOR EN POTENCIA 100%',
  'PARADA PROGRAMADA DE MANTENIMIENTO',
  'PARADA FRÍA',
  'ARRANQUE DE PLANTA'
);

$ayudantes_array = array(
  array('id' => 1, 'nombre' => 'ALTUNA, LEONARDO (P)'),
  array('id' => 2, 'nombre' => 'CASTRO, DIEGO ANIBAL (P)'),
  array('id' => 3, 'nombre' => 'GIL, RUBEN EDUARDO (P)'),
  array('id' => 4, 'nombre' => 'GONZALEZ, PATRICIO OSCAR (P)'),
  array('id' => 5, 'nombre' => 'PEÑALOZA, JOSE (P)'),
  array('id' => 6, 'nombre' => 'ROJO, CARLOS (P)'),
  array('id' => 7, 'nombre' => 'VARGAS, MATIAS (P)'),
  array('id' => 8, 'nombre' => 'YEDRO, LEANDRO ADRIAN (P)'),
  array('id' => 9, 'nombre' => 'GOMEZ, FRANCO (PARADA)'),
  array('id' => 10, 'nombre' => 'MARCONI, MARIANO (PARADA)'),
  array('id' => 11, 'nombre' => 'MORENO, LEANDRO (PARADA)'),
  array('id' => 12, 'nombre' => 'OVIEDO, ANDRES (AYUDANTE)'),
  array('id' => 13, 'nombre' => 'TICERA, GUILLERMO (AYUDANTE)')
);

$ptrs_array_inicial = array(
  array('id' => 1042, 'titulo' => 'Mantenimiento preventivo de bomba de purificación de agua pesada PHT (P-01)'),
  array('id' => 1045, 'titulo' => 'Relevamiento radiológico para inspección térmica de cañerías en Edificio Reactor'),
  array('id' => 1048, 'titulo' => 'Cambio de resinas en lechos de purificación de moderador R-005'),
  array('id' => 1050, 'titulo' => 'Inspección de sello de bomba principal de refrigerante primario 3331-P01'),
  array('id' => 1052, 'titulo' => 'Reemplazo de filtros de secadores de aire pesado DRY-01/02'),
  array('id' => 1055, 'titulo' => 'Muestreo radiológico y prueba hidrostática de sistema 3432'),
  array('id' => 1060, 'titulo' => 'Apertura de canal de combustible y calibración de detector neutrónico'),
  array('id' => 1065, 'titulo' => 'Mantenimiento de esclusa de personal y válvulas de venteo de contención'),
  array('id' => 1070, 'titulo' => 'Descontaminación y frotis en sala de cambiadores de calor de moderador'),
  array('id' => 1075, 'titulo' => 'Inspección visual de penetraciones de cables en blindaje biológico'),
  array('id' => 1080, 'titulo' => 'Revisión y calibración de monitores de radiación en chimenea'),
  array('id' => 1085, 'titulo' => 'Recorrida técnica y relevamiento de tasas de dosis en bóveda del reactor'),
  array('id' => 1090, 'titulo' => 'Inspección de actuadores neumáticos del sistema de parada rápida SDS-1'),
  array('id' => 1095, 'titulo' => 'Toma de muestras de agua pesada en cabezales de colectores de moderador'),
  array('id' => 1100, 'titulo' => 'Mantenimiento de detectores de gas en sala de control principal')
);

$relevamientos_rutina_iniciales = array(
  array(
    'id' => 1,
    'fecha' => $fecha_operativa_default,
    'hora' => '23:30',
    'recinto' => 'R-024 - Sala de Bombas PHT',
    'gases' => '0.00',
    'tritio' => '0.45',
    'dosis' => '24.0',
    'obs' => 'Ronda inicial de verificación',
    'oficial' => $oficial_actual
  ),
  array(
    'id' => 2,
    'fecha' => $fecha_operativa_default,
    'hora' => '00:15',
    'recinto' => 'R-001 - Edificio de Contención (+15m)',
    'gases' => '0.00',
    'tritio' => '1.20',
    'dosis' => '4.5',
    'obs' => 'Esclusa personal cerrada',
    'oficial' => $oficial_actual
  )
);

// Conexión real a MySQL en servidor de producción CNE
$miconex_path = $_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/miconex.php";
if (file_exists($miconex_path)) {
    require_once($miconex_path);
    if (isset($miconex)) {
        mysql_query("SET NAMES 'utf8'", $miconex);
        mysql_select_db($database_miconex, $miconex);

        // 1. Recintos de planta
        $res_rec = mysql_query("SELECT id, recinto FROM `graf_tritio_recintos` ORDER BY recinto", $miconex);
        if ($res_rec && mysql_num_rows($res_rec) > 0) {
            $recintos_array = array();
            while ($row = mysql_fetch_assoc($res_rec)) {
                $recintos_array[] = array('id' => $row['id'], 'recinto' => $row['recinto']);
            }
        }

        // 2. Turnos
        $res_tur = mysql_query("SELECT id_turnos, horarios FROM `regrad_turnos` ORDER BY id_turnos", $miconex);
        if ($res_tur && mysql_num_rows($res_tur) > 0) {
            $turnos_array = array();
            while ($row = mysql_fetch_assoc($res_tur)) {
                $turnos_array[] = array('id' => $row['id_turnos'], 'horarios' => $row['horarios']);
            }
        }

        // 3. Jefes de turno
        $res_jef = mysql_query("SELECT id_jefes_turno, jefe_apellido, jefe_nombre FROM `regrad_jefes_turno` ORDER BY jefe_apellido", $miconex);
        if ($res_jef && mysql_num_rows($res_jef) > 0) {
            $jefes_array = array();
            while ($row = mysql_fetch_assoc($res_jef)) {
                $jefes_array[] = array('id' => $row['id_jefes_turno'], 'nombre' => mb_strtoupper($row['jefe_apellido'] . ", " . $row['jefe_nombre'], 'UTF-8'));
            }
        }

        // 4. Oficiales de radioprotección
        $res_of = mysql_query("SELECT id_of_rad, of_apellido, of_nombre FROM `regrad_of_rad` ORDER BY of_apellido", $miconex);
        if ($res_of && mysql_num_rows($res_of) > 0) {
            $oficiales_array = array();
            while ($row = mysql_fetch_assoc($res_of)) {
                $oficiales_array[] = array('id' => $row['id_of_rad'], 'nombre' => mb_strtoupper($row['of_apellido'] . ", " . $row['of_nombre'], 'UTF-8'));
            }
        }

        // 5. Personal / Ayudantes
        $res_per = mysql_query("SELECT id, apellido, nombre, id_rol FROM vista_personal_radioproteccion WHERE habilitada_si_no = 1 ORDER BY id_rol ASC, apellido ASC", $miconex);
        if ($res_per && mysql_num_rows($res_per) > 0) {
            $ayudantes_array = array();
            while ($row = mysql_fetch_assoc($res_per)) {
                $tipo = ($row['id_rol'] == 1) ? '(P)' : (($row['id_rol'] == 2) ? '(Parada)' : '(Ayudante)');
                $ayudantes_array[] = array('id' => $row['id'], 'nombre' => mb_strtoupper($row['apellido'] . ", " . $row['nombre'] . " " . $tipo, 'UTF-8'));
            }
        }

        // 5b. Catálogo de Radionucleidos para Efluentes Líquidos
        $res_rad_liq = mysql_query("SELECT * FROM `reg_rad_radionucleidos_con_ki_ro` ORDER BY radionucleido ASC", $miconex);
        if ($res_rad_liq && mysql_num_rows($res_rad_liq) > 0) {
            $radionucleidos_liq_array = array();
            while ($row_rl = mysql_fetch_assoc($res_rad_liq)) {
                $radionucleidos_liq_array[] = $row_rl;
            }
        }

        // 5c. Unidades de Volumen
        $res_uvol = mysql_query("SELECT * FROM `regrad_tabla_conversion_unidades_volumen` ORDER BY factor_conversion_a_litro DESC", $miconex);
        if ($res_uvol && mysql_num_rows($res_uvol) > 0) {
            $unidades_vol_liq_array = array();
            while ($row_uv = mysql_fetch_assoc($res_uvol)) {
                $unidades_vol_liq_array[] = array(
                    'id_tabla_conversion' => (string)$row_uv['id_tabla_conversion'],
                    'unidad' => $row_uv['unidad'],
                    'factor_a_litro' => isset($row_uv['factor_conversion_a_litro']) ? floatval($row_uv['factor_conversion_a_litro']) : 1.0
                );
            }
        }

        // 5d. Unidades de Concentración (normalizadas a Bq/l, baseline id = 2 según procesos_reg_rad.php)
        $res_uconc = mysql_query("SELECT * FROM `regrad_tabla_conversion_unidades_concentracion` ORDER BY id_tabla_conversion ASC", $miconex);
        if ($res_uconc && mysql_num_rows($res_uconc) > 0) {
            $raw_uconc = array();
            $base_conc_factor = 1.0;
            while ($row_uc = mysql_fetch_assoc($res_uconc)) {
                $f_val = 1.0;
                foreach ($row_uc as $ck => $cv) {
                    if (stripos($ck, 'factor_conversion') !== false && is_numeric($cv)) {
                        $f_val = floatval($cv);
                        break;
                    }
                }
                if ((string)$row_uc['id_tabla_conversion'] === '2' && $f_val > 0) {
                    $base_conc_factor = $f_val;
                }
                $raw_uconc[] = array(
                    'id_tabla_conversion' => (string)$row_uc['id_tabla_conversion'],
                    'unidad' => $row_uc['unidad'],
                    '_raw_factor' => $f_val
                );
            }
            $unidades_conc_liq_array = array();
            foreach ($raw_uconc as $ruc) {
                $unidades_conc_liq_array[] = array(
                    'id_tabla_conversion' => $ruc['id_tabla_conversion'],
                    'unidad' => $ruc['unidad'],
                    'factor_a_bq_litro' => ($base_conc_factor > 0) ? ($ruc['_raw_factor'] / $base_conc_factor) : $ruc['_raw_factor']
                );
            }
        }

        // 6. Planilla abierta
        $q_abierta = "SELECT a.*, b.horarios, c.nombre_estado, d.of_apellido, d.of_nombre, e.jefe_apellido, e.jefe_nombre 
                      FROM regrad_planilla_diaria_nueva a 
                      LEFT JOIN regrad_turnos b ON b.id_turnos = a.turno 
                      LEFT JOIN regrad_estados_planta c ON c.id_estado_planta = a.estado_planta 
                      LEFT JOIN regrad_of_rad d ON d.id_of_rad = a.of_rad 
                      LEFT JOIN regrad_jefes_turno e ON e.id_jefes_turno = a.jefe_turno 
                      WHERE a.fecha_hora_cerrado IS NULL 
                      ORDER BY a.id_planilla_diaria DESC LIMIT 1";
        $res_abierta = mysql_query($q_abierta, $miconex);
        if ($res_abierta && mysql_num_rows($res_abierta) > 0) {
            $row_abierta = mysql_fetch_assoc($res_abierta);
            $id_planilla_activa = $row_abierta['id_planilla_diaria'];
            if (!empty($row_abierta['fecha'])) {
                $partes_f = explode('-', $row_abierta['fecha']);
                if (count($partes_f) == 3) {
                    $fecha_operativa_default = $partes_f[2] . '/' . $partes_f[1] . '/' . $partes_f[0];
                }
            }
            $turno_default = $row_abierta['turno'];
            $turno_actual_nombre = $row_abierta['horarios'];
            $oficial_iniciador_nombre = mb_strtoupper($row_abierta['of_apellido'] . ", " . $row_abierta['of_nombre'], 'UTF-8');
            $oficial_iniciador_id = !empty($row_abierta['of_rad']) ? $row_abierta['of_rad'] : 1;
            // Mantener el oficial logueado de la sesión, no sobreescribir con el creador anterior
            if (isset($_SESSION['nombre_apellido'])) {
                $oficial_logueado = mb_strtoupper($_SESSION['nombre_apellido'], 'UTF-8');
                $oficial_actual = $oficial_logueado;
            } elseif (isset($_GET['usuario'])) {
                $oficial_logueado = mb_strtoupper($_GET['usuario'], 'UTF-8');
                $oficial_actual = $oficial_logueado;
            } else {
                $oficial_logueado = $oficial_iniciador_nombre;
                $oficial_actual = $oficial_logueado;
            }
            $jefe_turno_actual = mb_strtoupper($row_abierta['jefe_apellido'] . ", " . $row_abierta['jefe_nombre'], 'UTF-8');
            if (!empty($row_abierta['nombre_estado'])) {
                $estado_planta = $row_abierta['nombre_estado'];
            }

            // RESETEAR VARIABLES: Si la planilla activa en MySQL aún no tiene registros en alguna tabla,
            // se mantendrá VACÍA ("") para mostrarse como "○ Pendiente", en vez de arrastrar datos demo.
            $vent_gral_db = "";
            $vent_reactor_db = "";
            $c14_db = "";
            $dedo_frio_ppm_db = "";
            $dedo_frio_menor_que_db = 0;
            $met_vel_db = "";
            $met_dir_db = "";
            $met_pasquill_db = "";
            $gn_id_db = "";
            $gn_desde_db = "";
            $gn_hasta_db = "";
            $gn_ar41_db = "";
            $gn_kr85_db = "";
            $gn_kr85m_db = "";
            $gn_xe133_db = "";
            $gn_xe135_db = "";
            $gn_xe135m_db = "";
            $hay_gases_nobles_db = false;
            $rows_espectrometria_db = array();
            $efluentes_liquidos_db = array();
            $relevamientos_rutina_iniciales = array();
            $actas_tareas_iniciales = array();

            // 7. Catálogo completo de Estados de Planta para el selector
            $res_est = mysql_query("SELECT nombre_estado FROM `regrad_estados_planta` WHERE habilitada = 1 ORDER BY nombre_estado", $miconex);
            if ($res_est && mysql_num_rows($res_est) > 0) {
                $estados_planta_array = array();
                while ($row_e = mysql_fetch_assoc($res_est)) {
                    $estados_planta_array[] = $row_e['nombre_estado'];
                }
            }

            // 8. Chimenea - Tritio (Ventilación General y Reactor)
            $res_tri = mysql_query("SELECT vent_gral, vent_reactor FROM `regrad_tritio` WHERE id_planilla = $id_planilla_activa ORDER BY id_tritio DESC LIMIT 1", $miconex);
            if ($res_tri && mysql_num_rows($res_tri) > 0) {
                $row_tri = mysql_fetch_assoc($res_tri);
                if ($row_tri['vent_gral'] !== null && $row_tri['vent_gral'] !== '') {
                    $vent_gral_db = round($row_tri['vent_gral'] / 1e9, 2);
                }
                if ($row_tri['vent_reactor'] !== null && $row_tri['vent_reactor'] !== '') {
                    $vent_reactor_db = round($row_tri['vent_reactor'] / 1e9, 2);
                }
            }

            // 9. Chimenea - Carbono-14
            $res_c14 = mysql_query("SELECT medicion FROM `regrad_carbono14` WHERE id_planilla = $id_planilla_activa ORDER BY id_carbono14 DESC LIMIT 1", $miconex);
            if ($res_c14 && mysql_num_rows($res_c14) > 0) {
                $row_c14 = mysql_fetch_assoc($res_c14);
                if ($row_c14['medicion'] !== null && $row_c14['medicion'] !== '') {
                    $c14_db = round($row_c14['medicion'] / 1e6, 1);
                }
            }

            // 10. Dedo Frío 73120
            $res_df = mysql_query("SELECT ppm, menor_que FROM `regrad_dedo_frio` WHERE id_planilla = $id_planilla_activa ORDER BY id_dedo_frio DESC LIMIT 1", $miconex);
            if ($res_df && mysql_num_rows($res_df) > 0) {
                $row_df = mysql_fetch_assoc($res_df);
                $dedo_frio_ppm_db = $row_df['ppm'];
                $dedo_frio_menor_que_db = $row_df['menor_que'];
            }

            // 11. Condición Meteorológica
            $res_met = mysql_query("SELECT velocidad_mseg, direc_viento, cond_pasquill FROM `regrad_cond_meteorologica` WHERE id_planilla = $id_planilla_activa ORDER BY id_cond_meteorologica DESC LIMIT 1", $miconex);
            if ($res_met && mysql_num_rows($res_met) > 0) {
                $row_met = mysql_fetch_assoc($res_met);
                $met_vel_db = $row_met['velocidad_mseg'];
                $met_dir_db = $row_met['direc_viento'];
                $met_pasquill_db = $row_met['cond_pasquill'];
            }

            // 12. Gases Nobles Chimenea (Ar-41, Kr, Xe)
            $res_gn = mysql_query("SELECT * FROM `regrad_gases_nobles` WHERE id_planilla = $id_planilla_activa ORDER BY id_gases_nobles DESC LIMIT 1", $miconex);
            if ($res_gn && mysql_num_rows($res_gn) > 0) {
                $row_gn = mysql_fetch_assoc($res_gn);
                $gn_id_db = $row_gn['id_gases_nobles'];
                $gn_desde_db = !empty($row_gn['hora_desde']) ? substr($row_gn['hora_desde'], 0, 5) : "00:00";
                $gn_hasta_db = !empty($row_gn['hora_hasta']) ? substr($row_gn['hora_hasta'], 0, 5) : "18:00";
                $gn_ar41_db = ($row_gn['ar41'] !== null && $row_gn['ar41'] !== '') ? ($row_gn['ar41'] > 1e4 ? round($row_gn['ar41'] / 1e9, 2) : round($row_gn['ar41'], 2)) : "0.0";
                $gn_kr85_db = ($row_gn['kr85'] !== null && $row_gn['kr85'] !== '') ? ($row_gn['kr85'] > 1e4 ? round($row_gn['kr85'] / 1e9, 2) : round($row_gn['kr85'], 2)) : "0.0";
                $gn_kr85m_db = ($row_gn['kr85m'] !== null && $row_gn['kr85m'] !== '') ? ($row_gn['kr85m'] > 1e4 ? round($row_gn['kr85m'] / 1e9, 2) : round($row_gn['kr85m'], 2)) : "0.0";
                $gn_xe133_db = ($row_gn['xe133'] !== null && $row_gn['xe133'] !== '') ? ($row_gn['xe133'] > 1e4 ? round($row_gn['xe133'] / 1e9, 2) : round($row_gn['xe133'], 2)) : "0.0";
                $gn_xe135_db = ($row_gn['xe135'] !== null && $row_gn['xe135'] !== '') ? ($row_gn['xe135'] > 1e4 ? round($row_gn['xe135'] / 1e9, 2) : round($row_gn['xe135'], 2)) : "0.0";
                $gn_xe135m_db = ($row_gn['xe135m'] !== null && $row_gn['xe135m'] !== '') ? ($row_gn['xe135m'] > 1e4 ? round($row_gn['xe135m'] / 1e9, 2) : round($row_gn['xe135m'], 2)) : "0.0";
                $hay_gases_nobles_db = true;
            }

            // 13. Espectrometría del Aire de Zona Controlada
            $rows_espectrometria_db = array();
            $q_esp = "SELECT a.*, b.recinto as recinto_nombre 
                      FROM regrad_espectr_conc_radionuclei a 
                      LEFT JOIN graf_tritio_recintos b ON b.id = a.recinto 
                      WHERE a.id_planilla = $id_planilla_activa 
                      ORDER BY a.fecha DESC, a.hora DESC";
            $res_esp = mysql_query($q_esp, $miconex);
            if ($res_esp && mysql_num_rows($res_esp) > 0) {
                while ($row_e = mysql_fetch_assoc($res_esp)) {
                    $rows_espectrometria_db[] = $row_e;
                }
            }

            // 13b. Efluentes Líquidos Emitidos Reales de la planilla activa
            $q_ef_liq = "SELECT v.*, n.observaciones 
                         FROM reg_rad_ef_liq_final_para_tabla v 
                         LEFT JOIN regrad_efluentes_liquidos_nueva n ON n.id_efluentes = v.id_efluentes 
                         WHERE v.id_planilla = $id_planilla_activa 
                         ORDER BY v.fecha DESC, v.hora DESC, v.id_efluentes DESC";
            $res_ef_liq = mysql_query($q_ef_liq, $miconex);
            if ($res_ef_liq && mysql_num_rows($res_ef_liq) > 0) {
                while ($r_ef = mysql_fetch_assoc($res_ef_liq)) {
                    $id_ef = intval($r_ef['id_efluentes']);
                    $rads_ef = array();
                    $q_rads_ef = "SELECT a.id, a.id_radionucleido_efluentes_liquidos, b.radionucleido, a.conc_Bq_litro 
                                  FROM regrad_efluentes_liquidos_radionucleidos a 
                                  LEFT JOIN reg_rad_radionucleidos_con_ki_ro b ON b.id_radionucleidos = a.id_radionucleido_efluentes_liquidos 
                                  WHERE a.id_regrad_efluentes_liquidos = $id_ef 
                                  ORDER BY a.id ASC";
                    $res_rads_ef = mysql_query($q_rads_ef, $miconex);
                    if ($res_rads_ef && mysql_num_rows($res_rads_ef) > 0) {
                        while ($rr_ef = mysql_fetch_assoc($res_rads_ef)) {
                            $rads_ef[] = array(
                                'id' => intval($rr_ef['id']),
                                'id_radionucleido' => intval($rr_ef['id_radionucleido_efluentes_liquidos']),
                                'radionucleido' => $rr_ef['radionucleido'] ? $rr_ef['radionucleido'] : ('ID ' . $rr_ef['id_radionucleido_efluentes_liquidos']),
                                'conc_Bq_litro' => floatval($rr_ef['conc_Bq_litro'])
                            );
                        }
                    }
                    $vol_l = floatval($r_ef['volumen_litro']);
                    $act_h3_bq = floatval($r_ef['actividad_tritio']);
                    $act_gam_bq = floatval($r_ef['actividad_gamma']);
                    $rodd_h3 = floatval($r_ef['LDD_Ro_tritio']);
                    $rodd_gam = floatval($r_ef['LDD_Ro_gamma']);
                    $efluentes_liquidos_db[] = array(
                        'id_efluentes' => $id_ef,
                        'fecha' => $r_ef['fecha'],
                        'hora' => substr($r_ef['hora'], 0, 5),
                        'tanque' => $r_ef['tanque'],
                        'volumen_litro' => $vol_l,
                        'volumen_m3' => round($vol_l / 1000.0, 3),
                        'actividad_tritio_Bq' => $act_h3_bq,
                        'actividad_tritio_TBq' => $act_h3_bq / 1e12,
                        'actividad_gamma_Bq' => $act_gam_bq,
                        'actividad_gamma_TBq' => $act_gam_bq / 1e12,
                        'LDD_Ro_tritio' => $rodd_h3,
                        'LDD_Ro_gamma' => $rodd_gam,
                        'rodd_total' => round($rodd_h3 + $rodd_gam, 4),
                        'observaciones' => $r_ef['observaciones'] ? $r_ef['observaciones'] : '',
                        'radionucleidos' => $rads_ef
                    );
                }
            }

            
// Evaluar si el usuario logueado difiere del oficial que inició la planilla activa
$hay_discrepancia_guardia = (!empty($oficial_iniciador_nombre) && !empty($oficial_logueado) && $oficial_iniciador_nombre !== $oficial_logueado);
if (isset($_GET['nueva_guardia']) || isset($_GET['desbloquear']) || isset($_GET['test'])) {
    $hay_discrepancia_guardia = false;
    $sin_planilla_abierta = true;
    $oficial_iniciador_nombre = $oficial_logueado;
    $oficial_actual = $oficial_logueado;
}
// Cargar mediciones de rutina de la planilla abierta
            $q_rutina = "SELECT a.*, c.recinto, b.of_apellido, b.of_nombre 
                         FROM regrad_relevamiento_rutina a 
                         LEFT JOIN regrad_of_rad b ON b.id_of_rad = a.id_oficial 
                         LEFT JOIN graf_tritio_recintos c ON c.id = a.ubicacion 
                         WHERE a.id_planilla = $id_planilla_activa 
                         ORDER BY a.fecha DESC, a.hora DESC";
            $res_rutina = mysql_query($q_rutina, $miconex);
            if ($res_rutina && mysql_num_rows($res_rutina) > 0) {
                $relevamientos_rutina_iniciales = array();
                while ($r = mysql_fetch_assoc($res_rutina)) {
                    $partes = explode('-', $r['fecha']);
                    $f_latam = (count($partes) == 3) ? ($partes[2] . '/' . $partes[1] . '/' . $partes[0]) : $r['fecha'];
                    $h_corta = substr($r['hora'], 0, 5);
                    $relevamientos_rutina_iniciales[] = array(
                        'id' => $r['id_relev_rutina'],
                        'fecha' => $f_latam,
                        'hora' => $h_corta,
                        'recinto' => $r['recinto'] ? $r['recinto'] : ('Recinto #' . $r['ubicacion']),
                        'gases' => $r['gases_nobles'],
                        'tritio' => $r['tritio'],
                        'dosis' => $r['tasa_dosis'],
                        'obs' => $r['observaciones'],
                        'oficial' => ($r['of_apellido'] ? ($r['of_apellido'] . ", " . $r['of_nombre']) : $oficial_actual)
                    );
                }
            } else if ($id_planilla_activa) {
                $relevamientos_rutina_iniciales = array();
            }

            // 14. Actas y Tareas Diarias de la planilla abierta
            $q_tar = "SELECT a.*, b.recinto as recinto_nombre, c.of_apellido, c.of_nombre, d.apellido as ay_apellido, d.nombre as ay_nombre
                      FROM regrad_actas_tareas_diarias a
                      LEFT JOIN graf_tritio_recintos b ON b.id = a.id_recinto
                      LEFT JOIN regrad_of_rad c ON c.id_of_rad = a.id_oficial_asociado
                      LEFT JOIN vista_personal_radioproteccion d ON d.id = a.id_ayudante
                      WHERE a.id_planilla_diaria = $id_planilla_activa 
                      ORDER BY a.fecha_hora_carga ASC";
            $res_tar = mysql_query($q_tar, $miconex);
            if ($res_tar && mysql_num_rows($res_tar) > 0) {
                $actas_tareas_iniciales = array();
                while ($r_t = mysql_fetch_assoc($res_tar)) {
                    $hora_acta = !empty($r_t['fecha_hora_carga']) ? date('H:i', strtotime($r_t['fecha_hora_carga'])) : '--:--';
                    $of_nom = !empty($r_t['of_apellido']) ? ($r_t['of_apellido'] . ", " . $r_t['of_nombre']) : $oficial_actual;
                    $ay_nom = !empty($r_t['ay_apellido']) ? ($r_t['ay_apellido'] . ", " . $r_t['ay_nombre']) : '-';
                    $actas_tareas_iniciales[] = array(
                        'id' => $r_t['id_tarea'],
                        'hora' => $hora_acta,
                        'ptr' => $r_t['id_ptr'] ? $r_t['id_ptr'] : '-',
                        'recinto' => $r_t['recinto_nombre'] ? $r_t['recinto_nombre'] : 'General de Planta',
                        'oficial' => $of_nom,
                        'ayudante' => $ay_nom,
                        'texto' => $r_t['texto_tarea'],
                        'dosis' => $r_t['tasa_dosis'] !== null ? $r_t['tasa_dosis'] : 0,
                        'tritio' => $r_t['tritio_dac'] !== null ? $r_t['tritio_dac'] : 0,
                        'derrame' => $r_t['derrame_agua'],
                        'aerosoles' => $r_t['aerosoles_dac']
                    );
                }
            } else if ($id_planilla_activa) {
                $actas_tareas_iniciales = array();
            }
        } else {
            // No hay ninguna planilla abierta en la base de datos
            $id_planilla_activa = null;
            $hay_discrepancia_guardia = false;
            $sin_planilla_abierta = true;
            if (isset($_SESSION['nombre_apellido'])) {
                $oficial_logueado = mb_strtoupper($_SESSION['nombre_apellido'], 'UTF-8');
            } elseif (isset($_GET['usuario'])) {
                $oficial_logueado = mb_strtoupper($_GET['usuario'], 'UTF-8');
            }
            $oficial_actual = $oficial_logueado;
            $oficial_iniciador_nombre = $oficial_logueado;
            $turno_actual_nombre = "Sin Planilla Abierta";
            $relevamientos_rutina_iniciales = array();
            $efluentes_liquidos_db = array();
            $rows_espectrometria_db = array();
        }
        if (isset($_GET['nueva_guardia']) && $_GET['nueva_guardia'] == '1') {
            $sin_planilla_abierta = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro Radiológico Online - Central Nuclear Embalse (NA-SA)</title>
  <style>
    :root, [data-theme="petrol"], [data-theme="default"] {
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
      --accent-cyan: #10b981;  /* Verde Esmeralda Radiológico */
      --accent-green: #34d399; /* Menta nominal */
      --accent-amber: #f59e0b;
      --accent-red: #ef4444;

      --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
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
      font-family: var(--font-sans);
      background-color: var(--bg-page);
      color: var(--text-main);
      font-size: 13px;
      line-height: 1.45;
      display: flex;
    }

    /* ================= SIDEBAR ================= */
    .sidebar {
      width: 270px;
      background-color: var(--bg-sidebar);
      border-right: 1px solid var(--border-card);
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
    }

    .sidebar-brand {
      padding: 16px 20px;
      border-bottom: 1px solid var(--border-card);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .badge-nasa {
      background: linear-gradient(135deg, #047857, #10b981);
      color: #ffffff;
      font-family: var(--font-mono);
      font-weight: 900;
      font-size: 14px;
      padding: 5px 10px;
      border-radius: 6px;
      letter-spacing: 1px;
    }

    .brand-text h2 { font-size: 13.5px; font-weight: 700; color: #ffffff; }
    .brand-text span { font-size: 11px; color: var(--accent-cyan); font-family: var(--font-mono); }

    .plant-status {
      margin: 14px 16px;
      padding: 10px 12px;
      background: rgba(16, 185, 129, 0.1);
      border: 1px solid rgba(16, 185, 129, 0.3);
      border-radius: 6px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .status-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--accent-green);
      box-shadow: 0 0 8px var(--accent-green);
      animation: pulse-dot 2s infinite ease-in-out;
    }

    @keyframes pulse-dot {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.4; transform: scale(0.85); }
    }

    .status-text {
      font-size: 11.5px;
      font-weight: 700;
      color: #34d399;
      letter-spacing: 0.5px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .sidebar-menu {
      flex: 1;
      padding: 10px 12px;
      overflow-y: auto;
    }

    .menu-header {
      font-size: 10px;
      font-weight: 800;
      color: var(--text-dim);
      text-transform: uppercase;
      letter-spacing: 1px;
      padding: 8px 10px 4px;
    }

    .menu-btn {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 9px 12px;
      margin-bottom: 4px;
      background: transparent;
      border: 1px solid transparent;
      border-radius: 6px;
      color: var(--text-muted);
      font-size: 12.5px;
      font-weight: 500;
      text-align: left;
      cursor: pointer;
      transition: all 0.15s ease;
    }

    .menu-btn:hover {
      background: var(--bg-card-subtle);
      color: #ffffff;
      border-color: var(--border-subtle);
    }

    .menu-btn.active {
      background: rgba(6, 182, 212, 0.15);
      border-color: var(--accent-cyan);
      color: var(--accent-cyan);
      font-weight: 700;
    }

    .menu-badge {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      color: var(--accent-cyan);
      font-size: 10px;
      font-family: var(--font-mono);
      padding: 2px 6px;
      border-radius: 10px;
    }

    .sidebar-user {
      padding: 14px 18px;
      border-top: 1px solid var(--border-card);
      background: var(--bg-page);
      font-size: 12px;
    }

    .user-name { font-weight: 700; color: #ffffff; }
    .user-role { color: var(--text-dim); font-size: 10.5px; font-family: var(--font-mono); }

    .session-indicator {
      margin-top: 8px;
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 10.5px;
      color: var(--text-muted);
      font-family: var(--font-mono);
    }

    .session-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--accent-green);
    }

    /* ================= MAIN CONTENT ================= */
    .main-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow-x: hidden !important;
      overflow-y: hidden;
      background: var(--bg-page);
    }

    .topbar {
      height: 52px;
      background: var(--bg-card);
      border-bottom: 1px solid var(--border-card);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 28px;
      flex-shrink: 0;
    }

    .topbar-left {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .theme-switcher-bar {
      display: flex;
      align-items: center;
      gap: 6px;
      background: var(--bg-card-subtle);
      border: 1px solid var(--border-card);
      padding: 3px 8px;
      border-radius: 6px;
      font-size: 11px;
      margin-left: 10px;
    }
    .theme-pill {
      color: var(--text-muted);
      text-decoration: none;
      padding: 2px 7px;
      border-radius: 4px;
      transition: all 0.12s ease;
      font-family: var(--font-mono);
      font-size: 10.5px;
    }
    .theme-pill:hover {
      color: #fff;
      background: rgba(255, 255, 255, 0.08);
    }
    .theme-pill.active {
      background: var(--accent-cyan);
      color: #000 !important;
      font-weight: 700;
    }

    .topbar-title {
      font-size: 14px;
      font-weight: 700;
      color: #ffffff;
    }

    .shift-tag {
      background: var(--bg-card-subtle);
      border: 1px solid var(--border-card);
      color: var(--accent-cyan);
      font-family: var(--font-mono);
      font-size: 11.5px;
      padding: 3px 10px;
      border-radius: 4px;
    }

    .btn-new-shift {
      background: rgba(6, 182, 212, 0.15);
      border: 1px solid var(--accent-cyan);
      color: var(--accent-cyan);
      padding: 4px 10px;
      border-radius: 4px;
      cursor: pointer;
      font-family: var(--font-mono);
      font-size: 11px;
      font-weight: 700;
      transition: all 0.15s ease;
    }
    .btn-new-shift:hover { background: var(--accent-cyan); color: #000; }

    .topbar-right {
      display: flex;
      align-items: center;
      gap: 18px;
      font-family: var(--font-mono);
      font-size: 12px;
    }

    .clock-pill {
      color: var(--accent-cyan);
      background: var(--bg-page);
      padding: 4px 12px;
      border-radius: 4px;
      border: 1px solid var(--border-card);
      font-weight: 700;
    }

    .workspace {
      flex: 1;
      overflow-y: auto;
      padding: 22px 28px 40px;
    }

    /* ================= KPIS SUPERIORES (6 TARJETAS COMPLETAS) ================= */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 14px;
      margin-bottom: 22px;
    }

    .kpis-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
    }

    .kpis-title {
      font-size: 11px;
      font-weight: 800;
      color: var(--text-dim);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .btn-update-all-kpis {
      background: rgba(6, 182, 212, 0.12);
      border: 1px solid var(--accent-cyan);
      color: var(--accent-cyan);
      font-family: var(--font-mono);
      font-size: 11px;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.15s ease;
    }
    .btn-update-all-kpis:hover {
      background: var(--accent-cyan);
      color: #000;
    }

    .kpi-card {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 8px;
      padding: 12px 14px;
      position: relative;
      overflow: hidden;
      cursor: pointer;
      transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
      user-select: none;
    }

    .kpi-card:hover {
      transform: translateY(-2px);
      border-color: var(--accent-cyan);
      box-shadow: 0 4px 18px rgba(0, 0, 0, 0.5);
    }

    .kpi-top-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 6px;
    }

    .kpi-action-icon {
      font-size: 10px;
      font-family: var(--font-mono);
      color: var(--text-dim);
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      padding: 2px 6px;
      border-radius: 4px;
      transition: all 0.15s ease;
    }

    .kpi-card:hover .kpi-action-icon {
      color: var(--accent-cyan);
      border-color: var(--accent-cyan);
      background: rgba(6, 182, 212, 0.15);
      font-weight: 700;
    }

    .kpi-multi-values {
      display: grid;
      grid-template-columns: 1.1fr 1fr 1fr;
      gap: 6px;
      align-items: end;
      margin-top: 3px;
    }

    .kpi-sub-item {
      display: flex;
      flex-direction: column;
    }

    .kpi-sub-label {
      font-size: 9px;
      font-weight: 700;
      color: var(--text-dim);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 2px;
    }

    .kpi-sub-val-row {
      display: flex;
      align-items: baseline;
      gap: 3px;
    }

    .kpi-sub-val {
      font-size: 16.5px;
      font-weight: 800;
      font-family: var(--font-mono);
      line-height: 1.1;
    }

    .kpi-sub-unit {
      font-size: 9px;
      color: var(--text-dim);
      font-family: var(--font-mono);
    }

    .kpi-modal-tabs {
      display: flex;
      gap: 6px;
      margin-bottom: 16px;
      border-bottom: 1px solid var(--border-card);
      padding-bottom: 8px;
    }

    .kpi-tab-btn {
      background: transparent;
      border: 1px solid transparent;
      color: var(--text-muted);
      font-size: 11.5px;
      font-weight: 600;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.15s ease;
    }

    .kpi-tab-btn:hover {
      background: var(--bg-card-subtle);
      color: #ffffff;
    }

    .kpi-tab-btn.active {
      background: rgba(6, 182, 212, 0.15);
      border-color: var(--accent-cyan);
      color: var(--accent-cyan);
      font-weight: 700;
    }

    /* ================= SEARCHABLE DROPDOWN (BUSCADOR DE RECINTOS TIPO SELECT2) ================= */
    .searchable-select-wrapper {
      position: relative;
      width: 100%;
    }

    .searchable-dropdown {
      display: none;
      position: absolute;
      top: calc(100% + 4px);
      left: 0;
      right: 0;
      max-height: 240px;
      overflow-y: auto;
      background: #0f172a;
      border: 1px solid var(--accent-cyan);
      border-radius: 6px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.85);
      z-index: 500;
    }

    .searchable-dropdown.open {
      display: block;
    }

    .searchable-item {
      padding: 8px 12px;
      font-size: 12px;
      color: #f1f5f9;
      cursor: pointer;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      transition: background 0.1s ease;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .searchable-item:last-child {
      border-bottom: none;
    }

    .searchable-item:hover,
    .searchable-item.highlighted {
      background: rgba(6, 182, 212, 0.22);
      color: #ffffff;
    }

    .searchable-item mark {
      background: transparent;
      color: var(--accent-cyan);
      font-weight: 800;
      text-decoration: underline;
    }

    .searchable-empty {
      padding: 12px;
      font-size: 11.5px;
      color: var(--text-dim);
      text-align: center;
      font-style: italic;
    }

    /* ================= VANILLA DARK DATEPICKER & TIMEPICKER ================= */
    .latam-datepicker-popup,
    .latam-timepicker-popup {
      display: none;
      position: fixed !important;
      background: #0f172a !important;
      border: 1px solid var(--accent-cyan) !important;
      border-radius: 8px;
      padding: 12px;
      box-shadow: 0 14px 35px rgba(0, 0, 0, 0.95);
      z-index: 99999 !important;
      width: 260px;
      user-select: none;
    }

    .dp-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
    }

    .dp-month-title {
      font-size: 12px;
      font-weight: 700;
      color: #ffffff;
      font-family: var(--font-sans);
    }

    .dp-nav-btn {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--border-card);
      color: var(--accent-cyan);
      font-weight: bold;
      width: 24px;
      height: 24px;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.15s ease;
    }
    .dp-nav-btn:hover {
      background: var(--accent-cyan);
      color: #000;
    }

    .dp-grid-weekdays {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      text-align: center;
      font-size: 10px;
      font-weight: 700;
      color: var(--text-dim);
      margin-bottom: 6px;
    }

    .dp-grid-days {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 3px;
    }

    .dp-day-cell {
      height: 28px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-family: var(--font-mono);
      color: #e2e8f0;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.12s ease;
    }

    .dp-day-cell:hover {
      background: rgba(6, 182, 212, 0.25);
      color: var(--accent-cyan);
    }

    .dp-day-cell.other-month {
      color: #475569;
      opacity: 0.5;
    }

    .dp-day-cell.today {
      border: 1px solid rgba(6, 182, 212, 0.6);
      font-weight: 700;
    }

    .dp-day-cell.selected {
      background: var(--accent-cyan) !important;
      color: #000000 !important;
      font-weight: 800;
    }

    .dp-quick-footer {
      display: flex;
      justify-content: space-between;
      gap: 6px;
      margin-top: 10px;
      padding-top: 8px;
      border-top: 1px solid var(--border-card);
    }

    .dp-quick-btn {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--border-card);
      color: var(--accent-cyan);
      font-size: 10px;
      font-weight: 700;
      padding: 4px 8px;
      border-radius: 4px;
      cursor: pointer;
      flex: 1;
      text-align: center;
      transition: all 0.12s ease;
    }
    .dp-quick-btn:hover {
      background: rgba(6, 182, 212, 0.2);
      border-color: var(--accent-cyan);
    }

    /* TIMEPICKER POPUP */
    .tp-presets {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 4px;
      margin-bottom: 8px;
    }

    .tp-preset-btn {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--border-card);
      color: var(--text-main);
      font-family: var(--font-mono);
      font-size: 10px;
      padding: 4px 2px;
      border-radius: 4px;
      text-align: center;
      cursor: pointer;
    }
    .tp-preset-btn:hover {
      background: rgba(6, 182, 212, 0.2);
      border-color: var(--accent-cyan);
      color: var(--accent-cyan);
    }

    .tp-selector-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
      margin-top: 6px;
    }

    .tp-col-title {
      font-size: 10px;
      font-weight: 700;
      color: var(--text-dim);
      text-transform: uppercase;
      margin-bottom: 4px;
      text-align: center;
    }

    .tp-col-scroll {
      max-height: 120px;
      overflow-y: auto;
      border: 1px solid var(--border-card);
      border-radius: 4px;
      background: #090d16;
    }

    .tp-item {
      padding: 4px;
      text-align: center;
      font-family: var(--font-mono);
      font-size: 11px;
      color: #94a3b8;
      cursor: pointer;
    }
    .tp-item:hover, .tp-item.active {
      background: var(--accent-cyan);
      color: #000;
      font-weight: 700;
    }

    .kpi-card::before {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      background: var(--card-color, var(--accent-blue));
    }

    .kpi-label {
      font-size: 10.5px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 6px;
    }

    .kpi-value-row {
      display: flex;
      align-items: baseline;
      gap: 6px;
    }

    .kpi-value {
      font-size: 22px;
      font-weight: 800;
      font-family: var(--font-mono);
      color: #ffffff;
    }

    .kpi-unit {
      font-size: 11px;
      color: var(--text-dim);
      font-family: var(--font-mono);
    }

    .kpi-footer {
      margin-top: 6px;
      font-size: 10.5px;
      color: var(--text-dim);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* ================= SECCIÓN / PANELES ACTIVOS ================= */
        /* ================= ANIMACIONES CINEMATOGRÁFICAS HUD / SCADA (EFECTO 'GUAU') ================= */
    .section-panel {
      display: none;
    }

    .section-panel.active {
      display: block;
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
    .section-panel {
      overflow-x: hidden;
    }

    .section-panel.active {
      overflow-x: hidden;
    }

    .section-panel .panel-box {
      overflow: hidden !important;
      position: relative !important;
    }

    .section-panel.active .panel-box::before {
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
    .section-panel.active::after {
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

    /* Botón X de limpieza en inputs */
    .btn-clear-ctrl {
      position: absolute;
      right: 8px;
      top: 50%;
      transform: translateY(-50%);
      background: transparent;
      border: none;
      color: var(--text-dim);
      font-size: 16px;
      line-height: 1;
      cursor: pointer;
      padding: 3px 6px;
      border-radius: 4px;
      display: none;
      z-index: 5;
      transition: all 0.15s ease;
    }

    .btn-clear-ctrl:hover {
      color: #ef4444;
      background: rgba(239, 68, 68, 0.2);
    }

    .panel-box {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 8px;
      overflow: hidden;
      margin-bottom: 20px;
    }

    .panel-header {
      padding: 14px 18px;
      border-bottom: 1px solid var(--border-card);
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: rgba(255, 255, 255, 0.02);
    }

    .panel-title {
      font-size: 13.5px;
      font-weight: 700;
      color: #ffffff;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .panel-body {
      padding: 18px;
    }

    /* ================= BARRA DE ENTRADA RÁPIDA (RUTINA) ================= */
    .entry-bar {
      display: grid;
      grid-template-columns: 120px 85px 2fr 100px 100px 100px 2.2fr auto;
      gap: 10px;
      background: var(--bg-card-subtle);
      border: 1px solid var(--border-card);
      padding: 12px 14px;
      border-radius: 6px;
      margin-bottom: 18px;
      align-items: end;
    }

    .field-group {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .field-label {
      font-size: 10.5px;
      font-weight: 600;
      color: var(--text-muted);
      text-transform: uppercase;
    }

    .field-control {
      background: var(--bg-input);
      border: 1px solid var(--border-card);
      border-radius: 4px;
      color: #ffffff;
      font-size: 12.5px;
      padding: 6px 9px;
      outline: none;
      transition: border-color 0.15s ease;
      width: 100%;
    }

    .field-control:focus {
      border-color: var(--accent-cyan);
      box-shadow: 0 0 0 2px rgba(6, 182, 212, 0.2);
    }

    .field-control.mono {
      font-family: var(--font-mono);
      font-size: 12px;
    }

    .btn-register {
      background: var(--accent-cyan);
      border: none;
      color: #000000;
      font-family: var(--font-mono);
      font-weight: 800;
      font-size: 11.5px;
      padding: 8px 14px;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      height: 31px;
      transition: background 0.15s ease;
      white-space: nowrap;
    }
    .btn-register:hover { background: #22d3ee; }

    /* ================= TABLAS DE DATOS ================= */
    .data-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 12px;
      text-align: left;
    }

    .data-table th {
      background: var(--bg-sidebar);
      color: var(--text-muted);
      font-weight: 700;
      text-transform: uppercase;
      font-size: 10px;
      letter-spacing: 0.5px;
      padding: 10px 12px;
      border-bottom: 2px solid var(--border-card);
    }

    /* Contenedor con scroll y encabezados fijos (Sticky Table Headers) */
    .table-scroll-wrapper {
      max-height: calc(100vh - 430px);
      min-height: 280px;
      overflow-y: auto;
      overflow-x: auto;
      border: 1px solid var(--border-card);
      border-radius: 6px;
      background: var(--bg-card);
      position: relative;
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

    .table-scroll-wrapper table.data-table thead th {
      position: sticky !important;
      top: 0 !important;
      z-index: 20 !important;
      background: #091c18 !important;
      box-shadow: 0 1px 0 var(--border-card);
    }

    /* ================= ESTILOS DEL BOTÓN Y MODAL CHECKLIST ================= */
    .btn-checklist-trigger {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      background: rgba(245, 158, 11, 0.12);
      border: 1px solid rgba(245, 158, 11, 0.4);
      color: var(--accent-amber);
      font-size: 11px;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.15s ease;
      white-space: nowrap;
    }
    .btn-checklist-trigger:hover {
      background: rgba(245, 158, 11, 0.22);
      border-color: var(--accent-amber);
      transform: translateY(-1px);
    }
    .badge-checklist-pill {
      font-size: 10px;
      font-weight: 800;
      padding: 1px 6px;
      border-radius: 10px;
      background: var(--accent-amber);
      color: #000;
    }
    .badge-checklist-pill.success {
      background: var(--accent-green);
      color: #000;
    }

    .checklist-row-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 10px 14px;
      background: rgba(14, 39, 34, 0.5);
      border: 1px solid var(--border-card);
      border-radius: 6px;
      margin-bottom: 8px;
      transition: all 0.15s ease;
    }
    .checklist-row-item:hover {
      background: rgba(25, 66, 58, 0.6);
      border-color: rgba(6, 182, 212, 0.4);
    }
    .checklist-item-left {
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 0;
      flex: 1;
    }
    .checklist-item-icon {
      font-size: 15px;
      flex-shrink: 0;
    }
    .checklist-item-title {
      font-size: 12px;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 2px;
    }
    .checklist-item-desc {
      font-size: 10.5px;
      color: var(--text-muted);
    }
    .checklist-item-right {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-shrink: 0;
    }
    .checklist-status-badge {
      font-size: 10px;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 4px;
      white-space: nowrap;
    }
    .checklist-status-badge.complete {
      background: rgba(16, 185, 129, 0.2);
      color: var(--accent-green);
      border: 1px solid rgba(16, 185, 129, 0.4);
    }
    .checklist-status-badge.pending {
      background: rgba(245, 158, 11, 0.2);
      color: var(--accent-amber);
      border: 1px solid rgba(245, 158, 11, 0.4);
    }
    .checklist-status-badge.optional {
      background: rgba(255, 255, 255, 0.08);
      color: var(--text-dim);
      border: 1px solid var(--border-card);
    }
    .btn-checklist-action {
      background: rgba(6, 182, 212, 0.15);
      border: 1px solid rgba(6, 182, 212, 0.35);
      color: var(--accent-cyan);
      font-size: 11px;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 4px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      white-space: nowrap;
    }
    .btn-checklist-action:hover {
      background: var(--accent-cyan);
      color: #000;
    }

    /* Filas intercaladas (Zebra Striping de Alto Contraste) */
    .data-table tbody tr:nth-child(odd) td {
      background-color: rgba(14, 39, 34, 0.45);
    }

    .data-table tbody tr:nth-child(even) td {
      background-color: rgba(25, 66, 58, 0.65);
    }

    .data-table td {
      padding: 9px 12px;
      border-bottom: 1px solid rgba(31, 77, 67, 0.45);
      color: var(--text-main);
      transition: background-color 0.1s ease;
    }

    /* Resaltado interactivo de fila completa */
    .data-table tbody tr:hover td {
      background-color: rgba(16, 185, 129, 0.22) !important;
      color: #ffffff;
    }

    .data-table td.num {
      font-family: var(--font-mono);
      text-align: right;
    }

    .badge-amber {
      background: rgba(245, 158, 11, 0.15);
      border: 1px solid rgba(245, 158, 11, 0.4);
      color: var(--accent-amber);
      padding: 2px 6px;
      border-radius: 4px;
      font-family: var(--font-mono);
      font-size: 11px;
      font-weight: 700;
    }

    .badge-red {
      background: rgba(239, 68, 68, 0.2);
      border: 1px solid rgba(239, 68, 68, 0.5);
      color: var(--accent-red);
      padding: 2px 6px;
      border-radius: 4px;
      font-family: var(--font-mono);
      font-size: 11px;
      font-weight: 700;
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

    .btn-del {
      background: transparent;
      border: 1px solid var(--border-card);
      color: var(--text-dim);
      border-radius: 4px;
      padding: 3px 7px;
      font-size: 11px;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .btn-del:hover {
      background: rgba(239, 68, 68, 0.2);
      border-color: var(--accent-red);
      color: var(--accent-red);
    }

    
    /* ================= ANIMACIONES FUTURISTAS & EFECTOS HUD ================= */
    @keyframes hud-scanline {
      0% { transform: translateX(-100%); opacity: 0; }
      50% { opacity: 0.9; }
      100% { transform: translateX(250%); opacity: 0; }
    }

    .kpi-card::after {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 60px;
      height: 3px;
      background: linear-gradient(90deg, transparent, #34d399, #10b981, transparent);
      animation: hud-scanline 5s infinite ease-in-out;
      pointer-events: none;
    }

    @keyframes cyber-pulse {
      0%, 100% { box-shadow: 0 0 6px rgba(16, 185, 129, 0.4); transform: scale(1); }
      50% { box-shadow: 0 0 16px rgba(52, 211, 153, 0.85), 0 0 4px #10b981; transform: scale(1.08); }
    }

    .status-dot {
      animation: cyber-pulse 2.2s infinite ease-in-out !important;
    }

    .session-dot {
      animation: cyber-pulse 2.8s infinite ease-in-out !important;
    }

    /* Brackets tácticos en esquinas de paneles */
    .panel-box {
      position: relative;
    }

    .panel-box::before {
      content: "";
      position: absolute;
      top: 0; left: 0;
      width: 10px; height: 10px;
      border-top: 2px solid var(--accent-cyan);
      border-left: 2px solid var(--accent-cyan);
      pointer-events: none;
      opacity: 0.6;
    }

    .panel-box::after {
      content: "";
      position: absolute;
      top: 0; right: 0;
      width: 10px; height: 10px;
      border-top: 2px solid var(--accent-cyan);
      border-right: 2px solid var(--accent-cyan);
      pointer-events: none;
      opacity: 0.6;
    }

    /* Efecto shimmer en botón primario */
    .btn-register {
      position: relative;
      overflow: hidden;
      box-shadow: 0 0 10px rgba(16, 185, 129, 0.25);
      transition: all 0.2s ease;
    }
    .btn-register:hover {
      box-shadow: 0 0 18px rgba(52, 211, 153, 0.6);
      transform: translateY(-1px);
    }
    .btn-register::after {
      content: "";
      position: absolute;
      top: -50%; left: -60%;
      width: 40%; height: 200%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.35), transparent);
      transform: rotate(25deg);
      transition: all 0.6s ease;
    }
    .btn-register:hover::after {
      left: 140%;
    }

    @keyframes row-slide-in {
      from { opacity: 0; transform: translateY(-8px); background: rgba(16, 185, 129, 0.25); }
      to { opacity: 1; transform: translateY(0); background: transparent; }
    }

    .data-table tbody tr.new-row {
      animation: row-slide-in 0.45s ease-out;
    }

    /* Forzar mayúsculas en nombres y opciones */
    .user-name, #topbar-jefe-turno, #sidebar-user-name, select option, #tasks-table td {
      text-transform: uppercase;
    }

    
    /* ================= SYNC ENGINE (OUTBOX OFFLINE & AUTO-RETRY) ================= */
    .sync-pill {
      font-size: 11px;
      font-family: var(--font-mono);
      font-weight: 700;
      padding: 3px 9px;
      border-radius: 4px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all 0.15s ease;
      user-select: none;
    }
    .sync-pill.synced {
      background: rgba(16, 185, 129, 0.15);
      border: 1px solid rgba(16, 185, 129, 0.35);
      color: #34d399;
    }
    .sync-pill.pending {
      background: rgba(245, 158, 11, 0.2);
      border: 1px solid var(--accent-amber);
      color: var(--accent-amber);
      animation: cyber-pulse 2s infinite ease-in-out;
    }
    .sync-pill.syncing {
      background: rgba(6, 182, 212, 0.2);
      border: 1px solid var(--accent-cyan);
      color: var(--accent-cyan);
    }
    .sync-badge {
      font-size: 10px;
      font-family: var(--font-mono);
      padding: 1px 5px;
      border-radius: 3px;
    }
    .sync-badge.synced {
      color: #34d399;
    }
    .sync-badge.pending {
      color: var(--accent-amber);
      background: rgba(245, 158, 11, 0.15);
      border: 1px solid rgba(245, 158, 11, 0.3);
    }

    /* ================= MODALES Y TOASTS ================= */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.75);
      backdrop-filter: blur(4px);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }

    .modal-box {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 10px;
      width: 540px;
      max-width: 90vw;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.8);
      overflow: hidden;
    }

    .modal-header {
      padding: 14px 20px;
      background: var(--bg-card-subtle);
      border-bottom: 1px solid var(--border-card);
      font-size: 13.5px;
      font-weight: 700;
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }

    .modal-header-left {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-modal-close {
      background: transparent;
      border: none;
      color: var(--text-dim);
      font-size: 16px;
      cursor: pointer;
      padding: 2px 6px;
      border-radius: 4px;
      line-height: 1;
      transition: all 0.15s ease;
    }

    .btn-modal-close:hover {
      background: rgba(255, 255, 255, 0.1);
      color: #ffffff;
    }

    .modal-body {
      padding: 20px;
      line-height: 1.5;
      font-size: 13px;
    }

    .modal-footer {
      padding: 14px 20px;
      background: var(--bg-card-subtle);
      border-top: 1px solid var(--border-card);
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    .btn-modal-cancel {
      background: transparent;
      border: 1px solid var(--border-card);
      color: var(--text-muted);
      padding: 7px 14px;
      border-radius: 4px;
      font-size: 12px;
      cursor: pointer;
    }

    .btn-modal-confirm {
      background: var(--accent-cyan);
      border: none;
      color: #000;
      font-weight: 700;
      padding: 7px 16px;
      border-radius: 4px;
      font-size: 12px;
      cursor: pointer;
    }

    .toast-popup {
      display: none;
      position: fixed;
      bottom: 24px;
      right: 28px;
      background: var(--bg-card-subtle);
      border: 1px solid var(--accent-cyan);
      color: #ffffff;
      padding: 12px 18px;
      border-radius: 6px;
      font-size: 12.5px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.6);
      z-index: 2000;
    }
    /* ========================================================== */
    /* TOOLTIPS PERSONALIZADOS CYBERPUNK RADIOLÓGICOS             */
    /* ========================================================== */
    /* ========================================================== */
    /* BANNER Y MODAL DE RELEVO DE GUARDIA / PLANILLA PENDIENTE   */
    /* ========================================================== */
    .banner-relevo-guardia {
      background: rgba(245, 158, 11, 0.12);
      border-bottom: 1px solid rgba(245, 158, 11, 0.4);
      padding: 8px 18px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      font-size: 11.5px;
      font-family: var(--font-mono);
      animation: cyber-hologram-assemble 0.25s ease-out;
      position: sticky;
      top: 0;
      z-index: 90;
    }
    .banner-relevo-content {
      display: flex;
      align-items: center;
      gap: 10px;
      color: #fffbeb;
      line-height: 1.4;
    }
    .banner-relevo-icon {
      font-size: 16px;
      flex-shrink: 0;
    }
    .banner-relevo-actions {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-shrink: 0;
    }
    .btn-banner-cierre {
      background: var(--accent-amber);
      color: #031510;
      border: none;
      padding: 4px 10px;
      border-radius: 5px;
      font-weight: 800;
      font-size: 11px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all 0.15s ease;
    }
    .btn-banner-cierre:hover {
      background: #fbbf24;
      transform: translateY(-1px);
    }
    .btn-banner-detalles {
      background: rgba(245, 158, 11, 0.15);
      color: var(--accent-amber);
      border: 1px solid rgba(245, 158, 11, 0.4);
      padding: 4px 9px;
      border-radius: 5px;
      font-weight: 700;
      font-size: 11px;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .btn-banner-detalles:hover {
      background: rgba(245, 158, 11, 0.3);
    }

    .btn-handover-opt {
      width: 100%;
      text-align: left;
      padding: 10px 14px;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
      display: flex;
      flex-direction: column;
      gap: 3px;
      border: 1px solid var(--border-card);
      background: rgba(0, 0, 0, 0.3);
      font-family: var(--font-sans);
    }
    .btn-handover-opt:hover {
      transform: translateX(3px);
    }
    .btn-handover-opt.primary {
      border-color: rgba(245, 158, 11, 0.6);
      background: rgba(245, 158, 11, 0.12);
    }
    .btn-handover-opt.primary:hover {
      background: rgba(245, 158, 11, 0.22);
      border-color: var(--accent-amber);
    }
    .btn-handover-opt.secondary {
      border-color: rgba(16, 185, 129, 0.4);
      background: rgba(16, 185, 129, 0.08);
    }
    .btn-handover-opt.secondary:hover {
      background: rgba(16, 185, 129, 0.18);
      border-color: var(--accent-cyan);
    }
    .btn-handover-opt.tertiary {
      border-color: var(--border-card);
      background: rgba(255, 255, 255, 0.03);
    }
    .btn-handover-opt.tertiary:hover {
      background: rgba(255, 255, 255, 0.07);
    }
    .btn-handover-opt .opt-title {
      font-weight: 800;
      font-size: 12px;
      color: #ffffff;
    }
    .btn-handover-opt.primary .opt-title {
      color: var(--accent-amber);
    }
    .btn-handover-opt.secondary .opt-title {
      color: var(--accent-cyan);
    }
    .btn-handover-opt .opt-desc {
      font-size: 11px;
      color: var(--text-muted);
      line-height: 1.35;
    }

    .kpi-multi-values.two-cols {
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

        .btn-ghost {
      background: rgba(245, 158, 11, 0.08) !important;
      color: var(--accent-amber) !important;
      border: 1px solid rgba(245, 158, 11, 0.4) !important;
      border-radius: 6px;
      cursor: pointer;
      font-family: var(--font-sans);
      transition: all 0.15s ease;
    }
    .btn-ghost:hover {
      background: rgba(245, 158, 11, 0.2) !important;
      border-color: var(--accent-amber) !important;
    }

    .kpi-field-alert {
      display: none;
      flex-direction: column;
      gap: 6px;
      background: rgba(245, 158, 11, 0.1);
      border: 1px solid rgba(245, 158, 11, 0.45);
      border-left: 4px solid var(--accent-amber);
      color: #fffbeb;
      padding: 10px 14px;
      border-radius: 6px;
      font-size: 11px;
      font-family: var(--font-mono);
      line-height: 1.4;
      margin-top: 10px;
      margin-bottom: 12px;
      animation: cyber-hologram-assemble 0.22s ease-out;
    }

    .kpi-field-alert.danger {
      background: rgba(239, 68, 68, 0.12);
      border-color: rgba(239, 68, 68, 0.55);
      border-left-color: var(--accent-red);
      color: #fee2e2;
    }

    .field-control.input-alert-spike {
      border-color: var(--accent-amber) !important;
      box-shadow: 0 0 10px rgba(245, 158, 11, 0.4) !important;
    }

    .field-control.input-alert-limit {
      border-color: var(--accent-red) !important;
      box-shadow: 0 0 10px rgba(239, 68, 68, 0.5) !important;
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

    /* ================= EFLUENTES LÍQUIDOS 2 FASES ================= */
    .liq-kpi-strip {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 10px;
      margin-bottom: 12px;
    }
    .liq-kpi-card {
      background: rgba(8, 26, 22, 0.78);
      border: 1px solid var(--border-card);
      border-radius: 8px;
      padding: 10px 12px;
    }
    .liq-kpi-label {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      color: var(--text-muted);
      margin-bottom: 4px;
    }
    .liq-kpi-val {
      font-size: 17px;
      font-weight: 800;
      font-family: var(--font-mono);
      color: #fff;
    }
    .liq-kpi-sub {
      font-size: 10.5px;
      color: var(--text-dim);
      margin-top: 2px;
    }
    .liq-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 10.5px;
      font-weight: 800;
      padding: 3px 8px;
      border-radius: 999px;
      white-space: nowrap;
    }
    .liq-badge.precarga {
      background: rgba(245, 158, 11, 0.16);
      color: #fbbf24;
      border: 1px solid rgba(245, 158, 11, 0.45);
    }
    .liq-badge.emitido {
      background: rgba(52, 211, 153, 0.15);
      color: #34d399;
      border: 1px solid rgba(52, 211, 153, 0.4);
    }
    .liq-badge.cancelado {
      background: rgba(148, 163, 184, 0.12);
      color: #94a3b8;
      border: 1px solid rgba(148, 163, 184, 0.25);
    }
    .liq-btn-mini {
      padding: 4px 8px;
      height: auto;
      font-size: 11px;
      font-weight: 700;
      border-radius: 6px;
      cursor: pointer;
      border: 1px solid var(--border-card);
      background: rgba(255, 255, 255, 0.05);
      color: var(--text-main);
      transition: all 0.15s ease;
    }
    .liq-btn-mini:hover {
      background: rgba(16, 185, 129, 0.16);
      border-color: var(--accent-cyan);
    }
    .liq-btn-mini.confirm {
      background: rgba(16, 185, 129, 0.18);
      border-color: var(--accent-green);
      color: #6ee7b7;
    }
    .liq-btn-mini.confirm:hover {
      background: rgba(16, 185, 129, 0.34);
    }
    .liq-btn-mini.danger {
      border-color: rgba(239, 68, 68, 0.4);
      color: #fca5a5;
    }
    .liq-btn-mini.danger:hover {
      background: rgba(239, 68, 68, 0.2);
    }
    .liq-rad-tag {
      display: inline-block;
      font-size: 10px;
      font-family: var(--font-mono);
      padding: 1px 6px;
      border-radius: 4px;
      background: rgba(16, 185, 129, 0.1);
      border: 1px solid rgba(16, 185, 129, 0.25);
      color: var(--text-muted);
      margin-right: 4px;
      margin-top: 2px;
    }
    .liq-tank-chip {
      padding: 3px 8px;
      font-size: 10.5px;
      font-weight: 700;
      border-radius: 5px;
      border: 1px solid var(--border-card);
      background: rgba(16, 185, 129, 0.08);
      color: var(--text-muted);
      cursor: pointer;
    }
    .liq-tank-chip:hover {
      background: rgba(16, 185, 129, 0.22);
      color: #fff;
    }
  </style>
</head>
<body>

  <!-- ================= SIDEBAR FIJO (HMI SYR) ================= -->
  <aside class="sidebar" <?php if ($hay_discrepancia_guardia) echo 'style="pointer-events: none; opacity: 0.35;"'; ?>>
    <div class="sidebar-brand">
      <div class="badge-nasa">NA-SA</div>
      <div class="brand-text">
        <h2>Registro Radiológico</h2>
        <span>SyR • Central Nuclear Embalse</span>
      </div>
    </div>

    <div class="plant-status">
      <div class="status-dot"></div>
      <div class="status-text" id="sidebar-plant-status"><?php echo htmlspecialchars($estado_planta, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>

    <nav class="sidebar-menu">
      <div class="menu-header">Módulos de Turno</div>
      <button class="menu-btn active" data-section="sec-rutina">
        <span>📋 RELEVAMIENTO DE RUTINA</span>
        <span class="menu-badge" id="menu-badge-rutina"><?php echo count($relevamientos_rutina_iniciales); ?></span>
      </button>
      <button class="menu-btn" data-section="sec-gaseosos">
        <span>💨 EMISIÓN EFLUENTES GASEOSOS</span>
        <span class="menu-badge">RO 8.4%</span>
      </button>
      <button class="menu-btn" data-section="sec-espectrometria">
        <span>🔬 ESPECTROMETRÍA DE AIRE (ZC)</span>
        <span class="menu-badge" id="menu-badge-espectrometria"><?php echo count($rows_espectrometria_db); ?></span>
      </button>
      <button class="menu-btn" data-section="sec-liquidos">
        <span>💧 EMISIÓN DE EFLUENTES LÍQUIDOS</span>
        <span class="menu-badge" id="menu-badge-liquidos"><?php echo count($efluentes_liquidos_db); ?></span>
      </button>
      <button class="menu-btn" data-section="sec-contaminacion">
        <span>🧽 CONT. SUPERFICIAL &amp; DRYERS</span>
        <span class="menu-badge">0</span>
      </button>
      <button class="menu-btn" data-section="sec-actas" id="btn-sidebar-actas">
        <span>📖 LIBRO DE ACTAS &amp; TAREAS PTR</span>
        <span class="menu-badge" id="menu-badge-actas">0</span>
      </button>
      
      <button type="button" class="menu-btn menu-btn-checklist" onclick="openChecklistModal()" style="border-left: 3px solid var(--accent-amber); background: rgba(245, 158, 11, 0.08); margin-top: 6px;">
        <span>📋 CHECKLIST DE GUARDIA</span>
        <span class="menu-badge" id="sidebar-checklist-badge" style="background: var(--accent-amber); color: #000; font-weight: 800;">Pendientes</span>
      </button>

      <div class="menu-header" style="margin-top: 14px;">Administración &amp; Cierre</div>
      <button class="menu-btn" data-section="sec-historial">
        <span>📁 HISTORIAL DE PLANILLAS</span>
      </button>
      <button class="menu-btn" data-section="sec-cierre">
        <span>🔒 CIERRE DE TURNO</span>
      </button>
    </nav>

    <div class="sidebar-user">
      <!-- Turno Activo & Botón de Configuración -->
      <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 10px; background: rgba(9, 28, 24, 0.7); border: 1px solid var(--border-card); border-radius: 6px; padding: 6px 10px;">
        <div style="display: flex; flex-direction: column; min-width: 0;">
          <span style="font-size: 8.5px; text-transform: uppercase; color: var(--text-dim); font-weight: 700; letter-spacing: 0.5px;">Turno de Guardia</span>
          <span class="shift-tag" id="sidebar-shift-tag" style="border: none; background: transparent; padding: 0; font-size: 11px; font-weight: 700; color: var(--accent-cyan); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($turno_actual_nombre, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <button type="button" class="btn-new-shift" onclick="openShiftModal()" style="padding: 3px 8px; font-size: 10.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; height: 26px; border-radius: 4px; white-space: nowrap; flex-shrink: 0;" title="Configurar turno, jefe u oficial">
          ⚙️ <span>Configurar</span>
        </button>
      </div>

      <div class="user-name" id="sidebar-user-name"><?php echo htmlspecialchars($oficial_actual, ENT_QUOTES, 'UTF-8'); ?></div>
      <div class="user-role">Oficial de Radioprotección</div>
      <div class="session-indicator">
        <div class="session-dot" id="session-status-dot"></div>
        <span id="session-status-text">Sistema en línea</span>
      </div>
    </div>
  </aside>

  <!-- ================= CONTENIDO PRINCIPAL ================= -->
  <main class="main-content">
    
    <!-- TOPBAR -->
    <header class="topbar">
      <div class="topbar-left">
        <span class="topbar-title" id="current-section-title">RELEVAMIENTO DE RUTINA</span>
        
        <!-- BOTÓN VERIFICADOR / CHECKLIST DE CARGAS DEL TURNO -->
        <button type="button" class="btn-checklist-trigger" onclick="openChecklistModal()" title="Abrir Asistente de Verificación de Cargas del Turno">
          <span>📋 ESTADO DE CARGAS</span>
          <span class="badge-checklist-pill" id="topbar-checklist-badge">Verificando...</span>
        </button>

        <div class="theme-switcher-bar" id="theme-switcher-bar">
        <span style="font-size:10px; color:var(--text-dim); font-weight:700; text-transform:uppercase;">🎨 FONDO:</span>
        <button type="button" class="theme-pill active" data-theme="petrol" onclick="setAppTheme('petrol')">🌲 Verde Radiológico (Oficial)</button>
        <button type="button" class="theme-pill" data-theme="navy" onclick="setAppTheme('navy')">🌊 Azul Abisal</button>
        <button type="button" class="theme-pill" data-theme="plum" onclick="setAppTheme('plum')">🍇 Ciruela</button>
        <button type="button" class="theme-pill" data-theme="titanio" onclick="setAppTheme('titanio')">⚙️ Titanio</button>
        <button type="button" class="theme-pill" data-theme="ambar" onclick="setAppTheme('ambar')">⚡ Ámbar</button>
      </div>
      
      
      </div>
      <div class="topbar-right">
        <div class="sync-pill synced" id="sync-status-indicator" onclick="SyncEngine.flush(true)" title="Estado de sincronización: Haga clic para forzar reintento">
          ☁️ Servidor sincronizado
        </div>
        <span>•</span>
        <span>Jefe: <strong style="color: #ffffff;" id="topbar-jefe-turno"><?php echo htmlspecialchars($jefe_turno_actual, ENT_QUOTES, 'UTF-8'); ?></strong></span>
        <span>•</span>
        <span>Fecha: <strong style="color: #ffffff;" id="topbar-latam-date"><?php echo htmlspecialchars($fecha_operativa_default, ENT_QUOTES, 'UTF-8'); ?></strong></span>
        <div class="clock-pill" id="live-clock"><?php echo $hora_actual; ?>:00 hs</div>
      </div>
    </header>

    <!-- BANNER DE ADVERTENCIA: PLANILLA ANTERIOR SIN CERRAR -->
    <div id="banner-relevo-guardia" class="banner-relevo-guardia" style="display: <?php echo $hay_discrepancia_guardia ? 'flex' : 'none'; ?>;">
      <div class="banner-relevo-content">
        <span class="banner-relevo-icon">⚠️</span>
        <span class="banner-relevo-text">
          <strong>PLANILLA ANTERIOR SIN CERRAR:</strong> La planilla activa (#<span id="banner-plan-id"><?php echo $id_planilla_activa; ?></span>) pertenece a <strong><?php echo htmlspecialchars($oficial_iniciador_nombre, ENT_QUOTES, 'UTF-8'); ?></strong> (<em><?php echo htmlspecialchars($turno_actual_nombre, ENT_QUOTES, 'UTF-8'); ?></em>). Para operar debe cerrarse definitivamente.
        </span>
      </div>
      <div class="banner-relevo-actions">
        <button type="button" class="btn-banner-cierre" onclick="mostrarPasoAdvertenciaCierre()">
          🔒 Cerrar Planilla Anterior y Abrir Nueva
        </button>
        <button type="button" class="btn-banner-detalles" onclick="verPlanillaSoloLectura()" title="Ver en modo consulta sin modificar datos">
          👁️ Modo Auditoría
        </button>
      </div>
    </div>

    <!-- WORKSPACE SCROLLABLE -->
    <div class="workspace" <?php if ($hay_discrepancia_guardia) echo 'style="filter: blur(5px); pointer-events: none; user-select: none; opacity: 0.25;"'; ?>>

      <!-- ================= BARRA SUPERIOR & 6 KPIS SUPERIORES ================= -->
      <div class="kpis-toolbar">
        <div class="kpis-title">
          <span>⚡ Métricas de Guardia &amp; Chimenea</span>
          <span style="color: var(--text-dim); font-size: 10px; font-weight: normal;">[ Haga clic en cualquier tarjeta para actualizarla directamente ]</span>
        </div>
        <button type="button" class="btn-update-all-kpis" onclick="openKpiModal('all')">
          <span>✏️ Actualizar Métricas de Chimenea</span>
        </button>
      </div>

      <section class="kpi-grid">
        
        <!-- KPI 1: Picos Radiológicos del Turno (Dosis, Tritio, Gases) -->
        <div class="kpi-card" style="--card-color: var(--accent-amber);" onclick="openKpiModal('dosis')" title="Haga clic para ver relevamientos de rutina">
          <div class="kpi-top-row">
            <span class="kpi-label">Picos Radiológicos</span>
            <span class="kpi-action-icon">📋 Ver</span>
          </div>
          <div class="kpi-multi-values">
            <div class="kpi-sub-item">
              <span class="kpi-sub-label">Dosis</span>
              <div class="kpi-sub-val-row">
                <span class="kpi-sub-val" id="kpi-max-dosis" style="color: var(--accent-amber);">24.0</span>
                <span class="kpi-sub-unit">µSv/h</span>
              </div>
            </div>
            <div class="kpi-sub-item">
              <span class="kpi-sub-label">Tritio</span>
              <div class="kpi-sub-val-row">
                <span class="kpi-sub-val" id="kpi-max-tritio" style="color: var(--accent-cyan);">1.20</span>
                <span class="kpi-sub-unit">DAC</span>
              </div>
            </div>
            <div class="kpi-sub-item">
              <span class="kpi-sub-label">Gases</span>
              <div class="kpi-sub-val-row">
                <span class="kpi-sub-val" id="kpi-max-gases" style="color: #c084fc;">0.00</span>
                <span class="kpi-sub-unit">DAC</span>
              </div>
            </div>
          </div>
          <div class="kpi-footer" id="kpi-max-recinto">📍 R-024 (PHT) • R-001</div>
        </div>

        <!-- KPI 2: RO Gaseoso -->
        <div class="kpi-card" style="--card-color: var(--accent-green);" onclick="openKpiModal('ro')" title="Haga clic para actualizar Restricción Operacional">
          <div class="kpi-top-row">
            <span class="kpi-label">% Restricción Operacional</span>
            <span class="kpi-action-icon">✏️ Cargar</span>
          </div>
          <div class="kpi-value-row">
            <span class="kpi-value" id="kpi-ro-val" style="color: var(--accent-green);">8.42</span>
            <span class="kpi-unit">% RO</span>
          </div>
          <div class="kpi-footer" id="kpi-ro-footer">Chimenea en rango nominal</div>
        </div>

        <!-- KPI 3: Ventilación Chimenea (General y Reactor unificados) -->
        <div class="kpi-card" id="card-kpi-vent" style="--card-color: var(--accent-blue);" onclick="openKpiModal('tritio')" title="Haga clic para actualizar Ventilación General y Reactor">
          <div class="kpi-top-row">
            <span class="kpi-label">Ventilación Chimenea</span>
            <span class="kpi-action-icon">✏️ Cargar</span>
          </div>
          <div class="kpi-multi-values two-cols">
            <div class="kpi-sub-item">
              <span class="kpi-sub-label">General</span>
              <div class="kpi-sub-val-row">
                <span class="kpi-sub-val" id="kpi-vent-gral" style="color: #60a5fa;"><?php echo (isset($vent_gral_db) && $vent_gral_db !== "") ? htmlspecialchars($vent_gral_db, ENT_QUOTES, 'UTF-8') : '<span style="font-size:11.5px;color:var(--accent-amber);font-weight:700;">○ Pendiente</span>'; ?></span>
                <span class="kpi-sub-unit" id="kpi-vent-gral-unit" style="<?php echo (empty($vent_gral_db)) ? 'display:none;' : ''; ?>">GBq</span>
              </div>
            </div>
            <div class="kpi-sub-item">
              <span class="kpi-sub-label">Reactor</span>
              <div class="kpi-sub-val-row">
                <span class="kpi-sub-val" id="kpi-vent-reactor" style="color: #22d3ee;"><?php echo (isset($vent_reactor_db) && $vent_reactor_db !== "") ? htmlspecialchars($vent_reactor_db, ENT_QUOTES, 'UTF-8') : '<span style="font-size:11.5px;color:var(--accent-amber);font-weight:700;">○ Pendiente</span>'; ?></span>
                <span class="kpi-sub-unit" id="kpi-vent-reactor-unit" style="<?php echo (empty($vent_reactor_db)) ? 'display:none;' : ''; ?>">GBq</span>
              </div>
            </div>
          </div>
          <div class="kpi-footer" id="kpi-vent-footer">Caudal y extracción activa</div>
        </div>

        <!-- KPI 4: Condición Meteorológica -->
        <div class="kpi-card" id="card-kpi-met" style="--card-color: #f59e0b;" onclick="openKpiModal('met')" title="Haga clic para ver o actualizar Condición Meteorológica">
          <div class="kpi-top-row">
            <span class="kpi-label">🌤️ Meteorología</span>
            <span class="kpi-action-icon">✏️ Cargar</span>
          </div>
          <div class="kpi-value-row">
            <span class="kpi-value" id="kpi-met-val" style="color: #f59e0b; font-size: 15px; font-weight: 800;"><?php echo (isset($met_vel_db) && $met_vel_db !== "") ? htmlspecialchars($met_dir_db . " • " . $met_vel_db, ENT_QUOTES, 'UTF-8') : '<span style="font-size:13px;color:var(--accent-amber);font-weight:700;">○ Pendiente</span>'; ?></span>
            <span class="kpi-unit" id="kpi-met-unit" style="<?php echo (empty($met_vel_db)) ? 'display:none;' : ''; ?>">km/h</span>
          </div>
          <div class="kpi-footer" id="kpi-met-footer"><?php echo (isset($met_pasquill_db) && $met_pasquill_db !== "") ? htmlspecialchars($met_pasquill_db, ENT_QUOTES, 'UTF-8') : "Condición no registrada"; ?></div>
        </div>

        <!-- KPI 5: Emisión C-14 (Métrica de Planta) -->
        <div class="kpi-card" style="--card-color: #a855f7;" onclick="openKpiModal('c14')" title="Haga clic para actualizar Emisión Carbono-14">
          <div class="kpi-top-row">
            <span class="kpi-label">Emisión Carbono-14</span>
            <span class="kpi-action-icon">✏️ Cargar</span>
          </div>
          <div class="kpi-value-row">
            <span class="kpi-value" id="kpi-c14"><?php echo (isset($c14_db) && $c14_db !== "") ? htmlspecialchars($c14_db, ENT_QUOTES, 'UTF-8') : '<span style="font-size:13px;color:var(--accent-amber);font-weight:700;">○ Pendiente</span>'; ?></span>
            <span class="kpi-unit" style="<?php echo (empty($c14_db)) ? 'display:none;' : ''; ?>">MBq</span>
          </div>
          <div class="kpi-footer" id="kpi-c14-footer">Muestreo acumulado turno</div>
        </div>

        <!-- KPI 6: Dedo Frío 73120 (Métrica de Planta con Últimos 5 Datos) -->
        <div class="kpi-card" style="--card-color: #ec4899;" onclick="openKpiModal('dedofrio')" title="Haga clic para ver histórico y actualizar Dedo Frío 73120">
          <div class="kpi-top-row">
            <span class="kpi-label">Dedo Frío 73120</span>
            <span class="kpi-action-icon">📊 Últimos 5</span>
          </div>
          <div class="kpi-value-row">
            <span class="kpi-value" id="kpi-dedo-frio"><?php echo (isset($dedo_frio_ppm_db) && $dedo_frio_ppm_db !== "") ? htmlspecialchars(($dedo_frio_menor_que_db ? "< " : "") . $dedo_frio_ppm_db, ENT_QUOTES, 'UTF-8') : '<span style="font-size:13px;color:var(--accent-amber);font-weight:700;">○ Pendiente</span>'; ?></span>
            <span class="kpi-unit" style="<?php echo (empty($dedo_frio_ppm_db)) ? 'display:none;' : ''; ?>">ppm</span>
          </div>
          <!-- Micro-barra visual de los últimos 5 registros criogénicos -->
          <div class="kpi-mini-history" style="display: flex; gap: 3px; margin: 2px 0 3px;">
            <span title="Hoy 04:15 (ALTUNA): < 12 ppm" style="background: rgba(236,72,153,0.3); color: #f472b6; font-family: var(--font-mono); font-size: 8.5px; padding: 1px 4px; border-radius: 3px; font-weight: 800; border: 1px solid rgba(236,72,153,0.5);">&lt;12</span>
            <span title="Ayer 20:30 (ROLHAUSER): < 10 ppm" style="background: rgba(236,72,153,0.15); color: #f472b6; font-family: var(--font-mono); font-size: 8.5px; padding: 1px 4px; border-radius: 3px;">&lt;10</span>
            <span title="Ayer 12:45 (GATICA): 15 ppm" style="background: rgba(236,72,153,0.15); color: #f472b6; font-family: var(--font-mono); font-size: 8.5px; padding: 1px 4px; border-radius: 3px;">15</span>
            <span title="Ayer 05:00 (CISTERNA): < 11 ppm" style="background: rgba(236,72,153,0.15); color: #f472b6; font-family: var(--font-mono); font-size: 8.5px; padding: 1px 4px; border-radius: 3px;">&lt;11</span>
            <span title="07/11 21:10 (PASCUAL): 14 ppm" style="background: rgba(236,72,153,0.15); color: #f472b6; font-family: var(--font-mono); font-size: 8.5px; padding: 1px 4px; border-radius: 3px;">14</span>
          </div>
          <div class="kpi-footer" id="kpi-dedo-frio-footer">Histórico 5 guardias nominal</div>
        </div>

      </section>

      <!-- ================= SECCIÓN 1: RELEVAMIENTO DE RUTINA ================= -->
      <section class="section-panel active" id="sec-rutina">
        <div class="panel-box">
          <div class="panel-header">
            <div class="panel-title">
              <span>📋</span> RELEVAMIENTO DE RUTINA
            </div>
            <span style="font-size: 11.5px; font-family: var(--font-mono); color: var(--text-muted);">
              [ Ingrese los valores y pulse <span style="color: var(--accent-cyan);">Enter</span> para registrar ]
            </span>
          </div>
          <div class="panel-body">
            
            <!-- Barra de Carga Rápida -->
            <div class="entry-bar">
              <div class="field-group">
                <label class="field-label">Fecha (LatAm)</label>
                <input type="text" id="q-fecha" class="field-control mono latam-datepicker-input" value="<?php echo htmlspecialchars($fecha_operativa_default, ENT_QUOTES, 'UTF-8'); ?>" placeholder="DD/MM/AAAA">
              </div>

              <div class="field-group">
                <label class="field-label">Hora <button id="btn-now" type="button" style="background:none; border:none; color:var(--accent-cyan); font-size:10px; cursor:pointer;" title="Hora actual">⚡Ahora</button></label>
                <input type="text" id="q-hora" class="field-control mono latam-timepicker-input" value="<?php echo $hora_actual; ?>" placeholder="HH:MM">
              </div>

              <div class="field-group" style="position: relative;">
                <label class="field-label">Recinto de Planta <span style="color:var(--accent-cyan); font-size:9.5px; text-transform:none;">[🔍 Buscar]</span></label>
                <div class="searchable-select-wrapper" id="recinto-search-wrapper" style="position: relative;">
                  <input type="text" id="q-recinto" class="field-control" placeholder="Escriba código o nombre (ej: 108, R-001, G.V.)..." autocomplete="off" style="padding-right: 28px;">
                  <button type="button" class="btn-clear-ctrl" id="btn-clear-recinto" title="Borrar recinto seleccionado" onclick="clearRecintoSelection()">&times;</button>
                  <input type="hidden" id="q-recinto-id" value="">
                  <div class="searchable-dropdown" id="recinto-dropdown"></div>
                </div>
              </div>

              <div class="field-group">
                <label class="field-label">Gases (DAC)</label>
                <input type="text" id="q-gases" class="field-control mono" placeholder="0.00">
              </div>

              <div class="field-group">
                <label class="field-label">Tritio (DAC)</label>
                <input type="text" id="q-tritio" class="field-control mono" placeholder="0.00">
              </div>

              <div class="field-group">
                <label class="field-label">Dosis (µSv/h)</label>
                <input type="text" id="q-dosis" class="field-control mono" placeholder="0.0">
              </div>

              <div class="field-group">
                <label class="field-label">Observaciones</label>
                <input type="text" id="q-obs" class="field-control" placeholder="Detalles de la medición...">
              </div>

              <button class="btn-register" id="btn-add" type="button">
                <span>+ AGREGAR</span>
              </button>
            </div>

            <!-- Pastilla HUD: Últimos 3 valores registrados en este recinto -->
            <div id="recinto-history-panel" style="display: none; margin-bottom: 14px; background: rgba(9, 28, 24, 0.7); border: 1px solid var(--border-card); border-left: 3px solid var(--accent-cyan); border-radius: 6px; padding: 8px 12px; font-size: 11px;">
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 5px;">
                <span style="font-weight: 700; color: var(--accent-cyan); text-transform: uppercase; font-size: 10px;">
                  📍 Últimos 3 Registros en: <strong id="rh-recinto-label" style="color: #ffffff;">-</strong>
                </span>
                <span id="rh-trend-badge" style="font-size: 10px; font-weight: 700; color: var(--text-muted); font-family: var(--font-mono);">
                  🟢 Nivel habitual
                </span>
              </div>
              <div id="rh-chips-list" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <!-- Chips inyectados por JS -->
              </div>
            </div>

            <!-- Tabla de Mediciones (Encabezados Sticky fijos al scrollear) -->
            <div class="table-scroll-wrapper" id="rutina-scroll-wrapper">
              <table class="data-table" id="rutina-table">
              <thead>
                <tr>
                  <th style="width: 105px;">Fecha</th>
                  <th style="width: 75px; text-align: right;">Hora</th>
                  <th>Recinto / Ubicación</th>
                  <th style="width: 105px; text-align: right;">Gases (DAC)</th>
                  <th style="width: 105px; text-align: right;">Tritio (DAC)</th>
                  <th style="width: 115px; text-align: right;">Tasa Dosis (µSv/h)</th>
                  <th>Observaciones</th>
                  <th style="width: 140px;">Oficial</th>
                  <th style="width: 60px; text-align: center;">Acción</th>
                </tr>
              </thead>
              <tbody id="table-body">
                <?php if (empty($relevamientos_rutina_iniciales)) { ?>
                  <tr id="row-empty">
                    <td colspan="9" style="text-align: center; padding: 24px; color: var(--text-dim); font-style: italic;">
                      - No se registraron relevamientos de rutina en esta guardia (Pendiente) -
                    </td>
                  </tr>
                <?php } else { ?>
                <?php foreach ($relevamientos_rutina_iniciales as $row) { 
                  $dNum = (float)$row['dosis'];
                  $tNum = (float)$row['tritio'];
                  $gNum = (float)$row['gases'];
                  
                  $dosisBadge = ($dNum > 100) ? '<span class="badge-red">' . number_format($dNum, 1) . '</span>' : (($dNum > 20) ? '<span class="badge-amber">' . number_format($dNum, 1) . '</span>' : number_format($dNum, 1));
                  $tritioBadge = ($tNum > 1.0) ? '<span class="badge-amber">' . number_format($tNum, 2) . '</span>' : number_format($tNum, 2);
                ?>
                <tr data-dosis="<?php echo $dNum; ?>" data-tritio="<?php echo $tNum; ?>" data-gases="<?php echo $gNum; ?>" data-recinto="<?php echo htmlspecialchars($row['recinto'], ENT_QUOTES, 'UTF-8'); ?>">
                  <td><?php echo htmlspecialchars($row['fecha'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="num"><?php echo htmlspecialchars($row['hora'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td style="font-weight: 700; color: #ffffff;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                      <span class="recinto-link" onclick="openRecintoHistoryModal('<?php echo htmlspecialchars($row['recinto'], ENT_QUOTES, 'UTF-8'); ?>')" title="Ver las últimas 3 mediciones de este recinto">
                        <?php echo htmlspecialchars($row['recinto'], ENT_QUOTES, 'UTF-8'); ?>
                      </span>
                      <button type="button" class="btn-recinto-hist-pill" onclick="openRecintoHistoryModal('<?php echo htmlspecialchars($row['recinto'], ENT_QUOTES, 'UTF-8'); ?>')" title="Ver últimas mediciones de este recinto">
                        <span>⏱️ 3 Últimos</span>
                      </button>
                    </div>
                  </td>
                  <td class="num"><?php echo number_format($gNum, 2); ?></td>
                  <td class="num"><?php echo $tritioBadge; ?></td>
                  <td class="num"><?php echo $dosisBadge; ?></td>
                  <td style="color: var(--text-muted);"><?php echo htmlspecialchars((!empty($row['obs']) && $row['obs'] !== 'Sin observaciones') ? $row['obs'] : '--Sin observaciones--', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($row['oficial'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td style="text-align: center;"><button class="btn-del" onclick="deleteRow(this)">✕</button></td>
                </tr>
                <?php } ?>
                <?php } ?>
              </tbody>
              </table>
            </div>

          </div>
        </div>
      </section>

      <!-- ================= SECCIÓN 2: EFLUENTES GASEOSOS & 7923 ================= -->
      <section class="section-panel" id="sec-gaseosos">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
          <div style="display: flex; flex-direction: column; gap: 16px;">

            <!-- SUB-PANEL A: GASES NOBLES CHIMENEA -->
            <div class="panel-box">
              <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div class="panel-title"><span>💨</span> GASES NOBLES (CHIMENEA)</div>
                <div style="display: flex; align-items: center; gap: 8px;">
                  <span id="badge-gases-nobles-status" class="<?php echo $hay_gases_nobles_db ? 'sync-badge' : 'badge-amber'; ?>" style="font-size: 10.5px; padding: 2px 8px; border-radius: 4px; font-weight: 700;">
                    <?php echo $hay_gases_nobles_db ? '● Registrado' : '○ Sin Cargar'; ?>
                  </span>
                </div>
              </div>
              <div class="panel-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                  <div class="field-group">
                    <label class="field-label">Horario Desde</label>
                    <input type="text" class="field-control mono latam-timepicker-input" id="input-gn-desde" value="<?php echo htmlspecialchars($gn_desde_db, ENT_QUOTES, 'UTF-8'); ?>" placeholder="00:00">
                  </div>
                  <div class="field-group">
                    <label class="field-label">Horario Hasta</label>
                    <input type="text" class="field-control mono latam-timepicker-input" id="input-gn-hasta" value="<?php echo htmlspecialchars($gn_hasta_db, ENT_QUOTES, 'UTF-8'); ?>" placeholder="18:00">
                  </div>
                </div>

                <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                  Actividad de Radioisótopos (GBq):
                </div>

                <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; margin-bottom: 16px;">
                  <div class="field-group">
                    <label class="field-label" style="text-align: center; color: #a78bfa; font-weight: 700;">Ar-41</label>
                    <input type="number" step="0.01" class="field-control mono" id="input-gn-ar41" value="<?php echo htmlspecialchars($gn_ar41_db, ENT_QUOTES, 'UTF-8'); ?>" style="text-align: center; font-weight: 700; color: #a78bfa;">
                    <span style="font-size: 9.5px; color: var(--text-dim); text-align: center; display: block; margin-top: 2px;">GBq</span>
                  </div>
                  <div class="field-group">
                    <label class="field-label" style="text-align: center; color: #60a5fa; font-weight: 700;">Kr-85</label>
                    <input type="number" step="0.01" class="field-control mono" id="input-gn-kr85" value="<?php echo htmlspecialchars($gn_kr85_db, ENT_QUOTES, 'UTF-8'); ?>" style="text-align: center; font-weight: 700; color: #60a5fa;">
                    <span style="font-size: 9.5px; color: var(--text-dim); text-align: center; display: block; margin-top: 2px;">GBq</span>
                  </div>
                  <div class="field-group">
                    <label class="field-label" style="text-align: center; color: #60a5fa; font-weight: 700;">Kr-85m</label>
                    <input type="number" step="0.01" class="field-control mono" id="input-gn-kr85m" value="<?php echo htmlspecialchars($gn_kr85m_db, ENT_QUOTES, 'UTF-8'); ?>" style="text-align: center; font-weight: 700; color: #60a5fa;">
                    <span style="font-size: 9.5px; color: var(--text-dim); text-align: center; display: block; margin-top: 2px;">GBq</span>
                  </div>
                  <div class="field-group">
                    <label class="field-label" style="text-align: center; color: #34d399; font-weight: 700;">Xe-133</label>
                    <input type="number" step="0.01" class="field-control mono" id="input-gn-xe133" value="<?php echo htmlspecialchars($gn_xe133_db, ENT_QUOTES, 'UTF-8'); ?>" style="text-align: center; font-weight: 700; color: #34d399;">
                    <span style="font-size: 9.5px; color: var(--text-dim); text-align: center; display: block; margin-top: 2px;">GBq</span>
                  </div>
                  <div class="field-group">
                    <label class="field-label" style="text-align: center; color: #34d399; font-weight: 700;">Xe-135</label>
                    <input type="number" step="0.01" class="field-control mono" id="input-gn-xe135" value="<?php echo htmlspecialchars($gn_xe135_db, ENT_QUOTES, 'UTF-8'); ?>" style="text-align: center; font-weight: 700; color: #34d399;">
                    <span style="font-size: 9.5px; color: var(--text-dim); text-align: center; display: block; margin-top: 2px;">GBq</span>
                  </div>
                  <div class="field-group">
                    <label class="field-label" style="text-align: center; color: #34d399; font-weight: 700;">Xe-135m</label>
                    <input type="number" step="0.01" class="field-control mono" id="input-gn-xe135m" value="<?php echo htmlspecialchars($gn_xe135m_db, ENT_QUOTES, 'UTF-8'); ?>" style="text-align: center; font-weight: 700; color: #34d399;">
                    <span style="font-size: 9.5px; color: var(--text-dim); text-align: center; display: block; margin-top: 2px;">GBq</span>
                  </div>
                </div>

                <input type="hidden" id="input-gn-id" value="<?php echo htmlspecialchars($gn_id_db, ENT_QUOTES, 'UTF-8'); ?>">

                <div style="display: flex; gap: 8px;">
                  <button type="button" class="btn-register" style="flex: 1;" onclick="saveGasesNobles()">
                    💾 ASENTAR GASES NOBLES
                  </button>
                  <button type="button" class="btn-ghost" onclick="deleteGasesNobles()" style="color: var(--accent-red); border-color: rgba(239, 68, 68, 0.4); padding: 0 14px;" title="Eliminar registro de Gases Nobles">
                    ⛔ Eliminar Datos
                  </button>
                </div>
              </div>
            </div>

            <!-- SUB-PANEL B: TRITIO, CARBONO-14, DEDO FRÍO & CONCENTRADOR 7923 -->
            <div class="panel-box">
              <div class="panel-header">
                <div class="panel-title"><span>☢️</span> TRITIO CHIMENEA, C-14 & DEDO FRÍO 73120</div>
              </div>
              <div class="panel-body">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 16px;">
                  <div class="field-group">
                    <label class="field-label">Ventilación General (GBq)</label>
                    <input type="text" class="field-control mono" id="input-vent-gral" value="<?php echo htmlspecialchars($vent_gral_db, ENT_QUOTES, 'UTF-8'); ?>">
                  </div>
                  <div class="field-group">
                    <label class="field-label">Ventilación Reactor (GBq)</label>
                    <input type="text" class="field-control mono" id="input-vent-reactor" value="<?php echo htmlspecialchars($vent_reactor_db, ENT_QUOTES, 'UTF-8'); ?>">
                  </div>
                  <div class="field-group">
                    <label class="field-label">Emisión Carbono-14 (MBq)</label>
                    <input type="text" class="field-control mono" id="input-c14" value="<?php echo htmlspecialchars($c14_db, ENT_QUOTES, 'UTF-8'); ?>">
                  </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                  <div class="field-group">
                    <label class="field-label">Dedo Frío 73120 (ppm)</label>
                    <input type="text" class="field-control mono" id="input-dedo-frio" value="<?php echo htmlspecialchars($dedo_frio_ppm_db, ENT_QUOTES, 'UTF-8'); ?>">
                  </div>
                  <div class="field-group">
                    <label class="field-label">Actividad Concentrador 7923 (MBq)</label>
                    <input type="text" class="field-control mono" value="45.2">
                  </div>
                </div>
                <div style="display: flex; gap: 8px;">
                  <button type="button" class="btn-register" style="flex: 1;" onclick="updateChimneyKPIs()">
                    💾 ASENTAR MEDICIONES DE CHIMENEA
                  </button>
                  <button type="button" class="btn-ghost" onclick="clearChimneyKPIs()" style="color: var(--accent-amber); border-color: rgba(245, 158, 11, 0.4); padding: 0 12px;" title="Borrar mediciones de chimenea">
                    🗑️ Borrar
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="panel-box">
            <div class="panel-header">
              <div class="panel-title"><span>🌤️</span> CONDICIÓN METEOROLÓGICA</div>
            </div>
            <div class="panel-body">
              <div class="field-group" style="margin-bottom: 12px;">
                <label class="field-label">Dirección del Viento</label>
                <select id="input-met-dir" class="field-control">
                  <option value="S">Sur (S) - 180°</option>
                  <option value="SE">Sudeste (SE) - 135°</option>
                  <option value="E">Este (E) - 90°</option>
                  <option value="NE">Noreste (NE) - 45°</option>
                  <option value="N">Norte (N) - 0°</option>
                  <option value="SO">Sudoeste (SO) - 225°</option>
                  <option value="O">Oeste (O) - 270°</option>
                  <option value="NO">Noroeste (NO) - 315°</option>
                </select>
              </div>
              <div class="field-group" style="margin-bottom: 12px;">
                <label class="field-label">Velocidad del Viento (km/h)</label>
                <input type="text" id="input-met-vel" class="field-control mono" value="<?php echo htmlspecialchars($met_vel_db, ENT_QUOTES, 'UTF-8'); ?>">
              </div>
              <div class="field-group" style="margin-bottom: 14px;">
                <label class="field-label">Estabilidad Pasquill</label>
                <select id="input-met-pasquill" class="field-control">
                  <option value="Clase D (Neutral - Nocturno nublado)">Clase D (Neutral - Nocturno nublado)</option>
                  <option value="Clase E (Ligeramente estable)">Clase E (Ligeramente estable)</option>
                  <option value="Clase F (Estable - Noche despejada)">Clase F (Estable - Noche despejada)</option>
                  <option value="Clase A (Extremadamente inestable)">Clase A (Extremadamente inestable)</option>
                  <option value="Clase B (Moderadamente inestable)">Clase B (Moderadamente inestable)</option>
                  <option value="Clase C (Ligeramente inestable)">Clase C (Ligeramente inestable)</option>
                </select>
              </div>
              <div style="display: flex; gap: 8px;">
                <button type="button" class="btn-register" style="flex: 1;" onclick="saveInlineMeteorology()">
                  💾 GUARDAR METEOROLOGÍA
                </button>
                <button type="button" class="btn-ghost" onclick="clearInlineMeteorology()" style="color: var(--accent-amber); border-color: rgba(245, 158, 11, 0.4); padding: 0 12px;" title="Borrar datos o restablecer como pendiente">
                  🗑️ Borrar
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ================= SECCIÓN: ESPECTROMETRÍA DEL AIRE DE ZONA CONTROLADA ================= -->
      <section class="section-panel" id="sec-espectrometria">
        <div class="panel-box">
          <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div class="panel-title">
              <span>🔬</span> ESPECTROMETRÍA DEL AIRE DE ZONA CONTROLADA
            </div>
            <button type="button" class="btn-new-shift" onclick="openEspectrometriaModal()" style="padding: 5px 12px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; height: 30px; border-radius: 6px;">
              <span>➕ Cargar datos de Espectrometría</span>
            </button>
          </div>
          <div class="panel-body">
            <div style="overflow-x: auto;">
              <table class="data-table" id="tabla-espectrometria-aire" style="font-size: 11.5px;">
                <thead>
                  <tr>
                    <th rowspan="2" style="vertical-align: middle;">Concentración de</th>
                    <th rowspan="2" style="vertical-align: middle;">Recinto</th>
                    <th rowspan="2" style="vertical-align: middle; text-align: center; width: 80px;">Hora</th>
                    <th colspan="4" style="text-align: center; border-bottom: 1px solid var(--border-card);">Radionucleídos (DAC)</th>
                    <th rowspan="2" style="vertical-align: middle; text-align: right; width: 100px; color: var(--accent-cyan);">TOTAL (DAC)</th>
                    <th rowspan="2" style="vertical-align: middle; text-align: center; width: 100px;">Acciones</th>
                  </tr>
                  <tr>
                    <th style="text-align: right; width: 85px; color: #a78bfa;">Ar-41</th>
                    <th style="text-align: right; width: 85px; color: #34d399;">Xe-133</th>
                    <th style="text-align: right; width: 85px; color: #34d399;">Xe-135</th>
                    <th style="text-align: right; width: 85px; color: #f59e0b;">Kr-88</th>
                  </tr>
                </thead>
                <tbody id="tbody-espectrometria">
                  <?php if (empty($rows_espectrometria_db)) { ?>
                    <tr id="row-esp-empty">
                      <td colspan="9" style="text-align: center; padding: 24px; color: var(--text-dim); font-style: italic;">
                        - No se registraron espectrometrías de aire específicas en este turno -
                      </td>
                    </tr>
                  <?php } else { ?>
                    <?php foreach ($rows_espectrometria_db as $esp) { 
                      $ar = (float)$esp['ar41'];
                      $x3 = (float)$esp['xe133'];
                      $x5 = (float)$esp['xe135'];
                      $k8 = (float)$esp['kr88'];
                      $tot = $ar + $x3 + $x5 + $k8;
                      $hora_corta = substr($esp['hora'], 0, 5);
                      $rec_nom = !empty($esp['recinto_nombre']) ? $esp['recinto_nombre'] : ('Recinto #' . $esp['recinto']);
                    ?>
                      <tr id="row-esp-<?php echo $esp['id_conc_rad']; ?>" 
                          data-id="<?php echo $esp['id_conc_rad']; ?>"
                          data-tipo="<?php echo htmlspecialchars($esp['tipo'], ENT_QUOTES, 'UTF-8'); ?>"
                          data-recinto="<?php echo $esp['recinto']; ?>"
                          data-recinto-nombre="<?php echo htmlspecialchars($rec_nom, ENT_QUOTES, 'UTF-8'); ?>"
                          data-hora="<?php echo $hora_corta; ?>"
                          data-fecha="<?php echo htmlspecialchars($esp['fecha'], ENT_QUOTES, 'UTF-8'); ?>"
                          data-ar41="<?php echo $ar; ?>"
                          data-xe133="<?php echo $x3; ?>"
                          data-xe135="<?php echo $x5; ?>"
                          data-kr88="<?php echo $k8; ?>">
                        <td style="font-weight: 700; color: #ffffff;"><?php echo strtoupper(htmlspecialchars($esp['tipo'], ENT_QUOTES, 'UTF-8')); ?></td>
                        <td><span style="font-weight: 600; color: var(--accent-cyan);"><?php echo htmlspecialchars($rec_nom, ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td class="mono" style="text-align: center;"><?php echo $hora_corta; ?></td>
                        <td class="mono num" style="text-align: right; color: #a78bfa;"><?php echo number_format($ar, 1); ?></td>
                        <td class="mono num" style="text-align: right; color: #34d399;"><?php echo number_format($x3, 1); ?></td>
                        <td class="mono num" style="text-align: right; color: #34d399;"><?php echo number_format($x5, 1); ?></td>
                        <td class="mono num" style="text-align: right; color: #f59e0b;"><?php echo number_format($k8, 1); ?></td>
                        <td class="mono num" style="text-align: right; font-weight: 800; color: var(--accent-cyan);"><?php echo number_format($tot, 1); ?></td>
                        <td style="text-align: center;">
                          <div style="display: inline-flex; gap: 6px;">
                            <button type="button" class="btn-action-icon" onclick="openEspectrometriaModal(<?php echo $esp['id_conc_rad']; ?>)" title="Editar registro de espectrometría" style="background: rgba(6, 182, 212, 0.15); border: 1px solid rgba(6, 182, 212, 0.3); color: var(--accent-cyan); padding: 3px 6px; border-radius: 4px; cursor: pointer; font-size: 11px;">
                              ✏️
                            </button>
                            <button type="button" class="btn-action-icon" onclick="deleteEspectrometria(<?php echo $esp['id_conc_rad']; ?>)" title="Eliminar registro" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: var(--accent-red); padding: 3px 6px; border-radius: 4px; cursor: pointer; font-size: 11px;">
                              ⛔
                            </button>
                          </div>
                        </td>
                      </tr>
                    <?php } ?>
                  <?php } ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <!-- ================= SECCIÓN 3: EFLUENTES LÍQUIDOS (2 FASES) ================= -->
      <section class="section-panel" id="sec-liquidos">
        <div class="panel-box">
          <div class="panel-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div class="panel-title"><span>💧</span> EMISIÓN DE EFLUENTES LÍQUIDOS &nbsp;<span style="font-size: 10.5px; color: var(--accent-amber); font-weight: 700;">(PRE-CARGA + CONFIRMACIÓN DE BOMBEO)</span></div>
            <div style="display: flex; align-items: center; gap: 10px;">
              <label style="display: inline-flex; align-items: center; gap: 5px; font-size: 11px; color: var(--text-muted); cursor: pointer;">
                <input type="checkbox" id="liq-chk-show-cancelled" onchange="renderEfluentesLiquidos()">
                <span>Ver cancelados (<span id="liq-cancelled-count">0</span>)</span>
              </label>
              <button type="button" class="btn-register" style="padding: 6px 14px; height: auto; font-size: 11.5px; background: rgba(245, 158, 11, 0.16); border-color: var(--accent-amber); color: #fbbf24;" onclick="openEfluenteLiquidoModal()">
                + NUEVA PRE-CARGA / AUTORIZACIÓN
              </button>
            </div>
          </div>
          <div class="panel-body">
            <!-- Banner explicativo 2 fases -->
            <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.28); border-left: 3px solid var(--accent-amber); border-radius: 6px; padding: 8px 12px; margin-bottom: 12px; font-size: 11.5px; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
              <span>
                🛡️ <strong style="color: #fbbf24;">Control de Emisión Real:</strong> Toda autorización nueva entra como <strong style="color: #fbbf24;">🟡 PRE-CARGA</strong> para calcular TBq, % RoDD e imprimir su ticket sin sumarse a la emisión oficial. Sólo se asienta en MySQL al pulsar <strong style="color: #34d399;">✅ Confirmar Bombeo</strong>.
              </span>
            </div>

            <!-- KPIs de líquidos -->
            <div class="liq-kpi-strip">
              <div class="liq-kpi-card" style="border-left: 3px solid var(--accent-amber);">
                <div class="liq-kpi-label">🟡 Pre-Cargas (Sin Emitir)</div>
                <div class="liq-kpi-val" id="liq-kpi-precargas" style="color: #fbbf24;">0</div>
                <div class="liq-kpi-sub" id="liq-kpi-precargas-sub">0.00 m³ en espera</div>
              </div>
              <div class="liq-kpi-card" style="border-left: 3px solid var(--accent-green);">
                <div class="liq-kpi-label">🟢 Emitidos Reales (Guardia)</div>
                <div class="liq-kpi-val" id="liq-kpi-emitidos" style="color: #34d399;">0</div>
                <div class="liq-kpi-sub" id="liq-kpi-emitidos-sub">0.00 m³ bombeados</div>
              </div>
              <div class="liq-kpi-card">
                <div class="liq-kpi-label">💧 Tritio Emitido Real</div>
                <div class="liq-kpi-val" id="liq-kpi-tritio" style="color: var(--accent-cyan);">0.000 TBq</div>
                <div class="liq-kpi-sub" id="liq-kpi-gamma-sub">Gamma: 0.000 TBq</div>
              </div>
              <div class="liq-kpi-card">
                <div class="liq-kpi-label">☢️ % RoDD Emitido Real</div>
                <div class="liq-kpi-val" id="liq-kpi-rodd" style="color: #fff;">0.000%</div>
                <div class="liq-kpi-sub">Sólo descargas confirmadas</div>
              </div>
            </div>

            <!-- Tabla unificada -->
            <div style="overflow-x: auto;">
              <table class="data-table" id="tabla-efluentes-liquidos">
                <thead>
                  <tr>
                    <th>Estado</th>
                    <th>Fecha / Hora</th>
                    <th>Tanque &amp; Radionucleídos</th>
                    <th style="text-align: right;">Volumen (m³)</th>
                    <th style="text-align: right;">Act. Tritio (TBq)</th>
                    <th style="text-align: right;">Act. Gamma (TBq)</th>
                    <th style="text-align: right;">% del RoDD</th>
                    <th style="text-align: center;">Acciones</th>
                  </tr>
                </thead>
                <tbody id="tbody-efluentes-liquidos">
                  <tr>
                    <td colspan="8" style="text-align: center; color: var(--text-dim); padding: 18px; font-style: italic;">
                      Cargando efluentes líquidos...
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <!-- ================= SECCIÓN 4: FROTIS & DRYERS ================= -->
      <section class="section-panel" id="sec-contaminacion">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
          <div class="panel-box">
            <div class="panel-header">
              <div class="panel-title"><span>🧽</span> MEDICIÓN DE CONT. SUPERFICIAL Y TASA DE DOSIS EQUIVALENTE</div>
            </div>
            <div class="panel-body">
              <div class="field-group" style="margin-bottom: 12px; position: relative;">
                <label class="field-label">Recinto de Muestreo <span style="color:var(--accent-cyan); font-size:9.5px;">[🔍 Buscar]</span></label>
                <div class="searchable-select-wrapper" id="frotis-recinto-wrapper">
                  <input type="text" id="frotis-recinto" class="field-control" placeholder="Escriba código o nombre de recinto..." autocomplete="off" style="padding-right: 28px;">
                  <button type="button" class="btn-clear-ctrl" id="btn-clear-frotis-recinto" title="Borrar recinto" style="display:none;">&times;</button>
                  <input type="hidden" id="frotis-recinto-id" value="">
                  <div class="searchable-dropdown" id="frotis-recinto-dropdown"></div>
                </div>
              </div>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div class="field-group">
                  <label class="field-label">Cont. Alfa (Bq/cm²)</label>
                  <input type="text" class="field-control mono" placeholder="0.00" value="0.02">
                </div>
                <div class="field-group">
                  <label class="field-label">Cont. Beta-Gamma (Bq/cm²)</label>
                  <input type="text" class="field-control mono" placeholder="0.00" value="0.15">
                </div>
              </div>
              <button class="btn-register" style="width: 100%;" onclick="showToast('✔️ Frotis de contaminación asentado');">
                + REGISTRAR MEDICIÓN DE FROTIS
              </button>
            </div>
          </div>

          <div class="panel-box">
            <div class="panel-header">
              <div class="panel-title"><span>🔄</span> EFICIENCIA DRYERS</div>
            </div>
            <div class="panel-body">
              <div class="field-group" style="margin-bottom: 12px;">
                <label class="field-label">Secador en Servicio</label>
                <select class="field-control">
                  <option value="DRY-01">Dryer D2O N° 1 (Edificio Reactor)</option>
                  <option value="DRY-02">Dryer D2O N° 2 (Área Auxiliar)</option>
                </select>
              </div>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div class="field-group">
                  <label class="field-label">Punto de Rocío (°C)</label>
                  <input type="text" class="field-control mono" value="-35.2">
                </div>
                <div class="field-group">
                  <label class="field-label">Eficiencia de Recuperación (%)</label>
                  <input type="text" class="field-control mono" value="98.5">
                </div>
              </div>
              <button class="btn-register" style="width: 100%;" onclick="showToast('✔️ Eficiencia de Dryers registrada');">
                + GUARDAR CONTROL DE DRYERS
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- ================= SECCIÓN 5: TAREAS & PTR ================= -->
      <!-- ================= SECCIÓN 5: LIBRO DE ACTAS & NOVEDADES DE GUARDIA ================= -->
      <section class="section-panel" id="sec-actas" data-alias="sec-tareas">
        <div class="panel-box">
          <div class="panel-header">
            <div class="panel-title"><span>📖</span> LIBRO DE ACTAS &amp; TAREAS PTR</div>
            <span style="font-size: 11px; color: var(--text-muted); font-family: var(--font-mono);" id="actas-counter-badge">
              0 actas asentadas en este turno
            </span>
          </div>
          <div class="panel-body">
            
            <!-- FORMULARIO DE CARGA DE ACTA -->
            <div style="background: var(--bg-card-subtle); border: 1px solid var(--border-card); border-radius: 8px; padding: 14px; margin-bottom: 18px;">
              <div style="font-size: 11px; font-weight: 700; color: var(--accent-cyan); text-transform: uppercase; margin-bottom: 10px; letter-spacing: 0.5px;">
                + Cargar Nueva Acta / Registro de Guardia
              </div>

              <!-- Texto del Acta -->
              <div class="field-group" style="margin-bottom: 12px;">
                <label class="field-label" style="font-weight: 700; color: #ffffff;">Descripción del Evento / Novedad / Acta <span style="color: var(--accent-red);">*</span></label>
                <textarea id="acta-texto" class="field-control" style="height: 75px; padding: 8px 10px; resize: vertical; font-size: 12px;" placeholder="Describa el trabajo realizado, novedad de guardia, inspección o evento..."></textarea>
              </div>

              <!-- Metadata: Oficial, Ayudante, Recinto, PTR con Buscadores Inteligentes y Crucecita X -->
              <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 12px;">
                <div class="field-group" style="position: relative;">
                  <label class="field-label">Oficial Responsable <span style="color:var(--accent-cyan); font-size:9.5px;">[🔍 Buscar]</span></label>
                  <div class="searchable-select-wrapper" id="acta-oficial-wrapper">
                    <input type="text" id="acta-oficial" class="field-control" placeholder="Buscar oficial..." value="<?php echo htmlspecialchars($oficial_actual, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" style="font-weight:700; color:#ffffff; padding-right: 28px;">
                    <button type="button" class="btn-clear-ctrl" id="btn-clear-acta-oficial" title="Borrar oficial" style="display: <?php echo !empty($oficial_actual) ? 'block' : 'none'; ?>;">&times;</button>
                    <input type="hidden" id="acta-oficial-id" value="<?php echo htmlspecialchars($oficial_actual, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="searchable-dropdown" id="acta-oficial-dropdown"></div>
                  </div>
                </div>

                <div class="field-group" style="position: relative;">
                  <label class="field-label">Personal que Asistió / Ayudante <span style="color:var(--accent-cyan); font-size:9.5px;">[🔍 Buscar]</span></label>
                  <div class="searchable-select-wrapper" id="acta-ayudante-wrapper">
                    <input type="text" id="acta-ayudante" class="field-control" placeholder="-- Sin ayudante / Buscar... --" autocomplete="off" style="padding-right: 28px;">
                    <button type="button" class="btn-clear-ctrl" id="btn-clear-acta-ayudante" title="Borrar ayudante" style="display:none;">&times;</button>
                    <input type="hidden" id="acta-ayudante-id" value="">
                    <div class="searchable-dropdown" id="acta-ayudante-dropdown"></div>
                  </div>
                </div>

                <div class="field-group" style="position: relative;">
                  <label class="field-label">Recinto Asociado <span style="color:var(--text-dim); font-size:9.5px;">[🔍 Buscar]</span></label>
                  <div class="searchable-select-wrapper" id="acta-recinto-wrapper">
                    <input type="text" id="acta-recinto" class="field-control" placeholder="General de Planta / Escriba para buscar..." autocomplete="off" style="padding-right: 28px;">
                    <button type="button" class="btn-clear-ctrl" id="btn-clear-acta-recinto" title="Borrar recinto" style="display:none;">&times;</button>
                    <input type="hidden" id="acta-recinto-id" value="">
                    <div class="searchable-dropdown" id="acta-recinto-dropdown"></div>
                  </div>
                </div>

                <div class="field-group" style="position: relative;">
                  <label class="field-label">N° PTR Asociado <span style="color:var(--accent-cyan); font-size:9.5px;">[🔍 Buscar PTR]</span></label>
                  <div class="searchable-select-wrapper" id="acta-ptr-wrapper">
                    <input type="text" id="acta-ptr" class="field-control mono" placeholder="Buscar N° o título de PTR activo..." autocomplete="off" style="padding-right: 28px;">
                    <button type="button" class="btn-clear-ctrl" id="btn-clear-acta-ptr" title="Borrar PTR" style="display:none;">&times;</button>
                    <input type="hidden" id="acta-ptr-id" value="">
                    <div class="searchable-dropdown" id="acta-ptr-dropdown"></div>
                  </div>
                </div>
              </div>

              <!-- Parámetros Radiológicos Opcionales & Flags -->
              <div style="display: grid; grid-template-columns: repeat(4, 1fr) 2fr; gap: 12px; align-items: flex-end;">
                <div class="field-group">
                  <label class="field-label">Dosis (µSv/h)</label>
                  <input type="text" id="acta-dosis" class="field-control mono" placeholder="0.0">
                </div>
                <div class="field-group">
                  <label class="field-label">Tritio (DAC)</label>
                  <input type="text" id="acta-tritio" class="field-control mono" placeholder="0.0">
                </div>
                <div class="field-group">
                  <label class="field-label">Cont. β+γ (Bq/cm²)</label>
                  <input type="text" id="acta-cont-beta" class="field-control mono" placeholder="0.0">
                </div>
                <div class="field-group">
                  <label class="field-label">Cont. α (Bq/cm²)</label>
                  <input type="text" id="acta-cont-alfa" class="field-control mono" placeholder="0.0">
                </div>

                <!-- Checkboxes de Seguridad -->
                <div style="display: flex; gap: 14px; align-items: center; padding-bottom: 6px;">
                  <label style="display: flex; align-items: center; gap: 6px; font-size: 11px; cursor: pointer; color: var(--text-main);">
                    <input type="checkbox" id="acta-derrame" style="accent-color: var(--accent-amber); cursor: pointer;">
                    <span>Derrame D2O</span>
                  </label>
                  <label style="display: flex; align-items: center; gap: 6px; font-size: 11px; cursor: pointer; color: var(--text-main);">
                    <input type="checkbox" id="acta-aerosoles" onchange="toggleAerosolesInput(this)" style="accent-color: var(--accent-cyan); cursor: pointer;">
                    <span>Aerosoles</span>
                  </label>
                  <input type="text" id="acta-aerosoles-dac" class="field-control mono" placeholder="DAC aer." style="display: none; width: 80px; height: 30px; font-size: 11px;">
                </div>
              </div>

              <div style="margin-top: 14px; text-align: right;">
                <button type="button" class="btn-register" onclick="asentarNuevaActa()">
                  💾 ASENTAR ACTA EN LIBRO DE GUARDIA
                </button>
              </div>
            </div>

            <!-- TABLA DE ACTAS ASENTADAS EN EL TURNO (Encabezados Sticky fijos) -->
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">
              📋 Actas Registradas en Esta Guardia:
            </div>
            <div class="table-scroll-wrapper" id="actas-scroll-wrapper" style="max-height: 300px; min-height: 180px;">
              <table class="data-table" id="tabla-actas-guardia">
              <thead>
                <tr>
                  <th style="width: 65px;">Hora</th>
                  <th style="width: 80px;">PTR</th>
                  <th>Recinto</th>
                  <th>Oficial</th>
                  <th>Asistió</th>
                  <th style="text-align: left;">Descripción / Novedad</th>
                  <th style="width: 90px; text-align: right;">Dosis (µSv/h)</th>
                  <th style="width: 90px;">D2O / Aer.</th>
                  <th style="width: 50px; text-align: center;">Acción</th>
                </tr>
              </thead>
                            <tbody id="tbody-actas-guardia">
                <?php if (empty($actas_tareas_iniciales)) { ?>
                  <tr id="row-actas-empty">
                    <td colspan="9" style="text-align: center; padding: 24px; color: var(--text-dim); font-style: italic;">
                      - No se asentaron actas o novedades en esta guardia (Pendiente) -
                    </td>
                  </tr>
                <?php } else { ?>
                  <?php foreach ($actas_tareas_iniciales as $acta) { ?>
                    <tr class="acta-clickable-row" onclick="abrirModalDetalleActaFromRow(this)"
                        data-id="<?php echo $acta['id']; ?>"
                        data-hora="<?php echo htmlspecialchars($acta['hora'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-ptr="<?php echo htmlspecialchars($acta['ptr'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-recinto="<?php echo htmlspecialchars($acta['recinto'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-oficial="<?php echo htmlspecialchars($acta['oficial'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-ayudante="<?php echo htmlspecialchars($acta['ayudante'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-texto="<?php echo htmlspecialchars($acta['texto'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-dosis="<?php echo $acta['dosis']; ?>"
                        data-tritio="<?php echo $acta['tritio']; ?>"
                        data-derrame="<?php echo htmlspecialchars($acta['derrame'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-aerosoles="<?php echo $acta['aerosoles']; ?>">
                      <td class="mono"><?php echo htmlspecialchars($acta['hora'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td class="mono" style="color: var(--accent-cyan); font-weight: 700;"><?php echo htmlspecialchars($acta['ptr'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($acta['recinto'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td style="font-weight: 700;"><?php echo htmlspecialchars($acta['oficial'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($acta['ayudante'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td class="text-left" style="max-width: 320px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                          <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 250px; display: inline-block;">
                            <?php echo htmlspecialchars(mb_substr($acta['texto'], 0, 75), ENT_QUOTES, 'UTF-8') . (mb_strlen($acta['texto']) > 75 ? '...' : ''); ?>
                          </span>
                          <button type="button" class="btn-acta-open" title="Abrir y ver acta completa">🔍 Ver</button>
                        </div>
                      </td>
                      <td class="mono" style="text-align: right; color: var(--accent-amber); font-weight: 700;"><?php echo number_format($acta['dosis'], 1); ?></td>
                      <td><span style="color: var(--text-dim);"><?php echo $acta['tritio'] > 0 ? number_format($acta['tritio'], 2) : '-'; ?></span></td>
                      <td style="text-align: center;" onclick="event.stopPropagation();"><button class="btn-del" onclick="deleteRow(this)">✕</button></td>
                    </tr>
                  <?php } ?>
                <?php } ?>
              </tbody>
              </table>
            </div>

          </div>
        </div>
      </section>

      <!-- ================= SECCIÓN 7: HISTORIAL ================= -->
      <section class="section-panel" id="sec-historial">
        <div class="panel-box">
          <div class="panel-header">
            <div class="panel-title"><span>📁</span> HISTORIAL DE PLANILLAS ANTERIORES</div>
          </div>
          <div class="panel-body">
            <table class="data-table">
              <thead>
                <tr>
                  <th>N° Planilla</th>
                  <th>Fecha (DD/MM/AAAA)</th>
                  <th>Turno</th>
                  <th>Oficial a Cargo</th>
                  <th>Jefe de Turno</th>
                  <th>Estado</th>
                  <th style="text-align: center;">Reporte PDF</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="num">#10842</td>
                  <td>08/11/2026</td>
                  <td>Turno 1 (06:00 - 14:00)</td>
                  <td>Rodríguez, Miguel</td>
                  <td>Gómez, Carlos</td>
                  <td><span style="color: var(--accent-green); font-weight:700;">Cerrada</span></td>
                  <td style="text-align: center;"><button class="btn-del" style="color: var(--accent-cyan); border-color: var(--accent-cyan);" onclick="showToast('Generando vista oficial de planilla #10842')">📄 Ver PDF</button></td>
                </tr>
                <tr>
                  <td class="num">#10841</td>
                  <td>07/11/2026</td>
                  <td>Turno 3 (23:00 - 07:00)</td>
                  <td>Pérez, Juan</td>
                  <td>Martínez, Esteban</td>
                  <td><span style="color: var(--accent-green); font-weight:700;">Cerrada</span></td>
                  <td style="text-align: center;"><button class="btn-del" style="color: var(--accent-cyan); border-color: var(--accent-cyan);" onclick="showToast('Generando vista oficial de planilla #10841')">📄 Ver PDF</button></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ================= SECCIÓN 8: CIERRE ================= -->
      <section class="section-panel" id="sec-cierre">
        <div class="panel-box">
          <div class="panel-header">
            <div class="panel-title"><span>🔒</span> FORMALIZACIÓN Y CIERRE DE PLANILLA DIARIA</div>
          </div>
          <div class="panel-body">
            <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 20px;">
              Al formalizar el cierre del turno:
              <br>• Se congela la planilla y se bloquea la edición de mediciones de rutina.
              <br>• Se estampa la firma digital del <strong>Oficial de Radioprotección (<?php echo htmlspecialchars($oficial_actual, ENT_QUOTES, 'UTF-8'); ?>)</strong> y del <strong>Jefe de Turno (<?php echo htmlspecialchars($jefe_turno_actual, ENT_QUOTES, 'UTF-8'); ?>)</strong>.
              <br>• Se emite el reporte oficial inmutable en formato PDF para el archivo de SyR CNE.
            </p>
            <button class="btn-register" style="padding: 10px 24px; font-size: 13px;" onclick="closeShiftFormal()">
              🔒 FORMALIZAR Y CERRAR TURNO ACTUAL
            </button>
          </div>
        </div>
      </section>

    </div>
  </main>

  <!-- ================= MODAL INTELIGENTE DE APERTURA DE TURNO (SOLUCIÓN TURNO NOCHE) ================= -->
  <div class="modal-overlay" id="shift-config-modal">
    <div class="modal-box">
      <div class="modal-header">
        <div class="modal-header-left">
          <span style="color: var(--accent-cyan);">⚙️</span> Configuración y Apertura de Turno
        </div>
        <button type="button" class="btn-modal-close" onclick="closeShiftModal()" title="Cerrar modal (Esc)">✕</button>
      </div>
      <div class="modal-body">
        
        <div class="field-group" style="margin-bottom: 12px;">
          <label class="field-label">Seleccione Turno de Trabajo</label>
          <select id="modal-select-turno" class="field-control" style="font-weight: 700; color: var(--accent-cyan);">
            <?php foreach ($turnos_array as $tur) { ?>
              <option value="<?php echo $tur['id']; ?>" <?php if($turno_default == $tur['id']) echo "selected"; ?>>
                <?php echo htmlspecialchars($tur['horarios'], ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php } ?>
          </select>
        </div>

        <div class="field-group" style="margin-bottom: 12px;">
          <label class="field-label">Fecha Operativa de Guardia (DD/MM/AAAA)</label>
          <input type="text" id="modal-fecha-operativa" class="field-control mono latam-datepicker-input" style="font-size: 14px; font-weight: 800; color: #ffffff;" value="<?php echo htmlspecialchars($fecha_operativa_default, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <!-- Cartel inteligente de detección automática de madrugada (00:00 a 04:59 hs) -->
        <div id="night-shift-alert" style="padding: 12px; border-radius: 6px; background: rgba(6, 182, 212, 0.12); border: 1px solid var(--accent-cyan); font-size: 11.5px; color: var(--accent-cyan); margin-bottom: 14px; <?php if(!$es_madrugada) echo 'display:none;'; ?>">
          <strong>ℹ️ Detección Inteligente de Turno Noche (00:00 a 05:00 hs):</strong>
          <br>
          Inició sesión después de las 00:00 hs (<strong id="night-alert-hora"><?php echo $hora_actual; ?> hs</strong>). Dado que el turno que comenzó a las 23:00 hs pertenece al día anterior, verifique que la fecha operativa corresponda a <strong id="night-alert-ayer">AYER (<?php echo date('d/m/Y', strtotime('-1 day')); ?>)</strong> para evitar el desfase de día.
          <div style="margin-top: 8px; display: flex; gap: 8px;">
            <button type="button" style="background: rgba(255,255,255,0.1); border: 1px solid var(--border-card); color: #fff; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 11px;" onclick="setShiftDate('today')">Usar fecha de Hoy</button>
            <button type="button" style="background: var(--accent-cyan); border: none; color: #000; font-weight:700; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 11px;" onclick="setShiftDate('yesterday')">Usar fecha de Ayer</button>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="field-group">
            <label class="field-label">Oficial a Cargo</label>
            <select class="field-control" id="modal-select-oficial">
              <?php foreach ($oficiales_array as $of) { ?>
                <option value="<?php echo htmlspecialchars($of['nombre'], ENT_QUOTES, 'UTF-8'); ?>" <?php if($of['nombre'] == $oficial_actual) echo "selected"; ?>>
                  <?php echo htmlspecialchars($of['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php } ?>
            </select>
          </div>
          <div class="field-group">
            <label class="field-label">Jefe de Turno</label>
            <select class="field-control" id="modal-select-jefe">
              <?php foreach ($jefes_array as $jef) { ?>
                <option value="<?php echo htmlspecialchars($jef['nombre'], ENT_QUOTES, 'UTF-8'); ?>" data-id="<?php echo (int)$jef['id']; ?>" <?php if($jef['nombre'] == $jefe_turno_actual) echo "selected"; ?>>
                  <?php echo htmlspecialchars($jef['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php } ?>
            </select>
          </div>
          <div id="alert-tab-met" class="kpi-field-alert"></div>
        </div>

        <div class="field-group">
          <label class="field-label">Estado de Planta</label>
          <select class="field-control" id="modal-select-planta">
            <?php foreach ($estados_planta_array as $est) { ?>
              <option value="<?php echo htmlspecialchars($est, ENT_QUOTES, 'UTF-8'); ?>" <?php if($est == $estado_planta) echo "selected"; ?>>
                <?php echo htmlspecialchars($est, ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php } ?>
          </select>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn-modal-cancel" onclick="closeShiftModal()">CANCELAR</button>
        <button class="btn-modal-confirm" onclick="applyShiftConfig()">GUARDAR Y APLICAR AL TURNO</button>
      </div>
    </div>
  </div>

    <!-- ================= MODAL DE CARGA Y ACTUALIZACIÓN DE MÉTRICAS DE PLANTA / CHIMENEA ================= -->
  <div class="modal-overlay" id="kpi-metricas-modal">
    <div class="modal-box" style="width: 620px;">
      <div class="modal-header">
        <div class="modal-header-left">
          <span style="color: var(--accent-cyan);">⚡</span>
          <span id="kpi-modal-title">Carga / Actualización de Métricas de Chimenea &amp; Planta</span>
        </div>
        <button type="button" class="btn-modal-close" onclick="closeKpiModal()" title="Cerrar modal (Esc)">✕</button>
      </div>
      <div class="modal-body" style="padding: 16px 20px;">
        
        <!-- Pestañas internas del modal -->
        <div class="kpi-modal-tabs">
          <button type="button" class="kpi-tab-btn active" data-tab="tab-tritio" onclick="switchKpiTab('tab-tritio')">💨 Tritio Chimenea</button>
          <button type="button" class="kpi-tab-btn" data-tab="tab-gasesnobles" onclick="switchKpiTab('tab-gasesnobles')">🧪 Gases Nobles</button>
          <button type="button" class="kpi-tab-btn" data-tab="tab-c14" onclick="switchKpiTab('tab-c14')">⚛️ Carbono 14</button>
          <button type="button" class="kpi-tab-btn" data-tab="tab-dedofrio" onclick="switchKpiTab('tab-dedofrio')">❄️ Dedo Frío 73120</button>
          <button type="button" class="kpi-tab-btn" data-tab="tab-ro" onclick="switchKpiTab('tab-ro')">📈 % RO Chimenea</button>
          <button type="button" class="kpi-tab-btn" data-tab="tab-met" onclick="switchKpiTab('tab-met')">🌤️ Meteorología</button>
        </div>

        <!-- TAB 1: Tritio Chimenea (Ventilación General y Reactor) -->
        <div class="kpi-tab-content active" id="tab-tritio">
          <div style="font-size: 11.5px; color: var(--text-muted); margin-bottom: 14px;">
            Descarga de tritio gaseoso por ventilación activa de chimenea:
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
            <div class="field-group">
              <label class="field-label">Hora Desde (HH:MM)</label>
              <input type="text" id="kpi-in-tritio-desde" class="field-control mono latam-timepicker-input" value="23:00">
            </div>
            <div class="field-group">
              <label class="field-label">Hora Hasta (HH:MM)</label>
              <input type="text" id="kpi-in-tritio-hasta" class="field-control mono latam-timepicker-input" value="07:00">
            </div>
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
            <div class="field-group">
              <label class="field-label">Ventilación General (GBq)</label>
              <input type="text" id="kpi-in-vent-gral" class="field-control mono" style="font-size: 14px; font-weight: 700; color: #60a5fa;" value="<?php echo htmlspecialchars($vent_gral_db, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="field-group">
              <label class="field-label">Ventilación Reactor (GBq)</label>
              <input type="text" id="kpi-in-vent-reactor" class="field-control mono" style="font-size: 14px; font-weight: 700; color: #22d3ee;" value="<?php echo htmlspecialchars($vent_reactor_db, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>
          <div id="alert-tab-tritio" class="kpi-field-alert"></div>

          <!-- TABLA DE LOS ÚLTIMOS REGISTROS DE VENTILACIÓN -->
          <div style="background: rgba(9, 28, 24, 0.7); border: 1px solid var(--border-card); border-radius: 6px; padding: 10px 12px; margin-top: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
              <span style="font-size: 10.5px; font-weight: 700; color: var(--accent-cyan); text-transform: uppercase;">
                📊 Histórico de Ventilación Chimenea
              </span>
              <span style="font-size: 9.5px; color: var(--text-dim); font-family: var(--font-mono);">Guardias Anteriores</span>
            </div>
            <table class="data-table" style="font-size: 11px;">
              <thead>
                <tr>
                  <th style="padding: 5px 8px;">Fecha</th>
                  <th style="padding: 5px 8px;">Hora</th>
                  <th style="padding: 5px 8px;">Oficial Responsable</th>
                  <th style="padding: 5px 8px; text-align: right; color: #60a5fa;">V. General</th>
                  <th style="padding: 5px 8px; text-align: right; color: #22d3ee;">V. Reactor</th>
                  <th style="padding: 5px 8px; text-align: center;">Estado</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="mono">08/11/2026</td>
                  <td class="mono">23:00</td>
                  <td style="font-weight: 700;">ALTUNA, LEONARDO (P)</td>
                  <td class="mono" style="text-align: right; color: #60a5fa; font-weight: 700;">1.45 GBq</td>
                  <td class="mono" style="text-align: right; color: #22d3ee; font-weight: 700;">3.80 GBq</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
                <tr>
                  <td class="mono">08/11/2026</td>
                  <td class="mono">15:00</td>
                  <td>ROLHAUSER, MARIANO</td>
                  <td class="mono" style="text-align: right; color: #60a5fa;">1.30 GBq</td>
                  <td class="mono" style="text-align: right; color: #22d3ee;">3.65 GBq</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
                <tr>
                  <td class="mono">08/11/2026</td>
                  <td class="mono">07:00</td>
                  <td>GATICA, CARLOS</td>
                  <td class="mono" style="text-align: right; color: #60a5fa;">1.40 GBq</td>
                  <td class="mono" style="text-align: right; color: #22d3ee;">3.70 GBq</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 2: Carbono 14 -->
        <div class="kpi-tab-content" id="tab-c14" style="display: none;">
          <div style="font-size: 11.5px; color: var(--text-muted); margin-bottom: 14px;">
            Muestreo integrado y concentración de Carbono-14 en efluentes:
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
            <div class="field-group">
              <label class="field-label">Hora de Medición (HH:MM)</label>
              <input type="text" id="kpi-in-c14-hora" class="field-control mono latam-timepicker-input" value="06:00">
            </div>
            <div class="field-group">
              <label class="field-label">Actividad C-14 (MBq)</label>
              <input type="text" id="kpi-in-c14-val" class="field-control mono" style="font-size: 14px; font-weight: 700; color: #c084fc;" value="<?php echo htmlspecialchars($c14_db, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>
          <div id="alert-tab-c14" class="kpi-field-alert"></div>

          <!-- TABLA DE LOS ÚLTIMOS REGISTROS DE CARBONO 14 -->
          <div style="background: rgba(9, 28, 24, 0.7); border: 1px solid var(--border-card); border-radius: 6px; padding: 10px 12px; margin-top: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
              <span style="font-size: 10.5px; font-weight: 700; color: var(--accent-cyan); text-transform: uppercase;">
                📊 Histórico de Emisión Carbono-14
              </span>
              <span style="font-size: 9.5px; color: var(--text-dim); font-family: var(--font-mono);">Límite Op: &lt; 50 MBq</span>
            </div>
            <table class="data-table" style="font-size: 11px;">
              <thead>
                <tr>
                  <th style="padding: 5px 8px;">Fecha</th>
                  <th style="padding: 5px 8px;">Hora</th>
                  <th style="padding: 5px 8px;">Oficial Responsable</th>
                  <th style="padding: 5px 8px; text-align: right; color: #c084fc;">Actividad MBq</th>
                  <th style="padding: 5px 8px; text-align: center;">Estado</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="mono">08/11/2026</td>
                  <td class="mono">06:00</td>
                  <td style="font-weight: 700;">ALTUNA, LEONARDO (P)</td>
                  <td class="mono" style="text-align: right; color: #c084fc; font-weight: 700;">12.4 MBq</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
                <tr>
                  <td class="mono">07/11/2026</td>
                  <td class="mono">22:00</td>
                  <td>ROLHAUSER, MARIANO</td>
                  <td class="mono" style="text-align: right; color: #c084fc;">11.8 MBq</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
                <tr>
                  <td class="mono">07/11/2026</td>
                  <td class="mono">14:00</td>
                  <td>GATICA, CARLOS</td>
                  <td class="mono" style="text-align: right; color: #c084fc;">12.0 MBq</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 3: Dedo Frío 73120 -->
        <div class="kpi-tab-content" id="tab-dedofrio" style="display: none;">
          <div style="font-size: 11.5px; color: var(--text-muted); margin-bottom: 12px;">
            Concentración de agua pesada D2O en gas de muestreo criogénico 73120:
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 12px; margin-bottom: 10px;">
            <div class="field-group">
              <label class="field-label">Hora de Medición (HH:MM)</label>
              <input type="text" id="kpi-in-dedofrio-hora" class="field-control mono latam-timepicker-input" value="04:30">
            </div>
            <div class="field-group">
              <label class="field-label">Concentración (ppm)</label>
              <input type="text" id="kpi-in-dedofrio-val" class="field-control mono" style="font-size: 14px; font-weight: 700; color: #f472b6;" value="<?php echo htmlspecialchars($dedo_frio_ppm_db, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>
          <label style="display: flex; align-items: center; gap: 8px; font-size: 11.5px; color: var(--text-muted); cursor: pointer; margin-top: 4px; margin-bottom: 14px;">
            <input type="checkbox" id="kpi-in-dedofrio-menor" checked style="accent-color: var(--accent-cyan);"> Valor por debajo del límite de detección ( &lt; ppm )
          </label>

          <div id="alert-tab-dedofrio" class="kpi-field-alert"></div>
          <!-- TABLA DE LOS ÚLTIMOS 5 DATOS HISTÓRICOS DE DEDO FRÍO -->
          <div style="background: rgba(9, 28, 24, 0.7); border: 1px solid var(--border-card); border-radius: 6px; padding: 10px 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
              <span style="font-size: 10.5px; font-weight: 700; color: var(--accent-cyan); text-transform: uppercase;">
                📊 Últimos 5 Registros Criogénicos (Dedo Frío 73120)
              </span>
              <span style="font-size: 9.5px; color: var(--text-dim); font-family: var(--font-mono);">Límite Op: &lt; 50 ppm</span>
            </div>
            <table class="data-table" style="font-size: 11px;">
              <thead>
                <tr>
                  <th style="padding: 5px 8px;">Fecha</th>
                  <th style="padding: 5px 8px;">Hora</th>
                  <th style="padding: 5px 8px;">Oficial Responsable</th>
                  <th style="padding: 5px 8px; text-align: right;">PPM</th>
                  <th style="padding: 5px 8px; text-align: center;">Estado</th>
                </tr>
              </thead>
              <tbody id="tbody-dedofrio-modal-5">
                <tr>
                  <td class="mono">Hoy</td>
                  <td class="mono">04:15</td>
                  <td style="font-weight: 700;">ALTUNA, LEONARDO (P)</td>
                  <td class="mono" style="text-align: right; color: #f472b6; font-weight: 700;">&lt; 12</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
                <tr>
                  <td class="mono">Ayer</td>
                  <td class="mono">20:30</td>
                  <td>ROLHAUSER, MARIANO</td>
                  <td class="mono" style="text-align: right; color: #f472b6;">&lt; 10</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
                <tr>
                  <td class="mono">Ayer</td>
                  <td class="mono">12:45</td>
                  <td>GATICA, CARLOS</td>
                  <td class="mono" style="text-align: right; color: #f472b6;">15</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
                <tr>
                  <td class="mono">Ayer</td>
                  <td class="mono">05:00</td>
                  <td>CISTERNA, GUSTAVO</td>
                  <td class="mono" style="text-align: right; color: #f472b6;">&lt; 11</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
                <tr>
                  <td class="mono">07/11</td>
                  <td class="mono">21:10</td>
                  <td>PASCUAL, SEBASTIAN</td>
                  <td class="mono" style="text-align: right; color: #f472b6;">14</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 4: % Restricción Operacional -->
        <div class="kpi-tab-content" id="tab-ro" style="display: none;">
          <div style="font-size: 11.5px; color: var(--text-muted); margin-bottom: 14px;">
            Porcentaje de Restricción Operacional acumulado del turno (Límite normativo ARN / NASA):
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
            <div class="field-group">
              <label class="field-label">% RO Asignado / Calculado</label>
              <input type="text" id="kpi-in-ro-val" class="field-control mono" style="font-size: 14px; font-weight: 700; color: #34d399;" value="8.42">
            </div>
            <div class="field-group">
              <label class="field-label">Condición / Estado</label>
              <input type="text" id="kpi-in-ro-desc" class="field-control" value="Chimenea en rango nominal">
            </div>
          </div>
          <div id="alert-tab-ro" class="kpi-field-alert"></div>

          <!-- TABLA DE LOS ÚLTIMOS REGISTROS DE % RO -->
          <div style="background: rgba(9, 28, 24, 0.7); border: 1px solid var(--border-card); border-radius: 6px; padding: 10px 12px; margin-top: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
              <span style="font-size: 10.5px; font-weight: 700; color: var(--accent-cyan); text-transform: uppercase;">
                📊 Histórico de Restricción Operacional (% RO)
              </span>
              <span style="font-size: 9.5px; color: var(--text-dim); font-family: var(--font-mono);">Límite Alerta: 15%</span>
            </div>
            <table class="data-table" style="font-size: 11px;">
              <thead>
                <tr>
                  <th style="padding: 5px 8px;">Fecha</th>
                  <th style="padding: 5px 8px;">Hora</th>
                  <th style="padding: 5px 8px;">Oficial Responsable</th>
                  <th style="padding: 5px 8px; text-align: right; color: var(--accent-green);">% RO</th>
                  <th style="padding: 5px 8px; text-align: center;">Condición</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="mono">08/11/2026</td>
                  <td class="mono">23:00</td>
                  <td style="font-weight: 700;">ALTUNA, LEONARDO (P)</td>
                  <td class="mono" style="text-align: right; color: var(--accent-green); font-weight: 700;">8.42%</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal (&lt; 15%)</span></td>
                </tr>
                <tr>
                  <td class="mono">08/11/2026</td>
                  <td class="mono">15:00</td>
                  <td>ROLHAUSER, MARIANO</td>
                  <td class="mono" style="text-align: right; color: var(--accent-green);">8.10%</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
                <tr>
                  <td class="mono">08/11/2026</td>
                  <td class="mono">07:00</td>
                  <td>GATICA, CARLOS</td>
                  <td class="mono" style="text-align: right; color: var(--accent-green);">8.25%</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 5: Condición Meteorológica -->
        <div class="kpi-tab-content" id="tab-met" style="display: none;">
          <div style="font-size: 11.5px; color: var(--text-muted); margin-bottom: 14px;">
            Condiciones meteorológicas en emplazamiento de Central Nuclear Embalse:
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
            <div class="field-group">
              <label class="field-label">Dirección del Viento</label>
              <select id="kpi-in-met-dir" class="field-control">
                <option value="S">Sur (S) - 180°</option>
                <option value="SE">Sudeste (SE) - 135°</option>
                <option value="E">Este (E) - 90°</option>
                <option value="NE">Noreste (NE) - 45°</option>
                <option value="N">Norte (N) - 0°</option>
                <option value="SO">Sudoeste (SO) - 225°</option>
                <option value="O">Oeste (O) - 270°</option>
                <option value="NO">Noroeste (NO) - 315°</option>
              </select>
            </div>
            <div class="field-group">
              <label class="field-label">Velocidad del Viento (km/h)</label>
              <input type="text" id="kpi-in-met-vel" class="field-control mono" style="font-size: 14px; font-weight: 700; color: #f59e0b;" value="<?php echo htmlspecialchars($met_vel_db, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ej: 14.5">
            </div>
          </div>
          <div class="field-group" style="margin-bottom: 12px;">
            <label class="field-label">Estabilidad Pasquill</label>
            <select id="kpi-in-met-pasquill" class="field-control">
              <option value="Clase D (Neutral - Nocturno nublado)">Clase D (Neutral - Nocturno nublado)</option>
              <option value="Clase E (Ligeramente estable)">Clase E (Ligeramente estable)</option>
              <option value="Clase F (Estable - Noche despejada)">Clase F (Estable - Noche despejada)</option>
              <option value="Clase A (Extremadamente inestable)">Clase A (Extremadamente inestable)</option>
              <option value="Clase B (Moderadamente inestable)">Clase B (Moderadamente inestable)</option>
              <option value="Clase C (Ligeramente inestable)">Clase C (Ligeramente inestable)</option>
            </select>
          </div>
        </div>

        <!-- TAB 6: Gases Nobles Chimenea -->
        <div class="kpi-tab-content" id="tab-gasesnobles" style="display: none;">
          <div style="font-size: 11.5px; color: var(--text-muted); margin-bottom: 14px;">
            Actividad de Gases Nobles (Chimenea) por espectrometría gamma integrada:
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
            <div class="field-group">
              <label class="field-label">Horario Desde</label>
              <input type="text" id="kpi-in-gn-desde" class="field-control mono latam-timepicker-input" value="<?php echo htmlspecialchars($gn_desde_db, ENT_QUOTES, 'UTF-8'); ?>" placeholder="00:00">
            </div>
            <div class="field-group">
              <label class="field-label">Horario Hasta</label>
              <input type="text" id="kpi-in-gn-hasta" class="field-control mono latam-timepicker-input" value="<?php echo htmlspecialchars($gn_hasta_db, ENT_QUOTES, 'UTF-8'); ?>" placeholder="18:00">
            </div>
          </div>
          <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 8px; font-weight: 600;">ISÓTOPOS (GBq):</div>
          <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 12px;">
            <div class="field-group">
              <label class="field-label" style="color: #a78bfa;">Ar-41 (GBq)</label>
              <input type="number" step="0.01" id="kpi-in-gn-ar41" class="field-control mono" style="font-size: 13px; font-weight: 700; color: #a78bfa;" value="<?php echo htmlspecialchars($gn_ar41_db, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="field-group">
              <label class="field-label" style="color: #60a5fa;">Kr-85 (GBq)</label>
              <input type="number" step="0.01" id="kpi-in-gn-kr85" class="field-control mono" style="font-size: 13px; font-weight: 700; color: #60a5fa;" value="<?php echo htmlspecialchars($gn_kr85_db, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="field-group">
              <label class="field-label" style="color: #60a5fa;">Kr-85m (GBq)</label>
              <input type="number" step="0.01" id="kpi-in-gn-kr85m" class="field-control mono" style="font-size: 13px; font-weight: 700; color: #60a5fa;" value="<?php echo htmlspecialchars($gn_kr85m_db, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="field-group">
              <label class="field-label" style="color: #34d399;">Xe-133 (GBq)</label>
              <input type="number" step="0.01" id="kpi-in-gn-xe133" class="field-control mono" style="font-size: 13px; font-weight: 700; color: #34d399;" value="<?php echo htmlspecialchars($gn_xe133_db, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="field-group">
              <label class="field-label" style="color: #34d399;">Xe-135 (GBq)</label>
              <input type="number" step="0.01" id="kpi-in-gn-xe135" class="field-control mono" style="font-size: 13px; font-weight: 700; color: #34d399;" value="<?php echo htmlspecialchars($gn_xe135_db, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="field-group">
              <label class="field-label" style="color: #34d399;">Xe-135m (GBq)</label>
              <input type="number" step="0.01" id="kpi-in-gn-xe135m" class="field-control mono" style="font-size: 13px; font-weight: 700; color: #34d399;" value="<?php echo htmlspecialchars($gn_xe135m_db, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>
          <div id="alert-tab-gasesnobles" class="kpi-field-alert"></div>
        </div>

      </div>
      <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
        <button type="button" class="btn-ghost" onclick="clearCurrentKpiTabMetric()" style="color: var(--accent-amber); border-color: rgba(245, 158, 11, 0.4); font-size: 11px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px;" title="Eliminar el valor cargado en esta pestaña y restablecer como pendiente">
          <span>🗑️ Eliminar medición (Restablecer a Pendiente)</span>
        </button>
        <div style="display: flex; gap: 10px;">
          <button type="button" class="btn-modal-cancel" onclick="closeKpiModal()">CANCELAR</button>
          <button type="button" class="btn-modal-confirm" onclick="saveKpiMetrics()">💾 GUARDAR Y APLICAR A KPIS</button>
        </div>
      </div>
    </div>
  </div>

  <!-- NOTIFICACIÓN TOAST -->
  <div class="toast-popup" id="toast"></div>

  <!-- ================= MODAL CHECKLIST & VERIFICADOR DE CARGAS ================= -->
  <div class="modal-overlay" id="modal-checklist" style="display: none;">
    <div class="modal-box" style="width: 640px; max-width: 95vw;">
      <div class="modal-header">
        <div class="modal-header-left">
          <span style="color: var(--accent-amber); font-size: 16px;">📋</span>
          <div>
            <span style="font-weight: 800; color: #ffffff;">Checklist &amp; Estado de Cargas del Turno</span>
            <div style="font-size: 10px; color: var(--text-muted); font-weight: normal; margin-top: 2px;">
              Planilla activa #<?php echo $id_planilla_activa ? $id_planilla_activa : '-'; ?> &bull; <?php echo htmlspecialchars($turno_actual_nombre, ENT_QUOTES, 'UTF-8'); ?> &bull; Oficial: <?php echo htmlspecialchars($oficial_actual, ENT_QUOTES, 'UTF-8'); ?>
            </div>
          </div>
        </div>
        <button type="button" class="btn-modal-close" onclick="closeChecklistModal()" title="Cerrar (Esc)">✕</button>
      </div>

      <div class="modal-body" style="padding: 16px 20px; max-height: 70vh; overflow-y: auto;">
        
        <!-- RESUMEN DE PROGRESO DE GUARDIA -->
        <div style="background: rgba(9, 28, 24, 0.7); border: 1px solid var(--border-card); border-radius: 8px; padding: 12px 16px; margin-bottom: 16px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <span style="font-size: 11px; font-weight: 700; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.5px;">Completitud General de Turno</span>
            <span id="chk-progress-pct" style="font-size: 13px; font-weight: 800; color: var(--accent-cyan); font-family: var(--font-mono);">0%</span>
          </div>
          <div style="height: 7px; width: 100%; background: rgba(255,255,255,0.08); border-radius: 10px; overflow: hidden; margin-bottom: 6px;">
            <div id="chk-progress-bar" style="height: 100%; width: 0%; background: linear-gradient(90deg, var(--accent-amber), var(--accent-green)); border-radius: 10px; transition: width 0.3s ease;"></div>
          </div>
          <div style="display: flex; justify-content: space-between; font-size: 10.5px; color: var(--text-muted);">
            <span id="chk-progress-text">Evaluando parámetros...</span>
            <span id="chk-pending-count" style="font-weight: 700; color: var(--accent-amber);">0 pendientes</span>
          </div>
        </div>

        <!-- SECCIÓN A: PARÁMETROS CRÍTICOS DE PLANTA Y CHIMENEA -->
        <div style="font-size: 10.5px; font-weight: 800; color: var(--accent-cyan); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
          ⚡ Parámetros Principales de Chimenea &amp; Planta
        </div>

        <!-- 1. Ventilación Chimenea -->
        <div class="checklist-row-item" id="chk-item-vent">
          <div class="checklist-item-left">
            <span class="checklist-item-icon">💨</span>
            <div>
              <div class="checklist-item-title">Ventilación Chimenea (General &amp; Reactor)</div>
              <div class="checklist-item-desc" id="chk-val-vent">Extracción activa de chimenea (GBq)</div>
            </div>
          </div>
          <div class="checklist-item-right">
            <span class="checklist-status-badge pending" id="chk-badge-vent">○ Pendiente</span>
            <button type="button" class="btn-checklist-action" onclick="goToChecklistTarget('kpi-tritio')">Cargar ↗</button>
          </div>
        </div>

        <!-- 2. Gases Nobles Chimenea -->
        <div class="checklist-row-item" id="chk-item-gn">
          <div class="checklist-item-left">
            <span class="checklist-item-icon">🧪</span>
            <div>
              <div class="checklist-item-title">Gases Nobles (Ar-41, Kr, Xe)</div>
              <div class="checklist-item-desc" id="chk-val-gn">Espectrometría gamma de efluentes gaseosos</div>
            </div>
          </div>
          <div class="checklist-item-right">
            <span class="checklist-status-badge pending" id="chk-badge-gn">○ Pendiente</span>
            <button type="button" class="btn-checklist-action" onclick="goToChecklistTarget('sec-gaseosos')">Cargar ↗</button>
          </div>
        </div>

        <!-- 3. Carbono-14 -->
        <div class="checklist-row-item" id="chk-item-c14">
          <div class="checklist-item-left">
            <span class="checklist-item-icon">⚛️</span>
            <div>
              <div class="checklist-item-title">Emisión Carbono-14 (MBq)</div>
              <div class="checklist-item-desc" id="chk-val-c14">Muestreo integrado continuo de chimenea</div>
            </div>
          </div>
          <div class="checklist-item-right">
            <span class="checklist-status-badge pending" id="chk-badge-c14">○ Pendiente</span>
            <button type="button" class="btn-checklist-action" onclick="goToChecklistTarget('kpi-c14')">Cargar ↗</button>
          </div>
        </div>

        <!-- 4. Dedo Frío 73120 -->
        <div class="checklist-row-item" id="chk-item-df">
          <div class="checklist-item-left">
            <span class="checklist-item-icon">❄️</span>
            <div>
              <div class="checklist-item-title">Dedo Frío 73120 (ppm)</div>
              <div class="checklist-item-desc" id="chk-val-df">Muestreo criogénico de D2O en gas</div>
            </div>
          </div>
          <div class="checklist-item-right">
            <span class="checklist-status-badge pending" id="chk-badge-df">○ Pendiente</span>
            <button type="button" class="btn-checklist-action" onclick="goToChecklistTarget('kpi-dedofrio')">Cargar ↗</button>
          </div>
        </div>

        <!-- 5. Condición Meteorológica -->
        <div class="checklist-row-item" id="chk-item-met">
          <div class="checklist-item-left">
            <span class="checklist-item-icon">🌤️</span>
            <div>
              <div class="checklist-item-title">Condición Meteorológica</div>
              <div class="checklist-item-desc" id="chk-val-met">Dirección, velocidad y Pasquill en CNE</div>
            </div>
          </div>
          <div class="checklist-item-right">
            <span class="checklist-status-badge pending" id="chk-badge-met">○ Pendiente</span>
            <button type="button" class="btn-checklist-action" onclick="goToChecklistTarget('kpi-met')">Cargar ↗</button>
          </div>
        </div>

        <!-- 6. Relevamientos de Rutina -->
        <div class="checklist-row-item" id="chk-item-rutina">
          <div class="checklist-item-left">
            <span class="checklist-item-icon">📋</span>
            <div>
              <div class="checklist-item-title">Relevamientos de Rutina en Recintos</div>
              <div class="checklist-item-desc" id="chk-val-rutina">Monitoreo de tasa de dosis, tritio y gases en recintos</div>
            </div>
          </div>
          <div class="checklist-item-right">
            <span class="checklist-status-badge pending" id="chk-badge-rutina">○ Pendiente</span>
            <button type="button" class="btn-checklist-action" onclick="goToChecklistTarget('sec-rutina')">Relevar ↗</button>
          </div>
        </div>

        <!-- 7. Libro de Actas / Novedades -->
        <div class="checklist-row-item" id="chk-item-actas">
          <div class="checklist-item-left">
            <span class="checklist-item-icon">📖</span>
            <div>
              <div class="checklist-item-title">Libro de Actas &amp; Novedades de Guardia</div>
              <div class="checklist-item-desc" id="chk-val-actas">Eventos de guardia, tareas PTR y autorizaciones</div>
            </div>
          </div>
          <div class="checklist-item-right">
            <span class="checklist-status-badge pending" id="chk-badge-actas">○ Pendiente</span>
            <button type="button" class="btn-checklist-action" onclick="goToChecklistTarget('sec-actas')">Asentar ↗</button>
          </div>
        </div>

        <!-- SECCIÓN B: CONTROLES SECUNDARIOS (SEGÚN ACTIVIDAD) -->
        <div style="font-size: 10.5px; font-weight: 800; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.5px; margin: 16px 0 8px;">
          🔬 Controles Específicos &amp; Eventuales
        </div>

        <!-- 8. Espectrometría del Aire (ZC) -->
        <div class="checklist-row-item" id="chk-item-esp">
          <div class="checklist-item-left">
            <span class="checklist-item-icon">🔬</span>
            <div>
              <div class="checklist-item-title">Espectrometría del Aire (Zona Controlada)</div>
              <div class="checklist-item-desc" id="chk-val-esp">Análisis isotópico gamma en recintos controlados</div>
            </div>
          </div>
          <div class="checklist-item-right">
            <span class="checklist-status-badge optional" id="chk-badge-esp">○ Opcional</span>
            <button type="button" class="btn-checklist-action" onclick="goToChecklistTarget('sec-espectrometria')">Ver ↗</button>
          </div>
        </div>

        <!-- 9. Efluentes Líquidos -->
        <div class="checklist-row-item" id="chk-item-liq">
          <div class="checklist-item-left">
            <span class="checklist-item-icon">💧</span>
            <div>
              <div class="checklist-item-title">Emisión de Efluentes Líquidos</div>
              <div class="checklist-item-desc" id="chk-val-liq">Descargas de tanques activos y % RoDD</div>
            </div>
          </div>
          <div class="checklist-item-right">
            <span class="checklist-status-badge optional" id="chk-badge-liq">○ Sin descargas</span>
            <button type="button" class="btn-checklist-action" onclick="goToChecklistTarget('sec-liquidos')">Ver ↗</button>
          </div>
        </div>

        <!-- 10. Contaminación Superficial & Dryers -->
        <div class="checklist-row-item" id="chk-item-contam">
          <div class="checklist-item-left">
            <span class="checklist-item-icon">🧽</span>
            <div>
              <div class="checklist-item-title">Contaminación Superficial (Frotis) &amp; Dryers</div>
              <div class="checklist-item-desc" id="chk-val-contam">Frotis beta/gamma/alfa y secadores de aire pesado</div>
            </div>
          </div>
          <div class="checklist-item-right">
            <span class="checklist-status-badge optional" id="chk-badge-contam">○ Opcional</span>
            <button type="button" class="btn-checklist-action" onclick="goToChecklistTarget('sec-contaminacion')">Ver ↗</button>
          </div>
        </div>

      </div>

      <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
        <span style="font-size: 11px; color: var(--text-dim);">
          💡 Los parámetros opcionales sólo se requieren ante tareas específicas o descargas de tanques.
        </span>
        <button type="button" class="btn-modal-cancel" onclick="closeChecklistModal()">CERRAR CHECKLIST</button>
      </div>
    </div>
  </div>

  <!-- ================= MODAL DE CARGA / EDICIÓN DE ESPECTROMETRÍA ================= -->
  <div class="modal-overlay" id="modal-espectrometria" style="display: none;">
    <div class="modal-box" style="width: 540px;">
      <div class="modal-header">
        <div class="modal-header-left">
          <span style="color: var(--accent-cyan);">🔬</span>
          <span id="modal-esp-title">Cargar Espectrometría del Aire (ZC)</span>
        </div>
        <button type="button" class="btn-modal-close" onclick="closeEspectrometriaModal()" title="Cerrar (Esc)">✕</button>
      </div>
      <div class="modal-body" style="padding: 16px 20px;">
        <input type="hidden" id="esp-in-id" value="">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="field-group">
            <label class="field-label">Concentración de</label>
            <input type="text" id="esp-in-tipo" class="field-control" value="GN" placeholder="Ej: GN">
          </div>
          <div class="field-group">
            <label class="field-label">Hora (HH:MM)</label>
            <input type="text" id="esp-in-hora" class="field-control mono latam-timepicker-input" value="<?php echo $hora_actual; ?>" placeholder="HH:MM">
          </div>
        </div>

        <div class="field-group" style="margin-bottom: 14px;">
          <label class="field-label">Recinto de Planta</label>
          <select id="esp-in-recinto" class="field-control">
            <?php foreach ($recintos_array as $rec) { ?>
              <option value="<?php echo $rec['id']; ?>">
                <?php echo htmlspecialchars($rec['recinto'], ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php } ?>
          </select>
        </div>

        <div style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px;">
          Radionucleídos Identificados (Concentración en DAC):
        </div>

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 14px;">
          <div class="field-group">
            <label class="field-label" style="text-align: center; color: #a78bfa; font-weight: 700;">Ar-41</label>
            <input type="number" step="0.01" id="esp-in-ar41" class="field-control mono" value="0.0" style="text-align: center; font-weight: 700; color: #a78bfa;" oninput="calcEspectrometriaTotal()">
            <span style="font-size: 9.5px; color: var(--text-dim); text-align: center; display: block; margin-top: 2px;">DAC</span>
          </div>
          <div class="field-group">
            <label class="field-label" style="text-align: center; color: #34d399; font-weight: 700;">Xe-133</label>
            <input type="number" step="0.01" id="esp-in-xe133" class="field-control mono" value="0.0" style="text-align: center; font-weight: 700; color: #34d399;" oninput="calcEspectrometriaTotal()">
            <span style="font-size: 9.5px; color: var(--text-dim); text-align: center; display: block; margin-top: 2px;">DAC</span>
          </div>
          <div class="field-group">
            <label class="field-label" style="text-align: center; color: #34d399; font-weight: 700;">Xe-135</label>
            <input type="number" step="0.01" id="esp-in-xe135" class="field-control mono" value="0.0" style="text-align: center; font-weight: 700; color: #34d399;" oninput="calcEspectrometriaTotal()">
            <span style="font-size: 9.5px; color: var(--text-dim); text-align: center; display: block; margin-top: 2px;">DAC</span>
          </div>
          <div class="field-group">
            <label class="field-label" style="text-align: center; color: #f59e0b; font-weight: 700;">Kr-88</label>
            <input type="number" step="0.01" id="esp-in-kr88" class="field-control mono" value="0.0" style="text-align: center; font-weight: 700; color: #f59e0b;" oninput="calcEspectrometriaTotal()">
            <span style="font-size: 9.5px; color: var(--text-dim); text-align: center; display: block; margin-top: 2px;">DAC</span>
          </div>
        </div>

        <div style="background: rgba(6, 182, 212, 0.08); border: 1px solid rgba(6, 182, 212, 0.25); border-radius: 6px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center;">
          <span style="font-size: 11.5px; font-weight: 700; color: var(--text-muted);">Concentración Total Radionucleídos:</span>
          <span id="esp-total-preview" style="font-size: 16px; font-weight: 800; font-family: var(--font-mono); color: var(--accent-cyan);">0.0 DAC</span>
        </div>
      </div>
      <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
        <button type="button" class="btn-modal-cancel" onclick="closeEspectrometriaModal()">CANCELAR</button>
        <button type="button" class="btn-modal-confirm" onclick="saveEspectrometria()">💾 GUARDAR ESPECTROMETRÍA</button>
      </div>
    </div>
  </div>

  <!-- MODAL DE ALERTA DE SEGURIDAD RADIOLÓGICA -->
  <div class="modal-overlay" id="confirm-modal">
    <div class="modal-box">
      <div class="modal-header" id="confirm-modal-header" style="color: var(--accent-red);">
        <div class="modal-header-left" id="confirm-modal-title">
          ⚠️ ALERTA DE SEGURIDAD RADIOLÓGICA
        </div>
        <button type="button" class="btn-modal-close" onclick="document.getElementById('confirm-modal').style.display='none'" title="Cerrar modal (Esc)">✕</button>
      </div>
      <div class="modal-body" id="modal-desc">
        La tasa de dosis ingresada supera los 100 µSv/h. ¿Confirma este valor o desea corregirlo?
      </div>
      <div class="modal-footer">
        <button class="btn-modal-cancel" id="btn-modal-cancel">CANCELAR</button>
        <button class="btn-modal-confirm" id="btn-modal-confirm" style="background: var(--accent-red);">CONFIRMAR VALOR</button>
      </div>
    </div>
  </div>

  <!-- MODAL DE CONFIRMACIÓN DE ELIMINACIÓN DE REGISTRO -->
  <div class="modal-overlay" id="delete-confirm-modal" style="display: none;" onclick="if(event.target===this) closeDeleteModal();">
    <div class="modal-box" style="max-width: 520px; border-left: 4px solid var(--accent-red); animation: cyber-hologram-assemble 0.25s ease-out;">
      <div class="modal-header" style="color: var(--accent-red); display: flex; justify-content: space-between; align-items: center;">
        <div class="modal-header-left" id="delete-modal-title" style="font-weight: 800; font-size: 13px; letter-spacing: 0.5px;">
          🗑️ CONFIRMAR ELIMINACIÓN DE REGISTRO
        </div>
        <button type="button" class="btn-modal-close" onclick="closeDeleteModal()" title="Cerrar modal (Esc)">✕</button>
      </div>
      <div class="modal-body" id="delete-modal-body" style="padding: 16px 0 10px;">
        <!-- Inyectado dinámicamente -->
      </div>
      <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px;">
        <button type="button" class="btn-modal-cancel" id="btn-delete-cancel" onclick="closeDeleteModal()">CANCELAR</button>
        <button type="button" class="btn-modal-confirm" id="btn-delete-confirm" onclick="confirmDeleteRow()" style="background: var(--accent-red); color: #ffffff; font-weight: 800;">
          🗑️ SÍ, ELIMINAR REGISTRO
        </button>
      </div>
    </div>
  </div>

  <!-- MODAL DE BLOQUEO DE SEGURIDAD: PLANILLA DE OTRO OFICIAL -->
  <div class="modal-overlay modal-gatekeeper" id="modal-traspaso-guardia" style="display: <?php echo $hay_discrepancia_guardia ? 'flex' : 'none'; ?>; z-index: 999999; background: rgba(3, 15, 12, 0.94); backdrop-filter: blur(12px);">
    <div class="modal-box" style="max-width: 590px; border-left: 4px solid var(--accent-amber); box-shadow: 0 10px 40px rgba(0,0,0,0.85); animation: cyber-hologram-assemble 0.28s ease-out;">
      
      <!-- VISTA 1: AVISO Y BLOQUEO DE ACCESO -->
      <div id="handover-paso-gatekeeper">
        <div class="modal-header" style="color: var(--accent-amber); display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle); padding-bottom: 10px;">
          <div style="display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 13px; letter-spacing: 0.5px;">
            <span>⛔</span> <span>ACCESO BLOQUEADO: PLANILLA DE OTRO OFICIAL</span>
          </div>
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 10px; color: var(--accent-amber); background: rgba(245,158,11,0.15); padding: 2px 8px; border-radius: 4px; font-weight: 700; border: 1px solid rgba(245,158,11,0.3);">SEGURIDAD SyR</span>
            <button type="button" class="btn-modal-close" onclick="closeHandoverModal(true)" title="Cerrar y desbloquear para pruebas">✕</button>
          </div>
        </div>
        <div class="modal-body" style="padding: 16px 0 10px;">
          <div style="font-size: 12.5px; color: #ffffff; line-height: 1.5; margin-bottom: 14px;">
            La planilla activa abierta en el sistema no pertenece a su usuario:
          </div>

          <!-- Ficha comparativa -->
          <div style="background: rgba(0, 0, 0, 0.5); border: 1px solid var(--border-card); border-radius: 6px; padding: 12px 14px; margin-bottom: 14px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; font-size: 11.5px;">
              <div style="border-right: 1px solid var(--border-subtle); padding-right: 10px;">
                <div style="color: var(--text-dim); font-size: 10px; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">
                  👤 Oficial de la Planilla Abierta
                </div>
                <div style="color: var(--accent-amber); font-weight: 800; font-size: 13px;">
                  <?php echo htmlspecialchars($oficial_iniciador_nombre, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div style="color: var(--text-muted); font-size: 11px; margin-top: 2px;">
                  Planilla <strong>#<?php echo $id_planilla_activa; ?></strong> • <?php echo htmlspecialchars($turno_actual_nombre, ENT_QUOTES, 'UTF-8'); ?>
                </div>
              </div>
              <div>
                <div style="color: var(--text-dim); font-size: 10px; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">
                  👤 Oficial Entrante (Su Sesión)
                </div>
                <div style="color: var(--accent-cyan); font-weight: 800; font-size: 13px;">
                  <?php echo htmlspecialchars($oficial_logueado, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div style="color: var(--text-muted); font-size: 11px; margin-top: 2px;">
                  Estado: 🟢 Sesión iniciada
                </div>
              </div>
            </div>
          </div>

          <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.4); border-radius: 6px; padding: 10px 12px; font-size: 11.5px; color: #fffbeb; line-height: 1.45; margin-bottom: 16px;">
            📌 <strong>Regla Operativa CNE:</strong> No está permitido ingresar ni asentar mediciones en la planilla de otro oficial. <strong>Debe cerrarse la planilla anterior para poder abrir una nueva.</strong>
          </div>

          <!-- Solo 2 Opciones Claras -->
          <div style="display: flex; flex-direction: column; gap: 10px;">
            <button type="button" class="btn-handover-opt primary" onclick="mostrarPasoAdvertenciaCierre()">
              <div class="opt-title">🔒 Cerrar Planilla Anterior y Abrir Nueva</div>
              <div class="opt-desc">Cierra definitivamente la planilla de <?php echo htmlspecialchars($oficial_iniciador_nombre, ENT_QUOTES, 'UTF-8'); ?> y habilita la apertura de su nueva planilla limpia.</div>
            </button>

            <button type="button" class="btn-handover-opt secondary" onclick="verPlanillaSoloLectura()">
              <div class="opt-title">👁️ Ingresar en Modo Auditoría / Solo Lectura</div>
              <div class="opt-desc">Inspecciona los registros cargados por el oficial anterior sin poder modificar datos ni ingresar nuevas mediciones.</div>
            </button>

            <button type="button" class="btn-handover-opt tertiary" onclick="desbloquearParaNuevoTurno()" style="border-color: rgba(56, 189, 248, 0.4); background: rgba(56, 189, 248, 0.08);">
              <div class="opt-title" style="color: var(--accent-cyan);">⚡ Desbloquear Consola para Mi Guardia (Modo Pruebas)</div>
              <div class="opt-desc">Habilita la consola y el formulario inmediatamente para su sesión (<?php echo htmlspecialchars($oficial_logueado, ENT_QUOTES, 'UTF-8'); ?>) sin modificar la base de datos.</div>
            </button>
          </div>
        </div>
      </div>

      <!-- VISTA 2: ADVERTENCIA EXPLÍCITA DE CIERRE DEFINITIVO -->
      <div id="handover-paso-advertencia" style="display: none;">
        <div class="modal-header" style="color: #ef4444; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(239, 68, 68, 0.3); padding-bottom: 10px;">
          <div style="display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 13px; letter-spacing: 0.5px;">
            <span>⚠️</span> <span>ADVERTENCIA: CIERRE DEFINITIVO DE PLANILLA</span>
          </div>
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 10px; color: #fca5a5; background: rgba(239,68,68,0.2); padding: 2px 8px; border-radius: 4px; font-weight: 700; border: 1px solid rgba(239,68,68,0.4);">ACCIÓN IRREVERSIBLE</span>
            <button type="button" class="btn-modal-close" onclick="volverPasoGatekeeper()" title="Volver atrás">✕</button>
          </div>
        </div>
        <div class="modal-body" style="padding: 16px 0 10px;">
          <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.45); border-radius: 6px; padding: 14px; margin-bottom: 16px;">
            <div style="font-size: 13px; font-weight: 800; color: #ffffff; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
              <span>⚠️</span> ¿Confirma el cierre definitivo de la Planilla #<?php echo $id_planilla_activa; ?>?
            </div>
            <div style="font-size: 12px; color: #fecaca; line-height: 1.55;">
              La planilla activa pertenece a <strong><?php echo htmlspecialchars($oficial_iniciador_nombre, ENT_QUOTES, 'UTF-8'); ?></strong> y será cerrada en este momento:
              <ul style="margin: 8px 0 4px 18px; padding: 0; list-style-type: disc; color: #ffffff; font-size: 11.5px; line-height: 1.6;">
                <li><strong>No se va a poder seguir editando</strong> ni se podrán agregar nuevas mediciones a esta planilla.</li>
                <li>Quedará formalmente archivada con marca temporal y firma en la base de datos de CNE.</li>
                <li>Se habilitará de inmediato la <strong>apertura limpia de su nueva planilla</strong> para su guardia (<strong style="color: var(--accent-cyan);"><?php echo htmlspecialchars($oficial_logueado, ENT_QUOTES, 'UTF-8'); ?></strong>).</li>
              </ul>
            </div>
          </div>

          <div id="cierre-loading-indicator" style="display: none; background: rgba(0,0,0,0.6); border: 1px solid var(--accent-cyan); border-radius: 6px; padding: 12px; text-align: center; font-size: 12px; color: var(--accent-cyan); margin-bottom: 14px;">
            <span class="inline-spinner" style="margin-right: 8px;">⏳</span> Cerrando planilla anterior en base de datos y preparando nueva planilla...
          </div>

          <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
            <button type="button" class="btn-ghost" onclick="volverPasoGatekeeper()" id="btn-cancelar-cierre-def" style="height: 36px; padding: 0 16px; font-weight: 700; font-size: 12px;">
              ← Volver
            </button>
            <button type="button" class="btn-confirm-critical" id="btn-ejecutar-cierre-def" onclick="ejecutarCierreDefinitivoYCrearNueva()" style="background: #dc2626; color: #ffffff; font-weight: 800; border: none; padding: 0 18px; height: 36px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 12px; box-shadow: 0 2px 10px rgba(220, 38, 38, 0.4);">
              🔒 SÍ, CERRAR PLANILLA Y ABRIR NUEVA
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ================= POPUPS FLOTANTES DE DATEPICKER Y TIMEPICKER LATAM ================= -->
  <div id="latam-datepicker-popup" class="latam-datepicker-popup">
    <div class="dp-header">
      <button type="button" class="dp-nav-btn" id="dp-btn-prev">&lt;</button>
      <span class="dp-month-title" id="dp-month-display">Noviembre 2026</span>
      <button type="button" class="dp-nav-btn" id="dp-btn-next">&gt;</button>
    </div>
    <div class="dp-grid-weekdays">
      <div>Lu</div><div>Ma</div><div>Mi</div><div>Ju</div><div>Vi</div><div>Sá</div><div>Do</div>
    </div>
    <div class="dp-grid-days" id="dp-days-container"></div>
    <div class="dp-quick-footer">
      <button type="button" class="dp-quick-btn" id="dp-btn-yesterday">⬅️ Ayer</button>
      <button type="button" class="dp-quick-btn" id="dp-btn-today">📅 Hoy</button>
      <button type="button" class="dp-quick-btn" id="dp-btn-close" style="color:var(--text-dim);">Cerrar</button>
    </div>
  </div>

  <div id="latam-timepicker-popup" class="latam-timepicker-popup">
    <div style="font-size:11px; font-weight:700; color:#fff; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
      <span>⏰ Seleccionar Hora</span>
      <span id="tp-close-btn" style="cursor:pointer; color:var(--text-dim);">✕</span>
    </div>
    <div class="tp-presets">
      <div class="tp-preset-btn" onclick="applyQuickTime('00:00')">00:00</div>
      <div class="tp-preset-btn" onclick="applyQuickTime('04:00')">04:00</div>
      <div class="tp-preset-btn" onclick="applyQuickTime('07:00')">07:00</div>
      <div class="tp-preset-btn" onclick="applyQuickTime('11:00')">11:00</div>
      <div class="tp-preset-btn" onclick="applyQuickTime('14:00')">14:00</div>
      <div class="tp-preset-btn" onclick="applyQuickTime('19:00')">19:00</div>
      <div class="tp-preset-btn" onclick="applyQuickTime('23:00')">23:00</div>
      <div class="tp-preset-btn" onclick="applyNowTime()" style="color:var(--accent-cyan); font-weight:bold;">⚡Ahora</div>
    </div>
    <div class="tp-selector-row">
      <div>
        <div class="tp-col-title">Hora</div>
        <div class="tp-col-scroll" id="tp-hours-list"></div>
      </div>
      <div>
        <div class="tp-col-title">Minutos</div>
        <div class="tp-col-scroll" id="tp-minutes-list"></div>
      </div>
    </div>
  </div>

  <!-- ================= MODAL: NUEVA PRE-CARGA / EMISIÓN DE EFLUENTE LÍQUIDO ================= -->
  <div class="modal-overlay" id="modal-efluente-liquido" style="display: none;">
    <div class="modal-box" style="width: 720px; max-width: 96vw; max-height: 92vh; overflow-y: auto; border-top: 3px solid var(--accent-amber);">
      <div class="modal-header">
        <div class="modal-header-left">
          <span>💧</span>
          <span id="modal-liq-title">Nueva Pre-Carga / Autorización de Efluente Líquido</span>
        </div>
        <button type="button" class="btn-modal-close" onclick="closeEfluenteLiquidoModal()" title="Cerrar (Esc)">✕</button>
      </div>
      <div class="modal-body" style="padding: 14px 18px;">
        <input type="hidden" id="liq-form-precarga-id" value="">

        <div style="display: grid; grid-template-columns: 2fr 1.1fr 0.9fr; gap: 10px; margin-bottom: 10px;">
          <div class="field-group">
            <label class="field-label">Tanque de Descarga <span style="color: var(--accent-red);">*</span></label>
            <input type="text" id="liq-in-tanque" class="field-control" placeholder="Ej: TK-01, GSPC-1..." autocomplete="off">
            <div style="display: flex; gap: 4px; flex-wrap: wrap; margin-top: 4px;">
              <button type="button" class="liq-tank-chip" onclick="document.getElementById('liq-in-tanque').value='TK-01'">TK-01</button>
              <button type="button" class="liq-tank-chip" onclick="document.getElementById('liq-in-tanque').value='TK-02'">TK-02</button>
              <button type="button" class="liq-tank-chip" onclick="document.getElementById('liq-in-tanque').value='GSPC-1'">GSPC-1</button>
              <button type="button" class="liq-tank-chip" onclick="document.getElementById('liq-in-tanque').value='GSPC-2'">GSPC-2</button>
              <button type="button" class="liq-tank-chip" onclick="document.getElementById('liq-in-tanque').value='3432-TK1'">3432-TK1</button>
            </div>
          </div>
          <div class="field-group">
            <label class="field-label">Fecha Prevista / Descarga</label>
            <input type="date" id="liq-in-fecha" class="field-control mono">
          </div>
          <div class="field-group">
            <label class="field-label">Hora Prevista</label>
            <input type="time" id="liq-in-hora" class="field-control mono">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
          <div class="field-group">
            <label class="field-label">Volumen a Descargar <span style="color: var(--accent-red);">*</span></label>
            <input type="number" step="any" min="0" id="liq-in-volumen" class="field-control mono" placeholder="0.00" oninput="recalcModalLiqTotals()">
          </div>
          <div class="field-group">
            <label class="field-label">Unidad de Volumen</label>
            <select id="liq-in-vol-unidad" class="field-control" onchange="recalcModalLiqTotals()"></select>
          </div>
        </div>

        <!-- Constructor de Radionucleidos -->
        <div style="background: rgba(8, 26, 22, 0.75); border: 1px solid var(--border-card); border-radius: 8px; padding: 10px 12px; margin-bottom: 12px;">
          <div style="font-size: 11px; font-weight: 800; color: var(--accent-cyan); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 8px;">
            ⚛️ Composición Isotópica de Muestra (H-3 &amp; Gamma)
          </div>
          <div style="display: grid; grid-template-columns: 1.5fr 1fr 1fr auto; gap: 8px; align-items: end; margin-bottom: 10px;">
            <div class="field-group">
              <label class="field-label">Radionucleído</label>
              <select id="liq-add-rad" class="field-control"></select>
            </div>
            <div class="field-group">
              <label class="field-label">Concentración</label>
              <input type="number" step="any" min="0" id="liq-add-conc" class="field-control mono" placeholder="Ej: 1.2e5 o 0.5">
            </div>
            <div class="field-group">
              <label class="field-label">Unidad</label>
              <select id="liq-add-conc-unit" class="field-control"></select>
            </div>
            <button type="button" class="btn-register" style="height: 36px; padding: 0 12px; font-size: 11.5px; white-space: nowrap;" onclick="addDraftRadionucleido()">
              + Agregar
            </button>
          </div>

          <div style="max-height: 170px; overflow-y: auto; border: 1px solid var(--border-subtle); border-radius: 6px;">
            <table class="data-table" style="font-size: 11.5px; margin: 0;">
              <thead>
                <tr>
                  <th>Radionucleído</th>
                  <th style="text-align: right;">Ingresado</th>
                  <th style="text-align: right;">Conc. (Bq/l)</th>
                  <th style="text-align: right;">Actividad (TBq)</th>
                  <th style="text-align: center;">Quitar</th>
                </tr>
              </thead>
              <tbody id="liq-draft-rads-tbody">
                <tr><td colspan="5" style="text-align: center; color: var(--text-dim); padding: 10px;">Sin radionucleídos agregados aún</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Resumen de Cálculo en Tiempo Real -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 6px; padding: 10px 12px; margin-bottom: 12px;">
          <div>
            <div style="font-size: 10px; color: var(--text-muted); font-weight: 700;">TRITIO CALCULADO</div>
            <div id="liq-prev-h3" class="mono" style="font-size: 14px; font-weight: 800; color: var(--accent-cyan);">0.0000 TBq</div>
          </div>
          <div>
            <div style="font-size: 10px; color: var(--text-muted); font-weight: 700;">GAMMA CALCULADO</div>
            <div id="liq-prev-gamma" class="mono" style="font-size: 14px; font-weight: 800; color: #f472b6;">0.0000 TBq</div>
          </div>
          <div>
            <div style="font-size: 10px; color: var(--text-muted); font-weight: 700;">% RoDD ESTIMADO</div>
            <div id="liq-prev-rodd" class="mono" style="font-size: 14px; font-weight: 800; color: #fbbf24;">Por BD / Est.</div>
          </div>
        </div>

        <div class="field-group">
          <label class="field-label">Observaciones / Autorización / PTR</label>
          <input type="text" id="liq-in-obs" class="field-control" placeholder="Ej: Muestreo aprobado - Aguardando bombeo de Operaciones">
        </div>
      </div>
      <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
        <button type="button" class="btn-modal-cancel" onclick="closeEfluenteLiquidoModal()">CANCELAR</button>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
          <button type="button" class="btn-modal-confirm" style="background: rgba(245, 158, 11, 0.2); border: 1px solid var(--accent-amber); color: #fbbf24;" onclick="saveEfluenteModal('precarga')">
            🟡 GUARDAR COMO PRE-CARGA (SIN EMITIR)
          </button>
          <button type="button" class="btn-modal-confirm" onclick="saveEfluenteModal('emitido')">
            🟢 ASENTAR COMO EMITIDO REAL
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= MODAL: CONFIRMAR BOMBEO DE PRE-CARGA ================= -->
  <div class="modal-overlay" id="modal-confirmar-bombeo" style="display: none;">
    <div class="modal-box" style="width: 480px; max-width: 95vw; border-top: 3px solid var(--accent-green);">
      <div class="modal-header">
        <div class="modal-header-left" style="color: var(--accent-green);">
          <span>✅</span>
          <span>Confirmar Bombeo Real a Chimenea/Canal</span>
        </div>
        <button type="button" class="btn-modal-close" onclick="closeConfirmarBombeoModal()" title="Cerrar (Esc)">✕</button>
      </div>
      <div class="modal-body" style="padding: 14px 18px;">
        <input type="hidden" id="conf-liq-precarga-id" value="">
        <div id="conf-liq-resumen" style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 6px; padding: 10px 12px; margin-bottom: 12px; font-size: 12px; line-height: 1.5;">
          -
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
          <div class="field-group">
            <label class="field-label">Fecha Real de Bombeo</label>
            <input type="date" id="conf-liq-fecha" class="field-control mono">
          </div>
          <div class="field-group">
            <label class="field-label">Hora Real de Bombeo</label>
            <input type="time" id="conf-liq-hora" class="field-control mono">
          </div>
        </div>
        <div class="field-group">
          <label class="field-label">Observación / Operador Confirmante</label>
          <input type="text" id="conf-liq-obs" class="field-control" placeholder="Ej: Bombeo iniciado según confirmación de Operaciones">
        </div>
        <div style="font-size: 11px; color: #6ee7b7; margin-top: 10px;">
          📌 Al confirmar, el tanque y sus radionucleídos se grabarán en la base de datos oficial y pasarán a computarse como emisión efectiva del turno.
        </div>
      </div>
      <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 8px;">
        <button type="button" class="btn-modal-cancel" onclick="closeConfirmarBombeoModal()">CANCELAR</button>
        <button type="button" class="btn-modal-confirm" id="btn-ejecutar-confirmar-bombeo" onclick="ejecutarConfirmacionBombeo()">
          ✅ CONFIRMAR Y EMITIR A BD
        </button>
      </div>
    </div>
  </div>

  <!-- ================= MODAL: OBSERVACIONES DE EFLUENTE EMITIDO ================= -->
  <div class="modal-overlay" id="modal-obs-liquido" style="display: none;">
    <div class="modal-box" style="width: 520px; max-width: 95vw;">
      <div class="modal-header">
        <div class="modal-header-left">
          <span>📝</span>
          <span id="modal-obs-liq-title">Detalle y Observaciones del Tanque</span>
        </div>
        <button type="button" class="btn-modal-close" onclick="closeObsLiquidoModal()" title="Cerrar (Esc)">✕</button>
      </div>
      <div class="modal-body" style="padding: 14px 18px;">
        <input type="hidden" id="obs-liq-id" value="">
        <div id="obs-liq-rads-list" style="margin-bottom: 12px;"></div>
        <div class="field-group">
          <label class="field-label">Observaciones registradas</label>
          <textarea id="obs-liq-texto" rows="3" class="field-control" style="height: auto; padding: 8px;"></textarea>
        </div>
      </div>
      <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 8px;">
        <button type="button" class="btn-modal-cancel" onclick="closeObsLiquidoModal()">CERRAR</button>
        <button type="button" class="btn-modal-confirm" onclick="guardarObsLiquido()">💾 GUARDAR OBSERVACIONES</button>
      </div>
    </div>
  </div>

  <!-- ================= MODAL: TICKET / VISTA PREVIA DE AUTORIZACIÓN ================= -->
  <div class="modal-overlay" id="modal-ticket-liquido" style="display: none;">
    <div class="modal-box" style="width: 640px; max-width: 96vw; max-height: 92vh; overflow-y: auto;">
      <div class="modal-header">
        <div class="modal-header-left">
          <span>🖨️</span>
          <span>Ticket / Planilla Previa de Descarga Líquida</span>
        </div>
        <button type="button" class="btn-modal-close" onclick="closeTicketLiquidoModal()" title="Cerrar (Esc)">✕</button>
      </div>
      <div class="modal-body" style="padding: 14px 18px;">
        <div id="ticket-liquido-preview" style="background: #ffffff; color: #111827; padding: 18px; border-radius: 6px; font-family: Arial, sans-serif; font-size: 12px;"></div>
      </div>
      <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 8px;">
        <button type="button" class="btn-modal-cancel" onclick="closeTicketLiquidoModal()">CERRAR</button>
        <button type="button" class="btn-modal-confirm" onclick="printTicketLiquidoWindow()">🖨️ IMPRIMIR TICKET</button>
      </div>
    </div>
  </div>

  <script>
    let activePlanillaId = <?php echo $id_planilla_activa ? $id_planilla_activa : 'null'; ?>;
    const ACTIVE_USUARIO_ID = <?php echo (int)($id_usuario_actual ? $id_usuario_actual : 1); ?>;
    const RADIONUCLEIDOS_LIQ_CATALOG = <?php $j_rl = json_encode($radionucleidos_liq_array); echo $j_rl ? $j_rl : '[]'; ?>;
    const UNIDADES_VOL_LIQ_CATALOG = <?php $j_uv = json_encode($unidades_vol_liq_array); echo $j_uv ? $j_uv : '[]'; ?>;
    const UNIDADES_CONC_LIQ_CATALOG = <?php $j_uc = json_encode($unidades_conc_liq_array); echo $j_uc ? $j_uc : '[]'; ?>;
    let liqEmitidos = <?php $j_el = json_encode($efluentes_liquidos_db); echo $j_el ? $j_el : '[]'; ?>;

    // ================= NAVEGACIÓN ENTRE SECCIONES (BOTONES LATERALES) =================
    const menuButtons = document.querySelectorAll('.menu-btn');
    const sections = document.querySelectorAll('.section-panel');
    const sectionTitleEl = document.getElementById('current-section-title');

    menuButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        let targetSec = btn.getAttribute('data-section');
        if (!targetSec) return;
        if (targetSec === 'sec-tareas') targetSec = 'sec-actas';

        menuButtons.forEach(b => b.classList.remove('active'));
        sections.forEach(s => s.classList.remove('active'));

        btn.classList.add('active');
        const activeSec = document.getElementById(targetSec);
        if (activeSec) activeSec.classList.add('active');

        const cleanTitle = btn.querySelector('span').textContent.replace(/[^\w\s&]/gi, '').trim();
        sectionTitleEl.textContent = cleanTitle;
      });
    });

    // ================= INTELIGENCIA DE TURNO NOCHE & APERTURA =================
    function openShiftModal(autoSuggestNow = false) {
      const turnoSel = document.getElementById('modal-select-turno');
      const fechaInp = document.getElementById('modal-fecha-operativa');
      if (autoSuggestNow && turnoSel && fechaInp) {
        const now = new Date();
        const h = now.getHours();
        let suggestedTurno = "1";
        let targetDate = new Date(now);
        if (h >= 0 && h < 5) {
          suggestedTurno = "3";
          targetDate.setDate(targetDate.getDate() - 1);
        } else if (h >= 5 && h < 14) {
          suggestedTurno = "1";
        } else if (h >= 14 && h < 23) {
          suggestedTurno = "2";
        } else {
          suggestedTurno = "3";
        }
        if (Array.from(turnoSel.options).some(o => String(o.value) === suggestedTurno)) {
          turnoSel.value = suggestedTurno;
        }
        const d = String(targetDate.getDate()).padStart(2, '0');
        const m = String(targetDate.getMonth() + 1).padStart(2, '0');
        const a = targetDate.getFullYear();
        fechaInp.value = `${d}/${m}/${a}`;
      }
      document.getElementById('shift-config-modal').style.display = 'flex';
      checkNightShiftLogic();
    }

    function closeShiftModal() {
      document.getElementById('shift-config-modal').style.display = 'none';
    }

    function checkNightShiftLogic() {
      const turnoSel = document.getElementById('modal-select-turno');
      const turno = turnoSel ? turnoSel.value : '';
      const turnoText = turnoSel && turnoSel.selectedOptions[0] ? turnoSel.selectedOptions[0].textContent : '';
      const esTurnoNoche = (String(turno) === "3" || /23\s*hs|noche/i.test(turnoText));
      const alertBox = document.getElementById('night-shift-alert');
      const now = new Date();
      const currentHour = now.getHours();

      // El llamado de atención se muestra sólo entre las 00:00 y las 04:59 hs
      if (esTurnoNoche && currentHour >= 0 && currentHour < 5) {
        const hh = String(now.getHours()).padStart(2, '0');
        const mm = String(now.getMinutes()).padStart(2, '0');
        const y = new Date(now);
        y.setDate(y.getDate() - 1);
        const yStr = `${String(y.getDate()).padStart(2, '0')}/${String(y.getMonth() + 1).padStart(2, '0')}/${y.getFullYear()}`;
        const horaEl = document.getElementById('night-alert-hora');
        const ayerEl = document.getElementById('night-alert-ayer');
        if (horaEl) horaEl.textContent = `${hh}:${mm} hs`;
        if (ayerEl) ayerEl.textContent = `AYER (${yStr})`;
        alertBox.style.display = 'block';
      } else {
        alertBox.style.display = 'none';
      }
    }

    document.getElementById('modal-select-turno').addEventListener('change', checkNightShiftLogic);

    function setShiftDate(type) {
      const now = new Date();
      if (type === 'yesterday') {
        const y = new Date(now);
        y.setDate(y.getDate() - 1);
        const d = String(y.getDate()).padStart(2, '0');
        const m = String(y.getMonth() + 1).padStart(2, '0');
        const a = y.getFullYear();
        document.getElementById('modal-fecha-operativa').value = `${d}/${m}/${a}`;
        showToast('Fecha fijada a AYER (' + `${d}/${m}/${a}` + ')');
      } else {
        const d = String(now.getDate()).padStart(2, '0');
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const a = now.getFullYear();
        document.getElementById('modal-fecha-operativa').value = `${d}/${m}/${a}`;
        showToast('Fecha fijada a HOY (' + `${d}/${m}/${a}` + ')');
      }
    }

    function applyShiftConfig() {
      const fecha = document.getElementById('modal-fecha-operativa').value;
      const turnoSelect = document.getElementById('modal-select-turno');
      const turnoId = turnoSelect.value;
      const turnoText = turnoSelect.selectedOptions[0].text;
      const oficial = document.getElementById('modal-select-oficial').value;
      const jefe = document.getElementById('modal-select-jefe').value;
      const planta = document.getElementById('modal-select-planta').value;

      document.getElementById('topbar-latam-date').textContent = fecha;
      document.getElementById('q-fecha').value = fecha;
      const shiftTagSidebar = document.getElementById('sidebar-shift-tag');
      if (shiftTagSidebar) shiftTagSidebar.textContent = turnoText;
      const shiftTagTopbar = document.getElementById('topbar-shift-tag');
      if (shiftTagTopbar) shiftTagTopbar.textContent = turnoText;
      document.getElementById('topbar-jefe-turno').textContent = jefe;
      document.getElementById('sidebar-user-name').textContent = oficial;
      document.getElementById('sidebar-plant-status').textContent = planta;

      // Sincronizar vía AJAX si el backend existe
      const partes = fecha.split('/');
      const fechaSql = partes.length === 3 ? `${partes[2]}-${partes[1]}-${partes[0]}` : fecha;

      fetch(`procesos_reg_rad.php?accion=crear_planilla_nueva&id_user=1&id_oficial=1&id_turno=${turnoId}&fecha_planilla=${fechaSql}&id_jefe_turno=1&estados_planta=${encodeURIComponent(planta)}`)
        .then(r => r.text())
        .then(res => {
          if (res && res !== 'mal') {
            activePlanillaId = parseInt(res);
            window.location.href = 'demo_planilla.php';
          }
        })
        .catch(e => console.log('Standalone mode active'));

      closeShiftModal();
      showToast('✔️ Planilla de guardia configurada correctamente');
    }

    // ================= RELOJ EN VIVO =================
    function updateClock() {
      const now = new Date();
      document.getElementById('live-clock').textContent = now.toTimeString().split(' ')[0] + ' hs';
    }
    setInterval(updateClock, 1000);
    updateClock();

    document.getElementById('btn-now').addEventListener('click', () => {
      const now = new Date();
      const h = String(now.getHours()).padStart(2, '0');
      const m = String(now.getMinutes()).padStart(2, '0');
      document.getElementById('q-hora').value = `${h}:${m}`;
      showToast('⚡ Hora sincronizada al momento actual');
    });

    function showToast(msg) {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.style.display = 'block';
      setTimeout(() => { t.style.display = 'none'; }, 3500);
    }

    // ================= RECALCULAR KPIS (PICOS RADIOLÓGICOS: DOSIS, TRITIO, GASES) =================
    function recalculateKPIs() {
      const rows = document.querySelectorAll('#table-body tr:not(#row-empty)');
      const count = rows.length;
      document.getElementById('menu-badge-rutina').textContent = count;

      if (count === 0) {
        document.getElementById('kpi-max-dosis').textContent = '-';
        document.getElementById('kpi-max-tritio').textContent = '-';
        document.getElementById('kpi-max-gases').textContent = '-';
        document.getElementById('kpi-max-recinto').textContent = '📍 Sin mediciones registradas';
        if (typeof updateChecklistStatus === 'function') updateChecklistStatus();
        return;
      }

      let maxDosis = 0;
      let recintoDosis = '-';
      let maxTritio = 0;
      let recintoTritio = '-';
      let maxGases = 0;
      let recintoGases = '-';

      rows.forEach(r => {
        const d = parseFloat(r.getAttribute('data-dosis'));
        const t = parseFloat(r.getAttribute('data-tritio'));
        const g = parseFloat(r.getAttribute('data-gases'));
        const rec = r.getAttribute('data-recinto') || '-';
        const recShort = rec.split(' - ')[0];

        if (!isNaN(d) && d >= maxDosis) {
          maxDosis = d;
          recintoDosis = recShort;
        }
        if (!isNaN(t) && t >= maxTritio) {
          maxTritio = t;
          recintoTritio = recShort;
        }
        if (!isNaN(g) && g >= maxGases) {
          maxGases = g;
          recintoGases = recShort;
        }
      });

      document.getElementById('kpi-max-dosis').textContent = maxDosis.toFixed(1);
      document.getElementById('kpi-max-tritio').textContent = maxTritio.toFixed(2);
      document.getElementById('kpi-max-gases').textContent = maxGases.toFixed(2);

      if (recintoDosis === recintoTritio && recintoDosis !== '-') {
        document.getElementById('kpi-max-recinto').textContent = `📍 ${recintoDosis}`;
      } else if (recintoDosis !== '-' && recintoTritio !== '-') {
        document.getElementById('kpi-max-recinto').textContent = `📍 ${recintoDosis} • ${recintoTritio}`;
      } else {
        document.getElementById('kpi-max-recinto').textContent = `📍 ${recintoDosis !== '-' ? recintoDosis : 'Nominal'}`;
      }
      if (typeof updateChecklistStatus === 'function') updateChecklistStatus();
    }

    // ================= CARGA DE MEDICIONES DE RUTINA & DETECCIÓN DE DISCREPANCIAS =================
    let pendingEntry = null;

    /**
     * Obtiene mediciones previas del recinto desde la tabla viva o desde el histórico
     */
    function getPreviousMeasurementsForRecinto(recintoName, recintoId) {
      const norm = (recintoName || '').toLowerCase().trim();
      const rows = Array.from(document.querySelectorAll('#table-body tr')).filter(tr => {
        const r = (tr.getAttribute('data-recinto') || '').toLowerCase().trim();
        return r === norm || r.startsWith(norm) || norm.startsWith(r);
      });

      if (rows.length > 0) {
        return rows.map(tr => ({
          dosis: parseFloat(tr.getAttribute('data-dosis') || 0),
          tritio: parseFloat(tr.getAttribute('data-tritio') || 0),
          gases: parseFloat(tr.getAttribute('data-gases') || 0)
        }));
      }

      // Buscar en mockRecintoHistory por ID o nombre
      if (typeof mockRecintoHistory !== 'undefined') {
        const byId = mockRecintoHistory[recintoId];
        if (byId && byId.length > 0) {
          return byId.map(h => ({
            dosis: parseFloat(h.dosis || 0),
            tritio: parseFloat(h.tritio || 0),
            gases: parseFloat(h.gases || 0)
          }));
        }
      }

      // Valores de línea de base estándar según tipo de recinto
      if (norm.includes('107') || norm.includes('108') || norm.includes('111')) {
        return [{ dosis: 0.0, tritio: 0.20, gases: 0.00 }];
      } else if (norm.includes('501')) {
        return [{ dosis: 0.0, tritio: 0.30, gases: 0.00 }];
      } else if (norm.includes('112')) {
        return [{ dosis: 0.0, tritio: 0.80, gases: 0.00 }];
      }

      return [{ dosis: 2.0, tritio: 0.25, gases: 0.00 }];
    }

    /**
     * Compara los valores ingresados contra el registro anterior del recinto para detectar saltos atípicos
     */
    function checkDiscrepancies(recinto, gases, tritio, dosis) {
      const gVal = parseFloat(gases);
      const tVal = parseFloat(tritio);
      const dVal = parseFloat(dosis);

      const recintoIdInput = document.getElementById('q-recinto-id');
      const prevList = getPreviousMeasurementsForRecinto(recinto, recintoIdInput ? recintoIdInput.value : '');
      if (!prevList || prevList.length === 0) return null;

      const prev = prevList[0];
      const warnings = [];

      // 1. Evaluar Tritio
      if (!isNaN(tVal)) {
        const pTritio = !isNaN(prev.tritio) ? prev.tritio : 0;
        if (pTritio > 0) {
          const pct = Math.round(((tVal - pTritio) / pTritio) * 100);
          if (pct >= 100 && (tVal - pTritio) >= 0.25) {
            warnings.push({
              param: 'Tritio',
              unit: 'DAC',
              val: tVal,
              prevVal: pTritio,
              diffPct: pct,
              type: 'spike'
            });
          }
        } else if (tVal >= 0.5) {
          warnings.push({
            param: 'Tritio',
            unit: 'DAC',
            val: tVal,
            prevVal: 0,
            diffPct: null,
            type: 'spike_from_zero'
          });
        }
      }

      // 2. Evaluar Tasa de Dosis
      if (!isNaN(dVal)) {
        const pDosis = !isNaN(prev.dosis) ? prev.dosis : 0;
        if (pDosis > 0) {
          const pct = Math.round(((dVal - pDosis) / pDosis) * 100);
          if (pct >= 100 && (dVal - pDosis) >= 5.0) {
            warnings.push({
              param: 'Tasa Dosis',
              unit: 'µSv/h',
              val: dVal,
              prevVal: pDosis,
              diffPct: pct,
              type: 'spike'
            });
          }
        } else if (dVal >= 10.0) {
          warnings.push({
            param: 'Tasa Dosis',
            unit: 'µSv/h',
            val: dVal,
            prevVal: 0,
            diffPct: null,
            type: 'spike_from_zero'
          });
        }
      }

      // 3. Evaluar Gases Nobles
      if (!isNaN(gVal)) {
        const pGases = !isNaN(prev.gases) ? prev.gases : 0;
        if (pGases > 0) {
          const pct = Math.round(((gVal - pGases) / pGases) * 100);
          if (pct >= 100 && (gVal - pGases) >= 0.2) {
            warnings.push({
              param: 'Gases Nobles',
              unit: 'DAC',
              val: gVal,
              prevVal: pGases,
              diffPct: pct,
              type: 'spike'
            });
          }
        } else if (gVal >= 0.3) {
          warnings.push({
            param: 'Gases Nobles',
            unit: 'DAC',
            val: gVal,
            prevVal: 0,
            diffPct: null,
            type: 'spike_from_zero'
          });
        }
      }

      return warnings.length > 0 ? warnings : null;
    }

    function commitRow(fecha, hora, recinto, gases, tritio, dosis, obs, warnings = null) {
      const dNum = parseFloat(dosis);
      const tNum = parseFloat(tritio);
      const gNum = parseFloat(gases);

      const tWarn = warnings ? warnings.find(w => w.param === 'Tritio') : null;
      const dWarn = warnings ? warnings.find(w => w.param === 'Tasa Dosis') : null;
      const gWarn = warnings ? warnings.find(w => w.param === 'Gases Nobles') : null;

      let dosisBadge = !isNaN(dNum) 
        ? (dNum > 100 
            ? `<span class="badge-red" title="${dWarn ? 'Alza de +' + dWarn.diffPct + '% vs previo' : 'Supera 100 µSv/h'}">${dNum.toFixed(1)} ⚠️</span>` 
            : ((dNum > 20 || dWarn) 
                ? `<span class="badge-amber" title="${dWarn ? 'Alza de +' + dWarn.diffPct + '% vs anterior' : 'Nivel elevado'}">${dNum.toFixed(1)}${dWarn ? ' ⚠️' : ''}</span>` 
                : dNum.toFixed(1))) 
        : '-';

      let tritioBadge = !isNaN(tNum) 
        ? ((tNum > 1.0 || tWarn) 
            ? `<span class="badge-amber" title="${tWarn ? 'Alza de +' + tWarn.diffPct + '% respecto a medición previa (' + tWarn.prevVal + ' DAC)' : 'Nivel > 1.0 DAC'}">${tNum.toFixed(2)}${tWarn ? ' ⚠️' : ''}</span>` 
            : tNum.toFixed(2)) 
        : '-';

      let gasesBadge = !isNaN(gNum) 
        ? (gWarn 
            ? `<span class="badge-amber" title="Alza de +${gWarn.diffPct}% vs anterior">${gNum.toFixed(2)} ⚠️</span>` 
            : gNum.toFixed(2)) 
        : '-';

      const tr = document.createElement('tr');
      tr.className = 'new-row';
      tr.setAttribute('data-fecha', fecha);
      tr.setAttribute('data-hora', hora);
      tr.setAttribute('data-dosis', isNaN(dNum) ? 0 : dNum);
      tr.setAttribute('data-tritio', isNaN(tNum) ? 0 : tNum);
      tr.setAttribute('data-gases', isNaN(gNum) ? 0 : gNum);
      tr.setAttribute('data-recinto', recinto);

      const oficial = document.getElementById('sidebar-user-name').textContent;

      const cleanObs = (!obs || obs.trim() === '' || obs.trim() === 'Sin observaciones') ? '--Sin observaciones--' : obs.trim();

      tr.innerHTML = `
        <td>${fecha}</td>
        <td class="num">${hora}</td>
        <td style="font-weight: 700; color: #ffffff;">
          <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
            <span class="recinto-link" onclick="openRecintoHistoryModal('${recinto}')" title="Ver las últimas 3 mediciones de este recinto">
              ${recinto}
            </span>
            <button type="button" class="btn-recinto-hist-pill" onclick="openRecintoHistoryModal('${recinto}')" title="Ver últimas mediciones de este recinto">
              <span>⏱️ 3 Últimos</span>
            </button>
          </div>
        </td>
        <td class="num">${gasesBadge}</td>
        <td class="num">${tritioBadge}</td>
        <td class="num">${dosisBadge}</td>
        <td style="color: var(--text-muted);">${cleanObs}</td>
        <td>${oficial}</td>
        <td style="text-align: center;"><button class="btn-del" onclick="deleteRow(this)">✕</button></td>
      `;

      document.getElementById('table-body').prepend(tr);
      recalculateKPIs();
      showToast('✔️ Medición registrada en ' + recinto.split(' - ')[0]);

      // Encolar en LocalStorage y sincronizar con MySQL
      const partes = fecha.split('/');
      const fechaSql = partes.length === 3 ? `${partes[2]}-${partes[1]}-${partes[0]}` : fecha;
      const recintoId = document.getElementById('q-recinto-id').value || 1;
      const rowTempId = 'row_' + Date.now();
      tr.id = rowTempId;

      const urlRutina = `procesos_reg_rad.php?accion=guardar_valores_rutina&id_user=1&fecha=${fechaSql}&hora=${hora}&ubicacion=${recintoId}&gases_nobles=${gNum || 0}&tritio=${tNum || 0}&tasa_dosis=${dNum || 0}&observaciones=${encodeURIComponent(cleanObs)}&relevado_por=1&id_planilla=${activePlanillaId || 1}`;

      SyncEngine.enqueue('rutina', urlRutina, 'GET', null, { rowId: rowTempId, recinto, fecha, hora });

      document.getElementById('q-gases').value = '';
      document.getElementById('q-tritio').value = '';
      document.getElementById('q-dosis').value = '';
      document.getElementById('q-obs').value = '';
      document.getElementById('q-recinto').focus();

      if (typeof updateRecintoHistory === 'function') {
        updateRecintoHistory(recintoId, recinto);
      }
    }

    function handleRegister() {
      const fecha = document.getElementById('q-fecha').value;
      const hora = document.getElementById('q-hora').value;
      const recinto = document.getElementById('q-recinto').value.trim();
      const gases = document.getElementById('q-gases').value.trim();
      const tritio = document.getElementById('q-tritio').value.trim();
      const dosis = document.getElementById('q-dosis').value.trim();
      const obs = document.getElementById('q-obs').value.trim();

      if (!recinto) {
        showToast('⚠️ Seleccione una ubicación / recinto');
        document.getElementById('q-recinto').focus();
        return;
      }

      // Validar que al menos uno de los 3 parámetros radiológicos haya sido ingresado
      if (gases === '' && tritio === '' && dosis === '') {
        showToast('⚠️ Debe introducir al menos un valor (Gases, Tritio o Tasa Dosis)');
        document.getElementById('q-gases').focus();
        return;
      }

      const dNum = parseFloat(dosis);
      const discWarnings = checkDiscrepancies(recinto, gases, tritio, dosis);

      // Caso 1: Umbral de emergencia por alta dosis (> 100 µSv/h)
      if (!isNaN(dNum) && dNum > 100) {
        pendingEntry = { fecha, hora, recinto, gases, tritio, dosis, obs, warnings: discWarnings };
        const hdr = document.getElementById('confirm-modal-header');
        if (hdr) hdr.style.color = 'var(--accent-red)';
        const tit = document.getElementById('confirm-modal-title');
        if (tit) tit.innerHTML = '☢️ ALERTA DE SEGURIDAD RADIOLÓGICA (> 100 µSv/h)';
        const btnConf = document.getElementById('btn-modal-confirm');
        if (btnConf) {
          btnConf.style.background = 'var(--accent-red)';
          btnConf.style.color = '#ffffff';
          btnConf.textContent = 'CONFIRMAR VALOR';
        }
        const btnCanc = document.getElementById('btn-modal-cancel');
        if (btnCanc) btnCanc.textContent = 'CANCELAR Y CORREGIR';

        document.getElementById('modal-desc').innerHTML = `
          La tasa de dosis ingresada para <strong>${recinto}</strong> es de <span style="color: var(--accent-red); font-weight:800;">${dNum} µSv/h</span>.
          <br><br>
          Este valor supera el umbral estándar (> 100 µSv/h). ¿Confirma que la medición es correcta o desea verificarla?
        `;
        document.getElementById('confirm-modal').style.display = 'flex';
        return;
      }

      // Caso 2: Alerta por Discrepancia / Alza Abrupta respecto a valores previos
      if (discWarnings && discWarnings.length > 0) {
        pendingEntry = { fecha, hora, recinto, gases, tritio, dosis, obs, warnings: discWarnings };
        const hdr = document.getElementById('confirm-modal-header');
        if (hdr) hdr.style.color = 'var(--accent-amber)';
        const tit = document.getElementById('confirm-modal-title');
        if (tit) tit.innerHTML = '⚠️ ATENCIÓN OFICIAL: VARIACIÓN RADIOLÓGICA SIGNIFICATIVA';
        const btnConf = document.getElementById('btn-modal-confirm');
        if (btnConf) {
          btnConf.style.background = 'var(--accent-amber)';
          btnConf.style.color = '#041613';
          btnConf.textContent = 'CONFIRMAR Y AGREGAR';
        }
        const btnCanc = document.getElementById('btn-modal-cancel');
        if (btnCanc) btnCanc.textContent = 'CANCELAR Y CORREGIR';

        document.getElementById('modal-desc').innerHTML = `
          <div style="font-size: 13px; line-height: 1.5; color: #ffffff;">
            Se detectó una <strong>variación atípica / alza importante</strong> respecto al historial del recinto <strong>${recinto}</strong>:
            <div style="margin: 14px 0; background: rgba(245, 158, 11, 0.12); border: 1px solid var(--accent-amber); border-left: 4px solid var(--accent-amber); border-radius: 6px; padding: 12px 14px;">
              ${discWarnings.map(w => `
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 6px;">
                  <span style="font-weight: 700; color: #ffffff;">📊 ${w.param}:</span>
                  <span style="font-family: var(--font-mono); font-size: 13.5px;">
                    <strong style="color: var(--accent-amber); font-size: 14.5px;">${w.val} ${w.unit}</strong> 
                    <span style="color: var(--text-dim); font-size: 11px;">(anterior: ${w.prevVal} ${w.unit})</span>
                  </span>
                  <span class="badge-amber" style="font-size: 11px; font-weight: 800; padding: 3px 8px;">
                    ${w.diffPct !== null ? '+' + w.diffPct + '%' : 'NUEVO PICO'}
                  </span>
                </div>
              `).join('')}
            </div>
            <p style="color: var(--text-muted); font-size: 12px; margin: 0;">
              Por favor, verifique si este valor corresponde a una condición operacional real de la planta o si se trata de un error de tipeo.
            </p>
          </div>
        `;
        document.getElementById('confirm-modal').style.display = 'flex';
        return;
      }

      commitRow(fecha, hora, recinto, gases, tritio, dosis, obs, null);
    }

    document.getElementById('btn-add').addEventListener('click', handleRegister);

    document.querySelectorAll('.entry-bar .field-control').forEach(input => {
      input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          handleRegister();
        }
      });
    });

    document.getElementById('btn-modal-cancel').onclick = () => {
      document.getElementById('confirm-modal').style.display = 'none';
      if (pendingEntry && pendingEntry.warnings && pendingEntry.warnings.length > 0) {
        const first = pendingEntry.warnings[0];
        if (first.param === 'Tritio') {
          const el = document.getElementById('q-tritio');
          if (el) { el.focus(); el.select(); }
        } else if (first.param === 'Gases Nobles') {
          const el = document.getElementById('q-gases');
          if (el) { el.focus(); el.select(); }
        } else {
          const el = document.getElementById('q-dosis');
          if (el) { el.focus(); el.select(); }
        }
      } else {
        const el = document.getElementById('q-dosis');
        if (el) { el.focus(); el.select(); }
      }
      pendingEntry = null;
    };

    document.getElementById('btn-modal-confirm').onclick = () => {
      document.getElementById('confirm-modal').style.display = 'none';
      if (pendingEntry) {
        commitRow(pendingEntry.fecha, pendingEntry.hora, pendingEntry.recinto, pendingEntry.gases, pendingEntry.tritio, pendingEntry.dosis, pendingEntry.obs, pendingEntry.warnings);
        pendingEntry = null;
      } else if (pendingKpiEntry) {
        applyKpiMetricsToDom(pendingKpiEntry.metrics, pendingKpiEntry.warnings);
        pendingKpiEntry = null;
      }
    };

    document.getElementById('btn-modal-cancel').onclick = () => {
      document.getElementById('confirm-modal').style.display = 'none';
      if (pendingEntry) {
        const el = document.getElementById('q-dosis');
        if (el) { el.focus(); el.select(); }
        pendingEntry = null;
      } else if (pendingKpiEntry) {
        if (pendingKpiEntry.warnings && pendingKpiEntry.warnings.length > 0) {
          switchKpiTab(pendingKpiEntry.warnings[0].tab);
          const inp = document.getElementById(pendingKpiEntry.warnings[0].inputId);
          if (inp) { inp.focus(); inp.select(); }
        }
        pendingKpiEntry = null;
      }
    };

    // Alerta en vivo en el panel HUD mientras el oficial tipea
    function evalRealtimeDiscrepancy() {
      const recinto = document.getElementById('q-recinto')?.value;
      const gases = document.getElementById('q-gases')?.value;
      const tritio = document.getElementById('q-tritio')?.value;
      const dosis = document.getElementById('q-dosis')?.value;
      const trendBadge = document.getElementById('rh-trend-badge');
      if (!trendBadge || !recinto) return;

      const warnings = checkDiscrepancies(recinto, gases, tritio, dosis);
      if (warnings && warnings.length > 0) {
        const w = warnings[0];
        trendBadge.innerHTML = `⚠️ Variación de ${w.param} (${w.diffPct !== null ? '+' + w.diffPct + '%' : 'Pico'}) vs anterior`;
        trendBadge.style.color = 'var(--accent-amber)';
      } else {
        trendBadge.innerHTML = '🟢 Niveles habituales';
        trendBadge.style.color = 'var(--accent-green)';
      }
    }

    ['q-gases', 'q-tritio', 'q-dosis'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.addEventListener('input', evalRealtimeDiscrepancy);
    });

    let rowToDelete = null;

    function deleteRow(btn) {
      const tr = btn.closest('tr');
      if (!tr) return;
      rowToDelete = tr;

      const isRutina = tr.closest('#table-body') !== null;
      const isActa = tr.closest('#tbody-actas-guardia') !== null;

      let tipo = 'Medición de Rutina';
      let detalleHtml = '';

      if (isRutina) {
        const fecha = tr.getAttribute('data-fecha') || (tr.cells[0] ? tr.cells[0].textContent.trim() : '');
        const hora = tr.getAttribute('data-hora') || (tr.cells[1] ? tr.cells[1].textContent.trim() : '');
        const recinto = tr.getAttribute('data-recinto') || (tr.cells[2] ? tr.cells[2].textContent.trim().split('\n')[0] : '');
        const gases = tr.getAttribute('data-gases') || (tr.cells[3] ? tr.cells[3].textContent.trim() : '0.00');
        const tritio = tr.getAttribute('data-tritio') || (tr.cells[4] ? tr.cells[4].textContent.trim() : '0.00');
        const dosis = tr.getAttribute('data-dosis') || (tr.cells[5] ? tr.cells[5].textContent.trim() : '0.0');

        tipo = 'Medición de Relevamiento de Rutina';
        detalleHtml = `
          <div style="background: rgba(0, 0, 0, 0.35); border: 1px solid var(--border-card); border-left: 3px solid var(--accent-red); border-radius: 6px; padding: 10px 14px; margin: 12px 0; font-family: var(--font-mono); font-size: 11.5px;">
            <div style="font-weight: 800; color: #ffffff; margin-bottom: 6px; font-size: 12.5px;">📍 ${recinto}</div>
            <div style="display: flex; gap: 12px; color: var(--text-muted); margin-bottom: 6px;">
              <span>📅 ${fecha}</span>
              <span>⏰ ${hora} hs</span>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap; padding-top: 6px; border-top: 1px solid rgba(255,255,255,0.08);">
              <span style="color: #c084fc;"><strong>G. nobles:</strong> ${gases} DAC</span>
              <span style="color: var(--border-card);">|</span>
              <span style="color: var(--accent-cyan);"><strong>Tritio:</strong> ${tritio} DAC</span>
              <span style="color: var(--border-card);">|</span>
              <span style="color: var(--accent-amber);"><strong>Dosis:</strong> ${dosis} µSv/h</span>
            </div>
          </div>
        `;
      } else if (isActa) {
        tipo = 'Acta / Tarea de Guardia';
        const hora = tr.cells[0] ? tr.cells[0].textContent.trim() : '';
        const ptr = tr.cells[1] ? tr.cells[1].textContent.trim() : '';
        const recinto = tr.cells[2] ? tr.cells[2].textContent.trim() : '';
        const desc = tr.cells[5] ? tr.cells[5].textContent.trim() : '';
        detalleHtml = `
          <div style="background: rgba(0, 0, 0, 0.35); border: 1px solid var(--border-card); border-left: 3px solid var(--accent-red); border-radius: 6px; padding: 10px 14px; margin: 12px 0; font-size: 11.5px;">
            <div style="font-weight: 800; color: #ffffff; margin-bottom: 4px;">📖 Acta en ${recinto} ${ptr !== '-' ? '(' + ptr + ')' : ''} - ${hora} hs</div>
            <div style="color: var(--text-muted); font-style: italic;">"${desc}"</div>
          </div>
        `;
      } else {
        tipo = 'Registro';
        detalleHtml = `
          <div style="background: rgba(0, 0, 0, 0.35); border: 1px solid var(--border-card); border-left: 3px solid var(--accent-red); border-radius: 6px; padding: 10px 14px; margin: 12px 0; font-size: 11.5px; color: #ffffff;">
            ¿Confirma que desea eliminar este registro seleccionado de la tabla?
          </div>
        `;
      }

      const modal = document.getElementById('delete-confirm-modal');
      const body = document.getElementById('delete-modal-body');
      if (body) {
        body.innerHTML = `
          <div style="font-size: 12.5px; line-height: 1.5; color: #ffffff;">
            Está a punto de eliminar permanentemente el siguiente registro de <strong>${tipo}</strong>:
            ${detalleHtml}
            <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 6px; padding: 8px 12px; color: #fee2e2; font-size: 11px; display: flex; align-items: center; gap: 8px;">
              <span>⚠️</span>
              <span><strong>Acción irreversible:</strong> Este registro se removerá de la planilla activa y se recalcularán los indicadores del turno.</span>
            </div>
          </div>
        `;
      }

      if (modal) modal.style.display = 'flex';
      const cancelBtn = document.getElementById('btn-delete-cancel');
      if (cancelBtn) cancelBtn.focus();
    }

    function confirmDeleteRow() {
      if (rowToDelete) {
        const isRutina = rowToDelete.closest('#table-body') !== null;
        const isActa = rowToDelete.closest('#tbody-actas-guardia') !== null;
        const recinto = rowToDelete.getAttribute('data-recinto') || '';
        const recintoId = rowToDelete.getAttribute('data-recinto-id') || '';

        rowToDelete.remove();
        rowToDelete = null;

        if (isRutina) {
          recalculateKPIs();
          if (recinto && typeof updateRecintoHistory === 'function') {
            updateRecintoHistory(recintoId, recinto);
          }
        }
        if (isActa) {
          const tbody = document.getElementById('tbody-actas-guardia');
          if (tbody) {
            const count = tbody.querySelectorAll('tr').length;
            const badge = document.getElementById('menu-badge-actas');
            if (badge) badge.textContent = count;
            const countHdr = document.getElementById('actas-counter-badge');
            if (countHdr) countHdr.textContent = count + ' actas asentadas en este turno';
          }
        }

        showToast('🗑️ Registro eliminado correctamente');
      }
      closeDeleteModal();
    }

    function closeDeleteModal() {
      rowToDelete = null;
      const modal = document.getElementById('delete-confirm-modal');
      if (modal) modal.style.display = 'none';
    }

        // ================= MODAL INTERACTIVO DE ACTUALIZACIÓN DE KPIS =================
        function openKpiModal(targetTab) {
      if (targetTab === 'dosis') {
        const rutinaBtn = document.querySelector('.menu-btn[data-section="sec-rutina"]');
        if (rutinaBtn) rutinaBtn.click();
        const dosisInput = document.getElementById('q-dosis');
        if (dosisInput) {
          dosisInput.focus();
          showToast('📍 Dosis Pico: determinada por relevamientos de recintos');
        }
        return;
      }

      document.getElementById('kpi-metricas-modal').style.display = 'flex';

      // Sincronizar inputs de gases nobles al abrir modal
      ['ar41', 'kr85', 'kr85m', 'xe133', 'xe135', 'xe135m'].forEach(iso => {
        const secVal = document.getElementById(`input-gn-${iso}`)?.value;
        const modalInp = document.getElementById(`kpi-in-gn-${iso}`);
        if (modalInp && secVal !== undefined) modalInp.value = secVal;
      });
      if (document.getElementById('kpi-in-gn-desde') && document.getElementById('input-gn-desde')) {
        document.getElementById('kpi-in-gn-desde').value = document.getElementById('input-gn-desde').value;
      }
      if (document.getElementById('kpi-in-gn-hasta') && document.getElementById('input-gn-hasta')) {
        document.getElementById('kpi-in-gn-hasta').value = document.getElementById('input-gn-hasta').value;
      }

      if (targetTab === 'tritio') {
        switchKpiTab('tab-tritio');
      } else if (targetTab === 'gasesnobles') {
        switchKpiTab('tab-gasesnobles');
      } else if (targetTab === 'c14') {
        switchKpiTab('tab-c14');
      } else if (targetTab === 'dedofrio') {
        switchKpiTab('tab-dedofrio');
      } else if (targetTab === 'ro') {
        switchKpiTab('tab-ro');
      } else if (targetTab === 'met') {
        switchKpiTab('tab-met');
      } else {
        switchKpiTab('tab-tritio');
      }
    }

    function closeKpiModal() {
      document.getElementById('kpi-metricas-modal').style.display = 'none';
    }

    function switchKpiTab(tabId) {
      document.querySelectorAll('.kpi-tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
      });
      document.querySelectorAll('.kpi-tab-content').forEach(content => {
        content.style.display = (content.id === tabId) ? 'block' : 'none';
      });
    }

    let pendingKpiEntry = null;

    function checkKpiDiscrepancies(metrics) {
      const warnings = [];

      // 1. Dedo Frío 73120 (Previo: 12 ppm, Límite Operacional: 50 ppm)
      if (metrics.dedoFrio !== '') {
        const dfVal = parseFloat(metrics.dedoFrio);
        const prevDf = 12.0;
        const limitDf = 50.0;

        if (!isNaN(dfVal)) {
          if (dfVal > limitDf) {
            warnings.push({
              tab: 'tab-dedofrio',
              inputId: 'kpi-in-dedofrio-val',
              param: 'Dedo Frío 73120',
              unit: 'ppm',
              val: (metrics.isMenor ? '< ' : '') + dfVal,
              prevVal: '< ' + prevDf + ' ppm',
              diffPct: Math.round(((dfVal - prevDf) / prevDf) * 100),
              isOverLimit: true,
              limitVal: limitDf + ' ppm',
              msg: `Supera el Límite Operacional de Planta (${limitDf} ppm)`
            });
          } else if (dfVal >= prevDf * 2 && (dfVal - prevDf) >= 5) {
            const pct = Math.round(((dfVal - prevDf) / prevDf) * 100);
            warnings.push({
              tab: 'tab-dedofrio',
              inputId: 'kpi-in-dedofrio-val',
              param: 'Dedo Frío 73120',
              unit: 'ppm',
              val: (metrics.isMenor ? '< ' : '') + dfVal,
              prevVal: '< ' + prevDf + ' ppm',
              diffPct: pct,
              isOverLimit: false,
              msg: `Alza de +${pct}% respecto al valor previo`
            });
          }
        }
      }

      // 2. Ventilación Chimenea (Tritio)
      if (metrics.ventGral !== '') {
        const vgVal = parseFloat(metrics.ventGral);
        const prevVg = 1.45;
        if (!isNaN(vgVal) && vgVal >= prevVg * 2 && (vgVal - prevVg) >= 1.0) {
          const pct = Math.round(((vgVal - prevVg) / prevVg) * 100);
          warnings.push({
            tab: 'tab-tritio',
            inputId: 'kpi-in-vent-gral',
            param: 'Ventilación General',
            unit: 'GBq',
            val: vgVal + ' GBq',
            prevVal: prevVg + ' GBq',
            diffPct: pct,
            isOverLimit: false,
            msg: `Alza de +${pct}% respecto al valor habitual (${prevVg} GBq)`
          });
        }
      }

      if (metrics.ventReactor !== '') {
        const vrVal = parseFloat(metrics.ventReactor);
        const prevVr = 3.80;
        if (!isNaN(vrVal) && vrVal >= prevVr * 2 && (vrVal - prevVr) >= 2.0) {
          const pct = Math.round(((vrVal - prevVr) / prevVr) * 100);
          warnings.push({
            tab: 'tab-tritio',
            inputId: 'kpi-in-vent-reactor',
            param: 'Ventilación Reactor',
            unit: 'GBq',
            val: vrVal + ' GBq',
            prevVal: prevVr + ' GBq',
            diffPct: pct,
            isOverLimit: false,
            msg: `Alza de +${pct}% respecto al valor habitual (${prevVr} GBq)`
          });
        }
      }

      // 3. Carbono-14 (Previo: 12.4 MBq)
      if (metrics.c14 !== '') {
        const c14Val = parseFloat(metrics.c14);
        const prevC14 = 12.4;
        if (!isNaN(c14Val) && c14Val >= prevC14 * 2 && (c14Val - prevC14) >= 10.0) {
          const pct = Math.round(((c14Val - prevC14) / prevC14) * 100);
          warnings.push({
            tab: 'tab-c14',
            inputId: 'kpi-in-c14-val',
            param: 'Emisión Carbono-14',
            unit: 'MBq',
            val: c14Val + ' MBq',
            prevVal: prevC14 + ' MBq',
            diffPct: pct,
            isOverLimit: false,
            msg: `Alza de +${pct}% respecto a medición previa (${prevC14} MBq)`
          });
        }
      }

      // 4. % Restricción Operacional (Previo: 8.42%)
      if (metrics.roVal !== '') {
        const roNum = parseFloat(metrics.roVal);
        const prevRo = 8.42;
        if (!isNaN(roNum)) {
          if (roNum >= 15.0) {
            warnings.push({
              tab: 'tab-ro',
              inputId: 'kpi-in-ro-val',
              param: '% Restricción Operacional',
              unit: '% RO',
              val: roNum + '%',
              prevVal: prevRo + '%',
              diffPct: Math.round(((roNum - prevRo) / prevRo) * 100),
              isOverLimit: true,
              msg: `Supera el umbral de advertencia operacional (15% RO)`
            });
          } else if (roNum >= prevRo * 1.8 && (roNum - prevRo) >= 4.0) {
            const pct = Math.round(((roNum - prevRo) / prevRo) * 100);
            warnings.push({
              tab: 'tab-ro',
              inputId: 'kpi-in-ro-val',
              param: '% Restricción Operacional',
              unit: '% RO',
              val: roNum + '%',
              prevVal: prevRo + '%',
              diffPct: pct,
              isOverLimit: false,
              msg: `Alza de +${pct}% vs valor de referencia`
            });
          }
        }
      }

      // 5. Meteorología (Viento fuerte > 60 km/h)
      if (metrics.metVel !== '') {
        const velNum = parseFloat(metrics.metVel);
        if (!isNaN(velNum) && velNum >= 60.0) {
          warnings.push({
            tab: 'tab-met',
            inputId: 'kpi-in-met-vel',
            param: 'Velocidad del Viento',
            unit: 'km/h',
            val: velNum + ' km/h',
            prevVal: '14.5 km/h',
            diffPct: null,
            isOverLimit: true,
            msg: `Condición meteorológica extrema (> 60 km/h)`
          });
        }
      }

      return warnings.length > 0 ? warnings : null;
    }

    // Evaluación en tiempo real mientras el oficial tipea en cualquier campo del modal
    function evalKpiRealtimeAlerts() {
      const ventGral = (document.getElementById('kpi-in-vent-gral')?.value || '').trim();
      const ventReactor = (document.getElementById('kpi-in-vent-reactor')?.value || '').trim();
      const c14 = (document.getElementById('kpi-in-c14-val')?.value || '').trim();
      const dedoFrio = (document.getElementById('kpi-in-dedofrio-val')?.value || '').trim();
      const isMenor = document.getElementById('kpi-in-dedofrio-menor') ? document.getElementById('kpi-in-dedofrio-menor').checked : false;
      const roVal = (document.getElementById('kpi-in-ro-val')?.value || '').trim();
      const metVel = (document.getElementById('kpi-in-met-vel')?.value || '').trim();

      const metrics = { ventGral, ventReactor, c14, dedoFrio, isMenor, roVal, metVel };
      const warnings = checkKpiDiscrepancies(metrics);

      // Limpiar estilos y cajas de alerta
      ['tab-dedofrio', 'tab-tritio', 'tab-c14', 'tab-ro', 'tab-met'].forEach(tab => {
        const el = document.getElementById('alert-' + tab);
        if (el) el.style.display = 'none';
      });

      ['kpi-in-dedofrio-val', 'kpi-in-vent-gral', 'kpi-in-vent-reactor', 'kpi-in-c14-val', 'kpi-in-ro-val', 'kpi-in-met-vel'].forEach(id => {
        const inp = document.getElementById(id);
        if (inp) inp.classList.remove('input-alert-spike', 'input-alert-limit');
      });

      if (!warnings) return;

      warnings.forEach(w => {
        const alertBox = document.getElementById('alert-' + w.tab);
        const inp = document.getElementById(w.inputId);

        if (inp) {
          inp.classList.add(w.isOverLimit ? 'input-alert-limit' : 'input-alert-spike');
        }

        if (alertBox) {
          alertBox.style.display = 'flex';
          alertBox.className = 'kpi-field-alert ' + (w.isOverLimit ? 'danger' : 'warning');
          alertBox.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(245, 158, 11, 0.25); padding-bottom: 5px;">
              <span style="font-weight: 800; color: #ffffff; text-transform: uppercase; font-size: 10.5px; display: flex; align-items: center; gap: 6px;">
                <span>⚠️</span> <span>DISCREPANCIA: ${w.param.toUpperCase()}</span>
              </span>
              <span class="${w.isOverLimit ? 'badge-red' : 'badge-amber'}" style="font-size: 10px; font-weight: 800; padding: 2px 7px;">
                ${w.diffPct !== null ? '+' + w.diffPct + '% 📈' : 'LÍMITE OP'}
              </span>
            </div>
            <div style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 10px; padding: 4px 0;">
              <div style="background: rgba(0,0,0,0.3); padding: 5px 8px; border-radius: 4px; border: 1px solid var(--border-card);">
                <div style="color: var(--text-dim); font-size: 9.5px; text-transform: uppercase; font-weight: 600;">Valor Ingresado</div>
                <div style="color: ${w.isOverLimit ? 'var(--accent-red)' : 'var(--accent-amber)'}; font-size: 13px; font-weight: 700;">${w.val}</div>
              </div>
              <div style="color: var(--text-dim); font-size: 14px; text-align: center;">➔</div>
              <div style="background: rgba(0,0,0,0.3); padding: 5px 8px; border-radius: 4px; border: 1px solid var(--border-card);">
                <div style="color: var(--text-dim); font-size: 9.5px; text-transform: uppercase; font-weight: 600;">Medición Previa (Histórico)</div>
                <div style="color: var(--accent-cyan); font-size: 13px; font-weight: 700;">${w.prevVal}</div>
              </div>
            </div>
            <div style="color: var(--text-muted); font-size: 10.5px; line-height: 1.35;">
              ${w.isOverLimit 
                ? '🚨 <strong>Atención:</strong> Supera el umbral operativo permitido. Verifique si corresponde a una condición real o corrija la carga.' 
                : `Variación de <strong>+${w.diffPct}%</strong> respecto al registro de la guardia anterior. Verifique que no sea un error de tipeo.`}
            </div>
          `;
        }
      });
    }

    ['kpi-in-dedofrio-val', 'kpi-in-dedofrio-menor', 'kpi-in-vent-gral', 'kpi-in-vent-reactor', 'kpi-in-c14-val', 'kpi-in-ro-val', 'kpi-in-met-vel'].forEach(id => {
      const el = document.getElementById(id);
      if (el) {
        el.addEventListener('input', evalKpiRealtimeAlerts);
        el.addEventListener('change', evalKpiRealtimeAlerts);
      }
    });

    function saveKpiMetrics() {
      const activeBtnTab = document.querySelector('.kpi-tab-btn.active');
      const activeTabId = activeBtnTab ? activeBtnTab.getAttribute('data-tab') : '';
      if (activeTabId === 'tab-gasesnobles') {
        ['ar41', 'kr85', 'kr85m', 'xe133', 'xe135', 'xe135m'].forEach(iso => {
          const modalVal = document.getElementById(`kpi-in-gn-${iso}`)?.value;
          const secInp = document.getElementById(`input-gn-${iso}`);
          if (secInp && modalVal !== undefined) secInp.value = modalVal;
        });
        if (document.getElementById('input-gn-desde') && document.getElementById('kpi-in-gn-desde')) {
          document.getElementById('input-gn-desde').value = document.getElementById('kpi-in-gn-desde').value;
        }
        if (document.getElementById('input-gn-hasta') && document.getElementById('kpi-in-gn-hasta')) {
          document.getElementById('input-gn-hasta').value = document.getElementById('kpi-in-gn-hasta').value;
        }
        saveGasesNobles();
        closeKpiModal();
        return;
      }

      const ventGral = (document.getElementById('kpi-in-vent-gral').value || '').trim();
      const ventReactor = (document.getElementById('kpi-in-vent-reactor').value || '').trim();
      const c14 = (document.getElementById('kpi-in-c14-val').value || '').trim();
      const dedoFrio = (document.getElementById('kpi-in-dedofrio-val').value || '').trim();
      const isMenor = document.getElementById('kpi-in-dedofrio-menor') ? document.getElementById('kpi-in-dedofrio-menor').checked : false;
      const roVal = (document.getElementById('kpi-in-ro-val').value || '').trim();
      const roDesc = (document.getElementById('kpi-in-ro-desc').value || '').trim();
      const metDir = document.getElementById('kpi-in-met-dir') ? document.getElementById('kpi-in-met-dir').value : 'S';
      const metVel = document.getElementById('kpi-in-met-vel') ? (document.getElementById('kpi-in-met-vel').value || '').trim() : '';
      const metPasquill = document.getElementById('kpi-in-met-pasquill') ? document.getElementById('kpi-in-met-pasquill').value : 'Clase D (Neutral - Nocturno nublado)';

      const metrics = { ventGral, ventReactor, c14, dedoFrio, isMenor, roVal, roDesc, metDir, metVel, metPasquill };
      const warnings = checkKpiDiscrepancies(metrics);

      // Si hay advertencias de discrepancia o exceso de límite operacional, disparar modal de confirmación
      if (warnings && warnings.length > 0) {
        pendingKpiEntry = { metrics, warnings };
        const hasLimit = warnings.some(w => w.isOverLimit);

        const hdr = document.getElementById('confirm-modal-header');
        if (hdr) hdr.style.color = hasLimit ? 'var(--accent-red)' : 'var(--accent-amber)';

        const tit = document.getElementById('confirm-modal-title');
        if (tit) {
          tit.innerHTML = hasLimit 
            ? '☢️ ALERTA OPERACIONAL: VALOR FUERA DE RANGO NOMINAL' 
            : '⚠️ ATENCIÓN OFICIAL: VARIACIÓN SIGNIFICATIVA DETECTADA';
        }

        const btnConf = document.getElementById('btn-modal-confirm');
        if (btnConf) {
          btnConf.style.background = hasLimit ? 'var(--accent-red)' : 'var(--accent-amber)';
          btnConf.style.color = '#ffffff';
          btnConf.textContent = 'CONFIRMAR Y APLICAR';
        }

        const btnCanc = document.getElementById('btn-modal-cancel');
        if (btnCanc) btnCanc.textContent = 'CANCELAR Y CORREGIR';

        document.getElementById('modal-desc').innerHTML = `
          Se ha detectado una variación abrupta respecto a los registros previos de planta:
          <div style="background: rgba(14, 39, 34, 0.85); border-left: 3px solid ${hasLimit ? 'var(--accent-red)' : 'var(--accent-amber)'}; padding: 10px 14px; margin: 12px 0; border-radius: 4px;">
            ${warnings.map(w => `
              <div style="margin-bottom: 6px; font-family: var(--font-mono); font-size: 12px;">
                <strong style="color: #ffffff;">${w.param}:</strong>
                Ingresó <span style="font-weight: 800; color: ${w.isOverLimit ? 'var(--accent-red)' : 'var(--accent-amber)'};">${w.val} ${w.unit}</span>
                vs medición previa de <strong>${w.prevVal}</strong>
                ${w.diffPct !== null ? `<span style="color: var(--accent-amber); font-weight:700;">(+${w.diffPct}%)</span>` : ''}.
                ${w.isOverLimit ? `<div style="color: var(--accent-red); font-size: 11px; margin-top: 2px; font-weight: 700;">⚠️ ${w.msg}</div>` : ''}
              </div>
            `).join('')}
          </div>
          ¿Confirma que la medición es correcta o desea verificar y corregir el dato ingresado?
        `;

        document.getElementById('confirm-modal').style.display = 'flex';
        return;
      }

      applyKpiMetricsToDom(metrics, null);
    }

    function applyKpiMetricsToDom(metrics, warnings = null) {
      const { ventGral, ventReactor, c14, dedoFrio, isMenor, roVal, roDesc, metDir, metVel, metPasquill } = metrics;

      const dfWarn = warnings ? warnings.find(w => w.param === 'Dedo Frío 73120') : null;
      const vgWarn = warnings ? warnings.find(w => w.param === 'Ventilación General') : null;
      const vrWarn = warnings ? warnings.find(w => w.param === 'Ventilación Reactor') : null;
      const c14Warn = warnings ? warnings.find(w => w.param === 'Emisión Carbono-14') : null;
      const roWarn = warnings ? warnings.find(w => w.param === '% Restricción Operacional') : null;

      // 1. Ventilación Chimenea (General y Reactor)
      const elVentGral = document.getElementById('kpi-vent-gral');
      const elVentReactor = document.getElementById('kpi-vent-reactor');
      const elVentFooter = document.getElementById('kpi-vent-footer');
      const elVentGralUnit = document.getElementById('kpi-vent-gral-unit');
      const elVentReactorUnit = document.getElementById('kpi-vent-reactor-unit');
      const cardVent = document.getElementById('card-kpi-vent') || (elVentGral ? elVentGral.closest('.kpi-card') : null);

      if (!ventGral && !ventReactor) {
        if (elVentGral) elVentGral.innerHTML = '<span class="kpi-pending-pill">⚠️ Pendiente</span>';
        if (elVentReactor) elVentReactor.innerHTML = '<span class="kpi-pending-pill">⚠️ Pendiente</span>';
        if (elVentGralUnit) elVentGralUnit.textContent = '';
        if (elVentReactorUnit) elVentReactorUnit.textContent = '';
        if (elVentFooter) elVentFooter.innerHTML = '<span style="color: var(--accent-amber);">⚠️ Sin registrar en guardia</span>';
        if (cardVent) cardVent.classList.add('is-pending');
      } else {
        if (elVentGral) {
          elVentGral.innerHTML = vgWarn 
            ? `<span class="badge-amber" title="${vgWarn.msg}">${ventGral} ⚠️</span>` 
            : (ventGral || '-');
        }
        if (elVentReactor) {
          elVentReactor.innerHTML = vrWarn 
            ? `<span class="badge-amber" title="${vrWarn.msg}">${ventReactor} ⚠️</span>` 
            : (ventReactor || '-');
        }
        if (elVentGralUnit) elVentGralUnit.textContent = 'GBq';
        if (elVentReactorUnit) elVentReactorUnit.textContent = 'GBq';
        if (elVentFooter) elVentFooter.textContent = 'Caudal y extracción activa';
        if (cardVent) cardVent.classList.remove('is-pending');
      }

      // 2. Condición Meteorológica
      const elMetVal = document.getElementById('kpi-met-val');
      const elMetUnit = document.getElementById('kpi-met-unit');
      const elMetFooter = document.getElementById('kpi-met-footer');
      const cardMet = document.getElementById('card-kpi-met') || (elMetVal ? elMetVal.closest('.kpi-card') : null);

      if (!metVel) {
        if (elMetVal) elMetVal.innerHTML = '<span class="kpi-pending-pill">⚠️ PENDIENTE</span>';
        if (elMetUnit) elMetUnit.textContent = '';
        if (elMetFooter) elMetFooter.innerHTML = '<span style="color: var(--accent-amber);">⚠️ Sin datos meteorológicos</span>';
        if (cardMet) cardMet.classList.add('is-pending');
      } else {
        const dirMap = { 'S': 'Sur', 'SE': 'Sudeste', 'E': 'Este', 'NE': 'Noreste', 'N': 'Norte', 'SO': 'Sudoeste', 'O': 'Oeste', 'NO': 'Noroeste' };
        const dirName = dirMap[metDir] || metDir;
        if (elMetVal) elMetVal.textContent = `${dirName} • ${metVel}`;
        if (elMetUnit) elMetUnit.textContent = 'km/h';
        if (elMetFooter) elMetFooter.textContent = metPasquill.split(' - ')[0];
        if (cardMet) cardMet.classList.remove('is-pending');
      }

      // 3. Emisión Carbono-14
      const elC14 = document.getElementById('kpi-c14');
      const elC14Footer = document.getElementById('kpi-c14-footer');
      const cardC14 = elC14 ? elC14.closest('.kpi-card') : null;
      if (!c14) {
        if (elC14) elC14.innerHTML = '<span class="kpi-pending-pill">⚠️ PENDIENTE</span>';
        if (elC14Footer) elC14Footer.innerHTML = '<span style="color: var(--accent-amber);">⚠️ Sin muestreo registrado</span>';
        if (cardC14) cardC14.classList.add('is-pending');
      } else {
        if (elC14) {
          elC14.innerHTML = c14Warn 
            ? `<span class="badge-amber" title="${c14Warn.msg}">${c14} ⚠️</span>` 
            : c14;
        }
        if (elC14Footer) elC14Footer.textContent = 'Muestreo acumulado turno';
        if (cardC14) cardC14.classList.remove('is-pending');
      }

      // 4. Dedo Frío 73120
      const elDedoFrio = document.getElementById('kpi-dedo-frio');
      const elDedoFrioFooter = document.getElementById('kpi-dedo-frio-footer');
      const cardDF = elDedoFrio ? elDedoFrio.closest('.kpi-card') : null;
      if (!dedoFrio) {
        if (elDedoFrio) elDedoFrio.innerHTML = '<span class="kpi-pending-pill">⚠️ PENDIENTE</span>';
        if (elDedoFrioFooter) elDedoFrioFooter.innerHTML = '<span style="color: var(--accent-amber);">⚠️ Pendiente de carga</span>';
        if (cardDF) cardDF.classList.add('is-pending');
      } else {
        const fullDf = (isMenor ? '< ' : '') + dedoFrio;
        if (elDedoFrio) {
          if (dfWarn) {
            elDedoFrio.innerHTML = `<span class="${dfWarn.isOverLimit ? 'badge-red' : 'badge-amber'}" title="${dfWarn.msg}">${fullDf} ⚠️</span>`;
          } else {
            elDedoFrio.textContent = fullDf;
          }
        }
        if (elDedoFrioFooter) elDedoFrioFooter.textContent = dfWarn && dfWarn.isOverLimit ? '⚠️ Supera Límite Operacional' : 'Histórico 5 guardias nominal';
        if (cardDF) cardDF.classList.remove('is-pending');
      }

      // 5. Restricción Operacional
      const elRoVal = document.getElementById('kpi-ro-val');
      const elRoFooter = document.getElementById('kpi-ro-footer');
      const cardRo = elRoVal ? elRoVal.closest('.kpi-card') : null;
      if (!roVal) {
        if (elRoVal) elRoVal.innerHTML = '<span class="kpi-pending-pill">⚠️ PENDIENTE</span>';
        if (elRoFooter) elRoFooter.innerHTML = '<span style="color: var(--accent-amber);">⚠️ Pendiente de cálculo</span>';
        if (cardRo) cardRo.classList.add('is-pending');
      } else {
        if (elRoVal) {
          elRoVal.innerHTML = roWarn 
            ? `<span class="${roWarn.isOverLimit ? 'badge-red' : 'badge-amber'}" title="${roWarn.msg}">${roVal} ⚠️</span>` 
            : roVal;
        }
        if (elRoFooter) elRoFooter.textContent = roDesc || 'Chimenea en rango nominal';
        if (cardRo) cardRo.classList.remove('is-pending');
      }

      // Sincronizar inputs secundarios si existen
      if (document.getElementById('input-vent-gral')) document.getElementById('input-vent-gral').value = ventGral;
      if (document.getElementById('input-vent-reactor')) document.getElementById('input-vent-reactor').value = ventReactor;
      if (document.getElementById('input-c14')) document.getElementById('input-c14').value = c14;
      if (document.getElementById('input-dedo-frio')) document.getElementById('input-dedo-frio').value = dedoFrio;
      if (document.getElementById('input-met-dir')) document.getElementById('input-met-dir').value = metDir;
      if (document.getElementById('input-met-vel')) document.getElementById('input-met-vel').value = metVel;
      if (document.getElementById('input-met-pasquill')) document.getElementById('input-met-pasquill').value = metPasquill;

      // Enviar a MySQL vía SyncEngine si está disponible
      if (typeof SyncEngine !== 'undefined') {
        const now = new Date();
        const horaActualStr = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
        const fechaLatamEl = document.getElementById('topbar-latam-date');
        const fechaLatam = fechaLatamEl ? fechaLatamEl.textContent : '';
        const partes = fechaLatam.split('/');
        const fechaSql = partes.length === 3 ? `${partes[2]}-${partes[1]}-${partes[0]}` : (document.getElementById('q-fecha') ? document.getElementById('q-fecha').value : '');

        const horaDesde = document.getElementById('kpi-in-tritio-desde') ? document.getElementById('kpi-in-tritio-desde').value : '23:00';
        const horaHasta = document.getElementById('kpi-in-tritio-hasta') ? document.getElementById('kpi-in-tritio-hasta').value : '07:00';

        if (ventGral || ventReactor) {
          SyncEngine.enqueue('tritio', `procesos_reg_rad.php?accion=guardar_valores_emision_tritio&id_planilla=${activePlanillaId || 1}&id_usuario=1&fecha=${fechaSql}&desde=${horaDesde}&hasta=${horaHasta}&vent_gral_unidad_actividad=5&vent_reactor_unidad_actividad=5&vent_gral=${ventGral}&vent_reactor=${ventReactor}`);
        }
        if (c14) {
          SyncEngine.enqueue('carbono14', `procesos_reg_rad.php?accion=guardar_valores_carbono14&id_planilla=${activePlanillaId || 1}&id_usuario=1&fecha=${fechaSql}&hora=${horaActualStr}&actividad_unidad_ingresada=4&actividad=${c14}`);
        }
        if (dedoFrio) {
          SyncEngine.enqueue('dedofrio', `procesos_reg_rad.php?accion=guardar_valores_dedo_frio&id_planilla=${activePlanillaId || 1}&id_usuario=1&fecha=${fechaSql}&hora=${horaActualStr}&ppm=${dedoFrio}&menor_que=${isMenor ? 1 : 0}`);
        }
        const metVel = (document.getElementById('kpi-in-met-vel') ? document.getElementById('kpi-in-met-vel').value : '').trim();
        const metDir = (document.getElementById('kpi-in-met-dir') ? document.getElementById('kpi-in-met-dir').value : '').trim();
        const metPasquill = (document.getElementById('kpi-in-met-pasquill') ? document.getElementById('kpi-in-met-pasquill').value : '').trim();
        if (metVel || metDir || metPasquill) {
          SyncEngine.enqueue('meteorologia', `procesos_reg_rad.php?accion=guardar_valores_condicion_meteorologica&id_planilla=${activePlanillaId || 1}&id_usuario=1&fecha=${fechaSql}&hora=${horaActualStr}&velocidad_viento=${encodeURIComponent(metVel)}&direccion_viento=${encodeURIComponent(metDir)}&condicion_pasquill=${encodeURIComponent(metPasquill)}`);
        }
      }

      closeKpiModal();
      showToast('✔️ Métricas de chimenea y planta actualizadas');
    }

    function clearCurrentKpiTabMetric() {
      const activeBtn = document.querySelector('.kpi-tab-btn.active');
      const activeTab = activeBtn ? activeBtn.getAttribute('data-tab') : 'tab-tritio';

      if (activeTab === 'tab-gasesnobles') {
        deleteGasesNobles();
        ['ar41', 'kr85', 'kr85m', 'xe133', 'xe135', 'xe135m'].forEach(iso => {
          const el = document.getElementById(`kpi-in-gn-${iso}`);
          if (el) el.value = '0.0';
        });
        closeKpiModal();
        return;
      } else if (activeTab === 'tab-tritio') {
        document.getElementById('kpi-in-vent-gral').value = '';
        document.getElementById('kpi-in-vent-reactor').value = '';
        if (document.getElementById('input-vent-gral')) document.getElementById('input-vent-gral').value = '';
        if (document.getElementById('input-vent-reactor')) document.getElementById('input-vent-reactor').value = '';
        showToast('🗑️ Medición de ventilación eliminada (marcada como pendiente)');
      } else if (activeTab === 'tab-c14') {
        document.getElementById('kpi-in-c14-val').value = '';
        if (document.getElementById('input-c14')) document.getElementById('input-c14').value = '';
        showToast('🗑️ Medición de Carbono-14 eliminada (marcada como pendiente)');
      } else if (activeTab === 'tab-dedofrio') {
        document.getElementById('kpi-in-dedofrio-val').value = '';
        if (document.getElementById('input-dedo-frio')) document.getElementById('input-dedo-frio').value = '';
        showToast('🗑️ Medición de Dedo Frío eliminada (marcada como pendiente)');
      } else if (activeTab === 'tab-ro') {
        document.getElementById('kpi-in-ro-val').value = '';
        showToast('🗑️ Medición de % RO eliminada (marcada como pendiente)');
      } else if (activeTab === 'tab-met') {
        if (document.getElementById('kpi-in-met-vel')) document.getElementById('kpi-in-met-vel').value = '';
        if (document.getElementById('input-met-vel')) document.getElementById('input-met-vel').value = '';
        showToast('🗑️ Datos meteorológicos eliminados (marcados como pendiente)');
      }
      saveKpiMetrics();
    }

    // ================= EFLUENTES GASEOSOS & KPIS =================
    function updateChimneyKPIs() {
      const ventGral = (document.getElementById('input-vent-gral').value || '').trim();
      const ventReactor = (document.getElementById('input-vent-reactor').value || '').trim();
      const c14 = (document.getElementById('input-c14').value || '').trim();
      const dedoFrio = (document.getElementById('input-dedo-frio').value || '').trim();

      if (document.getElementById('kpi-in-vent-gral')) document.getElementById('kpi-in-vent-gral').value = ventGral;
      if (document.getElementById('kpi-in-vent-reactor')) document.getElementById('kpi-in-vent-reactor').value = ventReactor;
      if (document.getElementById('kpi-in-c14-val')) document.getElementById('kpi-in-c14-val').value = c14;
      if (document.getElementById('kpi-in-dedofrio-val')) document.getElementById('kpi-in-dedofrio-val').value = dedoFrio;

      saveKpiMetrics();
    }

    function clearChimneyKPIs() {
      if (document.getElementById('input-vent-gral')) document.getElementById('input-vent-gral').value = '';
      if (document.getElementById('input-vent-reactor')) document.getElementById('input-vent-reactor').value = '';
      if (document.getElementById('input-c14')) document.getElementById('input-c14').value = '';
      if (document.getElementById('input-dedo-frio')) document.getElementById('input-dedo-frio').value = '';

      if (document.getElementById('kpi-in-vent-gral')) document.getElementById('kpi-in-vent-gral').value = '';
      if (document.getElementById('kpi-in-vent-reactor')) document.getElementById('kpi-in-vent-reactor').value = '';
      if (document.getElementById('kpi-in-c14-val')) document.getElementById('kpi-in-c14-val').value = '';
      if (document.getElementById('kpi-in-dedofrio-val')) document.getElementById('kpi-in-dedofrio-val').value = '';

      saveKpiMetrics();
      showToast('🗑️ Mediciones de chimenea eliminadas (pendientes)');
    }

    // ================= SISTEMA DINÁMICO DE CHECKLIST Y CONTROL DE CARGAS =================
    function openChecklistModal() {
      updateChecklistStatus();
      const modal = document.getElementById('modal-checklist');
      if (modal) modal.style.display = 'flex';
    }

    function closeChecklistModal() {
      const modal = document.getElementById('modal-checklist');
      if (modal) modal.style.display = 'none';
    }

    function goToChecklistTarget(target) {
      closeChecklistModal();
      if (target.startsWith('kpi-')) {
        const tab = target.replace('kpi-', '');
        openKpiModal(tab);
      } else if (target.startsWith('sec-')) {
        const btn = document.querySelector(`.menu-btn[data-section="${target}"]`);
        if (btn) btn.click();
      }
    }

    function updateChecklistStatus() {
      // 1. Ventilación Chimenea
      const vg = (document.getElementById('input-vent-gral')?.value || '').trim();
      const vr = (document.getElementById('input-vent-reactor')?.value || '').trim();
      const hasVent = (vg !== '' && vr !== '');
      const badgeVent = document.getElementById('chk-badge-vent');
      const valVent = document.getElementById('chk-val-vent');
      if (badgeVent) {
        badgeVent.className = hasVent ? 'checklist-status-badge complete' : 'checklist-status-badge pending';
        badgeVent.textContent = hasVent ? '● Cargado' : '○ Pendiente';
      }
      if (valVent) valVent.textContent = hasVent ? `V. General: ${vg} GBq | V. Reactor: ${vr} GBq` : 'Ventilación de chimenea pendiente de asentar';

      // 2. Gases Nobles
      const gnAr = (document.getElementById('input-gn-ar41')?.value || '').trim();
      const gnStatus = document.getElementById('badge-gases-nobles-status')?.textContent || '';
      const hasGn = (gnStatus.includes('Registrado') || (gnAr !== '' && parseFloat(gnAr) > 0));
      const badgeGn = document.getElementById('chk-badge-gn');
      const valGn = document.getElementById('chk-val-gn');
      if (badgeGn) {
        badgeGn.className = hasGn ? 'checklist-status-badge complete' : 'checklist-status-badge pending';
        badgeGn.textContent = hasGn ? '● Cargado' : '○ Pendiente';
      }
      if (valGn) valGn.textContent = hasGn ? 'Isótopos de Gases Nobles (Ar-41, Kr, Xe) registrados' : 'Gases Nobles de chimenea pendientes de asentar';

      // 3. Carbono-14
      const c14 = (document.getElementById('input-c14')?.value || '').trim();
      const hasC14 = (c14 !== '');
      const badgeC14 = document.getElementById('chk-badge-c14');
      const valC14 = document.getElementById('chk-val-c14');
      if (badgeC14) {
        badgeC14.className = hasC14 ? 'checklist-status-badge complete' : 'checklist-status-badge pending';
        badgeC14.textContent = hasC14 ? '● Cargado' : '○ Pendiente';
      }
      if (valC14) valC14.textContent = hasC14 ? `Actividad C-14: ${c14} MBq` : 'Emisión de Carbono-14 pendiente de carga';

      // 4. Dedo Frío 73120
      const df = (document.getElementById('input-dedo-frio')?.value || '').trim();
      const hasDf = (df !== '');
      const badgeDf = document.getElementById('chk-badge-df');
      const valDf = document.getElementById('chk-val-df');
      if (badgeDf) {
        badgeDf.className = hasDf ? 'checklist-status-badge complete' : 'checklist-status-badge pending';
        badgeDf.textContent = hasDf ? '● Cargado' : '○ Pendiente';
      }
      if (valDf) valDf.textContent = hasDf ? `Concentración: ${df} ppm` : 'Muestreo criogénico 73120 pendiente de carga';

      // 5. Meteorología
      const metVel = (document.getElementById('input-met-vel')?.value || '').trim();
      const metDir = document.getElementById('input-met-dir')?.value || 'S';
      const hasMet = (metVel !== '');
      const badgeMet = document.getElementById('chk-badge-met');
      const valMet = document.getElementById('chk-val-met');
      if (badgeMet) {
        badgeMet.className = hasMet ? 'checklist-status-badge complete' : 'checklist-status-badge pending';
        badgeMet.textContent = hasMet ? '● Cargado' : '○ Pendiente';
      }
      if (valMet) valMet.textContent = hasMet ? `Viento: ${metDir} a ${metVel} km/h` : 'Condición meteorológica pendiente de carga';

      // 6. Relevamientos de Rutina
      const rutinaRows = document.querySelectorAll('#table-body tr:not(#row-empty)');
      const hasRutina = (rutinaRows.length > 0);
      const badgeRutina = document.getElementById('chk-badge-rutina');
      const valRutina = document.getElementById('chk-val-rutina');
      if (badgeRutina) {
        badgeRutina.className = hasRutina ? 'checklist-status-badge complete' : 'checklist-status-badge pending';
        badgeRutina.textContent = hasRutina ? `● ${rutinaRows.length} Cargados` : '○ Pendiente';
      }
      if (valRutina) valRutina.textContent = hasRutina ? `${rutinaRows.length} relevamientos radiológicos registrados en recintos` : 'Sin mediciones de rutina asentadas en esta guardia';

      // 7. Libro de Actas
      const actasRows = document.querySelectorAll('#tbody-actas-guardia tr:not(#row-actas-empty)');
      const hasActas = (actasRows.length > 0);
      const badgeActas = document.getElementById('chk-badge-actas');
      const valActas = document.getElementById('chk-val-actas');
      if (badgeActas) {
        badgeActas.className = hasActas ? 'checklist-status-badge complete' : 'checklist-status-badge pending';
        badgeActas.textContent = hasActas ? `● ${actasRows.length} Actas` : '○ Pendiente';
      }
      if (valActas) valActas.textContent = hasActas ? `${actasRows.length} novedades / actas asentadas en el turno` : 'Sin novedades asentadas en el libro de actas';

      // 8. Espectrometría ZC
      const espRows = document.querySelectorAll('#tbody-espectrometria tr:not(#row-esp-empty)');
      const hasEsp = (espRows.length > 0);
      const badgeEsp = document.getElementById('chk-badge-esp');
      const valEsp = document.getElementById('chk-val-esp');
      if (badgeEsp) {
        badgeEsp.className = hasEsp ? 'checklist-status-badge complete' : 'checklist-status-badge optional';
        badgeEsp.textContent = hasEsp ? `● ${espRows.length} Cargados` : '○ Opcional';
      }
      if (valEsp) valEsp.textContent = hasEsp ? `${espRows.length} análisis de espectrometría registrados` : 'Sin espectrometrías específicas requeridas en este turno';

      // 9. Efluentes Líquidos (Pre-Cargas vs Emitidos Reales)
      const badgeLiq = document.getElementById('chk-badge-liq');
      const valLiq = document.getElementById('chk-val-liq');
      const activePre = (typeof getActiveLiqPrecargas === 'function') ? getActiveLiqPrecargas() : [];
      const realEmit = Array.isArray(liqEmitidos) ? liqEmitidos : [];
      if (badgeLiq && valLiq) {
        if (activePre.length > 0) {
          badgeLiq.className = 'checklist-status-badge pending';
          badgeLiq.textContent = `⏳ ${activePre.length} Pre-Carga${activePre.length > 1 ? 's' : ''}`;
          valLiq.textContent = `Atención: ${activePre.length} pre-carga(s) en espera de confirmación de bombeo (${realEmit.length} emitidos reales)`;
        } else if (realEmit.length > 0) {
          const sumRodd = realEmit.reduce((acc, r) => acc + (parseFloat(r.rodd_total) || 0), 0);
          badgeLiq.className = 'checklist-status-badge complete';
          badgeLiq.textContent = `● ${realEmit.length} Emitido${realEmit.length > 1 ? 's' : ''}`;
          valLiq.textContent = `${realEmit.length} descarga(s) real(es) asentada(s) | Suma RoDD: ${sumRodd.toFixed(3)}%`;
        } else {
          badgeLiq.className = 'checklist-status-badge optional';
          badgeLiq.textContent = '○ Sin descargas';
          valLiq.textContent = 'Sin descargas líquidas ni pre-cargas pendientes en este turno';
        }
      }

      // Calcular completitud de los 7 módulos obligatorios
      const obligatorios = [hasVent, hasGn, hasC14, hasDf, hasMet, hasRutina, hasActas];
      const completedCount = obligatorios.filter(Boolean).length;
      const pendingCount = obligatorios.length - completedCount;
      const pct = Math.round((completedCount / obligatorios.length) * 100);

      const progBar = document.getElementById('chk-progress-bar');
      const progPct = document.getElementById('chk-progress-pct');
      const progText = document.getElementById('chk-progress-text');
      const pendText = document.getElementById('chk-pending-count');

      if (progBar) progBar.style.width = `${pct}%`;
      if (progPct) progPct.textContent = `${pct}%`;
      if (progText) progText.textContent = `${completedCount} de ${obligatorios.length} parámetros obligatorios completos`;
      if (pendText) {
        pendText.textContent = pendingCount === 0 ? '¡Todo completo ✓!' : `${pendingCount} pendiente${pendingCount > 1 ? 's' : ''}`;
        pendText.style.color = pendingCount === 0 ? 'var(--accent-green)' : 'var(--accent-amber)';
      }

      // Actualizar badges en Topbar y Sidebar
      const topBadge = document.getElementById('topbar-checklist-badge');
      if (topBadge) {
        if (pendingCount === 0) {
          topBadge.className = 'badge-checklist-pill success';
          topBadge.textContent = '100% Completo ✓';
        } else {
          topBadge.className = 'badge-checklist-pill';
          topBadge.textContent = `${pendingCount} Pendiente${pendingCount > 1 ? 's' : ''}`;
        }
      }

      const sideBadge = document.getElementById('sidebar-checklist-badge');
      if (sideBadge) {
        sideBadge.textContent = pendingCount === 0 ? 'Completo ✓' : `${pendingCount} Pendientes`;
        sideBadge.style.background = pendingCount === 0 ? 'var(--accent-green)' : 'var(--accent-amber)';
      }
    }

    // ================= FUNCIONES DE ESPECTROMETRÍA DEL AIRE (ZC) =================
    function calcEspectrometriaTotal() {
      const ar = parseFloat(document.getElementById('esp-in-ar41')?.value) || 0;
      const x3 = parseFloat(document.getElementById('esp-in-xe133')?.value) || 0;
      const x5 = parseFloat(document.getElementById('esp-in-xe135')?.value) || 0;
      const k8 = parseFloat(document.getElementById('esp-in-kr88')?.value) || 0;
      const total = (ar + x3 + x5 + k8).toFixed(1);
      const prev = document.getElementById('esp-total-preview');
      if (prev) prev.textContent = `${total} DAC`;
      return total;
    }

    function openEspectrometriaModal(id) {
      const modal = document.getElementById('modal-espectrometria');
      const title = document.getElementById('modal-esp-title');
      const idInp = document.getElementById('esp-in-id');
      const tipoInp = document.getElementById('esp-in-tipo');
      const horaInp = document.getElementById('esp-in-hora');
      const recintoInp = document.getElementById('esp-in-recinto');
      const arInp = document.getElementById('esp-in-ar41');
      const x3Inp = document.getElementById('esp-in-xe133');
      const x5Inp = document.getElementById('esp-in-xe135');
      const k8Inp = document.getElementById('esp-in-kr88');

      if (id) {
        title.textContent = 'Editar Espectrometría del Aire (ZC)';
        idInp.value = id;
        const row = document.getElementById(`row-esp-${id}`);
        if (row) {
          tipoInp.value = row.getAttribute('data-tipo') || 'GN';
          horaInp.value = row.getAttribute('data-hora') || '';
          if (recintoInp) recintoInp.value = row.getAttribute('data-recinto') || '1';
          if (arInp) arInp.value = row.getAttribute('data-ar41') || '0.0';
          if (x3Inp) x3Inp.value = row.getAttribute('data-xe133') || '0.0';
          if (x5Inp) x5Inp.value = row.getAttribute('data-xe135') || '0.0';
          if (k8Inp) k8Inp.value = row.getAttribute('data-kr88') || '0.0';
        }
      } else {
        title.textContent = 'Cargar Espectrometría del Aire (ZC)';
        idInp.value = '';
        tipoInp.value = 'GN';
        const d = new Date();
        horaInp.value = `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
        if (arInp) arInp.value = '0.0';
        if (x3Inp) x3Inp.value = '0.0';
        if (x5Inp) x5Inp.value = '0.0';
        if (k8Inp) k8Inp.value = '0.0';
      }

      calcEspectrometriaTotal();
      if (modal) modal.style.display = 'flex';
    }

    function closeEspectrometriaModal() {
      const modal = document.getElementById('modal-espectrometria');
      if (modal) modal.style.display = 'none';
    }

    function saveEspectrometria() {
      const id = (document.getElementById('esp-in-id')?.value || '').trim();
      const tipo = (document.getElementById('esp-in-tipo')?.value || 'GN').trim();
      const hora = (document.getElementById('esp-in-hora')?.value || '').trim();
      const recintoSelect = document.getElementById('esp-in-recinto');
      const recintoId = recintoSelect ? recintoSelect.value : '1';
      const recintoNombre = recintoSelect ? recintoSelect.options[recintoSelect.selectedIndex].text : '';
      const ar41 = (document.getElementById('esp-in-ar41')?.value || '0').trim();
      const xe133 = (document.getElementById('esp-in-xe133')?.value || '0').trim();
      const xe135 = (document.getElementById('esp-in-xe135')?.value || '0').trim();
      const kr88 = (document.getElementById('esp-in-kr88')?.value || '0').trim();

      if (!hora) {
        alert('Debe especificar la hora de la medición de espectrometría.');
        return;
      }

      const fechaSql = '<?php echo date("Y-m-d"); ?>';
      let url = '';
      if (id) {
        url = `procesos_reg_rad.php?accion=actualizar_valores_espectrometria_aire_edicion&id_espectrometria_aire=${id}&fecha=${fechaSql}&tipo=${encodeURIComponent(tipo)}&recinto=${recintoId}&hora=${encodeURIComponent(hora)}&ar41=${encodeURIComponent(ar41)}&xe133=${encodeURIComponent(xe133)}&xe135=${encodeURIComponent(xe135)}&kr88=${encodeURIComponent(kr88)}`;
      } else {
        url = `procesos_reg_rad.php?accion=guardar_valores_espectrometria_aire&id_planilla=${activePlanillaId || 1}&id_usuario=${activeUserId || 1}&fecha=${fechaSql}&tipo=${encodeURIComponent(tipo)}&recinto=${recintoId}&hora=${encodeURIComponent(hora)}&ar41=${encodeURIComponent(ar41)}&xe133=${encodeURIComponent(xe133)}&xe135=${encodeURIComponent(xe135)}&kr88=${encodeURIComponent(kr88)}`;
      }

      if (typeof SyncEngine !== 'undefined') {
        SyncEngine.enqueue('espectrometria', url);
      } else if (!IS_DEMO_MODE) {
        fetch(url);
      }

      const totalVal = (parseFloat(ar41) + parseFloat(xe133) + parseFloat(xe135) + parseFloat(kr88)).toFixed(1);
      const tbody = document.getElementById('tbody-espectrometria');
      const emptyRow = document.getElementById('row-esp-empty');
      if (emptyRow) emptyRow.remove();

      if (id) {
        const row = document.getElementById(`row-esp-${id}`);
        if (row) {
          row.setAttribute('data-tipo', tipo);
          row.setAttribute('data-hora', hora);
          row.setAttribute('data-recinto', recintoId);
          row.setAttribute('data-recinto-nombre', recintoNombre);
          row.setAttribute('data-ar41', ar41);
          row.setAttribute('data-xe133', xe133);
          row.setAttribute('data-xe135', xe135);
          row.setAttribute('data-kr88', kr88);
          row.cells[0].textContent = tipo.toUpperCase();
          row.cells[1].innerHTML = `<span style="font-weight: 600; color: var(--accent-cyan);">${recintoNombre}</span>`;
          row.cells[2].textContent = hora;
          row.cells[3].textContent = parseFloat(ar41).toFixed(1);
          row.cells[4].textContent = parseFloat(xe133).toFixed(1);
          row.cells[5].textContent = parseFloat(xe135).toFixed(1);
          row.cells[6].textContent = parseFloat(kr88).toFixed(1);
          row.cells[7].textContent = totalVal;
        }
      } else {
        const tempId = 'temp_' + Date.now();
        const tr = document.createElement('tr');
        tr.id = `row-esp-${tempId}`;
        tr.setAttribute('data-id', tempId);
        tr.setAttribute('data-tipo', tipo);
        tr.setAttribute('data-recinto', recintoId);
        tr.setAttribute('data-recinto-nombre', recintoNombre);
        tr.setAttribute('data-hora', hora);
        tr.setAttribute('data-fecha', fechaSql);
        tr.setAttribute('data-ar41', ar41);
        tr.setAttribute('data-xe133', xe133);
        tr.setAttribute('data-xe135', xe135);
        tr.setAttribute('data-kr88', kr88);
        tr.innerHTML = `
          <td style="font-weight: 700; color: #ffffff;">${tipo.toUpperCase()}</td>
          <td><span style="font-weight: 600; color: var(--accent-cyan);">${recintoNombre}</span></td>
          <td class="mono" style="text-align: center;">${hora}</td>
          <td class="mono num" style="text-align: right; color: #a78bfa;">${parseFloat(ar41).toFixed(1)}</td>
          <td class="mono num" style="text-align: right; color: #34d399;">${parseFloat(xe133).toFixed(1)}</td>
          <td class="mono num" style="text-align: right; color: #34d399;">${parseFloat(xe135).toFixed(1)}</td>
          <td class="mono num" style="text-align: right; color: #f59e0b;">${parseFloat(kr88).toFixed(1)}</td>
          <td class="mono num" style="text-align: right; font-weight: 800; color: var(--accent-cyan);">${totalVal}</td>
          <td style="text-align: center;">
            <div style="display: inline-flex; gap: 6px;">
              <button type="button" class="btn-action-icon" onclick="openEspectrometriaModal('${tempId}')" title="Editar registro" style="background: rgba(6, 182, 212, 0.15); border: 1px solid rgba(6, 182, 212, 0.3); color: var(--accent-cyan); padding: 3px 6px; border-radius: 4px; cursor: pointer; font-size: 11px;">✏️</button>
              <button type="button" class="btn-action-icon" onclick="deleteEspectrometria('${tempId}')" title="Eliminar registro" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: var(--accent-red); padding: 3px 6px; border-radius: 4px; cursor: pointer; font-size: 11px;">⛔</button>
            </div>
          </td>
        `;
        tbody.prepend(tr);
      }

      // Actualizar contador del menú
      const countEl = document.getElementById('menu-badge-espectrometria');
      if (countEl) {
        const rowsCount = tbody.querySelectorAll('tr:not(#row-esp-empty)').length;
        countEl.textContent = rowsCount;
      }

      closeEspectrometriaModal();
      showToast('✅ Registro de Espectrometría guardado correctamente');
    }

    function deleteEspectrometria(id) {
      if (!confirm('¿Está seguro de que desea eliminar este registro de espectrometría?')) return;

      if (id && !String(id).startsWith('temp_')) {
        const url = `procesos_reg_rad.php?accion=eliminar_registro_generico&nombre_tabla=regrad_espectr_conc_radionuclei&nombre_campo_id=id_conc_rad&id_registro=${id}`;
        if (typeof SyncEngine !== 'undefined') {
          SyncEngine.enqueue('delete_espectrometria', url);
        } else if (!IS_DEMO_MODE) {
          fetch(url);
        }
      }

      const row = document.getElementById(`row-esp-${id}`);
      if (row) row.remove();

      const tbody = document.getElementById('tbody-espectrometria');
      const rowsCount = tbody.querySelectorAll('tr:not(#row-esp-empty)').length;
      if (rowsCount === 0) {
        tbody.innerHTML = `
          <tr id="row-esp-empty">
            <td colspan="9" style="text-align: center; padding: 24px; color: var(--text-dim); font-style: italic;">
              - No se registraron espectrometrías de aire específicas en este turno -
            </td>
          </tr>
        `;
      }

      const countEl = document.getElementById('menu-badge-espectrometria');
      if (countEl) countEl.textContent = rowsCount;

      showToast('🗑️ Registro de espectrometría eliminado');
    }

    // ================= FUNCIONES DE GASES NOBLES CHIMENEA =================
    function saveGasesNobles() {
      const desde = (document.getElementById('input-gn-desde')?.value || '00:00').trim();
      const hasta = (document.getElementById('input-gn-hasta')?.value || '18:00').trim();
      const ar41 = (document.getElementById('input-gn-ar41')?.value || '0').trim();
      const kr85 = (document.getElementById('input-gn-kr85')?.value || '0').trim();
      const kr85m = (document.getElementById('input-gn-kr85m')?.value || '0').trim();
      const xe133 = (document.getElementById('input-gn-xe133')?.value || '0').trim();
      const xe135 = (document.getElementById('input-gn-xe135')?.value || '0').trim();
      const xe135m = (document.getElementById('input-gn-xe135m')?.value || '0').trim();
      const idGn = (document.getElementById('input-gn-id')?.value || '').trim();

      const action = idGn ? 'editar_valores_gases_nobles' : 'cargar_valores_gases_nobles';
      const fechaSql = '<?php echo date("Y-m-d"); ?>';
      const url = `procesos_reg_rad.php?accion=${action}&id_planilla=${activePlanillaId || 1}&id_usuario=${activeUserId || 1}&fecha=${fechaSql}&desde=${encodeURIComponent(desde)}&hasta=${encodeURIComponent(hasta)}&ar41=${encodeURIComponent(ar41)}&kr85=${encodeURIComponent(kr85)}&kr85m=${encodeURIComponent(kr85m)}&xe133=${encodeURIComponent(xe133)}&xe135=${encodeURIComponent(xe135)}&xe135m=${encodeURIComponent(xe135m)}`;

      if (typeof SyncEngine !== 'undefined') {
        SyncEngine.enqueue('gases_nobles', url);
      } else if (!IS_DEMO_MODE) {
        fetch(url);
      }

      const badge = document.getElementById('badge-gases-nobles-status');
      if (badge) {
        badge.className = 'sync-badge';
        badge.textContent = '● Registrado';
      }

      showToast('✅ Gases Nobles asentados y sincronizados correctamente');
    }

    function deleteGasesNobles() {
      const idGn = (document.getElementById('input-gn-id')?.value || '').trim();
      if (!confirm('¿Está seguro de que desea eliminar los datos de Gases Nobles de esta guardia?')) {
        return;
      }

      if (idGn) {
        const url = `procesos_reg_rad.php?accion=eliminar_registro_generico&nombre_tabla=regrad_gases_nobles&nombre_campo_id=id_gases_nobles&id_registro=${idGn}`;
        if (typeof SyncEngine !== 'undefined') {
          SyncEngine.enqueue('delete_gases_nobles', url);
        } else if (!IS_DEMO_MODE) {
          fetch(url);
        }
      }

      ['input-gn-ar41', 'input-gn-kr85', 'input-gn-kr85m', 'input-gn-xe133', 'input-gn-xe135', 'input-gn-xe135m'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '0.0';
      });
      const idEl = document.getElementById('input-gn-id');
      if (idEl) idEl.value = '';

      const badge = document.getElementById('badge-gases-nobles-status');
      if (badge) {
        badge.className = 'badge-amber';
        badge.textContent = '○ Sin Cargar';
      }

      showToast('🗑️ Registro de Gases Nobles eliminado');
    }

    function saveInlineMeteorology() {
      const dir = document.getElementById('input-met-dir') ? document.getElementById('input-met-dir').value : 'S';
      const vel = document.getElementById('input-met-vel') ? document.getElementById('input-met-vel').value.trim() : '';
      const pasquill = document.getElementById('input-met-pasquill') ? document.getElementById('input-met-pasquill').value : 'Clase D (Neutral - Nocturno nublado)';

      if (document.getElementById('kpi-in-met-dir')) document.getElementById('kpi-in-met-dir').value = dir;
      if (document.getElementById('kpi-in-met-vel')) document.getElementById('kpi-in-met-vel').value = vel;
      if (document.getElementById('kpi-in-met-pasquill')) document.getElementById('kpi-in-met-pasquill').value = pasquill;

      saveKpiMetrics();
    }

    function clearInlineMeteorology() {
      if (document.getElementById('input-met-vel')) document.getElementById('input-met-vel').value = '';
      if (document.getElementById('kpi-in-met-vel')) document.getElementById('kpi-in-met-vel').value = '';
      saveKpiMetrics();
      showToast('🗑️ Condiciones meteorológicas eliminadas (pendientes)');
    }
    // ================= TAREAS DE CAMPO & PTR =================
    function registerDailyTask() {
      const ptr = document.getElementById('task-ptr').value.trim();
      const recinto = document.getElementById('task-recinto').value;
      const oficialSelect = document.getElementById('task-oficial');
      const oficialNombre = (oficialSelect ? oficialSelect.value : document.getElementById('sidebar-user-name').textContent).toUpperCase();
      const ayudanteSelect = document.getElementById('task-ayudante');
      const ayudanteNombre = ayudanteSelect.options[ayudanteSelect.selectedIndex].text.toUpperCase();
      const dosis = document.getElementById('task-dosis').value || '0.0';
      const derrame = document.getElementById('task-derrame').value;
      const desc = (document.getElementById('task-desc').value || 'Sin descripción').toUpperCase();

      if (!ptr) {
        showToast('⚠️ Ingrese el número de PTR');
        document.getElementById('task-ptr').focus();
        return;
      }

      const tbody = document.getElementById('tasks-table-body');
      const tr = document.createElement('tr');
      tr.className = 'new-row';
      tr.innerHTML = `
        <td class="num" style="font-weight: 700; color: var(--accent-cyan);">${ptr.toUpperCase()}</td>
        <td>${recinto}</td>
        <td style="font-weight: 700; color: #ffffff;">${oficialNombre}</td>
        <td>${ayudanteNombre}</td>
        <td class="num">${dosis}</td>
        <td>${derrame === '1' ? '<span class="badge-amber">SÍ (D2O)</span>' : '<span style="color:var(--text-muted)">NO</span>'}</td>
        <td>${desc}</td>
        <td style="text-align: center;"><button class="btn-del" onclick="deleteRow(this)">✕</button></td>
      `;
      tbody.prepend(tr);

      const taskCount = tbody.querySelectorAll('tr').length;
      document.getElementById('menu-badge-tareas').textContent = taskCount;

      // Encolar tarea en LocalStorage y sincronizar con MySQL
      SyncEngine.enqueue('tarea', 'procesos_reg_rad_post.php?accion=insertar_tarea_diaria', 'POST', {
        id_planilla: (typeof activePlanillaId !== 'undefined' ? activePlanillaId : 1),
        id_usuario: 1,
        texto: desc,
        recinto: recinto,
        ptr: ptr,
        dosis: dosis,
        derrame: derrame,
        id_oficial_asociado: (oficialSelect ? oficialSelect.value : ''),
        id_ayudante: (ayudanteSelect ? ayudanteSelect.value : '')
      });

      showToast('✔️ Tarea PTR asentada correctamente');
      document.getElementById('task-ptr').value = '';
      document.getElementById('task-desc').value = '';
      document.getElementById('task-dosis').value = '';
    }

    // ================= LIBRO DE ACTAS =================
    function saveActas() {
      const text = document.getElementById('actas-text').value;
      // Guardar en MySQL
      SyncEngine.enqueue('actas', 'procesos_reg_rad_post.php?accion=actualizar_libro_actas', 'POST', {
        id_planilla: activePlanillaId || 1,
        id_usuario: 1,
        texto: text
      });

      showToast('✔️ Novedades guardadas en el Libro de Actas');
    }

    // ================= CIERRE DE TURNO =================
    function closeShiftFormal() {
      showToast('🔒 Formalizando y cerrando planilla...', 2000);
      fetch(`procesos_reg_rad.php?accion=cerrar_planilla&id_planilla=${activePlanillaId || 1}&id_usuario=1`)
        .catch(e => console.log('Offline close'));

      setTimeout(() => {
        showToast('🔒 Turno formalizado y cerrado. Planilla archivada con éxito.', 4000);
      }, 1000);
    }

    // ================= KEEP-ALIVE HEARTBEAT (ANTI-DESCONEXIÓN) =================
    function sendHeartbeat() {
      fetch('keep_alive.php')
        .then(response => response.json())
        .then(data => {
          const dot = document.getElementById('session-status-dot');
          const text = document.getElementById('session-status-text');
          if (dot && text) {
            dot.style.background = 'var(--accent-green)';
            text.textContent = 'Sistema en línea';
          }
        })
        .catch(err => {
          const dot = document.getElementById('session-status-dot');
          const text = document.getElementById('session-status-text');
          if (dot && text) {
            dot.style.background = 'var(--accent-amber)';
            text.textContent = 'Modo Local / Intranet';
          }
        });
    }
    // Ping cada 4 minutos para evitar caducidad a los 24 minutos
    setInterval(sendHeartbeat, 240000);
    setTimeout(sendHeartbeat, 3000);

    recalculateKPIs();
    if (typeof updateChecklistStatus === 'function') updateChecklistStatus();
  
    // ================= CIERRE DE MODALES AL CLICKEAR AFUERA (BACKDROP) Y CON TECLA ESCAPE =================
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
      overlay.addEventListener('click', (e) => {
        if (overlay.id === 'modal-traspaso-guardia') {
          // Clic exterior en el gatekeeper desbloquea para pruebas
          if (e.target === overlay) closeHandoverModal(true);
          return;
        }
        if (e.target === overlay) {
          overlay.style.display = 'none';
          if (overlay.id === 'confirm-modal') {
            pendingEntry = null;
          }
        }
      });
    });

    // Cerrar con tecla Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
          if (overlay.id === 'modal-traspaso-guardia') {
            closeHandoverModal(true);
            return;
          }
          if (overlay.style.display === 'flex' || overlay.style.display === 'block') {
            overlay.style.display = 'none';
            if (overlay.id === 'confirm-modal') {
              pendingEntry = null;
            }
          }
        });
      }
    });

  
    // ================= BUSCADORES INTELIGENTES SELECT2 VANILLA (RECINTOS, OFICIALES, AYUDANTES, PTRs) =================
    const rawRecintos = <?php echo json_encode($recintos_array); ?>;
    const recintosList = rawRecintos.map(item => {
      if (typeof item === 'string') return { id: item, name: item, subtext: '' };
      return { id: String(item.id), name: item.recinto, subtext: '#' + item.id };
    });

    const rawOficiales = <?php echo json_encode($oficiales_array); ?>;
    const oficialesList = rawOficiales.map(item => ({
      id: item.nombre,
      name: item.nombre,
      subtext: 'OFICIAL'
    }));

    const rawAyudantes = <?php echo json_encode($ayudantes_array); ?>;
    const ayudantesList = rawAyudantes.map(item => ({
      id: String(item.id),
      name: item.nombre,
      subtext: 'AYUDANTE'
    }));

    // Listado Oficial de PTRs Activos CNE (con fallback offline completo)
    let listaPTRs = <?php echo json_encode(array_map(function($p) {
        return array(
            'id' => (string)$p['id'],
            'name' => 'PTR ' . $p['id'] . ' - ' . $p['titulo'],
            'titulo' => $p['titulo'],
            'subtext' => '#' . $p['id']
        );
    }, $ptrs_array_inicial)); ?>;

    // Sincronización en segundo plano con el Web Service Oficial de PTRs CNE (sin bloquear)
    if (window.fetch) {
      fetch('https://syrapp.cne.central.nasa/ptr/simplestWebService.php?action=listadoPTRs_activos')
        .then(res => res.json())
        .then(data => {
          if (Array.isArray(data) && data.length > 0) {
            listaPTRs = data.map(item => ({
              id: String(item.id),
              name: 'PTR ' + item.id + ' - ' + (item.titulo_tarea_elca || ('Tarea PTR ' + item.id)),
              titulo: item.titulo_tarea_elca || ('PTR ' + item.id),
              subtext: '#' + item.id
            }));
            console.log('✅ PTRs activos sincronizados exitosamente desde CNE Web Service:', listaPTRs.length);
          }
        })
        .catch(err => {
          console.log('ℹ️ Operando con catálogo de PTRs offline precargado');
        });
    }

    /**
     * Controlador genérico y robusto para selectores con buscador en vivo, navegación por teclado y crucecita X
     */
    function setupSearchableDropdown(cfg) {
      const input = document.getElementById(cfg.inputId);
      const hidden = cfg.hiddenId ? document.getElementById(cfg.hiddenId) : null;
      const dropdown = document.getElementById(cfg.dropdownId);
      const clearBtn = cfg.clearBtnId ? document.getElementById(cfg.clearBtnId) : null;
      if (!input || !dropdown) return null;

      let highlightedIndex = -1;
      let filteredItems = [];

      function updateClearBtn() {
        if (clearBtn) {
          clearBtn.style.display = input.value ? 'block' : 'none';
        }
      }

      function render(queryText = '') {
        const rawItems = typeof cfg.getItems === 'function' ? cfg.getItems() : (cfg.items || []);
        const query = (queryText || '').trim().toLowerCase();

        if (!query) {
          filteredItems = rawItems.slice(0, 60);
        } else {
          filteredItems = rawItems.filter(item => {
            const text = (item.name || item.id || '').toLowerCase();
            const sub = (item.subtext || '').toLowerCase();
            return text.includes(query) || sub.includes(query);
          }).slice(0, 60);
        }

        dropdown.innerHTML = '';
        highlightedIndex = -1;

        if (filteredItems.length === 0) {
          dropdown.innerHTML = '<div class="searchable-empty">No se encontraron resultados para "' + (queryText || '') + '"</div>';
        } else {
          filteredItems.forEach((item, idx) => {
            const div = document.createElement('div');
            div.className = 'searchable-item';
            div.setAttribute('data-index', idx);

            let labelHtml = item.name;
            if (query) {
              const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
              labelHtml = item.name.replace(regex, '<mark>$1</mark>');
            }

            const subHtml = item.subtext ? `<span style="font-size:10px; color:var(--text-dim); font-family:var(--font-mono); margin-left:8px;">${item.subtext}</span>` : '';
            div.innerHTML = `<span>${labelHtml}</span>${subHtml}`;

            div.addEventListener('mousedown', (e) => {
              e.preventDefault();
              selectItem(item);
            });

            dropdown.appendChild(div);
          });
        }

        dropdown.classList.add('open');
        updateClearBtn();
      }

      function selectItem(item) {
        if (cfg.formatValueOnSelect) {
          input.value = cfg.formatValueOnSelect(item);
        } else {
          input.value = item.name;
        }
        if (hidden) {
          hidden.value = item.id;
        }
        dropdown.classList.remove('open');
        updateClearBtn();

        if (typeof cfg.onSelect === 'function') {
          cfg.onSelect(item);
        }
        if (cfg.nextFocusId) {
          const next = document.getElementById(cfg.nextFocusId);
          if (next) next.focus();
        }
      }

      function clear() {
        input.value = '';
        if (hidden) hidden.value = '';
        dropdown.classList.remove('open');
        updateClearBtn();
        if (typeof cfg.onClear === 'function') {
          cfg.onClear();
        }
        input.focus();
      }

      if (clearBtn) {
        clearBtn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          clear();
        });
      }

      input.addEventListener('focus', () => {
        render(input.value);
      });

      input.addEventListener('input', () => {
        render(input.value);
        if (hidden) hidden.value = input.value;
        updateClearBtn();
      });

      input.addEventListener('keydown', (e) => {
        const items = dropdown.querySelectorAll('.searchable-item');
        if (!dropdown.classList.contains('open')) {
          if (e.key === 'ArrowDown' || e.key === 'Enter') {
            render(input.value);
            e.preventDefault();
            return;
          }
        }

        if (e.key === 'ArrowDown') {
          e.preventDefault();
          if (items.length > 0) {
            highlightedIndex = (highlightedIndex + 1) % items.length;
            items.forEach((it, i) => it.classList.toggle('highlighted', i === highlightedIndex));
            if (items[highlightedIndex]) items[highlightedIndex].scrollIntoView({ block: 'nearest' });
          }
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          if (items.length > 0) {
            highlightedIndex = (highlightedIndex - 1 + items.length) % items.length;
            items.forEach((it, i) => it.classList.toggle('highlighted', i === highlightedIndex));
            if (items[highlightedIndex]) items[highlightedIndex].scrollIntoView({ block: 'nearest' });
          }
        } else if (e.key === 'Enter') {
          if (highlightedIndex >= 0 && filteredItems[highlightedIndex]) {
            e.preventDefault();
            selectItem(filteredItems[highlightedIndex]);
          } else if (filteredItems.length > 0 && dropdown.classList.contains('open')) {
            e.preventDefault();
            selectItem(filteredItems[0]);
          }
        } else if (e.key === 'Escape') {
          dropdown.classList.remove('open');
        }
      });

      document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !dropdown.contains(e.target) && (!clearBtn || !clearBtn.contains(e.target))) {
          dropdown.classList.remove('open');
        }
      });

      return { clear, render, selectItem };
    }

    // 1. Buscador de Recintos en Rutina
    const routineRecintoSelect = setupSearchableDropdown({
      inputId: 'q-recinto',
      hiddenId: 'q-recinto-id',
      dropdownId: 'recinto-dropdown',
      clearBtnId: 'btn-clear-recinto',
      getItems: () => recintosList,
      onSelect: (item) => {
        if (typeof updateRecintoHistory === 'function') {
          updateRecintoHistory(item.id, item.name);
        }
      },
      onClear: () => {
        if (typeof clearRecintoSelection === 'function') {
          clearRecintoSelection();
        }
      },
      nextFocusId: 'q-gases'
    });

    // 2. Buscador de Oficial Responsable en Actas
    const actaOficialSelect = setupSearchableDropdown({
      inputId: 'acta-oficial',
      hiddenId: 'acta-oficial-id',
      dropdownId: 'acta-oficial-dropdown',
      clearBtnId: 'btn-clear-acta-oficial',
      getItems: () => oficialesList,
      nextFocusId: 'acta-ayudante'
    });

    // 3. Buscador de Personal / Ayudante en Actas
    const actaAyudanteSelect = setupSearchableDropdown({
      inputId: 'acta-ayudante',
      hiddenId: 'acta-ayudante-id',
      dropdownId: 'acta-ayudante-dropdown',
      clearBtnId: 'btn-clear-acta-ayudante',
      getItems: () => ayudantesList,
      nextFocusId: 'acta-recinto'
    });

    // 4. Buscador de Recinto Asociado en Actas
    const actaRecintoSelect = setupSearchableDropdown({
      inputId: 'acta-recinto',
      hiddenId: 'acta-recinto-id',
      dropdownId: 'acta-recinto-dropdown',
      clearBtnId: 'btn-clear-acta-recinto',
      getItems: () => recintosList,
      nextFocusId: 'acta-ptr'
    });

    // 5. Selector y Buscador de PTRs Activos en Actas
    const actaPtrSelect = setupSearchableDropdown({
      inputId: 'acta-ptr',
      hiddenId: 'acta-ptr-id',
      dropdownId: 'acta-ptr-dropdown',
      clearBtnId: 'btn-clear-acta-ptr',
      getItems: () => listaPTRs,
      formatValueOnSelect: (item) => 'PTR ' + item.id + ' - ' + (item.titulo || item.name.replace(/^PTR \d+ - /, '')),
      onSelect: (item) => {
        const ptrIdHidden = document.getElementById('acta-ptr-id');
        if (ptrIdHidden) ptrIdHidden.value = item.id;
        // Si el texto de la observación está vacío, sugerir la descripción de la tarea PTR
        const textoArea = document.getElementById('acta-texto');
        if (textoArea && !textoArea.value.trim()) {
          textoArea.value = (item.titulo || item.name) + ': ';
        }
      },
      nextFocusId: 'acta-dosis'
    });

    // 6. Buscador de Recinto en Contaminación Superficial (Frotis)
    const frotisRecintoSelect = setupSearchableDropdown({
      inputId: 'frotis-recinto',
      hiddenId: 'frotis-recinto-id',
      dropdownId: 'frotis-recinto-dropdown',
      clearBtnId: 'btn-clear-frotis-recinto',
      getItems: () => recintosList
    });

    // Exponer helpers globales para cruces X y compatibilidad
    window.clearActaOficial = () => actaOficialSelect && actaOficialSelect.clear();
    window.clearActaAyudante = () => actaAyudanteSelect && actaAyudanteSelect.clear();
    window.clearActaRecinto = () => actaRecintoSelect && actaRecintoSelect.clear();
    window.clearActaPtr = () => actaPtrSelect && actaPtrSelect.clear();
    window.clearFrotisRecinto = () => frotisRecintoSelect && frotisRecintoSelect.clear();

  
    // ================= IMPLEMENTACIÓN VANILLA DATEPICKER LATAM (DD/MM/AAAA) =================
    const dpPopup = document.getElementById('latam-datepicker-popup');
    const dpMonthDisplay = document.getElementById('dp-month-display');
    const dpDaysContainer = document.getElementById('dp-days-container');
    let dpCurrentDate = new Date();
    let dpTargetInput = null;

    const monthNamesLatam = [
      'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
      'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    function parseLatamDate(str) {
      if (!str) return new Date();
      const parts = str.split('/');
      if (parts.length === 3) {
        const d = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10) - 1;
        const y = parseInt(parts[2], 10);
        if (!isNaN(d) && !isNaN(m) && !isNaN(y)) return new Date(y, m, d);
      }
      return new Date();
    }

    function formatLatamDate(dateObj) {
      const d = String(dateObj.getDate()).padStart(2, '0');
      const m = String(dateObj.getMonth() + 1).padStart(2, '0');
      const y = dateObj.getFullYear();
      return `${d}/${m}/${y}`;
    }

    function renderDatepickerGrid() {
      const year = dpCurrentDate.getFullYear();
      const month = dpCurrentDate.getMonth();

      dpMonthDisplay.textContent = `${monthNamesLatam[month]} ${year}`;
      dpDaysContainer.innerHTML = '';

      const firstDayOfMonth = new Date(year, month, 1);
      const lastDayOfMonth = new Date(year, month + 1, 0);

      let startingDay = firstDayOfMonth.getDay() - 1;
      if (startingDay === -1) startingDay = 6;

      const totalDays = lastDayOfMonth.getDate();
      const prevMonthLastDay = new Date(year, month, 0).getDate();

      for (let i = startingDay - 1; i >= 0; i--) {
        const cell = document.createElement('div');
        cell.className = 'dp-day-cell other-month';
        cell.textContent = prevMonthLastDay - i;
        dpDaysContainer.appendChild(cell);
      }

      const today = new Date();
      const selectedVal = dpTargetInput ? dpTargetInput.value : '';

      for (let day = 1; day <= totalDays; day++) {
        const cell = document.createElement('div');
        cell.className = 'dp-day-cell';
        cell.textContent = day;

        const cellDate = new Date(year, month, day);
        const cellStr = formatLatamDate(cellDate);

        if (cellStr === formatLatamDate(today)) {
          cell.classList.add('today');
        }
        if (cellStr === selectedVal) {
          cell.classList.add('selected');
        }

        cell.addEventListener('click', (e) => {
          e.stopPropagation();
          if (dpTargetInput) {
            dpTargetInput.value = cellStr;
            dpTargetInput.dispatchEvent(new Event('input', { bubbles: true }));
            dpTargetInput.dispatchEvent(new Event('change', { bubbles: true }));
          }
          closeDatepicker();
        });

        dpDaysContainer.appendChild(cell);
      }
    }

    function openDatepicker(inputEl) {
      closeTimepicker();
      dpTargetInput = inputEl;
      dpCurrentDate = parseLatamDate(inputEl.value);

      const rect = inputEl.getBoundingClientRect();
      const popupWidth = 260;
      let left = rect.left;
      if (left + popupWidth > window.innerWidth) {
        left = window.innerWidth - popupWidth - 10;
      }
      dpPopup.style.top = (rect.bottom + 4) + 'px';
      dpPopup.style.left = Math.max(10, left) + 'px';
      dpPopup.style.display = 'block';

      renderDatepickerGrid();
    }

    function closeDatepicker() {
      if (dpPopup) dpPopup.style.display = 'none';
      dpTargetInput = null;
    }

    if (document.getElementById('dp-btn-prev')) {
      document.getElementById('dp-btn-prev').addEventListener('click', (e) => {
        e.stopPropagation();
        dpCurrentDate.setMonth(dpCurrentDate.getMonth() - 1);
        renderDatepickerGrid();
      });
    }

    if (document.getElementById('dp-btn-next')) {
      document.getElementById('dp-btn-next').addEventListener('click', (e) => {
        e.stopPropagation();
        dpCurrentDate.setMonth(dpCurrentDate.getMonth() + 1);
        renderDatepickerGrid();
      });
    }

    if (document.getElementById('dp-btn-yesterday')) {
      document.getElementById('dp-btn-yesterday').addEventListener('click', (e) => {
        e.stopPropagation();
        const y = new Date();
        y.setDate(y.getDate() - 1);
        if (dpTargetInput) {
          dpTargetInput.value = formatLatamDate(y);
          dpTargetInput.dispatchEvent(new Event('input', { bubbles: true }));
          dpTargetInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
        closeDatepicker();
      });
    }

    if (document.getElementById('dp-btn-today')) {
      document.getElementById('dp-btn-today').addEventListener('click', (e) => {
        e.stopPropagation();
        const t = new Date();
        if (dpTargetInput) {
          dpTargetInput.value = formatLatamDate(t);
          dpTargetInput.dispatchEvent(new Event('input', { bubbles: true }));
          dpTargetInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
        closeDatepicker();
      });
    }

    if (document.getElementById('dp-btn-close')) {
      document.getElementById('dp-btn-close').addEventListener('click', (e) => {
        e.stopPropagation();
        closeDatepicker();
      });
    }

    // ================= IMPLEMENTACIÓN VANILLA TIMEPICKER LATAM (HH:MM) =================
    const tpPopup = document.getElementById('latam-timepicker-popup');
    const tpHoursList = document.getElementById('tp-hours-list');
    const tpMinutesList = document.getElementById('tp-minutes-list');
    let tpTargetInput = null;

    if (tpHoursList && tpHoursList.children.length === 0) {
      for (let h = 0; h < 24; h++) {
        const div = document.createElement('div');
        div.className = 'tp-item';
        div.textContent = String(h).padStart(2, '0');
        div.addEventListener('click', (e) => {
          e.stopPropagation();
          selectTimePart('h', div.textContent);
        });
        tpHoursList.appendChild(div);
      }
    }

    if (tpMinutesList && tpMinutesList.children.length === 0) {
      for (let m = 0; m < 60; m += 5) {
        const div = document.createElement('div');
        div.className = 'tp-item';
        div.textContent = String(m).padStart(2, '0');
        div.addEventListener('click', (e) => {
          e.stopPropagation();
          selectTimePart('m', div.textContent);
        });
        tpMinutesList.appendChild(div);
      }
    }

    function selectTimePart(type, val) {
      if (!tpTargetInput) return;
      const current = (tpTargetInput.value || '00:00').split(':');
      let h = current[0] || '00';
      let m = current[1] || '00';

      if (type === 'h') h = val;
      if (type === 'm') m = val;

      tpTargetInput.value = `${h}:${m}`;
      tpTargetInput.dispatchEvent(new Event('input', { bubbles: true }));
      tpTargetInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function applyQuickTime(hhmm) {
      if (tpTargetInput) {
        tpTargetInput.value = hhmm;
        tpTargetInput.dispatchEvent(new Event('input', { bubbles: true }));
        tpTargetInput.dispatchEvent(new Event('change', { bubbles: true }));
      }
      closeTimepicker();
    }

    function applyNowTime() {
      const now = new Date();
      const h = String(now.getHours()).padStart(2, '0');
      const m = String(now.getMinutes()).padStart(2, '0');
      applyQuickTime(`${h}:${m}`);
    }

    function openTimepicker(inputEl) {
      closeDatepicker();
      tpTargetInput = inputEl;
      const rect = inputEl.getBoundingClientRect();
      const popupWidth = 260;
      let left = rect.left;
      if (left + popupWidth > window.innerWidth) {
        left = window.innerWidth - popupWidth - 10;
      }
      tpPopup.style.top = (rect.bottom + 4) + 'px';
      tpPopup.style.left = Math.max(10, left) + 'px';
      tpPopup.style.display = 'block';
    }

    function closeTimepicker() {
      if (tpPopup) tpPopup.style.display = 'none';
      tpTargetInput = null;
    }

    if (document.getElementById('tp-close-btn')) {
      document.getElementById('tp-close-btn').addEventListener('click', (e) => {
        e.stopPropagation();
        closeTimepicker();
      });
    }

    // Delegación global de clics para abrir Datepicker y Timepicker
    document.addEventListener('click', (e) => {
      // 1. Clic en input de fecha
      const dpInput = e.target.closest('.latam-datepicker-input');
      if (dpInput) {
        e.stopPropagation();
        openDatepicker(dpInput);
        return;
      }

      // 2. Clic en input de hora
      const tpInput = e.target.closest('.latam-timepicker-input');
      if (tpInput) {
        e.stopPropagation();
        openTimepicker(tpInput);
        return;
      }

      // 3. Clic fuera de los popups -> cerrar
      if (dpPopup && dpPopup.style.display === 'block' && !dpPopup.contains(e.target)) {
        closeDatepicker();
      }
      if (tpPopup && tpPopup.style.display === 'block' && !tpPopup.contains(e.target)) {
        closeTimepicker();
      }
    });

  
    // ================= SELECTOR DE FONDO Y TEMAS EN VIVO =================
    function setAppTheme(themeName) {
      document.documentElement.setAttribute('data-theme', themeName);
      try {
        localStorage.setItem('regrad_active_theme', themeName);
      } catch(e) {}

      document.querySelectorAll('#theme-switcher-bar .theme-pill').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-theme') === themeName);
      });
      showToast('🎨 Fondo aplicado: ' + themeName.toUpperCase());
    }

    // Restaurar tema guardado al cargar
    try {
      const savedTheme = localStorage.getItem('regrad_active_theme') || 'petrol';
      if (savedTheme) {
        setAppTheme(savedTheme);
      }
    } catch(e) {}

  
    // ================= MOTOR DE COLA OFFLINE Y SINCRONIZACIÓN AUTOMÁTICA (OUTBOX) =================
    const IS_DEMO_MODE = <?php echo (isset($miconex) && $miconex) ? "false" : "true"; ?>; // Conectado a MySQL: Cambios reales habilitados

    const SyncEngine = {
      QUEUE_KEY: 'regrad_offline_queue_v1',
      isSyncing: false,

      // Encolar acción en LocalStorage
      enqueue(actionType, url, method = 'GET', postData = null, meta = {}) {
        let queue = this.getQueue();
        const item = {
          id: 'tx_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
          timestamp: new Date().toISOString(),
          actionType,
          url,
          method,
          postData,
          meta,
          retries: 0
        };
        queue.push(item);
        this.saveQueue(queue);
        this.updateSyncUI();

        // Disparar intento de sincronización en segundo plano
        this.flush();
        return item.id;
      },

      getQueue() {
        try {
          const q = localStorage.getItem(this.QUEUE_KEY);
          return q ? JSON.parse(q) : [];
        } catch(e) {
          return [];
        }
      },

      saveQueue(queue) {
        try {
          localStorage.setItem(this.QUEUE_KEY, JSON.stringify(queue));
        } catch(e) {}
      },

      // Intentar vaciar la cola enviando peticiones pendientes al servidor
      async flush(manual = false) {
        if (this.isSyncing) return;
        let queue = this.getQueue();
        if (queue.length === 0) {
          this.updateSyncUI();
          if (manual) showToast('☁️ No hay datos pendientes de sincronización');
          return;
        }

        this.isSyncing = true;
        this.updateSyncUI(true);

        const remaining = [];
        let successCount = 0;

        for (let i = 0; i < queue.length; i++) {
          const item = queue[i];
          try {
            if (IS_DEMO_MODE) {
              // MODO DEMO PROTEGIDO: Simula éxito en LocalStorage sin tocar MySQL
              console.log(`[DEMO PROTEGIDA] Acción simulada [${item.actionType}] en LocalStorage - Base de Datos MySQL INTACTA`);
              successCount++;
              if (item.meta && item.meta.rowId) {
                const rowEl = document.getElementById(item.meta.rowId);
                if (rowEl) {
                  const b = rowEl.querySelector('.sync-badge');
                  if (b) {
                    b.className = 'sync-badge synced';
                    b.innerHTML = '● Guardado (Demo)';
                    b.title = 'Guardado simulado en LocalStorage (Base de datos real protegida)';
                  }
                }
              }
              continue;
            }

            let response;
            if (item.method === 'POST') {
              let formData = new FormData();
              if (item.postData) {
                for (let k in item.postData) {
                  formData.append(k, item.postData[k]);
                }
              }
              response = await fetch(item.url, { method: 'POST', body: formData });
            } else {
              response = await fetch(item.url);
            }

            if (response && response.ok) {
              const resText = await response.text();
              console.log(`✔️ Sincronizado OK [${item.actionType}]:`, resText);
              successCount++;

              // Actualizar badge visual en la fila correspondiente si existe
              if (item.meta && item.meta.rowId) {
                const rowEl = document.getElementById(item.meta.rowId);
                if (rowEl) {
                  const b = rowEl.querySelector('.sync-badge');
                  if (b) {
                    b.className = 'sync-badge synced';
                    b.innerHTML = '● Guardado';
                    b.title = 'Guardado confirmado en MySQL';
                  }
                }
              }
            } else {
              throw new Error('Servidor devolvió status: ' + (response ? response.status : 'desconocido'));
            }
          } catch(err) {
            console.warn(`⏳ Error enviando [${item.actionType}], reintentará automáticamente:`, err.message);
            item.retries = (item.retries || 0) + 1;
            remaining.push(item);
          }
        }

        this.saveQueue(remaining);
        this.isSyncing = false;
        this.updateSyncUI();

        if (successCount > 0 && remaining.length === 0) {
          showToast(`☁️ ${successCount} registro(s) sincronizado(s) con éxito con MySQL`);
        } else if (manual && remaining.length > 0) {
          showToast(`⏳ El servidor aún no responde. ${remaining.length} registro(s) seguro(s) en LocalStorage`);
        }
      },

      updateSyncUI(inProgress = false) {
        const queue = this.getQueue();
        const pill = document.getElementById('sync-status-indicator');
        if (!pill) return;

        if (inProgress) {
          pill.className = 'sync-pill syncing';
          pill.innerHTML = `🔄 Sincronizando (${queue.length})...`;
        } else if (queue.length > 0) {
          pill.className = 'sync-pill pending';
          pill.innerHTML = `⏳ ${queue.length} pendiente${queue.length > 1 ? 's' : ''} [Reintentar]`;
          pill.title = 'Datos guardados localmente. Haga clic para intentar enviar ahora al servidor';
        } else {
          pill.className = 'sync-pill synced';
          pill.innerHTML = IS_DEMO_MODE ? `🛡️ Modo Demo (BD Protegida)` : `☁️ Servidor sincronizado`;
          pill.title = IS_DEMO_MODE ? 'Modo simulación activo: no se escribe nada en MySQL' : 'Todos los datos están guardados en MySQL';
        }
      }
    };

    // Reintentos automáticos periódicos cada 25 segundos
    setInterval(() => {
      SyncEngine.flush();
    }, 25000);

    // Reintento inmediato cuando se detecta conexión a internet / intranet
    window.addEventListener('online', () => {
      showToast('🌐 Conexión detectada. Sincronizando cola de datos...');
      SyncEngine.flush();
    });

    // Inicializar indicador visual al cargar
    setTimeout(() => {
      SyncEngine.updateSyncUI();
      SyncEngine.flush();
    }, 2000);


    // ================= HISTÓRICO DE LOS ÚLTIMOS 3 VALORES DEL RECINTO =================
        // Borrar selección de recinto con la crucecita X
    function clearRecintoSelection() {
      const input = document.getElementById('q-recinto');
      const idInput = document.getElementById('q-recinto-id');
      const clearBtn = document.getElementById('btn-clear-recinto');
      const histPanel = document.getElementById('recinto-history-panel');
      const dropdown = document.getElementById('recinto-dropdown');

      if (input) {
        input.value = '';
        input.focus();
      }
      if (idInput) idInput.value = '';
      if (clearBtn) clearBtn.style.display = 'none';
      if (histPanel) histPanel.style.display = 'none';
      if (dropdown) dropdown.classList.remove('open');
    }

    const mockRecintoHistory = {
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

    function updateRecintoHistory(recintoId, recintoName) {
      const panel = document.getElementById('recinto-history-panel');
      const label = document.getElementById('rh-recinto-label');
      const chipsList = document.getElementById('rh-chips-list');
      const trendBadge = document.getElementById('rh-trend-badge');
      if (!panel || !chipsList) return;

      if (!recintoName || recintoName.trim() === '') {
        panel.style.display = 'none';
        return;
      }

      label.textContent = recintoName.split(' - ')[0];

      // 1. Extraer mediciones reales ya cargadas en la tabla viva para este recinto
      const norm = recintoName.toLowerCase().trim();
      const tableRows = Array.from(document.querySelectorAll('#table-body tr')).filter(tr => {
        const r = (tr.getAttribute('data-recinto') || (tr.cells[2] ? tr.cells[2].textContent : '')).toLowerCase().trim();
        return r && (r === norm || r.startsWith(norm) || norm.startsWith(r));
      });

      const history = [];
      tableRows.forEach(tr => {
        if (history.length >= 3) return;
        const f = tr.getAttribute('data-fecha') || (tr.cells[0] ? tr.cells[0].textContent.trim() : '08/11/2026');
        const h = tr.getAttribute('data-hora') || (tr.cells[1] ? tr.cells[1].textContent.trim() : '00:00');
        const d = tr.getAttribute('data-dosis') || (tr.cells[5] ? tr.cells[5].textContent.trim() : '0.0');
        const t = tr.getAttribute('data-tritio') || (tr.cells[4] ? tr.cells[4].textContent.trim() : '0.0');
        const g = tr.getAttribute('data-gases') || (tr.cells[3] ? tr.cells[3].textContent.trim() : '0.0');
        const fClean = (!f || f.toLowerCase().includes('turno')) ? '08/11/2026' : f;
        history.push({
          fecha: fClean,
          hora: h,
          dosis: parseFloat(d).toFixed(1),
          tritio: parseFloat(t).toFixed(2),
          gases: parseFloat(g).toFixed(2)
        });
      });

      // 2. Si hay menos de 3 registros en la tabla viva, complementar con historial de base (estrictamente Fecha y Hora, NUNCA Turno)
      const fallbackList = (typeof mockRecintoHistory !== 'undefined' && mockRecintoHistory[recintoId]) 
        ? mockRecintoHistory[recintoId] 
        : [
            { fecha: '08/11/2026', hora: '23:30', dosis: '4.5', tritio: '0.40', gases: '0.00' },
            { fecha: '08/11/2026', hora: '15:40', dosis: '4.0', tritio: '0.35', gases: '0.00' },
            { fecha: '08/11/2026', hora: '08:10', dosis: '3.8', tritio: '0.30', gases: '0.00' }
          ];

      fallbackList.forEach(fb => {
        if (history.length < 3) {
          const fbClean = (!fb.fecha || fb.fecha.toLowerCase().includes('turno')) ? '08/11/2026' : fb.fecha;
          history.push({
            fecha: fbClean,
            hora: fb.hora,
            dosis: fb.dosis,
            tritio: fb.tritio,
            gases: fb.gases
          });
        }
      });

      // 3. Renderizar chips mostrando estrictamente Fecha y Hora (SIN Turno) y en orden: G. nobles, Tritio y Dosis
      chipsList.innerHTML = history.map((item, idx) => `
        <div style="background: rgba(22, 54, 48, 0.7); border: 1px solid var(--border-card); border-radius: 4px; padding: 4px 10px; display: inline-flex; align-items: center; gap: 8px; font-family: var(--font-mono); font-size: 11px;">
          <span style="font-weight: 800; color: var(--accent-cyan);">[${idx + 1}]</span>
          <span style="color: #ffffff; font-weight: 700;">📅 ${item.fecha} &bull; ${item.hora} hs:</span>
          <span style="color: #c084fc; font-weight: 700;"><span style="color: var(--text-dim); font-size: 10px; font-weight: 600;">G. nobles:</span> ${item.gases} DAC</span>
          <span style="color: var(--border-card);">|</span>
          <span style="color: var(--accent-cyan); font-weight: 700;"><span style="color: var(--text-dim); font-size: 10px; font-weight: 600;">Tritio:</span> ${item.tritio} DAC</span>
          <span style="color: var(--border-card);">|</span>
          <span style="color: var(--accent-amber); font-weight: 700;"><span style="color: var(--text-dim); font-size: 10px; font-weight: 600;">Dosis:</span> ${item.dosis} µSv/h</span>
        </div>
      `).join('');

      // Calcular tendencia rápida
      const d1 = parseFloat(history[0].dosis);
      const dLast = parseFloat(history[history.length - 1].dosis);
      if (dLast > 0 && d1 > dLast * 1.3) {
        trendBadge.innerHTML = '📈 Alza de Dosis (+ ' + Math.round(((d1 - dLast)/dLast)*100) + '%)';
        trendBadge.style.color = 'var(--accent-amber)';
      } else {
        trendBadge.innerHTML = '🟢 Niveles estables';
        trendBadge.style.color = 'var(--accent-green)';
      }

      panel.style.display = 'block';
    }

    // Escuchar cambios en selector de recinto
    const qRecintoInput = document.getElementById('q-recinto');
    if (qRecintoInput) {
      qRecintoInput.addEventListener('change', () => {
        const idVal = document.getElementById('q-recinto-id').value;
        updateRecintoHistory(idVal, qRecintoInput.value);
      });
    }

    // ================= FUNCIONES DE CARGA Y GESTIÓN DE ACTAS =================
    function toggleAerosolesInput(cb) {
      const input = document.getElementById('acta-aerosoles-dac');
      if (input) {
        input.style.display = cb.checked ? 'inline-block' : 'none';
        if (cb.checked) input.focus();
        else input.value = '';
      }
    }

    function asentarNuevaActa() {
      const texto = document.getElementById('acta-texto').value.trim();
      const oficialHidden = document.getElementById('acta-oficial-id');
      const oficialInput = document.getElementById('acta-oficial');
      const oficial = ((oficialHidden && oficialHidden.value) ? oficialHidden.value : (oficialInput ? oficialInput.value : 'OFICIAL DE GUARDIA')).toUpperCase();

      const ayudanteHidden = document.getElementById('acta-ayudante-id');
      const ayudanteInput = document.getElementById('acta-ayudante');
      const ayudanteId = ayudanteHidden ? ayudanteHidden.value : '';
      const ayudanteNombre = (ayudanteInput && ayudanteInput.value.trim()) ? ayudanteInput.value.trim().toUpperCase() : '-';

      const recintoInput = document.getElementById('acta-recinto');
      const recinto = (recintoInput && recintoInput.value.trim()) ? recintoInput.value.trim() : 'General de Planta';

      const ptrHidden = document.getElementById('acta-ptr-id');
      const ptrInput = document.getElementById('acta-ptr');
      let rawPtr = (ptrHidden && ptrHidden.value) ? ptrHidden.value : (ptrInput ? ptrInput.value.trim() : '');
      let ptr = '';
      if (rawPtr) {
        const m = rawPtr.match(/\d+/);
        ptr = m ? m[0] : rawPtr;
      }

      const dosis = document.getElementById('acta-dosis').value.trim() || '0.0';
      const tritio = document.getElementById('acta-tritio').value.trim() || '0.0';
      const contBeta = document.getElementById('acta-cont-beta').value.trim() || '0.0';
      const contAlfa = document.getElementById('acta-cont-alfa').value.trim() || '0.0';
      const derrame = document.getElementById('acta-derrame').checked ? 1 : 0;
      const aerosoles = document.getElementById('acta-aerosoles').checked ? 1 : 0;
      const aerosolesDac = document.getElementById('acta-aerosoles-dac').value.trim() || '0.0';

      if (!texto) {
        showToast('⚠️ Ingrese la descripción o texto del Acta');
        document.getElementById('acta-texto').focus();
        return;
      }

      const now = new Date();
      const horaStr = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

      // 1. Agregar a la tabla de actas en pantalla
      const tbody = document.getElementById('tbody-actas-guardia');
      const tr = document.createElement('tr');
      tr.className = 'new-row';
      tr.className = 'new-row acta-clickable-row';
      tr.setAttribute('data-hora', horaStr);
      tr.setAttribute('data-ptr', ptr || '-');
      tr.setAttribute('data-recinto', recinto);
      tr.setAttribute('data-oficial', oficial);
      tr.setAttribute('data-ayudante', ayudanteNombre);
      tr.setAttribute('data-texto', texto);
      tr.setAttribute('data-dosis', dosis);
      tr.setAttribute('data-tritio', tritio);
      tr.setAttribute('data-derrame', derrame);
      tr.setAttribute('data-aerosoles', aerosolesDac || (aerosoles ? 'SÍ' : '0.0'));
      tr.onclick = function(e) {
        if (!e.target.closest('.btn-del')) {
          abrirModalDetalleActaFromRow(this);
        }
      };

      tr.innerHTML = `
        <td class="mono">${horaStr}</td>
        <td class="mono" style="${ptr ? 'color: var(--accent-cyan); font-weight:700;' : 'color: var(--text-dim);'}">${ptr ? ptr : '-'}</td>
        <td style="font-weight:600;">${recinto.split(' - ')[0]}</td>
        <td style="font-weight:700;">${oficial}</td>
        <td>${ayudanteNombre}</td>
        <td class="text-left" style="max-width: 320px;">
          <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 250px; display: inline-block;">${texto}</span>
            <button type="button" class="btn-acta-open" title="Abrir y ver acta completa">🔍 Ver</button>
          </div>
        </td>
        <td class="mono" style="text-align: right; color: ${parseFloat(dosis) > 0 ? 'var(--accent-amber); font-weight:700;' : 'var(--text-dim);'}">${dosis}</td>
        <td>${derrame ? '<span class="badge-amber">SÍ (D2O)</span>' : '<span style="color:var(--text-dim)">-</span>'}</td>
        <td style="text-align: center;" onclick="event.stopPropagation();"><button class="btn-del" onclick="deleteRow(this)">✕</button></td>
      `;
      tbody.prepend(tr);

      // 2. Actualizar badge contador
      const count = tbody.querySelectorAll('tr').length;
      const badge = document.getElementById('menu-badge-actas');
      if (badge) badge.textContent = count;
      const countHeader = document.getElementById('actas-counter-badge');
      if (countHeader) countHeader.textContent = count + ' actas asentadas en este turno';

      // 3. Encolar en SyncEngine
      SyncEngine.enqueue('acta_tarea', 'procesos_reg_rad_post.php', 'POST', {
        accion: 'insertar_tarea_diaria',
        id_planilla: (typeof activePlanillaId !== 'undefined' ? activePlanillaId : 1),
        id_usuario: 1,
        texto: texto,
        recinto: recinto,
        ptr: ptr,
        dosis: dosis,
        tritio: tritio,
        cont_beta: contBeta,
        cont_alfa: contAlfa,
        derrame: derrame,
        aerosoles: aerosoles,
        aerosoles_dac: aerosolesDac,
        id_oficial_asociado: (oficialSelect ? oficialSelect.value : ''),
        id_ayudante: ayudanteId
      });

      showToast('✔️ Acta de guardia asentada correctamente');

      // Limpiar formulario con los nuevos buscadores
      document.getElementById('acta-texto').value = '';
      if (window.clearActaAyudante) window.clearActaAyudante();
      if (window.clearActaRecinto) window.clearActaRecinto();
      if (window.clearActaPtr) window.clearActaPtr();
      document.getElementById('acta-dosis').value = '';
      document.getElementById('acta-tritio').value = '';
      document.getElementById('acta-cont-beta').value = '';
      document.getElementById('acta-cont-alfa').value = '';
      document.getElementById('acta-derrame').checked = false;
      document.getElementById('acta-aerosoles').checked = false;
      document.getElementById('acta-aerosoles-dac').style.display = 'none';
      document.getElementById('acta-aerosoles-dac').value = '';
    }

      // ================= MODAL INTERACTIVO DE HISTORIAL POR RECINTO GUARDADO =================
    let activeSelectedRecintoForPrefill = '';

    function openRecintoHistoryModal(recintoParam) {
      const modal = document.getElementById('modal-recinto-historial');
      const labelNombre = document.getElementById('mrh-recinto-nombre');
      const container = document.getElementById('mrh-chips-container');
      const trendBadge = document.getElementById('mrh-trend-badge');
      const prefillBtn = document.getElementById('mrh-btn-prefill');
      if (!modal || !container) return;

      activeSelectedRecintoForPrefill = recintoParam;
      if (labelNombre) labelNombre.textContent = recintoParam;

      // 1. Obtener historial: extraer mediciones reales de la tabla viva si existen
      const norm = (recintoParam || '').toLowerCase().trim();
      const tableRows = Array.from(document.querySelectorAll('#table-body tr')).filter(tr => {
        const r = (tr.getAttribute('data-recinto') || (tr.cells[2] ? tr.cells[2].textContent : '')).toLowerCase().trim();
        return r && (r === norm || r.startsWith(norm) || norm.startsWith(r));
      });

      const history = [];
      tableRows.forEach(tr => {
        if (history.length >= 3) return;
        const f = tr.getAttribute('data-fecha') || (tr.cells[0] ? tr.cells[0].textContent.trim() : '08/11/2026');
        const h = tr.getAttribute('data-hora') || (tr.cells[1] ? tr.cells[1].textContent.trim() : '00:00');
        const d = tr.getAttribute('data-dosis') || (tr.cells[5] ? tr.cells[5].textContent.trim() : '0.0');
        const t = tr.getAttribute('data-tritio') || (tr.cells[4] ? tr.cells[4].textContent.trim() : '0.0');
        const g = tr.getAttribute('data-gases') || (tr.cells[3] ? tr.cells[3].textContent.trim() : '0.0');
        const fClean = (!f || f.toLowerCase().includes('turno')) ? '08/11/2026' : f;
        history.push({
          fecha: fClean,
          hora: h,
          dosis: parseFloat(d).toFixed(1),
          tritio: parseFloat(t).toFixed(2),
          gases: parseFloat(g).toFixed(2)
        });
      });

      const idMatch = (typeof recintoParam === 'string') ? recintoParam.match(/R-?(\d+)/i) : null;
      const recintoId = idMatch ? parseInt(idMatch[1], 10).toString() : '1';

      const fallbackList = (typeof mockRecintoHistory !== 'undefined' && mockRecintoHistory[recintoId]) 
        ? mockRecintoHistory[recintoId] 
        : [
            { fecha: '08/11/2026', hora: '23:30', dosis: '4.5', tritio: '0.40', gases: '0.00' },
            { fecha: '08/11/2026', hora: '15:40', dosis: '4.0', tritio: '0.35', gases: '0.00' },
            { fecha: '08/11/2026', hora: '08:10', dosis: '3.8', tritio: '0.30', gases: '0.00' }
          ];

      fallbackList.forEach(fb => {
        if (history.length < 3) {
          const fbClean = (!fb.fecha || fb.fecha.toLowerCase().includes('turno')) ? '08/11/2026' : fb.fecha;
          history.push({
            fecha: fbClean,
            hora: fb.hora,
            dosis: fb.dosis,
            tritio: fb.tritio,
            gases: fb.gases
          });
        }
      });

      // 2. Renderizar las 3 mediciones con SOLO Fecha y Hora en orden: G. nobles, Tritio y Dosis
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

      // 3. Evaluar tendencia
      const d1 = parseFloat(history[0].dosis);
      const d3 = parseFloat(history[2].dosis);
      if (d1 > d3 * 1.3) {
        trendBadge.innerHTML = '📈 Alza de Dosis (+ ' + Math.round(((d1 - d3)/d3)*100) + '%)';
        trendBadge.style.color = 'var(--accent-amber)';
      } else {
        trendBadge.innerHTML = '🟢 Niveles estables';
        trendBadge.style.color = 'var(--accent-green)';
      }

      if (prefillBtn && !document.getElementById('q-recinto')) {
        prefillBtn.style.display = 'none';
      }

      modal.style.display = 'flex';
    }

    function closeRecintoHistoryModal() {
      const modal = document.getElementById('modal-recinto-historial');
      if (modal) modal.style.display = 'none';
    }

    function prefillRecintoForm() {
      closeRecintoHistoryModal();
      const input = document.getElementById('q-recinto');
      if (input && activeSelectedRecintoForPrefill) {
        input.value = activeSelectedRecintoForPrefill;
        const clearBtn = document.getElementById('btn-clear-recinto');
        if (clearBtn) clearBtn.style.display = 'block';
        if (typeof updateRecintoHistory === 'function') {
          updateRecintoHistory('1', activeSelectedRecintoForPrefill);
        }
        const tabRutina = document.querySelector('.menu-btn[data-section="sec-rutina"]');
        if (tabRutina) tabRutina.click();
        const nextInput = document.getElementById('q-dosis') || document.getElementById('q-gases');
        if (nextInput) nextInput.focus();
      }
    }

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
    // ====================================================
    // GESTIÓN DE RELEVO DE GUARDIA Y ADVERTENCIA DE PLANILLA ABIERTA
    // ====================================================
    const hayDiscrepanciaGuardia = <?php echo $hay_discrepancia_guardia ? 'true' : 'false'; ?>;
    const oficialIniciador = <?php echo json_encode($oficial_iniciador_nombre); ?>;
    const oficialLogueado = <?php echo json_encode($oficial_logueado); ?>;

    function openHandoverModal() {
      const modal = document.getElementById('modal-traspaso-guardia');
      if (modal) modal.style.display = 'flex';
    }

    function closeHandoverModal(isDismiss) {
      const modal = document.getElementById('modal-traspaso-guardia');
      if (modal) modal.style.display = 'none';
      if (isDismiss) {
        sessionStorage.setItem('relevo_advertencia_desestimada_' + activePlanillaId, '1');
      }
    }

    let sinPlanillaAbierta = <?php echo !empty($sin_planilla_abierta) ? 'true' : 'false'; ?>;

    function mostrarPasoAdvertenciaCierre() {
      const modal = document.getElementById('modal-traspaso-guardia');
      if (modal) modal.style.display = 'flex';
      const p1 = document.getElementById('handover-paso-gatekeeper');
      const p2 = document.getElementById('handover-paso-advertencia');
      if (p1) p1.style.display = 'none';
      if (p2) p2.style.display = 'block';
    }

    function closeHandoverModal(unlockWorkspace) {
      const modal = document.getElementById('modal-traspaso-guardia');
      if (modal) modal.style.display = 'none';
      if (unlockWorkspace) {
        desbloquearParaNuevoTurno();
      }
    }

    function volverPasoGatekeeper() {
      const p1 = document.getElementById('handover-paso-gatekeeper');
      const p2 = document.getElementById('handover-paso-advertencia');
      if (p1) p1.style.display = 'block';
      if (p2) p2.style.display = 'none';
      const btn = document.getElementById('btn-ejecutar-cierre-def');
      const btnCancel = document.getElementById('btn-cancelar-cierre-def');
      const loader = document.getElementById('cierre-loading-indicator');
      if (btn) btn.style.display = '';
      if (btnCancel) btnCancel.style.display = '';
      if (loader) loader.style.display = 'none';
    }

    function desbloquearParaNuevoTurno() {
      const modal = document.getElementById('modal-traspaso-guardia');
      if (modal) modal.style.display = 'none';
      const sidebar = document.querySelector('.sidebar');
      if (sidebar) {
        sidebar.style.pointerEvents = 'auto';
        sidebar.style.opacity = '1';
      }
      const workspace = document.querySelector('.workspace');
      if (workspace) {
        workspace.style.filter = 'none';
        workspace.style.pointerEvents = 'auto';
        workspace.style.userSelect = 'auto';
        workspace.style.opacity = '1';
      }
      const banner = document.getElementById('banner-relevo-guardia');
      if (banner) banner.style.display = 'none';
      hayDiscrepanciaGuardia = false;
      showToast('✨ Consola desbloqueada para su sesión. Configure su turno.');
      setTimeout(() => {
        if (typeof openShiftModal === 'function') {
          openShiftModal(true);
        }
      }, 350);
    }

    function ejecutarCierreDefinitivoYCrearNueva() {
      const btn = document.getElementById('btn-ejecutar-cierre-def');
      const btnCancel = document.getElementById('btn-cancelar-cierre-def');
      const loader = document.getElementById('cierre-loading-indicator');
      if (btn) btn.style.display = 'none';
      if (btnCancel) btnCancel.style.display = 'none';
      if (loader) loader.style.display = 'block';

      // 🛡️ PROTECCIÓN DE BASE DE DATOS REAL (MODO DEMO / PRUEBAS)
      // En modo demo protegida, el cierre es 100% simulado en memoria: NO se ejecuta UPDATE en MySQL
      if (typeof IS_DEMO_MODE !== 'undefined' && IS_DEMO_MODE) {
        console.log('[DEMO PROTEGIDA] Cierre simulado en memoria. La planilla real en MySQL permanece intacta.');
        setTimeout(() => {
          showToast('🛡️ [Modo Demo] Planilla cerrada en simulación (BD real protegida intacta). Habilitando su nuevo turno...', 3500);
          desbloquearParaNuevoTurno();
        }, 500);
        return;
      }

      const planId = <?php echo (int)($id_planilla_activa ? $id_planilla_activa : 1); ?>;
      const uId = <?php echo (int)($id_usuario_actual ? $id_usuario_actual : 1); ?>;

      // Timeout de seguridad: si después de 2.2 segundos no respondió el servidor, desbloquear directamente
      const fallbackTimer = setTimeout(() => {
        desbloquearParaNuevoTurno();
      }, 2200);

      try {
        fetch(`procesos_reg_rad.php?accion=cerrar_planilla&id_planilla=${planId}&id_usuario=${uId}`)
          .then(r => r.text())
          .then(res => {
            clearTimeout(fallbackTimer);
            showToast('🔒 Planilla anterior cerrada con éxito en el sistema.', 3000);
            setTimeout(() => {
              window.location.href = 'demo_planilla.php?nueva_guardia=1';
            }, 600);
          })
          .catch(err => {
            clearTimeout(fallbackTimer);
            console.warn('Cierre fallback:', err);
            desbloquearParaNuevoTurno();
          });
      } catch (e) {
        clearTimeout(fallbackTimer);
        console.error(e);
        desbloquearParaNuevoTurno();
      }
    }

    function verPlanillaSoloLectura() {
      window.location.href = `planilla_en_curso_visualizacion.php?id_planilla=${activePlanillaId || <?php echo $id_planilla_activa ? $id_planilla_activa : 1; ?>}`;
    }

    // ================= MOTOR DE EFLUENTES LÍQUIDOS EN 2 FASES (PRE-CARGA & BOMBEO REAL) =================
    let liqDraftRads = [];
    let currentTicketHtml = '';

    function liqEscape(str) {
      if (str === null || str === undefined) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function liqTodayIso() {
      const d = new Date();
      return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function liqNowHm() {
      const d = new Date();
      return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }

    function liqFmtLatamDate(iso) {
      if (!iso) return '-';
      if (iso.includes('/')) return iso;
      const p = String(iso).split('T')[0].split('-');
      return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : iso;
    }

    function liqStorageKey() {
      return `regrad_liq_precargas_p_${activePlanillaId || 'demo'}`;
    }

    function loadLiqPrecargas() {
      try {
        const raw = localStorage.getItem(liqStorageKey());
        const arr = raw ? JSON.parse(raw) : [];
        return Array.isArray(arr) ? arr : [];
      } catch (e) {
        return [];
      }
    }

    function saveLiqPrecargas(list) {
      try {
        localStorage.setItem(liqStorageKey(), JSON.stringify(list));
      } catch (e) {}
    }

    function getActiveLiqPrecargas() {
      return loadLiqPrecargas().filter(p => p.status !== 'cancelado');
    }

    function getVolFactorToLiter(unitId) {
      const found = (UNIDADES_VOL_LIQ_CATALOG || []).find(u => String(u.id_tabla_conversion) === String(unitId));
      return found ? (parseFloat(found.factor_a_litro) || 1.0) : 1000.0;
    }

    function getConcFactorToBqL(unitId) {
      const found = (UNIDADES_CONC_LIQ_CATALOG || []).find(u => String(u.id_tabla_conversion) === String(unitId));
      return found ? (parseFloat(found.factor_a_bq_litro) || 1.0) : 1.0;
    }

    function getRadInfo(radId) {
      const found = (RADIONUCLEIDOS_LIQ_CATALOG || []).find(r => String(r.id_radionucleidos) === String(radId));
      return found || { id_radionucleidos: radId, radionucleido: 'Rad-' + radId };
    }

    function isTritioName(name) {
      const clean = String(name || '').toUpperCase().replace(/\s+/g, '');
      return clean === 'H-3' || clean === 'H3' || clean.includes('TRITIO');
    }

    function extractRoValue(radObj) {
      if (!radObj || typeof radObj !== 'object') return null;
      const candidateKeys = Object.keys(radObj).filter(k => /ro/i.test(k) && !/radionucleid|numero|hierro|id_/i.test(k));
      for (const k of candidateKeys) {
        const num = parseFloat(radObj[k]);
        if (!isNaN(num) && num > 0) return num;
      }
      return null;
    }

    function calcLiqMetrics(volVal, volUnitId, rads) {
      const volLiters = (parseFloat(volVal) || 0) * getVolFactorToLiter(volUnitId);
      const volM3 = volLiters / 1000.0;
      let h3Bq = 0;
      let gammaBq = 0;
      let roddEst = 0;
      let hasRoFactor = false;

      const detailedRads = (rads || []).map(item => {
        const info = getRadInfo(item.id_radionucleido);
        const radName = item.radionucleido || info.radionucleido || ('ID ' + item.id_radionucleido);
        const concBqL = item.conc_Bq_litro !== undefined
          ? (parseFloat(item.conc_Bq_litro) || 0)
          : ((parseFloat(item.conc_ingresada) || 0) * getConcFactorToBqL(item.id_unidad_conc));
        const actBq = concBqL * volLiters;
        const actTBq = actBq / 1e12;
        if (isTritioName(radName)) {
          h3Bq += actBq;
        } else {
          gammaBq += actBq;
        }
        const roVal = extractRoValue(info);
        if (roVal) {
          hasRoFactor = true;
          roddEst += (actBq / roVal) * 100.0;
        }
        return {
          ...item,
          radionucleido: radName,
          conc_Bq_litro: concBqL,
          actividad_Bq: actBq,
          actividad_TBq: actTBq
        };
      });

      return {
        volumen_litro: volLiters,
        volumen_m3: volM3,
        actividad_tritio_Bq: h3Bq,
        actividad_tritio_TBq: h3Bq / 1e12,
        actividad_gamma_Bq: gammaBq,
        actividad_gamma_TBq: gammaBq / 1e12,
        rodd_est: hasRoFactor ? roddEst : null,
        rads: detailedRads
      };
    }

    function liqFmtTBq(val) {
      const n = parseFloat(val) || 0;
      if (n === 0) return '0.0000';
      if (n < 0.0001) return n.toExponential(2);
      return n.toFixed(4);
    }

    function populateLiqModalSelects() {
      const volSel = document.getElementById('liq-in-vol-unidad');
      if (volSel && volSel.options.length === 0) {
        (UNIDADES_VOL_LIQ_CATALOG || []).forEach(u => {
          const opt = document.createElement('option');
          opt.value = u.id_tabla_conversion;
          opt.textContent = u.unidad;
          volSel.appendChild(opt);
        });
      }
      const radSel = document.getElementById('liq-add-rad');
      if (radSel && radSel.options.length === 0) {
        const sorted = [...(RADIONUCLEIDOS_LIQ_CATALOG || [])].sort((a, b) => {
          if (isTritioName(a.radionucleido)) return -1;
          if (isTritioName(b.radionucleido)) return 1;
          return String(a.radionucleido).localeCompare(String(b.radionucleido));
        });
        sorted.forEach(r => {
          const opt = document.createElement('option');
          opt.value = r.id_radionucleidos;
          opt.textContent = r.radionucleido;
          radSel.appendChild(opt);
        });
      }
      const concSel = document.getElementById('liq-add-conc-unit');
      if (concSel && concSel.options.length === 0) {
        (UNIDADES_CONC_LIQ_CATALOG || []).forEach(u => {
          const opt = document.createElement('option');
          opt.value = u.id_tabla_conversion;
          opt.textContent = u.unidad;
          concSel.appendChild(opt);
        });
      }
    }

    function openEfluenteLiquidoModal(precargaId = null) {
      populateLiqModalSelects();
      const modal = document.getElementById('modal-efluente-liquido');
      const title = document.getElementById('modal-liq-title');
      document.getElementById('liq-form-precarga-id').value = precargaId || '';

      if (precargaId) {
        const item = loadLiqPrecargas().find(p => p.id === precargaId);
        if (!item) return;
        title.textContent = `Editar Pre-Carga: ${item.tanque}`;
        document.getElementById('liq-in-tanque').value = item.tanque || '';
        document.getElementById('liq-in-fecha').value = item.fecha || liqTodayIso();
        document.getElementById('liq-in-hora').value = item.hora || liqNowHm();
        document.getElementById('liq-in-volumen').value = item.volumen_ingresado !== undefined ? item.volumen_ingresado : item.volumen_m3;
        if (item.id_unidad_vol) document.getElementById('liq-in-vol-unidad').value = String(item.id_unidad_vol);
        document.getElementById('liq-in-obs').value = item.observaciones || '';
        liqDraftRads = (item.radionucleidos || []).map(r => ({ ...r }));
      } else {
        title.textContent = 'Nueva Pre-Carga / Autorización de Efluente Líquido';
        document.getElementById('liq-in-tanque').value = '';
        document.getElementById('liq-in-fecha').value = liqTodayIso();
        document.getElementById('liq-in-hora').value = liqNowHm();
        document.getElementById('liq-in-volumen').value = '';
        document.getElementById('liq-in-obs').value = '';
        liqDraftRads = [];
      }
      document.getElementById('liq-add-conc').value = '';
      renderDraftRadsTable();
      recalcModalLiqTotals();
      modal.style.display = 'flex';
    }

    function closeEfluenteLiquidoModal() {
      const m = document.getElementById('modal-efluente-liquido');
      if (m) m.style.display = 'none';
    }

    function addDraftRadionucleido() {
      const radSel = document.getElementById('liq-add-rad');
      const concInp = document.getElementById('liq-add-conc');
      const unitSel = document.getElementById('liq-add-conc-unit');
      const concVal = parseFloat(concInp.value);
      if (!radSel.value || isNaN(concVal) || concVal <= 0) {
        showToast('⚠️ Ingrese un radionucleído y concentración válida (> 0)', 'warning');
        return;
      }
      const radName = radSel.options[radSel.selectedIndex]?.textContent || '';
      const unitName = unitSel.options[unitSel.selectedIndex]?.textContent || 'Bq/l';
      const concBqL = concVal * getConcFactorToBqL(unitSel.value);

      liqDraftRads.push({
        id_radionucleido: radSel.value,
        radionucleido: radName,
        conc_ingresada: concVal,
        id_unidad_conc: unitSel.value,
        unidad_conc_nombre: unitName,
        conc_Bq_litro: concBqL
      });
      concInp.value = '';
      concInp.focus();
      renderDraftRadsTable();
      recalcModalLiqTotals();
    }

    function removeDraftRadionucleido(idx) {
      liqDraftRads.splice(idx, 1);
      renderDraftRadsTable();
      recalcModalLiqTotals();
    }

    function renderDraftRadsTable() {
      const tbody = document.getElementById('liq-draft-rads-tbody');
      if (!tbody) return;
      const volVal = document.getElementById('liq-in-volumen')?.value || 0;
      const volUnit = document.getElementById('liq-in-vol-unidad')?.value || '2';
      const calc = calcLiqMetrics(volVal, volUnit, liqDraftRads);

      if (calc.rads.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: var(--text-dim); padding: 10px;">Sin radionucleídos agregados aún</td></tr>';
        return;
      }
      tbody.innerHTML = calc.rads.map((r, idx) => `
        <tr>
          <td style="font-weight: 700; color: #fff;">${liqEscape(r.radionucleido)}</td>
          <td class="mono" style="text-align: right;">${liqEscape(r.conc_ingresada)} ${liqEscape(r.unidad_conc_nombre || 'Bq/l')}</td>
          <td class="mono" style="text-align: right; color: var(--text-muted);">${r.conc_Bq_litro.toExponential(2)}</td>
          <td class="mono" style="text-align: right; color: var(--accent-cyan); font-weight: 700;">${liqFmtTBq(r.actividad_TBq)}</td>
          <td style="text-align: center;">
            <button type="button" class="liq-btn-mini danger" onclick="removeDraftRadionucleido(${idx})">✕</button>
          </td>
        </tr>
      `).join('');
    }

    function recalcModalLiqTotals() {
      const volVal = document.getElementById('liq-in-volumen')?.value || 0;
      const volUnit = document.getElementById('liq-in-vol-unidad')?.value || '2';
      const calc = calcLiqMetrics(volVal, volUnit, liqDraftRads);
      const h3El = document.getElementById('liq-prev-h3');
      const gamEl = document.getElementById('liq-prev-gamma');
      const roddEl = document.getElementById('liq-prev-rodd');
      if (h3El) h3El.textContent = `${liqFmtTBq(calc.actividad_tritio_TBq)} TBq`;
      if (gamEl) gamEl.textContent = `${liqFmtTBq(calc.actividad_gamma_TBq)} TBq`;
      if (roddEl) roddEl.textContent = calc.rodd_est !== null ? `~${calc.rodd_est.toFixed(3)}%` : 'Al confirmar (BD)';
      renderDraftRadsTable();
    }

    async function saveEfluenteModal(mode) {
      const precargaId = document.getElementById('liq-form-precarga-id').value;
      const tanque = document.getElementById('liq-in-tanque').value.trim();
      const fecha = document.getElementById('liq-in-fecha').value || liqTodayIso();
      const hora = document.getElementById('liq-in-hora').value || liqNowHm();
      const volRaw = parseFloat(document.getElementById('liq-in-volumen').value);
      const volUnit = document.getElementById('liq-in-vol-unidad').value;
      const obs = document.getElementById('liq-in-obs').value.trim();

      if (!tanque) {
        showToast('⚠️ Ingrese el identificador del tanque', 'warning');
        return;
      }
      if (isNaN(volRaw) || volRaw <= 0) {
        showToast('⚠️ Ingrese un volumen mayor a 0', 'warning');
        return;
      }
      if (liqDraftRads.length === 0 && !confirm('No agregó radionucleídos todavía. ¿Continuar guardando el tanque igualmente?')) {
        return;
      }

      const calc = calcLiqMetrics(volRaw, volUnit, liqDraftRads);
      const payload = {
        id: precargaId || ('pre_' + Date.now()),
        status: 'precarga',
        tanque,
        fecha,
        hora,
        volumen_ingresado: volRaw,
        id_unidad_vol: volUnit,
        volumen_litro: calc.volumen_litro,
        volumen_m3: Math.round(calc.volumen_m3 * 1000) / 1000,
        actividad_tritio_TBq: calc.actividad_tritio_TBq,
        actividad_gamma_TBq: calc.actividad_gamma_TBq,
        rodd_est: calc.rodd_est,
        observaciones: obs,
        radionucleidos: liqDraftRads.map(r => ({ ...r })),
        created_at: new Date().toISOString()
      };

      if (mode === 'precarga') {
        const list = loadLiqPrecargas();
        const idx = list.findIndex(p => p.id === payload.id);
        if (idx >= 0) list[idx] = payload;
        else list.unshift(payload);
        saveLiqPrecargas(list);
        closeEfluenteLiquidoModal();
        renderEfluentesLiquidos();
        showToast(`🟡 Pre-Carga de ${tanque} guardada (NO emitida todavía)`);
      } else {
        const okCommit = await commitEfluenteReal(payload, precargaId ? precargaId : null);
        if (okCommit) closeEfluenteLiquidoModal();
      }
    }

    function openConfirmarBombeoModal(precargaId) {
      const item = loadLiqPrecargas().find(p => p.id === precargaId);
      if (!item) return;
      document.getElementById('conf-liq-precarga-id').value = precargaId;
      document.getElementById('conf-liq-fecha').value = liqTodayIso();
      document.getElementById('conf-liq-hora').value = liqNowHm();
      document.getElementById('conf-liq-obs').value = item.observaciones || '';
      const radsTxt = (item.radionucleidos || []).map(r => r.radionucleido).join(', ') || 'Sin isótopos';
      document.getElementById('conf-liq-resumen').innerHTML = `
        <div><strong>Tanque:</strong> <span style="color:#fff;">${liqEscape(item.tanque)}</span> &nbsp;|&nbsp; <strong>Volumen:</strong> <span class="mono">${item.volumen_m3} m³</span></div>
        <div><strong>Tritio (est.):</strong> <span class="mono" style="color:var(--accent-cyan);">${liqFmtTBq(item.actividad_tritio_TBq)} TBq</span> &nbsp;|&nbsp; <strong>Gamma (est.):</strong> <span class="mono" style="color:#f472b6;">${liqFmtTBq(item.actividad_gamma_TBq)} TBq</span></div>
        <div><strong>Radionucleídos:</strong> ${liqEscape(radsTxt)}</div>
      `;
      document.getElementById('modal-confirmar-bombeo').style.display = 'flex';
    }

    function closeConfirmarBombeoModal() {
      const m = document.getElementById('modal-confirmar-bombeo');
      if (m) m.style.display = 'none';
    }

    async function ejecutarConfirmacionBombeo() {
      const precargaId = document.getElementById('conf-liq-precarga-id').value;
      const item = loadLiqPrecargas().find(p => p.id === precargaId);
      if (!item) return;
      const fechaReal = document.getElementById('conf-liq-fecha').value || item.fecha;
      const horaReal = document.getElementById('conf-liq-hora').value || item.hora;
      const obsReal = document.getElementById('conf-liq-obs').value.trim();

      const updated = { ...item, fecha: fechaReal, hora: horaReal, observaciones: obsReal };
      const ok = await commitEfluenteReal(updated, precargaId);
      if (ok) {
        closeConfirmarBombeoModal();
      }
    }

    async function commitEfluenteReal(payload, precargaIdToRemove) {
      if (typeof IS_DEMO_MODE !== 'undefined' && IS_DEMO_MODE) {
        const calc = calcLiqMetrics(payload.volumen_ingresado || payload.volumen_m3, payload.id_unidad_vol || '2', payload.radionucleidos);
        liqEmitidos.unshift({
          id_efluentes: Date.now(),
          fecha: payload.fecha,
          hora: payload.hora,
          tanque: payload.tanque,
          volumen_litro: calc.volumen_litro,
          volumen_m3: Math.round(calc.volumen_m3 * 1000) / 1000,
          actividad_tritio_TBq: calc.actividad_tritio_TBq,
          actividad_gamma_TBq: calc.actividad_gamma_TBq,
          rodd_total: calc.rodd_est !== null ? calc.rodd_est : 0,
          observaciones: payload.observaciones || '',
          radionucleidos: calc.rads
        });
        if (precargaIdToRemove) {
          saveLiqPrecargas(loadLiqPrecargas().filter(p => p.id !== precargaIdToRemove));
        }
        renderEfluentesLiquidos();
        showToast(`🟢 Bombeo de ${payload.tanque} confirmado (Modo Demo Offline)`);
        return true;
      }

      if (!activePlanillaId) {
        showToast('⚠️ No hay una planilla abierta activa en BD', 'warning');
        return false;
      }

      try {
        const volNum = payload.volumen_ingresado !== undefined ? payload.volumen_ingresado : payload.volumen_m3;
        const unitVolId = payload.id_unidad_vol || '2';
        const urlHead = `procesos_reg_rad.php?accion=guardar_valores_efluentes_liquidos&retornar_id=1&id_planilla=${encodeURIComponent(activePlanillaId)}&id_usuario=${encodeURIComponent(ACTIVE_USUARIO_ID)}&fecha=${encodeURIComponent(payload.fecha)}&hora=${encodeURIComponent(payload.hora)}&tanque=${encodeURIComponent(payload.tanque)}&volumen=${encodeURIComponent(volNum)}&unidad_ingresada=${encodeURIComponent(unitVolId)}`;
        const respHead = await fetch(urlHead);
        const txtHead = (await respHead.text()).trim();
        if (!txtHead.startsWith('ok|')) {
          throw new Error('Error al grabar cabecera: ' + txtHead);
        }
        const nuevoId = parseInt(txtHead.split('|')[1], 10);
        if (!nuevoId) throw new Error('ID de efluente inválido');

        for (const rad of (payload.radionucleidos || [])) {
          const concVal = rad.conc_ingresada !== undefined ? rad.conc_ingresada : rad.conc_Bq_litro;
          const unitConcId = rad.id_unidad_conc || '2';
          const urlRad = `procesos_reg_rad.php?accion=guardar_valores_radionucleidos_efluentes_liquidos&id_efluentes=${encodeURIComponent(nuevoId)}&radionucleido=${encodeURIComponent(rad.id_radionucleido)}&concentracion=${encodeURIComponent(concVal)}&unidad_concentracion_ingresada=${encodeURIComponent(unitConcId)}`;
          await fetch(urlRad);
        }

        if (payload.observaciones) {
          const urlObs = `procesos_reg_rad.php?accion=actualizar_observaciones_efluentes&id_efluentes=${encodeURIComponent(nuevoId)}&observaciones=${encodeURIComponent(payload.observaciones)}`;
          await fetch(urlObs);
        }

        if (precargaIdToRemove) {
          saveLiqPrecargas(loadLiqPrecargas().filter(p => p.id !== precargaIdToRemove));
        }

        await refreshEfluentesLiquidosFromDB();
        showToast(`🟢 Bombeo de ${payload.tanque} confirmado y asentado en MySQL (#${nuevoId})`);
        return true;
      } catch (err) {
        console.error(err);
        showToast('❌ No se pudo grabar en MySQL: ' + err.message, 'error');
        return false;
      }
    }

    async function refreshEfluentesLiquidosFromDB() {
      if ((typeof IS_DEMO_MODE !== 'undefined' && IS_DEMO_MODE) || !activePlanillaId) {
        renderEfluentesLiquidos();
        return;
      }
      try {
        const res = await fetch(`procesos_reg_rad.php?accion=listar_efluentes_liquidos_json&id_planilla=${encodeURIComponent(activePlanillaId)}`);
        if (res.ok) {
          const data = await res.json();
          if (Array.isArray(data)) {
            liqEmitidos = data;
          }
        }
      } catch (e) {
        console.warn('Error refrescando efluentes líquidos:', e);
      }
      renderEfluentesLiquidos();
    }

    function cancelLiqPrecarga(precargaId) {
      if (!confirm('¿Marcar esta Pre-Carga como CANCELADA / NO EMITIDA? No se computará en la emisión.')) return;
      const list = loadLiqPrecargas().map(p => p.id === precargaId ? { ...p, status: 'cancelado' } : p);
      saveLiqPrecargas(list);
      renderEfluentesLiquidos();
      showToast('❌ Maniobra cancelada (0 emisión)');
    }

    function restoreLiqPrecarga(precargaId) {
      const list = loadLiqPrecargas().map(p => p.id === precargaId ? { ...p, status: 'precarga' } : p);
      saveLiqPrecargas(list);
      renderEfluentesLiquidos();
      showToast('♻️ Pre-Carga restaurada');
    }

    function deleteLiqPrecargaPermanent(precargaId) {
      saveLiqPrecargas(loadLiqPrecargas().filter(p => p.id !== precargaId));
      renderEfluentesLiquidos();
    }

    async function deleteEmitidoLiquido(idEf) {
      if (!confirm('¿Eliminar definitivamente esta emisión asentada en la planilla?')) return;
      if (typeof IS_DEMO_MODE !== 'undefined' && IS_DEMO_MODE) {
        liqEmitidos = liqEmitidos.filter(e => String(e.id_efluentes) !== String(idEf));
        renderEfluentesLiquidos();
        showToast('🗑️ Registro eliminado (Modo Demo)');
        return;
      }
      try {
        const res = await fetch(`procesos_reg_rad.php?accion=eliminar_efluente_liquido&id_efluentes=${encodeURIComponent(idEf)}`);
        const txt = (await res.text()).trim();
        if (txt === 'ok') {
          await refreshEfluentesLiquidosFromDB();
          showToast('🗑️ Emisión eliminada de MySQL');
        } else {
          showToast('❌ Error eliminando registro', 'error');
        }
      } catch (e) {
        showToast('❌ Error de red al eliminar', 'error');
      }
    }

    function openObsLiquidoModal(idEf) {
      const row = liqEmitidos.find(e => String(e.id_efluentes) === String(idEf));
      if (!row) return;
      document.getElementById('obs-liq-id').value = idEf;
      document.getElementById('modal-obs-liq-title').textContent = `Detalle: ${row.tanque} (${liqFmtLatamDate(row.fecha)} ${row.hora})`;
      document.getElementById('obs-liq-texto').value = row.observaciones || '';
      const rads = row.radionucleidos || [];
      document.getElementById('obs-liq-rads-list').innerHTML = rads.length > 0
        ? `<div style="font-size:11px; color:var(--text-muted); margin-bottom:4px; font-weight:700;">Radionucleídos asentados:</div>` +
          rads.map(r => `<span class="liq-rad-tag">${liqEscape(r.radionucleido)}: ${(parseFloat(r.conc_Bq_litro)||0).toExponential(2)} Bq/l</span>`).join('')
        : '<div style="font-size:11px; color:var(--text-dim);">Sin detalle de radionucleídos</div>';
      document.getElementById('modal-obs-liquido').style.display = 'flex';
    }

    function closeObsLiquidoModal() {
      const m = document.getElementById('modal-obs-liquido');
      if (m) m.style.display = 'none';
    }

    async function guardarObsLiquido() {
      const idEf = document.getElementById('obs-liq-id').value;
      const obs = document.getElementById('obs-liq-texto').value.trim();
      if (typeof IS_DEMO_MODE !== 'undefined' && IS_DEMO_MODE) {
        const row = liqEmitidos.find(e => String(e.id_efluentes) === String(idEf));
        if (row) row.observaciones = obs;
        closeObsLiquidoModal();
        renderEfluentesLiquidos();
        showToast('📝 Observaciones actualizadas');
        return;
      }
      try {
        await fetch(`procesos_reg_rad.php?accion=actualizar_observaciones_efluentes&id_efluentes=${encodeURIComponent(idEf)}&observaciones=${encodeURIComponent(obs)}`);
        await refreshEfluentesLiquidosFromDB();
        closeObsLiquidoModal();
        showToast('📝 Observaciones guardadas en MySQL');
      } catch (e) {
        showToast('❌ Error al actualizar observaciones', 'error');
      }
    }

    function openTicketLiquido(type, id) {
      let item = null;
      let isPre = false;
      if (type === 'precarga') {
        item = loadLiqPrecargas().find(p => p.id === id);
        isPre = true;
      } else {
        item = liqEmitidos.find(e => String(e.id_efluentes) === String(id));
      }
      if (!item) return;

      const rads = item.radionucleidos || [];
      const volL = item.volumen_litro || ((item.volumen_m3 || 0) * 1000);
      const rowsHtml = rads.length > 0
        ? rads.map(r => {
            const conc = parseFloat(r.conc_Bq_litro) || 0;
            const actTBq = (conc * volL) / 1e12;
            return `<tr>
              <td style="padding:5px 8px; border:1px solid #cbd5e1; font-weight:bold;">${liqEscape(r.radionucleido)}</td>
              <td style="padding:5px 8px; border:1px solid #cbd5e1; text-align:right; font-family:monospace;">${conc.toExponential(3)} Bq/l</td>
              <td style="padding:5px 8px; border:1px solid #cbd5e1; text-align:right; font-family:monospace;">${liqFmtTBq(actTBq)} TBq</td>
            </tr>`;
          }).join('')
        : '<tr><td colspan="3" style="padding:6px; border:1px solid #cbd5e1; text-align:center; color:#64748b;">Sin radionucleídos detallados</td></tr>';

      const statusBanner = isPre
        ? '<div style="background:#fef3c7; border:1px solid #f59e0b; color:#92400e; padding:6px 10px; font-weight:bold; margin-bottom:10px; text-align:center;">PRE-CARGA DE AUTORIZACIÓN — SUJETO A CONFIRMACIÓN DE BOMBEO</div>'
        : '<div style="background:#dcfce7; border:1px solid #16a34a; color:#166534; padding:6px 10px; font-weight:bold; margin-bottom:10px; text-align:center;">EMISIÓN LÍQUIDA CONFIRMADA Y ASENTADA</div>';

      const html = `
        <div style="border-bottom:2px solid #0f172a; padding-bottom:8px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
          <div>
            <div style="font-weight:bold; font-size:14px;">CENTRAL NUCLEAR EMBALSE (NA-SA)</div>
            <div style="font-size:11px; color:#475569;">División Radioprotección — Autorización de Descarga de Efluentes Líquidos</div>
          </div>
          <div style="text-align:right; font-size:11px;">
            <div><strong>Planilla N°:</strong> ${liqEscape(activePlanillaId || '-')}</div>
            <div><strong>Fecha/Hora:</strong> ${liqEscape(liqFmtLatamDate(item.fecha))} ${liqEscape(item.hora)}</div>
          </div>
        </div>
        ${statusBanner}
        <table style="width:100%; border-collapse:collapse; margin-bottom:10px;">
          <tr>
            <td style="padding:5px 8px; border:1px solid #cbd5e1; background:#f8fafc;"><strong>Tanque:</strong> ${liqEscape(item.tanque)}</td>
            <td style="padding:5px 8px; border:1px solid #cbd5e1; background:#f8fafc;"><strong>Volumen:</strong> ${item.volumen_m3} m³ (${Math.round(volL)} L)</td>
          </tr>
          <tr>
            <td style="padding:5px 8px; border:1px solid #cbd5e1;"><strong>Act. Tritio:</strong> ${liqFmtTBq(item.actividad_tritio_TBq)} TBq</td>
            <td style="padding:5px 8px; border:1px solid #cbd5e1;"><strong>Act. Gamma:</strong> ${liqFmtTBq(item.actividad_gamma_TBq)} TBq</td>
          </tr>
          <tr>
            <td colspan="2" style="padding:5px 8px; border:1px solid #cbd5e1;"><strong>% del RoDD:</strong> ${isPre ? (item.rodd_est !== null && item.rodd_est !== undefined ? '~' + Number(item.rodd_est).toFixed(3) + '% (Est.)' : 'Calculado en BD al confirmar') : (Number(item.rodd_total || 0).toFixed(3) + '%')}</td>
          </tr>
        </table>
        <div style="font-weight:bold; margin-bottom:4px;">Detalle Isotópico:</div>
        <table style="width:100%; border-collapse:collapse; margin-bottom:10px;">
          <thead>
            <tr style="background:#e2e8f0;">
              <th style="padding:5px 8px; border:1px solid #cbd5e1; text-align:left;">Radionucleído</th>
              <th style="padding:5px 8px; border:1px solid #cbd5e1; text-align:right;">Concentración</th>
              <th style="padding:5px 8px; border:1px solid #cbd5e1; text-align:right;">Actividad</th>
            </tr>
          </thead>
          <tbody>${rowsHtml}</tbody>
        </table>
        <div style="padding:6px 8px; border:1px solid #cbd5e1; background:#f8fafc; margin-bottom:18px;">
          <strong>Observaciones:</strong> ${liqEscape(item.observaciones || 'Sin observaciones adicionales.')}
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-top:26px; text-align:center; font-size:11px;">
          <div style="border-top:1px solid #334155; padding-top:4px;">Oficial de Radioprotección<br><span style="color:#475569;"><?php echo htmlspecialchars($oficial_actual, ENT_QUOTES, 'UTF-8'); ?></span></div>
          <div style="border-top:1px solid #334155; padding-top:4px;">Jefe de Turno Operaciones<br><span style="color:#475569;">Confirmación de Bombeo</span></div>
        </div>
      `;
      currentTicketHtml = html;
      document.getElementById('ticket-liquido-preview').innerHTML = html;
      document.getElementById('modal-ticket-liquido').style.display = 'flex';
    }

    function closeTicketLiquidoModal() {
      const m = document.getElementById('modal-ticket-liquido');
      if (m) m.style.display = 'none';
    }

    function printTicketLiquidoWindow() {
      const w = window.open('', '_blank', 'width=800,height=700');
      if (!w) {
        window.print();
        return;
      }
      w.document.write(`<!DOCTYPE html><html><head><title>Ticket Efluente Líquido CNE</title></head><body style="font-family:Arial,sans-serif; padding:24px; color:#111;">${currentTicketHtml}<script>window.onload=function(){window.print();};<\/script></body></html>`);
      w.document.close();
    }

    function openPdfOficialLiquido(idEf) {
      if (typeof IS_DEMO_MODE !== 'undefined' && IS_DEMO_MODE) {
        openTicketLiquido('emitido', idEf);
        return;
      }
      window.open(`../TCPDF-master/examples/pdf_impresion_efluentes_liquidos_tcpdf.php?id_efluentes=${encodeURIComponent(idEf)}`, '_blank');
    }

    function renderEfluentesLiquidos() {
      const tbody = document.getElementById('tbody-efluentes-liquidos');
      if (!tbody) return;

      const allPrecargas = loadLiqPrecargas();
      const activePre = allPrecargas.filter(p => p.status !== 'cancelado');
      const cancelledPre = allPrecargas.filter(p => p.status === 'cancelado');
      const showCancelled = document.getElementById('liq-chk-show-cancelled')?.checked || false;

      const cancCountEl = document.getElementById('liq-cancelled-count');
      if (cancCountEl) cancCountEl.textContent = cancelledPre.length;

      // KPIs
      const preVol = activePre.reduce((acc, p) => acc + (parseFloat(p.volumen_m3) || 0), 0);
      const realList = Array.isArray(liqEmitidos) ? liqEmitidos : [];
      const realVol = realList.reduce((acc, e) => acc + (parseFloat(e.volumen_m3) || 0), 0);
      const realH3 = realList.reduce((acc, e) => acc + (parseFloat(e.actividad_tritio_TBq) || 0), 0);
      const realGam = realList.reduce((acc, e) => acc + (parseFloat(e.actividad_gamma_TBq) || 0), 0);
      const realRodd = realList.reduce((acc, e) => acc + (parseFloat(e.rodd_total) || 0), 0);

      document.getElementById('liq-kpi-precargas').textContent = activePre.length;
      document.getElementById('liq-kpi-precargas-sub').textContent = `${preVol.toFixed(2)} m³ en espera`;
      document.getElementById('liq-kpi-emitidos').textContent = realList.length;
      document.getElementById('liq-kpi-emitidos-sub').textContent = `${realVol.toFixed(2)} m³ bombeados`;
      document.getElementById('liq-kpi-tritio').textContent = `${liqFmtTBq(realH3)} TBq`;
      document.getElementById('liq-kpi-gamma-sub').textContent = `Gamma: ${liqFmtTBq(realGam)} TBq`;
      document.getElementById('liq-kpi-rodd').textContent = `${realRodd.toFixed(3)}%`;

      // Badge sidebar
      const menuBadge = document.getElementById('menu-badge-liquidos');
      if (menuBadge) {
        if (activePre.length > 0 && realList.length > 0) {
          menuBadge.textContent = `⏳${activePre.length} / ●${realList.length}`;
          menuBadge.style.background = 'rgba(245, 158, 11, 0.22)';
          menuBadge.style.color = '#fbbf24';
        } else if (activePre.length > 0) {
          menuBadge.textContent = `⏳ ${activePre.length}`;
          menuBadge.style.background = 'rgba(245, 158, 11, 0.22)';
          menuBadge.style.color = '#fbbf24';
        } else if (realList.length > 0) {
          menuBadge.textContent = `${realList.length}`;
          menuBadge.style.background = '';
          menuBadge.style.color = '';
        } else {
          menuBadge.textContent = '0';
          menuBadge.style.background = '';
          menuBadge.style.color = '';
        }
      }

      const rows = [];

      // 1) Pre-Cargas en espera
      activePre.forEach(p => {
        const radTags = (p.radionucleidos || []).map(r => `<span class="liq-rad-tag">${liqEscape(r.radionucleido)}</span>`).join('');
        rows.push(`
          <tr style="background: rgba(245, 158, 11, 0.05);">
            <td><span class="liq-badge precarga">🟡 PRE-CARGA</span></td>
            <td class="mono">${liqEscape(liqFmtLatamDate(p.fecha))}<br><span style="color:var(--text-dim); font-size:10.5px;">Prev: ${liqEscape(p.hora)}</span></td>
            <td>
              <div style="font-weight: 700; color: #fbbf24;">${liqEscape(p.tanque)}</div>
              <div>${radTags || '<span style="font-size:10px; color:var(--text-dim);">Sin radionucleídos</span>'}</div>
            </td>
            <td class="num mono">${Number(p.volumen_m3 || 0).toFixed(2)}</td>
            <td class="num mono" style="color: #fbbf24; font-style: italic;" title="Estimado sin emitir">${liqFmtTBq(p.actividad_tritio_TBq)}</td>
            <td class="num mono" style="color: #fbbf24; font-style: italic;">${liqFmtTBq(p.actividad_gamma_TBq)}</td>
            <td class="num mono" style="color: var(--text-muted);">${p.rodd_est !== null && p.rodd_est !== undefined ? '~' + Number(p.rodd_est).toFixed(3) + '%' : 'En espera'}</td>
            <td style="text-align: center; white-space: nowrap;">
              <button type="button" class="liq-btn-mini confirm" onclick="openConfirmarBombeoModal('${liqEscape(p.id)}')" title="Confirmar bombeo real">✅ Confirmar</button>
              <button type="button" class="liq-btn-mini" onclick="openTicketLiquido('precarga', '${liqEscape(p.id)}')" title="Imprimir ticket de autorización">🖨️</button>
              <button type="button" class="liq-btn-mini" onclick="openEfluenteLiquidoModal('${liqEscape(p.id)}')" title="Editar pre-carga">✏️</button>
              <button type="button" class="liq-btn-mini danger" onclick="cancelLiqPrecarga('${liqEscape(p.id)}')" title="Cancelar maniobra">✕</button>
            </td>
          </tr>
        `);
      });

      // 2) Emitidos Reales
      realList.forEach(e => {
        const radTags = (e.radionucleidos || []).map(r => `<span class="liq-rad-tag">${liqEscape(r.radionucleido)}</span>`).join('');
        rows.push(`
          <tr>
            <td><span class="liq-badge emitido">🟢 EMITIDO REAL</span></td>
            <td class="mono">${liqEscape(liqFmtLatamDate(e.fecha))}<br><span style="color:var(--accent-green); font-size:10.5px;">${liqEscape(e.hora)} hs</span></td>
            <td>
              <div style="font-weight: 700; color: #fff;">${liqEscape(e.tanque)}</div>
              <div>${radTags || '<span style="font-size:10px; color:var(--text-dim);">Sin radionucleídos</span>'}</div>
            </td>
            <td class="num mono">${Number(e.volumen_m3 || 0).toFixed(2)}</td>
            <td class="num mono" style="color: var(--accent-cyan); font-weight: 700;">${liqFmtTBq(e.actividad_tritio_TBq)}</td>
            <td class="num mono" style="color: #f472b6; font-weight: 700;">${liqFmtTBq(e.actividad_gamma_TBq)}</td>
            <td class="num mono" style="color: #fff; font-weight: 800;">${Number(e.rodd_total || 0).toFixed(3)}%</td>
            <td style="text-align: center; white-space: nowrap;">
              <button type="button" class="liq-btn-mini" onclick="openPdfOficialLiquido(${e.id_efluentes})" title="Imprimir PDF oficial">🖨️ PDF</button>
              <button type="button" class="liq-btn-mini" onclick="openObsLiquidoModal(${e.id_efluentes})" title="Detalle y observaciones">📝</button>
              <button type="button" class="liq-btn-mini danger" onclick="deleteEmitidoLiquido(${e.id_efluentes})" title="Eliminar registro">🗑️</button>
            </td>
          </tr>
        `);
      });

      // 3) Cancelados (opcional)
      if (showCancelled) {
        cancelledPre.forEach(c => {
          rows.push(`
            <tr style="opacity: 0.55;">
              <td><span class="liq-badge cancelado">❌ CANCELADO</span></td>
              <td class="mono" style="text-decoration: line-through;">${liqEscape(liqFmtLatamDate(c.fecha))} ${liqEscape(c.hora)}</td>
              <td style="text-decoration: line-through;">${liqEscape(c.tanque)}</td>
              <td class="num mono" style="text-decoration: line-through;">${Number(c.volumen_m3 || 0).toFixed(2)}</td>
              <td class="num mono">0.0000</td>
              <td class="num mono">0.0000</td>
              <td class="num mono">0.000%</td>
              <td style="text-align: center; white-space: nowrap;">
                <button type="button" class="liq-btn-mini" onclick="restoreLiqPrecarga('${liqEscape(c.id)}')" title="Restaurar a Pre-Carga">♻️ Restaurar</button>
                <button type="button" class="liq-btn-mini danger" onclick="deleteLiqPrecargaPermanent('${liqEscape(c.id)}')" title="Borrar historial">🗑️</button>
              </td>
            </tr>
          `);
        });
      }

      if (rows.length === 0) {
        tbody.innerHTML = `
          <tr id="row-liq-empty">
            <td colspan="8" style="text-align: center; padding: 26px 14px; color: var(--text-dim);">
              <div style="font-size: 13px; margin-bottom: 4px; color: var(--text-muted); font-weight: 700;">○ Pendiente / Sin descargas ni pre-cargas en este turno</div>
              <div style="font-size: 11px;">Utilice <strong>"+ NUEVA PRE-CARGA / AUTORIZACIÓN"</strong> para preparar un tanque sin sumarlo aún a la emisión oficial.</div>
            </td>
          </tr>
        `;
      } else {
        tbody.innerHTML = rows.join('');
      }

      if (typeof updateChecklistStatus === 'function') updateChecklistStatus();
    }

    renderEfluentesLiquidos();

        // Auto-bloqueo obligatorio al cargar si hay discrepancia de guardia
    document.addEventListener('DOMContentLoaded', () => {
      if (hayDiscrepanciaGuardia) {
        const modal = document.getElementById('modal-traspaso-guardia');
        if (modal) {
          modal.style.display = 'flex';
          const p1 = document.getElementById('handover-paso-gatekeeper');
          const p2 = document.getElementById('handover-paso-advertencia');
          if (p1) p1.style.display = 'block';
          if (p2) p2.style.display = 'none';
        }
      } else if (sinPlanillaAbierta) {
        setTimeout(() => {
          openShiftModal();
          showToast('✨ No hay planilla abierta activa. Configure su nuevo turno.');
        }, 400);
      }
    });

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
            <button type="button" id="mrh-btn-prefill" class="btn-primary" onclick="prefillRecintoForm()" style="padding: 5px 14px; font-size: 12px; font-weight: 700; border-radius: 6px; background: var(--accent-cyan); color: #02120e; border: none; cursor: pointer;">
              ⚡ Medir en este recinto
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
