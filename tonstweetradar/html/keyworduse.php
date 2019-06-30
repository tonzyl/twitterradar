<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

require_once('config.inc.php');

if (isset($_REQUEST['keyword'])):
   $keyword = $_REQUEST['keyword'];   
else:
   $keyword = "";
endif;   
if (isset($_REQUEST['period'])):
   $period = $_REQUEST['period'];   
else:
   $period = "day";
endif;   
if (isset($_REQUEST['include_tweets'])):
   $include_tweets = true;   
else:
   $include_tweets = false;
endif;   
if (isset($_REQUEST['include_retweets'])):
   $include_retweets = true;   
else:
   $include_retweets = false;
endif;
if (isset($_REQUEST['include_urls'])):
   $include_urls = true;   
else:
   $include_urls = false;
endif;   
if (isset($_REQUEST['include_users'])):
   $include_users = true;   
else:
   $include_users = false;
endif;   

#-- Figure out the datetime of the last unshortened URL, so that we can measure 24 hours before that. Just in case the unshortening is not up-to-date
$res = mysql_query("select * from tweet_urls2 order by tweet_id desc limit 1");
$row = mysql_fetch_array($res);
$tweet_id = $row['tweet_id'];
if ($period=="week"):
   $daynum = 7;
else:
   $daynum = 1;
endif;      
$res = mysql_query("select created_at,date_add(created_at,interval -$daynum day) as from_date from tweets where tweet_id=$tweet_id");
$row = mysql_fetch_array($res);
$from = $row['from_date'];
$last_date = $row['created_at'];

if ($period=='week'):
   $recently = $from;
else:   
   #$res = mysql_query("select date_add(now(),interval -2 day) as week_ago");
   #$recently = mysql_result($res,0,0);
   $recently = $from;
endif;

if ($keyword==''):
   $keywordlist = $keywords;
else:   
   $keywordlist = [$keyword];
endif;   


?>
<!DOCTYPE html>
<html>
<head>
<title>Ton's Radar</title>
</head>
<body>
  
<h1>Keyword Use</h1>  
  
<form action="keyworduse.php" method="GET">  

<p>

Keyword <select name="keyword">
<option value="">* all *</option>   
<?php
for ($x=0;$x<count($keywords);$x++) {
   $xval = $keywords[$x];
   $xselect = ($xval==$keyword) ? "selected" : "";
   echo "<option $xselect>$xval</option>";
}
?>   
</select>  
      
Past <select name="period">
<?php
$xarr = array("day","week");
for ($x=0;$x<count($xarr);$x++) {
   $xval = $xarr[$x];
   $xselect = ($xval==$period) ? "selected" : "";
   echo "<option $xselect>$xval</option>";
}
?>   
</select>

<input type="checkbox" name="include_tweets" value="1" <?php echo ($include_tweets) ? "checked" : ""; ?>>include tweets
<input type="checkbox" name="include_retweets" value="1" <?php echo ($include_retweets) ? "checked" : ""; ?>>&amp;  retweets
<input type="checkbox" name="include_urls" value="1" <?php echo ($include_urls) ? "checked" : ""; ?>>include urls
<input type="checkbox" name="include_users" value="1" <?php echo ($include_users) ? "checked" : ""; ?>>include users

<input type="submit" value="update!">

</p>

</form>

<?php
for ($x=0;$x<count($keywordlist);$x++) {
   $keyword = $keywordlist[$x];

   echo "<h3>$keyword</h3>\n";
   
   echo "<p>";
   
   $xtables = "tweet_keywords,tweets";
   $xwhere = "tweets.created_at>='$from' and tweet_keywords.tweet_id=tweets.tweet_id and tweet_keywords.keyword='$keyword'";
   $res = mysql_query("select count(*) from $xtables where $xwhere");
   $num_tweets = mysql_result($res,0,0);
   echo "$num_tweets tweets<br>\n";

   if ($include_tweets):
      $zwhere = "tweets.created_at>='$from' and tweet_keywords.tweet_id=tweets.tweet_id and tweet_keywords.keyword='$keyword'";
      if (!$include_retweets):
         $zwhere .= " and tweets.is_rt=0";
      endif;
      $res2 = mysql_query("select tweets.* from tweets,tweet_keywords where $zwhere order by tweets.tweet_id desc");
      if ($res2<=0):
         echo "error: ".mysql_error()."<br>\n";
      endif;   
      echo "<ul>";
      while ($row2=mysql_fetch_array($res2)):
          $tweet_id = $row2['tweet_id'];
          $tweet_text = trim($row2['tweet_text']);
          $screen_name = $row2['screen_name'];
          $name = $row2['name'];
          $created_at = $row2['created_at'];
          $is_rt = (integer)$row2['is_rt'];
          if ($include_retweets || $is_rt==0):
             echo "<li><a href=\"https://twitter.com/$screen_name\" target=\"_blank\">$screen_name</a>: <a href=\"https://twitter.com/$screen_name/status/$tweet_id\" target=\"_blank\">$tweet_text</a></li>";
          endif;
      endwhile;
      echo "</ul>";
   endif;     
   
   $xtables = "tweet_urls2,tweet_keywords,tweets";
   $xwhere = "tweets.created_at>='$from' and tweet_keywords.tweet_id=tweets.tweet_id and tweet_keywords.keyword='$keyword' and tweet_urls2.tweet_id=tweets.tweet_id";
   $res = mysql_query("select count(distinct(tweet_urls2.real_url)) from $xtables where $xwhere");
   $num_urls = mysql_result($res,0,0);
   echo "$num_urls urls<br>\n";
   
   if ($include_urls):
      $zwhere = "tweets.created_at>='$from' and tweet_keywords.tweet_id=tweets.tweet_id and tweet_keywords.keyword='$keyword' and tweet_urls2.tweet_id=tweets.tweet_id";
      $res2 = mysql_query("select tweet_urls2.real_url,tweet_urls2.url_type,count(*) as ucount from tweet_urls2,tweets,tweet_keywords where $zwhere group by tweet_urls2.real_url order by ucount desc");
      if ($res2<=0):
         echo "error: ".mysql_error()."<br>\n";
      endif;   
      echo "<ul>";
      while ($row2=mysql_fetch_array($res2)):
         $real_url = $row2['real_url'];
         $url_type = $row2['url_type'];
         $ucount = $row2['ucount'];
         echo "<li><a href=\"$real_url\" target=\"_blank\">$real_url</a>  ($url_type) ($ucount)</li>";
      endwhile;
      echo "</ul>";
   endif;        

   $xtables = "users,tweet_keywords,tweets";
   $xwhere = "tweets.created_at>='$from' and tweet_keywords.tweet_id=tweets.tweet_id and tweet_keywords.keyword='$keyword' and users.user_id=tweets.user_id";
   $res = mysql_query("select count(distinct(users.user_id)) from $xtables where $xwhere");
   $num_users = mysql_result($res,0,0);
   echo "$num_users users<br>\n";

   if ($include_users):
      $zwhere = "tweets.created_at>='$from' and tweet_keywords.tweet_id=tweets.tweet_id and tweet_keywords.keyword='$keyword' and users.user_id=tweets.user_id";
      $res2 = mysql_query("select users.*,count(*) as ucount from users,tweets,tweet_keywords where $zwhere group by users.user_id order by ucount desc");
      if ($res2<=0):
         echo "error: ".mysql_error()."<br>\n";
      endif; 
      while ($row2=mysql_fetch_array($res2)):
         $user_id = $row2['user_id'];
         $screen_name = $row2['screen_name'];
         $name = $row2['name'];
         $location = $row2['location'];
         $followers_count = $row2['followers_count'];
         $friends_count = $row2['friends_count'];
         $profile_image_url = $row2['profile_image_url'];
         $created_at = $row2['created_at'];
         $ucount = $row2['ucount'];

         $res3 = mysql_query("select min(tweets.created_at) from users,tweet_keywords,tweets where tweet_keywords.tweet_id=tweets.tweet_id and tweet_keywords.keyword='$keyword' and users.user_id=tweets.user_id and users.user_id='$user_id'");
         $first_date = mysql_result($res3,0,0);
         $new = ($first_date>=$recently) ? "<img src=\"new_icon.gif\">" : ""; 

         echo "<div style=\"clear:both\">";  
         echo "<img src=\"$profile_image_url\" style=\"clear:left;float:left;margin:0 10px 5px 0;\"> <a href=\"https://twitter.com/$screen_name\" target=\"_blank\">$screen_name</a> $name ($ucount) $new<br>$location, $friends_count/$followers_count";
         echo "</div>";
      endwhile;
      echo "<div style=\"clear:both\"></div>";
   endif;        
   
   echo "</p>\n";
}
?>


<?php include("menu.inc.php"); ?> 

</body>
</html>