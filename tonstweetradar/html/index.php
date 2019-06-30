<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

require_once('config.inc.php');

#-- Counts
$res = mysql_query("select count(*) from tweets");
$num_tweets = (integer) mysql_result($res,0,0);
$res = mysql_query("select count(*) from tweet_urls");
$num_urls = (integer) mysql_result($res,0,0);
$res = mysql_query("select count(*) from tweet_urls2");
$num_urls2 = (integer) mysql_result($res,0,0);
$res = mysql_query("select count(*) from users");
$num_users = (integer) mysql_result($res,0,0);
$res = mysql_query("select count(*) from tweet_keywords");
$num_keywords = (integer) mysql_result($res,0,0);

#-- Most recent
$res = mysql_query("select * from tweets order by tweet_id desc limit 1");
$last_tweet = mysql_fetch_array($res);

$res = mysql_query("select * from tweet_urls order by tweet_id desc limit 1");
$last_url = mysql_fetch_array($res);
$url_tweet_id = $last_url['tweet_id'];
$res = mysql_query("select * from tweets where tweet_id='$url_tweet_id' limit 1");
$url_tweet = mysql_fetch_array($res);

$res = mysql_query("select * from tweet_urls2 order by tweet_id desc limit 1");
$last_url2 = mysql_fetch_array($res);
$url2_tweet_id = $last_url2['tweet_id'];
$res = mysql_query("select * from tweets where tweet_id='$url2_tweet_id' limit 1");
$url2_tweet = mysql_fetch_array($res);

$res = mysql_query("select * from users order by user_id desc limit 1");
$last_user = mysql_fetch_array($res);

$res = mysql_query("select * from tweet_keywords order by tweet_id desc limit 1");
$last_keyword = mysql_fetch_array($res);
$keyword_tweet_id = $last_keyword['tweet_id'];
$res = mysql_query("select * from tweets where tweet_id='$keyword_tweet_id' limit 1");
$keyword_tweet = mysql_fetch_array($res);

?>
<!DOCTYPE html>
<html>
<head>
<title>Ton's Radar</title>
</head>
<body>
  
<h1>Ton's Radar</h1>  
  
<ul>
<li><?php echo $num_tweets; ?> tweets. Most recent: <a href="https://twitter.com/<?php echo $last_tweet['screen_name'] ?>/status/<?php echo $last_tweet['tweet_id'] ?>" target="_blank"><?php echo $last_tweet['created_at'] ?></a>: <?php echo trim($last_tweet['tweet_text']); ?> </li>
<li><?php echo $num_urls; ?> raw urls. Most recent: <?php echo $url_tweet['created_at']; ?>: <a href="<?php echo $last_url['url']; ?>" target="_blank"><?php echo $last_url['url']; ?></a></li>
<li><?php echo $num_urls2; ?> processed urls. Most recent: <?php echo $url2_tweet['created_at']; ?>: <a href="<?php echo $last_url2['real_url']; ?>" target="_blank"><?php echo $last_url2['real_url']; ?></a> (<?php echo $last_url2['url_type']; ?>)</li>
<li><?php echo $num_keywords; ?> keyword assignments. Most recent: <?php echo $keyword_tweet['created_at']; ?>: <?php echo $last_keyword['keyword']; ?></li>
<li><?php echo $num_users; ?> users. Most recent: <a href="https://twitter.com/<?php echo $last_user['screen_name']; ?>" target="_blank"><?php echo $last_user['screen_name']; ?>: <?php echo $last_user['name']; ?></a></li>
</ul>

<h3>Breakdowns</h3>

<ul>
<li><a href="urlmentions.php">Ranked URL mentions</a></li>  
<li><a href="keyworduse.php">Keyword Use</a></li>  
</ul>  

<h3>Tweets Received</h3>

<ul>
<?php
$res = mysql_query("select date_add(now(),interval -15 day)");
$recent = mysql_result($res,0,0);
$res = mysql_query("select left(created_at,10) as day,count(*) as xcount from tweets where created_at>='$recent' group by day");
while($row=mysql_fetch_array($res)):
   $day = $row['day'];
   $xcount = $row['xcount'];
   echo "<li>$day: $xcount</li>\n";
endwhile;   
?>
</ul>

</body>
</html>