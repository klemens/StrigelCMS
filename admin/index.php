<?php

/**************************************************************\
 *    _____ _____ ___  ___ _____  ______            _         *
 *   /  ___/  __ \|  \/  |/  ___| | ___ \          | |        *
 *   \ `--.| /  \/| .  . |\ `--.  | |_/ / __ _  ___| | __     *
 *    `--. \ |    | |\/| | `--. \ | ___ \/ _` |/ __| |/ /     *
 *   /\__/ / \__/\| |  | |/\__/ / | |_/ / (_| | (__|   <      *
 *   \____/ \____/\_|  |_/\____/  \____/ \__,_|\___|_|\_\     *
 *                                                            *
 *                                   >> StrigelCMS Backend << *
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

//Clean up data send in get, post, cookie
function stripslashes_deep($value) {
    if(isset($value)) {
        $value = is_array($value) ?
                 array_map('stripslashes_deep', $value) :
                 stripslashes($value);
    }
    return $value;
}
if((function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc())
    || (ini_get('magic_quotes_sybase') && (strtolower(ini_get('magic_quotes_sybase'))!="off")) ){
    $_GET = stripslashes_deep($_GET);
    $_POST = stripslashes_deep($_POST);
    $_COOKIE = stripslashes_deep($_COOKIE);
    $_REQUEST = stripslashes_deep($_REQUEST);
}
//--

//Load basic settings
if(!file_exists('../settings.php') && is_dir('../install/')) {
    header("Location: ../install");
    die;
} else {
    require_once('../settings.php');
}
//--

//PHP - error settings
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 'On');
ini_set('display_errors', 1);
//--

//Set timezone
date_default_timezone_set('Europe/Berlin');
//--

//Some useful functions
//$use_old:
//  0: keep nothing
//  1: keep the module
//  2: keep everything
function make_link($use_old = 0) {
    $args = array();
    if(2 === $use_old) {
        $query = $_SERVER['QUERY_STRING'];
        $pairs = explode('&', $query);
        foreach($pairs AS $pair) {
            if(!strpos($pair, '=')) continue;
            list($arg, $value) = explode('=', $pair);
            $arg = trim($arg);
            if(!empty($arg)) $args[trim($arg)] = trim($value);
        }
    } else if(1 === $use_old) {
        if(!empty($_GET['do'])) {
            $args['do'] = $_GET['do'];
        }
    }

    foreach(func_get_args() AS $new_arg) {
        if(!strpos($new_arg, '=')) continue;
        $explodedarg = explode('=', $new_arg);
        $arg = array_shift($explodedarg);
        $value = implode('=', $explodedarg);
        $arg = trim($arg);
        if(!empty($arg)) $args[trim($arg)] = trim($value);
    }

    $link = $_SERVER['PHP_SELF'];

    $a_arg = array();
    foreach($args AS $arg => $value) {
        $arg = trim($arg);
        if(empty($arg)) continue;
        $a_arg[] = urlencode($arg).'='.urlencode($value);
    }

    if(!empty($a_arg)) $link .= '?';

    $link .= implode('&amp;', $a_arg);

    return $link;
}

function success_message($success, $message)
{
    switch($success) {
        case 0:
            $class = 'm_error';
            break;
        case 1:
            $class = 'm_success';
            break;
        case 2:
        default:
            $class = 'm_info';
            break;
    }
    printf('<div class="%s">%s</div>',
            $class,
            $message);
}
function message_success($success, $message)
{
    return success_message($success, $message);
}
//

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
//provides $af_version
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

//Load User class
$USER = new user($DB);
$USER->setSalt(CRYPT_SALT);
$USER->reLogin();
$loginError = '';
if(!empty($_POST['usr_username']) && !empty($_POST['usr_password']) && !$USER->loggedIn()) {
    try {
        $USER->login($_POST['usr_username'], $_POST['usr_password']);
    } catch(Exception $e) {
        switch($e->getMessage()) {
            case 'login: nothing found':
                $loginError = 'Benutzername und/oder Passwort falsch!';
                break;
            case 'login: not active':
                $loginError = 'Ihr Benutzerkonto wurde noch nicht aktiviert!';
                break;
            default:
                $loginError = 'Fehler: '.$e->getMessage();
        }
    }
}
if(isset($_POST['usr_logout']) && $USER->loggedIn()) {
    $USER->logout();
}
//--

//List of Modules
$defModules = array(
    'Inhalte'   => array(
        'pages'             => array(
            'name'          => 'Seiten verwalten',
            'file'          => 'pages.mod.php',
            'description'   => 'Die einzelnen Seiten, deren Anordnung und zugehörigen Dateien bearbeiten.',
            'menuEntry'     => true
        ),
        'edit_pages'        => array(
            'name'          => 'Seite bearbeiten',
            'file'          => 'edit_pages.mod.php',
            'description'   => 'Einzelne Seite bearbeiten.',
            'menuEntry'     => false
        ),
        'filemanager'        => array(
            'name'          => 'Dateimanager',
            'file'          => 'filemanager.mod.php',
            'description'   => 'Dateien einer Seite verwalten.',
            'menuEntry'     => false
        ),
        'news'      => array(
            'name'          => 'News',
            'file'          => 'news.mod.php',
            'description'   => 'Aktuelle Nachrichten bearbeiten.',
            'menuEntry'     => false
        )
    ),
    'Layout'    => array(
        'template'  => array(
            'name'          => 'Template',
            'file'          => 'template.mod.php',
            'description'   => 'Das Aussehen der Website festlegen.',
            'menuEntry'     => true
        )
    ),
    'Benutzer'  => array(
        'members'   => array(
            'name'          => 'Mitglieder',
            'file'          => 'members.mod.php',
            'description'   => 'Neue Mitglieder hinzufügen, deren Zugriffsrechte ändern und vorhandene Mitglieder bearbeiten und löschen.',
            'menuEntry'     => true
        ),
        'rights'    => array(
            'name'          => 'Rechte',
            'file'          => 'rights.mod.php',
            'description'   => 'Die Rechte der Benutzer und Mitglieder festlegen.',
            'menuEntry'     => false
        ),
        'change_pass'    => array(
            'name'          => 'Passwort ändern',
            'file'          => 'password.mod.php',
            'description'   => 'Hier können Sie Ihr persönliches Passwort ändern.',
            'menuEntry'     => true
        )
    ),
    'System'    => array(
        'settings'  => array(
            'name'          => 'Einstellungen',
            'file'          => 'settings.mod.php',
            'description'   => 'Allgemeine Einstellungen der Website bearbeiten.',
            'menuEntry'     => false
        ),
        '404'    => array(
            'name'          => '404-Fehler',
            'file'          => '404.mod.php',
            'description'   => 'Aufgerufene, aber nicht gefundene Seiten und deren Referrer anzeigen.',
            'menuEntry'     => true
        ),
        'errors'    => array(
            'name'          => 'Fehlermeldungen',
            'file'          => 'errors.mod.php',
            'description'   => 'Fehlermeldungen von PHP und des StrigelCMS analysieren und Fehler beseitigen.',
            'menuEntry'     => true
        ),
        'phpinfo'    => array(
            'name'          => 'PHP-Info',
            'file'          => 'phpinfo.mod.php',
            'description'   => 'Informationen über die installierte PHP Version und deren Erweiterungen anzeigen.',
            'menuEntry'     => true
        ),
        'backup'    => array(
            'name'          => 'DB-Backup',
            'file'          => 'backup.mod.php',
            'description'   => 'Datenbank Backups erstellen.',
            'menuEntry'     => true
        )
    )
);
//get direct access to all modules
$defModules_flat = array();
foreach($defModules AS $module_group_name => $module_group) {
    foreach($module_group AS $module_do => $module) {
        $defModules_flat[$module_do] = &$defModules[$module_group_name][$module_do];
    }
}

//pick the moduls the user has permission to access to
$modules = array();
foreach($defModules AS $module_group_name => $module_group){
    foreach($module_group AS $module_do => $module) {
        if(false !== $USER->hasRight('admin_'.$module_do)) {
            $modules[$module_group_name][$module_do] = $module;
        }
    }
}
//get direct access to user's modules
$modules_flat = array();
foreach($modules AS $module_group_name => $module_group) {
    foreach($module_group AS $module_do => $module) {
        $modules_flat[$module_do] = &$modules[$module_group_name][$module_do];
    }
}
//--



//
//Process modules
//

//Start output buffering
ob_start();
//--

//Load module
$print_overview = false;
$content_heading = '';

if(!empty($_GET['do']) && isset($modules_flat[$_GET['do']])) {
    if(file_exists('modules/'.$modules_flat[$_GET['do']]['file'])) {
        $content_heading = $modules_flat[$_GET['do']]['name'];
        include('modules/'.$modules_flat[$_GET['do']]['file']);
    } else {
        $content_heading = 'Übersicht';
        $print_overview = true;
    }
} else {
    $content_heading = 'Übersicht';
    $print_overview = true;
}
//--

//Print overview if no module
if($print_overview) {
    echo '<h2>Für Sie verfügbare Module</h2>'.LF;

    if(empty($modules)) {
        if(!$USER->loggedIn())
            message_success(0, 'Sie haben keinen Zugriff auf das Backend! Bitte rechts oben einloggen!');
        else
            message_success(0, 'Zur Zeit haben Sie auf keine Backend Funktion Zugriff! Bitte an den Administrator wenden!');
    }

    foreach($modules AS $group_name => $group) {
        if(empty($group)) continue;

        echo '<h3>'.$group_name.'</h3>'.LF;

        foreach($group AS $module_do => $module) {
            if(!$module['menuEntry']) continue;

            printf('<p><b><a href="%s">%s</a></b><br />'.LF,
                   make_link(0, 'do='.$module_do),
                   $module['name']);

            printf('%s</p>', isset($module['description'])
                             ? $module['description']
                             : 'Keine Beschreibung vorhanden');
        }
    }
}
//--


//
// OUTPUT
//

//Load template
$template = new template('template.tmp');
//--

//Set vars
$title = ($CONFIG->get('sys', 'website_name') ? 'SCMS Backend - '.$CONFIG->get('sys', 'website_name') : 'SCMS Backend - Version '.SCMS_VERSION);
$template->setVar('title', $title);
$template->setVar('heading', $title);
$template->setVar('heading_navigation', 'Navigation');
$template->setVar('heading_content', $content_heading);
//--

//Set additional headers
$addit_head = '<script type="text/javascript" src="media/script.js"></script>
<script type="text/javascript" src="media/tiny_mce/tiny_mce.js"></script>
<link rel="stylesheet" type="text/css" href="media/ffphp.css" />';
$template->setVar('addit_head', $addit_head);
//--

//Create and set menu (see definition of $modules)
$menu = '';
$menu .= '<ul>';
$menu .= '<li id="navHome"><a href="'.make_link(0).'">Übersicht</a></li>';
foreach($modules AS $group_name => $group) {
    $menu .= '<li>'.LF.$group_name;

    if(!empty($group)) {
        $menu .= '<ul>';

        foreach($group AS $module_do => $module) {
            if(!$module['menuEntry']) continue;

            $menu .= sprintf('<li>%s<a href="%s">%s</a></li>',
                             LF,
                             make_link(0, 'do='.$module_do),
                             $module['name']);
        }

        $menu .= '</ul>';
    }

    $menu .= '</li>';
}
$menu .= '</ul>';
if(empty($modules))
    $menu = '';

$template->setVar('navigation', $menu);
//--

//Insert login/logout form
if($USER->loggedIn()) {
    $usr_form = 'Hallo '.$USER->getName().'
            <input type="hidden" name="usr_logout" />
            <button type="submit">Logout</button>';
} else {
    $usr_form = ($loginError ? $loginError.' ' : '').
            '<input size="12" type="text" name="usr_username"/>
            <input size="12" type="password" name="usr_password" />
            <button type="submit">Login</button>';
}
$template->setVar('usr_form', $usr_form);
$template->setVar('usr_action', $_SERVER['PHP_SELF']);
//--

//Get and insert content into template
$content = ob_get_contents();
ob_end_clean();

$template->setVar('content', $content);
//--

//Output template
echo $template->getHTML();
//--
