<?php
// Garante que a sessão seja iniciada apenas uma vez
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclui a conexão com o banco de dados. O @ suprime erros caso o arquivo já tenha sido incluído.
@include_once 'config/db_conexao.php';

$count_notificacoes = 0;
$notificacoes_recentes = [];

// Apenas executa a consulta se o usuário estiver logado e a conexão existir
if (isset($_SESSION['id_usuario']) && isset($conn)) {
    $id_usuario = $_SESSION['id_usuario'];

    // Conta todas as notificações não lidas
    $query_count = "SELECT COUNT(id) as total FROM notificacoes WHERE lida = FALSE AND (id_usuario = ? OR id_usuario IS NULL)";
    $stmt_count = $conn->prepare($query_count);
    if ($stmt_count) {
        $stmt_count->bind_param("i", $id_usuario);
        $stmt_count->execute();
        $result_count = $stmt_count->get_result()->fetch_assoc();
        $count_notificacoes = $result_count['total'] ?? 0;
        $stmt_count->close();
    }

    // Busca as 5 notificações não lidas mais recentes
    $query_recentes = "SELECT id, mensagem, link, data_criacao FROM notificacoes WHERE lida = FALSE AND (id_usuario = ? OR id_usuario IS NULL) ORDER BY data_criacao DESC LIMIT 5";
    $stmt_recentes = $conn->prepare($query_recentes);
    if ($stmt_recentes) {
        $stmt_recentes->bind_param("i", $id_usuario);
        $stmt_recentes->execute();
        $result_recentes = $stmt_recentes->get_result();
        while ($row = $result_recentes->fetch_assoc()) {
            $notificacoes_recentes[] = $row;
        }
        $stmt_recentes->close();
    }
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <a class="navbar-brand" href="index.php">Sistema de Hidrantes e Mangueiras</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item">
                <a class="nav-link" href="index.php">Início</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="inspecao.php">Registrar Inspeção</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gerenciar_inspecoes.php">Visualizar Inspeções</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">Gráficos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="gerar_etiquetas.php">Gerar Etiquetas QR</a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAdmin" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Administração
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdownAdmin">
                    <a class="dropdown-item" href="gerenciar_perfis.php">Gerenciar Perfis</a>
                    <a class="dropdown-item" href="logs.php">Logs de Atividades</a>
                    <a class="dropdown-item" href="exportar_relatorios.php">Exportar Relatórios</a>
                </div>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link" href="#" id="navbarDropdownNotif" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Notificações">
                    <i class="fas fa-bell"></i>
                    <?php if ($count_notificacoes > 0): ?>
                        <span class="badge badge-danger" style="position: absolute; top: 5px; right: -5px;"><?php echo $count_notificacoes; ?></span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownNotif">
                    <h6 class="dropdown-header">Notificações</h6>
                    <?php if (count($notificacoes_recentes) > 0): ?>
                        <?php foreach ($notificacoes_recentes as $notificacao): ?>
                            <a class="dropdown-item" href="<?php echo htmlspecialchars($notificacao['link'] ?? '#'); ?>">
                                <small><?php echo date('d/m H:i', strtotime($notificacao['data_criacao'])); ?></small><br>
                                <?php echo htmlspecialchars($notificacao['mensagem']); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <a class="dropdown-item" href="#">Nenhuma nova notificação.</a>
                    <?php endif; ?>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-center" href="#">Ver todas</a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="sair.php">Sair</a>
            </li>
        </ul>
    </div>
</nav>