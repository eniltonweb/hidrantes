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
        $headers = ['Código', 'Aduchada', 'Conexões', 'Próximo Teste Hidrostático', 'Expira em (Dias)', 'Data da Inspeção', 'Vistoriador'];
        // CORREÇÃO: A tabela correta é 'mangueiras'
        $query = "SELECT codigo, aduchada, conexoes, teste_hidrostatico_proximo, expira_em_dias, data_manutencao, vistoriador FROM mangueiras";
    } elseif ($tipo_relatorio == 'inspecoes_hidrantes') {
        $headers = ['Código', 'Local', 'Esguincho', 'Chave Storz', 'Pintura', 'Abrigo', 'Data da Inspeção', 'Vistoriador'];
        $query = "SELECT codigo, local, esguincho, chave_storz, pintura, abrigo, data_manutencao, vistoriador FROM hidrantes";
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