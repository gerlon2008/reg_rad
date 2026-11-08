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

$query_ofrad = "SELECT * FROM `regrad_of_rad` order by of_apellido";
$ofrad = mysql_query($query_ofrad, $miconex) or die(mysql_error());
$row_ofrad = mysql_fetch_assoc($ofrad);
$totalRows_ofrad = mysql_num_rows($ofrad);

$query_turnos = "SELECT id_turnos, horarios FROM `regrad_turnos` order by id_turnos";
$turnos = mysql_query($query_turnos, $miconex) or die(mysql_error());
$row_turnos = mysql_fetch_assoc($turnos);
$totalRows_turnos = mysql_num_rows($turnos);

$query_jefes_turno = "SELECT * FROM `regrad_jefes_turno` order by jefe_apellido";
$jefes_turno = mysql_query($query_jefes_turno, $miconex) or die(mysql_error());
$row_jefes_turno = mysql_fetch_assoc($jefes_turno);
$totalRows_jefes_turno = mysql_num_rows($jefes_turno);

$query_estados_planta = "SELECT * FROM `regrad_estados_planta` where habilitada = 1 order by nombre_estado";
$estados_planta = mysql_query($query_estados_planta, $miconex) or die(mysql_error());
$row_estados_planta = mysql_fetch_assoc($estados_planta);
$totalRows_estados_planta = mysql_num_rows($estados_planta);

$query_planilla_abierta = "SELECT * FROM regrad_planilla_diaria_nueva a left join regrad_turnos b on b.id_turnos = a.turno left join regrad_estados_planta c on c.id_estado_planta = a.estado_planta left join regrad_of_rad d on d.id_of_rad = a.of_rad left join regrad_jefes_turno e on e.id_jefes_turno = a.jefe_turno where fecha_hora_cerrado is null";
$planilla_abierta = mysql_query($query_planilla_abierta, $miconex) or die(mysql_error());
$row_planilla_abierta = mysql_fetch_assoc($planilla_abierta);
$totalRows_planilla_abierta = mysql_num_rows($planilla_abierta);

if($totalRows_planilla_abierta == 0){
	$condicion_planilla_encurso = true;
}else{
	$condicion_planilla_encurso = false;
}

$usuario_id = $_SESSION['id_user'];

$fecha_actual = (new \DateTime())->format('Y-m-d');
$nuevafecha = strtotime ( '-1 day' , strtotime ( $fecha_actual ) ) ;
$nuevafecha = date ( 'Y-m-j' , $nuevafecha );
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
		.popup{
			position:fixed;
			left: 0px;
			top:0px;
			height:100%;
			width:100%;
			background: rgba(255,255,255,.8);
			text-align:center;
			font-size:30px;
			padding-top:90px;
			display:none;
		}
</style>

</head>
<body style="font-family:Ubuntu;text-align:;width:100%;height:100%;padding:0px;margin:0px" onload="">
	<div class="container">
		<div class="mainbody" style="background-image: background-size:100% auto;background-image:url('../images/fondo_verde_registro_rad.jpg');">
			<div style="display: inline-block">
				<h2>Carga Planillas</h2>
			<?php if($condicion_planilla_encurso){?>
				<div class="container_icon" id="capa">
					<a href="#" target="" onclick="mostrar_div()">
						<div class="caja" style="background-image:url(images/agregar_planilla.png);">
							<div class="titulo_icon">Agregar planilla diaria</div>
						</div>
					</a>
				</div>
				<?php }else{ ?>
				<div class="container_icon" id="capa">
					<a href="nueva_planilla_diaria.php?id_planilla=<?php echo $row_planilla_abierta['id_planilla_diaria'] ?>" target="" onclick="">
						<div class="caja" style="background-image:url(images/continuar_registro.jpg);">
							<div class="titulo_icon">Continuar carga de planilla diaria</div>
							<div style="font-size: 12px;color: gray"><?php echo $row_planilla_abierta['of_apellido'].", ".$row_planilla_abierta['of_nombre'] ?>
								<br>
								<?php echo formafecha6($row_planilla_abierta['fecha']) ?>
								<br>
								<?php echo $row_planilla_abierta['horarios'] ?>
							</div>
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
			<img style="position:relative;left:5px;top:5px;float:left;max-width:120px" src="../images/logo_nasa.svg">
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
				<div class="popup" id="popup_carga_planilla">
				<img src="Images/cross.png" style="width: 30px;position: fixed;top: 20px;right: 20px;cursor: pointer;" onclick="cerrar_ventana_login()">
					<table align="center" >
						<thead>
							<tr>
								<th >Seleccione Oficial</th>
							</tr>
							<tr>
								<th >
									<select class="ui-button ui-widget ui-state-default ui-corner-all" style="font-size: 14px" id="select_ofrad">
											<?php do{ ?>
										<option value='<?php echo $row_ofrad['id_of_rad'] ?>'
										<?php if(isset($row_ofrad['id_tabla_usuario']) ){if($row_ofrad['id_tabla_usuario']==$usuario_id){echo "selected";}}?> value="<?php echo $row_ofrad['id_of_rad'] ?>" >
										<?php echo $row_ofrad['of_apellido'].", ".$row_ofrad['of_nombre'] ?>
										</option>
											<?php }while($row_ofrad = mysql_fetch_assoc($ofrad)) ?>
									</select>
								</th>
							</tr>
							<tr style="">
								<th style="font-size: 22px;padding-top: 35px;">Turno</th>
								</tr>
								<tr>
									<th style="padding-bottom: ">
										<select class="ui-button ui-widget ui-state-default ui-corner-all" style="font-size: 14px" id="select_turno">
										<option value="-">-</option>
										<?php do{ ?>
											<option value='<?php echo $row_turnos['id_turnos'] ?>'><?php echo $row_turnos['horarios'] ?></option>
										<?php }while($row_turnos = mysql_fetch_assoc($turnos)) ?>
									</select>
									</th>
								</tr>
							<tr style="">
								<th style="font-size: 22px;padding-top: 35px;">Fecha</th>
								</tr>
								<tr>
									<th style="padding-bottom: ">
										<input min='<?php echo $nuevafecha ?>' type="date" id="fecha_planilla" class="ui-widget ui-state-default ui-corner-all" style="font-size: 14px" onfocusout="comprobar_fecha()" value="<?php echo $fecha_actual ?>" >
									</th>
								</tr>
								<tr>
								<th style="font-size: 22px;padding-top: 35px;">Jefe de Turno</th>
								</tr>
								<tr>
									<th style="padding-bottom: ">
											<select class="ui-button ui-widget ui-state-default ui-corner-all" style="font-size: 14px" id="select_jefe_turno">
										<option value="-">-</option>
											<?php do{ ?>
												<option value='<?php echo $row_jefes_turno['id_jefes_turno'] ?>'><?php echo $row_jefes_turno['jefe_apellido'].", ".$row_jefes_turno['jefe_nombre'] ?></option>
											<?php }while($row_jefes_turno = mysql_fetch_assoc($jefes_turno)) ?>
										</select>
									</th>
								</tr>
								<tr>
								<th style="font-size: 22px;padding-top: 35px;">Estado planta</th>
								</tr>
								<tr>
									<th style="padding-bottom: 35px">
											<select class="ui-button ui-widget ui-state-default ui-corner-all" style="font-size: 14px" id="select_estado_planta">
										<option value="-">-</option>
											<?php do{ ?>
												<option value='<?php echo $row_estados_planta['id_estado_planta'] ?>'><?php echo $row_estados_planta['nombre_estado'] ?></option>
											<?php }while($row_estados_planta = mysql_fetch_assoc($estados_planta)) ?>
										</select>
									</th>
								<tr>
									<th >
										<button style="font-size: 25px;width: 100%" class="ui-button ui-widget ui-state-default ui-corner-all" onclick="crear_planilla_nueva()">Crear</button>
									</th>
								</tr>
							</tr>
						</thead>
					</table>
				</div>
			</div>
			<?php } ?>
		</div>
	</div>
	<div id="bot_volver_id" style="position:fixed;right:5px;bottom:45px"><img src="../images/icono_volver.png" onclick="window.location.replace('inicio.php');" style="cursor:pointer;height:50px"></div>
	<script>
 document.getElementById("fecha_planilla").addEventListener("focusout", myFunction);
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
function mostrar_div(){
	$('#popup_carga_planilla').fadeIn('slow')
}
function cerrar_ventana_login(){
	$('#popup_carga_planilla').fadeOut('fast')
}
function crear_planilla_nueva(){
	if($('#select_turno').val()=='-'){
		alert("Debe seleccionar un turno!")
		}
	else if($('#fecha_planilla').val()==''){
		alert("Debe seleccionar una fecha!")
		}
	else if($('#select_jefe_turno').val()=='-'){
		alert("Debe seleccionar un Jefe de Turno!")
		}
	else if($('#select_estado_planta').val()=='-'){
		alert("Debe seleccionar un Estado de Planta!")
		}
		else{
			var id_usuario = '<?php echo $id_user ?>'
			var id_oficial = $('#select_ofrad').val()
			var id_turno = $('#select_turno').val()
			var fecha_planilla = $('#fecha_planilla').val()
			var id_jefe_turno = $('#select_jefe_turno').val()
			var estados_planta = $('#select_estado_planta').val()
			var url = "procesos_reg_rad.php?id_user="+id_usuario+"&id_oficial="+id_oficial+"&id_turno="+id_turno+"&fecha_planilla="+fecha_planilla+"&id_jefe_turno="+id_jefe_turno+"&estados_planta="+estados_planta+"&accion=crear_planilla_nueva"
			// alert(url)
			$.get(url, function( data ) {
				if(data == 'mal'){
					alert("Ha ocurrido un error. Verifique los datos, e intente nuevamente")
					// alert('<?php echo $nuevafecha ?>')
				}else{
					window.location.href = "nueva_planilla_diaria.php?id_planilla="+data;
				}
			})
		}
	}

	</script>
</body>
</html>