<?php
// Habilita a exibição de TODOS os erros para depuração.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Caminho para o autoload do Composer.
$autoload_path = __DIR__ . '/vendor/autoload.php';

if (!file_exists($autoload_path)) {
    http_response_code(500);
    die("ERRO CRÍTICO: Autoload do Composer não encontrado. Verifique a instalação.");
}
require $autoload_path;

// Importa a classe principal da biblioteca.
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

if (!isset($_GET['codigo']) || empty($_GET['codigo'])) {
    http_response_code(400);
    die("Erro: O parâmetro 'codigo' não foi fornecido.");
}

$codigo = htmlspecialchars($_GET['codigo']);

// IMPORTANTE: Verifique se esta URL está correta.
$url_base = "https://enilton.com.br/hidrantes/";
$urlParaInspecao = $url_base . 'inspecao.php?codigo=' . urlencode($codigo);

try {
    // --- MÉTODO CLÁSSICO E MAIS COMPATÍVEL ---
    // Instancia o objeto QrCode diretamente com a URL.
    $qrCode = QrCode::create($urlParaInspecao)
        ->setEncoding(new Encoding('UTF-8'))
        ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
        ->setSize(150)
        ->setMargin(10)
        ->setForegroundColor(new Color(0, 0, 0))
        ->setBackgroundColor(new Color(255, 255, 255));

    // Cria um "escritor" para o formato PNG
    $writer = new PngWriter();

    // Gera o resultado (a imagem do QR Code)
    $result = $writer->write($qrCode);

    // Envia o cabeçalho de imagem e a imagem gerada.
    header('Content-Type: ' . $result->getMimeType());
    echo $result->getString();

} catch (Exception $e) {
    // Se ocorrer um erro durante a geração do QR Code.
    http_response_code(500);
    echo "Ocorreu um erro ao gerar o QR Code: " . $e->getMessage();
}