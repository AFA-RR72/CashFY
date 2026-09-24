<?php session_start();
$params = $_GET;
$params['product'] = $_POST['product_id'];
$url = '?' . http_build_query($params);

header("Location: ../view/seller.php$url");
exit;

?>