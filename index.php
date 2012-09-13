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


//Protect the rest of the files
define('SCMS', 1);
//

//Load basic settings
if(!file_exists('settings.php') && is_dir('./install/')) {
    header("Location: install");
    die;
} else {
    require_once('settings.php');
}
//--

//Set timezone
date_default_timezone_set(TIMEZONE);
//--

//PHP - error settings
error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_DIRECTORY.'php.log');
//--

//Working Directory (mainly for log function)
define('DIR_WORK', str_replace('\\', '/', getcwd()).'/');
//--

//Measure runtime
$timeStart = microtime(true);
//--

//Session
session_start();
//--

//Header
header('Content-Type: text/html; charset=UTF-8');
//--

//MAINTENANCE MODE?
if(MAINTENANCE) {
    die(MAINTENANCE);
}
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

//Load functions
require_once(DIR_FUNC . 'all_functions.inc.php');
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

//Load Server and Parameters from URL
$URL = new url($CONFIG);

$startpage = $CONFIG->get('sys', 'startpage');
if(!$startpage) {
    logit('main/instantiate_url', 'No Startpage could be found');
    die('No Startpage defined!');
}

$URL->setStartpage($startpage);
$URL->setModRewrite($CONFIG->get('sys', 'force_mod_rewrite'));

define('SCRIPT', $URL->getServer()); //deprecated
define('SERVER', $URL->getServer());
//--

//Correct bad URLs
$last_char = substr($_SERVER['REQUEST_URI'], -1);
$last_4_chars = substr($_SERVER['REQUEST_URI'], -5);
$count_url_parameters = count($URL->getParameter());

if(($last_char != '/' AND $last_4_chars != '.html') OR
   ($last_char == '/' AND $count_url_parameters > 1) OR
   ($last_4_chars == '.html' AND $count_url_parameters <= 1)) {
    header('Location: '.$URL->makeLink($URL->getParameter()));
    header('HTTP/1.1 301 Moved Permanently');
    exit;
}

unset($last_char, $last_4_chars, $count_url_parameters);
//--

//Load plugins and trigger EVT_INIT
$PLUGINS = new PluginSystem;
$PLUGINS->LoadPlugins(DIR_PLUGIN, SERVER);

$event = new ContainerEvent($DB, $CONFIG, $URL);
$event->SetType(EVT_START);
$PLUGINS->TriggerEvent($event);
//--

//Load the menu and the content
$MENU = new menu($DB, $CONFIG, $URL);
$CONTENT = new content($DB, $CONFIG, $URL);
//--

//Run or include content
$head_title = '';
$include_file = false;
$content_type = $CONTENT->loadSite($URL->getComponent());

if(false === $content_type) {
    //Trigger EVT_SITE to find out if a plugin can provide the requested site
    $event = new SiteEvent($URL->getComponent());
    $event->SetType(EVT_SITE);
    $PLUGINS->TriggerEvent($event);

    $eventFile = $event->GetFile();
    if(!empty($eventFile) && is_file($eventFile)) {
        $content_type = 'file';
        $include_file = $eventFile;
        $head_title = $event->GetTitle();
    } else {
        header('HTTP/1.0 404 Not Found');
        define('ERROR404', true);
        $content_type = $CONTENT->loadSite('404');
    }
}

if($content_type == 'file') {
    //Use output buffering for simpler handling
    ob_start();

    if(false === $include_file)
        $include_file = DIR_INCL.$CONTENT->getInformation('file');

    if(file_exists($include_file)) {
        include_once($include_file);
    } else {
        logit('main/include_content', 'Requested File ('.$include_file.') doesnt exist!');
        echo 'Requested site could not be found!';
    }

    $html_content = ob_get_contents();
    ob_end_clean();
} else {
    $html_content = $CONTENT->getParsedContent();
}
//--

//Trigger EVT_CONTENT
$event = new TextModifyEvent($html_content);
$event->SetType(EVT_CONTENT);
$PLUGINS->TriggerEvent($event);
$html_content = $event->GetText();
//--

//Trigger EVT_MENU
$event = new MenuEvent($MENU);
$event->SetType(EVT_MENU);
$PLUGINS->TriggerEvent($event);
//--

//Select template
$template = $CONFIG->get('sys', 'current_template');
if(empty($template) OR !file_exists(DIR_TEMP.$template.'/'.'index.inc.html')) {
    logit('main/theme', 'No/wrong template');
    //Remove the ending /
    $template = substr(TEMP_DEF, 0, -1);
}
//--

//Initiate Template
$tmp = new template(DIR_TEMP.$template.'/'.'index.inc.html');
//--

//Trigger EVT_TEMPLATE
$event = new TemplateEvent($tmp);
$event->SetType(EVT_TEMPLATE);
$PLUGINS->TriggerEvent($event);
//--

//Add Template path
$tmp->setVar('tmp_path', SERVER.DIR_TEMP.$template.'/');
$tmp->setVar('root_path', SERVER);
//--

//Add Headers
$head = '';

if($CONTENT->isSetInformation('robot_visibility') && !$CONTENT->getInformation('robot_visibility')) {
    $head .= '<meta name="robots" content="noindex,follow" />'.LF;
}
if($CONTENT->getInformation('author')) {
    $head .= '<meta name="author" content="'.$CONTENT->getInformation('author').'" />'.LF;
}
if($CONTENT->getInformation('lang')) {
    $head .= '<meta http-equiv ="content-language" content="'.$CONTENT->getInformation('lang').'" />'.LF;
}

$meta_description = $CONTENT->getInformation('description');
$head .= '<meta name="description" content="'.(empty($meta_description) ?
         $CONFIG->get('sys', 'default_description') : $meta_description).'" />'.LF;

$meta_keywords = $CONTENT->getInformation('keywords');
$head .= '<meta name="keywords" content="'.(empty($meta_keywords) ?
         $CONFIG->get('sys', 'default_keywords') : $meta_keywords).'" />'.LF;

$head_css = $CONTENT->getParsedCss() ? $CONTENT->getParsedCss().LF : '';
if($CONTENT->getInformation('header_image') && file_exists(DIR_HEADER.$CONTENT->getInformation('header_image'))) {
    $head_css_image_size = getimagesize(DIR_HEADER.$CONTENT->getInformation('header_image'));
    $head_css .= 'div#header-image {'.LF.'background-image:url('.SCRIPT.DIR_HEADER.$CONTENT->getInformation('header_image').');'.
                 LF.'height: '.$head_css_image_size[1].'px;'.LF.'}';
}

$head .= empty($head_css) ? '' : '<style type="text/css">'.LF.$head_css.LF.'</style>'.LF;

$head .= $CONTENT->getParsedJs() ? $CONTENT->getParsedJs() : '';

$event = new TextAddEvent($head);
$event->SetType(EVT_HTML_HEAD);
$PLUGINS->TriggerEvent($event);
$head = $event->GetText();

$tmp->setVar('add_head', trim($head).(empty($head) ? '' : LF));
//--

//Add Title
if(empty($head_title))
    $head_title = $CONTENT->getInformation('title');
$tmp->setVar('website_title', $head_title.
                              ' - '.
                              ($CONFIG->get('sys', 'website_name') !== false ?
                              $CONFIG->get('sys', 'website_name') : 'StrigelCMS'));
//--

//Add Menu
$menu_path = $MENU->getPath($URL->getComponent(), ' &gt; ', '<a href="%2$s">%1$s</a>',
                            '<strong>%1$s</strong>', $CONFIG->get('sys', 'breadcrumb_full_path'));

if(defined('ERROR404') OR empty($menu_path)) {
	$menu_path = '<strong>'.$head_title.'</strong>';
}

$tmp->setVar('menu_path', $menu_path);

$html_menu = $MENU->getMenuHtml('path', '<a href="%2$s">%1$s</a>', $URL->getComponent());
$tmp->setVar('menu', $html_menu);
//--

//Add generation time and version
$tmp->setVar('scms_version', SCMS_VERSION);
$tmp->setVar('gen_time', round((microtime(true)-$timeStart), 4));
$tmp->setVar('db_query', $DB->get_count()-1);
$tmp->setVar('db_time', round(100*$DB->get_time()/$timeStart, 1));
//--

//Add the final content
$tmp->setVar('content', $html_content);
//--

//Add additional vars
$tmp->setVar('link_contact', $URL->makeLink('kontakt'));
$tmp->setVar('link_impress', $URL->makeLink('impressum'));
$tmp->setVar('link_scms', 'http://klemens.schoelhorn.eu/scms/');
//--

//Trigger EVT_HTML
$event = new TextModifyEvent($tmp->getHTML(true));
$event->SetType(EVT_HTML);
$PLUGINS->TriggerEvent($event);
$html = $event->GetText();
//--

//Send the complete html to the user
echo $html;
//--

//Trigger EVT_EXIT
$event = new NullEvent;
$event->SetType(EVT_EXIT);
$PLUGINS->TriggerEvent($event);
//--
