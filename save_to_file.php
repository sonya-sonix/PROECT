<?php

date_default_timezone_set('Europe/Minsk');

$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    $date = date("d.m.Y H:i");

    $record =
        "Дата: $date\n" .
        "Товар: {$data['name']}\n" .
        "Цена за шт: {$data['price']} BYN\n" .
        "Количество: {$data['count']}\n" .
        "Пожелания: {$data['wishes']}\n" .
        "Итого: {$data['total']} BYN\n" .
        "-------------------------\n";

    file_put_contents("orders.txt", $record, FILE_APPEND);
}
