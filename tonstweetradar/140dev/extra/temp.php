<?php

#-- unshorten_urls.php ------ Go through unprocessed urls, unshorten them if necessary, and figure out their type


require_once('extra_config.php');

function unshorten($short_url,$num,$tweet_id) {
  #-- Recursive shortening. Might be called several times, if the unshortened URL turns out to also be shortened
  global $shorteners,$last_unshortened_id;
  $real_url = $short_url;
  
  $unshortener = "longurl";
  #if ($tweet_id==$last_unshortened_id && $num==1):
   #  echo "We got #$tweet_id for a second time. Something is wrong.\n";
   #  exit;
  #endif;  
  $last_unshortened_id = $tweet_id;

  if ($num==1):
    echo "  $tweet_id: $short_url\n";
  endif;
  
  $regexp = preg_match_all($shorteners, $short_url, $matches);
  if (true || $matches[0]):
    #-- A recognized shortener
    #-- See if we already know the expansion of the url
    if ($matches[0] && gettype($matches[0])=="array"):
      $shortener = $matches[0][0];
      echo "  seems to be shortened by $shortener\n";
    else:  
      $shortener = '???';
    endif;
    $know_it = false;
    $res2 = mysql_query("select * from tweet_urls2 where short_url='$short_url' order by tweet_id desc limit 1");
    if (mysql_num_rows($res2)>0):
      $row2 = mysql_fetch_array($res2);
      $real_url = $row2['real_url'];
      echo "  we already had it: $real_url\n";  
      return($real_url);
      $know_it = true;
    endif;  
    if (!$know_it):
      #-- Ask the unshort API service about this
      $xurl = urlencode($short_url);
      echo "  calling api #$num for $short_url\n";
      # unshorteners: http://topalternate.com/unshort.me/
      if ($unshortener=='longurl'):
        $baseurl= "api.longurl.org/v2/expand?url={$xurl}&format=json";        
      else:  
        $baseurl= "api.unshort.me/?r={$xurl}&t=json";
      endif;  
      $useragent="Tons/Radar";
      $ch = curl_init($baseurl);
      curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $data = curl_exec($ch);
      curl_close($ch);
      $obj = json_decode($data);
      #print_r($obj);

      $success = false;

      if ($unshortener=='longurl'):
        # {"long-url":"http:\/\/cpplover.blogspot.jp\/2013\/04\/windowsp2p.html"}
        # {"messages":[{"message":"Could not expand URL. Please check that you have submitted a valid URL.","type":"error"}]}
        if ($obj && property_exists($obj,'long-url')):
          $success = true;
          $real_url = $obj->{'long-url'};
        endif;  
      else:
        $success = $obj->success;
        if ($success):
          $real_url = $obj->resolvedURL;
        endif;
      endif;
      
      if ($success):
        echo "  ->$real_url\n";
        if ($num<2 && $real_url!=$short_url):
          #-- Do it twice, if necessary
          $real_url = unshorten($real_url,$num+1,$tweet_id);
        endif;   
      else:
        echo "  failure\n";       
      endif;
      
      #-- Wait a moment
      sleep(2);
      
    endif;  
  elseif ($num==1):
    echo "  doesn't seem to be shortened\n";  
  endif;
  
  return($real_url);
  
}

function urltype($url) {
  #-- Guess what type of document this is
  if (preg_match("/\.pdf$|\.doc$|\.odt$/i",$url)):
    $url_type = "document file";
  elseif (preg_match("/twitter\.com/i",$url)):  
    $url_type = "tweet";
  elseif (preg_match("/slideshare|prezi|slidesha\.re/",$url)):  
    $url_type = "embedded presentation";
  elseif (preg_match("/scribd\.com/",$url)):  
    $url_type = "embedded document";
  elseif (preg_match("/twitpic|yfrog|flickr/",$url)):  
    $url_type = "photo";
  elseif (preg_match("/youtube\.com|vimeo\.com|youtu\.be/",$url)):  
    $url_type = "video";  
  elseif (preg_match("/\.jpg$|\.png$|\.gif$/",$url)):  
    $url_type = "image";
  elseif (preg_match("/pinterest\.com\/pin/",$url)):  
    $url_type = "image";
  elseif (preg_match("/\.htm$|\.html$/i",$url)):  
    $url_type = "general link";
  elseif (preg_match("(\.[a-z]{2,4}$|\.[a-z]{2,4}/$)",$url)):  
    $url_type = "domain name";  
  else:
    $url_type = "generic link";
  endif;   
  echo "  type: $url_type\n";
  return($url_type);
}

   $start = '2014-07-01 00:00:00';
   $end =  '2014-07-05 23:59:59';

   $res = mysql_query("select count(*) from tweet_urls");
   $num_urls = mysql_result($res,0,0);
   echo "There are $num_urls total urls\n";

   # list of url shorteners: http://bit.do/list-of-url-shorteners.php
   # even more here: http://longurl.org/services
   $shorteners = '(bit\.ly|t\.co|cot\.ag|is\.gd|lnk\.in|tinyurl\.com|tr\.im|goo\.gl|bit\.do|go2\.do|adf\.ly|adcrun\.ch|zpag\.es|ity\.im|q\.gs|lnk\.co|bc\.vc|yu2\.it|u\.to|j\.mp|u\.bb|fun\.ly|hit\.my|nov\.io|x\.co|fzy\.co|xtu\.me|qr\.net|1url\.com|sk\.gy|gog\.li|v\.gd|p6l\.org|id\.tl|dft\.ba|aka\.gr|wp\.me|ow\.ly)';

   $processed = $donebefore = $expanded = $untouched = $newrecs = $error = 0;

   $last_id = $start_id;
   $last_unshortened_id = -1;
   
   $res = mysql_query("select min(tweet_id) as start_id,max(tweet_id) as end_id from tweets where created_at>='$start' and created_at<='$end'");
   $row = mysql_fetch_array($res);
   $start_id = $row['start_id'];
   $end_id = $row['end_id'];
   echo "Want to process tweets $start_id($start) - $end_id($end)\n";

   #-- Go through raw urls identified by 140dev
   $res = mysql_query("select * from tweet_urls where tweet_id>='$start_id' and tweet_id<='$end_id' order by tweet_id desc");
   if ($res<=0):
      echo "error: ".mysql_error($res)."\n";
   endif;
   $num_urls = mysql_num_rows($res);
   echo "Got $num_urls to process\n";

   while ($row=mysql_fetch_array($res)):
    $tweet_id = $row['tweet_id'];
    $short_url = $row['url'];
    $real_url = $short_url;
    $processed++;  

    if ($processed%10==0):
      printf("%08d\n",$processed);
    endif;

    $real_url = unshorten($short_url,1,$tweet_id);

    if ($real_url!=$short_url):
      $expanded++;
    else:
      $untouched++;
    endif;    

    $url_type = urltype($real_url);

    #-- Store the expanded url, whether it really was expanded or not
    $xupdate = "real_url='$real_url',url_type='$url_type'";
    $res2 = mysql_query("select * from tweet_urls2 where tweet_id=$tweet_id and short_url='$short_url'");
    if (mysql_num_rows($res2)>0):
      #-- We've done it before. Just update it.
      $res2 = mysql_query("update tweet_urls2 set $xupdate where tweet_id=$tweet_id and short_url='$short_url'");    
      $donebefore++;
    else:  
      #-- This is new
      $xupdate .= ",tweet_id=$tweet_id,short_url='$short_url'";
      $res2 = mysql_query("insert into tweet_urls2 set $xupdate");   
      $newrecs++; 
    endif;  
    if ($res2<0):
      $error++;
    endif;  

    $last_id = $tweet_id;

   endwhile;  

   echo "$processed out of $num_urls urls processed\n";
   echo "$donebefore had been processed before\n";
   echo "$expanded urls were unshortened\n";
   echo "$untouched urls were left unchanged\n";
   echo "$newrecs urls were stored\n";
   echo "$error database errors\n";

?>