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

if(!empty($_GET['load'])) {
    switch($_GET['load']) {
        case 'cms':
            $file_handle = fopen('../'.LOG_DIRECTORY.LOG_FILE, 'r+');
            if(!$file_handle) {
                success_message(0, 'CMS Logdatei konnte nicht geöffnet werden!');
            }
            
            $inserts = array();
            
            while(($current_line = fgets($file_handle)) !== false) {
                $time = trim(substr($current_line, 0, 25));
                $message = trim(substr($current_line, 28));
                
                $mysql_datetime = substr($time, 0, -6);
                
                $inserts[] = sprintf("('%s', '%s')",
                                        $DB->escape($mysql_datetime),
                                        $DB->escape($message));
            }
            
            if(empty($inserts)) {
                success_message(1, 'Keine neuen CMS Fehlermeldungen!');
                fclose($file_handle);
                break;
            }
            
            $query = sprintf("INSERT INTO `%ssys_errors` (`date`, `message`) VALUES %s",
                                    DB_PRE, implode(', ', $inserts));
            
            if($DB->execute($query)) {
                ftruncate($file_handle, 0);
                success_message(1, 'CMS Logdatei erfolgreich eingelesen!');
            } else {
                success_message(0, 'Fehler beim Einlesen der CMS Logdatei!');
            }
            fclose($file_handle);
            break;
        case 'php':
            $file_handle = fopen('../'.LOG_DIRECTORY.'php.log', 'r+');
            if(!$file_handle) {
                success_message(0, 'PHP Logdatei konnte nicht geöffnet werden!');
            }
            
            $lines = array();
            $count = 0;
            $last_real_insert = -1;
            
            while(($current_line = fgets($file_handle)) !== false) {
                if(substr($current_line, 0, 1) === '[') {
                    $lines[$count] = $current_line;
                    $last_real_insert = $count;
                    $count++;
                } else {
                    if($last_real_insert !== -1) {
                        $lines[$last_real_insert] .= $current_line;
                    }
                }
            }
            
            $inserts = array();
            
            foreach($lines AS $current_line) {
                $current_line = str_replace("\r\n", "\n", $current_line);
                
                $time = trim(substr($current_line, 1, 20));
                $message = trim(substr($current_line, 23));
                
                $time_search = array('Jan', 'Feb', 'Mar', 'Apr', 'May',
                                        'Jun', 'Jul', 'Aug', 'Sep',
                                        'Oct', 'Nov', 'Dec');
                $time_replace = array('01', '02', '03', '04', '05', '06',
                                        '07', '08', '09', '10', '11', '12');
                $mysql_datetime = str_replace($time_search, $time_replace, $time);
                
                $mysql_datetime = preg_replace('#([0-9]{2})-([0-9]{2})-([0-9]{4})#i',
                                                '\3-\2-\1', $mysql_datetime);
                
                $inserts[] = sprintf("('%s', '%s')",
                                        $DB->escape($mysql_datetime),
                                        $DB->escape($message));
            }
            
            if(empty($inserts)) {
                success_message(1, 'Keine neuen PHP Fehlermeldungen!');
                fclose($file_handle);
                break;
            }
            
            $query = sprintf("INSERT INTO `%ssys_errors` (`date`, `message`) VALUES %s",
                                    DB_PRE, implode(', ', $inserts));
            
            if($DB->execute($query)) {
                ftruncate($file_handle, 0);
                success_message(1, 'PHP Logdatei erfolgreich eingelesen!');
            } else {
                success_message(0, 'Fehler beim Einlesen der PHP Logdatei!');
            }
            fclose($file_handle);
            break;
    }
}

if(!empty($_POST['errors'])) {
    $query = sprintf('DELETE FROM `%ssys_errors` WHERE `id` IN (%s)',
                        DB_PRE, $DB->escape(implode(', ', $_POST['errors'])));
    
    if($DB->execute($query)) {
        success_message(1, 'Einträge erfolgreich gelöscht!');
    } else {
        success_message(0, 'Fehler beim Löschen der Einträge');
    }
}

if(isset($_POST['delete_all'])) {
    $query = sprintf('DELETE FROM `%ssys_errors`', DB_PRE);
    
    if($DB->execute($query)) {
        success_message(1, 'Alle Einträge erfolgreich gelöscht!');
    } else {
        success_message(0, 'Fehler beim Löschen aller Einträge');
    }
}

echo '<h2>Logdateien laden</h2>'.LF;

printf('<h3><a href="%s">%s</a></h3>'.LF.'<h3><a href="%s">%s</a></h3>',
        make_link(1, 'load=cms'),
        'CMS Logdatei laden',
        make_link(1, 'load=php'),
        'PHP Logdatei laden');

echo '<h2>Fehlermeldungen</h2>'.LF;

$error_count = $DB->qquery("SELECT count(`id`) AS `count` FROM `".DB_PRE."sys_errors`")->count;

if(!empty($_GET['limit'])) {
    if($error_count > $_GET['limit']) {
        $query_limit = $_GET['limit'];
    } else {
        $query_limit = 0;
    }
} else {
    $query_limit = 0;
}

$query = sprintf("SELECT DATE_FORMAT(`date`, '%%d.%%m.%%Y %%H:%%i') AS `datef`,
                    `id`, `date`, `message` FROM `%ssys_errors`
                    ORDER BY `date` DESC, `id` DESC LIMIT %d, 20",
                    DB_PRE, $DB->escape($query_limit));

$q = $DB->query($query);

if($q->num_rows() === 0) {
    success_message(1, 'Keine Fehlermeldungen! (evt. Logdateien neu laden)');
    return true;
}

echo '<form method="post" action="'.make_link(1, 'action=delete').'">'.LF;

echo '<table>'.LF;
echo '<tr>'.LF;
echo '<th></th>'.LF;
echo '<th>Zeit</th>'.LF;
echo '<th>Nachricht</th>'.LF;
echo '</tr>'.LF;

while(false !== ($row = $q->fetch())) {
    echo '<tr>'.LF;
    echo '<td>'.LF;
    echo '<input type="checkbox" name="errors[]" value="'.$row->id.'" />'.LF;
    echo '</td>'.LF;
    echo '<td>'.LF;
    echo $row->datef.LF;
    echo '</td>'.LF;
    echo '<td>'.LF;
    echo htmlspecialchars($row->message.LF);
    echo '</td>'.LF;
    echo '</tr>'.LF;
}

echo '</table>'.LF;

echo '<p><button type="submit">Markierte löschen</button></p>'.LF;

echo '<p><button name="delete_all" type="submit">Alle Fehlermeldungen löschen!</button></p>'.LF;

echo '</form>'.LF;


echo '<p>'.LF;

if($query_limit == 0) {
    echo '« Neuere Fehler ';
} else {
    printf('<a href="%s">« Neuere Fehler</a> ',
            make_link(1, 'limit='. ((($query_limit - 20) < 0)
            ? 0 : ($query_limit - 20))));
}
echo '|';
if(($query_limit + 20) < $error_count) {
    printf(' <a href="%s">Ältere Fehler »</a> ',
            make_link(1, 'limit='.($query_limit + 20)));
} else {
    echo ' Ältere Fehler »';
}

echo '</p>'.LF;

?>