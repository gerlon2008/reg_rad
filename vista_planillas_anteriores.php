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

$query_registros = "SELECT * FROM regrad_planilla_diaria_nueva a left join regrad_turnos b on b.id_turnos = a.turno left join regrad_estados_planta c on c.id_estado_planta = a.estado_planta left join regrad_of_rad d on d.id_of_rad = a.of_rad left join regrad_jefes_turno e on e.id_jefes_turno = a.jefe_turno where fecha_hora_cerrado is not null and id_planilla_diaria >= 34 order by fecha DESC,fecha_hora_cerrado DESC";

//las anteriores a la planilla 34 son de prueba

$registros = mysql_query($query_registros, $miconex) or die(mysql_error());
$row_registros = mysql_fetch_assoc($registros);
$totalRows_registros = mysql_num_rows($registros);

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

</head>
<body style="font-family:Ubuntu;text-align:;width:100%;height:100%;padding:0px;margin:0px" onload="">
	<div class="container">
		<div class="mainbody" style="background-image: background-size:100% auto;background-image:url('../images/fondo_verde_registro_rad.jpg');">
			<div style="display: inline-block">
				<h2>Vista Planillas Anteriores</h2>
				<div style="width: 890px;margin: auto;display: none;" id="tabla_contenedor">
					<table id="tabla1" align="center" style="width: 100%">
						<thead>
							<tr>
								<th>
									Fecha
								</th>
								<th style="height: 30px;width: 200px">
									Oficial Radioprotección
								</th>
								<th style="height: 30px;width: 200px">
									Jefe de turno
								</th>
								<th style="height: 30px;width: 200px">
									Turno
								</th>
								<th style="height: 30px;width: 50px">
									Acciones
								</th>
							</tr>
						</thead>
						<tbody>
							<?php
							if($totalRows_registros==0){}else{
							do{ ?>
							<tr>
								<td>
									<?php formafecha1($row_registros['fecha']) ?>
								</td>
								<td>
									<?php echo $row_registros['of_apellido'].", ".$row_registros['of_nombre'] ?>
								</td>
								<td>					
									<?php
									if(($row_registros['jefe_apellido']<>"")or($row_registros['jefe_nombre']<>"")){
										echo $row_registros['jefe_apellido'].", ".$row_registros['jefe_nombre'];
									}else{
										echo "'SIN CARGAR'";
									}
									?>
								</td>
								<td>
									<?php echo $row_registros['horarios']?>
								</td>
								<td>
									<a href="planilla_en_curso_visualizacion.php?id_planilla=<?php echo $row_registros['id_planilla_diaria'] ?>" target="_new"><img src="images/informe.png" style="max-width: 20px;max-height: 30px" title="Ver planilla"></a>
									<a href="pagina_creadora_grafico_para_informe_anteriores.php?id_planilla=<?php echo $row_registros['id_planilla_diaria']?>&var_fecha_hasta=<?php echo $row_registros['fecha']?>" target="_new"><img src="images/print.png" style="max-width: 20px;max-height: 30px" title="Imprimir"></a>
									<a onclick="abrir_popup_vista_libro_actas('<?php echo $row_registros['id_planilla_diaria'] ?>')" target="_new"><img src="images/icono_libro_actas_vista.svg" style="max-width: 20px;max-height: 30px;cursor: pointer;" title="Novedades"></a>
								</td>
							</tr>
						<?php }while($row_registros = mysql_fetch_assoc($registros)); }?>

						</tbody>
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
		<div class="popup" id="popup_libro_actas" style="text-align: center;">
			<table style="font-size: 16px;width: 80%;text-align: center;" align="center" border="0">
				<tr>
					<th style="width: 25%;text-align: center;">Fecha: <span id="popup_libro_actas_campo_fecha" style="font-weight: normal;"></span></th>
					<th style="width: 50%;text-align: center;">Oficial de Turno: <span id="popup_libro_actas_campo_oficial" style="font-weight: normal;"></span></th>
					<th style="width: 25%;text-align: center;">Turno: <span id="popup_libro_actas_campo_turno" style="font-weight: normal;"></span></th>
				</tr>
			</table>
			<table style="font-size: 16px;width: 80%;text-align: center;" align="center" border="0">
				<tr>
					<th class="ui-widget-content ui-corner-all ui-shadow sombra" style="width: 25%;text-align: center;font-weight: normal;">Ventilación Gral: <br><b><span id="popup_libro_actas_campo_vent_gral" style=""></span></b></th>
					<th class="ui-widget-content ui-corner-all ui-shadow sombra" style="width: 25%;text-align: center;font-weight: normal;">Ventilación E.R.: <br><b><span id="popup_libro_actas_campo_vent_reactor" style=""></span></b></th>
					<th class="ui-widget-content ui-corner-all ui-shadow sombra" style="width: 25%;text-align: center;font-weight: normal;">Emisión C-14: <br><b><span id="popup_libro_actas_campo_emision_c14" style=""></span></b></th>
					<th class="ui-widget-content ui-corner-all ui-shadow sombra" style="width: 25%;text-align: center;font-weight: normal;">Dedo frío: <br><b><span id="popup_libro_actas_campo_dedo_frio" style=""></span></b></th>
				</tr>
			</table>
			<textarea style="width: 95%;height: 500px;font-size: 16px; resize: none;margin: auto;margin-top: 8px" class="ui-corner-all" id="texto_campo_libro_actas" readonly></textarea>
		</div>
	<script>
 
$('#tabla1').dataTable( {
  "initComplete": function( settings, json ) {
    $('#tabla_contenedor').show();
  },

			language: {url: "/ptr/prueba_mario/Spanish.json"}, 
			"rowCallback": function( row, data, index ) {
				if(index%2 == 0){
					$(row).removeClass('myodd myeven');
					$(row).addClass('myodd');
				}else{
					$(row).removeClass('myodd myeven');
					$(row).addClass('myeven');
				}
			},
			stripeClasses: [ 'odd-row', 'even-row' ],
			order: [], 
			paging: 1, 
			pageLength: 10, 
			lengthChange: 1, 
			searching: 1, 
			ordering: 1, 
			info: 1
		} );
		function abrir_popup_vista_libro_actas(id_planilla_diaria){
			recuperar_datos_planilla_diaria(id_planilla_diaria)
			recuperar_libro_actas(id_planilla_diaria)
			$( "#popup_libro_actas" ).dialog({
				height: 700,
				width: 1000,
				title: "Libro de Actas",
				resizable: false,
				modal: true,
			});
		}
		function recuperar_libro_actas(id_planilla_diaria){
			var id_planilla = id_planilla_diaria
			var url = "procesos_reg_rad.php?accion=recuperar_novedades_libro_actas&id_planilla="+id_planilla
				$.get(url, function( data ) {
					$('#texto_campo_libro_actas').val(data)
				})
		}
		function recuperar_datos_planilla_diaria(id_planilla){
			var url = "procesos_reg_rad.php?accion=recuperar_datos_planilla_diaria&id_planilla="+id_planilla
				$.get(url, function( data ) {
					var new_array = data.split("$$$");
					$( "#popup_libro_actas_campo_oficial" ).text(new_array['0'])
					$( "#popup_libro_actas_campo_fecha" ).text(new_array['1'])
					$( "#popup_libro_actas_campo_turno" ).text(new_array['2'])
					$( "#popup_libro_actas_campo_vent_gral" ).text(new_array['3']+" GBq")
					$( "#popup_libro_actas_campo_vent_reactor" ).text(new_array['4']+" GBq")
					$( "#popup_libro_actas_campo_emision_c14" ).text(new_array['5']+" MBq")
					$( "#popup_libro_actas_campo_dedo_frio" ).text(new_array['6']+" ppm")
					
				})
		}
	</script>
</body>
</html>