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
$tipo = '';

// Tenta identificar se o código segue o novo formato de mangueiras gerado na listagem "H01 (M2)"
if (preg_match('/\(M(\d+)\)$/', $codigo_ativo, $matches)) {
    $id_mangueira = $matches[1];
    $stmt = $conn->prepare("
        SELECT m.id as mangueira_id, c.codigo_caixa, c.local, c.predio 
        FROM mangueiras_novo m 
        JOIN caixas_hidrante c ON m.caixa_id = c.id 
        WHERE m.id = ?
    ");
    $stmt->bind_param("i", $id_mangueira);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $detalhes_ativo = [
            'codigo' => $row['codigo_caixa'] . " - Mangueira " . $row['mangueira_id'],
            'local' => $row['local'] . " (Prédio: " . $row['predio'] . ")",
            'tipo' => 'Mangueira',
            'id_real' => $row['mangueira_id']
        ];
        $tipo = 'Mangueira';
    }
    $stmt->close();
} else {
    // Tenta encontrar como Hidrante
    $stmt = $conn->prepare("SELECT * FROM caixas_hidrante WHERE codigo_caixa = ?");
    $stmt->bind_param("s", $codigo_ativo);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $detalhes_ativo = [
            'codigo' => $row['codigo_caixa'],
            'local' => $row['local'] . " (Prédio: " . $row['predio'] . ")",
            'tipo' => 'Caixa de Hidrante',
            'id_real' => $row['id']
        ];
        $tipo = 'Hidrante';
    }
    $stmt->close();
}

if (!$detalhes_ativo) {
    die("Ativo não encontrado.");
}

// Busca o histórico transacional nas novas tabelas
if ($tipo === 'Mangueira') {
    $stmt = $conn->prepare("
        SELECT data_inspecao, usuario, aduchada_ok, conexoes_ok, proximo_teste, comentarios 
        FROM historico_inspecoes_mangueira 
        WHERE mangueira_id = ? 
        ORDER BY data_inspecao DESC
    ");
    $stmt->bind_param("i", $detalhes_ativo['id_real']);
    $stmt->execute();
    $logs = $stmt->get_result();
    while($row = $logs->fetch_assoc()){
        $historico[] = $row;
    }
    $stmt->close();
} else {
    $stmt = $conn->prepare("
        SELECT data_inspecao, usuario, esguincho_ok, chave_storz_ok, pintura_ok, abrigo_ok, comentarios 
        FROM historico_inspecoes_hidrante 
        WHERE caixa_id = ? 
        ORDER BY data_inspecao DESC
    ");
    $stmt->bind_param("i", $detalhes_ativo['id_real']);
    $stmt->execute();
    $logs = $stmt->get_result();
    while($row = $logs->fetch_assoc()){
        $historico[] = $row;
    }
    $stmt->close();
}

// Extrai o último teste hidrostático (da inspeção mais recente) se for Mangueira
$ultimo_teste = null;
$expira_em_dias = null;
if ($tipo === 'Mangueira' && count($historico) > 0 && !empty($historico[0]['proximo_teste'])) {
    $ultimo_teste = $historico[0]['proximo_teste'];
    $hoje = new DateTime();
    $proximo = new DateTime($ultimo_teste);
    $diferenca = $hoje->diff($proximo);
    $expira_em_dias = $diferenca->invert ? 0 : $diferenca->days;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico do Ativo: <?php echo htmlspecialchars($detalhes_ativo['codigo']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .timeline { list-style: none; padding: 20px 0; position: relative; }
        .timeline:before {
            content: ''; position: absolute; top: 0; bottom: 0; width: 4px;
            background: #e9ecef; left: 31px; margin-right: -1.5px; border-radius: 4px;
        }
        .timeline-item { margin: 0 0 20px 70px; position: relative; }
        .timeline-item:before {
            content: ''; background: #fff; border-radius: 50%; border: 4px solid var(--primary);
            position: absolute; left: -49px; top: 15px; width: 22px; height: 22px; z-index: 1;
        }
        .timeline-item.falha:before {
            border-color: #dc3545; /* Vermelho para falhas */
        }
        .timeline-body { 
            background: #ffffff; 
            border-radius: var(--border-radius-md); 
            padding: 20px; 
            box-shadow: var(--shadow-soft);
            border-left: 5px solid var(--primary);
        }
        .timeline-item.falha .timeline-body {
            border-left-color: #dc3545;
        }
        .status-badge {
            font-size: 0.85rem;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container pb-5">
        <h2 class="my-4"><i class="fas fa-history mr-2 text-primary"></i>Histórico do Ativo</h2>
        
        <div class="card mb-5 shadow-sm border-0">
            <div class="card-header bg-white border-bottom font-weight-bold">
                <i class="fas fa-info-circle text-info mr-2"></i>Detalhes do Ativo
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Código:</strong> <span class="badge badge-dark"><?php echo htmlspecialchars($detalhes_ativo['codigo']); ?></span></p>
                        <p><strong>Tipo:</strong> <?php echo htmlspecialchars($detalhes_ativo['tipo']); ?></p>
                        <p><strong>Local:</strong> <?php echo htmlspecialchars($detalhes_ativo['local']); ?></p>
                    </div>
                    <?php if($tipo == 'Mangueira'): ?>
                    <div class="col-md-6">
                        <?php if($ultimo_teste): ?>
                            <p><strong>Teste Hidrostático Estimado:</strong> <?php echo date('d/m/Y', strtotime($ultimo_teste)); ?></p>
                            <p class="<?php echo $expira_em_dias <= 30 ? 'text-danger font-weight-bold' : 'text-success'; ?>">
                                <strong>Vence em:</strong> <?php echo $expira_em_dias; ?> dias
                            </p>
                        <?php else: ?>
                            <p class="text-muted"><strong>Teste Hidrostático:</strong> Sem dados recentes.</p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <h4 class="mb-4">Linha do Tempo Transacional</h4>
        <ul class="timeline">
            <?php if (count($historico) > 0): ?>
                <?php foreach ($historico as $evento): ?>
                    <?php 
                        // Determina se a inspeção teve alguma falha para pintar a bolinha de vermelho
                        $teve_falha = false;
                        if ($tipo === 'Mangueira') {
                            if ($evento['aduchada_ok'] == 0 || $evento['conexoes_ok'] == 0) $teve_falha = true;
                        } else {
                            if ($evento['esguincho_ok'] == 0 || $evento['chave_storz_ok'] == 0 || $evento['pintura_ok'] == 0 || $evento['abrigo_ok'] == 0) $teve_falha = true;
                        }
                    ?>
                    <li class="timeline-item <?php echo $teve_falha ? 'falha' : ''; ?>">
                        <div class="timeline-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0 <?php echo $teve_falha ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo $teve_falha ? '<i class="fas fa-exclamation-triangle mr-1"></i> Inspeção com Ressalvas' : '<i class="fas fa-check-circle mr-1"></i> Inspeção Aprovada'; ?>
                                </h5>
                                <small class="text-muted"><i class="far fa-calendar-alt mr-1"></i> <?php echo date('d/m/Y H:i', strtotime($evento['data_inspecao'])); ?></small>
                            </div>
                            
                            <p class="mb-3 text-muted"><small><i class="fas fa-user-shield mr-1"></i> Vistoriador: <strong><?php echo htmlspecialchars($evento['usuario']); ?></strong></small></p>
                            
                            <div class="row">
                                <?php if ($tipo === 'Mangueira'): ?>
                                    <div class="col-sm-6 mb-2">
                                        Aduchamento (NBR 12779): 
                                        <span class="status-badge <?php echo $evento['aduchada_ok'] ? 'bg-success text-white' : 'bg-danger text-white'; ?>">
                                            <?php echo $evento['aduchada_ok'] ? 'Ok' : 'Falhou'; ?>
                                        </span>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        Conexões/Acoplamento: 
                                        <span class="status-badge <?php echo $evento['conexoes_ok'] ? 'bg-success text-white' : 'bg-danger text-white'; ?>">
                                            <?php echo $evento['conexoes_ok'] ? 'Ok' : 'Falhou'; ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div class="col-sm-6 mb-2">
                                        Esguincho Presente: 
                                        <span class="status-badge <?php echo $evento['esguincho_ok'] ? 'bg-success text-white' : 'bg-danger text-white'; ?>">
                                            <?php echo $evento['esguincho_ok'] ? 'Ok' : 'Ausente/Danificado'; ?>
                                        </span>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        Chave Storz: 
                                        <span class="status-badge <?php echo $evento['chave_storz_ok'] ? 'bg-success text-white' : 'bg-danger text-white'; ?>">
                                            <?php echo $evento['chave_storz_ok'] ? 'Ok' : 'Ausente/Danificada'; ?>
                                        </span>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        Sinalização de Piso: 
                                        <span class="status-badge <?php echo $evento['pintura_ok'] ? 'bg-success text-white' : 'bg-danger text-white'; ?>">
                                            <?php echo $evento['pintura_ok'] ? 'Ok' : 'Desgastada'; ?>
                                        </span>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        Porta/Abrigo: 
                                        <span class="status-badge <?php echo $evento['abrigo_ok'] ? 'bg-success text-white' : 'bg-danger text-white'; ?>">
                                            <?php echo $evento['abrigo_ok'] ? 'Ok' : 'Danificado/Obstruído'; ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($evento['comentarios'])): ?>
                                <hr>
                                <p class="mb-0 font-italic text-secondary"><i class="fas fa-comment-dots mr-1"></i> "<?php echo htmlspecialchars($evento['comentarios']); ?>"</p>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="timeline-item">
                    <div class="timeline-body">
                        <p class="text-muted"><i class="fas fa-search mr-2"></i>Nenhuma inspeção registrada para este ativo no novo sistema transacional.</p>
                    </div>
                </li>
            <?php endif; ?>
        </ul>
    </div>
    <?php include 'navbarJS.php'; ?>
</body>
</html>