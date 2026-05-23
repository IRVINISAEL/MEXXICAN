<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php'); exit;
}

$error = '';
$ip    = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error = 'Solicitud inválida. Recarga la página.';
    } else {
        verificarIntentos($ip);
        $usuario  = trim($_POST['usuario'] ?? '');
        $password = $_POST['password'] ?? '';
        usleep(random_int(100000, 300000));

        if ($usuario && $password) {
            $stmt = db()->prepare("SELECT * FROM administradores WHERE usuario = ? AND activo = 1 LIMIT 1");
            $stmt->execute([$usuario]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                limpiarIntentos($ip);
                session_regenerate_id(true);
                $_SESSION['admin_id']      = $admin['id'];
                $_SESSION['admin']         = $admin;
                $_SESSION['login_time']    = time();
                $_SESSION['last_activity'] = time();
                $_SESSION['last_rotation'] = time();
                $_SESSION['login_ip']      = $_SERVER['REMOTE_ADDR'] ?? '';
                header('Location: dashboard.php');
                exit;
            }

            registrarIntentoFallido($ip);
            $error = 'Usuario o contraseña incorrectos';
        } else {
            $error = 'Completa todos los campos';
        }
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MexxicanMx</title>
  <link rel="icon" type="image/png" href="gorro.ico">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --gold:        #e8c07d;
      --gold-dark:   #c9993a;
      --gold-light:  #f2d9a2;
      --bg:          #0e0e10;
      --bg2:         #161618;
      --bg3:         #1d1d20;
      --border:      rgba(232,192,125,0.15);
      --text:        #f0ece4;
      --text-muted:  rgba(240,236,228,0.38);
    }

    body {
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh;
      background-color: var(--bg);
      background-image: url('fosc.png');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }

    body::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(160deg, rgba(10,10,12,0.84) 0%, rgba(14,14,16,0.76) 50%, rgba(10,10,12,0.90) 100%);
      z-index: 0;
    }

    body::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 700px;
      height: 700px;
      background: radial-gradient(ellipse, rgba(232,192,125,0.06) 0%, transparent 70%);
      z-index: 0;
      pointer-events: none;
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      margin-top: 1.2rem;
      color: var(--text-muted);
      font-size: 0.78rem;
      font-weight: 400;
      text-decoration: none;
      letter-spacing: 0.07em;
      text-transform: uppercase;
      transition: color 0.2s, border-color 0.2s;
      border: 1px solid rgba(232,192,125,0.12);
      border-radius: 8px;
      padding: 0.55rem 1.2rem;
    }
    .back-link::before { content: '←'; }
    .back-link:hover { color: var(--gold); border-color: rgba(232,192,125,0.35); }

    .login-wrap {
      position: relative;
      z-index: 2;
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 100%;
      max-width: 400px;
      padding: 1rem;
      animation: riseIn 0.65s cubic-bezier(.22,1,.36,1) both;
    }

    @keyframes riseIn {
      from { opacity: 0; transform: translateY(26px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .logo-ring {
      width: 180px;
      height: 180px;
      border-radius: 50%;
      border: 1.5px solid var(--gold-dark);
      box-shadow:
        0 0 0 4px rgba(232,192,125,0.08),
        0 0 30px rgba(232,192,125,0.18),
        inset 0 0 14px rgba(232,192,125,0.05);
      background: radial-gradient(ellipse at 40% 35%, #2a2318 0%, #141210 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1.5rem;
    }
    .logo-ring img {
      width: 178px;
      height: 178px;
      object-fit: cover;
      border-radius: 50%;
    }
    .logo-letter {
      font-family: 'Playfair Display', serif;
      font-size: 1.7rem;
      font-weight: 700;
      background: linear-gradient(135deg, var(--gold-light), var(--gold-dark));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .brand-name {
      font-family: 'Playfair Display', serif;
      font-size: 1.6rem;
      font-weight: 700;
      color: var(--text);
      text-align: center;
      margin-bottom: 0.3rem;
    }
    .brand-name em {
      font-style: italic;
      background: linear-gradient(135deg, var(--gold-light), var(--gold-dark));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .brand-sub {
      font-size: 0.72rem;
      color: var(--text-muted);
      letter-spacing: 0.10em;
      text-transform: uppercase;
      text-align: center;
      margin-bottom: 2rem;
    }

    .login-box {
      width: 100%;
      background: var(--bg2);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 2rem 1.8rem 1.8rem;
      box-shadow: 0 30px 70px rgba(0,0,0,0.6), inset 0 1px 0 rgba(232,192,125,0.07);
    }

    /* Gold top accent */
    .login-box::before {
      content: '';
      display: block;
      height: 2px;
      width: 44px;
      background: linear-gradient(90deg, var(--gold), var(--gold-dark));
      border-radius: 2px;
      margin: 0 auto 1.6rem;
    }

    .error-msg {
      background: rgba(224,92,92,0.08);
      border: 1px solid rgba(224,92,92,0.25);
      color: #f08080;
      padding: 0.75rem 1rem;
      border-radius: 8px;
      margin-bottom: 1.2rem;
      font-size: 0.83rem;
    }

    .field {
      position: relative;
      margin-bottom: 0.9rem;
    }
    .field > svg {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      width: 15px;
      height: 15px;
      color: var(--gold-dark);
      opacity: 0.55;
      pointer-events: none;
    }
    .field input {
      width: 100%;
      padding: 0.9rem 1rem 0.9rem 2.6rem;
      background: var(--bg3);
      border: 1px solid rgba(232,192,125,0.10);
      border-radius: 10px;
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.93rem;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    }
    .field input::placeholder { color: var(--text-muted); }
    .field input:focus {
      border-color: var(--gold-dark);
      background: #201e17;
      box-shadow: 0 0 0 3px rgba(232,192,125,0.09);
    }

    .btn-login {
      width: 100%;
      margin-top: 0.5rem;
      padding: 0.95rem;
      background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
      color: #111;
      font-family: 'DM Sans', sans-serif;
      font-size: 0.93rem;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: opacity 0.2s, transform 0.15s, box-shadow 0.2s;
      box-shadow: 0 4px 22px rgba(232,192,125,0.22);
    }
    .btn-login:hover {
      opacity: 0.90;
      transform: translateY(-1px);
      box-shadow: 0 8px 30px rgba(232,192,125,0.32);
    }
    .btn-login:active { transform: translateY(0); }
    .btn-login:disabled { opacity: 0.45; cursor: not-allowed; transform: none; }

    .divider {
      height: 1px;
      background: var(--border);
      margin: 1.3rem 0 1.1rem;
    }

    .notice {
      text-align: center;
      font-size: 0.70rem;
      color: var(--text-muted);
      line-height: 1.6;
      letter-spacing: 0.02em;
    }

    @media (max-width: 420px) {
      .login-box { padding: 1.6rem 1.2rem 1.4rem; }
      .brand-name { font-size: 1.3rem; }
    }

    .eye-btn {
      position: absolute;
      right: 0.85rem;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      padding: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--gold-dark);
      opacity: 0.5;
      transition: opacity 0.2s;
      z-index: 3;
    }
    .eye-btn:hover { opacity: 1; }
    .eye-btn svg { width: 16px; height: 16px; pointer-events: none; }
    
    .back-link {
  display: block;
  text-align: center;
  margin-top: 0.9rem;
  color: rgba(240,236,228,0.38);
  font-size: 0.78rem;
  text-decoration: none;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  border: 1px solid rgba(232,192,125,0.12);
  border-radius: 8px;
  padding: 0.55rem 1.2rem;
  transition: color 0.2s, border-color 0.2s;
}
.back-link:hover { color: #e8c07d; border-color: rgba(232,192,125,0.35); }
  </style>
</head>
<body>

  <div class="login-wrap">

    <div class="logo-ring">
      <img src="gorro.png" alt="MexxicanMx"
           onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
      <span class="logo-letter" style="display:none">M</span>
    </div>

    <h1 class="brand-name">Mexxican <em>Mx</em></h1>
    <p class="brand-sub">inicia secion y disfruta de MexxicanMx</p>


    <div class="login-box">

      <?php if ($error): ?>
        <div class="error-msg"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" onsubmit="this.querySelector('.btn-login').disabled=true">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="field">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
          </svg>
          <input type="text" name="usuario" placeholder="Usuario"
                 autocomplete="username" required maxlength="60"
                 value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">
        </div>

        <div class="field">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="5" y="11" width="14" height="11" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>
          </svg>
          <input type="password" id="pwd" name="password" placeholder="Contraseña"
                 autocomplete="current-password" required maxlength="100" style="padding-right:3rem">
          <button type="button" class="eye-btn" onclick="togglePwd(this)" aria-label="Mostrar contraseña">
            <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
            <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display:none">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
              <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
              <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
          </button>
        </div>

        <button type="submit" class="btn-login">Entrar →</button>
        <a href="index.html" class="back-link"> Volver</a>
        <!--<a href="mailto:irvinmartinealejo@gmail.com?subject=Recuperar%20contraseña&body=Mi%20usuario%20es:%20"-->
        <!--   style="display:block;text-align:center;margin-top:0.5rem;font-size:0.72rem;-->
        <!--          color:rgba(240,236,228,0.30);text-decoration:none;transition:color 0.2s;"-->
        <!--   onmouseover="this.style.color='#e8c07d'"-->
        <!--   onmouseout="this.style.color='rgba(240,236,228,0.30)'">-->
        <!--  ¿Olvidaste tu contraseña? Contacta al administrador-->
        <!--</a>-->
      </form>

      <div class="divider"></div>
      <p class="notice">Los accesos no autorizados son monitoreados y reportados.</p>

    </div>
  </div>

  <script>
  function togglePwd(btn) {
    var input = document.getElementById('pwd');
    var open  = btn.querySelector('.eye-open');
    var off   = btn.querySelector('.eye-off');
    if (input.type === 'password') {
      input.type = 'text';
      open.style.display = 'none';
      off.style.display  = 'block';
    } else {
      input.type = 'password';
      open.style.display = 'block';
      off.style.display  = 'none';
    }
  }
  </script>
</body>
</html>