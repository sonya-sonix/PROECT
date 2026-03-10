<?php
$upload_dir = 'uploads/designs/';

echo "<h2>🔍 Проверка папки загрузок</h2>";

if (file_exists($upload_dir)) {
    echo "✅ Папка существует: " . realpath($upload_dir) . "<br>";
    
    // Проверяем права на запись
    if (is_writable($upload_dir)) {
        echo "✅ Папка доступна для записи<br>";
    } else {
        echo "❌ Папка НЕ доступна для записи!<br>";
    }
    
    // Содержимое папки
    $files = scandir($upload_dir);
    echo "Файлов в папке: " . (count($files) - 2) . "<br>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "📸 <a href='$upload_dir$file' target='_blank'>$file</a><br>";
        }
    }
} else {
    echo "❌ Папка НЕ существует! Создаю...<br>";
    if (mkdir($upload_dir, 0777, true)) {
        echo "✅ Папка создана!<br>";
    } else {
        echo "❌ Не удалось создать папку!<br>";
    }
}

// Проверяем права на корневую папку
echo "<h3>Права на папки:</h3>";
echo "uploads/: " . substr(sprintf('%o', fileperms('uploads')), -4) . "<br>";
if (file_exists('uploads/designs')) {
    echo "uploads/designs/: " . substr(sprintf('%o', fileperms('uploads/designs')), -4) . "<br>";
}
?>