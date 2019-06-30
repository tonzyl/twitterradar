<?php
/**
* 140dev_config.php
* Constants for the entire 140dev Twitter framework
* You MUST modify these to match your server setup when installing the framework
* 
* Latest copy of this code: http://140dev.com/free-twitter-api-source-code-library/
* @author Adam Green <140dev@gmail.com>
* @license GNU Public License
* @version BETA 0.20
*/

// Directory for db_config.php
define('DB_CONFIG_DIR', '/public_html/radar/140dev/db/');

// Server path for scripts within the framework to reference each other
define('CODE_DIR', '/public_html/radar/140dev/');

// External URL for Javascript code in browsers to call the framework with Ajax
define('AJAX_URL', 'http://tonzijlstra.ch/radar/140dev/');

// OAuth settings for connecting to the Twitter streaming API
// Fill in the values for a valid Twitter app
define('TWITTER_CONSUMER_KEY','your key');
define('TWITTER_CONSUMER_SECRET','your secret');
define('OAUTH_TOKEN','token');
define('OAUTH_SECRET','secret');

// MySQL time zone setting to normalize dates
define('TIME_ZONE','America/New_York');

// Settings for monitor_tweets.php
define('TWEET_ERROR_INTERVAL',10);
// Fill in the email address for error messages
define('TWEET_ERROR_ADDRESS','yourmailaddress');
?>