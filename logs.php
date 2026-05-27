<?php
include 'verifica_sessao.php';
include 'config/db_conexao.php';

// Verificar se o usuário tem permissão (se necessário)
$tipo_usuario = $_SESSION['tipo_usuario'];

// Se você quiser fazer uma verificação de permissão básica:
if ($tipo_usuario != 'administrador') {
    die('Acesso não autorizado.');
}

// Verificar se o botão de apagar registros foi pressionado
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apagar_todos'])) {
    $queryDelete = "DELETE FROM logs";
    if ($conn->query($queryDelete) === TRUE) {
        echo "<script>alert('Todos os registros foram apagados com sucesso.');</script>";
    } else {
        echo "<script>alert('Erro ao apagar registros: " . $conn->error . "');</script>";
    }
}

// Consulta para obter os logs
$query = "SELECT * FROM logs ORDER BY data_hora DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Logs de Atividades - Sistema de Hidrantes e Mangueiras</title>
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
    <!-- NavBar -->
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h2 class="my-4">Logs de Atividades</h2>

        <!-- Formulário para apagar todos os registros -->
        <form method="POST" action="logs.php" onsubmit="return confirm('Tem certeza de que deseja apagar todos os registros de log? Esta ação não pode ser desfeita.');">
            <button type="submit" name="apagar_todos" class="btn btn-danger mb-4">Apagar Todos os Registros</button>
        </form>

        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Ação</th>
                    <th>Data e Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['usuario']; ?></td>
                    <td><?php echo $row['acao']; ?></td>
                    <td><?php echo $row['data_hora']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- NavBar Scripts -->
    <?php include 'navbarJS.php'; ?>
</body>
</html>