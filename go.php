<?php

// Create a function to set header, display error message on bad URLs, and exit
function showError ($error) {

	// The array keys are the HTTP status codes and the value of each key is the meaning of said code
	global $errors;
	$prettyerror = $errors["$error"];

	// Set the HTTP response header based on the error ("HTTP/1.1 404 Not Found", for example)
	header($_SERVER['SERVER_PROTOCOL'] . " $error $prettyerror");

	// Show the error page using the "pretty" error message
	echo <<< END
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="description" content="gaw.sh url shortener $prettyerror">
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" type="text/css" href="/gawsh.css">
<link rel="shortcut icon" href="/favicon.ico">
<title>gaw.sh URL short... $prettyerror</title>
</head>
<body>
<h1><a href="/">gaw.sh url shortener</a></h1><br>
<span id="error">$prettyerror</span>
</body>
</html>
END;
} // Finish our custom error message function

// Handle direct visits to this file, without an alias passed, by redirecting to "/"
if ( (!isset($_GET['x'])) || (empty($_GET['x'])) ) {
	header('Location: /', TRUE, 302);

// Start the search for the URL if an alias was given
} else {

	// Create an array of possible HTTP error codes and their meanings
	$errors = array('401' => 'Not Authorized', '403' => 'Forbidden', '404' => 'Not Found', '410' => 'Gone', '500' => 'Internal Server Error', '503' => 'Service Unavailable');

	// Just show an error immediately for forced errors and stop further execution
	if (array_key_exists($_GET['x'], $errors)) {
		showError($_GET['x']);
		exit;
	}

	// Require configuration; do not need functions.php here
	require('admin/config.php');

	// Connect to MySQL and choose database
	try {
		$link = new PDO("mysql:host=$sqlhost;dbname=$sqldb", $sqluser, $sqlpass);

	// Show clean 503 service unavailable error if the database is unavailable
	} catch (PDOException $e) {
		showError('503');
		exit;
	}

	// Check if the alias exists
	$check = $link->prepare("SELECT `id`, `url`, `status` FROM `urls` WHERE `alias` = ?");
	$check->bindValue(1, $_GET['x'], PDO::PARAM_STR);
	$check->execute();

	// Check if the alias exists in the database
	if ($check->rowCount() >= 1) {

		// Get ID, long URL, and status if the alias exists
		while ($row = $check->fetch(PDO::FETCH_ASSOC)) {
			$id = $row['id'];
			$to = $row['url'];
			$status = $row['status'];
		}

		// Decide what to do next based on URL status
		switch ($status) {

			// Active
			case 1:

				// Set a variable so that we redirect later, after the MySQL connection is closed
				$redirect = $to;

				// Add an entry to the visitors MySQL table
				$addvisit = $link->prepare("INSERT INTO `visits` (`id`, `ip`, `browser`, `referrer`, `time`) VALUES (?, ?, ?, ?, NOW())");
				$addvisit->bindValue(1, $id, PDO::PARAM_INT);
				$addvisit->bindValue(2, $ip, PDO::PARAM_STR);
				$addvisit->bindValue(3, $browser, PDO::PARAM_STR);
				$addvisit->bindValue(4, $referrer, PDO::PARAM_STR);
				$addvisit->execute();
			break;

			// Disabled returns 410 Gone error page
			case 0: showError('410'); break;

			// Neither active nor disabled (or weird status like "-1" for hidden) returns 404 Not Found error
			default: showError('404'); break;

		} // End status switch

	// Show 404 Not Found error if the alias was actually not found in the database
	} else {
		showError('404');
	}

	// Disconnect from MySQL
	$link = null;

} // End alias search check

// Redirect the user to the long URL if it was an active/valid alias
if (isset($redirect)) {
	header("Location: $redirect", TRUE, 301);
}
