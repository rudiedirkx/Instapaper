<?php

require 'inc.http.php';

function do_logout() {
	setcookie('ip_auth', '', 1);
	setcookie('oauth_auth', '', 1);
}

function get_ip_auth() {
	if ( isset($_COOKIE['ip_auth']) ) {
		$auth = @unserialize(do_decrypt($_COOKIE['ip_auth']));
		if ( $auth ) {
			return $auth;
		}
	}
}

function get_css() {
	$file = __DIR__ . '/style.css';
	$css = trim(file_get_contents($file));
	$css = preg_replace('/(\t|(?<!})(?:\r\n|\n))+/', ' ', $css);
	// $css = preg_replace('/[\s\r\n]+/', '', $css);
	return $css;
}

function do_encrypt( $data ) {
	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	return base64_encode($iv . mcrypt_encrypt(MCRYPT_RIJNDAEL_256, substr(APP_SECRET . APP_SECRET, 0, 24), $data, MCRYPT_MODE_CBC, $iv));
}

function do_decrypt( $data ) {
	$data = base64_decode($data);
	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
	$iv = substr($data, 0, $iv_size);
	$data = substr($data, $iv_size);
	return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, substr(APP_SECRET . APP_SECRET, 0, 24), $data, MCRYPT_MODE_CBC, $iv), "\0");
}

function ip_token( $redirect = true ) {
	// 1. Get token from COOKIE
	if ( isset($_COOKIE['oauth_auth']) ) {
		$auth = @unserialize(do_decrypt($_COOKIE['oauth_auth']));
		if ( $auth ) {
			return $auth;
		}
	}

	// 2. Get new token from IP API using user & pass from COOKIE
	if ( isset($_COOKIE['ip_auth']) ) {
		$auth = @unserialize(do_decrypt($_COOKIE['ip_auth']));
		if ( $auth ) {
			$auth = ip_authorize($auth['user'], $auth['pass'], $error);
			if ( $auth ) {
				$expire = strtotime('+1 year');
				setcookie('oauth_auth', do_encrypt(serialize($auth)), $expire);
				return $auth;
			}
		}
	}

	// 3. Ask user for user & pass
	if ( $redirect ) {
		return do_redirect('login');
	}
}

function do_redirect( $path, $query = null ) {
	$fragment = '';
	if ( is_int($p = strpos($path, '#')) ) {
		$fragment = substr($path, $p);
		$path = substr($path, 0, $p);
	}

	$query = $query ? '?' . http_build_query($query) : '';
	$location = $path . '.php' . $query . $fragment;
	header('Location: ' . $location);
	exit;
}

function html( $text ) {
	return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function ip_authorize( $user, $pass, &$error = array() ) {
	$method = 'POST';
	$url = 'https://www.instapaper.com/api/1/oauth/access_token';
	$data = array(
		'x_auth_username' => $user,
		'x_auth_password' => $pass,
		'x_auth_mode' => 'client_auth',
	);

	$http = sign_request($method, $url, $data);
	$response = $http->request();

	if ( $response->code == 200 ) {
		parse_str($response->body, $params);
		return array(
			'token' => $params['oauth_token'],
			'secret' => $params['oauth_token_secret'],
		);
	}

	$error['code'] = $response->code;
	$error['error'] = trim($response->body);
	$error['response'] = $response;
	return false;
}

function do_archive( $id ) {
	$method = 'POST';
	$url = 'https://www.instapaper.com/api/1/bookmarks/archive';
	$data = array(
		'bookmark_id' => $id,
	);

	$http = sign_request($method, $url, $data, SECRET);
	$response = $http->request();

	$encoded_response = $response->body;
	$decoded_response = json_decode($encoded_response);

	$bookmarks = do_filter_bookmarks($decoded_response);

	if ( !empty($bookmarks[0]->bookmark_id) ) {
		return $bookmarks[0];
	}
}

function do_filter_bookmarks( $items ) {
	return array_values(array_filter($items, function($item) {
		return $item->type == 'bookmark';
	}));
}

function set_bookmarks( $bookmarks ) {
	file_put_contents(BOOKMARKS_CACHE_FILE, json_encode($bookmarks));
}

function get_bookmarks( &$refresh = false ) {
	$bookmarks = @json_decode(file_get_contents(BOOKMARKS_CACHE_FILE));
	if ( $refresh || !$bookmarks || filemtime(BOOKMARKS_CACHE_FILE) < time() - BOOKMARKS_CACHE_TTL ) {
		$refresh = true;

		$method = 'POST';
		$url = 'https://www.instapaper.com/api/1/bookmarks/list';
		$data = array(
			'limit' => 50,
		);

		// /api/1/bookmarks/list
		// folder_id=&have=&limit=50&oauth_consumer_key=lZDAomC8wjvA5sjOKfkgOeVVwMMweCanbDstLgtCnX3zViMOl8&oauth_nonce=efdbd46b4e1526fea381cb9922551d16&oauth_signature=mDfCxiXsgL0HWFSN7OspLukgBuc%3D&oauth_signature_method=HMAC-SHA1&oauth_timestamp=1369081446&oauth_token=3P9tXGhosNCI1KSi5YFkeVzVIED7YdtkXvOAMBoaWJrQ5kCO73&oauth_version=1.0

		$http = sign_request($method, $url, $data, SECRET);
		$response = $http->request();

		if ( $response->code != 200 ) {
			do_logout();
			return do_redirect('login', array('error' => $response->code));
		}

		$encoded_response = $response->body;
		$decoded_response = json_decode($encoded_response);

		$bookmarks = do_filter_bookmarks($decoded_response);

		set_bookmarks($bookmarks);
	}

	return $bookmarks;
}

function sign_request( $method, $url, &$data, $secret = '' ) {
	if ( defined('TOKEN') ) {
		$data['oauth_token'] = TOKEN;
	}
	$data['oauth_consumer_key'] = OAUTH_COMSUMER;
	$data['oauth_timestamp'] = time();
	$data['oauth_nonce'] = generate_nonce();
	$data['oauth_signature_method'] = 'HMAC-SHA1';
	$data['oauth_version'] = '1.0';

	ksort($data);
	$signatureBaseString = $method . '&' . urlencode_rfc3986($url) . '&' . urlencode_rfc3986(http_build_query($data));

	$signatureKey = urlencode_rfc3986(OAUTH_SECRET) . '&' . urlencode_rfc3986($secret);
	$signature = base64_encode(hash_hmac('sha1', $signatureBaseString, $signatureKey, true));

	$data['oauth_signature'] = $signature;

	// $componentData = array_intersect_key($data, array_flip(array('oauth_consumer_key', 'oauth_token', 'oauth_signature_method', 'oauth_signature', 'oauth_timestamp', 'oauth_nonce', 'oauth_version')));
	// $components = array('realm="' . $url . '"');
	// foreach ( $componentData AS $cn => $cv ) {
		// $components[] = $cn . '="' . $cv . '"';
	// }

	$http = HTTP::create($url, array(
		'method' => $method,
		'data' => $data,
		// 'headers' => array('Authorization: OAuth ' . implode(', ', $components)),
	));
	return $http;
}

function urlencode_rfc3986( $input ) {
	return str_replace('+', ' ', str_replace('%7E', '~', rawurlencode($input)));
}

function generate_nonce() {
	$mt = microtime();
	$rand = mt_rand();
	return md5($mt . $rand);
}
