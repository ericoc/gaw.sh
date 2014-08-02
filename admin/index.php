<?php

// Require configuration
require('config.php');

// Create function to display the status of a URL and link to change its status
function displayStatus ($id, $status) {

	switch ($status) {
		case 0:
			$color = 'red';
			$name = 'Disabled';
			$watdo = 'enable';
		break;
		case 1:
			$color = 'green';
			$name = 'Active';
			$watdo = 'disable';
		break;
		default:
			$color = 'orange';
			$name = 'Hidden';
			$watdo = 'enable';
	}

	$out = "<a onclick=\"return confirm('Are you sure?');\" href=\"/admin/?do=$watdo&id=$id\"><font color=\"$color\">$name</font></a>";

	return $out;
}

// Create function to create a search query for a URL
function searchURL ($field, $how, $value) {

	global $link;
	global $sortby;
	global $sorthow;
	global $limit;

	$query = 'SELECT * FROM `';
	if ($field == 'visits') {
		$query .= 'searchurlsvisits';
	} else {
		$query .= 'urls';
	}

	$query .= "` WHERE `$field` ";

	switch ($how) {
		case 'equal':
			$query .= "= :value";
		break;
		case 'notequal':
			$query .= "!= :value";
		break;
		case 'contains':
			$value = '%' . $value . '%';
			$query .= "LIKE :value";
		break;
		case 'greater':
			$query .= "> :value";
		break;
		case 'less':
			$query .= "< :value";
		break;
	}

	$query .= " ORDER BY `$sortby` $sorthow LIMIT $limit";

	$st = $link->prepare($query);
	$st->bindValue(':value', $value);
	$st->execute();

	return $st;
}

// Create function to display the number of visits to a URL
function howmanyVisits ($id) {
	global $link;
	$howmanyvisits = $link->prepare("SELECT `time` FROM `visits` WHERE `id` = '$id'");
	$howmanyvisits->execute();
	return $howmanyvisits->rowCount();
}

// Connect to MySQL and choose database
try {
	$link = new PDO("mysql:host=$sqlhost;dbname=$sqldb", $sqluser, $sqlpass);
} catch (PDOException $e) {
	die ('Cannot connect to DB!');
}

// Determine limit of URLs to list and set cookie
if ( (isset($_POST['limit'])) && (is_numeric($_POST['limit'])) ) {
	$limit = $_POST['limit'];
	setcookie('limit', $limit);

} elseif ( (isset($_COOKIE['limit'])) && (is_numeric($_COOKIE['limit'])) ) {
	$limit = $_COOKIE['limit'];

} elseif ( (isset($_GET['limit'])) && (is_numeric($_GET['limit'])) ) {
	$limit = $_GET['limit'];

// Default limit of URLs returned
} else {
	$limit = 20;
}

// Default sort methods
$sortby = 'id';
$sorthow = 'desc';

// Get sort methods if reasonable values were passed via URL
if ( (isset($_GET['sortby'])) && (isset($_GET['sorthow'])) ) {

	if ( ($_GET['sortby'] == 'id') || ($_GET['sortby'] == 'alias') || ($_GET['sortby'] == 'url') || ($_GET['sortby'] == 'ip') || ($_GET['sortby'] == 'time') || ($_GET['sortby'] == 'visits') ) {

		if ( ($_GET['sorthow'] == 'asc') || ($_GET['sorthow'] == 'desc') ) {

			$sortby = $_GET['sortby'];
			$sorthow = $_GET['sorthow'];

			// Set cookie for sort methods
			setcookie('sortby', $sortby);
			setcookie('sorthow', $sorthow);
		}
	}

// Use sort method from cookie if available
} elseif ( (isset($_COOKIE['sortby'])) && (isset($_COOKIE['sorthow'])) ) {

	if ( ($_COOKIE['sortby'] == 'id') || ($_COOKIE['sortby'] == 'alias') ||  ($_COOKIE['sortby'] == 'url') || ($_COOKIE['sortby'] == 'ip') || ($_COOKIE['sortby'] == 'time') || ($_COOKIE['sortby'] == 'visits') ) {

		if ( ($_COOKIE['sorthow'] == 'asc') || ($_COOKIE['sorthow'] == 'desc') ) {

			$sortby = $_COOKIE['sortby'];
			$sorthow = $_COOKIE['sorthow'];
		}
	}
}

/* Process form submission */
if (isset($_GET['do'])) {

	/* Search */
	if ( ($_GET['do'] == 'search') && (isset($_POST['value'])) && (!empty($_POST['value'])) ) {

		// Set cookies to save search query
		setcookie('searchfield', $_POST['field']);
		setcookie('searchhow', $_POST['how']);
		setcookie('searchvalue', $_POST['value']);

		$listurls = searchURL($_POST['field'], $_POST['how'], $_POST['value']);

	/* Edit */
	} elseif ( ($_GET['do'] == 'edit') && (isset($_POST['editid'])) && (is_numeric($_POST['editid'])) ) {

		$editquery = $link->prepare("UPDATE `urls` SET `alias` = :alias, `url` = :url, `ip` = :ip, `status` = :status WHERE `id` = :id");
		$editquery->bindValue(':alias', $_POST['editalias'], PDO::PARAM_STR);
		$editquery->bindValue(':url', $_POST['editurl'], PDO::PARAM_STR);
		$editquery->bindValue(':ip', $_POST['editip'], PDO::PARAM_STR);
		$editquery->bindValue(':status', $_POST['editstatus'], PDO::PARAM_INT);
		$editquery->bindValue(':id', $_POST['editid'], PDO::PARAM_INT);
		$editquery->execute();

	/* Enable */
	} elseif ( ($_GET['do'] == 'enable') && (isset($_GET['id'])) && (is_numeric($_GET['id'])) ) {

		$enable = $link->prepare("UPDATE `urls` SET `status` = '1' WHERE `id` = :id");
		$enable->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
		$enable->execute();

	/* Disable */
	} elseif ( ($_GET['do'] == 'disable') && (isset($_GET['id'])) && (is_numeric($_GET['id'])) ) {

		$disable = $link->prepare("UPDATE `urls` SET `status` = '0' WHERE `id` = :id");
		$disable->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
		$disable->execute();

	/* Disable IP */
	} elseif ( ($_GET['do'] == 'disableip') && (isset($_GET['ip'])) ) {

		$disableip = $link->prepare("UPDATE `urls` SET `status` = '0' WHERE `ip` = :ip");
		$disableip->bindValue(':ip', $_GET['ip'], PDO::PARAM_STR);
		$disableip->execute();
	}
}

// Search if cookie present
if ( (isset($_COOKIE['searchfield'])) && (isset($_COOKIE['searchhow'])) && (isset($_COOKIE['searchvalue'])) && (!empty($_COOKIE['searchvalue'])) && (!isset($_POST['value'])) ) {

	$listurls = searchURL($_COOKIE['searchfield'], $_COOKIE['searchhow'], $_COOKIE['searchvalue']);
}

// Default query if we are not searching, and clear cookies
if (!isset($listurls)) {

	if ($sortby == 'visits') {
		$listurls = $link->prepare("SELECT `urls`.*, count(`visits`.`id`) AS `visitors` FROM `urls` INNER JOIN `visits` ON `visits`.`id` = `urls`.`id` GROUP BY `visits`.`id` ORDER BY `visitors` $sorthow LIMIT $limit");
	} else {
		$listurls = $link->prepare("SELECT * FROM `urls` ORDER BY `$sortby` $sorthow LIMIT $limit");
	}

	$listurls->execute();

	setcookie('searchfield', '', time()-100);
	setcookie('searchhow', '', time()-100);
	setcookie('searchvalue', '', time()-100);
}

// Count number of URLs being listed
$counturls = $listurls->rowCount();

/* HTML header */
?>
<!DOCTYPE html>
<html>
<head>
<title>gaw.sh URL short... admin</title>
<link rel="stylesheet" type="text/css" href="/gawsh.css">
</head>
<body>
<h1><a href="/admin/">gaw.sh admin</a></h1><br>
<?php

// Say so if there are no results
if ($counturls == '0') {
	echo "<i>No results</i><br><br>\n";

} else {

	/* List URLs in a table */
	echo "<table align=\"center\">\n";
	echo "<tr>\n";
	echo "<th><a href=\"?sortby=id&sorthow=asc\">&uarr;</a> ID <a href=\"?sortby=id&sorthow=desc\">&darr;</a></th>\n";
	echo "<th><a href=\"?sortby=alias&sorthow=asc\">&uarr;</a> Alias <a href=\"?sortby=alias&sorthow=desc\">&darr;</a></th>\n";
	echo "<th><a href=\"?sortby=url&sorthow=asc\">&uarr;</a> URL <a href=\"?sortby=url&sorthow=desc\">&darr;</a></th>\n";
	echo "<th><a href=\"?sortby=ip&sorthow=asc\">&uarr;</a> IP <a href=\"?sortby=ip&sorthow=desc\">&darr;</a></th>\n";
	echo "<th><a href=\"?sortby=time&sorthow=asc\">&uarr;</a> When <a href=\"?sortby=time&sorthow=desc\">&darr;</a></th>\n";
	echo "<th>Status</th>\n";
	echo "<th><a href=\"?sortby=visits&sorthow=asc\">&uarr;</a> Visits <a href=\"?sortby=visits&sorthow=desc\">&darr;</a></th>\n";
	echo "</tr>\n";

	// Count total visits based on results
	$totalvisits = 0;

	while ($row = $listurls->fetch(PDO::FETCH_ASSOC)) {

		$visits = howmanyVisits($row['id']);
		$totalvisits = $totalvisits + $visits;

		echo "<tr>\n";
		echo "<td align=\"center\"><a href=\"?do=edit&id=" . $row['id'] . "#edit\">" . $row['id'] . "</a></td>\n";
		echo "<td align=\"center\"><a href=\"/" . $row['alias'] . "\" target=\"_blank\">" . $row['alias'] . "</a></td>\n";

		// Only show the first 50 characters of the original URL if it is longer than 50 characters
		echo '<td><a href="' . $row['url'] . '" target="_blank">';
		if (strlen($row['url']) > 50) {
			echo substr($row['url'], 0, 50) . '...';
		} else {
			echo $row['url'];
		}
		echo "</a></td>\n";

		echo "<td align=\"center\"><a onclick=\"return confirm('Are you REALLY sure?');\" href=\"/admin/?do=disableip&ip=" . $row['ip'] . "\">" . $row['ip'] . "</a></td>\n";
		echo "<td align=\"center\">" . $row['time'] . "</td>\n";
		echo "<td align=\"center\">" . displayStatus($row['id'], $row['status']) . "</td>\n";
		echo "<td align=\"center\">" . $visits . "</td>\n";
		echo "</tr>\n";
	}

	// Show total results and visits at the bottom of the table
	echo "<tr>\n";
	echo "<th colspan=\"3\">Results: $counturls</td>\n";
	echo "<th colspan=\"4\">Visits: $totalvisits</td>\n";
	echo "</tr>\n";
	echo "</table><br><hr><br>\n";
}

/* Edit form */
if ( (isset($_GET['do'])) && ($_GET['do'] == 'edit') && (isset($_GET['id'])) && (is_numeric($_GET['id'])) ) {

	$editid = $_GET['id'];
	$editform = $link->prepare("SELECT * FROM `urls` WHERE `id` = '$editid'");
	$editform->execute();

	if ($editform->rowCount() != 0) {

		echo "<form method=\"post\" action=\"/admin/?do=edit&id=" . $editid . "#edit\">\n";
		echo "<table align=\"center\">\n";
		echo "<tr>\n";
		echo "<th colspan=\"2\"><a name=\"edit\">Edit URL</a></th>\n";
		echo "</tr>\n";

		while ($editrow = $editform->fetch(PDO::FETCH_ASSOC)) {

			$editvisits = howmanyVisits($editrow['id']);

			echo "<tr>\n";
			echo "<th>ID</th>\n";
			echo "<td><input type=\"text\" name=\"editid\" size=\"5\" style=\"background-color: #ccc\" value=\"" . $editrow['id'] . "\" readonly></td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "<th>Alias</th>\n";
			echo "<td><input type=\"text\" name=\"editalias\" value=\"" . $editrow['alias'] . "\"></td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "<th>URL</th>\n";
			echo "<td><input type=\"text\" name=\"editurl\" size=\"" . strlen($editrow['url']) . "\" value=\"" . $editrow['url'] . "\"></td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "<th>IP</th>\n";
			echo "<td><input type=\"text\" name=\"editip\" value=\"" . $editrow['ip'] . "\"></td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "<th>When</th>\n";
			echo "<td>" . $editrow['time'] . "</td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "<th>Status</th>\n";
			echo "<td>\n";
			echo "<select name=\"editstatus\">\n";
			echo "<option value=\"-1\" "; if ($editrow['status'] == '-1') { echo 'selected'; } echo ">Hidden</option>\n";
			echo "<option value=\"1\" "; if ($editrow['status'] == 1) { echo 'selected'; } echo ">Active</option>\n";
			echo "<option value=\"0\" "; if ($editrow['status'] == 0) { echo 'selected'; } echo ">Disabled</option>\n";
			echo "</select>\n";
			echo "</td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "<th>Visits</th>\n";
			echo "<td>" . $editvisits . "</td>\n";
			echo "</tr>\n";
		}

		echo "</table>\n";
		echo "<input type=\"submit\" value=\"edit!\">\n";
		echo "<input type=\"reset\" value=\"nevermind\">\n";
		echo "</form>\n";
		echo "<hr><br>\n";
	}
}

// Close MySQL connection
$link = null;

/* Search form */
?>
<form method="post" action="/admin/?do=search">
Search for
<select name="field">
<option value="id">id</option>
<option value="alias">alias</option>
<option value="url">url</option>
<option value="ip">ip</option>
<option value="status">status</option>
<option value="visits">visits</option>
</select>
that
<select name="how">
<option value="equal">is equal to</option>
<option value="notequal">is not equal to</option>
<option value="contains">contains</option>
<option value="greater">is greater than</option>
<option value="less">is less than</option>
</select>:
<input type="text" name="value">
and limit to <input type="text" name="limit" value="<?php echo $limit; ?>" size="3" maxlength="5"> results.<br>
<input type="submit" value="search!">
<input type="reset" value="nevermind">
</form>
</body>
</html>
