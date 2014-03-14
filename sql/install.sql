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
  `wijk` varchar(255) NOT NULL,
  `buurt` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
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
  `wijk` varchar(255) NOT NULL,
  `buurt` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;