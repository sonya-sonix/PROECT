<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
  exit;
}

// 🔐 ВСТАВЬ СЮДА СВОИ ДАННЫЕ
$BOT_TOKEN = '8538957974:AAHNiyGdkvwSstt-vY18adhJ2H_1O-Apw0E';
$CHAT_ID  = '730041774';

// Данные формы
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $message === '') {
  echo json_encode(['success' => false, 'message' => 'Заполните обязательные поля']);
  exit;
}

// Сообщение
$text = "📩 <b>Новое сообщение с сайта</b>\n\n"
      . "👤 <b>Имя:</b> {$name}\n"
      . "📧 <b>Email:</b> {$email}\n"
      . "📞 <b>Телефон:</b> " . ($phone ?: 'не указан') . "\n\n"
      . "💬 <b>Сообщение:</b>\n{$message}";

// Telegram API
$url = "https://api.telegram.org/bot8538957974:AAHNiyGdkvwSstt-vY18adhJ2H_1O-Apw0E/sendMessage";

$data = [
  'chat_id' => $CHAT_ID,
  'text' => $text,
  'parse_mode' => 'HTML'
];

// cURL — 💪
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
  echo json_encode([
    'success' => false,
    'message' => 'Ошибка Telegram: ' . $error
  ]);
  exit;
}

echo json_encode([
  'success' => true,
  'message' => 'Сообщение отправлено!'
]);
