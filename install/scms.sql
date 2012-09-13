-- MySQL Dump für die Installation von StrigelCMS

-- Copyright: Klemens Schölhorn, 2008 - 2011
-- Website: http://schoelhorn.eu
-- Licence: http://creativecommons.org/licenses/by/3.0/


-- Verbindung auf utf8 stellen

SET NAMES 'utf8';

-- Tabellen anlegen

CREATE TABLE IF NOT EXISTS `{dbPrefix}sys_404` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `referer` text COLLATE utf8_unicode_ci NOT NULL,
  `time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbPrefix}sys_autologin` (
  `uniqid` char(40) COLLATE utf8_unicode_ci NOT NULL,
  `userID` int(11) NOT NULL,
  `expire` int(11) NOT NULL,
  UNIQUE KEY `uniqid` (`uniqid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbPrefix}sys_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `link` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `content_type` enum('html','file','redirect') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'html',
  `showEditor` tinyint(1) NOT NULL DEFAULT '0',
  `file` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8_unicode_ci,
  `redirect` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date` datetime NOT NULL,
  `author` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `robot_visibility` tinyint(1) NOT NULL DEFAULT '1',
  `description` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `keywords` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `css` text COLLATE utf8_unicode_ci,
  `js` text COLLATE utf8_unicode_ci,
  `header_image` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `files` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lang` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'de-de',
  `m_title` varchar(256) COLLATE utf8_unicode_ci NOT NULL,
  `m_pid` int(11) NOT NULL DEFAULT '0',
  `m_sort` smallint(6) NOT NULL DEFAULT '10',
  `m_active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `link` (`link`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3 ;

CREATE TABLE IF NOT EXISTS `{dbPrefix}sys_errors` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `{dbPrefix}sys_rights` (
  `userID` int(11) NOT NULL,
  `right` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `{dbPrefix}sys_settings` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `section` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=14 ;

CREATE TABLE IF NOT EXISTS `{dbPrefix}sys_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `password` char(40) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL,
  `activation_code` char(40) COLLATE utf8_unicode_ci NOT NULL,
  `salt` char(3) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- Daten einfügen

INSERT INTO `{dbPrefix}sys_content` (`id`, `title`, `link`, `content_type`, `showEditor`, `file`, `content`, `redirect`, `date`, `author`, `robot_visibility`, `description`, `keywords`, `css`, `js`, `header_image`, `files`, `lang`, `m_title`, `m_pid`, `m_sort`, `m_active`) VALUES
(1, '404 - Seite nicht gefunden', '404', 'file', 0, '404.php', NULL, NULL, '2011-10-08 01:13:00', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 'de-de', '404 - Seite nicht gefunden', 0, 30, 0),
(2, 'Startseite', 'home', 'html', 1, NULL, '<h1>Willkommen beim StrigelCMS</h1><p>Herzlichen Glückwunsch. Die Installation des StrigelCMS wurde erfolgreich abgeschlossen.</p><p>Unter der URL <a href="{href:admin}">{href:admin}</a> finden Sie das Backend, wo Sie Ihre Website verwalten können.</p><p>Weitere Informationen und Hilfestellungen bei Problemen finden Sie unter <a href="http://klemens.schoelhorn.eu/scms">http://klemens.schoelhorn.eu/scms</a>.</p>', NULL, '2011-10-08 01:13:00', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 'de-de', 'Home', 0, 8, 1);

INSERT INTO `{dbPrefix}sys_rights` (`userID`, `right`, `value`) VALUES
(1, 'admin_pages', '0'),
(1, 'admin_edit_pages', NULL),
(1, 'admin_filemanager', NULL),
(1, 'admin_news', NULL),
(1, 'admin_template', NULL),
(1, 'admin_members', NULL),
(1, 'admin_rights', NULL),
(1, 'admin_change_pass', NULL),
(1, 'admin_settings', NULL),
(1, 'admin_404', NULL),
(1, 'admin_errors', NULL),
(1, 'admin_phpinfo', NULL),
(1, 'admin_backup', NULL);


INSERT INTO `{dbPrefix}sys_settings` (`id`, `name`, `section`, `value`) VALUES
(1, 'website_name', 'sys', '{websiteName}'),
(3, 'url_remove-html', 'sys', 'TRUE'),
(4, 'date_format', 'sys', '%d.%m.%Y'),
(5, 'time_format', 'sys', '%H:%i'),
(6, 'startpage', 'sys', 'home'),
(7, 'force_mod_rewrite', 'sys', '{advRewrite}'),
(9, 'current_template', 'sys', 'strigel'),
(10, 'breadcrumb_full_path', 'sys', '1'),
(11, 'default_timezone', 'sys', '{advTimezone}'),
(12, 'default_description', 'sys', '{websiteDescription}'),
(13, 'default_keywords', 'sys', '{websiteKeywords}');
