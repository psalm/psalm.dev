CREATE DATABASE IF NOT EXISTS  `psalm_web`;

GRANT ALL ON `psalm_web`.* TO 'psalm_mysql_user'@'%' IDENTIFIED BY 'psalm_mysql_development_password';

CREATE TABLE `psalm_web`.`codes` (
 `hash` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
 `code` text COLLATE utf8_unicode_ci NOT NULL,
 `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `ip` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
 `unused_variables` bit(1) NOT NULL DEFAULT b'1',
 `unused_methods` bit(1) NOT NULL DEFAULT b'0',
 `memoize_properties` bit(1) NOT NULL DEFAULT b'1',
 `memoize_method_calls` bit(1) NOT NULL DEFAULT b'0',
 `check_throws` bit(1) NOT NULL DEFAULT b'0',
 `strict_internal_functions` bit(1) NOT NULL DEFAULT b'0',
 `restrict_return_types` bit(1) NOT NULL DEFAULT b'0',
 `allow_phpstorm_generics` bit(1) NOT NULL DEFAULT b'0',
 PRIMARY KEY (`hash`),
 KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci


