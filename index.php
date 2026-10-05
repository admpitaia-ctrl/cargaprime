<?php

/*
 * =========================================================
 * CARGAPRIME — PÁGINA INICIAL
 * =========================================================
 * Página pública da plataforma.
 *
 * Responsabilidades principais:
 * - apresentar a proposta da CargaPrime;
 * - carregar os produtos ativos;
 * - apresentar stock e preços;
 * - apresentar até seis produtos em destaque;
 * - encaminhar para o catálogo completo com pesquisa, filtros e paginação;
 * - permitir adicionar produtos ao carrinho;
 * - apresentar o percurso de uma encomenda.
 */

require __DIR__ . '/includes/bootstrap.php';


/*
 * =========================================================
 * 1. CATÁLOGO DE PRODUTOS
 * =========================================================
 * Apenas produtos ativos são apresentados ao utilizador.
 *
 * A quantidade apresentada no website é calculada
 * diretamente a partir deste resultado, evitando números
 * comerciais fixos ou desatualizados.
 */

$products = db()->query(
    'SELECT
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
     WHERE active = 1
     ORDER BY CASE WHEN stock_tons < 0.5 THEN 1 ELSE 0 END, id ASC
     LIMIT 6'
)->fetchAll();


/*
 * =========================================================
 * 2. CONTAGEM DINÂMICA DE PRODUTOS
 * =========================================================
 * O número apresentado no website corresponde sempre
 * à quantidade real de produtos ativos.
 *
 * Exemplo:
 * 4 produtos ativos  -> mostra 4
 * 40 produtos ativos -> mostra 40
 */

$catalogStats = db()->query(
    'SELECT
        COUNT(*) AS total_products,
        SUM(CASE WHEN stock_tons >= 0.5 THEN 1 ELSE 0 END) AS available_products
     FROM products
     WHERE active = 1'
)->fetch();

$totalProducts = (int) ($catalogStats['total_products'] ?? 0);
$availableProducts = (int) ($catalogStats['available_products'] ?? 0);


/*
 * =========================================================
 * 3. RESUMO DO CARRINHO
 * =========================================================
 * Obtém o carrinho atual, tanto para visitante como
 * para utilizador autenticado.
 */

$cart = cart_snapshot();

$cartSummaryCount = array_sum($cart);

$cartSummaryTotal = 0.0;


/*
 * Calcula o valor monetário atual do carrinho.
 */

if ($cart) {

    $cartIds = array_map(
        'intval',
        array_keys($cart)
    );

    $cartPlaceholders = implode(
        ',',
        array_fill(
            0,
            count($cartIds),
            '?'
        )
    );

    $cartStatement = db()->prepare(
        "SELECT id, price_per_ton
         FROM products
         WHERE id IN ($cartPlaceholders)"
    );

    $cartStatement->execute($cartIds);

    foreach ($cartStatement as $cartProduct) {

        $cartSummaryTotal +=
            (float) ($cart[$cartProduct['id']] ?? 0)
            *
            (float) $cartProduct['price_per_ton'];
    }
}


/*
 * =========================================================
 * 4. SEO DA PÁGINA
 * =========================================================
 */

$pageTitle = 'Materiais industriais por tonelada';

$metaDescription =
    'Materiais industriais por tonelada com stock visível, '
    . 'compra simulada, preparação acompanhada e link de localização '
    . 'do transporte quando disponibilizado.';

$canonicalPath = '/';


/*
 * =========================================================
 * 5. CABEÇALHO
 * =========================================================
 */

require __DIR__ . '/includes/header.php';


/*
 * =========================================================
 * 6. IMAGENS DE FALLBACK DOS PRODUTOS
 * =========================================================
 * Utilizadas apenas quando o produto não possui uma
 * fotografia definida na base de dados.
 */

$productFallbacks = [

    1 => 'assets/images/products/gesso-ensacado.png',

    2 => 'assets/images/products/algodao-prensado.png',

    3 => 'assets/images/products/celulose-fardos.png'
];


/*
 * =========================================================
 * 7. PERCURSO DA ENCOMENDA
 * =========================================================
 * Conteúdo utilizado na apresentação visual das várias
 * fases do processo logístico.
 */

$journey = [

    [
        '01',
        'CLIENTE',
        'Pedido enviado',
        'A encomenda começa com uma escolha clara.',
        'O cliente seleciona o produto, confirma a quantidade, '
        . 'a data e a morada. O pedido segue de imediato para a fábrica.',
        'assets/media/01-encomenda.mp4',
        '19 t · Gesso ensacado'
    ],

    [
        '02',
        'FÁBRICA',
        'Aprovada',
        'A fábrica recebe, verifica e aprova.',
        'Stock e capacidade de preparação são confirmados no painel interno. '
        . 'O cliente acompanha cada mudança de estado.',
        'assets/media/02-aprovacao.mp4',
        'Stock validado · 24 t disponíveis'
    ],

    [
        '03',
        'CARREGAMENTO',
        'Em preparação',
        'A carga é preparada com segurança.',
        'O gesso permanece selado e paletizado. '
        . 'O empilhador posiciona a carga no camião-baú.',
        'assets/media/03-carregamento.mp4',
        'Carga paletizada · Pronta para transporte'
    ],

    [
        '04',
        'LOCALIZAÇÃO',
        'A caminho',
        'A saída permite disponibilizar o acompanhamento por link.',
        'Quando a administração marca “A caminho”, '
        . 'o link de localização fica disponível na área do cliente.',
        'assets/media/04-localizacao.mp4',
        'Link ativo · Partilha Google Maps'
    ],

    [
        '05',
        'VIAGEM',
        'Em trânsito',
        'O camião ganha estrada. O cliente acompanha.',
        'A viagem continua com acesso rápido '
        . 'ao link externo de localização associado à encomenda.',
        'assets/media/05-viagem.mp4',
        'Acompanhamento externo disponível'
    ]
];

?>


<!-- ======================================================
     HERO / APRESENTAÇÃO PRINCIPAL
======================================================= -->

<section
    class="cp3-hero"
    id="inicio"
>

    <video
        autoplay
        muted
        loop
        playsinline
        poster="<?= url('assets/images/journey/03-estrada.webp') ?>"
    >
        <source
            src="<?= url('assets/media/05-viagem.mp4') ?>"
            type="video/mp4"
        >
    </video>

    <div class="cp3-hero-shade"></div>


    <button
        class="cp3-hero-sound"
        type="button"
        aria-pressed="false"
        aria-label="Ativar som do vídeo"
    >
        <span aria-hidden="true">♪</span>
        <span>Ativar som</span>
    </button>


    <div class="cp3-hero-copy">

        <p class="cp3-eyebrow">
            PLATAFORMA B2B · LOGÍSTICA INDUSTRIAL
        </p>

        <h1>
            Da fábrica<br>
            até ao destino.
        </h1>

        <p>
            Compre materiais industriais por tonelada,
            acompanhe a preparação e aceda ao link de localização
            do transporte quando este for disponibilizado.
        </p>

        <div>

            <a
                class="cp3-button cp3-primary"
                href="<?= url('products.php') ?>"
            >
                Explorar produtos
                <b>↓</b>
            </a>

            <a
                class="cp3-button cp3-ghost"
                href="#percurso"
            >
                Ver o percurso
            </a>

        </div>

    </div>


    <div class="cp3-live">

        <i></i>

        <div>
            <small>DEMONSTRAÇÃO LOGÍSTICA</small>
            <strong>Exemplo #CP-1048</strong>
        </div>

        <b>A caminho</b>

    </div>

</section>


<!-- ======================================================
     DESTAQUES DA PLATAFORMA
======================================================= -->

<section
    class="cp3-trust"
    aria-label="Destaques da plataforma"
>

    <!-- Número real de produtos ativos -->
    <div>

        <strong>
            <?= (int) $totalProducts ?>
        </strong>

        <span>
            <?= $totalProducts === 1
                ? 'produto industrial'
                : 'produtos industriais'
            ?>
        </span>

    </div>


    <div>
        <strong>0,5 t</strong>
        <span>compra mínima</span>
    </div>


    <div>
        <strong>6</strong>
        <span>estados disponíveis</span>
    </div>


    <div>
        <strong>1 link</strong>
        <span>acompanhamento externo</span>
    </div>

</section>


<!-- ======================================================
     DESTAQUES DO CATÁLOGO
======================================================= -->

<section
    class="cp3-shop"
    id="produtos"
>

    <header class="cp3-section-head cp3-reveal">
        <div>
            <p class="cp3-eyebrow">COMPRA POR TONELADA</p>
            <h2>
                Alguns materiais.<br>
                O catálogo completo está a um clique.
            </h2>
        </div>

        <p>
            A página inicial apresenta até seis produtos do catálogo.
            Os artigos com stock surgem primeiro; os indisponíveis permanecem
            visíveis no final, sem possibilidade de compra.
        </p>
    </header>

    <aside
        class="cp3-cart-summary"
        aria-label="Resumo atual do carrinho"
    >
        <div>
            <small>CARRINHO ATUAL</small>
            <strong>
                <?= e(number_format((float) $cartSummaryCount, 1, ',', '.')) ?> t selecionadas
            </strong>
        </div>

        <div>
            <small>VALOR TOTAL</small>
            <strong><?= e(number_format($cartSummaryTotal, 2, ',', '.')) ?> €</strong>
        </div>

        <a href="<?= url('cart.php') ?>">
            Ver carrinho <span aria-hidden="true">↗</span>
        </a>
    </aside>

    <div class="cp3-catalog-overview cp3-reveal">
        <div>
            <small>CATÁLOGO ATUAL</small>
            <strong><?= (int) $totalProducts ?> <?= $totalProducts === 1 ? 'produto' : 'produtos' ?></strong>
            <span>
                <?= (int) $availableProducts ?> <?= $availableProducts === 1 ? 'disponível para compra' : 'disponíveis para compra' ?>
            </span>
        </div>

        <a class="cp3-button cp3-primary" href="<?= url('products.php') ?>">
            Ver todos os produtos <b>↗</b>
        </a>
    </div>

    <div class="cp3-products" data-product-grid>
        <?php foreach ($products as $index => $product): ?>
            <?php require __DIR__ . '/includes/product-card.php'; ?>
        <?php endforeach; ?>
    </div>

    <?php if ($totalProducts > 6): ?>
        <div class="cp3-catalog-cta cp3-reveal">
            <p>
                Está a ver 6 de <?= (int) $totalProducts ?> produtos ativos.
            </p>
            <a class="cp3-button cp3-primary" href="<?= url('products.php') ?>">
                Ver todos os produtos <b>↗</b>
            </a>
        </div>
    <?php endif; ?>

</section>


<!-- ======================================================
     PERCURSO DA ENCOMENDA
======================================================= -->

<section
    class="cp3-journey"
    id="percurso"
    data-cp3-journey
>


    <header
        class="cp3-journey-head cp3-reveal"
    >

        <p class="cp3-eyebrow">
            UMA ENCOMENDA · UM PERCURSO
        </p>

        <h2>
            Acompanhe cada fase,<br>
            na vertical.
        </h2>

        <p>
            O scroll conduz a história.
            Cada vídeo começa quando a respetiva etapa
            entra no ecrã.
        </p>

    </header>


    <div class="cp3-journey-layout">


        <!-- Navegação lateral das etapas -->

        <aside
            class="cp3-rail"
            aria-label="Progresso da encomenda"
        >

            <div class="cp3-rail-line">
                <i></i>
            </div>


            <?php foreach ($journey as $index => $step): ?>

                <button
                    type="button"
                    data-cp3-go="<?= $index ?>"
                    class="<?= $index === 0 ? 'active' : '' ?>"
                >

                    <span>
                        <?= e($step[0]) ?>
                    </span>


                    <span class="cp3-rail-copy">

                        <small>
                            <?= e($step[1]) ?>
                        </small>

                        <strong>
                            <?= e($step[2]) ?>
                        </strong>

                    </span>

                </button>

            <?php endforeach; ?>

        </aside>


        <!-- Conteúdo das etapas -->

        <div class="cp3-scenes">


            <?php foreach ($journey as $index => $step): ?>

                <article
                    class="cp3-scene <?= $index === 0 ? 'active' : '' ?>"
                    data-cp3-scene="<?= $index ?>"
                >


                    <div class="cp3-scene-copy">

                        <p class="cp3-eyebrow">

                            <?= e($step[0]) ?>
                            ·
                            <?= e($step[1]) ?>

                        </p>


                        <h3>
                            <?= e($step[3]) ?>
                        </h3>


                        <p>
                            <?= e($step[4]) ?>
                        </p>


                        <div class="cp3-meta">

                            <i></i>

                            <strong>
                                <?= e($step[2]) ?>
                            </strong>

                            <span>
                                <?= e($step[6]) ?>
                            </span>

                        </div>

                    </div>


                    <div class="cp3-video">

                        <video
                            muted
                            playsinline
                            loop
                            preload="metadata"
                            controls
                        >

                            <source
                                src="<?= url($step[5]) ?>"
                                type="video/mp4"
                            >

                        </video>


                        <span>
                            CARGAPRIME · FASE <?= e($step[0]) ?>
                        </span>


                        <button
                            class="cp3-video-sound"
                            type="button"
                            aria-pressed="false"
                        >

                            <b>
                                ♪
                            </b>

                            <span>
                                Ativar som
                            </span>

                        </button>

                    </div>

                </article>

            <?php endforeach; ?>


            <!-- Entrega -->

            <article
                class="cp3-delivery cp3-reveal"
            >

                <b>
                    ✓
                </b>


                <div>

                    <p class="cp3-eyebrow">
                        06 · ENTREGA
                    </p>

                    <h3>
                        Chegou ao destino.
                    </h3>

                    <p>
                        O cliente confirma a receção
                        e encerra o percurso.
                        O comprovativo fica disponível
                        na área da encomenda.
                    </p>

                </div>


                <aside>

                    <span>
                        Encomenda #CP-1048
                    </span>

                    <strong>
                        Entrega confirmada
                    </strong>

                    <small>
                        08 ago 2026 · 16:38
                    </small>

                </aside>

            </article>

        </div>

    </div>

</section>


<!-- ======================================================
     BENEFÍCIOS / CONTROLO
======================================================= -->

<section class="cp3-benefits">


    <header
        class="cp3-section-head cp3-reveal"
    >

        <div>

            <p class="cp3-eyebrow">
                CONTROLO TOTAL
            </p>

            <h2>
                Uma experiência clara<br>
                para cliente e fábrica.
            </h2>

        </div>

    </header>


    <div>


        <!-- Stock -->

        <article class="cp3-reveal">

            <span>
                01
            </span>

            <h3>
                Stock sem surpresas
            </h3>

            <p>
                O sistema impede quantidades superiores
                ao stock disponível e valida novamente
                o stock antes da confirmação da encomenda.
            </p>

        </article>


        <!-- Estados -->

        <article class="cp3-reveal">

            <span>
                02
            </span>

            <h3>
                Estados organizados
            </h3>

            <p>
                Pedido, aprovação, preparação,
                saída e entrega ficam organizados
                numa única área.
            </p>

        </article>


        <!-- Localização -->

        <article class="cp3-reveal">

            <span>
                03
            </span>

            <h3>
                Localização partilhada
            </h3>

            <p>
                O link externo de localização só é disponibilizado
                ao cliente quando a carga passa para o estado
                “A caminho”.
            </p>

        </article>

    </div>

</section>


<!-- ======================================================
     CHAMADA FINAL
======================================================= -->

<section class="cp3-closing">

    <p class="cp3-eyebrow">
        PRONTO PARA COMEÇAR?
    </p>

    <h2>
        A próxima carga<br>
        começa aqui.
    </h2>

    <a
        class="cp3-button cp3-primary"
        href="<?= url('products.php') ?>"
    >
        Escolher materiais ↑
    </a>

</section>


<?php

/*
 * =========================================================
 * RODAPÉ
 * =========================================================
 */

require __DIR__ . '/includes/footer.php';

?>