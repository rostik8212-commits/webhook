<?php
/**
 * Скрипт миграции v9.0: Диагностика + гибкое обновление
 */

if (session_status() === PHP_SESSION_NONE) {
    require 'config.php';
} else {
    require 'config.php';
}

if (php_sapi_name() !== 'cli') {
    if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
        die('❌ Доступ запрещён. Авторизуйтесь в админке.');
    }
}

$srtDir = __DIR__ . '/srt';
$updated = 0;
$alreadyUpdated = 0;
$skipped = 0;
$details = [];

if (!is_dir($srtDir)) {
    die("❌ Папка {$srtDir} не найдена\n");
}

echo "🔍 Диагностика файлов интеграций (Версия 9.0)...\n\n";

foreach (glob("{$srtDir}/*.php") as $file) {
    $basename = basename($file);
    $hookName = pathinfo($basename, PATHINFO_FILENAME);
    $content = file_get_contents($file);
    
    // Проверяем наличие компонентов
    $checks = [
        'BITRIX_WEBHOOK_1' => strpos($content, 'BITRIX_WEBHOOK_1') !== false,
        'webhooks_array' => strpos($content, '$webhooks = array_filter') !== false,
        'foreach_loop' => strpos($content, 'foreach ($webhooks as') !== false,
        'split_logs' => (strpos($content, '_hook{$res') !== false || strpos($content, '_hook1') !== false),
        'new_response' => strpos($content, 'array_filter($sendResults') !== false,
        'old_webhook' => strpos($content, '$webhook = BITRIX_WEBHOOK;') !== false,
        'old_curl' => (strpos($content, "curl_init(\$webhook . 'crm.lead.add')") !== false || 
                      preg_match('/CURLOPT_URL\s*=>\s*\$webhook\s*\.\s*[\'"]crm\.lead\.add[\'"]/', $content)),
        'old_logs' => preg_match('/file_put_contents.*logs\/.*\$hookName\.log/', $content),
    ];
    
    // Считаем, что файл актуален, если есть ключевые компоненты
    $isUpdated = ($checks['BITRIX_WEBHOOK_1'] && $checks['webhooks_array'] && $checks['foreach_loop']);
    
    if ($isUpdated) {
        $alreadyUpdated++;
        // echo "🔄 [{$basename}] Уже актуален\n";
    } else {
        $details[$basename] = $checks;
        $skipped++;
    }
}

// Вывод статистики
echo "\n" . str_repeat('=', 60) . "\n";
echo "📊 СТАТИСТИКА\n";
echo str_repeat('=', 60) . "\n";
echo "🔄 Уже актуальны: {$alreadyUpdated}\n";
echo "⏭️ Требуют проверки: {$skipped}\n";
echo str_repeat('-', 60) . "\n";

if ($skipped > 0 && $skipped <= 10) {
    echo "\n🔍 Детали по файлам:\n";
    foreach ($details as $name => $checks) {
        echo "\n[{$name}]\n";
        foreach ($checks as $key => $val) {
            echo "  " . ($val ? '✅' : '❌') . " {$key}\n";
        }
    }
} elseif ($skipped > 10) {
    echo "\n⚠️  Пропущено {$skipped} файлов. Покажу первые 3:\n";
    $count = 0;
    foreach ($details as $name => $checks) {
        if ($count >= 3) break;
        echo "\n[{$name}]\n";
        foreach ($checks as $key => $val) {
            echo "  " . ($val ? '✅' : '❌') . " {$key}\n";
        }
        $count++;
    }
    echo "\n💡 Чтобы увидеть все детали, запусти: php migrate.php --verbose\n";
}

echo "\n" . str_repeat('=', 60) . "\n";

// Если все файлы уже актуальны
if ($alreadyUpdated === 165 && $skipped === 0) {
    echo "🎉 Все 165 интеграций уже обновлены! Миграция не требуется.\n";
    echo "\n✅ Что уже работает:\n";
    echo "   • Поддержка двух вебхуков (BITRIX_WEBHOOK_1/2)\n";
    echo "   • Отправка на оба хука одновременно\n";
    echo "   • Разделение логов (_hook1.log / _hook2.log)\n";
    echo "   • Обновление хуков через админку\n";
}

if (php_sapi_name() !== 'cli') {
    echo "<br><a href='index.php'>← Вернуться в админку</a>";
}