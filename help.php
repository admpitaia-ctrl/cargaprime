<?php
require __DIR__ . '/includes/bootstrap.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    if (is_admin()) {
        if (is_json_request()) json_response(false, 'Utilize o painel administrativo para responder aos clientes.', 403);
        flash('error', 'Utilize o painel administrativo para responder aos clientes.');
        redirect('admin/support.php');
    }

    validate_csrf();
    $action = $_POST['action'] ?? 'create';
    $message = trim((string) ($_POST['message'] ?? ''));
    $ok = true;
    $notice = '';

    if ($message === '') {
        $ok = false;
        $notice = 'Escreva uma mensagem.';
    } elseif ($action === 'reply') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $owner = $pdo->prepare('SELECT id, factory_name FROM support_requests WHERE id = ? AND user_id = ?');
        $owner->execute([$requestId, (int) $_SESSION['user']['id']]);
        $ticket = $owner->fetch();
        if (!$ticket) {
            $ok = false;
            $notice = 'Conversa não encontrada ou não pertence à sua conta.';
        } else {
            $pdo->prepare("INSERT INTO support_messages(request_id,sender_role,message) VALUES (?,'customer',?)")
                ->execute([$requestId, $message]);
            $pdo->prepare("UPDATE support_requests SET status='Aberto' WHERE id=? AND user_id=?")
                ->execute([$requestId, (int) $_SESSION['user']['id']]);
            $notice = 'Mensagem enviada à fábrica responsável: ' . ($ticket['factory_name'] ?: 'CargaPrime') . '.';
        }
    } else {
        $orderItemId = (int) ($_POST['order_item_id'] ?? 0);
        $subject = trim((string) ($_POST['subject'] ?? ''));

        if ($subject === '') {
            $ok = false;
            $notice = 'Indique o assunto.';
        } elseif ($orderItemId <= 0) {
            $ok = false;
            $notice = 'Selecione a encomenda, o produto e a fábrica responsáveis pelo pedido.';
        } else {
            $itemStmt = $pdo->prepare(
                'SELECT oi.id AS order_item_id, oi.order_id, oi.product_name, oi.factory_name, oi.factory_address
                 FROM order_items oi
                 JOIN orders o ON o.id = oi.order_id
                 WHERE oi.id = ? AND o.user_id = ?
                 LIMIT 1'
            );
            $itemStmt->execute([$orderItemId, (int) $_SESSION['user']['id']]);
            $item = $itemStmt->fetch();

            if (!$item) {
                $ok = false;
                $notice = 'A encomenda ou o produto selecionado não pertence à sua conta.';
            } else {
                $pdo->beginTransaction();
                try {
                    $pdo->prepare(
                        'INSERT INTO support_requests (user_id, order_id, order_item_id, factory_name, factory_address, subject, message)
                         VALUES (?, ?, ?, ?, ?, ?, ?)'
                    )->execute([
                        (int) $_SESSION['user']['id'],
                        (int) $item['order_id'],
                        (int) $item['order_item_id'],
                        $item['factory_name'],
                        $item['factory_address'],
                        $subject,
                        $message,
                    ]);
                    $requestId = (int) $pdo->lastInsertId();
                    $pdo->prepare("INSERT INTO support_messages(request_id,sender_role,message) VALUES (?,'customer',?)")
                        ->execute([$requestId, $message]);
                    $pdo->commit();
                    $notice = 'Conversa iniciada com a fábrica responsável: ' . $item['factory_name'] . '.';
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    throw $e;
                }
            }
        }
    }

    if (is_json_request()) {
        json_response($ok, $notice, $ok ? 200 : 422, ['refresh' => $ok ? ['.support-conversations'] : []]);
    }
    flash($ok ? 'success' : 'error', $notice);
    redirect('help.php');
}

$supportItems = [];
$tickets = [];
$preselectedOrderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
$preselectedItemId = 0;

if (is_logged_in() && !is_admin()) {
    $itemStmt = $pdo->prepare(
        'SELECT oi.id AS order_item_id, o.id AS order_id, oi.product_name, oi.factory_name, oi.factory_address,
                oi.quantity_tons, o.collection_date
         FROM orders o
         JOIN order_items oi ON oi.order_id = o.id
         WHERE o.user_id = ?
         ORDER BY o.created_at DESC, oi.id ASC'
    );
    $itemStmt->execute([(int) $_SESSION['user']['id']]);
    $supportItems = $itemStmt->fetchAll();

    foreach ($supportItems as $supportItem) {
        if ($preselectedOrderId > 0 && (int) $supportItem['order_id'] === $preselectedOrderId) {
            $preselectedItemId = (int) $supportItem['order_item_id'];
            break;
        }
    }

    $stmt = $pdo->prepare(
        'SELECT s.*,
                COALESCE(s.factory_name, oi.factory_name, \'Fábrica não identificada\') AS resolved_factory_name,
                COALESCE(s.factory_address, oi.factory_address) AS resolved_factory_address,
                oi.product_name
         FROM support_requests s
         LEFT JOIN order_items oi ON oi.id = s.order_item_id
         WHERE s.user_id = ?
         ORDER BY s.created_at DESC'
    );
    $stmt->execute([(int) $_SESSION['user']['id']]);
    $tickets = $stmt->fetchAll();

    foreach ($tickets as &$ticket) {
        $m = $pdo->prepare('SELECT * FROM support_messages WHERE request_id = ? ORDER BY created_at');
        $m->execute([(int) $ticket['id']]);
        $ticket['messages'] = $m->fetchAll();
    }
    unset($ticket);
}

$pageTitle = 'Ajuda e contacto';
require __DIR__ . '/includes/header.php';
?>
<section class="section narrow">
  <p class="eyebrow">APOIO AO CLIENTE</p>
  <h1>Fale com a fábrica responsável</h1>
  <p>Cada conversa fica associada à sua conta, à encomenda, ao produto e à fábrica responsável. Outros clientes não conseguem consultar as suas mensagens.</p>

  <div class="help-grid">
    <article class="panel contact-card">
      <h2>Contacto geral CargaPrime</h2>
      <p><strong>Telefone</strong><br><a href="tel:<?= e(str_replace(' ', '', FACTORY_PHONE)) ?>"><?= e(FACTORY_PHONE) ?></a></p>
      <p><strong>E-mail</strong><br><a href="mailto:<?= e(FACTORY_EMAIL) ?>"><?= e(FACTORY_EMAIL) ?></a></p>
      <p><strong>Morada</strong><br><?= e(FACTORY_ADDRESS) ?></p>
      <small>Para questões sobre uma carga, utilize a conversa associada à encomenda para que o pedido seja encaminhado à fábrica correta.</small>
    </article>

    <article class="panel">
      <h2>Nova conversa</h2>
      <?php if (!is_logged_in()): ?>
        <p>Inicie sessão para conversar com a fábrica responsável pela sua encomenda.</p>
        <a class="button button-primary" href="<?= url('login.php') ?>">Iniciar sessão</a>
      <?php elseif (is_admin()): ?>
        <p>As conversas estão no painel administrativo.</p>
        <a class="button" href="<?= url('admin/support.php') ?>">Abrir conversas</a>
      <?php elseif (!$supportItems): ?>
        <p>Ainda não tem encomendas com produtos associados. Faça uma encomenda antes de abrir um pedido de ajuda à fábrica.</p>
        <a class="button button-primary" href="<?= url('products.php') ?>">Ver produtos</a>
      <?php else: ?>
        <form class="form-grid ajax-form" method="post" action="<?= url('help.php') ?>">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="create">
          <label class="span-2">Encomenda · produto · fábrica
            <select name="order_item_id" required>
              <option value="">Selecione...</option>
              <?php foreach ($supportItems as $item): ?>
                <option value="<?= (int) $item['order_item_id'] ?>" <?= (int) $item['order_item_id'] === $preselectedItemId ? 'selected' : '' ?>>
                  #CP-<?= str_pad((string) $item['order_id'], 5, '0', STR_PAD_LEFT) ?> · <?= e($item['product_name']) ?> · <?= e($item['factory_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Assunto<input name="subject" maxlength="120" required></label>
          <label class="span-2">Mensagem<textarea name="message" rows="5" required></textarea></label>
          <button class="button button-primary">Enviar à fábrica responsável</button>
          <span class="save-feedback" role="status"></span>
        </form>
      <?php endif; ?>
    </article>
  </div>

  <section class="support-conversations">
    <?php if ($tickets): ?>
      <h2>As minhas conversas</h2>
      <?php foreach ($tickets as $ticket): ?>
        <article class="panel conversation">
          <header>
            <div>
              <strong><?= e($ticket['subject']) ?></strong><br>
              <small>
                <?= $ticket['order_id'] ? 'Encomenda #CP-' . str_pad((string) $ticket['order_id'], 5, '0', STR_PAD_LEFT) : 'Sem encomenda' ?>
                <?php if (!empty($ticket['product_name'])): ?> · <?= e($ticket['product_name']) ?><?php endif; ?>
              </small><br>
              <small><strong>Fábrica:</strong> <?= e($ticket['resolved_factory_name']) ?></small>
            </div>
            <span class="status-pill"><?= e($ticket['status']) ?></span>
          </header>
          <div class="message-thread">
            <?php foreach ($ticket['messages'] as $msg): ?>
              <div class="chat-message <?= $msg['sender_role'] === 'admin' ? 'from-admin' : 'from-customer' ?>">
                <b><?= $msg['sender_role'] === 'admin' ? e($ticket['resolved_factory_name']) : 'Você' ?></b>
                <p><?= nl2br(e($msg['message'])) ?></p>
                <small><?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></small>
              </div>
            <?php endforeach; ?>
          </div>
          <form class="reply-form ajax-form" method="post" action="<?= url('help.php') ?>" data-reset-on-success="true">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="reply">
            <input type="hidden" name="request_id" value="<?= (int) $ticket['id'] ?>">
            <label>Responder<textarea name="message" rows="2" required></textarea></label>
            <button class="button">Enviar mensagem</button>
            <span class="save-feedback" role="status"></span>
          </form>
        </article>
      <?php endforeach; ?>
    <?php elseif (is_logged_in() && !is_admin()): ?>
      <p class="panel">Ainda não existem conversas.</p>
    <?php endif; ?>
  </section>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
