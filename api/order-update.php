<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Método não permitido.', 405);
}

if (!is_admin()) {
    json_response(false, 'Sessão de administrador inválida. Inicie sessão novamente.', 403);
}

$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    json_response(false, 'A página expirou. Atualize-a e tente novamente.', 419);
}

$allowed = ['Pendente', 'Aprovada', 'Pronta', 'A caminho', 'Concluída', 'Cancelada'];
$orderId = (int) ($_POST['order_id'] ?? 0);
$status = trim((string) ($_POST['status'] ?? ''));
$tracking = trim((string) ($_POST['tracking_url'] ?? ''));

if ($orderId < 1 || !in_array($status, $allowed, true)) {
    json_response(false, 'Encomenda ou estado inválido.', 422);
}
if ($tracking !== '' && !filter_var($tracking, FILTER_VALIDATE_URL)) {
    json_response(false, 'Cole um link completo do Google Maps, começando por https://.', 422);
}

try {
    $stmt = db()->prepare('UPDATE orders SET status = ?, tracking_url = ? WHERE id = ?');
    $stmt->execute([$status, $tracking !== '' ? $tracking : null, $orderId]);
    if ($stmt->rowCount() === 0) {
        $exists = db()->prepare('SELECT id FROM orders WHERE id = ?');
        $exists->execute([$orderId]);
        if (!$exists->fetch()) json_response(false, 'A encomenda já não existe.', 404);
    }
    json_response(true, $tracking !== ''
        ? 'Estado e link de localização guardados.'
        : 'Estado guardado. Não foi indicado um link de localização.');
} catch (PDOException $error) {
    error_log($error->getMessage());
    json_response(false, 'Não foi possível guardar na base de dados. Consulte o registo de erros do Apache.', 500);
}
