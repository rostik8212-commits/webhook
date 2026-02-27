<?php
require 'config.php';
header('Content-Type: application/json');

if ($_POST['newpass'] ?? '' !== '' && strlen($_POST['newpass']) >= 6) {
    $new = $_POST['newpass'];
    $file = file_get_contents('config.php');
    $file = preg_replace("/\\\$VALID_PASS\s*=\s*'[^']*';/", "\$VALID_PASS = '{$new}';", $file);
    file_put_contents('config.php', $file);
    echo json_encode(['success' => true, 'message' => 'Пароль успешно изменён!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Пароль должен быть не менее 6 символов']);
}
exit;