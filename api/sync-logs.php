<?php
// Habilitar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");

require_once '../config/db_conexao.php';

// Apenas aceita requisições POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método não permitido. Utilize POST."]);
    exit();
}

// Obtém o corpo JSON bruto
$raw_input = file_get_contents('php://input');
$logs = json_decode($raw_input, true);

if (empty($logs) || !is_array($logs)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Dados de log vazios ou formato inválido."]);
    exit();
}

$sucessos = 0;
$erros = 0;

foreach ($logs as $log) {
    $usuario = $log['usuario'] ?? 'offline';
    $acao = $log['acao'] ?? '';
    $data_hora = $log['data_hora'] ?? date("Y-m-d H:i:s");

    if (empty($acao)) {
        $erros++;
        continue;
    }

    $stmt = $conn->prepare("INSERT INTO logs (usuario, acao, data_hora) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $usuario, $acao, $data_hora);

    if ($stmt->execute()) {
        $sucessos++;
    } else {
        $erros++;
    }
    $stmt->close();
}

echo json_encode([
    "status" => "success",
    "message" => "Sincronização de logs concluída.",
    "detalhes" => ["sincronizados" => $sucessos, "erros" => $erros]
]);

$conn->close();
?>
