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
<center>
<h1><a href="/">gaw.sh url shortener</a></h1><br>
<?php

/* Process form if it was submitted */
if ( (isset($_POST['url'])) && (!empty($_POST['url'])) && ($_POST['url'] != 'http://') ) {

	// Require configuration/settings
	require('admin/config.php'); // MySQL credentials and user variables

	// Trim submitted URL, throw "http://" on the front if it does not start with either http:// or https://
	$url = trim($_POST['url']);
	if (!preg_match('/^http(s)?:\/\//i', $url)) {
		$url = 'http://' . $url;
	}

	// Trim the alias, if one was submitted
	if ( (isset($_POST['alias'])) && (!empty($_POST['alias'])) ) {
		$alias = trim(strtolower($_POST['alias']));

	// Set an empty alias if none was submitted
	} else {
		$alias = '';
	}

	// Check if submitted alias has sane characters
	if ( (!empty($alias)) && (!preg_match('/^[a-z0-9]+$/i', $alias)) ) {
		$error = 'Invalid alias';

	// Require functions with blacklist/URL checks and run the URL through said checks
	} else {
		require('functions.php');
		$error = checkURL($url);
	}

	// Move on with possibly adding URL if there are no errors
	if ( (!isset($error)) || (empty($error)) )  {

		// Connect to MySQL and choose database
		try {
			$link = new PDO("mysql:host=$sqlhost;dbname=$sqldb", $sqluser, $sqlpass);

		// Show clean 503 service unavailable error if the database is unavailable
		} catch (PDOException $e) {
			header('Location: /503', TRUE, 302);
		}

		// Check if the alias has been used already
		if ( (isset($alias)) && (!empty($alias)) ) {

			$checkalias = $link->prepare("SELECT `id` FROM `urls` WHERE `alias` = ?");
			$checkalias->bindValue(1, $alias, PDO::PARAM_STR);
			$checkalias->execute();

			// Error out if alias has been used before
			if ($checkalias->rowCount() >= 1) {
				$error = 'Alias taken';
			}
		}

		// Add the URL to the database if there are still not any errors
		$addurl = $link->prepare("INSERT INTO `urls` VALUES ('0', ?, ?, '$ip', '$time', '1')");
		$addurl->bindValue(1, $alias, PDO::PARAM_STR);
		$addurl->bindValue(2, $url, PDO::PARAM_STR);

		if ( (!isset($error)) && ($addurl->execute()) ) {

			// Determine the short URL that we are going to display
			if ( (isset($alias)) && (!empty($alias)) ) {
				$shorturl = $alias;

			// Generate an alias if none was given
			} else {

				// Get the database ID of the newly created URL
				$id = $link->lastInsertId();

				// Check if the alias we are generating has been used before (in case someone manually created this alias in the past)
				$aliasexists = 'TRUE';
				$a = 0;
				while ($aliasexists == 'TRUE') {

					// Create a small alias simply using the database ID if it is the first time going through the loop
					if ($a == '0') {
						$shorturl = base_convert($id, 10, 36);

					// Prepend a random number, then append the UNIX timestamp to the database ID, and convert that to base36, if we are going through the loop more than once
					} else {
						$shorturl = base_convert(rand(0,10).$id.time(), 10, 36);
					}

					// See if this alias exists in the database any where; if not, create it. Otherwise, re-loop
					$doublecheckalias = $link->prepare("SELECT `id` FROM `urls` WHERE `alias` = '$shorturl'");
					$doublecheckalias->execute();

					if ($doublecheckalias->rowCount() == '0') {
						$aliasexists = FALSE;
						$link->query("UPDATE `urls` SET `alias` = '$shorturl' WHERE `id` = '$id'");
					} else {
						$a++;
					}
				}
			}
		}

		// Close MySQL connection
		$link = null;
	}

	// Show the results
	if (isset($shorturl)) {
		echo '<a id="url" href="/' . $shorturl . '">http://gaw.sh/' . $shorturl . '</a>';

	// Otherwise, show any errors
	} elseif (isset($error)) {
		echo '<span id="error">' . $error . '</span>';
	}

	echo "<br><br>\n";

} /* Done processing form */

?>
<form method="post" action="">
<input type="text" size="50" name="url" value="http://"><br>
Alias (optional): <input type="text" size="20" name="alias"><br><br>
<input type="submit" value="shorten!">
<input type="reset" value="nevermind">
</form>
</center>
</body>
</html>
