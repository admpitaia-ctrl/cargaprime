<?php
require __DIR__ . '/includes/bootstrap.php';
$cart = cart_snapshot();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = parse_tons($_POST['quantity'] ?? null);
    $action = $_POST['action'] ?? '';
    $ok = true;
    $message = 'Carrinho atualizado.';
    if (!in_array($action, ['add', 'update', 'remove'], true)) {
        $ok = false;
        $message = 'Operação de carrinho inválida.';
    }
    elseif ($action === 'remove' && $id) {
        cart_remove_item((int) $id); $cart = cart_snapshot();
        $message = 'Produto removido do carrinho.';
    }
    elseif ($id && $quantity !== null) {
        $stmt = db()->prepare('SELECT name, stock_tons FROM products WHERE id = ? AND active = 1'); $stmt->execute([$id]);
        $product = $stmt->fetch();
        $stock = $product !== false ? (float) $product['stock_tons'] : null;
        if ($product === false) {
            $ok = false;
            $message = 'Este produto deixou de estar disponível.';
        }
        elseif ($quantity > $stock + 0.000001) {
            $ok = false;
            $message = 'Só existem ' . number_format($stock, 1, ',', '.') . ' t de ' . $product['name'] . ' em stock. Reduza a quantidade.';
        }
        else { cart_set_item((int) $id, $quantity); $cart = cart_snapshot(); $message = $action === 'add' ? 'Produto adicionado ao carrinho. O stock só será descontado ao confirmar a encomenda.' : 'Quantidade do carrinho atualizada. O stock só será descontado ao confirmar a encomenda.'; }
    }
    else { $ok = false; $message = 'Indique uma quantidade válida, em intervalos de 0,5 t.'; }
    if (is_json_request()) {
        json_response($ok, $message, $ok ? 200 : 422, [
            'cart_count' => array_sum($cart),
            'refresh' => ['.badge', '#cart-content'],
        ]);
    }
    flash($ok ? 'success' : 'error', $message);
    redirect('cart.php');
}

$items = [];
$total = 0.0;
$hasStockError = false;
$cart = cart_snapshot();

if ($cart) {
    $ids = array_values(array_unique(array_map('intval', array_keys($cart))));
    sort($ids, SORT_NUMERIC);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare(
        "SELECT id, name, factory_name, factory_address, price_per_ton, stock_tons, active
         FROM products
         WHERE id IN ($placeholders)
         ORDER BY id ASC"
    );
    $stmt->execute($ids);
    $products = $stmt->fetchAll();

    // Um item removido/desativado também invalida o checkout.
    if (count($products) !== count($ids)) {
        $hasStockError = true;
    }

    foreach ($products as $p) {
        $p['quantity'] = (float) ($cart[(int) $p['id']] ?? 0);
        $p['subtotal'] = $p['quantity'] * (float) $p['price_per_ton'];
        $p['stock_invalid'] =
            (int) $p['active'] !== 1 ||
            $p['quantity'] <= 0 ||
            $p['quantity'] > (float) $p['stock_tons'] + 0.000001 ||
            abs($p['quantity'] * 2 - round($p['quantity'] * 2)) > 0.000001;

        $total += $p['subtotal'];
        $hasStockError = $hasStockError || $p['stock_invalid'];
        $items[] = $p;
    }
}
$pageTitle = 'Carrinho'; require __DIR__ . '/includes/header.php';
?>
<section class="section narrow"><p class="eyebrow">A SUA SELEÇÃO</p><h1>Carrinho</h1><div id="cart-content">
<?php if (!$items): ?><div class="empty"><h2>O carrinho está vazio.</h2><p>Escolha um produto para iniciar a encomenda.</p><a class="button button-primary" href="<?= url('products.php') ?>">Ver produtos</a></div>
<?php else: ?><div class="cart-layout"><div class="cart-list">
<?php foreach ($items as $item): ?><article class="cart-row"><div><h3><?= e($item['name']) ?></h3><p><strong>Fábrica:</strong> <?= e($item['factory_name']) ?></p><p><?= number_format((float)$item['price_per_ton'],2,',','.') ?> € por tonelada</p><?php if ($item['stock_invalid']): ?><p class="error-text">Stock alterado: existem apenas <?= number_format((float)$item['stock_tons'],1,',','.') ?> t disponíveis. Esta quantidade já não pode ser confirmada.</p><?php endif; ?></div><strong><?= number_format($item['subtotal'],2,',','.') ?> €</strong><form class="cart-actions ajax-form" method="post" action="<?= url('cart.php') ?>"><input type="hidden" name="csrf_token" value="<?= csrf_token() ?>"><input type="hidden" name="product_id" value="<?= $item['id'] ?>"><label>Quantidade (t)<input type="number" name="quantity" min="0.5" step="0.5" value="<?= e((string)$item['quantity']) ?>" data-stock-quantity data-stock-limit="<?= e((string)$item['stock_tons']) ?>" required></label><button class="button" name="action" value="update">Atualizar</button><button class="link-button" name="action" value="remove">Remover</button><span class="save-feedback" role="status"></span></form></article><?php endforeach; ?>
</div><aside class="summary"><h2>Resumo</h2><div><span>Subtotal</span><strong><?= number_format($total,2,',','.') ?> €</strong></div><div><span>Transporte</span><span>A definir</span></div><hr><div class="summary-total"><span>Total</span><strong><?= number_format($total,2,',','.') ?> €</strong></div><?php if ($hasStockError): ?><button class="button button-primary full" type="button" disabled>Corrija a quantidade</button><small class="error-text">O stock mudou desde que o produto foi colocado no carrinho. Atualize ou remova o item antes de concluir.</small><?php else: ?><a class="button button-primary full" href="<?= url(is_logged_in() ? 'checkout.php' : 'login.php') ?>"><?= is_logged_in() ? 'Concluir compra' : 'Entrar para concluir' ?></a><small>O stock será novamente validado.</small><?php endif; ?></aside></div><?php endif; ?>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
