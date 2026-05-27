<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Arquivos CSV</title>
</head>
<body>
    <h2>Importar Arquivos CSV para Hidrantes e Mangueiras</h2>
    <form action="importar_csv.php" method="post" enctype="multipart/form-data">
        <label for="file_hidrantes">Escolha o arquivo CSV de Hidrantes:</label>
        <input type="file" name="file_hidrantes" id="file_hidrantes" accept=".csv" required>
        <br><br>
        <label for="file_mangueiras">Escolha o arquivo CSV de Mangueiras:</label>
        <input type="file" name="file_mangueiras" id="file_mangueiras" accept=".csv" required>
        <br><br>
        <button type="submit" name="import">Importar CSVs</button>
    </form>
</body>
</html>
