<?php

// Create function to set header and display error message on bad URLs
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

// Start the search for the URL if an alias was given
if ( (isset($_GET['x'])) && (!empty($_GET['x'])) ) {

	// Require configuration; do not need functions.php here
	require('config.php');

	// Connect to MySQL and choose database
	try {
		$link = new PDO("mysql:host=$sqlhost;dbname=$sqldb", $sqluser, $sqlpass);
	} catch (PDOException $e) {
		die ("Cannot connect to DB! - $e");
	}

	// Check if the alias exists
	$check = $link->prepare("SELECT id, url, status FROM `urls` WHERE `alias` = ?");
	$check->bindValue(1, $_GET['x'], PDO::PARAM_STR);
	$check->execute();

	// Get ID, long URL, and status if the alias exists
	if ($check->rowCount() >= 1) {

		while ($row = $check->fetch(PDO::FETCH_ASSOC)) {
			$id = $row['id'];
			$to = $row['url'];
			$status = $row['status'];
		}

		// Add an entry to the visits table
		$addvisit = $link->prepare("INSERT INTO `visits` VALUES (?, ?, ?, ?, ?)");
		$addvisit->bindValue(1, $id, PDO::PARAM_INT);
		$addvisit->bindValue(2, $ip, PDO::PARAM_STR);
		$addvisit->bindValue(3, $browser, PDO::PARAM_STR);
		$addvisit->bindValue(4, $referrer, PDO::PARAM_STR);
		$addvisit->bindValue(5, $time, PDO::PARAM_INT);
		$addvisit->execute();
	}

	// Disconnect from MySQL
	$link = null;
}

// Redirect to long URL if it was found and is active; show error message if it has been disabled, is in a weird status, or was not found
if ( (isset($to)) && ($status == '1') ) {
	header("Location: $to", TRUE, 301);

} elseif ( (isset($to)) && ($status == '0') ) {
	showError('410 gone');

} elseif ($alias == '403') {
	showError('403 forbidden');

} else {
	showError('404 not found');
}

?>
