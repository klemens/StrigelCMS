<?php

/**************************************************************\
 *     _____ _        _            _ _____ ___  ___ _____     *
 *    /  ___| |      (_)          | /  __ \|  \/  |/  ___|    *
 *    \ `--.| |_ _ __ _  __ _  ___| | /  \/| .  . |\ `--.     *
 *     `--. \ __| '__| |/ _` |/ _ \ | |    | |\/| | `--. \    *
 *    /\__/ / |_| |  | | (_| |  __/ | \__/\| |  | |/\__/ /    *
 *    \____/ \__|_|  |_|\__, |\___|_|\____/\_|  |_/\____/     *
 *                       __/ |                                *
 *                      |___/                >> StrigelCMS << *
 *                                                            *
 *   ## Info ##############################################   *
 *                                                            *
 *    Version: 1.0                                            *
 *    Greetings to BSG Memmingen and Herr Wetzstein           *
 *                                                            *
 *   ## Licence ###########################################   *
 *                                                            *
 *    Copyright: Klemens Schölhorn, 2008 - 2011               *
 *    Website: http://schoelhorn.eu                           *
 *    Licence: http://creativecommons.org/licenses/by/3.0/    *
 *    Any OpenSource licence would be nice for a remix!       *
 *                                                            *
\**************************************************************/

//Prevent direct access
if(!defined('SCMS')) {
    header('HTTP/1.1 403 Forbidden');
    die('Access denied.');
}
//--

//SCMS Version
define('SCMS_VERSION', '1.2');
//--

//MAIL
define('ADMIN_MAIL', '{adminMail}');
//--

//MAINTENANCE
//If set to a string, it will be displayed instead of website
define('MAINTENANCE', false);
//--

//TIMEZONE
define('TIMEZONE', '{timezone}');
//--

//DATABASE
define('MYSQL_HOST', '{dbServer}');
define('MYSQL_USER', '{dbUser}');
define('MYSQL_PASS', '{dbPass}');
define('MYSQL_DB', '{dbDatabase}');

define('DB_PRE', '{dbPrefix}');
//--

//SECURITY
define('CRYPT_SALT', '{salt}');
//--

//LOG FILES
define('LOG_DIRECTORY', 'sys/log/');
define('LOG_FILE', 'cms.log');
//--

//Directories
define('DIR_FUNC',   'sys/classes/');
define('DIR_INCL',   'modules/');
define('DIR_PLUGIN', 'plugins/');
define('DIR_RES',    'resources/');
define('DIR_HEADER', 'resources/header/');
define('DIR_FILES',  'resources/site_files/');
define('DIR_JS',     'resources/js/');
define('DIR_PDF',    'resources/pdf/');
define('DIR_CSS',    'resources/css/');

define('DIR_TEMP',   'resources/templates/');
define('TEMP_DEF',   'strigel/');
//--

//Special Chars
define('LF', "\n");
define('SP', " ");
//--
