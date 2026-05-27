<?php
require_once 'config/db_conexao.php';

$sql = "
-- 1. Tabela de Caixas de Hidrante (Pai)
CREATE TABLE IF NOT EXISTS caixas_hidrante (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_caixa VARCHAR(50) NOT NULL UNIQUE,
    local VARCHAR(255),
    predio VARCHAR(100),
    perimetro VARCHAR(100)
);

-- 2. Tabela de Mangueiras (Filha - Relacionamento 1:N)
CREATE TABLE IF NOT EXISTS mangueiras_novo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caixa_id INT NOT NULL,
    tipo VARCHAR(50) DEFAULT 'II',
    diametro_polegadas VARCHAR(50) DEFAULT '1 ½',
    comprimento_metros INT DEFAULT 15,
    FOREIGN KEY (caixa_id) REFERENCES caixas_hidrante(id) ON DELETE CASCADE
);

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

if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    echo "Tabelas criadas com sucesso!\n";
} else {
    echo "Erro ao criar tabelas: " . $conn->error . "\n";
}

$conn->close();
?>
