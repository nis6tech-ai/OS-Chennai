<?php
include "connect.php";
$products = $pdo->query("SELECT * FROM products")->fetchAll();
echo json_encode($products);
?>
