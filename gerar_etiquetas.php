<?php
include 'verifica_sessao.php';
include 'config/db_conexao.php';

// Busca todos os hidrantes e mangueiras
$hidrantes_result = $conn->query("SELECT codigo, local FROM hidrantes ORDER BY codigo");
$mangueiras_result = $conn->query("SELECT codigo, local FROM mangueiras ORDER BY codigo");

// Verifica se as consultas falharam
if (!$hidrantes_result || !$mangueiras_result) {
    die("Erro ao buscar ativos no banco de dados: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerar Etiquetas com QR Code</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        .etiqueta {
            border: 2px dashed #ccc;
            padding: 15px;
            margin: 10px;
            text-align: center;
            width: 280px; /* Largura ajustada */
            display: inline-block; /* Permite que fiquem lado a lado */
            page-break-inside: avoid; /* Evita que a etiqueta seja cortada na impressão */
        }
        /* Estilo para o container do QR Code para garantir o espaçamento */
        .etiqueta .qrcode-container {
            padding: 10px;
            margin-bottom: 10px;
        }
        .etiqueta .qrcode-container img {
             margin: auto; /* Centraliza a imagem do QR Code gerada pelo JS */
        }
        .etiqueta h5 {
            margin-top: 10px;
            font-family: monospace;
            font-size: 1.1rem;
        }
        .etiqueta p {
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        #areaDeImpressao {
            border: 1px solid #eee;
            padding: 20px;
            margin-top: 20px;
        }
        @media print {
            body * { visibility: hidden; }
            #areaDeImpressao, #areaDeImpressao * { visibility: visible; }
            #areaDeImpressao { position: absolute; left: 0; top: 0; width: 100%; border: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid">
        <div class="no-print">
            <h2 class="my-4">Gerar Etiquetas com QR Code</h2>
            <p>Selecione os ativos para gerar as etiquetas ou use a busca para filtrar a lista.</p>
            
            <div class="form-group">
                <input type="text" id="filtroBusca" class="form-control" placeholder="Buscar por código ou local...">
            </div>

            <form id="formEtiquetas">
                <div class="card my-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Hidrantes</h4>
                        <div>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="selecionarTodos('hidrantes', true)">Selecionar Todos</button>
                            <button type="button" class="btn btn-sm btn-light" onclick="selecionarTodos('hidrantes', false)">Desmarcar Todos</button>
                        </div>
                    </div>
                    <div class="card-body" id="lista-hidrantes">
                        <div class="row">
                            <?php while ($row = $hidrantes_result->fetch_assoc()): ?>
                                <div class="col-md-4 item-ativo">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="codigos[]" value="<?php echo htmlspecialchars($row['codigo']); ?>" data-local="<?php echo htmlspecialchars($row['local'] ?? ''); ?>" id="cb-h-<?php echo htmlspecialchars($row['codigo']); ?>">
                                        <label class="form-check-label" for="cb-h-<?php echo htmlspecialchars($row['codigo']); ?>">
                                            <?php echo htmlspecialchars($row['codigo']); ?> (<?php echo htmlspecialchars($row['local'] ?? ''); ?>)
                                        </label>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <div class="card my-3">
                     <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Mangueiras</h4>
                        <div>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="selecionarTodos('mangueiras', true)">Selecionar Todos</button>
                            <button type="button" class="btn btn-sm btn-light" onclick="selecionarTodos('mangueiras', false)">Desmarcar Todos</button>
                        </div>
                    </div>
                    <div class="card-body" id="lista-mangueiras">
                        <div class="row">
                            <?php while ($row = $mangueiras_result->fetch_assoc()): ?>
                                <div class="col-md-4 item-ativo">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="codigos[]" value="<?php echo htmlspecialchars($row['codigo']); ?>" data-local="<?php echo htmlspecialchars($row['local'] ?? ''); ?>" id="cb-m-<?php echo htmlspecialchars($row['codigo']); ?>">
                                        <label class="form-check-label" for="cb-m-<?php echo htmlspecialchars($row['codigo']); ?>">
                                            <?php echo htmlspecialchars($row['codigo']); ?> (<?php echo htmlspecialchars($row['local'] ?? ''); ?>)
                                        </label>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <button type="button" id="btnGerar" class="btn btn-primary mt-3">Gerar Visualização</button>
                <button type="button" onclick="window.print()" class="btn btn-success mt-3">Imprimir Etiquetas Selecionadas</button>
            </form>
            <hr>
        </div>
        
        <h4 class="no-print">Pré-visualização para Impressão</h4>
        <div id="areaDeImpressao">
            </div>
    </div>

    <?php include 'navbarJS.php'; ?>
    <script>
        function selecionarTodos(tipo, selecionar) {
            const container = document.getElementById('lista-' + tipo);
            const checkboxes = container.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(cb => cb.checked = selecionar);
        }

        document.getElementById('filtroBusca').addEventListener('keyup', function() {
            const filtro = this.value.toLowerCase();
            const itens = document.querySelectorAll('.item-ativo');
            itens.forEach(function(item) {
                const textoLabel = item.querySelector('label').textContent.toLowerCase();
                if (textoLabel.includes(filtro)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // --- LÓGICA DE GERAÇÃO DE ETIQUETAS COM QRCODE.JS ---
        document.getElementById('btnGerar').addEventListener('click', function() {
            const areaImpressao = document.getElementById('areaDeImpressao');
            areaImpressao.innerHTML = ''; // Limpa a área
            const checkboxes = document.querySelectorAll('input[name="codigos[]"]:checked');
            
            checkboxes.forEach(function(checkbox, index) {
                const codigo = checkbox.value;
                const local = checkbox.getAttribute('data-local') || '';
                const urlBase = "https://enilton.com.br/hidrantes/"; // URL base do sistema
                const urlParaInspecao = urlBase + 'inspecao.php?codigo=' + encodeURIComponent(codigo);

                // 1. Cria o contêiner principal da etiqueta
                const etiquetaDiv = document.createElement('div');
                etiquetaDiv.className = 'etiqueta';
                
                // 2. Cria um contêiner SÓ para o QR Code
                const qrcodeContainer = document.createElement('div');
                qrcodeContainer.className = 'qrcode-container';
                qrcodeContainer.id = 'qrcode-' + index; // ID único para cada QR Code
                
                // 3. Cria os outros elementos da etiqueta (textos)
                const codigoH5 = document.createElement('h5');
                codigoH5.textContent = codigo;

                const localP = document.createElement('p');
                localP.textContent = "Local: " + local;

                const michelinP = document.createElement('p');
                michelinP.innerHTML = '<strong>Michelin</strong><br>Sistema de Hidrantes';
                michelinP.style.marginTop = '10px';
                
                // 4. Monta a estrutura: QR Code, depois os textos
                etiquetaDiv.appendChild(qrcodeContainer);
                etiquetaDiv.appendChild(codigoH5);
                etiquetaDiv.appendChild(localP);
                etiquetaDiv.appendChild(michelinP);
                
                // 5. Adiciona a etiqueta completa na área de visualização
                areaImpressao.appendChild(etiquetaDiv);

                // 6. GERA O QR CODE usando a biblioteca JS
                new QRCode(document.getElementById('qrcode-' + index), {
                    text: urlParaInspecao,
                    width: 128,
                    height: 128,
                    colorDark : "#000000",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
            });
        });
    </script>
</body>
</html>