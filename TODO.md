# Things that need fixing/betterment

Fix blacklist lookup functions to understand multiple A (and AAAA) records/CNAMEs
 * Also not 100% sure how a URL that is simply an IP address would be handled as the blacklist functions are designed for use primarily with domain names

Check all URLs in the database against the blacklists every so often (once/day is probably good?) in case they have been flagged after having been added
 * [/admin/blcheck.php](https://github.com/ericoc/gawsh/blob/master/admin/blcheck.php) is in progress, but needs to execute more quickly

More to come at some point...
