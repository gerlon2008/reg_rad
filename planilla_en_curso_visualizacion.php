<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/miconex.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/ptr/Functions/funciones.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/niveles.php");

session_start();
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) {
	$tipo_user = $_SESSION['portal_nivel'];
	$id_user = $_SESSION['id_user'];
	$of_rad_registro = $_SESSION['id_user'];
} else {
	echo "Esta pagina es solo para usuarios registrados.<br>";
	exit;
}

$id_planilla = $_GET['id_planilla'];

mysql_set_charset('utf8', $miconex);

mysql_select_db($database_miconex, $miconex);


$query_cargar_valores_rutina = "SELECT * FROM regrad_relevamiento_rutina a left join regrad_of_rad b on b.id_of_rad = a.id_oficial left join graf_tritio_recintos c on c.id = a.ubicacion where id_planilla = $id_planilla order by fecha, hora";
$cargar_valores_rutina = mysql_query($query_cargar_valores_rutina, $miconex) or die(mysql_error());
$row_cargar_valores_rutina = mysql_fetch_assoc($cargar_valores_rutina);
$totalRows_cargar_valores_rutina = mysql_num_rows($cargar_valores_rutina);

$query_recuperar_datos_gases_nobles = "SELECT * FROM `regrad_gases_nobles` where id_planilla = $id_planilla";
$recuperar_datos_gases_nobles = mysql_query($query_recuperar_datos_gases_nobles, $miconex) or die(mysql_error());
$row_recuperar_datos_gases_nobles = mysql_fetch_assoc($recuperar_datos_gases_nobles);
$totalRows_recuperar_datos_gases_nobles = mysql_num_rows($recuperar_datos_gases_nobles);

$query_recuperar_datos_emision_tritio = "SELECT * FROM `regrad_tritio` where id_planilla = $id_planilla";
$recuperar_datos_emision_tritio = mysql_query($query_recuperar_datos_emision_tritio, $miconex) or die(mysql_error());
$row_recuperar_datos_emision_tritio = mysql_fetch_assoc($recuperar_datos_emision_tritio);
$totalRows_recuperar_datos_emision_tritio = mysql_num_rows($recuperar_datos_emision_tritio);

$query_recuperar_datos_carbono14 = "SELECT * FROM `regrad_carbono14` where id_planilla = $id_planilla";
$recuperar_datos_carbono14 = mysql_query($query_recuperar_datos_carbono14, $miconex) or die(mysql_error());
$row_recuperar_datos_carbono14 = mysql_fetch_assoc($recuperar_datos_carbono14);
$totalRows_recuperar_datos_carbono14 = mysql_num_rows($recuperar_datos_carbono14);

$query_recuperar_datos_dedo_frio = "SELECT * FROM `regrad_dedo_frio` where id_planilla = $id_planilla";
$recuperar_datos_dedo_frio = mysql_query($query_recuperar_datos_dedo_frio, $miconex) or die(mysql_error());
$row_recuperar_datos_dedo_frio = mysql_fetch_assoc($recuperar_datos_dedo_frio);
$totalRows_recuperar_datos_dedo_frio = mysql_num_rows($recuperar_datos_dedo_frio);

$query_recuperar_datos_concentrador7923 = "SELECT * FROM `regrad_tritio_concentrador` where id_planilla = $id_planilla";
$recuperar_datos_concentrador7923 = mysql_query($query_recuperar_datos_concentrador7923, $miconex) or die(mysql_error());
$row_recuperar_datos_concentrador7923 = mysql_fetch_assoc($recuperar_datos_concentrador7923);
$totalRows_recuperar_datos_concentrador7923 = mysql_num_rows($recuperar_datos_concentrador7923);

$query_recuperar_datos_control_precintos = "SELECT * FROM `regrad_control_precintos` where id_planilla = $id_planilla";
$recuperar_datos_control_precintos = mysql_query($query_recuperar_datos_control_precintos, $miconex) or die(mysql_error());
$row_recuperar_datos_control_precintos = mysql_fetch_assoc($recuperar_datos_control_precintos);
$totalRows_recuperar_datos_control_precintos = mysql_num_rows($recuperar_datos_control_precintos);

$query_recuperar_datos_condicion_meteorologica = "SELECT * FROM `regrad_cond_meteorologica` where id_planilla = $id_planilla";
$recuperar_datos_condicion_meteorologica = mysql_query($query_recuperar_datos_condicion_meteorologica, $miconex) or die(mysql_error());
$row_recuperar_datos_condicion_meteorologica = mysql_fetch_assoc($recuperar_datos_condicion_meteorologica);
$totalRows_recuperar_datos_condicion_meteorologica = mysql_num_rows($recuperar_datos_condicion_meteorologica);

$query_recuperar_datos_espectrometria_aire = "SELECT *, b.recinto as recinto_nombre FROM regrad_espectr_conc_radionuclei a left join graf_tritio_recintos b on b.id = a.recinto  where id_planilla = $id_planilla ";
$recuperar_datos_espectrometria_aire = mysql_query($query_recuperar_datos_espectrometria_aire, $miconex) or die(mysql_error());
$row_recuperar_datos_espectrometria_aire = mysql_fetch_assoc($recuperar_datos_espectrometria_aire);
$totalRows_recuperar_datos_espectrometria_aire = mysql_num_rows($recuperar_datos_espectrometria_aire);

$query_recuperar_datos_efluentes_liquidos = "SELECT * FROM `reg_rad_ef_liq_final_para_tabla` where id_planilla = $id_planilla ";
$recuperar_datos_efluentes_liquidos = mysql_query($query_recuperar_datos_efluentes_liquidos, $miconex) or die(mysql_error());
$row_recuperar_datos_efluentes_liquidos = mysql_fetch_assoc($recuperar_datos_efluentes_liquidos);
$totalRows_recuperar_datos_efluentes_liquidos = mysql_num_rows($recuperar_datos_efluentes_liquidos);

$query_cargar_valores_contaminacion_superficial = "SELECT *, b.recinto as recinto_nombre FROM regrad_contaminacion_superficial a left join graf_tritio_recintos b on b.id = a.recinto where id_planilla = $id_planilla order by fecha, hora";
$cargar_valores_contaminacion_superficial = mysql_query($query_cargar_valores_contaminacion_superficial, $miconex) or die(mysql_error());
$row_cargar_valores_contaminacion_superficial = mysql_fetch_assoc($cargar_valores_contaminacion_superficial);
$totalRows_cargar_valores_contaminacion_superficial = mysql_num_rows($cargar_valores_contaminacion_superficial);

$query_cargar_valores_eficiencia_dryers = "SELECT * FROM reg_rad_eficiencia_dryers where id_planilla = $id_planilla";
$cargar_valores_eficiencia_dryers = mysql_query($query_cargar_valores_eficiencia_dryers, $miconex) or die(mysql_error());
$row_cargar_valores_eficiencia_dryers = mysql_fetch_assoc($cargar_valores_eficiencia_dryers);
$totalRows_cargar_valores_eficiencia_dryers = mysql_num_rows($cargar_valores_eficiencia_dryers);

$query_campo_observaciones = "SELECT * FROM `regrad_planilla_diaria_nueva` where id_planilla_diaria = '$id_planilla'";
$campo_observaciones = mysql_query($query_campo_observaciones, $miconex) or die(mysql_error());
$row_campo_observaciones = mysql_fetch_assoc($campo_observaciones);

$query_planilla = "SELECT * FROM regrad_planilla_diaria_nueva where id_planilla_diaria = $id_planilla";
$planilla = mysql_query($query_planilla, $miconex) or die(mysql_error());
$row_planilla = mysql_fetch_assoc($planilla);

$oficial_de_planilla = $row_planilla['of_rad'];
$turno_de_planilla = $row_planilla['turno'];
$jefe_turno_de_planilla = $row_planilla['jefe_turno'];

$query_oficial_planilla_actual = "SELECT * FROM `ptr`.`regrad_of_rad` WHERE `id_of_rad` = '$oficial_de_planilla'";
$oficial_planilla_actual = mysql_query($query_oficial_planilla_actual, $miconex) or die(mysql_error());
$row_oficial_planilla_actual = mysql_fetch_assoc($oficial_planilla_actual);
$totalRows_oficial_planilla_actual = mysql_num_rows($oficial_planilla_actual);

$query_turno_planilla_actual = "SELECT * FROM `ptr`.`regrad_turnos` WHERE `id_turnos` = '$turno_de_planilla'";
$turno_planilla_actual = mysql_query($query_turno_planilla_actual, $miconex) or die(mysql_error());
$row_turno_planilla_actual = mysql_fetch_assoc($turno_planilla_actual);
$totalRows_turno_planilla_actual = mysql_num_rows($turno_planilla_actual);

$query_jefe_turno_planilla_actual = "SELECT * FROM `ptr`.`regrad_jefes_turno` WHERE `id_jefes_turno` = '$jefe_turno_de_planilla'";
$jefe_turno_planilla_actual = mysql_query($query_jefe_turno_planilla_actual, $miconex) or die(mysql_error());
$row_jefe_turno_planilla_actual = mysql_fetch_assoc($jefe_turno_planilla_actual);
$totalRows_jefe_turno_planilla_actual = mysql_num_rows($jefe_turno_planilla_actual);

$query_campo_observaciones = "SELECT * FROM `regrad_textos_varios` where dato_clave = 'observaciones_registro_predeterminadas'";
$campo_observaciones = mysql_query($query_campo_observaciones, $miconex) or die(mysql_error());
$row_campo_observaciones = mysql_fetch_assoc($campo_observaciones);

if (is_null($row_planilla['observaciones']) or $row_planilla['observaciones'] == "") {
	$campo_observaciones = $row_campo_observaciones['dato_contenido'];
} else {
	$campo_observaciones = $row_planilla['observaciones'];
}

?>
<!DOCTYPE html>
<html lang=“es” style="height:100%">

<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="viewport">
	<title>Registro Radiologico Online</title>
	<link rel="stylesheet" href="/ptr/css/portal_syr.css" />
	<link rel="stylesheet" href="/ptr/prueba_mario/main.css" />
	<link rel="stylesheet" href="/ptr/prueba_mario/jquery.dataTables.css">
	<link rel="stylesheet" href="/ptr/jquery-ui-1.11.4.custom/dataTables.jqueryui.css">
	<link rel="stylesheet" href="/ptr/jquery-ui-1.12.1 - verde2/jquery-ui.min.css" />
	<script src="/ptr/prueba_mario/jquery-2.1.3.min.js"></script>
	<script src="/ptr/prueba_mario/jquery-ui-1.11.4.min.js"></script>
	<script src="/ptr/prueba_mario/jquery.dataTables-1.10.7.min.js"></script>
	<script src="/ptr/prueba_mario/dataTables.jqueryui.min.js"></script>

	<style type="text/css">
		table.dataTable tbody tr.myeven {
			background-color: #ffffff;
		}

		table.dataTable tbody tr.myodd {
			background-color: #e4dfcc;
		}

		.popup {
			position: fixed;
			left: 0px;
			top: 0px;
			height: 100%;
			width: 100%;
			background: rgba(255, 255, 255, .8);
			text-align: center;
			font-size: 30px;
			padding-top: 90px;
			display: none;
		}

		.special.ui-state-default {
			background-color: #3173a5;
			background-image: none;
		}

		.tabla_custom {
			background-color: #e4dfcc;
			padding: 10px;
			border-radius: 10px;
		}

		input {
			text-align: center;
		}

		h3 {
			margin-top: -5px;
			margin-bottom: 5px;
		}

		.ui-accordion-content {
			margin-bottom: 10px
		}
	</style>
	<script type="text/javascript">
		$(function() {
			$(document).tooltip();
		});
	</script>
</head>

<body style="font-family:Ubuntu;text-align:;width:100%;height:100%;padding:0px;margin:0px" onload="carga_pagina_functions()">
	<div class="container">
		<div class="mainbody" style="background-image: background-size:100% auto;background-image:url('../images/fondo_verde_registro_rad.jpg');">
			<div style="display: inline-block">
				<h2>Planilla diaria - Carga en Curso</h2>
			</div>
			<div id="div_tabla_relevamiento_rutina" style="width: 75%;margin: auto;padding: 10px" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all ui-accordion-content-active">
				<table align="center" style="width: 100%;font-size: 20px">
					<tr style="padding-top: 6px">
						<th style="width: 25%;height: 50px">Fecha</th>
						<th style="width: 25%;height: 50px">Oficial de Radioprotección</th>
						<th style="width: 25%;height: 50px">Turno</th>
						<th style="width: 25%;height: 50px">Jefe de Turno</th>
					</tr>
					<tr>
						<td style="padding-top: 7px;padding-bottom: 7px;margin-left: 3px;margin-right: 3px" class="ui-widget ui-state-default">
							<?php echo formafecha6($row_planilla['fecha']) ?>
						</td>
						<td style="padding-top: 7px;padding-bottom: 7px;margin-left: 3px;margin-right: 3px" class="ui-widget ui-state-default">
							<?php echo $row_oficial_planilla_actual['of_apellido'] . ", " . $row_oficial_planilla_actual['of_nombre'] ?>
						</td>
						<td style="padding-top: 7px;padding-bottom: 7px;margin-left: 3px;margin-right: 3px" class="ui-widget ui-state-default">
							<?php echo $row_turno_planilla_actual['horarios'] ?>
						</td>
						<td style="padding-top: 7px;padding-bottom: 7px;margin-left: 3px;margin-right: 3px" class="ui-widget ui-state-default">
							<?php echo $row_jefe_turno_planilla_actual['jefe_apellido'] . ", " . $row_jefe_turno_planilla_actual['jefe_nombre'] ?>
						</td>
					</tr>
				</table>
			</div>
			<div id="div_tabla_relevamiento_rutina" style="width: 75%;margin: auto;padding: 10px" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all ui-accordion-content-active">
				<h3>RELEVAMIENTO DE RUTINA</h3>
				<h4 style="text-align: left;margin-top: -20px;margin-bottom: 0px">Mediciones cargadas: <span id="contador_mediciones"><?php echo $totalRows_cargar_valores_rutina ?></span></h4>
				<table style="width: 100%" id="tabla_relevamiento_rutina" align="center">
					<thead>
						<tr style="text-align: center;">
							<th rowspan="2" style="width: 80px">Fecha</th>
							<th rowspan="2" style="width: 60px">Hora</th>
							<th rowspan="2" style="width: 90px">Ubicación</th>
							<th style="border-bottom: none;width: 80px">G.Nobles</th>
							<th style="border-bottom: none;width: 80px">Tritio</th>
							<th style="border-bottom: none;width: 80px">T.Dosis</th>
							<th rowspan="2">Observaciones</th>
							<th rowspan="2" style="width: 150px">Relevado por</th>
						</tr>
						<tr style="text-align: center;font-size: 13px">
							<th style="border-top: none">(DAC)</th>
							<th style="border-top: none">(DAC)</th>
							<th style="border-top: none; border-right: none;">(µSv/h)</th>
						</tr>
					</thead>
					<tbody style="font-size: 14px;text-align: center;">
						<?php do { ?>
							<tr>
								<td><?php echo formafecha6($row_cargar_valores_rutina['fecha']) ?></td>
								<td><?php echo formato_hora_2($row_cargar_valores_rutina['hora']) ?></td>
								<td><?php echo ($row_cargar_valores_rutina['recinto']) ?></td>
								<td><?php echo nulo(decimales($row_cargar_valores_rutina['gases_nobles'], 1)) ?></td>
								<td><?php echo nulo(decimales($row_cargar_valores_rutina['tritio'], 1)) ?></td>
								<td><?php echo nulo(decimales($row_cargar_valores_rutina['tasa_dosis'], 1)) ?></td>
								<td><?php echo $row_cargar_valores_rutina['observaciones'] ?></td>
								<td><?php echo $row_cargar_valores_rutina['of_apellido'] . ", " . $row_cargar_valores_rutina['of_nombre'] ?></td>
							</tr>
						<?php } while ($row_cargar_valores_rutina = mysql_fetch_assoc($cargar_valores_rutina))  ?>
					</tbody>
				</table>
			</div>
			<br>
			<div id="div_efluentes_gaseosos" style="width: 75%;margin: auto;padding: 10px" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all ui-accordion-content-active">
				<h3>EMISIÓN DE EFLUENTES GASEOSOS</h3>
				<table align="center" style="width: 100%" border="0">
					<tr>
						<td colspan="4" style="width: 50%;padding: 10px" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all ui-accordion-content-active">
							<h3 id="gases_nobles_title">Gases Nobles</h3>
							<table align="center" style="width: 60%;text-align: center;" border="0" class="tabla_custom">
								<thead>
									<tr style="text-align: center;">
										<th>Desde</th>
										<td colspan="2">
											<div id="mostrar_gases_nobles_desde" style="width: 100px;height: 24px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"><?php echo formato_hora_personalizado($row_recuperar_datos_gases_nobles['hora_desde']) ?></div>
										</td>
										<th>Hasta</th>
										<td colspan="2">
											<div id="mostrar_gases_nobles_hasta" style="width: 100px;height: 24px;margin: auto" class="ui-widget ui-state-default ui-corner-all"><?php echo formato_hora_personalizado($row_recuperar_datos_gases_nobles['hora_hasta']) ?></div>
										</td>
									</tr>
									<tr style="font-size: 15px">
										<th>Ar-41</th>
										<th>Kr-85</th>
										<th>Kr-85m</th>
										<th>Xe-133</th>
										<th>Xe-135</th>
										<th>Xe-135m</th>
									</tr>
									<tr>
										<td>
											<div id="mostrar_gases_nobles_ar41" style="width: 80px;height: 22px;margin: auto" class="ui-widget ui-state-default "><?php echo nulo(decimales(conversion_actividad('1', '5', $row_recuperar_datos_gases_nobles['ar41']), 1)) ?></div>
										</td>
										<td>
											<div id="mostrar_gases_nobles_kr85" style="width: 80px;height: 22px;margin: auto" class="ui-widget ui-state-default "><?php echo nulo(decimales(conversion_actividad('1', '5', $row_recuperar_datos_gases_nobles['kr85']), 1)) ?></div>
										</td>
										<td>
											<div id="mostrar_gases_nobles_kr85m" style="width: 80px;height: 22px;margin: auto" class="ui-widget ui-state-default "><?php echo nulo(decimales(conversion_actividad('1', '5', $row_recuperar_datos_gases_nobles['kr85m']), 1)) ?></div>
										</td>
										<td>
											<div id="mostrar_gases_nobles_xe133" style="width: 80px;height: 22px;margin: auto" class="ui-widget ui-state-default "><?php echo nulo(decimales(conversion_actividad('1', '5', $row_recuperar_datos_gases_nobles['xe133']), 1)) ?></div>
										</td>
										<td>
											<div id="mostrar_gases_nobles_xe135" style="width: 80px;height: 22px;margin: auto" class="ui-widget ui-state-default "><?php echo nulo(decimales(conversion_actividad('1', '5', $row_recuperar_datos_gases_nobles['xe135']), 1)) ?></div>
										</td>
										<td>
											<div id="mostrar_gases_nobles_xe135m" style="width: 80px;height: 22px;margin: auto" class="ui-widget ui-state-default "><?php echo nulo(decimales(conversion_actividad('1', '5', $row_recuperar_datos_gases_nobles['xe135m']), 1)) ?></div>
										</td>
									</tr>
									<tr>
										<td style="color: ;font-size: 14px;vertical-align: top">GBq</td>
										<td style="color: ;font-size: 14px;vertical-align: top">GBq</td>
										<td style="color: ;font-size: 14px;vertical-align: top">GBq</td>
										<td style="color: ;font-size: 14px;vertical-align: top">GBq</td>
										<td style="color: ;font-size: 14px;vertical-align: top">GBq</td>
										<td style="color: ;font-size: 14px;vertical-align: top">GBq</td>
									</tr>
								</thead>
							</table>
						</td>
						<td colspan="4" style="width: 50%;;padding: 10px" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all ui-accordion-content-active">
							<h3 id="emision_tritio_title">Emisión Tritio</h3>
							<div style="width: 75%;margin:auto">
								<table id="tabla_emision_tritio">
									<thead>
										<tr>
											<th rowspan="2" style="width: 25%;font-size: 18px">Desde</th>
											<th rowspan="2" style="width: 25%;font-size: 18px">Hasta</th>
											<th style="width: 25%;font-size: 18px;border-bottom: none">Vent. Gral.</th>
											<th style="width: 25%;font-size: 18px;border-bottom: none">Vent. Reactor.</th>
										</tr>
										<tr style="font-size: 13px">
											<th style="border-top: none">(GBq)</th>
											<th style="border-top: none;border-right: ;">(GBq)</th>
										</tr>
									</thead>
									<tbody style="font-size: 15px">
										<?php if ($totalRows_recuperar_datos_emision_tritio == 0) {
										} else {
											do { ?>
												<tr>
													<td><?php echo formato_hora_personalizado($row_recuperar_datos_emision_tritio['hora_desde']) ?></td>
													<td><?php echo formato_hora_personalizado($row_recuperar_datos_emision_tritio['hora_hasta']) ?></td>
													<td><?php echo nulo(decimales(conversion_actividad('1', '5', $row_recuperar_datos_emision_tritio['vent_gral']), 1)) ?></td>
													<td><?php echo nulo(decimales(conversion_actividad('1', '5', $row_recuperar_datos_emision_tritio['vent_reactor']), 1)) ?></td>
												</tr>
										<?php } while ($row_recuperar_datos_emision_tritio = mysql_fetch_assoc($recuperar_datos_emision_tritio));
										} ?>
									</tbody>
								</table>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="2" style="padding: 10px" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all ui-accordion-content-active">
							<h3 id="carbono14_title">Carbono 14</h3>
							<table class="tabla_custom" style="width: 75%" align="center" border="0">
								<thead>
									<tr>
										<th>Hora</th>
										<th colspan="2">Actividad</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>
											<div id="mostrar_carbono14_hora" style="width: 80px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"><?php echo formato_hora_personalizado($row_recuperar_datos_carbono14['hora']) ?></div>
										</td>
										<td style="width: 30%">
											<div id="mostrar_carbono14_actividad" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"><?php echo nulo(decimales(conversion_actividad('1', '3', $row_recuperar_datos_carbono14['medicion']), 0)) ?></div>
										</td>
										<td style="text-align: ;font-size: 14px;width: 25%">(MBq)</td>
									</tr>
								</tbody>
							</table>
						</td>
						<td colspan="2" style=";padding: 10px" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all ui-accordion-content-active">
							<h3 id="dedo_frio_title">Dedo Frío</h3>
							<table class="tabla_custom" style="width: 50%" align="center" border="0">
								<thead>
									<tr>
										<th style="width: 50%">Hora</th>
										<th style="width: 50%">ppm</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>
											<div id="mostrar_dedo_frio_hora" style="width: 80px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"><?php echo formato_hora_personalizado($row_recuperar_datos_dedo_frio['hora_medicion']) ?></div>
										</td>
										<td>
											<div id="mostrar_dedo_frio_ppm" style="width: 80px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all">
												<?php if ($row_recuperar_datos_dedo_frio['menor_que'] == 1) {
													echo "< ";
												};
												echo nulo(decimales($row_recuperar_datos_dedo_frio['ppm'], 0)) ?>
											</div>
										</td>
									</tr>
								</tbody>
							</table>
						</td>
						<td colspan="" style=";padding: 10px" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all ui-accordion-content-active">
							<h3 id="concentrador_7923_title">Tritio Concentrador 7923</h3>
							<table class="tabla_custom" style="width: 75%" align="center" border="0">
								<thead>
									<tr>
										<th style="width: 25%;text-align: right;">Procedencia</th>
										<th style="width: 25%">
											<div id="mostrar_concentrador7923_procedencia" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"><?php echo nulo2($row_recuperar_datos_concentrador7923['procedencia']) ?></div>
										</th>
										<th style="width: 25%;text-align: right;">Actividad <span style="font-size: 14px;font-weight: normal;">(TBq)</span></th>
										<th style="width: 25%">
											<div id="mostrar_concentrador7923_actividad" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"><?php echo coma_cambio2(nulo(decimales(conversion_actividad('1', '2', $row_recuperar_datos_concentrador7923['actividad_Bq']), 3))) ?></div>
										</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<th style="width: 25%;text-align: right;">Volumen <span style="font-size: 14px;font-weight: normal;">(m<sup>3</sup>)</span></th>
										<th style="width: 25%">
											<div id="mostrar_concentrador7923_volumen" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"><?php echo coma_cambio2(nulo(decimales(conversion_volumen('1', '2', $row_recuperar_datos_concentrador7923['volumen_litro']), 2))) ?></div>
										</th>
										<th style="width: 25%;text-align: right;">RoDD <span style="font-size: 14px;font-weight: normal;">(%)</span></th>
										<th style="width: 25%">
											<div id="mostrar_concentrador7923_ldd" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"><?php echo coma_cambio2(nulo(decimales($row_recuperar_datos_concentrador7923['ldd%'], 2))) ?></div>
										</th>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
					<tr>
						<td colspan="3" style=";padding: 10px" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all ui-accordion-content-active">
							<h3 id="control_precintos_title">Control de Precintos</h3>
							<table class="tabla_custom" style="width: 75%" align="center" border="0">
								<thead>
									<tr>
										<th style="width: 25%;text-align: center;">Hora</th>
										<th style="width: 25%;text-align: center;">Puerta S-123B</th>
										<th style="width: 25%;text-align: center;">Puerta S-008</th>
										<th style="width: 25%;text-align: center;">Puerta S-144</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<th style="width: 25%;padding-left: 3px;padding-right: 3px">
											<div id="mostrar_control_precintos_hora" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"><?php echo (formato_hora_personalizado($row_recuperar_datos_control_precintos['hora'])) ?></div>
										</th>
										<th style="width: 25%;padding-left: 3px;padding-right: 3px">
											<div id="mostrar_control_precintos_puerta_s_123b" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"><?php echo nulo2(ucwords($row_recuperar_datos_control_precintos['puerta_s123B'])) ?></div>
										</th>
										<th style="width: 25%;padding-left: 3px;padding-right: 3px">
											<div id="mostrar_control_precintos_puerta_s_008" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"><?php echo nulo2(ucwords($row_recuperar_datos_control_precintos['puerta_s008'])) ?></div>
										</th>
										<th style="width: 25%;padding-left: 3px;padding-right: 3px">
											<div id="mostrar_control_precintos_puerta_s_144" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"><?php echo nulo2(ucwords($row_recuperar_datos_control_precintos['puerta_s144'])) ?></div>
										</th>
									</tr>
								</tbody>
							</table>
						</td>
						<td colspan="5" style=";padding: 10px" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all ui-accordion-content-active">
							<h3 id="condicion_meteorologica_title">Condición Meteorológica</h3>
							<table class="tabla_custom" style="width: 75%" align="center" border="0">
								<thead>
									<tr>
										<th style="width: 25%;text-align: center;">Hora</th>
										<th style="width: 25%;text-align: center;">Velocidad <br><span style="font-size: 14px;font-weight: normal;">(m/seg)</span></th>
										<th style="width: 25%;text-align: center;">Dirección del Viento</th>
										<th style="width: 25%;text-align: center;">Condición Pasquill</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<th style="width: 25%;padding-left: 3px;padding-right: 3px">
											<div id="mostrar_condicion_meteorologica_hora" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"><?php echo formato_hora_personalizado($row_recuperar_datos_condicion_meteorologica['hora']) ?></div>
										</th>
										<th style="width: 25%;padding-left: 3px;padding-right: 3px">
											<div id="mostrar_condicion_meteorologica_velocidad_viento" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"><?php echo nulo2(ucwords($row_recuperar_datos_condicion_meteorologica['velocidad_mseg'])) ?></div>
										</th>
										<th style="width: 25%;padding-left: 3px;padding-right: 3px">
											<div id="mostrar_condicion_meteorologica_direccion_viento" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"><?php echo nulo2(ucwords($row_recuperar_datos_condicion_meteorologica['direc_viento'])) ?></div>
										</th>
										<th style="width: 25%;padding-left: 3px;padding-right: 3px">
											<div id="mostrar_condicion_meteorologica_condicion_pasquill" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"><?php echo nulo2(ucwords($row_recuperar_datos_condicion_meteorologica['cond_pasquill'])) ?></div>
										</th>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
				</table>
			</div>
			<div id="div_espectrometria_aire" style="width: 75%;margin: auto;padding: 10px;margin-top: 10px" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all ui-accordion-content-active">
				<h3>ESPECTROMETRÍA DEL AIRE DE ZONA CONTROLADA</h3>
				<table align="center" style="width: 100%" border="0">
					<tr>
						<td>
							<div style="width:75%;margin:auto" id="datos_cargados_espectrometria_aire">
								<table id="tabla_espectrometria_aire">
									<thead>
										<tr>
											<th rowspan="3">Concentración de</th>
											<th rowspan="3">Recinto</th>
											<th rowspan="3">Hora</th>
											<th colspan="5" style="border-bottom: ;">Radionucleídos</th>
										</tr>
										<tr style="text-align: center;font-size: 14px">
											<th style="border-bottom: none;border-top: none;">Ar-41</th>
											<th style="border-bottom: none;border-top: none;">Xe-133</th>
											<th style="border-bottom: none;border-top: none;">Xe-135</th>
											<th style="border-bottom: none;border-top: none;border-right: none;">Kr-88</th>
											<th rowspan="2" style="border-bottom: ;border-top: none;border-right: ;">TOTAL</th>
										</tr>
										<tr style="text-align: center;font-size: 13px">
											<th style="border-top: none;">(DAC)</th>
											<th style="border-top: none;">(DAC)</th>
											<th style="border-top: none;">(DAC)</th>
											<th style="border-top: none;border-right: none;">(DAC)</th>
										</tr>
									</thead>
									<tbody style="font-size: 14px;text-align: center;">
										<?php if ($totalRows_recuperar_datos_espectrometria_aire == 0) {
										} else {
											do { ?>
												<tr>
													<td><?php echo strtoupper($row_recuperar_datos_espectrometria_aire['tipo']) ?></td>
													<td><?php echo nulo2($row_recuperar_datos_espectrometria_aire['recinto_nombre']) ?></td>
													<td><?php echo nulo2(formato_hora_personalizado($row_recuperar_datos_espectrometria_aire['hora'])) ?></td>
													<td><?php echo nulo(decimales($row_recuperar_datos_espectrometria_aire['ar41'], 1)) ?></td>
													<td><?php echo nulo(decimales($row_recuperar_datos_espectrometria_aire['xe133'], 1)) ?></td>
													<td><?php echo nulo(decimales($row_recuperar_datos_espectrometria_aire['xe135'], 1)) ?></td>
													<td><?php echo nulo(decimales($row_recuperar_datos_espectrometria_aire['kr88'], 1)) ?></td>
													<td><?php echo nulo(decimales(($row_recuperar_datos_espectrometria_aire['ar41'] + $row_recuperar_datos_espectrometria_aire['xe133'] + $row_recuperar_datos_espectrometria_aire['xe135'] + $row_recuperar_datos_espectrometria_aire['kr88']), 1)) ?></td>
												</tr>
										<?php } while ($row_recuperar_datos_espectrometria_aire = mysql_fetch_assoc($recuperar_datos_espectrometria_aire));
										} ?>
									</tbody>
								</table>
							</div>
						</td>
					</tr>
				</table>
			</div>
			<div id="div_emision_efluentes_liquidos" style="width: 75%;margin: auto;padding: 10px;margin-top: 10px" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all ui-accordion-content-active">
				<h3>EMISIÓN DE EFLUENTES LÍQUIDOS</h3>
				<table align="center" style="width: 75%" border="0">
					<tr>
						<td>
							<table id="tabla_efluentes_liquidos">
								<thead>
									<tr>
										<th rowspan="2">Fecha</th>
										<th rowspan="2">Hora</th>
										<th rowspan="2">Tanque</th>
										<th style="border-bottom: none;">Volumen</th>
										<th style="border-bottom: none;">Actividad Tritio</th>
										<th style="border-bottom: none;">Actividad Gamma</th>
										<th rowspan="2">% del RoDD</th>
										<th rowspan="2">Impresión</th>
									</tr>
									<tr style="text-align: center;font-size: 13px">
										<th style="border-top: none;">(m<sup>3</sup>)</th>
										<th style="border-top: none;">(TBq)</th>
										<th style="border-top: none;border-right: none;">(TBq)</th>
									</tr>
								</thead>
								<tbody style="font-size: 14px;text-align: center;">
									<?php if ($totalRows_recuperar_datos_efluentes_liquidos == 0) {
									} else {
										do { ?>
											<tr>
												<td><?php echo formafecha6($row_recuperar_datos_efluentes_liquidos['fecha']) ?></td>
												<td><?php echo formato_hora_personalizado($row_recuperar_datos_efluentes_liquidos['hora']) ?></td>
												<td><?php echo $row_recuperar_datos_efluentes_liquidos['tanque'] ?></td>
												<td><?php echo coma_punto(((conversion_volumen('1', '2', $row_recuperar_datos_efluentes_liquidos['volumen_litro'])))) ?></td>
												<td><?php echo notacion_cientifica2(conversion_actividad('1', '2', $row_recuperar_datos_efluentes_liquidos['actividad_tritio'])) ?></td>
												<td><?php echo notacion_cientifica2(conversion_actividad('1', '2', $row_recuperar_datos_efluentes_liquidos['actividad_gamma'])) ?></td>
												<td><?php echo decimales(($row_recuperar_datos_efluentes_liquidos['LDD_Ro_tritio'] + $row_recuperar_datos_efluentes_liquidos['LDD_Ro_gamma']), 3) ?></td>
												<td><img src='Images/print.png' style='max-height: 25px;: 25px;vertical-align: middle;cursor: pointer' title='Imprimir' onclick='imprimir_planilla_efluentes(<?php echo $row_recuperar_datos_efluentes_liquidos['id_efluentes'] ?>)'></td>
											</tr>
									<?php } while ($row_recuperar_datos_efluentes_liquidos = mysql_fetch_assoc($recuperar_datos_efluentes_liquidos));
									} ?>
								</tbody>
							</table>
						</td>
					</tr>
				</table>
			</div>
			<div id="div_contaminacion_superficial" style="width: 75%;margin: auto;padding: 10px;margin-top: 10px" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all ui-accordion-content-active">
				<h3>MEDICIÓN DE CONT. SUPERFICIAL Y TASA DE DOSIS EQUIVALENTE</h3>
				<table align="center" style="width: 60%" border="0">
					<tr>
						<td>
							<table id="tabla_contaminacion_superficial">
								<thead>
									<tr>
										<th style="width: 25%" rowspan="3">Recinto</th>
										<th style="width: 25%" rowspan="3">Hora</th>
										<th colspan="2" style="border-bottom: none;width: 25%">Contaminación Superficial</th>
										<th style="width: 25%" rowspan="2">Tasa de Dosis Equivalente</th>
									</tr>
									<tr style="text-align: center;font-size: 14px">
										<th colspan="2" style="border-top: none;border-right: none">(Bq/cm<sup>2</sup>)</th>
									</tr>
									<tr style="text-align: center;font-size: 13px">
										<th style="border-bottom: ;border-top: none;width: %">β+γ</th>
										<th style="border-bottom: ;border-top: none;border-right: none;width: %">α</th>
										<th style="border-bottom: ;border-top: none;border-right: ;">(µSv/h)</th>
									</tr>
								</thead>
								<tbody style="font-size: 14px;text-align: center;">
									<?php if ($totalRows_cargar_valores_contaminacion_superficial == 0) {
									} else {
										do { ?>
											<tr>
												<td><?php echo $row_cargar_valores_contaminacion_superficial['recinto_nombre'] ?></td>
												<td><?php echo formato_hora($row_cargar_valores_contaminacion_superficial['hora']) ?></td>
												<td><?php echo nulo(decimales($row_cargar_valores_contaminacion_superficial['beta_gamma_Bq_cm2'], 1)) ?></td>
												<td><?php echo nulo(decimales($row_cargar_valores_contaminacion_superficial['alfa_Bq_cm2'], 1)) ?></td>
												<td><?php echo nulo(decimales($row_cargar_valores_contaminacion_superficial['tasa_dosis_equivalente_µsv_h'], 1)) ?></td>
											</tr>
									<?php } while ($row_cargar_valores_contaminacion_superficial = mysql_fetch_assoc($cargar_valores_contaminacion_superficial));
									} ?>
								</tbody>
							</table>
						</td>
					</tr>
				</table>
			</div>
			<div id="div_eficiencia_dryers" style="width: 75%;margin: auto;padding: 10px;margin-top: 10px" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all ui-accordion-content-active">
				<h3>EFICIENCIA DRYERS</h3>
				<table align="center" style="width: 75%" border="0">
					<tr>
						<td>
							<table id="tabla_eficiencia_dryers">
								<thead>
									<tr>
										<th rowspan="2" style="width: calc(100% / 6)%">Fecha</th>
										<th rowspan="2" style="width: calc(100% / 6)%">Hora</th>
										<th rowspan="2" style="width: calc(100% / 6)%">Dryers</th>
										<th style="border-bottom: none;width: calc(100% / 6)%">Entrada</th>
										<th style="border-bottom: none;width: calc(100% / 6)%">Salida</th>
										<th style="border-bottom: none;width: calc(100% / 6)%">Eficiencia</th>
									</tr>
									<tr style="text-align: center;font-size: 14px">
										<th style="border-top: none;">(DAC)</th>
										<th style="border-top: none;">(DAC)</th>
										<th style="border-top: none;border-right: none;">(%)</th>
									</tr>
								</thead>
								<tbody style="font-size: 14px;text-align: center;">
									<?php if ($totalRows_cargar_valores_eficiencia_dryers == 0) {
									} else {
										do { ?>
											<tr>
												<td><?php echo formafecha6($row_cargar_valores_eficiencia_dryers['fecha']) ?></td>
												<td><?php echo formato_hora($row_cargar_valores_eficiencia_dryers['hora']) ?></td>
												<td><?php echo $row_cargar_valores_eficiencia_dryers['nombre_dryers'] ?></td>
												<td><?php echo coma_cambio2($row_cargar_valores_eficiencia_dryers['entrada_dac']) ?></td>
												<td><?php echo coma_cambio2($row_cargar_valores_eficiencia_dryers['salida_dac']) ?></td>
												<td><?php echo decimales($row_cargar_valores_eficiencia_dryers['eficiencia_porcentaje'], 2) ?></td>
											</tr>
									<?php } while ($row_cargar_valores_eficiencia_dryers = mysql_fetch_assoc($cargar_valores_eficiencia_dryers));
									} ?>
								</tbody>
							</table>
						</td>
					</tr>
				</table>
			</div>
			<div id="div_libro_actas_tareas" style="width: 75%; margin: auto; padding: 10px; margin-top: 10px;" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all ui-accordion-content-active">
				<h3>LIBRO DE ACTAS / REGISTRO DE TAREAS</h3>
				<!-- Contenedor donde se insertan las tarjetas individuales recuperadas -->
				<div id="contenedor_tareas_diarias" style="width: 100%; min-height: 50px; max-height: 450px; overflow-y: auto; background-color: #f5f5f5; border-radius: 5px; padding: 10px; box-sizing: border-box; text-align: left;">
					<span style="color: gray; font-style: italic;">Cargando tareas registradas...</span>
				</div>
			</div>

			<div id="div_observaciones_generales" style="width: 75%; margin: auto; padding: 10px; margin-top: 10px;" class="ui-accordion-content ui-helper-reset ui-widget-content ui-corner-all ui-accordion-content-active">
				<h3>OBSERVACIONES GENERALES</h3>
				<table align="center" style="width: 100%;" border="0">
					<tr>
						<td><textarea disabled style="width: 100%; height: 180px; box-sizing: border-box;" class="ui-helper-reset ui-corner-all"><?php echo $campo_observaciones ?></textarea></td>
					</tr>
				</table>
			</div>
		</div>
	</div>
	<div class="footer" style="background-color: #457315">
		<div style="text-align: right;margin-top: 10px">

		</div>
	</div>
	<div class="header" style="background-color: #acdc74">
		<img style="position:relative;left:5px;top:5px;float:left;max-width:120px" src="../images/logo_nasa.png">
		<div style="position:relative;height:100%;margin:auto;font-size:25px;font-family:arial;font-weight:bold;left:-88px;top:53px;float:left;text-shadow: 4px 4px 9px rgba(150, 150, 150, 0.6);">NA-SA</div>
		<div style="text-align:left;margin-left:-154px;text-shadow: 4px 4px 9px rgba(150, 150, 150, 0.6);">
			<h2 style="font-size:25px">Registro Radiológico Online - SyR</h2>
		</div>
		<div style="text-align:right;top: 0px;text-shadow: none;position:absolute;right: 15px">
			<h2 style="text-shadow: none;font-size:15px">Sesión iniciada: <?php echo ucwords($_SESSION['nombre_apellido']) ?></h2>
		</div>

		<?php if ($_SESSION['of_rad_registro_rad'] == '0') { ?>

			<div style="text-align:right;top: 30px;text-shadow: none;position:absolute;right: 15px">
				<h2 style="text-shadow: none;font-size:20px">Usuario normal</h2>



			</div>

		<?php } elseif ($_SESSION['of_rad_registro_rad'] == '1') { ?>

			<div style="text-align:right;top: 30px;text-shadow: none;position:absolute;right: 15px">
				<h2 style="text-shadow: none;font-size:20px">Oficial de Radioprotección</h2>



			</div>
		<?php } ?>
	</div>
	</div>
	<div id="bot_volver_id" style="position:fixed;right:20px;bottom:45px"><img src="../images/icono_volver.png" onclick="window.close();" style="cursor:pointer;height:50px"></div>
	<script type="text/javascript">
		document.body.style.zoom = "85%";

		function imprimir_planilla_efluentes(id_efluentes) {
			window.open('../TCPDF-master/examples/pdf_impresion_efluentes_liquidos_tcpdf.php?id_efluentes=' + id_efluentes)
		}

		$('#tabla_relevamiento_rutina').dataTable({
			"initComplete": function(settings, json) {},
			language: {
				url: "/ptr/prueba_mario/Spanish.json"
			},
			"rowCallback": function(row, data, index) {
				if (index % 2 == 0) {
					$(row).removeClass('myodd myeven');
					$(row).addClass('myodd');
				} else {
					$(row).removeClass('myodd myeven');
					$(row).addClass('myeven');
				}
			},
			stripeClasses: ['odd-row', 'even-row'],
			order: [],
			paging: 0,
			pageLength: 10,
			lengthChange: 0,
			searching: 0,
			ordering: 1,
			info: 0
		});
		$('#tabla_eficiencia_dryers').dataTable({
			"initComplete": function(settings, json) {},
			language: {
				url: "/ptr/prueba_mario/Spanish.json"
			},
			"rowCallback": function(row, data, index) {
				if (index % 2 == 0) {
					$(row).removeClass('myodd myeven');
					$(row).addClass('myodd');
				} else {
					$(row).removeClass('myodd myeven');
					$(row).addClass('myeven');
				}
			},
			stripeClasses: ['odd-row', 'even-row'],
			order: [],
			paging: 0,
			pageLength: 10,
			lengthChange: 0,
			searching: 0,
			ordering: 1,
			info: 0
		});
		$('#tabla_contaminacion_superficial').dataTable({
			"initComplete": function(settings, json) {},
			language: {
				url: "/ptr/prueba_mario/Spanish.json"
			},
			"rowCallback": function(row, data, index) {
				if (index % 2 == 0) {
					$(row).removeClass('myodd myeven');
					$(row).addClass('myodd');
				} else {
					$(row).removeClass('myodd myeven');
					$(row).addClass('myeven');
				}
			},
			stripeClasses: ['odd-row', 'even-row'],
			order: [],
			paging: 0,
			pageLength: 10,
			lengthChange: 0,
			searching: 0,
			ordering: 1,
			info: 0
		});
		$('#tabla_efluentes_liquidos').dataTable({
			"initComplete": function(settings, json) {},
			language: {
				url: "/ptr/prueba_mario/Spanish.json"
			},
			"rowCallback": function(row, data, index) {
				if (index % 2 == 0) {
					$(row).removeClass('myodd myeven');
					$(row).addClass('myodd');
				} else {
					$(row).removeClass('myodd myeven');
					$(row).addClass('myeven');
				}
			},
			stripeClasses: ['odd-row', 'even-row'],
			order: [],
			paging: 0,
			pageLength: 10,
			lengthChange: 0,
			searching: 0,
			ordering: 1,
			info: 0
		});
		$('#tabla_emision_tritio').dataTable({
			"initComplete": function(settings, json) {
				$('#div_tabla_gases_nobles').height($('#div_tabla_emision_tritio').height() - 0.5)
			},
			language: {
				url: "/ptr/prueba_mario/Spanish.json"
			},
			"rowCallback": function(row, data, index) {
				if (index % 2 == 0) {
					$(row).removeClass('myodd myeven');
					$(row).addClass('myodd');
				} else {
					$(row).removeClass('myodd myeven');
					$(row).addClass('myeven');
				}
			},
			stripeClasses: ['odd-row', 'even-row'],
			order: [],
			paging: 0,
			pageLength: 10,
			lengthChange: 0,
			searching: 0,
			ordering: 1,
			info: 0
		});
		$('#tabla_espectrometria_aire').dataTable({
			"initComplete": function(settings, json) {},
			language: {
				url: "/ptr/prueba_mario/Spanish.json"
			},
			"rowCallback": function(row, data, index) {
				if (index % 2 == 0) {
					$(row).removeClass('myodd myeven');
					$(row).addClass('myodd');
				} else {
					$(row).removeClass('myodd myeven');
					$(row).addClass('myeven');
				}
			},
			stripeClasses: ['odd-row', 'even-row'],
			order: [],
			paging: 0,
			pageLength: 10,
			lengthChange: 0,
			searching: 0,
			ordering: 1,
			info: 0
		});

		function recuperar_tareas_diarias_visualizacion() {
			var id_planilla = '<?php echo $id_planilla ?>';
			$.get("procesos_reg_rad.php?accion=recuperar_tareas_diarias&id_planilla=" + id_planilla, function(data) {
				if (!data || data.trim() === "") {
					$('#contenedor_tareas_diarias').html('<div style="text-align: center; color: gray; padding: 10px;">- No se registraron tareas individuales en este turno -</div>');
					return;
				}

				// Filtro de decimales: remueve .000 en enteros y deja 2 decimales con coma en el resto
				var data_formateada = data.replace(/\b(\d+)\.(\d{3})\b/g, function(match) {
					var num = parseFloat(match);
					if (num % 1 === 0) {
						return num.toString();
					} else {
						return num.toFixed(2).replace('.', ',');
					}
				});

				$('#contenedor_tareas_diarias').html(data_formateada);

				// Oculta botones de edición o borrado en vista de solo lectura
				$('#contenedor_tareas_diarias img').hide();
			});
		}

		$(document).ready(function() {
			recuperar_tareas_diarias_visualizacion();
		});
	</script>
</body>

</html>