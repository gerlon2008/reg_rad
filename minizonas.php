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

$query_minizonas = "SELECT * FROM `regrad_minizonas` where habilitada_si_no = 1 and id_minizonas != 4";
$minizonas = mysql_query($query_minizonas, $miconex) or die(mysql_error());
$row_minizonas = mysql_fetch_assoc($minizonas);
$totalRows_minizonas = mysql_num_rows($minizonas);

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
		table.dataTable tbody tr.myeven{
			background-color:#ffffff;
		}
		table.dataTable tbody tr.myodd{
			background-color:#e6e6e6;
		}

	</style>
	<script type="text/javascript">
		$( function() {
			$( document ).tooltip();
		} );
	</script>

</head>
<body style="font-family:Ubuntu;text-align:;width:100%;height:100%;padding:0px;margin:0px" onload="">
	<div class="container">
		<div class="mainbody" style="background-image: background-size:100% auto;background-image:url('../images/fondo_verde_registro_rad.jpg');">
			<div style="display: inline-block">
				<h2>Seleccione Minizona para continuar</h2>
				<div class="container_icon" id="capa">
					<?php do{ ?>
						<a href="acta_minizona.php?id_minizona=<?php echo $row_minizonas['id_minizonas'] ?>" target="_self">
							<div class="caja" style="background-image:url(images/<?php echo $row_minizonas['imagen_nombre'] ?>);">
								<div class="titulo_icon">Minizona <?php echo $row_minizonas['nombre_minizonas'] ?></div>
							</div>
						</a>
						<?php }while($row_minizonas = mysql_fetch_assoc($minizonas)) ?>
						<!-- <a href="acta_registros_ptr.php" target="_self">
							<div class="caja" style="background-image:url(images/icon_registros_ptr.svg);">
								<div class="titulo_icon">Registros PTRs</div>
							</div>
						</a> -->
				</div>
			</div>
		</div>
		<div class="footer" style="background-color: #457315">
			<div style="text-align: right;margin-top: 10px">

			</div>
		</div>
		<div class="header" style="background-color: #acdc74">
			<img style="position:relative;left:5px;top:5px;float:left;max-width:120px" src="../images/logo_nasa.svg">
			<div style="position:relative;height:100%;margin:auto;font-size:25px;font-family:arial;font-weight:bold;left:-88px;top:53px;float:left;text-shadow: 4px 4px 9px rgba(150, 150, 150, 0.6);">NA-SA</div>
			<div style="text-align:left;margin-left:-154px;text-shadow: 4px 4px 9px rgba(150, 150, 150, 0.6);"><h2 style="font-size:25px">Registro Radiológico Online - SyR</h2></div>
			<div style="text-align:right;top: 0px;text-shadow: none;position:absolute;right: 15px"><h2 style="text-shadow: none;font-size:15px">Sesión iniciada: <?php echo ucwords($_SESSION['nombre_apellido']) ?></h2></div>


		</div>
	</div>
	<div id="bot_volver_id" style="position:fixed;right:5px;bottom:45px"><img src="../images/icono_volver.png" onclick="window.history.back();" style="cursor:pointer;height:50px"></div>
	<script>

	</script>
</body>
</html>