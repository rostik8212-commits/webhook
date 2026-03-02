<?php
require 'config.php';

// Функция транслитерации кириллицы
function transliterate($text) {
    $cyr = ['а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',
            'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'];
    $lat = ['a','b','v','g','d','e','e','zh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','h','ts','ch','sh','sch','','y','','e','yu','ya',
            'A','B','V','G','D','E','E','Zh','Z','I','Y','K','L','M','N','O','P','R','S','T','U','F','H','Ts','Ch','Sh','Sch','','Y','','E','Yu','Ya'];
    $text = str_replace($cyr, $lat, $text);
    $text = preg_replace('/[^a-zA-Z0-9_-]/', '_', $text);
    $text = preg_replace('/_+/', '_', $text);
    $text = trim($text, '_');
    return strtolower($text);
}

// Функция проверки уникальности имени
function isNameUnique($name, $excludeName = '') {
    if ($name === $excludeName) return true;
    return !file_exists("srt/{$name}.php");
}

// Функция генерации уникального имени
function generateUniqueName($baseName, $excludeName = '') {
    $name = transliterate($baseName);
    if (empty($name)) $name = 'hook';
    
    if (isNameUnique($name, $excludeName)) return $name;
    
    $i = 1;
    while (!isNameUnique($name . '_' . $i, $excludeName)) {
        $i++;
    }
    return $name . '_' . $i;
}

// Предустановки полей для разных сервисов
$servicePresets = [
    'flexbe' => [
        'label' => 'Flexbe',
        'fields' => [
            ['field' => 'name', 'bitrix' => 'NAME'],
            ['field' => 'phone', 'bitrix' => 'PHONE'],
            ['field' => 'email', 'bitrix' => 'EMAIL'],
            ['field' => 'utm_source', 'bitrix' => 'UTM_SOURCE'],
            ['field' => 'utm_medium', 'bitrix' => 'UTM_MEDIUM'],
            ['field' => 'utm_campaign', 'bitrix' => 'UTM_CAMPAIGN'],
            ['field' => 'utm_term', 'bitrix' => 'UTM_TERM'],
            ['field' => 'utm_content', 'bitrix' => 'UTM_CONTENT'],
            ['field' => '1', 'bitrix' => 'ASSIGNED_BY_ID'],
            ['field' => 'comments', 'bitrix' => 'COMMENTS'],
        ]
    ],
    'tilda' => [
        'label' => 'Tilda',
        'fields' => [
            ['field' => 'Name', 'bitrix' => 'NAME'],
            ['field' => 'Phone', 'bitrix' => 'PHONE'],
            ['field' => 'Email', 'bitrix' => 'EMAIL'],
            ['field' => 'utm_source', 'bitrix' => 'UTM_SOURCE'],
            ['field' => 'utm_medium', 'bitrix' => 'UTM_MEDIUM'],
            ['field' => 'utm_campaign', 'bitrix' => 'UTM_CAMPAIGN'],
            ['field' => '1', 'bitrix' => 'ASSIGNED_BY_ID'],
            ['field' => 'comments', 'bitrix' => 'COMMENTS'],
        ]
    ],
    'marquiz' => [
        'label' => 'Marquiz',
        'fields' => [
            ['field' => 'name', 'bitrix' => 'NAME'],
            ['field' => 'phone', 'bitrix' => 'PHONE'],
            ['field' => 'email', 'bitrix' => 'EMAIL'],
            ['field' => 'utm_source', 'bitrix' => 'UTM_SOURCE'],
            ['field' => 'utm_medium', 'bitrix' => 'UTM_MEDIUM'],
            ['field' => 'utm_campaign', 'bitrix' => 'UTM_CAMPAIGN'],
            ['field' => '1', 'bitrix' => 'ASSIGNED_BY_ID'],
            ['field' => 'comments', 'bitrix' => 'COMMENTS'],
        ]
    ],
    'envybox' => [
        'label' => 'Envybox',
        'fields' => [
            ['field' => 'name', 'bitrix' => 'NAME'],
            ['field' => 'phone', 'bitrix' => 'PHONE'],
            ['field' => 'utm_source', 'bitrix' => 'UTM_SOURCE'],
            ['field' => 'utm_medium', 'bitrix' => 'UTM_MEDIUM'],
            ['field' => 'utm_campaign', 'bitrix' => 'UTM_CAMPAIGN'],
            ['field' => '1', 'bitrix' => 'ASSIGNED_BY_ID'],
            ['field' => 'comments', 'bitrix' => 'COMMENTS'],
        ]
    ],
    'stepform' => [
        'label' => 'StepForm / Quiz',
        'fields' => [
            ['field' => 'name', 'bitrix' => 'NAME'],
            ['field' => 'phone', 'bitrix' => 'PHONE'],
            ['field' => 'email', 'bitrix' => 'EMAIL'],
            ['field' => '1', 'bitrix' => 'ASSIGNED_BY_ID'],
            ['field' => 'comments', 'bitrix' => 'COMMENTS'],
        ]
    ],
    'vk' => [
        'label' => 'ВКонтакте (Lead Forms)',
        'fields' => [
            ['field' => 'first_name', 'bitrix' => 'NAME'],
            ['field' => 'phone_number', 'bitrix' => 'PHONE'],
            ['field' => 'email', 'bitrix' => 'EMAIL'],
            ['field' => 'form_name', 'bitrix' => 'TITLE'],
            ['field' => '1', 'bitrix' => 'ASSIGNED_BY_ID'],
            ['field' => 'answers', 'bitrix' => 'COMMENTS'],
        ]
    ],
    'senler' => [
        'label' => 'Senler',
        'fields' => [
            ['field' => 'first_name', 'bitrix' => 'NAME'],
            ['field' => 'phone', 'bitrix' => 'PHONE'],
            ['field' => 'bdate', 'bitrix' => 'BIRTHDATE'],
            ['field' => 'variables', 'bitrix' => 'COMMENTS'],
            ['field' => '1', 'bitrix' => 'ASSIGNED_BY_ID'],
        ]
    ],
    'custom' => [
        'label' => 'Свой вариант',
        'fields' => [
            ['field' => 'name', 'bitrix' => 'NAME'],
            ['field' => 'phone', 'bitrix' => 'PHONE'],
            ['field' => '1', 'bitrix' => 'ASSIGNED_BY_ID'],
        ]
    ]
];

$name   = $_GET['name'] ?? '';
$copy   = isset($_GET['copy']);
$isEdit = !empty($name) && !$copy;

// Загрузка конфигурации
$config = [];
if (!empty($name) && file_exists("srt/{$name}.json")) {
    $config = json_decode(file_get_contents("srt/{$name}.json"), true);
}

// Если это копирование — очищаем title для генерации нового
if ($copy && !empty($config['title'])) {
    $config['title'] = $config['title'] . ' (копия)';
}

// AJAX: проверка уникальности имени
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_name'])) {
    header('Content-Type: application/json');
    $checkTitle = trim($_POST['check_name']);
    $generated = generateUniqueName($checkTitle, $isEdit ? $name : '');
    echo json_encode([
        'slug' => $generated,
        'unique' => isNameUnique($generated, $isEdit ? $name : '')
    ]);
    exit;
}

// AJAX: получение предустановки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_preset'])) {
    header('Content-Type: application/json');
    $presetKey = $_POST['get_preset'];
    if (isset($servicePresets[$presetKey])) {
        echo json_encode($servicePresets[$presetKey]['fields']);
    } else {
        echo json_encode([]);
    }
    exit;
}

// Сохранение интеграции
if ($_POST['action'] ?? '' === 'save') {
    $title   = trim($_POST['title'] ?? 'Новая интеграция');
    $service = trim($_POST['service'] ?? '');
    $sourceId = trim($_POST['source_id'] ?? '');
    $cityField = trim($_POST['city_field'] ?? '');
    $cityMapping = trim($_POST['city_mapping'] ?? '');
    $fieldLabelsRaw = trim($_POST['field_labels'] ?? '');
    $sourceByRegionEnabled = isset($_POST['source_by_region_enabled']) ? true : false;
    $sourceByRegionMapping = trim($_POST['source_by_region_mapping'] ?? '');
    $autoUtmEnabled = isset($_POST['auto_utm_enabled']) ? true : false;
    $customResponseEnabled = isset($_POST['custom_response_enabled']) ? true : false;

    // VK Settings
    $vkConfirmationToken = trim($_POST['vk_confirmation_token'] ?? '');
    $vkSecret = trim($_POST['vk_secret'] ?? '');
    $vkFormIds = trim($_POST['vk_form_ids'] ?? '');

    if (empty($service)) {
        die('<div class="alert alert-danger text-center">Ошибка: укажите название сервиса!</div>');
    }

    $routes = [];
    $customFields = $_POST['custom_field'] ?? [];
    foreach ($_POST['field'] ?? [] as $i => $field) {
        $bitrixField = $_POST['bitrix'][$i] ?? '';
        
        // Если выбрано "Своё поле" — берём значение из custom_field
        if ($bitrixField === '__CUSTOM__' && !empty($customFields[$i])) {
            $bitrixField = trim($customFields[$i]);
        }
        
        if (!empty($field) && !empty($bitrixField)) {
            $routes[] = ['field' => $field, 'bitrix' => $bitrixField];
        }
    }

    // Генерация уникального имени с транслитерацией
    $newName = $isEdit ? $name : generateUniqueName($title);

    // Парсинг маппинга городов
    $cityMappingArray = [];
    if (!empty($cityMapping)) {
        $lines = explode("\n", $cityMapping);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '//') === 0) continue;
            if (preg_match("/['\"](.+?)['\"]\s*=>\s*['\"]?(\d+)['\"]?/u", $line, $m)) {
                $cityMappingArray[trim($m[1])] = trim($m[2]);
            }
        }
    }

    // Парсинг маппинга названий полей
    $fieldLabelsArray = [];
    if (!empty($fieldLabelsRaw)) {
        $lines = explode("\n", $fieldLabelsRaw);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '//') === 0) continue;
            if (preg_match("/['\"](.+?)['\"]\s*=>\s*['\"](.+?)['\"]/u", $line, $m)) {
                $fieldLabelsArray[trim($m[1])] = trim($m[2]);
            }
        }
    }

    // Парсинг маппинга регион → SOURCE_ID
    $sourceByRegionArray = [];
    if ($sourceByRegionEnabled && !empty($sourceByRegionMapping)) {
        $lines = explode("\n", $sourceByRegionMapping);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '//') === 0) continue;
            if (preg_match("/['\"](.+?)['\"]\s*=>\s*['\"]?(\d+)['\"]?/u", $line, $m)) {
                $sourceByRegionArray[trim($m[1])] = trim($m[2]);
            }
        }
    }

    file_put_contents("srt/{$newName}.json", json_encode([
        'title'        => $title,
        'service'      => $service,
        'source_id'    => $sourceId,
        'city_field'   => $cityField,
        'city_mapping' => $cityMappingArray,
        'field_labels' => $fieldLabelsArray,
        'source_by_region_enabled' => $sourceByRegionEnabled,
        'source_by_region' => $sourceByRegionArray,
        'auto_utm_enabled' => $autoUtmEnabled,
        'custom_response_enabled' => $customResponseEnabled,
        'vk_settings' => [
            'confirmation_token' => $vkConfirmationToken,
            'secret' => $vkSecret,
            'form_ids' => $vkFormIds
        ],
        'routes'       => $routes
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$webhook = defined('BITRIX_WEBHOOK_1') ? BITRIX_WEBHOOK_1 : '';
$source  = $sourceId;
require 'generate_hook.php';

    
    header('Location: index.php');
    exit;
}

// Правильный URL хука
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$basePath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = $basePath === '/' ? '' : rtrim($basePath, '/');
$hookUrl  = $isEdit ? $protocol . '://' . $_SERVER['HTTP_HOST'] . $basePath . '/srt/' . $name . '.php' : '';

// AJAX для обновления логов
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_view'])) {
    echo renderLogs($name, $_POST['log_view'] === 'detailed');
    exit;
}

// AJAX: Сброс счётчика ошибок
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_errors_hook'])) {
    $hookName = preg_replace('/[^a-z0-9_-]/i', '', $_POST['reset_errors_hook']);
    $statsFile = "logs/{$hookName}_stats.json";
    $stats = file_exists($statsFile) ? json_decode(file_get_contents($statsFile), true) : [];
    $stats['errors_reset_date'] = date('Y-m-d H:i:s');
    file_put_contents($statsFile, json_encode($stats));
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'reset_date' => $stats['errors_reset_date']]);
    exit;
}

// Преобразуем маппинг городов обратно в текст для редактирования
$cityMappingText = '';
if (!empty($config['city_mapping']) && is_array($config['city_mapping'])) {
    foreach ($config['city_mapping'] as $city => $id) {
        $cityMappingText .= "'{$city}' => '{$id}',\n";
    }
}

// Преобразуем маппинг названий полей обратно в текст
$fieldLabelsText = '';
if (!empty($config['field_labels']) && is_array($config['field_labels'])) {
    foreach ($config['field_labels'] as $field => $label) {
        $fieldLabelsText .= "'{$field}' => '{$label}',\n";
    }
}

// Преобразуем маппинг регион → SOURCE_ID обратно в текст
$sourceByRegionText = '';
if (!empty($config['source_by_region']) && is_array($config['source_by_region'])) {
    foreach ($config['source_by_region'] as $region => $srcId) {
        $sourceByRegionText .= "'{$region}' => '{$srcId}',\n";
    }
}

// Получаем статистику и последние лиды
function getHookLeads($hookName, $limit = 10) {
    $logFile = "logs/{$hookName}.log";
    $leads = [];
    
    if (!file_exists($logFile)) return $leads;
    
    $content = file_get_contents($logFile);
    $blocks = preg_split('/-{50,}/', $content);
    
    foreach ($blocks as $block) {
        if (trim($block) === '') continue;
        
        if (preg_match('/Lead ID[:\s]+(\d+)/', $block, $m)) {
            $time = '';
            if (preg_match('/Время:\s*(.+)/', $block, $timeMatch)) {
                $time = trim($timeMatch[1]);
            }
            $leads[] = [
                'id' => $m[1],
                'time' => $time
            ];
        }
    }
    
    return array_reverse(array_slice($leads, -$limit));
}

// Функция получения статистики хука
function getHookStatsForEdit($hookName) {
    $logFile = "logs/{$hookName}.log";
    $statsFile = "logs/{$hookName}_stats.json";
    $stats = [
        'total' => 0,
        'today' => 0,
        'errors' => 0,
        'errors_reset_date' => null
    ];
    
    // Загружаем дату сброса ошибок
    if (file_exists($statsFile)) {
        $savedStats = json_decode(file_get_contents($statsFile), true);
        if (!empty($savedStats['errors_reset_date'])) {
            $stats['errors_reset_date'] = $savedStats['errors_reset_date'];
        }
    }
    
    if (!file_exists($logFile)) return $stats;
    
    $content = file_get_contents($logFile);
    $blocks = preg_split('/-{50,}/', $content);
    $today = date('d.m.Y');
    $resetTimestamp = $stats['errors_reset_date'] ? strtotime($stats['errors_reset_date']) : 0;
    
    foreach ($blocks as $block) {
        if (trim($block) === '') continue;
        $stats['total']++;
        
        // Проверяем дату
        $blockTime = '';
        if (preg_match('/Время:\s*(\d{2}\.\d{2}\.\d{4})/', $block, $m)) {
            if ($m[1] === $today) {
                $stats['today']++;
            }
            $blockTime = $m[1];
        }
        
        // Проверяем ошибки (только после даты сброса)
        if (preg_match('/Статус: Ошибка|Ошибка CURL|Ошибка Bitrix|Ошибка HTTP/i', $block)) {
            // Извлекаем полную дату и время для сравнения
            if (preg_match('/Время:\s*(\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2}:\d{2})/', $block, $timeMatch)) {
                $blockTimestamp = strtotime(str_replace('.', '-', substr($timeMatch[1], 0, 10)) . substr($timeMatch[1], 10));
                if ($blockTimestamp > $resetTimestamp) {
                    $stats['errors']++;
                }
            } else {
                // Если не удалось извлечь точное время, считаем ошибку
                $stats['errors']++;
            }
        }
    }
    
    return $stats;
}

$hookLeads = $isEdit ? getHookLeads($name, 200) : [];
$hookStats = $isEdit ? getHookStatsForEdit($name) : null;
$leadsLimit = isset($_GET['leads']) ? intval($_GET['leads']) : 10;
$displayLeads = array_slice($hookLeads, 0, $leadsLimit);

// Загружаем недоставленные лиды
$failedLeads = [];
if ($isEdit) {
    $failedFile = "logs/{$name}_failed.json";
    if (file_exists($failedFile)) {
        $failedLeads = json_decode(file_get_contents($failedFile), true) ?: [];
    }
}
?>
<!DOCTYPE html>
<html lang="ru" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $isEdit ? 'Редактирование' : ($copy ? 'Копирование' : 'Новая интеграция') ?></title>
    <link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --header-gradient: linear-gradient(135deg, #667eea, #764ba2);
            --card-header-text: white;
            --navbar-bg: rgba(0,0,0,0.3);
            --btn-copy-bg: linear-gradient(135deg, #17a2b8, #138496);
        }

        [data-theme="ocean"] {
            --bg-gradient: linear-gradient(135deg, #2193b0, #6dd5ed);
            --header-gradient: linear-gradient(135deg, #2193b0, #6dd5ed);
        }

        [data-theme="nature"] {
            --bg-gradient: linear-gradient(135deg, #11998e, #38ef7d);
            --header-gradient: linear-gradient(135deg, #11998e, #38ef7d);
        }

        [data-theme="sunset"] {
            --bg-gradient: linear-gradient(135deg, #ff512f, #dd2476);
            --header-gradient: linear-gradient(135deg, #ff512f, #dd2476);
        }

        [data-theme="dark"] {
            --bg-gradient: linear-gradient(135deg, #232526, #414345);
            --header-gradient: linear-gradient(135deg, #434343, #000000);
            --navbar-bg: rgba(0,0,0,0.8);
        }

        body { background: var(--bg-gradient); min-height: 100vh; padding-top: 80px; transition: background 0.5s ease; }
        .navbar { background: var(--navbar-bg)!important; backdrop-filter: blur(10px); }
        .card { border: none; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); overflow: hidden; }
        .card-header { background: var(--header-gradient); color: var(--card-header-text); }
        .route-row { background: #f8f9fa; border-radius: 12px; padding: 14px; margin-bottom: 12px; border: 1px solid #e0e0e0; }

        /* Тема */
        .theme-select { background: rgba(255,255,255,0.2); border: none; color: white; border-radius: 8px; padding: 6px 12px; margin-right: 10px; }
        .theme-select option { color: #333; }

        /* Только блок логов — чёрный */
        #logContainer {
            background: #0d1117;
            color: #c9d1d9;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.92rem;
            line-height: 1.6;
            padding: 20px;
            border-radius: 0 0 16px 16px;
            max-height: 700px;
            overflow-y: auto;
            border-top: 1px solid #30363d;
        }
        .log-entry { padding: 16px 0; border-bottom: 1px solid #21262d; }
        .log-entry:last-child { border-bottom: none; }
        .log-time { color: #58a6ff; font-weight: 600; }
        .log-status-success { color: #3fb950; font-weight: bold; }
        .log-status-error   { color: #f85149; font-weight: bold; }
        .log-status-unknown { color: #8b949e; }
        .log-short-data {
            background: #161b22;
            padding: 12px;
            border-radius: 8px;
            margin-top: 10px;
            border-left: 4px solid #238636;
        }
        .log-detail pre {
            background: #0d1117;
            color: #79c0ff;
            padding: 14px;
            border-radius: 8px;
            margin-top: 10px;
            border: 1px solid #30363d;
            font-size: 0.85rem;
        }
        .slug-preview {
            font-family: 'JetBrains Mono', monospace;
            background: #e9ecef;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #495057;
        }
        .preset-btn {
            transition: all 0.2s;
        }
        .preset-btn:hover {
            transform: scale(1.05);
        }
        .preset-btn.active {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.5);
        }
        .var-hint {
            font-size: 0.8rem;
            color: #6c757d;
            font-family: 'JetBrains Mono', monospace;
        }
        .var-badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            cursor: pointer;
        }
        .var-badge:hover {
        .city-ref-item {
            padding: 4px 8px;
            margin: 2px 0;
            cursor: pointer;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .city-ref-item:hover {
            background: #d1e7dd;
        }
        .city-header {
            cursor: pointer;
            transition: color 0.2s;
        }
        .city-header:hover {
            color: #0d6efd !important;
            text-decoration: underline;
        }
            opacity: 0.8;
        }
        .custom-field-input {
            font-family: 'JetBrains Mono', monospace;
        }
        .city-mapping-area {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark fixed-top shadow-lg">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold fs-4" href="index.php">Менеджер хуков</a>
            <div class="d-flex align-items-center">
                <!-- Тема -->
                <select class="theme-select" id="themeSelect" onchange="setTheme(this.value)">
                    <option value="default">🎨 Стандартная</option>
                    <option value="ocean">🌊 Океан</option>
                    <option value="nature">🌿 Природа</option>
                    <option value="sunset">🌅 Закат</option>
                    <option value="dark">🌑 Тёмная</option>
                </select>
                <a href="logout.php" class="btn btn-outline-danger">Выйти</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <div class="row">
            <div class="col-12">
                <div class="row">
                    <div class="<?= $isEdit ? 'col-lg-8' : 'col-lg-6 offset-lg-3' ?>">
                        <div class="card mt-4">
                    <div class="card-header text-center">
                        <h2 class="mb-0 text-white"><?= $isEdit ? 'Редактирование интеграции' : ($copy ? 'Копирование интеграции' : 'Новая интеграция') ?></h2>
                    </div>
                    <div class="card-body p-5">
                        <?php if ($isEdit): ?>
                        <div class="alert alert-success rounded-pill text-center mb-4">
                            <strong>URL хука:</strong><br>
                            <code><?= htmlspecialchars($hookUrl) ?></code>
                            <button onclick="copyUrl('<?=addslashes($hookUrl)?>')" class="btn btn-sm btn-outline-primary ms-2">Скопировать</button>
                        </div>
                        <?php endif; ?>

                        <?php if (!$isEdit): ?>
                        <!-- Предустановки сервисов -->
                        <div class="mb-4">
                            <label class="form-label fs-5">Выберите сервис (предустановка полей)</label>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($servicePresets as $key => $preset): ?>
                                <button type="button" class="btn btn-outline-primary preset-btn" data-preset="<?= $key ?>">
                                    <?= htmlspecialchars($preset['label']) ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <form method="post" id="integrationForm">
                            <div class="mb-4">
                                <label class="form-label fs-5">Название интеграции</label>
                                <input type="text" name="title" id="titleInput" class="form-control form-control-lg" value="<?=htmlspecialchars($config['title'] ?? '')?>" required>
                                <?php if (!$isEdit): ?>
                                <div class="mt-2">
                                    <small class="text-muted">URL хука: </small>
                                    <span class="slug-preview" id="slugPreview">введите название...</span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fs-5 text-danger">Сервис <small class="text-muted">(обязательно)</small></label>
                                    <input type="text" name="service" id="serviceInput" class="form-control form-control-lg" value="<?=htmlspecialchars($config['service'] ?? '')?>" placeholder="Flexbe, Tilda, Marquiz..." required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fs-5">SOURCE_ID <small class="text-muted">(ID источника в Битрикс24)</small></label>
                                    <input type="text" name="source_id" class="form-control form-control-lg" value="<?=htmlspecialchars($config['source_id'] ?? '')?>" placeholder="Например: WEB, CALL, 1, UC_XXXXX">
                                    <small class="text-muted">Оставьте пустым, если не нужен</small>
                                </div>
                            </div>

                            <!-- VK Settings Block -->
                            <div class="card border-primary mb-4" id="vkSettingsBlock" style="display: none;">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">⚙️ Настройки ВКонтакте (Callback API)</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Строка подтверждения (confirmation_token)</label>
                                            <input type="text" name="vk_confirmation_token" class="form-control" value="<?=htmlspecialchars($config['vk_settings']['confirmation_token'] ?? '')?>" placeholder="Например: 5d81422c">
                                            <small class="text-muted">Из настроек сообщества (Callback API)</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Секретный ключ (secret key)</label>
                                            <input type="text" name="vk_secret" class="form-control" value="<?=htmlspecialchars($config['vk_settings']['secret'] ?? '')?>" placeholder="Придумайте ключ">
                                            <small class="text-muted">Для проверки подлинности запросов</small>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">ID форм (опционально)</label>
                                        <input type="text" name="vk_form_ids" class="form-control" value="<?=htmlspecialchars($config['vk_settings']['form_ids'] ?? '')?>" placeholder="Например: 1, 37, 39 (через запятую)">
                                        <small class="text-muted">Если указано, будут обрабатываться только заявки с этими ID. Оставьте пустым для всех форм.</small>
                                    </div>
                                </div>
                            </div>

                            <h4 class="mb-3">Сопоставление полей</h4>
                            
                            <!-- Подсказка по переменным -->
                            <div class="alert alert-info mb-3">
                                <strong>Переменные:</strong> В поле "Значение" можно использовать переменные:
                                <div class="mt-2">
                                    <span class="badge bg-primary var-badge me-1" onclick="insertVar('{name}')">{name}</span>
                                    <span class="badge bg-primary var-badge me-1" onclick="insertVar('{phone}')">{phone}</span>
                                    <span class="badge bg-primary var-badge me-1" onclick="insertVar('{email}')">{email}</span>
                                    <span class="badge bg-primary var-badge me-1" onclick="insertVar('{url}')">{url}</span>
                                    <span class="badge bg-primary var-badge me-1" onclick="insertVar('{city}')">{city}</span>
                                </div>
                                <div class="var-hint mt-2">Пример: <code>Новая заявка - {name}</code> или <code>Заявка с {url}</code></div>
                            </div>

                            <div id="routes">
                                <?php
                                $default = [['field'=>'name','bitrix'=>'NAME'],['field'=>'phone','bitrix'=>'PHONE']];
                                foreach (($config['routes'] ?? $default) as $i => $r): 
                                    $isCustom = strpos($r['bitrix'] ?? '', 'UF_') === 0;
                                ?>
                                <div class="route-row d-flex align-items-center gap-3 mb-3">
                                    <input type="text" name="field[]" class="form-control" value="<?=htmlspecialchars($r['field'])?>" placeholder="значение или переменная">
                                    <span class="fw-bold fs-4 text-primary">→</span>
                                    <select name="bitrix[]" class="form-select bitrix-select" onchange="toggleCustomField(this)">
                                        <option value="">-- Выберите --</option>
                                        <?php foreach($bitrixFields as $k=>$v): ?>
                                            <option value="<?=$k?>" <?= (!$isCustom && $k===$r['bitrix'])?'selected':'' ?>><?=$v?></option>
                                        <?php endforeach; ?>
                                        <option value="__CUSTOM__" <?= $isCustom ? 'selected' : '' ?>>+ Своё поле (UF_CRM_...)</option>
                                    </select>
                                    <input type="text" name="custom_field[]" class="form-control custom-field-input" style="display: <?= $isCustom ? 'block' : 'none' ?>; max-width: 180px;" placeholder="UF_CRM_XXXXX" value="<?= $isCustom ? htmlspecialchars($r['bitrix']) : '' ?>">
                                    <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">×</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-outline-primary mb-4" onclick="addRoute()">+ Добавить поле</button>

                            <!-- Авто-UTM -->
                            <div class="card bg-light mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <input type="checkbox" name="auto_utm_enabled" id="autoUtmEnabled" class="form-check-input me-2" <?= !empty($config['auto_utm_enabled']) ? 'checked' : '' ?>>
                                        <label for="autoUtmEnabled" class="form-check-label">🔗 Автоматическая передача UTM-меток</label>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <small class="text-muted">
                                        <strong>ЕСЛИ включено</strong> — UTM-метки (utm_source, utm_medium, utm_campaign, utm_content, utm_term) будут автоматически передаваться в Bitrix из входящего запроса.<br>
                                        <strong>ЕСЛИ выключено</strong> — UTM-метки будут передаваться только если настроены в маппинге полей выше.
                                    </small>
                                </div>
                            </div>

                            <!-- Кастомный формат ответа -->
                            <div class="card bg-light mb-4">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">
                                        <input type="checkbox" name="custom_response_enabled" id="customResponseEnabled" class="form-check-input me-2" <?= !empty($config['custom_response_enabled']) ? 'checked' : '' ?>>
                                        <label for="customResponseEnabled" class="form-check-label">⚙️ Кастомный формат ответа (JSON)</label>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <small class="text-muted">
                                        <strong>ЕСЛИ включено</strong> — при успехе вернётся JSON <code>{"id": "LEAD_ID"}</code>, при ошибке — HTTP статус (400/500) и JSON <code>{"error": "..."}</code>.<br>
                                        <strong>ЕСЛИ выключено</strong> — всегда возвращается HTTP 200 и JSON <code>{"status": "ok", "message": "..."}</code> (стандартный формат).
                                    </small>
                                </div>
                            </div>

                            <!-- Маппинг городов -->
                            <div class="card bg-light mb-4">
                                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">🏙️ Маппинг городов (опционально)</h5>
                                    <button type="button" class="btn btn-sm btn-outline-light" onclick="toggleCityReference()">📋 Справочник городов</button>
                                </div>
                                <div class="card-body">
                                    <!-- Справочник городов -->
                                    <div id="cityReference" class="alert alert-secondary mb-3" style="display: none; max-height: 300px; overflow-y: auto;">
                                        <h6 class="mb-2">Нажмите на заголовок группы для копирования всех вариантов:</h6>
                                        <div class="city-ref-list" style="font-family: monospace; font-size: 0.85rem;">
                                            
                                            <div class="city-group mb-3">
                                                <div class="city-header fw-bold mb-1 text-primary" onclick="copyCityGroup(this)">Брянск (264605) 📋</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'брянск' => '264605',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'БРЯНСК' => '264605',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Брянск' => '264605',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'брянская область' => '264605',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Брянская область' => '264605',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'брянская обл' => '264605',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'брянская обл.' => '264605',</div>
                                            </div>

                                            <div class="city-group mb-3">
                                                <div class="city-header fw-bold mb-1 text-primary" onclick="copyCityGroup(this)">Владимир (5051) 📋</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'владимир' => '5051',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ВЛАДИМИР' => '5051',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Владимир' => '5051',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'владимирская область' => '5051',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Владимирская область' => '5051',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'владимирская обл' => '5051',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'владимирская обл.' => '5051',</div>
                                            </div>

                                            <div class="city-group mb-3">
                                                <div class="city-header fw-bold mb-1 text-primary" onclick="copyCityGroup(this)">Волгоград (1410497) 📋</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'волгоград' => '1410497',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ВОЛГОГРАД' => '1410497',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Волгоград' => '1410497',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'волгоградская область' => '1410497',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Волгоградская область' => '1410497',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'волгоградская обл' => '1410497',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'волгоградская обл.' => '1410497',</div>
                                            </div>

                                            <div class="city-group mb-3">
                                                <div class="city-header fw-bold mb-1 text-primary" onclick="copyCityGroup(this)">Волгодонск (318851) 📋</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'волгодонск' => '318851',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ВОЛГОДОНСК' => '318851',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Волгодонск' => '318851',</div>
                                            </div>

                                            <div class="city-group mb-3">
                                                <div class="city-header fw-bold mb-1 text-primary" onclick="copyCityGroup(this)">Иваново (982203) 📋</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'иваново' => '982203',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ИВАНОВО' => '982203',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Иваново' => '982203',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ивановская область' => '982203',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Ивановская область' => '982203',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ивановская обл' => '982203',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ивановская обл.' => '982203',</div>
                                            </div>

                                            <div class="city-group mb-3">
                                                <div class="city-header fw-bold mb-1 text-primary" onclick="copyCityGroup(this)">Казань (1399959) 📋</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'казань' => '1399959',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'КАЗАНЬ' => '1399959',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Казань' => '1399959',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'республика татарстан' => '1399959',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Республика Татарстан' => '1399959',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'татарстан' => '1399959',</div>
                                            </div>

                                            <div class="city-group mb-3">
                                                <div class="city-header fw-bold mb-1 text-primary" onclick="copyCityGroup(this)">Калуга (5) 📋</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'калуга' => '5',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'КАЛУГА' => '5',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Калуга' => '5',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'калужская область' => '5',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Калужская область' => '5',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'калужская обл' => '5',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'калужская обл.' => '5',</div>
                                            </div>

                                            <div class="city-group mb-3">
                                                <div class="city-header fw-bold mb-1 text-primary" onclick="copyCityGroup(this)">Липецк (982201) 📋</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'липецк' => '982201',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ЛИПЕЦК' => '982201',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Липецк' => '982201',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'липецкая область' => '982201',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Липецкая область' => '982201',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'липецкая обл' => '982201',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'липецкая обл.' => '982201',</div>
                                            </div>

                                            <div class="city-group mb-3">
                                                <div class="city-header fw-bold mb-1 text-primary" onclick="copyCityGroup(this)">Нижний Новгород (1245589) 📋</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'нижний новгород' => '1245589',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'НИЖНИЙ НОВГОРОД' => '1245589',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Нижний Новгород' => '1245589',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'нижегородская область' => '1245589',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Нижегородская область' => '1245589',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'нижегородская обл' => '1245589',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'нижегородская обл.' => '1245589',</div>
                                            </div>

                                            <div class="city-group mb-3">
                                                <div class="city-header fw-bold mb-1 text-primary" onclick="copyCityGroup(this)">Орёл (1126563) 📋</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'орёл' => '1126563',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ОРЁЛ' => '1126563',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Орёл' => '1126563',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'орел' => '1126563',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ОРЕЛ' => '1126563',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Орел' => '1126563',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'орловская область' => '1126563',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Орловская область' => '1126563',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'орловская обл' => '1126563',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'орловская обл.' => '1126563',</div>
                                            </div>

                                            <div class="city-group mb-3">
                                                <div class="city-header fw-bold mb-1 text-primary" onclick="copyCityGroup(this)">Пенза (3) 📋</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'пенза' => '3',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ПЕНЗА' => '3',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'пензенская область' => '3',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'пензенская обл' => '3',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'пензенская обл.' => '3',</div>
                                            </div>

                                            <div class="city-group mb-3">
                                                <div class="city-header fw-bold mb-1 text-primary" onclick="copyCityGroup(this)">Ростов-на-Дону (39209) 📋</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ростов-на-дону' => '39209',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'РОСТОВ-НА-ДОНУ' => '39209',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ростов на дону' => '39209',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ростов' => '39209',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ростовская область' => '39209',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ростовская обл' => '39209',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ростовская обл.' => '39209',</div>
                                            </div>

                                            <div class="city-group mb-3">
                                                <div class="city-header fw-bold mb-1 text-primary" onclick="copyCityGroup(this)">Саранск (376495) 📋</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'саранск' => '376495',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'САРАНСК' => '376495',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Саранск' => '376495',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'республика мордовия' => '376495',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Республика Мордовия' => '376495',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'респ. мордовия' => '376495',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Респ. Мордовия' => '376495',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'мордовия' => '376495',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Мордовия' => '376495',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'МОРДОВИЯ' => '376495',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'респ мордовия' => '376495',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Респ Мордовия' => '376495',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Репс. Мордовия' => '376495',</div>
                                            </div>

                                            <div class="city-group mb-3">
                                                <div class="city-header fw-bold mb-1 text-primary" onclick="copyCityGroup(this)">Тула (29407) 📋</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'тула' => '29407',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ТУЛА' => '29407',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Тула' => '29407',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'тульская область' => '29407',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'Тульская область' => '29407',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'тульская обл' => '29407',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'тульская обл.' => '29407',</div>
                                            </div>

                                            <div class="city-group mb-3">
                                                <div class="city-header fw-bold mb-1 text-primary" onclick="copyCityGroup(this)">Ульяновск (1195433) 📋</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ульяновск' => '1195433',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'УЛЬЯНОВСК' => '1195433',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ульяновская область' => '1195433',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ульяновская обл' => '1195433',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ульяновская обл.' => '1195433',</div>
                                            </div>

                                            <div class="city-group mb-3">
                                                <div class="city-header fw-bold mb-1 text-primary" onclick="copyCityGroup(this)">Ярославль (332377) 📋</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ярославль' => '332377',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ЯРОСЛАВЛЬ' => '332377',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ярославская область' => '332377',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ярославская обл' => '332377',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'ярославская обл.' => '332377',</div>
                                            </div>

                                            <div class="city-group mb-3">
                                                <div class="city-header fw-bold mb-1 text-primary" onclick="copyCityGroup(this)">Дистанты 📋</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'дистант калуга' => '97209',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'дистант ростов-на-дону' => '664825',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'дистант ростов' => '664825',</div>
                                                <div class="city-ref-item" onclick="copyCityMapping(this)">'дистант ярославль' => '664827',</div>
                                            </div>

                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Название поля с городом в квизе</label>
                                        <input type="text" name="city_field" class="form-control" value="<?=htmlspecialchars($config['city_field'] ?? '')?>" placeholder="Например: Выберите ваш город, Город, Регион">
                                        <small class="text-muted">Укажите название вопроса из квиза, откуда брать город. Для Tilda также распознаются: <code>gorod</code>, <code>city</code>, <code>город</code>, <code>region</code></small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Таблица соответствий (город → ID)</label>
                                        <textarea name="city_mapping" class="form-control city-mapping-area" id="cityMappingArea" rows="8" placeholder="'Брянск' => '264605',
'Калуга' => '5',
'Орёл' => '1126563',
'Орел' => '1126563',
'Тула' => '29407',
'Брянская обл.' => '264605',
'Калужская обл.' => '5',"><?=htmlspecialchars($cityMappingText)?></textarea>
                                        <small class="text-muted">Формат: <code>'Название города' => 'ID',</code> — по одному на строку</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Маппинг названий полей для комментариев -->
                            <div class="card bg-light mb-4">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">🏷️ Названия полей в комментариях (опционально)</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Таблица соответствий (переменная → читаемое название)</label>
                                        <textarea name="field_labels" class="form-control" rows="6" placeholder="'summa' => 'Сумма долга',
'comments' => 'Комментарий',
'gorod' => 'Город',
'cookies' => 'Cookies',"><?=htmlspecialchars($fieldLabelsText)?></textarea>
                                        <small class="text-muted">Если в форме переменная называется <code>Summa</code>, но в комментарии должно быть <code>Сумма долга</code> — укажите соответствие здесь.<br>Формат: <code>'переменная' => 'Читаемое название',</code></small>
                                    </div>
                                </div>
                            </div>

                            <!-- Маппинг регион → SOURCE_ID -->
                            <div class="card bg-light mb-4">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0">
                                        <input type="checkbox" name="source_by_region_enabled" id="sourceByRegionEnabled" class="form-check-input me-2" <?= !empty($config['source_by_region_enabled']) ? 'checked' : '' ?> onchange="toggleSourceByRegion()">
                                        <label for="sourceByRegionEnabled" class="form-check-label">🗺️ Маппинг регион → SOURCE_ID (опционально)</label>
                                    </h5>
                                </div>
                                <div class="card-body" id="sourceByRegionBlock" style="display: <?= !empty($config['source_by_region_enabled']) ? 'block' : 'none' ?>;">
                                    <div class="alert alert-warning mb-3">
                                        <strong>⚠️ Внимание:</strong> Если включено, SOURCE_ID будет <strong>перезаписываться</strong> на основе региона/города клиента. Основной SOURCE_ID (указанный выше) будет использоваться только если регион не найден в маппинге.
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Таблица соответствий (регион → SOURCE_ID)</label>
                                        <textarea name="source_by_region_mapping" class="form-control" rows="8" placeholder="'Республика Мордовия' => '376495',
'Пензенская область' => '3',
'Ростовская область' => '39209',
'Калуга' => '5',
'Тула' => '29407',"><?=htmlspecialchars($sourceByRegionText)?></textarea>
                                        <small class="text-muted">Формат: <code>'Название региона/города' => 'SOURCE_ID',</code><br>Сравнение регистронезависимое.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button name="action" value="save" class="btn btn-success btn-lg px-5">Сохранить</button>
                                <a href="index.php" class="btn btn-secondary btn-lg px-5 ms-3">Отмена</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php if ($isEdit): ?>
            <div class="col-lg-4">
                <!-- Песочница -->
                <div class="card mt-4 border-primary">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">🧪 Песочница</h4>
                    </div>
                    <div class="card-body">
                        <form id="sandboxForm" onsubmit="return sendTestRequest(event)">
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm" name="name" placeholder="Имя" value="Тестовый Лид">
                            </div>
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm" name="phone" placeholder="Телефон" value="+79990000000">
                            </div>
                            <div class="mb-2">
                                <input type="email" class="form-control form-control-sm" name="email" placeholder="Email" value="test@example.com">
                            </div>
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm" name="city" placeholder="Город" value="Москва">
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-sm">🚀 Отправить тест</button>
                            </div>
                        </form>
                        <div id="sandboxResult" class="mt-2 small text-muted" style="display:none;"></div>
                    </div>
                </div>

                <!-- Блок статистики -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">📊 Статистика интеграции</h4>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="p-3 rounded text-center" style="background: #e3f2fd;">
                                    <div class="h2 mb-0 text-primary"><?= $hookStats['total'] ?></div>
                                    <small class="text-muted">Всего лидов</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 rounded text-center" style="background: #e8f5e9;">
                                    <div class="h2 mb-0 text-success"><?= $hookStats['today'] ?></div>
                                    <small class="text-muted">За сегодня</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 rounded text-center" style="background: #ffebee;">
                                    <div class="h2 mb-0 text-danger" id="errorsCount"><?= $hookStats['errors'] ?></div>
                                    <small class="text-muted">Ошибок</small>
                                    <?php if ($hookStats['errors_reset_date']): ?>
                                    <div class="mt-1"><small class="text-muted">с <?= date('d.m.Y H:i', strtotime($hookStats['errors_reset_date'])) ?></small></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 rounded text-center d-flex flex-column justify-content-center h-100" style="background: #fff3e0;">
                                    <button type="button" class="btn btn-warning btn-sm" onclick="resetErrors('<?= htmlspecialchars($name) ?>')">
                                        🔄 Сбросить счётчик ошибок
                                    </button>
                                    <small class="text-muted mt-2">Сбросить и начать отсчёт заново</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($failedLeads)): ?>
                <!-- Блок с недоставленными лидами -->
                <div class="card mt-4 border-danger">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">📤 Недоставленные лиды (<?= count($failedLeads) ?>)</h4>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-light" onclick="retryAllFailed('<?= htmlspecialchars($name) ?>')">
                                🔄 Переотправить все
                            </button>
                        </div>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Дата</th>
                                        <th>Клиент</th>
                                        <th>Телефон</th>
                                        <th>Ошибка</th>
                                        <th>Попыток</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody id="failedLeadsTable">
                                    <?php foreach ($failedLeads as $fl): ?>
                                    <tr id="failed-row-<?= htmlspecialchars($fl['id']) ?>">
                                        <td><small><?= htmlspecialchars($fl['time'] ?? '—') ?></small></td>
                                        <td><?= htmlspecialchars($fl['input']['client']['name'] ?? '—') ?></td>
                                        <td><code><?= htmlspecialchars($fl['input']['client']['phone'] ?? '—') ?></code></td>
                                        <td><small class="text-danger"><?= htmlspecialchars(mb_substr($fl['error'] ?? '—', 0, 50)) ?></small></td>
                                        <td>
                                            <span class="badge bg-secondary"><?= intval($fl['attempts'] ?? 0) ?>/3</span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if (($fl['attempts'] ?? 0) < 3): ?>
                                                <button type="button" class="btn btn-outline-success" onclick="retryFailed('<?= htmlspecialchars($name) ?>', '<?= htmlspecialchars($fl['id']) ?>')" title="Переотправить">
                                                    🔄
                                                </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-outline-danger" onclick="deleteFailed('<?= htmlspecialchars($name) ?>', '<?= htmlspecialchars($fl['id']) ?>')" title="Удалить">
                                                    🗑️
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Блок с лидами -->
                <div class="card mt-4">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">🔗 Созданные лиды в Bitrix24</h4>
                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select form-select-sm" style="width: auto;" onchange="location.href='?name=<?= urlencode($name) ?>&leads='+this.value">
                                <?php foreach ([10, 20, 50, 100, 200] as $n): ?>
                                <option value="<?= $n ?>" <?= $leadsLimit == $n ? 'selected' : '' ?>>Последние <?= $n ?></option>
                                <?php endforeach; ?>
                            </select>
                            <a href="<?= "index.php?download_log=" . urlencode($name) ?>" class="btn btn-sm btn-outline-light">⬇️ Скачать лог</a>
                        </div>
                    </div>
                    <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                        <?php if (empty($displayLeads)): ?>
                            <div class="text-center text-muted py-3">Лидов пока нет</div>
                        <?php else: ?>
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Lead ID</th>
                                        <th>Дата/Время</th>
                                        <th>Ссылка</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($displayLeads as $lead): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($lead['id']) ?></strong></td>
                                        <td><?= htmlspecialchars($lead['time']) ?></td>
                                        <td>
                                            <a href="https://bankrot40.bitrix24.ru/crm/lead/details/<?= htmlspecialchars($lead['id']) ?>/" target="_blank" class="btn btn-sm btn-outline-primary">
                                                Открыть в Bitrix →
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <div class="col-12">
                <!-- Логи -->
                <div class="card mt-4">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Логи</h4>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="logToggle">
                            <label class="form-check-label text-white" for="logToggle">
                                <span id="logModeText">Краткий режим</span>
                            </label>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="logContainer">
                            <?= renderLogs($name, false) ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const bitrixOptions = `<?php 
            $opts = '<option value="">-- Выберите --</option>';
            foreach($bitrixFields as $k=>$v) $opts .= '<option value="'.$k.'">'.$v.'</option>';
            $opts .= '<option value="__CUSTOM__">+ Своё поле (UF_CRM_...)</option>';
            echo addslashes($opts);
        ?>`;

        let lastFocusedInput = null;

        // Отслеживаем последний активный input
        document.addEventListener('focusin', function(e) {
            if (e.target.matches('input[name="field[]"]')) {
                lastFocusedInput = e.target;
            }
        });

        function insertVar(varName) {
            if (lastFocusedInput) {
                const start = lastFocusedInput.selectionStart;
                const end = lastFocusedInput.selectionEnd;
                const text = lastFocusedInput.value;
                lastFocusedInput.value = text.substring(0, start) + varName + text.substring(end);
                lastFocusedInput.focus();
                lastFocusedInput.setSelectionRange(start + varName.length, start + varName.length);
            }
        }

        function toggleCustomField(select) {
            const customInput = select.parentElement.querySelector('.custom-field-input');
            if (select.value === '__CUSTOM__') {
                customInput.style.display = 'block';
                customInput.focus();
            } else {
                customInput.style.display = 'none';
                customInput.value = '';
            }
        }

        // Функция больше не нужна - используем отдельное поле custom_field[]

        function addRoute(field = '', bitrix = '') {
            const div = document.createElement('div');
            div.className = 'route-row d-flex align-items-center gap-3 mb-3';
            
            let selectHtml = bitrixOptions;
            let customDisplay = 'none';
            let customValue = '';
            
            if (bitrix && bitrix.startsWith('UF_')) {
                selectHtml = selectHtml.replace('value="__CUSTOM__"', 'value="__CUSTOM__" selected');
                customDisplay = 'block';
                customValue = bitrix;
            } else if (bitrix) {
                selectHtml = selectHtml.replace(`value="${bitrix}"`, `value="${bitrix}" selected`);
            }
            
            div.innerHTML = `<input type="text" name="field[]" class="form-control" value="${field}" placeholder="значение или переменная">
                <span class="fw-bold fs-4 text-primary">→</span>
                <select name="bitrix[]" class="form-select bitrix-select" onchange="toggleCustomField(this)">${selectHtml}</select>
                <input type="text" name="custom_field[]" class="form-control custom-field-input" style="display: ${customDisplay}; max-width: 180px;" placeholder="UF_CRM_XXXXX" value="${customValue}">
                <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">×</button>`;
            document.getElementById('routes').appendChild(div);
        }

        function clearRoutes() {
            document.getElementById('routes').innerHTML = '';
        }

        function copyUrl(t) {
            navigator.clipboard.writeText(t).then(()=>{
                const b = event.target;
                const o = b.textContent;
                b.textContent = 'Скопировано!';
                setTimeout(() => b.textContent = o, 2000);
            });
        }

        function toggleSourceByRegion() {
            const checkbox = document.getElementById('sourceByRegionEnabled');
            const block = document.getElementById('sourceByRegionBlock');
            block.style.display = checkbox.checked ? 'block' : 'none';
        }
        
        function toggleCityReference() {
            const ref = document.getElementById('cityReference');
            ref.style.display = ref.style.display === 'none' ? 'block' : 'none';
        }
        
        function copyCityMapping(element) {
            const text = element.textContent;
            const textarea = document.getElementById('cityMappingArea');
            
            if (textarea.value && !textarea.value.endsWith('\n')) {
                textarea.value += '\n';
            }
            textarea.value += text;
            
            element.style.background = '#198754';
            element.style.color = 'white';
            setTimeout(() => {
                element.style.background = '';
                element.style.color = '';
            }, 500);
        }

        function copyCityGroup(headerElement) {
            const group = headerElement.closest('.city-group');
            const items = group.querySelectorAll('.city-ref-item');
            const textarea = document.getElementById('cityMappingArea');
            
            if (textarea.value && !textarea.value.endsWith('\n')) {
                textarea.value += '\n';
            }
            
            items.forEach(item => {
                let text = item.textContent.trim();
                textarea.value += text + '\n';
            });
            
            const originalText = headerElement.textContent;
            headerElement.textContent = originalText.replace('📋', '✅');
            headerElement.classList.add('text-success');
            
            setTimeout(() => {
                headerElement.textContent = originalText;
                headerElement.classList.remove('text-success');
            }, 1000);
        }

        // Обработка формы теперь на сервере через custom_field[]

        // Обновление slug при вводе названия
        const titleInput = document.getElementById('titleInput');
        const slugPreview = document.getElementById('slugPreview');
        const serviceInput = document.getElementById('serviceInput');
        
        let slugTimeout;
        titleInput?.addEventListener('input', function() {
            clearTimeout(slugTimeout);
            const val = this.value.trim();
            if (!val) {
                if (slugPreview) slugPreview.textContent = 'введите название...';
                return;
            }
            
            slugTimeout = setTimeout(() => {
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'check_name=' + encodeURIComponent(val)
                })
                .then(r => r.json())
                .then(data => {
                    if (slugPreview) {
                        slugPreview.textContent = data.slug + '.php';
                        slugPreview.className = 'slug-preview ' + (data.unique ? 'text-success' : 'text-warning');
                    }
                });
            }, 300);
        });

        // Предустановки
        document.querySelectorAll('.preset-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const preset = this.dataset.preset;
                
                // Убираем active у всех
                document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Устанавливаем сервис
                if (serviceInput && preset !== 'custom') {
                    serviceInput.value = this.textContent.trim();
                    toggleVkSettings(serviceInput.value);
                }
                
                // Загружаем поля
                fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'get_preset=' + encodeURIComponent(preset)
                })
                .then(r => r.json())
                .then(fields => {
                    clearRoutes();
                    fields.forEach(f => addRoute(f.field, f.bitrix));
                });
            });
        });

        // Логи
        const logToggle = document.getElementById('logToggle');
        const logContainer = document.getElementById('logContainer');
        const logModeText = document.getElementById('logModeText');

        function updateLogs(detailed = false) {
            fetch('', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams({ 'log_view': detailed ? 'detailed' : 'short' })
            })
            .then(r => r.text())
            .then(html => {
                if (logContainer) logContainer.innerHTML = html || '<div class="text-center text-muted py-5">Логов нет</div>';
            });
        }

        logToggle?.addEventListener('change', function() {
            const detailed = this.checked;
            if (logModeText) logModeText.textContent = detailed ? 'Подробный режим' : 'Краткий режим';
            updateLogs(detailed);
        });

        setInterval(() => {
            if (document.visibilityState === 'visible' && logContainer) {
                updateLogs(logToggle?.checked ?? false);
            }
        }, 7000);

        // Сброс счётчика ошибок
        function resetErrors(hookName) {
            if (!confirm('Сбросить счётчик ошибок? Отсчёт начнётся заново с текущего момента.')) {
                return;
            }
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'reset_errors_hook=' + encodeURIComponent(hookName)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('errorsCount').textContent = '0';
                    alert('Счётчик ошибок сброшен! Дата сброса: ' + data.reset_date);
                    location.reload();
                } else {
                    alert('Ошибка при сбросе счётчика');
                }
            })
            .catch(err => {
                alert('Ошибка: ' + err.message);
            });
        }

        // Переотправка одного недоставленного лида
        function retryFailed(hookName, leadId) {
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳';
            btn.disabled = true;

            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'retry_failed_lead=1&hook_name=' + encodeURIComponent(hookName) + '&lead_id=' + encodeURIComponent(leadId)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Удаляем строку из таблицы
                    const row = document.getElementById('failed-row-' + leadId);
                    if (row) {
                        row.style.background = '#d4edda';
                        setTimeout(() => row.remove(), 500);
                    }
                    alert('✅ Лид успешно отправлен! Lead ID: ' + data.lead_id);
                } else {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    alert('❌ Ошибка: ' + (data.message || 'Не удалось отправить'));
                    location.reload(); // Обновим счётчик попыток
                }
            })
            .catch(err => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert('❌ Ошибка сети: ' + err.message);
            });
        }

        // Удаление недоставленного лида
        function deleteFailed(hookName, leadId) {
            if (!confirm('Удалить этот лид без отправки?')) return;

            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'delete_failed_lead=1&hook_name=' + encodeURIComponent(hookName) + '&lead_id=' + encodeURIComponent(leadId)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById('failed-row-' + leadId);
                    if (row) {
                        row.style.background = '#f8d7da';
                        setTimeout(() => row.remove(), 300);
                    }
                } else {
                    alert('Ошибка при удалении');
                }
            })
            .catch(err => {
                alert('Ошибка: ' + err.message);
            });
        }

        // Переотправка всех недоставленных лидов
        function retryAllFailed(hookName) {
            if (!confirm('Переотправить все недоставленные лиды?')) return;

            const rows = document.querySelectorAll('#failedLeadsTable tr');
            const leadIds = [];
            rows.forEach(row => {
                const id = row.id.replace('failed-row-', '');
                if (id) leadIds.push(id);
            });

            if (leadIds.length === 0) {
                alert('Нет лидов для переотправки');
                return;
            }

            let successCount = 0;
            let errorCount = 0;
            let processed = 0;

            leadIds.forEach(leadId => {
                fetch('index.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'retry_failed_lead=1&hook_name=' + encodeURIComponent(hookName) + '&lead_id=' + encodeURIComponent(leadId)
                })
                .then(r => r.json())
                .then(data => {
                    processed++;
                    if (data.success) {
                        successCount++;
                        const row = document.getElementById('failed-row-' + leadId);
                        if (row) row.remove();
                    } else {
                        errorCount++;
                    }

                    if (processed === leadIds.length) {
                        alert('Готово!\n✅ Успешно: ' + successCount + '\n❌ Ошибок: ' + errorCount);
                        if (errorCount > 0) location.reload();
                    }
                })
                .catch(() => {
                    processed++;
                    errorCount++;
                    if (processed === leadIds.length) {
                        alert('Готово!\n✅ Успешно: ' + successCount + '\n❌ Ошибок: ' + errorCount);
                        location.reload();
                    }
                });
            });
        }
        // Управление темами
        function setTheme(themeName) {
            document.documentElement.setAttribute('data-theme', themeName);
            localStorage.setItem('appTheme', themeName);
            
            // Если селект есть на странице, обновляем его значение
            const select = document.getElementById('themeSelect');
            if (select) select.value = themeName;
        }

        // Инициализация темы
        const savedTheme = localStorage.getItem('appTheme') || 'default';
        setTheme(savedTheme);

        // Песочница
        function sendTestRequest(e) {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('button');
            const resultDiv = document.getElementById('sandboxResult');
            const hookUrl = '<?= $hookUrl ?>';
            
            if (!hookUrl) {
                alert('URL хука не найден');
                return false;
            }

            const originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Отправка...';
            btn.disabled = true;
            resultDiv.style.display = 'none';
            
            const formData = new FormData(form);
            const params = new URLSearchParams();
            params.append('name', formData.get('name'));
            params.append('phone', formData.get('phone'));
            params.append('email', formData.get('email'));
            params.append('city', formData.get('city'));
            params.append('test', 'sandbox');

            fetch(hookUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            })
            .then(r => r.json())
            .then(data => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                resultDiv.style.display = 'block';
                
                if (data.status === 'ok' || data.result) {
                    const leadInfo = data.lead_id ? ('Lead ID: ' + data.lead_id) : 'Тест пройден';
                    resultDiv.innerHTML = '✅ <b>Успешно!</b><br>' + leadInfo;
                    resultDiv.className = 'mt-2 small text-success';
                    setTimeout(() => updateLogs(document.getElementById('logToggle')?.checked), 1500);
                } else {
                    resultDiv.innerHTML = '❌ <b>Ошибка:</b><br>' + (data.message || 'Неизвестная ошибка');
                    resultDiv.className = 'mt-2 small text-danger';
                }
            })
            .catch(err => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = '❌ <b>Ошибка сети:</b><br>' + err.message;
                resultDiv.className = 'mt-2 small text-danger';
            });
            
            return false;
        }

        function toggleVkSettings(serviceName) {
            const block = document.getElementById('vkSettingsBlock');
            if (block) {
                // Check if service name contains "ВКонтакте" or "Lead Forms"
                if (serviceName && (serviceName.includes('ВКонтакте') || serviceName.includes('Lead Forms') || serviceName.includes('vk'))) {
                    block.style.display = 'block';
                } else {
                    block.style.display = 'none';
                }
            }
        }

        // Initialize VK settings block visibility
        if (serviceInput) {
            toggleVkSettings(serviceInput.value);
            serviceInput.addEventListener('input', function() {
                toggleVkSettings(this.value);
            });
        }
    </script>
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
function renderLogs($hookName, $detailed = false) {
    $hookNum = $_GET['hook_num'] ?? '1'; // 1 или 2, по умолчанию 1
    $logFile = "logs/{$hookName}_hook{$hookNum}.log";
    
    echo '<div class="logs-section mt-4">';
    echo '<div class="logs-header d-flex justify-content-between align-items-center">';
    echo '<h5 class="mb-0">📜 Логи интеграции: <strong>' . htmlspecialchars($hookName) . '</strong></h5>';
    
    // 🔀 Переключатель хуков
    echo '<div class="btn-group btn-group-sm me-3">';
    echo '<a href="?name=' . urlencode($hookName) . '&hook_num=1" class="btn btn-outline-light ' . ($hookNum == '1' ? 'active' : '') . '">Хук #1</a>';
    echo '<a href="?name=' . urlencode($hookName) . '&hook_num=2" class="btn btn-outline-light ' . ($hookNum == '2' ? 'active' : '') . '">Хук #2</a>';
    echo '</div>';
    
    echo '<div>';
    echo '<a href="?download_log=' . urlencode($hookName) . '&hook_num=' . $hookNum . '" class="btn btn-sm btn-outline-light">⬇️ Скачать</a>';
    echo '<button class="btn btn-sm btn-outline-light ms-2" onclick="toggleLogs(\'' . $hookName . '\')">▼ Свернуть</button>';
    echo '</div></div>';
    
    echo '<div class="logs-content" id="logs-' . $hookName . '">';
    
    if (!file_exists($logFile)) {
        echo '<div class="text-center text-muted py-4">📭 Логов пока нет для хука #' . $hookNum . '</div>';
    } else {
        $content = file_get_contents($logFile);
        $blocks = preg_split('/={50,}/', $content);
        $blocks = array_filter(array_map('trim', $blocks));
        
        if (empty($blocks)) {
            echo '<div class="text-center text-muted py-4">Лог пуст</div>';
        } else {
            // Показываем последние 50 записей
            foreach (array_slice(array_reverse($blocks), 0, 50) as $block) {
                if (trim($block) === '') continue;
                
                $isError = preg_match('/Ошибка|Error|Failed|CURL error/i', $block);
                $isSuccess = preg_match('/Успешно|Lead ID[:\s]+\d+/i', $block);
                
                echo '<div class="log-entry">';
                
                // Время
                if (preg_match('/Время:\s*(.+)/', $block, $m)) {
                    echo '<div class="log-time">🕐 ' . htmlspecialchars(trim($m[1])) . '</div>';
                }
                
                // URL хука
                if (preg_match('/URL:\s*(.+)/', $block, $m)) {
                    $url = htmlspecialchars(trim($m[1]));
                    echo '<div class="small text-muted">🔗 ' . (strlen($url) > 70 ? substr($url, 0, 67) . '...' : $url) . '</div>';
                }
                
                // Статус
                if ($isSuccess) {
                    echo '<div class="log-success">✅ ' . htmlspecialchars(extractLogStatus($block)) . '</div>';
                    // Ссылка на лид в Битрикс
                    if (preg_match('/Lead ID[:\s]+(\d+)/', $block, $m)) {
                        echo '<div><a href="https://bankrot40.bitrix24.ru/crm/lead/details/' . $m[1] . '/" target="_blank" class="text-info">🔗 Открыть лид #' . $m[1] . '</a></div>';
                    }
                } elseif ($isError) {
                    echo '<div class="log-error">❌ ' . htmlspecialchars(extractLogError($block)) . '</div>';
                }
                
                echo '</div>';
            }
        }
    }
    echo '</div></div>';
}

// Вспомогательная: извлечение статуса из лога
function extractLogStatus($log) {
    if (preg_match('/Статус:\s*Успешно \(Lead ID:\s*(\d+)\)/i', $log, $m)) {
        return 'Успешно, Lead ID: ' . $m[1];
    }
    return 'Успешно';
}

// Вспомогательная: извлечение ошибки из лога
function extractLogError($log) {
    if (preg_match('/Статус:\s*(Ошибка[^\n]+)/i', $log, $m)) {
        return trim($m[1]);
    }
    if (preg_match('/Ошибка CURL:\s*([^\n]+)/i', $log, $m)) {
        return 'CURL: ' . trim($m[1]);
    }
    if (preg_match('/Ошибка Bitrix:\s*([^\n]+)/i', $log, $m)) {
        return 'Bitrix: ' . trim($m[1]);
    }
    return 'Неизвестная ошибка';
}
?>
