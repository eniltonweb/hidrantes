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
        $esguincho_ok = ($inspecao['esguincho'] ?? '') == 'Ok' ? 1 : 0;
        $chave_storz_ok = ($inspecao['chave_storz'] ?? '') == 'Ok' ? 1 : 0;
        $pintura_ok = ($inspecao['pintura'] ?? '') == 'Ok' ? 1 : 0;
        $abrigo_ok = ($inspecao['abrigo'] ?? '') == 'Ok' ? 1 : 0;

        $stmt = $conn->prepare("INSERT INTO historico_inspecoes_hidrante (caixa_id, usuario, data_inspecao, esguincho_ok, chave_storz_ok, pintura_ok, abrigo_ok, comentarios) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issiiiis", $id_ativo, $vistoriador, $data_manutencao, $esguincho_ok, $chave_storz_ok, $pintura_ok, $abrigo_ok, $comentarios);
    
    } elseif ($tipo_ativo === 'mangueira') {
        $aduchada_ok = ($inspecao['aduchada'] ?? '') == 'Ok' ? 1 : 0;
        $conexoes_ok = ($inspecao['conexoes'] ?? '') == 'Ok' ? 1 : 0;
        $proximo_teste = $inspecao['teste_hidrostatico_proximo'] ?? null;

        $stmt = $conn->prepare("INSERT INTO historico_inspecoes_mangueira (mangueira_id, usuario, data_inspecao, aduchada_ok, conexoes_ok, proximo_teste, comentarios) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issiiis", $id_ativo, $vistoriador, $data_manutencao, $aduchada_ok, $conexoes_ok, $proximo_teste, $comentarios);
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
