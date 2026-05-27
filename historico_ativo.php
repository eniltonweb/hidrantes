<?php
// Habilitar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'verifica_sessao.php';
include 'config/db_conexao.php';

if (!isset($_GET['codigo'])) {
    die("Código do ativo não fornecido.");
}

$codigo_ativo = htmlspecialchars($_GET['codigo']);
$historico = [];
$detalhes_ativo = null;

// Tenta encontrar o ativo na tabela de hidrantes
$stmt = $conn->prepare("SELECT * FROM hidrantes WHERE codigo = ?");
$stmt->bind_param("s", $codigo_ativo);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $detalhes_ativo = $result->fetch_assoc();
    $detalhes_ativo['tipo'] = 'Hidrante';
}
$stmt->close();

// Se não for hidrante, tenta encontrar na tabela de mangueiras
if (!$detalhes_ativo) {
    $stmt = $conn->prepare("SELECT * FROM mangueiras WHERE codigo = ?");
    $stmt->bind_param("s", $codigo_ativo);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $detalhes_ativo = $result->fetch_assoc();
        $detalhes_ativo['tipo'] = 'Mangueira';
    }
    $stmt->close();
}

if (!$detalhes_ativo) {
    die("Ativo não encontrado.");
}

// Busca os logs de inspeção para este ativo específico
// A query busca por uma ação que contenha o código do ativo
$stmt = $conn->prepare("SELECT * FROM logs WHERE acao LIKE ? ORDER BY data_hora DESC");
$search_term = "%$codigo_ativo%";
$stmt->bind_param("s", $search_term);
$stmt->execute();
$logs = $stmt->get_result();
while($row = $logs->fetch_assoc()){
    $historico[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico do Ativo: <?php echo $codigo_ativo; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <style>
        .timeline { list-style: none; padding: 20px 0; position: relative; }
        .timeline:before {
            content: ''; position: absolute; top: 0; bottom: 0; width: 4px;
            background: #002f6c; left: 31px; margin-right: -1.5px;
        }
        .timeline-item { margin: 0 0 20px 70px; position: relative; }
        .timeline-item:before {
            content: ''; background: white; border-radius: 50%; border: 3px solid #fce500;
            position: absolute; left: -49px; top: 12px; width: 20px; height: 20px; z-index: 1;
        }
        .timeline-body { background: #f8f9fa; border-radius: .25rem; padding: 20px; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h2 class="my-4">Histórico do Ativo: <?php echo htmlspecialchars($detalhes_ativo['codigo']); ?></h2>
        
        <div class="card mb-4">
            <div class="card-header">
                Detalhes do Ativo
            </div>
            <div class="card-body">
                <p><strong>Código:</strong> <?php echo htmlspecialchars($detalhes_ativo['codigo']); ?></p>
                <p><strong>Tipo:</strong> <?php echo htmlspecialchars($detalhes_ativo['tipo']); ?></p>
                <p><strong>Local:</strong> <?php echo htmlspecialchars($detalhes_ativo['local']); ?></p>
                <?php if($detalhes_ativo['tipo'] == 'Mangueira'): ?>
                    <p><strong>Próximo Teste Hidrostático:</strong> <?php echo date('d/m/Y', strtotime($detalhes_ativo['teste_hidrostatico_proximo'])); ?></p>
                    <p class="text-danger"><strong>Vence em:</strong> <?php echo htmlspecialchars($detalhes_ativo['expira_em_dias']); ?> dias</p>
                <?php endif; ?>
            </div>
        </div>

        <h4>Linha do Tempo de Atividades</h4>
        <ul class="timeline">
            <?php if (count($historico) > 0): ?>
                <?php foreach ($historico as $evento): ?>
                    <li class="timeline-item">
                        <div class="timeline-body">
                            <h5><?php echo htmlspecialchars($evento['acao']); ?></h5>
                            <p class="text-muted"><small>Realizado por: <?php echo htmlspecialchars($evento['usuario']); ?></small></p>
                            <p class="text-muted"><small>Data: <?php echo date('d/m/Y H:i:s', strtotime($evento['data_hora'])); ?></small></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="timeline-item">
                    <div class="timeline-body">
                        <p>Nenhuma atividade registrada para este ativo ainda.</p>
                    </div>
                </li>
            <?php endif; ?>
        </ul>
    </div>
    <?php include 'navbarJS.php'; ?>
</body>
</html>