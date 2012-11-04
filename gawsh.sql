--
-- Database: `gawsh`
--

-- --------------------------------------------------------

--
-- Stand-in structure for view `recentvisits`
--
DROP VIEW IF EXISTS `recentvisits`;
CREATE TABLE IF NOT EXISTS `recentvisits` (
`alias` varchar(50)
,`FROM_UNIXTIME(visits.time)` datetime
,`ip` varchar(128)
,`referrer` text
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `searchurlsvisits`
--
DROP VIEW IF EXISTS `searchurlsvisits`;
CREATE TABLE IF NOT EXISTS `searchurlsvisits` (
`id` int(10) unsigned
,`alias` varchar(50)
,`url` text
,`ip` varchar(128)
,`time` varchar(15)
,`status` varchar(2)
,`visits` bigint(21)
);
-- --------------------------------------------------------

--
-- Table structure for table `urls`
--

DROP TABLE IF EXISTS `urls`;
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
(2, '404', '', '127.0.0.1', '1323141766', '-1'),
(3, '403', '', '127.0.0.1', '1323141767', '-1');

-- --------------------------------------------------------

--
-- Stand-in structure for view `urlsbyid`
--
DROP VIEW IF EXISTS `urlsbyid`;
CREATE TABLE IF NOT EXISTS `urlsbyid` (
`id` int(10) unsigned
,`alias` varchar(50)
,`url` text
,`ip` varchar(128)
,`time` varchar(15)
,`status` varchar(2)
,`visitors` bigint(21)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `urlsbyvisits`
--
DROP VIEW IF EXISTS `urlsbyvisits`;
CREATE TABLE IF NOT EXISTS `urlsbyvisits` (
`id` int(10) unsigned
,`alias` varchar(50)
,`url` text
,`ip` varchar(128)
,`time` varchar(15)
,`status` varchar(2)
,`visitors` bigint(21)
);
-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

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

--
-- Structure for view `recentvisits`
--
DROP TABLE IF EXISTS `recentvisits`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `recentvisits` AS select `urls`.`alias` AS `alias`,from_unixtime(`visits`.`time`) AS `FROM_UNIXTIME(visits.time)`,`visits`.`ip` AS `ip`,`visits`.`referrer` AS `referrer` from (`visits` join `urls` on((`urls`.`id` = `visits`.`id`))) order by `visits`.`time` desc limit 100;

-- --------------------------------------------------------

--
-- Structure for view `searchurlsvisits`
--
DROP TABLE IF EXISTS `searchurlsvisits`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `searchurlsvisits` AS select `urls`.`id` AS `id`,`urls`.`alias` AS `alias`,`urls`.`url` AS `url`,`urls`.`ip` AS `ip`,`urls`.`time` AS `time`,`urls`.`status` AS `status`,count(`visits`.`id`) AS `visits` from (`urls` join `visits` on((`visits`.`id` = `urls`.`id`))) group by `visits`.`id`;

-- --------------------------------------------------------

--
-- Structure for view `urlsbyid`
--
DROP TABLE IF EXISTS `urlsbyid`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `urlsbyid` AS select `urls`.`id` AS `id`,`urls`.`alias` AS `alias`,`urls`.`url` AS `url`,`urls`.`ip` AS `ip`,`urls`.`time` AS `time`,`urls`.`status` AS `status`,count(`visits`.`id`) AS `visitors` from (`urls` join `visits` on((`visits`.`id` = `urls`.`id`))) group by `visits`.`id` order by count(`visits`.`id`) desc;

-- --------------------------------------------------------

--
-- Structure for view `urlsbyvisits`
--
DROP TABLE IF EXISTS `urlsbyvisits`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `urlsbyvisits` AS select `urls`.`id` AS `id`,`urls`.`alias` AS `alias`,`urls`.`url` AS `url`,`urls`.`ip` AS `ip`,`urls`.`time` AS `time`,`urls`.`status` AS `status`,count(`visits`.`id`) AS `visitors` from (`urls` join `visits` on((`visits`.`id` = `urls`.`id`))) group by `visits`.`id` order by count(`visits`.`id`) desc;
