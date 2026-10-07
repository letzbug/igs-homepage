<?php
require __DIR__.'/common.php';
if (logged_in()) redirect('/admin/');
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
    $ip=(string)($_SERVER['REMOTE_ADDR']??'unknown'); $now=time();
    db()->prepare('DELETE FROM auth_attempts WHERE at < ?')->execute([$now-900]);
    $q=db()->prepare('SELECT COUNT(*) FROM auth_attempts WHERE ip=?'); $q->execute([$ip]);
    if ((int)$q->fetchColumn()>=5) { http_response_code(429); $error='Bitte versuchen Sie es in 15 Minuten erneut.'; }
    else {
        $hash=@file_get_contents(data_dir().'/admin-password.hash');
        $password=(string)($_POST['password']??'');
        if ($hash!==false && password_verify($password,$hash)) {
            db()->prepare('DELETE FROM auth_attempts WHERE ip=?')->execute([$ip]);
            session_regenerate_id(true); $_SESSION['editor']=true; $_SESSION['csrf']=bin2hex(random_bytes(32)); redirect('/admin/');
        }
        db()->prepare('INSERT INTO auth_attempts (ip,at) VALUES (?,?)')->execute([$ip,$now]);
        $error='Anmeldung nicht möglich. Bitte Zugangsdaten prüfen.';
    }
}
admin_header('Anmelden'); ?><div class="admin-login"><span class="admin-kicker">Redaktion</span><h1>Willkommen zurück.</h1><p>Verwalten Sie Inhalte, Bücher, Beiträge und Bilder des Instituts.</p><?php if($error):?><div class="admin-alert" role="alert"><?=h($error)?></div><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=h(csrf())?>"><label for="password">Passwort</label><input id="password" type="password" name="password" autocomplete="current-password" required autofocus><button class="admin-primary" type="submit">Anmelden →</button></form></div><?php admin_footer(); ?>
