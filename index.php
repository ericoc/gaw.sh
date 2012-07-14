<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="/gawsh.css">
<title>gaw.sh URL short...</title>
</head>
<body>
<center>
<h1><a href="/">gaw.sh url shortener</a></h1><br>
<?php

/* Process form if it was submitted */
if ( (isset($_POST['url'])) && (!empty($_POST['url'])) && ($_POST['url'] != 'http://') ) {

	// Include configuration
	include('config.php'); // MySQL credentials, user variables, and sqlsafe() function

	// Trim submitted URL, throw "http://" on the front if it doesn't start with either http:// or https://
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

	// Include functions with blacklist/URL checks and run the URL through said checks
	} else {
		include('functions.php');
		$error = checkURL($url);
	}

	// Move on with possibly adding URL if there are no errors
	if ( (!isset($error)) || (empty($error)) )  {

		// Connect to MySQL and choose database
		$link = mysql_connect($sqlhost, $sqluser, $sqlpass) OR die('Cannot connect to DB!');
		mysql_select_db($sqldb, $link);

		// Make URL and alias safe for MySQL
		$url = sqlsafe($url);
		$alias = sqlsafe($alias);

		// Check if the alias has been used already
		if ( (isset($alias)) && (!empty($alias)) ) {

			$checkalias = mysql_query("SELECT `id` FROM `urls` WHERE `alias` = '$alias'", $link);

			// Error out if alias has been used before
			if (mysql_num_rows($checkalias) >= '1') {
				$error = 'Alias taken';
			}
		}

		// Add the URL to the database if there are still not any errors
		if ( (!isset($error)) && (mysql_query("INSERT INTO `urls` VALUES ('0', '$alias', '$url', '$ip', '$time', '1')", $link)) ) {

			// Determine the short URL that we're going to display
			if ( (isset($alias)) && (!empty($alias)) ) {
				$shorturl = $alias;

			// Generate an alias if none was given
			} else {

				// Get the database ID of the newly created URL
				$id = mysql_insert_id();

				// Check if the alias we're generating has been used before (in case someone manually created this alias in the past)
				$aliasexists = TRUE;
				$a = 0;
				while ($aliasexists == TRUE) {

					// Create a small alias simply using the database ID if it's the first time going through the loop
					if ($a == 0) {
						$shorturl = base_convert($id, 10, 36);

					// Prepend a random number, then append the UNIX timestamp to the database ID, and convert that to base36, if we're going through the loop more than once
					} else {
						$shorturl = base_convert(rand(0,10).$id.time(), 10, 36);
					}

					// See if this alias exists in the database any where; if not, create it. Otherwise, re-loop
					if (mysql_num_rows(mysql_query("SELECT `id` FROM `urls` WHERE `alias` = '$shorturl'", $link)) == 0) {
						$aliasexists = FALSE;
						mysql_query("UPDATE `urls` SET `alias` = '$shorturl' WHERE `id` = '$id'", $link); 
					} else {
						$a++;
					}
				}
			}
		}

		// Close MySQL connection
		mysql_close($link);
	}

	// Show the results
	if (isset($shorturl)) {
		echo '<a id="url" href="/' . $shorturl . '">http://gaw.sh/' . $shorturl . '</a>';

	// Show any errors
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
