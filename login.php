<?php
require __DIR__ . '/includes/bootstrap.php'; $errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){validate_csrf();$username=trim((string)($_POST['username']??''));$password=(string)($_POST['password']??'');$stmt=db()->prepare('SELECT id, name, username, password_hash, role, birth_date FROM users WHERE username=? AND active=1 LIMIT 1');$stmt->execute([$username]);$user=$stmt->fetch();if($user&&password_verify($password,$user['password_hash'])){unset($user['password_hash']);
session_regenerate_id(true);$_SESSION['user']=$user;
unset($_SESSION['cart']);

merge_guest_cart_into_user_cart();

if ($user['role'] === 'admin') {
    $destination = 'admin/index.php';
} elseif (cart_count() > 0) {
    $destination = 'cart.php';
} else {
    $destination = 'index.php';
}

if(is_json_request())json_response(true,'Sessão iniciada.',200,['redirect'=>url($destination)]);

redirect($destination);
}$errors[]='Nome de utilizador ou palavra-passe incorretos.';if(is_json_request())json_response(false,$errors[0],422);}
$pageTitle='Entrar';require __DIR__.'/includes/header.php'; ?>
<section class="auth-page"><form class="panel auth-card ajax-form" method="post" action="<?=url('login.php')?>"><p class="eyebrow">ÁREA RESERVADA</p><h1>Iniciar sessão</h1><p>Clientes e administradores entram pela mesma porta; o perfil define o acesso.</p><?php foreach($errors as $error):?><div class="alert alert-error"><?=e($error)?></div><?php endforeach;?><input type="hidden" name="csrf_token" value="<?=csrf_token()?>"><label>Utilizador<input name="username" autocomplete="username" required></label><label>Palavra-passe<div class="password-wrap"><input id="password" type="password" name="password" autocomplete="current-password" required><button type="button" class="password-toggle" data-target="password" aria-label="Mostrar palavra-passe">Ver</button></div></label><button class="button button-primary full">Entrar</button><span class="save-feedback" role="status"></span><p class="auth-foot">Ainda não tem conta? <a href="<?=url('register.php')?>">Criar utilizador</a></p></form></section>
<?php require __DIR__.'/includes/footer.php'; ?>
