<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>MexxicanMx — Super Admin Planes</title>
  <link rel="icon" type="image/png" href="gorro.ico">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@300;400;500;700&display=swap');

    :root {
      --gold: #e8c07d; --gold-dark: #c9993a;
      --bg: #0f0f0f; --bg2: #1a1a1a; --bg3: #242424;
      --border: #2e2e2e; --text: #f0ece4; --text-muted: #888;
      --green: #4caf7a; --red: #e05c5c; --blue: #5c9ce0;
      --orange: #e09a5c; --radius: 14px;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; padding: 2rem; }
    h1, h2, h3 { font-family: 'Playfair Display', serif; }

    /* Login */
    #login-screen {
      display: flex; align-items: center; justify-content: center;
      min-height: 80vh;
    }
    .login-box {
      background: var(--bg2); border: 1px solid var(--border);
      border-radius: 20px; padding: 2.5rem; width: 100%; max-width: 380px;
    }

    /* Layout */
    #admin-screen { display: none; max-width: 1100px; margin: 0 auto; }
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }

    /* Cards */
    .card { background: var(--bg2); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1.5rem; }
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 1rem; margin-bottom: 2rem; }
    .stat-card { background: var(--bg2); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.25rem; }
    .stat-value { font-family: 'Playfair Display', serif; font-size: 2rem; color: var(--gold); }
    .stat-label { color: var(--text-muted); font-size: .82rem; margin-top: .2rem; }

    /* Table */
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th { background: var(--bg3); color: var(--text-muted); font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; padding: .75rem 1rem; text-align: left; }
    td { padding: .9rem 1rem; border-bottom: 1px solid var(--border); font-size: .88rem; vertical-align: middle; }
    tr:hover td { background: rgba(255,255,255,.02); }

    /* Buttons */
    .btn { display: inline-flex; align-items: center; gap: .4rem; padding: .55rem 1.1rem; border-radius: 8px; border: none; font-family: 'DM Sans', sans-serif; font-weight: 500; font-size: .85rem; cursor: pointer; transition: all .2s; }
    .btn-gold  { background: linear-gradient(135deg, var(--gold), var(--gold-dark)); color: #111; }
    .btn-green { background: rgba(76,175,122,.15); border: 1px solid var(--green); color: var(--green); }
    .btn-red   { background: rgba(224,92,92,.15); border: 1px solid var(--red); color: var(--red); }
    .btn-ghost { background: transparent; border: 1px solid var(--border); color: var(--text); }
    .btn:hover { opacity: .82; transform: translateY(-1px); }

    /* Badge */
    .badge { display: inline-block; padding: .2rem .65rem; border-radius: 99px; font-size: .72rem; font-weight: 600; letter-spacing: .04em; }
    .badge-activo    { background: rgba(76,175,122,.15); color: var(--green); border: 1px solid var(--green); }
    .badge-pendiente { background: rgba(224,154,92,.15); color: var(--orange); border: 1px solid var(--orange); }
    .badge-vencido   { background: rgba(224,92,92,.15); color: var(--red); border: 1px solid var(--red); }
    .badge-sin-plan  { background: rgba(100,100,100,.1); color: #555; border: 1px solid #333; }
    .badge-basico    { background: rgba(100,100,100,.15); color: #aaa; border: 1px solid #444; }
    .badge-plus      { background: rgba(232,192,125,.15); color: var(--gold); border: 1px solid var(--gold); }
    .badge-premium   { background: rgba(92,156,224,.15); color: var(--blue); border: 1px solid var(--blue); }

    /* Form */
    .form-control { width: 100%; padding: .75rem 1rem; background: var(--bg); border: 1px solid var(--border); border-radius: 10px; color: var(--text); font-size: .9rem; outline: none; font-family: 'DM Sans', sans-serif; transition: border-color .2s; }
    .form-control:focus { border-color: var(--gold); }
    .form-label { display: block; margin-bottom: .4rem; font-size: .75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .06em; }
    select.form-control { cursor: pointer; }

    /* Modal */
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.85); z-index: 500; display: none; align-items: center; justify-content: center; padding: 1rem; }
    .modal-overlay.show { display: flex; }
    .modal-box { background: var(--bg2); border: 1px solid var(--border); border-radius: 20px; padding: 2rem; max-width: 480px; width: 100%; }

    /* Toast */
    #toast-wrap { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999; display: flex; flex-direction: column; gap: .5rem; }
    .toast { background: var(--bg2); border: 1px solid var(--border); border-radius: 12px; padding: .9rem 1.3rem; min-width: 260px; animation: slideIn .3s ease; }
    .toast.ok  { border-color: var(--green); }
    .toast.err { border-color: var(--red); }
    @keyframes slideIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

    /* Días restantes */
    .dias-ok  { color: var(--green); font-weight: 600; }
    .dias-warn { color: var(--orange); font-weight: 600; }
    .dias-bad  { color: var(--red); font-weight: 600; }
  </style>
</head>
<body>

<!-- ══ LOGIN ══════════════════════════════════════════════════ -->
<div id="login-screen">
  <div class="login-box">
    <h2 style="color:var(--gold);text-align:center;margin-bottom:.5rem">MexxicanMx</h2>
    <p style="color:var(--text-muted);text-align:center;font-size:.85rem;margin-bottom:2rem">Super Admin · Planes</p>
    <label class="form-label">PIN de acceso</label>
    <input type="password" id="input-pin" class="form-control" placeholder="••••••••••" style="margin-bottom:1rem" onkeydown="if(event.key==='Enter')entrar()">
    <button class="btn btn-gold" style="width:100%;justify-content:center;padding:.85rem" onclick="entrar()">Entrar →</button>
    <p id="login-error" style="color:var(--red);font-size:.82rem;margin-top:.75rem;text-align:center;display:none">PIN incorrecto</p>
  </div>
</div>

<!-- ══ ADMIN SCREEN ════════════════════════════════════════════ -->
<div id="admin-screen">

  <div class="header">
    <div>
      <h1 style="font-size:1.8rem;color:var(--gold)">Panel de Planes</h1>
      <p style="color:var(--text-muted);font-size:.88rem;margin-top:.2rem">Activa y gestiona los planes de cada restaurante</p>
    </div>
    <div style="display:flex;gap:.75rem;align-items:center">
      <button class="btn btn-ghost" onclick="cargarLista()">🔄 Actualizar</button>
      <button class="btn btn-red" onclick="cerrarSesion()">Salir</button>
    </div>
  </div>

  <!-- Stats -->
  <div class="stat-grid">
    <div class="stat-card"><div class="stat-value" id="stat-total">0</div><div class="stat-label">Restaurantes totales</div></div>
    <div class="stat-card"><div class="stat-value" id="stat-activos">0</div><div class="stat-label">Planes activos</div></div>
    <div class="stat-card"><div class="stat-value" id="stat-sin-plan">0</div><div class="stat-label">Sin plan</div></div>
    <div class="stat-card"><div class="stat-value" id="stat-ingresos" style="font-size:1.4rem">$0</div><div class="stat-label">Ingresos estimados / mes</div></div>
  </div>

  <!-- Buscador -->
  <div class="card" style="padding:1rem">
    <input type="text" id="buscador" class="form-control" placeholder="🔍 Buscar restaurante..." oninput="filtrarTabla()" style="max-width:360px">
  </div>

  <!-- Tabla -->
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Restaurante</th>
            <th>Admin</th>
            <th>Plan actual</th>
            <th>Estado</th>
            <th>Vence</th>
            <th>Días restantes</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tabla-body">
          <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:3rem">Cargando...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /admin-screen -->

<!-- Modal ya no necesario -->

<div id="toast-wrap"></div>

<script>
const fmt = n => new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(n);
let _pin = '';
let _datos = [];
let _restauranteSeleccionado = null;

const precios = {
  basico:  { mensual: 135, anual: 101 * 12 },
  plus:    { mensual: 299, anual: 224 * 12 },
  premium: { mensual: 599, anual: 449 * 12 }
};

// ── LOGIN ────────────────────────────────────────────────────
function entrar() {
  _pin = document.getElementById('input-pin').value.trim();
  if (!_pin) return;
  cargarLista();
}

function cerrarSesion() {
  _pin = '';
  document.getElementById('admin-screen').style.display = 'none';
  document.getElementById('login-screen').style.display = 'flex';
  document.getElementById('input-pin').value = '';
}

// ── CARGAR LISTA ─────────────────────────────────────────────
async function cargarLista() {
  try {
    const r   = await fetch(`planes.php?accion=lista&pin=${encodeURIComponent(_pin)}`);
    const res = await r.json();

    if (!res.ok) {
      document.getElementById('login-error').style.display = 'block';
      return;
    }

    document.getElementById('login-error').style.display = 'none';
    document.getElementById('login-screen').style.display = 'none';
    document.getElementById('admin-screen').style.display = 'block';

    _datos = res.data;
    renderTabla(_datos);
    actualizarStats(_datos);

  } catch(e) {
    toast('Error de conexión', 'err');
  }
}

// ── STATS ────────────────────────────────────────────────────
function actualizarStats(data) {
  const activos  = data.filter(r => r.estado === 'activo').length;
  const sinPlan  = data.filter(r => !r.plan).length;
  const ingresos = data.filter(r => r.estado === 'activo').reduce((s,r) => s + parseFloat(r.monto || 0), 0);

  document.getElementById('stat-total').textContent   = data.length;
  document.getElementById('stat-activos').textContent = activos;
  document.getElementById('stat-sin-plan').textContent = sinPlan;
  document.getElementById('stat-ingresos').textContent = fmt(ingresos);
}

// ── TABLA ────────────────────────────────────────────────────
function renderTabla(data) {
  const hoy = new Date();
  document.getElementById('tabla-body').innerHTML = data.map(r => {

    const planActualBadge = r.plan
      ? `<span class="badge badge-${r.plan}">${{basico:'🥈 Básico',plus:'⭐ Plus',premium:'💎 Premium'}[r.plan]}</span>`
      : `<span class="badge badge-sin-plan">Sin plan</span>`;

    const estadoBadge = r.estado
      ? `<span class="badge badge-${r.estado}">${{activo:'Activo',pendiente:'Pendiente',vencido:'Vencido',cancelado:'Cancelado'}[r.estado] || r.estado}</span>`
      : `<span class="badge badge-sin-plan">—</span>`;

    let diasHtml = '—';
    if (r.fecha_vencimiento) {
      const vence = new Date(r.fecha_vencimiento);
      const dias  = Math.ceil((vence - hoy) / (1000*60*60*24));
      const cls   = dias > 10 ? 'dias-ok' : dias > 3 ? 'dias-warn' : 'dias-bad';
      diasHtml = `<span class="${cls}">${dias > 0 ? dias + ' días' : 'Vencido'}</span>`;
    }

    // Selectores inline con precios calculados
    const planDefault   = r.plan || 'plus';
    const precioDefault = precios[planDefault]['mensual'];

    return `
      <tr data-nombre="${(r.nombre||'').toLowerCase()}" id="fila-${r.id}">
        <td>
          <div style="font-weight:600">${r.nombre || '—'}</div>
          <div style="font-size:.78rem;color:var(--text-muted)">${r.direccion || 'Sin dirección'}</div>
        </td>
        <td>
          <div>${r.admin_nombre || '—'}</div>
          <div style="font-size:.78rem;color:var(--text-muted)">${r.admin_usuario || ''}</div>
        </td>
        <td>${planActualBadge}</td>
        <td>${estadoBadge}</td>
        <td style="font-size:.82rem;color:var(--text-muted)">${r.fecha_vencimiento || '—'}</td>
        <td>${diasHtml}</td>

        <!-- SELECTORES INLINE + BOTÓN ACTIVAR -->
        <td>
          <div style="display:flex;flex-direction:column;gap:.5rem;min-width:260px">

            <!-- Fila 1: Plan + Período -->
            <div style="display:flex;gap:.4rem">
              <select id="sel-plan-${r.id}"
                onchange="actualizarMontoInline(${r.id})"
                style="flex:1;padding:.4rem .5rem;background:#0d0d0d;border:1px solid #333;
                       border-radius:8px;color:var(--text);font-size:.82rem;cursor:pointer;outline:none">
                <option value="basico"  ${planDefault==='basico'  ? 'selected':''}>🥈 Básico</option>
                <option value="plus"    ${planDefault==='plus'    ? 'selected':''}>⭐ Plus</option>
                <option value="premium" ${planDefault==='premium' ? 'selected':''}>💎 Premium</option>
              </select>
              <select id="sel-periodo-${r.id}"
                onchange="actualizarMontoInline(${r.id})"
                style="flex:1;padding:.4rem .5rem;background:#0d0d0d;border:1px solid #333;
                       border-radius:8px;color:var(--text);font-size:.82rem;cursor:pointer;outline:none">
                <option value="mensual">Mensual</option>
                <option value="anual">Anual -25%</option>
              </select>
            </div>

            <!-- Fila 2: Monto + Notas -->
            <div style="display:flex;gap:.4rem">
              <input type="number" id="sel-monto-${r.id}" value="${precioDefault}"
                style="width:90px;padding:.4rem .5rem;background:#0d0d0d;border:1px solid #333;
                       border-radius:8px;color:var(--gold);font-size:.82rem;outline:none">
              <input type="text" id="sel-notas-${r.id}" placeholder="Notas (opcional)"
                style="flex:1;padding:.4rem .5rem;background:#0d0d0d;border:1px solid #333;
                       border-radius:8px;color:var(--text);font-size:.78rem;outline:none">
            </div>

            <!-- Fila 3: Botones -->
            <div style="display:flex;gap:.4rem">
              <button class="btn btn-green" style="flex:1;justify-content:center"
                onclick="activarPlanInline(${r.id})">
                ✅ Activar
              </button>
              ${r.plan_id ? `<button class="btn btn-red" onclick="cancelarPlan(${r.plan_id})">✕</button>` : ''}
              <button class="btn btn-ghost" onclick="resetPassword(${r.id}, '${(r.admin_usuario||'').replace(/'/g,'')}')">🔑</button>
            </div>

          </div>
        </td>
      </tr>`;
  }).join('') || '<tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:3rem">Sin resultados</td></tr>';
}

// Actualiza el monto cuando cambia plan o período
function actualizarMontoInline(rid) {
  const plan    = document.getElementById(`sel-plan-${rid}`).value;
  const periodo = document.getElementById(`sel-periodo-${rid}`).value;
  document.getElementById(`sel-monto-${rid}`).value = precios[plan][periodo];
}

// Activar plan directo desde la fila
async function activarPlanInline(rid) {
  const plan    = document.getElementById(`sel-plan-${rid}`).value;
  const periodo = document.getElementById(`sel-periodo-${rid}`).value;
  const monto   = parseFloat(document.getElementById(`sel-monto-${rid}`).value);
  const notas   = document.getElementById(`sel-notas-${rid}`).value;

  const btn = document.querySelector(`#fila-${rid} .btn-green`);
  btn.textContent = '⏳ Activando...';
  btn.disabled = true;

  try {
    const r   = await fetch(`planes.php?accion=activar&pin=${encodeURIComponent(_pin)}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ restaurante_id: rid, plan, periodo, monto, notas })
    });
    const res = await r.json();
    if (res.ok) {
      toast('✅ ' + res.mensaje, 'ok');
      cargarLista();
    } else {
      toast('❌ ' + res.mensaje, 'err');
      btn.textContent = '✅ Activar';
      btn.disabled = false;
    }
  } catch(e) {
    toast('Error de conexión', 'err');
    btn.textContent = '✅ Activar';
    btn.disabled = false;
  }
}

// ── FILTRO BÚSQUEDA ──────────────────────────────────────────
function filtrarTabla() {
  const q = document.getElementById('buscador').value.toLowerCase();
  const filtrado = _datos.filter(r => (r.nombre||'').toLowerCase().includes(q));
  renderTabla(filtrado);
}



async function cancelarPlan(planId) {
  if (!confirm('¿Cancelar este plan?')) return;
  try {
    const r   = await fetch(`planes.php?accion=cancelar&pin=${encodeURIComponent(_pin)}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ plan_id: planId })
    });
    const res = await r.json();
    if (res.ok) { toast('Plan cancelado', 'ok'); cargarLista(); }
    else toast('Error: ' + res.mensaje, 'err');
  } catch(e) {
    toast('Error de conexión', 'err');
  }
}

async function resetPassword(rid, usuario) {
  const nueva = prompt(`Nueva contraseña para "${usuario}":`);
  if (!nueva || nueva.length < 6) { alert('Mínimo 6 caracteres'); return; }
  if (!confirm(`¿Resetear contraseña de "${usuario}"?`)) return;
  try {
    const r = await fetch(`planes.php?accion=reset_password&pin=${encodeURIComponent(_pin)}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ restaurante_id: rid, nueva_password: nueva })
    });
    const res = await r.json();
    if (res.ok) toast('✅ Contraseña actualizada', 'ok');
    else toast('❌ ' + res.mensaje, 'err');
  } catch(e) {
    toast('Error de conexión', 'err');
  }
}

// ── TOAST ────────────────────────────────────────────────────
function toast(msg, tipo = 'ok') {
  const w = document.getElementById('toast-wrap');
  const t = document.createElement('div');
  t.className = `toast ${tipo}`;
  t.textContent = msg;
  w.appendChild(t);
  setTimeout(() => t.remove(), 4000);
}


</script>
</body>
</html>