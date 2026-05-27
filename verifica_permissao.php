<?php
// Função para verificar permissões do usuário
function verificar_permissao($tipo_requerido) {
    if ($_SESSION['tipo_usuario'] !== $tipo_requerido) {
        die('Acesso negado: Você não tem permissão para acessar esta página.');
    }
}
?>
