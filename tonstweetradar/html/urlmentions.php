<?php

require_once('config.inc.php');

if (isset($_REQUEST['period'])):
   $period = $_REQUEST['period'];   
else:
   $period = "day";
endif;   
if (isset($_REQUEST['howmany'])):
   $howmany = $_REQUEST['howmany'];   
else:
   $howmany = "50";
endif;   
if (isset($_REQUEST['keyword'])):
   $keyword = $_REQUEST['keyword'];   
else:
   $keyword = "";
endif;   
if (isset($_REQUEST['urltype'])):
   $urltype = $_REQUEST['urltype'];   
else:
   $urltype = "";
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

#-- Get the earliest tweet_id in that period, to make the other queries simpler
$res = mysql_query("select tweet_id from tweets where created_at>='$from' order by created_at limit 1");
$earliest_tweet_id = mysql_result($res,0,0);

$xtables = "tweet_urls2,tweets";
$xwhere = "tweets.created_at>='$from' and tweet_urls2.tweet_id=tweets.tweet_id";
$res = mysql_query("select count(*) from $xtables where $xwhere");
$num_tweets = mysql_result($res,0,0);
if ((integer)$howmany > 0):
   $xlimit = " limit $howmany";
else:
   $xlimit = "";
endif;   
if ($keyword!=""):
   $xtables .= ",tweet_keywords";
   $xwhere .= " and tweet_keywords.tweet_id=tweets.tweet_id and tweet_keywords.keyword='$keyword'";
endif;    
if ($urltype!=""):
   $xwhere .= " and tweet_urls2.url_type='$urltype'";
endif;        
$res = mysql_query("select real_url,short_url,url_type,count(*) as ucount from $xtables where $xwhere group by tweet_urls2.real_url order by ucount desc{$xlimit}");
if ($res<=0):
  echo "ERROR: ".mysql_error()."\n";
  exit;
endif;

?>
<!DOCTYPE html>
<html>
<head>
<title>Ton's Radar</title>
</head>
<body>
  
<h1>URL mentions</h1>  
  
<form action="urlmentions.php" method="GET">  

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

Type <select name="urltype">
<option value="">* all *</option>   
<?php
$xarr = array("document file","tweet","embedded presentation","embedded document","photo","video","image","general link","domain name","generic link");
for ($x=0;$x<count($xarr);$x++) {
   $xval = $xarr[$x];
   $xselect = ($xval==$urltype) ? "selected" : "";
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

Show <select name="howmany">
<?php
$xarr = array("5","10","25","50","100","*all*");
for ($x=0;$x<count($xarr);$x++) {
   $xval = $xarr[$x];
   $xselect = ($xval==$howmany) ? "selected" : "";
   echo "<option $xselect>$xval</option>";
}
?>    
</select>  

<input type="checkbox" name="include_tweets" value="1" <?php echo ($include_tweets) ? "checked" : ""; ?>>include tweets
<input type="checkbox" name="include_retweets" value="1" <?php echo ($include_retweets) ? "checked" : ""; ?>>&amp;  retweets

<input type="submit" value="update!">

</p>

<?php if ($from!=''): ?>
<p>Tweets from <?php echo $from; ?> to <?php echo $last_date; ?> (<?php echo $num_tweets;?> records)</p>
<?php endif; ?>

</form>
  
<ul>  
<?php
while ($row=mysql_fetch_array($res)):
  $real_url = $row['real_url'];
  $short_url = $row['short_url'];
  $url_type = $row['url_type'];
  $ucount = $row['ucount'];
  echo "<li>$ucount : <a href=\"$real_url\" target=\"_blank\">$real_url</a> ($url_type)";
  if ($include_tweets):
     $ywhere = "real_url='$real_url' and tweet_id>=$earliest_tweet_id";
     if (!$include_retweets):
        $zwhere = " and tweets.is_rt=0";
     else:
        $zwhere = "";     
     endif;
     
     $res2 = mysql_query("select distinct(tweet_id) as xtweet_id from tweet_urls2 where $ywhere");
     $xtweet_ids = array();
     while ($row2=mysql_fetch_array($res2)):
        $xtweet_ids[] = $row2['xtweet_id'];
     endwhile;     
     $xlist = join(",",$xtweet_ids);

     $res2 = mysql_query("select tweets.* from tweets where tweet_id in ($xlist) order by tweets.tweet_id desc");
          
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
  echo "</li>\n";
endwhile;
?>
</ul>


<?php include("menu.inc.php"); ?> 

</body>
</html>