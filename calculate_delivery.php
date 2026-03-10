<?php
require_once 'db.php';
header('Content-Type: application/json');

$street = $_POST['street'] ?? '';
$building = $_POST['building'] ?? '';

if (empty($street) || empty($building)) {
    echo json_encode(['success' => false, 'message' => 'Введите улицу и дом']);
    exit;
}

// Координаты вашей кондитерской (Асфальтная 63А)
$my_lat = 53.717848; 
$my_lng = 23.867393;

// Попробуем использовать этот ключ (если он не работает, ниже инструкция как создать свой)
$api_key = '64ab75c6-ac2d-4b14-ab70-d2fa7dab8b8c'; 

// Формируем запрос: Гродно + улица + дом
$address_query = "Беларусь, Гродно, " . trim($street) . " " . trim($building);
$url = "https://geocode-maps.yandex.ru/1.x/?apikey=" . $api_key . "&geocode=" . urlencode($address_query) . "&format=json";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
// ВАЖНО ДЛЯ LARAGON: отключаем проверку SSL сертификата
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');

$response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['success' => false, 'message' => 'Ошибка сети: ' . $curl_error]);
    exit;
}

$data = json_decode($response, true);

// Проверяем структуру ответа
if (isset($data['response']['GeoObjectCollection']['featureMember'][0])) {
    $geoObject = $data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject'];
    
    // Получаем координаты найденного объекта
    $pos = $geoObject['Point']['pos'];
    list($lng, $lat) = explode(' ', $pos);
    
    // Проверяем, действительно ли это Гродно (на всякий случай)
    // Координаты Гродно примерно: lat 53.6, lng 23.8
    if ($lat < 53.0 || $lat > 54.0 || $lng < 23.0 || $lng > 25.0) {
        echo json_encode(['success' => false, 'message' => 'Адрес найден, но он находится за пределами Гродно.']);
        exit;
    }

    // Расчет расстояния (Haversine)
    function getDistance($lat1, $lon1, $lat2, $lon2) {
        $earth_radius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2)**2;
        return 2 * $earth_radius * atan2(sqrt($a), sqrt(1-$a));
    }

    $dist = getDistance($my_lat, $my_lng, $lat, $lng);
    
    // Расчет цены
    $cost = 7; // Минималка
    if ($dist > 3) {
        $cost = 7 + (($dist - 3) * 1.5); // 1.5р за каждый км свыше 3-х
    }
    $cost = round($cost, 1);

    echo json_encode([
        'success' => true,
        'distance' => round($dist, 2),
        'delivery_cost' => $cost,
        'address' => $geoObject['metaDataProperty']['GeocoderMetaData']['text']
    ]);
} else {
    // Если Яндекс вообще ничего не вернул
    echo json_encode(['success' => false, 'message' => 'Яндекс не смог найти этот адрес. Попробуйте написать название улицы без сокращений (например, Пушкина вместо Пуш.).']);
}