CREATE TABLE `pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL COMMENT 'Имя обработчика',
  `url` varchar(145) NOT NULL COMMENT 'URL страницы (точный или regexp)',
  `description` varchar(245) NOT NULL COMMENT 'Общее описание',
  `cacheable` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Кешировать ?',
  PRIMARY KEY (`id`),
  KEY `idx_url` (`url`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='Web pages';
