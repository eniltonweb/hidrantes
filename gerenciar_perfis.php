<?php
// Habilitar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'verifica_sessao.php';
include 'config/db_conexao.php';
include 'verifica_permissao.php';
verificar_permissao('administrador'); // Garante que só administradores acessem

$feedback_message = '';

// --- LÓGICA PARA PROCESSAR AÇÕES (UPDATE E DELETE) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ação de Deletar Usuário
    if (isset($_POST['delete_user'])) {
        $id_usuario_delete = $_POST['id_usuario_delete'];
        // Proteção: não permitir que o administrador se delete
        if ($id_usuario_delete == $_SESSION['id_usuario']) {
            $feedback_message = '<div class="alert alert-danger">Você não pode deletar a si mesmo.</div>';
        } else {
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
            $stmt->bind_param("i", $id_usuario_delete);
            if ($stmt->execute()) {
                $feedback_message = '<div class="alert alert-success">Usuário deletado com sucesso!</div>';
            } else {
                $feedback_message = '<div class="alert alert-danger">Erro ao deletar o usuário.</div>';
            }
            $stmt->close();
        }
    }

    // Ação de Editar Usuário
    if (isset($_POST['edit_user'])) {
        $id_usuario_edit = $_POST['id_usuario_edit'];
        $nome_edit = htmlspecialchars($_POST['nome_edit']);
        $tipo_usuario_edit = htmlspecialchars($_POST['tipo_usuario_edit']);

        $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, tipo_usuario = ? WHERE id_usuario = ?");
        $stmt->bind_param("ssi", $nome_edit, $tipo_usuario_edit, $id_usuario_edit);
        if ($stmt->execute()) {
            $feedback_message = '<div class="alert alert-success">Usuário atualizado com sucesso!</div>';
        } else {
            $feedback_message = '<div class="alert alert-danger">Erro ao atualizar o usuário.</div>';
        }
        $stmt->close();
    }
}

// Busca todos os usuários para exibir na tabela
$query = "SELECT id_usuario, nome, tipo_usuario FROM usuarios ORDER BY nome ASC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, shrink-to-fit=no">
    <title>Gerenciamento de Perfis</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center my-4">
            <h2>Gerenciamento de Perfis</h2>
            <a href="cadastrar_usuarios.php" class="btn btn-primary"><i class="fas fa-plus"></i> Adicionar Novo Usuário</a>
        </div>

        <?php echo $feedback_message; // Exibe mensagens de sucesso ou erro ?>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Nome</th>
                        <th>Tipo de Usuário</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['nome']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($row['tipo_usuario'])); ?></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-warning btn-sm edit-btn" 
                                    data-toggle="modal" data-target="#editModal"
                                    data-id="<?php echo $row['id_usuario']; ?>"
                                    data-nome="<?php echo htmlspecialchars($row['nome']); ?>"
                                    data-tipo="<?php echo $row['tipo_usuario']; ?>">
                                <i class="fas fa-edit"></i> Editar
                            </button>

                            <form method="POST" action="gerenciar_perfis.php" style="display:inline-block;" onsubmit="return confirm('Tem certeza de que deseja deletar este usuário? Esta ação não pode ser desfeita.');">
                                <input type="hidden" name="id_usuario_delete" value="<?php echo $row['id_usuario']; ?>">
                                <button type="submit" name="delete_user" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i> Deletar
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="gerenciar_perfis.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Editar Usuário</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_usuario_edit" id="edit-id">
                        <div class="form-group">
                            <label for="edit-nome">Nome:</label>
                            <input type="text" name="nome_edit" id="edit-nome" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-tipo">Tipo de Usuário:</label>
                            <select name="tipo_usuario_edit" id="edit-tipo" class="form-control" required>
                                <option value="administrador">Administrador</option>
                                <option value="bombeiro">Bombeiro</option>
                                <option value="fornecedor">Fornecedor</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" name="edit_user" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'navbarJS.php'; ?>
    <script>
    // Script para passar os dados do usuário para o modal de edição
    document.addEventListener('DOMContentLoaded', function () {
        var editButtons = document.querySelectorAll('.edit-btn');
        editButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var userId = this.getAttribute('data-id');
                var userName = this.getAttribute('data-nome');
                var userType = this.getAttribute('data-tipo');

                document.getElementById('edit-id').value = userId;
                document.getElementById('edit-nome').value = userName;
                document.getElementById('edit-tipo').value = userType;
            });
        });
    });
    </script>
</body>
</html>