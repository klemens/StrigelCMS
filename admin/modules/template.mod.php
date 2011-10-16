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

//Prevent direct access
if(!defined('SCMS')) {
    header('HTTP/1.1 403 Forbidden');
    die('Access denied.');
}
//--

if(!empty($_GET['set'])) {
    if($CONFIG->set('sys', 'current_template', $_GET['set'])) {
        success_message(1, 'Neues Standard Template festgelegt.');
    } else {
        success_message(0, 'Fehler beim Festlegen des Standard Template.');
    }
}

echo '<h2>Verfügbare Templates</h2>'.LF;

$current_template = $CONFIG->get('sys', 'current_template');

$dir_template = '../'.DIR_TEMP;
$dir_handle = opendir($dir_template);

while(false !== ($dir_entry = readdir($dir_handle))) {
    if($dir_entry === '.' || $dir_entry === '..')
        continue;
    
    $name = null;
    $version = null;
    $screenshot = null;
    $author = null;
    $forversion = null;
    $notes = null;
    $licence = null;
    $licence_url = null;
    
    if(file_exists($dir_template.$dir_entry.'/info.xml')) {
        $xml = simplexml_load_file($dir_template.$dir_entry.'/info.xml');
        
        $name = $xml->name;
        $version = $xml->version;
        $screenshot = $xml->screenshot;
        $author = $xml->author;
        $forversion = $xml->forversion;
        $notes = $xml->notes;
        $licence = $xml->licence;
        $licence_url = $xml->licence['url'];
    }
    
    if(empty($name)) $name = $dir_entry;
    if(empty($version)) $version = false;
    if(empty($screenshot)) $screenshot = false;
    if(empty($author)) $author = 'Unbekannter Author';
    if(empty($forversion)) $forversion = SCMS_VERSION;
    if(empty($notes)) $notes = false;
    if(empty($licence)) $licence = 'k.A.';
    if(empty($licence_url)) $licence_url = false;
    
    echo '<div style="clear:right; margin-bottom: 15px;">'.LF;
    
    printf('<img style="%s" alt="Screenshot of %s Template" src="%s" />'.LF,
            'float: right; margin-bottom: 15px;',
            ($screenshot === false) ? 'Placeholder. Ignore!' : htmlspecialchars($name),
            ($screenshot === false) ? 'media/no_screenshot.png' :
                $dir_template.$dir_entry.'/'.$screenshot);
    
    printf('<h3>%s %s<small>(%s)</small></h3>'.LF,
            $name,
            $version ? $version.' ' : '',
            ($current_template === $dir_entry) ? 'Standard' :
                '<a href="'.make_link(1, 'set='.$dir_entry).'">als Standard</a>');
    
    printf('<p><small>von %s für StrigelCMS Version %s (Lizenz: '.
                '%s)</small></p><p>%s</p>'.LF,
            htmlspecialchars($author), $forversion,
            (false === $licence_url) ? $licence : '<a href="'.$licence_url.'">'.$licence.'</a>',
            (false === $notes) ? '' : '<br />'.htmlspecialchars($notes));
    
    echo '</div>'.LF;
}

?>