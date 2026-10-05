<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $requestId = (int) ($_POST['request_id'] ?? 0);
    $message = trim((string) ($_POST['message'] ?? ''));
    $ok = true;

    $exists = $pdo->prepare('SELECT id, factory_name FROM support_requests WHERE id = ?');
    $exists->execute([$requestId]);
    $ticket = $exists->fetch();

    if ($message === '' || !$ticket) {
        $ok = false;
        $notice = 'Conversa ou mensagem inválida.';
    } else {
        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT INTO support_messages(request_id,sender_role,message) VALUES (?,'admin',?)")
                ->execute([$requestId, $message]);
            $pdo->prepare("UPDATE support_requests SET status='Respondido' WHERE id=?")
                ->execute([$requestId]);
            $pdo->commit();
            $notice = 'Resposta enviada ao cliente em nome da fábrica responsável' . (!empty($ticket['factory_name']) ? ': ' . $ticket['factory_name'] : '') . '.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    if (is_json_request()) {
        json_response($ok, $notice, $ok ? 200 : 422, ['refresh' => $ok ? ['.support-conversations'] : []]);
    }
    flash($ok ? 'success' : 'error', $notice);
    redirect('admin/support.php');
}

$tickets = $pdo->query(
    "SELECT s.*, u.name AS customer_name,
            COALESCE(s.factory_name, oi.factory_name, 'Fábrica não identificada') AS resolved_factory_name,
            COALESCE(s.factory_address, oi.factory_address) AS resolved_factory_address,
            oi.product_name
     FROM support_requests s
     JOIN users u ON u.id = s.user_id
     LEFT JOIN order_items oi ON oi.id = s.order_item_id
     ORDER BY s.created_at DESC"
)->fetchAll();

foreach ($tickets as &$ticket) {
    $m = $pdo->prepare('SELECT * FROM support_messages WHERE request_id = ? ORDER BY created_at');
    $m->execute([(int) $ticket['id']]);
    $ticket['messages'] = $m->fetchAll();
}
unset($ticket);

$pageTitle = 'Conversas de ajuda';
require __DIR__ . '/../includes/header.php';
?>
<section class="section narrow">
  <p class="eyebrow">ADMINISTRAÇÃO</p>
  <h1>Conversas com clientes</h1>
  <p>Cada conversa identifica o cliente, a encomenda, o produto e a fábrica responsável.</p>
  <p><a class="button" href="<?= url('admin/index.php') ?>">Voltar ao painel</a></p>
  <div class="support-conversations">
    <?php if (!$tickets): ?>
      <div class="panel"><p>Não existem pedidos de ajuda.</p></div>
    <?php else: ?>
      <?php foreach ($tickets as $ticket): ?>
        <article class="panel conversation">
          <header>
            <div>
              <strong><?= e($ticket['customer_name']) ?> — <?= e($ticket['subject']) ?></strong><br>
              <small><?= $ticket['order_id'] ? 'Encomenda #CP-' . str_pad((string) $ticket['order_id'], 5, '0', STR_PAD_LEFT) : 'Sem encomenda' ?><?php if (!empty($ticket['product_name'])): ?> · <?= e($ticket['product_name']) ?><?php endif; ?></small><br>
              <small><strong>Fábrica responsável:</strong> <?= e($ticket['resolved_factory_name']) ?></small>
            </div>
            <span class="status-pill"><?= e($ticket['status']) ?></span>
          </header>
          <div class="message-thread">
            <?php foreach ($ticket['messages'] as $msg): ?>
              <div class="chat-message <?= $msg['sender_role'] === 'admin' ? 'from-admin' : 'from-customer' ?>">
                <b><?= $msg['sender_role'] === 'admin' ? e($ticket['resolved_factory_name']) : e($ticket['customer_name']) ?></b>
                <p><?= nl2br(e($msg['message'])) ?></p>
                <small><?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></small>
              </div>
            <?php endforeach; ?>
          </div>
          <form class="reply-form ajax-form" method="post" action="<?= url('admin/support.php') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="request_id" value="<?= (int) $ticket['id'] ?>">
            <label>Responder como <?= e($ticket['resolved_factory_name']) ?><textarea name="message" rows="2" required></textarea></label>
            <button class="button button-primary">Enviar resposta</button>
            <span class="save-feedback" role="status"></span>
          </form>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
