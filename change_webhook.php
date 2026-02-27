<?php
require 'config.php';
header('Content-Type: application/json');

if ($_POST['newhook'] ?? '' !== '' && filter_var($_POST['newhook'], FILTER_VALIDATE_URL)) {
    $newHook = rtrim($_POST['newhook'], '/') . '/';
    $newDate = date('Y-m-d H:i:s');

    $file = file_get_contents('config.php');
    $file = preg_replace("/define\('BITRIX_WEBHOOK',\s*'[^']*'\);/", "define('BITRIX_WEBHOOK', '{$newHook}');", $file);
    $file = preg_replace("/define\('WEBHOOK_SET_DATE',\s*'[^']*'\);/", "define('WEBHOOK_SET_DATE', '{$newDate}');", $file);
    if (!str_contains($file, 'WEBHOOK_SET_DATE')) {
        $file = str_replace("<?php\n", "<?php\ndefine('WEBHOOK_SET_DATE', '{$newDate}');\n", $file);
    }
    file_put_contents('config.php', $file);

    echo json_encode(['success' => true, 'message' => "Вебхук обновлён!<br>Дата: <strong>{$newDate}</strong>"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Некорректный URL вебхука']);
}
exit;