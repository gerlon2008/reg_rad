<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/miconex.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/ptr/Functions/funciones.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/niveles.php");

$dbh2 = mysql_connect('GTI-DC-V77', 'ccyrem', 'Roma2020', true);

mysql_select_db('ccyrem_online', $dbh2);

mysql_select_db($database_miconex, $miconex);
mysql_query("SET NAMES 'utf8'", $miconex);

$fecha_de_ahora = date_create();


$accion = $_GET['accion'];

if ($accion == "crear_planilla_nueva") {

	$query_planilla_diaria = "SELECT id_planilla_diaria FROM `regrad_planilla_diaria_nueva` order by id_planilla_diaria desc limit 1";
	$planilla_diaria = mysql_query($query_planilla_diaria, $miconex) or die(mysql_error());
	$row_planilla_diaria = mysql_fetch_assoc($planilla_diaria);

	if ($row_planilla_diaria['id_planilla_diaria'] == 0) {
		$id_nueva_planilla = 1;
	} else {
		$id_nueva_planilla = $row_planilla_diaria['id_planilla_diaria'] + 1;
	}

	$id_user = $_GET['id_user'];
	$id_oficial = $_GET['id_oficial'];
	$id_turno = $_GET['id_turno'];
	$fecha_planilla = $_GET['fecha_planilla'];
	$id_jefe_turno = $_GET['id_jefe_turno'];
	$estados_planta = $_GET['estados_planta'];

	$query_crear_planilla_diaria = "INSERT INTO `regrad_planilla_diaria_nueva`(`id_planilla_diaria`, `fecha`, `turno`, `of_rad`, `jefe_turno`, `estado_planta`, `usuario_portal_abierto`) VALUES ('$id_nueva_planilla','$fecha_planilla', '$id_turno', '$id_oficial', '$id_jefe_turno', '$estados_planta', '$id_user')";
	$crear_planilla_diaria = mysql_query($query_crear_planilla_diaria, $miconex);
	if ($crear_planilla_diaria) {
		echo $id_nueva_planilla;
	} else {
		echo "mal";
	}
}
if ($accion == "guardar_valores_rutina") {
	$id_user = $_GET['id_user'];
	$fecha = $_GET['fecha'];
	$hora = $_GET['hora'];
	$ubicacion = $_GET['ubicacion'];
	$gases_nobles = $_GET['gases_nobles'];
	$tritio = $_GET['tritio'];
	$tasa_dosis = $_GET['tasa_dosis'];
	$observaciones = $_GET['observaciones'];
	$relevado_por = $_GET['relevado_por'];
	$id_planilla = $_GET['id_planilla'];

	$query_guardar_valores_rutina = "INSERT INTO `regrad_relevamiento_rutina`(`id_planilla`, `fecha`, `hora`, `ubicacion`, `gases_nobles`, `tritio`, `tasa_dosis`, `observaciones`, `id_oficial`, `usuario_portal`) VALUES ('$id_planilla','$fecha','$hora','$ubicacion',$gases_nobles,$tritio,$tasa_dosis,'$observaciones','$relevado_por','$id_user')";
	$guardar_valores_rutina = mysql_query($query_guardar_valores_rutina, $miconex) or die(mysql_error());
	if ($guardar_valores_rutina) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "cargar_valores_rutina") {
	$id_planilla = $_GET['id_planilla'];

	$query_cargar_valores_rutina = "SELECT * FROM regrad_relevamiento_rutina a left join regrad_of_rad b on b.id_of_rad = a.id_oficial left join graf_tritio_recintos c on c.id = a.ubicacion where id_planilla = $id_planilla order by fecha, hora";
	$cargar_valores_rutina = mysql_query($query_cargar_valores_rutina, $miconex) or die(mysql_error());
	$row_cargar_valores_rutina = mysql_fetch_assoc($cargar_valores_rutina);
	$totalRows_cargar_valores_rutina = mysql_num_rows($cargar_valores_rutina);

	if ($totalRows_cargar_valores_rutina == 0) {
		echo "sin_datos";
	} else {
		do {
			echo formafecha12($row_cargar_valores_rutina['fecha']) . "@" . formato_hora_2($row_cargar_valores_rutina['hora']) . "@" . $row_cargar_valores_rutina['recinto'] . "@" . nulo(decimales($row_cargar_valores_rutina['gases_nobles'], 1)) . "@" . nulo(decimales($row_cargar_valores_rutina['tritio'], 1)) . "@" . nulo(decimales($row_cargar_valores_rutina['tasa_dosis'], 1)) . "@" . nulo_observaciones($row_cargar_valores_rutina['observaciones']) . "@" . $row_cargar_valores_rutina['of_apellido'] . ", " . $row_cargar_valores_rutina['of_nombre'] . "@" . ($row_cargar_valores_rutina['id_relev_rutina']) . "@@";
		} while ($row_cargar_valores_rutina = mysql_fetch_assoc($cargar_valores_rutina));
	}
}
if ($accion == "obtener_fecha_actual") {
	$fecha_actual = (new \DateTime())->format('Y-m-d');
	echo $fecha_actual;
}
if ($accion == "obtener_hora_actual") {
	$hora_actual = (new \DateTime())->format('H:i');
	echo $hora_actual;
}
if ($accion == "cerrar_planilla") {
	$id_planilla = $_GET['id_planilla'];
	$id_usuario = $_GET['id_usuario'];
	$timestamp = (new \DateTime())->format('Y-m-d H:i:s');

	$query_cargar_valores_rutina = "UPDATE `regrad_planilla_diaria_nueva` SET `fecha_hora_cerrado` = '$timestamp', `usuario_portal_cerrado` = '$id_usuario' where id_planilla_diaria = $id_planilla";
	$cargar_valores_rutina = mysql_query($query_cargar_valores_rutina, $miconex) or die(mysql_error());

	if ($cargar_valores_rutina) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "guardar_valores_gases_nobles") {
	$id_planilla = $_GET['id_planilla'];
	$id_usuario = $_GET['id_usuario'];
	$fecha = $_GET['fecha'];
	$desde = $_GET['desde'];
	$hasta = $_GET['hasta'];
	$ar41 = vacio5(conversion_actividad('5', '1', $_GET['ar41']));
	$kr85 = vacio5(conversion_actividad('5', '1', $_GET['kr85']));
	$kr85m = vacio5(conversion_actividad('5', '1', $_GET['kr85m']));
	$xe133 = vacio5(conversion_actividad('5', '1', $_GET['xe133']));
	$xe135 = vacio5(conversion_actividad('5', '1', $_GET['xe135']));
	$xe135m = vacio5(conversion_actividad('5', '1', $_GET['xe135m']));

	$datos_log = "Creación:" . date_format($fecha_de_ahora, 'd-m-Y H:i:s') . " usuario:" . nombre_usuario_web($id_usuario) . "///" . $fecha . "///" . $desde . "///" . $hasta . "///" . $ar41 . "///" . $kr85 . "///" . $kr85m . "///" . $xe133 . "///" . $xe135 . "///" . $xe135m;

	$query_cargar_valores_gases_nobles = "INSERT INTO `regrad_gases_nobles`(`id_planilla`, `fecha`, `hora_desde`, `hora_hasta`, `ar41`, `kr85`, `kr85m`, `xe133`, `xe135`, `xe135m`, `usuario`, `log_modificaciones`) VALUES ('$id_planilla','$fecha','$desde','$hasta','$ar41','$kr85','$kr85m','$xe133','$xe135','$xe135m','$id_usuario','$datos_log')";
	$cargar_valores_gases_nobles = mysql_query($query_cargar_valores_gases_nobles, $miconex) or die(mysql_error());

	if ($cargar_valores_gases_nobles) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "editar_valores_gases_nobles") {
	$id_planilla = $_GET['id_planilla'];
	$id_usuario = $_GET['id_usuario'];
	$fecha = $_GET['fecha'];
	$desde = $_GET['desde'];
	$hasta = $_GET['hasta'];
	$ar41 = vacio5(conversion_actividad('5', '1', $_GET['ar41']));
	$kr85 = vacio5(conversion_actividad('5', '1', $_GET['kr85']));
	$kr85m = vacio5(conversion_actividad('5', '1', $_GET['kr85m']));
	$xe133 = vacio5(conversion_actividad('5', '1', $_GET['xe133']));
	$xe135 = vacio5(conversion_actividad('5', '1', $_GET['xe135']));
	$xe135m = vacio5(conversion_actividad('5', '1', $_GET['xe135m']));

	$query_recuperar_datos_gases_nobles = "SELECT * FROM `regrad_gases_nobles` where id_planilla = $id_planilla";
	$recuperar_datos_gases_nobles = mysql_query($query_recuperar_datos_gases_nobles, $miconex) or die(mysql_error());
	$row_recuperar_datos_gases_nobles = mysql_fetch_assoc($recuperar_datos_gases_nobles);

	$datos_log = $row_recuperar_datos_gases_nobles['log_modificaciones'] . "||" . date_format($fecha_de_ahora, 'd-m-Y H:i:s') . " usuario:" . nombre_usuario_web($id_usuario) . "///" . $fecha . "///" . $desde . "///" . $hasta . "///" . $ar41 . "///" . $kr85 . "///" . $kr85m . "///" . $xe133 . "///" . $xe135 . "///" . $xe135m;

	$query_editar_valores_gases_nobles = "UPDATE `regrad_gases_nobles` SET `fecha`='$fecha',`hora_desde`='$desde',`hora_hasta`='$hasta',`ar41`='$ar41',`kr85`='$kr85',`kr85m`='$kr85m',`xe133`='$xe133',`xe135`='$xe135',`xe135m`='$xe135m',`log_modificaciones`='$datos_log' WHERE id_planilla = '$id_planilla'";
	$editar_valores_gases_nobles = mysql_query($query_editar_valores_gases_nobles, $miconex) or die(mysql_error());

	if ($editar_valores_gases_nobles) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "recuperar_valores_gases_nobles") {
	$id_planilla = $_GET['id_planilla'];

	$query_recuperar_datos_gases_nobles = "SELECT * FROM `regrad_gases_nobles` where id_planilla = $id_planilla";
	$recuperar_datos_gases_nobles = mysql_query($query_recuperar_datos_gases_nobles, $miconex) or die(mysql_error());
	$row_recuperar_datos_gases_nobles = mysql_fetch_assoc($recuperar_datos_gases_nobles);
	$totalRows_recuperar_datos_gases_nobles = mysql_num_rows($recuperar_datos_gases_nobles);

	if ($totalRows_recuperar_datos_gases_nobles == 0) {
		echo "sin_datos";
	} else {
		do {
			echo formato_hora_personalizado($row_recuperar_datos_gases_nobles['hora_desde']) . "@" . formato_hora_personalizado($row_recuperar_datos_gases_nobles['hora_hasta']) . "@" . (decimales(conversion_actividad('1', '5', $row_recuperar_datos_gases_nobles['ar41']), 1)) . "@" . decimales(conversion_actividad('1', '5', $row_recuperar_datos_gases_nobles['kr85']), 1) . "@" . decimales(conversion_actividad('1', '5', $row_recuperar_datos_gases_nobles['kr85m']), 1) . "@" . decimales(conversion_actividad('1', '5', $row_recuperar_datos_gases_nobles['xe133']), 1) . "@" . decimales(conversion_actividad('1', '5', $row_recuperar_datos_gases_nobles['xe135']), 1) . "@" . decimales(conversion_actividad('1', '5', $row_recuperar_datos_gases_nobles['xe135m']), 1) . "@" . formafecha6($row_recuperar_datos_gases_nobles['fecha']) . "@" . ($row_recuperar_datos_gases_nobles['fecha']) . "@" . coma_punto(formato_hora_personalizado($row_recuperar_datos_gases_nobles['hora_desde'])) . "@" . coma_punto(formato_hora_personalizado($row_recuperar_datos_gases_nobles['hora_hasta'])) . "@" . coma_punto(decimales(conversion_actividad('1', '5', $row_recuperar_datos_gases_nobles['ar41']), 1)) . "@" . coma_punto(decimales(conversion_actividad('1', '5', $row_recuperar_datos_gases_nobles['kr85']), 1)) . "@" . coma_punto(decimales(conversion_actividad('1', '5', $row_recuperar_datos_gases_nobles['kr85m']), 1)) . "@" . coma_punto(decimales(conversion_actividad('1', '5', $row_recuperar_datos_gases_nobles['xe133']), 1)) . "@" . coma_punto(decimales(conversion_actividad('1', '5', $row_recuperar_datos_gases_nobles['xe135']), 1)) . "@" . coma_punto(decimales(conversion_actividad('1', '5', $row_recuperar_datos_gases_nobles['xe135m']), 1)) . "@" . $row_recuperar_datos_gases_nobles['id_gases_nobles'];
		} while ($row_recuperar_datos_gases_nobles = mysql_fetch_assoc($recuperar_datos_gases_nobles));
	}
}
if ($accion == "guardar_valores_emision_tritio") {
	$id_planilla = $_GET['id_planilla'];
	$id_usuario = $_GET['id_usuario'];
	$fecha = $_GET['fecha'];
	$desde = $_GET['desde'];
	$hasta = $_GET['hasta'];
	$vent_gral_unidad_actividad = $_GET['vent_gral_unidad_actividad'];
	$vent_reactor_unidad_actividad = $_GET['vent_reactor_unidad_actividad'];
	$vent_gral = vacio5(conversion_actividad($vent_gral_unidad_actividad, '1', $_GET['vent_gral']));
	$vent_reactor = vacio5(conversion_actividad($vent_reactor_unidad_actividad, '1', $_GET['vent_reactor']));

	$query_guardar_valores_emision_tritio = "INSERT INTO `regrad_tritio`(`id_planilla`, `fecha`, `hora_desde`, `hora_hasta`, `vent_gral`, `vent_reactor`, `usuario_portal`) VALUES ('$id_planilla','$fecha','$desde','$hasta','$vent_gral','$vent_reactor','$id_usuario')";
	$guardar_valores_emision_tritio = mysql_query($query_guardar_valores_emision_tritio, $miconex) or die(mysql_error());

	if ($guardar_valores_emision_tritio) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "actualizar_valores_emision_tritio_edicion") {
	$id_emision_tritio = $_GET['id_emision_tritio'];
	$fecha = $_GET['fecha'];
	$desde = $_GET['desde'];
	$hasta = $_GET['hasta'];
	$vent_gral_unidad_actividad = $_GET['vent_gral_unidad_actividad'];
	$vent_reactor_unidad_actividad = $_GET['vent_reactor_unidad_actividad'];
	$vent_gral = vacio5(conversion_actividad($vent_gral_unidad_actividad, '1', $_GET['vent_gral']));
	$vent_reactor = vacio5(conversion_actividad($vent_reactor_unidad_actividad, '1', $_GET['vent_reactor']));

	$query_guardar_valores_emision_tritio = "UPDATE `regrad_tritio` SET `fecha`= '$fecha',`hora_desde`= '$desde',`hora_hasta`= '$hasta',`vent_gral`= '$vent_gral',`vent_reactor`= '$vent_reactor' WHERE id_tritio = $id_emision_tritio";
	$guardar_valores_emision_tritio = mysql_query($query_guardar_valores_emision_tritio, $miconex) or die(mysql_error());

	if ($guardar_valores_emision_tritio) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "recuperar_valores_emision_tritio") {
	$id_planilla = $_GET['id_planilla'];

	$query_recuperar_datos_emision_tritio = "SELECT * FROM `regrad_tritio` where id_planilla = $id_planilla";
	$recuperar_datos_emision_tritio = mysql_query($query_recuperar_datos_emision_tritio, $miconex) or die(mysql_error());
	$row_recuperar_datos_emision_tritio = mysql_fetch_assoc($recuperar_datos_emision_tritio);
	$totalRows_recuperar_datos_emision_tritio = mysql_num_rows($recuperar_datos_emision_tritio);

	if ($totalRows_recuperar_datos_emision_tritio == 0) {
		echo "sin_datos";
	} else {
		do {
			echo formato_hora_personalizado($row_recuperar_datos_emision_tritio['hora_desde']) . "@" . formato_hora_personalizado($row_recuperar_datos_emision_tritio['hora_hasta']) . "@" . nulo(decimales(conversion_actividad('1', '5', $row_recuperar_datos_emision_tritio['vent_gral']), 1)) . "@" . nulo(decimales(conversion_actividad('1', '5', $row_recuperar_datos_emision_tritio['vent_reactor']), 1)) . "@" . $row_recuperar_datos_emision_tritio['id_tritio'] . "@@";
		} while ($row_recuperar_datos_emision_tritio = mysql_fetch_assoc($recuperar_datos_emision_tritio));
	}
}
if ($accion == "recuperar_valores_emision_tritio_para_edicion") {
	$id_emision_tritio = $_GET['id_emision_tritio'];

	$query_recuperar_datos_emision_tritio = "SELECT * FROM `regrad_tritio` where id_tritio = $id_emision_tritio";
	$recuperar_datos_emision_tritio = mysql_query($query_recuperar_datos_emision_tritio, $miconex) or die(mysql_error());
	$row_recuperar_datos_emision_tritio = mysql_fetch_assoc($recuperar_datos_emision_tritio);

	echo ($row_recuperar_datos_emision_tritio['fecha']) . "@" . ($row_recuperar_datos_emision_tritio['hora_desde']) . "@" . ($row_recuperar_datos_emision_tritio['hora_hasta']) . "@" . coma_punto(decimales(conversion_actividad('1', '5', $row_recuperar_datos_emision_tritio['vent_gral']), 1)) . "@" . coma_punto(decimales(conversion_actividad('1', '5', $row_recuperar_datos_emision_tritio['vent_reactor']), 1));
}
if ($accion == "guardar_valores_carbono14") {
	$id_planilla = $_GET['id_planilla'];
	$id_usuario = $_GET['id_usuario'];
	$fecha = $_GET['fecha'];
	$hora = $_GET['hora'];
	$actividad_unidad_ingresada = $_GET['actividad_unidad_ingresada'];
	$actividad = vacio5(conversion_actividad($actividad_unidad_ingresada, '1', $_GET['actividad']));

	$datos_log = "Creación:" . date_format($fecha_de_ahora, 'd-m-Y H:i:s') . " usuario:" . nombre_usuario_web($id_usuario) . "///" . $fecha . "///" . $hora . "///" . $actividad;

	$query_guardar_valores_carbono14 = "INSERT INTO `regrad_carbono14`(`id_planilla`, `fecha`, `hora`, `medicion`, `usuario_portal`, `log_modificaciones`) VALUES ('$id_planilla','$fecha','$hora','$actividad','$id_usuario','$datos_log')";
	$guardar_valores_carbono14 = mysql_query($query_guardar_valores_carbono14, $miconex) or die(mysql_error());

	if ($guardar_valores_carbono14) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "editar_valores_carbono14") {
	$id_planilla = $_GET['id_planilla'];
	$id_usuario = $_GET['id_usuario'];
	$fecha = $_GET['fecha'];
	$hora = $_GET['hora'];
	$actividad_unidad_ingresada = $_GET['actividad_unidad_ingresada'];
	$actividad = vacio5(conversion_actividad($actividad_unidad_ingresada, '1', $_GET['actividad']));

	$query_recuperar_datos_carbono14 = "SELECT * FROM `regrad_carbono14` where id_planilla = $id_planilla";
	$recuperar_datos_carbono14 = mysql_query($query_recuperar_datos_carbono14, $miconex) or die(mysql_error());
	$row_recuperar_datos_carbono14 = mysql_fetch_assoc($recuperar_datos_carbono14);

	$datos_log = $row_recuperar_datos_carbono14['log_modificaciones'] . "||" . date_format($fecha_de_ahora, 'd-m-Y H:i:s') . " usuario:" . nombre_usuario_web($id_usuario) . "///" . $fecha . "///" . $hora . "///" . $actividad;

	$query_editar_valores_carbono14 = "UPDATE `regrad_carbono14` SET `fecha`='$fecha',`hora`='$hora',`medicion`='$actividad',`log_modificaciones`='$datos_log' where id_planilla = $id_planilla";
	$editar_valores_carbono14 = mysql_query($query_editar_valores_carbono14, $miconex) or die(mysql_error());

	if ($editar_valores_carbono14) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "recuperar_valores_carbono14") {
	$id_planilla = $_GET['id_planilla'];

	$query_recuperar_datos_carbono14 = "SELECT * FROM `regrad_carbono14` where id_planilla = $id_planilla";
	$recuperar_datos_carbono14 = mysql_query($query_recuperar_datos_carbono14, $miconex) or die(mysql_error());
	$row_recuperar_datos_carbono14 = mysql_fetch_assoc($recuperar_datos_carbono14);
	$totalRows_recuperar_datos_carbono14 = mysql_num_rows($recuperar_datos_carbono14);

	if ($totalRows_recuperar_datos_carbono14 == 0) {
		echo "sin_datos";
	} else {
		do {
			echo formato_hora_personalizado($row_recuperar_datos_carbono14['hora']) . "@" . nulo(decimales(conversion_actividad('1', '3', $row_recuperar_datos_carbono14['medicion']), 0)) . "@" . ($row_recuperar_datos_carbono14['fecha']) . "@" . ($row_recuperar_datos_carbono14['id_carbono14']);
		} while ($row_recuperar_datos_carbono14 = mysql_fetch_assoc($recuperar_datos_carbono14));
	}
}
if ($accion == "guardar_valores_dedo_frio") {
	$id_planilla = $_GET['id_planilla'];
	$id_usuario = $_GET['id_usuario'];
	$fecha = $_GET['fecha'];
	$hora = $_GET['hora'];
	$ppm = ($_GET['ppm']);
	$menor_que = $_GET['menor_que'];

	$datos_log = "Creación:" . date_format($fecha_de_ahora, 'd-m-Y H:i:s') . " usuario:" . nombre_usuario_web($id_usuario) . "///" . $fecha . "///" . $hora . "///" . $ppm . "///" . $menor_que;

	$query_guardar_valores_dedo_frio = "INSERT INTO `regrad_dedo_frio`(`id_planilla`, `fecha`, `hora_medicion`, `ppm`, `menor_que`, `usuario_portal`, `log_modificaciones`) VALUES ('$id_planilla','$fecha','$hora','$ppm','$menor_que','$id_usuario','$datos_log')";
	$guardar_valores_dedo_frio = mysql_query($query_guardar_valores_dedo_frio, $miconex) or die(mysql_error());

	if ($guardar_valores_dedo_frio) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "editar_valores_dedo_frio") {
	$id_planilla = $_GET['id_planilla'];
	$id_usuario = $_GET['id_usuario'];
	$fecha = $_GET['fecha'];
	$hora = $_GET['hora'];
	$ppm = ($_GET['ppm']);
	$menor_que = $_GET['menor_que'];

	$query_recuperar_datos_dedo_frio = "SELECT * FROM `regrad_dedo_frio` where id_planilla = $id_planilla";
	$recuperar_datos_dedo_frio = mysql_query($query_recuperar_datos_dedo_frio, $miconex) or die(mysql_error());
	$row_recuperar_datos_dedo_frio = mysql_fetch_assoc($recuperar_datos_dedo_frio);

	$datos_log = $row_recuperar_datos_dedo_frio['log_modificaciones'] . "||" . date_format($fecha_de_ahora, 'd-m-Y H:i:s') . " usuario:" . nombre_usuario_web($id_usuario) . "///" . $fecha . "///" . $hora . "///" . $ppm . "///" . $menor_que;

	$query_editar_valores_dedo_frio = "UPDATE `regrad_dedo_frio` SET `fecha`='$fecha',`hora_medicion`='$hora',`ppm`='$ppm',`menor_que`='$menor_que',`log_modificaciones`='$datos_log' where id_planilla = $id_planilla";
	$editar_valores_dedo_frio = mysql_query($query_editar_valores_dedo_frio, $miconex) or die(mysql_error());

	if ($editar_valores_dedo_frio) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "recuperar_valores_dedo_frio") {
	$id_planilla = $_GET['id_planilla'];

	$query_recuperar_datos_dedo_frio = "SELECT * FROM `regrad_dedo_frio` where id_planilla = $id_planilla";
	$recuperar_datos_dedo_frio = mysql_query($query_recuperar_datos_dedo_frio, $miconex) or die(mysql_error());
	$row_recuperar_datos_dedo_frio = mysql_fetch_assoc($recuperar_datos_dedo_frio);
	$totalRows_recuperar_datos_dedo_frio = mysql_num_rows($recuperar_datos_dedo_frio);

	if ($row_recuperar_datos_dedo_frio['menor_que'] == 0) {
		$menor_que = "";
	} else {
		$menor_que = "<";
	}

	if ($totalRows_recuperar_datos_dedo_frio == 0) {
		echo "sin_datos";
	} else {
		do {
			echo formato_hora_personalizado($row_recuperar_datos_dedo_frio['hora_medicion']) . "@" . nulo(decimales($row_recuperar_datos_dedo_frio['ppm'], 0)) . "@" . ($row_recuperar_datos_dedo_frio['menor_que']) . "@" . ($row_recuperar_datos_dedo_frio['fecha']) . "@" . ($row_recuperar_datos_dedo_frio['id_dedo_frio']);
		} while ($row_recuperar_datos_dedo_frio = mysql_fetch_assoc($recuperar_datos_dedo_frio));
	}
}
if ($accion == "guardar_valores_concentrador7923") {
	$id_planilla = $_GET['id_planilla'];
	$id_usuario = $_GET['id_usuario'];
	$fecha = $_GET['fecha'];
	$procedencia = $_GET['procedencia'];
	$actividad_unidad_ingresada = $_GET['actividad_unidad_ingresada'];
	$actividad = conversion_actividad($actividad_unidad_ingresada, '1', $_GET['actividad']);
	$volumen_unidad_ingresada = $_GET['volumen_unidad_ingresada'];
	$volumen = conversion_volumen($volumen_unidad_ingresada, '1', $_GET['volumen']);
	$ldd = $_GET['ldd'];

	$datos_log = "Creación:" . date_format($fecha_de_ahora, 'd-m-Y H:i:s') . " usuario:" . nombre_usuario_web($id_usuario) . "///" . $fecha . "///" . $procedencia . "///" . $actividad . "///" . $volumen . "///" . $ldd;

	$query_guardar_valores_concentrador7923 = "INSERT INTO `regrad_tritio_concentrador`(`id_planilla`,`fecha`, `procedencia`, `actividad_Bq`, `volumen_litro`, `ldd%`, `usuario_portal`, `log_modificaciones`) VALUES ('$id_planilla','$fecha','$procedencia','$actividad','$volumen','$ldd','$id_usuario','$datos_log')";
	$guardar_valores_concentrador7923 = mysql_query($query_guardar_valores_concentrador7923, $miconex) or die(mysql_error());

	if ($guardar_valores_concentrador7923) {
		echo "ok";
	} else {
		echo "mal";
	}
}


if ($accion == "guardar_emision_7923_prueba") {
	// 1) Leer cada parámetro usando isset() y asignar un valor por defecto si no exista
	$id_planilla = isset($_GET['id_planilla'])
		? mysql_real_escape_string($_GET['id_planilla'])
		: '';
	$id_usuario  = isset($_GET['id_usuario'])
		? mysql_real_escape_string($_GET['id_usuario'])
		: '';
	$fecha       = isset($_GET['fecha'])
		? mysql_real_escape_string($_GET['fecha'])
		: '';
	$hora        = isset($_GET['hora'])
		? mysql_real_escape_string($_GET['hora'])
		: '';
	$procedencia = isset($_GET['procedencia'])
		? mysql_real_escape_string($_GET['procedencia'])
		: '';

	// 2) Leer volumen (unidad y valor) y convertir
	$volumen_unidad_ingresada = isset($_GET['unidad_vol'])
		? mysql_real_escape_string($_GET['unidad_vol'])
		: '';
	$volumen_input = isset($_GET['volumen'])
		? mysql_real_escape_string($_GET['volumen'])
		: '';
	if ($volumen_unidad_ingresada !== '' && $volumen_input !== '') {
		$volumen = conversion_volumen($volumen_unidad_ingresada, '1', $volumen_input);
	} else {
		$volumen = null;
	}

	// 3) Actividad y LDD no vienen en este test, definimos sus SQL como NULL
	//    Si en el futuro los envías, tendrías que leer $_GET['actividad'] y $_GET['ldd'] aquí.
	$actividad_sql = 'NULL';
	$ldd_sql       = 'NULL';
	$rodd_sql      = 'NULL';

	// 4) Simulación de nombre de usuario y fecha/hora actual
	$fecha_de_ahora = new DateTime();
	$nombre_usuario = nombre_usuario_web($id_usuario);

	// 5) Armado del log. Como $actividad y $ldd no se leen en este test, aparecen como 'NULL'
	$actividad = null;
	$ldd       = null;
	$datos_log = "Creación: " . date_format($fecha_de_ahora, 'd-m-Y H:i:s') .
		" usuario: " . $nombre_usuario .
		"///" . $fecha . "///" . $procedencia .
		"///" . ($actividad !== null ? $actividad : 'NULL') .
		"///" . ($volumen !== null ? $volumen : 'NULL') .
		"///" . ($ldd !== null ? $ldd : 'NULL');

	// 6) Armado de la sentencia INSERT.
	//    IMPORTANTE: Escapamos los signos '%' en el nombre de la columna usando '%%'
	$insert = sprintf(
		"INSERT INTO regrad_tritio_concentrador
            (id_planilla, fecha, procedencia, actividad_Bq, volumen_litro, `ldd%%`, `rodd%%`, usuario_portal, log_modificaciones)
         VALUES ('%s', '%s', '%s', %s, %s, %s, %s, '%s', '%s')",
		mysql_real_escape_string($id_planilla),                   // 1
		mysql_real_escape_string($fecha),                         // 2
		mysql_real_escape_string($procedencia),                   // 3
		$actividad_sql,                                           // 4
		$volumen !== null ? $volumen : 'NULL',                    // 5
		$ldd_sql,                                                 // 6
		$rodd_sql,                                                // 7
		mysql_real_escape_string($id_usuario),                     // 8
		mysql_real_escape_string($datos_log)                       // 9
	);

	// 7) Armado de la respuesta JSON, incluyendo la cadena INSERT generada
	$respuesta = array(
		"estado"  => "simulacion",
		"mensaje" => "Este es un test, no se insertó nada.",
		"datos"   => array(
			"id_planilla"              => $id_planilla,
			"id_usuario"               => $id_usuario,
			"nombre_usuario"           => $nombre_usuario,
			"fecha"                    => $fecha,
			"procedencia"              => $procedencia,
			"volumen_unidad_ingresada" => $volumen_unidad_ingresada,
			"volumen_convertido_litro" => $volumen,
			"log_modificaciones"       => $datos_log,
			"insert"                   => $insert
		)
	);

	// 8) Devolver JSON y terminar ejecución
	header('Content-Type: application/json');
	echo json_encode($respuesta);
	exit;
}

if ($accion == "guardar_emision_7923") {
	// 1) Leer parámetros que sí llegan
	$id_planilla      = mysql_real_escape_string($_GET['id_planilla']);
	$id_usuario       = mysql_real_escape_string($_GET['id_usuario']);
	$fecha            = mysql_real_escape_string($_GET['fecha']);
	$procedencia      = mysql_real_escape_string($_GET['procedencia']);

	// 2) Leer volumen (unidad y valor) y convertir
	$volumen_unidad_ingresada = mysql_real_escape_string($_GET['unidad_vol']);
	$volumen_input            = mysql_real_escape_string($_GET['volumen']);
	$volumen = conversion_volumen($volumen_unidad_ingresada, '1', $volumen_input);

	// 3) Como no envías actividad ni ldd, las dejamos en NULL
	$actividad = null;
	$ldd       = null;

	// 4) Preparar log
	$fecha_de_ahora = new DateTime();
	$nombre_usuario = nombre_usuario_web($id_usuario);
	$datos_log = "Creación:" . date_format($fecha_de_ahora, 'd-m-Y H:i:s')
		. " usuario:" . $nombre_usuario
		. "///" . $fecha
		. "///" . $procedencia
		. "///NULL"                       // actividad aparece como NULL
		. "///" . $volumen
		. "///NULL";                      // ldd aparece como NULL

	// 5) INSERT en MySQL (colocamos NULL en actividad_Bq y en ldd%)
	$query_guardar_valores_concentrador7923 = "
        INSERT INTO `regrad_tritio_concentrador`
            (`id_planilla`, `fecha`, `procedencia`, `actividad_Bq`, `volumen_litro`, `ldd%`, `usuario_portal`, `log_modificaciones`)
        VALUES
            (
                '$id_planilla',
                '$fecha',
                '$procedencia',
                NULL,                       -- actividad_Bq
                '$volumen',
                NULL,                       -- ldd%
                '$id_usuario',
                '" . mysql_real_escape_string($datos_log) . "'
            )
    ";
	$guardar_valores_concentrador7923 = mysql_query($query_guardar_valores_concentrador7923, $miconex)
		or die(mysql_error());

	// 6) Devolver respuesta
	echo $guardar_valores_concentrador7923 ? "ok" : "mal";
}
if ($accion == "recuperar_emisiones_7923") {
	$id_planilla = mysql_real_escape_string($_GET['id_planilla']);

	$query = "
        SELECT id_tritio_conc, fecha, procedencia, actividad_Bq, volumen_litro, `ldd%`, `rodd%`, fecha_hora, usuario_portal, observaciones, log_modificaciones
        FROM regrad_tritio_concentrador
        WHERE id_planilla = '$id_planilla'
        ORDER BY fecha_hora DESC
    ";

	$result = mysql_query($query, $miconex) or die(mysql_error());
	$rows = array();

	while ($r = mysql_fetch_assoc($result)) {
		$rows[] = array(
			"id"           => $r["id_tritio_conc"],
			"fecha"        => $r["fecha"],
			"procedencia"  => $r["procedencia"],
			"volumen"      => $r["volumen_litro"],
			"fecha_hora"   => $r["fecha_hora"],
			"usuario"      => nombre_usuario_web($r["usuario_portal"]),
			"idusuario"      => $r["usuario_portal"],
			"observaciones"      => $r["observaciones"],
		);
	}

	if (count($rows) === 0) {
		echo json_encode(array("estado" => "sin_datos"));
	} else {
		header('Content-Type: application/json');
		echo json_encode(array("estado" => "ok", "datos" => $rows));
	}

	exit;
}
if ($accion == "radionucleidos_de_emision") {
	$id_conc = $_GET['id_conc'];

	$query = "
        SELECT 
            rtr.id,
            rtr.radionucleido as id_radionucleido,
			CONCAT(rr.parte_letra,'-',rr.parte_numero) as radionucleido,
            rtr.actividad_Bq_l
        FROM regrad_tritio_concentrador_radionucleidos rtr
        LEFT JOIN regrad_radionucleidos rr ON rr.id_radionucleidos = rtr.radionucleido
        WHERE rtr.id_tritio_conc = $id_conc
    ";

	$resultado = mysql_query($query, $miconex);
	$datos = [];

	while ($row = mysql_fetch_assoc($resultado)) {
		// $row['simbolo'] = $row['parte_letra'] . '-' . $row['parte_numero'];
		$datos[] = $row;
	}

	header('Content-Type: application/json');
	echo json_encode([
		"estado" => "ok",
		"datos" => $datos
	]);
	exit;
}





if ($accion == "eliminar_emision_7923") {
	$id_emision_7923 = isset($_GET['id_emision_7923']) ? mysql_real_escape_string($_GET['id_emision_7923']) : '';

	if ($id_emision_7923 == '') {
		echo "error_id";
		exit;
	}

	// Eliminar primero los radionucleídos asociados
	$query_borrar_radionucleidos = "DELETE FROM regrad_tritio_concentrador_radionucleidos WHERE id_tritio_conc = '$id_emision_7923'";
	$resultado_radionucleidos = mysql_query($query_borrar_radionucleidos, $miconex);

	// Luego eliminar la emisión principal
	$query_borrar = "DELETE FROM regrad_tritio_concentrador WHERE id_tritio_conc = '$id_emision_7923'";
	$resultado = mysql_query($query_borrar, $miconex);

	if ($resultado) {
		echo "ok";
	} else {
		echo "error";
	}

	exit;
}
if ($accion == "listar_radionucleidos_con_ro") {
	$query = "SELECT 
                id_radionucleidos AS id,
                CONCAT(parte_letra, '-', parte_numero) AS simbolo,
                nombre,
                Ro_gaseosos_Bq AS ro_gaseoso
              FROM regrad_radionucleidos
              WHERE Ro_gaseosos_Bq IS NOT NULL
              ORDER BY CAST(parte_numero AS UNSIGNED), parte_letra";

	$result = mysql_query($query, $miconex);
	$lista = array();

	while ($row = mysql_fetch_assoc($result)) {
		$lista[] = $row;
	}

	header('Content-Type: application/json');
	echo json_encode($lista);
	exit;
}
if ($accion == "guardar_radionucleidos_emision_7923") {
	$id_planilla     = isset($_GET['id_planilla'])     ? mysql_real_escape_string($_GET['id_planilla'])     : '';
	$id_tritio_conc  = isset($_GET['id_tritio_conc'])  ? mysql_real_escape_string($_GET['id_tritio_conc'])  : '';
	$datos_json      = isset($_GET['radios'])          ? $_GET['radios'] : '';

	if ($id_planilla == '' || $id_tritio_conc == '' || $datos_json == '') {
		echo json_encode(["estado" => "error", "mensaje" => "Faltan datos"]);
		exit;
	}

	$radionucleidos = json_decode($datos_json, true);

	if (!is_array($radionucleidos)) {
		echo json_encode(["estado" => "error", "mensaje" => "Datos mal formateados"]);
		exit;
	}

	// 🔴 Primero borramos los registros existentes para esta emisión
	$query_borrar = "DELETE FROM regrad_tritio_concentrador_radionucleidos WHERE id_tritio_conc = '$id_tritio_conc'";
	mysql_query($query_borrar, $miconex); // No cortamos si falla, porque puede que no haya nada

	// 🟢 Ahora insertamos los nuevos datos
	foreach ($radionucleidos as $r) {
		$id_radionucleido = mysql_real_escape_string($r['id']);
		$actividad_Bq_l   = mysql_real_escape_string($r['actividad_Bq_l']);

		$query = "INSERT INTO regrad_tritio_concentrador_radionucleidos
				  (id_planilla, id_tritio_conc, radionucleido, actividad_Bq_l)
				  VALUES
				  ('$id_planilla', '$id_tritio_conc', '$id_radionucleido', '$actividad_Bq_l')";

		$insertar = mysql_query($query, $miconex);

		if (!$insertar) {
			echo json_encode(["estado" => "error", "mensaje" => mysql_error()]);
			exit;
		}
	}

	echo json_encode(["estado" => "ok", "mensaje" => "Radionucleidos guardados"]);
	exit;
}
if ($accion == "guardar_observaciones_emision") {
	$id_tritio_conc = isset($_GET['id_tritio_conc']) ? mysql_real_escape_string($_GET['id_tritio_conc']) : '';
	$observaciones  = isset($_GET['observaciones']) ? mysql_real_escape_string($_GET['observaciones']) : '';

	if ($id_tritio_conc == '') {
		echo "error_id";
		exit;
	}

	$query = "UPDATE regrad_tritio_concentrador SET observaciones = '$observaciones' WHERE id_tritio_conc = '$id_tritio_conc'";
	$resultado = mysql_query($query, $miconex);

	if ($resultado) {
		echo "ok";
	} else {
		echo "error: " . mysql_error();
	}
	exit;
}







if ($accion == "editar_valores_concentrador7923") {
	$id_planilla = $_GET['id_planilla'];
	$id_usuario = $_GET['id_usuario'];
	$fecha = $_GET['fecha'];
	$procedencia = $_GET['procedencia'];
	$actividad_unidad_ingresada = $_GET['actividad_unidad_ingresada'];
	$actividad = conversion_actividad($actividad_unidad_ingresada, '1', $_GET['actividad']);
	$volumen_unidad_ingresada = $_GET['volumen_unidad_ingresada'];
	$volumen = conversion_volumen($volumen_unidad_ingresada, '1', $_GET['volumen']);
	$ldd = $_GET['ldd'];

	$query_recuperar_datos_concentrador7923 = "SELECT * FROM `regrad_tritio_concentrador` where id_planilla = $id_planilla";
	$recuperar_datos_concentrador7923 = mysql_query($query_recuperar_datos_concentrador7923, $miconex) or die(mysql_error());
	$row_recuperar_datos_concentrador7923 = mysql_fetch_assoc($recuperar_datos_concentrador7923);

	$datos_log = $row_recuperar_datos_concentrador7923['log_modificaciones'] . "||" . date_format($fecha_de_ahora, 'd-m-Y H:i:s') . " usuario:" . nombre_usuario_web($id_usuario) . "///" . $fecha . "///" . $procedencia . "///" . $actividad . "///" . $volumen . "///" . $ldd;

	$query_editar_valores_concentrador7923 = "UPDATE `regrad_tritio_concentrador` SET `fecha`='$fecha',`procedencia`='$procedencia',`actividad_Bq`='$actividad',`volumen_litro`='$volumen',`ldd%`='$ldd',`log_modificaciones`='$datos_log' where id_planilla = '$id_planilla'";
	$editar_valores_concentrador7923 = mysql_query($query_editar_valores_concentrador7923, $miconex) or die(mysql_error());

	if ($editar_valores_concentrador7923) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "recuperar_valores_concentrador7923") {
	$id_planilla = $_GET['id_planilla'];

	$query_recuperar_datos_concentrador7923 = "SELECT * FROM `regrad_tritio_concentrador` where id_planilla = $id_planilla";
	$recuperar_datos_concentrador7923 = mysql_query($query_recuperar_datos_concentrador7923, $miconex) or die(mysql_error());
	$row_recuperar_datos_concentrador7923 = mysql_fetch_assoc($recuperar_datos_concentrador7923);
	$totalRows_recuperar_datos_concentrador7923 = mysql_num_rows($recuperar_datos_concentrador7923);

	if ($totalRows_recuperar_datos_concentrador7923 == 0) {
		echo "sin_datos";
	} else {
		do {
			echo ($row_recuperar_datos_concentrador7923['procedencia']) . "@" . coma_cambio2(nulo(decimales(conversion_actividad('1', '2', $row_recuperar_datos_concentrador7923['actividad_Bq']), 3))) . "@" . coma_cambio2(nulo(decimales(conversion_volumen('1', '2', $row_recuperar_datos_concentrador7923['volumen_litro']), 2))) . "@" . coma_cambio2(nulo(decimales($row_recuperar_datos_concentrador7923['ldd%'], 2))) . "@" . $row_recuperar_datos_concentrador7923['fecha'] . "@" . $row_recuperar_datos_concentrador7923['id_tritio_conc'] . "@" . coma_punto(nulo(decimales(conversion_actividad('1', '2', $row_recuperar_datos_concentrador7923['actividad_Bq']), 3))) . "@" . coma_punto(nulo(decimales(conversion_volumen('1', '2', $row_recuperar_datos_concentrador7923['volumen_litro']), 2))) . "@" . coma_punto(nulo(decimales($row_recuperar_datos_concentrador7923['ldd%'], 2)));
		} while ($row_recuperar_datos_concentrador7923 = mysql_fetch_assoc($recuperar_datos_concentrador7923));
	}
}
if ($accion == "guardar_valores_control_precintos") {
	$id_planilla = $_GET['id_planilla'];
	$id_usuario = $_GET['id_usuario'];
	$fecha = $_GET['fecha'];
	$hora = $_GET['hora'];
	$puerta_s_123b = ucwords($_GET['puerta_s_123b']);
	$puerta_s_008 = ucwords($_GET['puerta_s_008']);
	$puerta_s_144 = ucwords($_GET['puerta_s_144']);

	$datos_log = "Creación:" . date_format($fecha_de_ahora, 'd-m-Y H:i:s') . " usuario:" . nombre_usuario_web($id_usuario) . "///" . $fecha . "///" . $hora . "///" . $puerta_s_123b . "///" . $puerta_s_008 . "///" . $puerta_s_144;

	$query_guardar_valores_control_precintos = "INSERT INTO `regrad_control_precintos`( `id_planilla`, `fecha`, `hora`, `puerta_s123B`, `puerta_s008`, `puerta_s144`, `usuario_portal`, `log_modificaciones`) VALUES ('$id_planilla','$fecha','$hora','$puerta_s_123b','$puerta_s_008','$puerta_s_144','$id_usuario','$datos_log')";
	$guardar_valores_control_precintos = mysql_query($query_guardar_valores_control_precintos, $miconex) or die(mysql_error());

	if ($guardar_valores_control_precintos) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "editar_valores_control_precintos") {
	$id_planilla = $_GET['id_planilla'];
	$id_usuario = $_GET['id_usuario'];
	$fecha = $_GET['fecha'];
	$hora = $_GET['hora'];
	$puerta_s_123b = ucwords($_GET['puerta_s_123b']);
	$puerta_s_008 = ucwords($_GET['puerta_s_008']);
	$puerta_s_144 = ucwords($_GET['puerta_s_144']);

	$query_recuperar_datos_control_precintos = "SELECT * FROM `regrad_control_precintos` where id_planilla = $id_planilla";
	$recuperar_datos_control_precintos = mysql_query($query_recuperar_datos_control_precintos, $miconex) or die(mysql_error());
	$row_recuperar_datos_control_precintos = mysql_fetch_assoc($recuperar_datos_control_precintos);

	$datos_log = $row_recuperar_datos_control_precintos['log_modificaciones'] . "||" . date_format($fecha_de_ahora, 'd-m-Y H:i:s') . " usuario:" . nombre_usuario_web($id_usuario) . "///" . $fecha . "///" . $hora . "///" . $puerta_s_123b . "///" . $puerta_s_008 . "///" . $puerta_s_144;


	$query_editar_valores_control_precintos = "UPDATE `regrad_control_precintos` SET `fecha`='$fecha',`hora`='$hora',`puerta_s123B`='$puerta_s_123b',`puerta_s008`='$puerta_s_008',`puerta_s144`='$puerta_s_144',`log_modificaciones`='$datos_log' WHERE id_planilla = '$id_planilla'";
	$editar_valores_control_precintos = mysql_query($query_editar_valores_control_precintos, $miconex) or die(mysql_error());

	if ($editar_valores_control_precintos) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "recuperar_valores_control_precintos") {
	$id_planilla = $_GET['id_planilla'];

	$query_recuperar_datos_control_precintos = "SELECT * FROM `regrad_control_precintos` where id_planilla = $id_planilla";
	$recuperar_datos_control_precintos = mysql_query($query_recuperar_datos_control_precintos, $miconex) or die(mysql_error());
	$row_recuperar_datos_control_precintos = mysql_fetch_assoc($recuperar_datos_control_precintos);
	$totalRows_recuperar_datos_control_precintos = mysql_num_rows($recuperar_datos_control_precintos);

	if ($totalRows_recuperar_datos_control_precintos == 0) {
		echo "sin_datos";
	} else {
		do {
			echo formato_hora_personalizado($row_recuperar_datos_control_precintos['hora']) . "@" . ucwords($row_recuperar_datos_control_precintos['puerta_s123B']) . "@" . ucwords($row_recuperar_datos_control_precintos['puerta_s008']) . "@" . ucwords($row_recuperar_datos_control_precintos['puerta_s144']) . "@" . ($row_recuperar_datos_control_precintos['fecha']) . "@" . ($row_recuperar_datos_control_precintos['id_control_precintos']);
		} while ($row_recuperar_datos_control_precintos = mysql_fetch_assoc($recuperar_datos_control_precintos));
	}
}
if ($accion == "guardar_valores_condicion_meteorologica") {
	$id_planilla = $_GET['id_planilla'];
	$id_usuario = $_GET['id_usuario'];
	$fecha = $_GET['fecha'];
	$hora = $_GET['hora'];
	$velocidad_viento = ucwords($_GET['velocidad_viento']);
	$direccion_viento = ucwords($_GET['direccion_viento']);
	$condicion_pasquill = ucwords($_GET['condicion_pasquill']);

	$datos_log = "Creación:" . date_format($fecha_de_ahora, 'd-m-Y H:i:s') . " usuario:" . nombre_usuario_web($id_usuario) . "///" . $fecha . "///" . $hora . "///" . $velocidad_viento . "///" . $direccion_viento . "///" . $condicion_pasquill;

	$query_guardar_valores_condicion_meteorologica = "INSERT INTO `regrad_cond_meteorologica`(`id_planilla`, `fecha`, `hora`, `velocidad_mseg`, `direc_viento`, `cond_pasquill`, `usuario_portal`,`log_modificaciones`) VALUES ('$id_planilla','$fecha','$hora','$velocidad_viento','$direccion_viento','$condicion_pasquill','$id_usuario','$datos_log')";
	$guardar_valores_condicion_meteorologica = mysql_query($query_guardar_valores_condicion_meteorologica, $miconex) or die(mysql_error());

	if ($guardar_valores_condicion_meteorologica) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "editar_valores_condicion_meteorologica") {
	$id_planilla = $_GET['id_planilla'];
	$id_usuario = $_GET['id_usuario'];
	$fecha = $_GET['fecha'];
	$hora = $_GET['hora'];
	$velocidad_viento = ucwords($_GET['velocidad_viento']);
	$direccion_viento = ucwords($_GET['direccion_viento']);
	$condicion_pasquill = ucwords($_GET['condicion_pasquill']);

	$query_recuperar_datos_condicion_meteorologica = "SELECT * FROM `regrad_cond_meteorologica` where id_planilla = $id_planilla";
	$recuperar_datos_condicion_meteorologica = mysql_query($query_recuperar_datos_condicion_meteorologica, $miconex) or die(mysql_error());
	$row_recuperar_datos_condicion_meteorologica = mysql_fetch_assoc($recuperar_datos_condicion_meteorologica);

	$datos_log = $row_recuperar_datos_condicion_meteorologica['log_modificaciones'] . "||" . date_format($fecha_de_ahora, 'd-m-Y H:i:s') . " usuario:" . nombre_usuario_web($id_usuario) . "///" . $fecha . "///" . $hora . "///" . $velocidad_viento . "///" . $direccion_viento . "///" . $condicion_pasquill;

	$query_editar_valores_condicion_meteorologica = "UPDATE `regrad_cond_meteorologica` SET `fecha`= '$fecha', `hora`= '$hora' ,`velocidad_mseg`= '$velocidad_viento' ,`direc_viento`= '$direccion_viento' ,`cond_pasquill`= '$condicion_pasquill', `log_modificaciones` = '$datos_log' WHERE id_planilla = $id_planilla";
	$editar_valores_condicion_meteorologica = mysql_query($query_editar_valores_condicion_meteorologica, $miconex) or die(mysql_error());

	if ($editar_valores_condicion_meteorologica) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "recuperar_valores_condicion_meteorologica") {
	$id_planilla = $_GET['id_planilla'];

	$query_recuperar_datos_condicion_meteorologica = "SELECT * FROM `regrad_cond_meteorologica` where id_planilla = $id_planilla";
	$recuperar_datos_condicion_meteorologica = mysql_query($query_recuperar_datos_condicion_meteorologica, $miconex) or die(mysql_error());
	$row_recuperar_datos_condicion_meteorologica = mysql_fetch_assoc($recuperar_datos_condicion_meteorologica);
	$totalRows_recuperar_datos_condicion_meteorologica = mysql_num_rows($recuperar_datos_condicion_meteorologica);

	if ($totalRows_recuperar_datos_condicion_meteorologica == 0) {
		echo "sin_datos";
	} else {
		do {
			echo formato_hora_personalizado($row_recuperar_datos_condicion_meteorologica['hora']) . "@" . ucwords($row_recuperar_datos_condicion_meteorologica['velocidad_mseg']) . "@" . ucwords($row_recuperar_datos_condicion_meteorologica['direc_viento']) . "@" . ucwords($row_recuperar_datos_condicion_meteorologica['cond_pasquill']) . "@" . ($row_recuperar_datos_condicion_meteorologica['fecha']) . "@" . ($row_recuperar_datos_condicion_meteorologica['id_cond_meteorologica']);
		} while ($row_recuperar_datos_condicion_meteorologica = mysql_fetch_assoc($recuperar_datos_condicion_meteorologica));
	}
}
if ($accion == "recuperar_observaciones") {
	$id_planilla = $_GET['id_planilla'];

	$query_planilla = "SELECT * FROM regrad_planilla_diaria_nueva where id_planilla_diaria = $id_planilla";
	$planilla = mysql_query($query_planilla, $miconex) or die(mysql_error());
	$row_planilla = mysql_fetch_assoc($planilla);

	$query_campo_observaciones = "SELECT * FROM `regrad_textos_varios` where dato_clave = 'observaciones_registro_predeterminadas'";
	$campo_observaciones = mysql_query($query_campo_observaciones, $miconex) or die(mysql_error());
	$row_campo_observaciones = mysql_fetch_assoc($campo_observaciones);

	if (is_null($row_planilla['observaciones']) or $row_planilla['observaciones'] == "") {
		$campo_observaciones = $row_campo_observaciones['dato_contenido'];
	} else {
		$campo_observaciones = $row_planilla['observaciones'];
	}

	echo $campo_observaciones;
}
if ($accion == "recuperar_novedades_libro_actas") {
	$id_planilla_diaria = $_GET['id_planilla'];

	$query_libro_actas = "SELECT * FROM `regrad_libro_actas` where id_planilla_diaria = $id_planilla_diaria";
	$libro_actas = mysql_query($query_libro_actas, $miconex) or die(mysql_error());
	$row_libro_actas = mysql_fetch_assoc($libro_actas);

	$query_novedades_libro_actas = "SELECT * FROM `regrad_textos_varios` where dato_clave = 'novedades_libro_actas_predeterminado'";
	$novedades_libro_actas = mysql_query($query_novedades_libro_actas, $miconex) or die(mysql_error());
	$row_novedades_libro_actas = mysql_fetch_assoc($novedades_libro_actas);

	if (is_null($row_libro_actas['novedades']) or $row_libro_actas['novedades'] == "") {
		$campo_novedades_libro_actas = $row_novedades_libro_actas['dato_contenido'];
	} else {
		$campo_novedades_libro_actas = $row_libro_actas['novedades'];
	}

	echo $campo_novedades_libro_actas;
}
if ($accion == "recuperar_datos_planilla_diaria") {
	$id_planilla_diaria = $_GET['id_planilla'];

	$query_planilla = "SELECT a.id_planilla_diaria as id_planilla_diaria, a.fecha as fecha, horarios, of_apellido, of_nombre, novedades, if(vent_gral is null,'-',(vent_gral/1000000000)) as vent_gral_gbq, if(vent_reactor is null,'-',(vent_reactor/1000000000)) as vent_reactor_gbq, if(g.medicion is null,'-',(g.medicion/1000000)) as carbono14_mbq, concat(if((menor_que=0 OR menor_que is null),'','< '),if(ppm is null,'-',ppm)) as dedo_frio_ppm FROM regrad_planilla_diaria_nueva a left join regrad_turnos b on b.id_turnos = a.turno left join regrad_estados_planta c on c.id_estado_planta = a.estado_planta left join regrad_of_rad d on d.id_of_rad = a.of_rad left join regrad_jefes_turno e on e.id_jefes_turno = a.jefe_turno left join regrad_libro_actas f on f.id_planilla_diaria = a.id_planilla_diaria left join regrad_carbono14 g on g.id_planilla = a.id_planilla_diaria left join regrad_dedo_frio h on h.id_planilla = a.id_planilla_diaria left join regrad_tritio i on i.id_planilla = a.id_planilla_diaria where a.id_planilla_diaria >= 34 and f.novedades is not null and a.id_planilla_diaria = $id_planilla_diaria order by a.fecha DESC,a.fecha_hora_abierto DESC LIMIT 15";
	$planilla = mysql_query($query_planilla, $miconex) or die(mysql_error());
	$row_planilla = mysql_fetch_assoc($planilla);
	$totalRows_planilla = mysql_num_rows($planilla);

	echo strtoupper($row_planilla['of_apellido'] . ", " . $row_planilla['of_nombre']);
	echo "$$$";
	echo formafecha6($row_planilla['fecha']);
	echo "$$$";
	echo ($row_planilla['horarios']);
	echo "$$$";
	echo ($row_planilla['vent_gral_gbq']);
	echo "$$$";
	echo ($row_planilla['vent_reactor_gbq']);
	echo "$$$";
	echo ($row_planilla['carbono14_mbq']);
	echo "$$$";
	echo ($row_planilla['dedo_frio_ppm']);
}
if ($accion == "actualizar_observaciones") {
	$id_planilla = $_GET['id_planilla'];
	$id_usuario = $_GET['id_usuario'];
	$observaciones = nl2br($_GET['observaciones']);

	$query_actualizar_observaciones = "UPDATE `regrad_planilla_diaria_nueva` SET `observaciones`='$observaciones' WHERE id_planilla_diaria = '$id_planilla'";
	$actualizar_observaciones = mysql_query($query_actualizar_observaciones, $miconex) or die(mysql_error());

	if ($actualizar_observaciones) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "eliminar_registro_generico") {
	$nombre_tabla = $_GET['nombre_tabla'];
	$nombre_campo_id = $_GET['nombre_campo_id'];
	$id_registro = $_GET['id_registro'];

	$query_eliminar_registro_generico = "DELETE FROM $nombre_tabla WHERE $nombre_campo_id = $id_registro";
	$eliminar_registro_generico = mysql_query($query_eliminar_registro_generico, $miconex) or die(mysql_error());

	echo $query_eliminar_registro_generico;
}
if ($accion == "guardar_valores_espectrometria_aire") {
	$id_planilla = $_GET['id_planilla'];
	$id_usuario = $_GET['id_usuario'];
	$fecha = $_GET['fecha'];
	$hora = $_GET['hora'];
	$recinto = $_GET['recinto'];
	$tipo = strtoupper($_GET['tipo']);
	$ar41 = vacio5($_GET['ar41']);
	$xe133 = vacio5($_GET['xe133']);
	$xe135 = vacio5($_GET['xe135']);
	$kr88 = vacio5($_GET['kr88']);

	$query_guardar_valores_espectrometria_aire = "INSERT INTO `regrad_espectr_conc_radionuclei`(`id_planilla`, `fecha`, `tipo`, `recinto`, `hora`, `ar41`, `xe133`, `xe135`, `kr88`, `usuario`) VALUES ('$id_planilla','$fecha','$tipo','$recinto','$hora','$ar41','$xe133','$xe135','$kr88','$id_usuario')";
	$guardar_valores_espectrometria_aire = mysql_query($query_guardar_valores_espectrometria_aire, $miconex) or die(mysql_error());

	if ($guardar_valores_espectrometria_aire) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "actualizar_valores_espectrometria_aire_edicion") {
	$id_espectrometria_aire = $_GET['id_espectrometria_aire'];
	$fecha = $_GET['fecha'];
	$hora = $_GET['hora'];
	$recinto = $_GET['recinto'];
	$tipo = strtoupper($_GET['tipo']);
	$ar41 = vacio5($_GET['ar41']);
	$xe133 = vacio5($_GET['xe133']);
	$xe135 = vacio5($_GET['xe135']);
	$kr88 = vacio5($_GET['kr88']);

	$query_guardar_valores_espectrometria_aire = "UPDATE `regrad_espectr_conc_radionuclei` SET `fecha`='$fecha',`tipo`='$tipo',`recinto`='$recinto',`hora`='$hora',`ar41`='$ar41',`xe133`='$xe133',`xe135`='$xe135',`kr88`='$kr88' WHERE id_conc_rad = $id_espectrometria_aire";
	$guardar_valores_espectrometria_aire = mysql_query($query_guardar_valores_espectrometria_aire, $miconex) or die(mysql_error());

	if ($guardar_valores_espectrometria_aire) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "recuperar_valores_espectrometria_aire") {
	$id_planilla = $_GET['id_planilla'];

	$query_recuperar_datos_espectrometria_aire = "SELECT *, b.recinto as recinto_nombre FROM regrad_espectr_conc_radionuclei a left join graf_tritio_recintos b on b.id = a.recinto  where id_planilla = $id_planilla ";
	$recuperar_datos_espectrometria_aire = mysql_query($query_recuperar_datos_espectrometria_aire, $miconex) or die(mysql_error());
	$row_recuperar_datos_espectrometria_aire = mysql_fetch_assoc($recuperar_datos_espectrometria_aire);
	$totalRows_recuperar_datos_espectrometria_aire = mysql_num_rows($recuperar_datos_espectrometria_aire);

	if ($totalRows_recuperar_datos_espectrometria_aire == 0) {
		echo "sin_datos";
	} else {
		do {
			echo strtoupper($row_recuperar_datos_espectrometria_aire['tipo']) . "@" . ($row_recuperar_datos_espectrometria_aire['recinto_nombre']) . "@" . formato_hora_personalizado($row_recuperar_datos_espectrometria_aire['hora']) . "@" . nulo(decimales($row_recuperar_datos_espectrometria_aire['ar41'], 1)) . "@" . nulo(decimales($row_recuperar_datos_espectrometria_aire['xe133'], 1)) . "@" . nulo(decimales($row_recuperar_datos_espectrometria_aire['xe135'], 1)) . "@" . nulo(decimales($row_recuperar_datos_espectrometria_aire['kr88'], 1)) . "@" . nulo(decimales(($row_recuperar_datos_espectrometria_aire['ar41'] + $row_recuperar_datos_espectrometria_aire['xe133'] + $row_recuperar_datos_espectrometria_aire['xe135'] + $row_recuperar_datos_espectrometria_aire['kr88']), 1)) . "@" . $row_recuperar_datos_espectrometria_aire['id_conc_rad'] . "@" . $row_recuperar_datos_espectrometria_aire['fecha'] . "@@";
		} while ($row_recuperar_datos_espectrometria_aire = mysql_fetch_assoc($recuperar_datos_espectrometria_aire));
	}
}
if ($accion == "recuperar_valores_espectrometria_aire_para_edicion") {
	$id_espectrometria_aire = $_GET['id_espectrometria_aire'];

	$query_recuperar_datos_espectrometria_aire = "SELECT * FROM `regrad_espectr_conc_radionuclei` WHERE `id_conc_rad` = $id_espectrometria_aire";
	$recuperar_datos_espectrometria_aire = mysql_query($query_recuperar_datos_espectrometria_aire, $miconex) or die(mysql_error());
	$row_recuperar_datos_espectrometria_aire = mysql_fetch_assoc($recuperar_datos_espectrometria_aire);

	echo strtoupper($row_recuperar_datos_espectrometria_aire['tipo']) . "@" . ($row_recuperar_datos_espectrometria_aire['recinto']) . "@" . formato_hora_personalizado($row_recuperar_datos_espectrometria_aire['hora']) . "@" . coma_punto(nulo(decimales($row_recuperar_datos_espectrometria_aire['ar41'], 1))) . "@" . coma_punto(nulo(decimales($row_recuperar_datos_espectrometria_aire['xe133'], 1))) . "@" . coma_punto(nulo(decimales($row_recuperar_datos_espectrometria_aire['xe135'], 1))) . "@" . coma_punto(nulo(decimales($row_recuperar_datos_espectrometria_aire['kr88'], 1))) . "@" . $row_recuperar_datos_espectrometria_aire['id_conc_rad'] . "@" . $row_recuperar_datos_espectrometria_aire['fecha'] . "@@";
}
if ($accion == "guardar_valores_efluentes_liquidos") {
	$id_planilla = $_GET['id_planilla'];
	$id_usuario = $_GET['id_usuario'];
	$fecha = $_GET['fecha'];
	$hora = $_GET['hora'];
	$tanque = $_GET['tanque'];
	$unidad_ingresada = $_GET['unidad_ingresada'];
	$volumen = conversion_volumen($unidad_ingresada, '1', $_GET['volumen']);

	$query_guardar_valores_efluentes_liquidos = "INSERT INTO `regrad_efluentes_liquidos_nueva`(`id_planilla`,`tanque`, `fecha`, `hora`, `volumen_litro`, `usuario`) VALUES ('$id_planilla','$tanque','$fecha','$hora','$volumen','$id_usuario')";
	$guardar_valores_efluentes_liquidos = mysql_query($query_guardar_valores_efluentes_liquidos, $miconex) or die(mysql_error());

	$query_eliminar_valores_en_tabla_archivo = "DELETE FROM `reg_rad_efluentes_liquidos_radionucleidos_calculos_archivo` WHERE id_planilla = $id_planilla";
	$eliminar_valores_en_tabla_archivo = mysql_query($query_eliminar_valores_en_tabla_archivo, $miconex) or die(mysql_error());

	$query_actualizar_valores_en_tabla_archivo = "INSERT INTO `reg_rad_efluentes_liquidos_radionucleidos_calculos_archivo`( `id_efluentes`, `id_planilla`, `tanque`, `fecha`, `hora`, `volumen_litro`, `id_radionucleidos`, `concentracion`, `actividad_Bq`, `AiKi`, `LDD`) SELECT `id_efluentes`, `id_planilla`, `tanque`, `fecha`, `hora`, `volumen_litro`, `id_radionucleidos`, `concentracion`, `actividad_Bq`, `AiKi`, `LDD` FROM `reg_rad_efluentes_liquidos_radionucleidos_calculos`  where id_planilla = $id_planilla";
	$actualizar_valores_en_tabla_archivo = mysql_query($query_actualizar_valores_en_tabla_archivo, $miconex) or die(mysql_error());

	if ($guardar_valores_efluentes_liquidos) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "recuperar_valores_efluentes_liquidos") {
	$id_planilla = $_GET['id_planilla'];

	$query_recuperar_datos_efluentes_liquidos = "SELECT * FROM `reg_rad_ef_liq_final_para_tabla` where id_planilla = $id_planilla ";
	$recuperar_datos_efluentes_liquidos = mysql_query($query_recuperar_datos_efluentes_liquidos, $miconex) or die(mysql_error());
	$row_recuperar_datos_efluentes_liquidos = mysql_fetch_assoc($recuperar_datos_efluentes_liquidos);
	$totalRows_recuperar_datos_efluentes_liquidos = mysql_num_rows($recuperar_datos_efluentes_liquidos);

	if ($totalRows_recuperar_datos_efluentes_liquidos == 0) {
		echo "sin_datos";
	} else {
		do {
			echo $row_recuperar_datos_efluentes_liquidos['id_efluentes'] . "@" . formafecha6($row_recuperar_datos_efluentes_liquidos['fecha']) . "@" . formato_hora_personalizado($row_recuperar_datos_efluentes_liquidos['hora']) . "@" . $row_recuperar_datos_efluentes_liquidos['tanque'] . "@" . coma_punto(((conversion_volumen('1', '2', $row_recuperar_datos_efluentes_liquidos['volumen_litro'])))) . "@" . notacion_cientifica2(conversion_actividad('1', '2', $row_recuperar_datos_efluentes_liquidos['actividad_tritio'])) . "@" . notacion_cientifica2(conversion_actividad('1', '2', $row_recuperar_datos_efluentes_liquidos['actividad_gamma'])) . "@" . decimales(($row_recuperar_datos_efluentes_liquidos['LDD_Ro_tritio'] + $row_recuperar_datos_efluentes_liquidos['LDD_Ro_gamma']), 3) . "@@";
		} while ($row_recuperar_datos_efluentes_liquidos = mysql_fetch_assoc($recuperar_datos_efluentes_liquidos));
	}
}
if ($accion == "recuperar_valores_radionucleidos_efluentes_liquidos") {
	$id_efluentes = $_GET['id_efluentes'];

	$query_recuperar_valores_radionucleidos_efluentes_liquidos = "SELECT id, radionucleido, conc_Bq_litro FROM regrad_efluentes_liquidos_radionucleidos a left join reg_rad_radionucleidos_con_ki_ro b on b.id_radionucleidos = a.id_radionucleido_efluentes_liquidos where id_regrad_efluentes_liquidos = $id_efluentes ";
	$recuperar_valores_radionucleidos_efluentes_liquidos = mysql_query($query_recuperar_valores_radionucleidos_efluentes_liquidos, $miconex) or die(mysql_error());
	$row_recuperar_valores_radionucleidos_efluentes_liquidos = mysql_fetch_assoc($recuperar_valores_radionucleidos_efluentes_liquidos);
	$totalRows_recuperar_valores_radionucleidos_efluentes_liquidos = mysql_num_rows($recuperar_valores_radionucleidos_efluentes_liquidos);

	if ($totalRows_recuperar_valores_radionucleidos_efluentes_liquidos == 0) {
		echo "sin_datos";
	} else {
		do {
			echo ($row_recuperar_valores_radionucleidos_efluentes_liquidos['radionucleido']) . "@" . notacion_cientifica2($row_recuperar_valores_radionucleidos_efluentes_liquidos['conc_Bq_litro']) . "@" . $row_recuperar_valores_radionucleidos_efluentes_liquidos['id'] . "@@";
		} while ($row_recuperar_valores_radionucleidos_efluentes_liquidos = mysql_fetch_assoc($recuperar_valores_radionucleidos_efluentes_liquidos));
	}
}
if ($accion == "guardar_valores_radionucleidos_efluentes_liquidos") {
	$id_efluentes = $_GET['id_efluentes'];
	$radionucleido = $_GET['radionucleido'];
	$unidad_concentracion_ingresada = $_GET['unidad_concentracion_ingresada'];
	$concentracion = conversion_concentracion($unidad_concentracion_ingresada, '2', $_GET['concentracion']);

	$query_guardar_valores_radionucleidos_efluentes_liquidos = "INSERT INTO `regrad_efluentes_liquidos_radionucleidos`(`id_regrad_efluentes_liquidos`, `id_radionucleido_efluentes_liquidos`, `conc_Bq_litro`) VALUES ('$id_efluentes','$radionucleido','$concentracion')";
	$guardar_valores_radionucleidos_efluentes_liquidos = mysql_query($query_guardar_valores_radionucleidos_efluentes_liquidos, $miconex) or die(mysql_error());

	$query_eliminar_valores_en_tabla_archivo = "DELETE FROM `reg_rad_efluentes_liquidos_radionucleidos_calculos_archivo` WHERE id_efluentes = $id_efluentes";
	$eliminar_valores_en_tabla_archivo = mysql_query($query_eliminar_valores_en_tabla_archivo, $miconex) or die(mysql_error());

	$query_actualizar_valores_en_tabla_archivo = "INSERT INTO `reg_rad_efluentes_liquidos_radionucleidos_calculos_archivo`( `id_efluentes`, `id_planilla`, `tanque`, `fecha`, `hora`, `volumen_litro`, `id_radionucleidos`, `concentracion`, `actividad_Bq`, `AiKi`, `LDD`) SELECT `id_efluentes`, `id_planilla`, `tanque`, `fecha`, `hora`, `volumen_litro`, `id_radionucleidos`, `concentracion`, `actividad_Bq`, `AiKi`, `LDD` FROM `reg_rad_efluentes_liquidos_radionucleidos_calculos` where id_efluentes = $id_efluentes";
	$actualizar_valores_en_tabla_archivo = mysql_query($query_actualizar_valores_en_tabla_archivo, $miconex) or die(mysql_error());


	if ($guardar_valores_radionucleidos_efluentes_liquidos) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "actualizar_observaciones_efluentes") {
	$id_efluentes = $_GET['id_efluentes'];
	$observaciones = acentos_mayus_todo_return(($_GET['observaciones']));

	$query_actualizar_observaciones_efluentes = "UPDATE `regrad_efluentes_liquidos_nueva` SET `observaciones`='$observaciones' WHERE id_efluentes = $id_efluentes";
	$actualizar_observaciones_efluentes = mysql_query($query_actualizar_observaciones_efluentes, $miconex) or die(mysql_error());

	if ($actualizar_observaciones_efluentes) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "recuperar_observaciones_efluentes") {
	$id_efluentes = $_GET['id_efluentes'];

	$query_actualizar_observaciones_efluentes = "SELECT * FROM `regrad_efluentes_liquidos_nueva` WHERE id_efluentes = $id_efluentes";
	$actualizar_observaciones_efluentes = mysql_query($query_actualizar_observaciones_efluentes, $miconex) or die(mysql_error());
	$row_actualizar_observaciones_efluentes = mysql_fetch_assoc($actualizar_observaciones_efluentes);
	$totalRows_actualizar_observaciones_efluentes = mysql_num_rows($actualizar_observaciones_efluentes);

	echo $row_actualizar_observaciones_efluentes['observaciones'] . "@" . formafecha6($row_actualizar_observaciones_efluentes['fecha']) . "@" . formato_hora_personalizado($row_actualizar_observaciones_efluentes['hora']) . "@" . $row_actualizar_observaciones_efluentes['tanque'] . "@" . coma_cambio2(conversion_volumen('1', '2', $row_actualizar_observaciones_efluentes['volumen_litro']));
}
if ($accion == "guardar_valores_contaminacion_superficial") {
	$id_planilla = $_GET['id_planilla'];
	$id_usuario = $_GET['id_usuario'];
	$fecha = $_GET['fecha'];
	$hora = $_GET['hora'];
	$recinto = $_GET['recinto'];
	$tasa_dosis_unidad_ingresada = $_GET['tasa_dosis_unidad_ingresada'];
	$beta_gamma_unidad_ingresada = $_GET['beta_gamma_unidad_ingresada'];
	$alfa_unidad_ingresada = $_GET['alfa_unidad_ingresada'];
	$beta_gamma = conversion_concentracion_superficial($beta_gamma_unidad_ingresada, '1', $_GET['beta_gamma']);
	$alfa = conversion_concentracion_superficial($alfa_unidad_ingresada, '1', $_GET['alfa']);
	$tasa_dosis = conversion_tasa_dosis($tasa_dosis_unidad_ingresada, '1', $_GET['tasa_dosis']);

	$query_guardar_valores_contaminacion_superficial = "INSERT INTO `regrad_contaminacion_superficial`(`id_planilla`, `fecha`, `hora`, `recinto`, `beta_gamma_Bq_cm2`, `alfa_Bq_cm2`, `tasa_dosis_equivalente_µsv_h`, `usuario_portal`) VALUES ('$id_planilla','$fecha','$hora','$recinto','$beta_gamma','$alfa','$tasa_dosis','$id_usuario')";
	$guardar_valores_contaminacion_superficial = mysql_query($query_guardar_valores_contaminacion_superficial, $miconex) or die(mysql_error());

	if ($guardar_valores_contaminacion_superficial) {
		//-------- conexion con servidor de jose martinez -----------
		$select = "INSERT INTO `cont_sup_reg_radiologico`(id_recinto, fecha, hora, beta_gamma_Bq_cm2, alfa_Bq_cm2, tasa_dosis_equivalente_microsv_h) VALUES ( '$recinto', '$fecha', '$hora', '$beta_gamma', '$alfa', '$tasa_dosis') ON DUPLICATE KEY UPDATE fecha = '$fecha' , hora = '$hora' , beta_gamma_Bq_cm2 = '$beta_gamma' , alfa_Bq_cm2 = '$alfa' , tasa_dosis_equivalente_microsv_h = '$tasa_dosis' ;";

		$query = mysql_query($select, $dbh2) or die(mysql_error());

		//-------- FIN conexion con servidor de jose martinez -----------
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "recuperar_valores_contaminacion_superficial") {
	$id_planilla = $_GET['id_planilla'];

	$query_cargar_valores_contaminacion_superficial = "SELECT *, b.recinto as recinto_nombre FROM regrad_contaminacion_superficial a left join graf_tritio_recintos b on b.id = a.recinto where id_planilla = $id_planilla order by fecha, hora";
	$cargar_valores_contaminacion_superficial = mysql_query($query_cargar_valores_contaminacion_superficial, $miconex) or die(mysql_error());
	$row_cargar_valores_contaminacion_superficial = mysql_fetch_assoc($cargar_valores_contaminacion_superficial);
	$totalRows_cargar_valores_contaminacion_superficial = mysql_num_rows($cargar_valores_contaminacion_superficial);

	if ($totalRows_cargar_valores_contaminacion_superficial == 0) {
		echo "sin_datos";
	} else {
		do {
			echo formafecha6($row_cargar_valores_contaminacion_superficial['fecha']) . "@" . formato_hora_2($row_cargar_valores_contaminacion_superficial['hora']) . "@" . $row_cargar_valores_contaminacion_superficial['recinto_nombre'] . "@" . nulo(decimales($row_cargar_valores_contaminacion_superficial['beta_gamma_Bq_cm2'], 2)) . "@" . nulo(decimales($row_cargar_valores_contaminacion_superficial['alfa_Bq_cm2'], 2)) . "@" . nulo(decimales($row_cargar_valores_contaminacion_superficial['tasa_dosis_equivalente_µsv_h'], 2)) . "@" . ($row_cargar_valores_contaminacion_superficial['id_contaminacion_superficial']) . "@@";
		} while ($row_cargar_valores_contaminacion_superficial = mysql_fetch_assoc($cargar_valores_contaminacion_superficial));
	}
}
if ($accion == "guardar_valores_eficiencia_dryers") {
	$id_planilla = $_GET['id_planilla'];
	$id_usuario = $_GET['id_usuario'];
	$fecha = $_GET['fecha'];
	$hora = $_GET['hora'];
	$dryers = $_GET['dryers'];
	$entrada = $_GET['entrada'];
	$salida = $_GET['salida'];

	$query_guardar_valores_eficiencia_dryers = "INSERT INTO `regrad_eficiencia_dryers`(`id_planilla`, `fecha`, `hora`, `dryers`, `entrada_dac`, `salida_dac`, `usuario`) VALUES ('$id_planilla','$fecha','$hora','$dryers','$entrada','$salida','$id_usuario')";
	$guardar_valores_eficiencia_dryers = mysql_query($query_guardar_valores_eficiencia_dryers, $miconex) or die(mysql_error());

	if ($guardar_valores_eficiencia_dryers) {
		echo "ok";
	} else {
		echo "mal";
	}
}
if ($accion == "recuperar_valores_eficiencia_dryers") {
	$id_planilla = $_GET['id_planilla'];

	$query_cargar_valores_eficiencia_dryers = "SELECT * FROM reg_rad_eficiencia_dryers where id_planilla = $id_planilla";
	$cargar_valores_eficiencia_dryers = mysql_query($query_cargar_valores_eficiencia_dryers, $miconex) or die(mysql_error());
	$row_cargar_valores_eficiencia_dryers = mysql_fetch_assoc($cargar_valores_eficiencia_dryers);
	$totalRows_cargar_valores_eficiencia_dryers = mysql_num_rows($cargar_valores_eficiencia_dryers);

	if ($totalRows_cargar_valores_eficiencia_dryers == 0) {
		echo "sin_datos";
	} else {
		do {
			echo ($row_cargar_valores_eficiencia_dryers['id_eficiencia_dryers']) . "@" . formafecha6($row_cargar_valores_eficiencia_dryers['fecha']) . "@" . formato_hora_2($row_cargar_valores_eficiencia_dryers['hora']) . "@" . ($row_cargar_valores_eficiencia_dryers['nombre_dryers']) . "@" . coma_cambio2($row_cargar_valores_eficiencia_dryers['entrada_dac']) . "@" . coma_cambio2($row_cargar_valores_eficiencia_dryers['salida_dac']) . "@" . decimales($row_cargar_valores_eficiencia_dryers['eficiencia_porcentaje'], 2) . "@@";
		} while ($row_cargar_valores_eficiencia_dryers = mysql_fetch_assoc($cargar_valores_eficiencia_dryers));
	}
}
if ($accion == "obtener_registros_de_minizonas") {
	$id_minizonas = $_GET['id_minizonas'];

	$query_obtener_registros_de_minizonas = "SELECT *,DATE_FORMAT(fecha_hora_agregado, '%d-%m-%Y %H:%i') as fecha_hora FROM regrad_minizonas_actas a left join regrad_of_rad b on b.id_of_rad = a.id_of_rad where a.id_minizonas = '$id_minizonas' order by fecha_hora_agregado DESC";
	$obtener_registros_de_minizonas = mysql_query($query_obtener_registros_de_minizonas, $miconex);
	$row_obtener_registros_de_minizonas = mysql_fetch_assoc($obtener_registros_de_minizonas);
	$totalRows_obtener_registros_de_minizonas = mysql_num_rows($obtener_registros_de_minizonas);

	if ($totalRows_obtener_registros_de_minizonas == 0) {
		echo "<h2 style='margin:auto;width:100%;text-align:center'>No existen registros cargados aún</h2>";
	} else {
		do {
			echo "<div class='registros_pasados_titulo_oficial'>Oficial &nbsp;&nbsp;";
			echo "<span class='registros_pasados_titulo'>";
			echo $row_obtener_registros_de_minizonas['of_apellido'] . ", " . $row_obtener_registros_de_minizonas['of_nombre'];
			echo "</span>";
			echo "<span style='margin-left:17em;font-weight:bold;font-size:1.2em' class='numero_ptr'>PTR N° " . $row_obtener_registros_de_minizonas['id_ptr'] . "</span>";
			echo "<div class='registros_pasados_fecha_hora'>";
			echo $row_obtener_registros_de_minizonas['fecha_hora'];
			echo "</div>";
			echo "</div>";
			echo "<div class='textarea_texto_acta'>" . str_replace("\n", "<br>", ucfirst($row_obtener_registros_de_minizonas['texto_acta'])) . "</div>";
			echo "<hr style='border-color:#ffffff;margin-bottom: 20px'>";
		} while ($row_obtener_registros_de_minizonas = mysql_fetch_assoc($obtener_registros_de_minizonas));
	}
}
if ($accion == "obtener_registros_de_minizonas_registros_ptr") {
	$id_ptr_buscar = '';
	if (isset($_GET['id_ptr_buscar']) && !empty($_GET['id_ptr_buscar'])) {
		$id_ptr_buscar = $_GET['id_ptr_buscar'];
		$id_ptr_buscar = "and a.id_ptr = '$id_ptr_buscar' ";
	}
	$id_minizonas = $_GET['id_minizonas'];

	$query_obtener_registros_de_minizonas = "SELECT *,DATE_FORMAT(fecha_hora_agregado, '%d-%m-%Y %H:%i') as fecha_hora FROM regrad_minizonas_actas a left join regrad_of_rad_minizonas b on b.id_of_rad = a.id_of_rad where a.id_minizonas = '$id_minizonas' $id_ptr_buscar order by fecha_hora_agregado DESC";
	$obtener_registros_de_minizonas = mysql_query($query_obtener_registros_de_minizonas, $miconex);
	$row_obtener_registros_de_minizonas = mysql_fetch_assoc($obtener_registros_de_minizonas);
	$totalRows_obtener_registros_de_minizonas = mysql_num_rows($obtener_registros_de_minizonas);

	if ($totalRows_obtener_registros_de_minizonas == 0) {
		echo "<h2 style='margin:auto;width:100%;text-align:center'>No existen registros cargados aún</h2>";
	} else {
		do {
			echo "<div class='registros_pasados_titulo_oficial'>Oficial &nbsp;&nbsp;";
			echo "<span class='registros_pasados_titulo'>";
			echo $row_obtener_registros_de_minizonas['of_apellido'] . ", " . $row_obtener_registros_de_minizonas['of_nombre'];
			echo "</span>";
			echo "<span style='margin-left:17em;font-weight:bold;font-size:1.2em' class='numero_ptr'>PTR N° " . $row_obtener_registros_de_minizonas['id_ptr'] . "</span>";
			echo "<div class='registros_pasados_fecha_hora'>";
			echo $row_obtener_registros_de_minizonas['fecha_hora'];
			echo "</div>";
			echo "</div>";
			echo "<div class='textarea_texto_acta'>" . str_replace("\n", "<br>", ucfirst($row_obtener_registros_de_minizonas['texto_acta'])) . "</div>";
			echo "<hr style='border-color:#ffffff;margin-bottom: 20px'>";
		} while ($row_obtener_registros_de_minizonas = mysql_fetch_assoc($obtener_registros_de_minizonas));
	}
}
if ($accion == "recuperar_tareas_diarias") {
	$id_planilla = $_GET['id_planilla'];

	// Hacemos el JOIN con la vista_personal_radioproteccion dos veces (una para el oficial, otra para el ayudante)
	$query = "SELECT t.*, r.recinto as nombre_recinto,
			  v1.apellido as of_apellido, v1.nombre as of_nombre,
			  v2.apellido as ay_apellido, v2.nombre as ay_nombre
			  FROM regrad_actas_tareas_diarias t 
			  LEFT JOIN graf_tritio_recintos r ON t.id_recinto = r.id 
			  LEFT JOIN vista_personal_radioproteccion v1 ON t.id_oficial_asociado = v1.id
			  LEFT JOIN vista_personal_radioproteccion v2 ON t.id_ayudante = v2.id
			  WHERE t.id_planilla_diaria = $id_planilla 
			  ORDER BY t.fecha_hora_carga ASC";

	$resultado = mysql_query($query, $miconex) or die(mysql_error());

	if (mysql_num_rows($resultado) == 0) {
		echo "";
	} else {
		while ($row = mysql_fetch_assoc($resultado)) {
			$recinto = $row['nombre_recinto'] ? $row['nombre_recinto'] : "-";
			$ptr = $row['id_ptr'] ? $row['id_ptr'] : "-";
			$id_tarea = $row['id_tarea'];

			// Armamos los nombres en MAYUSCULAS
			$texto_oficial = $row['of_apellido'] ? mb_strtoupper($row['of_apellido'] . ", " . $row['of_nombre'], 'UTF-8') : false;
			$texto_ayudante = $row['ay_apellido'] ? mb_strtoupper($row['ay_apellido'] . ", " . $row['ay_nombre'], 'UTF-8') : false;

			// --- LÓGICA DE LOS 60 MINUTOS ---
			$minutos_transcurridos = round((time() - strtotime($row['fecha_hora_carga'])) / 60);

			echo "<div style='background: white; padding: 10px; margin-bottom: 10px; border-radius: 5px; border: 1px solid #ccc;'>";

			// Cabecera con flexbox
			echo "<div style='border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 5px; display: flex; justify-content: space-between; align-items: center;'>";
			echo "<b style='font-size: 16px; color: #3173a5;'>📍 Recinto: $recinto | 📋 PTR: $ptr</b>";

			if ($minutos_transcurridos <= 60) {
				echo "<div>";
				echo "<img src='Images/icon_edit.png' style='width: 20px; cursor: pointer; margin-right: 15px;' title='Editar (Menos de 60 min)' onclick='editar_tarea($id_tarea)'>";
				echo "<img src='Images/icon_delete.png' style='width: 20px; cursor: pointer;' title='Eliminar' onclick='eliminar_tarea($id_tarea)'>";
				echo "</div>";
			}
			echo "</div>";

			// NUEVO: Fila del personal (Solo se muestra si hay alguien asignado)
			if ($texto_oficial || $texto_ayudante) {
				echo "<div style='font-size: 15px; color: #155724; background-color: #d4edda; padding: 4px 8px; border-radius: 3px; margin-bottom: 5px; display: inline-block;'>";
				if ($texto_oficial) echo "<b>👷‍♂️ Oficial:</b> $texto_oficial ";
				if ($texto_oficial && $texto_ayudante) echo " | ";
				if ($texto_ayudante) echo "<b>👨‍🔧 Asistió:</b> $texto_ayudante";
				echo "</div><br>";
			}

			// Parámetros
			echo "<div style='font-size: 14px; color: #555;'>";
			if ($row['tasa_dosis']) echo "<b>Dosis:</b> {$row['tasa_dosis']} µSv/h | ";
			if ($row['tritio_dac']) echo "<b>Tritio:</b> {$row['tritio_dac']} DAC | ";
			if ($row['contaminacion_beta_gamma']) echo "<b>β+γ:</b> {$row['contaminacion_beta_gamma']} | ";
			if ($row['contaminacion_alfa']) echo "<b>α:</b> {$row['contaminacion_alfa']} | ";
			if ($row['derrame_agua'] == 1) echo "<b style='color:blue;'>💧 Hubo Derrame</b> | ";
			if ($row['dispersion_aerosoles'] == 1) echo "<b style='color:red;'>💨 Aerosoles: {$row['aerosoles_dac']} DAC</b>";
			echo "</div>";

			// Texto
			echo "<div style='margin-top: 10px; font-size: 15px;'>";
			echo "<b>Observaciones:</b> " . nl2br($row['texto_tarea']);
			echo "</div>";
			echo "</div>";
		}
	}
}
if ($accion == "obtener_registros_de_minizonas_V2") {
	$id_minizonas = $_GET['id_minizonas'];
	$id_usuario_actual = $_GET['id_usuario']; // Recibimos quién está mirando
	$es_admin = ($id_usuario_actual == '0'); // Llave maestra
	// 1. PRIMERO TRAEMOS LOS NUEVOS
	$query_nuevos = "SELECT a.*, DATE_FORMAT(a.fecha_hora_agregado, '%d-%m-%Y %H:%i') as fecha_hora, 
					r.recinto as nombre_recinto,
					v1.apellido as of_apellido, v1.nombre as of_nombre, v1.id_rol as of_rol,
					v2.apellido as ay_apellido, v2.nombre as ay_nombre, v2.id_rol as ay_rol
					FROM regrad_minizonas_registros a 
					LEFT JOIN graf_tritio_recintos r ON a.id_recinto = r.id
					LEFT JOIN vista_personal_radioproteccion v1 ON a.id_oficial = v1.id
					LEFT JOIN vista_personal_radioproteccion v2 ON a.id_ayudante = v2.id
					WHERE a.id_minizonas = '$id_minizonas' 
					ORDER BY a.fecha_hora_agregado DESC";

	$res_nuevos = mysql_query($query_nuevos, $miconex);

	if (mysql_num_rows($res_nuevos) > 0) {
		while ($row = mysql_fetch_assoc($res_nuevos)) {
			$recinto = $row['nombre_recinto'] ? $row['nombre_recinto'] : "-";
			$ptr = $row['id_ptr'] ? $row['id_ptr'] : "-";
			$id_registro = $row['id_registro'];

			$texto_oficial = $row['of_apellido'] ? mb_strtoupper($row['of_apellido'] . ", " . $row['of_nombre'], 'UTF-8') : false;
			$texto_ayudante = $row['ay_apellido'] ? mb_strtoupper($row['ay_apellido'] . ", " . $row['ay_nombre'], 'UTF-8') : false;

			$minutos_transcurridos = round((time() - strtotime($row['fecha_hora_agregado'])) / 60);

			echo "<div style='background: white; padding: 15px; margin-bottom: 15px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #ccc;'>";

			echo "<div style='border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;'>";
			echo "<b style='font-size: 16px; color: #3173a5;'>📍 Recinto: $recinto | 📋 PTR: $ptr</b>";

			echo "<div>";
			echo "<span style='font-style: italic; font-size: 0.9em; color: gray; margin-right: 15px;'>" . $row['fecha_hora'] . "</span>";

			// Botones si está dentro de los 60 min O si es el admin
			if ($minutos_transcurridos <= 60 || $es_admin) {
				echo "<img src='Images/icon_edit.png' style='width: 20px; cursor: pointer; margin-right: 10px; vertical-align: middle;' title='Editar' onclick='editar_registro($id_registro)'>";
				echo "<img src='Images/icon_delete.png' style='width: 20px; cursor: pointer; vertical-align: middle;' title='Eliminar' onclick='eliminar_registro($id_registro)'>";
			}
			echo "</div>";
			echo "</div>";

			if ($texto_oficial || $texto_ayudante) {
				echo "<div style='font-size: 15px; color: #155724; background-color: #d4edda; padding: 4px 8px; border-radius: 3px; margin-bottom: 10px; display: inline-block;'>";
				if ($texto_oficial) echo "<b>👷‍♂️ Oficial:</b> $texto_oficial ";
				if ($texto_oficial && $texto_ayudante) echo " | ";
				if ($texto_ayudante) echo "<b>👨‍🔧 Ayudante:</b> $texto_ayudante";
				echo "</div><br>";
			}

			echo "<div style='font-size: 14px; color: #555;'>";
			if ($row['tasa_dosis']) echo "<b>Dosis:</b> {$row['tasa_dosis']} µSv/h | ";
			if ($row['tritio_dac']) echo "<b>Tritio:</b> {$row['tritio_dac']} DAC | ";
			if ($row['contaminacion_beta_gamma']) echo "<b>β+γ:</b> {$row['contaminacion_beta_gamma']} | ";
			if ($row['contaminacion_alfa']) echo "<b>α:</b> {$row['contaminacion_alfa']} | ";
			if ($row['derrame_agua'] == 1) echo "<b style='color:blue;'>💧 Hubo Derrame</b> | ";
			if ($row['dispersion_aerosoles'] == 1) echo "<b style='color:red;'>💨 Aerosoles: {$row['aerosoles_dac']} DAC</b>";
			echo "</div>";

			echo "<div style='margin-top: 10px; font-size: 15px;'>";
			echo "<b>Observaciones:</b><br>" . nl2br($row['texto_acta']);
			echo "</div>";
			echo "</div>";
		}
	}

	// 2. DESPUÉS TRAEMOS EL HISTORIAL VIEJO
	$query_viejos = "SELECT *,DATE_FORMAT(fecha_hora_agregado, '%d-%m-%Y %H:%i') as fecha_hora FROM regrad_minizonas_actas a left join regrad_of_rad b on b.id_of_rad = a.id_of_rad where a.id_minizonas = '$id_minizonas' order by fecha_hora_agregado DESC";
	$res_viejos = mysql_query($query_viejos, $miconex);

	if (mysql_num_rows($res_viejos) > 0) {
		while ($row = mysql_fetch_assoc($res_viejos)) {
			echo "<div class='registros_pasados_titulo_oficial'>Oficial &nbsp;&nbsp;";
			echo "<span class='registros_pasados_titulo'>" . $row['of_apellido'] . ", " . $row['of_nombre'] . "</span>";
			echo "<span style='margin-left:17em;font-weight:bold;font-size:1.2em' class='numero_ptr'>PTR N° " . $row['id_ptr'] . "</span>";
			echo "<div class='registros_pasados_fecha_hora'>" . $row['fecha_hora'] . "</div>";
			echo "</div>";
			echo "<div class='textarea_texto_acta'>" . str_replace("\n", "<br>", ucfirst($row['texto_acta'])) . "</div>";
			echo "<hr style='border-color:#ccc; margin-bottom: 20px'>";
		}
	}

	if (mysql_num_rows($res_nuevos) == 0 && mysql_num_rows($res_viejos) == 0) {
		echo "<h2 style='margin:auto;width:100%;text-align:center'>No existen registros cargados aún</h2>";
	}
}
if ($accion == "recuperar_registro_minizonas_para_edicion_V2") {
	$id_registro = $_GET['id_registro'];

	$query = "SELECT a.*, r.recinto as nombre_recinto, 
			  v1.apellido as of_apellido, v1.nombre as of_nombre, v1.id_rol as of_rol,
			  v2.apellido as ay_apellido, v2.nombre as ay_nombre, v2.id_rol as ay_rol
			  FROM regrad_minizonas_registros a
			  LEFT JOIN graf_tritio_recintos r ON a.id_recinto = r.id
			  LEFT JOIN vista_personal_radioproteccion v1 ON a.id_oficial = v1.id
			  LEFT JOIN vista_personal_radioproteccion v2 ON a.id_ayudante = v2.id
			  WHERE a.id_registro = $id_registro";

	$resultado = mysql_query($query, $miconex);
	$row = mysql_fetch_assoc($resultado);

	$oficial_label = "";
	if ($row['id_oficial']) {
		$tipo_of = ($row['of_rol'] == 1) ? '(P)' : (($row['of_rol'] == 2) ? '(Parada)' : '(Ayudante)');
		$oficial_label = mb_strtoupper($row['of_apellido'] . ", " . $row['of_nombre'], 'UTF-8') . " " . $tipo_of;
	}

	$ayudante_label = "";
	if ($row['id_ayudante']) {
		$tipo_ay = ($row['ay_rol'] == 1) ? '(P)' : (($row['ay_rol'] == 2) ? '(Parada)' : '(Ayudante)');
		$ayudante_label = mb_strtoupper($row['ay_apellido'] . ", " . $row['ay_nombre'], 'UTF-8') . " " . $tipo_ay;
	}

	$datos = array(
		"id_registro" => $row['id_registro'],
		"texto_acta" => $row['texto_acta'],
		"id_recinto" => $row['id_recinto'] ? $row['id_recinto'] : "-",
		"nombre_recinto" => $row['nombre_recinto'] ? $row['nombre_recinto'] : "",
		"id_ptr" => $row['id_ptr'] ? $row['id_ptr'] : "",
		"tasa_dosis" => $row['tasa_dosis'],
		"tritio_dac" => $row['tritio_dac'],
		"contaminacion_beta_gamma" => $row['contaminacion_beta_gamma'],
		"contaminacion_alfa" => $row['contaminacion_alfa'],
		"derrame_agua" => $row['derrame_agua'],
		"dispersion_aerosoles" => $row['dispersion_aerosoles'],
		"aerosoles_dac" => $row['aerosoles_dac'],
		"id_oficial" => $row['id_oficial'],
		"oficial_label" => $oficial_label,
		"id_ayudante" => $row['id_ayudante'],
		"ayudante_label" => $ayudante_label
	);

	header('Content-Type: application/json');
	echo json_encode($datos);
	exit;
}
Mysql_close();
?>