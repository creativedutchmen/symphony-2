<?php

	define('DOCROOT', rtrim(dirname(__FILE__), '\\/'));
	define('DOMAIN', rtrim(rtrim($_SERVER['HTTP_HOST'], '\\/') . dirname($_SERVER['PHP_SELF']), '\\/'));

	require(DOCROOT . '/symphony/lib/boot/bundle.php');

	function renderer($mode='frontend'){
		if(!in_array($mode, array('frontend', 'administration'))){
			throw new Exception('Invalid Symphony Renderer mode specified. Must be either "frontend" or "administration".');
		}
		require_once(CORE . "/class.{$mode}.php");
		return ($mode == 'administration' ? Administration::instance() : Frontend::instance());
	}

	$renderer = (isset($_GET['mode']) && strtolower($_GET['mode']) == 'administration'
			? 'administration'
			: 'frontend');

	header('Expires: Mon, 12 Dec 1982 06:14:00 GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');

	$output = renderer($renderer)->display(getCurrentPage());

	/*
		Lazy Cookie Setter; only sets session cookies when needed.
		This will improve the interoperability with caches like Varnish and Squid.

		Unfortunately there is no way to delete a specific previously set cookie from PHP.
		The only way seems to be the method employed here: store all the cookie we need to keep, then delete every cookie and add the stored cookies again.
		Luckily we can just store the raw header and output them again, so we do not need to actively parse the header string.
	*/

	$cookie_params = session_get_cookie_params();
	$list = headers_list();
	$custom_cookies = array();

	foreach ($list as $hdr) {
		if ((stripos($hdr, 'Set-Cookie') !== FALSE) && (stripos($hdr, session_id()) === FALSE)) {
			$custom_cookies[] = $hdr;
		}
	}
	header_remove('Set-Cookie');
	foreach ($custom_cookies as $custom_cookie) {
		header($custom_cookie);
	}
	if (empty($_SESSION[__SYM_COOKIE_PREFIX_]) && !empty($_COOKIE[session_name()])) {
		setcookie(
			session_name(),
			session_id(),
			time() - 3600,
			$cookie_params['path'],
			$cookie_params['domain'],
			$cookie_params['secure'],
			$cookie_params['httponly']
		);
	}
	elseif(!empty($_SESSION[__SYM_COOKIE_PREFIX_])) {
		setcookie(
			session_name(),
			session_id(),
			time() + TWO_WEEKS,
			$cookie_params['path'],
			$cookie_params['domain'],
			$cookie_params['secure'],
			$cookie_params['httponly']
		);
	}

	echo $output;

	exit;
