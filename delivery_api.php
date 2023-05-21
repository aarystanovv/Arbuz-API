<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "arbuz_delivery";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    $productIds = $data["productIds"];
    $products = explode(",", $productIds);
    $invalidProducts = array();

    foreach ($products as $productId) {
        $sql = "SELECT id FROM products WHERE id = $productId AND available = TRUE";
        $result = $conn->query($sql);

        if ($result->num_rows != 1) {
            $invalidProducts[] = $productId;
        }
    }

    if (!empty($invalidProducts)) {
        $response = array("error" => "Выбранные продукты недоступны: " . implode(", ", $invalidProducts));
        header("Content-Type: application/json");
        echo json_encode($response);
        exit();
    }

    $deliveryDay = $data["deliveryDay"];
    $deliveryPeriod = $data["deliveryPeriod"];
    $deliveryAddress = $data["deliveryAddress"];
    $customerPhone = $data["customerPhone"];
    $subscriptionDuration = $data["subscriptionDuration"];

    $sql = "INSERT INTO orders (product_ids, delivery_day, delivery_period, delivery_address, customer_phone, subscription_duration)
            VALUES ('$productIds', $deliveryDay, '$deliveryPeriod', '$deliveryAddress', '$customerPhone', $subscriptionDuration)";

    if ($conn->query($sql) === TRUE) {
        // Возвращение успешного ответа
        $response = array("success" => "Заказ успешно создан");
        header("Content-Type: application/json");
        echo json_encode($response);
    } else {
        $response = array("error" => "Ошибка при создании заказа: " . $conn->error);
        header("Content-Type: application/json");
        echo json_encode($response);
    }
}

$conn->close();


/*CREATE TABLE products (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    weight DECIMAL(10,2) NOT NULL,
    available BOOLEAN DEFAULT TRUE
);

CREATE TABLE orders (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_ids VARCHAR(255) NOT NULL,
    delivery_day INT(1) NOT NULL,
    delivery_period VARCHAR(255) NOT NULL,
    delivery_address VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    subscription_duration INT(2) NOT NULL
);

JSON:
{
  "productIds": "1,2,3",
  "deliveryDay": 2,
  "deliveryPeriod": "Утро",
  "deliveryAddress": "ул. Примерная, д. 123",
  "customerPhone": "+7 123 456 7890",
  "subscriptionDuration": 4
}

*/
?>
