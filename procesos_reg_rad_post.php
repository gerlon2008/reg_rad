<?php 
require_once($_SERVER["DOCUMENT_ROOT"]."/ptr/Connections/miconex.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/ptr/Functions/funciones.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/ptr/Connections/niveles.php");

mysql_select_db($database_miconex, $miconex);
mysql_query("SET NAMES 'utf8'", $miconex);

$accion = $_POST['accion'];
if($accion == "actualizar_observaciones"){
	$id_planilla = $_POST['id_planilla'];
	$id_usuario = $_POST['id_usuario'];
	$observaciones = $_POST['observaciones'];

	$query_actualizar_observaciones = "UPDATE `regrad_planilla_diaria_nueva` SET `observaciones`='$observaciones' WHERE id_planilla_diaria = '$id_planilla'";
	$actualizar_observaciones = mysql_query($query_actualizar_observaciones, $miconex) or die(mysql_error());	
}
if($accion == "actualizar_libro_actas"){
	$id_planilla = $_POST['id_planilla'];
	$libro_actas = $_POST['libro_actas'];

	$query_consultar_si_existe = "SELECT * FROM `regrad_libro_actas` WHERE `id_planilla_diaria` = '$id_planilla'";
	$consultar_si_existe = mysql_query($query_consultar_si_existe, $miconex) or die(mysql_error());
	$row_consultar_si_existe = mysql_fetch_assoc($consultar_si_existe);
	$totalRows_consultar_si_existe = mysql_num_rows($consultar_si_existe);

	
	if($totalRows_consultar_si_existe==0){
		$query_insert_novedades_libro_actas = "INSERT INTO `regrad_libro_actas`(`id_planilla_diaria`, `novedades`) VALUES ('$id_planilla','$libro_actas')";
		$insert_novedades_libro_actas = mysql_query($query_insert_novedades_libro_actas, $miconex) or die(mysql_error());	
	}else{
		$id_libro_actas = $row_consultar_si_existe['id_libro_actas'];

		$query_actualizar_novedades_libro_actas = "UPDATE `regrad_libro_actas` SET `novedades`='$libro_actas' WHERE id_libro_actas = '$id_libro_actas'";
		$actualizar_novedades_libro_actas = mysql_query($query_actualizar_novedades_libro_actas, $miconex) or die(mysql_error());	
	}

}
if($accion == "actualizar_observaciones_efluentes"){

	$id_efluentes = $_POST['id_efluentes'];
	$observaciones = acentos_mayus_todo_return(($_POST['observaciones']));

	$query_actualizar_observaciones_efluentes = "UPDATE `regrad_efluentes_liquidos_nueva` SET `observaciones`='$observaciones' WHERE id_efluentes = '$id_efluentes'";
	$actualizar_observaciones_efluentes = mysql_query($query_actualizar_observaciones_efluentes, $miconex) or die(mysql_error());	
}
if($accion == "insertar_registro_minizonas"){

	$id_usuario_web = $_POST['id_usuario_web'];
	$id_minizonas = $_POST['id_minizonas'];
	$texto_acta = $_POST['texto_acta'];
	$id_of_rad = $_POST['id_of_rad'];
	$id_ptr = $_POST['id_ptr'];

	$query_seleccionar_id_planilla_ultima = "SELECT id_planilla_diaria FROM `regrad_planilla_diaria_nueva` order by id_planilla_diaria DESC limit 1";
	$seleccionar_id_planilla_ultima = mysql_query($query_seleccionar_id_planilla_ultima, $miconex) or die(mysql_error());
	$row_seleccionar_id_planilla_ultima = mysql_fetch_assoc($seleccionar_id_planilla_ultima);
	$totalRows_seleccionar_id_planilla_ultima = mysql_num_rows($seleccionar_id_planilla_ultima);
	$id_planilla_diaria = $row_seleccionar_id_planilla_ultima['id_planilla_diaria'];

	$id_ptr = empty($id_ptr) ? 'NULL' : "'$id_ptr'";

	$query_insertar_registro_minizonas = "INSERT INTO `regrad_minizonas_actas`(`id_minizonas`, `id_of_rad`, `id_planilla_diaria`, `texto_acta`, `id_usuario_web`, `id_ptr`) VALUES ('$id_minizonas','$id_of_rad','$id_planilla_diaria','$texto_acta','$id_usuario_web',$id_ptr)";
	$insertar_registro_minizonas = mysql_query($query_insertar_registro_minizonas, $miconex);
	
	if($insertar_registro_minizonas){
		echo "ok";
	}else{
		echo "error: " . mysql_error();
	}
}
if($accion == "insertar_tarea_diaria"){

	$id_planilla = $_POST['id_planilla'];
	$id_usuario = $_POST['id_usuario'];
	$texto = mysql_real_escape_string($_POST['texto']);
	
	// Convertimos los campos vacíos a "NULL" para que SQL no tire error
	$recinto = !empty($_POST['recinto']) ? "'" . mysql_real_escape_string($_POST['recinto']) . "'" : "NULL";
	$ptr = !empty($_POST['ptr']) ? mysql_real_escape_string($_POST['ptr']) : "NULL";
	$dosis = !empty($_POST['dosis']) ? mysql_real_escape_string($_POST['dosis']) : "NULL";
	$tritio = !empty($_POST['tritio']) ? mysql_real_escape_string($_POST['tritio']) : "NULL";
	$cont_beta = !empty($_POST['cont_beta']) ? mysql_real_escape_string($_POST['cont_beta']) : "NULL";
	$cont_alfa = !empty($_POST['cont_alfa']) ? mysql_real_escape_string($_POST['cont_alfa']) : "NULL";
	
	// NUEVOS CAMPOS DE PERSONAL (Truco del NULL)
	$oficial = !empty($_POST['id_oficial_asociado']) ? "'" . mysql_real_escape_string($_POST['id_oficial_asociado']) . "'" : "NULL";
	$ayudante = !empty($_POST['id_ayudante']) ? "'" . mysql_real_escape_string($_POST['id_ayudante']) . "'" : "NULL";

	$derrame = $_POST['derrame']; // Viene como 1 o 0
	$aerosoles = $_POST['aerosoles']; // Viene como 1 o 0
	$aerosoles_dac = !empty($_POST['aerosoles_dac']) ? mysql_real_escape_string($_POST['aerosoles_dac']) : "NULL";

	$fecha_hora_carga = (new \DateTime())->format('Y-m-d H:i:s');

	// Aca agregamos id_oficial_asociado y id_ayudante
	$query_insertar_tarea = "INSERT INTO `regrad_actas_tareas_diarias` 
	(`id_planilla_diaria`, `texto_tarea`, `id_recinto`, `tasa_dosis`, `tritio_dac`, `id_ptr`, `contaminacion_beta_gamma`, `contaminacion_alfa`, `derrame_agua`, `dispersion_aerosoles`, `aerosoles_dac`, `fecha_hora_carga`, `usuario_portal`, `id_oficial_asociado`, `id_ayudante`) 
	VALUES 
	('$id_planilla', '$texto', $recinto, $dosis, $tritio, $ptr, $cont_beta, $cont_alfa, '$derrame', '$aerosoles', $aerosoles_dac, '$fecha_hora_carga', '$id_usuario', $oficial, $ayudante)";

	$insertar_tarea = mysql_query($query_insertar_tarea, $miconex);
	
	if($insertar_tarea){
		echo "ok";
	}else{
		echo "Error SQL: " . mysql_error();
	}
}if($accion == "eliminar_tarea_diaria"){
	$id_tarea = $_POST['id_tarea'];

	// 1. Buscamos la hora exacta en la que se guardó esa tarea
	$query_fecha = "SELECT fecha_hora_carga FROM `regrad_actas_tareas_diarias` WHERE id_tarea = $id_tarea";
	$resultado_fecha = mysql_query($query_fecha, $miconex);
	$row_fecha = mysql_fetch_assoc($resultado_fecha);

	if($row_fecha) {
		// 2. Calculamos los minutos reales transcurridos en el servidor
		$minutos_transcurridos = round((time() - strtotime($row_fecha['fecha_hora_carga'])) / 60);

		// 3. Evaluamos la regla de oro
		if($minutos_transcurridos <= 60) {
			$query_eliminar = "DELETE FROM `regrad_actas_tareas_diarias` WHERE id_tarea = $id_tarea";
			$eliminar = mysql_query($query_eliminar, $miconex);
			
			if($eliminar) {
				echo "ok";
			} else {
				echo "error_sql";
			}
		} else {
			// Si pasaron más de 60 min, devolvemos esta palabra clave
			echo "tiempo_agotado"; 
		}
	} else {
		echo "error_tarea_no_encontrada";
	}
}
if($accion == "insertar_registro_minizonas_V2"){

	$id_usuario_web = $_POST['id_usuario_web'];
	$id_minizonas = $_POST['id_minizonas'];
	$texto_acta = mysql_real_escape_string($_POST['texto_acta']);
	
	// Tratamiento de nulos
	$id_oficial = !empty($_POST['id_of_rad']) ? "'" . mysql_real_escape_string($_POST['id_of_rad']) . "'" : "NULL";
	$id_ayudante = !empty($_POST['id_ayudante']) ? "'" . mysql_real_escape_string($_POST['id_ayudante']) . "'" : "NULL";
	$id_recinto = !empty($_POST['id_recinto']) ? "'" . mysql_real_escape_string($_POST['id_recinto']) . "'" : "NULL";
	$id_ptr = !empty($_POST['id_ptr']) ? "'" . mysql_real_escape_string($_POST['id_ptr']) . "'" : "NULL";
	
	$dosis = !empty($_POST['dosis']) ? mysql_real_escape_string($_POST['dosis']) : "NULL";
	$tritio = !empty($_POST['tritio']) ? mysql_real_escape_string($_POST['tritio']) : "NULL";
	$cont_beta = !empty($_POST['cont_beta']) ? mysql_real_escape_string($_POST['cont_beta']) : "NULL";
	$cont_alfa = !empty($_POST['cont_alfa']) ? mysql_real_escape_string($_POST['cont_alfa']) : "NULL";
	
	$derrame = $_POST['derrame']; 
	$aerosoles = $_POST['aerosoles']; 
	$aerosoles_dac = !empty($_POST['aerosoles_dac']) ? mysql_real_escape_string($_POST['aerosoles_dac']) : "NULL";

	$fecha_hora_agregado = (new \DateTime())->format('Y-m-d H:i:s');

	$query_seleccionar_id_planilla_ultima = "SELECT id_planilla_diaria FROM `regrad_planilla_diaria_nueva` order by id_planilla_diaria DESC limit 1";
	$seleccionar_id_planilla_ultima = mysql_query($query_seleccionar_id_planilla_ultima, $miconex) or die(mysql_error());
	$row_seleccionar_id_planilla_ultima = mysql_fetch_assoc($seleccionar_id_planilla_ultima);
	$id_planilla_diaria = $row_seleccionar_id_planilla_ultima['id_planilla_diaria'];

	$query = "INSERT INTO `regrad_minizonas_registros`
	(`id_minizonas`, `id_planilla_diaria`, `texto_acta`, `id_oficial`, `id_ayudante`, `id_recinto`, `id_ptr`, `tasa_dosis`, `tritio_dac`, `contaminacion_beta_gamma`, `contaminacion_alfa`, `derrame_agua`, `dispersion_aerosoles`, `aerosoles_dac`, `fecha_hora_agregado`, `id_usuario_web`) 
	VALUES 
	('$id_minizonas', '$id_planilla_diaria', '$texto_acta', $id_oficial, $id_ayudante, $id_recinto, $id_ptr, $dosis, $tritio, $cont_beta, $cont_alfa, '$derrame', '$aerosoles', $aerosoles_dac, '$fecha_hora_agregado', '$id_usuario_web')";
	
	if(mysql_query($query, $miconex)){
		echo "ok";
	}else{
		echo "error: " . mysql_error();
	}
}
if($accion == "editar_registro_minizonas_V2"){
	$id_registro = $_POST['id_registro'];
	$texto_acta = mysql_real_escape_string($_POST['texto_acta']);
	
	// Tratamiento de nulos
	$id_oficial = !empty($_POST['id_of_rad']) ? "'" . mysql_real_escape_string($_POST['id_of_rad']) . "'" : "NULL";
	$id_ayudante = !empty($_POST['id_ayudante']) ? "'" . mysql_real_escape_string($_POST['id_ayudante']) . "'" : "NULL";
	$id_recinto = !empty($_POST['id_recinto']) ? "'" . mysql_real_escape_string($_POST['id_recinto']) . "'" : "NULL";
	$id_ptr = !empty($_POST['id_ptr']) ? "'" . mysql_real_escape_string($_POST['id_ptr']) . "'" : "NULL";
	
	$dosis = !empty($_POST['dosis']) ? mysql_real_escape_string($_POST['dosis']) : "NULL";
	$tritio = !empty($_POST['tritio']) ? mysql_real_escape_string($_POST['tritio']) : "NULL";
	$cont_beta = !empty($_POST['cont_beta']) ? mysql_real_escape_string($_POST['cont_beta']) : "NULL";
	$cont_alfa = !empty($_POST['cont_alfa']) ? mysql_real_escape_string($_POST['cont_alfa']) : "NULL";
	
	$derrame = $_POST['derrame']; 
	$aerosoles = $_POST['aerosoles']; 
	$aerosoles_dac = !empty($_POST['aerosoles_dac']) ? mysql_real_escape_string($_POST['aerosoles_dac']) : "NULL";

	$id_usuario_web = $_POST['id_usuario_web']; // <-- Atrapamos al usuario
	$es_admin = ($id_usuario_web == '0'); // <-- TU LLAVE MAESTRA

	// Verificación de seguridad
	$query_fecha = "SELECT fecha_hora_agregado FROM `regrad_minizonas_registros` WHERE id_registro = $id_registro";
	$resultado_fecha = mysql_query($query_fecha, $miconex);
	$row_fecha = mysql_fetch_assoc($resultado_fecha);

	if($row_fecha) {
		$minutos_transcurridos = round((time() - strtotime($row_fecha['fecha_hora_agregado'])) / 60);

		// Si pasaron menos de 60 min, o si sos vos (el admin)...
		if($minutos_transcurridos <= 60 || $es_admin) {
		    
		    // Armamos el sello de auditoría silencioso
            $fecha_edicion = (new \DateTime())->format('d-m-Y H:i:s');
            $sello_log = " || [EDITADO el $fecha_edicion por Usuario ID: $id_usuario_web]";
            
			$query_update = "UPDATE `regrad_minizonas_registros` SET 
				`texto_acta` = '$texto_acta',
				`id_oficial` = $id_oficial,
				`id_ayudante` = $id_ayudante,
				`id_recinto` = $id_recinto,
				`id_ptr` = $id_ptr,
				`tasa_dosis` = $dosis,
				`tritio_dac` = $tritio,
				`contaminacion_beta_gamma` = $cont_beta,
				`contaminacion_alfa` = $cont_alfa,
				`derrame_agua` = '$derrame',
				`dispersion_aerosoles` = '$aerosoles',
				`aerosoles_dac` = $aerosoles_dac,
				`log_modificaciones` = CONCAT(IFNULL(`log_modificaciones`,''), '$sello_log')
				WHERE id_registro = $id_registro";
				
			if(mysql_query($query_update, $miconex)){
				echo "ok";
			}else{
				echo "Error SQL: " . mysql_error();
			}
		} else {
			echo "tiempo_agotado"; 
		}
	} else {
		echo "error_no_encontrado";
	}
}

if($accion == "eliminar_registro_minizonas_V2"){
	$id_registro = $_POST['id_registro'];
	$id_usuario_web = $_POST['id_usuario_web']; // <-- Atrapamos al usuario
	$es_admin = ($id_usuario_web == '0'); // <-- TU LLAVE MAESTRA

	$query_fecha = "SELECT fecha_hora_agregado FROM `regrad_minizonas_registros` WHERE id_registro = $id_registro";
	$resultado_fecha = mysql_query($query_fecha, $miconex);
	$row_fecha = mysql_fetch_assoc($resultado_fecha);

	if($row_fecha) {
		$minutos_transcurridos = round((time() - strtotime($row_fecha['fecha_hora_agregado'])) / 60);

		if($minutos_transcurridos <= 60 || $es_admin) {
			$query_eliminar = "DELETE FROM `regrad_minizonas_registros` WHERE id_registro = $id_registro";
			if(mysql_query($query_eliminar, $miconex)) {
				echo "ok";
			} else {
				echo "error_sql";
			}
		} else {
			echo "tiempo_agotado"; 
		}
	} else {
		echo "error_no_encontrado";
	}
}Mysql_close()
?>