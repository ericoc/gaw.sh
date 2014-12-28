<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="description" content="gaw.sh url shortener">
<meta name="keywords" content="gaw.sh, url, shortener, gawsh">
<link rel="stylesheet" type="text/css" href="/gawsh.css">
<link rel="shortcut icon" href="/favicon.ico">
<title>gaw.sh URL short...</title>
</head>
<body>
<h1><a href="/">gaw.sh url shortener</a></h1><br>
<?php

/* Process form if it was submitted */
if ( (isset($_POST['url'])) && (!empty($_POST['url'])) && ($_POST['url'] != 'http://') ) {

	// Require configuration/settings
	require('admin/config.php'); // MySQL credentials and user variables
	require('functions.php'); // Blacklist/URL functions

	// Trim submitted URL, throw "http://" on the front if it does not start with either http:// or https://
	$url = trim($_POST['url']);
	if (!preg_match('/^http(s)?:\/\//i', $url)) {
		$url = 'http://' . $url;
	}

	// Trim and lower-case the alias, if one was submitted
	if ( (isset($_POST['alias'])) && (!empty(trim($_POST['alias']))) ) {
		$alias = trim(strtolower($_POST['alias']));

		// Check if submitted alias is sane (<= 50 characters, and alpha-numeric)
		if ( (strlen($alias) > 50) || (!preg_match('/^[a-z0-9]+$/i', $alias)) ) {
			$badalias = true;
		}

	// Set an empty alias if none was submitted
	} else {
		$alias = '';
	}

	// Return error if alias was not sane
	if (isset($badalias)) {
		$error = 'Invalid alias';

	// Run blacklist/URL checks
	} else {
		$error = checkURL($url);
	}

	// Move on with possibly adding URL if there are no errors
	if ( (!isset($error)) || (empty($error)) ) {

		// Connect to MySQL and choose database
		try {
			$link = new PDO("mysql:host=$sqlhost;dbname=$sqldb", $sqluser, $sqlpass);
			$link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		// Show clean 503 service unavailable error if the database is unavailable
		} catch (PDOException $e) {
			header('Location: /503', TRUE, 302);
		}

		// Bail if the IP address is being rude and has added too many URLs recently
		if (isRude($link, $ip)) {
			$link = null;
			header('Location: /429', TRUE, 302);
		}

		// Try to add the URL to the database right now if we were given an alias that has a possibility of working
		if ( (isset($alias)) && (!empty($alias)) ) {

			try {
				$addurl = $link->prepare("INSERT INTO `urls` (`id`, `alias`, `url`, `ip`, `time`, `status`) VALUES ('0', :alias, :url, :ip, NOW(), '1')");
				$addurl->bindValue(':alias', $alias, PDO::PARAM_STR);
				$addurl->bindValue(':url', $url, PDO::PARAM_STR);
				$addurl->bindValue(':ip', $ip, PDO::PARAM_STR);
				$addurl->execute();

				$shorturl = $alias;

			// Catch any exception, most likely meaning that the alias is taken so we give up
			} catch (PDOException $addex) {
				$error = 'Alias taken!';
			}

		// Generate a unique alias if none was given to add the URL to the database
		} else {

			// Start a MySQL transaction where we insert the row for the URL first with an empty alias, so we can later update it with a generated/unique alias
			$link->beginTransaction();
			$addurl = $link->prepare("INSERT INTO `urls` (`id`, `alias`, `url`, `ip`, `time`, `status`) VALUES ('0', '', :url, :ip, NOW(), '1')");
			$addurl->bindValue(':url', $url, PDO::PARAM_STR);
			$addurl->bindValue(':ip', $ip, PDO::PARAM_STR);
			$addurl->execute();

			// Get the database ID of the URL that we are inserting
			$id = $link->lastInsertId();

			// Loop until we can update the row that we just inserted with a uniquely generated alias without an exception being thrown
			// This normally should just work on the first shot, unless someone manually created it in the past
			$aliasexists = TRUE;
			$fix = 0;

			while ($aliasexists == TRUE) {

				// Create a small alias simply using the database ID if it is the first time going through the loop
				if ($fix == 0) {
					$shorturl = base_convert($id, 10, 36);

				// Prepend a random number, then append the UNIX timestamp to the database ID, and convert that to base36, if we are going through the loop more than once
				} else {
					$shorturl = base_convert(rand(0,10).$id.time(), 10, 36);
				}

				// Try to update the URL row that we inserted with the alias that we just generated and commit our transaction
				try {
					$fixalias = $link->prepare("UPDATE `urls` SET `alias` = :alias WHERE `id` = :id");
					$fixalias->bindValue(':alias', $shorturl, PDO::PARAM_STR);
					$fixalias->bindValue(':id', $id, PDO::PARAM_INT);
					$fixalias->execute();
					$link->commit();
					$aliasexists = FALSE;

				// If we get an exception indicating that the alias is not unique, re-loop generating a new hopefully unique alias
				} catch (PDOException $fixex) {
					$fix++;
					$aliasexists = TRUE;
				}
			}
		}
	}

	// Close MySQL connection
	$link = null;

	// Show any errors
	if (isset($error)) {
		echo '<span id="error">' . $error . '</span>';

	// Otherwise, show any successful result
	} elseif (isset($shorturl)) {
		echo '<a id="url" href="/' . $shorturl . '">http://gaw.sh/' . $shorturl . '</a>';
	}

	echo "<br><br>\n";

} /* Done processing form */

?>
<form method="post">
<input type="text" size="50" name="url" value="http://"><br>
Alias (optional): <input type="text" size="20" maxlength="50" name="alias"><br><br>
<input type="submit" value="shorten!">
<input type="reset" value="nevermind">
</form>
</body>
</html>
