<?php
$servername = "bd_hidrantes.mysql.dbaas.com.br";
$username = "bd_hidrantes";
$password = "Nil2024#";
$dbname = "bd_hidrantes";

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$conn->set_charset("utf8");
?>
