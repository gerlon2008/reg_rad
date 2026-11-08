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

mysql_query("SET NAMES 'utf8'", $miconex);

mysql_select_db($database_miconex, $miconex);

$id_planilla = $_GET['id_planilla'];

$query_planilla = "SELECT * FROM regrad_planilla_diaria_nueva a left join regrad_turnos b on b.id_turnos = a.turno left join regrad_estados_planta c on c.id_estado_planta = a.estado_planta left join regrad_of_rad d on d.id_of_rad = a.of_rad left join regrad_jefes_turno e on e.id_jefes_turno = a.jefe_turno where id_planilla_diaria = $id_planilla and fecha_hora_cerrado is null";
$planilla = mysql_query($query_planilla, $miconex) or die(mysql_error());
$row_planilla = mysql_fetch_assoc($planilla);
$totalRows_planilla = mysql_num_rows($planilla);


$query_ofrad = "SELECT * FROM `regrad_of_rad` order by of_apellido";
$ofrad = mysql_query($query_ofrad, $miconex) or die(mysql_error());
$row_ofrad = mysql_fetch_assoc($ofrad);
$totalRows_ofrad = mysql_num_rows($ofrad);

$query_dryers = "SELECT * FROM `regrad_dryers`";
$dryers = mysql_query($query_dryers, $miconex) or die(mysql_error());
$row_dryers = mysql_fetch_assoc($dryers);
$totalRows_dryers = mysql_num_rows($dryers);

$query_radionucleidos = "SELECT * FROM `reg_rad_radionucleidos_con_ki_ro`";
$radionucleidos = mysql_query($query_radionucleidos, $miconex) or die(mysql_error());
$row_radionucleidos = mysql_fetch_assoc($radionucleidos);
$totalRows_radionucleidos = mysql_num_rows($radionucleidos);

$query_unidades_concentracion = "SELECT * FROM `regrad_tabla_conversion_unidades_concentracion` order by factor_conversion_a_µci_ml";
$unidades_concentracion = mysql_query($query_unidades_concentracion, $miconex) or die(mysql_error());
$row_unidades_concentracion = mysql_fetch_assoc($unidades_concentracion);
$totalRows_unidades_concentracion = mysql_num_rows($unidades_concentracion);

$query_unidades_concentracion_superficial = "SELECT * FROM `regrad_tabla_conversion_unidades_concentracion_superficial` order by factor_conversion_a_Bq_cm2";
$unidades_concentracion_superficial = mysql_query($query_unidades_concentracion_superficial, $miconex) or die(mysql_error());
$row_unidades_concentracion_superficial = mysql_fetch_assoc($unidades_concentracion_superficial);
$totalRows_unidades_concentracion_superficial = mysql_num_rows($unidades_concentracion_superficial);

$query_unidades_volumen = "SELECT * FROM `regrad_tabla_conversion_unidades_volumen` order by factor_conversion_a_litro";
$unidades_volumen = mysql_query($query_unidades_volumen, $miconex) or die(mysql_error());
$row_unidades_volumen = mysql_fetch_assoc($unidades_volumen);
$totalRows_unidades_volumen = mysql_num_rows($unidades_volumen);

$query_unidades_actividad = "SELECT * FROM `regrad_tabla_conversion_unidades_actividad` order by factor_conversion_a_Bq";
$unidades_actividad = mysql_query($query_unidades_actividad, $miconex) or die(mysql_error());
$row_unidades_actividad = mysql_fetch_assoc($unidades_actividad);
$totalRows_unidades_actividad = mysql_num_rows($unidades_actividad);

$query_unidades_tasa_dosis = "SELECT * FROM `regrad_tabla_conversion_unidades_tasa_dosis` order by factor_conversion_a_µSv_h";
$unidades_tasa_dosis = mysql_query($query_unidades_tasa_dosis, $miconex) or die(mysql_error());
$row_unidades_tasa_dosis = mysql_fetch_assoc($unidades_tasa_dosis);
$totalRows_unidades_tasa_dosis = mysql_num_rows($unidades_tasa_dosis);

$query_recintos = "SELECT * FROM `graf_tritio_recintos` order by recinto";
$recintos = mysql_query($query_recintos, $miconex) or die(mysql_error());
$row_recintos = mysql_fetch_assoc($recintos);
$totalRows_recintos = mysql_num_rows($recintos);

$usuario_id = $_SESSION['id_user'];

$fecha_actual = (new \DateTime())->format('Y-m-d');
$nuevafecha = strtotime('-1 day', strtotime($fecha_actual));
$nuevafecha = date('Y-m-j', $nuevafecha);

$hora_actual = (new \DateTime())->format('H:i');


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
	<link rel="stylesheet" href="select2/select2.min.css" />
	<script src="/ptr/prueba_mario/jquery-2.1.3.min.js"></script>
	<script src="/ptr/prueba_mario/jquery-ui-1.11.4.min.js"></script>
	<script src="/ptr/prueba_mario/jquery.dataTables-1.10.7.min.js"></script>
	<script src="/ptr/prueba_mario/dataTables.jqueryui.min.js"></script>
	<script src="select2/select2.min.js"></script>

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
			text-transform: uppercase;
		}

		/* Estilos para domar el Autocomplete del recinto */
		.ui-autocomplete {
			max-height: 250px;
			overflow-y: auto;
			overflow-x: hidden;
			font-size: 15px !important;
			z-index: 9999 !important;
			/* Para asegurar que siempre flote por encima del popup */
			background-color: #ffffff;
			border: 1px solid #ccc;
			border-radius: 4px;
			box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
		}

		.ui-autocomplete .ui-menu-item {
			padding: 3px 10px !important;
		}

		.ui-autocomplete .ui-menu-item-wrapper {
			padding: 2px !important;
			font-size: 15px !important;
			line-height: 1.2 !important;
			text-transform: uppercase !important;
			/* <-- AGREGAR ESTA LÍNEA */
		}

		/* --- ESTILOS PARA QUE SELECT2 SE VEA COMO JQUERY UI VERDE --- */

		/* 1. El botón cerrado (Igual al de PTR) */
		.select2-container--default .select2-selection--single {
			background: #c3e696 !important;
			background: linear-gradient(to bottom, #e1f2c9 0%, #abdc73 100%) !important;
			border: 1px solid #73a832 !important;
			border-radius: 5px !important;
			height: 30px !important;
			/* Altura para que coincida con tus otros inputs */
			font-family: 'Ubuntu', sans-serif !important;
			font-size: 15px !important;
		}

		/* 2. El texto adentro del select */
		.select2-container--default .select2-selection--single .select2-selection__rendered {
			color: #333 !important;
			line-height: 28px !important;
			padding-left: 10px !important;
			text-transform: uppercase !important;
		}

		/* 3. La flechita de la derecha */
		.select2-container--default .select2-selection--single .select2-selection__arrow {
			height: 28px !important;
		}

		/* 4. La ventanita que se despliega (Domando el texto gigante) */
		.select2-dropdown {
			border: 1px solid #73a832 !important;
			border-radius: 4px !important;
			font-family: 'Ubuntu', sans-serif !important;
			text-transform: uppercase !important;
			-webkit-box-shadow: 1px 4px 14px 0px rgba(0, 0, 0, 0.25);
			box-shadow: 1px 4px 14px 0px rgba(0, 0, 0, 0.25);
			z-index: 99999 !important;
			/* Para que quede siempre por encima de todo */
		}

		/* 5. Achicando las opciones para que no se desborden como en tu captura */
		.select2-results__option {
			font-size: 14px !important;
			padding: 6px 10px !important;
		}

		/* 6. El color al pasar el mouse por la lista */
		.select2-container--default .select2-results__option--highlighted[aria-selected] {
			background-color: #73a832 !important;
			color: white !important;
		}

		/* 7. La cajita del buscador interno */
		.select2-search--dropdown .select2-search__field {
			border: 1px solid #aaa !important;
			border-radius: 3px !important;
			text-transform: uppercase !important;
			font-size: 14px !important;
			padding: 4px !important;
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
				<h2>Planilla diaria</h2>

				<div style="width: 1200px;margin: auto;display: none;" id="tabla_contenedor">
					<div id="accordion" style="width: ">
						<h3>RELEVAMIENTO DE RUTINA</h3>
						<div>
							<p>
							<h4 style="text-align: left;margin-top: -20px;margin-bottom: 0px">Mediciones cargadas: <span id="contador_mediciones">- ninguna -</span></h4>
							<div id="div_lugar_agregar_campos" style="text-align: left;">
								<table style="width: 100%" id="tabla1">
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
											<th rowspan="2" style="width: 150px">Acciones</th>
										</tr>
										<tr style="text-align: center;font-size: 13px">
											<th style="border-top: none">(DAC)</th>
											<th style="border-top: none">(DAC)</th>
											<th style="border-top: none; border-right: none;">(µSv/h)</th>
										</tr>
									</thead>
									<tbody style="font-size: 14px;text-align: center;"></tbody>
								</table>
								<input type="hidden" id="contador_lineas_campos" value="0">
							</div>
							<br>
							<div style="text-align: left;cursor: pointer;" id="div_boton_agregar_medicion_rutina" onclick="agregar_campos_medicion_rutina()">
								<img src="Images/add.svg" style="width: 25px;vertical-align: middle">
								<span style="font-size: 15px">Agregar medición (Gases nobles / Tritio / Tasa de dosis)</span>
							</div>
							</p>
						</div>
						<h3>EMISIÓN EFLUENTES GASEOSOS</h3>
						<div style="padding: 5px">
							<div style="width: 60%;margin: auto">
								<div id="accordion_efluentes" style="width: " class="special">
									<h3 id="gases_nobles_title">Gases Nobles</h3>
									<div>
										<div style="text-align: center;cursor: pointer" id="div_boton_agregar_datos_gases_nobles" onclick="abrir_popup_carga_gases_nobles()">
											<img src="Images/add.svg" style=";width: 25px;vertical-align: middle">
											<span style="font-size: 15px">Cargar datos Gases Nobles</span>
										</div>
										<div style="display: none;" id="datos_cargados_gases_nobles">
											<table align="center" style="width: 60%;text-align: center;" border="0" class="tabla_custom">
												<thead>
													<tr style="text-align: center;">
														<th>Desde</th>
														<td colspan="2">
															<div id="mostrar_gases_nobles_desde" style="width: 100px;height: 24px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"></div>
														</td>
														<th>Hasta</th>
														<td colspan="2">
															<div id="mostrar_gases_nobles_hasta" style="width: 100px;height: 24px;margin: auto" class="ui-widget ui-state-default ui-corner-all"></div>
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
															<div id="mostrar_gases_nobles_ar41" style="width: 80px;height: 22px;margin: auto" class="ui-widget ui-state-default "></div>
														</td>
														<td>
															<div id="mostrar_gases_nobles_kr85" style="width: 80px;height: 22px;margin: auto" class="ui-widget ui-state-default "></div>
														</td>
														<td>
															<div id="mostrar_gases_nobles_kr85m" style="width: 80px;height: 22px;margin: auto" class="ui-widget ui-state-default "></div>
														</td>
														<td>
															<div id="mostrar_gases_nobles_xe133" style="width: 80px;height: 22px;margin: auto" class="ui-widget ui-state-default "></div>
														</td>
														<td>
															<div id="mostrar_gases_nobles_xe135" style="width: 80px;height: 22px;margin: auto" class="ui-widget ui-state-default "></div>
														</td>
														<td>
															<div id="mostrar_gases_nobles_xe135m" style="width: 80px;height: 22px;margin: auto" class="ui-widget ui-state-default "></div>
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
											<input type="hidden" id="id_datos_cargados_gases_nobles" value="">
											<table align="center">
												<tr>
													<td style="width: 300px">
														<div style="text-align: center;margin-top: 10px;cursor: pointer; display: ;" id="div_boton_edicion_datos_gases_nobles" onclick="edicion_gases_nobles()">
															<img src="Images/icon_edit.png" style="width: 25px;vertical-align: middle">
															<span style="font-size: 15px">Editar Datos</span>
														</div>
													</td>
													<td style="width: 300px">
														<div style="text-align: center;margin-top: 10px;cursor: pointer; display: ;" id="div_boton_eliminar_datos_gases_nobles" onclick="eliminar_registro_por_input('regrad_gases_nobles','id_gases_nobles','id_datos_cargados_gases_nobles')">
															<img src="Images/icon_delete.png" style="width: 25px;vertical-align: middle">
															<span style="font-size: 15px">Eliminar Datos</span>
														</div>
													</td>
												</tr>
											</table>
										</div>
									</div>
									<h3 id="emision_tritio_title">Emisión Tritio</h3>
									<div>
										<div style="display: none;" id="datos_cargados_emision_tritio">
											<table id="tabla_emision_tritio">
												<thead>
													<tr>
														<th rowspan="2" style="width: 25%;font-size: 18px">Desde</th>
														<th rowspan="2" style="width: 25%;font-size: 18px">Hasta</th>
														<th style="width: 25%;font-size: 18px;border-bottom: none">Vent. Gral.</th>
														<th style="width: 25%;font-size: 18px;border-bottom: none">Vent. Reactor.</th>
														<th rowspan="2" style="width: 25%;font-size: 18px;border-bottom: ">Acciones</th>
													</tr>
													<tr style="font-size: 13px">
														<th style="border-top: none">(GBq)</th>
														<th style="border-top: none;border-right: none;">(GBq)</th>
													</tr>
												</thead>
												<tbody style="font-size: 15px">
												</tbody>
											</table>
										</div>
										<div style="text-align: center;margin-top: 10px;cursor: pointer;" id="div_boton_agregar_datos_emision_tritio" onclick="abrir_popup_carga_emision_tritio()">
											<img src="Images/add.svg" style="width: 25px;vertical-align: middle">
											<span style="font-size: 15px">Cargar datos Emisión Tritio</span>
										</div>
									</div>
									<h3 id="carbono14_title">Carbono 14</h3>
									<div>
										<div style="text-align: center;margin-top: 10px;cursor: pointer;" id="div_boton_agregar_datos_carbono14" onclick="abrir_popup_carga_carbono14()">
											<img src="Images/add.svg" style="width: 25px;vertical-align: middle">
											<span style="font-size: 15px">Cargar datos Carbono 14</span>
										</div>
										<div style="display: none;" id="datos_cargados_carbono14">
											<table class="tabla_custom" style="width: 50%" align="center" border="0">
												<thead>
													<tr>
														<th>Hora</th>
														<th colspan="2">Actividad</th>
													</tr>
												</thead>
												<tbody>
													<tr>
														<td>
															<div id="mostrar_carbono14_hora" style="width: 80px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"></div>
														</td>
														<td style="width: 25%">
															<div id="mostrar_carbono14_actividad" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"></div>
														</td>
														<td style="text-align: ;font-size: 16px;width: 25%">(MBq)</td>
													</tr>
												</tbody>
											</table>
											<table align="center">
												<tr>
													<td style="width: 300px">
														<div style="text-align: center;margin-top: 10px;cursor: pointer; display: ;" id="div_boton_edicion_datos_carbono14" onclick="edicion_carbono14()">
															<img src="Images/icon_edit.png" style="width: 25px;vertical-align: middle">
															<span style="font-size: 15px">Editar Datos</span>
														</div>
													</td>
													<td style="width: 300px">
														<div style="text-align: center;margin-top: 10px;cursor: pointer; display: ;" id="div_boton_eliminar_datos_carbono14" onclick="eliminar_registro_por_input('regrad_carbono14','id_carbono14','id_datos_cargados_carbono14')">
															<img src="Images/icon_delete.png" style="width: 25px;vertical-align: middle">
															<span style="font-size: 15px">Eliminar Datos</span>
														</div>
													</td>
												</tr>
											</table>
											<input type="hidden" id="id_datos_cargados_carbono14" value="">
										</div>
									</div>
									<h3 id="dedo_frio_title">Dedo Frío 7312</h3>
									<div>
										<div style="text-align: center;margin-top: 10px;cursor: pointer;" id="div_boton_agregar_datos_dedo_frio" onclick="abrir_popup_carga_dedo_frio()">
											<img src="Images/add.svg" style="width: 25px;vertical-align: middle">
											<span style="font-size: 15px">Cargar datos Dedo Frío</span>
										</div>
										<div style="display: none;" id="datos_cargados_dedo_frio">
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
															<div id="mostrar_dedo_frio_hora" style="width: 80px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"></div>
														</td>
														<td>
															<div id="mostrar_dedo_frio_ppm" style="width: 80px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"></div>
														</td>
													</tr>
												</tbody>
											</table>
											<table align="center">
												<tr>
													<td style="width: 300px">
														<div style="text-align: center;margin-top: 10px;cursor: pointer; display: ;" id="div_boton_edicion_datos_dedo_frio" onclick="edicion_dedo_frio()">
															<img src="Images/icon_edit.png" style="width: 25px;vertical-align: middle">
															<span style="font-size: 15px">Editar Datos</span>
														</div>
													</td>
													<td style="width: 300px">
														<div style="text-align: center;margin-top: 10px;cursor: pointer; display: ;" id="div_boton_eliminar_datos_dedo_frio" onclick="eliminar_registro_por_input('regrad_dedo_frio','id_dedo_frio','id_datos_cargados_dedo_frio')">
															<img src="Images/icon_delete.png" style="width: 25px;vertical-align: middle">
															<span style="font-size: 15px">Eliminar Datos</span>
														</div>
													</td>
												</tr>
											</table>
											<input type="hidden" id="id_datos_cargados_dedo_frio" value="">
										</div>
									</div>
									<h3 id="control_precintos_title">Control de Precintos</h3>
									<div>
										<div style="text-align: center;margin-top: 10px;cursor: pointer; display: ;" id="div_boton_agregar_datos_control_precintos" onclick="abrir_popup_carga_control_precintos()">
											<img src="Images/add.svg" style="width: 25px;vertical-align: middle">
											<span style="font-size: 15px">Cargar datos Control de Precintos</span>
										</div>
										<div style="display:none;" id="datos_cargados_control_precintos">
											<table class="tabla_custom" style="width: 90%" align="center" border="0">
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
															<div id="mostrar_control_precintos_hora" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"></div>
														</th>
														<th style="width: 25%;padding-left: 3px;padding-right: 3px">
															<div id="mostrar_control_precintos_puerta_s_123b" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"></div>
														</th>
														<th style="width: 25%;padding-left: 3px;padding-right: 3px">
															<div id="mostrar_control_precintos_puerta_s_008" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"></div>
														</th>
														<th style="width: 25%;padding-left: 3px;padding-right: 3px">
															<div id="mostrar_control_precintos_puerta_s_144" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"></div>
														</th>
													</tr>
												</tbody>
											</table>
											<table align="center">
												<tr>
													<td style="width: 300px">
														<div style="text-align: center;margin-top: 10px;cursor: pointer; display: ;" id="div_boton_edicion_datos_control_precintos" onclick="edicion_control_precintos()">
															<img src="Images/icon_edit.png" style="width: 25px;vertical-align: middle">
															<span style="font-size: 15px">Editar Datos</span>
														</div>
													</td>
													<td style="width: 300px">
														<div style="text-align: center;margin-top: 10px;cursor: pointer; display: ;" id="div_boton_eliminar_datos_control_precintos" onclick="eliminar_registro_por_input('regrad_control_precintos','id_control_precintos','id_datos_cargados_control_precintos');$('#guardar_editar_control_precintos').val('guardar')">
															<img src="Images/icon_delete.png" style="width: 25px;vertical-align: middle">
															<span style="font-size: 15px">Eliminar Datos</span>
														</div>
													</td>
												</tr>
											</table>
											<input type="hidden" id="id_datos_cargados_control_precintos" value="">
										</div>
									</div>
									<h3 id="condicion_meteorologica_title">Condición Meteorológica</h3>
									<div>
										<div style="text-align: center;margin-top: 10px;cursor: pointer; display: ;" id="div_boton_agregar_datos_condicion_meteorologica" onclick="abrir_popup_carga_condicion_meteorologica()">
											<img src="Images/add.svg" style="width: 25px;vertical-align: middle">
											<span style="font-size: 15px">Cargar datos Condición Meteorológica</span>
										</div>
										<div style="display:none;" id="datos_cargados_condicion_meteorologica">
											<table class="tabla_custom" style="width: 90%" align="center" border="0">
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
															<div id="mostrar_condicion_meteorologica_hora" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"></div>
														</th>
														<th style="width: 25%;padding-left: 3px;padding-right: 3px">
															<div id="mostrar_condicion_meteorologica_velocidad_viento" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"></div>
														</th>
														<th style="width: 25%;padding-left: 3px;padding-right: 3px">
															<div id="mostrar_condicion_meteorologica_direccion_viento" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"></div>
														</th>
														<th style="width: 25%;padding-left: 3px;padding-right: 3px">
															<div id="mostrar_condicion_meteorologica_condicion_pasquill" style="width: 80px;height: 22px;margin: auto;width: 100%" class="ui-widget ui-state-default ui-corner-all"></div>
														</th>
													</tr>
												</tbody>
											</table>
											<table align="center">
												<tr>
													<td style="width: 300px">
														<div style="text-align: center;margin-top: 10px;cursor: pointer; display: ;" id="div_boton_edicion_datos_condicion_meteorologica" onclick="edicion_condicion_meteorologica()">
															<img src="Images/icon_edit.png" style="width: 25px;vertical-align: middle">
															<span style="font-size: 15px">Editar Datos</span>
														</div>
													</td>
													<td style="width: 300px">
														<div style="text-align: center;margin-top: 10px;cursor: pointer; display: ;" id="div_boton_eliminar_datos_condicion_meteorologica" onclick="eliminar_registro_por_input('regrad_cond_meteorologica','id_cond_meteorologica','id_datos_cargados_condicion_meteorologica')">
															<img src="Images/icon_delete.png" style="width: 25px;vertical-align: middle">
															<span style="font-size: 15px">Eliminar Datos</span>
														</div>
													</td>
												</tr>
											</table>
											<input type="hidden" id="id_datos_cargados_condicion_meteorologica" value="">
										</div>
									</div>
								</div>
							</div>
							<p></p>
						</div>
						<h3 id="concentrador7923_title">TRITIO CONCENTRADOR 7923 <span id="datos_concentrador_tritio_sin_cargar">-sin cargar-</span></h3>
						<div>

							<div>
								<table id="tabla_emisiones_7923" style="width: 100%;">
									<thead>
										<tr>
											<th rowspan="2">Fecha</th>
											<th rowspan="2">Hora</th>
											<th rowspan="2">Procedencia</th>
											<th rowspan="2">Volumen<br>(l)</th>
											<th colspan="3">Valores calculados</th>
											<th rowspan="2">Acciones</th>
										</tr>
										<tr style="text-align: center;font-size: 14px">
											<th style="border-top: none;">Actividad<br>(Bq)</th>
											<th style="border-top: none;">% LDD</th>
											<th style="border-top: none;border-right: none;">% RoDD</th>
										</tr>
									</thead>
									<tbody style="font-size: 14px; text-align: center;"></tbody>
								</table>
							</div>
							<div style="text-align: center; margin-top: 10px; cursor: pointer;" id="div_boton_agregar_datos_tritio_concentrador" onclick="abrir_popup_carga_datos_tritio_concentrador()">
								<img src="Images/add.svg" style="width: 25px; vertical-align: middle;">
								<span style="font-size: 15px">
									Cargar datos de Tritio Concentrador 7923
								</span>
							</div>


						</div>

						<h3>ESPECTROMETRÍA DEL AIRE DE ZONA CONTROLADA <span id="datos_espectrometria_aire_sin_cargar">-sin cargar-</span></h3>
						<div>
							<div style="display:;" id="datos_cargados_espectrometria_aire">
								<table id="tabla_espectrometria_aire">
									<thead>
										<tr>
											<th rowspan="3">Concentración de</th>
											<th rowspan="3">Recinto</th>
											<th rowspan="3">Hora</th>
											<th colspan="5" style="border-bottom: ;">Radionucleídos</th>
											<th rowspan="3">Acciones</th>
										</tr>
										<tr style="text-align: center;font-size: 14px">
											<th style="border-bottom: none;border-top: none;">Ar-41</th>
											<th style="border-bottom: none;border-top: none;">Xe-133</th>
											<th style="border-bottom: none;border-top: none;">Xe-135</th>
											<th style="border-bottom: none;border-top: none;border-right: none;">Kr-88</th>
											<th rowspan="2" style="border-bottom: ;border-top: none;border-right: none;">TOTAL</th>
										</tr>
										<tr style="text-align: center;font-size: 13px">
											<th style="border-top: none;">(DAC)</th>
											<th style="border-top: none;">(DAC)</th>
											<th style="border-top: none;">(DAC)</th>
											<th style="border-top: none;border-right: none;">(DAC)</th>
										</tr>
									</thead>
									<tbody style="font-size: 14px;text-align: center;"></tbody>
								</table>
							</div>
							<div style="text-align: center;margin-top: 10px;cursor: pointer; display: ;" id="div_boton_agregar_datos_espectrometria_aire" onclick="abrir_popup_carga_datos_espectrometria_aire()">
								<img src="Images/add.svg" style="width: 25px;vertical-align: middle">
								<span style="font-size: 15px">Cargar datos de Espectrometría</span>
							</div>
						</div>
						<h3>EMISIÓN DE EFLUENTES LÍQUIDOS <span id="datos_efluentes_liquidos_sin_cargar">-sin cargar-</span></h3>
						<div>
							<div id="datos_cargados_efluentes_liquidos">
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
											<th rowspan="2">Acciones</th>
										</tr>
										<tr style="text-align: center;font-size: 13px">
											<th style="border-top: none;">(m<sup>3</sup>)</th>
											<th style="border-top: none;">(TBq)</th>
											<th style="border-top: none;border-right: none;">(TBq)</th>
										</tr>
									</thead>
									<tbody style="font-size: 14px;text-align: center;"></tbody>
								</table>
							</div>
							<div style="text-align: center;margin-top: 10px;cursor: pointer; display: ;" id="div_boton_agregar_datos_efluentes_liquidos" onclick="abrir_popup_carga_datos_efluentes_liquidos()">
								<img src="Images/add.svg" style="width: 25px;vertical-align: middle">
								<span style="font-size: 15px">Cargar datos de Emisión de Efluentes Líquidos</span>
							</div>
						</div>
						<h3>MEDICIÓN DE CONT. SUPERFICIAL Y TASA DE DOSIS EQUIVALENTE <span id="datos_contaminacion_superficial_sin_cargar">-sin cargar-</span></h3>
						<div>
							<div style="display:;" id="datos_cargados_contaminacion_superficial">
								<table id="tabla_contaminacion_superficial">
									<thead>
										<tr>
											<th style="width: 23%" rowspan="3">Recinto</th>
											<th style="width: 23%" rowspan="3">Hora</th>
											<th colspan="2" style="border-bottom: none;width: 23%">Contaminación Superficial</th>
											<th style="width: 23%" rowspan="2">Tasa de Dosis Equivalente</th>
											<th style="width: 50px" rowspan="3">Acciones</th>
										</tr>
										<tr style="text-align: center;font-size: 14px">
											<th colspan="2" style="border-top: none;border-right: none">(Bq/cm<sup>2</sup>)</th>
										</tr>
										<tr style="text-align: center;font-size: 13px">
											<th style="border-bottom: ;border-top: none;width: 50%">β+γ</th>
											<th style="border-bottom: ;border-top: none;border-right: none;width: 50%">α</th>
											<th style="border-bottom: ;border-top: none;border-right: none;">(µSv/h)</th>
										</tr>
									</thead>
									<tbody style="font-size: 14px;text-align: center;"></tbody>
								</table>
							</div>
							<div style="text-align: center;margin-top: 10px;cursor: pointer; display: ;" id="div_boton_agregar_datos_espectrometria_aire" onclick="abrir_popup_carga_datos_contaminacion_superficial()">
								<img src="Images/add.svg" style="width: 25px;vertical-align: middle">
								<span style="font-size: 15px">Cargar datos de Contaminación Superficial</span>
							</div>
						</div>
						<h3>EFICIENCIA DRYERS <span id="datos_eficiencia_dryers_sin_cargar">-sin cargar-</span></h3>
						<div>
							<div style="display:;" id="datos_cargados_eficiencia_dryers">
								<table id="tabla_eficiencia_dryers">
									<thead>
										<tr>
											<th rowspan="2" style="width: calc(100% / 6)%">Fecha</th>
											<th rowspan="2" style="width: calc(100% / 6)%">Hora</th>
											<th rowspan="2" style="width: calc(100% / 6)%">Dryers</th>
											<th style="border-bottom: none;width: calc(100% / 6)%">Entrada</th>
											<th style="border-bottom: none;width: calc(100% / 6)%">Salida</th>
											<th style="border-bottom: none;width: calc(100% / 6)%">Eficiencia</th>
											<th rowspan="2" style="border-bottom: ;width: calc(100% / 6)%">Acciones</th>
										</tr>
										<tr style="text-align: center;font-size: 14px">
											<th style="border-top: none;">(DAC)</th>
											<th style="border-top: none;">(DAC)</th>
											<th style="border-top: none;border-right: none;">(%)</th>
										</tr>
									</thead>
									<tbody style="font-size: 14px;text-align: center;"></tbody>
								</table>
							</div>
							<div style="text-align: center;margin-top: 10px;cursor: pointer; display: ;" id="div_boton_agregar_datos_eficiencia_dryers" onclick="abrir_popup_carga_datos_eficiencia_dryers()">
								<img src="Images/add.svg" style="width: 25px;vertical-align: middle">
								<span style="font-size: 15px">Cargar datos de Eficiencia Dryers</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div style="text-align:right;top: 70px;text-shadow: none;position:absolute;right: 25px">
			<h4>Oficial: <?php echo strtoupper($row_planilla['of_apellido'] . ", " . $row_planilla['of_nombre']) ?><br>Turno: <?php echo $row_planilla['horarios'] ?></h4>
		</div>
		<!-- //////////////////////////////////////////////Popups HTML///////////////////////////////////////////////////////// -->
		<div class="popup" id="popup_carga_eficiencia_dryers">
			<table class="tabla_custom" align="center" border="0">
				<tr style="font-size: 22px;font-weight: bold;">
					<td colspan="2" style="padding-bottom: 20px;"><label for="cargar_datos_eficiencia_dryers_fecha">Fecha </label><input type="date" id="cargar_datos_eficiencia_dryers_fecha" style="font-size: 22px" class="ui-widget ui-state-default ui-corner-all"></td>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th style="font-size: 18px;text-align: left;padding-left: 55px">Hora</th>
					<td><input type="time" id="cargar_datos_eficiencia_dryers_hora" class="ui-widget ui-state-default ui-corner-all" style="width: "></td>
				</tr>
				<tr style="font-size: 18px;text-align: center;">
					<th style="padding-left: 55px;font-size: 18px;text-align: left;">Dryers</th>
					<td style="margin-left: 0px;font-size: 18px;text-align: left;">
						<select id="cargar_datos_eficiencia_dryers_dryer" class='select_dryers ui-widget ui-state-default ui-corner-all' style="">
						</select>
					</td>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th style="padding-left: 55px;width: 80px">Entrada</th>
					<td><input type="number" id="cargar_datos_eficiencia_dryers_entrada" class="ui-widget ui-state-default ui-corner-all" style="width: 80px"><span style="font-size: 13px"> DAC</span></td>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th style="padding-left: 55px;width: 80px">Salida</th>
					<td><input type="number" id="cargar_datos_eficiencia_dryers_salida" class="ui-widget ui-state-default ui-corner-all" style="width: 80px"><span style="font-size: 13px"> DAC</span></td>
			</table>
			<br>
			<input type="hidden" id="id_contaminacion_superficial" value="">
			<button style="height: 70px;width: 100%;font-size: 22px" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="guardar_valores_eficiencia_dryers()">Guardar Datos Eficiencia Dryers</button>
		</div>
		<div class="popup" id="popup_carga_contaminacion_superficial">
			<table class="tabla_custom" align="center">
				<tr style="font-size: 22px;font-weight: bold;">
					<td colspan="3" style="padding-bottom: 20px;"><label for="cargar_datos_contaminacion_superficial_fecha">Fecha </label><input type="date" id="cargar_datos_contaminacion_superficial_fecha" style="font-size: 22px" class="ui-widget ui-state-default ui-corner-all"></td>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th>Recinto</th>
					<td colspan="2">
						<select id="cargar_datos_contaminacion_superficial_recinto" class='select_recintos ui-widget ui-state-default ui-corner-all' style="">
						</select>
					</td>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th>Hora</th>
					<td><input type="time" id="cargar_datos_contaminacion_superficial_hora" class="ui-widget ui-state-default ui-corner-all" style="width: "></td>
				</tr>
				<tr style="font-size: 18px;text-align: center;">
					<th colspan="3" style="padding-top: 25px;padding-bottom: 10px">Contaminación Superficial</th>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th style="padding-left: 55px;width: 80px">β+γ</th>
					<td><input type="number" id="cargar_datos_contaminacion_superficial_beta_gamma" class="ui-widget ui-state-default ui-corner-all" style="width: 80px"></td>
					<td>
						<select id="cargar_datos_contaminacion_superficial_unidad_beta_gamma" class='select_unidad_concentracion_superficial_ingresada ui-widget ui-state-default ui-corner-all' style="">
						</select>
					</td>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th style="padding-left: 55px;width: 80px">α</th>
					<td><input type="number" id="cargar_datos_contaminacion_superficial_alfa" class="ui-widget ui-state-default ui-corner-all" style="width: 80px"></td>
					<td>
						<select id="cargar_datos_contaminacion_superficial_unidad_alfa" class='select_unidad_concentracion_superficial_ingresada ui-widget ui-state-default ui-corner-all' style="">
						</select>
					</td>
				<tr style="font-size: 18px;text-align: center;">
					<th colspan="3" style="padding-top: 25px;padding-bottom: 10px">Tasa de dosis Equivalente</th>
				</tr>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th style="padding-left: 55px;width: 80px">Tasa de Dosis</th>
					<td><input type="number" id="cargar_datos_contaminacion_superficial_dosis_equivalente" class="ui-widget ui-state-default ui-corner-all" style="width: 80px"></td>
					<td>
						<select id="cargar_datos_contaminacion_superficial_unidad_dosis_equivalente" class='select_unidad_tasa_dosis_ingresada ui-widget ui-state-default ui-corner-all' style="">
						</select>
					</td>
				</tr>
			</table>
			<br>
			<input type="hidden" id="id_contaminacion_superficial" value="">
			<button style="height: 70px;width: 100%;font-size: 22px" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="guardar_valores_contaminacion_superficial()">Guardar Datos Contaminación Superficial</button>
		</div>
		<div class="popup" id="popup_editar_observaciones">
			<textarea style="width: 95%;height: 240px;font-size: 14px" class="ui-corner-all" id="texto_campo_observaciones"></textarea>
			<button style="height: 70px;width: 95%;font-size: 22px" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="actualizar_observaciones()">Actualizar Observaciones</button>
		</div>
		<div class="popup" id="popup_libro_actas">
			<table style="font-size: 19px;width: 80%;text-align: center;" align="center" border="0">
				<tr>
					<th style="text-align: left;">Fecha: <span style="font-weight: normal;"><?php echo formafecha6($row_planilla['fecha']); ?></span></th>
					<th style="text-align: right;">Oficial de Turno: <span style="font-weight: normal;"><?php echo strtoupper($row_planilla['of_apellido'] . ", " . $row_planilla['of_nombre']) ?></span></th>
					<th style="text-align: right;">Turno: <span style="font-weight: normal;"><?php echo ($row_planilla['horarios']) ?></span></th>
				</tr>
			</table>
			<table style="font-size: 19px;width: 100%;text-align: center;margin-bottom: 10px" align="center" border="0">
				<tr>
					<td class="ui-widget-content ui-corner-all" style="width: 25%">Ventilación General: <br><span style="font-weight: bold"><b id="popup_libro_actas_campo_vent_gral"></b> GBq</span></td>
					<td class="ui-widget-content ui-corner-all" style="width: 25%">Ventilación Reactor: <br><span style="font-weight: bold"><b id="popup_libro_actas_campo_vent_reactor"></b> GBq</span></td>
					<td class="ui-widget-content ui-corner-all" style="width: 25%">Emisión C14: <br><span style="font-weight: bold"><b id="popup_libro_actas_campo_emision_c14"></b> MBq</span></td>
					<td class="ui-widget-content ui-corner-all" style="width: 25%">Dedo Frío 73120: <br><span style="font-weight: bold"><b id="popup_libro_actas_campo_dedo_frio"></b> ppm</span></td>
				</tr>
			</table>
			<!-- Lo que ya tenés: el textarea histórico -->
			<textarea style="width: calc(100% - 7px);height: 150px;font-size: 16px" class="ui-corner-all" id="texto_campo_libro_actas" placeholder="Observaciones generales o históricas..."></textarea>

			<!-- NUEVO: Contenedor donde se van a ir dibujando las tareas individuales -->
			<div id="contenedor_tareas_diarias" style="width: 100%; min-height: 250px; max-height: 400px; overflow-y: auto; background-color: #f5f5f5; margin-top: 10px; border-radius: 5px; padding: 10px; text-align: left;">
				<!-- Acá el JavaScript va a inyectar las tareas -->
			</div>

			<!-- NUEVO: Botón para abrir la carga de la tarea -->
			<button style="height: 50px;width: 100%;font-size: 18px; margin-top: 10px; background-color: #3173a5; color: white;" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="abrir_popup_carga_tarea()">
				+ AGREGAR REGISTRO DE TAREA / EVENTO
			</button>

			<!-- Lo que ya tenés: El botón viejo de actualizar -->
			<button style="height: 70px;width: calc(100%);font-size: 22px; margin-top: 10px;" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="actualizar_libro_actas()">Actualizar Libro de Actas</button>
		</div>
		<div class="popup" id="popup_carga_tarea_diaria">
			<h3 style="margin-top: 0px;">Nueva Tarea / Evento</h3>

			<?php
			// Consultamos la vista maestra trayendo a todos los habilitados
			$q_personal_extra = mysql_query("SELECT id, apellido, nombre, id_rol FROM vista_personal_radioproteccion WHERE habilitada_si_no = 1 ORDER BY id_rol ASC, apellido ASC", $miconex);

			$options_personal = '<option value="">- Ninguno (Opcional) -</option>';
			while ($per = mysql_fetch_assoc($q_personal_extra)) {
				// Etiqueta visual para distinguir permanentes y refuerzos
				$tipo = ($per['id_rol'] == 1) ? '(P)' : (($per['id_rol'] == 2) ? '(Parada)' : '(Ayudante)');
				$options_personal .= '<option value="' . $per['id'] . '">' . $per['apellido'] . ', ' . $per['nombre'] . ' ' . $tipo . '</option>';
			}
			?>

			<table class="tabla_custom" style="width: 100%; margin-top: 10px; border-collapse: separate; border-spacing: 5px 10px;" align="center" border="0">
				<tr>
					<td colspan="4">
						<label style="font-weight: bold; font-size: 16px;">Descripción del evento:</label><br>
						<textarea id="tarea_texto" class="ui-corner-all ui-widget-content" style="width: 100%; height: 80px; font-size: 16px; resize: none; padding: 5px; box-sizing: border-box;">Sin observaciones</textarea>
						<input type="hidden" id="tarea_id_editar" value="">
					</td>
				</tr>
				<tr style="font-size: 16px; text-align: left;">
					<th style="width: 1%; white-space: nowrap;">Oficial:</th>
					<td style="width: 49%;">
						<input type="text" id="tarea_oficial_buscador" class="ui-widget-content ui-corner-all" style="width: 100%; height: 30px; padding: 0 5px; box-sizing: border-box; text-transform: uppercase;" placeholder="Buscar oficial...">
						<input type="hidden" id="tarea_oficial_asociado" value="">
					</td>
					<th style="width: 1%; white-space: nowrap; padding-left: 20px;">Asistió:</th>
					<td style="width: 49%;">
						<input type="text" id="tarea_ayudante_buscador" class="ui-widget-content ui-corner-all" style="width: 100%; height: 30px; padding: 0 5px; box-sizing: border-box; text-transform: uppercase;" placeholder="Buscar ayudante...">
						<input type="hidden" id="tarea_ayudante" value="">
					</td>
				</tr>
				<tr style="font-size: 16px; text-align: left;">
					<th style="white-space: nowrap;">Recinto/Sala:</th>
					<td>
						<input type="text" id="tarea_recinto_buscador" class="ui-widget-content ui-corner-all" style="width: 100%; height: 30px; padding: 0 5px; box-sizing: border-box;" placeholder="Buscar recinto...">
						<input type="hidden" id="tarea_recinto" value="-">
					</td>
					<th style="white-space: nowrap; padding-left: 20px;">PTR utilizado:</th>
					<td>
						<input type="text" id="tarea_ptr_buscador" class="ui-widget-content ui-corner-all" style="width: 100%; height: 30px; padding: 0 5px; box-sizing: border-box;" placeholder="Buscar N° o palabra...">
						<input type="hidden" id="tarea_ptr" value="">
					</td>
				</tr>
				<tr style="font-size: 16px; text-align: left;">
					<th style="white-space: nowrap;">Tasa de Dosis:</th>
					<td>
						<input type="number" id="tarea_dosis" class="ui-widget-content ui-corner-all" style="width: 100px; height: 30px; padding: 0 5px; box-sizing: border-box;">
						<span style="font-size: 14px;">µSv/h</span>
					</td>
					<th style="white-space: nowrap; padding-left: 20px;">Tritio:</th>
					<td>
						<input type="number" id="tarea_tritio" class="ui-widget-content ui-corner-all" style="width: 100px; height: 30px; padding: 0 5px; box-sizing: border-box;">
						<span style="font-size: 14px;">DAC</span>
					</td>
				</tr>
				<tr style="font-size: 16px; text-align: left;">
					<th colspan="4" style="padding-top: 15px;">Contaminación Superficial (Bq/cm²)</th>
				</tr>
				<tr style="font-size: 16px; text-align: left;">
					<th style="white-space: nowrap;">β+γ:</th>
					<td><input type="number" id="tarea_cont_beta" class="ui-widget-content ui-corner-all" style="width: 100px; height: 30px; padding: 0 5px; box-sizing: border-box;"></td>
					<th style="white-space: nowrap; padding-left: 20px;">α:</th>
					<td><input type="number" id="tarea_cont_alfa" class="ui-widget-content ui-corner-all" style="width: 100px; height: 30px; padding: 0 5px; box-sizing: border-box;"></td>
				</tr>
				<tr style="font-size: 16px; text-align: left;">
					<th colspan="2" style="padding-top: 15px;">
						<input type="checkbox" id="tarea_derrame" value="1" style="transform: scale(1.5); margin-right: 10px; cursor: pointer;">
						<label for="tarea_derrame" style="cursor: pointer;">¿Hubo derrame de agua?</label>
					</th>
					<th colspan="2" style="padding-top: 15px; padding-left: 20px;">
						<input type="checkbox" id="tarea_aerosoles" value="1" style="transform: scale(1.5); margin-right: 10px; cursor: pointer;" onchange="toggle_aerosoles_dac()">
						<label for="tarea_aerosoles" style="cursor: pointer;">¿Dispersión de aerosoles?</label>
					</th>
				</tr>
				<tr id="fila_aerosoles_dac" style="font-size: 16px; text-align: left; display: none;">
					<td colspan="2"></td>
					<th style="color: red; padding-top: 10px; white-space: nowrap; padding-left: 20px;">Valor Aerosoles:</th>
					<td style="padding-top: 10px;">
						<input type="number" id="tarea_aerosoles_dac" class="ui-widget-content ui-corner-all" style="width: 100px; height: 30px; padding: 0 5px; border-color: red; box-sizing: border-box;">
						<span style="font-size: 14px;">DAC</span>
					</td>
				</tr>
			</table>
			<!-- NUEVO CONTENEDOR PARA ANCLAR AL FONDO -->
			<div style="position: absolute; bottom: 25px; left: 0; width: 100%; text-align: center;">
				<button style="height: 70px;width: 90%;font-size: 22px; background-color: #4CAF50; color: white;" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="guardar_nueva_tarea()">
					CONFIRMAR Y GUARDAR TAREA
				</button><br>
				<button style="height: 50px;width: 90%;font-size: 18px; margin-top: 10px;" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="$('#popup_carga_tarea_diaria').dialog('close')">
					Cancelar
				</button>
			</div>
		</div>
		<div class="popup" id="popup_carga_radionucleidos_efluentes_liquidos_agregar">
			<table style="font-size: 18px;text-align: center;" align="center" id="" class="tabla_custom">
				<thead>
					<tr>
						<th>Radionucleído</th>
						<th colspan="2">Concentración</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<select id="agregar_datos_radionucleidos_select" class="select_radionucleidos ui-widget ui-state-default ui-corner-all">
							</select>
						</td>
						<td><input type="number" class="ui-widget ui-state-default ui-corner-all" id="cargar_datos_concentracion_radionucleidos_efluentes_liquidos"></td>
						<td>
							<select style="height: 28px" id="cargar_datos_unidad_concentracion_radionucleidos_efluentes_liquidos" class="select_unidad_concentracion_ingresada ui-widget ui-state-default ui-corner-all">
							</select>
						</td>
					</tr>
				</tbody>
			</table>
			<button style="position: absolute;margin-right: 5px;margin-left: 5px;margin-bottom: 10px;left: 5px;bottom: 0px;height: 70px;width: calc(100% - 20px) ;font-size: 22px;" id="boton_guardar_datos_emision_efluentes_liquidos" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="guardar_valores_radionucleidos_efluentes_liquidos()">Agregar Radionucleido</button>
		</div>
		<div class="popup" id="popup_carga_observaciones_efluentes">
			<table class="tabla_custom" align="center" style="font-size: 17px;text-align: left;margin-bottom: 10px">
				<tr>
					<td>Fecha</td>
					<td>
						<div style="text-align: center;width: 100px; font-size: 17px" class="ui-widget ui-state-default ui-corner-all" id="mostrar_efluentes_liquidos_fecha">ewrh</div>
					</td>
				</tr>
				<tr>
					<td>Hora</td>
					<td>
						<div style="text-align: center;width: 100px; font-size: 17px" class="ui-widget ui-state-default ui-corner-all" id="mostrar_efluentes_liquidos_hora">ewrhtj</div>
					</td>
				</tr>
				<tr>
					<td>Tanque</td>
					<td>
						<div style="text-align: center;width: 100px; font-size: 17px" class="ui-widget ui-state-default ui-corner-all" id="mostrar_efluentes_liquidos_tanque">erjr</div>
					</td>
				</tr>
				<tr>
					<td>Volumen (m<sup>3</sup>)</td>
					<td>
						<div style="text-align: center;width: 100px; font-size: 17px" class="ui-widget ui-state-default ui-corner-all" id="mostrar_efluentes_liquidos_volumen">erwert</div>
					</td>
				</tr>
			</table>
			<label style="font-size: 18px;font-weight: bold;" for="cargar_datos_efluentes_liquidos_observaciones">Observaciones:</label>
			<textarea style="height: 100px;width: 400px; font-size: 12px" class="ui-corner-all" id="cargar_datos_efluentes_liquidos_observaciones"></textarea>
			<input type="hidden" id="var_id_efluentes_para_carga_observaciones_efluentes" value="">
			<button style="position: absolute;margin-right: 5px;margin-left: 5px;margin-bottom: 10px;left: 5px;bottom: 0px;height: 70px;width: calc(100% - 20px) ;font-size: 22px;" id="boton_guardar_datos_emision_efluentes_liquidos" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="actualizar_observaciones_efluentes()">Actualizar Observaciones</button>
		</div>
		<div class="popup" id="popup_carga_radionucleidos_efluentes_liquidos">
			<table style="font-size: 18px;text-align: center;" align="center" id="tabla_radionucleidos_efluentes_liquidos" class="tabla_custom">
				<thead>
					<tr>
						<th>Radionucleído</th>
						<th colspan="2">Concentración (Bq/l)</th>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
			<input type="hidden" id="var_id_efluentes_para_carga_radionucleidos" value="">
			<div style="text-align: center;margin-top: 10px;cursor: pointer; display: ;" id="div_boton_agregar_datos_espectrometria_aire" onclick="abrir_popup_carga_radionucleidos_efluentes_liquidos_agregar()">
				<img src="Images/add.svg" style="width: 25px;vertical-align: middle">
				<span style="font-size: 15px">Cargar datos radionucleídos</span>
			</div>
			<button style="position: absolute;margin-right: 5px;margin-left: 5px;margin-bottom: 10px;left: 5px;bottom: 0px;height: 70px;width: calc(100% - 20px) ;font-size: 22px;" id="boton_guardar_datos_emision_efluentes_liquidos" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="actualizar_valores_radionucleidos_efluentes_liquidos()">Actualizar Radionucleidos</button>
		</div>
		<div class="popup" id="popup_carga_efluentes_liquidos">
			<table style="font-size: 18px;text-align: left;" align="center">
				<tr style="font-size: 22px;font-weight: bold;">
					<td><label for="cargar_datos_efluentes_liquidos_fecha">Fecha </label></td>
					<td style="padding-bottom: 20px;"><input type="date" id="cargar_datos_efluentes_liquidos_fecha" style="font-size: 22px" class="ui-widget ui-state-default ui-corner-all"></td>
				</tr>
				<tr style="font-size: 22px;font-weight: bold;">
					<td colspan="2" style="padding-bottom: 20px;"><label for="cargar_datos_efluentes_liquidos_hora">Hora </label><input type="time" id="cargar_datos_efluentes_liquidos_hora" style="font-size: 22px" class="ui-widget ui-state-default ui-corner-all"></td>
				</tr>
			</table>
			<table class="tabla_custom" style="font-size: 18px;text-align: left;" align="center">
				<tr>
					<th style="width: 100px">7921 TK</th>
					<td style="width: 100px"><input class="ui-widget ui-state-default ui-corner-all" type="text" id="cargar_datos_efluentes_liquidos_tanque" style="width: 100px"></td>
				</tr>
				<tr>
					<th style="width: 100px">Volumen</th>
					<td style="width: 100px">
						<input class="ui-widget ui-state-default ui-corner-all" type="number" id="cargar_datos_efluentes_liquidos_volumen" style="width: 100px">
					</td>
					<td style="width: 100px">
						<select style="height: 28px" id="cargar_datos_efluentes_liquidos_unidad_volumen_ingresada" class="select_unidad_volumen_ingresada ui-widget ui-state-default ui-corner-all">
						</select>
					</td>
				</tr>
			</table>
			<br>
			<input type="hidden" id="id_efluentes_liquidos" value="">
			<button style="height: 70px;width: 100%;font-size: 22px;" id="boton_guardar_datos_emision_efluentes_liquidos" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="guardar_valores_efluentes_liquidos()">Guardar Datos Emisión Efluentes Líquidos</button>
		</div>
		<div class="popup" id="popup_carga_espectrometria_aire">
			<table class="tabla_custom" align="center">
				<tr style="font-size: 22px;font-weight: bold;">
					<td colspan="2" style="padding-bottom: 20px;"><label for="cargar_datos_espectrometria_aire_fecha">Fecha </label><input type="date" id="cargar_datos_espectrometria_aire_fecha" style="font-size: 22px" class="ui-widget ui-state-default ui-corner-all"></td>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th>Concentración de</th>
					<td><input type="text" id="cargar_datos_espectrometria_aire_tipo" class="ui-widget ui-state-default ui-corner-all" style="width: 80px"></td>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th>Hora</th>
					<td><input type="time" id="cargar_datos_espectrometria_aire_hora" class="ui-widget ui-state-default ui-corner-all" style="width: "></td>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th>Recinto</th>
					<td>
						<select id="cargar_datos_espectrometria_aire_recinto" class='ui-widget ui-state-default ui-corner-all' style="">
							<option value="-">-</option>
							<?php mysql_data_seek($recintos, 0);
							do {
								if ($row_recintos['id'] <> "") { ?>
									<option value="<?php echo $row_recintos['id'] ?>"><?php echo $row_recintos['recinto'] ?></option>
							<?php };
							} while ($row_recintos = mysql_fetch_assoc($recintos)) ?>
						</select>
					</td>
				</tr>
				<tr style="font-size: 18px;text-align: center;">
					<th colspan="2" style="padding-top: 25px;padding-bottom: 10px">Radionucleídos</th>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th style="padding-left: 55px">Ar-41</th>
					<td><input type="number" id="cargar_datos_espectrometria_aire_ar41" class="ui-widget ui-state-default ui-corner-all" style="width: 80px"><span style="font-size: 13px"> DAC</span></td>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th style="padding-left: 55px">Xe-133</th>
					<td><input type="number" id="cargar_datos_espectrometria_aire_xe133" class="ui-widget ui-state-default ui-corner-all" style="width: 80px"><span style="font-size: 13px"> DAC</span></td>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th style="padding-left: 55px">Xe-135</th>
					<td><input type="number" id="cargar_datos_espectrometria_aire_xe135" class="ui-widget ui-state-default ui-corner-all" style="width: 80px"><span style="font-size: 13px"> DAC</span></td>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th style="padding-left: 55px">Kr-88</th>
					<td><input type="number" id="cargar_datos_espectrometria_aire_kr88" class="ui-widget ui-state-default ui-corner-all" style="width: 80px"><span style="font-size: 13px"> DAC</span></td>
				</tr>
			</table>
			<br>
			<input type="hidden" id="id_espectrometria_aire" value="">
			<button style="height: 70px;width: 100%;font-size: 22px" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="guardar_valores_espectrometria_aire()">Guardar Datos Espectrometría Aire de Zona Controlada</button>
		</div>
		<div class="popup" id="popup_carga_espectrometria_aire_editar">
			<table class="tabla_custom" align="center">
				<tr style="font-size: 22px;font-weight: bold;">
					<td colspan="2" style="padding-bottom: 20px;"><label for="cargar_datos_espectrometria_aire_editar_fecha">Fecha </label><input type="date" id="cargar_datos_espectrometria_aire_editar_fecha" style="font-size: 22px" class="ui-widget ui-state-default ui-corner-all"></td>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th>Concentración de</th>
					<td><input type="text" id="cargar_datos_espectrometria_aire_editar_tipo" class="ui-widget ui-state-default ui-corner-all" style="width: 80px"></td>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th>Hora</th>
					<td><input type="time" id="cargar_datos_espectrometria_aire_editar_hora" class="ui-widget ui-state-default ui-corner-all" style="width: "></td>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th>Recinto</th>
					<td>
						<select id="cargar_datos_espectrometria_aire_editar_recinto" class='ui-widget ui-state-default ui-corner-all' style="">
							<option value="-">-</option>
							<?php mysql_data_seek($recintos, 0);
							do {
								if ($row_recintos['id'] <> "") { ?>
									<option value="<?php echo $row_recintos['id'] ?>"><?php echo $row_recintos['recinto'] ?></option>
							<?php };
							} while ($row_recintos = mysql_fetch_assoc($recintos)) ?>
						</select>
					</td>
				</tr>
				<tr style="font-size: 18px;text-align: center;">
					<th colspan="2" style="padding-top: 25px;padding-bottom: 10px">Radionucleídos</th>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th style="padding-left: 55px">Ar-41</th>
					<td><input type="number" id="cargar_datos_espectrometria_aire_editar_ar41" class="ui-widget ui-state-default ui-corner-all" style="width: 80px"><span style="font-size: 13px"> DAC</span></td>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th style="padding-left: 55px">Xe-133</th>
					<td><input type="number" id="cargar_datos_espectrometria_aire_editar_xe133" class="ui-widget ui-state-default ui-corner-all" style="width: 80px"><span style="font-size: 13px"> DAC</span></td>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th style="padding-left: 55px">Xe-135</th>
					<td><input type="number" id="cargar_datos_espectrometria_aire_editar_xe135" class="ui-widget ui-state-default ui-corner-all" style="width: 80px"><span style="font-size: 13px"> DAC</span></td>
				</tr>
				<tr style="font-size: 18px;text-align: left;">
					<th style="padding-left: 55px">Kr-88</th>
					<td><input type="number" id="cargar_datos_espectrometria_aire_editar_kr88" class="ui-widget ui-state-default ui-corner-all" style="width: 80px"><span style="font-size: 13px"> DAC</span></td>
				</tr>
			</table>
			<br>
			<input type="hidden" id="id_espectrometria_aire" value="">
			<button style="height: 70px;width: 100%;font-size: 22px" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="actualizar_valores_espectrometria_aire_edicion()">Guardar Datos Espectrometría Aire de Zona Controlada</button>
		</div>
		<div class="popup" id="popup_carga_condicion_meteorologica">
			<table class="tabla_custom" style="width: 80%" align="center" border="0">
				<thead>
					<tr style="font-size: 22px;font-weight: bold;">
						<td colspan="2" style="padding-bottom: 20px;"><label for="cargar_datos_condicion_meteorologica_fecha">Fecha </label><input type="date" id="cargar_datos_condicion_meteorologica_fecha" style="font-size: 22px" class="ui-widget ui-state-default ui-corner-all"></td>
					</tr>
					<tr style="font-size: 18px">
						<th style="width: 25%;text-align: right;">Hora</th>
						<th style="width: 25%;text-align: left;"><input type="text" id="cargar_datos_condicion_meteorologica_hora" style="width: 80px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"></th>
					</tr>
					<tr style="font-size: 18px">
						<th style="width: 25%;text-align: right;">Velocidad del Viento</th>
						<th style="width: 25%;text-align: left;"><input type="text" id="cargar_datos_condicion_meteorologica_velocidad_viento" style="width: 80px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"><span style="font-size: 14px;font-weight: normal;"> (m/seg)</span></th>
					</tr>
				</thead>
				<tbody>
					<tr style="font-size: 18px">
						<th style="width: 25%;text-align: right;">Dirección del Viento</th>
						<th style="width: 25%;text-align: left;">
							<select id="cargar_datos_condicion_meteorologica_direccion_viento" style="width: 200px;height: 26px;margin: auto;font-size: 18PX" class="ui-widget ui-state-default ui-corner-all">
								<optgroup label="Norte">
									<option value="N">N - Norte</option>
									<option value="NNE">NNE (Nornoreste)</option>
									<option value="NE">NE (Noreste)</option>
									<option value="ENE">ENE (Estenoreste)</option>
								</optgroup>
								<optgroup label="Este">
									<option value="E">E - Este</option>
									<option value="ESE">ESE (Estesudeste)</option>
									<option value="SE">SE (Sudeste)</option>
									<option value="SSE">SSE (Sursudeste)</option>
								</optgroup>
								<optgroup label="Sur">
									<option value="S">S - Sur</option>
									<option value="SSO">SSO (Sursudoeste)</option>
									<option value="SO">SO (Sudoeste)</option>
									<option value="OSO">OSO (Oestesudoeste)</option>
								</optgroup>
								<optgroup label="Oeste">
									<option value="O">O - Oeste</option>
									<option value="ONO">ONO (Oestenoroeste)</option>
									<option value="NO">NO (Noroeste)</option>
									<option value="NNO">NNO (Nornoroeste)</option>
								</optgroup>
							</select>
						</th>
					</tr>
					<tr style="font-size: 18px">
						<th style="width: 25%;text-align: right;">Condición Pasquill</th>
						<th style="width: 25%;text-align: left;"><input type="text" id="cargar_datos_condicion_meteorologica_condicion_pasquill" style="width: 80px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"></th>
					</tr>
				</tbody>
			</table>
			<br>
			<input type="hidden" id="guardar_editar_condicion_meteorologica" value="guardar">
			<button style="height: 70px;width: 100%;font-size: 22px" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="guardar_valores_condicion_meteorologica()">Guardar Datos Condición Meteorológica</button>
		</div>
		<div class="popup" id="popup_carga_control_precintos">
			<table class="tabla_custom" style="width: 80%" align="center" border="0">
				<thead>
					<tr style="font-size: 22px;font-weight: bold;">
						<td colspan="2" style="padding-bottom: 20px;"><label for="cargar_datos_control_precintos_fecha">Fecha </label><input type="date" id="cargar_datos_control_precintos_fecha" style="font-size: 22px" class="ui-widget ui-state-default ui-corner-all"></td>
					</tr>
					<tr style="font-size: 18px">
						<th style="width: 25%;text-align: right;">Hora</th>
						<th style="width: 25%;text-align: left;"><input type="text" id="cargar_datos_control_precintos_hora" style="width: 80px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"></th>
					</tr>
					<tr style="font-size: 18px">
						<th style="width: 25%;text-align: right;">Puerta S-123B</th>
						<th style="width: 25%;text-align: left;"><input type="text" id="cargar_datos_control_precintos_puerta_s_123b" style="width: 80px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"></th>
					</tr>
				</thead>
				<tbody>
					<tr style="font-size: 18px">
						<th style="width: 25%;text-align: right;">Puerta S-008</th>
						<th style="width: 25%;text-align: left;"><input type="text" id="cargar_datos_control_precintos_puerta_s_008" style="width: 80px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"></th>
					</tr>
					<tr style="font-size: 18px">
						<th style="width: 25%;text-align: right;">Puerta S-144</th>
						<th style="width: 25%;text-align: left;"><input type="text" id="cargar_datos_control_precintos_puerta_s_144" style="width: 80px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"></th>
					</tr>
				</tbody>
			</table>
			<br>
			<input type="hidden" id="guardar_editar_control_precintos" value="guardar">
			<button style="height: 70px;width: 100%;font-size: 22px" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="guardar_valores_control_precintos()">Guardar Datos Control de Precintos</button>
		</div>
		<div class="popup" id="popup_carga_concentrador7923">
			<table class="tabla_custom" style="width: 80%" align="center" border="0">
				<thead>
					<tr style="font-size: 22px;font-weight: bold;">
						<td colspan="3" style="padding-bottom: 20px;"><label for="cargar_datos_concentrador7923_fecha">Fecha </label><input type="date" id="cargar_datos_concentrador7923_fecha" style="font-size: 22px" class="ui-widget ui-state-default ui-corner-all"></td>
					</tr>
					<tr style="font-size: 18px">
						<th style="width: 25%;text-align: right;">Procedencia</th>
						<th style="width: 5%;text-align: left;"><input type="text" id="cargar_datos_concentrador7923_procedencia" style="width: 120px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"></th>
					</tr>
					<tr style="font-size: 18px">
						<th style="width: 25%;text-align: right;">Concentración </th>
						<th style="width: 5%;text-align: left;"><input type="number" id="cargar_datos_concentrador7923_actividad" style="width: 120px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"></th>
						<th style="width: 25%;text-align: left;">
							<select style="height: 28px" id="cargar_datos_concentrador7923_actividad_unidad_ingresada" class="select_unidad_actividad_ingresada ui-widget ui-state-default ui-corner-all">
							</select>
						</th>
					</tr>
				</thead>
				<tbody>
					<tr style="font-size: 18px">
						<th style="width: 25%;text-align: right;">Volumen </th>
						<th style="width: 5%;text-align: left;"><input type="number" id="cargar_datos_concentrador7923_volumen" style="width: 120px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"></th>
						<th style="width: 25%;text-align: left;">
							<select style="height: 28px" id="cargar_datos_concentrador7923_volumen_unidad_ingresada" class="select_unidad_volumen_ingresada ui-widget ui-state-default ui-corner-all">
							</select>
						</th>
					</tr>
					<tr style="font-size: 18px">
						<th style="width: 25%;text-align: right;">RoDD </th>
						<th style="width: 5%;text-align: left;"><input type="number" id="cargar_datos_concentrador7923_ldd" style="width: 120px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"></th>
						<th style="width: 25%;text-align: left;">%</th>
					</tr>
				</tbody>
			</table>
			<br>
			<input type="hidden" id="guardar_editar_concentrador7923" value="guardar">
			<button style="height: 70px;width: 100%;font-size: 22px" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="guardar_valores_concentrador7923()">Guardar Datos Tritio Concentrador 7923</button>
		</div>
		<div class="popup" id="popup_carga_tritio_concentrador_7923">
			<table class="tabla_custom" style="width: 80%;" align="center" border="0">
				<tr style="font-size: 22px; font-weight: bold;">
					<td colspan="2" style="padding-bottom: 20px;">
						<label for="emision_7923_fecha">Fecha</label>
						<input type="date" id="emision_7923_fecha" class="ui-widget ui-state-default ui-corner-all" style="font-size: 22px;">
					</td>
					<td colspan="2" style="padding-bottom: 20px;">
						<label for="emision_7923_hora">Hora</label>
						<input type="time" id="emision_7923_hora" class="ui-widget ui-state-default ui-corner-all" style="font-size: 22px;">
					</td>
				</tr>
				<tr style="font-size: 18px;">
					<th style="width: 25%; text-align: right;">Procedencia</th>
					<td style="width: 25%; text-align: left;">
						<input type="text" id="emision_7923_procedencia" class="ui-widget ui-state-default ui-corner-all" style="width: 120px; height: 22px;">
					</td>
				</tr>
				<tr style="font-size: 18px;">
					<th style="width: 25%; text-align: right;">Volumen</th>
					<td style="width: 25%; text-align: left;">
						<input type="number" id="emision_7923_volumen" class="ui-widget ui-state-default ui-corner-all" style="width: 120px; height: 22px;">
					</td>
					<td style="width: 25%; text-align: left;">
						<select id="emision_7923_unidad_volumen" class="select_unidad_volumen_ingresada ui-widget ui-state-default ui-corner-all" style="height: 28px;">
							<!-- opciones de unidad -->
						</select>
					</td>
				</tr>
			</table>

			<input type="hidden" id="guardar_editar_emision_7923" value="guardar">

			<button class="ui-button ui-widget ui-state-default ui-corner-all" style="height: 70px; width: 100%; font-size: 22px; margin-top:35px" onclick="guardar_emision_7923_prueba()">
				Guardar Emisión Tritio Concentrador 7923
			</button>
		</div>

		<div class="popup" id="popup_carga_concentrador7923_VIEJOOOOOOO">
			<table class="tabla_custom" style="width: 80%" align="center" border="0">
				<thead>
					<tr style="font-size: 22px;font-weight: bold;">
						<td colspan="3" style="padding-bottom: 20px;"><label for="cargar_datos_concentrador7923_VIEJOOOOOOO_fecha">Fecha </label><input type="date" id="cargar_datos_concentrador7923_VIEJOOOOOOO_fecha" style="font-size: 22px" class="ui-widget ui-state-default ui-corner-all"></td>
					</tr>
					<tr style="font-size: 18px">
						<th style="width: 25%;text-align: right;">Procedencia</th>
						<th style="width: 5%;text-align: left;"><input type="text" id="cargar_datos_concentrador7923_VIEJOOOOOOO_procedencia" style="width: 120px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"></th>
					</tr>
					<tr style="font-size: 18px">
						<th style="width: 25%;text-align: right;">Actividad </th>
						<th style="width: 5%;text-align: left;"><input type="number" id="cargar_datos_concentrador7923_VIEJOOOOOOO_actividad" style="width: 120px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"></th>
						<th style="width: 25%;text-align: left;">
							<select style="height: 28px" id="cargar_datos_concentrador7923_VIEJOOOOOOO_actividad_unidad_ingresada" class="select_unidad_actividad_ingresada ui-widget ui-state-default ui-corner-all">
							</select>
						</th>
					</tr>
				</thead>
				<tbody>
					<tr style="font-size: 18px">
						<th style="width: 25%;text-align: right;">Volumen </th>
						<th style="width: 5%;text-align: left;"><input type="number" id="cargar_datos_concentrador7923_VIEJOOOOOOO_volumen" style="width: 120px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"></th>
						<th style="width: 25%;text-align: left;">
							<select style="height: 28px" id="cargar_datos_concentrador7923_VIEJOOOOOOO_volumen_unidad_ingresada" class="select_unidad_volumen_ingresada ui-widget ui-state-default ui-corner-all">
							</select>
						</th>
					</tr>
					<tr style="font-size: 18px">
						<th style="width: 25%;text-align: right;">RoDD </th>
						<th style="width: 5%;text-align: left;"><input type="number" id="cargar_datos_concentrador7923_VIEJOOOOOOO_ldd" style="width: 120px;height: 22px;margin: auto;" class="ui-widget ui-state-default ui-corner-all"></th>
						<th style="width: 25%;text-align: left;">%</th>
					</tr>
				</tbody>
			</table>
			<br>
			<input type="hidden" id="guardar_editar_concentrador7923_VIEJOOOOOOO" value="guardar">
			<button style="height: 70px;width: 100%;font-size: 22px" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="guardar_valores_concentrador7923_VIEJOOOOOOO()">Guardar Datos Tritio Concentrador 7923</button>
		</div>
		<div class="popup" id="popup_carga_dedo_frio">
			<table class="tabla_custom" style="width: 80%" align="center" border="0">
				<thead>
					<tr style="font-size: 22px;font-weight: bold;">
						<td colspan="6" style="padding-bottom: 20px;"><label for="cargar_datos_dedo_frio_fecha">Fecha </label><input type="date" id="cargar_datos_dedo_frio_fecha" style="font-size: 22px" class="ui-widget ui-state-default ui-corner-all"></td>
					</tr>
					<tr>
						<th style="font-size: 22px">Hora</th>
						<th style="font-size: 22px">ppm</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td style="vertical-align: top;"><input type="time" id="cargar_datos_dedo_frio_hora" style="font-size: 20px" class="ui-widget ui-state-default ui-corner-all"></td>
						<td>
							<input type="number" id="cargar_datos_dedo_frio_ppm" style="font-size: 20px;text-align: center;width: 100px;" class="ui-widget ui-state-default ui-corner-all"><br><input type="checkbox" id="cargar_datos_dedo_frio_menor_que" style="margin-bottom: -10px" value="0">
							<label for="cargar_datos_dedo_frio_menor_que" style="font-size: 13px;color: gray">(menor que)</label>
						</td>
					</tr>
				</tbody>
			</table>
			<br>
			<input type="hidden" id="guardar_editar_dedo_frio" value="guardar">
			<button style="height: 70px;width: 100%;font-size: 22px" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="guardar_valores_dedo_frio()">Guardar Datos Dedo Frío</button>
		</div>
		<div class="popup" id="popup_carga_carbono14">
			<table class="tabla_custom" style="width: 80%" align="center" border="0">
				<thead>
					<tr style="font-size: 22px;font-weight: bold;">
						<td colspan="6" style="padding-bottom: 20px;"><label for="cargar_datos_carbono14_fecha">Fecha </label><input type="date" id="cargar_datos_carbono14_fecha" style="font-size: 22px" class="ui-widget ui-state-default ui-corner-all"></td>
					</tr>
					<tr>
						<th style="font-size: 22px">Hora</th>
						<th style="font-size: 22px" colspan="2">Actividad (MBq)</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><input type="time" id="cargar_datos_carbono14_hora" style="font-size: 20px" class="ui-widget ui-state-default ui-corner-all"></td>
						<td><input type="number" id="cargar_datos_carbono14_actividad" style="font-size: 20px;text-align: center;width: 100px;" class="ui-widget ui-state-default ui-corner-all"></td>
						<td>
							<select style="font-size: 20px;text-align: center;width: 100px;" id="cargar_datos_carbono14_actividad_unidad_ingresada" class="select_unidad_actividad_ingresada ui-widget ui-state-default ui-corner-all">
							</select>
						</td>
					</tr>
				</tbody>
			</table>
			<br>
			<input type="hidden" id="guardar_editar_carbono14" value="guardar">
			<button style="height: 70px;width: 100%;font-size: 22px" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="guardar_valores_carbono14()">Guardar Datos Carbono 14</button>
		</div>
		<div class="popup" id="popup_cerrar_planilla">
			<h5 style="margin-top: 10px;margin-bottom: -15px">Está seguro que desea cerrar la planilla de éste turno?</h5><span style="color: gray; font-size: 15px;margin-bottom: 20px">(ésta acción no puede deshacerse)</span>
			<table class="tabla_custom" style="font-size: 15px;width: 75%" align="center" id="tabla_datos_cierre">
				<tr style="text-align: left;">
					<th>Oficial</th>
					<td><?php echo $row_planilla['of_apellido'] . ", " . $row_planilla['of_nombre'] ?></td>
				</tr>
				<tr style="text-align: left;">
					<th>Fecha</th>
					<td><?php echo formafecha6($row_planilla['fecha']) ?></td>
				</tr>
				<tr style="text-align: left;">
					<th>Turno</th>
					<td><?php echo $row_planilla['horarios'] ?></td>
				</tr>
				<tr style="text-align: left;">
					<th>Jefe de Turno</th>
					<td><?php echo $row_planilla['jefe_apellido'] . ", " . $row_planilla['jefe_nombre'] ?></td>
				</tr>
				<tr style="text-align: left;">
					<th>Estado de Planta</th>
					<td><?php echo $row_planilla['nombre_corto'] ?></td>
				</tr>
			</table>
			<br>
			<button style="height: 70px;width: 100%;font-size: 22px" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="cerrar_planilla()">Guardar datos y cerrar planilla</button>
		</div>
		<div class="popup" id="popup_carga_emision_tritio">
			<table class="tabla_custom" align="center" style="width: 100%">
				<thead>
					<tr style="font-size: 22px;font-weight: bold;">
						<td colspan="6" style="padding-bottom: 20px;"><label for="cargar_datos_emision_tritio_fecha">Fecha </label><input type="date" id="cargar_datos_emision_tritio_fecha" style="font-size: 22px" class="ui-widget ui-state-default ui-corner-all"></td>
					</tr>
					<tr style="font-size: 15px">
						<th style="font-size: 22px">Desde</th>
						<td><input type="time" id="cargar_datos_emision_tritio_desde" style="font-size: 20px" class="ui-widget ui-state-default ui-corner-all"></td>
						<th style="font-size: 22px">Hasta</th>
						<td><input type="time" id="cargar_datos_emision_tritio_hasta" style="font-size: 20px" class="ui-widget ui-state-default ui-corner-all"></td>
					</tr>
					<tr>
						<th style="font-size: 20px;text-align: right;" colspan="2">Ventilación General</th>
						<td colspan="" style="text-align: ;"><input type="number" id="cargar_datos_emision_tritio_vent_gral" style="font-size: 20px;text-align: center;width: 100px;" class="ui-widget ui-state-default ui-corner-all"></td>
						<td style="text-align: left;font-size: 18px">
							<select style="height: 28px" id="cargar_datos_emision_tritio_vent_gral_unidad_actividad" class="select_unidad_actividad_ingresada ui-widget ui-state-default ui-corner-all">
							</select>
						</td>
					</tr>
					<tr>
						<th style="font-size: 20px;text-align: right;" colspan="2">Ventilación Reactor</th>
						<td colspan="" style="text-align: ;"><input type="number" id="cargar_datos_emision_tritio_vent_reactor" style="font-size: 20px;text-align: center;width: 100px" class="ui-widget ui-state-default ui-corner-all"></td>
						<td style="text-align: left;font-size: 18px">
							<select style="height: 28px" id="cargar_datos_emision_tritio_vent_reactor_unidad_actividad" class="select_unidad_actividad_ingresada ui-widget ui-state-default ui-corner-all">
							</select>
						</td>
					</tr>
				</thead>
			</table>
			<br>
			<input type="hidden" id="guardar_editar_emision_tritio" value="guardar">
			<button style="height: 70px;width: 100%;font-size: 22px" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="guardar_valores_emision_tritio()">Guardar Datos Emisión Tritio</button>
		</div>
		<div class="popup" id="popup_carga_emision_tritio_editar">
			<table class="tabla_custom" align="center" style="width: 100%">
				<thead>
					<tr style="font-size: 22px;font-weight: bold;">
						<td colspan="6" style="padding-bottom: 20px;"><label for="cargar_datos_emision_tritio_editar_fecha">Fecha </label><input type="date" id="cargar_datos_emision_tritio_editar_fecha" style="font-size: 22px" class="ui-widget ui-state-default ui-corner-all"></td>
					</tr>
					<tr style="font-size: 15px">
						<th style="font-size: 22px">Desde</th>
						<td><input type="time" id="cargar_datos_emision_tritio_editar_desde" style="font-size: 20px" class="ui-widget ui-state-default ui-corner-all"></td>
						<th style="font-size: 22px">Hasta</th>
						<td><input type="time" id="cargar_datos_emision_tritio_editar_hasta" style="font-size: 20px" class="ui-widget ui-state-default ui-corner-all"></td>
					</tr>
					<tr>
						<th style="font-size: 20px;text-align: right;" colspan="2">Ventilación General</th>
						<td colspan="" style="text-align: ;"><input type="number" id="cargar_datos_emision_tritio_editar_vent_gral" style="font-size: 20px;text-align: center;width: 100px;" class="ui-widget ui-state-default ui-corner-all"></td>
						<td style="text-align: left;font-size: 18px">
							<select style="height: 28px" id="cargar_datos_emision_tritio_editar_vent_gral_unidad_actividad" class="select_unidad_actividad_ingresada ui-widget ui-state-default ui-corner-all">
							</select>
						</td>
					</tr>
					<tr>
						<th style="font-size: 20px;text-align: right;" colspan="2">Ventilación Reactor</th>
						<td colspan="" style="text-align: ;"><input type="number" id="cargar_datos_emision_tritio_editar_vent_reactor" style="font-size: 20px;text-align: center;width: 100px" class="ui-widget ui-state-default ui-corner-all"></td>
						<td style="text-align: left;font-size: 18px">
							<select style="height: 28px" id="cargar_datos_emision_tritio_editar_vent_reactor_unidad_actividad" class="select_unidad_actividad_ingresada ui-widget ui-state-default ui-corner-all">
							</select>
						</td>
					</tr>
				</thead>
			</table>
			<br>
			<input type="hidden" id="id_emision_tritio" value="">
			<button style="height: 70px;width: 100%;font-size: 22px" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="actualizar_valores_emision_tritio_edicion()">Guardar Datos Emisión Tritio</button>
		</div>
		<div class="popup" id="popup_carga_gases_nobles">
			<table class="tabla_custom" align="center" style="width: 100%" border=0>
				<thead>
					<tr style="font-size: 22px;font-weight: bold;">
						<td colspan="6" style="padding-bottom: 20px;"><label for="cargar_datos_gases_nobles_fecha">Fecha </label><input type="date" id="cargar_datos_gases_nobles_fecha" style="font-size: 22px" class="ui-widget ui-state-default ui-corner-all"></td>
					</tr>
					<tr style="font-size: 15px">
						<th style="font-size: 22px">Desde</th>
						<td colspan="2"><input type="time" id="cargar_datos_gases_nobles_desde" style="font-size: 22px" class="ui-widget ui-state-default ui-corner-all"></td>
						<th style="font-size: 22px">Hasta</th>
						<td colspan="2"><input type="time" id="cargar_datos_gases_nobles_hasta" style="font-size: 22px" class="ui-widget ui-state-default ui-corner-all"></td>
					</tr>
				</thead>
			</table>
			<br>
			<table>
				<tbody>
					<tr style="font-size: 15px;padding-bottom: -30px">
						<th style="padding-bottom: -30px">Ar-41</th>
						<th style="padding-bottom: -30px">Kr-85</th>
						<th style="padding-bottom: -30px">Kr-85m</th>
						<th style="padding-bottom: -30px">Xe-133</th>
						<th style="padding-bottom: -30px">Xe-135</th>
						<th style="padding-bottom: -30px">Xe-135m</th>
					</tr>
					<tr style="height: 25px;font-size: 15px">
						<td style="height: 5px">
							<input type="number" id="cargar_datos_gases_nobles_ar41" style="font-size: 15px;width: 100%;margin-bottom: -5px" class="ui-widget ui-state-default ui-corner-all ">
						</td>
						<td style="height: 5px">
							<input type="number" id="cargar_datos_gases_nobles_kr85" style="font-size: 15px;width: 100%;margin-bottom: -5px" class="ui-widget ui-state-default ui-corner-all ">
						</td>
						<td style="height: 5px">
							<input type="number" id="cargar_datos_gases_nobles_kr85m" style="font-size: 15px;width: 100%;margin-bottom: -5px" class="ui-widget ui-state-default ui-corner-all ">
						</td>
						<td style="height: 5px">
							<input type="number" id="cargar_datos_gases_nobles_xe133" style="font-size: 15px;width: 100%;margin-bottom: -5px" class="ui-widget ui-state-default ui-corner-all ">
						</td>
						<td style="height: 5px">
							<input type="number" id="cargar_datos_gases_nobles_xe135" style="font-size: 15px;width: 100%;margin-bottom: -5px" class="ui-widget ui-state-default ui-corner-all ">
						</td>
						<td style="height: 5px">
							<input type="number" id="cargar_datos_gases_nobles_xe135m" style="font-size: 15px;width: 100%;margin-bottom: -5px" class="ui-widget ui-state-default ui-corner-all ">
						</td>
					</tr>
					<tr style="">
						<td style="color: ;font-size: 16px;vertical-align: top">GBq</td>
						<td style="color: ;font-size: 16px;vertical-align: top">GBq</td>
						<td style="color: ;font-size: 16px;vertical-align: top">GBq</td>
						<td style="color: ;font-size: 16px;vertical-align: top">GBq</td>
						<td style="color: ;font-size: 16px;vertical-align: top">GBq</td>
						<td style="color: ;font-size: 16px;vertical-align: top">GBq</td>
					</tr>
				</tbody>
			</table>
			<br>
			<input type="hidden" id="guardar_editar_gases_nobles" value="guardar">
			<button style="height: 70px;width: 100%;font-size: 22px" class='ui-button ui-widget ui-state-default ui-corner-all' onclick="guardar_valores_gases_nobles()">Guardar Datos Gases Nobles</button>
		</div>
		<div class="popup" id="popup_form">
			<table style="font-size: 16px;width: 100%;text-align: center;">
				<tr>
					<th>Fecha</th>
					<th>Hora</th>
				</tr>
				<tr>
					<td><input id="fecha_rutina_campo" type='date' class='ui-widget ui-state-default ui-corner-all' style='font-size: 15px' value='<?php echo $fecha_actual ?>'></td>
					<td><input id="hora_rutina_campo" type='time' class='ui-widget ui-state-default ui-corner-all' style='font-size: 15px' value='<?php echo $hora_actual ?>'></td>
				</tr>
				<tr>
					<th>Ubicación</th>
					<th>Relevado por</th>
				</tr>
				<tr>
					<td>
						<select id="ubicacion_rutina_campo" class='ui-widget ui-state-default ui-corner-all'>
							<option value="-">-</option>
							<?php mysql_data_seek($recintos, 0);
							do {
								if ($row_recintos['id'] <> "") { ?>
									<option value="<?php echo $row_recintos['id'] ?>"><?php echo $row_recintos['recinto'] ?></option>
							<?php };
							} while ($row_recintos = mysql_fetch_assoc($recintos)) ?>
						</select>
					</td>
					<td>
						<select id="relevado_por_rutina_campo" class='ui-widget ui-state-default ui-corner-all'>
							<option value="-">-</option>
							<?php do { ?>
								<option value="<?php echo $row_ofrad['id_of_rad'] ?>" <?php if ($row_ofrad['id_of_rad'] == $row_planilla['of_rad']) {
																							echo "selected";
																						}

																						?> value="<?php echo $row_ofrad['id_of_rad'] ?>"><?php echo $row_ofrad['of_apellido'] . ", " . $row_ofrad['of_nombre'] ?></option>
							<?php } while ($row_ofrad = mysql_fetch_assoc($ofrad)) ?>
						</select>
					</td>
				</tr>
			</table>
			<table style="font-size: 16px;width: 100%">
				<tr>
					<th>Gases Nobles<br><span style="font-size: 12px">(DAC)</span></th>
					<th>Tritio<br><span style="font-size: 12px">(DAC)</span></th>
					<th>Tasa de dosis<br><span style="font-size: 12px">(µSv/h)</span></th>
				</tr>
				<tr>
					<td style="padding: 5px"><input min="0" id="gases_nobles_rutina_campo" type='number' class='ui-widget ui-state-default ui-corner-all' style='font-size: 15px;width: 100%;'></td>
					<td style="padding: 5px"><input min="0" id="tritio_rutina_campo" type='number' class='ui-widget ui-state-default ui-corner-all' style='font-size: 15px;width: 100%;'></td>
					<td style="padding: 5px"><input min="0" id="tasa_dosis_rutina_campo" type='number' class='ui-widget ui-state-default ui-corner-all' style='font-size: 15px;width: 100%;'></td>
				</tr>
				<tr>
					<th colspan="3">
						Observaciones
					</th>
				</tr>
				<tr>
					<td colspan="3">
						<textarea style="width: 400px;height: 50px" class="ui-state-default ui-corner-all" id="observaciones_rutina_campo"></textarea>
					</td>
				</tr>
				<tr>
					<td colspan="3"><button class='ui-button ui-widget ui-state-default ui-corner-all' style="width: 100%;font-size: 22px" onclick="guardar_datos_relev_rutina()">Guardar</button></td>
				</tr>
			</table>
		</div>
		<div id="popup_confirmar_borrado_emision" title="Confirmar eliminación" style="display: none;">
			<div style="font-size: 16px; line-height: 1.4;">
				¿Estás seguro de que querés eliminar esta emisión?<br>
				<span style="font-weight: bold; color: #900;">Esta acción no se puede deshacer.</span>
			</div>
		</div>

		<div class="popup" id="popup_radionucleidos_concentrador">
			<div id="info_volumen_emision" style="font-size: 16px; text-align: right; margin-top: 0px; margin-bottom: 12px"></div>

			<table style="font-size: 18px; text-align: center; margin: auto;" class="tabla_custom">
				<thead>
					<tr>
						<th>Radionucleído</th>
						<th colspan="2">Actividad</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<select id="select_radionucleido" class="ui-widget ui-state-default ui-corner-all" style="width: 100%; height: 28px;"></select>
						</td>
						<td>
							<input type="number" id="actividad_radionucleido" class="ui-widget ui-state-default ui-corner-all" style="width: 100%; height: 26px;">
						</td>
						<td>
							<span style="padding: 0 8px;">Bq</span>
						</td>
					</tr>
				</tbody>
			</table>

			<button style="position: absolute; margin-right: 5px; margin-left: 5px; margin-bottom: 10px; left: 5px; bottom: 0px; height: 70px; width: calc(100% - 20px); font-size: 22px;" id="btn_guardar_radios" class="ui-button ui-widget ui-state-default ui-corner-all" onclick="guardar_radionucleidos_concentrador()">
				Guardar
			</button>

			<table id="tabla_radionucleidos_agregados" class="tabla_custom" style="font-size: 18px;text-align: center; width: 100%; margin-top: 10px">
				<thead>
					<tr>
						<th>Radionucleído</th>
						<th>Actividad (Bq/l)</th>
						<th>Actividad (TBq)</th>
						<th>Ai / ROi</th>
						<th>% Parcial LDD</th>
						<th>Acciones</th>
					</tr>
				</thead>
				<tbody>
					<td colspan="3">No hay datos cargados</td>
				</tbody>
			</table>

			<div style="margin-top: 20px; text-align: center;">
				<button onclick="agregar_radionucleido_a_tabla()" id="btn_agregar_radionucleido" class="ui-button ui-widget ui-state-default ui-corner-all" style="padding: 10px 30px; font-size: 18px;">
					Agregar Radionucleído
				</button>
			</div>
		</div>
		<div id="popup_observaciones_tritio" title="Editar Observaciones" style="display: none;">
			<textarea id="input_observaciones_tritio" style="width: 100%; height: 200px;" class="ui-widget ui-state-default ui-corner-all"></textarea>

			<div style="margin-top: 15px; text-align: center;">
				<button class="ui-button ui-widget ui-state-default ui-corner-all" onclick="guardar_observaciones_tritio()">
					Guardar Observaciones
				</button>
			</div>
		</div>

		<!-- ////////////////////////////////////////////// fin Popups HTML///////////////////////////////////////////////////////// -->
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
	<div id="bot_volver_id" style="position:fixed;right:20px;bottom:45px"><img src="../images/icono_volver.png" onclick="window.location.replace('cargar_planilla.php');" style="cursor:pointer;width:50px"></div>
	<div id="boton_finalizar_planilla" style="position:fixed;left:10px;bottom:45px;text-align: center;cursor:pointer" title="Finalizar Planilla"><img src="Images/icon_guardar.svg" onclick="abrir_popup_cerrar_planilla()" style="cursor:pointer;width:50px"></div>
	<div id="boton_modificar_observaciones" style="position:fixed;left:175px;bottom:45px;text-align: center;cursor:pointer" title="Observaciones"><img src="Images/icon_observaciones.png" onclick="abrir_editar_observaciones()" style="cursor:pointer;width:50px"></div>
	<div id="boton_modificar_libro_actas" style="position:fixed;left:230px;bottom:45px;text-align: center;cursor:pointer" title="Libro de Actas"><img src="Images/libro_actas.svg" onclick="abrir_editar_libro_actas()" style="cursor:pointer;width:50px"></div>
	<div id="boton_imprimir_registro" style="position:fixed;left:65px;bottom: 45px;text-align: center;cursor:pointer" title="Imprimir Planilla"><img src="Images/icon_print.svg" onclick="imprimir_planilla_registro_radiologico()" style="cursor:pointer;width:50px"></div>
	<div id="boton_ver_planilla_curso" style="position:fixed;left:125px;bottom:45px;text-align: center;cursor:pointer" title="Ver planilla en curso"><img src="Images/icon_ver_informe_en_curso2.png" onclick="ver_planilla_en_curso()" style="cursor:pointer;width:50px"></div>



	<script>
		$("#actividad_radionucleido").on("keydown", function(e) {
			if (e.key === "Enter") {
				e.preventDefault();
				$("#btn_agregar_radionucleido").click();
			}
		});
		const EMISIONES_7923 = {}; // clave: id_emision, valor: objeto con volumen y lo que quieras
		let RADIONUCLEIDOS_DISPONIBLES = [];
		let RADIONUCLEIDOS_DE_EMISIONES = [];
		let ID_TRITIO_CONC_EDITANDO = null;
		let TURNO_OFICIAL = '<?php echo ($row_planilla['horarios']) ?>';
		let id_planilla = '<?php echo $id_planilla ?>';

		document.body.style.zoom = "85%";
		$('#popup_carga_radionucleidos_efluentes_liquidos').on('dialogclose', function(event) {
			var id_planilla = '<?php echo $id_planilla ?>'
			recuperar_valores_efluentes_liquidos(id_planilla)
		});
		$(document).ready(function() {
			var agregar_datos_radionucleidos_select = "<option value='-'>-</option><?php do { ?><option value='<?php echo $row_radionucleidos['id_radionucleidos'] ?>'><?php echo $row_radionucleidos['radionucleido'] ?></option><?php } while ($row_radionucleidos = mysql_fetch_assoc($radionucleidos)) ?>"
			$("#agregar_datos_radionucleidos_select").append(agregar_datos_radionucleidos_select)
		});
		$(document).ready(function() {
			var agregar_datos_dryers = "<option value='-'>-</option><?php do { ?><option value='<?php echo $row_dryers['id_dryers'] ?>'><?php echo $row_dryers['nombre_dryers'] ?></option><?php } while ($row_dryers = mysql_fetch_assoc($dryers)) ?>"
			$(".select_dryers").append(agregar_datos_dryers)
		});
		$(document).ready(function() {
			var agregar_datos_recintos_select = "<option value='-'>-</option><?php mysql_data_seek($recintos, 0);
																				do {
																					if (isset($row_recintos['recinto'])) { ?><option value='<?php echo $row_recintos['id'] ?>'><?php echo $row_recintos['recinto'] ?></option><?php };
																																																						} while ($row_recintos = mysql_fetch_assoc($recintos)) ?>"
			$(".select_recintos").append(agregar_datos_recintos_select)
		});
		$(document).ready(function() {
			var agregar_datos_unidades_volumen = "<option value='-'>-</option><?php do { ?><option value='<?php echo $row_unidades_volumen['id_tabla_conversion'] ?>'><?php echo $row_unidades_volumen['unidad'] ?></option><?php } while ($row_unidades_volumen = mysql_fetch_assoc($unidades_volumen)) ?>"
			$(".select_unidad_volumen_ingresada").append(agregar_datos_unidades_volumen)
		});
		$(document).ready(function() {
			var agregar_datos_unidades_actividad = "<option value='-'>-</option><?php do { ?><option value='<?php echo $row_unidades_actividad['id_tabla_conversion'] ?>'><?php echo $row_unidades_actividad['unidad'] ?></option><?php } while ($row_unidades_actividad = mysql_fetch_assoc($unidades_actividad)) ?>"
			$(".select_unidad_actividad_ingresada").append(agregar_datos_unidades_actividad)
		});
		$(document).ready(function() {
			var agregar_datos_unidades_tasa_dosis = "<option value='-'>-</option><?php do { ?><option value='<?php echo $row_unidades_tasa_dosis['id_tabla_conversion'] ?>'><?php echo $row_unidades_tasa_dosis['unidad'] ?></option><?php } while ($row_unidades_tasa_dosis = mysql_fetch_assoc($unidades_tasa_dosis)) ?>"
			$(".select_unidad_tasa_dosis_ingresada").append(agregar_datos_unidades_tasa_dosis)
		});
		$(document).ready(function() {
			var agregar_datos_unidades_concentracion = "<option value='-'>-</option><?php do { ?><option value='<?php echo $row_unidades_concentracion['id_tabla_conversion'] ?>'><?php echo $row_unidades_concentracion['unidad'] ?></option><?php } while ($row_unidades_concentracion = mysql_fetch_assoc($unidades_concentracion)) ?>"
			$(".select_unidad_concentracion_ingresada").append(agregar_datos_unidades_concentracion)
		});
		$(document).ready(function() {
			var agregar_datos_unidades_concentracion_superficial = "<option value='-'>-</option><?php do { ?><option value='<?php echo $row_unidades_concentracion_superficial['id_tabla_conversion'] ?>'><?php echo $row_unidades_concentracion_superficial['unidad'] ?></option><?php } while ($row_unidades_concentracion_superficial = mysql_fetch_assoc($unidades_concentracion_superficial)) ?>"
			$(".select_unidad_concentracion_superficial_ingresada").append(agregar_datos_unidades_concentracion_superficial)
		});
		$(document).ready(function() {
			var cant_planillas = "<?php echo $totalRows_planilla ?>"
			if (cant_planillas == 0) {
				alert("La planilla solicitada no existe o ya fue cerrada!");
				$(location).attr('href', 'cargar_planilla.php')
			}
		});
		$(document).ready(function() {
			recuperar_emisiones_7923('<?php echo $id_planilla ?>');
		});
		$(document).ready(function() {
			// Inicializar Select2 para el personal opcional
			$('.select2-opcional').select2({
				placeholder: "- Ninguno (Opcional) -",
				allowClear: true,
				dropdownParent: $('#popup_carga_tarea_diaria') // Clave para que no se esconda detrás del modal
			});
		});
		// Limpiamos los campos del personal
		$('#tarea_oficial_buscador').val('');
		$('#tarea_oficial_asociado').val('');
		$('#tarea_ayudante_buscador').val('');
		$('#tarea_ayudante').val('');

		var aerosoles_dac = $('#tarea_aerosoles_dac').val();
		// Captura de personal opcional
		var id_oficial_asociado = $('#tarea_oficial_asociado').val();
		var id_ayudante = $('#tarea_ayudante').val();

		$('#tabla_emisiones_7923').DataTable({
			paging: false,
			searching: false,
			info: false,
			"rowCallback": function(row, data, index) {
				if (index % 2 == 0) {
					$(row).removeClass('myodd myeven');
					$(row).addClass('myodd');
				} else {
					$(row).removeClass('myodd myeven');
					$(row).addClass('myeven');
				}
			},
			sort: false,
			stripeClasses: ['odd-row', 'even-row'],
			language: {
				emptyTable: "No hay datos disponibles",
				loadingRecords: "Cargando...",
				zeroRecords: "No se encontraron resultados"
			}
		});
		$('#tabla1').dataTable({
			"initComplete": function(settings, json) {
				$('#tabla_contenedor').show();
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
		$('#tabla_emision_tritio').dataTable({
			"initComplete": function(settings, json) {
				$('#tabla_contenedor').show();
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
			"initComplete": function(settings, json) {
				$('#tabla_contenedor').show();
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
		$('#tabla_efluentes_liquidos').dataTable({
			"initComplete": function(settings, json) {
				$('#tabla_contenedor').show();
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

		$('#tabla_contaminacion_superficial').dataTable({
			"initComplete": function(settings, json) {
				$('#tabla_contenedor').show();
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

		$('#tabla_eficiencia_dryers').dataTable({
			"initComplete": function(settings, json) {
				$('#tabla_contenedor').show();
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

		function agregar_campos_medicion_rutina() {
			var contador = $('#contador_lineas_campos').val()
			contador++
			actualizar_accordion()
			abrir_popup()
		}

		function guardar_datos_relev_rutina() {
			var id_user = '<?php echo $id_user ?>'
			var fecha = ($('#fecha_rutina_campo').val())
			var hora = $('#hora_rutina_campo').val()
			var ubicacion = $('#ubicacion_rutina_campo').val()
			var gases_nobles = valores_nulos($('#gases_nobles_rutina_campo').val())
			var tritio = valores_nulos($('#tritio_rutina_campo').val())
			var tasa_dosis = valores_nulos($('#tasa_dosis_rutina_campo').val())
			var observaciones = sin_observaciones($('#observaciones_rutina_campo').val())
			var relevado_por = $('#relevado_por_rutina_campo').val()
			var id_planilla = '<?php echo $id_planilla ?>'
			var url = "procesos_reg_rad.php?accion=guardar_valores_rutina&fecha=" + fecha + "&hora=" + hora + "&ubicacion=" + ubicacion + "&gases_nobles=" + gases_nobles + "&tritio=" + tritio + "&tasa_dosis=" + tasa_dosis + "&observaciones=" + observaciones + "&relevado_por=" + relevado_por + "&id_planilla=" + id_planilla + "&id_user=" + id_user
			//alert(url)
			if ((gases_nobles == "NULL") && (tritio == "NULL") && (tasa_dosis == "NULL")) {
				alert("Debe introducir al menos un valor")
			} else {
				$.get(url, function(data) {
					// alert(data)
					cerrar_popup()
					actualizar_tabla()
				})
			}
		}

		function agregar_fila(valores_array) {
			//alert(valores_array)
			var t = $('#tabla1').DataTable()
			t.clear()
			t.draw()
			var new_array = valores_array.split("@@");
			$.each(new_array, function(index, value) {
				if (value == "") {} else {
					var otro_array = value.split("@");
					t.row.add([
						otro_array[0],
						otro_array[1],
						otro_array[2],
						otro_array[3],
						otro_array[4],
						otro_array[5],
						otro_array[6],
						otro_array[7],
						"<img src='Images/icon_delete.png' style='max-height: 25px;: 25px;vertical-align: middle;cursor: pointer' title='Eliminar Registro' onclick=\"eliminar_registro('regrad_relevamiento_rutina','id_relev_rutina'," + otro_array[8] + ")\">"
					]).draw(false);
				}

			})
		}

		function agregar_fila_emision_tritio(valores_array) {
			//alert(valores_array)
			var t = $('#tabla_emision_tritio').DataTable()
			t.clear()
			t.draw()
			var new_array = valores_array.split("@@");
			$.each(new_array, function(index, value) {
				if (value == "") {} else {
					var otro_array = value.split("@");
					t.row.add([
						otro_array[0],
						otro_array[1],
						otro_array[2],
						otro_array[3],
						"<img src='Images/icon_edit.png' style='width: 25px;vertical-align: middle;cursor: pointer' title='Editar' onclick='editar_emision_tritio(" + otro_array[4] + ")'><img src='Images/icon_delete.png' style='width: 25px;vertical-align: middle;cursor: pointer' title='Eliminar Registro' onclick=\"eliminar_registro('regrad_tritio','id_tritio'," + otro_array[4] + ")\">",
					]).draw(false);
				}

			})
		}

		function agregar_fila_espectrometria_aire(valores_array) {
			//alert(valores_array)
			var t = $('#tabla_espectrometria_aire').DataTable()
			t.clear()
			t.draw()
			var new_array = valores_array.split("@@");
			$.each(new_array, function(index, value) {
				if (value == "") {} else {
					var otro_array = value.split("@");
					t.row.add([
						otro_array[0],
						otro_array[1],
						otro_array[2],
						otro_array[3],
						otro_array[4],
						otro_array[5],
						otro_array[6],
						otro_array[7],
						"<img src='Images/icon_edit.png' style='width: 25px;vertical-align: middle;cursor: pointer' title='Editar' onclick='editar_espectrometria_aire(" + otro_array[8] + ")'><img src='Images/icon_delete.png' style='width: 25px;vertical-align: middle;cursor: pointer' title='Eliminar Registro' onclick=\"eliminar_registro('regrad_espectr_conc_radionuclei','id_conc_rad'," + otro_array[8] + ")\">",
					]).draw(false);
				}

			})
		}

		function agregar_fila_efluentes_liquidos(valores_array) {
			//alert(valores_array)
			var t = $('#tabla_efluentes_liquidos').DataTable()
			t.clear()
			t.draw()
			var new_array = valores_array.split("@@");
			$.each(new_array, function(index, value) {
				if (value == "") {} else {
					var otro_array = value.split("@");
					t.row.add([
						otro_array[1],
						otro_array[2],
						otro_array[3],
						otro_array[4],
						otro_array[5],
						otro_array[6],
						otro_array[7],
						"<img src='Images/add.png' style='max-height: 25px;: 25px;vertical-align: middle;cursor: pointer' title='Cargar radionucleidos' onclick='abrir_popup_carga_radionucleidos_efluentes_liquidos(" + otro_array[0] + ")'> <img src='Images/icon_observaciones_efluentes.png' style='max-height: 25px;: 25px;vertical-align: middle;cursor: pointer' title='Editar Observaciones' onclick=\"abrir_popup_carga_observaciones_efluentes(" + otro_array[0] + ")\"> <img src='Images/print.png' style='max-height: 25px;: 25px;vertical-align: middle;cursor: pointer' title='Imprimir' onclick='imprimir_planilla_efluentes(" + otro_array[0] + ")'> <img src='Images/icon_delete.png' style='max-height: 25px;: 25px;vertical-align: middle;cursor: pointer' title='Eliminar Registro' onclick=\"eliminar_registro('regrad_efluentes_liquidos_nueva','id_efluentes'," + otro_array[0] + ")\">",
					]).draw(false);
				}

			})
		}

		function agregar_fila_eficiencia_dryers(valores_array) {
			//alert(valores_array)
			var t = $('#tabla_eficiencia_dryers').DataTable()
			t.clear()
			t.draw()
			var new_array = valores_array.split("@@");
			$.each(new_array, function(index, value) {
				if (value == "") {} else {
					var otro_array = value.split("@");
					t.row.add([
						otro_array[1],
						otro_array[2],
						otro_array[3],
						otro_array[4],
						otro_array[5],
						otro_array[6] + ' %',
						"<img src='Images/icon_delete.png' style='max-height: 25px;: 25px;vertical-align: middle;cursor: pointer' title='Eliminar Registro' onclick=\"eliminar_registro('regrad_eficiencia_dryers','id_eficiencia_dryers'," + otro_array[0] + ")\">",
					]).draw(false);
				}

			})
		}

		function agregar_fila_contaminacion_superficial(valores_array) {
			//alert(valores_array)
			var t = $('#tabla_contaminacion_superficial').DataTable()
			t.clear()
			t.draw()
			var new_array = valores_array.split("@@");
			$.each(new_array, function(index, value) {
				if (value == "") {} else {
					var otro_array = value.split("@");
					t.row.add([
						otro_array[2],
						otro_array[1],
						otro_array[3],
						otro_array[4],
						otro_array[5],
						"<img src='Images/icon_delete.png' style='max-height: 25px;: 25px;vertical-align: middle;cursor: pointer' title='Eliminar Registro' onclick=\"eliminar_registro('regrad_contaminacion_superficial','id_contaminacion_superficial'," + otro_array[6] + ")\">",
					]).draw(false);
				}

			})
		}

		function actualizar_tabla() {
			var id_planilla = '<?php echo $id_planilla ?>'
			var url = "procesos_reg_rad.php?accion=cargar_valores_rutina&id_planilla=" + id_planilla
			//alert(url)
			$.get(url, function(data) {
				if (data == "sin_datos") {
					var t = $('#tabla1').DataTable()
					t.clear()
					t.draw()
					contar_filas()
				} else {
					agregar_fila(data)
					contar_filas()
				}
			})
		}

		function valores_nulos(dato) {
			if (dato == "") {
				return "NULL"
			} else {
				return dato
			}
		}

		function sin_observaciones(dato) {
			if (dato == "") {
				return "--Sin observaciones--"
			} else {
				return dato
			}
		}

		function select_r501() {
			$('#ubicacion_rutina_campo').val('45');
		}

		function hora_actual(campo) {
			var url2 = "procesos_reg_rad.php?accion=obtener_hora_actual"
			$.get(url2, function(data) {
				$('#' + campo).val(data)
			})

		}

		function fecha_actual(campo) {
			var url2 = "procesos_reg_rad.php?accion=obtener_fecha_actual"
			$.get(url2, function(data) {
				$('#' + campo).val(data)
			})

		}

		function fecha_hora_actual_campo() {
			var url = "procesos_reg_rad.php?accion=obtener_fecha_actual"
			var url2 = "procesos_reg_rad.php?accion=obtener_hora_actual"
			$.get(url, function(data) {
				$('#fecha_rutina_campo').val(data)
			})
			$.get(url2, function(data) {
				$('#hora_rutina_campo').val(data)
			})
		}

		function contar_filas() {
			var oTable = $('#tabla1').DataTable();
			var info = oTable.page.info();
			var count = info.recordsTotal;
			if (count == 0) {
				$('#contador_mediciones').text("- ninguna -")
			} else {
				$('#contador_mediciones').text(count)
			}
		}

		function poner_en_blanco_campos() {
			$('#gases_nobles_rutina_campo').val('')
			$('#tritio_rutina_campo').val('')
			$('#tasa_dosis_rutina_campo').val('')
		}

		function cerrar_planilla() {
			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var url = "procesos_reg_rad.php?accion=cerrar_planilla&id_planilla=" + id_planilla + "&id_usuario=" + id_usuario
			$.get(url, function(data) {
				if (data == "ok") {
					window.location.replace('cargar_planilla.php')
				}
			})

		}
		//---------------------------------accordions--------------------------------------------------------
		$(function() {
			$("#accordion").accordion({
				collapsible: true,
				heightStyle: false,
				clearStyle: true
			});
		});
		$(function() {
			$("#accordion_efluentes").accordion({
				collapsible: true,
				heightStyle: false,
				clearStyle: true,
				active: false
			});
		});
		$(function() {
			$("#accordion_tritio").accordion({
				collapsible: true,
				heightStyle: false,
				clearStyle: true,
				active: false
			});
		});

		function actualizar_accordion() {
			$("#accordion").accordion("refresh");
		}
		//---------------------------------accordions--------------------------------------------------------
		//---------------------------------popups------------------------------------------------------------
		function abrir_popup() {
			fecha_hora_actual_campo()
			select_r501()
			poner_en_blanco_campos()
			$("#popup_form").dialog({
				height: 430,
				width: 500,
				title: "Agregar mediciones",
				resizable: false,
				modal: true,

			});
			$("#popup_form").dialog();
		};

		function cerrar_popup() {
			$("#popup_form").dialog('close');
		};

		function abrir_popup_cerrar_planilla() {
			$("#popup_cerrar_planilla").dialog({
				height: 450,
				width: 700,
				title: "Cerrar Planilla",
				resizable: false,
				modal: true,
			});
		}

		function abrir_popup_carga_carbono14() {
			hora_actual("cargar_datos_carbono14_hora")
			fecha_actual("cargar_datos_carbono14_fecha")
			$('#cargar_datos_carbono14_actividad_unidad_ingresada').val('3')
			$("#popup_carga_carbono14").dialog({
				height: 350,
				width: 700,
				title: "Carga Carbono 14",
				resizable: false,
				modal: true,
			});
		}

		function abrir_popup_carga_dedo_frio() {
			hora_actual("cargar_datos_dedo_frio_hora")
			fecha_actual("cargar_datos_dedo_frio_fecha")
			$("#popup_carga_dedo_frio").dialog({
				height: 420,
				width: 700,
				title: "Carga Dedo Frio 7312",
				resizable: false,
				modal: true,
			});
		}

		function abrir_popup_carga_concentrador7923() {
			fecha_actual("cargar_datos_concentrador7923_fecha")
			$("#cargar_datos_concentrador7923_actividad_unidad_ingresada").val(2)
			$("#cargar_datos_concentrador7923_volumen_unidad_ingresada").val(1)
			$("#cargar_datos_concentrador7923_procedencia").val('')
			$("#cargar_datos_concentrador7923_actividad").val('')
			$("#cargar_datos_concentrador7923_volumen").val('')
			$("#cargar_datos_concentrador7923_ldd").val('')
			$("#popup_carga_concentrador7923").dialog({
				height: 420,
				width: 700,
				title: "Carga Tritio Concentrador 7923",
				resizable: false,
				modal: true,
			});
		}

		function abrir_popup_carga_control_precintos() {
			hora_actual("cargar_datos_control_precintos_hora")
			fecha_actual("cargar_datos_control_precintos_fecha")
			$('#cargar_datos_control_precintos_puerta_s_123b').val('B')
			$('#cargar_datos_control_precintos_puerta_s_008').val('B')
			$('#cargar_datos_control_precintos_puerta_s_144').val('B')
			$("#popup_carga_control_precintos").dialog({
				height: 420,
				width: 700,
				title: "Carga Control de Precintos",
				resizable: false,
				modal: true,
			});
		}

		function abrir_popup_carga_condicion_meteorologica() {
			hora_actual("cargar_datos_condicion_meteorologica_hora")
			fecha_actual("cargar_datos_condicion_meteorologica_fecha")
			$("#popup_carga_condicion_meteorologica").dialog({
				height: 420,
				width: 700,
				title: "Carga Condición Meteorológica",
				resizable: false,
				modal: true,
			});
		}

		function abrir_popup_carga_gases_nobles() {
			hora_actual("cargar_datos_gases_nobles_desde")
			hora_actual("cargar_datos_gases_nobles_hasta")
			fecha_actual("cargar_datos_gases_nobles_fecha")
			$('#cargar_datos_gases_nobles_ar41').val('0')
			$('#cargar_datos_gases_nobles_kr85').val('0')
			$('#cargar_datos_gases_nobles_kr85m').val('0')
			$('#cargar_datos_gases_nobles_xe133').val('0')
			$('#cargar_datos_gases_nobles_xe135').val('0')
			$('#cargar_datos_gases_nobles_xe135m').val('0')
			$("#popup_carga_gases_nobles").dialog({
				height: 450,
				width: 700,
				title: "Carga Gases Nobles",
				resizable: false,
				modal: true,
			});
		}

		function abrir_popup_carga_emision_tritio() {
			hora_actual("cargar_datos_emision_tritio_desde")
			hora_actual("cargar_datos_emision_tritio_hasta")
			fecha_actual("cargar_datos_emision_tritio_fecha")
			$('#cargar_datos_emision_tritio_vent_gral').val("")
			$('#cargar_datos_emision_tritio_vent_reactor').val("")
			$('#cargar_datos_emision_tritio_vent_reactor_unidad_actividad').val("5")
			$('#cargar_datos_emision_tritio_vent_gral_unidad_actividad').val("5")
			$("#popup_carga_emision_tritio").dialog({
				height: 400,
				width: 700,
				title: "Carga Emisión Tritio",
				resizable: false,
				modal: true,
			});
		}

		function abrir_popup_carga_emision_tritio_editar() {
			$('#cargar_datos_emision_tritio_editar_vent_reactor_unidad_actividad').val("5")
			$('#cargar_datos_emision_tritio_editar_vent_gral_unidad_actividad').val("5")
			$("#popup_carga_emision_tritio_editar").dialog({
				height: 400,
				width: 700,
				title: "Edición Emisión Tritio",
				resizable: false,
				modal: true,
			});
		}

		function abrir_editar_observaciones() {
			recuperar_observaciones()
			$("#popup_editar_observaciones").dialog({
				height: 420,
				width: 900,
				title: "Observaciones",
				resizable: false,
				modal: true,
			});
		}

		function abrir_editar_libro_actas() {
			recuperar_libro_actas();
			cargar_tareas_diarias();
			$("#popup_libro_actas").dialog({
				height: 800,
				width: 1100,
				title: "Libro de Actas",
				resizable: false,
				modal: true,
			});
		}

		function abrir_popup_carga_datos_espectrometria_aire() {
			hora_actual("cargar_datos_espectrometria_aire_hora")
			fecha_actual("cargar_datos_espectrometria_aire_fecha")
			$('#cargar_datos_espectrometria_aire_ar41').val("")
			$('#cargar_datos_espectrometria_aire_xe133').val("")
			$('#cargar_datos_espectrometria_aire_xe135').val("")
			$('#cargar_datos_espectrometria_aire_kr88').val("")
			$("#popup_carga_espectrometria_aire").dialog({
				height: 550,
				width: 900,
				title: "Carga Espectrometria Aire Zona Controlada",
				resizable: false,
				modal: true,
			});
		}

		function abrir_popup_carga_datos_efluentes_liquidos() {
			hora_actual("cargar_datos_efluentes_liquidos_hora")
			fecha_actual("cargar_datos_efluentes_liquidos_fecha")
			$('#cargar_datos_efluentes_liquidos_tanque').val('')
			$('#cargar_datos_efluentes_liquidos_volumen').val('')
			$('#cargar_datos_efluentes_liquidos_unidad_volumen_ingresada').val('2')
			$("#popup_carga_efluentes_liquidos").dialog({
				height: 400,
				width: 900,
				title: "Carga Emisión Efluentes Líquidos",
				resizable: false,
				modal: true,
			});
		}

		function abrir_popup_carga_datos_tritio_concentrador() {
			// Pone fecha y hora actual
			hora_actual("emision_7923_hora");
			fecha_actual("emision_7923_fecha");

			// Limpia campos
			$('#emision_7923_volumen').val('');
			$('#emision_7923_procedencia').val('');
			$('#emision_7923_unidad_volumen').val('2'); // por defecto la opción 2, cambiá si hiciera falta

			// Abre el diálogo
			$("#popup_carga_tritio_concentrador_7923").dialog({
				height: 350,
				width: 900,
				title: "Carga Tritio Concentrador 7923",
				resizable: false,
				modal: true
			});
		}

		function abrir_popup_carga_datos_contaminacion_superficial() {
			hora_actual("cargar_datos_contaminacion_superficial_hora")
			fecha_actual("cargar_datos_contaminacion_superficial_fecha")
			$('#cargar_datos_contaminacion_superficial_beta_gamma').val('')
			$('#cargar_datos_contaminacion_superficial_alfa').val('')
			$('#cargar_datos_contaminacion_superficial_dosis_equivalente').val('')
			$('#cargar_datos_contaminacion_superficial_unidad_beta_gamma').val('1')
			$('#cargar_datos_contaminacion_superficial_unidad_alfa').val('1')
			$('#cargar_datos_contaminacion_superficial_unidad_dosis_equivalente').val('1')
			$("#popup_carga_contaminacion_superficial").dialog({
				height: 570,
				width: 900,
				title: "Carga Medición de Contaminación superficial y Tasa de Dosis Equivalente",
				resizable: false,
				modal: true,
			});
		}

		function abrir_popup_carga_datos_eficiencia_dryers() {
			hora_actual("cargar_datos_eficiencia_dryers_hora")
			fecha_actual("cargar_datos_eficiencia_dryers_fecha")
			$('#cargar_datos_eficiencia_dryers_dryer').val('1')
			$('#cargar_datos_eficiencia_dryers_entrada').val('')
			$('#cargar_datos_eficiencia_dryers_salida').val('')
			$("#popup_carga_eficiencia_dryers").dialog({
				height: 390,
				width: 900,
				title: "Carga Eficiencia Dryers",
				resizable: false,
				modal: true,
			});
		}

		function abrir_popup_carga_datos_espectrometria_aire_editar() {
			$("#popup_carga_espectrometria_aire_editar").dialog({
				height: 550,
				width: 900,
				title: "Edición Espectrometria Aire Zona Controlada",
				resizable: false,
				modal: true,
			});
		}

		function abrir_popup_carga_radionucleidos_efluentes_liquidos(id_efluentes) {
			recuperar_datos_tabla_radionucleidos_efluentes_liquidos(id_efluentes)
			$("#popup_carga_radionucleidos_efluentes_liquidos").dialog({
				height: 600,
				width: 600,
				title: "Agregar Radionucleídos y Concentración",
				resizable: false,
				modal: true,
			});

		}

		function abrir_popup_carga_radionucleidos_efluentes_liquidos_agregar() {
			$('#agregar_datos_radionucleidos_select').val('-')
			$('#cargar_datos_concentracion_radionucleidos_efluentes_liquidos').val('')
			$('#cargar_datos_unidad_concentracion_radionucleidos_efluentes_liquidos').val('-')
			$("#popup_carga_radionucleidos_efluentes_liquidos_agregar").dialog({
				height: 300,
				width: 500,
				title: "Agregar Radionucleídos y Concentración",
				resizable: false,
				modal: true,
			});

		}

		function abrir_popup_carga_observaciones_efluentes(id_efluentes) {
			recuperar_observaciones_efluentes(id_efluentes)
			$('#var_id_efluentes_para_carga_observaciones_efluentes').val(id_efluentes)
			$("#popup_carga_observaciones_efluentes").dialog({
				height: 450,
				width: 500,
				title: "Actualizar Observaciones",
				resizable: false,
				modal: true,
			});

		}

		function obtenerRadionucleidosRoGaseoso(callback) {
			$.get("procesos_reg_rad.php?accion=listar_radionucleidos_con_ro", function(radios) {
				RADIONUCLEIDOS_DISPONIBLES = radios.sort((a, b) => {
					const [letraA, numeroA] = a.simbolo.split('-');
					const [letraB, numeroB] = b.simbolo.split('-');
					const numA = parseInt(numeroA, 10);
					const numB = parseInt(numeroB, 10);
					return numA !== numB ? numA - numB : letraA.localeCompare(letraB, 'es', {
						sensitivity: 'base'
					});
				});

				$("#select_radionucleido").empty();
				RADIONUCLEIDOS_DISPONIBLES.forEach(r => {
					$("#select_radionucleido").append(`<option value="${r.id}">${r.simbolo}</option>`);
				});

				if (callback) callback(); // 🧠 Esto es lo importante
			}, 'json');
		}

		function abrir_popup_radionucleidos(id_emision, volumen) {
			$("#popup_radionucleidos_concentrador").data("id_emision", id_emision);
			$("#popup_radionucleidos_concentrador").data("volumen", volumen);
			$("#actividad_radionucleido").val('');
			$("#select_radionucleido").empty();
			$("#tabla_radionucleidos_agregados tbody").empty();
			$("#btn_agregar_radionucleido").prop("disabled", false);

			$("#info_volumen_emision").html(`<strong>VOLÚMEN:</strong> ${parseFloat(volumen).toFixed(3)} l`);

			// ✅ Cargar el select y luego llenar la tabla
			obtenerRadionucleidosRoGaseoso(() => {
				// 🔁 recién acá cargás la tabla y eliminás opciones del select
				const datosGuardados = EMISIONES_7923[id_emision]?.datosRadionucleidos || [];

				datosGuardados.forEach(r => {
					const fila = `
                            <tr>
                                <td data-id="${r.id_radionucleido}">${r.radionucleido}</td>
                                <td>${parseFloat(r.actividad_Bq_l).toExponential(3)}</td>
                                <td>${r.actividad_total_Tbq.toExponential(3)}</td>
                                <td>${r.ai_roi.toExponential(3)}</td>
                                <td>${r.porcentaje_ldd.toFixed(2)}%</td>
                                <td>
                                    <img src="Images/icon_delete.png" style="width: 25px; cursor: pointer" 
                                        onclick="eliminar_radionucleido_de_tabla(this, '${r.id_radionucleido}', '${r.radionucleido}')">
                                </td>
                            </tr>
                        `;

					$("#tabla_radionucleidos_agregados tbody").append(fila);
					$(`#select_radionucleido option[value="${r.id_radionucleido}"]`).remove();
				});
				if ($("#select_radionucleido option").length === 0) {
					$("#btn_agregar_radionucleido").prop("disabled", true);
				}
			});
			$("#popup_radionucleidos_concentrador").dialog({
				modal: true,
				width: 950,
				height: 750,
				resizable: false,
				title: "Cargar Radionucleídos para emisión"
			});
		}
		//---------------------------------popups------------------------------------------------------------

		function agregar_radionucleido_a_tabla() {
			const select = $("#select_radionucleido");
			const id = select.val();
			const actividad_bq_l = parseFloat($("#actividad_radionucleido").val());
			const volumen = parseFloat($("#popup_radionucleidos_concentrador").data("volumen"));
			// console.log(id + "-" + actividad_bq_l + "-" + volumen)
			if (!id || isNaN(actividad_bq_l) || actividad_bq_l <= 0) {
				alert("Complete todos los campos correctamente.");
				return;
			}

			const nombre = select.find("option:selected").text();
			const calculado = calcular_datos_radionucleido(id, actividad_bq_l, volumen);
			if (!calculado) {
				alert("No se pudo calcular los datos del radionucleido.");
				return;
			}
			// console.log(calculado);
			const fila = `
                        <tr>
                            <td data-id="${id}">${nombre}</td>
                            <td>${actividad_bq_l.toExponential(3)}</td>
                            <td>${calculado.actividad_total_Tbq.toExponential(3)}</td>
                            <td>${calculado.ai_roi.toExponential(3)}</td>
                            <td>${calculado.porcentaje_ldd.toFixed(2)}%</td>
                            <td>
                                <img src="Images/icon_delete.png" style="width: 25px; cursor: pointer" onclick="eliminar_radionucleido_de_tabla(this, '${id}')">
                            </td>
                        </tr>
                    `;
			$("#tabla_radionucleidos_agregados tbody").append(fila);

			select.find(`option[value="${id}"]`).remove();
			$("#actividad_radionucleido").val('').focus();

			if (select.children().length === 0) {
				$("#btn_agregar_radionucleido").prop("disabled", true);
			}
		}

		function eliminar_radionucleido_de_tabla(btn, id) {
			$(btn).closest('tr').remove();

			const select = $("#select_radionucleido");
			select.empty();

			// Volver a llenar con todos los que aún no están en la tabla
			const idsYaAgregados = new Set();
			$("#tabla_radionucleidos_agregados tbody tr").each(function() {
				const idAgregado = $(this).find("td:first").data("id");
				idsYaAgregados.add(String(idAgregado));
			});

			RADIONUCLEIDOS_DISPONIBLES.forEach(r => {
				if (!idsYaAgregados.has(String(r.id))) {
					select.append(`<option value="${r.id}">${r.simbolo}</option>`);
				}
				$("#btn_agregar_radionucleido").prop("disabled", false);
			});
		}

		function guardar_radionucleidos_concentrador() {
			const id_planilla = '<?php echo $id_planilla ?>';
			const id_tritio_conc = $("#popup_radionucleidos_concentrador").data("id_emision");

			const radios = [];
			$("#tabla_radionucleidos_agregados tbody tr").each(function() {
				const id = $(this).find("td").eq(0).data("id");
				const actividad_Bq_l = $(this).find("td").eq(1).text().replace(",", ".");
				radios.push({
					id: id,
					actividad_Bq_l: parseFloat(actividad_Bq_l)
				});
			});
			if (radios.length === 0) {
				alert("No hay radionucleídos cargados para guardar.");
				return;
			}

			$.get("procesos_reg_rad.php", {
				accion: "guardar_radionucleidos_emision_7923",
				id_planilla: id_planilla,
				id_tritio_conc: id_tritio_conc,
				radios: JSON.stringify(radios)
			}, function(res) {
				if (res.estado === "ok") {
					// alert("Radionucleídos guardados con éxito.");
					$("#popup_radionucleidos_concentrador").dialog("close");
					recuperar_emisiones_7923(id_planilla);
				} else {
					alert("Error al guardar: " + (res.mensaje || "respuesta inesperada"));
					console.error(res);
				}
			}, "json");
		}

		function calcular_datos_radionucleido(id, actividad_bq_l, volumen_litros) {
			// console.log("⏳ Ejecutando cálculo para:");
			// console.log("ID:", id);
			// console.log("Actividad (Bq/l):", actividad_bq_l);
			// console.log("Volumen (l):", volumen_litros);

			const radionuclido = RADIONUCLEIDOS_DISPONIBLES.find(r => r.id == id);
			console.log("Radionucleido encontrado:", radionuclido);
			const ro_diario = parseFloat(radionuclido.ro_gaseoso) * 0.01;

			if (!radionuclido) {
				console.warn("⚠️ Radionucleido no encontrado con ID:", id);
				return null;
			}

			if (!ro_diario) {
				console.warn("⚠️ RO gaseoso no definido para este radionucleido:", radionuclido);
				return null;
			}

			//1E-12 pasaje de Bq a TBq

			const actividad_total_Tbq = actividad_bq_l * 1E-12 * volumen_litros;
			const ai_roi = (actividad_total_Tbq * 1000000000000) / ro_diario;
			const porcentaje_ldd = ai_roi * 100;

			// console.log("✅ Cálculos:");
			// console.log("Actividad total (Bq):", actividad_total_Tbq);
			// console.log("Ai / ROi:", ai_roi);
			// console.log("% LDD:", porcentaje_ldd);

			return {
				actividad_total_Tbq,
				ai_roi,
				porcentaje_ldd
			};
		}

		function abrir_popup_observaciones(id_emision) {
			ID_TRITIO_CONC_EDITANDO = id_emision;

			// Llenar con lo que ya tenga si existe
			const texto_actual = EMISIONES_7923[id_emision]?.observaciones || '';
			$("#input_observaciones_tritio").val(texto_actual);

			$("#popup_observaciones_tritio").dialog({
				modal: true,
				width: 600,
				resizable: false,
				title: "Editar Observaciones"
			});
		}

		function guardar_observaciones_tritio() {
			const observaciones = $("#input_observaciones_tritio").val();

			if (!ID_TRITIO_CONC_EDITANDO) {
				alert("No se seleccionó una emisión válida.");
				return;
			}

			$.get("procesos_reg_rad.php", {
				accion: "guardar_observaciones_emision",
				id_tritio_conc: ID_TRITIO_CONC_EDITANDO,
				observaciones: observaciones
			}, function(respuesta) {
				if (respuesta === "ok") {
					$("#popup_observaciones_tritio").dialog("close");
					EMISIONES_7923[ID_TRITIO_CONC_EDITANDO].observaciones = observaciones;
				} else {
					alert("⚠️ Error al guardar: " + respuesta);
				}
			});
		}

		function guardar_valores_gases_nobles() {

			var guardar_editar = $("#guardar_editar_gases_nobles").val()
			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var fecha = $("#cargar_datos_gases_nobles_fecha").val()
			var desde = $("#cargar_datos_gases_nobles_desde").val()
			var hasta = $("#cargar_datos_gases_nobles_hasta").val()
			var ar41 = $("#cargar_datos_gases_nobles_ar41").val()
			var kr85 = $("#cargar_datos_gases_nobles_kr85").val()
			var kr85m = $("#cargar_datos_gases_nobles_kr85m").val()
			var xe133 = $("#cargar_datos_gases_nobles_xe133").val()
			var xe135 = $("#cargar_datos_gases_nobles_xe135").val()
			var xe135m = $("#cargar_datos_gases_nobles_xe135m").val()

			if (desde == "") {
				alert("Debe introducir un horario")
			} else if (hasta == "") {
				alert("Debe introducir un horario")
			} else if (ar41 == "" && kr85 == "" && kr85m == "" && xe133 == "" && xe135 == "" || xe135m == "") {
				alert("Debe introducir al menos un valor de 0")
			} else {
				var url = "procesos_reg_rad.php?accion=" + guardar_editar + "_valores_gases_nobles&id_planilla=" + id_planilla + "&id_usuario=" + id_usuario + "&desde=" + desde + "&hasta=" + hasta + "&ar41=" + ar41 + "&kr85=" + kr85 + "&kr85m=" + kr85m + "&xe133=" + xe133 + "&xe135=" + xe135 + "&xe135m=" + xe135m + "&fecha=" + fecha
				$.get(url, function(data) {
					if (data == "ok") {
						recuperar_valores_gases_nobles(id_planilla)
						$("#popup_carga_gases_nobles").dialog("close")
						$("#guardar_editar_gases_nobles").val('guardar')
					} else {
						alert("Ha ocurrido un error!")
						$("#popup_carga_gases_nobles").dialog("close")
					}
				})

			}
		}

		function guardar_valores_emision_tritio() {

			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var fecha = $("#cargar_datos_emision_tritio_fecha").val()
			var desde = $("#cargar_datos_emision_tritio_desde").val()
			var hasta = $("#cargar_datos_emision_tritio_hasta").val()
			var vent_gral = $("#cargar_datos_emision_tritio_vent_gral").val()
			var vent_reactor = $("#cargar_datos_emision_tritio_vent_reactor").val()
			var vent_reactor_unidad_actividad = $("#cargar_datos_emision_tritio_vent_reactor_unidad_actividad").val()
			var vent_gral_unidad_actividad = $("#cargar_datos_emision_tritio_vent_gral_unidad_actividad").val()

			if (desde == "") {
				alert("Debe introducir un horario")
			} else if (hasta == "") {
				alert("Debe introducir un horario")
			} else if (vent_gral == "" && vent_reactor == "") {
				alert("Debe introducir al menos uno de los dos valores")
			} else {
				var url = "procesos_reg_rad.php?accion=guardar_valores_emision_tritio&id_planilla=" + id_planilla + "&id_usuario=" + id_usuario + "&desde=" + desde + "&hasta=" + hasta + "&vent_gral=" + vent_gral + "&vent_reactor=" + vent_reactor + "&fecha=" + fecha + "&vent_reactor_unidad_actividad=" + vent_reactor_unidad_actividad + "&vent_gral_unidad_actividad=" + vent_gral_unidad_actividad
				$.get(url, function(data) {
					if (data == "ok") {
						recuperar_valores_emision_tritio(id_planilla)
						$("#popup_carga_emision_tritio").dialog("close")
					} else {
						alert(data)
						$("#popup_carga_emision_tritio").dialog("close")
					}
				})

			}
		}

		function guardar_valores_carbono14() {

			var guardar_editar = $("#guardar_editar_carbono14").val()
			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var fecha = $("#cargar_datos_carbono14_fecha").val()
			var hora = $("#cargar_datos_carbono14_hora").val()
			var actividad = $("#cargar_datos_carbono14_actividad").val()
			var actividad_unidad_ingresada = $("#cargar_datos_carbono14_actividad_unidad_ingresada").val()
			if (hora == "") {
				alert("Debe introducir un horario")
			} else if (actividad == "") {
				alert("Debe introducir una actividad")
			} else {
				var url = "procesos_reg_rad.php?accion=" + guardar_editar + "_valores_carbono14&id_planilla=" + id_planilla + "&id_usuario=" + id_usuario + "&hora=" + hora + "&actividad=" + actividad + "&fecha=" + fecha + "&actividad_unidad_ingresada=" + actividad_unidad_ingresada
				$.get(url, function(data) {
					recuperar_valores_carbono14(id_planilla)
					$("#popup_carga_carbono14").dialog('close')
					$("#guardar_editar_carbono14").val('guardar')
				})
			}
		}

		function guardar_valores_dedo_frio() {

			var guardar_editar = $("#guardar_editar_dedo_frio").val()
			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var fecha = $("#cargar_datos_dedo_frio_fecha").val()
			var hora = $("#cargar_datos_dedo_frio_hora").val()
			var ppm = $("#cargar_datos_dedo_frio_ppm").val()
			var menor_que = 0
			if ($('#cargar_datos_dedo_frio_menor_que').prop('checked')) {
				menor_que = 1
			}
			if (hora == "") {
				alert("Debe introducir un horario")
			} else if (ppm == "") {
				alert("Debe introducir una ppm")
			} else {
				var url = "procesos_reg_rad.php?accion=" + guardar_editar + "_valores_dedo_frio&id_planilla=" + id_planilla + "&id_usuario=" + id_usuario + "&hora=" + hora + "&ppm=" + ppm + "&fecha=" + fecha + "&menor_que=" + menor_que
				$.get(url, function(data) {
					recuperar_valores_dedo_frio(id_planilla)
					$("#popup_carga_dedo_frio").dialog('close')
				})
			}
		}

		function guardar_valores_concentrador7923() {

			var guardar_editar = $("#guardar_editar_concentrador7923").val()
			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var fecha = $("#cargar_datos_concentrador7923_fecha").val()
			var procedencia = $("#cargar_datos_concentrador7923_procedencia").val()
			var actividad = $("#cargar_datos_concentrador7923_actividad").val()
			var actividad_unidad_ingresada = $("#cargar_datos_concentrador7923_actividad_unidad_ingresada").val()
			var volumen = $("#cargar_datos_concentrador7923_volumen").val()
			var volumen_unidad_ingresada = $("#cargar_datos_concentrador7923_volumen_unidad_ingresada").val()
			var ldd = $("#cargar_datos_concentrador7923_ldd").val()
			if (procedencia == "") {
				alert("Debe introducir una procedencia")
			} else if (actividad == "") {
				alert("Debe introducir una actividad")
			} else if (volumen == "") {
				alert("Debe introducir un volumen")
			} else if (ldd == "") {
				alert("Debe introducir un RoDD")
			} else {
				var url = "procesos_reg_rad.php?accion=" + guardar_editar + "_valores_concentrador7923&id_planilla=" + id_planilla + "&id_usuario=" + id_usuario + "&fecha=" + fecha + "&procedencia=" + procedencia + "&actividad=" + actividad + "&volumen=" + volumen + "&ldd=" + ldd + "&actividad_unidad_ingresada=" + actividad_unidad_ingresada + "&volumen_unidad_ingresada=" + volumen_unidad_ingresada
				$.get(url, function(data) {
					recuperar_valores_concentrador7923(id_planilla)
					$("#popup_carga_concentrador7923").dialog('close')
				})
			}
		}

		function guardar_emision_7923_prueba() {
			var id_emision = $("#id_emision_7923").val();
			var accion = "guardar_emision_7923";
			var id_planilla = '<?php echo $id_planilla ?>';
			var id_usuario = '<?php echo $usuario_id ?>';
			var fecha = $("#emision_7923_fecha").val();
			var hora = $("#emision_7923_hora").val();
			var procedencia = $("#emision_7923_procedencia").val();
			var volumen = $("#emision_7923_volumen").val();
			var unidad_vol = $("#emision_7923_unidad_volumen").val();

			if (!fecha) {
				console.warn("Debe introducir la fecha");
			} else if (!procedencia) {
				console.warn("Debe introducir una procedencia");
			} else if (!volumen) {
				console.warn("Debe introducir un volumen");
			} else {
				var url = "procesos_reg_rad.php?accion=" + encodeURIComponent(accion) +
					"&id_planilla=" + encodeURIComponent(id_planilla) +
					"&id_usuario=" + encodeURIComponent(id_usuario) +
					"&fecha=" + encodeURIComponent(fecha) +
					"&hora=" + encodeURIComponent(hora) +
					"&procedencia=" + encodeURIComponent(procedencia) +
					"&volumen=" + encodeURIComponent(volumen) +
					"&unidad_vol=" + encodeURIComponent(unidad_vol);

				if (id_emision) {
					url += "&id_emision_7923=" + encodeURIComponent(id_emision);
				}

				$.get(url, function(respuesta) {
					respuesta = respuesta.trim();
					if (respuesta === "ok") {
						console.log("Se guardó correctamente");
						// recuperar_valores_concentrador7923(id_planilla);
						$("#popup_carga_tritio_concentrador_7923").dialog('close')
						recuperar_emisiones_7923(id_planilla)
					} else {
						console.error("Error al guardar: ", respuesta);
						alert("La respuesta del servidor no fue OK: " + respuesta);
					}
				});
			}
		}

		function recuperar_emisiones_7923(id_planilla) {
			$.get("procesos_reg_rad.php", {
				accion: "recuperar_emisiones_7923",
				id_planilla: id_planilla
			}, function(respuesta) {
				if (respuesta.estado === "ok") {
					const total = respuesta.datos.length;
					let completadas = 0;

					respuesta.datos.forEach(emision => {
						const id_emision = emision.id;
						const volumen_litros = parseFloat(emision.volumen);
						EMISIONES_7923[id_emision] = emision;

						obtener_radionucleidos_de_emision(id_emision, function(radios) {
							const radios_calculados = (radios || []).map(r => {
								const id = r.id_radionucleido;
								const actividad = parseFloat(r.actividad_Bq_l);
								const calculo = calcular_datos_radionucleido(id, actividad, volumen_litros);
								return {
									...r,
									...calculo
								};
							});

							EMISIONES_7923[id_emision].datosRadionucleidos = radios_calculados;

							completadas++;
							if (completadas === total) {
								llenar_tabla_emisiones_7923(Object.values(EMISIONES_7923));
							}
						});
					});

					// Si no hay emisiones directamente
					if (total === 0) {
						$("#tabla_emisiones_7923 tbody").html("<tr><td colspan='8'>No hay datos disponibles</td></tr>");
						$('#concentrador7923_title').text("TRITIO CONCENTRADOR 7923 -sin cargar-")

					}

				} else {
					$("#tabla_emisiones_7923 tbody").html("<tr><td colspan='8'>No hay datos disponibles</td></tr>");
					$('#concentrador7923_title').text("TRITIO CONCENTRADOR 7923 -sin cargar-")

				}
			}, "json");
		}

		function obtener_radionucleidos_de_emision(id_tritio_conc, callback) {
			$.get("procesos_reg_rad.php", {
				accion: "radionucleidos_de_emision",
				id_conc: id_tritio_conc
			}, function(respuesta) {
				if (respuesta.estado === "ok") {
					// console.log("🔬 Radionucleidos recuperados:", respuesta.datos);
					if (callback) callback(respuesta.datos);
				} else {
					// console.warn("❌ No se pudo recuperar radionucleidos");
					if (callback) callback([]);
				}
			}, 'json');
		}

		function mostrar(valor, sufijo = "") {
			return (valor !== undefined && valor !== null && valor !== "") ? valor + sufijo : "-";
		}

		function formatear_fecha(fecha) {
			if (!fecha || typeof fecha !== "string") return "-";
			const partes = fecha.split("-");
			return (partes.length === 3) ? `${partes[2]}/${partes[1]}/${partes[0]}` : fecha;
		}

		function formatear_hora(horaCompleta) {
			if (!horaCompleta || typeof horaCompleta !== "string") return "-";
			const hora = horaCompleta.split(" ")[1]; // "HH:mm:ss"
			return hora ? hora.substring(0, 5) : "-"; // toma solo "HH:mm"
		}

		const tbody = $("#tabla_emisiones_7923 tbody");

		function llenar_tabla_emisiones_7923(data) {
			tbody.empty();

			data.forEach(function(item) {
				const fecha_formateada = formatear_fecha(item.fecha);
				const hora_formateada = formatear_hora(item.fecha_hora);
				let total_tbq = "-";
				let total_ldd = "-";

				// Si tiene datos de radionucleidos
				if (EMISIONES_7923[item.id] && EMISIONES_7923[item.id].datosRadionucleidos) {
					const lista = EMISIONES_7923[item.id].datosRadionucleidos;

					total_tbq = lista.reduce((acc, r) => acc + (r.actividad_total_Tbq || 0), 0);
					total_ldd = lista.reduce((acc, r) => acc + (r.porcentaje_ldd || 0), 0);

					// Redondear los valores
					total_tbq = total_tbq.toExponential(3);
					total_ldd = total_ldd.toFixed(3);
				}
				const fila = `
                        <tr>
                            <td>${fecha_formateada}</td>
                            <td>${hora_formateada}</td>
                            <td>${mostrar(item.procedencia)}</td>
                            <td>${item.volumen ? parseFloat(item.volumen).toFixed(3) : "-"}</td>
                            <td>${total_tbq}</td>
                            <td>${total_ldd} %</td>
                            <td>${mostrar(item.rodd, ' %')}</td>
                            <td>
                                <img src="Images/add.png" style="width: 25px;vertical-align: middle;cursor: pointer" title="Agregar Radionucleidos" onclick="abrir_popup_radionucleidos(${item.id},${item.volumen})">
                                <img src="Images/icon_observaciones_efluentes.png" style="max-height: 25px;: 25px;vertical-align: middle;cursor: pointer" title="Editar Observaciones" onclick="abrir_popup_observaciones(${item.id})">
                                <img src="Images/print.png" style="width: 25px;vertical-align: middle;cursor: pointer" title="Imprimir planilla emisión" onclick="imprimir_planilla_emision(${item.id})">
                                <img src="Images/icon_delete.png" style="width: 25px;vertical-align: middle;cursor: pointer" title="Eliminar Datos" onclick="confirmar_borrado_emision(${item.id})">
                            </td>
                        </tr>
                    `;
				tbody.append(fila);
			});

			// Aplicar clases myodd/myeven
			$("#tabla_emisiones_7923 tbody tr").each(function(index) {
				$(this).removeClass('myodd myeven');
				$(this).addClass(index % 2 === 0 ? 'myodd' : 'myeven');
			});
		}

		function imprimir_planilla_emision(id_emision) {
			const datosOriginales = EMISIONES_7923[id_emision];

			const ordenados = [...datosOriginales.datosRadionucleidos].sort((a, b) => {
				const getPartes = (nombre) => {
					const match = nombre.match(/^([A-Za-z]+)-?(\d+)?$/);
					return {
						letra: match?.[1] || '',
						numero: match?.[2] ? parseInt(match[2]) : Infinity
					};
				};

				const A = getPartes(a.radionucleido);
				const B = getPartes(b.radionucleido);

				if (A.numero !== B.numero) {
					return A.numero - B.numero;
				}
				return A.letra.localeCompare(B.letra);
			});

			const datos = {
				id: id_emision,
				datos: {
					...datosOriginales,
					datosRadionucleidos: ordenados
				},
				turno: TURNO_OFICIAL
			};

			const form = document.createElement('form');
			form.method = 'POST';
			form.action = '../TCPDF-master/examples/pdf_impresion_emision_7923_tcpdf.php';
			form.target = '_blank';

			const input = document.createElement('input');
			input.type = 'hidden';
			input.name = 'json';
			input.value = JSON.stringify(datos);

			form.appendChild(input);
			document.body.appendChild(form);
			form.submit();
			document.body.removeChild(form);
		}

		function confirmar_borrado_emision(id_emision) {
			$("#popup_confirmar_borrado_emision").dialog({
				resizable: false,
				width: 400,
				modal: true,
				buttons: {
					"Eliminar": function() {
						$(this).dialog("close");
						eliminar_emision_7923(id_emision);
					},
					"Cancelar": function() {
						$(this).dialog("close");
					}
				}
			});
		}

		function eliminar_emision_7923(id_emision) {
			$.get("procesos_reg_rad.php", {
				accion: "eliminar_emision_7923",
				id_emision_7923: id_emision
			}, function(resp) {
				console.log('respuesta: ' + resp);
				if (resp === "ok") {
					alert("Emisión eliminada correctamente");
					delete EMISIONES_7923[id_emision];
					recuperar_emisiones_7923(id_planilla); // o pasá el ID desde variable
				} else {
					alert("Ocurrió un error al eliminar.");
				}
			});
		}


		function guardar_valores_condicion_meteorologica() {

			var guardar_editar = $("#guardar_editar_condicion_meteorologica").val()
			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var fecha = $("#cargar_datos_condicion_meteorologica_fecha").val()
			var hora = $("#cargar_datos_condicion_meteorologica_hora").val()
			var velocidad_viento = $("#cargar_datos_condicion_meteorologica_velocidad_viento").val()
			var direccion_viento = $("#cargar_datos_condicion_meteorologica_direccion_viento").val()
			var condicion_pasquill = $("#cargar_datos_condicion_meteorologica_condicion_pasquill").val()
			if (hora == "") {
				alert("Debe introducir un horario")
			} else if (velocidad_viento == "") {
				alert("Debe introducir algún valor en todos los campos")
			} else if (direccion_viento == "") {
				alert("Debe introducir algún valor en todos los campos")
			} else if (condicion_pasquill == "") {
				alert("Debe introducir algún valor en todos los campos")
			} else {
				var url = "procesos_reg_rad.php?accion=" + guardar_editar + "_valores_condicion_meteorologica&id_planilla=" + id_planilla + "&id_usuario=" + id_usuario + "&fecha=" + fecha + "&hora=" + hora + "&velocidad_viento=" + velocidad_viento + "&direccion_viento=" + direccion_viento + "&condicion_pasquill=" + condicion_pasquill
				$.get(url, function(data) {
					recuperar_valores_condicion_meteorologica(id_planilla)
					$("#popup_carga_condicion_meteorologica").dialog('close')
				})
			}
		}

		function guardar_valores_espectrometria_aire() {

			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var fecha = $("#cargar_datos_espectrometria_aire_fecha").val()
			var hora = $("#cargar_datos_espectrometria_aire_hora").val()
			var tipo = $("#cargar_datos_espectrometria_aire_tipo").val()
			var recinto = $("#cargar_datos_espectrometria_aire_recinto").val()
			var ar41 = $("#cargar_datos_espectrometria_aire_ar41").val()
			var xe133 = $("#cargar_datos_espectrometria_aire_xe133").val()
			var xe135 = $("#cargar_datos_espectrometria_aire_xe135").val()
			var kr88 = $("#cargar_datos_espectrometria_aire_kr88").val()
			if (tipo == "") {
				alert("Debe introducir un tipo de concentración")
			} else if (hora == "") {
				alert("Debe introducir algún valor en todos los campos")
			} else if (recinto == "-") {
				alert("Debe seleccionar un recinto")
			} else if (ar41 == "" && xe133 == "" && xe135 == "" && kr88 == "") {
				alert("Debe introducir algún valor")
			} else {
				var url = "procesos_reg_rad.php?accion=guardar_valores_espectrometria_aire&id_planilla=" + id_planilla + "&id_usuario=" + id_usuario + "&fecha=" + fecha + "&hora=" + hora + "&tipo=" + tipo + "&recinto=" + recinto + "&ar41=" + ar41 + "&xe133=" + xe133 + "&xe135=" + xe135 + "&kr88=" + kr88 + "&id_usuario=" + id_usuario
				$.get(url, function(data) {
					recuperar_valores_espectrometria_aire(id_planilla)
					$("#popup_carga_espectrometria_aire").dialog('close')
				})
			}
		}

		function guardar_valores_control_precintos() {

			var guardar_editar = $("#guardar_editar_control_precintos").val()
			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var fecha = $("#cargar_datos_control_precintos_fecha").val()
			var hora = $("#cargar_datos_control_precintos_hora").val()
			var puerta_s_123b = $("#cargar_datos_control_precintos_puerta_s_123b").val()
			var puerta_s_008 = $("#cargar_datos_control_precintos_puerta_s_008").val()
			var puerta_s_144 = $("#cargar_datos_control_precintos_puerta_s_144").val()
			if (hora == "") {
				alert("Debe introducir un horario")
			} else if (puerta_s_123b == "") {
				alert("Debe introducir algún valor")
			} else if (puerta_s_008 == "") {
				alert("Debe introducir algún valor")
			} else if (puerta_s_144 == "") {
				alert("Debe introducir algún valor")
			} else {
				var url = "procesos_reg_rad.php?accion=" + guardar_editar + "_valores_control_precintos&id_planilla=" + id_planilla + "&id_usuario=" + id_usuario + "&fecha=" + fecha + "&hora=" + hora + "&puerta_s_123b=" + puerta_s_123b + "&puerta_s_008=" + puerta_s_008 + "&puerta_s_144=" + puerta_s_144
				$.get(url, function(data) {
					recuperar_valores_control_precintos(id_planilla)
					$("#popup_carga_control_precintos").dialog('close')
				})
			}
		}

		function guardar_valores_efluentes_liquidos() {

			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var fecha = $("#cargar_datos_efluentes_liquidos_fecha").val()
			var hora = $("#cargar_datos_efluentes_liquidos_hora").val()
			var tanque = $("#cargar_datos_efluentes_liquidos_tanque").val()
			var volumen = $("#cargar_datos_efluentes_liquidos_volumen").val()
			var unidad_ingresada = $("#cargar_datos_efluentes_liquidos_unidad_volumen_ingresada").val()
			if (hora == "") {
				alert("Debe introducir un horario")
			} else if (tanque == "") {
				alert("Debe introducir algún valor")
			} else if (volumen == "") {
				alert("Debe introducir algún valor")
			} else if (unidad_ingresada == "-") {
				alert("Debe seleccionar una unidad de volumen")
			} else {
				var url = "procesos_reg_rad.php?accion=guardar_valores_efluentes_liquidos&id_planilla=" + id_planilla + "&id_usuario=" + id_usuario + "&fecha=" + fecha + "&hora=" + hora + "&tanque=" + tanque + "&volumen=" + volumen + "&unidad_ingresada=" + unidad_ingresada
				$.get(url, function(data) {
					recuperar_valores_efluentes_liquidos(id_planilla)
					$("#popup_carga_efluentes_liquidos").dialog('close')
				})
			}
		}

		function guardar_valores_contaminacion_superficial() {

			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var fecha = $("#cargar_datos_contaminacion_superficial_fecha").val()
			var hora = $("#cargar_datos_contaminacion_superficial_hora").val()
			var recinto = $("#cargar_datos_contaminacion_superficial_recinto").val()
			var beta_gamma = $("#cargar_datos_contaminacion_superficial_beta_gamma").val()
			var alfa = $("#cargar_datos_contaminacion_superficial_alfa").val()
			var tasa_dosis = $("#cargar_datos_contaminacion_superficial_dosis_equivalente").val()
			var tasa_dosis_unidad_ingresada = $("#cargar_datos_contaminacion_superficial_unidad_dosis_equivalente").val()
			var beta_gamma_unidad_ingresada = $("#cargar_datos_contaminacion_superficial_unidad_beta_gamma").val()
			var alfa_unidad_ingresada = $("#cargar_datos_contaminacion_superficial_unidad_alfa").val()
			if (hora == "") {
				alert("Debe introducir un horario")
			} else if (recinto == "-") {
				alert("Debe seleccionar un recinto")
			} else if (beta_gamma == "" && alfa == "" && tasa_dosis == "") {
				alert("Debe introducir algún valor")
			} else {
				var url = "procesos_reg_rad.php?accion=guardar_valores_contaminacion_superficial&id_planilla=" + id_planilla + "&id_usuario=" + id_usuario + "&fecha=" + fecha + "&hora=" + hora + "&beta_gamma=" + beta_gamma + "&alfa=" + alfa + "&tasa_dosis=" + tasa_dosis + "&tasa_dosis_unidad_ingresada=" + tasa_dosis_unidad_ingresada + "&beta_gamma_unidad_ingresada=" + beta_gamma_unidad_ingresada + "&alfa_unidad_ingresada=" + alfa_unidad_ingresada + "&recinto=" + recinto
				$.get(url, function(data) {
					recuperar_valores_contaminacion_superficial(id_planilla)
					$("#popup_carga_contaminacion_superficial").dialog('close')
				})
			}
		}

		function guardar_valores_eficiencia_dryers() {

			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var fecha = $("#cargar_datos_eficiencia_dryers_fecha").val()
			var hora = $("#cargar_datos_eficiencia_dryers_hora").val()
			var dryers = $("#cargar_datos_eficiencia_dryers_dryer").val()
			var entrada = $("#cargar_datos_eficiencia_dryers_entrada").val()
			var salida = $("#cargar_datos_eficiencia_dryers_salida").val()
			if (hora == "") {
				alert("Debe introducir un horario")
			} else if (dryers == "-") {
				alert("Debe seleccionar una opción")
			} else if (entrada == "") {
				alert("Debe introducir algún valor")
			} else if (salida == "") {
				alert("Debe introducir algún valor")
			} else {
				var url = "procesos_reg_rad.php?accion=guardar_valores_eficiencia_dryers&id_planilla=" + id_planilla + "&id_usuario=" + id_usuario + "&fecha=" + fecha + "&hora=" + hora + "&dryers=" + dryers + "&entrada=" + entrada + "&salida=" + salida
				$.get(url, function(data) {
					recuperar_valores_eficiencia_dryers(id_planilla)
					$("#popup_carga_eficiencia_dryers").dialog('close')
				})
			}
		}

		function actualizar_valores_radionucleidos_efluentes_liquidos() {

			var id_planilla = '<?php echo $id_planilla ?>'
			recuperar_valores_efluentes_liquidos(id_planilla)
			$("#popup_carga_radionucleidos_efluentes_liquidos").dialog('close')
		}

		function guardar_valores_radionucleidos_efluentes_liquidos() {
			var id_efluentes = $('#var_id_efluentes_para_carga_radionucleidos').val()
			var radionucleido = $('#agregar_datos_radionucleidos_select').val()
			var concentracion = $('#cargar_datos_concentracion_radionucleidos_efluentes_liquidos').val()
			var unidad_concentracion_ingresada = $('#cargar_datos_unidad_concentracion_radionucleidos_efluentes_liquidos').val()
			var url = "procesos_reg_rad.php?accion=guardar_valores_radionucleidos_efluentes_liquidos&id_efluentes=" + id_efluentes + "&radionucleido=" + radionucleido + "&concentracion=" + concentracion + "&unidad_concentracion_ingresada=" + unidad_concentracion_ingresada
			if (radionucleido == "-") {
				alert("Debe seleccionar un radionucleido")
			} else if (concentracion == "") {
				alert("Debe introducir un valor en concentracion")
			} else if (unidad_concentracion_ingresada == "-") {
				alert("Debe elegir una unidad de concentración")
			} else {
				$.get(url, function(data) {
					console.log(data);
					$("#popup_carga_radionucleidos_efluentes_liquidos_agregar").dialog('close')
					recuperar_datos_tabla_radionucleidos_efluentes_liquidos(id_efluentes)
				})
			}
		}

		function edicion_condicion_meteorologica() {
			$("#guardar_editar_condicion_meteorologica").val('editar')
			abrir_popup_carga_condicion_meteorologica()
			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var url1 = "procesos_reg_rad.php?accion=recuperar_valores_condicion_meteorologica&id_planilla=" + id_planilla
			$.get(url1, function(data) {
				var array = data.split('@')
				$('#cargar_datos_condicion_meteorologica_fecha').val(array[4])
				$('#cargar_datos_condicion_meteorologica_hora').val(array[0])
				$('#cargar_datos_condicion_meteorologica_velocidad_viento').val(array[1])
				$('#cargar_datos_condicion_meteorologica_direccion_viento').val(array[2])
				$('#cargar_datos_condicion_meteorologica_condicion_pasquill').val(array[3])
			})
		}

		function edicion_control_precintos() {
			$("#guardar_editar_control_precintos").val('editar')
			abrir_popup_carga_control_precintos()
			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var url1 = "procesos_reg_rad.php?accion=recuperar_valores_control_precintos&id_planilla=" + id_planilla
			$.get(url1, function(data) {
				var array = data.split('@')
				$('#cargar_datos_control_precintos_fecha').val(array[4])
				$('#cargar_datos_control_precintos_hora').val(array[0])
				$('#cargar_datos_control_precintos_puerta_s_123b').val(array[1])
				$('#cargar_datos_control_precintos_puerta_s_008').val(array[2])
				$('#cargar_datos_control_precintos_puerta_s_144').val(array[3])
			})
		}

		function edicion_tritio_concentrador7923() {
			$("#guardar_editar_concentrador7923").val('editar')
			abrir_popup_carga_concentrador7923()
			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var url1 = "procesos_reg_rad.php?accion=recuperar_valores_concentrador7923&id_planilla=" + id_planilla
			$.get(url1, function(data) {
				var array = data.split('@')
				$('#cargar_datos_concentrador7923_fecha').val(array[4])
				$('#cargar_datos_concentrador7923_procedencia').val(array[0])
				$('#cargar_datos_concentrador7923_actividad').val(array[6])
				$('#cargar_datos_concentrador7923_volumen').val(array[7])
				$('#cargar_datos_concentrador7923_ldd').val(array[8])
			})
		}

		function edicion_dedo_frio() {
			$("#guardar_editar_dedo_frio").val('editar')
			abrir_popup_carga_dedo_frio()
			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var url1 = "procesos_reg_rad.php?accion=recuperar_valores_dedo_frio&id_planilla=" + id_planilla
			$.get(url1, function(data) {
				var array = data.split('@')
				if (array[2] == 1) {
					$('#cargar_datos_dedo_frio_menor_que').prop('checked', true)
				} else {
					$('#cargar_datos_dedo_frio_menor_que').prop('checked', false)
				}
				$('#cargar_datos_dedo_frio_fecha').val(array[3])
				$('#cargar_datos_dedo_frio_hora').val(array[0])
				$('#cargar_datos_dedo_frio_ppm').val(array[1])
			})
		}

		function edicion_carbono14() {
			$("#guardar_editar_carbono14").val('editar')
			abrir_popup_carga_carbono14()
			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var url1 = "procesos_reg_rad.php?accion=recuperar_valores_carbono14&id_planilla=" + id_planilla
			$.get(url1, function(data) {
				var array = data.split('@')
				$('#cargar_datos_carbono14_fecha').val(array[2])
				$('#cargar_datos_carbono14_hora').val(array[0])
				$('#cargar_datos_carbono14_actividad').val(array[1])
			})
		}

		function edicion_gases_nobles() {
			$("#guardar_editar_gases_nobles").val('editar')
			abrir_popup_carga_gases_nobles()
			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var url1 = "procesos_reg_rad.php?accion=recuperar_valores_gases_nobles&id_planilla=" + id_planilla
			$.get(url1, function(data) {
				var array = data.split('@')
				$('#cargar_datos_gases_nobles_fecha').val(array[9])
				$('#cargar_datos_gases_nobles_desde').val(array[10])
				$('#cargar_datos_gases_nobles_hasta').val(array[11])
				$('#cargar_datos_gases_nobles_ar41').val(array[12])
				$('#cargar_datos_gases_nobles_kr85').val(array[13])
				$('#cargar_datos_gases_nobles_kr85m').val(array[14])
				$('#cargar_datos_gases_nobles_xe133').val(array[15])
				$('#cargar_datos_gases_nobles_xe135').val(array[16])
				$('#cargar_datos_gases_nobles_xe135m').val(array[17])
			})
		}

		function recuperar_valores_gases_nobles(id_planilla) {
			var url = "procesos_reg_rad.php?accion=recuperar_valores_gases_nobles&id_planilla=" + id_planilla
			$.get(url, function(data) {
				if (data == "sin_datos") {
					$('#gases_nobles_title').text("Gases Nobles -sin cargar-")
					$("#datos_cargados_gases_nobles").hide()
					$("#div_boton_agregar_datos_gases_nobles").show()
				} else {
					$('#gases_nobles_title').text("Gases Nobles")
					var new_array = data.split("@")
					var desde = $("#mostrar_gases_nobles_desde").text(new_array[0])
					var hasta = $("#mostrar_gases_nobles_hasta").text(new_array[1])
					var ar41 = $("#mostrar_gases_nobles_ar41").text(new_array[2])
					var kr85 = $("#mostrar_gases_nobles_kr85").text(new_array[3])
					var kr85m = $("#mostrar_gases_nobles_kr85m").text(new_array[4])
					var xe133 = $("#mostrar_gases_nobles_xe133").text(new_array[5])
					var xe135 = $("#mostrar_gases_nobles_xe135").text(new_array[6])
					var xe135m = $("#mostrar_gases_nobles_xe135m").text(new_array[7])
					var id_gases_nobles = $("#id_datos_cargados_gases_nobles").val(new_array[18])

					$("#datos_cargados_gases_nobles").show()
					$("#div_boton_agregar_datos_gases_nobles").hide()
				}
			})
		}

		function recuperar_valores_emision_tritio(id_planilla) {
			var url = "procesos_reg_rad.php?accion=recuperar_valores_emision_tritio&id_planilla=" + id_planilla
			$.get(url, function(data) {
				if (data == "sin_datos") {
					$('#emision_tritio_title').text("Emisión Tritio -sin cargar-")
					var t = $('#tabla_emision_tritio').DataTable()
					t.clear()
					t.draw()
					$("#datos_cargados_emision_tritio").hide()
				} else {
					$('#emision_tritio_title').text("Emisión Tritio")
					agregar_fila_emision_tritio(data)
					$("#datos_cargados_emision_tritio").show()
				}
			})
		}

		function recuperar_datos_tabla_radionucleidos_efluentes_liquidos(id_efluentes) {
			var url = "procesos_reg_rad.php?accion=recuperar_valores_radionucleidos_efluentes_liquidos&id_efluentes=" + id_efluentes
			$('#var_id_efluentes_para_carga_radionucleidos').val(id_efluentes)
			$.get(url, function(data) {
				insertar_fila_tabla_radionucleidos_efluentes_liquidos(data, id_efluentes)
			})
		}

		function recuperar_valores_efluentes_liquidos(id_planilla) {
			var url = "procesos_reg_rad.php?accion=recuperar_valores_efluentes_liquidos&id_planilla=" + id_planilla
			$.get(url, function(data) {
				if (data == "sin_datos") {
					$('#datos_efluentes_liquidos_sin_cargar').text("-sin cargar-")
					var t = $('#tabla_efluentes_liquidos').DataTable()
					t.clear()
					t.draw()
					$("#datos_cargados_efluentes_liquidos").hide()
				} else {
					$('#datos_efluentes_liquidos_sin_cargar').text("")
					agregar_fila_efluentes_liquidos(data)
					$("#datos_cargados_efluentes_liquidos").show()
				}
			})
		}

		function recuperar_valores_eficiencia_dryers(id_planilla) {
			var url = "procesos_reg_rad.php?accion=recuperar_valores_eficiencia_dryers&id_planilla=" + id_planilla
			$.get(url, function(data) {
				if (data == "sin_datos") {
					$('#datos_eficiencia_dryers_sin_cargar').text("-sin cargar-")
					var t = $('#tabla_eficiencia_dryers').DataTable()
					t.clear()
					t.draw()
					$("#datos_cargados_eficiencia_dryers").hide()
				} else {
					$('#datos_eficiencia_dryers_sin_cargar').text("")
					agregar_fila_eficiencia_dryers(data)
					$("#datos_cargados_eficiencia_dryers").show()
				}
			})
		}

		function recuperar_valores_contaminacion_superficial(id_planilla) {
			var url = "procesos_reg_rad.php?accion=recuperar_valores_contaminacion_superficial&id_planilla=" + id_planilla
			$.get(url, function(data) {
				if (data == "sin_datos") {
					$('#datos_contaminacion_superficial_sin_cargar').text("-sin cargar-")
					var t = $('#tabla_contaminacion_superficial').DataTable()
					t.clear()
					t.draw()
					$("#datos_cargados_contaminacion_superficial").hide()
				} else {
					$('#datos_contaminacion_superficial_sin_cargar').text("")
					agregar_fila_contaminacion_superficial(data)
					$("#datos_cargados_contaminacion_superficial").show()
				}
			})
		}

		function recuperar_valores_espectrometria_aire(id_planilla) {
			var url = "procesos_reg_rad.php?accion=recuperar_valores_espectrometria_aire&id_planilla=" + id_planilla
			$.get(url, function(data) {

				if (data == "sin_datos") {
					$('#datos_espectrometria_aire_sin_cargar').show()
					var t = $('#tabla_espectrometria_aire').DataTable()
					t.clear()
					t.draw()
					$("#datos_cargados_espectrometria_aire").hide()
				} else {
					$('#datos_espectrometria_aire_sin_cargar').hide()
					agregar_fila_espectrometria_aire(data)
					$("#datos_cargados_espectrometria_aire").show()
				}
			})
		}

		function recuperar_valores_carbono14(id_planilla) {
			var url = "procesos_reg_rad.php?accion=recuperar_valores_carbono14&id_planilla=" + id_planilla
			$.get(url, function(data) {
				if (data == "sin_datos") {
					$('#carbono14_title').text("Carbono 14 -sin cargar-")
					$("#datos_cargados_carbono14").hide()
					$("#div_boton_agregar_datos_carbono14").show()
				} else {
					$('#carbono14_title').text("Carbono 14")
					var new_array = data.split("@")
					var hora = $("#mostrar_carbono14_hora").text(new_array[0])
					var actividad = $("#mostrar_carbono14_actividad").text(new_array[1])
					$('#id_datos_cargados_carbono14').val(new_array[3])
					$("#datos_cargados_carbono14").show()
					$("#div_boton_agregar_datos_carbono14").hide()
				}
			})
		}

		function recuperar_valores_dedo_frio(id_planilla) {
			var url = "procesos_reg_rad.php?accion=recuperar_valores_dedo_frio&id_planilla=" + id_planilla
			$.get(url, function(data) {
				if (data == "sin_datos") {
					$('#dedo_frio_title').text("Dedo Frio 7312 -sin cargar-")
					$("#datos_cargados_dedo_frio").hide()
					$("#div_boton_agregar_datos_dedo_frio").show()
				} else {
					$('#dedo_frio_title').text("Dedo Frio 7312")
					var new_array = data.split("@")
					if (new_array[2] == 1) {
						var valor_ppm_con_signo = "< " + new_array[1]
					} else {
						var valor_ppm_con_signo = new_array[1]
					}
					var hora = $("#mostrar_dedo_frio_hora").text(new_array[0])
					var ppm = $("#mostrar_dedo_frio_ppm").text(valor_ppm_con_signo)
					$('#id_datos_cargados_dedo_frio').val(new_array[4])
					$("#datos_cargados_dedo_frio").show()
					$("#div_boton_agregar_datos_dedo_frio").hide()
				}
			})
		}

		function recuperar_valores_concentrador7923(id_planilla) {
			var url = "procesos_reg_rad.php?accion=recuperar_valores_concentrador7923&id_planilla=" + id_planilla
			$.get(url, function(data) {
				if (data == "sin_datos") {
					$('#concentrador7923_title').text("TRITIO CONCENTRADOR 7923 -sin cargar-")
					$("#datos_cargados_concentrador7923").hide()
					$("#div_boton_agregar_datos_concentrador7923").show()
				} else {
					$('#concentrador7923_title').text("TRITIO CONCENTRADOR 7923")
					var new_array = data.split("@")
					var procedencia = $("#mostrar_concentrador7923_procedencia").text(new_array[0])
					var actividad = $("#mostrar_concentrador7923_actividad").text(new_array[1])
					var volumen = $("#mostrar_concentrador7923_volumen").text(new_array[2])
					var ldd = $("#mostrar_concentrador7923_ldd").text(new_array[3])
					$('#id_datos_cargados_concentrador7923').val(new_array[5])
					$("#datos_cargados_concentrador7923").show()
					$("#div_boton_agregar_datos_concentrador7923").hide()
				}
			})
		}

		function recuperar_valores_concentrador7923_prueba(id_planilla) {
			var url = "procesos_reg_rad.php?accion=recuperar_valores_concentrador7923&id_planilla=" + id_planilla
			$.get(url, function(data) {
				console.log(data);
				if (data == "sin_datos") {
					$('#concentrador7923_title').text("Tritio Concentrador 7923 -sin cargar-")
					$("#datos_cargados_concentrador7923").hide()
					$("#div_boton_agregar_datos_concentrador7923").show()
				} else {
					// $('#concentrador7923_title').text("Tritio Concentrador 7923")
					// var new_array = data.split("@")
					// var procedencia = $("#mostrar_concentrador7923_procedencia").text(new_array[0])
					// var actividad = $("#mostrar_concentrador7923_actividad").text(new_array[1])
					// var volumen = $("#mostrar_concentrador7923_volumen").text(new_array[2])
					// var ldd = $("#mostrar_concentrador7923_ldd").text(new_array[3])
					// $('#id_datos_cargados_concentrador7923').val(new_array[5])
					// $("#datos_cargados_concentrador7923").show()
					// $("#div_boton_agregar_datos_concentrador7923").hide()
				}
			})
		}


		function recuperar_valores_control_precintos(id_planilla) {
			var url = "procesos_reg_rad.php?accion=recuperar_valores_control_precintos&id_planilla=" + id_planilla
			$.get(url, function(data) {
				if (data == "sin_datos") {
					$('#control_precintos_title').text("Control de Precintos -sin cargar-")
					$("#datos_cargados_control_precintos").hide()
					$("#div_boton_agregar_datos_control_precintos").show()
				} else {
					$('#control_precintos_title').text("Control de Precintos")
					var new_array = data.split("@")
					var hora = $("#mostrar_control_precintos_hora").text(new_array[0])
					var puerta_s_123b = $("#mostrar_control_precintos_puerta_s_123b").text(new_array[1])
					var puerta_s_008 = $("#mostrar_control_precintos_puerta_s_008").text(new_array[2])
					var puerta_s_144 = $("#mostrar_control_precintos_puerta_s_144").text(new_array[3])
					$('#id_datos_cargados_control_precintos').val(new_array[5])
					$("#datos_cargados_control_precintos").show()
					$("#div_boton_agregar_datos_control_precintos").hide()
				}
			})
		}

		function recuperar_valores_condicion_meteorologica(id_planilla) {
			var url = "procesos_reg_rad.php?accion=recuperar_valores_condicion_meteorologica&id_planilla=" + id_planilla
			$.get(url, function(data) {
				if (data == "sin_datos") {
					$('#condicion_meteorologica_title').text("Condición Meteorológica -sin cargar-")
					$("#guardar_editar_condicion_meteorologica").val('guardar')
					$("#datos_cargados_condicion_meteorologica").hide()
					$("#div_boton_agregar_datos_condicion_meteorologica").show()
				} else {
					$('#condicion_meteorologica_title').text("Condición Meteorológica")
					var new_array = data.split("@")
					var hora = $("#mostrar_condicion_meteorologica_hora").text(new_array[0])
					var velocidad_viento = $("#mostrar_condicion_meteorologica_velocidad_viento").text(new_array[1])
					var direccion_viento = $("#mostrar_condicion_meteorologica_direccion_viento").text(new_array[2])
					var condicion_pasquill = $("#mostrar_condicion_meteorologica_condicion_pasquill").text(new_array[3])
					$('#id_datos_cargados_condicion_meteorologica').val(new_array[5])
					$("#datos_cargados_condicion_meteorologica").show()
					$("#div_boton_agregar_datos_condicion_meteorologica").hide()
				}
			})
		}

		function actualizar_observaciones() {
			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var observaciones = $('#texto_campo_observaciones').val()
			var url = "procesos_reg_rad_post.php"
			$.post(url, {
				accion: "actualizar_observaciones",
				id_planilla: id_planilla,
				observaciones: observaciones,
				id_usuario: id_usuario
			})
			$("#popup_editar_observaciones").dialog('close')
		}

		function actualizar_libro_actas() {
			var id_planilla = '<?php echo $id_planilla ?>'
			var libro_actas = $('#texto_campo_libro_actas').val()
			var url = "procesos_reg_rad_post.php"
			$.post(url, {
				accion: "actualizar_libro_actas",
				id_planilla: id_planilla,
				libro_actas: libro_actas
			}) //.done(function( data ){alert(data)} )
			$("#popup_libro_actas").dialog('close')
		}

		function recuperar_observaciones() {
			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var observaciones = $('#texto_campo_observaciones').val()
			var url = "procesos_reg_rad.php?accion=recuperar_observaciones&id_planilla=" + id_planilla
			$.get(url, function(data) {
				$('#texto_campo_observaciones').val(data)
			})
		}

		function recuperar_libro_actas() {
			var id_planilla = '<?php echo $id_planilla ?>'
			var id_usuario = '<?php echo $usuario_id ?>'
			var libro_actas = $('#texto_campo_libro_actas').val()
			var url = "procesos_reg_rad.php?accion=recuperar_novedades_libro_actas&id_planilla=" + id_planilla + "&libro_actas=" + libro_actas
			$.get(url, function(data) {
				$('#texto_campo_libro_actas').val(data)
			})
			recuperar_datos_mediciones_para_libro_actas()
		}

		function recuperar_datos_mediciones_para_libro_actas() {
			var id_planilla = '<?php echo $id_planilla ?>'
			var url = "procesos_reg_rad.php?accion=recuperar_datos_planilla_diaria&id_planilla=" + id_planilla
			$.get(url, function(data) {
				var new_array = data.split("$$$");
				$('#popup_libro_actas_campo_vent_gral').text(new_array[3])
				$('#popup_libro_actas_campo_vent_reactor').text(new_array[4])
				$('#popup_libro_actas_campo_emision_c14').text(new_array[5])
				$('#popup_libro_actas_campo_dedo_frio').text(new_array[6])
			})
		}

		function editar_emision_tritio(id_emision_tritio) {
			var url = "procesos_reg_rad.php?accion=recuperar_valores_emision_tritio_para_edicion&id_emision_tritio=" + id_emision_tritio
			$.get(url, function(data) {
				var new_string = data.split('@')
				abrir_popup_carga_emision_tritio_editar()
				var fecha = $("#cargar_datos_emision_tritio_editar_fecha").val(new_string[0])
				var desde = $("#cargar_datos_emision_tritio_editar_desde").val(new_string[1])
				var hasta = $("#cargar_datos_emision_tritio_editar_hasta").val(new_string[2])
				var vent_gral = $("#cargar_datos_emision_tritio_editar_vent_gral").val(new_string[3])
				var vent_reactor = $("#cargar_datos_emision_tritio_editar_vent_reactor").val(new_string[4])
				$('#id_emision_tritio').val(id_emision_tritio)
			})
		}

		function editar_espectrometria_aire(id_espectrometria_aire) {
			var url = "procesos_reg_rad.php?accion=recuperar_valores_espectrometria_aire_para_edicion&id_espectrometria_aire=" + id_espectrometria_aire
			$.get(url, function(data) {
				var new_string = data.split('@')
				abrir_popup_carga_datos_espectrometria_aire_editar()
				var tipo = $("#cargar_datos_espectrometria_aire_editar_tipo").val(new_string[0])
				var recinto = $("#cargar_datos_espectrometria_aire_editar_recinto").val(new_string[1])
				var hora = $("#cargar_datos_espectrometria_aire_editar_hora").val(new_string[2])
				var ar41 = $("#cargar_datos_espectrometria_aire_editar_ar41").val(new_string[3])
				var xe133 = $("#cargar_datos_espectrometria_aire_editar_xe133").val(new_string[4])
				var xe135 = $("#cargar_datos_espectrometria_aire_editar_xe135").val(new_string[5])
				var kr88 = $("#cargar_datos_espectrometria_aire_editar_kr88").val(new_string[6])
				var fecha = $("#cargar_datos_espectrometria_aire_editar_fecha").val(new_string[8])
				$('#id_espectrometria_aire').val(id_espectrometria_aire)
			})
		}

		function actualizar_valores_emision_tritio_edicion() {
			var id_emision_tritio = $('#id_emision_tritio').val()
			var id_planilla = '<?php echo $id_planilla ?>'
			var fecha = $("#cargar_datos_emision_tritio_editar_fecha").val()
			var desde = $("#cargar_datos_emision_tritio_editar_desde").val()
			var hasta = $("#cargar_datos_emision_tritio_editar_hasta").val()
			var vent_gral = $("#cargar_datos_emision_tritio_editar_vent_gral").val()
			var vent_reactor = $("#cargar_datos_emision_tritio_editar_vent_reactor").val()
			var vent_gral_unidad_actividad = $("#cargar_datos_emision_tritio_editar_vent_gral_unidad_actividad").val()
			var vent_reactor_unidad_actividad = $("#cargar_datos_emision_tritio_editar_vent_reactor_unidad_actividad").val()
			var url = "procesos_reg_rad.php?accion=actualizar_valores_emision_tritio_edicion&id_emision_tritio=" + id_emision_tritio + "&fecha=" + fecha + "&desde=" + desde + "&hasta=" + hasta + "&vent_gral=" + vent_gral + "&vent_reactor=" + vent_reactor + "&vent_gral_unidad_actividad=" + vent_gral_unidad_actividad + "&vent_reactor_unidad_actividad=" + vent_reactor_unidad_actividad
			$.get(url, function(data) {
				recuperar_valores_emision_tritio(id_planilla)
				$("#popup_carga_emision_tritio_editar").dialog("close")
			})
		}

		function actualizar_valores_espectrometria_aire_edicion() {
			var id_espectrometria_aire = $('#id_espectrometria_aire').val()
			var id_planilla = '<?php echo $id_planilla ?>'
			var tipo = $("#cargar_datos_espectrometria_aire_editar_tipo").val()
			var recinto = $("#cargar_datos_espectrometria_aire_editar_recinto").val()
			var hora = $("#cargar_datos_espectrometria_aire_editar_hora").val()
			var ar41 = $("#cargar_datos_espectrometria_aire_editar_ar41").val()
			var xe133 = $("#cargar_datos_espectrometria_aire_editar_xe133").val()
			var xe135 = $("#cargar_datos_espectrometria_aire_editar_xe135").val()
			var kr88 = $("#cargar_datos_espectrometria_aire_editar_kr88").val()
			var fecha = $("#cargar_datos_espectrometria_aire_editar_fecha").val()
			var url = "procesos_reg_rad.php?accion=actualizar_valores_espectrometria_aire_edicion&id_espectrometria_aire=" + id_espectrometria_aire + "&fecha=" + fecha + "&hora=" + hora + "&tipo=" + tipo + "&recinto=" + recinto + "&ar41=" + ar41 + "&xe133=" + xe133 + "&xe135=" + xe135 + "&kr88=" + kr88
			$.get(url, function(data) {
				recuperar_valores_espectrometria_aire(id_planilla)
				$("#popup_carga_espectrometria_aire_editar").dialog("close")
			})
		}

		function cargar_radionucleidos_a_efluentes_liquidos() {
			var id_espectrometria_aire = $('#id_espectrometria_aire').val()
			var id_planilla = '<?php echo $id_planilla ?>'
			var tipo = $("#cargar_datos_espectrometria_aire_editar_tipo").val()
			var recinto = $("#cargar_datos_espectrometria_aire_editar_recinto").val()
			var hora = $("#cargar_datos_espectrometria_aire_editar_hora").val()
			var ar41 = $("#cargar_datos_espectrometria_aire_editar_ar41").val()
			var xe133 = $("#cargar_datos_espectrometria_aire_editar_xe133").val()
			var xe135 = $("#cargar_datos_espectrometria_aire_editar_xe135").val()
			var kr88 = $("#cargar_datos_espectrometria_aire_editar_kr88").val()
			var fecha = $("#cargar_datos_espectrometria_aire_editar_fecha").val()
			var url = "procesos_reg_rad.php?accion=actualizar_valores_espectrometria_aire_edicion&id_espectrometria_aire=" + id_espectrometria_aire + "&fecha=" + fecha + "&hora=" + hora + "&tipo=" + tipo + "&recinto=" + recinto + "&ar41=" + ar41 + "&xe133=" + xe133 + "&xe135=" + xe135 + "&kr88=" + kr88
			$.get(url, function(data) {
				recuperar_valores_espectrometria_aire(id_planilla)
				$("#popup_carga_espectrometria_aire_editar").dialog("close")
			})
		}

		function eliminar_registro(nombre_tabla, nombre_campo_id, id_registro) {
			var id_planilla = '<?php echo $id_planilla ?>'
			var url = "procesos_reg_rad.php?accion=eliminar_registro_generico&nombre_tabla=" + nombre_tabla + "&nombre_campo_id=" + nombre_campo_id + "&id_registro=" + id_registro
			var pregunta_eliminacion = confirm("Está seguro que desea eliminar el registro?")
			if (pregunta_eliminacion) {
				$.get(url, function(data) {
					carga_pagina_functions()
				})
			}
		}

		function eliminar_registro_por_input(nombre_tabla, nombre_campo_id, nombre_input_id_registro) {
			var id_registro = $('#' + nombre_input_id_registro).val()
			var id_planilla = '<?php echo $id_planilla ?>'
			var url = "procesos_reg_rad.php?accion=eliminar_registro_generico&nombre_tabla=" + nombre_tabla + "&nombre_campo_id=" + nombre_campo_id + "&id_registro=" + id_registro
			var pregunta_eliminacion = confirm("Está seguro que desea eliminar el registro?")
			if (pregunta_eliminacion) {
				$.get(url, function(data) {
					carga_pagina_functions()
				})
			}
		}

		function insertar_fila_tabla_radionucleidos_efluentes_liquidos(valores_array, id_efluentes) {
			var t = $('#tabla_radionucleidos_efluentes_liquidos')
			$('#tabla_radionucleidos_efluentes_liquidos tbody').empty();
			var new_array = valores_array.split("@@");
			$.each(new_array, function(index, value) {
				var otro_array = value.split("@");
				if (otro_array[0] == "" || otro_array[0] == "sin_datos") {} else {
					var markup = "<tr><td>" + otro_array[0] + "</td><td>" + otro_array[1] + "</td><td><img src='Images/icon_delete.png' style='max-height: 25px;: 25px;vertical-align: middle;cursor: pointer' title='Eliminar Registro' onclick=\"eliminar_registro('regrad_efluentes_liquidos_radionucleidos','id'," + otro_array[2] + ");recuperar_datos_tabla_radionucleidos_efluentes_liquidos(" + id_efluentes + ")\"></td></tr>";
					t.append(markup);
				}
			})
		}

		function actualizar_observaciones_efluentes() {
			var id_efluentes = $('#var_id_efluentes_para_carga_observaciones_efluentes').val()
			var observaciones = $('#cargar_datos_efluentes_liquidos_observaciones').val()

			var url = "procesos_reg_rad_post.php"
			$.post(url, {
				accion: "actualizar_observaciones_efluentes",
				id_efluentes: id_efluentes,
				observaciones: observaciones
			})
			$('#popup_carga_observaciones_efluentes').dialog('close')
			//var url = "procesos_reg_rad.php?accion=actualizar_observaciones_efluentes&id_efluentes="+id_efluentes+"&observaciones="+observaciones
			//$.get(url, function( data ) {
			//	if(data = "ok"){
			//	}else{
			//		alert("Ha ocurrido un error")
			//	}
			//})			
		}

		function recuperar_observaciones_efluentes(id_efluentes) {
			var url = "procesos_reg_rad.php?accion=recuperar_observaciones_efluentes&id_efluentes=" + id_efluentes
			$.get(url, function(data) {
				var array = data.split("@")
				$('#mostrar_efluentes_liquidos_fecha').text(array[1])
				$('#mostrar_efluentes_liquidos_hora').text(array[2])
				$('#mostrar_efluentes_liquidos_tanque').text(array[3])
				$('#mostrar_efluentes_liquidos_volumen').text(array[4])
				var observaciones = $('#cargar_datos_efluentes_liquidos_observaciones').val(array[0])
			})
		}

		function imprimir_planilla_efluentes(id_efluentes) {
			//window.open('pdf_impresion_efluentes_liquidos.php?id_efluentes='+id_efluentes)
			window.open('../TCPDF-master/examples/pdf_impresion_efluentes_liquidos_tcpdf.php?id_efluentes=' + id_efluentes)
		}

		function imprimir_planilla_registro_radiologico() {
			var id_planilla = '<?php echo $id_planilla ?>'
			window.open('/ptr/registro_rad5/pagina_creadora_grafico_para_informe_anteriores.php?id_planilla=' + id_planilla)
		}

		function ver_planilla_en_curso() {
			window.open('planilla_en_curso.php')
		}
		//ejecutar al cargar---------------------------------------------------------------------------------------
		function carga_pagina_functions() {
			var id_planilla = '<?php echo $id_planilla ?>'
			obtenerRadionucleidosRoGaseoso();
			recuperar_valores_eficiencia_dryers(id_planilla)
			recuperar_valores_contaminacion_superficial(id_planilla)
			recuperar_valores_efluentes_liquidos(id_planilla)
			recuperar_valores_gases_nobles(id_planilla)
			recuperar_valores_espectrometria_aire(id_planilla)
			recuperar_valores_emision_tritio(id_planilla)
			recuperar_valores_carbono14(id_planilla)
			recuperar_valores_dedo_frio(id_planilla)
			recuperar_valores_concentrador7923(id_planilla)
			recuperar_valores_control_precintos(id_planilla)
			recuperar_valores_condicion_meteorologica(id_planilla)
			recuperar_observaciones()
			recuperar_libro_actas()
			actualizar_tabla()
		}
		// 1. Abre el popup nuevo y limpia los campos por si había algo de antes
		function abrir_popup_carga_tarea() {
			// 1. Limpiamos los campos (ahora limpiamos el buscador y el id oculto)
			$('#tarea_texto').val('Sin observaciones');
			$('#tarea_recinto_buscador').val('');
			$('#tarea_recinto').val('-');
			$('#tarea_ptr_buscador').val('');
			$('#tarea_ptr').val('');
			$('#tarea_dosis').val('');
			$('#tarea_tritio').val('');
			$('#tarea_cont_beta').val('');
			$('#tarea_cont_alfa').val('');
			$('#tarea_derrame').prop('checked', false);
			$('#tarea_aerosoles').prop('checked', false);
			$('#tarea_aerosoles_dac').val('');
			$('#fila_aerosoles_dac').hide();
			$('#tarea_oficial_buscador').val('');
			$('#tarea_oficial_asociado').val('');
			$('#tarea_ayudante_buscador').val('');
			$('#tarea_ayudante').val('');

			// 2. Abrimos el popup
			$("#popup_carga_tarea_diaria").dialog({
				height: 850, // <--- ACÁ LE SUBIMOS EL ALTO (Antes estaba en 650)
				width: 1000,
				title: "Cargar Nueva Tarea",
				resizable: false,
				modal: true
			});
			// 3. Armamos el array de datos consultando tu variable de base de datos
			var lista_recintos = [
				<?php
				mysql_data_seek($recintos, 0);
				while ($row_recintos = mysql_fetch_assoc($recintos)) {
					if ($row_recintos['id'] <> "") {
						echo "{ label: '" . $row_recintos['recinto'] . "', value: '" . $row_recintos['id'] . "' },";
					}
				}
				?>
			];
			// Armamos el array de personal consultando la BD
			var lista_personal = [
				<?php
				$q_personal_extra = mysql_query("SELECT id, apellido, nombre, id_rol FROM vista_personal_radioproteccion WHERE habilitada_si_no = 1 ORDER BY id_rol ASC, apellido ASC", $miconex);
				while ($per = mysql_fetch_assoc($q_personal_extra)) {
					$tipo = ($per['id_rol'] == 1) ? '(P)' : (($per['id_rol'] == 2) ? '(Parada)' : '(Ayudante)');

					// FORZAMOS LA MAYÚSCULA DESDE PHP (Usamos mb_strtoupper para no romper los tildes ni las eñes)
					$nombre_completo = mb_strtoupper($per['apellido'] . ", " . $per['nombre'], 'UTF-8');

					echo "{ label: '" . $nombre_completo . " " . $tipo . "', value: '" . $per['id'] . "' },\n";
				}
				?>
			];
			// Inicializamos el Autocomplete del Oficial
			$("#tarea_oficial_buscador").autocomplete({
				source: lista_personal,
				appendTo: "#popup_carga_tarea_diaria",
				minLength: 0,
				position: {
					my: "left top",
					at: "left bottom",
					collision: "none"
				}, // <--- REGLA DE POSICIÓN
				select: function(event, ui) {
					event.preventDefault();
					$("#tarea_oficial_buscador").val(ui.item.label);
					$("#tarea_oficial_asociado").val(ui.item.value);
				},
				focus: function(event, ui) {
					event.preventDefault();
					$("#tarea_oficial_buscador").val(ui.item.label);
				},
				change: function(event, ui) {
					if (!ui.item) {
						$(this).val("");
						$("#tarea_oficial_asociado").val("");
					}
				}
			}).on('focus click', function() {
				$(this).autocomplete("search", $(this).val());
			});

			// Inicializamos el Autocomplete del Ayudante
			$("#tarea_ayudante_buscador").autocomplete({
				source: lista_personal,
				appendTo: "#popup_carga_tarea_diaria",
				minLength: 0,
				position: {
					my: "left top",
					at: "left bottom",
					collision: "none"
				}, // <--- REGLA DE POSICIÓN
				select: function(event, ui) {
					event.preventDefault();
					$("#tarea_ayudante_buscador").val(ui.item.label);
					$("#tarea_ayudante").val(ui.item.value);
				},
				focus: function(event, ui) {
					event.preventDefault();
					$("#tarea_ayudante_buscador").val(ui.item.label);
				},
				change: function(event, ui) {
					if (!ui.item) {
						$(this).val("");
						$("#tarea_ayudante").val("");
					}
				}
			}).on('focus click', function() {
				$(this).autocomplete("search", $(this).val());
			});

			$("#tarea_recinto_buscador").autocomplete({
				source: lista_recintos,
				appendTo: "#popup_carga_tarea_diaria",
				minLength: 0,
				position: {
					my: "left top",
					at: "left bottom",
					collision: "none"
				}, // <--- REGLA DE POSICIÓN
				select: function(event, ui) {
					event.preventDefault();
					$("#tarea_recinto_buscador").val(ui.item.label);
					$("#tarea_recinto").val(ui.item.value);
				},
				focus: function(event, ui) {
					event.preventDefault();
					$("#tarea_recinto_buscador").val(ui.item.label);
				},
				change: function(event, ui) {
					if (!ui.item) {
						$(this).val("");
						$("#tarea_recinto").val("-");
					}
				}
			}).on('focus click', function() {
				$(this).autocomplete("search", $(this).val());
			});

			// (Agregá esto en la sección donde limpiás los campos al abrir el popup)
			$('#tarea_ptr_buscador').val('');
			$('#tarea_ptr').val('');

			// (Agregá esto debajo de la inicialización del autocomplete de recintos)
			$("#tarea_ptr_buscador").autocomplete({
				source: lista_ptrs,
				appendTo: "#popup_carga_tarea_diaria",
				minLength: 0,
				position: {
					my: "left top",
					at: "left bottom",
					collision: "none"
				}, // <--- REGLA DE POSICIÓN
				select: function(event, ui) {
					event.preventDefault();
					$("#tarea_ptr_buscador").val(ui.item.label);
					$("#tarea_ptr").val(ui.item.value);
				},
				focus: function(event, ui) {
					event.preventDefault();
					$("#tarea_ptr_buscador").val(ui.item.label);
				},
				change: function(event, ui) {
					if (!ui.item) {
						$(this).val("");
						$("#tarea_ptr").val("");
					}
				}
			}).on('focus click', function() {
				$(this).autocomplete("search", $(this).val());
			});
		}

		// 2. Muestra u oculta el campo de DAC si tildan dispersión de aerosoles
		function toggle_aerosoles_dac() {
			if ($('#tarea_aerosoles').prop('checked')) {
				$('#fila_aerosoles_dac').show();
			} else {
				$('#fila_aerosoles_dac').hide();
				$('#tarea_aerosoles_dac').val(''); // Limpiamos el valor por seguridad
			}
		}

		// 3. Recopila los datos y los manda a guardar
		function guardar_nueva_tarea() {
			var id_planilla = '<?php echo $id_planilla ?>';
			var id_usuario = '<?php echo $usuario_id ?>';

			var texto = $('#tarea_texto').val();
			var recinto = $('#tarea_recinto').val();
			var ptr = $('#tarea_ptr').val();
			var dosis = $('#tarea_dosis').val();
			var tritio = $('#tarea_tritio').val();
			var cont_beta = $('#tarea_cont_beta').val();
			var cont_alfa = $('#tarea_cont_alfa').val();
			var derrame = $('#tarea_derrame').prop('checked') ? 1 : 0;
			var aerosoles = $('#tarea_aerosoles').prop('checked') ? 1 : 0;
			var aerosoles_dac = $('#tarea_aerosoles_dac').val();

			// 🚨 ACÁ ESTÁ LA MAGIA: CAPTURAMOS LOS IDs EN EL MOMENTO DEL CLIC
			var id_oficial_asociado = $('#tarea_oficial_asociado').val();
			var id_ayudante = $('#tarea_ayudante').val();

			// Si borraron todo el texto a mano, forzamos el "Sin observaciones"
			if (texto.trim() === '') {
				texto = 'Sin observaciones';
			}

			// Armamos el paquete de datos para mandar a PHP
			var datos = {
				accion: "insertar_tarea_diaria",
				id_planilla: id_planilla,
				id_usuario: id_usuario,
				texto: texto,
				recinto: recinto === '-' ? '' : recinto,
				ptr: ptr,
				dosis: dosis,
				tritio: tritio,
				cont_beta: cont_beta,
				cont_alfa: cont_alfa,
				derrame: derrame,
				aerosoles: aerosoles,
				aerosoles_dac: aerosoles_dac,
				// Nuevas variables inyectadas al paquete
				id_oficial_asociado: id_oficial_asociado,
				id_ayudante: id_ayudante
			};

			// Enviamos por POST a procesos
			$.post("procesos_reg_rad_post.php", datos, function(respuesta) {
				if (respuesta.trim() === "ok") {
					$('#popup_carga_tarea_diaria').dialog('close');
					cargar_tareas_diarias();
				} else {
					alert("Error al guardar la tarea: " + respuesta);
				}
			});
		}

		function cargar_tareas_diarias() {
			var id_planilla = '<?php echo $id_planilla ?>';
			$.get("procesos_reg_rad.php?accion=recuperar_tareas_diarias&id_planilla=" + id_planilla, function(data) {

				// --- FILTRO INTELIGENTE DE NÚMEROS ---
				// Busca cualquier número con formato de "punto y tres decimales" en el texto devuelto por PHP
				var data_formateada = data.replace(/\b(\d+)\.(\d{3})\b/g, function(match) {
					var num = parseFloat(match); // Convierte "15.000" a 15 y "1.200" a 1.2

					if (num % 1 === 0) {
						// Si es un número entero (ej: 15.000 o 65.000), lo deja limpio sin decimales ni comas: "15"
						return num.toString();
					} else {
						// Si tiene decimales verdaderos (ej: 1.200), lo formatea con 2 decimales y coma: "1,20"
						return num.toFixed(2).replace('.', ',');
					}
				});
				// ---------------------------------------

				// Dibujamos las tarjetas en la pantalla, ya procesadas
				$('#contenedor_tareas_diarias').html(data_formateada);
			});
		}

		function eliminar_tarea(id_tarea) {
			if (confirm("¿Estás seguro de que querés eliminar esta tarea? (Solo posible dentro de los 60 min)")) {
				$.post("procesos_reg_rad_post.php", {
					accion: "eliminar_tarea_diaria",
					id_tarea: id_tarea
				}, function(respuesta) {

					if (respuesta.trim() === "ok") {
						cargar_tareas_diarias(); // Recarga la lista para que desaparezca visualmente
					} else if (respuesta.trim() === "tiempo_agotado") {
						alert("Operación denegada: El tiempo permitido (60 min) ya expiró.");
						cargar_tareas_diarias(); // Recargamos para "limpiar" los botones vencidos de la pantalla
					} else {
						alert("Error al eliminar: " + respuesta);
					}

				});
			}
		}
		// Dejamos la función de editar vacía por ahora para que no te tire error
		function editar_tarea(id_tarea) {
			alert("Función de edición en construcción para la tarea: " + id_tarea);
		}
		// Variable global para guardar los PTRs
		var lista_ptrs = [];

		// Apenas carga la página, vamos a buscar los PTRs al servidor en silencio
		$(document).ready(function() {
			$.get("https://syrapp.cne.central.nasa/ptr/simplestWebService.php?action=listadoPTRs_activos", function(data) {
				var ptrs = JSON.parse(data);
				// Transformamos los datos al formato que le gusta al Autocomplete (label y value)
				$.each(ptrs, function(i, item) {
					lista_ptrs.push({
						label: "PTR " + item.id + " - " + item.titulo_tarea_elca,
						value: item.id
					});
				});
			});
		});
	</script>
</body>
<?php Mysql_close() ?>

</html>