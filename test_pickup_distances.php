<?php
$test_streets = [
    'Асфальтная',
    'Барановичская',
    'Советская',
    'Ожешко',
    'Фолюш',
    'Девятовка',
    'Колбасино',
    'Грандичи',
    'Пестрака',
    'Томина',
    'Славинского',
    'Ольшанка',
    'Вишневец',
    'Скидель'
];

echo "<h2>📍 СТОИМОСТЬ ДОСТАВКИ ОТ АСФАЛЬТНОЙ 63А</h2>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr style='background: #d8737f; color: white;'><th>Улица</th><th>Район</th><th>Стоимость</th></tr>";

foreach ($test_streets as $street) {
    $street_lower = mb_strtolower($street);
    $cost = 15;
    $zone = 'Ольшанка/Вишневец';
    
    if (strpos($street_lower, 'асфальтная') !== false || strpos($street_lower, 'барановичская') !== false) {
        $cost = 7;
        $zone = 'Рядом с кондитерской';
    }
    elseif (strpos($street_lower, 'советская') !== false || strpos($street_lower, 'ожешко') !== false) {
        $cost = 9;
        $zone = 'Центр';
    }
    elseif (strpos($street_lower, 'фолюш') !== false || strpos($street_lower, 'девятовка') !== false) {
        $cost = 10;
        $zone = 'Фолюш/Девятовка';
    }
    elseif (strpos($street_lower, 'колбасино') !== false || strpos($street_lower, 'грандичи') !== false) {
        $cost = 12;
        $zone = 'Колбасино/Грандичи';
    }
    elseif (strpos($street_lower, 'скидель') !== false) {
        $cost = 20;
        $zone = 'Пригород';
    }
    
    $color = $cost == 7 ? '#d4edda' : ($cost == 9 ? '#fff3cd' : ($cost <= 12 ? '#f8d7da' : '#f5c6cb'));
    echo "<tr style='background: $color'>";
    echo "<td><strong>$street</strong></td>";
    echo "<td>$zone</td>";
    echo "<td align='center'><strong style='font-size: 1.1rem;'>$cost BYN</strong></td>";
    echo "</tr>";
}
echo "</table>";
?>