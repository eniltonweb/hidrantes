<?php
include 'verifica_sessao.php'; // Verifica se o usuário está autenticado
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Hidrantes e Mangueiras</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
	<link rel="manifest" href="js/manifest.json">
</head>
<body>
    <!-- NavBar -->
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h1 class="my-4">Bem-vindo ao Sistema de Hidrantes e Mangueiras</h1>
        <!-- Conteúdo adicional da página inicial -->
    </div>

    <!-- IndexedDB e Service Worker -->
    <script src="js/indexedDB.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('js/service-worker.js').then(function(reg) {
                console.log('Service Worker registrado com sucesso:', reg);
            }).catch(function(err) {
                console.warn('Erro ao registrar o Service Worker:', err);
            });
        }
    </script>

    <!-- NavBar Scripts -->
    <?php include 'navbarJS.php'; ?>
</body>
</html>