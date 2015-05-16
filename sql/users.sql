CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `displayUsername` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `CSRF` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `billingPlan` int(11) DEFAULT '0',
  `apiToken` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `locked` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
