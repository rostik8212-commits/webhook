<?php
//  — Неизвестный сервис — 11.12.2025 11:09

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_POST['data'])) {
    http_response_code(405);
    exit('Method Not Allowed');
}

$input = $_POST['data'] ?? $_POST ?? [];
$from = 'Заявка из Неизвестный сервис';  // ←←← Теперь использует название сервиса
if (!empty($input['page']['url'])) {
    $from .= ' → ' . $input['page']['url'];
}

$fields = [];
$comments = '';

$utm = $input['utm'] ?? [];
$fields['UTM_SOURCE']   = $utm['utm_source'] ?? '';
$fields['UTM_MEDIUM']   = $utm['utm_medium'] ?? '';
$fields['UTM_CAMPAIGN'] = $utm['utm_campaign'] ?? '';
$fields['UTM_CONTENT']  = $utm['utm_content'] ?? '';
$fields['UTM_TERM']     = $utm['utm_term'] ?? '';

foreach ($input['form_data'] ?? [] as $item) {
    $name = $item['orig_name'] ?? $item['name'] ?? 'Поле';
    $value = $item['value'] ?? '';
    $comments .= "<b>" . htmlspecialchars($name) . ":</b> " . htmlspecialchars($value) . "<br>";
}


$fields += [
    'TITLE' => $from,
    'SOURCE_ID' => '',
    'ASSIGNED_BY_ID' => 1,
    'OPENED' => 'Y',
    'STATUS_ID' => 'NEW'
];

if ($comments) $fields['COMMENTS'] = $comments;

$log  = "Время: " . date('d.m.Y H:i:s') . "\n";
$log .= "Сервис: Неизвестный сервис\n";
$log .= "From: $from\n";
file_put_contents(__DIR__.'/../logs/.log', $log . print_r($fields, true) . "\n" . str_repeat('-', 40) . "\n", FILE_APPEND);

$url = 'crm.lead.add';
$data = http_build_query(['fields' => $fields, 'params' => ['REGISTER_SONET_EVENT' => 'Y']]);
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_SSL_VERIFYPEER => false
]);
curl_exec($ch);
echo 'OK';
