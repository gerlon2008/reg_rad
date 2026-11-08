/**
 * REGISTRO RADIOLÓGICO ONLINE - CENTRAL NUCLEAR EMBALSE / NA-SA
 * Lógica de Interfaz, Cálculos Radiológicos y Sistema de Popups/Toasts Futuristas
 * 100% Offline (Sin alertas nativas ni dependencias externas)
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
   SISTEMA DE POPUPS MODALES Y NOTIFICACIONES TOAST (SIN ALERT NATIVO)
   ========================================================================== */
function showToast(message, type = "info", duration = 3500) {
  let container = document.getElementById("toast-container");
  if (!container) {
    container = document.createElement("div");
    container.id = "toast-container";
    container.className = "toast-container";
    document.body.appendChild(container);
  }

  const toast = document.createElement("div");
  toast.className = `toast toast-${type}`;

  let icon = "ℹ️";
  if (type === "success") icon = "✔️";
  if (type === "warning") icon = "⚠️";
  if (type === "danger") icon = "🚨";

  toast.innerHTML = `
    <span style="font-size: 15px;">${icon}</span>
    <div style="flex: 1; word-break: break-word;">${message}</div>
    <span style="cursor: pointer; opacity: 0.6; font-size: 14px;" onclick="this.parentElement.remove()">✕</span>
  `;

  container.appendChild(toast);

  setTimeout(() => {
    toast.style.transition = "opacity 0.3s ease, transform 0.3s ease";
    toast.style.opacity = "0";
    toast.style.transform = "translateX(40px)";
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

function showConfirmModal({
  title = "Confirmación de Seguridad",
  message = "¿Está seguro de realizar esta acción?",
  confirmText = "CONFIRMAR",
  cancelText = "CANCELAR",
  isDanger = false,
  onConfirm = () => {},
  onCancel = () => {},
}) {
  let overlay = document.getElementById("cyber-modal-overlay");
  if (!overlay) {
    overlay = document.createElement("div");
    overlay.id = "cyber-modal-overlay";
    overlay.className = "modal-overlay";
    document.body.appendChild(overlay);
  }

  const confirmBtnClass = isDanger ? "btn-danger-outline" : "btn-primary";

  overlay.innerHTML = `
    <div class="modal-box">
      <div class="modal-header">
        <h3><span style="color: var(--neon-cyan);">◈</span> ${title}</h3>
        <span style="cursor: pointer; color: var(--text-muted); font-size: 16px;" id="modal-close-x">✕</span>
      </div>
      <div class="modal-body">
        ${message}
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" id="modal-btn-cancel">${cancelText}</button>
        <button class="btn ${confirmBtnClass}" id="modal-btn-confirm">${confirmText}</button>
      </div>
    </div>
  `;

  overlay.classList.add("active");

  function close() {
    overlay.classList.remove("active");
  }

  document.getElementById("modal-close-x").onclick = () => {
    close();
    onCancel();
  };
  document.getElementById("modal-btn-cancel").onclick = () => {
    close();
    onCancel();
  };
  document.getElementById("modal-btn-confirm").onclick = () => {
    close();
    onConfirm();
  };
}

/* ==========================================================================
   TEMA (DARK / LIGHT MODE - DEFAULT DARK FUTURISTA)
   ========================================================================== */
function initTheme() {
  const savedTheme = localStorage.getItem("reg_rad_theme") || "dark";
  document.documentElement.setAttribute("data-theme", savedTheme);

  const toggleBtn = document.getElementById("btn-toggle-theme");
  if (toggleBtn) {
    toggleBtn.addEventListener("click", () => {
      const current = document.documentElement.getAttribute("data-theme");
      const next = current === "dark" ? "light" : "dark";
      document.documentElement.setAttribute("data-theme", next);
      localStorage.setItem("reg_rad_theme", next);
      toggleBtn.innerHTML = next === "dark" ? "☀️ Modo Claro" : "🌙 Modo Oscuro";
      showToast(`Modo visual cambiado a: ${next === "dark" ? "Consola Dark" : "Modo Claro"}`, "info", 2000);
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

  function ping() {
    const now = new Date();
    const timeStr = `${String(now.getHours()).padStart(2, "0")}:${String(now.getMinutes()).padStart(2, "0")}:${String(now.getSeconds()).padStart(2, "0")}`;
    if (lastSyncEl) {
      lastSyncEl.textContent = `Sesión activa (Ping: ${timeStr})`;
    }
  }

  ping();
  setInterval(ping, 120000);
}

function updateAutosaveBadge(status) {
  const el = document.getElementById("autosave-status");
  if (!el) return;
  if (status === "saved") {
    el.innerHTML = "💾 Sincronizado en borrador local";
    el.style.color = "var(--neon-green)";
  } else if (status === "saving") {
    el.innerHTML = "⏳ Escribiendo...";
    el.style.color = "var(--neon-amber)";
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
    obs: "Ronda inicial de turno. Parámetros normales.",
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
    obs: "Trabajo en sello PHT. Monitoreo continuo.",
    oficial: "Pérez, Juan",
  },
];

function initRoutineTable() {
  renderRoutineRows();

  const addBtn = document.getElementById("btn-add-routine");
  if (addBtn) {
    addBtn.addEventListener("click", handleAddRoutineRow);
  }

  const quickInputs = document.querySelectorAll(".quick-input");
  quickInputs.forEach((input) => {
    input.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        handleAddRoutineRow();
      }
    });
  });

  const nowBtn = document.getElementById("btn-time-now");
  if (nowBtn) {
    nowBtn.addEventListener("click", () => {
      const now = new Date();
      const h = String(now.getHours()).padStart(2, "0");
      const m = String(now.getMinutes()).padStart(2, "0");
      const timeInput = document.getElementById("q-hora");
      if (timeInput) timeInput.value = `${h}:${m}`;
      showToast("Hora sincronizada al momento actual", "info", 1500);
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
    showToast("Debe ingresar la fecha y hora de la medición", "warning");
    return;
  }
  if (!recinto || recinto === "-") {
    showToast("Seleccione una ubicación / recinto de planta", "warning");
    recintoSelect.focus();
    return;
  }
  if (gases === null && tritio === null && dosis === null) {
    showToast("Debe ingresar al menos un valor radiológico (Gases, Tritio o Tasa de Dosis)", "warning");
    return;
  }

  function commitEntry() {
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

    gasesInput.value = "";
    tritioInput.value = "";
    dosisInput.value = "";
    obsInput.value = "";
    recintoSelect.focus();

    showToast(`Medición registrada en ${recinto.split(" ")[0]}`, "success");
  }

  // Si la tasa de dosis supera 100 µSv/h -> Popup Modal Futurista de Alerta Crítica
  if (dosis !== null && dosis > 100) {
    showConfirmModal({
      title: "ALERTA DE SEGURIDAD RADIOLÓGICA",
      message: `
        <div style="color: var(--neon-red); font-weight: 700; margin-bottom: 8px;">
          ⚠️ Tasa de dosis elevada: ${dosis} µSv/h
        </div>
        El valor ingresado supera el umbral operativo estándar (> 100 µSv/h). 
        <br><br>
        ¿Confirma que la medición para el recinto <strong>${recinto}</strong> es correcta o desea corregirla?
      `,
      confirmText: "CONFIRMAR VALOR",
      cancelText: "VOLVER Y CORREGIR",
      isDanger: true,
      onConfirm: () => commitEntry(),
      onCancel: () => dosisInput.focus(),
    });
  } else {
    commitEntry();
  }
}

function renderRoutineRows() {
  const tbody = document.getElementById("tbody-rutina");
  const badgeEl = document.getElementById("badge-count-rutina");
  if (!tbody) return;

  tbody.innerHTML = "";

  if (routineData.length === 0) {
    tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding: 24px; color: var(--text-dim); font-family: var(--font-mono);">[ SIN MEDICIONES CARGADAS EN EL TURNO ]</td></tr>`;
  } else {
    routineData.forEach((row) => {
      const tr = document.createElement("tr");

      const gasesClass = row.gases > 1.0 ? "cell-warning" : row.gases > 3.0 ? "cell-danger" : "";
      const tritioClass = row.tritio > 1.0 ? "cell-warning" : row.tritio > 5.0 ? "cell-danger" : "";
      const dosisClass = row.dosis > 20.0 ? "cell-warning" : row.dosis > 100.0 ? "cell-danger" : "";

      tr.innerHTML = `
        <td style="font-family: var(--font-mono); font-weight:600;">${row.fecha}</td>
        <td class="num">${row.hora}</td>
        <td><strong style="color: var(--neon-cyan);">${row.recinto}</strong></td>
        <td class="num ${gasesClass}">${row.gases !== null ? row.gases.toFixed(2) : "-"}</td>
        <td class="num ${tritioClass}">${row.tritio !== null ? row.tritio.toFixed(2) : "-"}</td>
        <td class="num ${dosisClass}">${row.dosis !== null ? row.dosis.toFixed(1) : "-"}</td>
        <td style="color: var(--text-muted); font-size: 11.5px;">${row.obs}</td>
        <td><span class="badge-info" style="font-size: 11px;">${row.oficial}</span></td>
        <td style="text-align: center;">
          <button class="btn btn-danger-outline" style="padding: 3px 8px; font-size: 11px;" onclick="deleteRoutineRow(${row.id})" title="Eliminar registro">✕</button>
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
  const row = routineData.find((r) => r.id === id);
  const recintoName = row ? row.recinto : "esta medición";

  showConfirmModal({
    title: "Eliminar Registro Radiológico",
    message: `¿Confirma la eliminación del registro para <strong>${recintoName}</strong>? Esta acción actualizará el historial del turno.`,
    confirmText: "ELIMINAR REGISTRO",
    cancelText: "CANCELAR",
    isDanger: true,
    onConfirm: () => {
      routineData = routineData.filter((r) => r.id !== id);
      renderRoutineRows();
      saveDraft();
      showToast("Medición eliminada correctamente", "info");
    },
  });
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

  const btnSaveGases = document.getElementById("btn-save-gases");
  if (btnSaveGases) {
    btnSaveGases.addEventListener("click", () => {
      showToast("Espectrometría de Gases Nobles registrada en base de datos", "success");
    });
  }
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
  const roPorcentaje = Math.min(100, (totalActividad / 500) * 100);

  const totalEl = document.getElementById("total-actividad-gases");
  const roEl = document.getElementById("ro-gases-percentage");
  const roBar = document.getElementById("ro-gases-bar");

  if (totalEl) totalEl.textContent = totalActividad > 0 ? totalActividad.toExponential(3) + " MBq" : "0.000 MBq";
  if (roEl) roEl.textContent = roPorcentaje.toFixed(2) + " %";

  if (roBar) {
    roBar.style.width = roPorcentaje + "%";
    if (roPorcentaje < 20) {
      roBar.style.backgroundColor = "var(--neon-green)";
      roBar.style.boxShadow = "var(--neon-green-glow)";
    } else if (roPorcentaje < 70) {
      roBar.style.backgroundColor = "var(--neon-amber)";
      roBar.style.boxShadow = "var(--neon-amber-glow)";
    } else {
      roBar.style.backgroundColor = "var(--neon-red)";
      roBar.style.boxShadow = "var(--neon-red-glow)";
    }
  }
}

/* ==========================================================================
   CIERRE DE TURNO Y LIBRO DE ACTAS CON POPUPS
   ========================================================================== */
window.handleSaveActas = function () {
  const text = document.getElementById("textarea-actas")?.value;
  if (!text || text.trim() === "") {
    showToast("Por favor escriba las novedades antes de guardar en el libro de actas", "warning");
    return;
  }
  showToast("Novedades guardadas en el Libro de Actas de Guardia", "success");
};

window.handleCloseShift = function () {
  showConfirmModal({
    title: "FORMALIZACIÓN Y CIERRE DE TURNO",
    message: `
      Al cerrar la planilla diaria:
      <ul style="margin: 10px 0 0 18px; line-height: 1.6; font-size: 12.5px;">
        <li>Se congelarán todos los relevamientos y efluentes del turno.</li>
        <li>Se estampará la firma digital de guardia.</li>
        <li>Se generará el reporte oficial en PDF para archivo SyR.</li>
      </ul>
      <br>
      ¿Desea proceder con el cierre definitivo de este turno?
    `,
    confirmText: "🔒 CERRAR TURNO Y GENERAR PDF",
    cancelText: "CANCELAR",
    isDanger: false,
    onConfirm: () => {
      showToast("Turno cerrado con éxito. Planilla oficial archivada.", "success", 5000);
    },
  });
};

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
