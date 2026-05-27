<?php
include 'verifica_sessao.php';
include 'config/db_conexao.php';

$dataAduchada = [];
$dataConexoes = [];
$dataTipo = [];
$dataExpiracao = [];

// --- CONSULTAS DE MANGUEIRAS ---

// Query de Aduchada
$queryAduchada = "SELECT aduchada, COUNT(DISTINCT codigo) as total FROM mangueiras GROUP BY aduchada";
$resultAduchada = $conn->query($queryAduchada);
if ($resultAduchada) {
    while ($row = $resultAduchada->fetch_assoc()) {
        $dataAduchada[] = $row;
    }
}

// Query de Conexões
$queryConexoes = "SELECT conexoes, COUNT(DISTINCT codigo) as total FROM mangueiras GROUP BY conexoes";
$resultConexoes = $conn->query($queryConexoes);
if ($resultConexoes) {
    while ($row = $resultConexoes->fetch_assoc()) {
        $dataConexoes[] = $row;
    }
}

// Query por Tipo
$queryTipo = "SELECT tipo, COUNT(DISTINCT codigo) as total FROM mangueiras GROUP BY tipo";
$resultTipo = $conn->query($queryTipo);
if ($resultTipo) {
    while ($row = $resultTipo->fetch_assoc()) {
        $dataTipo[] = $row;
    }
}

// Query de Expiração
$queryExpiracao = "SELECT data_manutencao, MIN(expira_em_dias) as expira_em_dias FROM mangueiras WHERE data_manutencao IS NOT NULL GROUP BY data_manutencao ORDER BY data_manutencao";
$resultExpiracao = $conn->query($queryExpiracao);
if ($resultExpiracao) {
    while ($row = $resultExpiracao->fetch_assoc()) {
        $dataExpiracao[] = $row;
    }
}

// --- CONSULTAS DE HIDRANTES (NBR & CBMERJ) ---

$dataHidranteEsguincho = [];
$queryH_Esguincho = "SELECT esguincho, COUNT(DISTINCT codigo) as total FROM hidrantes WHERE esguincho IS NOT NULL AND esguincho != '' GROUP BY esguincho";
$result = $conn->query($queryH_Esguincho);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dataHidranteEsguincho[] = $row;
    }
}

$dataHidranteChave = [];
$queryH_Chave = "SELECT chave_storz, COUNT(DISTINCT codigo) as total FROM hidrantes WHERE chave_storz IS NOT NULL AND chave_storz != '' GROUP BY chave_storz";
$result = $conn->query($queryH_Chave);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dataHidranteChave[] = $row;
    }
}

$dataHidrantePiso = [];
$queryH_Piso = "SELECT pintura, COUNT(DISTINCT codigo) as total FROM hidrantes WHERE pintura IS NOT NULL AND pintura != '' GROUP BY pintura";
$result = $conn->query($queryH_Piso);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dataHidrantePiso[] = $row;
    }
}

$dataHidranteAbrigo = [];
$queryH_Abrigo = "SELECT abrigo, COUNT(DISTINCT codigo) as total FROM hidrantes WHERE abrigo IS NOT NULL AND abrigo != '' GROUP BY abrigo";
$result = $conn->query($queryH_Abrigo);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dataHidranteAbrigo[] = $row;
    }
}

$dataHidranteConexoes = [];
$queryH_Conexoes = "SELECT conexoes, COUNT(DISTINCT codigo) as total FROM hidrantes WHERE conexoes IS NOT NULL AND conexoes != '' GROUP BY conexoes";
$result = $conn->query($queryH_Conexoes);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dataHidranteConexoes[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Painel Analítico - Gráficos Premium</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="manifest" href="js/manifest.json">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/min/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@1.0.0"></script>
    <style>
        .chart-box {
            background-color: var(--white);
            border-radius: var(--border-radius-md);
            padding: 1.5rem;
            box-shadow: var(--shadow-soft);
            margin-bottom: 30px;
            border-top: 4px solid var(--primary);
            transition: var(--transition-smooth);
        }
        .chart-box:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }
        .chart-box h5 {
            font-weight: 600;
            color: var(--dark-bg);
            border-bottom: 1px solid rgba(0,0,0,0.06);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .nav-tabs .nav-link {
            border: none;
            color: var(--text-muted);
            font-weight: 600;
            padding: 0.8rem 1.5rem;
            transition: var(--transition-smooth);
        }
        .nav-tabs .nav-link.active {
            color: var(--primary) !important;
            border-bottom: 3px solid var(--primary);
            background-color: transparent;
        }
    </style>
</head>
<body>
    <!-- NavBar -->
    <?php include 'navbar.php'; ?>

    <div class="container-fluid px-5">
        <div class="d-flex justify-content-between align-items-center my-4">
            <div>
                <h2 class="font-weight-bold mb-1">Painel Analítico de Conformidade</h2>
                <p class="text-muted">Acompanhamento estatístico dos índices regulatórios ABNT/CBMERJ.</p>
            </div>
            <span class="badge badge-ok"><i class="fas fa-sync-alt mr-1"></i>Atualizado em tempo real</span>
        </div>

        <!-- Abas do Dashboard -->
        <ul class="nav nav-tabs border-bottom mb-4" id="dashboardTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="hidrantes-tab" data-toggle="tab" href="#hidrantes" role="tab" aria-controls="hidrantes" aria-selected="true">
                    <i class="fas fa-fire-extinguisher mr-2"></i>Conformidade de Hidrantes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="mangueiras-tab" data-toggle="tab" href="#mangueiras" role="tab" aria-controls="mangueiras" aria-selected="false">
                    <i class="fas fa-road mr-2"></i>Conformidade de Mangueiras
                </a>
            </li>
        </ul>

        <div class="tab-content" id="dashboardTabsContent">
            <!-- ABA HIDRANTES -->
            <div class="tab-pane fade show active" id="hidrantes" role="tabpanel" aria-labelledby="hidrantes-tab">
                <div class="row">
                    <!-- Abrigo e Acessibilidade (CBMERJ) -->
                    <div class="col-lg-4 col-md-6">
                        <div class="chart-box">
                            <h5><i class="fas fa-door-open mr-2 text-danger"></i>Acesso e Abrigo (CBMERJ)</h5>
                            <canvas id="hidranteAbrigoChart" height="220"></canvas>
                        </div>
                    </div>
                    <!-- Sinalização e Demarcação NBR 7195 -->
                    <div class="col-lg-4 col-md-6">
                        <div class="chart-box">
                            <h5><i class="fas fa-palette mr-2 text-danger"></i>Demarcação de Piso (NBR 7195)</h5>
                            <canvas id="hidrantePisoChart" height="220"></canvas>
                        </div>
                    </div>
                    <!-- Borrachas e Junta Storz -->
                    <div class="col-lg-4 col-md-6">
                        <div class="chart-box">
                            <h5><i class="fas fa-circle-notch mr-2 text-danger"></i>Juntas e Vedação (NBR 13714)</h5>
                            <canvas id="hidranteConexoesChart" height="220"></canvas>
                        </div>
                    </div>
                    <!-- Esguinchos -->
                    <div class="col-lg-6 col-md-6">
                        <div class="chart-box">
                            <h5><i class="fas fa-tint mr-2 text-danger"></i>Presença de Esguinchos</h5>
                            <canvas id="hidranteEsguinchoChart" height="150"></canvas>
                        </div>
                    </div>
                    <!-- Chaves Storz -->
                    <div class="col-lg-6 col-md-6">
                        <div class="chart-box">
                            <h5><i class="fas fa-wrench mr-2 text-danger"></i>Disponibilidade de Chaves Storz</h5>
                            <canvas id="hidranteChaveChart" height="150"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ABA MANGUEIRAS -->
            <div class="tab-pane fade" id="mangueiras" role="tabpanel" aria-labelledby="mangueiras-tab">
                <div class="row">
                    <!-- Enrolamento NBR 12779 -->
                    <div class="col-lg-4 col-md-6">
                        <div class="chart-box">
                            <h5><i class="fas fa-redo mr-2 text-danger"></i>Dobras / Aduchada (NBR 12779)</h5>
                            <canvas id="aduchadaChart" height="220"></canvas>
                        </div>
                    </div>
                    <!-- Conexões de Engate -->
                    <div class="col-lg-4 col-md-6">
                        <div class="chart-box">
                            <h5><i class="fas fa-link mr-2 text-danger"></i>Estado dos Acoplamentos</h5>
                            <canvas id="conexoesChart" height="220"></canvas>
                        </div>
                    </div>
                    <!-- Contagem por Tipo NBR 11861 -->
                    <div class="col-lg-4 col-md-6">
                        <div class="chart-box">
                            <h5><i class="fas fa-tags mr-2 text-danger"></i>Classificação (NBR 11861)</h5>
                            <canvas id="tipoChart" height="220"></canvas>
                        </div>
                    </div>
                    <!-- Tendência de Vencimento -->
                    <div class="col-lg-12">
                        <div class="chart-box">
                            <h5><i class="fas fa-chart-line mr-2 text-danger"></i>Cronograma de Teste Hidrostático (NBR 12779)</h5>
                            <canvas id="expiracaoChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- NavBar Scripts -->
    <?php include 'navbarJS.php'; ?>

    <script>
        // Configurações comuns de design dos gráficos
        const coresStatus = ['#2a9d8f', '#e63946', '#ffb703', '#3a506b'];

        // --- GRAFICOS HIDRANTES ---
        
        // Abrigo
        new Chart(document.getElementById('hidranteAbrigoChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: [<?php foreach ($dataHidranteAbrigo as $d) { echo "'".$d['abrigo']."',"; } ?>],
                datasets: [{
                    data: [<?php foreach ($dataHidranteAbrigo as $d) { echo $d['total'].","; } ?>],
                    backgroundColor: coresStatus,
                    borderWidth: 0
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

        // Sinalização / Pintura
        new Chart(document.getElementById('hidrantePisoChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: [<?php foreach ($dataHidrantePiso as $d) { echo "'".$d['pintura']."',"; } ?>],
                datasets: [{
                    data: [<?php foreach ($dataHidrantePiso as $d) { echo $d['total'].","; } ?>],
                    backgroundColor: coresStatus,
                    borderWidth: 0
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

        // Conexões e Vedações
        new Chart(document.getElementById('hidranteConexoesChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: [<?php foreach ($dataHidranteConexoes as $d) { echo "'".$d['conexoes']."',"; } ?>],
                datasets: [{
                    data: [<?php foreach ($dataHidranteConexoes as $d) { echo $d['total'].","; } ?>],
                    backgroundColor: coresStatus,
                    borderWidth: 0
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

        // Esguincho
        new Chart(document.getElementById('hidranteEsguinchoChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: [<?php foreach ($dataHidranteEsguincho as $d) { echo "'".$d['esguincho']."',"; } ?>],
                datasets: [{
                    label: 'Esguinchos',
                    data: [<?php foreach ($dataHidranteEsguincho as $d) { echo $d['total'].","; } ?>],
                    backgroundColor: '#e63946',
                    borderRadius: 4
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });

        // Chave Storz
        new Chart(document.getElementById('hidranteChaveChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: [<?php foreach ($dataHidranteChave as $d) { echo "'".$d['chave_storz']."',"; } ?>],
                datasets: [{
                    label: 'Chaves Storz',
                    data: [<?php foreach ($dataHidranteChave as $d) { echo $d['total'].","; } ?>],
                    backgroundColor: '#1c2541',
                    borderRadius: 4
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });


        // --- GRAFICOS MANGUEIRAS ---

        // Aduchada
        new Chart(document.getElementById('aduchadaChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: [<?php foreach ($dataAduchada as $d) { echo "'".$d['aduchada']."',"; } ?>],
                datasets: [{
                    data: [<?php foreach ($dataAduchada as $d) { echo $d['total'].","; } ?>],
                    backgroundColor: coresStatus,
                    borderWidth: 0
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

        // Conexões
        new Chart(document.getElementById('conexoesChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: [<?php foreach ($dataConexoes as $d) { echo "'".$d['conexoes']."',"; } ?>],
                datasets: [{
                    data: [<?php foreach ($dataConexoes as $d) { echo $d['total'].","; } ?>],
                    backgroundColor: coresStatus,
                    borderWidth: 0
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

        // Tipo
        new Chart(document.getElementById('tipoChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: [<?php foreach ($dataTipo as $d) { echo "'".$d['tipo']."',"; } ?>],
                datasets: [{
                    label: 'Total de Mangueiras',
                    data: [<?php foreach ($dataTipo as $d) { echo $d['total'].","; } ?>],
                    backgroundColor: '#3a506b',
                    borderRadius: 6
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });

        // Tendência de Expiração
        new Chart(document.getElementById('expiracaoChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: [<?php foreach ($dataExpiracao as $d) { echo "'".$d['data_manutencao']."',"; } ?>],
                datasets: [{
                    label: 'Dias Restantes',
                    data: [<?php foreach ($dataExpiracao as $d) { echo $d['expira_em_dias'].","; } ?>],
                    borderColor: '#e63946',
                    borderWidth: 3,
                    fill: false,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        type: 'time',
                        time: { unit: 'month' }
                    }
                }
            }
        });
    </script>
</body>
</html>
