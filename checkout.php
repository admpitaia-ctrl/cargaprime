<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();
$cart = cart_snapshot();
if (!$cart) { flash('error', 'O carrinho está vazio.'); redirect('index.php'); }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $name = trim((string)($_POST['name'] ?? '')); $birthDate = (string)($_POST['birth_date'] ?? ''); $address = trim((string)($_POST['address'] ?? '')); $collectionDate = (string)($_POST['collection_date'] ?? ''); $collectionTime = (string)($_POST['collection_time'] ?? ''); $adultConfirmed = isset($_POST['adult_confirm']);
    if ($name === '' || $birthDate === '' || $address === '' || $collectionDate === '' || $collectionTime === '') $errors[] = 'Todos os campos são obrigatórios.';
    $birth = DateTime::createFromFormat('Y-m-d', $birthDate); $today = new DateTime('today');
    if (!$birth || $birth > new DateTime('today -18 years')) $errors[] = 'É necessário ter 18 anos ou mais.';
    $collection = DateTime::createFromFormat('Y-m-d', $collectionDate);
    if (!$collection || $collection <= $today) $errors[] = 'Escolha uma data de recolha futura.';
    $time = DateTime::createFromFormat('H:i', $collectionTime);
    if (!$time || $time->format('H:i') !== $collectionTime) $errors[] = 'Escolha um horário de recolha válido.';
    if (!$adultConfirmed) $errors[] = 'Confirme a maioridade e a correção dos dados.';
    if (!$errors) {
        $pdo = db();
        try {
            /*
             * REGRA DE STOCK — FONTE DE VERDADE
             * ----------------------------------
             * O carrinho NÃO reserva stock. No instante da confirmação,
             * cada produto é novamente validado e descontado diretamente
             * no MySQL com uma operação atómica:
             *
             *   UPDATE ... SET stock = stock - quantidade
             *   WHERE stock >= quantidade
             *
             * Duas compras concorrentes do mesmo produto não conseguem
             * consumir as mesmas toneladas. Se outra encomenda consumir
             * o stock primeiro, rowCount() será 0 e toda esta transação
             * é revertida (ROLLBACK), sem criar encomenda parcial.
             */
            $pdo->beginTransaction();

            $ids = array_values(array_unique(array_map('intval', array_keys($cart))));
            sort($ids, SORT_NUMERIC); // ordem estável reduz risco de deadlock
            if (!$ids) throw new RuntimeException('O carrinho está vazio.');

            $ph = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare(
                "SELECT id, name, factory_name, factory_address, price_per_ton, stock_tons
                 FROM products
                 WHERE active = 1 AND id IN ($ph)
                 ORDER BY id ASC
                 FOR UPDATE"
            );
            $stmt->execute($ids);
            $products = $stmt->fetchAll();

            if (count($products) !== count($ids)) {
                throw new RuntimeException('Um produto deixou de estar disponível. Atualize o carrinho.');
            }

            $total = 0.0;
            $validated = [];
            foreach ($products as $p) {
                $productId = (int) $p['id'];
                $q = parse_tons($cart[$productId] ?? null);
                if ($q === null) {
                    throw new RuntimeException('A quantidade de ' . $p['name'] . ' é inválida.');
                }

                $currentStock = (float) $p['stock_tons'];
                if ($q > $currentStock + 0.000001) {
                    throw new RuntimeException(
                        'Stock insuficiente para ' . $p['name'] .
                        ': existem apenas ' . number_format($currentStock, 1, ',', '.') .
                        ' t. A encomenda não foi criada.'
                    );
                }

                $validated[] = ['product' => $p, 'quantity' => $q];
                $total += $q * (float) $p['price_per_ton'];
            }

            /*
             * Segundo bloqueio defensivo: o desconto é condicionado ao stock
             * no próprio UPDATE. Mesmo que a lógica acima seja alterada no
             * futuro, nunca é permitido stock negativo.
             */
            $stockStmt = $pdo->prepare(
                'UPDATE products
                 SET stock_tons = stock_tons - ?
                 WHERE id = ? AND active = 1 AND stock_tons >= ?'
            );

            foreach ($validated as $entry) {
                $p = $entry['product'];
                $q = $entry['quantity'];
                $stockStmt->execute([$q, (int) $p['id'], $q]);

                if ($stockStmt->rowCount() !== 1) {
                    $check = $pdo->prepare('SELECT stock_tons FROM products WHERE id = ?');
                    $check->execute([(int) $p['id']]);
                    $remaining = (float) ($check->fetchColumn() ?: 0);
                    throw new RuntimeException(
                        'O stock de ' . $p['name'] .
                        ' foi alterado por outra encomenda. Restam ' .
                        number_format($remaining, 1, ',', '.') .
                        ' t. Reveja o carrinho.'
                    );
                }
            }

            // Só depois de TODO o stock estar garantido é criada a encomenda.
            $stmt = $pdo->prepare(
                'INSERT INTO orders
                 (user_id, customer_name, birth_date, address, collection_date, collection_time, total, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                (int) $_SESSION['user']['id'],
                $name,
                $birthDate,
                $address,
                $collectionDate,
                $collectionTime . ':00',
                $total,
                'Pendente'
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                'INSERT INTO order_items
                 (order_id, product_id, product_name, factory_name, factory_address, quantity_tons, unit_price, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );

            foreach ($validated as $entry) {
                $p = $entry['product'];
                $q = $entry['quantity'];
                $sub = $q * (float) $p['price_per_ton'];
                $itemStmt->execute([
                    $orderId,
                    (int) $p['id'],
                    $p['name'],
                    $p['factory_name'],
                    $p['factory_address'],
                    $q,
                    $p['price_per_ton'],
                    $sub
                ]);
            }

            $pdo->commit();
            cart_clear();
            $_SESSION['last_order'] = $orderId;

            if (is_json_request()) {
                json_response(
                    true,
                    'Encomenda confirmada e stock descontado com sucesso.',
                    200,
                    ['redirect' => url('success.php')]
                );
            }
            redirect('success.php');
        } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); $errors[]=$e->getMessage(); }
    }
    if ($errors && is_json_request()) json_response(false, implode(' ', $errors), 422);
}
$pageTitle='Finalizar encomenda'; require __DIR__ . '/includes/header.php';
?>
<section class="section form-page"><div><p class="eyebrow">ÚLTIMO PASSO</p><h1>Dados da encomenda</h1><p>Os dados serão usados apenas para processar esta encomenda académica.</p></div><form class="panel form-grid ajax-form" method="post" action="<?= url('checkout.php') ?>" novalidate><input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
<?php foreach($errors as $error): ?><div class="alert alert-error span-2"><?= e($error) ?></div><?php endforeach; ?>
<label>Nome completo<input name="name" maxlength="120" value="<?= e($_POST['name'] ?? $_SESSION['user']['name'] ?? '') ?>" required></label><label>Data de nascimento<input type="date" name="birth_date" max="<?= date('Y-m-d', strtotime('-18 years')) ?>" value="<?= e($_POST['birth_date'] ?? $_SESSION['user']['birth_date'] ?? '') ?>" required></label><label class="span-2">Morada<textarea name="address" maxlength="300" required><?= e($_POST['address'] ?? '') ?></textarea></label><label>Data pretendida para recolha<input type="date" name="collection_date" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= e($_POST['collection_date'] ?? '') ?>" required></label><label>Horário pretendido<input type="time" name="collection_time" min="06:00" max="22:00" step="900" value="<?= e($_POST['collection_time'] ?? '09:00') ?>" required><small class="field-help">Pode escolher intervalos de 15 minutos.</small></label><label class="check span-2"><input type="checkbox" name="adult_confirm" value="1" <?= isset($_POST['adult_confirm']) ? 'checked' : '' ?> required> Confirmo que tenho 18 anos ou mais e que os dados estão corretos.</label><button class="button button-primary span-2" type="submit">Confirmar encomenda</button><span class="save-feedback span-2" role="status"></span></form></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
