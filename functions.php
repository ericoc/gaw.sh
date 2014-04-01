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

/*
Commenting this out for now since it might have been over-kill; was catching anything using Cloudflares name servers for example

// Create a function to check if the authoritative nameservers of a domain are on Spamhaus' ZEN
function isZENNS ($domain) {

	// Determine authoritative nameservers for the domain name
	$authns = dns_get_record($domain, DNS_NS);

	// Check the IP address of each authoritative nameserver
	for ($z = 0; $z < count($authns); $z++) {
		if (isZEN($authns[$z][target])) {
			return TRUE;
		}
	}
}
*/

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
function checkURL ($url) {

	// Need the Google Safe Browsing API and PhishTank keys from "config.php"
	global $gsbkey, $ptkey;

	// Determine domain name
	$domain = parse_url($url, PHP_URL_HOST);

	// Only try to check the PhishTank and Google Safe Browsing APIs if it is a local URL
	if ($domain == $_SERVER['SERVER_NAME']) {

		if ( (!empty($gsbkey)) && (isGSB($url, $gsbkey)) ) {
			$error = 'Invalid URL (<a href="http://www.google.com/safebrowsing/diagnostic?site=' . $domain . '">blacklisted</a>)';

		} elseif ( (!empty($ptkey)) && (isPT($url, $ptkey)) ) {
			$error = 'Invalid URL (<a href="https://www.phishtank.com/">phishing</a>)';
		}

	// Run through all of the checks if is a real/remote URL
	} else {

		// Check if domain is on the dumb list
		if (isDumb($domain)) {
			$error = 'Invalid URL (bad domain name)';

		// Check domain against Spamhaus' DBL
		} elseif (isDBL($domain)) {
			$error = 'Invalid URL (<a href="http://www.spamhaus.org/faq/answers.lasso?section=Spamhaus%20DBL">blacklisted</a>)';

		// Check domain against SURBL
		} elseif (isSURBL($domain)) {
			$error = 'Invalid URL (<a href="http://www.surbl.org/faqs">blacklisted</a>)';

		// Check domain against URIBL
		} elseif (isURIBL($domain)) {
			$error = 'Invalid URL (<a href="http://www.uribl.com/about.shtml">blacklisted</a>)';

		// Check domain against Spamhaus' ZEN
		} elseif (isZEN($domain)) {
			$error = 'Invalid URL (<a href="http://www.spamhaus.org/faq/index.lasso">blacklisted</a>)';

		// Check domain against Google Safe Browsing API if an API key was given in "config.php"
		} elseif ( (!empty($gsbkey)) && (isGSB($url, $gsbkey)) ) {
			$error = 'Invalid URL (<a href="http://www.google.com/safebrowsing/diagnostic?site=' . $domain . '">blacklisted</a>)';

		// Check URL against PhishTank API if a developer key was given in "config.php"
		} elseif ( (!empty($ptkey)) && (isPT($url, $ptkey)) ) {
			$error = 'Invalid URL (<a href="https://www.phishtank.com/">phishing</a>)';

		// Check that the URL actually works
		} elseif (!isLegit($url)) {
			$error = 'Invalid URL (not found)';
		}
	}

	// Return any error (i.e. the URL is bad)
	if ( (isset($error)) && (!empty($error)) ) {
		return $error;
	}
}
