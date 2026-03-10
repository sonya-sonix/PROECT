<?php
$host = 'localhost';          // или твой хост (чаще localhost)
$dbname = 'mydb';            // имя твоей БД
$username = 'root';          // твой пользователь БД
$password = 's0p0ck1n';             // твой пароль (в Laragon обычно пусто)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}
?>