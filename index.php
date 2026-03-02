<?php 
require 'config.php';

// Функция подсчёта статистики из лога
function getHookStats($hookName) {
    $logFile = "logs/{$hookName}.log";
    $statsFile = "logs/{$hookName}_stats.json";
    $failedFile = "logs/{$hookName}_failed.json";
    $stats = [
        'total' => 0,
        'today' => 0,
        'yesterday' => 0,
        'errors' => 0,
        'failed' => 0, // Недоставленные лиды
        'leads' => [] // Последние лиды с ID
    ];
    
    // Считаем недоставленные лиды
    if (file_exists($failedFile)) {
        $failedLeads = json_decode(file_get_contents($failedFile), true);
        if (is_array($failedLeads)) {
            $stats['failed'] = count($failedLeads);
        }
    }
    
    // Загружаем дату сброса ошибок
    $resetTimestamp = 0;
    if (file_exists($statsFile)) {
        $savedStats = json_decode(file_get_contents($statsFile), true);
        if (!empty($savedStats['errors_reset_date'])) {
            $resetTimestamp = strtotime($savedStats['errors_reset_date']);
        }
    }
    
    if (!file_exists($logFile)) return $stats;
    
    $content = file_get_contents($logFile);
    $blocks = preg_split('/-{50,}/', $content);
    $today = date('d.m.Y');
    $yesterday = date('d.m.Y', strtotime('-1 day'));
    
    foreach ($blocks as $block) {
        if (trim($block) === '') continue;
        $stats['total']++;
        
        // Проверяем дату
        if (preg_match('/Время:\s*(\d{2}\.\d{2}\.\d{4})/', $block, $m)) {
            if ($m[1] === $today) {
                $stats['today']++;
            } elseif ($m[1] === $yesterday) {
                $stats['yesterday']++;
            }
        }
        
        // Проверяем ошибки (только после даты сброса)
        if (preg_match('/Статус: Ошибка|Ошибка CURL|Ошибка Bitrix|Ошибка HTTP/i', $block)) {
            if ($resetTimestamp > 0) {
                // Извлекаем полную дату и время для сравнения
                if (preg_match('/Время:\s*(\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2}:\d{2})/', $block, $timeMatch)) {
                    $blockTimestamp = strtotime(str_replace('.', '-', substr($timeMatch[1], 0, 10)) . substr($timeMatch[1], 10));
                    if ($blockTimestamp > $resetTimestamp) {
                        $stats['errors']++;
                    }
                }
            } else {
                $stats['errors']++;
            }
        }
        
        // Извлекаем Lead ID
        if (preg_match('/Lead ID[:\s]+(\d+)/', $block, $m)) {
            preg_match('/Время:\s*(.+)/', $block, $timeMatch);
            $stats['leads'][] = [
                'id' => $m[1],
                'time' => $timeMatch[1] ?? 'Неизвестно'
            ];
        }
    }
    
    // Последние лиды в обратном порядке (новые сверху)
    $stats['leads'] = array_reverse(array_slice($stats['leads'], -200));
    
    return $stats;
}

// Функция ротации логов (удаление записей старше 7 дней)
function rotateLogs() {
    $days = 7;
    $cutoff = time() - ($days * 86400);
    $logFiles = glob('logs/*.log');
    
    foreach ($logFiles as $logFile) {
        $content = file_get_contents($logFile);
        if (empty($content)) continue;
        
        // Разбиваем на блоки по разделителям (----- или =====)
        // Используем preg_split с PREG_SPLIT_DELIM_CAPTURE чтобы сохранить разделители не получится легко,
        // поэтому используем preg_match_all
        
        $newContent = "";
        $changed = false;
        $blocks = [];
        
        // Ищем блоки: контент + разделитель
        // Разделитель: 50+ тире или равно + переводы строк
        if (preg_match_all('/(.*?)(?:-{50,}|={50,})\s+/s', $content, $matches)) {
            foreach ($matches[0] as $block) {
                // Извлекаем дату из блока
                if (preg_match('/(\d{2}\.\d{2}\.\d{4})/', $block, $m)) {
                     $date = DateTime::createFromFormat('d.m.Y', $m[1]);
                     // Если дата распарсилась и она свежая (новее cutoff) - оставляем
                     if ($date && $date->getTimestamp() > $cutoff) {
                         $newContent .= $block;
                     } else {
                         $changed = true; // Старая запись, пропускаем
                     }
                } else {
                    // Блоки без даты (например заголовки или странные записи) оставляем
                    $newContent .= $block;
                }
            }
            
            // Если были изменения и новый контент не пустой (или если старый был не пустой, а новый пустой - значит все удалили)
            if ($changed) {
                file_put_contents($logFile, $newContent);
            }
        }
    }
}

// Запускаем ротацию раз в час
$rotationMarker = 'logs/last_rotation.txt';
if (!file_exists($rotationMarker) || (time() - filemtime($rotationMarker) > 3600)) {
    rotateLogs();
    if (!file_exists('logs')) mkdir('logs', 0777, true);
    touch($rotationMarker);
}

// Функция получения общих логов
function getAllLogs($limit = 100) {
    $allLogs = [];
    $logFiles = glob('logs/*.log');
    
    foreach ($logFiles as $logFile) {
        $hookName = pathinfo($logFile, PATHINFO_FILENAME);
        // Пропускаем security_alerts.log в общем списке
        if ($hookName === 'security_alerts') continue;
        
        $content = file_get_contents($logFile);
        $blocks = preg_split('/-{50,}/', $content);
        
        foreach ($blocks as $block) {
            if (trim($block) === '') continue;
            
            $time = '';
            if (preg_match('/Время:\s*(.+)/', $block, $m)) {
                $time = trim($m[1]);
            }
            
            // Извлекаем телефон
            $phone = '';
            if (preg_match('/\[phone\]\s*=>\s*(.+)/i', $block, $m)) {
                $phone = trim($m[1]);
            }
            
            $allLogs[] = [
                'hook' => $hookName,
                'time' => $time,
                'phone' => $phone,
                'timestamp' => strtotime(str_replace('.', '-', substr($time, 0, 10)) . substr($time, 10)),
                'content' => trim($block)
            ];
        }
    }
    
    // Сортируем по времени (новые сверху)
    usort($allLogs, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
    
    return array_slice($allLogs, 0, $limit);
}

// Функция получения алертов безопасности
function getSecurityAlerts() {
    $alertFile = 'logs/security_alerts.log';
    if (!file_exists($alertFile)) return [];
    
    $content = file_get_contents($alertFile);
    $blocks = preg_split('/={50,}/', $content);
    $alerts = [];
    
    foreach ($blocks as $block) {
        if (trim($block) === '') continue;
        
        $time = '';
        $integration = '';
        $method = '';
        $ip = '';
        
        if (preg_match('/^(\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2}:\d{2})/', trim($block), $m)) {
            $time = $m[1];
        }
        if (preg_match('/Интеграция:\s*(.+)/', $block, $m)) {
            $integration = trim($m[1]);
        }
        if (preg_match('/Обнаружен метод:\s*(.+)/', $block, $m)) {
            $method = trim($m[1]);
        }
        if (preg_match('/IP:\s*(.+)/', $block, $m)) {
            $ip = trim($m[1]);
        }
        
        if ($time && $method) {
            $alerts[] = [
                'time' => $time,
                'integration' => $integration,
                'method' => $method,
                'ip' => $ip,
                'content' => trim($block)
            ];
        }
    }
    
    return array_reverse($alerts);
}

// ============================================================================
// ФУНКЦИЯ: Отображение логов с переключателем хуков (1 или 2)
// ============================================================================
function renderLogs($hookName) {
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
    if (preg_match('/Статус:\s*(Ошибка[^\\n]+)/i', $log, $m)) {
        return trim($m[1]);
    }
    if (preg_match('/Ошибка CURL:\s*([^\\n]+)/i', $log, $m)) {
        return 'CURL: ' . trim($m[1]);
    }
    if (preg_match('/Ошибка Bitrix:\s*([^\\n]+)/i', $log, $m)) {
        return 'Bitrix: ' . trim($m[1]);
    }
    return 'Неизвестная ошибка';
}

// AJAX: Скачивание полного лога
if (isset($_GET['download_log'])) {
    $hookName = preg_replace('/[^a-z0-9_-]/i', '', $_GET['download_log']);
$hookNum = $_GET['hook_num'] ?? '1'; // 1 или 2
$logFile = "logs/{$hookName}_hook{$hookNum}.log";
    if (file_exists($logFile)) {
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $hookName . '_log_' . date('Y-m-d') . '.txt"');
        readfile($logFile);
        exit;
    }
    http_response_code(404);
    exit('Лог не найден');
}

// AJAX: Скачивание всех логов
if (isset($_GET['download_all_logs'])) {
    $allContent = "=== ВСЕ ЛОГИ === Дата выгрузки: " . date('d.m.Y H:i:s') . "\n\n";
$logFiles = glob('logs/*.log');
foreach ($logFiles as $logFile) {
    $hookName = pathinfo($logFile, PATHINFO_FILENAME);
    // Пропускаем файлы логов по хукам (_hook1, _hook2) в общем списке
    if (preg_match('/_hook[12]$/', $hookName)) continue;
    // Пропускаем security_alerts.log
    if ($hookName === 'security_alerts') continue;
        $allContent .= "\n\n" . str_repeat('=', 60) . "\n";
        $allContent .= "ИНТЕГРАЦИЯ: {$hookName}\n";
        $allContent .= str_repeat('=', 60) . "\n\n";
        $allContent .= file_get_contents($logFile);
    }
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="all_logs_' . date('Y-m-d_H-i') . '.txt"');
    echo $allContent;
    exit;
}

// AJAX: Сброс счётчика ошибок
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_errors'])) {
    $hookName = preg_replace('/[^a-z0-9_-]/i', '', $_POST['reset_errors']);
    $statsFile = "logs/{$hookName}_stats.json";
    $stats = file_exists($statsFile) ? json_decode(file_get_contents($statsFile), true) : [];
    $stats['errors_reset_date'] = date('Y-m-d H:i:s');
    file_put_contents($statsFile, json_encode($stats));
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// AJAX: Очистка алертов безопасности
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_security_alerts'])) {
    $alertFile = 'logs/security_alerts.log';
    if (file_exists($alertFile)) {
        // Архивируем перед очисткой
        $archiveFile = 'logs/security_alerts_' . date('Y-m-d_H-i-s') . '.log.bak';
        copy($alertFile, $archiveFile);
        file_put_contents($alertFile, '');
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// AJAX: Переотправка недоставленного лида
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['retry_failed_lead'])) {
    $hookName = preg_replace('/[^a-z0-9_-]/i', '', $_POST['hook_name'] ?? '');
    $leadId = $_POST['lead_id'] ?? '';
    $failedFile = "logs/{$hookName}_failed.json";
    
    if (!file_exists($failedFile)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Файл не найден']);
        exit;
    }
    
    $failedLeads = json_decode(file_get_contents($failedFile), true);
    if (!is_array($failedLeads)) $failedLeads = [];
    
    $leadIndex = null;
    $leadData = null;
    foreach ($failedLeads as $i => $lead) {
        if ($lead['id'] === $leadId) {
            $leadIndex = $i;
            $leadData = $lead;
            break;
        }
    }
    
    if ($leadData === null) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Лид не найден']);
        exit;
    }
    
    // Проверяем количество попыток
    if (($leadData['attempts'] ?? 0) >= 3) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Превышено максимальное количество попыток (3)']);
        exit;
    }
    
    // Отправляем в Bitrix
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => (defined('BITRIX_WEBHOOK_1') ? BITRIX_WEBHOOK_1 : '') . 'crm.lead.add',
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => http_build_query(['fields' => $leadData['fields'], 'params' => ['REGISTER_SONET_EVENT' => 'Y']]),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 15
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    
    $result = json_decode($response, true);
    
    if (!$curlError && !empty($result['result'])) {
        // Успешно - удаляем из списка
        array_splice($failedLeads, $leadIndex, 1);
        file_put_contents($failedFile, json_encode($failedLeads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'lead_id' => $result['result'], 'message' => 'Лид успешно создан: ' . $result['result']]);
    } else {
        // Неудача - увеличиваем счётчик
        $failedLeads[$leadIndex]['attempts'] = ($leadData['attempts'] ?? 0) + 1;
        $failedLeads[$leadIndex]['last_attempt'] = date('d.m.Y H:i:s');
        $failedLeads[$leadIndex]['last_error'] = $curlError ?: ($result['error_description'] ?? $result['error'] ?? 'Неизвестная ошибка');
        file_put_contents($failedFile, json_encode($failedLeads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Ошибка: ' . ($curlError ?: ($result['error_description'] ?? 'Неизвестная ошибка'))]);
    }
    exit;
}

// AJAX: Удаление недоставленного лида
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_failed_lead'])) {
    $hookName = preg_replace('/[^a-z0-9_-]/i', '', $_POST['hook_name'] ?? '');
    $leadId = $_POST['lead_id'] ?? '';
    $failedFile = "logs/{$hookName}_failed.json";
    
    if (file_exists($failedFile)) {
        $failedLeads = json_decode(file_get_contents($failedFile), true);
        if (is_array($failedLeads)) {
            $failedLeads = array_filter($failedLeads, fn($l) => $l['id'] !== $leadId);
            file_put_contents($failedFile, json_encode(array_values($failedLeads), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Собираем данные
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$basePath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = $basePath === '/' ? '' : rtrim($basePath, '/');
$baseUrl  = $protocol . '://' . $_SERVER['HTTP_HOST'] . $basePath;

$hooks = glob('srt/*.php');
$hooksData = [];
$idCounter = 1;

// Глобальная статистика
$globalStats = [
    'total' => 0,
    'today' => 0,
    'yesterday' => 0
];

foreach ($hooks as $file) {
    $name = pathinfo($file, PATHINFO_FILENAME);
    $json = "srt/{$name}.json";
    $config = file_exists($json) ? json_decode(file_get_contents($json), true) : [];
    $stats = getHookStats($name);
    
    // Суммируем глобальную статистику
    $globalStats['total'] += $stats['total'];
    $globalStats['today'] += $stats['today'];
    $globalStats['yesterday'] += $stats['yesterday'];
    
    $hooksData[] = [
        'id' => $idCounter++,
        'name' => $name,
        'title' => $config['title'] ?? $name,
        'service' => $config['service'] ?? '',
        'source_id' => $config['source_id'] ?? '',
        'url' => $baseUrl . '/srt/' . $name . '.php',
        'stats' => $stats
    ];
}
?>
<!DOCTYPE html>
<html lang="ru" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Менеджер хуков</title>
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

        body { background: var(--bg-gradient); min-height: 100vh; padding-top: 90px; transition: background 0.5s ease; }
        .navbar { box-shadow: 0 10px 30px rgba(0,0,0,0.2); background: var(--navbar-bg)!important; backdrop-filter: blur(10px); }
        
        /* Карточки (плитка) */
        .card { transition: all 0.3s ease; }
        .card:hover { transform: translateY(-8px); box-shadow: 0 25px 50px rgba(0,0,0,0.25)!important; }
        .card-header { background: var(--header-gradient); color: var(--card-header-text); }
        .hook-url { background: rgba(255,255,255,0.9); border-radius: 8px; padding: 8px; font-size: 0.8rem; word-break: break-all; font-family: 'JetBrains Mono', monospace; }
        
        /* Статистика */
        .stats-row { display: flex; gap: 10px; margin-bottom: 10px; }
        .stat-badge { padding: 4px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 600; }
        .stat-total { background: #e3f2fd; color: #1565c0; }
        .stat-today { background: #e8f5e9; color: #2e7d32; }
        .stat-errors { background: #ffebee; color: #c62828; }
        .stat-failed { background: #fff3e0; color: #e65100; }
        
        /* Алерты безопасности */
        .security-alert { background: linear-gradient(135deg, #d32f2f, #b71c1c); border-radius: 12px; margin-bottom: 20px; animation: pulse 2s infinite; }
        .security-alert .alert-header { padding: 15px 20px; color: white; }
        .security-alert .alert-body { background: #ffebee; padding: 15px; border-radius: 0 0 12px 12px; }
        @keyframes pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(211, 47, 47, 0.4); } 50% { box-shadow: 0 0 0 10px rgba(211, 47, 47, 0); } }
        
        /* Таблица */
        .table-view { display: none; }
        .table-view.active { display: block; }
        .grid-view.active { display: flex; }
        .grid-view { display: none; }
        
        .hooks-table { background: white; border-radius: 12px; overflow: hidden; }
        .hooks-table th { background: var(--header-gradient); color: white; border: none; font-size: 0.85rem; }
        .hooks-table td { vertical-align: middle; font-size: 0.9rem; }
        .hooks-table .url-cell { font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; max-width: 500px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .hooks-table { width: 100%; }
        .hooks-table .table { width: 100%; }
        
        /* Переключатель вида */
        .view-toggle { background: rgba(255,255,255,0.2); border-radius: 8px; padding: 4px; }
        .view-toggle .btn { padding: 6px 12px; border: none; background: transparent; color: white; }
        .view-toggle .btn.active { background: white; color: #667eea; border-radius: 6px; }
        
        /* Сортировка и Тема */
        .sort-select, .theme-select { background: rgba(255,255,255,0.2); border: none; color: white; border-radius: 8px; padding: 6px 12px; }
        .sort-select option, .theme-select option { color: #333; }
        
        /* Логи */
        .logs-section { background: #0d1117; border-radius: 12px; margin-top: 30px; }
        .logs-header { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; padding: 15px 20px; border-radius: 12px 12px 0 0; }
        .logs-content { max-height: 500px; overflow-y: auto; padding: 15px; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: #c9d1d9; }
        .log-entry { border-bottom: 1px solid #30363d; padding: 10px 0; }
        .log-entry:last-child { border-bottom: none; }
        .log-hook { color: #58a6ff; }
        .log-time { color: #8b949e; }
        .log-success { color: #3fb950; }
        .log-error { color: #f85149; }
        
        /* Кнопки */
        .btn-copy-integration { background: var(--btn-copy-bg); border: none; }
        .btn-copy-integration:hover { opacity: 0.9; }
    </style>
</head>
<body>

    <!-- Шапка -->
    <nav class="navbar navbar-dark fixed-top shadow-lg" style="background: rgba(0,0,0,0.3); backdrop-filter: blur(10px);">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold fs-4" href="index.php">Менеджер хуков</a>
            <div class="d-flex align-items-center gap-3">
                <!-- Сортировка -->
                <select class="sort-select" id="sortSelect" onchange="sortHooks()">
                    <option value="id">Сортировка: по ID</option>
                    <option value="title">Сортировка: по названию</option>
                    <option value="service">Сортировка: по сервису</option>
                </select>

                <!-- Тема -->
                <select class="theme-select" id="themeSelect" onchange="setTheme(this.value)">
                    <option value="default">🎨 Тема: Стандартная</option>
                    <option value="ocean">🌊 Тема: Океан</option>
                    <option value="nature">🌿 Тема: Природа</option>
                    <option value="sunset">🌅 Тема: Закат</option>
                    <option value="dark">🌑 Тема: Тёмная</option>
                </select>
                
                <!-- Переключатель вида -->
                <div class="view-toggle">
                    <button class="btn active" id="btnGrid" onclick="setView('grid')">▦ Плитка</button>
                    <button class="btn" id="btnTable" onclick="setView('table')">☰ Таблица</button>
                </div>
                
                <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#changeLoginModal">Сменить логин</button>
                <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#changeWebhookModal">Сменить вебхук</button>
                <button class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#changePassModal">Сменить пароль</button>
                <a href="logout.php" class="btn btn-outline-danger">Выйти</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <div class="text-center mb-4 mt-4">
            <h1 class="display-5 text-white fw-bold">Мои интеграции</h1>
            <p class="text-white-50">Bitrix24: <?= htmlspecialchars(defined('BITRIX_WEBHOOK_1') ? BITRIX_WEBHOOK_1 : 'Не задан') ?></p>
        </div>
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center gap-4 p-3 rounded-4 shadow bg-white bg-opacity-10 backdrop-blur">
                <a href="create.php" class="btn btn-light btn-lg px-5 py-3 shadow rounded-pill fw-bold text-primary">+ Новая интеграция</a>
                
                <div class="d-flex gap-3 text-white border-start ps-4 border-white border-opacity-25">
                    <div class="text-center">
                        <div class="small opacity-75">Сегодня</div>
                        <div class="fw-bold fs-4"><?= $globalStats['today'] ?></div>
                    </div>
                    <div class="text-center">
                        <div class="small opacity-75">Вчера</div>
                        <div class="fw-bold fs-4"><?= $globalStats['yesterday'] ?></div>
                    </div>
                    <div class="text-center">
                        <div class="small opacity-75">Всего</div>
                        <div class="fw-bold fs-4"><?= $globalStats['total'] ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Сбор статистики по недоставленным лидам
        $failedLeadsCount = 0;
        $failedHooks = [];
        foreach ($hooksData as $hook) {
            if (!empty($hook['stats']['failed']) && $hook['stats']['failed'] > 0) {
                $failedLeadsCount += $hook['stats']['failed'];
                $failedHooks[] = $hook;
            }
        }
        ?>

        <?php if ($failedLeadsCount > 0): ?>
        <div class="alert alert-warning shadow-lg mb-4 border-danger border-2" style="background: #fff3cd; color: #664d03;">
            <div class="d-flex align-items-center">
                <div class="display-4 me-3 text-danger">⚠️</div>
                <div>
                    <h4 class="alert-heading fw-bold text-danger">Внимание! Недоставленные лиды (<?= $failedLeadsCount ?>)</h4>
                    <p class="mb-1">В следующих интеграциях обнаружены ошибки отправки лидов. Необходимо проверить логи и повторить отправку.</p>
                </div>
            </div>
            <hr>
            <div class="row g-2">
                <?php foreach ($failedHooks as $hook): ?>
                <div class="col-md-4">
                    <div class="p-2 border rounded bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= htmlspecialchars($hook['title']) ?></strong>
                            <div class="small text-muted"><?= htmlspecialchars($hook['name']) ?></div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-danger rounded-pill mb-1"><?= $hook['stats']['failed'] ?> шт.</span><br>
                            <a href="create.php?name=<?= urlencode($hook['name']) ?>#failed-leads" class="btn btn-sm btn-outline-danger py-0" style="font-size: 0.75rem;">Исправить &rarr;</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php 
        // Блок алертов безопасности
        $securityAlerts = getSecurityAlerts();
        if (!empty($securityAlerts)): 
        ?>
        <div class="security-alert mb-4">
            <div class="alert-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">🚨 ВНИМАНИЕ! Обнаружены попытки несанкционированного доступа (<?= count($securityAlerts) ?>)</h5>
                <button class="btn btn-sm btn-outline-light" onclick="clearSecurityAlerts()">🗑️ Очистить все</button>
            </div>
            <div class="alert-body">
                <?php foreach (array_slice($securityAlerts, 0, 5) as $alert): ?>
                <div class="mb-2 p-2 bg-white rounded">
                    <strong class="text-danger"><?= htmlspecialchars($alert['time']) ?></strong> — 
                    Интеграция: <strong><?= htmlspecialchars($alert['integration']) ?></strong><br>
                    Попытка вызова метода: <code class="text-danger"><?= htmlspecialchars($alert['method']) ?></code><br>
                    IP: <code><?= htmlspecialchars($alert['ip']) ?></code>
                </div>
                <?php endforeach; ?>
                <?php if (count($securityAlerts) > 5): ?>
                <div class="text-muted small mt-2">... и ещё <?= count($securityAlerts) - 5 ?> записей</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($hooksData)): ?>
            <div class="text-center text-white"><h3>Пока нет интеграций</h3></div>
        <?php else: ?>
        
        <!-- Вид: Плитка -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 grid-view active" id="gridView">
            <?php foreach ($hooksData as $hook): ?>
            <div class="col hook-item" data-id="<?= $hook['id'] ?>" data-title="<?= htmlspecialchars($hook['title']) ?>" data-service="<?= htmlspecialchars($hook['service']) ?>">
                <div class="card h-100 shadow-lg">
                    <div class="card-header text-center py-3">
                        <!-- Верхняя строка: сервис слева, source_id справа -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <?php if ($hook['service']): ?>
                            <span class="badge bg-light text-dark"><?= htmlspecialchars($hook['service']) ?></span>
                            <?php else: ?>
                            <span></span>
                            <?php endif; ?>
                            <?php if ($hook['source_id']): ?>
                            <span class="badge bg-warning text-dark">Source: <?= htmlspecialchars($hook['source_id']) ?></span>
                            <?php endif; ?>
                        </div>
                        <!-- Название по центру -->
                        <h5 class="mb-0 text-white"><?= htmlspecialchars($hook['title']) ?></h5>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <!-- Статистика -->
                        <div class="stats-row">
                            <span class="stat-badge stat-total" title="Всего лидов">📊 <?= $hook['stats']['total'] ?></span>
                            <span class="stat-badge stat-today" title="За сегодня">📅 <?= $hook['stats']['today'] ?></span>
                            <span class="stat-badge stat-errors" title="Ошибки">⚠️ <?= $hook['stats']['errors'] ?></span>
                            <?php if ($hook['stats']['failed'] > 0): ?>
                            <span class="stat-badge stat-failed" title="Не доставлены">📤 <?= $hook['stats']['failed'] ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="hook-url mb-2"><?= htmlspecialchars($hook['url']) ?></div>
                        <button onclick="copyUrl('<?= addslashes($hook['url']) ?>')" class="btn btn-outline-primary btn-sm mb-2">📋 Скопировать URL</button>
                        <div class="mt-auto d-grid gap-2">
                            <a href="create.php?name=<?= urlencode($hook['name']) ?>" class="btn btn-primary btn-sm">✏️ Редактировать</a>
                            <div class="d-flex gap-2">
                                <a href="create.php?name=<?= urlencode($hook['name']) ?>&copy=1" class="btn btn-copy-integration text-white btn-sm flex-fill">📑 Копировать</a>
                                <a href="delete.php?name=<?= urlencode($hook['name']) ?>" class="btn btn-outline-danger btn-sm flex-fill" onclick="return confirm('Удалить интеграцию «<?= addslashes(htmlspecialchars($hook['title'])) ?>»?')">🗑️</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Вид: Таблица -->
        <div class="table-view" id="tableView">
            <div class="hooks-table shadow-lg" style="width: 100%; overflow-x: auto;">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="50">ID</th>
                            <th>Название</th>
                            <th>Сервис</th>
                            <th width="80">Source</th>
                            <th width="80">Всего</th>
                            <th width="80">Сегодня</th>
                            <th width="80">Ошибки</th>
                            <th>Ссылка на вебхук</th>
                            <th width="180">Действия</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php foreach ($hooksData as $hook): ?>
                        <tr class="hook-row" data-id="<?= $hook['id'] ?>" data-title="<?= htmlspecialchars($hook['title']) ?>" data-service="<?= htmlspecialchars($hook['service']) ?>">
                            <td><strong><?= $hook['id'] ?></strong></td>
                            <td><strong><?= htmlspecialchars($hook['title']) ?></strong></td>
                            <td><span class="badge bg-primary"><?= htmlspecialchars($hook['service']) ?: '-' ?></span></td>
                            <td><?php if ($hook['source_id']): ?><span class="badge bg-warning text-dark"><?= htmlspecialchars($hook['source_id']) ?></span><?php else: ?>-<?php endif; ?></td>
                            <td><span class="stat-badge stat-total">📊 <?= $hook['stats']['total'] ?></span></td>
                            <td><span class="stat-badge stat-today">📅 <?= $hook['stats']['today'] ?></span></td>
                            <td>
                                <span class="stat-badge stat-errors">⚠️ <?= $hook['stats']['errors'] ?></span>
                                <?php if ($hook['stats']['failed'] > 0): ?>
                                <span class="stat-badge stat-failed">📤 <?= $hook['stats']['failed'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="url-cell" title="<?= htmlspecialchars($hook['url']) ?>"><?= htmlspecialchars($hook['url']) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button onclick="copyUrl('<?= addslashes($hook['url']) ?>')" class="btn btn-outline-primary" title="Копировать URL">📋</button>
                                    <a href="create.php?name=<?= urlencode($hook['name']) ?>" class="btn btn-outline-success" title="Редактировать">✏️</a>
                                    <a href="create.php?name=<?= urlencode($hook['name']) ?>&copy=1" class="btn btn-outline-info" title="Копировать">📑</a>
                                    <a href="delete.php?name=<?= urlencode($hook['name']) ?>" class="btn btn-outline-danger" title="Удалить" onclick="return confirm('Удалить?')">🗑️</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php endif; ?>

        <!-- Блок общих логов -->
        <div class="logs-section mt-5">
            <div class="logs-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">📜 Общие логи (последние 100 записей)</h5>
                <div>
                    <a href="?download_all_logs=1" class="btn btn-sm btn-outline-light">⬇️ Скачать все логи</a>
                    <button class="btn btn-sm btn-outline-light ms-2" onclick="toggleLogs()">▼ Свернуть/Развернуть</button>
                </div>
            </div>
            <div class="logs-content" id="logsContent">
                <?php 
                $allLogs = getAllLogs(100);
                if (empty($allLogs)): 
                ?>
                    <div class="text-center text-muted py-4">Логов пока нет</div>
                <?php else: ?>
                    <?php foreach ($allLogs as $log): 
                        $isError = preg_match('/Ошибка|Error/i', $log['content']);
                        $isSuccess = strpos($log['content'], 'Успешно') !== false;
                    ?>
                    <div class="log-entry">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="log-hook">[<?= htmlspecialchars($log['hook']) ?>]</span>
                            <span class="log-time"><?= htmlspecialchars($log['time']) ?></span>
                        </div>
                        <div class="<?= $isError ? 'log-error' : ($isSuccess ? 'log-success' : '') ?>">
                            <?php
                            // Краткая информация
                            if (preg_match('/Lead ID[:\s]+(\d+)/', $log['content'], $m)) {
                                $phoneDisplay = !empty($log['phone']) ? ' - ' . htmlspecialchars($log['phone']) : '';
                                echo "✅ Lead ID: <a href='https://bankrot40.bitrix24.ru/crm/lead/details/{$m[1]}/' target='_blank' style='color: #58a6ff;'>{$m[1]}</a>{$phoneDisplay}";
                            } elseif ($isError) {
                                echo "❌ Ошибка";
                                if (preg_match('/Ошибка[:\s]+(.+)/i', $log['content'], $m)) {
                                    echo ": " . htmlspecialchars(substr($m[1], 0, 100));
                                }
                            } else {
                                echo "📝 Запрос обработан";
                            }
                            ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Уведомление -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="liveToast" class="toast" role="alert">
            <div class="toast-header">
                <strong class="me-auto">Уведомление</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>

    <!-- Модалки смены -->
    <div class="modal fade" id="changeLoginModal"><div class="modal-dialog"><div class="modal-content">
        <form id="formLogin">
            <div class="modal-header"><h5>Сменить логин</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><input type="text" name="newlogin" class="form-control" placeholder="Новый логин" required></div>
            <div class="modal-footer"><button type="submit" class="btn btn-warning">Сменить</button></div>
        </form>
    </div></div></div>

    <div class="modal fade" id="changePassModal"><div class="modal-dialog"><div class="modal-content">
        <form id="formPass">
            <div class="modal-header"><h5>Сменить пароль</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><input type="password" name="newpass" class="form-control" placeholder="Новый пароль" required minlength="6"></div>
            <div class="modal-footer"><button type="submit" class="btn btn-success">Сменить</button></div>
        </form>
    </div></div></div>

    <div class="modal fade" id="changeWebhookModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Сменить вебхуки</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Вебхук #1 привязан: <strong><?= defined('WEBHOOK_SET_DATE_1') ? WEBHOOK_SET_DATE_1 : 'не задан' ?></strong></p>
                <p class="text-muted small">Текущий #1: <?= htmlspecialchars(defined('BITRIX_WEBHOOK_1') ? BITRIX_WEBHOOK_1 : '') ?></p>
                <hr>
                <p>Вебхук #2 привязан: <strong><?= defined('WEBHOOK_SET_DATE_2') ? WEBHOOK_SET_DATE_2 : 'не задан' ?></strong></p>
                <p class="text-muted small">Текущий #2: <?= htmlspecialchars(defined('BITRIX_WEBHOOK_2') ? BITRIX_WEBHOOK_2 : '(пусто)') ?></p>
                <hr>
                <form id="formWebhook">
                    <div class="mb-3">
                        <label class="form-label">Вебхук Битрикс #1 (основной)</label>
                        <input type="url" name="webhook1" class="form-control" 
                               value="<?= defined('BITRIX_WEBHOOK_1') ? BITRIX_WEBHOOK_1 : '' ?>" 
                               placeholder="https://portal.bitrix24.ru/rest/USER_ID/TOKEN/" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Вебхук Битрикс #2 (дополнительный, опционально)</label>
                        <input type="url" name="webhook2" class="form-control" 
                               value="<?= defined('BITRIX_WEBHOOK_2') ? BITRIX_WEBHOOK_2 : '' ?>" 
                               placeholder="https://second-portal.bitrix24.ru/rest/.../">
                        <small class="text-muted">Оставьте пустым, если не нужен второй хук</small>
                    </div>
                    <button type="submit" class="btn btn-info w-100">Обновить вебхуки</button>
                </form>
            </div>
        </div>
    </div>
</div>

    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        const toastEl = document.getElementById('liveToast');
        const toast = new bootstrap.Toast(toastEl);

        function showToast(message, isSuccess = true) {
            toastEl.querySelector('.toast-header').className = 'toast-header ' + (isSuccess ? 'bg-success text-white' : 'bg-danger text-white');
            toastEl.querySelector('.toast-body').innerHTML = message;
            toast.show();
        }

        // Переключение вида
        function setView(view) {
            localStorage.setItem('hooksView', view);
            document.getElementById('gridView').classList.toggle('active', view === 'grid');
            document.getElementById('tableView').classList.toggle('active', view === 'table');
            document.getElementById('btnGrid').classList.toggle('active', view === 'grid');
            document.getElementById('btnTable').classList.toggle('active', view === 'table');
        }

        // Восстановление вида из localStorage
        const savedView = localStorage.getItem('hooksView') || 'grid';
        setView(savedView);

        // Сортировка
        function sortHooks() {
            const sortBy = document.getElementById('sortSelect').value;
            localStorage.setItem('hooksSort', sortBy); // Сохраняем в localStorage
            
            const gridItems = [...document.querySelectorAll('.hook-item')];
            const tableRows = [...document.querySelectorAll('.hook-row')];
            
            const sortFn = (a, b) => {
                if (sortBy === 'id') {
                    return parseInt(a.dataset.id) - parseInt(b.dataset.id);
                } else if (sortBy === 'title') {
                    return a.dataset.title.localeCompare(b.dataset.title, 'ru');
                } else if (sortBy === 'service') {
                    return a.dataset.service.localeCompare(b.dataset.service, 'ru');
                }
            };
            
            gridItems.sort(sortFn);
            tableRows.sort(sortFn);
            
            const gridContainer = document.getElementById('gridView');
            const tableBody = document.getElementById('tableBody');
            
            gridItems.forEach(item => gridContainer.appendChild(item));
            tableRows.forEach(row => tableBody.appendChild(row));
        }
        
        // Восстановление сортировки из localStorage
        const savedSort = localStorage.getItem('hooksSort') || 'id';
        document.getElementById('sortSelect').value = savedSort;
        if (savedSort !== 'id') sortHooks();
        
        // Двойной клик на интеграцию — открыть редактирование
        document.querySelectorAll('.hook-item, .hook-row').forEach(el => {
            el.addEventListener('dblclick', function() {
                const name = this.querySelector('a[href*="create.php?name="]')?.href?.match(/name=([^&]+)/)?.[1];
                if (name) {
                    window.location.href = 'create.php?name=' + name;
                }
            });
            el.style.cursor = 'pointer';
        });
        
        // Очистка алертов безопасности
        function clearSecurityAlerts() {
            if (!confirm('Очистить все алерты безопасности?')) return;
            
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'clear_security_alerts=1'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }

        // Сворачивание логов
        function toggleLogs() {
            const content = document.getElementById('logsContent');
            content.style.display = content.style.display === 'none' ? 'block' : 'none';
        }

        // Формы
        document.querySelectorAll('#formLogin, #formPass, #formWebhook').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const data = new FormData(this);
                const action = this.closest('.modal').id === 'changeWebhookModal' ? 'change_webhook.php' :
                              this.closest('.modal').id === 'changeLoginModal' ? 'change_login.php' : 'change_password.php';

                fetch(action, { method: 'POST', body: data })
                .then(r => r.json())
                .then(res => {
                    showToast(res.message, res.success);
                    if (res.success) {
                        bootstrap.Modal.getInstance(this.closest('.modal')).hide();
                        if (action === 'change_webhook.php') setTimeout(() => location.reload(), 1500);
                    }
                })
                .catch(() => showToast('Ошибка сервера', false));
            });
        });

        function copyUrl(t) {
            navigator.clipboard.writeText(t).then(() => {
                showToast('URL скопирован!', true);
            }).catch(() => {
                const input = document.createElement('input');
                input.value = t;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                showToast('URL скопирован!', true);
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
    </script>
</body>
</html>
