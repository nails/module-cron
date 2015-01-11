# INITIAL ADMIN DB
# This is the schema of the Cron module database as of 09/01/2015
DROP TABLE IF EXISTS `{{NAILS_DB_PREFIX}}log_cron`;
CREATE TABLE `{{NAILS_DB_PREFIX}}log_cron` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `task` varchar(150) NOT NULL DEFAULT '', `duration` double NOT NULL, `message` varchar(500) DEFAULT NULL, `created` datetime NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;