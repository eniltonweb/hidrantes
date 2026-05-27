<?php
require_once 'config/db_conexao.php';

$tables = ['historico_inspecoes_mangueira', 'historico_inspecoes_hidrante', 'mangueiras_novo', 'caixas_hidrante'];

foreach ($tables as $table) {
    echo "Tabela: $table\n";
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo " - " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo " - Erro ou não existe: " . $conn->error . "\n";
    }
    echo "\n";
}
$conn->close();
?>
