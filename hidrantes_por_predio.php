<?php
include 'config/db_conexao.php';

if (isset($_GET['predio'])) {
    $predio = htmlspecialchars($_GET['predio']);
    
    $stmt = $conn->prepare("SELECT id_hidrante, codigo FROM hidrantes WHERE predio = ?");
    $stmt->bind_param("s", $predio);
    $stmt->execute();
    $result = $stmt->get_result();

    $hidrantes = [];
    while ($row = $result->fetch_assoc()) {
        $hidrantes[] = $row;
    }

    echo json_encode($hidrantes);
}

$conn->close();
?>
