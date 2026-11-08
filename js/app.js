/**
 * REGISTRO RADIOLÓGICO ONLINE - CNE / NA-SA
 * Lógica de Interfaz, Cálculos Radiológicos y Respaldo Local (100% Offline)
 */

document.addEventListener("DOMContentLoaded", function () {
  initTheme();
  initClock();
  initTabs();
  initKeepAlive();
  initRoutineTable();
  initNobleGasesCalculator();
  checkLocalDraft();
});

/* ==========================================================================
   TEMA (DARK / LIGHT MODE)
   ========================================================================== */
function initTheme() {
  const savedTheme = localStorage.getItem("reg_rad_theme") || "light";
  document.documentElement.setAttribute("data-theme", savedTheme);

  const toggleBtn = document.getElementById("btn-toggle-theme");
  if (toggleBtn) {
    toggleBtn.addEventListener("click", () => {
      const current = document.documentElement.getAttribute("data-theme");
      const next = current === "dark" ? "light" : "dark";
      document.documentElement.setAttribute("data-theme", next);
      localStorage.setItem("reg_rad_theme", next);
      toggleBtn.innerHTML = next === "dark" ? "☀️ Modo Claro" : "🌙 Modo Oscuro";
    });
    toggleBtn.innerHTML = savedTheme === "dark" ? "☀️ Modo Claro" : "🌙 Modo Oscuro";
  }
}

/* ==========================================================================
   RELOJ EN VIVO
   ========================================================================== */
function initClock() {
  const clockEl = document.getElementById("live-clock");
  if (!clockEl) return;
  function update() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2, "0");
    const m = String(now.getMinutes()).padStart(2, "0");
    const s = String(now.getSeconds()).padStart(2, "0");
    clockEl.textContent = `${h}:${m}:${s} hs`;
  }
  update();
  setInterval(update, 1000);
}

/* ==========================================================================
   NAVEGACIÓN POR PESTAÑAS (TABS)
   ========================================================================== */
function initTabs() {
  const tabButtons = document.querySelectorAll(".tab-btn");
  const tabContents = document.querySelectorAll(".tab-content");

  tabButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const targetId = btn.getAttribute("data-tab");

      tabButtons.forEach((b) => b.classList.remove("active"));
      tabContents.forEach((c) => c.classList.remove("active"));

      btn.classList.add("active");
      const targetContent = document.getElementById(targetId);
      if (targetContent) {
        targetContent.classList.add("active");
      }
    });
  });
}

/* ==========================================================================
   KEEP-ALIVE Y RESPALDO (PREVENCIÓN DE PÉRDIDAS)
   ========================================================================== */
function initKeepAlive() {
  const lastSyncEl = document.getElementById("last-sync-time");
  const autosaveEl = document.getElementById("autosave-status");

  function ping() {
    // En producción se hace fetch('keep_alive.php')
    const now = new Date();
    const timeStr = `${String(now.getHours()).padStart(2, "0")}:${String(now.getMinutes()).padStart(2, "0")}:${String(now.getSeconds()).padStart(2, "0")}`;
    if (lastSyncEl) {
      lastSyncEl.textContent = `Sesión activa (Ping: ${timeStr})`;
    }
  }

  ping();
  // Ping cada 2 minutos en segundo plano
  setInterval(ping, 120000);
}

function updateAutosaveBadge(status) {
  const el = document.getElementById("autosave-status");
  if (!el) return;
  if (status === "saved") {
    el.innerHTML = "💾 Guardado en borrador local";
    el.style.color = "var(--color-success)";
  } else if (status === "saving") {
    el.innerHTML = "⏳ Guardando...";
    el.style.color = "var(--color-warning)";
  }
}

/* ==========================================================================
   RELEVAMIENTO DE RUTINA - CARGA RÁPIDA EN LÍNEA
   ========================================================================== */
let routineData = [
  {
    id: 1,
    fecha: "2026-11-08",
    hora: "10:15",
    recinto: "R-001 (Edificio de Contención - Nivel +15m)",
    gases: 0.12,
    tritio: 0.45,
    dosis: 8.5,
    obs: "Ronda habitual de inicio de turno. Todo normal.",
    oficial: "Pérez, Juan",
  },
  {
    id: 2,
    fecha: "2026-11-08",
    hora: "11:30",
    recinto: "R-024 (Sala de Bombas PHT)",
    gases: 0.35,
    tritio: 1.80,
    dosis: 24.0,
    obs: "Acceso con máscara de protección por tarea en sello PHT.",
    oficial: "Pérez, Juan",
  },
];

function initRoutineTable() {
  renderRoutineRows();

  const addBtn = document.getElementById("btn-add-routine");
  if (addBtn) {
    addBtn.addEventListener("click", handleAddRoutineRow);
  }

  // Atajo de teclado: Enter en los inputs de carga rápida añade la fila
  const quickInputs = document.querySelectorAll(".quick-input");
  quickInputs.forEach((input) => {
    input.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        handleAddRoutineRow();
      }
    });
  });

  // Botón para poner la hora actual
  const nowBtn = document.getElementById("btn-time-now");
  if (nowBtn) {
    nowBtn.addEventListener("click", () => {
      const now = new Date();
      const h = String(now.getHours()).padStart(2, "0");
      const m = String(now.getMinutes()).padStart(2, "0");
      const timeInput = document.getElementById("q-hora");
      if (timeInput) timeInput.value = `${h}:${m}`;
    });
  }
}

function normalizeNumber(val) {
  if (!val || val.trim() === "") return null;
  const cleaned = val.replace(",", ".");
  const num = parseFloat(cleaned);
  return isNaN(num) ? null : num;
}

function handleAddRoutineRow() {
  const fechaInput = document.getElementById("q-fecha");
  const horaInput = document.getElementById("q-hora");
  const recintoSelect = document.getElementById("q-recinto");
  const gasesInput = document.getElementById("q-gases");
  const tritioInput = document.getElementById("q-tritio");
  const dosisInput = document.getElementById("q-dosis");
  const obsInput = document.getElementById("q-obs");

  const fecha = fechaInput.value;
  const hora = horaInput.value;
  const recinto = recintoSelect.value;
  const gases = normalizeNumber(gasesInput.value);
  const tritio = normalizeNumber(tritioInput.value);
  const dosis = normalizeNumber(dosisInput.value);
  const obs = obsInput.value.trim();

  if (!fecha || !hora) {
    alert("Por favor ingrese fecha y hora de la medición.");
    return;
  }
  if (!recinto || recinto === "-") {
    alert("Debe seleccionar un recinto de planta.");
    return;
  }
  if (gases === null && tritio === null && dosis === null) {
    alert("Debe ingresar al menos un valor radiológico (Gases, Tritio o Tasa de Dosis).");
    return;
  }

  // Prevención de error de tipeo (Alerta si supera 100 µSv/h)
  if (dosis !== null && dosis > 100) {
    if (!confirm(`⚠️ ATENCIÓN: La tasa de dosis ingresada es de ${dosis} µSv/h. ¿Confirma que este valor es correcto?`)) {
      return;
    }
  }

  const newEntry = {
    id: Date.now(),
    fecha,
    hora,
    recinto,
    gases,
    tritio,
    dosis,
    obs: obs || "Sin observaciones",
    oficial: "Oficial en Turno",
  };

  routineData.unshift(newEntry);
  renderRoutineRows();
  saveDraft();

  // Limpiar inputs de valores dejando fecha/hora listos para la siguiente
  gasesInput.value = "";
  tritioInput.value = "";
  dosisInput.value = "";
  obsInput.value = "";
  recintoSelect.focus();
}

function renderRoutineRows() {
  const tbody = document.getElementById("tbody-rutina");
  const badgeEl = document.getElementById("badge-count-rutina");
  if (!tbody) return;

  tbody.innerHTML = "";

  if (routineData.length === 0) {
    tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding: 20px; color: var(--text-muted);">No hay mediciones cargadas aún para este turno.</td></tr>`;
  } else {
    routineData.forEach((row) => {
      const tr = document.createElement("tr");

      // Clases según límites radiológicos
      const gasesClass = row.gases > 1.0 ? "cell-warning" : row.gases > 3.0 ? "cell-danger" : "";
      const tritioClass = row.tritio > 1.0 ? "cell-warning" : row.tritio > 5.0 ? "cell-danger" : "";
      const dosisClass = row.dosis > 20.0 ? "cell-warning" : row.dosis > 100.0 ? "cell-danger" : "";

      tr.innerHTML = `
        <td style="font-weight:600;">${row.fecha}</td>
        <td class="num">${row.hora}</td>
        <td><strong>${row.recinto}</strong></td>
        <td class="num ${gasesClass}">${row.gases !== null ? row.gases.toFixed(2) : "-"}</td>
        <td class="num ${tritioClass}">${row.tritio !== null ? row.tritio.toFixed(2) : "-"}</td>
        <td class="num ${dosisClass}">${row.dosis !== null ? row.dosis.toFixed(1) : "-"}</td>
        <td style="color: var(--text-muted); font-size: 11.5px;">${row.obs}</td>
        <td><span class="badge-info">${row.oficial}</span></td>
        <td style="text-align: center;">
          <button class="btn btn-danger-outline btn-icon" onclick="deleteRoutineRow(${row.id})" title="Eliminar fila">✕</button>
        </td>
      `;
      tbody.appendChild(tr);
    });
  }

  if (badgeEl) {
    badgeEl.textContent = routineData.length;
  }
}

window.deleteRoutineRow = function (id) {
  if (confirm("¿Desea eliminar esta medición?")) {
    routineData = routineData.filter((r) => r.id !== id);
    renderRoutineRows();
    saveDraft();
  }
};

/* ==========================================================================
   EFLUENTES GASEOSOS - CALCULADORA EN TIEMPO REAL
   ========================================================================== */
function initNobleGasesCalculator() {
  const inputs = document.querySelectorAll(".isotope-input");
  inputs.forEach((input) => {
    input.addEventListener("input", calculateNobleGases);
  });
  calculateNobleGases();
}

function calculateNobleGases() {
  const getVal = (id) => normalizeNumber(document.getElementById(id)?.value) || 0;

  const ar41 = getVal("iso-ar41");
  const kr85 = getVal("iso-kr85");
  const kr85m = getVal("iso-kr85m");
  const xe133 = getVal("iso-xe133");
  const xe135 = getVal("iso-xe135");
  const xe135m = getVal("iso-xe135m");

  const totalActividad = ar41 + kr85 + kr85m + xe133 + xe135 + xe135m;

  // Factores ilustrativos de Restricción Operacional (RO)
  const roPorcentaje = Math.min(100, (totalActividad / 500) * 100);

  const totalEl = document.getElementById("total-actividad-gases");
  const roEl = document.getElementById("ro-gases-percentage");
  const roBar = document.getElementById("ro-gases-bar");

  if (totalEl) totalEl.textContent = totalActividad > 0 ? totalActividad.toExponential(3) + " MBq" : "0.000 MBq";
  if (roEl) roEl.textContent = roPorcentaje.toFixed(2) + " %";

  if (roBar) {
    roBar.style.width = roPorcentaje + "%";
    if (roPorcentaje < 20) {
      roBar.style.backgroundColor = "var(--color-success)";
    } else if (roPorcentaje < 70) {
      roBar.style.backgroundColor = "var(--color-warning)";
    } else {
      roBar.style.backgroundColor = "var(--color-danger)";
    }
  }
}

/* ==========================================================================
   RESPALDO LOCAL EN LOCALSTORAGE
   ========================================================================== */
function saveDraft() {
  updateAutosaveBadge("saving");
  const draft = {
    timestamp: Date.now(),
    routine: routineData,
  };
  localStorage.setItem("reg_rad_demo_draft", JSON.stringify(draft));
  setTimeout(() => updateAutosaveBadge("saved"), 300);
}

function checkLocalDraft() {
  const raw = localStorage.getItem("reg_rad_demo_draft");
  if (!raw) return;
  try {
    const draft = JSON.parse(raw);
    if (draft.routine && draft.routine.length > 0) {
      routineData = draft.routine;
      renderRoutineRows();
      updateAutosaveBadge("saved");
    }
  } catch (e) {
    console.error("Error al cargar borrador", e);
  }
}
