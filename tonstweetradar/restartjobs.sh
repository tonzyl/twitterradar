echo "Ending running radar jobs"
pkill -u user -f "php get_tweets.php"
pkill -u user -f "php parse_tweets.php"
pkill -u user -f "php get_keywords.php"
pkill -u user -f "php unshorten_urls.php"
echo "Starting radar jobs anew"
cd /home/user/140dev/db
nohup php get_tweets.php > /dev/null &
nohup php parse_tweets.php > /dev/null &
cd /home/user/140dev/extra
nohup php get_keywords.php > /dev/null &
nohup php unshorten_urls.php > /dev/null &
