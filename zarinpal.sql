-- --------------------------------------------------------

-- 
-- Table structure for table `zarinpal_ereceipt`
-- 

CREATE TABLE `PREFIX_zarinpal_ereceipt` (
  `id` int(11) NOT NULL auto_increment,
  `orderid` int(11) NOT NULL default '0',
  `amount` decimal(15,4) NOT NULL default '0.0000',
  `refer_number` varchar(36) NOT NULL default '',
  `verify` varchar(10) default NULL,
  `return` tinyint(1) NOT NULL default '0',
  `used` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM ;

-- --------------------------------------------------------

-- 
-- Table structure for table `zarinpal_reservation`
-- 

CREATE TABLE `PREFIX_zarinpal_reservation` (
  `id` int(11) NOT NULL auto_increment,
  `orderid` int(11) NOT NULL default '0',
  `amount` decimal(15,4) NOT NULL default '0.0000',
  `res_number` varchar(36) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM ;

-- --------------------------------------------------------

-- 
-- Table structure for table `zarinpal_returnlog`
-- 

CREATE TABLE `PREFIX_zarinpal_returnlog` (
  `id` int(11) NOT NULL auto_increment,
  `erid` int(11) NOT NULL default '0',
  `ret_amount` decimal(15,4) NOT NULL default '0.0000',
  `error` int(7) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;
