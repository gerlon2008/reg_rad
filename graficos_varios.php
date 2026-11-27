<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/ptr/Connections/miconex.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/ptr/Functions/funciones.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/ptr/Connections/niveles.php");

session_start();
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) {
	$tipo_user = $_SESSION['portal_nivel'];
	$id_user = $_SESSION['id_user'];
	$of_rad_registro = $_SESSION['id_user'];
} else {
	echo "Esta pagina es solo para usuarios registrados.<br>";
	exit;
}

mysql_set_charset('utf8',$miconex);

mysql_select_db($database_miconex, $miconex);

$query_gases_nobles = "SELECT * FROM `regrad_gases_nobles` where id_planilla >= 34 order by fecha asc";
$gases_nobles = mysql_query($query_gases_nobles, $miconex) or die(mysql_error());
$row_gases_nobles = mysql_fetch_assoc($gases_nobles);
$totalRows_gases_nobles = mysql_num_rows($gases_nobles);

$variable_datos_gases_nobles_ar41 = "";
$variable_datos_gases_nobles_kr85 = "";
$variable_datos_gases_nobles_kr85m = "";
$variable_datos_gases_nobles_xe133 = "";
$variable_datos_gases_nobles_xe135 = "";
$variable_datos_gases_nobles_xe135m = "";

do{
	if($row_gases_nobles['ar41'] == 0){}else{$variable_datos_gases_nobles_ar41 = $variable_datos_gases_nobles_ar41."[Date.UTC(".fecha($row_gases_nobles['fecha'])."),".conversion_actividad(1,5,($row_gases_nobles['ar41']))."],";}
	if($row_gases_nobles['kr85'] == 0){}else{$variable_datos_gases_nobles_kr85 = $variable_datos_gases_nobles_kr85."[Date.UTC(".fecha($row_gases_nobles['fecha'])."),".conversion_actividad(1,5,($row_gases_nobles['kr85']))."],";}
	if($row_gases_nobles['kr85m'] == 0){}else{$variable_datos_gases_nobles_kr85m = $variable_datos_gases_nobles_kr85m."[Date.UTC(".fecha($row_gases_nobles['fecha'])."),".conversion_actividad(1,5,($row_gases_nobles['kr85m']))."],";}
	if($row_gases_nobles['xe133'] == 0){}else{$variable_datos_gases_nobles_xe133 = $variable_datos_gases_nobles_xe133."[Date.UTC(".fecha($row_gases_nobles['fecha'])."),".conversion_actividad(1,5,($row_gases_nobles['xe133']))."],";}
	if($row_gases_nobles['xe135'] == 0){}else{$variable_datos_gases_nobles_xe135 = $variable_datos_gases_nobles_xe135."[Date.UTC(".fecha($row_gases_nobles['fecha'])."),".conversion_actividad(1,5,($row_gases_nobles['xe135']))."],";}
	if($row_gases_nobles['xe135m'] == 0){}else{$variable_datos_gases_nobles_xe135m = $variable_datos_gases_nobles_xe135m."[Date.UTC(".fecha($row_gases_nobles['fecha'])."),".conversion_actividad(1,5,($row_gases_nobles['xe135m']))."],";}
}while($row_gases_nobles = mysql_fetch_assoc($gases_nobles));

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
	<script src="/ptr/highcharts/highcharts.js"></script>
	<script src="/ptr/highcharts/highcharts-3d.js"></script>
	<script src="/ptr/highcharts/exporting.js"></script>

	<style type="text/css">
		table.dataTable tbody tr.myeven{
			background-color:#ffffff;
		}
		table.dataTable tbody tr.myodd{
			background-color:#e6e6e6;
		}
	</style>

</head>
<body style="font-family:Ubuntu;text-align:;width:100%;height:100%;padding:0px;margin:0px" onload="">
	<div class="container">
		<div class="mainbody" style="background-image: background-size:100% auto;background-image:url('../images/fondo_verde_registro_rad.jpg');">
			<div style="display: inline-block">
				<h2>Gráficos Varios</h2>
			</div>
				<div id="gases_nobles" style="width: 100%;height: 500px;margin: 0 auto; float: left"></div>
		</div>
		<div class="footer" style="background-color: #457315">
			<div style="text-align: right;margin-top: 10px">

			</div>
		</div>
		<div class="header" style="background-color: #acdc74">
			<img style="position:relative;left:5px;top:5px;float:left;max-width:120px" src="../images/logo_nasa.png">
			<div style="position:relative;height:100%;margin:auto;font-size:25px;font-family:arial;font-weight:bold;left:-88px;top:53px;float:left;text-shadow: 4px 4px 9px rgba(150, 150, 150, 0.6);">NA-SA</div>
			<div style="text-align:left;margin-left:-154px;text-shadow: 4px 4px 9px rgba(150, 150, 150, 0.6);"><h2 style="font-size:25px">Registro Radiológico Online - SyR</h2></div>
			<div style="text-align:right;top: 0px;text-shadow: none;position:absolute;right: 15px"><h2 style="text-shadow: none;font-size:15px">Sesión iniciada: <?php echo ucwords($_SESSION['nombre_apellido']) ?></h2></div>

			<?php if($_SESSION['of_rad_registro_rad']=='0'){ ?>

			<div style="text-align:right;top: 30px;text-shadow: none;position:absolute;right: 15px">
				<h2 style="text-shadow: none;font-size:20px">Usuario normal</h2>



			</div>

			<?php }elseif($_SESSION['of_rad_registro_rad']=='1'){ ?>

			<div style="text-align:right;top: 30px;text-shadow: none;position:absolute;right: 15px">
				<h2 style="text-shadow: none;font-size:20px">Oficial de Radioprotección</h2>



			</div>
			<?php } ?>
		</div>
	</div>
	<div id="bot_volver_id" style="position:fixed;right:5px;bottom:45px"><img src="../images/icono_volver.png" onclick="window.location.replace('inicio.php');" style="cursor:pointer;height:50px"></div>
	<script>
		var datos_gases_nobles_ar41 = new Array(<?php echo $variable_datos_gases_nobles_ar41 ?>)
		var datos_gases_nobles_kr85 = new Array(<?php echo $variable_datos_gases_nobles_kr85 ?>)
		var datos_gases_nobles_kr85m = new Array(<?php echo $variable_datos_gases_nobles_kr85m ?>)
		var datos_gases_nobles_xe133 = new Array(<?php echo $variable_datos_gases_nobles_xe133 ?>)
		var datos_gases_nobles_xe135 = new Array(<?php echo $variable_datos_gases_nobles_xe135 ?>)
		var datos_gases_nobles_xe135m = new Array(<?php echo $variable_datos_gases_nobles_xe135m ?>)
		$(function () {
			Highcharts.setOptions({
				lang: {
					months: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
					weekdays: ['Domingo', 'Lunes', 'Martes', 'Mi\u00e9rcoles', 'Jueves', 'Viernes', 'S\u00e1bado'],
					shortMonths: [ "Enero" , "Febrero" , "Marzo" , "Abril" , "Mayo" , "Junio" , "Julio" , "Agosto" , "Septiembre" , "Octubre" , "Noviembre" , "Diciembre"],
					decimalPoint: ',',
					downloadJPEG: 'Descargar JPG',
					downloadPDF: 'Descargar PDF',
					downloadPNG: 'Descargar PNG',
					downloadSVG: 'Descargar SVG',
					printChart: 'Imprimir Gr\u00e1fico',
				}
			}),
			Highcharts.chart('gases_nobles', {
				tooltip: {
					xDateFormat: '%B-%Y',
					enabled: true,
					pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y}</b> mSv.p<br/>',
					valueDecimals: 2,
					split: false,
					shared: true
				},
				chart: {
					zoomType: 'x'
				},
				title: {
					text: 'DOSIS POR SECTORES'
				},

				subtitle: {
					text: 'NADA'
				},

				yAxis: {
					title: {
						text: 'Dosis Colectiva (mSv.p)'
					}
				},
				legend: {
					layout: 'vertical',
					align: 'right',
					verticalAlign: 'middle'
				},

				plotOptions: {
					areaspline: {
						fillOpacity: 0.5
					},
					series: {
						label: {
							connectorAllowed: true
						},
					}
				},
				xAxis: {
					type: 'datetime',
					tickInterval   : 24 * 3600 * 1000*30, 
					dateTimeLabelFormats: {
						second: '%d-%m-%Y<br/>%H:%M:%S',
						minute: '%d-%m-%Y<br/>%H:%M',
						hour: '%d-%m-%Y<br/>%H:%M',
						day: '%d-%m<br/>%Y',
						week: '%d-%m<br/>%Y',
						month: '%m-%Y',
						year: '%Y'
					},
					labels: {
						format: '{value:%Y-%b}'
					},
				},

				series: [{
					marker: {
						radius: 3,
						lineColor: null,
						states: {
							hover: {
								fillColor: 'white',
								radius: 4
							}
						}},
						type: 'line',
						color: '#29a329', 
						name: 'Dosis Estimada',
						data: datos_gases_nobles_ar41,
					},{
					marker: {
						radius: 3,
						lineColor: null,
						states: {
							hover: {
								fillColor: 'white',
								radius: 4
							}
						}},
						type: 'line',
						color: '#29a329', 
						name: 'Dosis Estimada',
						data: datos_gases_nobles_xe133,
					}],

								responsive: {
									rules: [{
										condition: {
											maxWidth: 500
										},
										chartOptions: {
											legend: {
												layout: 'horizontal',
												align: 'center',
												verticalAlign: 'bottom'
											}
										}
									}]
								}

							});
		});

	</script>
</body>
</html>