<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['products']) || !isset($data['delivery_day']) || !isset($data['delivery_period']) || !isset($data['address']) || !isset($data['phone']) || !isset($data['subscription_duration'])) {
    http_response_code(400);
    echo json_encode(array('error' => 'Недостаточно данных для подписки.'));
    exit;
}

$host = 'localhost';
$db = 'arbuz_api';
$user = 'root';
$password = '';

$pdo = new PDO("mysql:host=$host;dbname=$db", $user, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$selectedProducts = $data['products'];
$unavailableProducts = array();

foreach ($selectedProducts as $productId) {
    $query = "SELECT * FROM products WHERE id = :productId AND available = 1";
    $statement = $pdo->prepare($query);
    $statement->bindParam(':productId', $productId, PDO::PARAM_INT);
    $statement->execute();

    if ($statement->rowCount() === 0) {
        $unavailableProducts[] = $productId;
    }
}

if (!empty($unavailableProducts)) {
    http_response_code(400);
    echo json_encode(array('error' => 'Выбранные продукты недоступны: ' . implode(', ', $unavailableProducts)));
    exit;
}

$products = implode(',', $selectedProducts);
$deliveryDay = $data['delivery_day'];
$deliveryPeriod = $data['delivery_period'];
$address = $data['address'];
$phone = $data['phone'];
$subscriptionDuration = $data['subscription_duration'];

$query = "INSERT INTO subscriptions (products, delivery_day, delivery_period, address, phone, subscription_duration) VALUES (:products, :deliveryDay, :deliveryPeriod, :address, :phone, :subscriptionDuration)";
$statement = $pdo->prepare($query);
$statement->bindParam(':products', $products);
$statement->bindParam(':deliveryDay', $deliveryDay);
$statement->bindParam(':deliveryPeriod', $deliveryPeriod);
$statement->bindParam(':address', $address);
$statement->bindParam(':phone', $phone);
$statement->bindParam(':subscriptionDuration', $subscriptionDuration);
$statement->execute();

$pdo = null;

echo json_encode(array('success' => 'Подписка оформлена успешно.'));

/*
 CREATE TABLE products (
  id INT(11) AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  weight DECIMAL(10,2) NOT NULL,
  available TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE subscriptions (
  id INT(11) AUTO_INCREMENT PRIMARY KEY,
  products TEXT NOT NULL,
  delivery_day VARCHAR(20) NOT NULL,
  delivery_period VARCHAR(20) NOT NULL,
  address VARCHAR(200) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  subscription_duration INT(11) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

{
  "products": [1, 2, 3],
  "delivery_day": "Monday",
  "delivery_period": "morning",
  "address": "123 Main St",
  "phone": "123-456-7890",
  "subscription_duration": 4
}

{
  "products": [2, 3],
  "delivery_day": "Wednesday",
  "delivery_period": "afternoon",
  "address": "456 Elm St",
  "phone": "987-654-3210",
  "subscription_duration": 6
}
 */
?>
