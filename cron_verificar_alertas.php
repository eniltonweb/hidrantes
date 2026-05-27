<?php
// Este script deve ser protegido e não acessível publicamente
include 'config/db_conexao.php';

echo "Iniciando verificação de alertas...\n";

// --- Alerta 1: Mangueiras próximas do vencimento do teste hidrostático ---
$dias_para_alerta = 30;
$query_mangueiras = "
    SELECT 
        CONCAT(c.codigo_caixa, ' (M', m.id, ')') as codigo, 
        DATEDIFF(h.proximo_teste, CURDATE()) as expira_em_dias 
    FROM mangueiras_novo m
    JOIN caixas_hidrante c ON m.caixa_id = c.id
    JOIN (
        SELECT h1.* FROM historico_inspecoes_mangueira h1
        INNER JOIN (
            SELECT mangueira_id, MAX(id) as max_id FROM historico_inspecoes_mangueira GROUP BY mangueira_id
        ) h2 ON h1.id = h2.max_id
    ) h ON m.id = h.mangueira_id
    WHERE DATEDIFF(h.proximo_teste, CURDATE()) <= ?
";
$stmt = $conn->prepare($query_mangueiras);
$stmt->bind_param("i", $dias_para_alerta);
$stmt->execute();
$result = $stmt->get_result();

while ($mangueira = $result->fetch_assoc()) {
    $mensagem = "Alerta: A mangueira " . $mangueira['codigo'] . " vence em " . $mangueira['expira_em_dias'] . " dias!";
    $link = "historico_ativo.php?codigo=" . $mangueira['codigo'];
    
    // Insere a notificação (para todos os administradores, id_usuario = NULL)
    $stmt_insert = $conn->prepare("INSERT INTO notificacoes (mensagem, link) VALUES (?, ?)");
    $stmt_insert->bind_param("ss", $mensagem, $link);
    $stmt_insert->execute();
    echo "Notificação criada: $mensagem\n";
}
$stmt->close();

// --- Alerta 2: Inspeções com itens "Não Ok" (exemplo para hidrantes) ---
// Esta é uma lógica mais complexa, idealmente você teria uma tabela de 'inspecoes'
// Por agora, podemos criar um alerta genérico se o último vistoriador marcou algo como Não Ok
// Esta parte pode ser expandida no futuro.

echo "Verificação de alertas concluída.\n";
$conn->close();

/*
CONFIGURAÇÃO DO CRON JOB NO SERVIDOR (Exemplo):
# Executa o script de verificação todos os dias à 1 da manhã
0 1 * * * /usr/bin/php /caminho/completo/para/seu/projeto/cron_verificar_alertas.php
*/
?>