# Things that need fixing/betterment

* Fix blacklist lookup functions to understand multiple A (and AAAA) records/CNAMEs and check all of the things
	* isZEN/isZENNS does not work if the URL is an IP address instead of a domain name since dns_get_record is quirky

* Check all URLs in the database against the blacklists every so often (once/day is probably good) in case they have been flagged after having been added

* More to come at some point...

