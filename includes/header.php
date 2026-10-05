<?php
$pageTitle = $pageTitle ?? APP_NAME;
$flash = pull_flash();
$scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$scriptName = basename($scriptPath);
$isAdminPage = str_contains($scriptPath, '/admin/');
$seoDescriptions = [
  'index.php' => 'Conheça a CargaPrime, explore materiais industriais por tonelada e acompanhe o percurso da encomenda.',
  'products.php' => 'Consulte o catálogo CargaPrime com pesquisa, filtros, stock visível e paginação de materiais industriais por tonelada.',
  'help.php' => 'Contacte a equipa CargaPrime e acompanhe pedidos de ajuda relacionados com encomendas de materiais industriais.',
  'cart.php' => 'Reveja os materiais e as toneladas selecionadas antes de concluir a encomenda CargaPrime.',
  'checkout.php' => 'Indique os dados necessários para concluir a encomenda simulada de materiais industriais.',
  'login.php' => 'Acesso reservado para clientes e administração da plataforma CargaPrime.',
  'register.php' => 'Crie uma conta de cliente para concluir e acompanhar encomendas na CargaPrime.',
  'orders.php' => 'Área reservada para acompanhamento das encomendas do cliente CargaPrime.',
  'success.php' => 'Confirmação de encomenda registada com sucesso na plataforma CargaPrime.',
  'transport-document.php' => 'Documento de transporte associado a uma encomenda CargaPrime.',
];
$metaDescription = $metaDescription ?? ($isAdminPage
  ? 'Área administrativa reservada da plataforma CargaPrime.'
  : ($seoDescriptions[$scriptName] ?? 'Plataforma CargaPrime para compra simulada de materiais industriais por tonelada.'));
$canonicalPath = $canonicalPath ?? ($scriptName === 'index.php' ? '/' : '/' . $scriptName);
$canonicalUrl = APP_CANONICAL_BASE_URL . $canonicalPath;
$privatePages = ['cart.php', 'checkout.php', 'login.php', 'register.php', 'orders.php', 'success.php', 'transport-document.php'];
$robotsContent = ($isAdminPage || in_array($scriptName, $privatePages, true)) ? 'noindex, nofollow' : 'index, follow';
$socialImage = APP_CANONICAL_BASE_URL . '/assets/images/journey/03-estrada.webp';
$socialImageAlt = 'Camião em transporte numa estrada, representando o acompanhamento logístico CargaPrime.';
?>
<!doctype html>
<html lang="pt-PT" data-app-base="<?= e(url()) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= e($metaDescription) ?>">
  <meta name="robots" content="<?= e($robotsContent) ?>">
  <meta name="theme-color" content="#07182c">
  <link rel="canonical" href="<?= e($canonicalUrl) ?>">
  <meta property="og:locale" content="pt_PT">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="CargaPrime">
  <meta property="og:title" content="<?= e($pageTitle) ?> | <?= APP_NAME ?>">
  <meta property="og:description" content="<?= e($metaDescription) ?>">
  <meta property="og:url" content="<?= e($canonicalUrl) ?>">
  <meta property="og:image" content="<?= e($socialImage) ?>">
  <meta property="og:image:alt" content="<?= e($socialImageAlt) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($pageTitle) ?> | <?= APP_NAME ?>">
  <meta name="twitter:description" content="<?= e($metaDescription) ?>">
  <meta name="twitter:image" content="<?= e($socialImage) ?>">
  <meta name="twitter:image:alt" content="<?= e($socialImageAlt) ?>">
  <title><?= e($pageTitle) ?> | <?= APP_NAME ?></title>
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
  <link rel="stylesheet" href="<?= url('assets/css/improvements.css') ?>">
  <link rel="stylesheet" href="<?= url('assets/css/support.css') ?>">
  <link rel="stylesheet" href="<?= url('assets/css/v3.css') ?>">
  <link rel="stylesheet" href="<?= url('assets/css/v3.1.css?v=3.3.1') ?>">
  <script defer src="<?= url('assets/js/app.js?v=3.2.0') ?>"></script>
  <script defer src="<?= url('assets/js/v3.js') ?>"></script>
  <script defer src="<?= url('assets/js/v3.1.js?v=3.2.1') ?>"></script>
  <?php if ($scriptName === 'products.php'): ?>
  <script defer src="<?= url('assets/js/catalog.js?v=3.6.4') ?>"></script>
  <?php endif; ?>
  <?php if ($scriptName === 'index.php'): ?>
  <script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'OnlineStore',
    'name' => APP_NAME,
    'url' => APP_CANONICAL_BASE_URL,
    'description' => $metaDescription,
    'inLanguage' => 'pt-PT',
    'areaServed' => 'PT',
    'email' => FACTORY_EMAIL,
    'telephone' => FACTORY_PHONE,
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => FACTORY_ADDRESS, 'addressCountry' => 'PT'],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
  <?php endif; ?>
</head>
<body>
<header class="site-header cp3-header">
  <a class="brand" href="<?= url('index.php') ?>" aria-label="CargaPrime - início">
    <span>Carga</span><strong>Prime.</strong>
  </a>
  <button class="menu-button" type="button" aria-expanded="false" aria-controls="main-nav">Menu</button>
  <nav id="main-nav" class="main-nav" aria-label="Navegação principal">
    <a href="<?= url('products.php') ?>">Comprar</a>
    <a href="<?= url('index.php#percurso') ?>">Como funciona</a>
    <a href="<?= url('help.php') ?>">Ajuda</a>
    <a href="<?= url('cart.php') ?>">Carrinho <span class="badge"><?= e(number_format(cart_count(), 1, ',', '.')) ?></span></a>
    <?php if (is_admin()): ?><a href="<?= url('admin/index.php') ?>">Administração</a><a href="<?= url('logout.php') ?>">Sair</a>
    <?php elseif (is_logged_in()): ?><a href="<?= url('orders.php') ?>">As minhas encomendas</a><a href="<?= url('logout.php') ?>">Sair</a>
    <?php else: ?><a href="<?= url('login.php') ?>">Entrar</a><?php endif; ?>
  </nav>
</header>
<main>
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>
