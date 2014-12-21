<?php

// Create a function to check if a URL is valid/online phishing website according to PhishTank
function isPT ($url, $ptkey) {

	// PhishTank expects the URL that you are checking to be URL encoded
	$url = urlencode($url);

	// Perform an HTTP POST request to PhishTank including the encoded url to get a JSON response using your application/developer key
	$c = curl_init();
	curl_setopt($c, CURLOPT_URL, 'http://checkurl.phishtank.com/checkurl/');
	curl_setopt($c, CURLOPT_POST, 1);
	curl_setopt($c, CURLOPT_POSTFIELDS, "format=json&app_key=$ptkey&url=$url");
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($c, CURLOPT_USERAGENT, 'GAW.SH URL Shortener - http://gaw.sh/');
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt($c, CURLOPT_TIMEOUT, 2);
	$r = curl_exec($c);
	curl_close($c);

	// If the URL is in PhishTanks database, it is a valid and online phishing website
	if (preg_match('/"in_database":true/', $r)) {
		return TRUE;
	} else {
		return FALSE;
	}
}

// Create a function to check if a URL is listed on the Google Safe Browsing API which includes phishing/malware URLs
function isGSB ($url, $gsbkey) {

	// Append the encoded URL that we are checking to the Google Safe Browsing API lookup URL
	$gsburl = 'https://sb-ssl.google.com/safebrowsing/api/lookup?client=gawsh&apikey=' . $gsbkey . '&appver=1.5.2&pver=3.0&url=' . urlencode($url);

	// Perform an HTTP GET request to the Google Safe Browsing API and make a decision based on response code
	$c = curl_init();
	curl_setopt($c, CURLOPT_URL, $gsburl);
	curl_setopt($c, CURLOPT_HEADER, 1);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($c, CURLOPT_NOBODY, 1);
	curl_setopt($c, CURLOPT_USERAGENT, 'GAW.SH URL Shortener - http://gaw.sh/');
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt($c, CURLOPT_TIMEOUT, 2);
	$r = curl_exec($c);
	$code = curl_getinfo($c, CURLINFO_HTTP_CODE);
	curl_close($c);

	// A 200 HTTP response code indicates that the website is involved with phishing or malware
	if ($code == '200') {
		return TRUE;
	} else {
		return FALSE;
	}
}

// Create a function to check if a domain is on Spamhaus' DBL
function isDBL ($domain) {

	// Append ".dbl.spamhaus.org" to the domain name and look it up
	$domain .= '.dbl.spamhaus.org';
	$lookup = gethostbyname($domain);

	// Check the domain name in question against the Spamhaus DBL; ignore 127.0.1.255 (IPs)
	if ( ($lookup == '127.0.1.255') || ($lookup == $domain) ) {
		return FALSE;
	} else {
		return TRUE;
	}
}

// Create a function to check if a domain is on SURBL
function isSURBL ($domain) {

	// Append ".multi.surbl.org" to the domain name and look it up
	$domain .= '.multi.surbl.org';
	$lookup = gethostbyname($domain);

	// Check the domain name in question against SURBL
	if ($lookup == $domain) {
		return FALSE;
	} else {
		return TRUE;
	}
}

// Create a function to check if a domain is on URIBL
function isURIBL ($domain) {

	// Append ".multi.uribl.com" to the domain name and look it up
	$domain .= '.multi.uribl.com';
	$lookup = gethostbyname($domain);

	// Check the domain name in question against URIBL
	if ($lookup == $domain) {
		return FALSE;
	} else {
		return TRUE;
	}
}

// Create a function to check if a domain resolves to an IP address on Spamhaus' ZEN
function isZEN ($domain) {

	// Resolve the domain name to an IPv4 address
	$lookups = dns_get_record($domain, DNS_A);

	// Loop through each IP address returned
	foreach ($lookups as $lookup) {

		// Reverse the octet order of the IP address, append ".zen.spamhaus.org", and look it up
		$checkname = implode('.', array_reverse(explode('.', $lookup['ip']))) . '.zen.spamhaus.org';
		$check = gethostbyname($checkname);

		// Check the IP address in question against Spamhaus' ZEN; ignore 127.0.0.10-11 IPs (PBL)
		if ( ($check != $checkname) && ($check != '127.0.0.10') && ($check != '127.0.0.11') ) {
			return TRUE;
		}
	}
}

// Create a function to check if a domain name is dumb
function isDumb ($domain) {

	// Create an array of dumb domain names from file
	$dumbfile = $_SERVER['DOCUMENT_ROOT'] . 'admin/dumb.txt';
	$dumb = file($dumbfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	// Check the domain name in question against list of dumb domains
	if (array_search(strtolower($domain), $dumb)) {
		return TRUE;
	}
}

// Create a function to check if a URL is legit
function isLegit ($url) {

	// Hit the URL with an HTTP request using cURL to make sure it connects/works
	$c = curl_init();
	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_HEADER, 1);
	curl_setopt($c, CURLOPT_NOBODY, 1);
	curl_setopt($c, CURLOPT_USERAGENT, 'GAW.SH URL Shortener - http://gaw.sh/');
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt($c, CURLOPT_TIMEOUT, 2);
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0); // Do not fail on "invalid" SSL certificates
	curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
	$r = curl_exec($c);
	$code = curl_getinfo($c, CURLINFO_HTTP_CODE);
	curl_close($c);

	// As long as the URL works and does not return 404/Not Found, it is legit
	if ( ($code != '0') && ($code != '404') ) {
		return TRUE;
	} else {
		return FALSE;
	}
}

// Master function to run all of the above checks against a URL and/or its domain name
// ...but do not use all functions against local URLs/aliases passed from admin/blcheck.php where local = true
function checkURL ($url, $local = 'false') {

	// Need the Google Safe Browsing API and PhishTank keys from "config.php"
	global $gsbkey, $ptkey;

	// Always determine domain name
	$domain = parse_url($url, PHP_URL_HOST);

	// Always check that all URLs have sane characters
	//  but this bug breaks IPv6 address URLs: https://bugs.php.net/bug.php?id=54629
	if (!filter_var($url, FILTER_VALIDATE_URL)) {
		$error = 'Invalid URL (formatting)';

	// Disallow URLs containing "=http://" or "=https://"
	} elseif ( (preg_match('/=http(s)?:\/\//', $url)) || (preg_match('/%3Dhttp(s)?%3A%2F%2F/', $url)) ) {
		$error = 'Invalid URL';

	// Check remote domain names against the dumb domain list
	} elseif ( ($local == 'false') && (isDumb($domain)) ) {
		$error = 'Invalid URL (bad domain name)';

	// Check that remote URLs actually work
	} elseif ( ($local == 'false') && (!isLegit($url)) ) {
		$error = 'Invalid URL (not found)';

	// Check remote domain names against Spamhaus' DBL
	} elseif ( ($local == 'false') && (isDBL($domain)) ) {
		$error = 'Invalid URL (<a href="http://www.spamhaus.org/faq/answers.lasso?section=Spamhaus%20DBL">blacklisted</a>)';

	// Check remote domain names against SURBL
	} elseif ( ($local == 'false') && (isSURBL($domain)) ) {
		$error = 'Invalid URL (<a href="http://www.surbl.org/faqs">blacklisted</a>)';

	// Check remote domain names against URIBL
	} elseif ( ($local == 'false') && (isURIBL($domain)) ) {
		$error = 'Invalid URL (<a href="http://www.uribl.com/about.shtml">blacklisted</a>)';

	// Check remote domain names against Spamhaus' ZEN
	} elseif ( ($local == 'false') && (isZEN($domain)) ) {
		$error = 'Invalid URL (<a href="http://www.spamhaus.org/faq/index.lasso">blacklisted</a>)';

	// Check all URLs against Google Safe Browsing API, if an API key was given in "config.php"
	} elseif ( (!empty($gsbkey)) && (isGSB($url, $gsbkey)) ) {
		$error = 'Invalid URL (<a href="http://www.google.com/safebrowsing/diagnostic?site=' . $domain . '">blacklisted</a>)';

	// Check all URLs against PhishTank API, if a developer key was given in "config.php"
	} elseif ( (!empty($ptkey)) && (isPT($url, $ptkey)) ) {
		$error = 'Invalid URL (<a href="https://www.phishtank.com/">phishing</a>)';
	}

	// Return any error (i.e. the URL is bad)
	if ( (isset($error)) && (!empty($error)) ) {
		return $error;
	}
}
