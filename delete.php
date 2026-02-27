<?php
require 'config.php';
$name = $_GET['name'] ?? '';
if ($name && preg_match('/^[a-z0-9_-]+$/i', $name)) {
    @unlink("srt/{$name}.php");
    @unlink("srt/{$name}.json");
    @unlink("logs/{$name}.log");
}
header('Location: index.php');
exit;