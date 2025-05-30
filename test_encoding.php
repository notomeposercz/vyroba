<?php
// Test skript pro diagnostiku kódování
echo "PHP verze: " . PHP_VERSION . "\n";
echo "Dostupná kódování:\n";

// Test mb_list_encodings
if (function_exists('mb_list_encodings')) {
    $encodings = mb_list_encodings();
    echo "MB encodings: " . implode(', ', array_slice($encodings, 0, 10)) . "...\n";
} else {
    echo "mb_list_encodings není dostupná\n";
}

// Test iconv
if (function_exists('iconv')) {
    echo "iconv je dostupná\n";
    $test = @iconv('WINDOWS-1250', 'UTF-8', 'test');
    echo "iconv WINDOWS-1250 test: " . ($test !== false ? "OK" : "FAIL") . "\n";
} else {
    echo "iconv není dostupná\n";
}

// Test souboru
$testFiles = glob('*.csv');
if (!empty($testFiles)) {
    $file = $testFiles[0];
    echo "Testování souboru: $file\n";
    
    $content = file_get_contents($file);
    $firstLine = substr($content, 0, 100);
    
    echo "První řádek (hex): " . bin2hex($firstLine) . "\n";
    echo "Je UTF-8: " . (mb_check_encoding($content, 'UTF-8') ? "ANO" : "NE") . "\n";
}
?>