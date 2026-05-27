<?php
// Habilitar exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'verifica_sessao.php';
include 'config/db_conexao.php';

// Verifica se a sessão 'nome' existe, senão, atribui um valor padrão.
$vistoriador = $_SESSION['nome'] ?? 'usuário desconhecido';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['token'], $_POST['csrf_token'])) {
        die('Falha na validação do token CSRF');
    }

    $id_ativo = htmlspecialchars($_POST['id_ativo']);
    $tipo_ativo = htmlspecialchars($_POST['tipo_ativo']);
    $codigo_ativo_log = htmlspecialchars($_POST['codigo_ativo_log']);

    // Log no MySQL
    $acao = "Inspeção realizada no ativo " . $codigo_ativo_log . " por " . $vistoriador;
    $stmt_log = $conn->prepare("INSERT INTO logs (usuario, acao) VALUES (?, ?)");
    $stmt_log->bind_param("ss", $vistoriador, $acao);
    $stmt_log->execute();

    if ($tipo_ativo == 'hidrante') {
        // Processa a inspeção de um HIDRANTE (Checklist em Conformidade NBR 13714/7195 e CBMERJ COSCIP)
        $esguincho = htmlspecialchars($_POST['esguincho']);
        $chave_storz = htmlspecialchars($_POST['chave_storz']);
        $pintura = htmlspecialchars($_POST['pintura']); // Representa Sinalização do Piso NBR 7195
        $abrigo = htmlspecialchars($_POST['abrigo']);   // Representa Acesso e Desobstrução CBMERJ
        $conexoes = htmlspecialchars($_POST['conexoes']); // Junta Storz e Anel de Vedação NBR 13714
        $comentarios = htmlspecialchars($_POST['comentarios_hidrante']);

        // 1. Inserir no Histórico (Auditoria)
        $stmt_hist = $conn->prepare("INSERT INTO historico_inspecoes_hidrante (id_hidrante, vistoriador, esguincho, chave_storz, pintura, abrigo, conexoes, comentarios) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_hist->bind_param("isssssss", $id_ativo, $vistoriador, $esguincho, $chave_storz, $pintura, $abrigo, $conexoes, $comentarios);
        $stmt_hist->execute();
        $stmt_hist->close();

        // 2. Atualizar o "Estado Atual" na tabela principal
        $stmt = $conn->prepare("UPDATE hidrantes SET esguincho = ?, chave_storz = ?, pintura = ?, abrigo = ?, conexoes = ?, vistoriador = ?, comentarios = ?, data_manutencao = NOW() WHERE id_hidrante = ?");
        $stmt->bind_param("sssssssi", $esguincho, $chave_storz, $pintura, $abrigo, $conexoes, $vistoriador, $comentarios, $id_ativo);
    
    } elseif ($tipo_ativo == 'mangueira') {
        // Processa a inspeção de uma MANGUEIRA (Checklist em Conformidade NBR 12779)
        $aduchada = htmlspecialchars($_POST['aduchada']); // Enrolamento Aduchado e Secagem NBR 12779
        $conexoes = htmlspecialchars($_POST['conexoes']); // Garras Storz e Vedação NBR 12779
        $teste_hidrostatico_proximo = htmlspecialchars($_POST['teste_hidrostatico_proximo']);
        
        $conservacao = htmlspecialchars($_POST['conservacao_carcaca']); // Estado da carcaça têxtil NBR 12779
        $comentarios_raw = htmlspecialchars($_POST['comentarios_mangueira']);
        $comentarios = "Carcaça Têxtil: " . $conservacao . ". " . $comentarios_raw;
        
        $expira_em_dias = 0;
        if(!empty($teste_hidrostatico_proximo)){
            $hoje = new DateTime();
            $proximo_teste = new DateTime($teste_hidrostatico_proximo);
            $diferenca = $hoje->diff($proximo_teste);
            $expira_em_dias = $diferenca->invert ? 0 : $diferenca->days;
        }

        // 1. Inserir no Histórico (Auditoria)
        $stmt_hist = $conn->prepare("INSERT INTO historico_inspecoes_mangueira (id_mangueira, vistoriador, aduchada, conexoes, teste_hidrostatico_proximo, expira_em_dias, comentarios) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_hist->bind_param("issssis", $id_ativo, $vistoriador, $aduchada, $conexoes, $teste_hidrostatico_proximo, $expira_em_dias, $comentarios);
        $stmt_hist->execute();
        $stmt_hist->close();

        // 2. Atualizar o "Estado Atual" na tabela principal
        $stmt = $conn->prepare("UPDATE mangueiras SET aduchada = ?, conexoes = ?, teste_hidrostatico_proximo = ?, expira_em_dias = ?, comentarios = ?, vistoriador = ?, data_manutencao = NOW() WHERE id_mangueira = ?");
        $stmt->bind_param("sssissi", $aduchada, $conexoes, $teste_hidrostatico_proximo, $expira_em_dias, $comentarios, $vistoriador, $id_ativo);
    }
    
    if (isset($stmt)) {
        $stmt->execute();
        $stmt->close();
    }
    
    $stmt_log->close();
    $conn->close();

    header("Location: sucesso.php");
    exit();
}

// Lógica para QR Code e preenchimento da lista de ativos
$codigo_qr = isset($_GET['codigo']) ? htmlspecialchars($_GET['codigo']) : '';
$todos_ativos = [];
$result_hidrantes = $conn->query("SELECT id_hidrante as id, codigo, local FROM hidrantes ORDER BY codigo");
if($result_hidrantes) {
    while($row = $result_hidrantes->fetch_assoc()) {
        $todos_ativos[] = ['id' => $row['id'], 'codigo' => $row['codigo'], 'local' => $row['local'] ?? '', 'tipo' => 'hidrante'];
    }
}
$result_mangueiras = $conn->query("SELECT id_mangueira as id, codigo, local FROM mangueiras ORDER BY codigo");
if($result_mangueiras){
    while($row = $result_mangueiras->fetch_assoc()) {
        $todos_ativos[] = ['id' => $row['id'], 'codigo' => $row['codigo'], 'local' => $row['local'] ?? '', 'tipo' => 'mangueira'];
    }
}

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Registrar Inspeção - Sistema Premium</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="manifest" href="js/manifest.json">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card mt-4 shadow-lg border-0">
                    <div class="card-header text-center py-4 bg-transparent border-0">
                        <i class="fas fa-clipboard-check fa-3x text-danger mb-2"></i>
                        <h2 class="section-title text-center">Registrar Nova Inspeção</h2>
                        <p class="text-muted">Certifique-se do atendimento pleno às regulamentações técnicas do CBMERJ e ABNT (NBR 12779 e NBR 13714).</p>
                    </div>
                    <div class="card-body px-5 pb-5">
                        <form method="POST" action="inspecao.php" id="inspecaoForm">
                            <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo htmlspecialchars($_SESSION['token']); ?>">
                            <input type="hidden" name="tipo_ativo" id="tipo_ativo" value="">
                            <input type="hidden" name="codigo_ativo_log" id="codigo_ativo_log" value="">

                            <div class="form-group mb-4">
                                <label for="id_ativo"><i class="fas fa-qrcode mr-2 text-danger"></i>Selecione o Ativo (Hidrante ou Mangueira)</label>
                                <select id="id_ativo" name="id_ativo" class="form-control form-control-lg" required>
                                    <option value="">-- Selecione um equipamento cadastrado --</option>
                                    <?php foreach ($todos_ativos as $ativo): ?>
                                        <?php 
                                            $texto_opcao = htmlspecialchars($ativo['codigo'] . " (" . ucfirst($ativo['tipo']) . ") - " . ($ativo['local'] ?? 'Sem local'));
                                            $valor_opcao = htmlspecialchars($ativo['id']) . '|' . htmlspecialchars($ativo['tipo']) . '|' . htmlspecialchars($ativo['codigo']);
                                            $selecionado = ($codigo_qr == $ativo['codigo']) ? 'selected' : '';
                                        ?>
                                        <option value="<?php echo $valor_opcao; ?>" <?php echo $selecionado; ?>><?php echo $texto_opcao; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- SEÇÃO DE DETALHES DO HIDRANTE (NBR 13714 / NBR 7195 e CBMERJ COSCIP) -->
                            <div id="campos_hidrante" style="display: none;" class="border rounded p-4 mb-4 bg-light">
                                <h4 class="mb-3 text-danger border-bottom pb-2"><i class="fas fa-fire-extinguisher mr-2"></i>Conformidade do Hidrante</h4>
                                
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="esguincho"><i class="fas fa-tint mr-1 text-muted"></i>Esguincho:</label>
                                        <select id="esguincho" name="esguincho" class="form-control">
                                            <option value="Ok">Ok (Presente e Operacional)</option>
                                            <option value="Não Ok">Não Ok (Ausente ou Quebrado)</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="chave_storz"><i class="fas fa-wrench mr-1 text-muted"></i>Chave Storz:</label>
                                        <select id="chave_storz" name="chave_storz" class="form-control">
                                            <option value="Ok">Ok (Disponível no Abrigo)</option>
                                            <option value="Não Ok">Não Ok (Ausente ou Incompatível)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="pintura"><i class="fas fa-palette mr-1 text-muted"></i>Sinalização e Piso (NBR 7195):</label>
                                        <select id="pintura" name="pintura" class="form-control">
                                            <option value="Ok">Ok (Piso Demarcado / Pintura)</option>
                                            <option value="Não Ok">Não Ok (Fora de Padrão / Obstruído)</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="abrigo"><i class="fas fa-door-open mr-1 text-muted"></i>Abrigo e Acesso (CBMERJ):</label>
                                        <select id="abrigo" name="abrigo" class="form-control">
                                            <option value="Ok">Ok (Livre / Trinco de Acesso Rápido)</option>
                                            <option value="Não Ok">Não Ok (Obstruído / Trancado)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="conexoes_hidrante"><i class="fas fa-circle-notch mr-1 text-muted"></i>Junta Storz e Vedação (NBR 13714):</label>
                                    <select id="conexoes_hidrante" name="conexoes" class="form-control">
                                        <option value="Ok">Ok (Acoplamentos e Borrachas íntegras)</option>
                                        <option value="Não Ok">Não Ok (Borracha Ressecada / Vazamento)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="comentarios_hidrante"><i class="fas fa-comment-alt mr-1 text-muted"></i>Comentários do Técnico:</label>
                                    <textarea id="comentarios_hidrante" name="comentarios_hidrante" class="form-control" rows="3" placeholder="Insira observações relevantes sobre o estado físico deste hidrante..."></textarea>
                                </div>
                            </div>

                            <!-- SEÇÃO DE DETALHES DA MANGUEIRA (NBR 12779) -->
                            <div id="campos_mangueira" style="display: none;" class="border rounded p-4 mb-4 bg-light">
                                <h4 class="mb-3 text-danger border-bottom pb-2"><i class="fas fa-road mr-2"></i>Conformidade da Mangueira</h4>
                                
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="aduchada"><i class="fas fa-redo mr-1 text-muted"></i>Enrolamento (NBR 12779):</label>
                                        <select id="aduchada" name="aduchada" class="form-control">
                                            <option value="Ok">Ok (Aduchada ou Espiral e Seca)</option>
                                            <option value="Não Ok">Não Ok (Incorreta / Úmida com Risco de Mofo)</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="conexoes_mangueira"><i class="fas fa-link mr-1 text-muted"></i>Conexões Storz e Engate:</label>
                                        <select id="conexoes_mangueira" name="conexoes" class="form-control">
                                            <option value="Ok">Ok (Garras de Acoplamento Operantes)</option>
                                            <option value="Não Ok">Não Ok (Garras Amassadas ou Desgastadas)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="conservacao_carcaca"><i class="fas fa-shield-alt mr-1 text-muted"></i>Carcaça Têxtil (NBR 12779):</label>
                                        <select id="conservacao_carcaca" name="conservacao_carcaca" class="form-control">
                                            <option value="Ok">Ok (Sem furos, mofo ou fios desfiados)</option>
                                            <option value="Não Ok">Não Ok (Presença de furos, manchas de mofo ou cortes)</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="teste_hidrostatico_proximo"><i class="fas fa-calendar-check mr-1 text-muted"></i>Próximo Teste Hidrostático (Anual):</label>
                                        <input type="date" id="teste_hidrostatico_proximo" name="teste_hidrostatico_proximo" class="form-control">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="comentarios_mangueira"><i class="fas fa-comment-alt mr-1 text-muted"></i>Comentários do Técnico:</label>
                                    <textarea id="comentarios_mangueira" name="comentarios_mangueira" class="form-control" rows="3" placeholder="Observações de conservação ou histórico do teste da mangueira..."></textarea>
                                </div>
                            </div>

                            <button type="submit" id="btn_submit" class="btn btn-primary btn-block btn-lg mt-4" style="display: none;">
                                <i class="fas fa-check-circle mr-2"></i>Registrar Inspeção
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'navbarJS.php'; ?>
    <script src="js/indexedDB.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAtivo = document.getElementById('id_ativo');
            const tipoAtivoInput = document.getElementById('tipo_ativo');
            const codigoAtivoLogInput = document.getElementById('codigo_ativo_log');
            const camposHidrante = document.getElementById('campos_hidrante');
            const camposMangueira = document.getElementById('campos_mangueira');
            const btnSubmit = document.getElementById('btn_submit');

            function toggleFormFields() {
                const selectedOptionValue = selectAtivo.value;
                
                camposHidrante.style.display = 'none';
                camposMangueira.style.display = 'none';
                btnSubmit.style.display = 'none';

                // Desabilita os campos para não enviarem valores errados no POST
                camposHidrante.querySelectorAll('input, select, textarea').forEach(el => el.disabled = true);
                camposMangueira.querySelectorAll('input, select, textarea').forEach(el => el.disabled = true);

                document.getElementById('teste_hidrostatico_proximo').required = false;

                if (selectedOptionValue) {
                    const [id, tipo, codigo] = selectedOptionValue.split('|');
                    tipoAtivoInput.value = tipo;
                    codigoAtivoLogInput.value = codigo;
                    
                    selectAtivo.name = 'id_ativo_full';
                    
                    let hiddenIdInput = document.getElementById('hidden_id_ativo');
                    if (!hiddenIdInput) {
                        hiddenIdInput = document.createElement('input');
                        hiddenIdInput.type = 'hidden';
                        hiddenIdInput.id = 'hidden_id_ativo';
                        hiddenIdInput.name = 'id_ativo';
                        selectAtivo.form.appendChild(hiddenIdInput);
                    }
                    hiddenIdInput.value = id;

                    if (tipo === 'hidrante') {
                        camposHidrante.style.display = 'block';
                        btnSubmit.style.display = 'block';
                        camposHidrante.querySelectorAll('input, select, textarea').forEach(el => el.disabled = false);
                    } else if (tipo === 'mangueira') {
                        camposMangueira.style.display = 'block';
                        btnSubmit.style.display = 'block';
                        camposMangueira.querySelectorAll('input, select, textarea').forEach(el => el.disabled = false);
                        document.getElementById('teste_hidrostatico_proximo').required = true;
                    }
                }
            }
            
            selectAtivo.addEventListener('change', toggleFormFields);
            toggleFormFields();

            // INTERCEPTADOR DE SUBMISSÃO OFFLINE (PWA INTEGRATION)
            document.getElementById('inspecaoForm').addEventListener('submit', function(event) {
                if (!navigator.onLine) {
                    event.preventDefault(); // Cancela envio tradicional POST ao PHP
                    
                    const selectVal = selectAtivo.value;
                    if (!selectVal) return;

                    const [id, tipo, codigo] = selectVal.split('|');
                    const vistoriadorAtual = "<?php echo htmlspecialchars($vistoriador); ?>";

                    const inspecaoData = {
                        id_ativo: id,
                        tipo_ativo: tipo,
                        codigo: codigo,
                        vistoriador: vistoriadorAtual,
                        data_manutencao: new Date().toISOString().slice(0, 19).replace('T', ' '),
                        online: false
                    };

                    if (tipo === 'hidrante') {
                        inspecaoData.esguincho = document.getElementById('esguincho').value;
                        inspecaoData.chave_storz = document.getElementById('chave_storz').value;
                        inspecaoData.pintura = document.getElementById('pintura').value;
                        inspecaoData.abrigo = document.getElementById('abrigo').value;
                        inspecaoData.conexoes = document.getElementById('conexoes_hidrante').value;
                        inspecaoData.comentarios = document.getElementById('comentarios_hidrante').value;
                    } else if (tipo === 'mangueira') {
                        inspecaoData.aduchada = document.getElementById('aduchada').value;
                        inspecaoData.conexoes = document.getElementById('conexoes_mangueira').value;
                        inspecaoData.teste_hidrostatico_proximo = document.getElementById('teste_hidrostatico_proximo').value;
                        
                        const conservacao = document.getElementById('conservacao_carcaca').value;
                        const coms = document.getElementById('comentarios_mangueira').value;
                        inspecaoData.comentarios = `Carcaça Têxtil: ${conservacao}. ${coms}`;
                    }

                    // Grava no IndexedDB do navegador
                    if (typeof salvarInspecaoLocal === 'function') {
                        salvarInspecaoLocal(inspecaoData);

                        // Cria log local
                        const logData = {
                            usuario: vistoriadorAtual,
                            acao: `Inspeção realizada OFFLINE no ativo ${codigo} (${tipo}) por ${vistoriadorAtual}`,
                            data_hora: new Date().toISOString().slice(0, 19).replace('T', ' ')
                        };
                        salvarLogLocal(logData);

                        // Redireciona para página de sucesso com aviso de sincronização pendente
                        window.location.href = "sucesso.php?offline=true";
                    } else {
                        alert("Erro crítico: Mecanismo offline PWA indisponível.");
                    }
                }
            });
        });
    </script>
</body>
</html>