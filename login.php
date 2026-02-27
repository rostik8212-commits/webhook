<?php
// Скрываем все технические ошибки от пользователя
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Подключаем конфиг (если его нет — аварийное сообщение)
if (!@include 'config.php') {
    die('<div style="background:#0d1117;color:#f85149;font-family:sans-serif;padding:50px;text-align:center;font-size:20px;">
         Система временно недоступна.<br><small>Обратитесь к администратору.</div>');
}

// Переменная для ошибки
$loginError = '';

// Обработка входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputLogin = trim($_POST['login'] ?? '');
    $inputPass  = $_POST['pass'] ?? '';

    if ($inputLogin === $VALID_LOGIN && $inputPass === $VALID_PASS) {
        $_SESSION['auth'] = true;
        $_SESSION['last_activity'] = time();
        header('Location: index.php');
        exit;
    } else {
        $loginError = 'Неверный логин или пароль';
    }
}

// Если уже авторизован — сразу на главную
if (isset($_SESSION['auth']) && $_SESSION['auth'] === true) {
    header('Location: index.php');
    exit;
}

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вход — Менеджер хуков</title>
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0d1117 0%, #161b22 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            color: #c9d1d9;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: rgba(22, 27, 34, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.6);
            border: 1px solid #30363d;
            backdrop-filter: blur(12px);
            max-width: 420px;
            margin: 0 auto;
        }
        .form-control {
            background: #0d1117;
            border: 1px solid #30363d;
            color: #c9d1d9;
            border-radius: 12px;
            height: 52px;
        }
        .form-control:focus {
            background: #0d1117;
            border-color: #58a6ff;
            box-shadow: 0 0 0 3px rgba(88, 166, 255, 0.3);
            color: #c9d1d9;
        }
        .btn-primary {
            background: #238636;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .btn-primary:hover { background: #2ea043; }
        .alert-danger {
            background: rgba(248, 81, 73, 0.15);
            border: 1px solid #f85149;
            color: #ffa198;
            border-radius: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card login-card border-0">
                    <div class="card-body p-5 text-center">
                        <h1 class="mb-2 text-white fw-bold fs-3">Менеджер хуков</h1>
                        <p class="text-white-50 mb-4">Вход в систему интеграций</p>

                        <!-- ОШИБКА ВВОДА ЛОГИНА/ПАРОЛЯ -->
                        <?php if ($loginError): ?>
                            <div class="alert alert-danger py-3">
                                <?= htmlspecialchars($loginError) ?>
                            </div>
                        <?php endif; ?>

                        <!-- СИСТЕМНЫЕ ОШИБКИ (таймаут, смена IP) -->
                        <?php if ($error === 'timeout'): ?>
                            <div class="alert alert-warning py-3">Сессия истекла. Войдите заново.</div>
                        <?php elseif ($error === 'security'): ?>
                            <div class="alert alert-danger py-3">Доступ запрещён: смена IP или браузера</div>
                        <?php endif; ?>

                        <form method="post" class="mt-4">
                            <div class="mb-3">
                                <input type="text" name="login" class="form-control form-control-lg" 
                                       placeholder="Логин" required autofocus autocomplete="username">
                            </div>
                            <div class="mb-4">
                                <input type="password" name="pass" class="form-control form-control-lg" 
                                       placeholder="Пароль" required autocomplete="current-password">
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100">Войти</button>
                        </form>

                        <div class="mt-5 text-center">
                            <small class="text-white-50 opacity-75">
                                © 2025 Все права защищены
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>