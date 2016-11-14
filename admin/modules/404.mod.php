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

$action = empty($_GET['action']) ? 'overview' : $_GET['action'];

switch($action) {
    case 'delete':
        $DB->execute("DELETE FROM `".DB_PRE."sys_404`"
                        . (!empty($_GET['site']) ? " WHERE `site` = '".$DB->escape($_GET['site'])."'" : ""));
    case 'overview':
        echo '<h2>Aufgaben</h2>'.LF;
        echo '<h3><a href="'.make_link(1, 'action=delete').'">Alle löschen</a></h3>'.LF;
        echo '<h2>Nicht gefundene Seiten</h2>'.LF;

        echo '<table>'.LF;
        echo '<tr><th>Aufrufe</th><th>Seite</th>';
        echo '</tr>'.LF;

        //If performance is too low, remove the subselect!
        $q = $DB->query("SELECT COUNT(id) AS `aufrufe`, `a`.`site`".
                         "FROM `".DB_PRE."sys_404` AS `a`
                         GROUP BY `a`.`site`
                         ORDER BY `aufrufe` DESC");

        if(!$q) {
            echo '</table>'.LF;
            message_success(0, 'Fehler beim Auslesen der Einträge!');
            return false;
        }
        if(0 == $q->num_rows()) {
            echo '</table>'.LF;
            message_success(1, 'Keine Einträge vorhanden!');
            return true;
        }

        while($row = $q->fetch()) {
            echo '<tr>';

            echo '<td>'.$row->aufrufe.'</td>';
            echo '<td><a href="'.make_link(1, 'action=detail',
                                              'site='.$row->site).
                 '">'.htmlspecialchars($row->site).'</a></td>';

            echo '</tr>'.LF;
        }

        $q = null;

        echo '</table>'.LF;
        break;

    case 'detail':
        echo '<h2>Aufgaben</h2>'.LF;
        echo '<h3><a href="'.make_link(1).'">Zurück</a></h3>'.LF;
        echo '<h3><a href="'.make_link(1, 'action=delete', 'site='.$_GET['site']).'">Diesen Fehler löschen</a></h3>'.LF;
        echo '<h2>Die häufigsten Referrer für "'.htmlspecialchars($_GET['site']).'"</h2>'.LF;

        $q = $DB->query("SELECT `referer`, COUNT(`id`) AS `aufrufe`
                         FROM `".DB_PRE."sys_404`
                         WHERE `site` = '".$DB->escape($_GET['site'])."'
                         GROUP BY `referer`
                         ORDER BY `aufrufe` DESC");

        if(!$q) {
            success_message(0, "Fehler beim Auslesen der Referrer.");
            return false;
        }
        if(0 == $q->num_rows()) {
            success_message(2, "Keine Einträge für diese Seite vorhanden.");
            return true;
        }

        echo '<table>'.LF;
        echo '<tr><th>Aufrufe</th><th>Referrer</th></tr>'.LF;

        while($row = $q->fetch()) {
            echo '<tr>';

            $referer = empty($row->referer) ? 'kein Referrer' : $row->referer;

            echo '<td>'.$row->aufrufe.'</td>';
            echo '<td>'.htmlspecialchars($referer).'</td>';

            echo '</tr>'.LF;
        }

        break;
}
