<?php

/**************************************************************\
 *        _____ ________  ________  ___  _                    *
 *       /  ___/  __ \  \/  /  ___|/ _ \(_)                   *
 *       \ `--.| /  \/ .  . \ `--./ /_\ \_  __ ___  __        *
 *        `--. \ |   | |\/| |`--. \  _  | |/ _` \ \/ /        *
 *       /\__/ / \__/\ |  | /\__/ / | | | | (_| |>  <         *
 *       \____/ \____|_|  |_|____/\_| |_/ |\__,_/_/\_\        *
 *                                     _/ |                   *
 *    >> StrigelCMS Ajax <<           |__/                    *
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


//Protect the rest of the files
define('SCMS', 1);
//--

//Load basic settings
require_once("../settings.php");
//--

//PHP - error settings
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 'On');
ini_set('display_errors', 1);
//--

//Set timezone
date_default_timezone_set('Europe/Berlin');
//--

//Session
session_start();
//--

//Header
header('Content-Type: text/html; charset=UTF-8');
//--

//Working Directory (mainly for log function)
define('DIR_WORK', str_replace('admin/', '', str_replace('\\', '/', getcwd()).'/'));
//--

//Load functions
require_once('../'.DIR_FUNC.'all_functions.inc.php');
//--

//Instantiate Database
$DB = new mysql(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
if($DB->error()) {
    logit('main/instantiate_database', 'Could not connect to Database! '.
                                       'See db logs for more information!');
    die('Heavy Database Error! Could not connect! Call Admin: '.ADMIN_MAIL);
}
//--

//Set UTF-8 as encoding for DB connection
$DB->execute("SET NAMES 'utf8'");
//--

//Load Settings class
$CONFIG = new settings($DB);
//--

//Useful functions
function getImageList($dir, $subdir = '/') {
    $files = scandir($dir);

    $images = array();
    $folders = array();
    foreach($files AS $file) {
        if(in_array($file, array('..', '.')))
            continue;
        if(is_dir($dir.'/'.$file)) {
            $folders = array_merge($folders, getImageList($dir.'/'.$file, $subdir.$file.'/'));
            continue;
        }
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if(in_array(strtolower($ext), array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg')))
            $images[] =  $subdir.$file;
    }

    return array_merge($folders, $images);
}
//--


if(empty($_GET['get']))
    die();

switch($_GET['get']){
    case 'tinymce_image_list':
        if(empty($_GET['include']))
            die();

        $include = str_replace('..', '', $_GET['include']);
        if(!is_dir('../'.DIR_FILES.$include))
            die();

        $images = getImageList('../'.DIR_FILES.$include);

        $output = array();
        foreach($images AS $image) {
            $output[] = '["'.substr($image, 1).'", "../'.DIR_FILES.$include.$image.'"]';
        }

        echo 'var tinyMCEImageList = new Array('.LF.implode(",\n", $output).LF.');';
        break;
    case 'tinymce_content_css':
        if(empty($_GET['site']))
            die();

        $result = $DB->qquery(sprintf("SELECT `css` FROM `%ssys_content` WHERE `link` = '%s' LIMIT 1",
                                      DB_PRE, $DB->escape($_GET['site'])));

        if($result)
            echo $result->css;
        break;
}
