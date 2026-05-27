<?php
// Habilitar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'verifica_sessao.php';
include 'config/db_conexao.php';

// Usando o operador de coalescência nula (??) para evitar passar null para htmlspecialchars
$codigoFiltro = $_GET['codigo'] ?? '';
$vistoriadorFiltro = $_GET['vistoriador'] ?? '';
$aduchadaFiltro = $_GET['aduchada'] ?? '';
$conexoesFiltro = $_GET['conexoes'] ?? '';
$testeHidroFiltro = $_GET['teste_hidrostatico_proximo'] ?? '';
$expiraEmFiltro = $_GET['expira_em_dias'] ?? '';

// Consulta base para recuperar todas as inspeções de mangueiras
$query = "SELECT codigo, aduchada, conexoes, teste_hidrostatico_proximo, expira_em_dias, data_manutencao, vistoriador FROM mangueiras WHERE 1=1";

$params = [];
$types = "";

if ($codigoFiltro) {
    $query .= " AND codigo LIKE ?";
    $params[] = "%$codigoFiltro%";
    $types .= "s";
}
if ($vistoriadorFiltro) {
    $query .= " AND vistoriador LIKE ?";
    $params[] = "%$vistoriadorFiltro%";
    $types .= "s";
}
if ($aduchadaFiltro) {
    $query .= " AND aduchada = ?";
    $params[] = $aduchadaFiltro;
    $types .= "s";
}
if ($conexoesFiltro) {
    $query .= " AND conexoes = ?";
    $params[] = $conexoesFiltro;
    $types .= "s";
}
if ($testeHidroFiltro) {
    $query .= " AND teste_hidrostatico_proximo = ?";
    $params[] = $testeHidroFiltro;
    $types .= "s";
}
if ($expiraEmFiltro) {
    $query .= " AND expira_em_dias = ?";
    $params[] = $expiraEmFiltro;
    $types .= "i";
}

$stmt = $conn->prepare($query);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    die("Erro na preparação da consulta: " . $conn->error);
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, shrink-to-fit=no">
    <title>Visualizar Inspeções - Sistema de Hidrantes e Mangueiras</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="manifest" href="js/manifest.json">
    <style>
        h2 { color: #002f6c; }
        table { width: 100%; }
        th, td { text-align: center; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid">
        <h2 class="my-4">Visualizar Inspeções de Mangueiras</h2>

        <form method="GET" action="gerenciar_inspecoes.php" class="mb-4">
            <div class="form-row">
                <div class="col-md-2">
                    <input type="text" name="codigo" class="form-control" placeholder="Filtrar por Código" value="<?php echo htmlspecialchars($codigoFiltro); ?>">
                </div>
                <div class="col-md-2">
                    <input type="text" name="vistoriador" class="form-control" placeholder="Filtrar por Vistoriador" value="<?php echo htmlspecialchars($vistoriadorFiltro); ?>">
                </div>
                <div class="col-md-2">
                    <select name="aduchada" class="form-control">
                        <option value="">Por Aduchada</option>
                        <option value="Ok" <?php if ($aduchadaFiltro == 'Ok') echo 'selected'; ?>>Ok</option>
                        <option value="Não Ok" <?php if ($aduchadaFiltro == 'Não Ok') echo 'selected'; ?>>Não Ok</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="conexoes" class="form-control">
                        <option value="">Por Conexões</option>
                        <option value="Ok" <?php if ($conexoesFiltro == 'Ok') echo 'selected'; ?>>Ok</option>
                        <option value="Não Ok" <?php if ($conexoesFiltro == 'Não Ok') echo 'selected'; ?>>Não Ok</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="teste_hidrostatico_proximo" class="form-control" value="<?php echo htmlspecialchars($testeHidroFiltro); ?>">
                </div>
                <div class="col-md-1">
                    <input type="number" name="expira_em_dias" class="form-control" placeholder="Expira em" value="<?php echo htmlspecialchars($expiraEmFiltro); ?>">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
            </div>
        </form>

        <table class="table table-bordered table-striped table-responsive-sm">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Aduchada</th>
                    <th>Conexões</th>
                    <th>Próximo Teste Hidrostático</th>
                    <th>Expira em (Dias)</th>
                    <th>Data da Inspeção</th>
                    <th>Vistoriador</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="inspecoesTableBody">
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['codigo'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['aduchada'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['conexoes'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['teste_hidrostatico_proximo'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['expira_em_dias'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['data_manutencao'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['vistoriador'] ?? ''); ?></td>
                    <td>
                        <a href="historico_ativo.php?codigo=<?php echo urlencode($row['codigo'] ?? ''); ?>" class="btn btn-info btn-sm" title="Ver Histórico">
                            Histórico
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php include 'navbarJS.php'; ?>
    
    <script src="js/service-worker.js"></script>
    <script src="js/indexedDB.js"></script>
</body>
</html>