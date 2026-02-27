<?php
require 'config.php';
header('Content-Type: application/json');

if ($_POST['newlogin'] ?? '' !== '' && preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $_POST['newlogin'])) {
    $new = $_POST['newlogin'];
    $file = file_get_contents('config.php');
    $file = preg_replace("/\\\$VALID_LOGIN\s*=\s*'[^']*';/", "\$VALID_LOGIN = '{$new}';", $file);
    file_put_contents('config.php', $file);
    echo json_encode(['success' => true, 'message' => "Логин успешно изменён на: <strong>{$new}</strong>"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Логин: 3–20 символов (a-z, 0-9, _, -)']);
}
exit;