<?php

#-- We're probably in /data/radarthingy/140dev/extra

require_once('/home/tonroots/140dev/db/140dev_config.php');
require_once('/home/tonroots/140dev/db/db_config.php');

# probably how to access the database:
# mysql -h address -u awsuser -pwpw 140dev

mysql_connect($db_host,$db_user,$db_password);
mysql_select_db($db_name);

#-- The keywords are copied from get_tweets.php and must be updated manually. They should probably go in a common place
$keywords = array('opendata', 'fablab', 'opengov', 'resilience', '#makers','energytransition', 'p2p');

?>
