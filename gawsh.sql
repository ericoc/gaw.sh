-- Database: `gawsh`
-- --------------------------------------------------------

-- `urls` table
CREATE TABLE IF NOT EXISTS `urls` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alias` varchar(50) NOT NULL,
  `url` text NOT NULL,
  `ip` varchar(128) NOT NULL,
  `time` int(10) unsigned NOT NULL,
  `status` varchar(2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alias` (`alias`)
);

-- few default `urls`
INSERT INTO `urls` (`id`, `alias`, `url`, `ip`, `time`, `status`) VALUES
(1, 'admin', '', '127.0.0.1', '1323141765', '-1'),
(2, '401', '', '127.0.0.1', '1323141766', '-1'),
(3, '403', '', '127.0.0.1', '1323141767', '-1'),
(4, '404', '', '127.0.0.1', '1323141768', '-1'),
(5, '410', '', '127.0.0.1', '1323141769', '-1'),
(6, '503', '', '127.0.0.1', '1323141770', '-1');


-- `visits` table
CREATE TABLE IF NOT EXISTS `visits` (
  `id` int(10) unsigned NOT NULL,
  `ip` varchar(128) NOT NULL,
  `browser` text NOT NULL,
  `referrer` text NOT NULL,
  `time` int(10) unsigned NOT NULL,
  KEY `visits` (`id`),
  CONSTRAINT `urlid_fk` FOREIGN KEY (`id`) REFERENCES `urls` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
);

-- --------------------------------------------------------

-- `recentvisits` view
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `recentvisits` AS select `urls`.`alias` AS `alias`,from_unixtime(`visits`.`time`) AS `FROM_UNIXTIME(visits.time)`,`visits`.`ip` AS `ip`,`visits`.`referrer` AS `referrer` from (`visits` join `urls` on((`urls`.`id` = `visits`.`id`))) order by `visits`.`time` desc limit 100;

-- `searchurlsvisits` view
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `searchurlsvisits` AS select `urls`.`id` AS `id`,`urls`.`alias` AS `alias`,`urls`.`url` AS `url`,`urls`.`ip` AS `ip`,`urls`.`time` AS `time`,`urls`.`status` AS `status`,count(`visits`.`id`) AS `visits` from (`urls` join `visits` on((`visits`.`id` = `urls`.`id`))) group by `visits`.`id`;

-- `urlsbyid` view
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `urlsbyid` AS select `urls`.`id` AS `id`,`urls`.`alias` AS `alias`,`urls`.`url` AS `url`,`urls`.`ip` AS `ip`,`urls`.`time` AS `time`,`urls`.`status` AS `status`,count(`visits`.`id`) AS `visitors` from (`urls` join `visits` on((`visits`.`id` = `urls`.`id`))) group by `visits`.`id` order by count(`visits`.`id`) desc;

-- `urlsbyvisits` view
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `urlsbyvisits` AS select `urls`.`id` AS `id`,`urls`.`alias` AS `alias`,`urls`.`url` AS `url`,`urls`.`ip` AS `ip`,`urls`.`time` AS `time`,`urls`.`status` AS `status`,count(`visits`.`id`) AS `visitors` from (`urls` join `visits` on((`visits`.`id` = `urls`.`id`))) group by `visits`.`id` order by count(`visits`.`id`) desc;
