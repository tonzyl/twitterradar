<?php

#-- get_keywords.php ----- got through all unprocessed tweets and figure out what keywords they were for

require_once('extra_config.php');

$debug = false;

while (true):
   #-- Keep running indefinitely, unless it is just a test

   echo "\n";

   $res = mysql_query("select count(*) from tweets");
   $num_tweets = mysql_result($res,0,0);
   echo "There are $num_tweets total tweets\n";

   $res = mysql_query("select * from counters where name='keywords' limit 1");
   $row = mysql_fetch_array($res);
   $start_id = $row['last_id'];

   if ($debug):
     echo "Just testing, we'll do the last 100 tweets and not save anything\n";
   elseif ($start_id>0):
     echo "We're starting at id $start_id\n";
     $res = mysql_query("select count(*) from tweets where tweet_id>=$start_id");
     $num_tweets = mysql_result($res,0,0);
     echo "We have $num_tweets to process\n";
   endif;  

   $processed = $missing = $found = $keyfound = $skipped = $error = 0;
   $last_id = $start_id;

   if ($debug):
    $res = mysql_query("select * from tweets order by tweet_id desc limit 100");
   else:  
    $res = mysql_query("select * from tweets where tweet_id>$start_id order by tweet_id limit 10000");
   endif;

   while ($row=mysql_fetch_array($res)):
    $tweet_id = $row['tweet_id'];
    $tweet_text = strtolower($row['tweet_text']);
    $processed++;  
    $found_in_tweet = 0;
    $keywords_in_tweet = '';

    if ($processed%1000==0):
      printf("%08d\n",$processed);
    endif;

    if ($debug):
      echo "-----------------\n$tweet_id: $tweet_text\n";
    endif;  

    $res2 = mysql_query("select * from tweet_keywords where tweet_id=$tweet_id");
    if (!$debug && mysql_num_rows($res2)>0):
      $skipped++;
      continue;
    endif;  

    for ($x=0;$x<count($keywords);$x++) {
      $keyword = strtolower($keywords[$x]);
      if (strstr($tweet_text,$keyword)):
        #-- The keyword is there    
        $keyfound++;
        $found_in_tweet++;
        $keywords_in_tweet .= "$keyword ";
        $xupdate = "tweet_id=$tweet_id,keyword='$keyword'";
        if (!$debug):
          $res2 = mysql_query("insert into tweet_keywords set $xupdate");
          if ($res2<=0):
            $error++;
          endif;  
        endif;
      endif;
    }

    if ($found_in_tweet>0):
      if ($debug):
        echo "Keywords found: $keywords_in_tweet\n";
      endif;  
      $found++;
    else:
      if ($debug):
        echo "NO keywords found\n";
      endif;
      $missing++;  
    endif;  

    $last_id = $tweet_id;

   endwhile;  

   if (!$debug && $num_tweets>0):
      $res = mysql_query("update counters set last_id=$last_id,records=$num_tweets,processed=$processed,skipped=$skipped,added=$keyfound,last_date=now() where name='keywords'");
   endif;

   echo "$processed out of $num_tweets tweets processed\n";
   echo "$skipped were skipped because we already had keywords for them\n";
   echo "$found tweets had keywords in them\n";
   echo "$missing did not\n";
   echo "$keyfound keywords identified\n";
   echo "$error database errors\n";

   if ($debug):
      break;
   endif;   
   
   echo "sleeping for a moment\n";
   sleep(60);

endwhile;

?>