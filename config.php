<?php
session_start();

// === КОНСТАНТЫ ДОЛЖНЫ БЫТЬ В САМОМ НАЧАЛЕ — ДО ЛЮБОЙ ПРОВЕРКИ СЕССИИ! ===
define('BITRIX_WEBHOOK', 'https://bankrot40.bitrix24.ru/rest/18903/n2i6t24dkq1mith8/');
define('WEBHOOK_SET_DATE', '2026-02-04 13:56:18');

// === ИСКЛЮЧЕНИЕ ДЛЯ ХУКОВ (папка srt/) ===
$isWebhook = false;
if (isset($_SERVER['SCRIPT_FILENAME'])) {
    $scriptPath = realpath($_SERVER['SCRIPT_FILENAME']);
    $webhookDir = realpath(__DIR__ . '/srt');
    if ($webhookDir && strpos($scriptPath, $webhookDir) === 0) {
        $isWebhook = true;
    }
}

// Если это хук — пропускаем всю авторизацию
if ($isWebhook) {
    // Хуки могут работать без сессии
    goto webhook_end;
}

// === АВТОРИЗАЦИЯ ТОЛЬКО ДЛЯ АДМИНКИ ===
$VALID_LOGIN = 'CFR40';
$VALID_PASS = '40343361';

// Таймаут 30 минут
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset(); session_destroy();
    header('Location: login.php?error=timeout'); exit;
}
$_SESSION['last_activity'] = time();

// Привязка к IP и браузеру
if (!isset($_SESSION['initiated'])) {
    $_SESSION['initiated'] = true;
    $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
} elseif (
    ($_SESSION['ip'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) ||
    ($_SESSION['ua'] !== ($_SERVER['HTTP_USER_AGENT'] ?? ''))
) {
    session_unset(); session_destroy();
    header('Location: login.php?error=security'); exit;
}

// Проверка авторизации
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    if (basename($_SERVER['SCRIPT_NAME']) !== 'login.php') {
        header('Location: login.php'); exit;
    }
} else {
    if (basename($_SERVER['SCRIPT_NAME']) === 'login.php') {
        header('Location: index.php'); exit;
    }
}

// Поля Битрикс24
$bitrixFields = [
    'TITLE' => 'Заголовок лида',
    'NAME' => 'Имя',
    'LAST_NAME' => 'Фамилия',
    'PHONE' => 'Телефон (массив)',
    'EMAIL' => 'Email (массив)',
    'STATUS_ID' => 'Статус лида',
    'SOURCE_ID' => 'Источник',
    'SOURCE_DESCRIPTION' => 'Описание источника',
    'ASSIGNED_BY_ID' => 'Ответственный',
    'COMMENTS' => 'Комментарий',
    'UTM_SOURCE' => 'UTM Source',
    'UTM_MEDIUM' => 'UTM Medium',
    'UTM_CAMPAIGN' => 'UTM Campaign',
    'UTM_CONTENT' => 'UTM Content',
    'UTM_TERM' => 'UTM Term',
];

webhook_end:
// Теперь константа доступна и для хуков, и для админки
?>