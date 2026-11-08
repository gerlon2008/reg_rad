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

$query_ult_carga = "SELECT concat(mes_medicion_oficial,' - ',ano_medicion_oficial) as mes_ano, mes, ano_medicion_oficial as ano FROM mediciones a left join meses b on b.id_meses = a.mes_medicion_oficial where tipo_medicion = 1 order by ano_medicion_oficial DESC, mes_medicion_oficial DESC limit 1";
$ult_carga = mysql_query($query_ult_carga, $miconex) or die(mysql_error());
$row_ult_carga = mysql_fetch_assoc($ult_carga);
$totalRows_ult_carga = mysql_num_rows($ult_carga);

?>
<!DOCTYPE html>
<html lang=“es” style="height:100%">

<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="viewport">
	<title>Gr&aacute;ficos Din&aacute;micos - ALARA CNE</title>
	<link rel="stylesheet" href="/ptr/css/portal_syr.css" />
	<link rel="stylesheet" href="/ptr/prueba_mario/main.css" />
	<link rel="stylesheet" href="../../ptr/css/jquery-ui.min.css" />
	<script src="../../ptr/prueba_mario/jquery-2.1.3.min.js"></script>
	<script src="../../ptr/prueba_mario/jquery-ui-1.11.4.min.js"></script>


	<style>
		@font-face {
			font-family: "Ubuntu";
			src: url(font/Ubuntu.ttf) format("truetype");
		}

		.highcharts-background {
			fill: #efefef;
			stroke: black;
			stroke-width: 0px;
		}

		select {
			width: auto;
			text-align-last: left;
			font-size: 12px
		}

		textarea {
			resize: vertical;
			max-height: 200px;
		}

		tr {
			background-color: #f2f2f2;
		}

		.icono {
			max-width: 20px;
			max-height: 20px;
			cursor: pointer;
		}

		.fila_cambio:hover {
			background-color: white;
			color: #0084ff
		}

		.subindice {
			font-size: 11px;
			font-weight: normal;
		}

		td,
		th {
			padding-right: 10px;
			padding-left: 10px;
			height: 30px
		}

		th {
			border: thin solid #d9d9d9;
			background-color: #ececec;
		}

		.fila_eliminar {
			background-color: #F5A9A9;
		}

		#boton_logout {
			position: fixed;
			right: 3px;
			top: 3px;
		}
	</style>
</head>

<body style="font-family:Ubuntu;text-align:center;width:100%;height:100%;padding:0px;margin:0px" onload="">
	<div class="container">
		<div class="mainbody" style="background-image: background-size:100% auto;background-image:url('../images/fondo_verde_registro_rad.jpg');">
			<div style="display: inline-block">
				<h2>Registro Radiológico Online</h2>

				<?php if ($_SESSION['of_rad_registro_rad'] == '0') { ?>
					<div class="container_icon" id="capa">
						<a href="vista_planillas_anteriores.php" target="_self">
							<div class="caja" style="background-image:url(images/vista_planillas_anteriores.png);">
								<div class="titulo_icon">Vista planillas anteriores</div>
							</div>
						</a>
					</div>

				<?php } elseif ($_SESSION['of_rad_registro_rad'] == '1') { ?>
					<div class="container_icon" id="capa">
						<a href="vista_planillas_anteriores.php" target="_self">
							<div class="caja" style="background-image:url(images/vista_planillas_anteriores.png);">
								<div class="titulo_icon">Vista planillas anteriores</div>
							</div>
						</a>
						<a href="cargar_planilla.php" target="_self">
							<div class="caja" style="background-image:url(images/agregar_registro_diario.jpg);">
								<div class="titulo_icon">Carga Diaria</div>
							</div>
						</a>
						<a href="planilla_en_curso.php" target="_self">
							<div class="caja" style="background-image:url(images/icon_ver_informe_en_curso.png);">
								<div class="titulo_icon">Ver Informe en Curso</div>
							</div>
						</a>
						<a href="libro_actas.php" target="_self">
							<div class="caja" style="background-image:url(images/icono_libro_actas_vista_grande.svg);">
								<div class="titulo_icon">Vista Libro Actas - Últimos 15 registros
								</div>
							</div>
						</a>
						<a href="minizonas.php" target="_self">
							<div class="caja" style="background-image:url(images/icon_minizona.svg);">
								<div class="titulo_icon">Minizonas - Actas
								</div>
							</div>
						</a>
						<a href="acta_registros_ptr.php" target="_self">
							<div class="caja" style="background-image:url(images/icono_Registros_Auxiliares_Radioprotección.png);">
								<div class="titulo_icon">Registros Auxiliares Radioprotección</div>
							</div>
						</a>

					</div>
				<?php } elseif ($_SESSION['of_rad_registro_rad'] == '2') { ?>
					<div class="container_icon" id="capa">
						<a href="vista_planillas_anteriores.php" target="_self">
							<div class="caja" style="background-image:url(images/vista_planillas_anteriores.png);">
								<div class="titulo_icon">Vista planillas anteriores</div>
							</div>
						</a>
						<a href="planilla_en_curso.php" target="_self">
							<div class="caja" style="background-image:url(images/icon_ver_informe_en_curso.png);">
								<div class="titulo_icon">Ver Informe en Curso</div>
							</div>
						</a>
						<a href="libro_actas.php" target="_self">
							<div class="caja" style="background-image:url(images/icono_libro_actas_vista_grande.svg);">
								<div class="titulo_icon">Vista Libro Actas - Últimos 15 registros
								</div>
							</div>
						</a>
						<a href="minizonas.php" target="_self">
							<div class="caja" style="background-image:url(images/icon_minizona.svg);">
								<div class="titulo_icon">Minizonas - Actas
								</div>
							</div>
						</a>
						<a href="acta_registros_ptr.php" target="_self">
							<div class="caja" style="background-image:url(images/icono_Registros_Auxiliares_Radioprotección.png);">
								<div class="titulo_icon">Registros Auxiliares Radioprotección</div>
							</div>
						</a>

					</div>

				<?php } ?>
				<?php if ($id_user == 0) { ?>
					<div class="container_icon" id="capa">
						<h2>Edición Planillas anteriores</h2>
						<a href="vista_planillas_anteriores_y_edicion.php" target="_self">
							<div class="caja" style="background-image:url(images/vista_planillas_anteriores.png);">
								<div class="titulo_icon">Editar Planillas Anteriores</div>
							</div>
						</a>
						<a href="graficos_varios.php" target="_self">
							<div class="caja" style="background-image:url(images/graficos.svg);">
								<div class="titulo_icon">prueba gráficos</div>
							</div>
						</a>
						<a href="admin_personal_paradas.php" target="_self">
							<div class="caja" style="background-image:url(images/administracion_personal.jpg);">
								<div class="titulo_icon">Administracion personal</div>
							</div>
						</a>
					</div>
				<?php } ?>

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
	<div id="bot_volver_id" style="position:fixed;right:5px;bottom:45px"><img src="../images/icono_volver.png" onclick="window.history.back();" style="cursor:pointer;height:50px"></div>
</body>

</html>