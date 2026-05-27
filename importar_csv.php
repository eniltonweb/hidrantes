<?php
// Habilitar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclui a conexão existente com o banco de dados
require_once 'config/db_conexao.php';

function importarCSV($conn, $fileName, $tabela, $colunas) {
    if (!empty($fileName)) {
        if ($_FILES[$fileName]['size'] > 0) {
            $file = fopen($_FILES[$fileName]['tmp_name'], 'r');
            $header = fgetcsv($file);  // Ler o cabeçalho

            while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
                $valores = [];
                foreach ($colunas as $index => $coluna) {
                    $valor = isset($column[$index]) ? $column[$index] : null;
                    $valores[] = !empty($valor) ? "'$valor'" : "NULL";
                }
                $valoresStr = implode(", ", $valores);

                $sqlInsert = "INSERT INTO $tabela (" . implode(", ", $colunas) . ") VALUES ($valoresStr)";

                if ($conn->query($sqlInsert) === TRUE) {
                    echo "Registro inserido com sucesso na tabela $tabela!<br>";
                } else {
                    echo "Erro ao inserir o registro na tabela $tabela: " . $conn->error . "<br>";
                }
            }

            fclose($file);
        } else {
            echo "O arquivo CSV para $tabela está vazio.<br>";
        }
    }
}

if (isset($_POST['import'])) {
    // Colunas para a tabela hidrantes
    $colunasHidrantes = [
        'codigo', 'local', 'esguincho', 'chave_storz', 'pintura', 'abrigo', 'qte', 'tipo',
        'diametro_polegadas', 'comprimento_metros', 'aduchada', 'conexoes', 'teste_hidrostatico',
        'expira_em_dias', 'mangueiras_menos_de_60_dias', 'perimetro', 'predio', 'vistoriador', 'comentarios'
    ];

    // Colunas para a tabela mangueiras (ajustado conforme suas especificações)
    $colunasMangueiras = [
        'codigo', 'local', 'qte', 'tipo', 'diametro_polegadas', 'comprimento_metros', 
        'aduchada', 'conexoes', 'teste_hidrostatico_proximo', 'expira_em_dias', 
        'mangueiras_menos_de_60_dias', 'perimetro', 'predio', 'vistoriador', 'comentarios'
    ];

    // Importar os dados para hidrantes
    importarCSV($conn, 'file_hidrantes', 'hidrantes', $colunasHidrantes);

    // Importar os dados para mangueiras
    importarCSV($conn, 'file_mangueiras', 'mangueiras', $colunasMangueiras);
}

// Fechar a conexão
$conn->close();
?>
