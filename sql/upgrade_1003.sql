CREATE TABLE IF NOT EXISTS `civicrm_postcodenl_alt_city` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provincie` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `alt_city` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;