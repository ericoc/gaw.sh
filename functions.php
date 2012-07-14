<?php

// Create a function to check if a URL is on Spamhaus' DBL
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

// Create a function to check if a URL is on SURBL
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

// Create a function to check if a URL is on URIBL
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

// Create a function to check if an URL is on Spamhaus' ZEN
function isZEN ($domain) {

	// Resolve the domain name; this is broken if it's an IP address instead of a domain name - need to fix
	$lookup = dns_get_record($domain, DNS_A);

	// Loop through each IP address
	for ($z = 0; $z < count($lookup); $z++) {

		// Reverse octets of the IP address, append ".zen.spamhaus.org", and look it up
		$ips = explode('.', $lookup[$z][ip]);
		$checkip = $ips[3] . '.' . $ips[2] . '.' . $ips[1] . '.' . $ips[0] . '.zen.spamhaus.org';
		$check = gethostbyname($checkip);

		// Check the IP address in question against Spamhaus' ZEN; ignore 127.0.0.10-11 (IPs)
		if ( ($check != $checkip) && ($check != '127.0.0.10') && ($check != '127.0.0.11') ) {
			return TRUE;
		}
	}
}

// Create a function to check if the authoritative nameservers of a URL are on Spamhaus' ZEN
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

// Create a function to check if a URL is dumb
function isDumb ($domain) {

	// Create an array of dumb domain names from file
        $dumbfile = $_SERVER['DOCUMENT_ROOT'] . '/admin/dumb.txt';
        $dumb = file($dumbfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	// Check the domain name in question against list of dumb domains
	if (array_search(strtolower($domain), $dumb)) {
		return TRUE;
	}
}

// Create a function to check if a URL is legit
function isLegit ($url) {

	// Hit the URL with a HEAD request using cURL to make sure it connects/works
	$c = curl_init();
	curl_setopt($c, CURLOPT_URL, $url); // url
	curl_setopt($c, CURLOPT_HEADER, 1); // head request
	curl_setopt($c, CURLOPT_NOBODY, 1); // screw the body
	curl_setopt($c, CURLOPT_USERAGENT, 'GAW.SH URL Shortener - http://gaw.sh/'); // friendly user agent/browser string
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1); // get the thing
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, '1'); // short timeouts
	curl_setopt($c, CURLOPT_TIMEOUT, '1');
	curl_setopt($c, CURLOPT_DNS_USE_GLOBAL_CACHE, 1); // should make dns lookup faster?
	curl_setopt($c, CURLOPT_NOSIGNAL, 1); // "ignore any cURL function that causes a signal to be sent to the PHP process."
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0); // let me shorten urls even if that have a bogus ssl cert
	curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
	$r = curl_exec($c);
	$code = curl_getinfo($c, CURLINFO_HTTP_CODE); // get the http code returned by our head requests
	curl_close($c);

	// As long as the URL exists, it's legit
	if ( ($code != '0') && ($code != '404') ) {
		return TRUE;
	} else {
		return FALSE;
	}
}

?>
