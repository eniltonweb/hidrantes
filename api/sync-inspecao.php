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
$inspecoes = json_decode($raw_input, true);

if (empty($inspecoes) || !is_array($inspecoes)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Dados de inspeção vazios ou formato inválido."]);
    exit();
}

$sucessos = 0;
$erros = 0;

foreach ($inspecoes as $inspecao) {
    $id_ativo = $inspecao['id_ativo'] ?? null;
    $tipo_ativo = $inspecao['tipo_ativo'] ?? null;
    $codigo = $inspecao['codigo'] ?? null;
    $vistoriador = $inspecao['vistoriador'] ?? 'offline';
    $comentarios = $inspecao['comentarios'] ?? '';
    $data_manutencao = $inspecao['data_manutencao'] ?? date("Y-m-d H:i:s");

    if (!$id_ativo || !$tipo_ativo || !$codigo) {
        $erros++;
        continue;
    }

    if ($tipo_ativo === 'hidrante') {
        $esguincho = $inspecao['esguincho'] ?? 'Ok';
        $chave_storz = $inspecao['chave_storz'] ?? 'Ok';
        $pintura = $inspecao['pintura'] ?? 'Ok';
        $abrigo = $inspecao['abrigo'] ?? 'Ok';
        $conexoes = $inspecao['conexoes'] ?? 'Ok';

        $stmt_hist = $conn->prepare("INSERT INTO historico_inspecoes_hidrante (id_hidrante, vistoriador, esguincho, chave_storz, pintura, abrigo, conexoes, comentarios, data_inspecao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_hist->bind_param("issssssss", $id_ativo, $vistoriador, $esguincho, $chave_storz, $pintura, $abrigo, $conexoes, $comentarios, $data_manutencao);
        $stmt_hist->execute();
        $stmt_hist->close();

        $stmt = $conn->prepare("UPDATE hidrantes SET esguincho = ?, chave_storz = ?, pintura = ?, abrigo = ?, conexoes = ?, vistoriador = ?, comentarios = ?, data_manutencao = ? WHERE id_hidrante = ?");
        $stmt->bind_param("ssssssssi", $esguincho, $chave_storz, $pintura, $abrigo, $conexoes, $vistoriador, $comentarios, $data_manutencao, $id_ativo);
    
    } elseif ($tipo_ativo === 'mangueira') {
        $aduchada = $inspecao['aduchada'] ?? 'Ok';
        $conexoes = $inspecao['conexoes'] ?? 'Ok';
        $teste_hidrostatico_proximo = $inspecao['teste_hidrostatico_proximo'] ?? null;

        $expira_em_dias = 0;
        if (!empty($teste_hidrostatico_proximo)) {
            $hoje = new DateTime();
            $proximo_teste = new DateTime($teste_hidrostatico_proximo);
            $diferenca = $hoje->diff($proximo_teste);
            $expira_em_dias = $diferenca->invert ? 0 : $diferenca->days;
        }

        $stmt_hist = $conn->prepare("INSERT INTO historico_inspecoes_mangueira (id_mangueira, vistoriador, aduchada, conexoes, teste_hidrostatico_proximo, expira_em_dias, comentarios, data_inspecao) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_hist->bind_param("issssiss", $id_ativo, $vistoriador, $aduchada, $conexoes, $teste_hidrostatico_proximo, $expira_em_dias, $comentarios, $data_manutencao);
        $stmt_hist->execute();
        $stmt_hist->close();

        $stmt = $conn->prepare("UPDATE mangueiras SET aduchada = ?, conexoes = ?, teste_hidrostatico_proximo = ?, expira_em_dias = ?, comentarios = ?, vistoriador = ?, data_manutencao = ? WHERE id_mangueira = ?");
        $stmt->bind_param("sssisssi", $aduchada, $conexoes, $teste_hidrostatico_proximo, $expira_em_dias, $comentarios, $vistoriador, $data_manutencao, $id_ativo);
    } else {
        $erros++;
        continue;
    }

    if (isset($stmt)) {
        if ($stmt->execute()) {
            $sucessos++;
        } else {
            $erros++;
        }
        $stmt->close();
    }
}

echo json_encode([
    "status" => "success",
    "message" => "Sincronização de vistorias concluída.",
    "detalhes" => ["sincronizados" => $sucessos, "erros" => $erros]
]);

$conn->close();
?>
