<?php
require_once 'config/db_conexao.php';

$sql_drop = "
DROP TABLE IF EXISTS historico_inspecoes_mangueira;
DROP TABLE IF EXISTS historico_inspecoes_hidrante;
";

if ($conn->multi_query($sql_drop)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Tabelas antigas removidas.\n";
} else {
    echo "Erro ao remover tabelas: " . $conn->error . "\n";
}

$sql_create = "
-- 3. Histórico de Inspeção do Abrigo
CREATE TABLE IF NOT EXISTS historico_inspecoes_hidrante (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caixa_id INT NOT NULL,
    usuario VARCHAR(100) NOT NULL,
    data_inspecao DATETIME DEFAULT CURRENT_TIMESTAMP,
    esguincho_ok BOOLEAN DEFAULT TRUE,
    chave_storz_ok BOOLEAN DEFAULT TRUE,
    pintura_ok BOOLEAN DEFAULT TRUE,
    abrigo_ok BOOLEAN DEFAULT TRUE,
    comentarios TEXT,
    FOREIGN KEY (caixa_id) REFERENCES caixas_hidrante(id) ON DELETE CASCADE
);

-- 4. Histórico de Inspeção das Mangueiras
CREATE TABLE IF NOT EXISTS historico_inspecoes_mangueira (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mangueira_id INT NOT NULL,
    usuario VARCHAR(100) NOT NULL,
    data_inspecao DATETIME DEFAULT CURRENT_TIMESTAMP,
    aduchada_ok BOOLEAN DEFAULT TRUE,
    conexoes_ok BOOLEAN DEFAULT TRUE,
    proximo_teste DATE NOT NULL,
    comentarios TEXT,
    FOREIGN KEY (mangueira_id) REFERENCES mangueiras_novo(id) ON DELETE CASCADE
);
";

if ($conn->multi_query($sql_create)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Tabelas novas criadas com a arquitetura correta.\n";
} else {
    echo "Erro ao criar tabelas: " . $conn->error . "\n";
}

$conn->close();
?>
