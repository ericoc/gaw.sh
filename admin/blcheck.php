<?php

// Turn on debugging if asked to
if ( (isset($_GET['debug'])) && ($_GET['debug'] == 'yes') ) {
        $debug = TRUE;
} else {
	$debug = FALSE;
}

// Include configuration and functions files
include('config.php'); // MySQL credentials and user variables
include('functions.php'); // blacklist/URL verification checks

// Connect to MySQL and choose database
$link = mysql_connect($sqlhost, $sqluser, $sqlpass) OR die('Cannot connect to DB!');
mysql_select_db($sqldb, $link);

// Query for enabled URLs
$geturls = mysql_query("SELECT id, url FROM `urls` WHERE `status` = '1'", $link);

// Start out and begin counting bad URLs
if ($debug) {
	echo "Checking " . mysql_num_rows($geturls) . " URLs...<br><br>\n";
}
$badurls = 0;

// Loop through every enabled URL
while ($row = mysql_fetch_array($geturls)) {

	// Get the URL id and actual long URL
	$id = $row['id'];
	$url = $row['url'];

	if ($debug) {
		echo "Checking URL \"$url\" (ID $id)... ";
	}

	// Determine domain name
	$domain = parse_url($url, PHP_URL_HOST);

	// Check dumb domain list
	if (isDumb(strtolower($domain))) {
		$error = 'dumb domain';

	} elseif (!isLegit($url)) {
		$error = 'not legit';

	// Check URL against Spamhaus' DBL
	} elseif (isDBL($domain)) {
		$error = 'dbl';

	// Check URL against SURBL
	} elseif (isSURBL($domain)) {
		$error = 'surbl';

	// Check URL against URIBL
	} elseif (isURIBL($domain)) {
		$error = 'uribl';

	// Check URL and its nameservers against Spamhaus' ZEN
	} elseif ( (isZEN($domain)) || (isZENNS($domain)) ) {
		$error = 'zen/ns';

	} else {
		if ($debug) {
			echo "OK<br>\n";
		}
	}

	// If the URL is bad, do something (either increment the amount of fails or disable entirely?)
	if ( (isset($error)) && (!empty($error)) ) {
		$badurl++;

		if ($debug) {
			echo "$error<br>\n";
		}
		$error = "";
	}
}

if ($debug) {
	echo "<br>Found $badurl bad URLs<br>\n";
	echo "Done.\n";
}

// Close MySQL connection
mysql_close($link);

?>
