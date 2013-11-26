<?php

require 'inc.bootstrap.php';

if ( isset($_POST['user'], $_POST['pass']) ) {
	setcookie('ip_user', $_POST['user']);

	$auth = ip_authorize($_POST['user'], $_POST['pass'], $error);
	if ( $auth ) {
		$expire = strtotime('+1 year');
		$data = array(
			'user' => $_POST['user'],
			'pass' => $_POST['pass'],
		);
		setcookie('ip_auth', do_encrypt(serialize($data)), $expire);
		setcookie('oauth_auth', do_encrypt(serialize($auth)), $expire);

		return do_redirect('index');
	}

	echo "<p>That didn't work...</p>";
	echo '<pre>' . print_r($error, 1) . '</pre>';
	exit;
}

$error = @$_GET['error'];

?>

<? if ($error): ?>
	<p class="error">Error: <?= html($error) ?></p>
<? endif ?>

<form method="post" action>
	<p>E-mail: <input type="email" name="user" value="<?= @$_COOKIE['ip_user'] ?>" required /></p>
	<p>Password: <input type="password" name="pass" required /></p>
	<p><input type="submit" value="Log in" /></p>
</form>

<p>Credentials are encrypted and ONLY saved on YOUR device in a cookie.</p>
<p><a href="https://github.com/rudiedirkx/Instapaper">Code @ Github</a></p>
