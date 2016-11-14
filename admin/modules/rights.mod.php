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

//functions
function printMenuTree(array $array, $id = 0, $depth = 0) {
    $ret = '';
    foreach($array AS $entry) {
        $ret .= '<option value="'.$entry['id'].'"';
        if($entry['id'] === (string)$id)
            $ret .= ' selected="selected"';
        $ret .= '>'.str_repeat('&nbsp;|&nbsp;&nbsp;', $depth).$entry['real_title'].'</option>'.LF;
        if(!empty($entry['child']))
            $ret .= printMenuTree($entry['child'], $id, $depth+1);
    }
    return $ret;
}
//--

if(!isset($_GET['id'])) {
    success_message(2, 'Um die Berechtigungen eines Mitglieds zu bearbeiten, öffnen Sie \'Mitglieder\' im Menü!');
    return true;
}

$id = intval($_GET['id']);
if($id < 1) {
    success_message(0, 'Fehlerhafte ID!');
    return false;
}

$menu = new menu($DB, $CONFIG, null);

if(isset($_POST['save'])) {
    $rights_new = array();

	$post_rights = isset($_POST['rights']) ? $_POST['rights'] : array();

    foreach($post_rights AS $right) {
        if(isset($_POST['value_'.$right]) && '' !== $_POST['value_'.$right]) {
            $rights_new[] = '('.$id.', \''.$DB->escape($right).'\', \''.$DB->escape($_POST['value_'.$right]).'\')';
        } else {
            $rights_new[] = '('.$id.', \''.$DB->escape($right).'\', NULL)';;
        }
    }

    $DB->execute("DELETE FROM `".DB_PRE."sys_rights` WHERE `userID` = ".$id);
    $DB->execute("INSERT INTO `".DB_PRE."sys_rights`(`userID`, `right`, `value`) VALUES".implode(',', $rights_new));
}

echo '<h2>Benutzerrechte verwalten</h2>'.LF;

$q = $DB->query("SELECT `right`, `value` FROM `".DB_PRE."sys_rights` WHERE `userID` = ".$id);
$user_rights = array();
while($row = $q->fetch()) {
    $user_rights[$row->right] = (null !== $row->value) ? $row->value : '';
}
$q = null;

echo '<form method="post" action="'.make_link(2).'">'.LF;
echo '<table style="border-spacing: 0.7em">'.LF;
echo '<tr>'.LF;
echo '<th></th>'.LF;
echo '<th>Modul</th>'.LF;
echo '<th>Beschreibung</th>'.LF;
echo '<th>Einstellungen</th>'.LF;
echo '</tr>'.LF;

foreach($defModules_flat AS $module_do => $modul) {
    echo '<tr>'.LF;
    echo '<td align="right">'.LF;
    if(isset($user_rights['admin_'.$module_do])) {
        echo '<input id="check-'.$module_do.'" value="admin_'.$module_do.'" type="checkbox" name="rights[]" checked="checked"/>'.LF;
    } else {
        echo '<input id="check-'.$module_do.'" value="admin_'.$module_do.'" type="checkbox" name="rights[]" />'.LF;
    }
    echo '</td>'.LF;
    echo '<td><label for="check-'.$module_do.'">'.LF;
    echo $modul['name'];
    echo '</label></td>'.LF;
    echo '<td>'.LF;
    echo $modul['description'];
    echo '</td>'.LF;
    echo '<td>'.LF;
    if($module_do === 'pages')
        echo '<select name="value_admin_pages">'.printMenuTree(array(array('id' => '0', 'real_title' => 'Alle Seiten', 'child' => $menu->getMenu(0))), ((isset($user_rights['admin_'.$module_do]) && ('' !== $user_rights['admin_'.$module_do])) ? $user_rights['admin_'.$module_do] : '0')).'</select>';
    echo '</td>'.LF;
    echo '</tr>'.LF;
}

echo '</table>'.LF;
echo '<p><button name="save" value="1" type="submit">Speichern</button></p>'.LF;
echo '</form>'.LF;

?>
