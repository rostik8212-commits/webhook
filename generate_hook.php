<?php
$jsonFile = "srt/{$newName}.json";
$config = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];

$title       = $config['title'] ?? 'Без названия';
$serviceName = $config['service'] ?? 'Неизвестный сервис';
$sourceId    = $config['source_id'] ?? ($source ?? '');
$cityField   = $config['city_field'] ?? '';
$cityMapping = $config['city_mapping'] ?? [];
$fieldLabels = $config['field_labels'] ?? []; // Маппинг названий полей (Summa → Сумма долга)
$sourceByRegionEnabled = $config['source_by_region_enabled'] ?? false;
$sourceByRegion = $config['source_by_region'] ?? [];
$autoUtmEnabled = $config['auto_utm_enabled'] ?? false; // Автоматическая передача UTM
$customResponseEnabled = $config['custom_response_enabled'] ?? false; // Кастомный формат ответа
$vkSettings = $config['vk_settings'] ?? [];
$vkConfirmationToken = $vkSettings['confirmation_token'] ?? '';
$vkSecret = $vkSettings['secret'] ?? '';
$vkFormIds = $vkSettings['form_ids'] ?? '';

$map = [];
foreach ($routes as $r) {
    $map[$r['field']] = $r['bitrix'];
}

// Проверяем, есть ли маппинг comments → COMMENTS
$sendComments = in_array('COMMENTS', $map);

// Проверяем, есть ли UTM поля в маппинге
$hasUtmMapping = false;
foreach ($map as $form => $bx) {
    if (strpos($bx, 'UTM_') === 0) {
        $hasUtmMapping = true;
        break;
    }
}

$code = "<?php\n";
$code .= "// {$title} — {$serviceName} — " . date('d.m.Y H:i') . "\n";
$code .= "// Универсальный хук для Flexbe, Tilda, Marquiz, Quiz и других сервисов\n";
$code .= "error_reporting(0); // Подавляем вывод ошибок чтобы не ломать JSON ответ\n";
if ($sourceId) {
    $code .= "// SOURCE_ID: {$sourceId}\n";
}
$code .= "// Отправка комментариев в Bitrix: " . ($sendComments ? "ДА" : "НЕТ (только логи)") . "\n";
$code .= "// Авто-UTM: " . ($autoUtmEnabled ? "ДА" : "НЕТ") . "\n";
$code .= "\n";

$code .= "require __DIR__.'/../config.php';
";
$code .= "if (!defined('BITRIX_WEBHOOK_1')) die('Ошибка: вебхук не настроен');
";
$code .= "// Массив активных вебхуков
";
$code .= "\$webhooks = array_filter([
";
$code .= "    defined('BITRIX_WEBHOOK_1') ? BITRIX_WEBHOOK_1 : '',
";
$code .= "    defined('BITRIX_WEBHOOK_2') ? BITRIX_WEBHOOK_2 : ''
";
$code .= "]);
";
$code .= "\$hookName = '" . addslashes($newName) . "';
";

// Флаг отправки комментариев
$code .= "// Флаг отправки комментариев в Bitrix (зависит от маппинга)\n";
$code .= "\$sendCommentsToBitrix = " . ($sendComments ? "true" : "false") . ";\n\n";

// Флаг автоматической передачи UTM
$code .= "// Флаг автоматической передачи UTM меток\n";
$code .= "\$autoUtmEnabled = " . ($autoUtmEnabled ? "true" : "false") . ";\n";
$code .= "\$hasUtmMapping = " . ($hasUtmMapping ? "true" : "false") . ";\n";
$code .= "\$customResponseEnabled = " . ($customResponseEnabled ? "true" : "false") . ";\n\n";

// Маппинг городов
if (!empty($cityMapping)) {
    $code .= "// Маппинг городов\n";
    $code .= "\$cityMapping = [\n";
    foreach ($cityMapping as $city => $id) {
        $cityEsc = addslashes($city);
        $code .= "    '{$cityEsc}' => '{$id}',\n";
    }
    $code .= "];\n";
    $code .= "\$cityFieldName = '" . addslashes($cityField) . "';\n\n";
} else {
    $code .= "\$cityMapping = [];\n";
    $code .= "\$cityFieldName = '';\n\n";
}

// Маппинг названий полей для комментариев
if (!empty($fieldLabels)) {
    $code .= "// Маппинг названий полей для комментариев\n";
    $code .= "\$fieldLabels = [\n";
    foreach ($fieldLabels as $field => $label) {
        $fieldEsc = addslashes(strtolower($field));
        $labelEsc = addslashes($label);
        $code .= "    '{$fieldEsc}' => '{$labelEsc}',\n";
    }
    $code .= "];\n\n";
} else {
    $code .= "\$fieldLabels = [];\n\n";
}

// Маппинг регион → SOURCE_ID
if ($sourceByRegionEnabled && !empty($sourceByRegion)) {
    $code .= "// Маппинг регион → SOURCE_ID (включён)\n";
    $code .= "\$sourceByRegionEnabled = true;\n";
    $code .= "\$sourceByRegion = [\n";
    foreach ($sourceByRegion as $region => $srcId) {
        $regionEsc = addslashes($region);
        $code .= "    '{$regionEsc}' => '{$srcId}',\n";
    }
    $code .= "];\n\n";
} else {
    $code .= "\$sourceByRegionEnabled = false;\n";
    $code .= "\$sourceByRegion = [];\n\n";
}

// === ЗАЩИТА ОТ ЧТЕНИЯ CRM (ПРОВЕРЯЕМ ДО ПРОВЕРКИ МЕТОДА!) ===
$code .= "// ========================================\n";
$code .= "// ЗАЩИТА ОТ НЕСАНКЦИОНИРОВАННОГО ЧТЕНИЯ CRM\n";
$code .= "// Проверяем ДО проверки метода, чтобы ловить GET и POST\n";
$code .= "// ========================================\n";
$code .= "\$forbiddenMethods = ['crm.lead.list', 'crm.lead.get', 'crm.deal.list', 'crm.deal.get', \n";
$code .= "    'crm.contact.list', 'crm.contact.get', 'crm.company.list', 'crm.company.get',\n";
$code .= "    'user.get', 'user.search', 'crm.status.list', 'crm.category.list', 'crm.dealcategory.list',\n";
$code .= "    'crm.product.list', 'crm.currency.list', 'crm.requisite.list'];\n\n";

$code .= "// Собираем ВСЕ данные запроса для проверки (включая GET параметры и URI)\n";
$code .= "\$rawInputForCheck = file_get_contents('php://input');\n";
$code .= "\$requestData = \$rawInputForCheck . ' ' . http_build_query(\$_POST) . ' ' . http_build_query(\$_GET) . ' ' . (\$_SERVER['REQUEST_URI'] ?? '') . ' ' . (\$_SERVER['QUERY_STRING'] ?? '');\n";
$code .= "\$requestDataLower = strtolower(\$requestData);\n\n";

$code .= "\$detectedMethod = null;\n";
$code .= "foreach (\$forbiddenMethods as \$method) {\n";
$code .= "    if (strpos(\$requestDataLower, strtolower(\$method)) !== false) {\n";
$code .= "        \$detectedMethod = \$method;\n";
$code .= "        break;\n";
$code .= "    }\n";
$code .= "}\n\n";

$code .= "if (\$detectedMethod) {\n";
$code .= "    // Логируем попытку в отдельный файл безопасности\n";
$code .= "    \$securityLog = date('d.m.Y H:i:s') . \"\\n\";\n";
$code .= "    \$securityLog .= \"Интеграция: {$title}\\n\";\n";
$code .= "    \$securityLog .= \"Hook: " . addslashes($newName) . "\\n\";\n";
$code .= "    \$securityLog .= \"Обнаружен метод: \" . \$detectedMethod . \"\\n\";\n";
$code .= "    \$securityLog .= \"HTTP метод: \" . (\$_SERVER['REQUEST_METHOD'] ?? 'unknown') . \"\\n\";\n";
$code .= "    \$securityLog .= \"IP: \" . (\$_SERVER['REMOTE_ADDR'] ?? 'unknown') . \"\\n\";\n";
$code .= "    \$securityLog .= \"User-Agent: \" . (\$_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . \"\\n\";\n";
$code .= "    \$securityLog .= \"Request URI: \" . (\$_SERVER['REQUEST_URI'] ?? '') . \"\\n\";\n";
$code .= "    \$securityLog .= \"Query String: \" . (\$_SERVER['QUERY_STRING'] ?? '') . \"\\n\";\n";
$code .= "    \$securityLog .= \"GET: \" . print_r(\$_GET, true) . \"\\n\";\n";
$code .= "    \$securityLog .= \"POST: \" . print_r(\$_POST, true) . \"\\n\";\n";
$code .= "    \$securityLog .= \"RAW: \" . substr(\$rawInputForCheck, 0, 1000) . \"\\n\";\n";
$code .= "    \$securityLog .= str_repeat('=', 50) . \"\\n\\n\";\n";
$code .= "    file_put_contents(__DIR__ . '/../logs/security_alerts.log', \$securityLog, FILE_APPEND);\n\n";

$code .= "    // Выбираем случайный матерный ответ\n";
$code .= "    \$responses = [\n";
$code .= "        'Читать CRM? Ты охуел?',\n";
$code .= "        'Попытка несанкционированного чтения зафиксирована.',\n";
$code .= "        'Не твоего ума данные.',\n";
$code .= "        'Ты вообще кто?',\n";
$code .= "        'Попытка жалкая. Результат нулевой.',\n";
$code .= "        'Ты что тут забыл, блядь?',\n";
$code .= "        'Пошёл нахуй с этим методом.'\n";
$code .= "    ];\n";
$code .= "    \$message = \$responses[array_rand(\$responses)];\n\n";

$code .= "    header('Content-Type: application/json; charset=utf-8');\n";
$code .= "    http_response_code(403);\n";
$code .= "    echo json_encode(['status' => 'error', 'message' => \$message], JSON_UNESCAPED_UNICODE);\n";
$code .= "    exit;\n";
$code .= "}\n\n";

$code .= "// Разрешаем CORS для внешних запросов\n";
$code .= "header('Access-Control-Allow-Origin: *');\n";
$code .= "header('Access-Control-Allow-Methods: POST, GET, OPTIONS');\n";
$code .= "header('Access-Control-Allow-Headers: Content-Type');\n\n";

$code .= "if (\$_SERVER['REQUEST_METHOD'] === 'OPTIONS') {\n";
$code .= "    http_response_code(200);\n";
$code .= "    exit;\n";
$code .= "}\n\n";

$code .= "if (\$_SERVER['REQUEST_METHOD'] !== 'POST') {\n";
$code .= "    http_response_code(405);\n";
$code .= "    exit('Method Not Allowed');\n";
$code .= "}\n\n";

// === Логирование входящих данных для отладки ===
$code .= "// ========================================\n";
$code .= "// ЛОГИРОВАНИЕ RAW ЗАПРОСА\n";
$code .= "// ========================================\n";
$code .= "// Используем уже прочитанный rawInputForCheck\n";
$code .= "\$rawInput = \$rawInputForCheck;\n";
$code .= "\$rawPost = \$_POST;\n";
$code .= "\$rawGet = \$_GET;\n";
$code .= "\$requestHeaders = getallheaders() ?: [];\n\n";

// === VK CALLBACK API HANDLING ===
if ($serviceName === 'ВКонтакте (Lead Forms)' || (!empty($vkConfirmationToken))) {
    $code .= "// ========================================\n";
    $code .= "// VK CALLBACK API HANDLER\n";
    $code .= "// ========================================\n";
    $code .= "\$vkData = json_decode(\$rawInput, true);\n";
    $code .= "if (isset(\$vkData['type']) && isset(\$vkData['group_id'])) {\n";
    
    // 1. Confirmation
    if ($vkConfirmationToken) {
        $code .= "    // Подтверждение сервера\n";
        $code .= "    if (\$vkData['type'] === 'confirmation') {\n";
        $code .= "        exit('{$vkConfirmationToken}');\n";
        $code .= "    }\n";
    }

    // 2. Secret key check
    if ($vkSecret) {
        $code .= "    // Проверка секретного ключа\n";
        $code .= "    if ((\$vkData['secret'] ?? '') !== '{$vkSecret}') {\n";
        $code .= "        http_response_code(403);\n";
        $code .= "        exit('Invalid secret key');\n";
        $code .= "    }\n";
    }

    // 3. Lead forms processing
    $code .= "    // Обработка новых лидов\n";
    $code .= "    if (\$vkData['type'] === 'lead_forms_new') {\n";
    $code .= "        \$lead = \$vkData['object'];\n";
    $code .= "        \$formId = \$lead['form_id'];\n";

    // Filter by Form ID if specified
    if ($vkFormIds) {
        $ids = array_map('trim', explode(',', $vkFormIds));
        $idsStr = "['" . implode("', '", $ids) . "']";
        $code .= "        // Фильтрация по ID формы\n";
        $code .= "        \$allowedForms = {$idsStr};\n";
        $code .= "        if (!in_array(\$formId, \$allowedForms)) {\n";
        $code .= "            exit('ok'); // Игнорируем чужие формы\n";
        $code .= "        }\n";
    }

    $code .= "        // Парсинг ответов\n";
    $code .= "        \$answers = [];\n";
    $code .= "        \$vkQuiz = []; // Массив для вопросов и ответов (для комментариев)\n";
    $code .= "        if (isset(\$lead['answers']) && is_array(\$lead['answers'])) {\n";
    $code .= "            foreach (\$lead['answers'] as \$ans) {\n";
    $code .= "                \$key = \$ans['key'];\n";
    $code .= "                \$question = \$ans['question'];\n";
    $code .= "                \$answer = \$ans['answer'];\n";
    $code .= "                \$answers[\$key] = \$answer;\n";
    $code .= "                \n";
    $code .= "                // Добавляем в массив для комментариев\n";
    $code .= "                \$vkQuiz[] = ['question' => \$question, 'answer' => \$answer];\n";
    $code .= "                \n";
    $code .= "                // Сопоставляем стандартные поля\n";
    $code .= "                if (\$key === 'first_name') {\n";
    $code .= "                    \$_POST['first_name'] = \$answer;\n";
    $code .= "                    \$_POST['name'] = \$answer;\n";
    $code .= "                } elseif (\$key === 'phone_number') {\n";
    $code .= "                    \$_POST['phone_number'] = \$answer;\n";
    $code .= "                    \$_POST['phone'] = \$answer;\n";
    $code .= "                } elseif (\$key === 'email') {\n";
    $code .= "                    \$_POST['email'] = \$answer;\n";
    $code .= "                } else {\n";
    $code .= "                    // Остальные поля добавляем в POST для стандартной обработки\n";
    $code .= "                    \$_POST[\$key] = \$answer;\n";
    $code .= "                }\n";
    $code .= "            }\n";
    $code .= "        }\n";
    
    // Add form name/title/group info to POST
    $code .= "        \$_POST['form_name'] = \$lead['form_name'] ?? '';\n";
    $code .= "        \$_POST['form_id'] = \$formId;\n";
    $code .= "        \$_POST['group_id'] = \$lead['group_id'] ?? '';\n";
    $code .= "        \$_POST['ad_id'] = \$lead['ad_id'] ?? '';\n";
    $code .= "        \$_POST['answers'] = json_encode(\$answers, JSON_UNESCAPED_UNICODE);\n";
    $code .= "        \$_POST['vk_quiz'] = \$vkQuiz;\n";
    
    // Important: VK expects 'ok' response
    $code .= "        // Отвечаем ВК, что приняли\n";
    $code .= "        echo 'ok';\n";
    $code .= "        // Закрываем соединение для ВК, продолжаем работу скрипта\n";
    $code .= "        if (function_exists('fastcgi_finish_request')) {\n";
    $code .= "            fastcgi_finish_request();\n";
    $code .= "        } else {\n";
    $code .= "            // Fallback для Apache/Windows\n";
    $code .= "            if (ob_get_level() > 0) {\n";
    $code .= "                 ob_end_flush();\n";
    $code .= "            }\n";
    $code .= "            flush();\n";
    $code .= "        }\n";
    $code .= "        \$suppressResponse = true;\n";
    
    $code .= "    } else {\n";
    $code .= "        // Другие типы событий просто подтверждаем\n";
    $code .= "        exit('ok');\n";
    $code .= "    }\n";
    $code .= "}\n\n";
}

// === SENLER HANDLING ===
if ($serviceName === 'Senler') {
    $code .= "// ========================================\n";
    $code .= "// SENLER HANDLER\n";
    $code .= "// ========================================\n";
    $code .= "\$senlerData = json_decode(\$rawInput, true);\n";
    $code .= "    // Идемпотентность (защита от дублей)\n";
    $code .= "    \$uniqBits = [\n";
    $code .= "        (string)(\$senlerData['object']['vk_user_id'] ?? ''),\n";
    $code .= "        (string)(\$senlerData['object']['unixtime'] ?? ''),\n";
    $code .= "        (string)(\$senlerData['type'] ?? ''),\n";
    $code .= "        (string)(\$senlerData['object']['date'] ?? ''),\n";
    $code .= "    ];\n";
    $code .= "    \$idemKey = hash('sha256', implode('|', \$uniqBits) . '|' . \$rawInput);\n";
    $code .= "    \$idemDir = __DIR__ . '/../logs/idempotency/' . date('Y-m');\n";
    $code .= "    if (!is_dir(\$idemDir)) @mkdir(\$idemDir, 0775, true);\n";
    $code .= "    \$idemFile = \$idemDir . '/' . \$idemKey . '.lock';\n\n";
    $code .= "    if (file_exists(\$idemFile)) {\n";
    $code .= "        echo json_encode(['status' => 'ok', 'duplicate' => true]);\n";
    $code .= "        exit;\n";
    $code .= "    }\n";
    $code .= "    @file_put_contents(\$idemFile, date('c'));\n\n";
    
    $code .= "    if (isset(\$senlerData['object']) && is_array(\$senlerData['object'])) {\n";
    $code .= "    \$obj = \$senlerData['object'];\n";
    
    $code .= "    // Name\n";
    $code .= "    \$firstName = trim((string)(\$obj['first_name'] ?? ''));\n";
    $code .= "    \$lastName = trim((string)(\$obj['last_name'] ?? ''));\n";
    $code .= "    \$input['client']['name'] = trim(\$firstName . ' ' . \$lastName);\n\n";

    $code .= "    // Birthdate\n";
    $code .= "    \$bdate = trim((string)(\$obj['bdate'] ?? ''));\n";
    $code .= "    if (\$bdate !== '') {\n";
    $code .= "        \$bdateParts = explode('.', \$bdate);\n";
    $code .= "        if (count(\$bdateParts) === 3) {\n";
    $code .= "            // Bitrix expects YYYY-MM-DD\n";
    $code .= "            \$_POST['bdate'] = \$bdateParts[2] . '-' . \$bdateParts[1] . '-' . \$bdateParts[0];\n";
    $code .= "        } else {\n";
    $code .= "             \$_POST['bdate'] = \$bdate;\n";
    $code .= "        }\n";
    $code .= "    }\n\n";

    $code .= "    // Phone search (scan variables)\n";
    $code .= "    \$variables = is_array(\$obj['variables'] ?? null) ? \$obj['variables'] : [];\n";
    $code .= "    \$phoneKeys = ['nomer', 'number', 'phone', 'Phone', 'PHONE', 'telephone', 'tel', 'mobile', 'номер', 'телефон'];\n";
    $code .= "    foreach (\$phoneKeys as \$pkey) {\n";
    $code .= "        foreach (\$variables as \$varKey => \$varValue) {\n";
    $code .= "            if (strcasecmp((string)\$varKey, \$pkey) === 0 && is_scalar(\$varValue)) {\n";
    $code .= "                \$input['client']['phone'] = (string)\$varValue;\n";
    $code .= "                break 2;\n";
    $code .= "            }\n";
    $code .= "        }\n";
    $code .= "    }\n\n";
    
    $code .= "    // Map variables to form_data\n";
    $code .= "    foreach (\$variables as \$k => \$v) {\n";
    $code .= "        if (is_scalar(\$v)) {\n";
    $code .= "            \$input['form_data'][] = ['name' => \$k, 'value' => \$v];\n";
    $code .= "        }\n";
    $code .= "    }\n\n";

    $code .= "    // VK Link & User ID\n";
    $code .= "    \$vkUserId = \$obj['vk_user_id'] ?? '';\n";
    $code .= "    if (\$vkUserId) {\n";
    $code .= "        \$input['form_data'][] = ['name' => 'VK User ID', 'value' => \$vkUserId];\n";
    $code .= "        \$domain = \$obj['domain'] ?? '';\n";
    $code .= "        \$vkLink = \$domain ? 'https://vk.com/' . \$domain : 'https://vk.com/id' . \$vkUserId;\n";
    $code .= "        \$input['form_data'][] = ['name' => 'VK Link', 'value' => \$vkLink];\n";
    $code .= "    }\n\n";
    
    $code .= "    // Message from subscriptions\n";
    $code .= "    \$subs = \$obj['subscriptions'] ?? [];\n";
    $code .= "    if (!empty(\$subs) && isset(\$subs[0]['message']['text'])) {\n";
    $code .= "        \$input['form_data'][] = ['name' => 'Сообщение', 'value' => \$subs[0]['message']['text']];\n";
    $code .= "    }\n\n";
    
    $code .= "    // Senler response\n";
    $code .= "    echo json_encode(['status' => 'ok']);\n";
    $code .= "    if (function_exists('fastcgi_finish_request')) {\n";
    $code .= "        fastcgi_finish_request();\n";
    $code .= "    } else {\n";
    $code .= "        if (ob_get_level() > 0) ob_end_flush();\n";
    $code .= "        flush();\n";
    $code .= "    }\n";
    $code .= "    \$suppressResponse = true;\n";
    $code .= "}\n";
}

$code .= "\$input = [\n";
$code .= "    'client' => ['name' => '', 'phone' => '', 'email' => '', 'birthdate' => ''],\n";
$code .= "    'utm' => [],\n";
$code .= "    'form_data' => [],\n";
$code .= "    'page' => ['url' => '', 'name' => ''],\n";
$code .= "    'quiz' => [],\n";
$code .= "    'city' => ''\n";
$code .= "];\n\n";

$code .= "// Переносим вопросы из VK в input[quiz] для красивых комментариев\n";
$code .= "if (!empty(\$_POST['vk_quiz'])) {\n";
$code .= "    \$input['quiz'] = \$_POST['vk_quiz'];\n";
$code .= "}\n\n";

// === Обработка JSON input ===
$code .= "// Обработка JSON input (для некоторых сервисов)\n";
$code .= "if (empty(\$_POST) && !empty(\$rawInput)) {\n";
$code .= "    \$jsonData = json_decode(\$rawInput, true);\n";
$code .= "    if (is_array(\$jsonData)) {\n";
$code .= "        \$_POST = \$jsonData;\n";
$code .= "    }\n";
$code .= "}\n\n";

$code .= "// ========================================\n";
$code .= "// ОБРАБОТКА СТАНДАРТНОГО BITRIX WEBHOOK (fields[...])\n";
$code .= "// ========================================\n";
$code .= "if (isset(\$_POST['fields']) && is_array(\$_POST['fields'])) {\n";
$code .= "    \$bxFields = \$_POST['fields'];\n";
$code .= "    \$input['raw_bitrix'] = \$bxFields; // Сохраняем все поля для сквозной передачи\n\n";
$code .= "    // Name\n";
$code .= "    if (empty(\$input['client']['name']) && !empty(\$bxFields['NAME'])) {\n";
$code .= "         \$input['client']['name'] = \$bxFields['NAME'];\n";
$code .= "    }\n";
$code .= "    // Birthdate\n";
    $code .= "    if (empty(\$input['client']['birthdate']) && !empty(\$bxFields['BIRTHDATE'])) {\n";
    $code .= "         \$input['client']['birthdate'] = \$bxFields['BIRTHDATE'];\n";
    $code .= "    }\n";
    $code .= "    // Phone (Bitrix sends complex array, or string)\n";
$code .= "    if (empty(\$input['client']['phone']) && !empty(\$bxFields['PHONE'])) {\n";
$code .= "         \$ph = \$bxFields['PHONE'];\n";
$code .= "         if (is_array(\$ph)) {\n";
$code .= "             // Extract first value\n";
$code .= "             \$first = reset(\$ph);\n";
$code .= "             \$input['client']['phone'] = is_array(\$first) ? (\$first['VALUE'] ?? '') : \$first;\n";
$code .= "         } else {\n";
$code .= "             \$input['client']['phone'] = \$ph;\n";
$code .= "         }\n";
$code .= "    }\n";
$code .= "    // Email\n";
$code .= "    if (empty(\$input['client']['email']) && !empty(\$bxFields['EMAIL'])) {\n";
$code .= "         \$em = \$bxFields['EMAIL'];\n";
$code .= "         if (is_array(\$em)) {\n";
$code .= "             \$first = reset(\$em);\n";
$code .= "             \$input['client']['email'] = is_array(\$first) ? (\$first['VALUE'] ?? '') : \$first;\n";
$code .= "         } else {\n";
$code .= "             \$input['client']['email'] = \$em;\n";
$code .= "         }\n";
$code .= "    }\n";
$code .= "    // Comments\n";
$code .= "    if (!empty(\$bxFields['COMMENTS'])) {\n";
$code .= "         \$input['form_data'][] = ['name' => 'Комментарий (Bitrix)', 'value' => \$bxFields['COMMENTS']];\n";
$code .= "    }\n";
$code .= "    // UTM\n";
$code .= "    foreach (['UTM_SOURCE', 'UTM_MEDIUM', 'UTM_CAMPAIGN', 'UTM_CONTENT', 'UTM_TERM'] as \$utm) {\n";
$code .= "        if (!empty(\$bxFields[\$utm])) {\n";
$code .= "            \$key = strtolower(substr(\$utm, 4));\n";
$code .= "            if (empty(\$input['utm'][\$key])) {\n";
$code .= "                \$input['utm'][\$key] = \$bxFields[\$utm];\n";
$code .= "            }\n";
$code .= "        }\n";
$code .= "    }\n";
$code .= "}\n\n";

// === Главная обработка данных Flexbe ===
$code .= "// ========================================\n";
$code .= "// ОБРАБОТКА ДАННЫХ FLEXBE\n";
$code .= "// Структура: data[client], data[form_data], data[page], data[utm]\n";
$code .= "// ========================================\n";
$code .= "if (isset(\$_POST['data']) && is_array(\$_POST['data'])) {\n";
$code .= "    \$data = \$_POST['data'];\n";
$code .= "    \n";
$code .= "    // Клиентские данные из data[client]\n";
$code .= "    if (!empty(\$data['client']['name'])) {\n";
$code .= "        \$input['client']['name'] = trim(\$data['client']['name']);\n";
$code .= "    }\n";
$code .= "    if (!empty(\$data['client']['phone'])) {\n";
$code .= "        \$input['client']['phone'] = trim(\$data['client']['phone']);\n";
$code .= "    }\n";
$code .= "    if (!empty(\$data['client']['email'])) {\n";
$code .= "        \$input['client']['email'] = trim(\$data['client']['email']);\n";
$code .= "    }\n";
$code .= "    \n";
$code .= "    // URL страницы из data[page]\n";
$code .= "    if (!empty(\$data['page']['url'])) {\n";
$code .= "        \$input['page']['url'] = trim(\$data['page']['url']);\n";
$code .= "    }\n";
$code .= "    if (!empty(\$data['page']['name'])) {\n";
$code .= "        \$input['page']['name'] = trim(\$data['page']['name']);\n";
$code .= "    }\n";
$code .= "    \n";
$code .= "    // UTM метки из data[utm]\n";
$code .= "    if (!empty(\$data['utm']) && is_array(\$data['utm'])) {\n";
$code .= "        foreach (\$data['utm'] as \$utmKey => \$utmVal) {\n";
$code .= "            if (is_scalar(\$utmVal) && \$utmKey !== 'cookies' && \$utmKey !== 'ip') {\n";
$code .= "                \$input['utm'][\$utmKey] = trim((string)\$utmVal);\n";
$code .= "            }\n";
$code .= "        }\n";
$code .= "    }\n";
$code .= "    \n";
$code .= "    // ========================================\n";
$code .= "    // ОБРАБОТКА form_data (КВИЗ ВОПРОСЫ FLEXBE)\n";
$code .= "    // Структура: data[form_data][fld_X][name], [value], [orig_name]\n";
$code .= "    // ========================================\n";
$code .= "    if (!empty(\$data['form_data']) && is_array(\$data['form_data'])) {\n";
$code .= "        foreach (\$data['form_data'] as \$fieldKey => \$fieldData) {\n";
$code .= "            if (!is_array(\$fieldData)) continue;\n";
$code .= "            \n";
$code .= "            // Пропускаем стандартные поля name, phone, email\n";
$code .= "            \$fieldType = \$fieldData['type'] ?? '';\n";
$code .= "            \$fieldId = \$fieldData['id'] ?? \$fieldKey;\n";
$code .= "            \n";
$code .= "            if (in_array(\$fieldId, ['name', 'phone', 'email'])) {\n";
$code .= "                // Эти поля уже обработаны из client\n";
$code .= "                continue;\n";
$code .= "            }\n";
$code .= "            \n";
$code .= "            // Получаем название вопроса и ответ\n";
$code .= "            \$questionName = \$fieldData['orig_name'] ?? \$fieldData['name'] ?? 'Вопрос';\n";
$code .= "            \$answerValue = \$fieldData['value'] ?? '';\n";
$code .= "            \n";
$code .= "            if (is_array(\$answerValue)) {\n";
$code .= "                \$answerValue = implode(', ', \$answerValue);\n";
$code .= "            }\n";
$code .= "            \n";
$code .= "            // Если это checkbox со значением 'on' - пропускаем или меняем на 'Да'\n";
$code .= "            if (\$fieldType === 'checkbox' && \$answerValue === 'on') {\n";
$code .= "                \$answerValue = 'Да';\n";
$code .= "            }\n";
$code .= "            \n";
$code .= "            // Проверяем, не является ли это полем города\n";
$code .= "            if (!empty(\$cityFieldName) && (stripos(\$questionName, \$cityFieldName) !== false || \$questionName === \$cityFieldName)) {\n";
$code .= "                \$input['city'] = trim(\$answerValue);\n";
$code .= "            }\n";
$code .= "            \n";
$code .= "            if (trim(\$answerValue) !== '') {\n";
$code .= "                \$input['quiz'][] = [\n";
$code .= "                    'question' => \$questionName,\n";
$code .= "                    'answer' => \$answerValue\n";
$code .= "                ];\n";
$code .= "            }\n";
$code .= "        }\n";
$code .= "    }\n";
$code .= "}\n\n";

// === Обработка простых полей (Tilda, обычные формы) ===
$code .= "// ========================================\n";
$code .= "// ОБРАБОТКА ПРОСТЫХ ПОЛЕЙ (Tilda, обычные формы)\n";
$code .= "// ========================================\n";
$code .= "foreach (\$_POST as \$k => \$v) {\n";
$code .= "    if (!is_scalar(\$k) || \$k === 'data' || \$k === 'event' || \$k === 'site') continue;\n";
$code .= "    \$key = strtolower(trim((string)\$k));\n";
$code .= "    if (\$key === '') continue;\n\n";

$code .= "    // Если значение - массив, пробуем извлечь скалярное значение\n";
$code .= "    if (is_array(\$v)) {\n";
$code .= "        if (isset(\$v['value'])) {\n";
$code .= "            \$v = \$v['value'];\n";
$code .= "        } elseif (count(\$v) > 0) {\n";
$code .= "            \$first = reset(\$v);\n";
$code .= "            \$v = is_scalar(\$first) ? \$first : json_encode(\$v, JSON_UNESCAPED_UNICODE);\n";
$code .= "        } else {\n";
$code .= "            continue;\n";
$code .= "        }\n";
$code .= "    }\n";
$code .= "    \$value = is_scalar(\$v) ? trim((string)\$v) : '';\n\n";

$code .= "    // Определяем тип поля\n";
$code .= "    if (empty(\$input['client']['name']) && in_array(\$key, ['name','имя','fio','fullname','client_name','contactname'])) {\n";
$code .= "        \$input['client']['name'] = \$value;\n";
$code .= "    } elseif (empty(\$input['client']['phone']) && in_array(\$key, ['phone','tel','telephone','телефон','mobile','client_phone','contactphone'])) {\n";
$code .= "        \$input['client']['phone'] = \$value;\n";
$code .= "    } elseif (empty(\$input['client']['email']) && in_array(\$key, ['email','e-mail','mail','почта','client_email','contactemail'])) {\n";
$code .= "        \$input['client']['email'] = \$value;\n";
$code .= "    } elseif (strpos(\$key, 'utm_') === 0 && empty(\$input['utm'][substr(\$key, 4)])) {\n";
$code .= "        \$input['utm'][substr(\$key, 4)] = \$value;\n";
$code .= "    } elseif (empty(\$input['page']['url']) && in_array(\$key, ['url','page','referrer','source','page_url','formurl'])) {\n";
$code .= "        \$input['page']['url'] = \$value;\n";
$code .= "    } elseif (in_array(\$key, ['city', 'город', 'регион', 'region', 'gorod'])) {\n";
$code .= "        \$input['city'] = \$value;\n";
$code .= "    } elseif (\$value !== '' && !in_array(\$key, ['tranid', 'formid', 'formname'])) {\n";
$code .= "        \$input['form_data'][] = ['name' => ucfirst(\$key), 'value' => \$value];\n";
$code .= "    }\n";
$code .= "}\n\n";

// === ОБРАБОТКА ТЕСТОВЫХ ЗАПРОСОВ ===
$code .= "// ========================================\n";
$code .= "// ОБРАБОТКА ТЕСТОВЫХ ЗАПРОСОВ\n";
$code .= "// ========================================\n";
$code .= "if (isset(\$_REQUEST['test']) || (isset(\$_REQUEST['action']) && \$_REQUEST['action'] === 'test')) {\n";
$code .= "    // Проверка на специфичный тест Tilda (test=test, пустые поля)\n";
$code .= "    \$isTildaTest = (isset(\$_REQUEST['test']) && \$_REQUEST['test'] === 'test' && empty(\$input['client']['phone']));\n";
$code .= "    \$logMsg = \$isTildaTest ? \"Тестовый запрос Tilda (OK, игнорируем ошибку валидации)\\n\" : \"Тестовый запрос (OK)\\n\";\n";
$code .= "    file_put_contents(__DIR__ . '/../logs/" . addslashes($newName) . ".log', date('d.m.Y H:i:s') . \" \" . \$logMsg, FILE_APPEND);\n";
$code .= "    header('Content-Type: application/json; charset=utf-8');\n";
$code .= "    if (\$customResponseEnabled) {\n";
$code .= "        echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);\n";
$code .= "    } else {\n";
$code .= "        echo json_encode(['status' => 'ok', 'message' => 'Test successful'], JSON_UNESCAPED_UNICODE);\n";
$code .= "    }\n";
$code .= "    exit;\n";
$code .= "}\n\n";

// === ВАЛИДАЦИЯ ТЕЛЕФОНА ===
$code .= "// ========================================\n";
$code .= "// ВАЛИДАЦИЯ ТЕЛЕФОНА\n";
$code .= "// ========================================\n";
$code .= "\$phone = trim(\$input['client']['phone'] ?? '');\n";
$code .= "\$phoneDigits = preg_replace('/[^0-9]/', '', \$phone);\n";
$code .= "\$phoneValid = false;\n";
$code .= "\$phoneError = '';\n\n";

$code .= "if (empty(\$phone)) {\n";
$code .= "    \$phoneError = 'Телефон не указан';\n";
$code .= "} elseif (strlen(\$phoneDigits) < 10) {\n";
$code .= "    \$phoneError = 'Телефон слишком короткий (менее 10 цифр): ' . \$phone;\n";
$code .= "} elseif (strlen(\$phoneDigits) > 15) {\n";
$code .= "    \$phoneError = 'Телефон слишком длинный (более 15 цифр): ' . \$phone;\n";
$code .= "} else {\n";
$code .= "    \$phoneValid = true;\n";
$code .= "}\n\n";

$code .= "if (!\$phoneValid) {\n";
$code .= "    // Логируем ошибку\n";
$code .= "    \$logTime = date('d.m.Y H:i:s');\n";
$code .= "    \$logEntry = \"Время: {\$logTime}\\n\";\n";
$code .= "    \$logEntry .= \"Сервис: " . addslashes($serviceName) . "\\n\";\n";
$code .= "    \$logEntry .= \"ОШИБКА ВАЛИДАЦИИ: {\$phoneError}\\n\";\n";
$code .= "    \$logEntry .= \"Клиент: \" . print_r(\$input['client'], true) . \"\\n\";\n";
$code .= "    \$logEntry .= \"RAW Input: \" . substr(\$rawInput, 0, 500) . \"\\n\";\n";
$code .= "    \$logEntry .= \"POST: \" . print_r(\$rawPost, true) . \"\\n\";\n";
$code .= "    \$logEntry .= \"Статус: Ошибка валидации телефона\\n\";\n";
$code .= "    \$logEntry .= str_repeat('-', 50) . \"\\n\\n\";\n";
$code .= "    file_put_contents(__DIR__ . '/../logs/" . addslashes($newName) . ".log', \$logEntry, FILE_APPEND);\n\n";

$code .= "    header('Content-Type: application/json; charset=utf-8');\n";
$code .= "    if (\$customResponseEnabled) {\n";
$code .= "        http_response_code(400);\n";
$code .= "        echo json_encode(['error' => \$phoneError], JSON_UNESCAPED_UNICODE);\n";
$code .= "    } else {\n";
$code .= "        echo json_encode([\n";
$code .= "            'status' => 'error',\n";
$code .= "            'lead_id' => 0,\n";
$code .= "            'message' => 'Ошибка: телефон не найден или заполнен некорректно'\n";
$code .= "        ], JSON_UNESCAPED_UNICODE);\n";
$code .= "    }\n";
$code .= "    exit;\n";
$code .= "}\n\n";

// Формирование заголовка
$code .= "// Формируем заголовок лида\n";
$code .= "\$from = 'Заявка из {$serviceName}';\n";
$code .= "if (!empty(\$input['page']['url'])) {\n";
$code .= "    \$from .= ' → ' . \$input['page']['url'];\n";
$code .= "} elseif (!empty(\$input['page']['name'])) {\n";
$code .= "    \$from .= ' → ' . \$input['page']['name'];\n";
$code .= "}\n\n";

// Функция замены переменных
$code .= "// Функция замены переменных {name}, {phone}, {email}, {url}, {city}\n";
$code .= "function replaceVariables(\$text, \$input) {\n";
$code .= "    \$replacements = [\n";
$code .= "        '{name}'  => \$input['client']['name'] ?? '',\n";
$code .= "        '{phone}' => \$input['client']['phone'] ?? '',\n";
$code .= "        '{email}' => \$input['client']['email'] ?? '',\n";
$code .= "        '{url}'   => \$input['page']['url'] ?? '',\n";
$code .= "        '{city}'  => \$input['city'] ?? '',\n";
$code .= "    ];\n";
$code .= "    return str_replace(array_keys(\$replacements), array_values(\$replacements), \$text);\n";
$code .= "}\n\n";

// Определение города по маппингу (регистронезависимо)
$code .= "// Определяем ID города по маппингу (регистронезависимо)\n";
$code .= "\$cityId = '';\n";
$code .= "if (!empty(\$input['city']) && !empty(\$cityMapping)) {\n";
$code .= "    \$cityName = mb_strtolower(trim(\$input['city']), 'UTF-8');\n";
$code .= "    // Ищем город регистронезависимо\n";
$code .= "    foreach (\$cityMapping as \$key => \$id) {\n";
$code .= "        if (mb_strtolower(\$key, 'UTF-8') === \$cityName) {\n";
$code .= "            \$cityId = \$id;\n";
$code .= "            break;\n";
$code .= "        }\n";
$code .= "    }\n";
$code .= "}\n\n";

$code .= "\$fields = [];\n";
$code .= "if (!empty(\$input['raw_bitrix'])) {\n";
$code .= "    \$fields = \$input['raw_bitrix'];\n";
$code .= "}\n";
$code .= "\$comments = '';\n\n";

// UTM с поддержкой авто-режима
$code .= "// UTM метки (с поддержкой авто-режима)\n";
$code .= "if (\$autoUtmEnabled || \$hasUtmMapping) {\n";
$code .= "    foreach (\$input['utm'] as \$k => \$v) {\n";
$code .= "        \$utmKey = strtoupper(\$k);\n";
$code .= "        if (is_scalar(\$v) && trim((string)\$v) !== '' && in_array(\$utmKey, ['SOURCE', 'MEDIUM', 'CAMPAIGN', 'CONTENT', 'TERM'])) {\n";
$code .= "            \$fields['UTM_' . \$utmKey] = trim((string)\$v);\n";
$code .= "        }\n";
$code .= "    }\n";
$code .= "}\n\n";

// Квиз ответы - собираем всегда для логов
$code .= "// Обработка ответов квиза/формы\n";
$code .= "if (!empty(\$input['quiz'])) {\n";
$code .= "    \$comments .= \"<b>Ответы на вопросы:</b><br>\";\n";
$code .= "    foreach (\$input['quiz'] as \$q) {\n";
$code .= "        \$question = htmlspecialchars(\$q['question'] ?? 'Вопрос');\n";
$code .= "        \$answer = htmlspecialchars(\$q['answer'] ?? '');\n";
$code .= "        \$comments .= \"<b>\" . \$question . \":</b> \" . \$answer . \"<br>\";\n";
$code .= "    }\n";
$code .= "    \$comments .= \"<br>\";\n";
$code .= "}\n\n";

// Дополнительные данные формы
$code .= "// Дополнительные поля формы\n";
$code .= "if (!empty(\$input['form_data'])) {\n";
$code .= "    foreach (\$input['form_data'] as \$item) {\n";
$code .= "        \$fieldName = \$item['name'] ?? 'Поле';\n";
$code .= "        \$fieldKey = strtolower(\$fieldName);\n";
$code .= "        // Применяем маппинг названий полей\n";
$code .= "        \$displayName = \$fieldLabels[\$fieldKey] ?? \$fieldName;\n";
$code .= "        \$n = htmlspecialchars(\$displayName);\n";
$code .= "        \$v = htmlspecialchars(\$item['value'] ?? '');\n";
$code .= "        if (trim(\$v) !== '') {\n";
$code .= "            \$comments .= \"<b>\" . \$n . \":</b> \" . \$v . \"<br>\";\n";
$code .= "        }\n";
$code .= "    }\n";
$code .= "}\n\n";

// Добавляем город в комментарии
$code .= "// Добавляем город в комментарии\n";
$code .= "if (!empty(\$input['city'])) {\n";
$code .= "    \$cityLabel = \$fieldLabels['gorod'] ?? \$fieldLabels['city'] ?? \$fieldLabels['город'] ?? 'Город';\n";
$code .= "    \$comments .= \"<b>\" . htmlspecialchars(\$cityLabel) . \":</b> \" . htmlspecialchars(\$input['city']) . \"<br>\";\n";
$code .= "}\n\n";

// Сопоставление по карте с поддержкой переменных
foreach ($map as $form => $bx) {
    // Проверяем, содержит ли значение переменные
    $hasVariables = preg_match('/\{(name|phone|email|url|city)\}/', $form);
    
    if ($bx === 'PHONE') {
        $code .= "\$phone = trim(\$input['client']['phone'] ?? '');\n";
        $code .= "if (\$phone !== '') {\n";
        $code .= "    // Нормализация телефона\n";
        $code .= "    \$phone = preg_replace('/[^0-9+]/', '', \$phone);\n";
        $code .= "    \$fields['PHONE'] = [['VALUE' => \$phone, 'VALUE_TYPE' => 'MOBILE']];\n";
        $code .= "}\n";
    } elseif ($bx === 'EMAIL') {
        $code .= "\$email = trim(\$input['client']['email'] ?? '');\n";
        $code .= "if (\$email !== '' && filter_var(\$email, FILTER_VALIDATE_EMAIL)) {\n";
        $code .= "    \$fields['EMAIL'] = [['VALUE' => \$email, 'VALUE_TYPE' => 'WORK']];\n";
        $code .= "}\n";
    } elseif ($bx === 'NAME') {
        if ($hasVariables) {
            $formEscaped = addslashes($form);
            $code .= "\$nameValue = replaceVariables('{$formEscaped}', \$input);\n";
            $code .= "if (trim(\$nameValue) !== '') \$fields['NAME'] = trim(\$nameValue);\n";
        } else {
            $code .= "\$name = trim(\$input['client']['name'] ?? '');\n";
            $code .= "if (\$name !== '') \$fields['NAME'] = \$name;\n";
        }
    } elseif ($bx === 'TITLE') {
        if ($hasVariables) {
            $formEscaped = addslashes($form);
            $code .= "\$titleValue = replaceVariables('{$formEscaped}', \$input);\n";
            $code .= "if (trim(\$titleValue) !== '') \$fields['TITLE'] = trim(\$titleValue);\n";
        } else {
            $code .= "\$fields['TITLE'] = \$from;\n";
        }
    } elseif ($bx === 'COMMENTS') {
        // Комментарии обрабатываются отдельно
        $code .= "// COMMENTS: маппинг найден - комментарии будут отправлены в Bitrix\n";
    } elseif ($bx === 'ASSIGNED_BY_ID') {
        $formVal = is_numeric($form) ? intval($form) : 1;
        $code .= "\$fields['ASSIGNED_BY_ID'] = {$formVal};\n";
    } elseif (strpos($bx, 'UF_') === 0) {
        // Кастомное поле UF_CRM_*
        $bxEscaped = addslashes($bx);
        
        // Проверяем, является ли это поле города (по имени поля формы или по имени поля Bitrix)
        $isCityField = in_array(strtolower($form), ['city', 'gorod', 'город', 'регион', 'region']) ||
                       $bx === 'UF_CRM_GOROD' || 
                       stripos($bx, 'CITY') !== false || 
                       stripos($bx, 'GOROD') !== false;
        
        // Проверяем, является ли значение статическим (число или текст без переменных и не похоже на имя поля)
        $isStaticValue = is_numeric($form) || (
            !$hasVariables && 
            !preg_match('/^[a-z_]+$/i', $form) && // не похоже на имя поля
            strlen($form) > 0
        );
        
        if ($hasVariables) {
            $formEscaped = addslashes($form);
            $code .= "// Кастомное поле {$bx} (с переменными)\n";
            $code .= "\$customValue = replaceVariables('{$formEscaped}', \$input);\n";
            $code .= "if (trim(\$customValue) !== '') \$fields['{$bxEscaped}'] = trim(\$customValue);\n";
        } elseif ($isStaticValue) {
            // Статическое значение — записываем напрямую
            $formEscaped = addslashes($form);
            $code .= "// Кастомное поле {$bx} (статическое значение)\n";
            $code .= "\$fields['{$bxEscaped}'] = '{$formEscaped}';\n";
        } elseif ($isCityField) {
            // Специальная обработка для поля города — берём из $input['city'] и используем cityId если есть
            $code .= "// Кастомное поле города {$bx} (из \$input['city'] с маппингом)\n";
            $code .= "if (!empty(\$cityId)) {\n";
            $code .= "    \$fields['{$bxEscaped}'] = \$cityId;\n";
            $code .= "} elseif (!empty(\$input['city'])) {\n";
            $code .= "    \$fields['{$bxEscaped}'] = \$input['city'];\n";
            $code .= "}\n";
        } else {
            // Значение из POST
            $formEscaped = addslashes($form);
            $code .= "// Кастомное поле {$bx} (из POST)\n";
            $code .= "\$v = \$_POST['{$formEscaped}'] ?? '';\n";
            $code .= "if (is_array(\$v) && isset(\$v['value'])) \$v = \$v['value'];\n";
            $code .= "if (is_array(\$v)) \$v = reset(\$v) ?: '';\n";
            $code .= "if (is_scalar(\$v) && trim((string)\$v) !== '') \$fields['{$bxEscaped}'] = trim((string)\$v);\n";
        }
    } else {
        // Обычные поля
        if ($hasVariables) {
            $formEscaped = addslashes($form);
            $bxEscaped = addslashes($bx);
            $code .= "// Поле {$form} -> {$bx} (с переменными)\n";
            $code .= "\$fieldValue = replaceVariables('{$formEscaped}', \$input);\n";
            $code .= "if (trim(\$fieldValue) !== '') \$fields['{$bxEscaped}'] = trim(\$fieldValue);\n";
        } else {
            $formEscaped = addslashes($form);
            $code .= "// Поле {$form} -> {$bx}\n";
            $code .= "\$v = \$_POST['{$formEscaped}'] ?? '';\n";
            $code .= "if (is_array(\$v) && isset(\$v['value'])) \$v = \$v['value'];\n";
            $code .= "if (is_array(\$v)) \$v = reset(\$v) ?: '';\n";
            $code .= "if (is_scalar(\$v) && trim((string)\$v) !== '') \$fields['{$bx}'] = trim((string)\$v);\n";
        }
    }
}

// Базовые поля
$code .= "\n// Базовые поля лида\n";
$code .= "\$fields['TITLE'] = \$fields['TITLE'] ?? \$from;\n";
$code .= "\$fields['ASSIGNED_BY_ID'] = \$fields['ASSIGNED_BY_ID'] ?? 1;\n";
$code .= "\$fields['OPENED'] = 'Y';\n";
$code .= "\$fields['STATUS_ID'] = 'NEW';\n";

// SOURCE_ID с поддержкой маппинга по региону
if ($sourceId) {
    $sourceIdEscaped = addslashes($sourceId);
    $code .= "\n// SOURCE_ID: базовый = {$sourceIdEscaped}\n";
    $code .= "\$finalSourceId = '{$sourceIdEscaped}';\n";
}

// Маппинг SOURCE_ID по региону
$code .= "\n// Маппинг SOURCE_ID по региону (если включён)\n";
$code .= "if (\$sourceByRegionEnabled && !empty(\$input['city']) && !empty(\$sourceByRegion)) {\n";
$code .= "    \$regionName = mb_strtolower(trim(\$input['city']), 'UTF-8');\n";
$code .= "    foreach (\$sourceByRegion as \$region => \$srcId) {\n";
$code .= "        if (mb_strtolower(\$region, 'UTF-8') === \$regionName) {\n";
$code .= "            \$finalSourceId = \$srcId;\n";
$code .= "            break;\n";
$code .= "        }\n";
$code .= "    }\n";
$code .= "}\n";
$code .= "if (!empty(\$finalSourceId)) {\n";
$code .= "    \$fields['SOURCE_ID'] = \$finalSourceId;\n";
$code .= "}\n";

// COMMENTS — ТОЛЬКО если флаг активен
$code .= "\n// КОММЕНТАРИИ: добавляем в поля ТОЛЬКО если включён маппинг comments -> COMMENTS\n";
$code .= "if (\$sendCommentsToBitrix && \$comments !== '') {\n";
$code .= "    \$fields['COMMENTS'] = \$comments;\n";
$code .= "}\n\n";

// === ЛОГИ + ОТПРАВКА НА НЕСКОЛЬКО ВЕБХУКОВ ===
$code .= "// ========================================\n";
$code .= "// ЛОГИРОВАНИЕ И ОТПРАВКА В BITRIX24 (НЕСКОЛЬКО ХУКОВ)\n";
$code .= "// ========================================\n";
$code .= "\$logTime = date('d.m.Y H:i:s');\n";
$code .= "\$baseLogEntry = \"Время: {\$logTime}\\n\";\n";
$code .= "\$baseLogEntry .= \"Сервис: " . addslashes($serviceName) . "\\n\";\n";
$code .= "\$baseLogEntry .= \"From: {\$from}\\n\";\n";
$code .= "\$baseLogEntry .= \"Клиент: \" . print_r(\$input['client'], true) . \"\\n\";\n";
$code .= "\$baseLogEntry .= \"Город: \" . (\$input['city'] ?: 'не определён') . \" (ID: \" . (\$cityId ?: 'не найден') . \")\\n\";\n";
$code .= "\$baseLogEntry .= \"RAW Input: \" . substr(\$rawInput, 0, 1000) . \"\\n\";\n";
$code .= "\$baseLogEntry .= \"POST: \" . print_r(\$rawPost, true) . \"\\n\";\n";
$code .= "\$baseLogEntry .= \"Quiz/Комментарии: \" . print_r(\$input['quiz'], true) . \"\\n\";\n";
$code .= "\$baseLogEntry .= \"Собранные комментарии (для лога): \\n\" . strip_tags(str_replace('<br>', \"\\n\", \$comments)) . \"\\n\";\n";
$code .= "\$baseLogEntry .= \"Отправка комментариев в Bitrix: \" . (\$sendCommentsToBitrix ? 'ДА' : 'НЕТ') . \"\\n\";\n";
$code .= "\$baseLogEntry .= \"Поля для Bitrix: \" . print_r(\$fields, true) . \"\\n\";\n\n";

// === Формируем массив вебхуков ===
$code .= "// Массив активных вебхуков\n";
$code .= "\$webhooks = array_filter([\n";
$code .= "    defined('BITRIX_WEBHOOK_1') ? BITRIX_WEBHOOK_1 : '',\n";
$code .= "    defined('BITRIX_WEBHOOK_2') ? BITRIX_WEBHOOK_2 : ''\n";
$code .= "]);\n\n";

$code .= "// Отправка на каждый активный хук\n";
$code .= "\$sendResults = [];\n";
$code .= "foreach (\$webhooks as \$hookIndex => \$webhookUrl) {\n";
$code .= "    \$ch = curl_init(\$webhookUrl . 'crm.lead.add');\n";
$code .= "    curl_setopt_array(\$ch, [\n";
$code .= "        CURLOPT_POST => true,\n";
$code .= "        CURLOPT_POSTFIELDS => http_build_query(['fields' => \$fields, 'params' => ['REGISTER_SONET_EVENT' => 'Y']]),\n";
$code .= "        CURLOPT_RETURNTRANSFER => true,\n";
$code .= "        CURLOPT_SSL_VERIFYPEER => false,\n";
$code .= "        CURLOPT_TIMEOUT => 15\n";
$code .= "    ]);\n";
$code .= "    \$response = curl_exec(\$ch);\n";
$code .= "    \$httpCode = curl_getinfo(\$ch, CURLINFO_HTTP_CODE);\n";
$code .= "    \$curlError = curl_error(\$ch);\n";
$code .= "    curl_close(\$ch);\n\n";

$code .= "    \$status = 'Неизвестно';\n";
$code .= "    \$leadId = null;\n";
$code .= "    \$deliveryFailed = false;\n";
$code .= "    if (\$curlError) {\n";
$code .= "        \$status = 'Ошибка CURL: ' . \$curlError;\n";
$code .= "        \$deliveryFailed = true;\n";
$code .= "    } elseif (\$httpCode >= 500 || \$httpCode === 0) {\n";
$code .= "        \$status = 'Ошибка сети/сервера HTTP ' . \$httpCode;\n";
$code .= "        \$deliveryFailed = true;\n";
$code .= "    } else {\n";
$code .= "        \$json = json_decode(\$response, true);\n";
$code .= "        if (!empty(\$json['result'])) {\n";
$code .= "            \$leadId = \$json['result'];\n";
$code .= "            \$status = 'Успешно (Lead ID: ' . \$leadId . ')';\n";
$code .= "        } elseif (!empty(\$json['error'])) {\n";
$code .= "            \$status = 'Ошибка Bitrix: ' . (\$json['error_description'] ?? \$json['error']);\n";
$code .= "            \$deliveryFailed = true;\n";
$code .= "        } elseif (\$httpCode >= 400) {\n";
$code .= "            \$status = 'Ошибка HTTP ' . \$httpCode;\n";
$code .= "            \$deliveryFailed = true;\n";
$code .= "        } else {\n";
$code .= "            \$status = 'Успешно';\n";
$code .= "        }\n";
$code .= "    }\n\n";

$code .= "    \$sendResults[] = [\n";
$code .= "        'hook_index' => \$hookIndex + 1,\n";
$code .= "        'url' => \$webhookUrl,\n";
$code .= "        'http_code' => \$httpCode,\n";
$code .= "        'success' => (\$leadId !== null),\n";
$code .= "        'lead_id' => \$leadId,\n";
$code .= "        'error' => \$status,\n";
$code .= "        'response' => \$response,\n";
$code .= "        'deliveryFailed' => \$deliveryFailed,\n";
$code .= "        'fields' => \$fields\n";
$code .= "    ];\n";
$code .= "}\n\n";

// === Логирование по каждому хуку ===
$code .= "// Разделение логов по хукам\n";
$code .= "foreach (\$sendResults as \$res) {\n";
$code .= "    \$logEntry = \$baseLogEntry;\n";
$code .= "    \$logEntry .= \"=== Хук #\" . \$res['hook_index'] . \" ===\\n\";\n";
$code .= "    \$logEntry .= \"URL: \" . \$res['url'] . \"\\n\";\n";
$code .= "    \$logEntry .= \"Статус: \" . \$res['error'] . \"\\n\";\n";
$code .= "    \$logEntry .= \"Bitrix Response: \" . substr(\$res['response'] ?? '', 0, 500) . \"\\n\";\n";
$code .= "    \$logEntry .= str_repeat('=', 50) . \"\\n\\n\";\n";
$code .= "    \n";
$code .= "    \$logFile = __DIR__ . '/../logs/" . addslashes($newName) . "_hook\" . \$res['hook_index'] . \".log';\n";
$code .= "    file_put_contents(\$logFile, \$logEntry, FILE_APPEND);\n";
$code .= "}\n\n";

// === Сохранение недоставленных лидов (если все хуки упали) ===
$code .= "// Сохраняем недоставленный лид, если ВСЕ хуки не сработали\n";
$code .= "\$allFailed = array_filter(\$sendResults, fn(\$r) => \$r['deliveryFailed']);\n";
$code .= "if (count(\$allFailed) === count(\$sendResults) && !empty(\$sendResults)) {\n";
$code .= "    \$first = \$sendResults[0];\n";
$code .= "    \$failedLead = [\n";
$code .= "        'id' => uniqid('lead_'),\n";
$code .= "        'time' => \$logTime,\n";
$code .= "        'fields' => \$first['fields'],\n";
$code .= "        'input' => \$input,\n";
$code .= "        'error' => \$first['error'],\n";
$code .= "        'attempts' => 0,\n";
$code .= "        'last_attempt' => null\n";
$code .= "    ];\n";
$code .= "    \$failedFile = __DIR__ . '/../logs/" . addslashes($newName) . "_failed.json';\n";
$code .= "    \$failedLeads = file_exists(\$failedFile) ? json_decode(file_get_contents(\$failedFile), true) : [];\n";
$code .= "    if (!is_array(\$failedLeads)) \$failedLeads = [];\n";
$code .= "    \$failedLeads[] = \$failedLead;\n";
$code .= "    file_put_contents(\$failedFile, json_encode(\$failedLeads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));\n";
$code .= "}\n\n";

// === Ответ клиенту ===
$code .= "// Ответ сервису (успех, если хотя бы один хук сработал)\n";
$code .= "if (!isset(\$suppressResponse) || !\$suppressResponse) {\n";
$code .= "    header('Content-Type: application/json; charset=utf-8');\n";
$code .= "    \$anySuccess = array_filter(\$sendResults, fn(\$r) => \$r['success']);\n";
$code .= "    \n";
$code .= "    if (!empty(\$anySuccess) || empty(\$webhooks)) {\n";
$code .= "        \$leadId = \$sendResults[0]['lead_id'] ?? null;\n";
$code .= "        if (\$customResponseEnabled) {\n";
$code .= "            if (\$leadId) {\n";
$code .= "                echo json_encode(['id' => (string)\$leadId], JSON_UNESCAPED_UNICODE);\n";
$code .= "            } else {\n";
$code .= "                echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);\n";
$code .= "            }\n";
$code .= "        } else {\n";
$code .= "            echo json_encode([\n";
$code .= "                'status' => \$leadId ? 'ok' : 'warning',\n";
$code .= "                'lead_id' => \$leadId,\n";
$code .= "                'message' => 'Отправлено в ' . count(\$webhooks) . ' вебхук(ов)',\n";
$code .= "                'hooks_sent' => count(\$webhooks)\n";
$code .= "            ], JSON_UNESCAPED_UNICODE);\n";
$code .= "        }\n";
$code .= "    } else {\n";
$code .= "        http_response_code(502);\n";
$code .= "        echo json_encode([\n";
$code .= "            'status' => 'error',\n";
$code .= "            'message' => 'Не удалось отправить ни в один вебхук',\n";
$code .= "            'details' => array_map(fn(\$r) => ['hook' => \$r['hook_index'], 'error' => \$r['error']], \$sendResults)\n";
$code .= "        ], JSON_UNESCAPED_UNICODE);\n";
$code .= "    }\n";
$code .= "}\n";

file_put_contents("srt/{$newName}.php", $code);
