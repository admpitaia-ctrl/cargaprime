<?php

require __DIR__ . '/includes/bootstrap.php';

/*
 * =========================================================
 * CATÁLOGO COMPLETO
 * =========================================================
 * Página dedicada de e-commerce com:
 * - pesquisa;
 * - filtro por categoria;
 * - filtro de disponibilidade;
 * - paginação real no servidor;
 * - produtos com stock primeiro;
 * - produtos sem stock no final.
 */

$q = trim((string) ($_GET['q'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$availability = (string) ($_GET['availability'] ?? 'all');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;

if (!in_array($availability, ['all', 'in_stock'], true)) {
    $availability = 'all';
}

/* Estatísticas globais do catálogo. */
$catalogStats = db()->query(
    'SELECT
        COUNT(*) AS total_products,
        SUM(CASE WHEN stock_tons >= 0.5 THEN 1 ELSE 0 END) AS available_products
     FROM products
     WHERE active = 1'
)->fetch();

$totalProducts = (int) ($catalogStats['total_products'] ?? 0);
$availableProducts = (int) ($catalogStats['available_products'] ?? 0);

/* Categorias disponíveis para os filtros. */
$categoryStmt = db()->query(
    "SELECT DISTINCT COALESCE(NULLIF(category, ''), 'Outros') AS category
     FROM products
     WHERE active = 1
     ORDER BY category ASC"
);
$categories = array_values(array_filter(array_map(
    static fn(array $row): string => (string) $row['category'],
    $categoryStmt->fetchAll()
)));

/* Condições dinâmicas da pesquisa. */
$where = ['active = 1'];
$params = [];

if ($q !== '') {
    $where[] = '(name LIKE ? OR description LIKE ? OR factory_name LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}

if ($category !== '') {
    $where[] = "COALESCE(NULLIF(category, ''), 'Outros') = ?";
    $params[] = $category;
}

if ($availability === 'in_stock') {
    $where[] = 'stock_tons >= 0.5';
}

$whereSql = implode(' AND ', $where);

/* Total de resultados filtrados. */
$countStmt = db()->prepare("SELECT COUNT(*) FROM products WHERE $whereSql");
$countStmt->execute($params);
$totalResults = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalResults / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

/*
 * Os produtos compráveis surgem primeiro.
 * Os sem stock permanecem visíveis no final do catálogo.
 */
$sql = "SELECT
            id,
            name,
            description,
            category,
            factory_name,
            factory_address,
            price_per_ton,
            stock_tons,
            image
        FROM products
        WHERE $whereSql
        ORDER BY CASE WHEN stock_tons < 0.5 THEN 1 ELSE 0 END, id ASC
        LIMIT ? OFFSET ?";

$productStmt = db()->prepare($sql);
$position = 1;
foreach ($params as $param) {
    $productStmt->bindValue($position++, $param, PDO::PARAM_STR);
}
$productStmt->bindValue($position++, $perPage, PDO::PARAM_INT);
$productStmt->bindValue($position, $offset, PDO::PARAM_INT);
$productStmt->execute();
$products = $productStmt->fetchAll();

/* Resumo do carrinho, preservando o comportamento existente. */
$cart = cart_snapshot();
$cartSummaryCount = array_sum($cart);
$cartSummaryTotal = 0.0;

if ($cart) {
    $cartIds = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($cartIds), '?'));
    $cartStatement = db()->prepare(
        "SELECT id, price_per_ton FROM products WHERE id IN ($placeholders)"
    );
    $cartStatement->execute($cartIds);
    foreach ($cartStatement as $cartProduct) {
        $cartSummaryTotal += (float) ($cart[$cartProduct['id']] ?? 0)
            * (float) $cartProduct['price_per_ton'];
    }
}

$pageTitle = 'Catálogo de produtos industriais';
$metaDescription = 'Consulte o catálogo CargaPrime com pesquisa, filtros, stock visível e paginação de materiais industriais vendidos por tonelada.';
$canonicalPath = '/products.php';

require __DIR__ . '/includes/header.php';

$productFallbacks = [
    1 => 'assets/images/products/gesso-ensacado.png',
    2 => 'assets/images/products/algodao-prensado.png',
    3 => 'assets/images/products/celulose-fardos.png',
];

/* Mantém filtros/pesquisa ao mudar de página. */
function catalog_url(array $overrides = []): string
{
    global $q, $category, $availability;

    $query = array_merge([
        'q' => $q,
        'category' => $category,
        'availability' => $availability,
    ], $overrides);

    $query = array_filter(
        $query,
        static fn($value): bool => $value !== '' && $value !== null && $value !== 'all'
    );

    $qs = http_build_query($query);
    return url('products.php') . ($qs !== '' ? '?' . $qs : '');
}

$firstResult = $totalResults > 0 ? $offset + 1 : 0;
$lastResult = min($offset + $perPage, $totalResults);
?>

<section class="cp3-shop cp3-catalog-page">
    <header class="cp3-section-head cp3-reveal">
        <div>
            <p class="cp3-eyebrow">CATÁLOGO INDUSTRIAL</p>
            <h1>Materiais por tonelada.</h1>
        </div>
        <p>
            Pesquise e filtre o catálogo completo. Os produtos com stock aparecem primeiro;
            os indisponíveis permanecem visíveis no final para consulta.
        </p>
    </header>

    <aside class="cp3-cart-summary" aria-label="Resumo atual do carrinho">
        <div>
            <small>CARRINHO ATUAL</small>
            <strong><?= e(number_format((float) $cartSummaryCount, 1, ',', '.')) ?> t selecionadas</strong>
        </div>
        <div>
            <small>VALOR TOTAL</small>
            <strong><?= e(number_format($cartSummaryTotal, 2, ',', '.')) ?> €</strong>
        </div>
        <a href="<?= url('cart.php') ?>">Ver carrinho <span aria-hidden="true">↗</span></a>
    </aside>

    <div class="cp3-catalog-overview cp3-reveal">
        <div>
            <small>CATÁLOGO ATUAL</small>
            <strong><?= (int) $totalProducts ?> <?= $totalProducts === 1 ? 'produto' : 'produtos' ?></strong>
            <span><?= (int) $availableProducts ?> <?= $availableProducts === 1 ? 'disponível para compra' : 'disponíveis para compra' ?></span>
        </div>
        <a class="cp3-button cp3-ghost-dark" href="<?= url('index.php') ?>">Voltar ao início</a>
    </div>

    <form class="cp3-product-search cp3-catalog-search" method="get" action="<?= url('products.php') ?>" role="search">
        <label for="catalog-search">
            <span>Pesquisar produtos</span>
            <span class="cp3-search-field">
                <b aria-hidden="true">⌕</b>
                <input
                    id="catalog-search"
                    name="q"
                    type="search"
                    value="<?= e($q) ?>"
                    placeholder="Ex.: gesso, algodão ou celulose"
                    autocomplete="off"
                >
            </span>
        </label>

        <?php if ($category !== ''): ?>
            <input type="hidden" name="category" value="<?= e($category) ?>">
        <?php endif; ?>
        <?php if ($availability !== 'all'): ?>
            <input type="hidden" name="availability" value="<?= e($availability) ?>">
        <?php endif; ?>

        <div class="cp3-catalog-search-actions">
            <button class="cp3-button cp3-primary" type="submit">Pesquisar</button>
            <?php if ($q !== '' || $category !== '' || $availability !== 'all'): ?>
                <a class="cp3-button cp3-ghost-dark" href="<?= url('products.php') ?>">Limpar filtros</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="cp3-catalog-filter-group cp3-reveal">
        <strong>Categoria</strong>
        <div class="cp3-filter-pills">
            <a class="<?= $category === '' ? 'active' : '' ?>" href="<?= e(catalog_url(['category' => '', 'page' => 1])) ?>">Todos</a>
            <?php foreach ($categories as $catalogCategory): ?>
                <a
                    class="<?= $category === $catalogCategory ? 'active' : '' ?>"
                    href="<?= e(catalog_url(['category' => $catalogCategory, 'page' => 1])) ?>"
                ><?= e($catalogCategory) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="cp3-catalog-filter-group cp3-reveal">
        <strong>Disponibilidade</strong>
        <div class="cp3-filter-pills">
            <a class="<?= $availability === 'all' ? 'active' : '' ?>" href="<?= e(catalog_url(['availability' => 'all', 'page' => 1])) ?>">Todos</a>
            <a class="<?= $availability === 'in_stock' ? 'active' : '' ?>" href="<?= e(catalog_url(['availability' => 'in_stock', 'page' => 1])) ?>">Em stock</a>
        </div>
    </div>

    <div class="cp3-catalog-results cp3-reveal">
        <span>
            <?php if ($totalResults > 0): ?>
                A mostrar <?= (int) $firstResult ?>–<?= (int) $lastResult ?> de <?= (int) $totalResults ?> resultados
            <?php else: ?>
                Nenhum resultado encontrado
            <?php endif; ?>
        </span>
        <span><?= (int) $totalProducts ?> no catálogo · <?= (int) $availableProducts ?> disponíveis</span>
    </div>

    <?php if ($products): ?>
        <div class="cp3-products" data-product-grid>
            <?php foreach ($products as $index => $product): ?>
                <?php require __DIR__ . '/includes/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="cp3-no-results">
            Nenhum produto corresponde aos filtros selecionados.
            Experimente outra pesquisa, categoria ou disponibilidade.
        </p>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <nav class="cp3-pagination" aria-label="Paginação do catálogo">
            <?php if ($page > 1): ?>
                <a class="cp3-page-link" href="<?= e(catalog_url(['page' => $page - 1])) ?>">← Anterior</a>
            <?php else: ?>
                <span class="cp3-page-link is-disabled">← Anterior</span>
            <?php endif; ?>

            <div class="cp3-page-numbers">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a
                        class="cp3-page-number<?= $i === $page ? ' active' : '' ?>"
                        href="<?= e(catalog_url(['page' => $i])) ?>"
                        <?= $i === $page ? 'aria-current="page"' : '' ?>
                    ><?= $i ?></a>
                <?php endfor; ?>
            </div>

            <?php if ($page < $totalPages): ?>
                <a class="cp3-page-link" href="<?= e(catalog_url(['page' => $page + 1])) ?>">Seguinte →</a>
            <?php else: ?>
                <span class="cp3-page-link is-disabled">Seguinte →</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
