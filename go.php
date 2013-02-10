<?php

// Create function to set header, display error message on bad URLs, and exit
function showError ($error) {

	$pretty = ucwords($error);
	header($_SERVER['SERVER_PROTOCOL'] . ' ' . $pretty);
	echo <<< END
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="description" content="gaw.sh url shortener $error">
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" type="text/css" href="/gawsh.css">
<link rel="shortcut icon" href="/favicon.ico">
<title>gaw.sh URL short... $error</title>
</head>
<body>
<center>
<h1><a href="/">gaw.sh url shortener</a></h1><br>
<span id="error">$pretty</span>
</center>
</body>
</html>
END;
}

// Handle direct visits to this file without an alias passed
if ( (!isset($_GET['x'])) || (empty($_GET['x'])) ) {

	// Redirect to "/"
	header('Location: /', TRUE, 302);

// Start the search for the URL if an alias was given
} else {

	// Just show an error immediately for forced 401s, 403s, 404s, or 410s
	switch ($_GET['x']) {
		case 401: showError('401 not authorized'); exit; break;
		case 403: showError('403 forbidden'); exit; break;
		case 404: showError('404 not found'); exit; break;
		case 410: showError('410 gone'); exit; break;
		default: break;
	}

	// Require configuration; do not need functions.php here
	require('admin/config.php');

	// Connect to MySQL and choose database
	try {
		$link = new PDO("mysql:host=$sqlhost;dbname=$sqldb", $sqluser, $sqlpass);
	} catch (PDOException $e) {
		die ('Cannot connect to DB!');
	}

	// Check if the alias exists
	$check = $link->prepare("SELECT id, url, status FROM `urls` WHERE `alias` = ?");
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
				$addvisit = $link->prepare("INSERT INTO `visits` VALUES (?, ?, ?, ?, ?)");
				$addvisit->bindValue(1, $id, PDO::PARAM_INT);
				$addvisit->bindValue(2, $ip, PDO::PARAM_STR);
				$addvisit->bindValue(3, $browser, PDO::PARAM_STR);
				$addvisit->bindValue(4, $referrer, PDO::PARAM_STR);
				$addvisit->bindValue(5, $time, PDO::PARAM_INT);
				$addvisit->execute();
			break;

			// Disabled
			case 0: showError('410 gone'); break;

			// Neither active nor disabled (weird status like "-1" for hidden or something)
			default: showError('404 not found'); break;

		} // End status switch

	// Show 404 not found error if the alias was not found
	} else {
		showError('404 not found');
	}

	// Disconnect from MySQL
	$link = null;

} // End alias existence check

// Redirect the user to the long URL if it was an active alias
if (isset($redirect)) {
	header("Location: $redirect", TRUE, 301);
}

?>
