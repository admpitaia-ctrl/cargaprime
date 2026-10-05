<?php
require __DIR__ . '/includes/bootstrap.php';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $name = trim((string)($_POST['name'] ?? ''));
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm'] ?? '');
    $birthDate = (string)($_POST['birth_date'] ?? '');
    $adultConfirmed = isset($_POST['adult_confirm']);

    if ($name === '' || $username === '' || $password === '' || $birthDate === '') $errors[] = 'Todos os campos são obrigatórios.';
    if (mb_strlen($name) < 3 || mb_strlen($name) > 120) $errors[] = 'Indique um nome completo válido.';
    if (!preg_match('/^[A-Za-z0-9._-]{4,60}$/', $username)) $errors[] = 'O utilizador deve ter 4 a 60 caracteres e usar apenas letras, números, ponto, hífen ou underscore (sem @ e sem espaços).';
    $birth = DateTime::createFromFormat('Y-m-d', $birthDate);
    $adultLimit = new DateTime('today -18 years');
    if (!$birth || $birth->format('Y-m-d') !== $birthDate || $birth > $adultLimit) $errors[] = 'É necessário ter 18 anos ou mais.';
    if (!$adultConfirmed) $errors[] = 'Confirme que tem 18 anos ou mais.';
    if (strlen($password) < 8) $errors[] = 'A palavra-passe deve ter pelo menos 8 caracteres.';
    if ($password !== $confirm) $errors[] = 'As palavras-passe não coincidem.';

    if (!$errors) {
        try {
            $stmt = db()->prepare('INSERT INTO users (name, username, password_hash, role, birth_date) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $username, password_hash($password, PASSWORD_DEFAULT), 'customer', $birthDate]);
            if (is_json_request()) json_response(true, 'Conta criada. Já pode iniciar sessão.', 200, ['redirect' => url('login.php')]);
            flash('success', 'Conta criada. Já pode iniciar sessão.');
            redirect('login.php');
        } catch (PDOException $e) {
            $errors[] = 'Esse nome de utilizador já existe.';
        }
    }
    if ($errors && is_json_request()) json_response(false, implode(' ', $errors), 422);
}
$pageTitle = 'Criar conta';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-page"><form class="panel auth-card ajax-form" method="post" action="<?=url('register.php')?>"><p class="eyebrow">NOVO CLIENTE</p><h1>Criar conta</h1><?php foreach($errors as $error):?><div class="alert alert-error"><?=e($error)?></div><?php endforeach;?><input type="hidden" name="csrf_token" value="<?=csrf_token()?>"><label>Nome completo<input name="name" maxlength="120" autocomplete="name" value="<?=e($_POST['name']??'')?>" required></label><label>Data de nascimento<input type="date" name="birth_date" max="<?=date('Y-m-d',strtotime('-18 years'))?>" value="<?=e($_POST['birth_date']??'')?>" required></label><label>Utilizador<input name="username" minlength="4" maxlength="60" pattern="[A-Za-z0-9._-]{4,60}" title="Use 4 a 60 caracteres: letras, números, ponto, hífen ou underscore; sem @ e sem espaços." autocomplete="username" value="<?=e($_POST['username']??'')?>" required></label><label>Palavra-passe<div class="password-wrap"><input id="new-password" type="password" name="password" minlength="8" autocomplete="new-password" required><button type="button" class="password-toggle" data-target="new-password">Ver</button></div></label><label>Confirmar palavra-passe<div class="password-wrap"><input id="confirm-password" type="password" name="confirm" minlength="8" autocomplete="new-password" required><button type="button" class="password-toggle" data-target="confirm-password">Ver</button></div></label><label class="check"><input type="checkbox" name="adult_confirm" value="1" <?=isset($_POST['adult_confirm'])?'checked':''?> required> Confirmo que tenho 18 anos ou mais.</label><button class="button button-primary full">Criar conta</button><span class="save-feedback" role="status"></span><p class="auth-foot">Já tem conta? <a href="<?=url('login.php')?>">Iniciar sessão</a></p></form></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
