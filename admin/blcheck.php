<?php

// Show any and all possible errors and begin counting how long it takes for this script to run
error_reporting(E_ALL);
$starttime = microtime(true);

// Include configuration and functions files
require('config.php'); // MySQL credentials and user variables
require('../functions.php'); // Blacklist/URL verification checks

// Connect to MySQL and choose database
try {
	$link = new PDO("mysql:host=$sqlhost;dbname=$sqldb", $sqluser, $sqlpass);
} catch (PDOException $e) {
	die ('Cannot connect to DB!');
}

// Query for enabled URLs added within the past week
$weekago = $time - 604800; // 604800 seconds in 7 days
$geturls = $link->prepare("SELECT id, alias, url FROM `urls` WHERE `status` = '1' AND `time` > $weekago");
$geturls->execute();

// Show total number of URLs being checked and begin counting bad URLs
echo "Checking " . $geturls->rowCount() . " URLs...\n\n";
$badurls = 0;

// Loop through every enabled URL from the past week
while ($row = $geturls->fetch(PDO::FETCH_ASSOC)) {

	// Get the URLs id, alias, and actual (long) URL
	$id = $row['id'];
	$alias = $row['alias'];
	$url = $row['url'];

	// Actually check the URL
	$error = checkURL($url);

	// If the URL is bad, say so while incrementing the amount of fails and disable the short URL/alias in question
	if ( (isset($error)) && (!empty($error)) ) {

		$badurls++;
		echo "Found and disabled bad URL \"$url\" (ID $id / $alias) " . strip_tags($error) . " \n";
		$disableurl = $link->prepare("UPDATE `urls` SET `status` = '0' WHERE `id` = ?");
		$disableurl->bindValue(1, $id, PDO::PARAM_STR);
		$disableurl->execute();
		$error = '';
	}
}

// Close MySQL connection
$link = null;

// Show final results
$endtime = microtime(true);
$howlong = $endtime - $starttime;
echo "\nFound and disabled $badurls bad URLs\n";
echo "Took $howlong seconds\n";
echo "Done!\n";

?>
