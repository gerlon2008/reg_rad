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

mysql_set_charset('utf8', $miconex);

mysql_select_db($database_miconex, $miconex);

$query_registros = "SELECT a.id_planilla_diaria as id_planilla_diaria, a.fecha as fecha, horarios, of_apellido, of_nombre, f.novedades, if(vent_gral is null,'-',(vent_gral/1000000000)) as vent_gral_gbq, if(vent_reactor is null,'-',(vent_reactor/1000000000)) as vent_reactor_gbq, if(g.medicion is null,'-',(g.medicion/1000000)) as carbono14_mbq, concat(if((menor_que=0 OR menor_que is null),'','< '),if(ppm is null,'-',ppm)) as dedo_frio_ppm 
	FROM regrad_planilla_diaria_nueva a 
	left join regrad_turnos b on b.id_turnos = a.turno 
	left join regrad_estados_planta c on c.id_estado_planta = a.estado_planta 
	left join regrad_of_rad d on d.id_of_rad = a.of_rad 
	left join regrad_jefes_turno e on e.id_jefes_turno = a.jefe_turno 
	left join regrad_libro_actas f on f.id_planilla_diaria = a.id_planilla_diaria 
	left join regrad_carbono14 g on g.id_planilla = a.id_planilla_diaria 
	left join regrad_dedo_frio h on h.id_planilla = a.id_planilla_diaria 
	left join regrad_tritio i on i.id_planilla = a.id_planilla_diaria 
	where a.id_planilla_diaria >= 34 
	order by a.fecha DESC, a.fecha_hora_abierto DESC LIMIT 15";
$registros = mysql_query($query_registros, $miconex) or die(mysql_error());
$row_registros = mysql_fetch_assoc($registros);
$totalRows_registros = mysql_num_rows($registros);

$query_actas_minizonas = "SELECT * FROM `reg_rad_resumen_minizonas` order by id_planilla_diaria, id_minizonas, fecha_hora_agregado DESC";
$actas_minizonas = mysql_query($query_actas_minizonas, $miconex) or die(mysql_error());
$row_actas_minizonas = mysql_fetch_assoc($actas_minizonas);
$totalRows_actas_minizonas = mysql_num_rows($actas_minizonas);

// Traemos todas las tareas registradas de las planillas que se van a mostrar
$query_tareas_turnos = "SELECT t.*, 
                               r.recinto as nombre_recinto, 
                               m.nombre_minizonas,
                               concat(o2.of_apellido, ', ', o2.of_nombre) as oficial_tarea,
                               concat(o3.of_apellido, ', ', o3.of_nombre) as ayudante_tarea
                        FROM regrad_minizonas_registros t
                        left join graf_tritio_recintos r on r.id = t.id_recinto
                        left join regrad_minizonas m on m.id_minizonas = t.id_minizonas
                        left join regrad_of_rad o2 on o2.id_of_rad = t.id_oficial
                        left join regrad_of_rad o3 on o3.id_of_rad = t.id_ayudante
                        where t.id_planilla_diaria is not null and t.id_planilla_diaria > 0
                        order by t.id_registro ASC";
$tareas_turnos = mysql_query($query_tareas_turnos, $miconex) or die(mysql_error());
$row_tareas_turnos = mysql_fetch_assoc($tareas_turnos);
$totalRows_tareas_turnos = mysql_num_rows($tareas_turnos);
// Función auxiliar para quitar .000 en enteros y poner coma en decimales
function formatear_numero_acta($val)
{
	if ($val === null || $val === '') return '';
	$n = floatval($val);
	return ($n == floor($n)) ? number_format($n, 0, ',', '.') : number_format($n, 2, ',', '.');
}

$variable_sin_sentido = '';
do {
	$variable_sin_sentido = $row_actas_minizonas['id_minizonas_actas'];
} while ($row_actas_minizonas = mysql_fetch_assoc($actas_minizonas));

// Traemos las tareas cargadas desde el Registro Diario común (fuera de minizonas)
$query_tareas_diarias = "SELECT t.*, 
                                r.recinto as nombre_recinto,
                                concat(o2.apellido, ', ', o2.nombre) as oficial_tarea,
                                concat(o3.apellido, ', ', o3.nombre) as ayudante_tarea
                         FROM regrad_actas_tareas_diarias t
                         left join graf_tritio_recintos r on r.id = t.id_recinto
                         left join vista_personal_radioproteccion o2 on o2.id = t.id_oficial_asociado
                         left join vista_personal_radioproteccion o3 on o3.id = t.id_ayudante
                         where t.id_planilla_diaria is not null and t.id_planilla_diaria > 0
                         order by t.id_tarea ASC";
$tareas_diarias = mysql_query($query_tareas_diarias, $miconex) or die(mysql_error());
$totalRows_tareas_diarias = mysql_num_rows($tareas_diarias);
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
	<link rel="stylesheet" href="/ptr/jquery-ui-1.11.4.custom - verde/jquery-ui.min.css" />
	<script src="/ptr/prueba_mario/jquery-2.1.3.min.js"></script>
	<script src="/ptr/prueba_mario/jquery-ui-1.11.4.min.js"></script>
	<script src="/ptr/prueba_mario/jquery.dataTables-1.10.7.min.js"></script>
	<script src="/ptr/prueba_mario/dataTables.jqueryui.min.js"></script>

	<style type="text/css">
		table.dataTable tbody tr.myeven {
			background-color: #ffffff;
		}

		table.dataTable tbody tr.myodd {
			background-color: #e6e6e6;
		}

		.sombra {
			-webkit-box-shadow: 1px 0px 14px 2px rgba(0, 0, 0, 0.25);
			box-shadow: 1px 0px 14px 2px rgba(0, 0, 0, 0.25);
		}

		.registros_pasados_titulo {
			font-weight: bold;
			font-size: 1.1em;
		}

		.registros_pasados_titulo_oficial {
			width: 100%;
			position: relative;
		}

		.registros_pasados_fecha_hora {
			font-style: italic;
			font-size: 0.8em;
			position: absolute;
			right: 100px;
			top: 0px;
		}

		.div_contenedor_actas_minizonas {
			font-size: 17px;
			margin: auto;
			background-color: white;
			text-align: left;
			padding: 15px;
			position: relative;
			margin-bottom: -20px;
			padding-bottom: 0px;
			padding-top: 10px
		}

		.ui-widget {
			font-family: Ubuntu;
		}
	</style>
	<script type="text/javascript">
		$(function() {
			$(document).tooltip();
		});
	</script>

</head>

<body style="font-family:Ubuntu;text-align:;width:100%;height:100%;padding:0px;margin:0px" onload="">
	<div class="container">
		<div class="mainbody" style="background-image: background-size:100% auto;background-image:url('../images/fondo_verde_registro_rad.jpg');">
			<div style="display: inline-block">
				<h2>Vista Libro de Actas</h2>
				<div style="width: 1200px;margin: auto" id="tabla_contenedor">

					<?php
					if ($totalRows_registros == 0) { ?>
						<h3>NO EXISTEN REGISTROS EN LIBRO DE ACTAS</h3>
						<?php } else {
						do { ?>
							<div style="margin-top: 15px" class="ui-widget-content  ui-corner-all ">
								<table style="width: 90%" align="center">
									<tr>
										<td style="width: 33.33%">
											<h3>Fecha: <?php echo formafecha6($row_registros['fecha']) ?></h3>
										</td>
										<td style="width: 33.33%">
											<h3>Turno: <?php echo $row_registros['horarios'] ?></h3>
										</td>
										<td style="width: 33.33%">
											<h3>Oficial: <?php echo strtoupper($row_registros['of_apellido'] . ", " . $row_registros['of_nombre']) ?></h3>
										</td>
									</tr>
								</table>
								<table style="width: 90%;margin-top: -15px;margin-bottom: " align="center">
									<tr>
										<td class="ui-widget-content ui-corner-all" style="width: 25%">Ventilación General: <br><span style="font-weight: bold"><?php echo ($row_registros['vent_gral_gbq']) ?> GBq</span></td>
										<td class="ui-widget-content ui-corner-all" style="width: 25%">Ventilación Reactor: <br><span style="font-weight: bold"><?php echo ($row_registros['vent_reactor_gbq']) ?> GBq</span></td>
										<td class="ui-widget-content ui-corner-all" style="width: 25%">Emisión C14: <br><span style="font-weight: bold"><?php echo ($row_registros['carbono14_mbq']) ?> MBq</span></td>
										<td class="ui-widget-content ui-corner-all" style="width: 25%">Dedo Frío 73120: <br><span style="font-weight: bold"><?php echo ($row_registros['dedo_frio_ppm']) ?> ppm</span></td>
									</tr>
									<tr>
										<td colspan="4" style="padding-top: 8px">

											<!-- CONTENEDOR DE TAREAS DEL REGISTRO DIARIO COMÚN -->
											<!-- 1. NOVEDADES GENERALES -->
											<?php if (!empty($row_registros['novedades']) && trim($row_registros['novedades']) != '') { ?>
												<!-- Tarjeta idéntica a las de tareas, sin textarea ni scroll -->
												<div style="background-color: white; border-radius: 6px; border: 1px solid #d4d4d4; border-left: 5px solid #1e5799; box-shadow: 1px 1px 4px rgba(0,0,0,0.1); margin-bottom: 10px; padding: 10px 14px; text-align: left;">
													<div style="font-size: 15px; font-weight: bold; color: #1e5799; margin-bottom: 5px;">
														📝 Novedades Generales / Históricas
													</div>
													<div style="font-size: 14px; color: #222; line-height: 1.4;">
														<?php echo nl2br(htmlspecialchars($row_registros['novedades'])); ?>
													</div>
												</div>
											<?php } ?> <!-- 2. TAREAS DEL REGISTRO DIARIO COMÚN (VERDE) -->
											<div style="width: 100%; text-align: left;">
												<?php
												if ($totalRows_tareas_diarias > 0) {
													mysql_data_seek($tareas_diarias, 0);
													while ($td = mysql_fetch_assoc($tareas_diarias)) {
														if ($td['id_planilla_diaria'] == $row_registros['id_planilla_diaria']) {
															$recinto_lbl = !empty($td['nombre_recinto']) ? $td['nombre_recinto'] : 'S/E';
															$ptr_lbl = !empty($td['id_ptr']) ? " | 📋 PTR: " . $td['id_ptr'] : '';
															$oficial_asoc = !empty($td['oficial_tarea']) ? $td['oficial_tarea'] : strtoupper($row_registros['of_apellido'] . ", " . $row_registros['of_nombre']);
												?>
															<div style="background-color: white; border-radius: 6px; border: 1px solid #d4d4d4; border-left: 5px solid #73a832; box-shadow: 1px 1px 4px rgba(0,0,0,0.1); margin-bottom: 10px; padding: 10px 14px;">
																<div style="font-size: 15px; font-weight: bold; color: #1e5799; margin-bottom: 5px;">
																	📍 Recinto: <?php echo $recinto_lbl . $ptr_lbl; ?>
																	<span style="background-color: #e8f5e9; border: 1px solid #c8e6c9; color: #2e7d32; font-size: 11px; font-weight: bold; padding: 2px 6px; border-radius: 4px; margin-left: 8px;">📋 Oficial Turno Registro Diario</span>
																</div>

																<div style="margin-bottom: 6px; font-size: 13px;">
																	<span style="background-color: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 3px; padding: 2px 6px; color: #2e7d32; font-weight: bold;">👷 Oficial: <?php echo $oficial_asoc; ?></span>
																	<?php if (!empty($td['ayudante_tarea'])) { ?>
																		<span style="background-color: #e3f2fd; border: 1px solid #bbdefb; border-radius: 3px; padding: 2px 6px; color: #1565c0; margin-left: 8px;">🤝 Asistió: <?php echo $td['ayudante_tarea']; ?></span>
																	<?php } ?>
																</div>
																<div style="font-size: 14px; color: #222; line-height: 1.4;">
																	<b>Observaciones:</b> <?php echo nl2br(htmlspecialchars($td['texto_tarea'])); ?>
																</div>
															</div>
												<?php
														}
													}
												}
												?>
											</div>

											<!-- 3. TAREAS DE MINIZONAS (NARANJA - LAS QUE YA VENÍAN FUNCIONANDO) -->
											<div style="width: 100%; text-align: left;">
												<?php
												if ($totalRows_tareas_turnos > 0) {
													mysql_data_seek($tareas_turnos, 0);
													while ($t = mysql_fetch_assoc($tareas_turnos)) {
														if ($t['id_planilla_diaria'] == $row_registros['id_planilla_diaria']) {
															$nombre_mz = !empty($t['nombre_minizonas']) ? $t['nombre_minizonas'] : $t['id_minizonas'];
															$recinto_lbl = !empty($t['nombre_recinto']) ? $t['nombre_recinto'] : 'S/E';
															$ptr_lbl = !empty($t['id_ptr']) ? " | 📋 PTR: " . $t['id_ptr'] : '';
															$oficial_asoc = !empty($t['oficial_tarea']) ? $t['oficial_tarea'] : strtoupper($row_registros['of_apellido'] . ", " . $row_registros['of_nombre']);
												?>
															<div style="background-color: white; border-radius: 6px; border: 1px solid #d4d4d4; border-left: 5px solid #ff9800; box-shadow: 1px 1px 4px rgba(0,0,0,0.1); margin-bottom: 10px; padding: 10px 14px;">
																<div style="font-size: 15px; font-weight: bold; color: #1e5799; margin-bottom: 5px;">
																	📍 Recinto: <?php echo $recinto_lbl . $ptr_lbl; ?>
																	<span style="background-color: #fff3e0; border: 1px solid #ffe0b2; color: #e65100; font-size: 11px; font-weight: bold; padding: 2px 6px; border-radius: 4px; margin-left: 8px;">📍 Minizona <?php echo $nombre_mz; ?></span>
																</div>

																<div style="margin-bottom: 6px; font-size: 13px;">
																	<span style="background-color: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 3px; padding: 2px 6px; color: #2e7d32; font-weight: bold;">👷 Oficial: <?php echo $oficial_asoc; ?></span>
																	<?php if (!empty($t['ayudante_tarea'])) { ?>
																		<span style="background-color: #e3f2fd; border: 1px solid #bbdefb; border-radius: 3px; padding: 2px 6px; color: #1565c0; margin-left: 8px;">🤝 Asistió: <?php echo $t['ayudante_tarea']; ?></span>
																	<?php } ?>
																</div>
																<div style="font-size: 14px; color: #222; line-height: 1.4;">
																	<b>Observaciones:</b> <?php echo nl2br(htmlspecialchars($t['texto_acta'])); ?>
																</div>
															</div>
												<?php
														}
													}
												}
												?>
											</div>
										</td>
									</tr>
								</table>
								<?php
								mysql_data_seek($actas_minizonas, 0);
								$temporal_dato_id_acta = 0;
								$temporal_dato_id_minizona = 0;
								do {
									if ($row_actas_minizonas['id_planilla_diaria'] == $row_registros['id_planilla_diaria']) {
										echo "<div class='div_contenedor_actas_minizonas ui-corner-all'>";
										if ($row_actas_minizonas['id_minizonas'] != $temporal_dato_id_minizona) {
											echo "<h3 style='margin-top:0px;margin-bottom:0px'>Acta minizona " . $row_actas_minizonas['nombre_minizonas'] . "</h3>";
											echo "<hr style='height:2px;border-width:0;color:gray;background-color:gray'>";
										};
										$temporal_dato_id_minizona = $row_actas_minizonas['id_minizonas'];
										if ($row_actas_minizonas['id_planilla_diaria'] != $temporal_dato_id_acta && !is_null($row_actas_minizonas['id_planilla_diaria'])) { ?>
											<div class='registros_pasados_titulo_oficial'>Oficial &nbsp;&nbsp;
												<span class='registros_pasados_titulo'>
													<?php echo $row_actas_minizonas['of_apellido'] . ", " . $row_actas_minizonas['of_nombre']; ?>
												</span>
												<div class='registros_pasados_fecha_hora'>
													<?php echo $row_actas_minizonas['fecha_hora']; ?>
												</div>
											</div>
											<div class='textarea_texto_acta'>
												<?php echo str_replace("\n", "<br>", $row_actas_minizonas['texto_acta']) ?>
											</div>
											<hr style='border-color:#ffffff;margin-bottom: 20px'>
									<?php }
										$temporal_dato_id_acta = $row_actas_minizonas['id_minizonas_actas'];
										echo "</div>";
									} ?>
								<?php } while ($row_actas_minizonas = mysql_fetch_assoc($actas_minizonas)) ?>
							</div>
							<br>
					<?php } while ($row_registros = mysql_fetch_assoc($registros));
					} ?>

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


		</div>
	</div>
	<div id="bot_volver_id" style="position:fixed;right:5px;bottom:45px"><img src="../images/icono_volver.png" onclick="window.location.replace('inicio.php');" style="cursor:pointer;height:50px"></div>
	<script>
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
			paging: 1,
			pageLength: 10,
			lengthChange: 1,
			searching: 1,
			ordering: 1,
			info: 1
		});

		function abrir_popup_vista_libro_actas(id_planilla_diaria) {
			recuperar_datos_planilla_diaria(id_planilla_diaria)
			recuperar_libro_actas(id_planilla_diaria)
			$("#popup_libro_actas").dialog({
				height: 700,
				width: 1000,
				title: "Libro de Actas",
				resizable: false,
				modal: true,
			});
		}

		function recuperar_libro_actas(id_planilla_diaria) {
			var id_planilla = id_planilla_diaria
			var url = "procesos_reg_rad.php?accion=recuperar_novedades_libro_actas&id_planilla=" + id_planilla
			$.get(url, function(data) {
				$('#texto_campo_libro_actas').val(data)
			})
		}

		function recuperar_datos_planilla_diaria(id_planilla) {
			var url = "procesos_reg_rad.php?accion=recuperar_datos_planilla_diaria&id_planilla=" + id_planilla
			$.get(url, function(data) {
				var new_array = data.split("$$$");
				$("#popup_libro_actas_campo_oficial").text(new_array['0'])
				$("#popup_libro_actas_campo_fecha").text(new_array['1'])
				$("#popup_libro_actas_campo_turno").text(new_array['2'])

			})
		}
	</script>
</body>

</html>