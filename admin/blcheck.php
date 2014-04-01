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
$weekago = time() - 604800; // 604800 seconds in 7 days
$geturls = $link->prepare("SELECT `id`, `alias`, `url` FROM `urls` WHERE `status` = '1' AND `time` > FROM_UNIXTIME($weekago)");
$geturls->execute();

// Bail if there are no URLs to check, otherwise show number being checked
$checkcount = $geturls->rowCount();
if ($checkcount == 0) {
	die('No URLs to check!');
} else {
	echo "Checking $checkcount URL(s)...\n\n";
}

// Loop through every enabled URL from the past week
while ($row = $geturls->fetch(PDO::FETCH_ASSOC)) {

	// Get the URLs id, alias, and actual (long) URL
	$id = $row['id'];
	$alias = $row['alias'];
	$longurl = $row['url'];

	// Loop through both the user-added long URL and the local alias
	$localurl = 'http://' . $_SERVER['SERVER_NAME'] . '/' . $alias;
	foreach (array($localurl, $longurl) as $checkurl) {

		// Check the URL in question
		$error = checkURL($checkurl);

		// Handle a bad URL/failed check
		if ( (isset($error)) && (!empty($error)) ) {

			// Add the URL to an array of bad URLs that we will disable later and give details about the bad URL
			$badurls[] = $id;
			echo "$checkurl (ID $id / $alias) - " . strip_tags($error) . "\n";

			break; # Don't bother checking the long/real URL if the local alias is already bad
		}
	}
}

// Bail if there are no URLs to disable
if (empty($badurls)) {
	echo "No URLs to disable!\n";

// Disable all bad URLs in our array and count affected
} else {

	// Disable all bad URLs in our array and count affected
	$badurls = implode(', ', $badurls);
	$disableurls = $link->prepare("UPDATE `urls` SET `status` = '0' WHERE `id` IN ($badurls)");

	if ($disableurls->execute()) {
		$disabledcount = $disableurls->rowCount();
	} else {
		die('Error disabling bad URLs!');
	}

	echo "\nDisabled $disabledcount URL(s).\n";
}

// Close MySQL connection
$link = null;

// Show final results
$endtime = microtime(true);
$howlong = $endtime - $starttime;
echo "\nTook $howlong seconds.\n";
echo "Done!\n";
