CREATE USER 'pacs'@'localhost' IDENTIFIED BY 'pacspassword';

GRANT USAGE ON * . * TO 'pacs'@'localhost' IDENTIFIED BY 'pacspassword' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;

CREATE DATABASE IF NOT EXISTS `pacs` ;

GRANT ALL PRIVILEGES ON `pacs` . * TO 'pacs'@'localhost';

use pacs;

CREATE TABLE IF NOT EXISTS `studies` (
  `seq` int(11) NOT NULL auto_increment,
  `firstname` varchar(25) NOT NULL,
  `lastname` varchar(25) NOT NULL,
  `id` varchar(25) NOT NULL,
  `appt_date` date NOT NULL,
  `dob` date NOT NULL,
  `study_uid` varchar(100) NOT NULL,
  `study_desc` varchar(100) NOT NULL,
  `accession` varchar(25) NOT NULL,
  `history` varchar(255) NOT NULL,
  `institution` varchar(255) NOT NULL,
  `sent_from_ae` varchar(25) NOT NULL,
  `sent_to_ae` varchar(25) NOT NULL,
  PRIMARY KEY  (`seq`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `images` (
  `seq` int(11) NOT NULL auto_increment,
  `study_seq` int(11) NOT NULL,
  `series_number` int(11) NOT NULL,
  `instance_number` int(11) NOT NULL,
  `sop_instance` varchar(255) NOT NULL,
  `transfer_syntax` varchar(100) NOT NULL,
  `body_part_examined` varchar(100) NOT NULL,
  `image_date` datetime NOT NULL,
  `modality` varchar(4) NOT NULL,
  PRIMARY KEY  (`seq`),
  KEY `study_seq` (`study_seq`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;