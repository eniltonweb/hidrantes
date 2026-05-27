<?php
session_start();
require_once 'config/db_conexao.php';

$error_message = ''; // Variável para armazenar a mensagem de erro

// Função para sanitizar as entradas do usuário
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // --- CORREÇÃO APLICADA AQUI ---
    // Usando 'username' e 'password' para corresponder ao formulário HTML
    $nome_usuario = sanitize_input($_POST['username']);
    $senha_usuario = sanitize_input($_POST['password']);
    
    // Consulta preparada para evitar SQL injection
    $stmt = $conn->prepare("SELECT id_usuario, nome, tipo_usuario, senha FROM usuarios WHERE nome = ?");
    $stmt->bind_param("s", $nome_usuario);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id_usuario, $nome_db, $tipo_usuario_db, $hashed_password);
        $stmt->fetch();

        // Verifica a senha
        if (password_verify($senha_usuario, $hashed_password)) {
            // Sucesso no login, configurando a sessão
            session_regenerate_id(true); // Prevenção contra session fixation
            $_SESSION['id_usuario'] = $id_usuario;
            $_SESSION['nome'] = $nome_db;
            $_SESSION['tipo_usuario'] = $tipo_usuario_db;

            // Redirecionamento com base no tipo de usuário
            if ($tipo_usuario_db == 'administrador') {
                header("Location: dashboard.php");
            } else {
                header("Location: gerenciar_inspecoes.php");
            }
            exit();
        } else {
            // --- MELHORIA APLICADA AQUI ---
            $error_message = "Nome de usuário ou senha inválidos.";
        }
    } else {
        // --- MELHORIA APLICADA AQUI ---
        $error_message = "Nome de usuário ou senha inválidos.";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login - Sistema de Hidrantes e Mangueiras</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #0b132b, #1c2541);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background-color: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            border-top: 5px solid var(--primary);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
        }
        .login-header {
            background-color: rgba(11, 19, 43, 0.02);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 2.5rem 2rem 1.5rem;
            text-align: center;
        }
        .login-header i {
            color: var(--primary);
            margin-bottom: 15px;
        }
        .login-body {
            padding: 2.5rem 2rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-shield-alt fa-3x"></i>
            <h3 class="font-weight-bold m-0 text-dark">Acesso Restrito</h3>
            <small class="text-muted">Sistema de Hidrantes e Mangueiras</small>
        </div>
        <div class="login-body">
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger py-2" role="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group mb-4">
                    <label for="username"><i class="fas fa-user mr-2 text-muted"></i>Usuário:</label>
                    <input type="text" id="username" name="username" class="form-control form-control-lg" placeholder="Digite seu usuário..." required>
                </div>
                <div class="form-group mb-4">
                    <label for="password"><i class="fas fa-key mr-2 text-muted"></i>Senha:</label>
                    <input type="password" id="password" name="password" class="form-control form-control-lg" placeholder="Digite sua senha..." required>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg py-3 mt-4">
                    <i class="fas fa-sign-in-alt mr-2"></i>Entrar
                </button>
            </form>
        </div>
    </div>
</body>
</html>