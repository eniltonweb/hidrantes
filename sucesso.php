<?php
include 'verifica_sessao.php';

// Detecta se a gravação foi offline
$is_offline = isset($_GET['offline']) && $_GET['offline'] === 'true';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sucesso - Sistema Premium de Inspeções</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <!-- NavBar -->
    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7 text-center">
                <div class="card p-5 border-0 shadow-lg mt-5">
                    <div class="card-body">
                        <?php if ($is_offline): ?>
                            <!-- Feedback de Gravação Offline -->
                            <div class="mb-4">
                                <span class="fa-stack fa-3x">
                                    <i class="fas fa-circle fa-stack-2x text-warning" style="opacity: 0.15;"></i>
                                    <i class="fas fa-wifi fa-stack-1x text-warning"></i>
                                </span>
                            </div>
                            <h2 class="font-weight-bold mb-3 text-warning">Inspeção Salva Offline!</h2>
                            <p class="text-muted px-3">
                                Você está desconectado da internet. Não se preocupe! Os dados técnicos foram gravados com segurança no banco de dados local do seu dispositivo e serão sincronizados automaticamente assim que você se conectar à internet.
                            </p>
                            <div class="alert alert-warning py-2 mb-4" role="alert">
                                <i class="fas fa-info-circle mr-2"></i><strong>Evite limpar os dados do navegador</strong> até a sincronização.
                            </div>
                        <?php else: ?>
                            <!-- Feedback de Gravação Online Síncrona -->
                            <div class="mb-4">
                                <span class="fa-stack fa-3x">
                                    <i class="fas fa-circle fa-stack-2x text-success" style="opacity: 0.15;"></i>
                                    <i class="fas fa-check-circle fa-stack-1x text-success"></i>
                                </span>
                            </div>
                            <h2 class="font-weight-bold mb-3 text-success">Inspeção Registrada com Sucesso!</h2>
                            <p class="text-muted px-3">
                                Os registros foram enviados em tempo real e consolidados com sucesso no banco de dados central MySQL, em estrita conformidade com as diretrizes reguladoras.
                            </p>
                        <?php endif; ?>

                        <div class="mt-4 d-flex flex-column gap-2 px-4">
                            <a href="inspecao.php" class="btn btn-primary btn-block btn-lg mb-2">
                                <i class="fas fa-plus-circle mr-2"></i>Nova Inspeção
                            </a>
                            <a href="gerenciar_inspecoes.php" class="btn btn-secondary btn-block">
                                <i class="fas fa-list-ul mr-2"></i>Visualizar Inspeções
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- NavBar Scripts -->
    <?php include 'navbarJS.php'; ?>
    <script src="js/indexedDB.js"></script>
    <script>
        // Tenta disparar a sincronização se voltar a ficar online na tela de sucesso
        if (navigator.onLine && typeof iniciarSincronizacao === 'function') {
            iniciarSincronizacao();
        }
    </script>
</body>
</html>
