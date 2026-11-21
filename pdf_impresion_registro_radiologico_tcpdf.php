<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/ptr/Connections/miconex.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/ptr/Functions/funciones.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/ptr/Connections/niveles.php");


mysql_set_charset('utf8',$miconex);

mysql_select_db($database_miconex, $miconex);

$fecha_de_ahora = (new \DateTime())->format('d-m-Y H:i');
$fecha_de_ahora_para_archivo = (new \DateTime())->format('d-m-Y');


$id_planilla = isset($_GET['id_planilla']) ? intval($_GET['id_planilla']) : 0;

// Primero buscar sin restringir por fecha_hora_cerrado para permitir imprimir tanto planillas activas como cerradas/históricas
$query_planilla_en_curso = "SELECT * FROM regrad_planilla_diaria_nueva a 
    left join regrad_turnos b on b.id_turnos = a.turno 
    left join regrad_estados_planta c on c.id_estado_planta = a.estado_planta 
    left join regrad_of_rad d on d.id_of_rad = a.of_rad 
    left join regrad_jefes_turno e on e.id_jefes_turno = a.jefe_turno 
    where id_planilla_diaria = $id_planilla LIMIT 1";
$planilla_en_curso = mysql_query($query_planilla_en_curso, $miconex) or die(mysql_error());
$row_planilla_en_curso = mysql_fetch_assoc($planilla_en_curso);
$totalRows_planilla_en_curso = mysql_num_rows($planilla_en_curso);

if ($row_planilla_en_curso && !empty($row_planilla_en_curso['id_planilla_diaria'])) {
    $id_planilla = intval($row_planilla_en_curso['id_planilla_diaria']);
}

$query_cargar_valores_rutina = "SELECT * FROM regrad_relevamiento_rutina a left join regrad_of_rad b on b.id_of_rad = a.id_oficial left join graf_tritio_recintos c on c.id = a.ubicacion where id_planilla = $id_planilla order by fecha, hora limit 10";
$cargar_valores_rutina = mysql_query($query_cargar_valores_rutina, $miconex) or die(mysql_error());
$row_cargar_valores_rutina = mysql_fetch_assoc($cargar_valores_rutina);
$totalRows_cargar_valores_rutina = mysql_num_rows($cargar_valores_rutina);

$query_cargar_valores_no_rutinarios = "SELECT * FROM regrad_relevamiento_rutina a left join regrad_of_rad b on b.id_of_rad = a.id_oficial left join graf_tritio_recintos c on c.id = a.ubicacion where id_planilla = $id_planilla order by fecha, hora limit 10,17";
$cargar_valores_no_rutinarios = mysql_query($query_cargar_valores_no_rutinarios, $miconex) or die(mysql_error());
$row_cargar_valores_no_rutinarios = mysql_fetch_assoc($cargar_valores_no_rutinarios);
$totalRows_cargar_valores_no_rutinarios = mysql_num_rows($cargar_valores_no_rutinarios);

$query_cargar_valores_no_rutinarios_parada = "SELECT * FROM regrad_relevamiento_rutina a left join regrad_of_rad b on b.id_of_rad = a.id_oficial left join graf_tritio_recintos c on c.id = a.ubicacion where id_planilla = $id_planilla order by fecha, hora limit 27,200";
$cargar_valores_no_rutinarios_parada = mysql_query($query_cargar_valores_no_rutinarios_parada, $miconex) or die(mysql_error());
$row_cargar_valores_no_rutinarios_parada = mysql_fetch_assoc($cargar_valores_no_rutinarios_parada);
$totalRows_cargar_valores_no_rutinarios_parada = mysql_num_rows($cargar_valores_no_rutinarios_parada);

$query_recuperar_datos_gases_nobles = "SELECT * FROM `regrad_gases_nobles` where id_planilla = $id_planilla";
$recuperar_datos_gases_nobles = mysql_query($query_recuperar_datos_gases_nobles, $miconex) or die(mysql_error());
$row_recuperar_datos_gases_nobles = mysql_fetch_assoc($recuperar_datos_gases_nobles);
$totalRows_recuperar_datos_gases_nobles = mysql_num_rows($recuperar_datos_gases_nobles);

$query_recuperar_datos_emision_tritio = "SELECT * FROM `regrad_tritio` where id_planilla = $id_planilla";
$recuperar_datos_emision_tritio = mysql_query($query_recuperar_datos_emision_tritio, $miconex) or die(mysql_error());
$row_recuperar_datos_emision_tritio = mysql_fetch_assoc($recuperar_datos_emision_tritio);
$totalRows_recuperar_datos_emision_tritio = mysql_num_rows($recuperar_datos_emision_tritio);

$i = 0;
do{
    $tritio[$i]["desde"] = formato_hora_personalizado($row_recuperar_datos_emision_tritio['hora_desde']);
    $tritio[$i]["hasta"] = formato_hora_personalizado($row_recuperar_datos_emision_tritio['hora_hasta']);
    $tritio[$i]["vent_gral"] = nulo(decimales(conversion_actividad('1','5',$row_recuperar_datos_emision_tritio['vent_gral']),1));
    $tritio[$i]["vent_reactor"] = nulo(decimales(conversion_actividad('1','5',$row_recuperar_datos_emision_tritio['vent_reactor']),1));
    $i++;
}while($row_recuperar_datos_emision_tritio = mysql_fetch_assoc($recuperar_datos_emision_tritio));
if($i<3){
    do{
        $tritio[$i]["desde"] = "-";
        $tritio[$i]["hasta"] = "-";
        $tritio[$i]["vent_gral"] = "-";
        $tritio[$i]["vent_reactor"] = "-";
        $i++;
    }while($i<=3);
}


$query_recuperar_datos_carbono14 = "SELECT * FROM `regrad_carbono14` where id_planilla = $id_planilla";
$recuperar_datos_carbono14 = mysql_query($query_recuperar_datos_carbono14, $miconex) or die(mysql_error());
$row_recuperar_datos_carbono14 = mysql_fetch_assoc($recuperar_datos_carbono14);
$totalRows_recuperar_datos_carbono14 = mysql_num_rows($recuperar_datos_carbono14);

$query_recuperar_datos_dedo_frio = "SELECT * FROM `regrad_dedo_frio` where id_planilla = $id_planilla";
$recuperar_datos_dedo_frio = mysql_query($query_recuperar_datos_dedo_frio, $miconex) or die(mysql_error());
$row_recuperar_datos_dedo_frio = mysql_fetch_assoc($recuperar_datos_dedo_frio);
$totalRows_recuperar_datos_dedo_frio = mysql_num_rows($recuperar_datos_dedo_frio);

$datos_dedo_frio = "";
if($row_recuperar_datos_dedo_frio['menor_que']==1){
    $datos_dedo_frio = "< ";
};
$datos_dedo_frio = $datos_dedo_frio.nulo(decimales($row_recuperar_datos_dedo_frio['ppm'],0));

$query_recuperar_datos_concentrador7923 = "SELECT
                                                sub.id_planilla,
                                                GROUP_CONCAT(DISTINCT sub.fecha) AS fecha,
                                                CASE 
                                                    WHEN COUNT(DISTINCT sub.procedencia) = 1 THEN MAX(sub.procedencia)
                                                    ELSE '-VARIAS-'
                                                END AS procedencia,
                                                CASE 
                                                    WHEN COUNT(DISTINCT sub.observaciones) = 1 THEN MAX(sub.observaciones)
                                                    ELSE 'VARIAS'
                                                END AS observaciones,
                                                SUM(DISTINCT sub.volumen_litro) * 1000 AS volumen_litro,
                                                SUM(r.actividad_TBq) AS actividad_TBq,
                                                SUM(r.parcial_ldd) AS parcial_ldd
                                                FROM (
                                                SELECT
                                                    a.id_tritio_conc,
                                                    a.id_planilla,
                                                    a.fecha,
                                                    a.procedencia,
                                                    a.observaciones,
                                                    a.volumen_litro
                                                FROM regrad_tritio_concentrador a
                                                WHERE a.id_planilla = $id_planilla
                                                ) sub
                                                LEFT JOIN reg_rad_concentrador_radionucleidos r
                                                ON r.id_tritio_conc = sub.id_tritio_conc
                                                GROUP BY sub.id_planilla;
                                                ";
$recuperar_datos_concentrador7923 = mysql_query($query_recuperar_datos_concentrador7923, $miconex) or die(mysql_error());
$row_recuperar_datos_concentrador7923 = mysql_fetch_assoc($recuperar_datos_concentrador7923);
$totalRows_recuperar_datos_concentrador7923 = mysql_num_rows($recuperar_datos_concentrador7923);

$query_recuperar_datos_control_precintos = "SELECT * FROM `regrad_control_precintos` where id_planilla = $id_planilla";
$recuperar_datos_control_precintos = mysql_query($query_recuperar_datos_control_precintos, $miconex) or die(mysql_error());
$row_recuperar_datos_control_precintos = mysql_fetch_assoc($recuperar_datos_control_precintos);
$totalRows_recuperar_datos_control_precintos = mysql_num_rows($recuperar_datos_control_precintos);

$query_recuperar_datos_condicion_meteorologica = "SELECT * FROM `regrad_cond_meteorologica` where id_planilla = $id_planilla";
$recuperar_datos_condicion_meteorologica = mysql_query($query_recuperar_datos_condicion_meteorologica, $miconex) or die(mysql_error());
$row_recuperar_datos_condicion_meteorologica = mysql_fetch_assoc($recuperar_datos_condicion_meteorologica);
$totalRows_recuperar_datos_condicion_meteorologica = mysql_num_rows($recuperar_datos_condicion_meteorologica);

$query_recuperar_datos_espectrometria_aire = "SELECT *, b.recinto as recinto_nombre FROM regrad_espectr_conc_radionuclei a left join graf_tritio_recintos b on b.id = a.recinto  where id_planilla = $id_planilla ";
$recuperar_datos_espectrometria_aire = mysql_query($query_recuperar_datos_espectrometria_aire, $miconex) or die(mysql_error());
$row_recuperar_datos_espectrometria_aire = mysql_fetch_assoc($recuperar_datos_espectrometria_aire);
$totalRows_recuperar_datos_espectrometria_aire = mysql_num_rows($recuperar_datos_espectrometria_aire);

$query_recuperar_datos_efluentes_liquidos = "SELECT * FROM `reg_rad_ef_liq_final_para_tabla` where id_planilla = $id_planilla ";
$recuperar_datos_efluentes_liquidos = mysql_query($query_recuperar_datos_efluentes_liquidos, $miconex) or die(mysql_error());
$row_recuperar_datos_efluentes_liquidos = mysql_fetch_assoc($recuperar_datos_efluentes_liquidos);
$totalRows_recuperar_datos_efluentes_liquidos = mysql_num_rows($recuperar_datos_efluentes_liquidos);

$query_cargar_valores_contaminacion_superficial_1 = "SELECT *, b.recinto as recinto_nombre FROM regrad_contaminacion_superficial a left join graf_tritio_recintos b on b.id = a.recinto where id_planilla = $id_planilla order by fecha, hora LIMIT 10";
$cargar_valores_contaminacion_superficial_1 = mysql_query($query_cargar_valores_contaminacion_superficial_1, $miconex) or die(mysql_error());
$row_cargar_valores_contaminacion_superficial_1 = mysql_fetch_assoc($cargar_valores_contaminacion_superficial_1);
$totalRows_cargar_valores_contaminacion_superficial_1 = mysql_num_rows($cargar_valores_contaminacion_superficial_1);

$query_cargar_valores_contaminacion_superficial_2 = "SELECT *, b.recinto as recinto_nombre FROM regrad_contaminacion_superficial a left join graf_tritio_recintos b on b.id = a.recinto where id_planilla = $id_planilla order by fecha, hora LIMIT 10,10";
$cargar_valores_contaminacion_superficial_2 = mysql_query($query_cargar_valores_contaminacion_superficial_2, $miconex) or die(mysql_error());
$row_cargar_valores_contaminacion_superficial_2 = mysql_fetch_assoc($cargar_valores_contaminacion_superficial_2);
$totalRows_cargar_valores_contaminacion_superficial_2 = mysql_num_rows($cargar_valores_contaminacion_superficial_2);

$query_cargar_valores_eficiencia_dryers = "SELECT * FROM reg_rad_eficiencia_dryers where id_planilla = $id_planilla";
$cargar_valores_eficiencia_dryers = mysql_query($query_cargar_valores_eficiencia_dryers, $miconex) or die(mysql_error());
$row_cargar_valores_eficiencia_dryers = mysql_fetch_assoc($cargar_valores_eficiencia_dryers);
$totalRows_cargar_valores_eficiencia_dryers = mysql_num_rows($cargar_valores_eficiencia_dryers);

$query_campo_observaciones = "SELECT * FROM `regrad_textos_varios` where dato_clave = 'observaciones_registro_predeterminadas'";
$campo_observaciones = mysql_query($query_campo_observaciones, $miconex) or die(mysql_error());
$row_campo_observaciones = mysql_fetch_assoc($campo_observaciones);

if(is_null($row_planilla_en_curso['observaciones']) or $row_planilla_en_curso['observaciones']==""){
    $campo_observaciones = $row_campo_observaciones['dato_contenido'];
}else{
    $campo_observaciones = $row_planilla_en_curso['observaciones'];
}


/* incluimos primeramente el archivo que contiene la clase TCPDF */
if (!class_exists('TCPDF')) {
    $tcpdf_candidates = array(
        __DIR__ . '/../TCPDF-master/tcpdf.php',
        dirname(__DIR__) . '/TCPDF-master/tcpdf.php',
        (isset($_SERVER["DOCUMENT_ROOT"]) ? $_SERVER["DOCUMENT_ROOT"] . '/ptr/TCPDF-master/tcpdf.php' : ''),
        (isset($_SERVER["DOCUMENT_ROOT"]) ? $_SERVER["DOCUMENT_ROOT"] . '/TCPDF-master/tcpdf.php' : ''),
        __DIR__ . '/tcpdf.php',
        __DIR__ . '/../tcpdf.php',
        __DIR__ . '/../../tcpdf.php',
        (isset($_SERVER["DOCUMENT_ROOT"]) ? $_SERVER["DOCUMENT_ROOT"] . '/ptr/TCPDF-master/examples/tcpdf_include.php' : ''),
        __DIR__ . '/../TCPDF-master/examples/tcpdf_include.php'
    );
    foreach ($tcpdf_candidates as $candidate) {
        if (!empty($candidate) && file_exists($candidate)) {
            require_once($candidate);
            if (class_exists('TCPDF')) {
                break;
            }
        }
    }
}

if (!class_exists('TCPDF')) {
    die("Error crítico: No se pudo localizar la librería TCPDF en ../TCPDF-master/tcpdf.php ni en las rutas del servidor.");
}
class MYPDF extends TCPDF {

    public function Header()
    {
        global $id_efluentes,$fecha_de_ahora;
//global $ptr_header;
        if ($this->page != 1)
        {
            $this->SetFont('Helvetica', 'BI', 10);

            $this->Image($_SERVER["DOCUMENT_ROOT"]."/ptr/logo_nasa.png",15,12,35);
            $this->Cell(0,3,'','LRT',0,'');
            $this->Ln(3);
            $this->Cell(35,7,'','L',0,'L');
            $this->Cell(62,7,('NUCLEOELECTRICA ARGENTINA S.A.'),'',0,'L');
            $this->SetFont('Helvetica', 'I', 9);
            $this->Cell(89,4,($fecha_de_ahora.'   '),'R',0,'R');
            $this->Ln(4);
            $this->Cell(35,10,'','L',0,'L');
            $this->SetFont('Helvetica', '', 10);
            $this->Cell(62,10,('Central Nuclear Embalse'),'',0,'L');
            $this->SetFont('Helvetica', 'B', 10);
            $this->Cell(89,10,'','R',0,'R');
            $this->Ln(8);
            $this->SetFont('Helvetica', 'BI', 20);
            $this->Cell(0,12,('REGISTRO RADIOLÓGICO'),'LRB',0,'C');
            $this->Ln(13);

        }
    }
    function Footer()
    {
        global $row_planilla_en_curso;
//Posición: a 1,5 cm del final
//global $id_user,$fechaactual;
        $cuenta_pagina = '             Pagina '.$this->getAliasNumPage()." / ".$this->getAliasNbPages();
        $this->SetFillColor(230, 230, 230);
        $this->SetY(-13);
//Arial italic 8
        $this->SetFont('Helvetica','B',8);
//Número de página
        $this->SetFillColor(245, 245, 245);
        $this->SetFillColor(235, 235, 235);
        $this->Cell(25,4,('Fecha'),'LTR',0,'C',TRUE);
        $this->Cell(25,4,('Turno'),'LTR',0,'C',TRUE);
        $this->Cell(50,4,('Oficial de Radioprotección'),'LTR',0,'C',TRUE);
        $this->Cell(50,4,('Jefe de Turno'),'LTR',0,'C',TRUE);
        $this->Cell(0,8,($cuenta_pagina),'LTBR',0,'L',TRUE);
        $this->Ln(4);
        $this->SetFont('Helvetica','',8);
        $this->Cell(25,4,(formafecha6($row_planilla_en_curso['fecha'])),'LBR',0,'C',TRUE);
        $this->Cell(25,4,(($row_planilla_en_curso['horarios'])),'LBR',0,'C',TRUE);
        $this->Cell(50,4,(($row_planilla_en_curso['of_apellido'].", ".$row_planilla_en_curso['of_nombre'])),'LBR',0,'C',TRUE);
        $this->Cell(50,4,(($row_planilla_en_curso['jefe_apellido'].", ".$row_planilla_en_curso['jefe_nombre'])),'LBR',0,'C',TRUE);
    }
}

// create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('LONCAR, GERMAN');
$pdf->SetTitle('REGISTRO RADIOLÓGICO');
$pdf->SetSubject('INFORME DIARIO');
$pdf->SetKeywords('RADIOPROTECCIÓN');

$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(12, 10, 12);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(false, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set font
$pdf->SetFont('times', '', 11);

// add a page
$pdf->AddPage();

//Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=0, $link='', $stretch=0, $ignore_min_height=false, $calign='T', $valign='M')

// test Cell stretching
$pdf->SetFont('Helvetica', 'BI', 9);
$pdf->Image($_SERVER["DOCUMENT_ROOT"]."/ptr/logo_nasa.png",15,12,35);
$pdf->Cell(0,3,'','LRT',0,'');
$pdf->Ln(3);
$pdf->Cell(35,7,'','L',0,'L');
$pdf->Cell(62,7,('NUCLEOELECTRICA ARGENTINA S.A.'),'',0,'L');
$pdf->Cell(89,4,($fecha_de_ahora.'   '),'R',0,'R');
$pdf->Ln(4);
$pdf->Cell(35,10,'','L',0,'L');
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(62,10,('Central Nuclear Embalse'),'',0,'L');
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell(89,10,'','R',0,'R');
$pdf->Ln(8);
$pdf->SetFont('Helvetica', 'BI', 20);
$pdf->Cell(0,12,('REGISTRO RADIOLÓGICO'),'LRB',0,'C');

$pdf->Ln(0);

$pdf->SetFont('Helvetica', 'B', 12);
$pdf->Ln(13);
$pdf->SetFillColor(219, 219, 219);
$pdf->Cell(0,8,('RELEVAMIENTO DE RUTINA'),'LTBR',0,'C',TRUE);
$pdf->Ln(8);

$pdf->SetFillColor(235, 235, 235);
$pdf->SetFont('Helvetica', '', 9);
$pdf->Cell(16,12,('HORA'),'LTBR',0,'C',TRUE);
$pdf->Cell(20,12,('UBICACIÓN'),'LTBR',0,'C',TRUE);
$pdf->Cell(22,8,('G. NOBLES'),'LTBR',0,'C',TRUE);
$pdf->Cell(22,8,('TRITIO'),'LTBR',0,'C',TRUE);
$pdf->Cell(22,8,('TASA DOSIS'),'LTR',0,'C',TRUE);
$pdf->Cell(50,12,('OBSERVACIONES'),'LBTR',0,'C',TRUE);
$pdf->Cell(0,12,('RELEVADO POR'),'LBTR',0,'C',TRUE);
$pdf->Ln(6);
$pdf->Cell(36);
$pdf->SetFont('Helvetica', 'I', 7);
$pdf->Cell(22,6,('(DAC)'),'LBR',0,'C',TRUE);
$pdf->Cell(22,6,('(DAC)'),'LBR',0,'C',TRUE);
$pdf->Cell(22,6,('(µSv/h)'),'LBR',0,'C',TRUE);
$pdf->Ln(6);
$contador = 0;
if($totalRows_cargar_valores_rutina==0){
    $pdf->Cell(0,6,('NO HAY DATOS CARGADOS'),'LBRT',0,'C',TRUE);
$pdf->Ln(6);
}else{
    do{
        if ($contador%2==0){
            $pdf->SetFillColor(256, 256, 256);
        }else{
            $pdf->SetFillColor(245, 245, 245);
        }
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->Cell(16,5,(formato_hora_2($row_cargar_valores_rutina['hora'])),'LBRT',0,'C',TRUE);
        $pdf->Cell(20,5,(($row_cargar_valores_rutina['recinto'])),'LBRT',0,'C',TRUE);
        $pdf->Cell(22,5,(nulo(decimales($row_cargar_valores_rutina['gases_nobles'],1))),'LBRT',0,'C',TRUE);
        $pdf->Cell(22,5,(nulo(decimales($row_cargar_valores_rutina['tritio'],1))),'LBRT',0,'C',TRUE);
        $pdf->Cell(22,5,(nulo(decimales($row_cargar_valores_rutina['tasa_dosis'],1))),'LBRT',0,'C',TRUE);
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell(50,5,($row_cargar_valores_rutina['observaciones']),'LBRT',0,'C',TRUE);
        $pdf->Cell(0,5,($row_cargar_valores_rutina['of_apellido'].", ".$row_cargar_valores_rutina['of_nombre']),'LBRT',0,'C',TRUE);
        $pdf->Ln(5);
        $contador++;
    }while($row_cargar_valores_rutina = mysql_fetch_assoc($cargar_valores_rutina));
}
if($contador<10){
    do{
         if ($contador%2==0){
            $pdf->SetFillColor(256, 256, 256);
        }else{
            $pdf->SetFillColor(245, 245, 245);
        }
        $pdf->Cell(16,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->Cell(20,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->Cell(22,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->Cell(22,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->Cell(22,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell(50,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->Cell(0,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->Ln(5);
        $contador++;
    }while($contador<10);
}
$pdf->Ln(1);
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->SetFillColor(219, 219, 219);
$pdf->Cell(0,8,('EMISIÓN DE EFLUENTES GASEOSOS'),'LTBR',0,'C',TRUE);
$pdf->SetFillColor(235, 235, 235);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Ln(8);
$pdf->Cell(108,7,('GASES NOBLES (GBq)'),'LTBR',0,'C',TRUE);
$pdf->Cell(0,7,('EMISIÓN TRITIO'),'LTBR',0,'C',TRUE);
$pdf->Ln(7);
$pdf->SetFont('Helvetica', '', 11);
$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(18,8,('Desde'),'LTBR',0,'C',TRUE);
$pdf->SetFillColor(256, 256, 256);
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(36,8,(nulo2(formato_hora_personalizado($row_recuperar_datos_gases_nobles['hora_desde']))),'LTBR',0,'C',TRUE);
$pdf->SetFillColor(245, 245, 245);
$pdf->SetFont('Helvetica', '', 11);
$pdf->Cell(18,8,('Hasta'),'LTBR',0,'C',TRUE);
$pdf->SetFillColor(256, 256, 256);
$pdf->SetFont('Helvetica', '', 10);
$pdf->Cell(36,8,(nulo2(formato_hora_personalizado($row_recuperar_datos_gases_nobles['hora_hasta']))),'LTBR',0,'C',TRUE);
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(19.5,8,('Desde'),'LTBR',0,'C',TRUE);
$pdf->Cell(19.5,8,('Hasta'),'LTBR',0,'C',TRUE);
$pdf->Cell(19.5,4,('Vent.Gral'),'LTR',0,'C',TRUE);
$pdf->Cell(19.5,4,('Vent.React.'),'LTR',0,'C',TRUE);
$pdf->Ln(4);
$pdf->Cell(147);
$pdf->SetFont('Helvetica', 'I', 7);
$pdf->Cell(19.5,4,('(GBq)'),'LBR',0,'C',TRUE);
$pdf->Cell(0,4,('(GBq)'),'LBR',0,'C',TRUE);
$pdf->Ln(4);
$pdf->SetFont('Helvetica', '', 9);
$pdf->Cell(18,6,('Ar-41'),'LTBR',0,'C',TRUE);
$pdf->Cell(18,6,('Kr-85'),'LTBR',0,'C',TRUE);
$pdf->Cell(18,6,('Kr-85m'),'LTBR',0,'C',TRUE);
$pdf->Cell(18,6,('Xe-133'),'LTBR',0,'C',TRUE);
$pdf->Cell(18,6,('Xe-135'),'LTBR',0,'C',TRUE);
$pdf->Cell(18,6,('Xe-135m'),'LTBR',0,'C',TRUE);
$pdf->SetFillColor(256, 256, 256);
$pdf->SetFont('Helvetica', '', 7);
$pdf->Cell(19.5,4,(nulo2($tritio[0]["desde"])),'LTBR',0,'C',TRUE);
$pdf->Cell(19.5,4,(nulo2($tritio[0]['hasta'])),'LTBR',0,'C',TRUE);
$pdf->Cell(19.5,4,($tritio[0]['vent_gral']),'LTBR',0,'C',TRUE);
$pdf->Cell(0,4,($tritio[0]['vent_reactor']),'LTBR',0,'C',TRUE);
$pdf->Ln(4);

$pdf->Cell(108);
$pdf->Cell(19.5,4,($tritio[1]["desde"]),'LTBR',0,'C',TRUE);
$pdf->Cell(19.5,4,($tritio[1]['hasta']),'LTBR',0,'C',TRUE);
$pdf->Cell(19.5,4,($tritio[1]['vent_gral']),'LTBR',0,'C',TRUE);
$pdf->Cell(0,4,($tritio[1]['vent_reactor']),'LTBR',0,'C',TRUE);
$pdf->Ln(2);

$pdf->SetFont('Helvetica', '', 9);
$pdf->Cell(18,6,(nulo(decimales(conversion_actividad('1','5',$row_recuperar_datos_gases_nobles['ar41']),1))),'LTBR',0,'C',TRUE);
$pdf->Cell(18,6,(nulo(decimales(conversion_actividad('1','5',$row_recuperar_datos_gases_nobles['kr85']),1))),'LTBR',0,'C',TRUE);
$pdf->Cell(18,6,(nulo(decimales(conversion_actividad('1','5',$row_recuperar_datos_gases_nobles['kr85m']),1))),'LTBR',0,'C',TRUE);
$pdf->Cell(18,6,(nulo(decimales(conversion_actividad('1','5',$row_recuperar_datos_gases_nobles['xe133']),1))),'LTBR',0,'C',TRUE);
$pdf->Cell(18,6,(nulo(decimales(conversion_actividad('1','5',$row_recuperar_datos_gases_nobles['xe135']),1))),'LTBR',0,'C',TRUE);
$pdf->Cell(18,6,(nulo(decimales(conversion_actividad('1','5',$row_recuperar_datos_gases_nobles['xe135m']),1))),'LTBR',0,'C',TRUE);

$pdf->SetFont('Helvetica', '', 7);
$pdf->Ln(2);
$pdf->Cell(108);
$pdf->Cell(19.5,4,($tritio[2]["desde"]),'LTBR',0,'C',TRUE);
$pdf->Cell(19.5,4,($tritio[2]['hasta']),'LTBR',0,'C',TRUE);
$pdf->Cell(19.5,4,($tritio[2]['vent_gral']),'LTBR',0,'C',TRUE);
$pdf->Cell(0,4,($tritio[2]['vent_reactor']),'LTBR',0,'C',TRUE);
$pdf->Ln(4);

$pdf->SetFillColor(235, 235, 235);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell(36,7,('CARBONO 14'),'LTBR',0,'C',TRUE);
$pdf->Cell(36,7,('DEDO FRÍO 7312'),'LTBR',0,'C',TRUE);
$pdf->Cell(0,7,('TRITIO CONCENTRADOR 7923'),'LTBR',0,'C',TRUE);
$pdf->Ln(7);
$pdf->SetFillColor(245, 245, 245);
$pdf->SetFont('Helvetica', '', 9);
$pdf->Cell(18,6,('Hora'),'LTBR',0,'C',TRUE);
$pdf->Cell(18,6,('MBq'),'LTBR',0,'C',TRUE);
$pdf->Cell(18,6,('Hora'),'LTBR',0,'C',TRUE);
$pdf->Cell(18,6,('ppm'),'LTBR',0,'C',TRUE);
$pdf->Cell(28,6,('Procedencia:'),'LTBR',0,'C',TRUE);
$pdf->SetFillColor(256, 256, 256);
$pdf->Cell(29, 6, strtoupper(nulo2($row_recuperar_datos_concentrador7923['procedencia'])), 'LTBR', 0, 'C', TRUE);
$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(28,6,('Actividad (TBq):'),'LTBR',0,'C',TRUE);
$pdf->SetFillColor(256, 256, 256);
$pdf->Cell(0,6,(coma_cambio2(nulo(decimales($row_recuperar_datos_concentrador7923['actividad_TBq'],3)))),'LTBR',0,'C',TRUE);
$pdf->Ln(6);
$pdf->Cell(18,6,(nulo(formato_hora_personalizado($row_recuperar_datos_carbono14['hora']))),'LTBR',0,'C',TRUE);
$pdf->Cell(18,6,(nulo(decimales(conversion_actividad('1','3',$row_recuperar_datos_carbono14['medicion']),0))),'LTBR',0,'C',TRUE);
$pdf->Cell(18,6,(nulo(formato_hora_personalizado($row_recuperar_datos_dedo_frio['hora_medicion']))),'LTBR',0,'C',TRUE);
$pdf->Cell(18,6,($datos_dedo_frio),'LTBR',0,'C',TRUE);
$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(28,6,('Volúmen (m3):'),'LTBR',0,'C',TRUE);
$pdf->SetFillColor(256, 256, 256);
$pdf->Cell(29,6,(coma_cambio2(nulo(decimales($row_recuperar_datos_concentrador7923['volumen_litro'],2)))),'LTBR',0,'C',TRUE);
$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(28,6,('RoDD(%):'),'LTBR',0,'C',TRUE);
$pdf->SetFillColor(256, 256, 256);
$pdf->Cell(0,6,(coma_cambio2(nulo(decimales($row_recuperar_datos_concentrador7923['parcial_ldd'],2)))),'LTBR',0,'C',TRUE);
$pdf->Ln(6);
$pdf->SetFillColor(235, 235, 235);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->Cell(72,7,('CONTROL DE PRECINTOS'),'LTBR',0,'C',TRUE);
$pdf->Cell(0,7,('CONDICIÓN METEOROLÓGICA'),'LTBR',0,'C',TRUE);
$pdf->Ln(7);
$pdf->SetFillColor(245, 245, 245);
$pdf->SetFont('Helvetica', '', 9);
$pdf->Cell(18,6,('Hora'),'LTBR',0,'C',TRUE);
$pdf->SetFont('Helvetica', '', 7);
$pdf->Cell(18,4,('Puerta'),'LTR',0,'C',TRUE);
$pdf->Cell(18,4,('Puerta'),'LTR',0,'C',TRUE);
$pdf->Cell(18,4,('Puerta'),'LTR',0,'C',TRUE);
$pdf->SetFont('Helvetica', '', 9);
$pdf->Cell(28.5,6,('Hora'),'LTBR',0,'C',TRUE);
$pdf->Cell(28.5,6,('Velocidad (m/seg)'),'LTBR',0,'C',TRUE);
$pdf->Cell(28.5,6,('Dirección del viento'),'LTBR',0,'C',TRUE);
$pdf->Cell(28.5,6,('Condición Pasquill'),'LTBR',0,'C',TRUE);
$pdf->Ln(3);
$pdf->Cell(18);
$pdf->SetFont('Helvetica', '', 7);
$pdf->Cell(18,3,('S-123B'),'LBR',0,'C',TRUE);
$pdf->Cell(18,3,('S-008'),'LBR',0,'C',TRUE);
$pdf->Cell(18,3,('S-144'),'LBR',0,'C',TRUE);
$pdf->Ln(3);
$pdf->SetFillColor(256, 256, 256);
$pdf->SetFont('Helvetica', '', 9);
$pdf->Cell(18,6,(nulo2(formato_hora_personalizado($row_recuperar_datos_control_precintos['hora']))),'LTBR',0,'C',TRUE);
$pdf->Cell(18,6,(nulo2(ucwords($row_recuperar_datos_control_precintos['puerta_s123B']))),'LTBR',0,'C',TRUE);
$pdf->Cell(18,6,(nulo2(ucwords($row_recuperar_datos_control_precintos['puerta_s008']))),'LTBR',0,'C',TRUE);
$pdf->Cell(18,6,(nulo2(ucwords($row_recuperar_datos_control_precintos['puerta_s144']))),'LTBR',0,'C',TRUE);
$pdf->Cell(28.5,6,(nulo2(formato_hora_personalizado($row_recuperar_datos_condicion_meteorologica['hora']))),'LTBR',0,'C',TRUE);
$pdf->Cell(28.5,6,(nulo2(ucwords($row_recuperar_datos_condicion_meteorologica['velocidad_mseg']))),'LTBR',0,'C',TRUE);
$pdf->Cell(28.5,6,(nulo2(ucwords($row_recuperar_datos_condicion_meteorologica['direc_viento']))),'LTBR',0,'C',TRUE);
$pdf->Cell(28.5,6,(nulo2(ucwords($row_recuperar_datos_condicion_meteorologica['cond_pasquill']))),'LTBR',0,'C',TRUE);
$pdf->Ln(6);
$pdf->Ln(1);
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->SetFillColor(219, 219, 219);


$pdf->Cell(0,8,('ESPECTROMETRÍA DEL AIRE EN ZONA CONTROLADA'),'LTBR',0,'C',TRUE);
$pdf->Ln(8);
$pdf->SetFillColor(245, 245, 245);
$pdf->SetFont('Helvetica',  '', 10);
$pdf->Cell(40,12,('Concentración de'),'LTBR',0,'C',TRUE);
$pdf->Cell(26,12,('Recinto'),'LTBR',0,'C',TRUE);
$pdf->Cell(20,12,('Hora'),'LTBR',0,'C',TRUE);
$pdf->SetFont('Helvetica',  '', 9);
$pdf->Cell(0,4,('Radionucleídos'),'LTBR',0,'C',TRUE);
$pdf->Ln(4);
$pdf->SetFont('Helvetica',  '', 8);
$pdf->Cell(86);
$pdf->Cell(20,5,('Ar-41'),'LTR',0,'C',TRUE);
$pdf->Cell(20,5,('Xe-133'),'LTR',0,'C',TRUE);
$pdf->Cell(20,5,('Xe-135'),'LTR',0,'C',TRUE);
$pdf->Cell(20,5,('Kr-88'),'LTR',0,'C',TRUE);
$pdf->Cell(20,8,('Total'),'LTBR',0,'C',TRUE);
$pdf->Ln(4);
$pdf->Cell(86);
$pdf->SetFont('Helvetica',  'I', 7);
$pdf->Cell(20,4,('(DAC)'),'LBR',0,'C',TRUE);
$pdf->Cell(20,4,('(DAC)'),'LBR',0,'C',TRUE);
$pdf->Cell(20,4,('(DAC)'),'LBR',0,'C',TRUE);
$pdf->Cell(20,4,('(DAC)'),'LBR',0,'C',TRUE);
$pdf->Ln(4);
$contador5 = 0;

do{
    if($contador5%2==0){
        $pdf->SetFillColor(256, 256, 256);
    }else{
        $pdf->SetFillColor(245, 245, 245);
    }

    $pdf->SetFillColor(256, 256, 256);
    $pdf->SetFont('Helvetica',  '', 9);
    $pdf->Cell(40,6,(strtoupper($row_recuperar_datos_espectrometria_aire['tipo'])),'LTBR',0,'C',TRUE);
    $pdf->Cell(26,6,(nulo2($row_recuperar_datos_espectrometria_aire['recinto_nombre'])),'LTBR',0,'C',TRUE);
    $pdf->Cell(20,6,(nulo2(formato_hora_personalizado($row_recuperar_datos_espectrometria_aire['hora']))),'LTBR',0,'C',TRUE);
    $pdf->Cell(20,6,(nulo(decimales($row_recuperar_datos_espectrometria_aire['ar41'],1))),'LTBR',0,'C',TRUE);
    $pdf->Cell(20,6,(nulo(decimales($row_recuperar_datos_espectrometria_aire['xe133'],1))),'LTBR',0,'C',TRUE);
    $pdf->Cell(20,6,(nulo(decimales($row_recuperar_datos_espectrometria_aire['xe135'],1))),'LTBR',0,'C',TRUE);
    $pdf->Cell(20,6,(nulo(decimales($row_recuperar_datos_espectrometria_aire['kr88'],1))),'LTBR',0,'C',TRUE);
    $pdf->Cell(20,6,(nulo(decimales(($row_recuperar_datos_espectrometria_aire['ar41']+$row_recuperar_datos_espectrometria_aire['xe133']+$row_recuperar_datos_espectrometria_aire['xe135']+$row_recuperar_datos_espectrometria_aire['kr88']),1))),'LTBR',0,'C',TRUE);
    $pdf->Ln(6);
    $contador5++;
}while($row_recuperar_datos_espectrometria_aire = mysql_fetch_assoc($recuperar_datos_espectrometria_aire));

if($contador5<4){
    do{
        if($contador5%2==0){
            $pdf->SetFillColor(256, 256, 256);
        }else{
            $pdf->SetFillColor(245, 245, 245);
        }
        $pdf->Cell(40,6,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(26,6,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(20,6,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(20,6,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(20,6,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(20,6,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(20,6,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(20,6,('-'),'LTBR',0,'C',TRUE);
        $pdf->Ln(6);
        $contador5++;
    }while($contador5<4);
}


$pdf->Ln(1);
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->SetFillColor(219, 219, 219);
$pdf->Cell(0,8,('OBSERVACIONES'),'LTBR',0,'C',TRUE);
$pdf->Ln(8);
$pdf->SetFont('Helvetica', '', 7);
$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(0,1,(''),'LTR',0,'C',TRUE);
$pdf->Ln(1);
$pdf->MultiCell(0,3,($campo_observaciones),'LR',1,'L',TRUE);
$H = $pdf->GetY();
$alto_restante = 283 - $H;

$pdf->Ln(0);
$pdf->Cell(0,$alto_restante,(''),'LBR',0,'C',TRUE);


$pdf->AddPage(); 
$pdf->SetMargins(12, 10, 12);
$pdf->Ln(28);
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->SetFillColor(219, 219, 219);
$pdf->Cell(0,8,('EMISIÓN DE EFLUENTES LÍQUIDOS'),'LTBR',0,'C',TRUE);
$pdf->Ln(8);
$pdf->SetFillColor(235, 235, 235);
$pdf->SetFont('Helvetica', '', 9);
$pdf->Cell(20,12,('FECHA'),'LTBR',0,'C',TRUE);
$pdf->Cell(16,12,('HORA'),'LTBR',0,'C',TRUE);
$pdf->Cell(22,12,('TANQUE'),'LTBR',0,'C',TRUE);
$pdf->Cell(22,8,('VOLUMEN'),'LTBR',0,'C',TRUE);
$pdf->Cell(36,8,('ACTIVIDAD TRITIO'),'LTR',0,'C',TRUE);
$pdf->Cell(36,8,('ACTIVIDAD GAMMA'),'LBTR',0,'C',TRUE);
$pdf->Cell(0,12,('% DEL LDD'),'LBTR',0,'C',TRUE);
$pdf->Ln(6);
$pdf->Cell(58);
$pdf->SetFont('Helvetica', 'I', 7);
$pdf->Cell(22,6,('(m3)'),'LBR',0,'C',TRUE);
$pdf->Cell(36,6,('(TBq)'),'LBR',0,'C',TRUE);
$pdf->Cell(36,6,('(TBq)'),'LBR',0,'C',TRUE);
$pdf->Ln(6);
$pdf->SetFont('Helvetica', '', 7);

$contador2 = 0;

do{
    if($contador2%2==0){
        $pdf->SetFillColor(256, 256, 256);
    }else{
        $pdf->SetFillColor(245, 245, 245);
    }
    $pdf->Cell(20,4,(formafecha6($row_recuperar_datos_efluentes_liquidos['fecha'])),'LTBR',0,'C',TRUE);
    $pdf->Cell(16,4,(formato_hora_personalizado($row_recuperar_datos_efluentes_liquidos['hora'])),'LTBR',0,'C',TRUE);
    $pdf->Cell(22,4,($row_recuperar_datos_efluentes_liquidos['tanque']),'LTBR',0,'C',TRUE);
    $pdf->Cell(22,4,(coma_punto(((conversion_volumen('1','2',$row_recuperar_datos_efluentes_liquidos['volumen_litro']))))),'LTBR',0,'C',TRUE);
    $pdf->Cell(36,4,(notacion_cientifica2(conversion_actividad('1','2',$row_recuperar_datos_efluentes_liquidos['actividad_tritio']))),'LTBR',0,'C',TRUE);
    $pdf->Cell(36,4,(notacion_cientifica2(conversion_actividad('1','2',$row_recuperar_datos_efluentes_liquidos['actividad_gamma']))),'LBTR',0,'C',TRUE);
    $pdf->Cell(0,4,(decimales(($row_recuperar_datos_efluentes_liquidos['LDD_Ro_tritio']+$row_recuperar_datos_efluentes_liquidos['LDD_Ro_gamma']),3)),'LBTR',0,'C',TRUE);
    
    $pdf->Ln();
    $contador2++;
}while($row_recuperar_datos_efluentes_liquidos = mysql_fetch_assoc($recuperar_datos_efluentes_liquidos));

if($contador2<4){
    do{
        if($contador2%2==0){
            $pdf->SetFillColor(256, 256, 256);
        }else{
            $pdf->SetFillColor(245, 245, 245);
        }
        $pdf->Cell(20,4,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(16,4,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(22,4,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(22,4,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(36,4,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(36,4,('-'),'LBTR',0,'C',TRUE);
        $pdf->Cell(0,4,('-'),'LBTR',0,'C',TRUE);
        
        $pdf->Ln(4);
        $contador2++;
    }while($contador2<4);
}
$pdf->Ln(1);
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->SetFillColor(219, 219, 219);
$pdf->Cell(0,8,('MEDICION DE CONT. SUPERFICIAL Y TASA DE DOSIS EQUIVALENTE'),'LTBR',0,'C',TRUE);
$pdf->Ln(8);

$pdf->SetFillColor(235, 235, 235);
$pdf->SetFont('Helvetica', '', 8);
$pdf->Cell(20,12,('RECINTO'),'LTBR',0,'C',TRUE);
$pdf->Cell(17,12,('HORA'),'LTBR',0,'C',TRUE);
$pdf->SetFont('Helvetica', '', 7);
$pdf->Cell(28,4,('CONTAMINACIÓN'),'LTR',0,'C',TRUE);
$pdf->Cell(27,4,('TASA DE DOSIS'),'LTR',0,'C',TRUE);
$pdf->Cell(1);
$pdf->Cell(20,12,('RECINTO'),'LTBR',0,'C',TRUE);
$pdf->Cell(17,12,('HORA'),'LTBR',0,'C',TRUE);
$pdf->SetFont('Helvetica', '', 7);
$pdf->Cell(28,4,('CONTAMINACIÓN'),'LTR',0,'C',TRUE);
$pdf->Cell(28,4,('TASA DE DOSIS'),'LTR',0,'C',TRUE);
$pdf->Ln(4);
$pdf->Cell(37);
$pdf->SetFont('Helvetica', '', 6.5);
$pdf->Cell(28,4,('SUPERFICIAL (Bq/cm2)'),'LRB',0,'C',TRUE);
$pdf->Cell(27,4,('EQUIVALENTE'),'LRB',0,'C',TRUE);
$pdf->Cell(38);
$pdf->Cell(28,4,('SUPERFICIAL (Bq/cm2)'),'LRB',0,'C',TRUE);
$pdf->Cell(28,4,('EQUIVALENTE'),'LRB',0,'C',TRUE);
$pdf->Ln(4);
$pdf->Cell(37);
$pdf->SetFont('Helvetica', 'I', 6);
$pdf->Cell(14,4,utf8_decode('Beta+Gamma'),'LBRT',0,'C',TRUE);
$pdf->Cell(14,4,utf8_decode('Alfa'),'LBRT',0,'C',TRUE);
$pdf->Cell(27,4,('µSv/h'),'LBRT',0,'C',TRUE);
$pdf->Cell(38);
$pdf->Cell(14,4,utf8_decode('Beta+Gamma'),'LBRT',0,'C',TRUE);
$pdf->Cell(14,4,utf8_decode('Alfa'),'LBRT',0,'C',TRUE);
$pdf->Cell(28,4,('µSv/h'),'LBRT',0,'C',TRUE);
$pdf->Ln(4);
$pdf->SetFont('Helvetica', '', 7);
$pdf->SetFillColor(256, 256, 256);

$variable_ubicacion = $pdf->GetY();
$contador3 = 0;
do{
    if($contador3%2==0){
        $pdf->SetFillColor(256, 256, 256);
    }else{
        $pdf->SetFillColor(245, 245, 245);
    }
    $pdf->Cell(20,4,($row_cargar_valores_contaminacion_superficial_1['recinto_nombre']),'LTBR',0,'C',TRUE);
    $pdf->Cell(17,4,(formato_hora_2($row_cargar_valores_contaminacion_superficial_1['hora'])),'LTBR',0,'C',TRUE);
    $pdf->Cell(14,4,(nulo(decimales($row_cargar_valores_contaminacion_superficial_1['beta_gamma_Bq_cm2'],1))),'LTBR',0,'C',TRUE);
    $pdf->Cell(14,4,(nulo(decimales($row_cargar_valores_contaminacion_superficial_1['alfa_Bq_cm2'],1))),'LTBR',0,'C',TRUE);
    $pdf->Cell(27,4,(nulo(decimales($row_cargar_valores_contaminacion_superficial_1['tasa_dosis_equivalente_µsv_h'],1))),'LTBR',0,'C',TRUE);
    $pdf->Ln(4);
    $contador3++;
}while($row_cargar_valores_contaminacion_superficial_1 = mysql_fetch_assoc($cargar_valores_contaminacion_superficial_1));

if($contador3<10){
    do{
        if($contador3%2==0){
            $pdf->SetFillColor(256, 256, 256);
        }else{
            $pdf->SetFillColor(245, 245, 245);
        }
        $pdf->Cell(20,4,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(17,4,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(14,4,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(14,4,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(27,4,('-'),'LTBR',0,'C',TRUE);
        $pdf->Ln(4);
        $contador3++;
    }while($contador3<10);
}

$pdf->SetXY(12, $variable_ubicacion);

$contador4 = 0;
do{
    if($contador4%2==0){
        $pdf->SetFillColor(256, 256, 256);
    }else{
        $pdf->SetFillColor(245, 245, 245);
    }
    $pdf->Cell(93);
    $pdf->Cell(20,4,($row_cargar_valores_contaminacion_superficial_2['recinto_nombre']),'LTBR',0,'C',TRUE);
    $pdf->Cell(17,4,(formato_hora_2($row_cargar_valores_contaminacion_superficial_2['hora'])),'LTBR',0,'C',TRUE);
    $pdf->Cell(14,4,(nulo(decimales($row_cargar_valores_contaminacion_superficial_2['beta_gamma_Bq_cm2'],1))),'LTBR',0,'C',TRUE);
    $pdf->Cell(14,4,(nulo(decimales($row_cargar_valores_contaminacion_superficial_2['alfa_Bq_cm2'],1))),'LTBR',0,'C',TRUE);
    $pdf->Cell(28,4,(nulo(decimales($row_cargar_valores_contaminacion_superficial_2['tasa_dosis_equivalente_µsv_h'],1))),'LTBR',0,'C',TRUE);
    $pdf->Ln(4);
    $contador4++;
}while($row_cargar_valores_contaminacion_superficial_2 = mysql_fetch_assoc($cargar_valores_contaminacion_superficial_2));

if($contador4<10){
    do{
        if($contador4%2==0){
            $pdf->SetFillColor(256, 256, 256);
        }else{
            $pdf->SetFillColor(245, 245, 245);
        }
        $pdf->Cell(93);
        $pdf->Cell(20,4,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(17,4,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(14,4,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(14,4,('-'),'LTBR',0,'C',TRUE);
        $pdf->Cell(28,4,('-'),'LTBR',0,'C',TRUE);
        $pdf->Ln(4);
        $contador4++;
    }while($contador4<10);
}
$pdf->Ln(1);
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->SetFillColor(219, 219, 219);
$pdf->Cell(0,8,('RELEVAMIENTO NO RUTINARIO'),'LTBR',0,'C',TRUE);
$pdf->Ln(8);

$pdf->SetFillColor(235, 235, 235);
$pdf->SetFont('Helvetica', '', 9);
$pdf->Cell(16,12,('HORA'),'LTBR',0,'C',TRUE);
$pdf->Cell(20,12,('UBICACIÓN'),'LTBR',0,'C',TRUE);
$pdf->Cell(22,8,('G. NOBLES'),'LTBR',0,'C',TRUE);
$pdf->Cell(22,8,('TRITIO'),'LTBR',0,'C',TRUE);
$pdf->Cell(22,8,('TASA DOSIS'),'LTR',0,'C',TRUE);
$pdf->Cell(50,12,('OBSERVACIONES'),'LBTR',0,'C',TRUE);
$pdf->Cell(0,12,('RELEVADO POR'),'LBTR',0,'C',TRUE);
$pdf->Ln(6);
$pdf->Cell(36);
$pdf->SetFont('Helvetica', 'I', 7);
$pdf->Cell(22,6,('(DAC)'),'LBR',0,'C',TRUE);
$pdf->Cell(22,6,('(DAC)'),'LBR',0,'C',TRUE);
$pdf->Cell(22,6,('(µSv/h)'),'LBR',0,'C',TRUE);
$pdf->Ln(6);

$pdf->SetFont('Helvetica', '', 8);
$contador6 = 0;
if($totalRows_cargar_valores_no_rutinarios==0){
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Cell(0,5,('NO HAY DATOS CARGADOS'),'LBRT',0,'C',TRUE);
    $contador6++;
$pdf->Ln(5);
}else{
    do{
        if ($contador6%2==0){
            $pdf->SetFillColor(256, 256, 256);
        }else{
            $pdf->SetFillColor(245, 245, 245);
        }
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->Cell(16,5,(formato_hora_2($row_cargar_valores_no_rutinarios['hora'])),'LBRT',0,'C',TRUE);
        $pdf->Cell(20,5,(($row_cargar_valores_no_rutinarios['recinto'])),'LBRT',0,'C',TRUE);
        $pdf->Cell(22,5,(nulo(decimales($row_cargar_valores_no_rutinarios['gases_nobles'],1))),'LBRT',0,'C',TRUE);
        $pdf->Cell(22,5,(nulo(decimales($row_cargar_valores_no_rutinarios['tritio'],1))),'LBRT',0,'C',TRUE);
        $pdf->Cell(22,5,(nulo(decimales($row_cargar_valores_no_rutinarios['tasa_dosis'],1))),'LBRT',0,'C',TRUE);
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell(50,5,($row_cargar_valores_no_rutinarios['observaciones']),'LBRT',0,'C',TRUE);
        $pdf->Cell(0,5,($row_cargar_valores_no_rutinarios['of_apellido'].", ".$row_cargar_valores_no_rutinarios['of_nombre']),'LBRT',0,'C',TRUE);
        $pdf->Ln(5);
        $contador6++;
    }while($row_cargar_valores_no_rutinarios = mysql_fetch_assoc($cargar_valores_no_rutinarios));
}
if($contador6<17){
    do{
         if ($contador6%2==0){
            $pdf->SetFillColor(256, 256, 256);
        }else{
            $pdf->SetFillColor(245, 245, 245);
        }
        $pdf->Cell(16,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->Cell(20,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->Cell(22,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->Cell(22,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->Cell(22,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->Cell(50,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->Cell(0,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->Ln(5);
        $contador6++;
    }while($contador6<17);
}

$pdf->Ln(1);
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->SetFillColor(219, 219, 219);
$pdf->Cell(186,8,('EFICIENCIA DRYERS'),'LTBR',0,'C',TRUE);
$pdf->Ln(8);

$pdf->SetFillColor(235, 235, 235);
$pdf->SetFont('Helvetica', '', 9);
$pdf->Cell(31,8,('FECHA'),'LTBR',0,'C',TRUE);
$pdf->Cell(31,8,('HORA'),'LTBR',0,'C',TRUE);
$pdf->Cell(31,8,('DRYERS'),'LTBR',0,'C',TRUE);
$pdf->SetFont('Helvetica', '', 8);
$pdf->Cell(31,4,('ENTRADA'),'LTR',0,'C',TRUE);
$pdf->Cell(31,4,('SALIDA'),'LTR',0,'C',TRUE);
$pdf->Cell(0,4,('EFICIENCIA'),'LTR',0,'C',TRUE);
$pdf->Ln(4);

$pdf->Cell(93);
$pdf->SetFont('Helvetica', 'I', 7);
$pdf->Cell(31,4,('DAC'),'LBR',0,'C',TRUE);
$pdf->Cell(31,4,('DAC'),'LBR',0,'C',TRUE);
$pdf->Cell(0,4,('%'),'LBR',0,'C',TRUE);
$pdf->Ln(4);

$contador7 = 0;
if($totalRows_cargar_valores_eficiencia_dryers==0){
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Cell(0,5,('NO HAY DATOS CARGADOS'),'LBRT',0,'C',TRUE);
    $contador7++;
$pdf->Ln(5);
}else{
    do{
        if ($contador7%2==0){
            $pdf->SetFillColor(256, 256, 256);
        }else{
            $pdf->SetFillColor(245, 245, 245);
        }
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->Cell(31,5,(formafecha6($row_cargar_valores_eficiencia_dryers['fecha'])),'LBRT',0,'C',TRUE);
        $pdf->Cell(31,5,(formato_hora_2($row_cargar_valores_eficiencia_dryers['hora'])),'LBRT',0,'C',TRUE);
        $pdf->Cell(31,5,($row_cargar_valores_eficiencia_dryers['nombre_dryers']),'LBRT',0,'C',TRUE);
        $pdf->Cell(31,5,(coma_cambio2($row_cargar_valores_eficiencia_dryers['entrada_dac'])),'LBRT',0,'C',TRUE);
        $pdf->Cell(31,5,(coma_cambio2($row_cargar_valores_eficiencia_dryers['salida_dac'])),'LBRT',0,'C',TRUE);
        $pdf->Cell(0,5,(decimales($row_cargar_valores_eficiencia_dryers['eficiencia_porcentaje'],2)),'LBRT',0,'C',TRUE);
        $pdf->Ln(5);
        $contador7++;
    }while($row_cargar_valores_eficiencia_dryers = mysql_fetch_assoc($cargar_valores_eficiencia_dryers));
}
if($contador7<5){
    do{
         if ($contador7%2==0){
            $pdf->SetFillColor(256, 256, 256);
        }else{
            $pdf->SetFillColor(245, 245, 245);
        }
        $pdf->Cell(31,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->Cell(31,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->Cell(31,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->Cell(31,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->Cell(31,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->Cell(0,5,('-'),'LBRT',0,'C',TRUE);
        $pdf->Ln(5);
        $contador7++;
    }while($contador7<5);
}



if($totalRows_cargar_valores_no_rutinarios_parada==0){
}else{
    $pdf->AddPage(); 
    $pdf->Ln(28);

    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->SetFillColor(219, 219, 219);
    $pdf->Cell(0,8,('RELEVAMIENTO NO RUTINARIO'),'LTBR',0,'C',TRUE);
    $pdf->Ln(8);

    $pdf->SetFillColor(235, 235, 235);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(16,12,('HORA'),'LTBR',0,'C',TRUE);
    $pdf->Cell(20,12,('UBICACIÓN'),'LTBR',0,'C',TRUE);
    $pdf->Cell(22,8,('G. NOBLES'),'LTBR',0,'C',TRUE);
    $pdf->Cell(22,8,('TRITIO'),'LTBR',0,'C',TRUE);
    $pdf->Cell(22,8,('TASA DOSIS'),'LTR',0,'C',TRUE);
    $pdf->Cell(50,12,('OBSERVACIONES'),'LBTR',0,'C',TRUE);
    $pdf->Cell(0,12,('RELEVADO POR'),'LBTR',0,'C',TRUE);
    $pdf->Ln(6);
    $pdf->Cell(36);
    $pdf->SetFont('Helvetica', 'I', 7);
    $pdf->Cell(22,6,('(DAC)'),'LBR',0,'C',TRUE);
    $pdf->Cell(22,6,('(DAC)'),'LBR',0,'C',TRUE);
    $pdf->Cell(22,6,('(µSv/h)'),'LBR',0,'C',TRUE);
    $pdf->Ln(6);

    $pdf->SetFont('Helvetica', '', 8);
    $contador8 = 0;
    if($totalRows_cargar_valores_no_rutinarios==0){
        $pdf->SetFillColor(245, 245, 245);
        $pdf->Cell(0,6,('NO HAY DATOS CARGADOS'),'LBRT',0,'C',TRUE);
    $pdf->Ln(6);
    }else{
        do{
            if ($contador8%2==0){
                $pdf->SetFillColor(256, 256, 256);
            }else{
                $pdf->SetFillColor(245, 245, 245);
            }
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell(16,5,(formato_hora_2($row_cargar_valores_no_rutinarios_parada['hora'])),'LBRT',0,'C',TRUE);
            $pdf->Cell(20,5,(($row_cargar_valores_no_rutinarios_parada['recinto'])),'LBRT',0,'C',TRUE);
            $pdf->Cell(22,5,(nulo(decimales($row_cargar_valores_no_rutinarios_parada['gases_nobles'],1))),'LBRT',0,'C',TRUE);
            $pdf->Cell(22,5,(nulo(decimales($row_cargar_valores_no_rutinarios_parada['tritio'],1))),'LBRT',0,'C',TRUE);
            $pdf->Cell(22,5,(nulo(decimales($row_cargar_valores_no_rutinarios_parada['tasa_dosis'],1))),'LBRT',0,'C',TRUE);
            $pdf->SetFont('Helvetica', '', 7);
            $pdf->Cell(50,5,($row_cargar_valores_no_rutinarios_parada['observaciones']),'LBRT',0,'C',TRUE);
            $pdf->Cell(0,5,($row_cargar_valores_no_rutinarios_parada['of_apellido'].", ".$row_cargar_valores_no_rutinarios_parada['of_nombre']),'LBRT',0,'C',TRUE);
            $pdf->Ln(5);
            $contador8++;
        }while($row_cargar_valores_no_rutinarios_parada = mysql_fetch_assoc($cargar_valores_no_rutinarios_parada));
    }
    if($contador8<45){
        do{
             if ($contador8%2==0){
                $pdf->SetFillColor(256, 256, 256);
            }else{
                $pdf->SetFillColor(245, 245, 245);
            }
            $pdf->Cell(16,5,('-'),'LBRT',0,'C',TRUE);
            $pdf->Cell(20,5,('-'),'LBRT',0,'C',TRUE);
            $pdf->Cell(22,5,('-'),'LBRT',0,'C',TRUE);
            $pdf->Cell(22,5,('-'),'LBRT',0,'C',TRUE);
            $pdf->Cell(22,5,('-'),'LBRT',0,'C',TRUE);
            $pdf->SetFont('Helvetica', '', 7);
            $pdf->Cell(50,5,('-'),'LBRT',0,'C',TRUE);
            $pdf->Cell(0,5,('-'),'LBRT',0,'C',TRUE);
            $pdf->Ln(5);
            $contador8++;
        }while($contador8<45);
    }



}

$pdf->AddPage(); 
$pdf->Ln(28);

$pdf->Cell(0,245,(''),'LTBR',0,'C',TRUE);

// Búsqueda dinámica de la imagen del gráfico (evita rutas absolutas rígidas)
$grafico_path = '';
$candidatos_grafico = array(
    __DIR__ . '/Graficos/graf.jpg',
    (isset($_SERVER["DOCUMENT_ROOT"]) ? $_SERVER["DOCUMENT_ROOT"] . '/ptr/registro_rad7/Graficos/graf.jpg' : ''),
    (isset($_SERVER["DOCUMENT_ROOT"]) ? $_SERVER["DOCUMENT_ROOT"] . '/ptr/registro_rad5/Graficos/graf.jpg' : ''),
    (isset($_SERVER["DOCUMENT_ROOT"]) ? $_SERVER["DOCUMENT_ROOT"] . '/ptr/registro_rad/Graficos/graf.jpg' : '')
);
foreach ($candidatos_grafico as $cg) {
    if (!empty($cg) && file_exists($cg)) {
        $grafico_path = $cg;
        break;
    }
}
if (!empty($grafico_path)) {
    $pdf->Image($grafico_path, 13, 39, 184, 242, 'JPG');
}


// example using general stretching and spacing


// ---------------------------------------------------------

//Close and output PDF document
//Close and output PDF document
ob_end_clean();

$nombre_archivo_pdf = 'Registro_'.formafecha11($row_planilla_en_curso['fecha']).' - '.($row_planilla_en_curso['horarios']).'.pdf';
$dir_registros      = __DIR__ . '/Registros_pdfs';
$ruta_archivo_pdf   = $dir_registros . '/' . $nombre_archivo_pdf;

// 1. Obtener el contenido binario del PDF generado en memoria (garantiza que tenga todos los datos)
$pdf_contenido = $pdf->Output('', 'S');

// 2. Asegurar que el directorio Registros_pdfs exista y guardar una copia física en el servidor
if (!is_dir($dir_registros)) {
    @mkdir($dir_registros, 0777, true);
}
@file_put_contents($ruta_archivo_pdf, $pdf_contenido);

// 3. Enviar por correo institucional a Exchange SOLO si se solicita explícitamente con ?enviar_mail=1
$debe_enviar_mail = isset($_GET['enviar_mail']) && ($_GET['enviar_mail'] === '1' || $_GET['enviar_mail'] === 'true');

if ($debe_enviar_mail) {
    if (file_exists(__DIR__ . '/enviar_mail_planilla.php')) {
        require_once(__DIR__ . '/enviar_mail_planilla.php');
    } else if (isset($_SERVER["DOCUMENT_ROOT"]) && file_exists($_SERVER["DOCUMENT_ROOT"] . "/ptr/registro_rad5/enviar_mail_planilla.php")) {
        require_once($_SERVER["DOCUMENT_ROOT"] . "/ptr/registro_rad5/enviar_mail_planilla.php");
    }

    if (function_exists('enviar_planilla_por_correo')) {
        enviar_planilla_por_correo($id_planilla, $ruta_archivo_pdf, $nombre_archivo_pdf, $row_planilla_en_curso, $miconex, $pdf_contenido);
    }
}

// 4. Entregar el archivo PDF al navegador para descarga/visualización
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $nombre_archivo_pdf . '"');
header('Content-Length: ' . strlen($pdf_contenido));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
echo $pdf_contenido;
exit;

//============================================================+
// END OF FILE
//============================================================+
