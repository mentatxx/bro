CREATE TABLE `modules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page` int(10) unsigned NOT NULL COMMENT 'id страницы',
  `name` varchar(45) NOT NULL COMMENT 'Имя модуля',
  `enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Включен ?',
  `isRaw` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Чистый HTML ?',
  `rawHTML` text COMMENT 'Статичный HTML',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='Список веб-модулей';

INSERT INTO `modules` (`id`,`page`,`name`,`enabled`,`isRaw`,`rawHTML`) VALUES (1,0,'commonModule',1,0,NULL);
