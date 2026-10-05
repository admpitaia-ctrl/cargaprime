<?php

require __DIR__ . '/includes/bootstrap.php';

require_login();

$id = (int) ($_GET['id'] ?? 0);


/*
 * Recupera os dados gerais da encomenda.
 *
 * Para utilizadores normais, a consulta também confirma
 * que a encomenda pertence ao utilizador autenticado.
 * O administrador pode consultar qualquer encomenda.
 */
$sql = 'SELECT o.*
        FROM orders o
        WHERE o.id = ?';

if (!is_admin()) {
    $sql .= ' AND o.user_id = ' . (int) $_SESSION['user']['id'];
}

$stmt = db()->prepare($sql);
$stmt->execute([$id]);

$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    exit('Documento não encontrado.');
}


/*
 * As mercadorias são recuperadas separadamente.
 *
 * Não é criado HTML dentro da consulta SQL.
 * Isto permite escapar individualmente os dados
 * apresentados no documento e reduz o risco de XSS.
 */
$itemsStmt = db()->prepare(
    'SELECT
        product_name,
        factory_name,
        factory_address,
        quantity_tons
     FROM order_items
     WHERE order_id = ?
     ORDER BY id ASC'
);

$itemsStmt->execute([$id]);

$items = $itemsStmt->fetchAll();


$pageTitle = 'Documento de transporte';

require __DIR__ . '/includes/header.php';

?>

<div class="no-print">
    <button
        class="button button-primary"
        type="button"
        onclick="window.print()"
    >
        Guardar / imprimir PDF
    </button>
</div>

<article class="panel print-document">

    <div class="document-head">

        <div>
            <p class="eyebrow">
                DOCUMENTO DE TRANSPORTE
            </p>

            <h1><?= e(APP_NAME) ?></h1>
        </div>

        <div>
            <strong>
                #CP-<?= str_pad(
                    (string) $order['id'],
                    5,
                    '0',
                    STR_PAD_LEFT
                ) ?>
            </strong>

            <br>

            Emitido em <?= date('d/m/Y') ?>

            <br>

            Estado: <?= e((string) $order['status']) ?>
        </div>

    </div>


    <div class="address-grid">

        <section>

            <h2>Origem — fábrica(s)</h2>

            <?php

            /*
             * Uma encomenda pode conter vários produtos
             * provenientes da mesma fábrica.
             *
             * Este array evita apresentar a mesma origem
             * repetidamente no documento.
             */
            $shownFactories = [];

            foreach ($items as $item):

                $factoryName = (string) (
                    $item['factory_name'] ?? ''
                );

                $factoryAddress = (string) (
                    $item['factory_address'] ?? ''
                );

                $factoryKey =
                    $factoryName . '|' . $factoryAddress;

                if (isset($shownFactories[$factoryKey])) {
                    continue;
                }

                $shownFactories[$factoryKey] = true;

            ?>

                <div class="document-factory">

                    <strong>
                        <?= e($factoryName) ?>
                    </strong>

                    <?php if ($factoryAddress !== ''): ?>

                        <p>
                            <?= e($factoryAddress) ?>
                        </p>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>


            <p>
                <small>
                    Contacto da plataforma:
                    <?= e(FACTORY_PHONE) ?>
                    ·
                    <?= e(FACTORY_EMAIL) ?>
                </small>
            </p>

        </section>


        <section>

            <h2>Destino — cliente</h2>

            <strong>
                <?= e((string) $order['customer_name']) ?>
            </strong>

            <p>
                <?= e((string) $order['address']) ?>
            </p>

            <p>
                Recolha prevista:

                <?=
                    date(
                        'd/m/Y',
                        strtotime(
                            (string) $order['collection_date']
                        )
                    )
                ?>

                às

                <?=
                    e(
                        substr(
                            (string) (
                                $order['collection_time']
                                ?? '09:00'
                            ),
                            0,
                            5
                        )
                    )
                ?>
            </p>

        </section>

    </div>


    <section>

        <h2>Mercadoria</h2>

        <?php if (!$items): ?>

            <p>
                Nenhuma mercadoria associada a esta encomenda.
            </p>

        <?php else: ?>

            <?php foreach ($items as $item): ?>

                <p>

                    <?= e(
                        (string) $item['product_name']
                    ) ?>

                    —

                    <?= e(
                        (string) $item['factory_name']
                    ) ?>

                    —

                    <?= number_format(
                        (float) $item['quantity_tons'],
                        1,
                        ',',
                        '.'
                    ) ?> t

                </p>

            <?php endforeach; ?>

        <?php endif; ?>


        <p>
            <strong>Valor total:</strong>

            <?= number_format(
                (float) $order['total'],
                2,
                ',',
                '.'
            ) ?> €
        </p>

    </section>


    <hr>


    <small>
        Documento académico de apoio logístico.
        Não substitui guia de transporte ou documento fiscal legal.
    </small>

</article>


<?php

require __DIR__ . '/includes/footer.php';

?>