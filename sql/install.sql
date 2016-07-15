CREATE TABLE IF NOT EXISTS `civicrm_postcodenl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `postcode_nr` int(4) NOT NULL,
  `postcode_letter` varchar(2) NOT NULL,
  `huisnummer_van` int(11) NOT NULL,
  `huisnummer_tot` int(11) NOT NULL,
  `adres` varchar(255) NOT NULL,
  `even` tinyint(1) NOT NULL DEFAULT '1',
  `provincie` varchar(255) NOT NULL,
  `gemeente` varchar(255) NOT NULL,
  `woonplaats` varchar(255) NOT NULL,
  `cbs_wijkcode` varchar(255) NOT NULL,
  `cbs_buurtcode` varchar(255) NOT NULL,
  `cbs_buurtnaam` varchar(255) NOT NULL,
  `latitude` DOUBLE NULL,
  `longitude` DOUBLE NULL,
  PRIMARY KEY (`id`),
  KEY `postcode` (`postcode_nr`,`postcode_letter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `civicrm_pro6pp_import` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `postcode_nr` int(4) NOT NULL,
  `postcode_letter` varchar(2) NOT NULL,
  `huisnummer_van` int(11) NOT NULL,
  `huisnummer_tot` int(11) NOT NULL,
  `adres` varchar(255) NOT NULL,
  `even` tinyint(1) NOT NULL DEFAULT '1',
  `provincie` varchar(255) NOT NULL,
  `gemeente` varchar(255) NOT NULL,
  `woonplaats` varchar(255) NOT NULL,
  `cbs_wijkcode` varchar(255) NULL default '',
  `cbs_buurtcode` varchar(255) NULL default '',
  `cbs_buurtnaam` varchar(255) NULL default '',
  `latitude` DOUBLE NULL,
  `longitude` DOUBLE NULL,
  PRIMARY KEY (`id`),
  KEY `postcode` (`postcode_nr`,`postcode_letter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `civicrm_pro6pp_import_cbsbuurt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `postcode_nr` int(4) NOT NULL,
  `postcode_letter` varchar(2) NOT NULL,
  `cbs_wijkcode` varchar(255) NOT NULL,
  `cbs_buurtcode` varchar(255) NOT NULL,
  `cbs_buurtnaam` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `postcode` (`postcode_nr`,`postcode_letter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `civicrm_postcodenl_alt_city` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provincie` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `alt_city` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;