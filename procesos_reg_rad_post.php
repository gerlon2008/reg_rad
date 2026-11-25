<?php 
require_once($_SERVER["DOCUMENT_ROOT"]."/ptr/Connections/miconex.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/ptr/Functions/funciones.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/ptr/Connections/niveles.php");
require_once(__DIR__ . "/auditoria_reg_rad.php");

mysql_select_db($database_miconex, $miconex);
mysql_query("SET NAMES 'utf8'", $miconex);

$ctx_aud = auditoria_obtener_contexto($miconex);
$motivo_aud_req = auditoria_obtener_motivo_request();

function post_validar_guard($guard) {
	if (!$guard['ok']) {
		if (!empty($guard['codigo']) && function_exists('http_response_code')) {
			http_response_code($guard['codigo']);
		}
		echo "error_auditoria: " . $guard['error'];
		exit;
	}
}

$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

if($accion == "actualizar_observaciones"){
	$id_planilla = intval($_POST['id_planilla']);
	$id_usuario = $ctx_aud['desde_sesion'] ? $ctx_aud['usuario_id'] : (isset($_POST['id_usuario']) ? $_POST['id_usuario'] : '1');
	$observaciones = mysql_real_escape_string($_POST['observaciones'], $miconex);

	post_validar_guard(auditoria_exigir_permiso_escritura_planilla($miconex, $id_planilla, $motivo_aud_req));
	$antes = auditoria_snapshot_fila($miconex, 'regrad_planilla_diaria_nueva', 'id_planilla_diaria', $id_planilla);

	$query_actualizar_observaciones = "UPDATE `regrad_planilla_diaria_nueva` SET `observaciones`='$observaciones' WHERE id_planilla_diaria = '$id_planilla'";
	$actualizar_observaciones = mysql_query($query_actualizar_observaciones, $miconex) or die(mysql_error());
	if ($actualizar_observaciones) {
		auditoria_registrar($miconex, array(
			'id_planilla' => $id_planilla,
			'modulo' => 'PLANILLA',
			'accion' => 'EDITAR_OBSERVACIONES',
			'tabla_afectada' => 'regrad_planilla_diaria_nueva',
			'id_registro' => $id_planilla,
			'datos_antes' => $antes ? array('observaciones' => $antes['observaciones']) : null,
			'datos_despues' => array('observaciones' => $_POST['observaciones']),
			'fallback_uid' => $id_usuario
		));
	}
}
if($accion == "actualizar_libro_actas"){
	$id_planilla = intval($_POST['id_planilla']);
	$libro_actas_raw = isset($_POST['libro_actas']) ? $_POST['libro_actas'] : '';
	$libro_actas = mysql_real_escape_string($libro_actas_raw, $miconex);

	post_validar_guard(auditoria_exigir_permiso_escritura_planilla($miconex, $id_planilla, $motivo_aud_req));

	$query_consultar_si_existe = "SELECT * FROM `regrad_libro_actas` WHERE `id_planilla_diaria` = '$id_planilla'";
	$consultar_si_existe = mysql_query($query_consultar_si_existe, $miconex) or die(mysql_error());
	$row_consultar_si_existe = mysql_fetch_assoc($consultar_si_existe);
	$totalRows_consultar_si_existe = mysql_num_rows($consultar_si_existe);

	if($totalRows_consultar_si_existe==0){
		$query_insert_novedades_libro_actas = "INSERT INTO `regrad_libro_actas`(`id_planilla_diaria`, `novedades`) VALUES ('$id_planilla','$libro_actas')";
		$insert_novedades_libro_actas = mysql_query($query_insert_novedades_libro_actas, $miconex) or die(mysql_error());
		if ($insert_novedades_libro_actas) {
			auditoria_registrar($miconex, array(
				'id_planilla' => $id_planilla,
				'modulo' => 'ACTAS',
				'accion' => 'CREAR',
				'tabla_afectada' => 'regrad_libro_actas',
				'id_registro' => mysql_insert_id($miconex),
				'datos_despues' => array('id_planilla_diaria' => $id_planilla, 'novedades' => $libro_actas_raw)
			));
		}
	}else{
		$id_libro_actas = $row_consultar_si_existe['id_libro_actas'];
		$query_actualizar_novedades_libro_actas = "UPDATE `regrad_libro_actas` SET `novedades`='$libro_actas' WHERE id_libro_actas = '$id_libro_actas'";
		$actualizar_novedades_libro_actas = mysql_query($query_actualizar_novedades_libro_actas, $miconex) or die(mysql_error());
		if ($actualizar_novedades_libro_actas) {
			auditoria_registrar($miconex, array(
				'id_planilla' => $id_planilla,
				'modulo' => 'ACTAS',
				'accion' => 'EDITAR',
				'tabla_afectada' => 'regrad_libro_actas',
				'id_registro' => $id_libro_actas,
				'datos_antes' => $row_consultar_si_existe,
				'datos_despues' => array('id_libro_actas' => $id_libro_actas, 'id_planilla_diaria' => $id_planilla, 'novedades' => $libro_actas_raw)
			));
		}
	}
}
if($accion == "actualizar_observaciones_efluentes"){
	$id_efluentes = intval($_POST['id_efluentes']);
	$antes = auditoria_snapshot_fila($miconex, 'regrad_efluentes_liquidos_nueva', 'id_efluentes', $id_efluentes);
	$id_planilla_ef = ($antes && isset($antes['id_planilla'])) ? intval($antes['id_planilla']) : 0;
	post_validar_guard(auditoria_exigir_permiso_escritura_planilla($miconex, $id_planilla_ef, $motivo_aud_req));

	$obs_norm = acentos_mayus_todo_return(($_POST['observaciones']));
	$observaciones = mysql_real_escape_string($obs_norm, $miconex);

	$query_actualizar_observaciones_efluentes = "UPDATE `regrad_efluentes_liquidos_nueva` SET `observaciones`='$observaciones' WHERE id_efluentes = '$id_efluentes'";
	$actualizar_observaciones_efluentes = mysql_query($query_actualizar_observaciones_efluentes, $miconex) or die(mysql_error());
	if ($actualizar_observaciones_efluentes) {
		auditoria_registrar($miconex, array(
			'id_planilla' => $id_planilla_ef,
			'modulo' => 'LIQUIDOS',
			'accion' => 'EDITAR_OBSERVACIONES',
			'tabla_afectada' => 'regrad_efluentes_liquidos_nueva',
			'id_registro' => $id_efluentes,
			'datos_antes' => $antes ? array('observaciones' => $antes['observaciones']) : null,
			'datos_despues' => array('observaciones' => $obs_norm)
		));
	}
}
if($accion == "insertar_registro_minizonas"){
	$id_usuario_web = $ctx_aud['desde_sesion'] ? $ctx_aud['usuario_id'] : $_POST['id_usuario_web'];
	$id_minizonas = mysql_real_escape_string($_POST['id_minizonas'], $miconex);
	$texto_acta = mysql_real_escape_string($_POST['texto_acta'], $miconex);
	$id_of_rad = mysql_real_escape_string($_POST['id_of_rad'], $miconex);
	$id_ptr_raw = $_POST['id_ptr'];

	$query_seleccionar_id_planilla_ultima = "SELECT id_planilla_diaria FROM `regrad_planilla_diaria_nueva` order by id_planilla_diaria DESC limit 1";
	$seleccionar_id_planilla_ultima = mysql_query($query_seleccionar_id_planilla_ultima, $miconex) or die(mysql_error());
	$row_seleccionar_id_planilla_ultima = mysql_fetch_assoc($seleccionar_id_planilla_ultima);
	$id_planilla_diaria = intval($row_seleccionar_id_planilla_ultima['id_planilla_diaria']);

	post_validar_guard(auditoria_exigir_permiso_escritura_planilla($miconex, $id_planilla_diaria, $motivo_aud_req));

	$id_ptr = empty($id_ptr_raw) ? 'NULL' : "'" . mysql_real_escape_string($id_ptr_raw, $miconex) . "'";

	$query_insertar_registro_minizonas = "INSERT INTO `regrad_minizonas_actas`(`id_minizonas`, `id_of_rad`, `id_planilla_diaria`, `texto_acta`, `id_usuario_web`, `id_ptr`) VALUES ('$id_minizonas','$id_of_rad','$id_planilla_diaria','$texto_acta','$id_usuario_web',$id_ptr)";
	$insertar_registro_minizonas = mysql_query($query_insertar_registro_minizonas, $miconex);
	
	if($insertar_registro_minizonas){
		$new_id = mysql_insert_id($miconex);
		auditoria_registrar($miconex, array(
			'id_planilla' => $id_planilla_diaria,
			'modulo' => 'MINIZONAS',
			'accion' => 'CREAR',
			'tabla_afectada' => 'regrad_minizonas_actas',
			'id_registro' => $new_id,
			'datos_despues' => array('id_minizonas' => $_POST['id_minizonas'], 'id_of_rad' => $_POST['id_of_rad'], 'texto_acta' => $_POST['texto_acta'], 'id_ptr' => $id_ptr_raw),
			'fallback_uid' => $id_usuario_web
		));
		echo "ok";
	}else{
		echo "error: " . mysql_error();
	}
}
if($accion == "insertar_tarea_diaria"){
	$id_planilla = intval($_POST['id_planilla']);
	$id_usuario = $ctx_aud['desde_sesion'] ? $ctx_aud['usuario_id'] : $_POST['id_usuario'];
	$texto = mysql_real_escape_string($_POST['texto'], $miconex);

	post_validar_guard(auditoria_exigir_permiso_escritura_planilla($miconex, $id_planilla, $motivo_aud_req));
	
	// Convertimos los campos vacíos a "NULL" para que SQL no tire error
	$recinto = !empty($_POST['recinto']) ? "'" . mysql_real_escape_string($_POST['recinto'], $miconex) . "'" : "NULL";
	$ptr = !empty($_POST['ptr']) ? mysql_real_escape_string($_POST['ptr'], $miconex) : "NULL";
	$dosis = !empty($_POST['dosis']) ? mysql_real_escape_string($_POST['dosis'], $miconex) : "NULL";
	$tritio = !empty($_POST['tritio']) ? mysql_real_escape_string($_POST['tritio'], $miconex) : "NULL";
	$cont_beta = !empty($_POST['cont_beta']) ? mysql_real_escape_string($_POST['cont_beta'], $miconex) : "NULL";
	$cont_alfa = !empty($_POST['cont_alfa']) ? mysql_real_escape_string($_POST['cont_alfa'], $miconex) : "NULL";
	
	// NUEVOS CAMPOS DE PERSONAL (Truco del NULL)
	$oficial = !empty($_POST['id_oficial_asociado']) ? "'" . mysql_real_escape_string($_POST['id_oficial_asociado'], $miconex) . "'" : "NULL";
	$ayudante = !empty($_POST['id_ayudante']) ? "'" . mysql_real_escape_string($_POST['id_ayudante'], $miconex) . "'" : "NULL";

	$derrame = mysql_real_escape_string($_POST['derrame'], $miconex);
	$aerosoles = mysql_real_escape_string($_POST['aerosoles'], $miconex);
	$aerosoles_dac = !empty($_POST['aerosoles_dac']) ? mysql_real_escape_string($_POST['aerosoles_dac'], $miconex) : "NULL";

	$fecha_hora_carga = (new \DateTime())->format('Y-m-d H:i:s');

	// Aca agregamos id_oficial_asociado y id_ayudante
	$query_insertar_tarea = "INSERT INTO `regrad_actas_tareas_diarias` 
	(`id_planilla_diaria`, `texto_tarea`, `id_recinto`, `tasa_dosis`, `tritio_dac`, `id_ptr`, `contaminacion_beta_gamma`, `contaminacion_alfa`, `derrame_agua`, `dispersion_aerosoles`, `aerosoles_dac`, `fecha_hora_carga`, `usuario_portal`, `id_oficial_asociado`, `id_ayudante`) 
	VALUES 
	('$id_planilla', '$texto', $recinto, $dosis, $tritio, $ptr, $cont_beta, $cont_alfa, '$derrame', '$aerosoles', $aerosoles_dac, '$fecha_hora_carga', '$id_usuario', $oficial, $ayudante)";

	$insertar_tarea = mysql_query($query_insertar_tarea, $miconex);
	
	if($insertar_tarea){
		$new_id = mysql_insert_id($miconex);
		$despues = auditoria_snapshot_fila($miconex, 'regrad_actas_tareas_diarias', 'id_tarea', $new_id);
		auditoria_registrar($miconex, array(
			'id_planilla' => $id_planilla,
			'modulo' => 'ACTAS',
			'accion' => 'CREAR',
			'tabla_afectada' => 'regrad_actas_tareas_diarias',
			'id_registro' => $new_id,
			'datos_despues' => $despues,
			'fallback_uid' => $id_usuario
		));
		echo "ok";
	}else{
		echo "Error SQL: " . mysql_error();
	}
}
if($accion == "eliminar_tarea_diaria"){
	$id_tarea = intval($_POST['id_tarea']);
	$antes = auditoria_snapshot_fila($miconex, 'regrad_actas_tareas_diarias', 'id_tarea', $id_tarea);

	if($antes) {
		$id_planilla_t = isset($antes['id_planilla_diaria']) ? intval($antes['id_planilla_diaria']) : 0;
		post_validar_guard(auditoria_exigir_permiso_escritura_planilla($miconex, $id_planilla_t, $motivo_aud_req));

		$minutos_transcurridos = round((time() - strtotime($antes['fecha_hora_carga'])) / 60);
		$puede_bypass_tiempo = ($ctx_aud['es_u0'] || auditoria_usuario_tiene_permiso($miconex, 'editar_planillas_cerradas'));

		if($minutos_transcurridos <= 60 || $puede_bypass_tiempo) {
			$query_eliminar = "DELETE FROM `regrad_actas_tareas_diarias` WHERE id_tarea = $id_tarea";
			$eliminar = mysql_query($query_eliminar, $miconex);
			
			if($eliminar) {
				auditoria_registrar($miconex, array(
					'id_planilla' => $id_planilla_t,
					'modulo' => 'ACTAS',
					'accion' => 'ELIMINAR',
					'tabla_afectada' => 'regrad_actas_tareas_diarias',
					'id_registro' => $id_tarea,
					'datos_antes' => $antes
				));
				echo "ok";
			} else {
				echo "error_sql";
			}
		} else {
			echo "tiempo_agotado"; 
		}
	} else {
		echo "error_tarea_no_encontrada";
	}
}
if($accion == "insertar_registro_minizonas_V2"){
	$id_usuario_web = $ctx_aud['desde_sesion'] ? $ctx_aud['usuario_id'] : $_POST['id_usuario_web'];
	$id_minizonas = mysql_real_escape_string($_POST['id_minizonas'], $miconex);
	$texto_acta = mysql_real_escape_string($_POST['texto_acta'], $miconex);
	
	$id_oficial = !empty($_POST['id_of_rad']) ? "'" . mysql_real_escape_string($_POST['id_of_rad'], $miconex) . "'" : "NULL";
	$id_ayudante = !empty($_POST['id_ayudante']) ? "'" . mysql_real_escape_string($_POST['id_ayudante'], $miconex) . "'" : "NULL";
	$id_recinto = !empty($_POST['id_recinto']) ? "'" . mysql_real_escape_string($_POST['id_recinto'], $miconex) . "'" : "NULL";
	$id_ptr = !empty($_POST['id_ptr']) ? "'" . mysql_real_escape_string($_POST['id_ptr'], $miconex) . "'" : "NULL";
	
	$dosis = !empty($_POST['dosis']) ? mysql_real_escape_string($_POST['dosis'], $miconex) : "NULL";
	$tritio = !empty($_POST['tritio']) ? mysql_real_escape_string($_POST['tritio'], $miconex) : "NULL";
	$cont_beta = !empty($_POST['cont_beta']) ? mysql_real_escape_string($_POST['cont_beta'], $miconex) : "NULL";
	$cont_alfa = !empty($_POST['cont_alfa']) ? mysql_real_escape_string($_POST['cont_alfa'], $miconex) : "NULL";
	
	$derrame = mysql_real_escape_string($_POST['derrame'], $miconex); 
	$aerosoles = mysql_real_escape_string($_POST['aerosoles'], $miconex); 
	$aerosoles_dac = !empty($_POST['aerosoles_dac']) ? mysql_real_escape_string($_POST['aerosoles_dac'], $miconex) : "NULL";

	$fecha_hora_agregado = (new \DateTime())->format('Y-m-d H:i:s');

	$query_seleccionar_id_planilla_ultima = "SELECT id_planilla_diaria FROM `regrad_planilla_diaria_nueva` order by id_planilla_diaria DESC limit 1";
	$seleccionar_id_planilla_ultima = mysql_query($query_seleccionar_id_planilla_ultima, $miconex) or die(mysql_error());
	$row_seleccionar_id_planilla_ultima = mysql_fetch_assoc($seleccionar_id_planilla_ultima);
	$id_planilla_diaria = intval($row_seleccionar_id_planilla_ultima['id_planilla_diaria']);

	post_validar_guard(auditoria_exigir_permiso_escritura_planilla($miconex, $id_planilla_diaria, $motivo_aud_req));

	$query = "INSERT INTO `regrad_minizonas_registros`
	(`id_minizonas`, `id_planilla_diaria`, `texto_acta`, `id_oficial`, `id_ayudante`, `id_recinto`, `id_ptr`, `tasa_dosis`, `tritio_dac`, `contaminacion_beta_gamma`, `contaminacion_alfa`, `derrame_agua`, `dispersion_aerosoles`, `aerosoles_dac`, `fecha_hora_agregado`, `id_usuario_web`) 
	VALUES 
	('$id_minizonas', '$id_planilla_diaria', '$texto_acta', $id_oficial, $id_ayudante, $id_recinto, $id_ptr, $dosis, $tritio, $cont_beta, $cont_alfa, '$derrame', '$aerosoles', $aerosoles_dac, '$fecha_hora_agregado', '$id_usuario_web')";
	
	if(mysql_query($query, $miconex)){
		$new_id = mysql_insert_id($miconex);
		$despues = auditoria_snapshot_fila($miconex, 'regrad_minizonas_registros', 'id_registro', $new_id);
		auditoria_registrar($miconex, array(
			'id_planilla' => $id_planilla_diaria,
			'modulo' => 'MINIZONAS',
			'accion' => 'CREAR',
			'tabla_afectada' => 'regrad_minizonas_registros',
			'id_registro' => $new_id,
			'datos_despues' => $despues,
			'fallback_uid' => $id_usuario_web
		));
		echo "ok";
	}else{
		echo "error: " . mysql_error();
	}
}
if($accion == "editar_registro_minizonas_V2"){
	$id_registro = intval($_POST['id_registro']);
	$texto_acta = mysql_real_escape_string($_POST['texto_acta'], $miconex);
	
	$id_oficial = !empty($_POST['id_of_rad']) ? "'" . mysql_real_escape_string($_POST['id_of_rad'], $miconex) . "'" : "NULL";
	$id_ayudante = !empty($_POST['id_ayudante']) ? "'" . mysql_real_escape_string($_POST['id_ayudante'], $miconex) . "'" : "NULL";
	$id_recinto = !empty($_POST['id_recinto']) ? "'" . mysql_real_escape_string($_POST['id_recinto'], $miconex) . "'" : "NULL";
	$id_ptr = !empty($_POST['id_ptr']) ? "'" . mysql_real_escape_string($_POST['id_ptr'], $miconex) . "'" : "NULL";
	
	$dosis = !empty($_POST['dosis']) ? mysql_real_escape_string($_POST['dosis'], $miconex) : "NULL";
	$tritio = !empty($_POST['tritio']) ? mysql_real_escape_string($_POST['tritio'], $miconex) : "NULL";
	$cont_beta = !empty($_POST['cont_beta']) ? mysql_real_escape_string($_POST['cont_beta'], $miconex) : "NULL";
	$cont_alfa = !empty($_POST['cont_alfa']) ? mysql_real_escape_string($_POST['cont_alfa'], $miconex) : "NULL";
	
	$derrame = mysql_real_escape_string($_POST['derrame'], $miconex); 
	$aerosoles = mysql_real_escape_string($_POST['aerosoles'], $miconex); 
	$aerosoles_dac = !empty($_POST['aerosoles_dac']) ? mysql_real_escape_string($_POST['aerosoles_dac'], $miconex) : "NULL";

	$id_usuario_web = $ctx_aud['desde_sesion'] ? $ctx_aud['usuario_id'] : $_POST['id_usuario_web'];
	$es_admin = ($ctx_aud['es_u0'] || auditoria_usuario_tiene_permiso($miconex, 'editar_planillas_cerradas'));

	$antes = auditoria_snapshot_fila($miconex, 'regrad_minizonas_registros', 'id_registro', $id_registro);

	if($antes) {
		$id_planilla_m = isset($antes['id_planilla_diaria']) ? intval($antes['id_planilla_diaria']) : 0;
		post_validar_guard(auditoria_exigir_permiso_escritura_planilla($miconex, $id_planilla_m, $motivo_aud_req));

		$minutos_transcurridos = round((time() - strtotime($antes['fecha_hora_agregado'])) / 60);

		if($minutos_transcurridos <= 60 || $es_admin) {
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
				$despues = auditoria_snapshot_fila($miconex, 'regrad_minizonas_registros', 'id_registro', $id_registro);
				auditoria_registrar($miconex, array(
					'id_planilla' => $id_planilla_m,
					'modulo' => 'MINIZONAS',
					'accion' => 'EDITAR',
					'tabla_afectada' => 'regrad_minizonas_registros',
					'id_registro' => $id_registro,
					'datos_antes' => $antes,
					'datos_despues' => $despues,
					'fallback_uid' => $id_usuario_web
				));
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
	$id_registro = intval($_POST['id_registro']);
	$id_usuario_web = $ctx_aud['desde_sesion'] ? $ctx_aud['usuario_id'] : $_POST['id_usuario_web'];
	$es_admin = ($ctx_aud['es_u0'] || auditoria_usuario_tiene_permiso($miconex, 'editar_planillas_cerradas'));

	$antes = auditoria_snapshot_fila($miconex, 'regrad_minizonas_registros', 'id_registro', $id_registro);

	if($antes) {
		$id_planilla_m = isset($antes['id_planilla_diaria']) ? intval($antes['id_planilla_diaria']) : 0;
		post_validar_guard(auditoria_exigir_permiso_escritura_planilla($miconex, $id_planilla_m, $motivo_aud_req));

		$minutos_transcurridos = round((time() - strtotime($antes['fecha_hora_agregado'])) / 60);

		if($minutos_transcurridos <= 60 || $es_admin) {
			$query_eliminar = "DELETE FROM `regrad_minizonas_registros` WHERE id_registro = $id_registro";
			if(mysql_query($query_eliminar, $miconex)) {
				auditoria_registrar($miconex, array(
					'id_planilla' => $id_planilla_m,
					'modulo' => 'MINIZONAS',
					'accion' => 'ELIMINAR',
					'tabla_afectada' => 'regrad_minizonas_registros',
					'id_registro' => $id_registro,
					'datos_antes' => $antes,
					'fallback_uid' => $id_usuario_web
				));
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
}
@mysql_close();
?>