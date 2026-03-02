<?php
require 'config.php';
header('Content-Type: application/json');

// Получаем данные из POST
$hook1 = rtrim($_POST['webhook1'] ?? '', '/') . '/';
$hook2 = rtrim($_POST['webhook2'] ?? '', '/') . '/';
$date = date('Y-m-d H:i:s');

// Валидация URL
$valid1 = empty($hook1) || filter_var($hook1, FILTER_VALIDATE_URL);
$valid2 = empty($hook2) || filter_var($hook2, FILTER_VALIDATE_URL);

if (!$valid1 || !$valid2) {
    echo json_encode(['success' => false, 'message' => 'Некорректный URL вебхука']);
    exit;
}

$file = file_get_contents('config.php');

// === Обновляем первый хук ===
if (!empty($hook1)) {
    $file = preg_replace(
        "/define\('BITRIX_WEBHOOK_1',\s*'[^']*'\);/",
        "define('BITRIX_WEBHOOK_1', '{$hook1}');",
        $file
    );
    $file = preg_replace(
        "/define\('WEBHOOK_SET_DATE_1',\s*'[^']*'\);/",
        "define('WEBHOOK_SET_DATE_1', '{$date}');",
        $file
    );
}

// === Обновляем второй хук ===
if (!empty($hook2)) {
    if (preg_match("/define\('BITRIX_WEBHOOK_2',/", $file)) {
        $file = preg_replace(
            "/define\('BITRIX_WEBHOOK_2',\s*'[^']*'\);/",
            "define('BITRIX_WEBHOOK_2', '{$hook2}');",
            $file
        );
        $file = preg_replace(
            "/define\('WEBHOOK_SET_DATE_2',\s*'[^']*'\);/",
            "define('WEBHOOK_SET_DATE_2', '{$date}');",
            $file
        );
    } else {
        // Добавляем новые константы после WEBHOOK_SET_DATE_1
        $file = str_replace(
            "define('WEBHOOK_SET_DATE_1', '{$date}');",
            "define('WEBHOOK_SET_DATE_1', '{$date}');\ndefine('BITRIX_WEBHOOK_2', '{$hook2}');\ndefine('WEBHOOK_SET_DATE_2', '{$date}');",
            $file
        );
    }
}

file_put_contents('config.php', $file);

// Миграция: обновляем все существующие интеграции в srt/ ===
$hooks = array_filter([$hook1, $hook2]); // Только непустые
if (!empty($hooks)) {
    $srtDir = __DIR__ . '/srt';
    if (is_dir($srtDir)) {
        foreach (glob("{$srtDir}/*.php") as $hookFile) {
            $content = file_get_contents($hookFile);
            
            // Заменяем старую строку $webhook = BITRIX_WEBHOOK;
            $oldPattern = '/\$webhook\s*=\s*BITRIX_WEBHOOK\s*;/';
            $newCode = "// Отправка на несколько хуков\n\$webhooks = array_filter([defined('BITRIX_WEBHOOK_1') ? BITRIX_WEBHOOK_1 : '', defined('BITRIX_WEBHOOK_2') ? BITRIX_WEBHOOK_2 : '']);";
            $content = preg_replace($oldPattern, $newCode, $content);
            
            // Заменяем блок отправки (одиночный curl на цикл)
            $oldCurl = '/\$ch\s*=\s*curl_init\(\s*\$webhook\s*\.\s*[\'"]crm\.lead\.add[\'"]\s*\);.*?curl_exec\(\s*\$ch\s*\);.*?curl_close\(\s*\$ch\s*\);/s';
            
            $newCurlBlock = <<<'CURL_BLOCK'
$sendResults = [];
foreach ($webhooks as $hookIndex => $webhookUrl) {
    $ch = curl_init($webhookUrl . 'crm.lead.add');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['fields' => $fields, 'params' => ['REGISTER_SONET_EVENT' => 'Y']]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 15
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $sendResults[] = [
        'hook_index' => $hookIndex + 1,
        'url' => $webhookUrl,
        'http_code' => $httpCode,
        'success' => ($httpCode === 200 && !$error && !empty(json_decode($response, true)['result'])),
        'error' => $error,
        'response' => $response
    ];
}
CURL_BLOCK;
            
            $content = preg_replace($oldCurl, $newCurlBlock, $content);
            
            // Обновляем логи: разделение по хукам
            $oldLog = '/file_put_contents\(__DIR__\s*\.\s*[\'"]\/\.\.\/logs\/[\'"]\s*\.\s*\$hookName\s*\.\s*[\'"]\.log[\'"],\s*\$logEntry,\s*FILE_APPEND\);/';
            $newLog = <<<'LOG_BLOCK'
// Разделение логов по хукам
foreach ($sendResults as $res) {
    $logMsg = $logEntry;
    $logMsg .= "=== Хук #{$res['hook_index']} ===\n";
    $logMsg .= "URL: {$res['url']}\n";
    $logMsg .= "Статус: " . ($res['success'] ? 'Успешно' : 'Ошибка') . "\n";
    if (!$res['success']) {
        $logMsg .= "Ошибка: {$res['error']}\nHTTP Code: {$res['http_code']}\n";
        $logMsg .= "Ответ: " . substr($res['response'], 0, 300) . "\n";
    }
    $logMsg .= str_repeat('=', 50) . "\n\n";
    
    $logFile = __DIR__ . "/../logs/{$hookName}_hook{$res['hook_index']}.log";
    file_put_contents($logFile, $logMsg, FILE_APPEND);
}
LOG_BLOCK;
            
            $content = preg_replace($oldLog, $newLog, $content);
            
            file_put_contents($hookFile, $content);
        }
    }
}

echo json_encode([
    'success' => true, 
    'message' => "Вебхуки обновлены!<br>Дата: <strong>{$date}</strong>",
    'webhooks' => ['hook1' => $hook1, 'hook2' => $hook2]
]);
exit;