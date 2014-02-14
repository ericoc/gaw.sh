-- Database: `gawsh`
-- --------------------------------------------------------

-- Table structure for table `urls`
CREATE TABLE IF NOT EXISTS `urls` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alias` varchar(50) NOT NULL,
  `url` text NOT NULL,
  `ip` varchar(128) NOT NULL,
  `time` varchar(15) NOT NULL DEFAULT '',
  `status` varchar(2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `alias` (`alias`),
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO `urls` (`id`, `alias`, `url`, `ip`, `time`, `status`) VALUES
(1, 'admin', '', '127.0.0.1', '1323141765', '-1'),
(2, '401', '', '127.0.0.1', '1323141766', '-1'),
(3, '403', '', '127.0.0.1', '1323141767', '-1'),
(4, '404', '', '127.0.0.1', '1323141768', '-1'),
(5, '410', '', '127.0.0.1', '1323141769', '-1'),
(6, '503', '', '127.0.0.1', '1323141770', '-1');


-- Table structure for table `visits`
DROP TABLE IF EXISTS `visits`;
CREATE TABLE IF NOT EXISTS `visits` (
  `id` int(10) unsigned NOT NULL,
  `ip` varchar(128) NOT NULL,
  `browser` text NOT NULL,
  `referrer` text NOT NULL,
  `time` varchar(15) NOT NULL,
  KEY `visits` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- Structure for view `recentvisits`
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `recentvisits` AS select `urls`.`alias` AS `alias`,from_unixtime(`visits`.`time`) AS `FROM_UNIXTIME(visits.time)`,`visits`.`ip` AS `ip`,`visits`.`referrer` AS `referrer` from (`visits` join `urls` on((`urls`.`id` = `visits`.`id`))) order by `visits`.`time` desc limit 100;

-- Structure for view `searchurlsvisits`
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `searchurlsvisits` AS select `urls`.`id` AS `id`,`urls`.`alias` AS `alias`,`urls`.`url` AS `url`,`urls`.`ip` AS `ip`,`urls`.`time` AS `time`,`urls`.`status` AS `status`,count(`visits`.`id`) AS `visits` from (`urls` join `visits` on((`visits`.`id` = `urls`.`id`))) group by `visits`.`id`;

-- Structure for view `urlsbyid`
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `urlsbyid` AS select `urls`.`id` AS `id`,`urls`.`alias` AS `alias`,`urls`.`url` AS `url`,`urls`.`ip` AS `ip`,`urls`.`time` AS `time`,`urls`.`status` AS `status`,count(`visits`.`id`) AS `visitors` from (`urls` join `visits` on((`visits`.`id` = `urls`.`id`))) group by `visits`.`id` order by count(`visits`.`id`) desc;

-- Structure for view `urlsbyvisits`
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `urlsbyvisits` AS select `urls`.`id` AS `id`,`urls`.`alias` AS `alias`,`urls`.`url` AS `url`,`urls`.`ip` AS `ip`,`urls`.`time` AS `time`,`urls`.`status` AS `status`,count(`visits`.`id`) AS `visitors` from (`urls` join `visits` on((`visits`.`id` = `urls`.`id`))) group by `visits`.`id` order by count(`visits`.`id`) desc;
