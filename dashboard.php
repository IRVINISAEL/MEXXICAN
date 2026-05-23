<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
// Verificar que la sesión no lleve más de 8 horas
if (!empty($_SESSION['login_time']) && time() - $_SESSION['login_time'] > 28800) {
    session_destroy();
    header('Location: login.php');
    exit;
}
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
$admin = $_SESSION['admin'];

// Obtener nombre del restaurante
$stmt = db()->prepare("SELECT nombre FROM restaurantes WHERE id = ?");
$stmt->execute([$admin['restaurante_id']]);
$restaurante = $stmt->fetch();
$admin['restaurante_nombre'] = $restaurante['nombre'] ?? 'Mi Restaurante';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>MexxicanMx <?= htmlspecialchars($admin['restaurante_nombre'] ?? 'Mi Restaurante') ?></title>
  <link rel="icon" type="image/png" href="gorro.ico">
  <style>
    /* ── Google Fonts ── */
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400&family=DM+Sans:wght@300;400;500;700&display=swap');

    /* ── Variables globales ── */
    :root {
      --gold:       #e8c07d;
      --gold-dark:  #c9993a;
      --bg:         #0f0f0f;
      --bg2:        #1a1a1a;
      --bg3:        #242424;
      --border:     #2e2e2e;
      --text:       #f0ece4;
      --text-muted: #888;
      --green:      #4caf7a;
      --red:        #e05c5c;
      --blue:       #5c9ce0;
      --orange:     #e09a5c;
      --radius:     14px;
      --shadow:     0 8px 32px rgba(0,0,0,.45);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { font-size: 16px; scroll-behavior: smooth; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      -webkit-font-smoothing: antialiased;
    }

    h1, h2, h3 { font-family: 'Playfair Display', serif; }
    a { color: var(--gold); text-decoration: none; }

    /* ── Scrollbar ── */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: var(--bg); }
    ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 99px; }

    /* ── Buttons ── */
    .btn {
      display: inline-flex; align-items: center; gap: .5rem;
      padding: .65rem 1.4rem; border-radius: 10px; border: none;
      font-family: 'DM Sans', sans-serif; font-weight: 500; font-size: .9rem;
      cursor: pointer; transition: all .2s; letter-spacing: .02em;
    }
    .btn-gold  { background: linear-gradient(135deg, var(--gold), var(--gold-dark)); color: #111; }
    .btn-ghost { background: transparent; border: 1px solid var(--border); color: var(--text); }
    .btn-green { background: rgba(76,175,122,.15); border: 1px solid var(--green); color: var(--green); }
    .btn-red   { background: rgba(224,92,92,.15);  border: 1px solid var(--red);   color: var(--red); }
    .btn-blue  { background: rgba(92,156,224,.15); border: 1px solid var(--blue);  color: var(--blue); }
    .btn:hover  { opacity: .85; transform: translateY(-1px); }
    .btn:active { transform: translateY(0); }

    /* ── Card ── */
    .card {
      background: var(--bg2); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 1.5rem;
      box-shadow: var(--shadow);
    }

    /* ── Badge ── */
    .badge { display: inline-block; padding: .2rem .65rem; border-radius: 99px; font-size: .75rem; font-weight: 500; letter-spacing: .04em; }
    .badge-pendiente      { background: rgba(224,154,92,.15); color: var(--orange); border: 1px solid var(--orange); }
    .badge-en_preparacion { background: rgba(92,156,224,.15); color: var(--blue);   border: 1px solid var(--blue); }
    .badge-listo          { background: rgba(76,175,122,.15); color: var(--green);  border: 1px solid var(--green); }
    .badge-entregado      { background: rgba(100,100,100,.15); color: #888;         border: 1px solid #444; }
    .badge-cancelado      { background: rgba(224,92,92,.15);  color: var(--red);    border: 1px solid var(--red); }

    /* ── Form ── */
    .form-control {
      width: 100%; padding: .8rem 1rem; background: var(--bg);
      border: 1px solid var(--border); border-radius: 10px;
      color: var(--text); font-size: .95rem; outline: none;
      font-family: 'DM Sans', sans-serif; transition: border-color .2s;
    }
    .form-control:focus { border-color: var(--gold); }
    .form-label { display: block; margin-bottom: .4rem; font-size: .8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .06em; }

    /* ── Table ── */
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th { background: var(--bg3); color: var(--text-muted); font-size: .75rem; text-transform: uppercase; letter-spacing: .08em; padding: .75rem 1rem; text-align: left; }
    td { padding: .85rem 1rem; border-bottom: 1px solid var(--border); font-size: .9rem; }
    tr:hover td { background: rgba(255,255,255,.02); }

    /* ── Stats ── */
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 1.2rem; }
    .stat-card { background: var(--bg2); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; }
    .stat-card .stat-value { font-family: 'Playfair Display', serif; font-size: 2.2rem; color: var(--gold); }
    .stat-card .stat-label { color: var(--text-muted); font-size: .85rem; margin-top: .25rem; }

    /* ── Toast ── */
    #toast-container { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999; display: flex; flex-direction: column; gap: .6rem; }
    .toast {
      background: var(--bg2); border: 1px solid var(--border); border-radius: 12px;
      padding: 1rem 1.4rem; display: flex; align-items: center; gap: .75rem;
      box-shadow: 0 8px 32px rgba(0,0,0,.5);
      animation: slideIn .3s ease; min-width: 280px; max-width: 380px;
    }
    .toast.success { border-color: var(--green); }
    .toast.error   { border-color: var(--red); }
    .toast.info    { border-color: var(--blue); }
    @keyframes slideIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes fadeOut { to { opacity: 0; transform: translateX(60%); } }

    /* ── Order card ── */
    .order-card { background: var(--bg2); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.25rem; transition: border-color .3s; }
    .order-card.nuevo { border-color: var(--orange); animation: pulse 1.5s ease infinite; }
    @keyframes pulse { 0%,100% { box-shadow: 0 0 0 0 rgba(224,154,92,.3); } 50% { box-shadow: 0 0 0 8px rgba(224,154,92,0); } }

    /* ── Filter buttons ── */
    .filter-btn { padding: .5rem 1.2rem; border-radius: 99px; border: 1px solid var(--border); background: transparent; color: var(--text-muted); cursor: pointer; font-size: .85rem; transition: all .2s; }
    .filter-btn.active, .filter-btn:hover { background: var(--gold); color: #111; border-color: var(--gold); }

    /* ── Admin layout ── */
    .admin-layout { display: grid; grid-template-columns: 240px 1fr; min-height: 100vh; }
    .sidebar {
      background: var(--bg2); border-right: 1px solid var(--border);
      padding: 1.5rem; position: sticky; top: 0; height: 100vh;
      overflow-y: auto; display: flex; flex-direction: column;
    }
    .sidebar-logo { font-family: 'Playfair Display', serif; color: var(--gold); font-size: 1.5rem; margin-bottom: 2rem; text-align: center; }
    .nav-item {
      display: flex; align-items: center; gap: .75rem; padding: .75rem 1rem;
      border-radius: 10px; color: var(--text-muted); cursor: pointer;
      transition: all .2s; margin-bottom: .25rem; border: none;
      background: none; width: 100%; text-align: left; font-size: .95rem;
      font-family: 'DM Sans', sans-serif;
    }
    .nav-item:hover, .nav-item.active { background: rgba(232,192,125,.1); color: var(--gold); }
    .main-area { padding: 2rem; overflow-y: auto; }

    /* ── Sections ── */
    .page-section { display: none; }
    .page-section.active { display: block; }
    .orders-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px,1fr)); gap: 1rem; }
    .order-actions { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: 1rem; }

    /* ── Estado dots ── */
    .estado-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: .4rem; }
    .dot-pendiente      { background: var(--orange); }
    .dot-en_preparacion { background: var(--blue); }
    .dot-listo          { background: var(--green); }
    .dot-entregado      { background: #666; }

    /* ── Live indicator ── */
    #live-indicator { display: flex; align-items: center; gap: .5rem; color: var(--green); font-size: .85rem; }
    .pulse-dot { width: 8px; height: 8px; background: var(--green); border-radius: 50%; animation: pulse 1.5s ease infinite; }

    /* ── Corte / print ── */
    .summary-row { display: flex; justify-content: space-between; padding: .5rem 0; border-bottom: 1px solid var(--border); }
    @media print {
      .admin-layout, .sidebar { display: none !important; }
    }

    /* ── Modals ── */
    .modal-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,.8);
      z-index: 400; display: none; align-items: center;
      justify-content: center; padding: 1rem;
    }
    .modal-overlay.show { display: flex; }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      .admin-layout { grid-template-columns: 1fr; }
      .sidebar { height: auto; position: static; flex-direction: row; flex-wrap: wrap; gap: .5rem; padding: 1rem; }
      .stat-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 480px) {
      .stat-grid { grid-template-columns: 1fr; }
      .main-area { padding: 1rem; }
    }
  </style>
</head>
<body>

<div class="admin-layout">

  <!-- ═══ SIDEBAR ════════════════════════════════════════════ -->
  <aside class="sidebar">
    <div class="sidebar-logo" style="display:flex;align-items:center;justify-content:center;gap:.5rem;flex-wrap:wrap">
  <span id="logo-restaurante"></span>
  <span><?= htmlspecialchars($admin['restaurante_nombre'] ?? 'Mi Restaurante') ?></span>
  <!--<button onclick="showModal('modal-editar-restaurante')" style="background:rgba(232,192,125,.15);border:1px solid rgba(232,192,125,.3);color:var(--gold);border-radius:6px;padding:.2rem .45rem;font-size:.7rem;cursor:pointer" title="Editar"></button>-->
</div>
    <button class="nav-item active" onclick="showSection('pedidos',this)"> Pedidos en vivo</button>
    <button class="nav-item" onclick="showSection('empleados',this)"> Asistencia</button>
    <button class="nav-item" onclick="showSection('menu',this)"> Menú</button>
    <button class="nav-item" id="nav-promociones" onclick="showSection('promociones',this)" style="display:none">📢 Promociones</button>
    <button class="nav-item" id="nav-premium" onclick="showSection('premium',this)" style="display:none">👑 Premium/Plus</button>
    <button class="nav-item" onclick="showSection('corte',this)"> Corte de caja</button>
    <button class="nav-item" onclick="showSection('pagos',this)">Cuentas / Pagos</button>
    <button class="nav-item" onclick="window.location.href='checador.html'">
      Checador
      </button>
    <button class="nav-item" onclick="abrirModalPlanes()" 
      style="margin-top:.5rem;background:linear-gradient(135deg,rgba(232,192,125,.15),rgba(201,153,58,.08));
             border:1px solid rgba(232,192,125,.3);color:var(--gold);font-weight:600">
      Planes
    </button>

    <div style="margin-top:auto;padding-top:1.5rem;border-top:1px solid var(--border)">
      <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:.5rem"><?= htmlspecialchars($admin['nombre']) ?></div>
      <a href="logout.php" class="btn btn-ghost" style="width:100%;justify-content:center;font-size:.85rem">Cerrar sesión</a>
    </div>
  </aside>

  <!-- ═══ MAIN ════════════════════════════════════════════════ -->
  <main class="main-area">

    <!-- HEADER -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem">
      <div>
        <h1 style="font-size:1.8rem" id="page-title">Pedidos en vivo</h1>
        <div id="live-indicator"><div class="pulse-dot"></div> Actualización automática cada 5s</div>
      </div>
      <div style="display:flex;gap:.75rem;align-items:center">
        <span id="fecha-display" style="color:var(--text-muted);font-size:.9rem"></span>
        <button class="btn btn-ghost" onclick="cargarPedidos()">🔄 Actualizar</button>
      </div>
    </div>


    <!-- ── BANNER PLAN ACTIVO ─────────────────────────────────── -->
    <div id="banner-plan" style="display:none;margin-bottom:1.5rem"></div>

    <!-- ── SECCIÓN PEDIDOS ──────────────────────────────────── -->
    <section class="page-section active" id="section-pedidos">
      <div class="stat-grid" style="margin-bottom:2rem" id="stats-bar">
        <div class="stat-card"><div class="stat-value" id="stat-pendientes">0</div><div class="stat-label">Pendientes</div></div>
        <div class="stat-card"><div class="stat-value" id="stat-prep">0</div><div class="stat-label">En preparación</div></div>
        <div class="stat-card"><div class="stat-value" id="stat-listos">0</div><div class="stat-label">Listos</div></div>
        <div class="stat-card"><div class="stat-value" id="stat-total-dia" style="font-size:1.5rem">$0</div><div class="stat-label"> Total del día</div></div>
      </div>

      <div style="display:flex;gap:.6rem;margin-bottom:1.5rem;flex-wrap:wrap" id="filter-estados">
        <button class="filter-btn active" data-estado="" onclick="filtrarEstado('',this)">Todos</button>
        <button class="filter-btn" data-estado="pendiente" onclick="filtrarEstado('pendiente',this)"><span class="estado-dot dot-pendiente"></span>Pendientes</button>
        <button class="filter-btn" data-estado="en_preparacion" onclick="filtrarEstado('en_preparacion',this)"><span class="estado-dot dot-en_preparacion"></span>En preparación</button>
        <button class="filter-btn" data-estado="listo" onclick="filtrarEstado('listo',this)"><span class="estado-dot dot-listo"></span>Listos</button>
        <button class="filter-btn" data-estado="entregado" onclick="filtrarEstado('entregado',this)">Entregados</button>
      </div>

      <div id="orders-grid" class="orders-grid">
        <div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-muted)">Cargando pedidos...</div>
      </div>
    </section>

    <!-- ── SECCIÓN EMPLEADOS ────────────────────────────────── -->
    <section class="page-section" id="section-empleados">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
        <div style="display:flex;gap:.75rem;align-items:center">
          <input type="date" id="fecha-asistencia" class="form-control" style="width:auto" value="<?= date('Y-m-d') ?>">
          <button class="btn btn-gold" onclick="cargarAsistencia()">Ver asistencia</button>
        </div>
        <button class="btn btn-ghost" onclick="showModal('modal-empleado')">➕ Nuevo empleado</button>
      </div>
      
      <div id="alerta-pin" style="display:none;background:rgba(224,92,92,.08);border:1.5px solid rgba(224,92,92,.35);border-radius:14px;padding:1.1rem 1.5rem;margin-bottom:1.5rem"></div>

      <div class="card" style="margin-bottom:1.5rem">
        <div class="table-wrap">
          <table id="tabla-asistencia">
            <thead><tr><th>Empleado</th><th>Puesto</th><th>Entrada</th><th>Salida</th><th>Horas</th><th>H. Extra</th><th>Pago Total</th><th>Estado</th></tr></thead>
            <tbody id="asistencia-body"><tr><td colspan="8" style="text-align:center;color:var(--text-muted)">Selecciona una fecha</td></tr></tbody>
          </table>
        </div>
      </div>
      
      <div class="card" style="margin-bottom:1.5rem">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem">
          <h3>Empleados registrados</h3>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Puesto</th>
                <th>Sueldo/hora</th>
                <th>Mult. extra</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody id="empleados-body">
              <tr><td colspan="5" style="text-align:center;color:var(--text-muted)">Cargando...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <h3 style="margin-bottom:1.2rem"> Reporte semanal</h3>
        <div class="table-wrap">
          <table id="tabla-semanal">
            <thead><tr><th>Empleado</th><th>Puesto</th><th>Días trabajados</th><th>Total horas</th><th>H. Extra</th><th>Pago total</th></tr></thead>
            <tbody id="semanal-body"></tbody>
          </table>
        </div>
      </div>
    </section>
    
    <!-- ── SECCIÓN PROMOCIONES ──────────────────────────────── -->
    <section class="page-section" id="section-promociones">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
        <p style="color:var(--text-muted)">Sube imágenes de ofertas y eventos para que tus clientes las vean</p>
      </div>

      <!-- Formulario subir promoción -->
      <div class="card" style="margin-bottom:1.5rem">
        <h3 style="margin-bottom:1.2rem">📤 Nueva promoción</h3>
        <div style="display:grid;gap:1rem">
          <div>
            <label class="form-label">Imagen (JPG, PNG, WEBP)</label>
            <input type="file" id="promo-imagen" accept="image/*" class="form-control" style="padding:.5rem">
            <div id="promo-preview" style="display:none;margin-top:.75rem">
              <img id="promo-preview-img" style="max-width:100%;max-height:220px;object-fit:contain;border-radius:10px;border:1px solid var(--border)">
            </div>
          </div>
          <div>
            <label class="form-label">Título (opcional)</label>
            <input id="promo-titulo" class="form-control" placeholder="Ej: Martes de Hamburguesas">
          </div>
          <div>
            <label class="form-label">Descripción (opcional)</label>
            <input id="promo-desc" class="form-control" placeholder="Ej: $80 toda la noche">
          </div>
        </div>
        <div style="margin-top:1.2rem">
          <button class="btn btn-gold" onclick="subirPromocion()">📢 Publicar promoción</button>
        </div>
      </div>

      <!-- Lista de promociones activas -->
      <div class="card">
        <h3 style="margin-bottom:1.2rem">📋 Promociones activas</h3>
        <div id="lista-promociones" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem">
          <div style="color:var(--text-muted);text-align:center;padding:2rem">Cargando...</div>
        </div>
      </div>
    </section>

    <!-- ── SECCIÓN PREMIUM ──────────────────────────────────── -->
    <section class="page-section" id="section-premium">

      <!-- Tema Visual -->
      <div class="card" style="margin-bottom:1.5rem">
        <h3 style="margin-bottom:.4rem"> Tema visual del menú</h3>
        <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:1.5rem">Personaliza los colores que ven tus clientes al ordenar</p>

        <div style="margin-bottom:1.2rem">
          <label class="form-label">Temas predefinidos</label>
          <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem" id="temas-predefinidos">
            <div onclick="seleccionarTema('dorado','#e8c07d')" class="tema-card" data-tema="dorado"
              style="width:80px;height:60px;border-radius:10px;background:linear-gradient(135deg,#1a1a1a,#0f0f0f);border:3px solid #e8c07d;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.7rem;color:#e8c07d;font-weight:700">Dorado</div>
            <div onclick="seleccionarTema('rojo','#e05c5c')" class="tema-card" data-tema="rojo"
              style="width:80px;height:60px;border-radius:10px;background:linear-gradient(135deg,#1a0f0f,#0f0808);border:3px solid #2e2e2e;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.7rem;color:#e05c5c;font-weight:700">Rojo</div>
            <div onclick="seleccionarTema('azul','#5c9ce0')" class="tema-card" data-tema="azul"
              style="width:80px;height:60px;border-radius:10px;background:linear-gradient(135deg,#0f1420,#080d18);border:3px solid #2e2e2e;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.7rem;color:#5c9ce0;font-weight:700">Azul</div>
            <div onclick="seleccionarTema('verde','#4caf7a')" class="tema-card" data-tema="verde"
              style="width:80px;height:60px;border-radius:10px;background:linear-gradient(135deg,#0f1a10,#081008);border:3px solid #2e2e2e;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.7rem;color:#4caf7a;font-weight:700">Verde</div>
            <div onclick="seleccionarTema('morado','#a855f7')" class="tema-card" data-tema="morado"
              style="width:80px;height:60px;border-radius:10px;background:linear-gradient(135deg,#150f1a,#0d0810);border:3px solid #2e2e2e;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.7rem;color:#a855f7;font-weight:700">Morado</div>
          </div>
          <label class="form-label">O elige tu propio color</label>
          <div style="display:flex;align-items:center;gap:1rem">
            <input type="color" id="tema-color-picker" value="#e8c07d"
              style="width:60px;height:44px;border-radius:8px;border:1px solid var(--border);background:var(--bg);cursor:pointer;padding:4px"
              oninput="seleccionarTema('personalizado', this.value)">
            <span id="tema-color-hex" style="color:var(--text-muted);font-size:.85rem">#e8c07d</span>
          </div>
        </div>

        <div style="display:flex;gap:.75rem;align-items:center;margin-top:1rem">
          <button class="btn btn-gold" onclick="guardarTema()">💾 Guardar tema</button>
          <span id="tema-preview-txt" style="font-size:.85rem;color:var(--text-muted)"></span>
        </div>
      </div>
      
      <!-- Redes Sociales -->
<div class="card" style="margin-bottom:1.5rem">
  <h3 style="margin-bottom:.4rem"> Redes sociales</h3>
  <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:1.5rem">Aparecen en la sección de Eventos de tus clientes</p>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.2rem">
    <div>
      <label class="form-label">📘 Facebook</label>
      <input id="red-facebook" class="form-control" placeholder="https://facebook.com/tu-pagina">
    </div>
    <div>
      <label class="form-label">📸 Instagram</label>
      <input id="red-instagram" class="form-control" placeholder="https://instagram.com/tu-usuario">
    </div>
    <div>
      <label class="form-label">🟢 WhatsApp</label>
      <input id="red-whatsapp" class="form-control" placeholder="521234567890 (solo número)">
    </div>
    <div>
      <label class="form-label">🎵 TikTok</label>
      <input id="red-tiktok" class="form-control" placeholder="https://tiktok.com/@tu-usuario">
    </div>
    <div>
      <label class="form-label">🐦 Twitter / X</label>
      <input id="red-twitter" class="form-control" placeholder="https://x.com/tu-usuario">
    </div>
  </div>
  <button class="btn btn-gold" onclick="guardarRedes()">💾 Guardar redes</button>
</div>

      <!-- Sistema de Puntos -->
      <div class="card" style="margin-bottom:1.5rem">
        <h3 style="margin-bottom:.4rem"> Sistema de puntos</h3>
        <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:1.5rem">Cada $50 gastados = 1 punto. Al llegar a la meta ganan un premio.</p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.2rem">
          <div>
            <label class="form-label">Premio al llegar a la meta</label>
            <input id="pts-premio" class="form-control" placeholder="Ej: Bebida gratis, 10% descuento...">
          </div>
          <div>
            <label class="form-label">Meta de puntos</label>
            <input id="pts-meta" class="form-control" type="number" value="10" min="1" max="100" placeholder="10">
          </div>
        </div>
        <button class="btn btn-gold" onclick="guardarConfigPuntos()"> Guardar configuración</button>
      </div>

      <!-- Tabla de clientes con puntos -->
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem">
          <h3> Clientes y sus puntos</h3>
          <button class="btn btn-ghost" onclick="cargarClientesPuntos()">🔄 Actualizar</button>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Teléfono</th><th>Puntos</th><th>Visitas</th><th>Última visita</th><th>Acción</th></tr></thead>
            <tbody id="clientes-puntos-body">
              <tr><td colspan="4" style="text-align:center;color:var(--text-muted)">Sin clientes con puntos aún</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ── SECCIÓN MENÚ ─────────────────────────────────────── -->
    <section class="page-section" id="section-menu">
      <div style="display:flex;justify-content:flex-end;margin-bottom:1.5rem">
        <button class="btn btn-gold" onclick="showModal('modal-menu')">➕ Agregar platillo</button>
      </div>
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Emoji</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Disponible</th><th>Acciones</th></tr></thead>
            <tbody id="menu-admin-body"></tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ── SECCIÓN QRs ──────────────────────────────────────── -->
    <section class="page-section" id="section-qrs">
      <div class="card" style="margin-bottom:1.5rem">
        <h3 style="margin-bottom:1.5rem"> Generador de Códigos QR</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem">

          <!-- QR 1: Mesa -->
          <div style="background:var(--bg3);border-radius:16px;padding:1.5rem;text-align:center">
            <div style="font-size:2.5rem;margin-bottom:.75rem">🪑</div>
            <h4 style="color:var(--gold);margin-bottom:.5rem">QR Mesa (Interior)</h4>
            <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:1rem">El cliente escanea, se registra y ordena desde su mesa</p>
            <div id="qr-mesa-container" style="background:#fff;border-radius:12px;padding:1rem;margin-bottom:1rem;display:inline-block"></div>
            <div style="display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap">
              <input type="number" id="qr-mesa-num" placeholder="# Mesa" min="1" max="30" class="form-control" style="width:100px">
              <button class="btn btn-gold" onclick="generarQRMesa()">Generar</button>
              <button class="btn btn-ghost" onclick="imprimirQR('mesa')">🖨️</button>
            </div>
          </div>

          <!-- QR 2: Para llevar -->
          <div style="background:var(--bg3);border-radius:16px;padding:1.5rem;text-align:center">
            <div style="font-size:2.5rem;margin-bottom:.75rem">🥡</div>
            <h4 style="color:var(--gold);margin-bottom:.5rem">QR Para llevar (Exterior)</h4>
            <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:1rem">Afuera del restaurante — cliente ordena sin entrar</p>
            <div id="qr-llevar-container" style="background:#fff;border-radius:12px;padding:1rem;margin-bottom:1rem;display:inline-block"></div>
            <div style="display:flex;gap:.5rem;justify-content:center">
              <button class="btn btn-gold" onclick="generarQRLlevar()">Generar</button>
              <button class="btn btn-ghost" onclick="imprimirQR('llevar')">🖨️</button>
            </div>
          </div>

          <!-- QR 3: Empleados -->
          <!-- <div style="background:var(--bg3);border-radius:16px;padding:1.5rem;text-align:center">
            <div style="font-size:2.5rem;margin-bottom:.75rem">👤</div>
            <h4 style="color:var(--gold);margin-bottom:.5rem">QR Checador (Empleados)</h4>
            <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:1rem">Entrada/salida del personal — soporta horas extra</p>
            <div id="qr-emp-container" style="background:#fff;border-radius:12px;padding:1rem;margin-bottom:1rem;display:inline-block"></div>
            <div style="display:flex;gap:.5rem;justify-content:center">
              <button class="btn btn-gold" onclick="generarQREmp()">Generar</button>
              <button class="btn btn-ghost" onclick="imprimirQR('emp')">🖨️</button>
            </div>
          </div> -->
        </div>
      </div>
    </section>

    <!-- ── SECCIÓN CORTE ────────────────────────────────────── -->
    <section class="page-section" id="section-corte">
      <div style="display:flex;gap:.75rem;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap">
        <input type="date" id="fecha-corte" class="form-control" style="width:auto" value="<?= date('Y-m-d') ?>">
        <button class="btn btn-gold" onclick="cargarCorte()">Ver corte</button>
        <button class="btn btn-ghost" onclick="imprimirCorte()">🖨️ Imprimir corte</button>
      </div>

      <div id="corte-content">
        <div style="text-align:center;color:var(--text-muted);padding:3rem">Selecciona una fecha y genera el corte</div>
      </div>
    </section>
    
    <section class="page-section" id="section-pagos">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
        <p style="color:var(--text-muted)">Solicitudes de cuenta del día</p>
        <button class="btn btn-ghost" onclick="cargarPagos()">🔄 Actualizar</button>
      </div>
      <div id="pagos-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1rem">
        <div style="color:var(--text-muted);text-align:center;padding:3rem;grid-column:1/-1">Cargando...</div>
      </div>
    </section>

  </main><!-- /main-area -->
</div><!-- /admin-layout -->

<!-- ═══ MODAL EDITAR SALARIO ════════════════════════════════ -->
<div class="modal-overlay" id="modal-editar-salario">
  <div class="card" style="max-width:400px;width:100%">
    <h3 style="margin-bottom:.4rem">✏️ Editar salario</h3>
    <p id="edit-sal-nombre" style="color:var(--gold);font-size:.95rem;margin-bottom:1.5rem;font-weight:600"></p>
    <input type="hidden" id="edit-sal-id">
    <div style="display:grid;gap:1rem">
      <div>
        <label class="form-label">Sueldo por hora ($MXN)</label>
        <input id="edit-sal-sueldo" class="form-control" type="number" min="1" step="0.50" placeholder="Ej: 80">
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:.3rem">
          Este es el pago que recibirá por cada hora trabajada al registrar su salida
        </div>
      </div>
      <div>
        <label class="form-label">Multiplicador hora extra</label>
        <input id="edit-sal-mult" class="form-control" type="number" min="1" step="0.1" placeholder="Ej: 1.5">
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:.3rem">
          Ejemplo: 1.5 = se paga 1.5× el sueldo/hora por cada hora extra
        </div>
      </div>
    </div>
    <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:1.5rem">
      <button class="btn btn-ghost" onclick="hideModal('modal-editar-salario')">Cancelar</button>
      <button class="btn btn-gold" onclick="guardarSalario()">💾 Guardar</button>
    </div>
  </div>
</div>

<!-- ═══ MODAL NUEVO EMPLEADO ══════════════════════════════════ -->
<div class="modal-overlay" id="modal-empleado">
  <div class="card" style="max-width:480px;width:100%">
    <h3 style="margin-bottom:1.5rem">➕ Nuevo empleado</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
      <div><label class="form-label">Nombre</label><input id="emp-nombre" class="form-control" placeholder="Carlos"></div>
      <div><label class="form-label">Apellido</label><input id="emp-apellido" class="form-control" placeholder="Ramírez"></div>
      <div><label class="form-label">Puesto</label>
  <select id="emp-puesto" class="form-control">
    <optgroup label="🍽️ Sala (Front of House)">
      <option value="mesero">Mesero / Camarero</option>
      <option value="bartender">Bartender / Barman</option>
      <option value="runner">Runner / Corredor</option>
      <option value="busser">Busser / Ayudante de Camarero</option>
      <option value="hostess">Hostess / Recepcionista</option>
      <option value="maitre">Maître / Jefe de Sala</option>
      <option value="sommelier">Sommelier</option>
      <option value="gerente">Gerente General</option>
    </optgroup>
    <optgroup label="👨‍🍳 Cocina (Back of House)">
      <option value="chef">Chef Ejecutivo / Jefe de Cocina</option>
      <option value="souschef">Sous Chef / Subchef</option>
      <option value="cocinero">Cocinero de Línea</option>
      <option value="pastelero">Pastelero</option>
      <option value="lavaplatos">Steward / Lavaplatos</option>
    </optgroup>
    <optgroup label="📋 Gestión y Apoyo">
      <option value="compras">Encargado de Compras</option>
      <option value="cajero">Cajero / Caja</option>
    </optgroup>
  </select>
</div>
      <div><label class="form-label">PIN (4-6 dígitos)</label><input id="emp-pin" class="form-control" placeholder="1234" maxlength="6"></div>
      <div><label class="form-label">Sueldo/hora ($MXN)</label><input id="emp-sueldo" class="form-control" type="number" value="80" min="0"></div>
      <div><label class="form-label">Mult. hora extra</label><input id="emp-mult" class="form-control" type="number" value="1.5" step=".1" min="1"></div>
    </div>
    <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:1.5rem">
      <button class="btn btn-ghost" onclick="hideModal('modal-empleado')">Cancelar</button>
      <button class="btn btn-gold" onclick="crearEmpleado()">Guardar empleado</button>
    </div>
  </div>
</div>

<!-- ═══ MODAL EDITAR PLATILLO ════════════════════════════════ -->
<div class="modal-overlay" id="modal-editar-platillo">
  <div class="card" style="max-width:480px;width:100%">
    <h3 style="margin-bottom:1.5rem">✏️ Editar platillo</h3>
    <input type="hidden" id="edit-plat-id">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
      <div style="grid-column:1/-1"><label class="form-label">Nombre</label><input id="edit-plat-nombre" class="form-control" placeholder="Nombre del platillo"></div>
      <div style="grid-column:1/-1"><label class="form-label">Descripción</label><input id="edit-plat-desc" class="form-control" placeholder="Descripción breve"></div>
      <div><label class="form-label">Precio ($MXN)</label><input id="edit-plat-precio" class="form-control" type="number" min="0"></div>
      <div><label class="form-label">Emoji</label><input id="edit-plat-emoji" class="form-control" placeholder="️" maxlength="4"></div>
      <div style="grid-column:1/-1">
        <label class="form-label">Categoría</label>
        <select id="edit-plat-cat" class="form-control">
          <option value="comida">️ Comida</option>
          <option value="bebida"> Bebida</option>
          <option value="postre"> Postre</option>
          <option value="extra">➕ Extra</option>
        </select>
      </div>
    </div>
    <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:1.5rem">
      <button class="btn btn-ghost" onclick="hideModal('modal-editar-platillo')">Cancelar</button>
      <button class="btn btn-gold" onclick="guardarEdicionPlatillo()">💾 Guardar cambios</button>
    </div>
  </div>
</div>
<!-- ═══ MODAL NUEVO PLATILLO ══════════════════════════════════ -->
<div class="modal-overlay" id="modal-menu">
  <div class="card" style="max-width:480px;width:100%">
    <h3 style="margin-bottom:1.5rem">🍽️ Nuevo platillo</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
      <div style="grid-column:1/-1"><label class="form-label">Nombre</label><input id="mi-nombre" class="form-control" placeholder="Tacos de birria"></div>
      <div style="grid-column:1/-1"><label class="form-label">Descripción</label><input id="mi-desc" class="form-control" placeholder="Con consomé incluido"></div>
      <div><label class="form-label">Precio ($MXN)</label><input id="mi-precio" class="form-control" type="number" min="0" value="0"></div>
      <div><label class="form-label">Emoji</label><input id="mi-emoji" class="form-control" placeholder="🌮" maxlength="4"></div>
      <div style="grid-column:1/-1">
        <label class="form-label">Foto del platillo (opcional)</label>
        <input type="file" id="mi-imagen" accept="image/*" class="form-control" style="padding:.5rem">
        <div id="mi-imagen-preview" style="margin-top:.5rem;display:none">
          <img id="mi-imagen-img" style="width:100%;max-height:160px;object-fit:cover;border-radius:10px;border:1px solid var(--border)">
        </div>
      </div>
      <div><label class="form-label">Categoría</label>
        <select id="mi-cat" class="form-control">
          <option value="comida">🍽️ Comida</option>
          <option value="bebida">🥤 Bebida</option>
          <option value="postre">🍮 Postre</option>
          <option value="extra">➕ Extra</option>
        </select>
      </div>
    </div>
    <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:1.5rem">
      <button class="btn btn-ghost" onclick="hideModal('modal-menu')">Cancelar</button>
      <button class="btn btn-gold" onclick="crearPlatillo()">Guardar platillo</button>
    </div>
  </div>
</div>
<!-- MODAL EDITAR RESTAURANTE -->
<div class="modal-overlay" id="modal-editar-restaurante">
  <div class="card" style="max-width:480px;width:100%;max-height:90vh;overflow-y:auto">
    <h3 style="margin-bottom:1.5rem"> Personalizar restaurante</h3>

    <!-- Logo imagen -->
    <!-- <div style="margin-bottom:1.5rem">
      <label class="form-label">Logo del restaurante (PNG, JPG)</label>
      <div style="display:flex;gap:1rem;align-items:center">
        <div id="logo-img-preview" style="width:64px;height:64px;border-radius:12px;border:2px solid var(--border);background:var(--bg3);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
          <span style="font-size:1.8rem">🍽️/</span>
        </div>
        <div style="flex:1">
          <input type="file" id="logo-img-file" accept="image/*" class="form-control" style="padding:.5rem">
          <div style="font-size:.75rem;color:var(--text-muted);margin-top:.3rem">Se mostrará en el sidebar del dashboard</div>
        </div>
      </div>
    </div> -->

    <!-- Imagen de fondo del menú -->
    <div style="margin-bottom:1.5rem">
      <label class="form-label">Imagen de fondo del menú (banner)</label>
      <div id="hero-preview-box" style="width:100%;height:100px;border-radius:10px;border:2px solid var(--border);background:var(--bg3);background-size:cover;background-position:center;margin-bottom:.5rem;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:.85rem">
        Sin imagen
      </div>
      <input type="file" id="hero-img-file" accept="image/*" class="form-control" style="padding:.5rem">
      <div style="font-size:.75rem;color:var(--text-muted);margin-top:.3rem">Aparece como fondo del header en el menú del cliente</div>
    </div>

    <!-- Slogan -->
    <div style="margin-bottom:1.5rem">
      <label class="form-label">Slogan (texto debajo del nombre)</label>
      <input id="edit-slogan" class="form-control" placeholder="Bienvenido · Ordena desde tu mesa">
    </div>

    <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:1.5rem">
      <button class="btn btn-ghost" onclick="hideModal('modal-editar-restaurante')">Cancelar</button>
      <button class="btn btn-gold" onclick="guardarPersonalizacion()">💾 Guardar</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<!-- ═══ MODAL PLANES ═══════════════════════════════════════════ -->
<div id="modal-planes" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.92);
     z-index:1000;align-items:flex-start;justify-content:center;padding:1.5rem;overflow-y:auto">

  <div style="background:#111;border:1px solid #2a2a2a;border-radius:24px;max-width:860px;
              width:100%;padding:2.5rem;position:relative;margin:auto">

    <!-- Cerrar -->
    <button onclick="cerrarModalPlanes()" style="position:absolute;top:1.25rem;right:1.25rem;
      background:rgba(255,255,255,.06);border:1px solid #2a2a2a;color:#888;
      width:36px;height:36px;border-radius:50%;font-size:1.2rem;cursor:pointer">×</button>

    <!-- Header -->
    <div style="text-align:center;margin-bottom:2rem">
      <div style="display:inline-block;background:rgba(232,192,125,.1);border:1px solid rgba(232,192,125,.3);
           border-radius:99px;padding:.35rem 1rem;font-size:.78rem;color:var(--gold);margin-bottom:.75rem">
        MexxicanMx · Planes
      </div>
      <h2 style="font-family:'Playfair Display',serif;font-size:2rem;color:#f0ece4;margin-bottom:.4rem">
        Elige tu plan
      </h2>
      <p style="color:#666;font-size:.9rem">Sin contratos. Cancela cuando quieras.</p>
    </div>

    <!-- Toggle mensual / anual -->
    <div style="display:flex;align-items:center;justify-content:center;gap:.75rem;margin-bottom:2rem">
      <span id="lbl-mensual" style="font-size:.9rem;color:var(--gold);font-weight:600">Mensual</span>
      <div id="toggle-periodo" onclick="togglePeriodo()"
        style="width:48px;height:26px;background:#222;border:1px solid #333;border-radius:99px;
               cursor:pointer;position:relative;transition:background .2s">
        <div id="toggle-dot" style="width:20px;height:20px;background:#555;border-radius:50%;
             position:absolute;top:2px;left:3px;transition:all .25s"></div>
      </div>
      <span id="lbl-anual" style="font-size:.9rem;color:#555">
        Anual <span style="background:rgba(76,175,122,.15);border:1px solid var(--green);
        color:var(--green);border-radius:99px;padding:.1rem .5rem;font-size:.72rem;margin-left:.3rem">
        -25%</span>
      </span>
    </div>

    <!-- Cards de planes -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:1rem;margin-bottom:2rem">

      <!-- BÁSICO -->
      <div style="background:#161616;border:1px solid #2a2a2a;border-radius:18px;padding:1.75rem;
                  display:flex;flex-direction:column;gap:.6rem;transition:border-color .2s"
           onmouseover="this.style.borderColor='#3a3a3a'" onmouseout="this.style.borderColor='#2a2a2a'">
        <div style="font-size:.75rem;color:#666;text-transform:uppercase;letter-spacing:.1em">Básico</div>
        <div style="display:flex;align-items:baseline;gap:.35rem">
          <span class="precio-plan" data-mensual="135" data-anual="101"
            style="font-family:'Playfair Display',serif;font-size:2.2rem;color:#f0ece4">$135</span>
          <span style="color:#555;font-size:.85rem">/mes</span>
        </div>
        <div class="precio-anual-note" style="font-size:.75rem;color:#555;display:none">$1,212/año · ahorras $408</div>
        <hr style="border:none;border-top:1px solid #222;margin:.25rem 0">
        <div style="font-size:.85rem;color:#999;display:flex;flex-direction:column;gap:.45rem">
          <span> Hasta <b style="color:#f0ece4">5</b> mesas</span>
          <span> Hasta <b style="color:#f0ece4">20</b> productos</span>
          <span> Hasta <b style="color:#f0ece4">300</b> pedidos/mes</span>
          <span> Hasta <b style="color:#f0ece4">2</b> meseros</span>
          <span style="color:#444"> Sin estadísticas</span>
          <span style="color:#444"> Soporte por email</span>
        </div>
        <button onclick="seleccionarPlan('basico')" 
          style="margin-top:auto;padding:.75rem;border-radius:10px;border:1px solid #333;
                 background:transparent;color:#f0ece4;font-family:'DM Sans',sans-serif;
                 font-size:.9rem;cursor:pointer;transition:all .2s"
          onmouseover="this.style.background='#222'" onmouseout="this.style.background='transparent'">
          Elegir Básico
        </button>
      </div>

      <!-- PLUS (destacado) -->
      <div style="background:linear-gradient(160deg,#1a1810,#111);border:1.5px solid rgba(232,192,125,.4);
                  border-radius:18px;padding:1.75rem;display:flex;flex-direction:column;gap:.6rem;
                  position:relative;box-shadow:0 0 40px rgba(232,192,125,.06)">
        <div style="position:absolute;top:-12px;left:50%;transform:translateX(-50%);
             background:linear-gradient(135deg,var(--gold),var(--gold-dark));color:#111;
             border-radius:99px;padding:.25rem .9rem;font-size:.72rem;font-weight:700;white-space:nowrap">
          MÁS POPULAR
        </div>
        <div style="font-size:.75rem;color:var(--gold);text-transform:uppercase;letter-spacing:.1em">Plus</div>
        <div style="display:flex;align-items:baseline;gap:.35rem">
          <span class="precio-plan" data-mensual="299" data-anual="224"
            style="font-family:'Playfair Display',serif;font-size:2.2rem;color:#f0ece4">$299</span>
          <span style="color:#555;font-size:.85rem">/mes</span>
        </div>
        <div class="precio-anual-note" style="font-size:.75rem;color:#555;display:none">$2,688/año · ahorras $900</div>
        <hr style="border:none;border-top:1px solid rgba(232,192,125,.15);margin:.25rem 0">
        <div style="font-size:.85rem;color:#999;display:flex;flex-direction:column;gap:.45rem">
            <span> Hasta <b style="color:#f0ece4">15</b> mesas</span>
            <span> Hasta <b style="color:#f0ece4">60</b> productos</span>
            <span> Hasta <b style="color:#f0ece4">1,000</b> pedidos/mes</span>
            <span> Hasta <b style="color:#f0ece4">5</b> meseros</span>
            <span> Estadísticas <b style="color:#f0ece4">básicas</b></span>
            <span> Email + <b style="color:#f0ece4">WhatsApp</b></span>
            <span> <b style="color:#f0ece4">Enlaces de redes sociales</b></span>
            <span>️ Publicar <b style="color:#f0ece4">ofertas</b> en tu menú</span>
            <span> <b style="color:#f0ece4">Personalización</b> de colores</span>
        </div>
        <button onclick="seleccionarPlan('plus')"
          style="margin-top:auto;padding:.75rem;border-radius:10px;border:none;
                 background:linear-gradient(135deg,var(--gold),var(--gold-dark));color:#111;
                 font-family:'DM Sans',sans-serif;font-size:.9rem;font-weight:700;cursor:pointer;transition:opacity .2s"
          onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
          Elegir Plus
        </button>
      </div>

      <!-- PREMIUM -->
      <div style="background:#161616;border:1px solid #2a2a2a;border-radius:18px;padding:1.75rem;
                  display:flex;flex-direction:column;gap:.6rem;transition:border-color .2s"
           onmouseover="this.style.borderColor='#3a3a3a'" onmouseout="this.style.borderColor='#2a2a2a'">
        <div style="font-size:.75rem;color:#888;text-transform:uppercase;letter-spacing:.1em">Premium</div>
        <div style="display:flex;align-items:baseline;gap:.35rem">
          <span class="precio-plan" data-mensual="599" data-anual="449"
            style="font-family:'Playfair Display',serif;font-size:2.2rem;color:#f0ece4">$599</span>
          <span style="color:#555;font-size:.85rem">/mes</span>
        </div>
        <div class="precio-anual-note" style="font-size:.75rem;color:#555;display:none">$5,388/año · ahorras $1,800</div>
        <hr style="border:none;border-top:1px solid #222;margin:.25rem 0">
        <div style="font-size:.85rem;color:#999;display:flex;flex-direction:column;gap:.45rem">
            <span> <b style="color:#f0ece4">Mesas ilimitadas</b></span>
            <span> <b style="color:#f0ece4">Productos ilimitados</b></span>
            <span> <b style="color:#f0ece4">Pedidos ilimitados</b></span>
            <span> <b style="color:#f0ece4">Meseros ilimitados</b></span>
            <span> Estadísticas <b style="color:#f0ece4">completas</b></span>
            <span> Soporte <b style="color:#f0ece4">prioritario</b></span>
            <span> <b style="color:#f0ece4">Sistema de puntos</b> para clientes</span>
            <span> <b style="color:#f0ece4">Bordes especiales</b> en ofertas</span>
            <span> <b style="color:#f0ece4">Cliente del mes</b> en tablero <span style="color:#f0c040;font-size:.75rem">(próximamente)</span></span>
            <span> <b style="color:#f0ece4">Celebración de cumpleaños</b> para clientes <span style="color:#f0c040;font-size:.75rem">(próximamente)</span></span>
            <span> <b style="color:#f0ece4">Redes sociales</b> + ofertas + colores</span>
        </div>
        <button onclick="seleccionarPlan('premium')"
          style="margin-top:auto;padding:.75rem;border-radius:10px;border:1px solid #333;
                 background:transparent;color:#f0ece4;font-family:'DM Sans',sans-serif;
                 font-size:.9rem;cursor:pointer;transition:all .2s"
          onmouseover="this.style.background='#222'" onmouseout="this.style.background='transparent'">
          Elegir Premium
        </button>
      </div>
    </div>

    <!-- Panel de pago (aparece al seleccionar un plan) -->
    <!-- Panel de confirmación -->
    <div id="panel-pago" style="display:none;margin-top:1.5rem"></div>
    <!-- ═══ MODAL DE CONFIRMACIÓN ════════════════════════════════ -->
<div id="modal-confirmar-plan" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);
     z-index:1100;align-items:center;justify-content:center;padding:1rem">
  <div style="background:#111;border:1px solid #2a2a2a;border-radius:20px;
              max-width:480px;width:100%;padding:2rem;position:relative">

    <div style="text-align:center;margin-bottom:1.25rem">
      <div style="font-size:2.8rem"></div>
      <h3 style="font-family:'Playfair Display',serif;color:var(--gold);font-size:1.4rem;margin-top:.5rem">
        ¿Confirmas tu plan?
      </h3>
    </div>

    <div id="confirm-resumen" style="background:#0d0d0d;border:1px solid #2a2a2a;
         border-radius:14px;padding:1.25rem;margin-bottom:1.25rem;text-align:center">
    </div>

    <div style="background:rgba(232,192,125,.06);border:1px solid rgba(232,192,125,.2);
         border-radius:12px;padding:1rem;margin-bottom:1.5rem">
      <p style="color:#999;font-size:.85rem;line-height:1.7;margin:0">
        Al confirmar te vamos a abrir <b style="color:#f0ece4">WhatsApp</b> con un mensaje
        listo donde nos dices qué plan quieres. Nosotros te respondemos con los datos
        de pago y en cuanto transfieras activamos tu plan. 
      </p>
    </div>

    <div style="display:flex;gap:.75rem">
      <button onclick="cancelarConfirmacion()"
        style="flex:1;padding:.85rem;border-radius:10px;border:1px solid #333;
               background:transparent;color:#888;font-family:'DM Sans',sans-serif;
               font-size:.9rem;cursor:pointer">
        ← Cambiar plan
      </button>
      <a id="btn-confirmar-wa" href="#" target="_blank"
        onclick="confirmarYEnviar(event)"
        style="flex:1;display:flex;align-items:center;justify-content:center;gap:.5rem;
               padding:.85rem;border-radius:10px;background:#25D366;color:#fff;
               font-family:'DM Sans',sans-serif;font-size:.9rem;font-weight:700;
               text-decoration:none;cursor:pointer">
        ✅ Sí, quiero este plan
      </a>
    </div>

  </div>
</div>

  </div>
</div>
<!-- QR library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
const _canjesVistos = new Set();
const BASE = '';
const fmt = n => new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(n);
let todosLosPedidos = [];
let estadoFiltro = '';

function toast(msg,type='info'){
  let c=document.getElementById('toast-container');
  const icons={success:'✅',error:'❌',info:'ℹ️'};
  const t=document.createElement('div');t.className=`toast ${type}`;
  t.innerHTML=`<span>${icons[type]}</span><span>${msg}</span>`;c.appendChild(t);
  setTimeout(()=>{t.style.animation='fadeOut .4s forwards';setTimeout(()=>t.remove(),400);},3500);
}

function showSection(name,btn){
  document.querySelectorAll('.page-section').forEach(s=>s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(b=>b.classList.remove('active'));
  document.getElementById('section-'+name).classList.add('active');
  btn.classList.add('active');
  const titles={pedidos:' Pedidos en vivo',empleados:' Asistencia del personal',menu:' Gestión de menú',qrs:' Códigos QR',corte:' Corte de caja',pagos:'Cuentas / Pagos',premium:' Premium/Plus'};
  document.getElementById('page-title').textContent=titles[name]||name;
  if(name==='empleados'){cargarAsistencia();cargarReporteSemanal();cargarSolicitudesPin();cargarListaEmpleados();}
  if(name==='promociones'){cargarPromocionesAdmin();}
  if(name==='menu')cargarMenuAdmin();
  if(name==='pagos')cargarPagos();
  if(name==='premium'){cargarClientesPuntos();cargarTemaActual();cargarRedes();}
}

function showModal(id){document.getElementById(id).classList.add('show');}
function hideModal(id){document.getElementById(id).classList.remove('show');}

// ── Fecha ────────────────────────────────────────────────────
document.getElementById('fecha-display').textContent = new Date().toLocaleDateString('es-MX',{weekday:'long',year:'numeric',month:'long',day:'numeric'});

// ── PEDIDOS ──────────────────────────────────────────────────
let prevCount = 0;
// ── SONIDO DE NOTIFICACIÓN (tipo Messenger) ──────────────────
function playNotifSound() {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();

    // Nota 1 — tono bajo ascendente
    const o1 = ctx.createOscillator();
    const g1 = ctx.createGain();
    o1.connect(g1); g1.connect(ctx.destination);
    o1.type = 'sine';
    o1.frequency.setValueAtTime(523, ctx.currentTime);
    o1.frequency.exponentialRampToValueAtTime(659, ctx.currentTime + 0.08);
    g1.gain.setValueAtTime(0.35, ctx.currentTime);
    g1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
    o1.start(ctx.currentTime);
    o1.stop(ctx.currentTime + 0.35);

    // Nota 2 — tono alto
    const o2 = ctx.createOscillator();
    const g2 = ctx.createGain();
    o2.connect(g2); g2.connect(ctx.destination);
    o2.type = 'sine';
    o2.frequency.setValueAtTime(784, ctx.currentTime + 0.1);
    o2.frequency.exponentialRampToValueAtTime(988, ctx.currentTime + 0.18);
    g2.gain.setValueAtTime(0, ctx.currentTime + 0.1);
    g2.gain.setValueAtTime(0.28, ctx.currentTime + 0.12);
    g2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.55);
    o2.start(ctx.currentTime + 0.1);
    o2.stop(ctx.currentTime + 0.55);

    setTimeout(() => ctx.close(), 700);
  } catch(e) {}
}

async function cargarPedidos(){
  try{
    const hoy = new Date().toLocaleDateString('en-CA');
    const r=await fetch(`${BASE}/pedidos.php?accion=todos_pedidos&fecha=${hoy}`,{credentials:'include'});
    const res=await r.json();
    if(!res.ok){
      document.getElementById('orders-grid').innerHTML =
        '<div style="grid-column:1/-1;text-align:center;padding:3rem;color:#e05c5c">'
        + '❌ ' + (res.mensaje || 'Error de sesión')
        + ' — <a href="login.php" style="color:#e8c07d">Volver a iniciar sesión</a>'
        + '</div>';
      return; 
    }
    todosLosPedidos=res.data;
    
    // Alertas de canje de premio
const canjes = res.data.filter(p => p.solicitud_canje);
if (canjes.length) {
  canjes.forEach(c => {
    if (!_canjesVistos.has(c.id)) {
      _canjesVistos.add(c.id);
      toast('🎁 ' + c.solicitud_canje, 'info');
    }
  });
}

    if(res.data.length>prevCount && prevCount>0){
      playNotifSound();
      toast('🔔 ¡Nuevo pedido recibido!','info');
    }
    prevCount=res.data.length;
    actualizarStats(res.data);
    renderPedidos(res.data,estadoFiltro);
  }catch(e){console.error(e);}
}

function actualizarStats(pedidos){
  document.getElementById('stat-pendientes').textContent=pedidos.filter(p=>p.estado==='pendiente').length;
  document.getElementById('stat-prep').textContent=pedidos.filter(p=>p.estado==='en_preparacion').length;
  document.getElementById('stat-listos').textContent=pedidos.filter(p=>p.estado==='listo').length;
  const total=pedidos.filter(p=>p.estado!=='cancelado').reduce((s,p)=>s+parseFloat(p.total),0);
  document.getElementById('stat-total-dia').textContent=fmt(total);
}

function filtrarEstado(estado,btn){
  estadoFiltro=estado;
  document.querySelectorAll('#filter-estados .filter-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  renderPedidos(todosLosPedidos,estado);
}

const estadoLabels={pendiente:'⏳ Pendiente',en_preparacion:'👨‍🍳 En preparación',listo:'✅ Listo',entregado:'🎉 Entregado',cancelado:'❌ Cancelado'};
const siguienteEstado={pendiente:'en_preparacion',en_preparacion:'listo'};

function renderPedidos(pedidos,filtro){
  const grid=document.getElementById('orders-grid');
  const filtered=filtro?pedidos.filter(p=>p.estado===filtro):pedidos.filter(p=>p.estado!=='entregado'&&p.estado!=='cancelado');
  if(!filtered.length){grid.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-muted)">No hay pedidos con este estado</div>';return;}
  grid.innerHTML=filtered.map(p=>`
    <div class="order-card ${p.estado==='pendiente'?'nuevo':''}" id="order-${p.id}">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.75rem">
        <div>
          <div style="font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--gold)">
            #${p.numero_orden}
            <span style="font-size:.9rem;color:var(--text-muted);font-family:'DM Sans',sans-serif;margin-left:.4rem">${p.tipo==='mesa'?`Mesa ${p.mesa_numero}`:'Para llevar'}</span>
          </div>
          <div style="font-size:.9rem;color:var(--text-muted)">
  ${p.cliente} · ${new Date(p.creado_en).toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit'})}
  ${p.mesero_nombre ? `<span style="margin-left:.5rem;background:rgba(232,192,125,.12);border:1px solid rgba(232,192,125,.3);color:var(--gold);padding:.1rem .5rem;border-radius:99px;font-size:.8rem">🧑‍🍽️ ${p.mesero_nombre}</span>` : ''}
</div>
        </div>
        <span class="badge badge-${p.estado}">${estadoLabels[p.estado]}</span>
      </div>
      <div style="background:var(--bg3);border-radius:8px;padding:.75rem;margin-bottom:.75rem;white-space:pre-line;font-size:.875rem;line-height:1.6">${p.items_texto||''}</div>
${p.notas ? `
  <div style="background:rgba(232,192,125,.08);border:1px solid rgba(232,192,125,.25);border-radius:8px;padding:.6rem .85rem;margin-bottom:.75rem;font-size:.82rem;color:var(--gold)">
    📝 <strong>Nota del cliente:</strong> ${p.notas}
  </div>` : ''}
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem">
        <span style="color:var(--text-muted);font-size:.85rem">${p.total_items} ítems</span>
        <span style="font-weight:700;color:var(--gold)">${fmt(p.total)}</span>
      </div>
      <div class="order-actions">
        ${siguienteEstado[p.estado]?`<button class="btn btn-green" onclick="cambiarEstado(${p.id},'${siguienteEstado[p.estado]}')">→ ${estadoLabels[siguienteEstado[p.estado]]}</button>`:''}
        ${p.estado!=='cancelado'&&p.estado!=='entregado'?`<button class="btn btn-red" onclick="cambiarEstado(${p.id},'cancelado')">Cancelar</button>`:''}
      </div>
    </div>`).join('');
}

async function cambiarEstado(id,estado){
  try{
    const r=await fetch(`${BASE}/pedidos.php?accion=actualizar_estado`,{method:'PUT',headers:{'Content-Type':'application/json'},credentials:'include',body:JSON.stringify({pedido_id:id,estado})});
    const res=await r.json();
    if(!res.ok){toast(res.mensaje,'error');return;}
    toast('Estado actualizado ✓','success');
    cargarPedidos();
  }catch{toast('Error','error');}
}

// ── ASISTENCIA ───────────────────────────────────────────────
async function cargarAsistencia(){
  const fecha=document.getElementById('fecha-asistencia').value;
  const r=await fetch(`${BASE}/empleados.php?accion=asistencia&fecha=${fecha}`);
  const res=await r.json();
  const tbody=document.getElementById('asistencia-body');
  if(!res.data||!res.data.length){tbody.innerHTML='<tr><td colspan="8" style="text-align:center;color:var(--text-muted)">Sin registros</td></tr>';return;}
  tbody.innerHTML=res.data.map(e=>`
    <tr>
      <td style="font-weight:500">${e.nombre} ${e.apellido}</td>
      <td>${e.puesto}</td>
      <td style="color:var(--green)">${e.hora_entrada?new Date(e.hora_entrada).toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit'}):'—'}</td>
      <td style="color:var(--red)">${e.hora_salida?new Date(e.hora_salida).toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit'}):'—'}</td>
      <td>${e.horas_trabajadas||'—'}</td>
      <td style="color:${e.horas_extra>0?'var(--gold)':'var(--text-muted)'}">${e.horas_extra||0}</td>
      <td style="font-weight:700;color:var(--gold)">${e.pago_total?fmt(e.pago_total):'—'}</td>
      <td><span class="badge ${e.hora_salida?'badge-entregado':'badge-en_preparacion'}">${e.hora_salida?'Completo':'En turno'}</span></td>
    </tr>`).join('');
}

async function cargarReporteSemanal(){
  const r=await fetch(`${BASE}/empleados.php?accion=reporte_semana`);
  const res=await r.json();
  if(!res.data)return;
  document.getElementById('semanal-body').innerHTML=res.data.map(e=>`
    <tr>
      <td style="font-weight:500">${e.nombre} ${e.apellido}</td>
      <td>${e.puesto}</td>
      <td>${e.dias_trabajados||0}</td>
      <td>${parseFloat(e.total_horas||0).toFixed(1)} h</td>
      <td style="color:var(--gold)">${parseFloat(e.total_extra||0).toFixed(1)} h</td>
      <td style="font-weight:700;color:var(--gold)">${fmt(e.total_pago||0)}</td>
    </tr>`).join('');
}

async function crearEmpleado(){
  const data={nombre:document.getElementById('emp-nombre').value,apellido:document.getElementById('emp-apellido').value,puesto:document.getElementById('emp-puesto').value,pin:document.getElementById('emp-pin').value,sueldo_hora:document.getElementById('emp-sueldo').value,hora_extra_mult:document.getElementById('emp-mult').value};
  const r=await fetch(`${BASE}/empleados.php?accion=crear`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
  const res=await r.json();
  if(!res.ok){toast(res.mensaje,'error');return;}
  toast('Empleado creado ✓ Token QR: '+res.data.token_qr,'success');
  hideModal('modal-empleado');cargarAsistencia();
}

// ── MENÚ ADMIN ───────────────────────────────────────────────
async function cargarMenuAdmin(){
  const r=await fetch(`${BASE}/menu.php?accion=lista`);
  const res=await r.json();
  if(!res.data)return;
  const cats={comida:'️',bebida:'',postre:'',extra:''};
  document.getElementById('menu-admin-body').innerHTML=res.data.map(m=>`
    <tr>
      <td style="font-size:1.5rem">${m.emoji}</td>
      <td style="font-weight:500">${m.nombre}<div style="font-size:.8rem;color:var(--text-muted)">${m.descripcion||''}</div></td>
      <td>${cats[m.categoria]||''} ${m.categoria}</td>
      <td style="font-weight:700;color:var(--gold)">${fmt(m.precio)}</td>
      <td><span class="badge ${m.disponible?'badge-listo':'badge-cancelado'}">${m.disponible?'Activo':'Inactivo'}</span></td>
      <td>
        <div style="display:flex;gap:.4rem">
          <button class="btn btn-ghost" style="padding:.3rem .75rem;font-size:.8rem" onclick="toggleDisponible(${m.id},${m.disponible})">${m.disponible?'Desactivar':'Activar'}</button>
          <button class="btn btn-blue" style="padding:.3rem .75rem;font-size:.8rem" onclick='abrirEditarPlatillo(${JSON.stringify(m).replace(/'/g,"&#39;")})'>✏️ Editar</button>
        </div>
      </td>
    </tr>`).join('');
}

async function crearPlatillo(){
  const nombre = document.getElementById('mi-nombre').value.trim();
  if (!nombre) { toast('⚠️ Escribe el nombre del platillo', 'error'); return; }

  const archivo = document.getElementById('mi-imagen').files[0];
  let imagenUrl = null;

  // Subir imagen — si falla, continúa sin imagen (no bloquea)
  if (archivo) {
    try {
      const fd = new FormData();
      fd.append('imagen', archivo);
      const ru = await fetch(`${BASE}/menu.php?accion=subir_imagen`, { method:'POST', body: fd, credentials:'include' });
      
      // Verificar que la respuesta sea JSON válido
      const texto = await ru.text();
      
      try {
        const ju = JSON.parse(texto);
        if (ju.ok) imagenUrl = ju.data.url;
        else toast('⚠️ Imagen no guardada: ' + (ju.mensaje || 'error'), 'info');
      } catch {
        console.warn('subir_imagen no devolvió JSON válido:', texto);
        toast('⚠️ Error al subir imagen — platillo se guardará sin foto', 'info');
      }
    } catch (e) {
      console.error('Error subiendo imagen:', e);
      toast('⚠️ No se pudo subir la imagen — guardando sin foto', 'info');
    }
  }

  // Guardar platillo (con o sin imagen)
  try {
    const data = {
      nombre,
      descripcion: document.getElementById('mi-desc').value,
      precio:      document.getElementById('mi-precio').value,
      emoji:       document.getElementById('mi-emoji').value,
      categoria:   document.getElementById('mi-cat').value,
      imagen:      imagenUrl
    };

    const r = await fetch(`${BASE}/menu.php?accion=crear`, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      credentials:'include',
      body: JSON.stringify(data)
    });

    const texto = await r.text();


    const res = JSON.parse(texto);
    if (!res.ok){ toast(res.mensaje || 'Error al guardar', 'error'); return; }

    toast('Platillo creado ✓', 'success');
    hideModal('modal-menu');
    document.getElementById('mi-imagen').value = '';
    document.getElementById('mi-imagen-preview').style.display = 'none';
    cargarMenuAdmin();

  } catch(e) {
    console.error('Error creando platillo:', e);
    toast('❌ Error al conectar con el servidor', 'error');
  }
}

function abrirEditarPlatillo(m) {
  document.getElementById('edit-plat-id').value     = m.id;
  document.getElementById('edit-plat-nombre').value = m.nombre;
  document.getElementById('edit-plat-desc').value   = m.descripcion || '';
  document.getElementById('edit-plat-precio').value = m.precio;
  document.getElementById('edit-plat-emoji').value  = m.emoji || '️';
  document.getElementById('edit-plat-cat').value    = m.categoria || 'comida';
  showModal('modal-editar-platillo');
}

async function guardarEdicionPlatillo() {
  const id = document.getElementById('edit-plat-id').value;
  const data = {
    id,
    nombre:      document.getElementById('edit-plat-nombre').value.trim(),
    descripcion: document.getElementById('edit-plat-desc').value.trim(),
    precio:      document.getElementById('edit-plat-precio').value,
    emoji:       document.getElementById('edit-plat-emoji').value.trim(),
    categoria:   document.getElementById('edit-plat-cat').value,
  };
  if (!data.nombre) { toast('⚠️ El nombre es obligatorio', 'error'); return; }
  const r   = await fetch(`${BASE}/menu.php?accion=editar`, {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    credentials: 'include', body: JSON.stringify(data)
  });
  const res = await r.json();
  if (!res.ok) { toast('❌ ' + res.mensaje, 'error'); return; }
  toast('✅ Platillo actualizado', 'success');
  hideModal('modal-editar-platillo');
  cargarMenuAdmin();
}

async function toggleDisponible(id,actual){
  await fetch(`${BASE}/menu.php?accion=toggle`,{method:'POST',headers:{'Content-Type':'application/json'},credentials:'include',body:JSON.stringify({id,disponible:!actual})});
  cargarMenuAdmin();
}

// ── QR GENERATOR ─────────────────────────────────────────────
const APP_URL=window.location.origin+window.location.pathname.replace(/\/[^\/]*$/, '');
function generarQR(containerId,url){
  const c=document.getElementById(containerId);c.innerHTML='';
  new QRCode(c,{text:url,width:180,height:180,colorDark:'#000',colorLight:'#fff'});
}
function generarQRMesa(){
  const n=document.getElementById('qr-mesa-num').value||'1';
  generarQR('qr-mesa-container',`${APP_URL}/order.html?tipo=mesa&mesa=${n}`);
}
function generarQRLlevar(){generarQR('qr-llevar-container',`${APP_URL}/order.html?tipo=para_llevar`);}
function generarQREmp(){generarQR('qr-emp-container',`${APP_URL}/checador.html`);}
function imprimirQR(tipo){window.print();}

// ── CORTE ────────────────────────────────────────────────────
async function cargarCorte(){
  const fecha=document.getElementById('fecha-corte').value;
  const r=await fetch(`${BASE}/pedidos.php?accion=corte_caja&fecha=${fecha}`);
  const res=await r.json();
  if(!res.ok||!res.data){toast(res.mensaje,'error');return;}
  const {resumen,pedidos,top}=res.data;
  document.getElementById('corte-content').innerHTML=`
    <div class="stat-grid" style="margin-bottom:2rem">
      <div class="stat-card"><div class="stat-value">${resumen.total_pedidos||0}</div><div class="stat-label">Total pedidos</div></div>
      <div class="stat-card"><div class="stat-value" style="font-size:1.4rem">${fmt(resumen.total_mesa||0)}</div><div class="stat-label"> Ventas en mesa</div></div>
      <div class="stat-card"><div class="stat-value" style="font-size:1.4rem">${fmt(resumen.total_llevar||0)}</div><div class="stat-label"> Para llevar</div></div>
      <div class="stat-card" style="border-color:var(--gold)"><div class="stat-value" style="font-size:1.8rem">${fmt(resumen.gran_total||0)}</div><div class="stat-label"> GRAN TOTAL</div></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
      <div class="card">
        <h3 style="margin-bottom:1rem"> Detalle de pedidos — ${new Date(fecha+'T12:00:00').toLocaleDateString('es-MX',{day:'numeric',month:'long',year:'numeric'})}</h3>
        ${pedidos.map(p=>`
          <div class="summary-row">
            <div><span style="color:var(--gold);font-weight:700">#${p.numero_orden}</span> <span style="font-size:.85rem;color:var(--text-muted)">${p.cliente} · ${p.tipo==='mesa'?`Mesa ${p.mesa_numero}`:'Para llevar'}</span><br><span style="font-size:.8rem;color:var(--text-muted)">${p.items}</span></div>
            <div style="font-weight:700;color:var(--gold);white-space:nowrap">${fmt(p.total)}</div>
          </div>`).join('')}
        <div style="display:flex;justify-content:flex-end;margin-top:1rem;font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--gold)">Total: ${fmt(resumen.gran_total||0)}</div>
      </div>
      <div class="card">
        <h3 style="margin-bottom:1rem"> Top platillos del día</h3>
        ${top.map((t,i)=>`
          <div class="summary-row">
            <div>${['🥇','🥈','🥉','4️⃣','5️⃣'][i]} ${t.emoji} ${t.nombre}</div>
            <div style="text-align:right"><span style="color:var(--gold);font-weight:700">${t.vendidos} uds</span><br><span style="font-size:.8rem;color:var(--text-muted)">${fmt(t.ingresos)}</span></div>
          </div>`).join('')}
      </div>
    </div>`;
}

function imprimirCorte(){
  const corteHTML=document.getElementById('corte-content').innerHTML;
  const win=window.open('','_blank');
  win.document.write(`<!DOCTYPE html><html><head><title>Corte — <?= addslashes(htmlspecialchars($admin['restaurante_nombre'] ?? 'Mi Restaurante')) ?></title>
  <style>body{font-family:Arial,sans-serif;padding:2rem;color:#000}h3{margin-bottom:1rem}.summary-row{display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid #ddd}.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem}.stat-card{border:1px solid #ddd;padding:1rem;border-radius:8px}.stat-value{font-size:1.5rem;font-weight:bold}.stat-label{font-size:.8rem;color:#666}.card{border:1px solid #ddd;padding:1.5rem;border-radius:8px;margin-bottom:1rem}</style>
  </head><body>${corteHTML}</body></html>`);
  win.document.close();win.print();
}

// ── PAGOS ─────────────────────────────────────────────────────
async function cargarPagos() {
  const r   = await fetch(`${BASE}/pedidos.php?accion=solicitudes_pago`, { credentials: 'include' });
  const res = await r.json();
  if (!res.ok) return;
  const grid = document.getElementById('pagos-grid');
  if (!res.data || !res.data.length) {
    grid.innerHTML = '<div style="color:var(--text-muted);text-align:center;padding:3rem;grid-column:1/-1">Sin solicitudes de pago hoy</div>';
    return;
  }
  grid.innerHTML = res.data.map(p => {
    let items = [];
    try { items = JSON.parse(p.items_json || '[]'); } catch {}
// Mostrar resumen del items_json guardado
    return `
    <div class="order-card ${!p.pagado ? 'nuevo' : ''}" style="padding:1.5rem">

      <!-- ENCABEZADO -->
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem">
        <div>
          <span style="font-family:'Playfair Display',serif;font-size:1.3rem;color:var(--gold)">
            ${p.tipo === 'mesa' ? ` Mesa ${p.mesa_numero}` : '🥡 Para llevar'}
          </span>
          <div style="font-size:.85rem;color:var(--text-muted);margin-top:.2rem">${p.nombre_cliente}</div>
        </div>
        <span class="badge ${p.pagado ? 'badge-entregado' : 'badge-pendiente'}">
          ${p.pagado ? '✅ Pagado' : '⏳ Pendiente'}
        </span>
      </div>

      <!-- LO QUE COMIÓ -->
      <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:.4rem">
        🍽️ Consumo del cliente
      </div>
      <div style="background:var(--bg3);border-radius:8px;padding:.75rem;margin-bottom:.75rem;font-size:.85rem;line-height:1.9">
        ${items.map(i => `
          <div style="display:flex;justify-content:space-between;gap:.5rem">
            <span style="color:var(--text-muted)">#${i.numero_orden} — ${i.items_texto || ''}</span>
            <span style="color:var(--gold);font-weight:700;white-space:nowrap">${fmt(i.total)}</span>
          </div>`).join('')}
        <div style="display:flex;justify-content:space-between;border-top:1px solid var(--border);margin-top:.5rem;padding-top:.5rem">
          <span style="color:var(--text-muted)">Subtotal consumo</span>
          <span style="font-family:'Playfair Display',serif;font-size:1.2rem;color:var(--gold)">${fmt(p.total)}</span>
        </div>
      </div>

      <!-- PROPINA — solo si dejó -->
      ${p.propina > 0 ? `
      <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:.4rem">
         Propina al mesero
      </div>
      <div style="background:rgba(76,175,122,.07);border:1px solid rgba(76,175,122,.25);
                  border-radius:8px;padding:.85rem 1rem;margin-bottom:.75rem">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div>
            <div style="font-weight:600;color:var(--green);font-size:.95rem">
               ${p.mesero_nombre || 'Mesero'}
            </div>
            <div style="font-size:.8rem;color:var(--text-muted);margin-top:.15rem">
              El cliente dejó propina
            </div>
          </div>
          <span style="font-family:'Playfair Display',serif;font-size:1.6rem;color:var(--green);font-weight:700">
            +${fmt(p.propina)}
          </span>
        </div>
      </div>` : `
      <div style="background:var(--bg3);border-radius:8px;padding:.6rem 1rem;margin-bottom:.75rem;
                  font-size:.82rem;color:var(--text-muted);text-align:center">
        Sin propina esta vez
      </div>`}

      <!-- TOTALES SEPARADOS -->
      <div style="border-top:1px solid var(--border);padding-top:.75rem;margin-bottom:1rem">
        <div style="display:flex;justify-content:space-between;margin-bottom:.3rem">
          <span style="color:var(--text-muted);font-size:.88rem">Consumo</span>
          <span style="color:var(--text);font-weight:600">${fmt(p.total)}</span>
        </div>
        ${p.propina > 0 ? `
        <div style="display:flex;justify-content:space-between;margin-bottom:.3rem">
          <span style="color:var(--text-muted);font-size:.88rem">Propina ${p.mesero_nombre ? '→ ' + p.mesero_nombre : ''}</span>
          <span style="color:var(--green);font-weight:600">+${fmt(p.propina)}</span>
        </div>` : ''}
        <div style="display:flex;justify-content:space-between;border-top:1px solid var(--border);padding-top:.5rem;margin-top:.3rem">
          <span style="font-size:.9rem;color:var(--text-muted)">Total a cobrar</span>
          <span style="font-family:'Playfair Display',serif;font-size:1.8rem;color:var(--gold)">
            ${fmt(parseFloat(p.total) + parseFloat(p.propina || 0))}
          </span>
        </div>
      </div>

      <!-- BOTONES -->
      <div style="display:flex;gap:.5rem">
        <button class="btn btn-ghost" style="flex:1" onclick='imprimirRecibo(${JSON.stringify(p)})'>
          🖨️ Imprimir
        </button>
        ${!p.pagado ? `<button class="btn btn-green" style="flex:1" onclick="marcarPagado(${p.id})">✅ Marcar pagado</button>` : ''}
      </div>
    </div>`;
  }).join('');
}

async function marcarPagado(id) {
  const r = await fetch(`${BASE}/pedidos.php?accion=marcar_pagado`, {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    credentials: 'include', body: JSON.stringify({ id })
  });
  const res = await r.json();
  if (res.ok) { toast('✅ Pago registrado', 'success'); cargarPagos(); cargarPedidos(); }
  else toast('❌ Error', 'error');
}

function imprimirRecibo(p) {
  const items = typeof p.items_json === 'string' ? JSON.parse(p.items_json) : (p.items_json || []);
  const fecha = new Date(p.creado_en).toLocaleString('es-MX');
  const mesa  = p.tipo === 'mesa' ? `Mesa ${p.mesa_numero}` : 'Para llevar';
  const win   = window.open('', '_blank');
  win.document.write(`<!DOCTYPE html><html><head>
    <meta charset="UTF-8"><title>Recibo</title>
    <style>
      body { font-family: Arial, sans-serif; padding: 2rem; max-width: 380px; margin: 0 auto; }
      h2   { text-align: center; margin-bottom: .2rem; }
      .sub { text-align: center; color: #666; font-size: .85rem; margin-bottom: 1.5rem; border-bottom: 2px dashed #ccc; padding-bottom: 1rem; }
      .row { display: flex; justify-content: space-between; padding: .45rem 0; border-bottom: 1px dashed #eee; font-size: .9rem; }
      .total { font-size: 1.4rem; font-weight: bold; display: flex; justify-content: space-between; margin-top: 1rem; padding-top: .75rem; border-top: 2px solid #000; }
      .footer { text-align: center; color: #999; font-size: .8rem; margin-top: 1.5rem; }
    </style>
  </head><body>
    <h2>🧾 Recibo de Pago</h2>
    <div class="sub">${mesa} · ${p.nombre_cliente}<br><small>${fecha}</small></div>
    ${items.map(i => `<div class="row"><span>#${i.numero_orden} ${i.items_texto||''}</span><span>$${parseFloat(i.total).toFixed(2)}</span></div>`).join('')}
    <div class="total"><span>TOTAL</span><span>$${parseFloat(p.total).toFixed(2)}</span></div>
    <div class="footer">¡Gracias por su visita! 🙏</div>
    <script>window.onload=()=>window.print()<\/script>
  </body></html>`);
  win.document.close();
}

setInterval(() => {
  if (document.getElementById('section-pagos')?.classList.contains('active')) cargarPagos();
}, 15000);

// ── Auto refresh ─────────────────────────────────────────────
cargarPedidos();
setInterval(cargarPedidos,15000);

// ── Alertas de canje de premio ────────────────────────────
async function pollCanjes() {
  try {
    const r   = await fetch('/pedidos.php?accion=notif_admin_canjes', { credentials: 'include' });
    const res = await r.json();
    if (!res.ok || !res.data) return;
    res.data.forEach(n => {
      if (!_canjesVistos.has(n.id)) {
        _canjesVistos.add(n.id);
        // Toast visible con sonido
        toast('🎁 ' + n.mensaje, 'info');
        // Banner destacado en pantalla
        const banner = document.createElement('div');
        banner.innerHTML = `
          <div style="position:fixed;top:1.5rem;left:50%;transform:translateX(-50%);
               background:#1a1a1a;border:2px solid var(--gold);border-radius:16px;
               padding:1.25rem 2rem;z-index:9999;text-align:center;
               box-shadow:0 8px 40px rgba(232,192,125,.3);animation:slideIn .3s ease;
               min-width:320px">
            <div style="font-size:1.8rem;margin-bottom:.3rem">🎁</div>
            <div style="color:var(--gold);font-weight:700;font-size:1rem">${n.mensaje}</div>
            <div style="color:var(--text-muted);font-size:.8rem;margin-top:.3rem">Muéstrale el premio al cliente</div>
          </div>`;
        document.body.appendChild(banner);
        setTimeout(() => banner.remove(), 6000);
      }
    });
  } catch(e) {}
}
pollCanjes();
setInterval(pollCanjes, 10000);

document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('mi-imagen').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      document.getElementById('mi-imagen-img').src = e.target.result;
      document.getElementById('mi-imagen-preview').style.display = '';
    };
    reader.readAsDataURL(file);
  });
});

// ── PERSONALIZAR RESTAURANTE ─────────────────────────────────
const logoFileEl = document.getElementById('logo-img-file');
if (logoFileEl) {
  logoFileEl.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      const preview = document.getElementById('logo-img-preview');
      if (preview) preview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover">`;
      window._logoImgData = e.target.result;
    };
    reader.readAsDataURL(file);
  });
}

document.getElementById('hero-img-file').addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const box = document.getElementById('hero-preview-box');
    box.style.backgroundImage = `url(${e.target.result})`;
    box.textContent = '';
    window._heroBgData = e.target.result;
  };
  reader.readAsDataURL(file);
});

function guardarPersonalizacion() {
  if (window._logoImgData) {
    localStorage.setItem(KEY_LOGO, window._logoImgData);
    aplicarLogoImg(window._logoImgData);
  }
  if (window._heroBgData) {
    localStorage.setItem(KEY_HERO, window._heroBgData);
    localStorage.setItem('ros_hero_bg', window._heroBgData);
  }
  const slogan = document.getElementById('edit-slogan').value;
  if (slogan) localStorage.setItem(KEY_SLOGAN, slogan);

  hideModal('modal-editar-restaurante');
  toast('✅ Cambios guardados', 'success');
}

function aplicarLogoImg(src) {
  const el = document.getElementById('logo-restaurante');
  if (!el) return;
  el.innerHTML = `<img src="${src}" style="width:36px;height:36px;border-radius:8px;object-fit:cover;vertical-align:middle">`;
}

// Clave única por restaurante
const RID = '<?= $admin["restaurante_id"] ?>';
const KEY_LOGO = 'ros_logo_' + RID;
const KEY_HERO = 'ros_hero_' + RID;
const KEY_SLOGAN = 'ros_slogan_' + RID;

// Aplicar al cargar la página
(function() {
  const logoImg = localStorage.getItem(KEY_LOGO);
  if (logoImg) aplicarLogoImg(logoImg);

  const heroBg = localStorage.getItem(KEY_HERO);
  if (heroBg) {
    const box = document.getElementById('hero-preview-box');
    if (box) { box.style.backgroundImage = `url(${heroBg})`; box.textContent = ''; }
  }
  const slogan = localStorage.getItem(KEY_SLOGAN);
  if (slogan) {
    const el = document.getElementById('edit-slogan');
    if (el) el.value = slogan;
  }
})();

// ── PLANES ────────────────────────────────────────────────────
let _periodoAnual = false;
let _planSel = null;

const _precios = {
  basico:  { mensual: 135, anual: 101 },
  plus:    { mensual: 299, anual: 224 },
  premium: { mensual: 599, anual: 449 }
};

function abrirModalPlanes() {
  document.getElementById('modal-planes').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function cerrarModalPlanes() {
  document.getElementById('modal-planes').style.display = 'none';
  document.body.style.overflow = '';
  document.getElementById('panel-pago').style.display = 'none';
  _planSel = null;
}
window.abrirModalPlanes  = abrirModalPlanes;
window.cerrarModalPlanes = cerrarModalPlanes;

// Cerrar con Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModalPlanes(); });
// Cerrar al click fuera
document.getElementById('modal-planes').addEventListener('click', function(e) {
  if (e.target === this) cerrarModalPlanes();
});

function togglePeriodo() {
  _periodoAnual = !_periodoAnual;
  const dot  = document.getElementById('toggle-dot');
  const lblM = document.getElementById('lbl-mensual');
  const lblA = document.getElementById('lbl-anual');
  const toggle = document.getElementById('toggle-periodo');

  if (_periodoAnual) {
    dot.style.left = '25px';
    dot.style.background = 'var(--gold)';
    toggle.style.background = 'rgba(232,192,125,.15)';
    lblM.style.color = '#555'; lblM.style.fontWeight = '400';
    lblA.style.color = 'var(--gold)'; lblA.style.fontWeight = '600';
  } else {
    dot.style.left = '3px';
    dot.style.background = '#555';
    toggle.style.background = '#222';
    lblM.style.color = 'var(--gold)'; lblM.style.fontWeight = '600';
    lblA.style.color = '#555'; lblA.style.fontWeight = '400';
  }

  // Actualizar precios en cards
  document.querySelectorAll('.precio-plan').forEach(el => {
    const p = _periodoAnual ? el.dataset.anual : el.dataset.mensual;
    el.textContent = '$' + parseInt(p).toLocaleString('es-MX');
  });
  document.querySelectorAll('.precio-anual-note').forEach(el => {
    el.style.display = _periodoAnual ? 'block' : 'none';
  });

  // Actualizar panel de pago si ya hay plan seleccionado
  if (_planSel) seleccionarPlan(_planSel);
}
window.togglePeriodo = togglePeriodo;

const _WA_SOPORTE = '522381172308'; // ← tu número de WhatsApp

function seleccionarPlan(plan) {
  _planSel = plan;
  const nombres = { basico: 'Básico', plus: 'Plus', premium: 'Premium' };
  const emojis  = { basico: '', plus: '', premium: '' };
  const precio  = _periodoAnual ? _precios[plan].anual * 12 : _precios[plan].mensual;
  const periodo = _periodoAnual ? 'año' : 'mes';

  // Llenar resumen
  document.getElementById('confirm-resumen').innerHTML = `
    <div style="font-size:2.2rem;margin-bottom:.5rem">${emojis[plan]}</div>
    <div style="font-size:.72rem;color:#555;text-transform:uppercase;letter-spacing:.1em;margin-bottom:.3rem">Plan elegido</div>
    <div style="font-family:'Playfair Display',serif;font-size:1.6rem;color:var(--gold);margin-bottom:.5rem">${nombres[plan]}</div>
    <div style="font-size:1.1rem;color:#f0ece4;font-weight:600">$${precio.toLocaleString('es-MX')} MXN / ${periodo}</div>
    ${_periodoAnual ? `<div style="font-size:.78rem;color:var(--green);margin-top:.3rem">✓ Precio anual con 25% de descuento</div>` : ''}
  `;

  // Mensaje WhatsApp
  const restaurante = '<?= addslashes(htmlspecialchars($admin["restaurante_nombre"] ?? "")) ?>';
  const contacto    = '<?= addslashes(htmlspecialchars($admin["nombre"] ?? "")) ?>';

  const msg = encodeURIComponent(
    `Hola! Deseo el plan *${nombres[plan]}* de MexxicanMx.\n\n` +
    `*Restaurante:* ${restaurante}\n` +
    `*Contacto:* ${contacto}\n` +
    `*Direccion:* ESCRIBE_TU_DIRECCION_AQUI\n` +
    `*Plan:* ${nombres[plan]}\n` +
    `*Monto:* $${precio.toLocaleString('es-MX')} MXN / ${periodo}\n\n` +
    `Puedo pagar con cualquier tarjeta. Te aviso cuando transfiera y te mando el recibo.\n\n` +
    `Nota: Recibire la activacion de mi cuenta antes de 48 horas. Gracias!`

  );

  document.getElementById('btn-confirmar-wa').href = `https://wa.me/${_WA_SOPORTE}?text=${msg}`;
  document.getElementById('modal-confirmar-plan').style.display = 'flex';
}
window.seleccionarPlan = seleccionarPlan;

function cancelarConfirmacion() {
  document.getElementById('modal-confirmar-plan').style.display = 'none';
}
window.cancelarConfirmacion = cancelarConfirmacion;

function confirmarYEnviar(e) {
  setTimeout(() => {
    document.getElementById('modal-confirmar-plan').style.display = 'none';
    cerrarModalPlanes();
    toast('✅ ¡Perfecto! Te respondemos por WhatsApp a la brevedad.', 'success');
  }, 500);
}
window.confirmarYEnviar = confirmarYEnviar;

document.getElementById('modal-confirmar-plan').addEventListener('click', function(e) {
  if (e.target === this) cancelarConfirmacion();
});

// ── PLAN DEL RESTAURANTE ──────────────────────────────────────
async function cargarMiPlan() {
  try {
    const r   = await fetch('/planes.php?accion=mi_plan', { credentials: 'include' });
    const res = await r.json();
    const banner = document.getElementById('banner-plan');
    if (!banner) return;

    if (!res.ok || !res.data) {
      // Sin plan activo — mostrar aviso de contratar
      banner.style.display = 'block';
      banner.innerHTML = `
        <div style="background:rgba(224,92,92,.08);border:1.5px solid rgba(224,92,92,.35);
             border-radius:14px;padding:1.1rem 1.5rem;display:flex;align-items:center;
             justify-content:space-between;flex-wrap:wrap;gap:.75rem">
          <div style="display:flex;align-items:center;gap:.75rem">
            <span style="font-size:1.5rem">⚠️</span>
            <div>
              <div style="font-weight:600;color:var(--red)">Sin plan activo</div>
              <div style="font-size:.82rem;color:var(--text-muted);margin-top:.1rem">
                Tu restaurante no tiene un plan. Contrata uno para navegar mas facilmente en MexxicanMx.
              </div>
            </div>
          </div>
          <button onclick="abrirModalPlanes()"
            style="padding:.6rem 1.4rem;background:linear-gradient(135deg,var(--gold),var(--gold-dark));
                   color:#111;border:none;border-radius:10px;font-weight:700;font-size:.9rem;cursor:pointer">
            Ver planes →
          </button>
        </div>`;
      aplicarVisibilidadPlan('free');
      return;
    }

    const p        = res.data;
    const dias     = parseInt(p.dias_restantes) || 0;
    aplicarVisibilidadPlan(p.plan);
    const nombres  = { basico: ' Básico', plus: ' Plus', premium: ' Premium' };
    const vence    = new Date(p.fecha_vencimiento + 'T12:00:00').toLocaleDateString('es-MX', { day:'numeric', month:'long', year:'numeric' });

    // Color según días restantes
    let color, bg, icono, msg;
    if (dias <= 5) {
      color = 'var(--red)'; bg = 'rgba(224,92,92,.08)';
      icono = '🔴'; msg = `¡Solo quedan <b>${dias} días</b>! Renueva antes de que venza.`;
    } else if (dias <= 10) {
      color = 'var(--orange)'; bg = 'rgba(224,154,92,.08)';
      icono = '🟠'; msg = `Quedan <b>${dias} días</b> de tu plan. Pronto vencerá.`;
    } else {
      color = 'var(--green)'; bg = 'rgba(76,175,122,.06)';
      icono = '✅'; msg = `Plan activo · Vence el <b>${vence}</b> (<b>${dias} días</b> restantes)`;
    }

    banner.style.display = 'block';
    banner.innerHTML = `
      <div style="background:${bg};border:1.5px solid ${color}44;
           border-radius:14px;padding:1.1rem 1.5rem;display:flex;align-items:center;
           justify-content:space-between;flex-wrap:wrap;gap:.75rem">
        <div style="display:flex;align-items:center;gap:.75rem">
          <span style="font-size:1.4rem">${icono}</span>
          <div>
            <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap">
              <span style="font-weight:700;color:${color}">${nombres[p.plan] || p.plan}</span>
              <span style="font-size:.75rem;background:${color}22;border:1px solid ${color}55;
                    color:${color};border-radius:99px;padding:.15rem .6rem">Activo</span>
            </div>
            <div style="font-size:.82rem;color:var(--text-muted);margin-top:.15rem">${msg}</div>
          </div>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
          ${dias <= 10 ? `
          <button onclick="renovarPlan('${p.plan}')"
            style="padding:.6rem 1.2rem;background:linear-gradient(135deg,var(--gold),var(--gold-dark));
                   color:#111;border:none;border-radius:10px;font-weight:700;font-size:.85rem;cursor:pointer">
            🔄 Renovar plan
          </button>` : `
          <button onclick="abrirModalPlanes()"
            style="padding:.6rem 1.2rem;background:transparent;
                   border:1px solid var(--border);color:var(--text-muted);
                   border-radius:10px;font-size:.85rem;cursor:pointer">
            Ver planes
          </button>`}
        </div>
      </div>`;
  } catch(e) { console.error('cargarMiPlan:', e); }
}

function aplicarVisibilidadPlan(plan) {
  const navPromo    = document.getElementById('nav-promociones');
  const navPremium  = document.getElementById('nav-premium');

  // Promociones: solo Plus y Premium
  if (navPromo) {
    navPromo.style.display = (plan === 'plus' || plan === 'premium') ? '' : 'none';
  }
  // Premium/Plus: solo Plus y Premium
  if (navPremium) {
    navPremium.style.display = (plan === 'plus' || plan === 'premium') ? '' : 'none';
  }
}

function renovarPlan(planActual) {
  // Abrir modal de planes y preseleccionar el plan actual
  abrirModalPlanes();
  setTimeout(() => {
    _planSel = planActual;
    seleccionarPlan(planActual);
  }, 300);
}

// ── LISTA DE EMPLEADOS CON SALARIO ───────────────────────────
async function cargarListaEmpleados() {
  const r   = await fetch(`${BASE}/empleados.php?accion=lista`, { credentials: 'include' });
  const res = await r.json();
  const tbody = document.getElementById('empleados-body');
  if (!tbody) return;
  if (!res.ok || !res.data || !res.data.length) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--text-muted)">Sin empleados registrados</td></tr>';
    return;
  }
  tbody.innerHTML = res.data.map(e => `
    <tr>
      <td style="font-weight:500">${e.nombre} ${e.apellido}</td>
      <td>${e.puesto}</td>
      <td style="color:var(--gold);font-weight:700" id="sueldo-${e.id}">$${parseFloat(e.sueldo_hora).toFixed(2)}/hr</td>
      <td style="color:var(--text-muted)" id="mult-${e.id}">×${parseFloat(e.hora_extra_mult).toFixed(1)}</td>
      <td>
        <button class="btn btn-ghost" style="padding:.3rem .75rem;font-size:.8rem"
          onclick="abrirEditarSalario(${e.id},'${e.nombre} ${e.apellido}',${e.sueldo_hora},${e.hora_extra_mult})">
          ✏️ Editar salario
        </button>
      </td>
    </tr>
  `).join('');
}

function abrirEditarSalario(id, nombre, sueldo, mult) {
  document.getElementById('edit-sal-id').value      = id;
  document.getElementById('edit-sal-nombre').textContent = nombre;
  document.getElementById('edit-sal-sueldo').value  = sueldo;
  document.getElementById('edit-sal-mult').value    = mult;
  showModal('modal-editar-salario');
}

async function guardarSalario() {
  const id     = document.getElementById('edit-sal-id').value;
  const sueldo = parseFloat(document.getElementById('edit-sal-sueldo').value);
  const mult   = parseFloat(document.getElementById('edit-sal-mult').value);

  if (!sueldo || sueldo <= 0) { toast('⚠️ Sueldo inválido', 'error'); return; }
  if (!mult   || mult < 1)    { toast('⚠️ Multiplicador inválido (mínimo 1)', 'error'); return; }

  const r   = await fetch(`${BASE}/empleados.php?accion=actualizar_salario`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify({ empleado_id: id, sueldo_hora: sueldo, hora_extra_mult: mult })
  });
  const res = await r.json();
  if (res.ok) {
    toast(`✅ Salario actualizado`, 'success');
    hideModal('modal-editar-salario');
    cargarListaEmpleados();
  } else {
    toast('❌ ' + res.mensaje, 'error');
  }
}

// ── SOLICITUDES PIN OLVIDADO ──────────────────────────────────
async function cargarSolicitudesPin() {
  try {
    const r   = await fetch(`${BASE}/empleados.php?accion=solicitudes_pin`, { credentials: 'include' });
    const res = await r.json();
    const alertaDiv = document.getElementById('alerta-pin');
    if (!alertaDiv) return;

    if (!res.ok || !res.data || !res.data.length) {
      alertaDiv.style.display = 'none';
      return;
    }

    alertaDiv.style.display = 'block';
    alertaDiv.innerHTML = `
      <div style="font-weight:700;color:var(--red);margin-bottom:.75rem;font-size:.95rem">
        🔑 ${res.data.length} empleado(s) olvidaron su PIN
      </div>
      ${res.data.map(s => `
        <div style="display:flex;justify-content:space-between;align-items:center;
                    background:rgba(255,255,255,.03);border:1px solid var(--border);
                    border-radius:10px;padding:.65rem .9rem;margin-bottom:.5rem;">
          <div>
            <div style="font-weight:600;font-size:.9rem;">${s.nombre_empleado}</div>
            <div style="font-size:.75rem;color:var(--text-muted);">
              Entrada registrada: ${new Date(s.hora_entrada_registrada).toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit'})} 
              · Fecha: ${s.fecha}
            </div>
          </div>
          <button onclick="resetearPin(${s.empleado_id}, '${s.nombre_empleado.replace(/'/g,"\\'")}', ${s.id})"
                  class="btn btn-gold" style="font-size:.8rem;padding:.45rem .9rem;white-space:nowrap">
            🔑 Resetear PIN
          </button>
        </div>
      `).join('')}
    `;
  } catch(e) { console.error('cargarSolicitudesPin:', e); }
}

async function resetearPin(empId, nombre, solicitudId) {
  const nuevoPIN = prompt(`Nuevo PIN para ${nombre} (4-6 dígitos):`);
  if (!nuevoPIN || nuevoPIN.length < 4 || nuevoPIN.length > 6 || isNaN(nuevoPIN)) {
    alert('PIN inválido. Debe ser entre 4 y 6 dígitos numéricos.');
    return;
  }
  const r   = await fetch(`${BASE}/empleados.php?accion=resetear_pin`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify({ empleado_id: empId, nuevo_pin: nuevoPIN, solicitud_id: solicitudId })
  });
  const res = await r.json();
  if (res.ok) {
    toast(`✅ PIN de ${nombre} actualizado a: ${nuevoPIN}`, 'success');
    cargarSolicitudesPin();
  } else {
    toast('❌ ' + res.mensaje, 'error');
  }
}

// ── PROMOCIONES ───────────────────────────────────────────────
document.getElementById('promo-imagen').addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('promo-preview-img').src = e.target.result;
    document.getElementById('promo-preview').style.display = '';
  };
  reader.readAsDataURL(file);
});

async function subirPromocion() {
  const archivo = document.getElementById('promo-imagen').files[0];
  if (!archivo) { toast('⚠️ Selecciona una imagen', 'error'); return; }

  const fd = new FormData();
  fd.append('imagen',      archivo);
  fd.append('titulo',      document.getElementById('promo-titulo').value);
  fd.append('descripcion', document.getElementById('promo-desc').value);

  const r   = await fetch(`${BASE}/menu.php?accion=crear_promocion`, { method: 'POST', body: fd, credentials: 'include' });
  const res = await r.json();
  if (!res.ok) {
    if (res.data?.upgrade_required) {
      toast('⬆️ ' + res.mensaje, 'error');
      setTimeout(() => abrirModalPlanes(), 1500);
    } else {
      toast('❌ ' + res.mensaje, 'error');
    }
    return;
  }

  toast('✅ Promoción publicada', 'success');
  document.getElementById('promo-imagen').value = '';
  document.getElementById('promo-preview').style.display = 'none';
  document.getElementById('promo-titulo').value = '';
  document.getElementById('promo-desc').value   = '';
  cargarPromocionesAdmin();
}

async function cargarPromocionesAdmin() {
  const r   = await fetch(`${BASE}/menu.php?accion=lista_promociones`, { credentials: 'include' });
  const res = await r.json();
  const grid = document.getElementById('lista-promociones');
  if (!res.ok || !res.data || !res.data.length) {
    grid.innerHTML = '<div style="color:var(--text-muted);text-align:center;padding:2rem;grid-column:1/-1">Sin promociones activas</div>';
    return;
  }
  grid.innerHTML = res.data.map(p => `
    <div style="background:var(--bg3);border-radius:12px;overflow:hidden;border:1px solid var(--border)">
      <img src="/${p.imagen}" style="width:100%;height:180px;object-fit:cover">
      <div style="padding:.85rem">
        ${p.titulo ? `<div style="font-weight:700;margin-bottom:.25rem">${p.titulo}</div>` : ''}
        ${p.descripcion ? `<div style="font-size:.85rem;color:var(--text-muted);margin-bottom:.5rem">${p.descripcion}</div>` : ''}
        <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:.75rem">${new Date(p.creado_en).toLocaleDateString('es-MX')}</div>
        <button class="btn btn-red" style="font-size:.8rem;padding:.4rem .85rem" onclick="eliminarPromocion(${p.id})">🗑 Eliminar</button>
      </div>
    </div>
  `).join('');
}

async function eliminarPromocion(id) {
  if (!confirm('¿Eliminar esta promoción?')) return;
  const r   = await fetch(`${BASE}/menu.php?accion=eliminar_promocion`, {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    credentials: 'include', body: JSON.stringify({ id })
  });
  const res = await r.json();
  if (res.ok) { toast('🗑 Promoción eliminada', 'success'); cargarPromocionesAdmin(); }
}
// ── PREMIUM: TEMA VISUAL ─────────────────────────────────────
let _temaActual = { nombre: 'dorado', color: '#e8c07d' };

function seleccionarTema(nombre, color) {
  _temaActual = { nombre, color };
  // Resaltar tarjeta seleccionada
  document.querySelectorAll('.tema-card').forEach(c => {
    c.style.borderColor = '#2e2e2e';
    c.style.transform = 'scale(1)';
  });
  const card = document.querySelector(`.tema-card[data-tema="${nombre}"]`);
  if (card) { card.style.borderColor = color; card.style.transform = 'scale(1.08)'; }
  // Actualizar picker y hex
  const picker = document.getElementById('tema-color-picker');
  if (picker) picker.value = color;
  const hex = document.getElementById('tema-color-hex');
  if (hex) hex.textContent = color;
  const prev = document.getElementById('tema-preview-txt');
  if (prev) prev.textContent = `Vista previa: color ${color}`;
}

async function guardarTema() {
  const r   = await fetch(`${BASE}/restaurantes.php?accion=guardar_tema`, {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify({ tema_color: _temaActual.color, tema_nombre: _temaActual.nombre })
  });
  const res = await r.json();
  if (!res.ok) {
    if (res.upgrade_required) { toast('⬆️ ' + res.mensaje, 'error'); setTimeout(() => abrirModalPlanes(), 1500); }
    else toast('❌ ' + res.mensaje, 'error');
    return;
  }
  toast('✅ Tema guardado — tus clientes ya lo verán', 'success');
}

async function cargarTemaActual() {
  try {
    const RID = '<?= $admin["restaurante_id"] ?>';
    const r   = await fetch(`${BASE}/restaurantes.php?accion=tema&id=${RID}`);
    const res = await r.json();
    if (res.ok && res.tema_color) {
      seleccionarTema(res.tema_nombre || 'personalizado', res.tema_color);
    }
    // Cargar config puntos
    const rp  = await fetch(`${BASE}/restaurantes.php?accion=clientes_puntos`, { credentials: 'include' });
  } catch(e) {}
}

// ── PREMIUM: PUNTOS ───────────────────────────────────────────
async function guardarConfigPuntos() {
  const premio = document.getElementById('pts-premio').value.trim();
  const meta   = parseInt(document.getElementById('pts-meta').value) || 10;
  if (!premio) { toast('⚠️ Escribe el nombre del premio', 'error'); return; }

  const r   = await fetch(`${BASE}/restaurantes.php?accion=config_puntos`, {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    body: JSON.stringify({ premio_nombre: premio, puntos_meta: meta })
  });
  const res = await r.json();
  if (!res.ok) {
    if (res.upgrade_required) { toast('⬆️ ' + res.mensaje, 'error'); setTimeout(() => abrirModalPlanes(), 1500); }
    else toast('❌ ' + res.mensaje, 'error');
    return;
  }
  toast('✅ Configuración de puntos guardada', 'success');
}

async function cargarClientesPuntos() {
  try {
    const r   = await fetch(`${BASE}/restaurantes.php?accion=clientes_puntos`, { credentials: 'include' });
    const res = await r.json();
    const tbody = document.getElementById('clientes-puntos-body');
    if (!tbody) return;
    if (!res.ok || !res.data || !res.data.length) {
      tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--text-muted)">Sin clientes con puntos aún</td></tr>';
      return;
    }
    tbody.innerHTML = res.data.map(c => `
      <tr>
        <td style="font-weight:500">${c.telefono}</td>
        <td>
          <div style="display:flex;align-items:center;gap:.5rem">
            <div style="flex:1;height:8px;background:var(--bg3);border-radius:99px;overflow:hidden">
              <div style="height:100%;width:${Math.min(100, (c.puntos_total/10)*100)}%;background:var(--gold);border-radius:99px;transition:width .5s"></div>
            </div>
            <span style="color:var(--gold);font-weight:700;min-width:40px">${c.puntos_total} </span>
          </div>
        </td>
        <td>${c.visitas}</td>
        <td style="color:var(--text-muted);font-size:.85rem">${new Date(c.updated_at).toLocaleDateString('es-MX')}</td>
        <td>
          ${c.puntos_total > 0 ? `
          <button onclick="canjearPuntosAdmin('${c.telefono}', ${c.puntos_total})"
            class="btn btn-gold" style="font-size:.78rem;padding:.35rem .85rem">
            🎁 Canjear y resetear
          </button>` : '<span style="color:var(--text-muted);font-size:.8rem">Sin puntos</span>'}
        </td>
      </tr>
    `).join('');
  } catch(e) { console.error(e); }
}

async function canjearPuntosAdmin(telefono, puntosActuales) {
  if (!confirm(`¿Confirmas el canje de ${puntosActuales} puntos para ${telefono}?\nSus puntos volverán a 0 y empezará de nuevo.`)) return;
  try {
    const r   = await fetch(`${BASE}/restaurantes.php?accion=resetear_puntos`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ telefono })
    });
    const res = await r.json();
    if (!res.ok) { toast('❌ ' + res.mensaje, 'error'); return; }
    toast('✅ Premio canjeado — puntos reseteados a 0', 'success');
    cargarClientesPuntos();
  } catch(e) { toast('❌ Error de conexión', 'error'); }
}
window.canjearPuntosAdmin = canjearPuntosAdmin;

// ── REDES SOCIALES ────────────────────────────────────────────
async function cargarRedes() {
  try {
    const r   = await fetch(`${BASE}/restaurantes.php?accion=get_redes`, { credentials: 'include' });
    const res = await r.json();
    if (!res.ok || !res.data) return;
    const d = res.data;
    ['facebook','instagram','whatsapp','tiktok','twitter'].forEach(red => {
      const el = document.getElementById('red-' + red);
      if (el && d[red]) el.value = d[red];
    });
  } catch(e) {}
}

async function guardarRedes() {
  const redes = {};
  ['facebook','instagram','whatsapp','tiktok','twitter'].forEach(red => {
    const val = document.getElementById('red-' + red)?.value.trim();
    if (val) redes[red] = val;
  });
  try {
    const r   = await fetch(`${BASE}/restaurantes.php?accion=guardar_redes`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(redes)
    });
    const res = await r.json();
    if (!res.ok) { toast('❌ ' + res.mensaje, 'error'); return; }
    toast('✅ Redes sociales guardadas', 'success');
  } catch(e) { toast('❌ Error de conexión', 'error'); }
}
window.guardarRedes = guardarRedes;
// Cargar plan al iniciar
cargarMiPlan();

</script>
</body>
</html>