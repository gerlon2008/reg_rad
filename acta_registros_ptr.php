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

$id_minizona = 4;

mysql_set_charset('utf8', $miconex);

mysql_select_db($database_miconex, $miconex);

$query_minizonas = "SELECT * FROM `regrad_minizonas` where habilitada_si_no = 1 and id_minizonas = '$id_minizona'";
$minizonas = mysql_query($query_minizonas, $miconex) or die(mysql_error());
$row_minizonas = mysql_fetch_assoc($minizonas);
$totalRows_minizonas = mysql_num_rows($minizonas);

$query_of_rad = "SELECT * FROM `regrad_of_rad_minizonas` where habilitada_si_no = 1 order by of_apellido ";
$of_rad = mysql_query($query_of_rad, $miconex) or die(mysql_error());
$row_of_rad = mysql_fetch_assoc($of_rad);
$totalRows_of_rad = mysql_num_rows($of_rad);

// Consultamos la vista maestra trayendo a todos los habilitados
$q_personal = mysql_query("SELECT id, apellido, nombre, id_rol FROM vista_personal_radioproteccion WHERE habilitada_si_no = 1 ORDER BY id_rol ASC, apellido ASC", $miconex);

$options_personal = '<option value="">- Ninguno (Opcional) -</option>';
while ($per = mysql_fetch_assoc($q_personal)) {
	// Le agregamos una etiqueta visual para saber quién es quién
	$tipo = ($per['id_rol'] == 1) ? '(P)' : (($per['id_rol'] == 2) ? '(Parada)' : '(Ayudante)');
	$options_personal .= '<option value="' . $per['id'] . '">' . $per['apellido'] . ', ' . $per['nombre'] . ' ' . $tipo . '</option>';
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
	<link rel="stylesheet" href="/ptr/jquery-ui-1.11.4.custom - verde/jquery-ui.min.css" />
	<link rel="stylesheet" href="select2/select2.min.css" />
	<script src="/ptr/prueba_mario/jquery-2.1.3.min.js"></script>
	<script src="/ptr/prueba_mario/jquery-ui-1.11.4.min.js"></script>
	<script src="/ptr/prueba_mario/jquery.dataTables-1.10.7.min.js"></script>
	<script src="/ptr/prueba_mario/dataTables.jqueryui.min.js"></script>
	<script src="select2/select2.min.js"></script>

	<style type="text/css">
		body {
			overflow: hidden;
		}

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
			font-size: 1.2em;
		}

		.registros_pasados_titulo_oficial {
			width: 100%;
			position: relative;
		}

		.registros_pasados_fecha_hora {
			font-style: italic;
			font-size: 0.8em;
			position: absolute;
			right: 10px;
			top: 2px;
		}

		.numero_ptr {
			font-size: 1.2em;
			position: absolute;
			right: 265px;
			top: 2px;
		}

		.registros_pasados_contenido {}

		.textarea_texto_acta {
			padding-left: 30px;
			font-size: 0.9em;
			width: 100%;
			resize: none;
			overflow: hidden;
			border: 0px;
			margin-top: 10px;
			transform: translate(-30px);
			/* Equivalente a translateX(10px) */
		}

		.ui-widget {
			font-family: Ubuntu;
		}

		.select2-container .select2-selection--single {
			height: 32px !important;
			border: 1px solid #ccc !important;
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
				<h2>Registros Auxiliares Radioprotección</h2>
				<div class="container_icon" id="capa" style="width: 1200px;margin: auto">
					<div style="text-align: left;padding: 10px;position: relative" class="ui-widget-content  ui-corner-all ">
						<h3 style="margin: 5px">Nuevo registro de acta</h3>
						<div id="nombre_minizona" style="position: absolute;right: 4px;top: 4px; font-size: 1.2em; display: none"><?php echo $row_minizonas['nombre_minizonas'] ?></div>
						<table>
							<tr>
								<td>
									<label for="select_oficial">Oficial responsable de éste registro: </label>
								</td>
								<td>
									<select style="font-size: 0.9em;margin-left: 10px" id="select_oficial" name="select_oficial" class="ui-button ui-widget ui-state-default ui-corner-all">
										<?php do { ?>
											<option value="<?php echo $row_of_rad['id_of_rad'] ?>"><?php echo $row_of_rad['of_apellido'] . ", " . $row_of_rad['of_nombre'] ?></option>
										<?php } while ($row_of_rad = mysql_fetch_assoc($of_rad)) ?>
									</select>
									<input type="hidden" id="hidden_id_usuario_web">
								</td>
								<td>
									<label for="select_ptr" style="margin-left: 135px;">Escribir / elegir un PTR</label>
								</td>
								<td>
									<select style="width: 250px; text-align: left;" id="select_ptr" name="select_ptr" class="ui-widget ui-state-default ui-corner-all">
										<option value="">- Buscar PTR -</option>
									</select>

									</datalist>
								</td>
							</tr>
						</table>
						<table style="width: 90%;margin-top: -5px;margin: auto">
							<tr>
							</tr>
							<tr>
								<td colspan="4" style="padding-top: 8px">
									<textarea style="width: calc(100% - 7px);font-size: 17px;min-height: 300px" id="texto_campo_libro_actas" class="ui-corner-all" placeholder="Desarrolle las observaciones aquí..."></textarea>
								</td>
							</tr>
							<tr>
								<td>
									<button onclick='abrir_popup_vista_libro_actas(11)' class="ui-button ui-widget ui-state-default ui-corner-all" style="padding: 10px;width: 100%">AGREGAR REGISTRO DE ACTA</button>
								</td>
							</tr>
						</table>
					</div>
				</div>
				<h2>Actas anteriormente cargadas<span class="id_ptr_clase"></span></h2>
				<div style="text-align: center;padding: 10px;width: 1178px;margin:auto" class="ui-widget-content  ui-corner-all ">
					<div style="width: calc(87% - 6px);font-size: 17px;min-height: 300px;margin: auto;background-color: white; text-align: left;padding: 15px;position:relative; " class="sombra ui-widget ui-corner-all" id="div_listado_registros_actas_minizonas">
					</div>
				</div>
			</div>
		</div>
		<div class="footer" style="background-color: #457315">
			<div style="text-align: right;margin-top: 10px">

			</div>
		</div>
		<div class="header" style="background-color: #acdc74">
			<img style="position:relative;left:5px;top:5px;float:left;max-width:120px" src="../images/logo_nasa.svg">
			<div style="position:relative;height:100%;margin:auto;font-size:25px;font-family:Ubuntu;font-weight:bold;left:-88px;top:53px;float:left;text-shadow: 4px 4px 9px rgba(150, 150, 150, 0.6);">NA-SA</div>
			<div style="text-align:left;margin-left:-154px;text-shadow: 4px 4px 9px rgba(150, 150, 150, 0.6);">
				<h2 style="font-size:25px">Registro Radiológico Online - SyR</h2>
			</div>
			<div style="text-align:right;top: 0px;text-shadow: none;position:absolute;right: 15px">
				<h2 style="text-shadow: none;font-size:15px">Sesión iniciada: <?php echo ucwords($_SESSION['nombre_apellido']) ?></h2>
			</div>


		</div>
	</div>
	<div id="bot_volver_id" style="position:fixed;right:5px;bottom:45px"><img src="../images/icono_volver.png" onclick="window.history.back();" style="cursor:pointer;height:50px"></div>


	<div class="popup" id="popup_libro_actas" style="text-align: center;">
		<h3>Está seguro que desea agregar éste registro?</h3>
		<h4>Una vez agregado, no podrá editarse</h4>
	</div>

	<div class="popup" id="popup_alerta" style="text-align: center;">
		<h3 id="popup_alerta_texto1"></h3>
		<h4 id="popup_alerta_texto2"></h4>
	</div>


	<script>
		$(document).ready(function() {
			obtener_registros_de_minizonas('cargar');
			obtener_listado_ptrs()
		})
		$('.textarea_texto_acta').each(function() {
			alert("ok")
			$(this).height($(this)[0].scrollHeight);
		})

		function insertar_registro_acta() {
			var id_ptr = $('#select_ptr').val();
			var id_usuario_web = '<?php echo $id_user ?>';
			var id_minizonas = '<?php echo $id_minizona ?>';
			var texto_acta = $('#texto_campo_libro_actas').val()
			var id_of_rad = $('#select_oficial').val();
			if ($('#texto_campo_libro_actas').val() == '') {
				abrir_popup_alerta('ATENCIÓN!!!', 'Para poder guardar el registro, debe tener algún texto tipeado', 'Escriba en el cuadro de texto y vuelva a intentar')
			} else {
				var url = "procesos_reg_rad_post.php";
				console.log(id_usuario_web + " - " + id_minizonas + " - " + texto_acta + " - " + id_of_rad + " - " + id_ptr);
				$.post(url, {
					accion: "insertar_registro_minizonas",
					id_usuario_web: id_usuario_web,
					id_minizonas: id_minizonas,
					texto_acta: texto_acta,
					id_of_rad: id_of_rad,
					id_ptr: id_ptr
				}, function(data) {
					console.log(data);
					if (data == 'ok') {
						$('#texto_campo_libro_actas').empty()
						$('#select_ptr').empty('');
						$('#select_ptr').val('');
						$('#texto_campo_libro_actas').val('')
						$("#popup_libro_actas").dialog('close')
						obtener_registros_de_minizonas('cargar')
					} else {}
				})
			}
		}

		function obtener_registros_de_minizonas(condicion) {
			var id_ptr_buscar = $('#select_ptr').val();
			var id_minizonas = '<?php echo $id_minizona ?>';
			var div_listado = $('#div_listado_registros_actas_minizonas')
			if (condicion == 'cargar') {
				var url = "procesos_reg_rad.php?accion=obtener_registros_de_minizonas_registros_ptr&id_minizonas=" + id_minizonas + "&id_ptr_buscar=" + id_ptr_buscar
				$.get(url, function(data) {
					div_listado.empty()
					if (id_ptr_buscar != '') {
						$('.id_ptr_clase').html(' - PTR N° ' + id_ptr_buscar);
					} else {
						$('.id_ptr_clase').html('');
					}
					div_listado.append(data)
				})
			} else if (condicion == 'agregar') {
				var ultimo_registro = $('#hidden_ultimo_registro')
			}
		}

		function obtener_listado_ptrs() {
			// 1. Llamamos a la nueva acción del Web Service
			var url = "https://syrapp.cne.central.nasa/ptr/simplestWebService.php?action=listadoPTRs_activos";

			$.get(url, function(data) {
				var ptrs = JSON.parse(data);

				// 2. Llenamos el select
				$.each(ptrs, function(i, item) {
					$('#select_ptr').append($('<option>', {
						value: item.id,
						// Le concateno el ID al título para que sea más fácil buscar tipeando el número
						text: 'PTR ' + item.id + ' - ' + item.titulo_tarea_elca
					}));
				});

				// 3. Inicializamos Select2
				$('#select_ptr').select2({
					placeholder: "Escriba un N° de PTR o palabra...",
					allowClear: true
				});
			});
		}

		function abrir_popup_alerta(titulo, texto1, texto2) {
			$('#popup_alerta_texto1').text(texto1)
			$('#popup_alerta_texto2').text(texto2)
			$("#popup_alerta").dialog({
				height: 300,
				width: 700,
				title: titulo,
				resizable: false,
				modal: true,
				buttons: [{
					text: 'Entendido',
					icon: 'ui-icon-check',
					click: function() {
						$("#popup_alerta").dialog('close')
					}
				}]
			})
		}
		$('#select_ptr').on('change focusout', function() {
			obtener_registros_de_minizonas('cargar');
		})

		function abrir_popup_vista_libro_actas() {
			if ($('#texto_campo_libro_actas').val() == '') {
				abrir_popup_alerta('ATENCIÓN!!!', 'Para poder guardar el registro, debe tener algún texto ingresado', 'Escriba en el cuadro de texto y vuelva a intentar')
				// } else if ($('#select_ptr').val() == '') {
				// 	abrir_popup_alerta('ATENCIÓN!!!', 'Debe escribir un PTR, sobre el que está escribiendo un acta', 'Escriba en el cuadro de texto y vuelva a intentar');
			} else {

				$("#popup_libro_actas").dialog({
					height: 300,
					width: 700,
					title: "Libro de Actas",
					resizable: false,
					modal: true,
					buttons: [{
						text: 'OK',
						icon: 'ui-icon-check',
						click: function() {
							insertar_registro_acta()
							$("#popup_libro_actas").dialog('close')
						}
					}, {
						text: 'Cancelar',
						icon: 'ui-icon-close',
						click: function() {
							$("#popup_libro_actas").dialog('close')
						}
					}]
				});
			}
		}
	</script>
</body>

</html>