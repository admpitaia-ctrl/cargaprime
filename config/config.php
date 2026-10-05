<?php
declare(strict_types=1);

// Configuração geral. O caminho é detetado automaticamente, mesmo que a pasta seja renomeada.
const APP_NAME = 'CargaPrime';
// Domínio académico simulado para demonstrar planeamento de publicação e SEO.
// O website continua a funcionar localmente no XAMPP; não existe compra real do domínio.
const APP_DOMAIN = 'cargaprime.pt';
const APP_CANONICAL_BASE_URL = 'https://www.cargaprime.pt';
const FACTORY_NAME = 'CargaPrime — Unidade Industrial';
const FACTORY_ADDRESS = 'Zona Industrial de Coimbra, Lote 12, 3020-000 Coimbra';
const FACTORY_PHONE = '+351 239 000 000';
const FACTORY_EMAIL = 'apoio@cargaprime.pt';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function url(string $path = ''): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $projectRoot = str_contains($scriptName, '/admin/')
        ? dirname(dirname($scriptName))
        : dirname($scriptName);
    $baseUrl = $projectRoot === '/' ? '' : rtrim($projectRoot, '/');
    return $baseUrl . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/**
 * Deteta pedidos enviados pelo JavaScript.
 *
 * Alguns servidores Apache/XAMPP não disponibilizam o cabeçalho
 * X-Requested-With ao PHP. O navegador também envia Accept: application/json,
 * por isso aceitamos os dois sinais para nunca devolver HTML a um formulário
 * que está à espera de JSON.
 */
function is_json_request(): bool
{
    $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));

    return $requestedWith === 'xmlhttprequest'
        || str_contains($accept, 'application/json');
}

function json_response(bool $ok, string $message, int $statusCode = 200, array $extra = []): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function validate_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        if (is_json_request()) {
            json_response(false, 'A sessão expirou. Atualize a página e tente novamente.', 419);
        }
        http_response_code(419);
        exit('Pedido inválido. Atualize a página e tente novamente.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = compact('type', 'message');
}

function pull_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function is_admin(): bool
{
    return ($_SESSION['user']['role'] ?? '') === 'admin';
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']['id']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        if (is_json_request()) {
            json_response(false, 'A sessão terminou. Inicie sessão novamente.', 401);
        }
        flash('error', 'Inicie sessão para continuar a encomenda.');
        redirect('login.php');
    }
}

function require_admin(): void
{
    if (!is_admin()) {
        if (is_json_request()) {
            json_response(false, 'A sessão de administrador terminou. Inicie sessão novamente.', 403);
        }
        flash('error', 'Acesso reservado ao administrador.');
        redirect('login.php');
    }
}
