<?php

	define('DOCROOT', rtrim(dirname(__FILE__), '\\/'));
	define('PATH_INFO', isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : NULL);
	define('DOMAIN_PATH', dirname(rtrim($_SERVER['PHP_SELF'], PATH_INFO)));
	define('DOMAIN', rtrim(rtrim($_SERVER['HTTP_HOST'], '\\/') . DOMAIN_PATH, '\\/'));

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

	$output = renderer($renderer)->display(getCurrentPage());

	$cookie_params = session_get_cookie_params();
	if(empty($_SESSION[__SYM_COOKIE_PREFIX_])) {
		if(empty($_COOKIE[session_name()])) {
			header_remove('Set-Cookie');
		}
		else {
			setcookie(session_name(),session_id(),time()-3600, $cookie_params['path'], $cookie_params['domain'], $cookie_params['secure'], $cookie_params['httponly']);
		}
	}
	else {
		header_remove('Set-Cookie');
		setcookie(session_name(),session_id(),time() + TWO_WEEKS, $cookie_params['path'], $cookie_params['domain'], $cookie_params['secure'], $cookie_params['httponly']);
	}

	echo $output;

	exit;
