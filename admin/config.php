<?php

// Set some MySQL variables
$sqlhost = 'localhost';
$sqluser = 'gawsh';
$sqlpass = 'password';
$sqldb = 'gawsh';

// Google Safe Browsing API key; leave blank if you do not want to check this blacklist
$gsbkey = '';

// Get some user variables
$time = time();
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) { $ip = $_SERVER['HTTP_X_FORWARDED_FOR']; } else { $ip = $_SERVER['REMOTE_ADDR']; }
$browser = $_SERVER['HTTP_USER_AGENT'];
$referrer = $_SERVER['HTTP_REFERER'];

?>
