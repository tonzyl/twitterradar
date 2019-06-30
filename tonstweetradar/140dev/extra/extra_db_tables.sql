

CREATE TABLE IF NOT EXISTS `tweet_keywords` (
  `tweet_id` bigint(20) unsigned NOT NULL,
  `keyword` varchar(100) NOT NULL,
  KEY `tweet_id` (`tweet_id`),
  KEY `keyword` (`keyword`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tweet_urls2` (
  `tweet_id` bigint(20) NOT NULL,
  `short_url` varchar(140) NOT NULL,
  `real_url` varchar(140) NOT NULL,
  `url_type` varchar(20) NOT NULL,
  KEY `tweet_id` (`tweet_id`),
  KEY `short_url` (`short_url`),
  KEY `real_url` (`real_url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `counters` (
    `name` varchar(20) NOT NULL,
    `last_id` bigint(20) unsigned NOT NULL,
    `records` int(11) unsigned NOT NULL,
    `processed` int(11) unsigned NOT NULL,
    `skipped` int(11) unsigned NOT NULL,
    `added` int(11) unsigned NOT NULL,
    `last_date` datetime NOT NULL,
    KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


