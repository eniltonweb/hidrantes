<?php
include 'verifica_sessao.php';
include 'config/db_conexao.php';

// Desabilitar a exibição de erros em produção, mas manter para depuração
// ini_set('display_errors', 0);
// error_reporting(0);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tipo_relatorio = $_POST['tipo_relatorio'];
    $formato = $_POST['formato'];
    
    $query = '';
    $headers = [];

    if ($tipo_relatorio == 'inspecoes_mangueiras') {
        $headers = ['Código da Mangueira', 'Aduchada', 'Conexões', 'Próximo Teste Hidrostático', 'Expira em (Dias)', 'Data da Inspeção', 'Vistoriador'];
        $query = "
            SELECT 
                CONCAT(c.codigo_caixa, ' (M', m.id, ')') as codigo,
                CASE WHEN h.aduchada_ok = 1 THEN 'Ok' WHEN h.aduchada_ok = 0 THEN 'Não Ok' ELSE 'Sem Inspeção' END as aduchada,
                CASE WHEN h.conexoes_ok = 1 THEN 'Ok' WHEN h.conexoes_ok = 0 THEN 'Não Ok' ELSE 'Sem Inspeção' END as conexoes,
                h.proximo_teste as teste_hidrostatico_proximo,
                DATEDIFF(h.proximo_teste, CURDATE()) as expira_em_dias,
                h.data_inspecao as data_manutencao,
                h.usuario as vistoriador
            FROM mangueiras_novo m
            JOIN caixas_hidrante c ON m.caixa_id = c.id
            LEFT JOIN (
                SELECT h1.* FROM historico_inspecoes_mangueira h1
                INNER JOIN (
                    SELECT mangueira_id, MAX(id) as max_id FROM historico_inspecoes_mangueira GROUP BY mangueira_id
                ) h2 ON h1.id = h2.max_id
            ) h ON m.id = h.mangueira_id
        ";
    } elseif ($tipo_relatorio == 'inspecoes_hidrantes') {
        $headers = ['Código', 'Local', 'Esguincho', 'Chave Storz', 'Pintura', 'Abrigo', 'Data da Inspeção', 'Vistoriador'];
        $query = "
            SELECT 
                c.codigo_caixa as codigo, 
                c.local, 
                CASE WHEN h.esguincho_ok = 1 THEN 'Ok' WHEN h.esguincho_ok = 0 THEN 'Não Ok' ELSE 'Sem Inspeção' END as esguincho,
                CASE WHEN h.chave_storz_ok = 1 THEN 'Ok' WHEN h.chave_storz_ok = 0 THEN 'Não Ok' ELSE 'Sem Inspeção' END as chave_storz,
                CASE WHEN h.pintura_ok = 1 THEN 'Ok' WHEN h.pintura_ok = 0 THEN 'Não Ok' ELSE 'Sem Inspeção' END as pintura,
                CASE WHEN h.abrigo_ok = 1 THEN 'Ok' WHEN h.abrigo_ok = 0 THEN 'Não Ok' ELSE 'Sem Inspeção' END as abrigo,
                h.data_inspecao as data_manutencao, 
                h.usuario as vistoriador
            FROM caixas_hidrante c
            LEFT JOIN (
                SELECT h1.* FROM historico_inspecoes_hidrante h1
                INNER JOIN (
                    SELECT caixa_id, MAX(id) as max_id FROM historico_inspecoes_hidrante GROUP BY caixa_id
                ) h2 ON h1.id = h2.max_id
            ) h ON c.id = h.caixa_id
        ";
    } else {
        die('Tipo de relatório inválido.');
    }

    $result = $conn->query($query);
    if (!$result) {
        die('Erro na consulta SQL: ' . $conn->error);
    }

    if ($formato == 'csv') {
        $filename = $tipo_relatorio . "_relatorio_" . date("Y-m-d") . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit();
    } elseif ($formato == 'html') {
        $filename = $tipo_relatorio . "_relatorio_" . date("Y-m-d") . ".html";
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Relatório</title>";
        echo "<link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css'>";
        echo "<style>body{padding: 20px;} h2 { color: #002f6c; } table { width: 100%; } th, td { text-align: center; border: 1px solid #ddd; padding: 8px; }</style>";
        echo "</head><body><div class='container-fluid'>";
        echo "<h2>Relatório de " . ucwords(str_replace('_', ' ', $tipo_relatorio)) . "</h2>";
        echo "<table class='table table-bordered table-striped'><thead><tr>";
        foreach ($headers as $header) {
            echo "<th>" . htmlspecialchars($header) . "</th>";
        }
        echo "</tr></thead><tbody>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
            }
            echo "</tr>";
        }
        echo "</tbody></table></div></body></html>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, shrink-to-fit=no">
    <title>Exportação de Relatórios</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h2 class="my-4">Exportar Relatórios</h2>
        <form method="POST" action="exportar_relatorios.php">
            <div class="form-group">
                <label for="tipo_relatorio">Selecione o tipo de relatório:</label>
                <select id="tipo_relatorio" name="tipo_relatorio" class="form-control">
                    <option value="inspecoes_mangueiras">Inspeções de Mangueiras</option>
                    <option value="inspecoes_hidrantes">Inspeções de Hidrantes</option>
                </select>
            </div>
            <div class="form-group">
                <label for="formato">Selecione o formato de exportação:</label>
                <select id="formato" name="formato" class="form-control">
                    <option value="csv">CSV</option>
                    <option value="html">HTML</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Exportar</button>
        </form>
    </div>
    <?php include 'navbarJS.php'; ?>
</body>
</html>