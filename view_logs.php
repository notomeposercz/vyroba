<?php
// Jednoduchý nástroj pro sledování log souborů
session_start();
require_once 'auth.php';

// Vyžadovat admin přístup
requireRole(['admin']);

$logFiles = [
    'debug.log' => 'Debug Log',
    'php_errors.log' => 'PHP Errors'
];

$selectedLog = $_GET['log'] ?? 'debug.log';
$lines = intval($_GET['lines'] ?? 50);
$auto_refresh = isset($_GET['auto_refresh']);

function tailFile($filename, $lines = 50) {
    if (!file_exists($filename)) {
        return "Log soubor $filename neexistuje.";
    }
    
    $file = file($filename);
    if ($file === false) {
        return "Nelze číst soubor $filename.";
    }
    
    return implode('', array_slice($file, -$lines));
}

if (isset($_GET['clear']) && $_GET['clear'] === $selectedLog) {
    file_put_contents($selectedLog, '');
    header("Location: view_logs.php?log=$selectedLog");
    exit;
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Viewer - Výrobní systém</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 20px;
            background: #1e1e1e;
            color: #d4d4d4;
        }
        
        .header {
            background: #2d2d30;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .header select, .header input, .header button {
            padding: 8px 12px;
            background: #3c3c3c;
            border: 1px solid #555;
            color: #d4d4d4;
            border-radius: 3px;
        }
        
        .header button {
            background: #007acc;
            cursor: pointer;
        }
        
        .header button:hover {
            background: #005a9e;
        }
        
        .header button.danger {
            background: #d73a49;
        }
        
        .header button.danger:hover {
            background: #b31d28;
        }
        
        .log-content {
            background: #1e1e1e;
            border: 1px solid #3c3c3c;
            padding: 15px;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 70vh;
            overflow-y: auto;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .log-line {
            margin-bottom: 2px;
        }
        
        .log-line.error {
            color: #f48771;
        }
        
        .log-line.warning {
            color: #ddb96c;
        }
        
        .log-line.debug {
            color: #9cdcfe;
        }
        
        .log-line.info {
            color: #4ec9b0;
        }
        
        .log-line.fatal {
            color: #f44747;
            font-weight: bold;
        }
        
        .timestamp {
            color: #888;
        }
        
        .level {
            font-weight: bold;
            margin: 0 5px;
        }
    </style>
    <?php if ($auto_refresh): ?>
    <meta http-equiv="refresh" content="5">
    <?php endif; ?>
</head>
<body>
    <div class="header">
        <form method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <label>Log soubor:</label>
            <select name="log" onchange="this.form.submit()">
                <?php foreach ($logFiles as $file => $name): ?>
                    <option value="<?= $file ?>" <?= $selectedLog === $file ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
            
            <label>Řádků:</label>
            <input type="number" name="lines" value="<?= $lines ?>" min="10" max="1000" style="width: 80px;">
            
            <label>
                <input type="checkbox" name="auto_refresh" <?= $auto_refresh ? 'checked' : '' ?>>
                Auto refresh (5s)
            </label>
            
            <button type="submit">Aktualizovat</button>
            <button type="button" onclick="location.href='?log=<?= $selectedLog ?>&clear=<?= $selectedLog ?>'" class="danger">Smazat log</button>
            <button type="button" onclick="location.href='index.php'">Zpět na hlavní stránku</button>
        </form>
    </div>
    
    <div class="log-content">
        <?php
        $content = tailFile($selectedLog, $lines);
        
        // Colorize log lines
        $lines_array = explode("\n", $content);
        foreach ($lines_array as $line) {
            if (empty(trim($line))) continue;
            
            $class = '';
            if (strpos($line, '[ERROR]') !== false) $class = 'error';
            elseif (strpos($line, '[WARNING]') !== false) $class = 'warning';
            elseif (strpos($line, '[DEBUG]') !== false) $class = 'debug';
            elseif (strpos($line, '[INFO]') !== false) $class = 'info';
            elseif (strpos($line, '[FATAL]') !== false) $class = 'fatal';
            
            echo "<div class='log-line $class'>" . htmlspecialchars($line) . "</div>";
        }
        ?>
    </div>
</body>
</html>
