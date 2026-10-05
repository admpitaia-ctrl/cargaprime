<?php
require __DIR__ . '/../includes/bootstrap.php';
require_admin();
$rows = db()->query('SELECT id, name, stock_tons, active FROM products ORDER BY id')->fetchAll();
$pageTitle = 'Diagnóstico de stock';
require __DIR__ . '/../includes/header.php';
?>
<section class="section narrow">
  <p class="eyebrow">DIAGNÓSTICO</p>
  <h1>Stock diretamente na base de dados</h1>
  <p>Esta página lê <code>products.stock_tons</code> diretamente do MySQL. Use-a para confirmar o valor real depois de cada compra.</p>
  <div class="panel">
    <?php foreach ($rows as $row): ?>
      <p><strong>CP-<?= str_pad((string)$row['id'], 3, '0', STR_PAD_LEFT) ?> · <?= e($row['name']) ?></strong> — <?= number_format((float)$row['stock_tons'], 1, ',', '.') ?> t <?= (int)$row['active'] === 1 ? '' : '(oculto)' ?></p>
    <?php endforeach; ?>
  </div>
  <a class="button" href="<?= url('admin/index.php') ?>">Voltar à administração</a>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
