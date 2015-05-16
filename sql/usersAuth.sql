CREATE TABLE `usersAuth` (
  `userId` int(11) NOT NULL COMMENT 'Номер пользователя в users',
  `service` int(11) NOT NULL COMMENT 'Тип сервиса',
  `serviceKey1` int(11) DEFAULT NULL COMMENT 'Ключ 1',
  `serviceKey2` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Ключ 2',
  `serviceKey3` mediumtext COLLATE utf8_unicode_ci COMMENT 'Ключ 3',
  UNIQUE KEY `uniq_UserId_Service` (`userId`,`service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
