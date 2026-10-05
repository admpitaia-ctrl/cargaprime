<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $ok = true;
    $message = 'Alteração guardada.';
    $extra = [];

    try {
        if ($action === 'create' || $action === 'update') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $category = trim((string) ($_POST['category'] ?? 'Outros')) ?: 'Outros';
            $factoryName = trim((string) ($_POST['factory_name'] ?? ''));
            $factoryAddress = trim((string) ($_POST['factory_address'] ?? ''));
            $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
            $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_FLOAT);
            $active = isset($_POST['active']) ? 1 : 0;

            if ($name === '' || $description === '' || mb_strlen($name) > 120 || mb_strlen($description) > 300) {
                throw new RuntimeException('Preencha um nome e uma descrição válidos.');
            }
            if (mb_strlen($category) > 60) throw new RuntimeException('A categoria deve ter no máximo 60 caracteres.');
            if ($factoryName === '' || mb_strlen($factoryName) > 140) throw new RuntimeException('Indique o nome da fábrica/indústria.');
            if (mb_strlen($factoryAddress) > 300) throw new RuntimeException('A morada/origem da fábrica deve ter no máximo 300 caracteres.');
            if ($price === false || $stock === false || $price < 0 || $stock < 0) {
                throw new RuntimeException('Preço e stock devem ser valores positivos.');
            }

            if ($action === 'create') {
                $image = save_product_image($_FILES['image'] ?? []);
                $stmt = $pdo->prepare('INSERT INTO products (name, description, category, factory_name, factory_address, price_per_ton, stock_tons, image, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $description, $category, $factoryName, $factoryAddress !== '' ? $factoryAddress : null, $price, $stock, $image, $active]);
                $message = 'Produto criado com sucesso.';
            } else {
                $productId = (int) ($_POST['product_id'] ?? 0);
                $stmt = $pdo->prepare('SELECT image, stock_tons FROM products WHERE id = ?');
                $stmt->execute([$productId]);
                $current = $stmt->fetch();
                if (!$current) throw new RuntimeException('Produto não encontrado.');

                $image = $current['image'];
                if (isset($_POST['remove_image'])) {
                    delete_uploaded_product_image($image);
                    $image = null;
                }
                $newImage = save_product_image($_FILES['image'] ?? []);
                if ($newImage) {
                    delete_uploaded_product_image($image);
                    $image = $newImage;
                }

                $originalStock = filter_var($_POST['original_stock'] ?? null, FILTER_VALIDATE_FLOAT);
                $currentStock = (float) $current['stock_tons'];
                // Se o administrador não alterou deliberadamente o campo stock, preservamos
                // o valor atual da base. Isto impede que um formulário antigo volte a colocar
                // 24 t depois de outra sessão ter comprado e reduzido o stock para 5 t.
                $stockToSave = ($originalStock !== false && abs((float)$stock - (float)$originalStock) < 0.000001)
                    ? $currentStock
                    : (float)$stock;

                $stmt = $pdo->prepare('UPDATE products SET name = ?, description = ?, category = ?, factory_name = ?, factory_address = ?, price_per_ton = ?, stock_tons = ?, image = ?, active = ? WHERE id = ?');
                $stmt->execute([$name, $description, $category, $factoryName, $factoryAddress !== '' ? $factoryAddress : null, $price, $stockToSave, $image, $active, $productId]);
                $message = 'Produto e imagem atualizados.';
            }
            $extra['refresh'] = ['#admin-product-grid', '#product-metric'];
            $extra['close_dialog'] = true;
        } elseif ($action === 'order_status') {
            $allowed = ['Pendente', 'Aprovada', 'Pronta', 'A caminho', 'Concluída', 'Cancelada'];
            $status = (string) ($_POST['status'] ?? '');
            $tracking = trim((string) ($_POST['tracking_url'] ?? ''));
            if (!in_array($status, $allowed, true)) throw new RuntimeException('Estado inválido.');
            if ($tracking !== '' && !filter_var($tracking, FILTER_VALIDATE_URL)) throw new RuntimeException('Introduza um link de localização válido.');
            $stmt = $pdo->prepare('UPDATE orders SET status = ?, tracking_url = ? WHERE id = ?');
            $stmt->execute([$status, $tracking !== '' ? $tracking : null, (int) $_POST['order_id']]);
            $message = 'Estado e localização atualizados.';
        } else {
            throw new RuntimeException('Ação administrativa inválida.');
        }
    } catch (Throwable $error) {
        $ok = false;
        $message = $error->getMessage();
    }

    if (is_json_request()) json_response($ok, $message, $ok ? 200 : 422, $extra);
    flash($ok ? 'success' : 'error', $message);
    redirect('admin/index.php');
}

$products = $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
$orders = $pdo->query('SELECT o.*, GROUP_CONCAT(CONCAT(oi.product_name," · ",oi.factory_name," (",oi.quantity_tons," t)") SEPARATOR ", ") items FROM orders o LEFT JOIN order_items oi ON oi.order_id=o.id GROUP BY o.id ORDER BY o.created_at DESC')->fetchAll();
$support = $pdo->query('SELECT s.*,u.name customer_name FROM support_requests s JOIN users u ON u.id=s.user_id ORDER BY s.created_at DESC')->fetchAll();
$pageTitle = 'Administração';
require __DIR__ . '/../includes/header.php';
?>
<section class="admin-shell">
  <aside class="admin-side"><p class="eyebrow">PAINEL</p><h1>Administração</h1><a href="#resumo">Resumo</a><a href="#encomendas">Encomendas</a><a href="<?= url('admin/support.php') ?>">Pedidos de ajuda</a><a href="#produtos-admin">Produtos</a><a href="<?= url('admin/stock-diagnostic.php') ?>">Diagnóstico de stock</a><a href="<?= url('index.php') ?>">Ver loja</a></aside>
  <div class="admin-content">
    <div id="resumo" class="metrics"><article><span>Encomendas</span><strong><?= count($orders) ?></strong></article><article id="product-metric"><span>Produtos</span><strong><?= count($products) ?></strong></article><article><span>Pedidos de ajuda</span><strong><?= count($support) ?></strong></article></div>

    <section id="encomendas" class="panel admin-section"><div class="section-heading"><h2>Encomendas recentes</h2></div><div class="table-wrap"><table><thead><tr><th>Ref.</th><th>Cliente</th><th>Produtos</th><th>Recolha</th><th>Total</th><th>Estado, localização e documento</th></tr></thead><tbody>
    <?php foreach ($orders as $o): ?><tr><td>#CP-<?= str_pad((string) $o['id'], 5, '0', STR_PAD_LEFT) ?></td><td><?= e($o['customer_name']) ?></td><td><?= e($o['items']) ?></td><td><?= e(date('d/m/Y', strtotime($o['collection_date']))) ?><br><strong><?= e(substr((string) ($o['collection_time'] ?? '09:00'), 0, 5)) ?></strong></td><td><?= number_format((float) $o['total'], 2, ',', '.') ?> €</td><td><form class="status-form ajax-form" method="post" action="<?= url('api/order-update.php') ?>"><input type="hidden" name="csrf_token" value="<?= csrf_token() ?>"><input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>"><select name="status" aria-label="Estado da encomenda"><?php foreach (['Pendente','Aprovada','Pronta','A caminho','Concluída','Cancelada'] as $status): ?><option value="<?= e($status) ?>" <?= $o['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select><input class="tracking-input" type="url" name="tracking_url" value="<?= e($o['tracking_url']) ?>" placeholder="Cole o link https://maps.app.goo.gl/…"><button class="button">Guardar</button><span class="save-feedback" role="status"></span></form><small class="field-help">Para o cliente ver o botão, selecione “A caminho”, cole o link e guarde.</small><a class="document-link" href="<?= url('transport-document.php?id=' . (int) $o['id']) ?>" target="_blank">Documento de transporte</a></td></tr><?php endforeach; ?>
    </tbody></table></div></section>

    <section id="produtos-admin" class="panel admin-section admin-products-section">
      <div class="admin-products-heading"><div><p class="eyebrow">CATÁLOGO</p><h2>Produtos, imagens e stock</h2><p>Edite todos os dados num cartão próprio, troque a fotografia ou retire-a.</p></div><button class="button button-primary" type="button" data-dialog-open="new-product-dialog">+ Adicionar produto</button></div>
      <div class="admin-product-grid" id="admin-product-grid">
      <?php foreach ($products as $p): $dialogId = 'edit-product-' . (int) $p['id']; ?>
        <article class="admin-product-card <?= (int) $p['active'] === 1 ? '' : 'is-inactive' ?>" data-admin-product-id="<?= (int) $p['id'] ?>">
          <div class="admin-product-image"><?php if ($p['image']): ?><img src="<?= url($p['image']) ?>" alt="<?= e($p['name']) ?>"><?php else: ?><div class="admin-image-placeholder">Sem imagem</div><?php endif; ?><span><?= e($p['category'] ?: 'Outros') ?></span></div>
          <div class="admin-product-info"><small>CP-<?= str_pad((string) $p['id'], 3, '0', STR_PAD_LEFT) ?> · <?= (int) $p['active'] === 1 ? 'Visível' : 'Oculto' ?></small><h3><?= e($p['name']) ?></h3><p><strong>Fábrica:</strong> <?= e($p['factory_name']) ?></p><p><?= e($p['description']) ?></p><div><strong><?= number_format((float) $p['price_per_ton'], 2, ',', '.') ?> €/t</strong><span data-admin-stock-label><?= number_format((float) $p['stock_tons'], 1, ',', '.') ?> t em stock</span></div><button class="button" type="button" data-dialog-open="<?= e($dialogId) ?>">Editar produto</button></div>
        </article>
        <dialog class="product-dialog" id="<?= e($dialogId) ?>"><form class="form-grid ajax-form product-editor" method="post" enctype="multipart/form-data" action="<?= url('admin/index.php') ?>"><input type="hidden" name="csrf_token" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>"><div class="dialog-heading span-2"><div><p class="eyebrow">EDITAR PRODUTO</p><h2><?= e($p['name']) ?></h2></div><button type="button" class="dialog-close" data-dialog-close aria-label="Fechar">×</button></div><label>Nome<input name="name" maxlength="120" value="<?= e($p['name']) ?>" required></label><label>Categoria<input name="category" maxlength="60" value="<?= e($p['category'] ?: 'Outros') ?>" list="product-categories" required></label><label>Fábrica / indústria<input name="factory_name" maxlength="140" value="<?= e($p['factory_name']) ?>" required></label><label>Morada / origem da fábrica<input name="factory_address" maxlength="300" value="<?= e($p['factory_address'] ?? '') ?>" placeholder="Ex.: Coimbra, Portugal"></label><label class="span-2">Descrição<textarea name="description" maxlength="300" required><?= e($p['description']) ?></textarea></label><label>Preço por tonelada<input type="number" name="price" min="0" step="0.01" value="<?= e($p['price_per_ton']) ?>" required></label><label>Stock (t)<input type="number" name="stock" min="0" step="0.5" value="<?= e($p['stock_tons']) ?>" data-admin-stock-input required><input type="hidden" name="original_stock" value="<?= e($p['stock_tons']) ?>" data-admin-stock-original></label><div class="image-editor span-2"><?php if (!$p['image']): ?><div class="image-placeholder" data-image-placeholder>Sem imagem</div><?php endif; ?><img data-image-preview <?= $p['image'] ? 'src="' . e(url($p['image'])) . '"' : 'hidden' ?> alt="Pré-visualização"><label>Substituir imagem<input data-image-input type="file" name="image" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG ou WEBP até 5 MB.</small></label></div><label class="check"><input type="checkbox" name="remove_image" value="1"> Remover imagem atual</label><label class="check"><input type="checkbox" name="active" value="1" <?= (int) $p['active'] === 1 ? 'checked' : '' ?>> Produto visível na loja</label><button class="button button-primary span-2" type="submit">Guardar alterações</button><span class="save-feedback span-2" role="status"></span></form></dialog>
      <?php endforeach; ?>
      </div>
    </section>
  </div>
</section>

<datalist id="product-categories"><option value="Construção"><option value="Têxtil"><option value="Papel"><option value="Químicos"><option value="Metais"><option value="Outros"></datalist>
<dialog class="product-dialog" id="new-product-dialog"><form class="form-grid ajax-form product-editor" method="post" enctype="multipart/form-data" action="<?= url('admin/index.php') ?>"><input type="hidden" name="csrf_token" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="create"><div class="dialog-heading span-2"><div><p class="eyebrow">NOVO PRODUTO</p><h2>Adicionar ao catálogo</h2></div><button type="button" class="dialog-close" data-dialog-close aria-label="Fechar">×</button></div><label>Nome<input name="name" maxlength="120" required></label><label>Categoria<input name="category" maxlength="60" list="product-categories" placeholder="Ex.: Construção" required></label><label>Fábrica / indústria<input name="factory_name" maxlength="140" placeholder="Ex.: Fábrica Alfa" required></label><label>Morada / origem da fábrica<input name="factory_address" maxlength="300" placeholder="Ex.: Coimbra, Portugal"></label><label class="span-2">Descrição<textarea name="description" maxlength="300" required></textarea></label><label>Preço por tonelada<input type="number" name="price" min="0" step="0.01" required></label><label>Stock (t)<input type="number" name="stock" min="0" step="0.5" required></label><div class="image-editor span-2"><div class="image-placeholder" data-image-placeholder>Imagem do produto</div><img data-image-preview hidden alt="Pré-visualização"><label>Escolher imagem<input data-image-input type="file" name="image" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG ou WEBP até 5 MB.</small></label></div><label class="check span-2"><input type="checkbox" name="active" value="1" checked> Produto visível na loja</label><button class="button button-primary span-2" type="submit">Criar produto</button><span class="save-feedback span-2" role="status"></span></form></dialog>
<?php require __DIR__ . '/../includes/footer.php'; ?>
