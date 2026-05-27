<?php
// Habilitar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'verifica_sessao.php';
include 'config/db_conexao.php';
include 'verifica_permissao.php';
verificar_permissao('administrador');

$feedback_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. VERIFICAÇÃO DO TOKEN CSRF (Mais segurança)
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['token'], $_POST['csrf_token'])) {
        $feedback_message = '<div class="alert alert-danger">Falha na validação de segurança. Tente novamente.</div>';
    } else {
        $nome = htmlspecialchars($_POST['nome']);
        $tipo_usuario = htmlspecialchars($_POST['tipo_usuario']);
        $senha = $_POST['senha'];
        $confirma_senha = $_POST['confirma_senha'];

        // 2. VERIFICAÇÃO SE AS SENHAS CONFEREM
        if ($senha !== $confirma_senha) {
            $feedback_message = '<div class="alert alert-danger">As senhas não conferem.</div>';
        } else {
            // 3. VERIFICAÇÃO SE O USUÁRIO JÁ EXISTE
            $stmt_check = $conn->prepare("SELECT id_usuario FROM usuarios WHERE nome = ?");
            $stmt_check->bind_param("s", $nome);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $feedback_message = '<div class="alert alert-danger">Este nome de usuário já está em uso.</div>';
            } else {
                // Se tudo estiver ok, insere o novo usuário
                $senha_hashed = password_hash($senha, PASSWORD_DEFAULT);
                $stmt_insert = $conn->prepare("INSERT INTO usuarios (nome, tipo_usuario, senha) VALUES (?, ?, ?)");
                $stmt_insert->bind_param("sss", $nome, $tipo_usuario, $senha_hashed);
                
                if ($stmt_insert->execute()) {
                    $feedback_message = '<div class="alert alert-success">Usuário cadastrado com sucesso!</div>';
                } else {
                    $feedback_message = '<div class="alert alert-danger">Erro ao cadastrar o usuário.</div>';
                }
                $stmt_insert->close();
            }
            $stmt_check->close();
        }
    }
}

// Cria um novo token CSRF para o formulário
$_SESSION['token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, shrink-to-fit=no">
    <title>Cadastro de Usuários</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card mt-5 shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Cadastro de Novo Usuário</h2>
                        
                        <?php echo $feedback_message; // Exibe mensagens de sucesso ou erro ?>

                        <form method="POST" action="cadastrar_usuarios.php" id="cadastroForm">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['token']); ?>">
                            
                            <div class="form-group">
                                <label for="nome">Nome de Usuário:</label>
                                <input type="text" id="nome" name="nome" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="tipo_usuario">Tipo de Usuário:</label>
                                <select id="tipo_usuario" name="tipo_usuario" class="form-control" required>
                                    <option value="administrador">Administrador</option>
                                    <option value="bombeiro" selected>Bombeiro</option>
                                    <option value="fornecedor">Fornecedor</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="senha">Senha:</label>
                                <input type="password" id="senha" name="senha" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="confirma_senha">Confirmar Senha:</label>
                                <input type="password" id="confirma_senha" name="confirma_senha" class="form-control" required>
                                <div class="invalid-feedback">As senhas não conferem.</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-user-plus"></i> Cadastrar</button>
                            <a href="gerenciar_perfis.php" class="btn btn-secondary btn-block mt-2">Voltar</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'navbarJS.php'; ?>

    <script>
    // Script para validar se as senhas conferem em tempo real
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('cadastroForm');
        const senha = document.getElementById('senha');
        const confirmaSenha = document.getElementById('confirma_senha');

        function validarSenhas() {
            if (senha.value !== confirmaSenha.value) {
                confirmaSenha.classList.add('is-invalid'); // Adiciona borda vermelha
                return false;
            } else {
                confirmaSenha.classList.remove('is-invalid'); // Remove a borda vermelha
                return true;
            }
        }

        senha.addEventListener('keyup', validarSenhas);
        confirmaSenha.addEventListener('keyup', validarSenhas);

        form.addEventListener('submit', function(event) {
            if (!validarSenhas()) {
                event.preventDefault(); // Impede o envio do formulário se as senhas não conferirem
                alert('Erro: As senhas não conferem. Por favor, verifique.');
            }
        });
    });
    </script>
</body>
</html>