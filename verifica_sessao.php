<?php
session_start();

// Cabeçalhos de Segurança HTTP
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: default-src 'self' https://maxcdn.bootstrapcdn.com https://cdnjs.cloudflare.com https://code.jquery.com https://cdn.jsdelivr.net; script-src 'self' 'unsafe-inline' https://maxcdn.bootstrapcdn.com https://cdnjs.cloudflare.com https://code.jquery.com https://cdn.jsdelivr.net; img-src 'self' data: https://* http://*; style-src 'self' 'unsafe-inline' https://maxcdn.bootstrapcdn.com https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com;");

// --- CORREÇÃO APLICADA AQUI ---
// Verifica se a variável 'id_usuario' existe na sessão, pois é ela que o login.php cria.
if (!isset($_SESSION['id_usuario'])) {
    // Se não estiver logado, destrói a sessão e redireciona para o login.
    session_destroy();
    header("Location: login.php");
    exit();
}

// Regenera o ID de sessão a cada nova requisição para aumentar a segurança.
// A verificação `isset` garante que não tentemos regenerar uma sessão já destruída.
if (isset($_SESSION['id_usuario'])) {
    session_regenerate_id(true);
}
?>