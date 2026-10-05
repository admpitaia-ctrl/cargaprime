<?php
/**
 * Card reutilizável de produto.
 *
 * Variáveis esperadas:
 * - $product (array)
 * - $index (int)
 * - $productFallbacks (array)
 */
$image = $product['image'] ?: ($productFallbacks[(int) $product['id']] ?? null);
$isOutOfStock = (float) $product['stock_tons'] < 0.5;
$defaultQuantity = min(
    (float) $product['stock_tons'],
    (int) $product['id'] === 1 ? 19.0 : 1.0
);
?>
<article
    class="cp3-product cp3-reveal<?= $isOutOfStock ? ' is-out-of-stock' : '' ?>"
    data-product-card
    data-product-id="<?= (int) $product['id'] ?>"
    data-product-name="<?= e($product['name'] . ' ' . $product['description']) ?>"
    data-product-card-category="<?= e($product['category'] ?: 'Outros') ?>"
    style="--delay: <?= (int) $index * 80 ?>ms"
>
    <div class="cp3-product-media">
        <?php if ($image): ?>
            <img
                src="<?= url(e($image)) ?>"
                alt="<?= e($product['name']) ?>"
                loading="<?= $index < 3 ? 'eager' : 'lazy' ?>"
                decoding="async"
            >
        <?php else: ?>
            <div class="cp3-product-placeholder">
                <b><?= e(mb_strtoupper(mb_substr($product['name'], 0, 1))) ?></b>
                <span>Imagem não adicionada</span>
            </div>
        <?php endif; ?>

        <span
            class="cp3-stock<?= $isOutOfStock ? ' is-out' : '' ?>"
            data-stock-label
        >
            <i></i><?= $isOutOfStock
                ? 'Sem stock'
                : e(number_format((float) $product['stock_tons'], 1, ',', '.') . ' t em stock') ?>
        </span>

        <span class="cp3-category">
            <?= e($product['category'] ?: 'Outros') ?>
        </span>
    </div>

    <div class="cp3-product-body">
        <small>CP-<?= str_pad((string) $product['id'], 3, '0', STR_PAD_LEFT) ?></small>
        <h3><?= e($product['name']) ?></h3>

        <p class="cp3-product-factory">
            <strong>Fábrica:</strong> <?= e($product['factory_name']) ?>
        </p>

        <p><?= e($product['description']) ?></p>

        <div class="cp3-buy-row">
            <div>
                <strong><?= e(number_format((float) $product['price_per_ton'], 2, ',', '.')) ?> €</strong>
                <span>/ tonelada</span>
            </div>

            <form
                class="ajax-form"
                action="<?= url('cart.php') ?>"
                method="post"
                data-refresh-selector=".badge|.cp3-cart-summary"
            >
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">

                <label>
                    <span>Quantidade</span>
                    <input
                        type="number"
                        name="quantity"
                        min="0.5"
                        step="0.5"
                        value="<?= e((string) $defaultQuantity) ?>"
                        data-stock-quantity
                        data-product-stock-input
                        data-stock-limit="<?= e((string) $product['stock_tons']) ?>"
                        <?= $isOutOfStock ? 'disabled' : '' ?>
                        required
                    >
                    t
                </label>

                <button
                    type="submit"
                    data-stock-submit
                    <?= $isOutOfStock ? 'disabled' : '' ?>
                >
                    <?= $isOutOfStock ? 'Indisponível' : 'Adicionar ao carrinho <b>↗</b>' ?>
                </button>

                <span class="save-feedback" role="status"></span>
            </form>
        </div>

        <em data-stock-help>
            <?= $isOutOfStock
                ? 'Stock esgotado.'
                : 'Stock atual: ' . e(number_format((float) $product['stock_tons'], 1, ',', '.'))
                    . ' t. O desconto ocorre ao confirmar a encomenda.' ?>
        </em>
    </div>
</article>
