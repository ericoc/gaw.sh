# Things that need fixing/betterment

 * Fix blacklist checks and functions.php over-all to understand and properly handle URLs that are purely IP (v4 or v6) addresses
  * IPv6 address URLs depend on this PHP bug being closed: https://bugs.php.net/bug.php?id=54629

 * Expand the showInfo() function within "go.php" to offer additional public statistics on short URLs

 * Prevent the isRude() IP address rate limiting function from performing so many MySQL queries
  * Thinking of either using Redis or memcached or something sweet like that to store recent IP addresses that have added URLs with a timestamp and/or expire time maybe
