<?php

// Start calculating execution time
$starttime = microtime(true);

// Include configuration and functions files
require('config.php'); // MySQL credentials
require('../functions.php'); // Blacklist/URL verification checks

// Connect to MySQL and choose database
try {
	$link = new PDO("mysql:host=$sqlhost;dbname=$sqldb", $sqluser, $sqlpass);
} catch (PDOException $e) {
	die ('Cannot connect to DB!');
}

// Query for all enabled/active URLs
$geturls = $link->prepare("SELECT id, alias, url FROM `urls` WHERE `status` = '1'");
$geturls->execute();

// Show total number of URLs being checked and begin counting bad URLs
echo "Checking " . $geturls->rowCount() . " URLs...\n\n";
$badurls = 0;

// Loop through every enabled URL
while ($row = $geturls->fetch(PDO::FETCH_ASSOC)) {

	// Get the URL id , alias, and actual (long) URL
	$id = $row['id'];
	$alias = $row['alias'];
	$url = $row['url'];

	// Actually check the URL
	$error = checkURL($url);

	// If the URL is bad, do something (eventually disable entirely?)
	if ( (isset($error)) && (!empty($error)) ) {

		// For now, just increment the number of failed URLs and show any bad URLs while stripping the HTML from checkURL()
		$badurls++;
		echo "Found bad URL \"$url\" (ID $id / $alias) " . strip_tags($error) . " \n";
		$error = '';
	}
}

// Close MySQL connection
$link = null;

// Calculate execution time
$endtime = microtime(true);
$howlong = $endtime - $starttime;

// Show final results (number of bad URLs) and execution time
echo "\nFound $badurls bad URLs\n";
echo "Took $howlong seconds\n";
echo 'Done!';

?>
