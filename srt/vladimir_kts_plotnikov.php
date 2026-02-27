<?php
// Владимир. КЦ Плотников — КЦ — 20.02.2026 00:27
// Универсальный хук для Flexbe, Tilda, Marquiz, Quiz и других сервисов
error_reporting(0); // Подавляем вывод ошибок чтобы не ломать JSON ответ
// SOURCE_ID: 584
// Отправка комментариев в Bitrix: ДА
// Авто-UTM: НЕТ

require __DIR__.'/../config.php';
if (!defined('BITRIX_WEBHOOK')) die('Ошибка: вебхук не настроен');
$webhook = BITRIX_WEBHOOK;
$hookName = 'vladimir_kts_plotnikov';

// Флаг отправки комментариев в Bitrix (зависит от маппинга)
$sendCommentsToBitrix = true;

// Флаг автоматической передачи UTM меток
$autoUtmEnabled = false;
$hasUtmMapping = false;

$cityMapping = [];
$cityFieldName = '';

$fieldLabels = [];

$sourceByRegionEnabled = false;
$sourceByRegion = [];

// ========================================
// ЗАЩИТА ОТ НЕСАНКЦИОНИРОВАННОГО ЧТЕНИЯ CRM
// Проверяем ДО проверки метода, чтобы ловить GET и POST
// ========================================
$forbiddenMethods = ['crm.lead.list', 'crm.lead.get', 'crm.deal.list', 'crm.deal.get', 
    'crm.contact.list', 'crm.contact.get', 'crm.company.list', 'crm.company.get',
    'user.get', 'user.search', 'crm.status.list', 'crm.category.list', 'crm.dealcategory.list',
    'crm.product.list', 'crm.currency.list', 'crm.requisite.list'];

// Собираем ВСЕ данные запроса для проверки (включая GET параметры и URI)
$rawInputForCheck = file_get_contents('php://input');
$requestData = $rawInputForCheck . ' ' . http_build_query($_POST) . ' ' . http_build_query($_GET) . ' ' . ($_SERVER['REQUEST_URI'] ?? '') . ' ' . ($_SERVER['QUERY_STRING'] ?? '');
$requestDataLower = strtolower($requestData);

$detectedMethod = null;
foreach ($forbiddenMethods as $method) {
    if (strpos($requestDataLower, strtolower($method)) !== false) {
        $detectedMethod = $method;
        break;
    }
}

if ($detectedMethod) {
    // Логируем попытку в отдельный файл безопасности
    $securityLog = date('d.m.Y H:i:s') . "\n";
    $securityLog .= "Интеграция: Владимир. КЦ Плотников\n";
    $securityLog .= "Hook: vladimir_kts_plotnikov\n";
    $securityLog .= "Обнаружен метод: " . $detectedMethod . "\n";
    $securityLog .= "HTTP метод: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . "\n";
    $securityLog .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
    $securityLog .= "User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . "\n";
    $securityLog .= "Request URI: " . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
    $securityLog .= "Query String: " . ($_SERVER['QUERY_STRING'] ?? '') . "\n";
    $securityLog .= "GET: " . print_r($_GET, true) . "\n";
    $securityLog .= "POST: " . print_r($_POST, true) . "\n";
    $securityLog .= "RAW: " . substr($rawInputForCheck, 0, 1000) . "\n";
    $securityLog .= str_repeat('=', 50) . "\n\n";
    file_put_contents(__DIR__ . '/../logs/security_alerts.log', $securityLog, FILE_APPEND);

    // Выбираем случайный матерный ответ
    $responses = [
        'Читать CRM? Ты охуел?',
        'Попытка несанкционированного чтения зафиксирована.',
        'Не твоего ума данные.',
        'Ты вообще кто?',
        'Попытка жалкая. Результат нулевой.',
        'Ты что тут забыл, блядь?',
        'Пошёл нахуй с этим методом.'
    ];
    $message = $responses[array_rand($responses)];

    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// Разрешаем CORS для внешних запросов
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// ========================================
// ЛОГИРОВАНИЕ RAW ЗАПРОСА
// ========================================
// Используем уже прочитанный rawInputForCheck
$rawInput = $rawInputForCheck;
$rawPost = $_POST;
$rawGet = $_GET;
$requestHeaders = getallheaders() ?: [];

$input = [
    'client' => ['name' => '', 'phone' => '', 'email' => ''],
    'utm' => [],
    'form_data' => [],
    'page' => ['url' => '', 'name' => ''],
    'quiz' => [],
    'city' => ''
];

// Обработка JSON input (для некоторых сервисов)
if (empty($_POST) && !empty($rawInput)) {
    $jsonData = json_decode($rawInput, true);
    if (is_array($jsonData)) {
        $_POST = $jsonData;
    }
}

// ========================================
// ОБРАБОТКА ДАННЫХ FLEXBE
// Структура: data[client], data[form_data], data[page], data[utm]
// ========================================
if (isset($_POST['data']) && is_array($_POST['data'])) {
    $data = $_POST['data'];
    
    // Клиентские данные из data[client]
    if (!empty($data['client']['name'])) {
        $input['client']['name'] = trim($data['client']['name']);
    }
    if (!empty($data['client']['phone'])) {
        $input['client']['phone'] = trim($data['client']['phone']);
    }
    if (!empty($data['client']['email'])) {
        $input['client']['email'] = trim($data['client']['email']);
    }
    
    // URL страницы из data[page]
    if (!empty($data['page']['url'])) {
        $input['page']['url'] = trim($data['page']['url']);
    }
    if (!empty($data['page']['name'])) {
        $input['page']['name'] = trim($data['page']['name']);
    }
    
    // UTM метки из data[utm]
    if (!empty($data['utm']) && is_array($data['utm'])) {
        foreach ($data['utm'] as $utmKey => $utmVal) {
            if (is_scalar($utmVal) && $utmKey !== 'cookies' && $utmKey !== 'ip') {
                $input['utm'][$utmKey] = trim((string)$utmVal);
            }
        }
    }
    
    // ========================================
    // ОБРАБОТКА form_data (КВИЗ ВОПРОСЫ FLEXBE)
    // Структура: data[form_data][fld_X][name], [value], [orig_name]
    // ========================================
    if (!empty($data['form_data']) && is_array($data['form_data'])) {
        foreach ($data['form_data'] as $fieldKey => $fieldData) {
            if (!is_array($fieldData)) continue;
            
            // Пропускаем стандартные поля name, phone, email
            $fieldType = $fieldData['type'] ?? '';
            $fieldId = $fieldData['id'] ?? $fieldKey;
            
            if (in_array($fieldId, ['name', 'phone', 'email'])) {
                // Эти поля уже обработаны из client
                continue;
            }
            
            // Получаем название вопроса и ответ
            $questionName = $fieldData['orig_name'] ?? $fieldData['name'] ?? 'Вопрос';
            $answerValue = $fieldData['value'] ?? '';
            
            if (is_array($answerValue)) {
                $answerValue = implode(', ', $answerValue);
            }
            
            // Если это checkbox со значением 'on' - пропускаем или меняем на 'Да'
            if ($fieldType === 'checkbox' && $answerValue === 'on') {
                $answerValue = 'Да';
            }
            
            // Проверяем, не является ли это полем города
            if (!empty($cityFieldName) && (stripos($questionName, $cityFieldName) !== false || $questionName === $cityFieldName)) {
                $input['city'] = trim($answerValue);
            }
            
            if (trim($answerValue) !== '') {
                $input['quiz'][] = [
                    'question' => $questionName,
                    'answer' => $answerValue
                ];
            }
        }
    }
}

// ========================================
// ОБРАБОТКА ПРОСТЫХ ПОЛЕЙ (Tilda, обычные формы)
// ========================================
foreach ($_POST as $k => $v) {
    if (!is_scalar($k) || $k === 'data' || $k === 'event' || $k === 'site') continue;
    $key = strtolower(trim((string)$k));
    if ($key === '') continue;

    // Если значение - массив, пробуем извлечь скалярное значение
    if (is_array($v)) {
        if (isset($v['value'])) {
            $v = $v['value'];
        } elseif (count($v) > 0) {
            $first = reset($v);
            $v = is_scalar($first) ? $first : json_encode($v, JSON_UNESCAPED_UNICODE);
        } else {
            continue;
        }
    }
    $value = is_scalar($v) ? trim((string)$v) : '';

    // Определяем тип поля
    if (empty($input['client']['name']) && in_array($key, ['name','имя','fio','fullname','client_name','contactname'])) {
        $input['client']['name'] = $value;
    } elseif (empty($input['client']['phone']) && in_array($key, ['phone','tel','telephone','телефон','mobile','client_phone','contactphone'])) {
        $input['client']['phone'] = $value;
    } elseif (empty($input['client']['email']) && in_array($key, ['email','e-mail','mail','почта','client_email','contactemail'])) {
        $input['client']['email'] = $value;
    } elseif (strpos($key, 'utm_') === 0 && empty($input['utm'][substr($key, 4)])) {
        $input['utm'][substr($key, 4)] = $value;
    } elseif (empty($input['page']['url']) && in_array($key, ['url','page','referrer','source','page_url','formurl'])) {
        $input['page']['url'] = $value;
    } elseif (in_array($key, ['city', 'город', 'регион', 'region', 'gorod'])) {
        $input['city'] = $value;
    } elseif ($value !== '' && !in_array($key, ['tranid', 'formid', 'formname'])) {
        $input['form_data'][] = ['name' => ucfirst($key), 'value' => $value];
    }
}

// ========================================
// ВАЛИДАЦИЯ ТЕЛЕФОНА
// ========================================
$phone = trim($input['client']['phone'] ?? '');
$phoneDigits = preg_replace('/[^0-9]/', '', $phone);
$phoneValid = false;
$phoneError = '';

if (empty($phone)) {
    $phoneError = 'Телефон не указан';
} elseif (strlen($phoneDigits) < 10) {
    $phoneError = 'Телефон слишком короткий (менее 10 цифр): ' . $phone;
} elseif (strlen($phoneDigits) > 15) {
    $phoneError = 'Телефон слишком длинный (более 15 цифр): ' . $phone;
} else {
    $phoneValid = true;
}

if (!$phoneValid) {
    // Логируем ошибку
    $logTime = date('d.m.Y H:i:s');
    $logEntry = "Время: {$logTime}\n";
    $logEntry .= "Сервис: КЦ\n";
    $logEntry .= "ОШИБКА ВАЛИДАЦИИ: {$phoneError}\n";
    $logEntry .= "Клиент: " . print_r($input['client'], true) . "\n";
    $logEntry .= "RAW Input: " . substr($rawInput, 0, 500) . "\n";
    $logEntry .= "POST: " . print_r($rawPost, true) . "\n";
    $logEntry .= "Статус: Ошибка валидации телефона\n";
    $logEntry .= str_repeat('-', 50) . "\n\n";
    file_put_contents(__DIR__ . '/../logs/vladimir_kts_plotnikov.log', $logEntry, FILE_APPEND);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'error',
        'lead_id' => 0,
        'message' => 'Ошибка: телефон не найден или заполнен некорректно'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Формируем заголовок лида
$from = 'Заявка из КЦ';
if (!empty($input['page']['url'])) {
    $from .= ' → ' . $input['page']['url'];
} elseif (!empty($input['page']['name'])) {
    $from .= ' → ' . $input['page']['name'];
}

// Функция замены переменных {name}, {phone}, {email}, {url}, {city}
function replaceVariables($text, $input) {
    $replacements = [
        '{name}'  => $input['client']['name'] ?? '',
        '{phone}' => $input['client']['phone'] ?? '',
        '{email}' => $input['client']['email'] ?? '',
        '{url}'   => $input['page']['url'] ?? '',
        '{city}'  => $input['city'] ?? '',
    ];
    return str_replace(array_keys($replacements), array_values($replacements), $text);
}

// Определяем ID города по маппингу (регистронезависимо)
$cityId = '';
if (!empty($input['city']) && !empty($cityMapping)) {
    $cityName = mb_strtolower(trim($input['city']), 'UTF-8');
    // Ищем город регистронезависимо
    foreach ($cityMapping as $key => $id) {
        if (mb_strtolower($key, 'UTF-8') === $cityName) {
            $cityId = $id;
            break;
        }
    }
}

$fields = [];
$comments = '';

// UTM метки (с поддержкой авто-режима)
if ($autoUtmEnabled || $hasUtmMapping) {
    foreach ($input['utm'] as $k => $v) {
        $utmKey = strtoupper($k);
        if (is_scalar($v) && trim((string)$v) !== '' && in_array($utmKey, ['SOURCE', 'MEDIUM', 'CAMPAIGN', 'CONTENT', 'TERM'])) {
            $fields['UTM_' . $utmKey] = trim((string)$v);
        }
    }
}

// Обработка ответов квиза/формы
if (!empty($input['quiz'])) {
    $comments .= "<b>Ответы на вопросы:</b><br>";
    foreach ($input['quiz'] as $q) {
        $question = htmlspecialchars($q['question'] ?? 'Вопрос');
        $answer = htmlspecialchars($q['answer'] ?? '');
        $comments .= "<b>" . $question . ":</b> " . $answer . "<br>";
    }
    $comments .= "<br>";
}

// Дополнительные поля формы
if (!empty($input['form_data'])) {
    foreach ($input['form_data'] as $item) {
        $fieldName = $item['name'] ?? 'Поле';
        $fieldKey = strtolower($fieldName);
        // Применяем маппинг названий полей
        $displayName = $fieldLabels[$fieldKey] ?? $fieldName;
        $n = htmlspecialchars($displayName);
        $v = htmlspecialchars($item['value'] ?? '');
        if (trim($v) !== '') {
            $comments .= "<b>" . $n . ":</b> " . $v . "<br>";
        }
    }
}

// Добавляем город в комментарии
if (!empty($input['city'])) {
    $cityLabel = $fieldLabels['gorod'] ?? $fieldLabels['city'] ?? $fieldLabels['город'] ?? 'Город';
    $comments .= "<b>" . htmlspecialchars($cityLabel) . ":</b> " . htmlspecialchars($input['city']) . "<br>";
}

$name = trim($input['client']['name'] ?? '');
if ($name !== '') $fields['NAME'] = $name;
$phone = trim($input['client']['phone'] ?? '');
if ($phone !== '') {
    // Нормализация телефона
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    $fields['PHONE'] = [['VALUE' => $phone, 'VALUE_TYPE' => 'MOBILE']];
}
$fields['ASSIGNED_BY_ID'] = 1;
$titleValue = replaceVariables('Новая заявка - {phone}', $input);
if (trim($titleValue) !== '') $fields['TITLE'] = trim($titleValue);
// COMMENTS: маппинг найден - комментарии будут отправлены в Bitrix

// Базовые поля лида
$fields['TITLE'] = $fields['TITLE'] ?? $from;
$fields['ASSIGNED_BY_ID'] = $fields['ASSIGNED_BY_ID'] ?? 1;
$fields['OPENED'] = 'Y';
$fields['STATUS_ID'] = 'NEW';

// SOURCE_ID: базовый = 584
$finalSourceId = '584';

// Маппинг SOURCE_ID по региону (если включён)
if ($sourceByRegionEnabled && !empty($input['city']) && !empty($sourceByRegion)) {
    $regionName = mb_strtolower(trim($input['city']), 'UTF-8');
    foreach ($sourceByRegion as $region => $srcId) {
        if (mb_strtolower($region, 'UTF-8') === $regionName) {
            $finalSourceId = $srcId;
            break;
        }
    }
}
if (!empty($finalSourceId)) {
    $fields['SOURCE_ID'] = $finalSourceId;
}

// КОММЕНТАРИИ: добавляем в поля ТОЛЬКО если включён маппинг comments -> COMMENTS
if ($sendCommentsToBitrix && $comments !== '') {
    $fields['COMMENTS'] = $comments;
}

// ========================================
// ЛОГИРОВАНИЕ И ОТПРАВКА В BITRIX24
// ========================================
$logTime = date('d.m.Y H:i:s');
$logEntry = "Время: {$logTime}\n";
$logEntry .= "Сервис: КЦ\n";
$logEntry .= "From: {$from}\n";
$logEntry .= "Клиент: " . print_r($input['client'], true) . "\n";
$logEntry .= "Город: " . ($input['city'] ?: 'не определён') . " (ID: " . ($cityId ?: 'не найден') . ")\n";
$logEntry .= "RAW Input: " . substr($rawInput, 0, 1000) . "\n";
$logEntry .= "POST: " . print_r($rawPost, true) . "\n";
$logEntry .= "Quiz/Комментарии: " . print_r($input['quiz'], true) . "\n";
$logEntry .= "Собранные комментарии (для лога): \n" . strip_tags(str_replace('<br>', "\n", $comments)) . "\n";
$logEntry .= "Отправка комментариев в Bitrix: " . ($sendCommentsToBitrix ? 'ДА' : 'НЕТ') . "\n";
$logEntry .= "Поля для Bitrix: " . print_r($fields, true) . "\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $webhook . 'crm.lead.add',
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => http_build_query(['fields' => $fields, 'params' => ['REGISTER_SONET_EVENT' => 'Y']]),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT        => 15
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
// curl_close() убран — не нужен в PHP 8+

$status = 'Неизвестно';
$leadId = null;
$deliveryFailed = false;
if ($curlError) {
    $status = 'Ошибка CURL: ' . $curlError;
    $deliveryFailed = true;
} elseif ($httpCode >= 500 || $httpCode === 0) {
    $status = 'Ошибка сети/сервера HTTP ' . $httpCode;
    $deliveryFailed = true;
} else {
    $json = json_decode($response, true);
    if (!empty($json['result'])) {
        $leadId = $json['result'];
        $status = 'Успешно (Lead ID: ' . $leadId . ')';
    } elseif (!empty($json['error'])) {
        $status = 'Ошибка Bitrix: ' . ($json['error_description'] ?? $json['error']);
        $deliveryFailed = true; // Ошибка API — сохраняем для переотправки
    } elseif ($httpCode >= 400) {
        $status = 'Ошибка HTTP ' . $httpCode;
        $deliveryFailed = true; // HTTP 4xx ошибка — сохраняем для переотправки
    } else {
        $status = 'Успешно';
    }
}

// Сохраняем недоставленный лид для повторной отправки
if ($deliveryFailed) {
    $failedLead = [
        'id' => uniqid('lead_'),
        'time' => $logTime,
        'fields' => $fields,
        'input' => $input,
        'error' => $status,
        'attempts' => 0,
        'last_attempt' => null
    ];
    $failedFile = __DIR__ . '/../logs/vladimir_kts_plotnikov_failed.json';
    $failedLeads = file_exists($failedFile) ? json_decode(file_get_contents($failedFile), true) : [];
    if (!is_array($failedLeads)) $failedLeads = [];
    $failedLeads[] = $failedLead;
    file_put_contents($failedFile, json_encode($failedLeads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$logEntry .= "Bitrix Response: " . substr($response ?? '', 0, 500) . "\n";
$logEntry .= "Статус: {$status}\n";
$logEntry .= str_repeat('-', 50) . "\n\n";

file_put_contents(__DIR__ . '/../logs/vladimir_kts_plotnikov.log', $logEntry, FILE_APPEND);

// Ответ сервису
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => 'ok', 'lead_id' => $leadId, 'message' => $status], JSON_UNESCAPED_UNICODE);
