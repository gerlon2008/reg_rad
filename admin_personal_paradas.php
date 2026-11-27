<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/miconex.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/ptr/Functions/funciones.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/niveles.php");

session_start();
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) {
    $tipo_user = $_SESSION['portal_nivel'];
    $id_user = $_SESSION['id_user'];
    // Validación de seguridad extra: idealmente acá deberías chequear que $tipo_user sea > 100 para que solo entren jefes
} else {
    echo "Esta pagina es solo para usuarios registrados.<br>";
    exit;
}

mysql_set_charset('utf8', $miconex);
mysql_select_db($database_miconex, $miconex);

// Consultas para llenar la pantalla
$query_users = "
    SELECT idusuarios, apellido, nombre 
    FROM usuarios 
    WHERE apellido IS NOT NULL AND TRIM(apellido) != '' 
      AND nombre IS NOT NULL AND TRIM(nombre) != ''
      AND apellido NOT IN ('admin', 'Control Acceso', 'cualquiera', 'fisica')
      AND idusuarios NOT IN (
          SELECT id_tabla_usuario FROM regrad_of_rad WHERE id_tabla_usuario IS NOT NULL
          UNION
          SELECT id_tabla_usuario FROM regrad_personal_parada WHERE id_tabla_usuario IS NOT NULL
      )
    ORDER BY apellido ASC
";
$res_users = mysql_query($query_users, $miconex) or die(mysql_error());
$query_personal = "
    SELECT p.id_personal, u.apellido, u.nombre, p.id_rol, p.habilitada_si_no 
    FROM regrad_personal_parada p
    INNER JOIN usuarios u ON p.id_tabla_usuario = u.idusuarios
    ORDER BY p.id_rol ASC, u.apellido ASC
";
$res_personal = mysql_query($query_personal, $miconex) or die(mysql_error());

?>
<!DOCTYPE html>
<html lang="es" style="height:100%">

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="viewport">
    <title>Registro Radiologico Online - Administración</title>
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

        .sombra {
            -webkit-box-shadow: 1px 0px 14px 2px rgba(0, 0, 0, 0.25);
            box-shadow: 1px 0px 14px 2px rgba(0, 0, 0, 0.25);
        }

        .ui-widget {
            font-family: Ubuntu;
        }

        .tabla_abm {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }

        .tabla_abm th {
            background-color: #e6e6e6;
            padding: 10px;
            border-bottom: 2px solid #ccc;
        }

        .tabla_abm td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }

        /* --- ESTILOS PARA QUE SELECT2 SE VEA COMO JQUERY UI VERDE --- */

        /* 1. El contenedor principal (el botón cerrado) */
        .select2-container--default .select2-selection--single {
            background: #c3e696;
            background: linear-gradient(to bottom, #e1f2c9 0%, #abdc73 100%);
            border: 1px solid #73a832 !important;
            border-radius: 5px !important;
            height: 32px !important;
            font-family: 'Ubuntu', sans-serif;
            font-size: 0.9em;
        }

        /* 2. El texto adentro del select (Ahora en mayúsculas) */
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #333 !important;
            line-height: 30px !important;
            padding-left: 12px !important;
            text-transform: uppercase !important;
            /* <--- MAGIA ACÁ */
        }

        /* 3. La flechita de la derecha */
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 30px !important;
        }

        /* 4. La ventanita que se despliega al hacer clic (Ahora en mayúsculas) */
        .select2-dropdown {
            border: 1px solid #73a832 !important;
            border-radius: 4px !important;
            font-family: 'Ubuntu', sans-serif;
            font-size: 0.9em;
            text-transform: uppercase !important;
            /* <--- MAGIA ACÁ */
            -webkit-box-shadow: 1px 4px 14px 0px rgba(0, 0, 0, 0.25);
            box-shadow: 1px 4px 14px 0px rgba(0, 0, 0, 0.25);
        }

        /* 5. La opción de la lista cuando le pasás el mouse por arriba */
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #73a832 !important;
            color: white !important;
        }

        /* 6. El cuadrito de texto donde tipeás la búsqueda (Opcional: tipeo en mayúsculas) */
        .select2-search--dropdown .select2-search__field {
            border: 1px solid #aaa !important;
            border-radius: 3px !important;
            text-transform: uppercase !important;
            /* <--- MAGIA ACÁ */
        }
    </style>

    <script type="text/javascript">
        $(function() {
            $(document).tooltip();
        });
    </script>
</head>

<body style="font-family:Ubuntu;width:100%;height:100%;padding:0px;margin:0px">
    <div class="container">
        <div class="mainbody" style="background-image: background-size:100% auto;background-image:url('../images/fondo_verde_registro_rad.jpg'); overflow-y: auto;">
            <div style="display: inline-block; width: 100%; text-align: center;">

                <h2 style="margin-top: 20px;">Administración: Personal de Parada de Planta</h2>

                <!-- CONTENEDOR PRINCIPAL -->
                <div class="container_icon" id="capa" style="width: 1200px; margin: auto; margin-bottom: 50px;">

                    <!-- SECCIÓN: ALTA DE PERSONAL -->
                    <div style="text-align: left; padding: 20px;" class="ui-widget-content ui-corner-all sombra">
                        <h3 style="margin-top: 0px; margin-bottom: 15px; border-bottom: 1px solid #ccc; padding-bottom: 10px;">Nuevo Registro de Personal</h3>

                        <table style="width: 100%;">
                            <tr>
                                <td style="width: 45%;">
                                    <label for="select_nuevo_usuario">Seleccionar Usuario (Sistema General): </label><br>
                                    <select style="width: 100%;" id="select_nuevo_usuario">
                                        <option value="">- Buscar usuario... -</option>
                                        <?php while ($u = mysql_fetch_assoc($res_users)) { ?>
                                            <option value="<?php echo $u['idusuarios']; ?>"><?php echo $u['apellido'] . ", " . $u['nombre']; ?></option>
                                        <?php } ?>
                                    </select>
                                </td>
                                <td style="width: 30%;">
                                    <label for="select_nuevo_rol">Rol asignado: </label><br>
                                    <select style="font-size: 0.9em; width: 90%; margin-top: 5px;" id="select_nuevo_rol" class="ui-button ui-widget ui-state-default ui-corner-all">
                                        <option value="2">Oficial de Parada</option>
                                        <option value="3">Ayudante</option>
                                    </select>
                                </td>
                                <td style="width: 25%; text-align: right; vertical-align: bottom;">
                                    <button id="btn_agregar_personal" class="ui-button ui-widget ui-state-default ui-corner-all" style="padding: 10px; width: 100%; font-weight: bold;">AGREGAR A LA LISTA</button>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- SECCIÓN: TABLA DE CONTROL -->
                    <h2 style="margin-top: 30px;">Personal Registrado</h2>
                    <div style="text-align: center; padding: 15px; background-color: white;" class="ui-widget-content ui-corner-all sombra">
                        <table class="tabla_abm">
                            <thead>
                                <tr>
                                    <th>ID Reg</th>
                                    <th style="text-align: left;">Apellido y Nombre</th>
                                    <th>Rol Asignado</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($p = mysql_fetch_assoc($res_personal)) {
                                    $rol_texto = ($p['id_rol'] == 2) ? 'Oficial de Parada' : 'Ayudante';
                                    $color_estado = ($p['habilitada_si_no'] == 1) ? '#457315' : '#cc0000';
                                    $texto_estado = ($p['habilitada_si_no'] == 1) ? 'ACTIVO' : 'INACTIVO';
                                ?>
                                    <tr>
                                        <td><?php echo $p['id_personal']; ?></td>
                                        <td style="text-align: left; font-weight: bold; text-transform: uppercase;"><?php echo $p['apellido'] . ", " . $p['nombre']; ?></td>
                                        <td>
                                            <select class="ui-widget ui-state-default ui-corner-all select-cambiar-rol" data-id="<?php echo $p['id_personal']; ?>" style="padding: 4px; font-size: 0.9em; border: 1px solid #ccc; cursor: pointer;">
                                                <option value="2" <?php if ($p['id_rol'] == 2) echo 'selected'; ?>>Oficial de Parada</option>
                                                <option value="3" <?php if ($p['id_rol'] == 3) echo 'selected'; ?>>Ayudante</option>
                                            </select>
                                        </td>
                                        <td style="color: <?php echo $color_estado; ?>; font-weight: bold;"><?php echo $texto_estado; ?></td>
                                        <td>
                                            <?php if ($p['habilitada_si_no'] == 1) { ?>
                                                <button class="ui-button ui-widget ui-state-default ui-corner-all btn-toggle-estado" style="color: #cc0000; padding: 6px 12px; cursor: pointer;" data-id="<?php echo $p['id_personal']; ?>" data-estado="0">Dar de Baja</button>
                                            <?php } else { ?>
                                                <button class="ui-button ui-widget ui-state-default ui-corner-all btn-toggle-estado" style="color: #457315; padding: 6px 12px; cursor: pointer;" data-id="<?php echo $p['id_personal']; ?>" data-estado="1">Habilitar</button>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- FOOTER Y HEADER (Igual a tu plantilla) -->
        <div class="footer" style="background-color: #457315">
            <div style="text-align: right;margin-top: 10px"></div>
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

    <!-- POPUP DE ALERTA -->
    <div class="popup" id="popup_alerta" style="text-align: center; display: none;">
        <h3 id="popup_alerta_texto1"></h3>
        <h4 id="popup_alerta_texto2"></h4>
    </div>

    <script>
        // Reutilizamos tu función de alertas
        function abrir_popup_alerta(titulo, texto1, texto2) {
            $('#popup_alerta_texto1').text(texto1);
            $('#popup_alerta_texto2').text(texto2);
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
                        $("#popup_alerta").dialog('close');
                    }
                }]
            });
        }

        $(document).ready(function() {
            $('#select_nuevo_usuario').select2({
                placeholder: "- Buscar usuario... -",
                allowClear: true
            });
            // AGREGAR PERSONAL
            $('#btn_agregar_personal').on('click', function() {
                var id_usuario = $('#select_nuevo_usuario').val();
                var id_rol = $('#select_nuevo_rol').val();

                if (id_usuario == '') {
                    abrir_popup_alerta('ATENCIÓN!!!', 'No seleccionó a nadie.', 'Elija un usuario de la lista desplegable y vuelva a intentar.');
                    return;
                }

                // Llamada AJAX al backend
                $.post("procesos_abm_personal.php", {
                    action: 'agregar_personal',
                    id_usuario: id_usuario,
                    id_rol: id_rol
                }, function(data) {
                    if (data.trim() == 'ok') {
                        location.reload();
                    } else if (data.trim() == 'existe') {
                        abrir_popup_alerta('USUARIO DUPLICADO', 'Esta persona ya se encuentra registrada en la tabla inferior.', 'Si no la ve activa, busque su nombre y haga clic en "Habilitar".');
                    } else {
                        abrir_popup_alerta('ERROR', 'Hubo un error al procesar la solicitud.', data);
                    }
                });
            });

            // HABILITAR / DESHABILITAR
            $('.btn-toggle-estado').on('click', function() {
                var id_personal = $(this).data('id');
                var nuevo_estado = $(this).data('estado');

                $.post("procesos_abm_personal.php", {
                    action: 'cambiar_estado',
                    id_personal: id_personal,
                    nuevo_estado: nuevo_estado
                }, function(data) {
                    if (data.trim() == 'ok') {
                        location.reload();
                    } else {
                        abrir_popup_alerta('ERROR', 'No se pudo cambiar el estado.', data);
                    }
                });
            });
            $('.select-cambiar-rol').on('change', function() {
                var id_personal = $(this).data('id');
                var nuevo_rol = $(this).val();

                $.post("procesos_abm_personal.php", {
                    action: 'cambiar_rol',
                    id_personal: id_personal,
                    nuevo_rol: nuevo_rol
                }, function(data) {
                    if (data.trim() !== 'ok') {
                        abrir_popup_alerta('ERROR', 'No se pudo actualizar el rol.', data);
                        location.reload(); // Recarga para volver al estado anterior si falla
                    }
                });
            });
        });
    </script>
</body>

</html>