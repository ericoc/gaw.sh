<?php

// Show all errors
error_reporting('E_ALL');

// Set some MySQL variables
$sqlhost = 'localhost';
$sqluser = 'gawsh';
$sqlpass = 'password';
$sqldb = 'gawsh';

// Get some user variables
$time = time();
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) { $ip = $_SERVER['HTTP_X_FORWARDED_FOR']; } else { $ip = $_SERVER['REMOTE_ADDR']; }
if (isset($_SERVER['HTTP_USER_AGENT'])) { $browser = $_SERVER['HTTP_USER_AGENT']; }
if (isset($_SERVER['HTTP_REFERER'])) { $referrer = $_SERVER['HTTP_REFERER']; }

?>
