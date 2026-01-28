<?php
// Тест на MySQL връзка - ВРЕМЕНЕН ФАЙЛ

echo "Тестване на MySQL връзката...\n\n";

// Опция 1: Без парола
echo "1️⃣ Опит без парола:\n";
$test1 = @mysqli_connect("localhost", "root", "", "mydatabase");
if ($test1) {
    echo "✅ УСПЕХ! Парола е ПРАЗНА\n";
    mysqli_close($test1);
} else {
    echo "❌ Неуспешно\n";
}

echo "\n2️⃣ Опит със стара парола (General123#):\n";
$test2 = @mysqli_connect("localhost", "root", "General123#", "mydatabase");
if ($test2) {
    echo "✅ УСПЕХ! Парола е 'General123#'\n";
    mysqli_close($test2);
} else {
    echo "❌ Неуспешно\n";
}

echo "\n📌 Препоръка: Отворете phpMyAdmin и проверете MySQL настройките!\n";
?>
