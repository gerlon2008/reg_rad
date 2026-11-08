<?php
/**
 * REGISTRO RADIOLÓGICO ONLINE - CNE / NA-SA
 * Prototipo y Maqueta de Interfaz Moderna
 * Compatible con PHP 5.5.7 y 100% Offline (Sin CDNs ni conexión externa)
 */

$fecha_hoy = date('Y-m-d');
$hora_actual = date('H:i');
$oficial_actual = "Pérez, Juan (Of. Radioprotección)";
$jefe_turno_actual = "Gómez, Carlos (Jefe de Turno)";
$estado_planta = "POTENCIA 100%";
$turno_actual = "Turno 2 (14:00 a 22:00 hs)";
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro Radiológico Online - CNE NA-SA</title>
  <link rel="stylesheet" href="css/app.css">
</head>
<body>

  <!-- CABECERA PRINCIPAL -->
  <header class="header-bar">
    <div class="header-left">
      <div class="brand-badge">NA-SA</div>
      <div class="header-title">
        <h1>
          Registro Radiológico Online
          <span style="font-weight: normal; font-size: 12px; color: var(--text-muted);">| SyR - Central Nuclear Embalse</span>
        </h1>
      </div>
    </div>

    <div class="header-center">
      <div class="badge-info status-online" title="Estado Operativo de Planta">
        <span style="font-size: 9px;">●</span> <?php echo $estado_planta; ?>
      </div>
      <div class="badge-info">
        ⏱️ <?php echo $turno_actual; ?>
      </div>
    </div>

    <div class="header-right">
      <div class="user-profile">
        <div class="user-name"><?php echo $oficial_actual; ?></div>
        <div class="user-role"><?php echo $jefe_turno_actual; ?></div>
      </div>
      <div class="badge-info" id="live-clock" style="font-family: var(--font-mono); font-weight: 700;">
        <?php echo $hora_actual; ?>:00 hs
      </div>
      <button class="btn btn-outline" id="btn-toggle-theme" title="Alternar Modo Oscuro / Claro">
        🌙 Modo Oscuro
      </button>
    </div>
  </header>

  <!-- CONTENEDOR PRINCIPAL -->
  <main class="main-wrapper">

    <!-- BARRA DE PESTAÑAS (TABS) -->
    <nav class="tabs-navigation">
      <button class="tab-btn active" data-tab="tab-rutina">
        📋 Relevamiento de Rutina
        <span class="tab-badge" id="badge-count-rutina">2</span>
      </button>
      <button class="tab-btn" data-tab="tab-gaseosos">
        💨 Efluentes Gaseosos
        <span class="tab-badge">Calculadora RO</span>
      </button>
      <button class="tab-btn" data-tab="tab-liquidos">
        💧 Efluentes Líquidos
      </button>
      <button class="tab-btn" data-tab="tab-tareas">
        ⚠️ Tareas &amp; PTR
      </button>
      <button class="tab-btn" data-tab="tab-actas">
        📖 Libro de Actas
      </button>
      <button class="tab-btn" data-tab="tab-resumen">
        ⚙️ Resumen y Cierre
      </button>
    </nav>

    <!-- ====================================================================
         PESTAÑA 1: RELEVAMIENTO DE RUTINA
         ==================================================================== -->
    <section class="tab-content active" id="tab-rutina">
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <span>📋</span> Carga Rápida y Relevamientos de Recintos
          </div>
          <div style="font-size: 11.5px; color: var(--text-muted);">
            💡 <strong>Modo Rápido:</strong> Complete los campos y presione <code>Enter</code> para guardar inmediatamente.
          </div>
        </div>

        <div class="card-body" style="padding: 0;">
          <div class="table-responsive">
            <table class="table-tech" id="tabla-relevamiento">
              <thead>
                <!-- Fila de Carga Rápida Fija -->
                <tr class="quick-entry-row">
                  <th style="width: 110px;">
                    Fecha
                    <input type="date" id="q-fecha" class="input-tech quick-input" value="<?php echo $fecha_hoy; ?>">
                  </th>
                  <th style="width: 120px;">
                    Hora
                    <div style="display: flex; gap: 4px;">
                      <input type="time" id="q-hora" class="input-tech quick-input mono" value="<?php echo $hora_actual; ?>">
                      <button class="btn btn-outline" id="btn-time-now" title="Hora actual" style="padding: 4px 6px;">⚡</button>
                    </div>
                  </th>
                  <th style="min-width: 260px;">
                    Ubicación / Recinto
                    <select id="q-recinto" class="input-tech quick-input" style="font-weight: 600;">
                      <option value="">-- Seleccionar Recinto --</option>
                      <option value="R-001 (Edificio de Contención - Nivel +15m)">R-001 (Edificio Contención +15m)</option>
                      <option value="R-004 (Blindaje Fuera del Núcleo)">R-004 (Blindaje Fuera del Núcleo)</option>
                      <option value="R-024 (Sala de Bombas PHT)">R-024 (Sala Bombas PHT)</option>
                      <option value="R-045 (Bóveda de Calandria)">R-045 (Bóveda de Calandria)</option>
                      <option value="R-080 (Área Tanques D2O)">R-080 (Área Tanques D2O)</option>
                      <option value="R-110 (Muestreador de Efluentes)">R-110 (Muestreador Efluentes)</option>
                    </select>
                  </th>
                  <th style="width: 100px;">
                    G. Nobles (DAC)
                    <input type="text" id="q-gases" class="input-tech quick-input mono" placeholder="ej: 0.15">
                  </th>
                  <th style="width: 100px;">
                    Tritio (DAC)
                    <input type="text" id="q-tritio" class="input-tech quick-input mono" placeholder="ej: 0.80">
                  </th>
                  <th style="width: 105px;">
                    T. Dosis (µSv/h)
                    <input type="text" id="q-dosis" class="input-tech quick-input mono" placeholder="ej: 12.5">
                  </th>
                  <th style="min-width: 220px;">
                    Observaciones
                    <input type="text" id="q-obs" class="input-tech quick-input" placeholder="Nota o novedad del área...">
                  </th>
                  <th style="width: 120px;">Oficial</th>
                  <th style="width: 80px; text-align: center;">
                    <button class="btn btn-primary" id="btn-add-routine" style="width: 100%;">
                      + Agregar
                    </button>
                  </th>
                </tr>

                <!-- Encabezados de Columnas -->
                <tr>
                  <th>Fecha</th>
                  <th>Hora</th>
                  <th>Ubicación / Recinto</th>
                  <th style="text-align: right;">G. Nobles (DAC)</th>
                  <th style="text-align: right;">Tritio (DAC)</th>
                  <th style="text-align: right;">T. Dosis (µSv/h)</th>
                  <th>Observaciones</th>
                  <th>Relevado por</th>
                  <th style="text-align: center;">Acción</th>
                </tr>
              </thead>
              <tbody id="tbody-rutina">
                <!-- Se llena dinámicamente con JavaScript desde localStorage/PHP -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>

    <!-- ====================================================================
         PESTAÑA 2: EFLUENTES GASEOSOS & CALCULADORA RO
         ==================================================================== -->
    <section class="tab-content" id="tab-gaseosos">
      <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
        
        <!-- Formulario Espectrometría Gases Nobles -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">
              <span>💨</span> Espectrometría de Gases Nobles de Chimenea
            </div>
            <div class="badge-info">Cálculo de RO en Tiempo Real</div>
          </div>
          <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px;">
              <div>
                <label style="font-weight: 600; font-size: 12px;">Fecha Muestreo</label>
                <input type="date" class="input-tech" value="<?php echo $fecha_hoy; ?>">
              </div>
              <div>
                <label style="font-weight: 600; font-size: 12px;">Hora Desde</label>
                <input type="time" class="input-tech mono" value="08:00">
              </div>
              <div>
                <label style="font-weight: 600; font-size: 12px;">Hora Hasta</label>
                <input type="time" class="input-tech mono" value="14:00">
              </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
              <div>
                <label style="font-weight: 600; font-size: 12px;">Argón-41 (Ar-41) [MBq]</label>
                <input type="text" id="iso-ar41" class="input-tech mono isotope-input" placeholder="0.00" value="14.2">
              </div>
              <div>
                <label style="font-weight: 600; font-size: 12px;">Kriptón-85 (Kr-85) [MBq]</label>
                <input type="text" id="iso-kr85" class="input-tech mono isotope-input" placeholder="0.00" value="2.1">
              </div>
              <div>
                <label style="font-weight: 600; font-size: 12px;">Kriptón-85m (Kr-85m) [MBq]</label>
                <input type="text" id="iso-kr85m" class="input-tech mono isotope-input" placeholder="0.00" value="0.8">
              </div>
              <div>
                <label style="font-weight: 600; font-size: 12px;">Xenón-133 (Xe-133) [MBq]</label>
                <input type="text" id="iso-xe133" class="input-tech mono isotope-input" placeholder="0.00" value="18.5">
              </div>
              <div>
                <label style="font-weight: 600; font-size: 12px;">Xenón-135 (Xe-135) [MBq]</label>
                <input type="text" id="iso-xe135" class="input-tech mono isotope-input" placeholder="0.00" value="5.4">
              </div>
              <div>
                <label style="font-weight: 600; font-size: 12px;">Xenón-135m (Xe-135m) [MBq]</label>
                <input type="text" id="iso-xe135m" class="input-tech mono isotope-input" placeholder="0.00" value="1.1">
              </div>
            </div>

            <div style="margin-top: 18px; text-align: right;">
              <button class="btn btn-primary" onclick="alert('Datos de Gases Nobles persistidos.');">
                💾 Guardar Medición de Gases Nobles
              </button>
            </div>
          </div>
        </div>

        <!-- Panel de Impacto / Restricción Operacional en Vivo -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">
              <span>📊</span> Balance &amp; Límites Operacionales
            </div>
          </div>
          <div class="card-body">
            <div style="margin-bottom: 16px;">
              <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Actividad Total Calculada</div>
              <div id="total-actividad-gases" style="font-size: 22px; font-family: var(--font-mono); font-weight: 800; color: var(--color-primary);">
                42.100 MBq
              </div>
            </div>

            <div style="margin-bottom: 16px;">
              <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 600; margin-bottom: 6px;">
                <span>% Restricción Operacional (RO)</span>
                <span id="ro-gases-percentage">8.42 %</span>
              </div>
              <div style="width: 100%; height: 10px; background-color: var(--bg-card-subtle); border-radius: 5px; overflow: hidden; border: 1px solid var(--border-color);">
                <div id="ro-gases-bar" style="height: 100%; width: 8.42%; background-color: var(--color-success); transition: all 0.3s ease;"></div>
              </div>
              <div style="font-size: 10.5px; color: var(--text-muted); margin-top: 4px;">
                Límite normativo diario SyR.
              </div>
            </div>

            <div style="padding: 10px; border-radius: 6px; background-color: var(--color-success-bg); border: 1px solid var(--color-success-border); font-size: 11.5px; color: var(--color-success);">
              ✔️ <strong>Valores normales:</strong> La tasa de descarga calculada se encuentra ampliamente dentro de los límites de licencia.
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- ====================================================================
         PESTAÑA 3: EFLUENTES LÍQUIDOS
         ==================================================================== -->
    <section class="tab-content" id="tab-liquidos">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><span>💧</span> Tanques de Descarga de Efluentes Activos</div>
        </div>
        <div class="card-body">
          <p style="color: var(--text-muted);">
            Monitoreo y autorización de descarga de tanques de retención y efluentes activos de planta.
          </p>
        </div>
      </div>
    </section>

    <!-- ====================================================================
         PESTAÑA 4: TAREAS, EVENTOS & PTR
         ==================================================================== -->
    <section class="tab-content" id="tab-tareas">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><span>⚠️</span> Tareas con Permiso de Trabajo con Radiación (PTR)</div>
        </div>
        <div class="card-body">
          <p style="color: var(--text-muted);">
            Control de dosis en tareas de mantenimiento, monitoreo de derrames de agua pesada D2O y dispersión de aerosoles.
          </p>
        </div>
      </div>
    </section>

    <!-- ====================================================================
         PESTAÑA 5: LIBRO DE ACTAS
         ==================================================================== -->
    <section class="tab-content" id="tab-actas">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><span>📖</span> Libro de Actas / Novedades de Turno</div>
        </div>
        <div class="card-body">
          <textarea class="input-tech" rows="6" placeholder="Escriba las novedades relevantes del turno para el traspaso de guardia..."></textarea>
          <div style="margin-top: 10px; text-align: right;">
            <button class="btn btn-primary">Guardar en Libro de Actas</button>
          </div>
        </div>
      </div>
    </section>

    <!-- ====================================================================
         PESTAÑA 6: RESUMEN Y CIERRE
         ==================================================================== -->
    <section class="tab-content" id="tab-resumen">
      <div class="card">
        <div class="card-header">
          <div class="card-title"><span>⚙️</span> Cierre y Formalización de Planilla Diaria</div>
        </div>
        <div class="card-body">
          <p style="color: var(--text-muted); margin-bottom: 14px;">
            Al cerrar la planilla, se congela el registro del turno, se emite el PDF oficial y se transfiere la guardia.
          </p>
          <button class="btn btn-success" style="font-size: 13px; padding: 8px 16px;">
            🔒 Cerrar Turno y Emitir Planilla Oficial
          </button>
        </div>
      </div>
    </section>

  </main>

  <!-- BARRA INFERIOR DE ESTADO (KEEP-ALIVE Y PERSISTENCIA) -->
  <footer class="status-bar">
    <div class="status-left">
      <span>
        <span class="status-indicator pulse"></span>
        <strong id="last-sync-time" style="color: var(--color-success); margin-left: 5px;">Sesión activa (Ping: <?php echo $hora_actual; ?>)</strong>
      </span>
      <span>•</span>
      <span id="autosave-status">💾 Guardado en borrador local</span>
      <span>•</span>
      <span>Apache 2.4 / PHP 5.5.7 (sn-c-c55.cne.central.nasa)</span>
    </div>
    <div class="status-right">
      <span>100% Offline (Sin dependencias externas)</span>
      <span>•</span>
      <button class="btn btn-outline" style="padding: 2px 8px; font-size: 11px;" onclick="localStorage.removeItem('reg_rad_demo_draft'); location.reload();">
        Reiniciar Datos Demo
      </button>
    </div>
  </footer>

  <script src="js/app.js"></script>
</body>
</html>
