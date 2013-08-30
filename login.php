<?php

require 'inc.bootstrap.php';

if ( isset($_POST['user'], $_POST['pass']) ) {
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

?>

<form method="post" action>
	<p>E-mail: <input type="email" name="user" autofocus /></p>
	<p>Password: <input type="password" name="pass" /></p>
	<p><input type="submit" value="Log in" /></p>
</form>
