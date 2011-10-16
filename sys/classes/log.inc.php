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

function logit($part, $error_text)
{
    if(!$error_text) {
        logit("logit/no_part!", $part);
        die("logit: No part given! Call Admin: ".ADMIN_MAIL);
    }
    
    $file = DIR_WORK.LOG_DIRECTORY.LOG_FILE;
    $error_text = preg_replace("#(\n|\r|\n\r)+#", " ↵ ", $error_text);
    $error_text = preg_replace("#( +)#", " ", $error_text);
    
    if(!($file_handler = fopen($file, "ab")))
        die("ERROR: Could not open log file! Call Admin: ".ADMIN_MAIL);
    
    if(!fwrite($file_handler, date("c | ").$part.": ".$error_text."\n"))
        die("ERROR: Could not write data into log file! Call Admin: ".ADMIN_MAIL);
    
    fclose($file_handler);
    
    return true;
}
