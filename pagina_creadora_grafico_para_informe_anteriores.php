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
$var_dias_resta = "30";
if(isset($_GET['var_dias_resta'])){
	$var_dias_resta = $_GET['var_dias_resta'];
}
$var_dias_resta = "-".$var_dias_resta." day";

$var_recinto = "45";
$var_fecha_desde = date('Y-m-d',strtotime($var_dias_resta));
$var_fecha_hasta = date('Y-m-d');

if(isset($_GET['var_recinto'])){
	$var_recinto = $_GET['var_recinto'];
}
if(isset($_GET['var_fecha_desde'])){
	$var_fecha_desde = $_GET['var_fecha_desde'];
}
if(isset($_GET['var_fecha_hasta'])){
	$var_fecha_hasta = $_GET['var_fecha_hasta'];
}
$var_where = "where recinto = $var_recinto and fecha BETWEEN '$var_fecha_desde' and '$var_fecha_hasta'";
$query_tritio = "SELECT *, (SELECT round(AVG(medicion),2) as promedio_medicion FROM `reg_rad_tritio_relevamiento_union_access_y_nuevo` $var_where) as promedio  FROM `reg_rad_tritio_relevamiento_union_access_y_nuevo` $var_where order by fecha, hora";
// echo $query_tritio;
$tritio = mysql_query($query_tritio, $miconex) or die(mysql_error());
$row_tritio = mysql_fetch_assoc($tritio);
$totalRows_tritio = mysql_num_rows($tritio);
//echo $query_tritio;

$id_planilla = $_GET['id_planilla'];
?>

<!doctype html>
<html>

<head>
	<title>Registro Radiológico</title>
	<script src="/ptr/Chart.js-2.8.0/dist/Chart.min.js"></script>
	<script src="/ptr/Chart.js-2.8.0/samples/utils.js"></script>
	<link rel="stylesheet" href="/ptr/css/portal_syr.css" /> 
	<link rel="stylesheet" href="/ptr/prueba_mario/main.css" />
	<link rel="stylesheet" href="/ptr/prueba_mario/jquery.dataTables.css">
	<link rel="stylesheet" href="/ptr/jquery-ui-1.11.4.custom/dataTables.jqueryui.css">
	<link rel="stylesheet" href="/ptr/jquery-ui-1.12.1 - verde2/jquery-ui.min.css" /> 
	<script src="/ptr/prueba_mario/jquery-2.1.3.min.js"></script>
	<script src="/ptr/prueba_mario/jquery-ui-1.11.4.min.js"></script>
	<script src="/ptr/prueba_mario/jquery.dataTables-1.10.7.min.js"></script>
	<script src="/ptr/prueba_mario/dataTables.jqueryui.min.js"></script>
	<style>
	canvas {
		-moz-user-select: none;
		-webkit-user-select: none;
		-ms-user-select: none;
	}
	</style>
</head>

<body style="text-align: center;"	>
<div>
	Espere mientras se actualiza el grafico de tritio...
</div>
<div>
	<img src="Images/loading.gif">
</div>
	<div id="container" style="width: 1210px; height: 920px; display: none;">
		<canvas id="canvas" width="1210" height="920"></canvas>
	<button id="randomizeData">Randomize Data</button>
	<button id="addDataset">Add Dataset</button>
	<button id="removeDataset">Remove Dataset</button>
	<button id="addData">Add Data</button>
	<button id="removeData">Remove Data</button>
	<div id="cargaexterna">Aquí se cargará el HTML externo</div>
	<div>
		<img id="ChartImage" style="margin: 0 auto;" />
	</div>
	</div>
	<script>
		Chart.plugins.register({
			beforeDraw: function(chartInstance) {
				var ctx = chartInstance.chart.ctx;
				ctx.fillStyle = '#ffffff';
				ctx.fillRect(0, 0, chartInstance.chart.width, chartInstance.chart.height);
			}
		});

		var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
		var color = Chart.helpers.color;
		var barChartData = {
			labels: [<?php data_tritio3("tritio","fecha","hora")?>],
			datasets: [{
				label: 'Tritio (DAC)',
				backgroundColor: color(window.chartColors.blue).alpha(0.5).rgbString(),
				borderColor: window.chartColors.blue,
				borderWidth: 1,
				data: [<?php data_tritio2("tritio","medicion")?>]
			}]

		};

			var ctx = document.getElementById('canvas').getContext('2d');
			var myBar = new Chart(ctx, {
				type: 'line',
				data: barChartData,
				options: {
			        scales: {
			            yAxes: [{
			                ticks: {
			                    beginAtZero: true
			                }
			            }]
			        },
			        bezierCurve: false,
					elements: {point: {
						radius: 0
					}, 
					line:{
						tension: 0
					}},
					responsive: false,
					maintainAspectRatio: false,
					legend: {
						position: 'top',
					},
					title: {
						display: true,
						text: 'Tritio en R-501'
					},
					animation: false
				}
			});


		document.getElementById('randomizeData').addEventListener('click', function() {
			var zero = Math.random() < 0.2 ? true : false;
			barChartData.datasets.forEach(function(dataset) {
				dataset.data = dataset.data.map(function() {
					return zero ? 0.0 : randomScalingFactor();
				});

			});
			window.myBar.update();
		});

		var colorNames = Object.keys(window.chartColors);
		document.getElementById('addDataset').addEventListener('click', function() {
			var colorName = colorNames[barChartData.datasets.length % colorNames.length];
			var dsColor = window.chartColors[colorName];
			var newDataset = {
				label: 'Dataset ' + (barChartData.datasets.length + 1),
				backgroundColor: color(window.chartColors.blue).alpha(0.5).rgbString(),
				borderColor: dsColor,
				borderWidth: 1,
				data: []
			};

			for (var index = 0; index < barChartData.labels.length; ++index) {
				newDataset.data.push(randomScalingFactor());
			}

			barChartData.datasets.push(newDataset);
			window.myBar.update();
		});

		document.getElementById('addData').addEventListener('click', function() {
			if (barChartData.datasets.length > 0) {
				var month = MONTHS[barChartData.labels.length % MONTHS.length];
				barChartData.labels.push(month);

				for (var index = 0; index < barChartData.datasets.length; ++index) {
					// window.myBar.addData(randomScalingFactor(), index);
					barChartData.datasets[index].data.push(randomScalingFactor());
				}

				window.myBar.update();
			}
		});

		document.getElementById('removeDataset').addEventListener('click', function() {
			barChartData.datasets.pop();
			window.myBar.update();
		});

		document.getElementById('removeData').addEventListener('click', function() {
			barChartData.labels.splice(-1, 1); // remove the label first

			barChartData.datasets.forEach(function(dataset) {
				dataset.data.pop();
			});

			window.myBar.update();
		});

var url = myBar.toBase64Image()
document.getElementById("ChartImage").src = url; //to fill image in html

$.post("pagina_recibe_imagen_y_guarda_a_archivo_para_informe.php", {coche: url}, function(htmlexterno){
   $("#cargaexterna").html(htmlexterno);
    		});
setTimeout(function() {
	window.location.href = '/ptr/TCPDF-master/examples/pdf_impresion_registro_radiologico_tcpdf_anteriores.php?id_planilla=<?php echo $id_planilla ?>';
}, 5000);
setTimeout(function() {
	window.close();
}, 8000);

	</script>
</body>

</html>

