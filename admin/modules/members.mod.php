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

echo '<h2>Neuen Benutzer anlegen</h2>'.LF;

$new_form = new ffphp(17, make_link(1));
$new_form->setLang('de');
$id_username = $new_form->addField('input_singleline', array('label'=>'Benutzername','id'=>'new_username'), true);
$id_name = $new_form->addField('input_singleline', array('label'=>'Name','id'=>'new_name'), true);
$id_email = $new_form->addField('input_singleline', array('label'=>'E-Mail','id'=>'new_email'), true, array('regex'=>
    '/([a-z\d]+)([\-]{1}[a-z\d]+)*([\.]{1}[a-z\d]+)*\@([a-z\d]+)([\-]{1}[a-z\d]+)*([\.]{1}[a-z\d]+)*\.([a-z]{2,6})/'));
$id_pass1 = $new_form->addField('input_singleline', array('label'=>'Passwort','id'=>'new_pass1','password'=>true), true);
$id_pass2 = $new_form->addField('input_singleline', array('label'=>'Passwort','id'=>'new_pass2','password'=>true), true);
$new_form->addField('button', array('text'=>'Benutzer hinzufügen','id'=>'new_submit','type'=>'submit'), true);

if($new_form->checkFormSent()) {
    if($new_form->checkFormComplete()) {
        if($_POST['new_pass1'] === $_POST['new_pass2']) {
            if(strlen($_POST['new_pass1']) >= 6) {
                $new_user = new user($DB);
				$new_user->setSalt(CRYPT_SALT);
                if($new_user->register($_POST['new_username'], $_POST['new_name'], $_POST['new_pass1'], $_POST['new_email'])) {
                    success_message(1, 'Der Benutzer wurde erfolgreich angelegt!');
                } else {
                    success_message(0, 'Fehler beim Anlegen des Benutzers! Existiert der Benutzername oder die E-Mail evt. bereits?');
                }
                $new_user = null;
            } else {
                $new_form->addError($id_pass1, 'Das Passwort muss mindestens 6 Zeichen lang sein!');
                $new_form->assignSelection();
            }
        } else {
            $new_form->addError($id_pass2, 'Die beiden Passwörter müssen übereinstimmen.');
            $new_form->assignSelection();
        }
    } else {
        $new_form->assignSelection();
    }
}

echo $new_form->getHTML();

echo '<h2>Benutzer verwalten</h2>'.LF;

if(!empty($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if($id >= 0) {
        switch($_GET['action']) {
            case 'activate':
                $DB->execute("UPDATE `".DB_PRE."sys_user` SET `active` = `active` XOR 1 WHERE `id` = ".$id." LIMIT 1");
                break;
            
            case 'delete':
                success_message(2, 'Wollen Sie \''.$_GET['username'].'\' wirklich löschen? <a href="'.make_link(2, 'action=delete2').'">Löschen</a>!');
                break;
            
            case 'delete2':
                if($DB->execute("DELETE FROM `".DB_PRE."sys_user` WHERE `id` = ".$id." LIMIT 1")) {
                    success_message(1, '\''.$_GET['username'].'\' erfolgreich gelöscht!');
                } else {
                    success_message(0, 'Fehler beim Löschen von \''.$_GET['username'].'\'!');
                }
                break;
        }
    }
}

$error_count = $DB->qquery("SELECT count(`id`) AS `count` FROM `".DB_PRE."sys_user`")->count;

if(!empty($_GET['limit'])) {
    if($error_count > $_GET['limit']) {
        $query_limit = $_GET['limit'];
    } else {
        $query_limit = 0;
    }
} else {
    $query_limit = 0;
}

$q = $DB->query("SELECT `id`, `username`, `name`, `active`, `email` FROM `".DB_PRE."sys_user` ORDER BY `username` ASC LIMIT ".$query_limit.", 10");

echo '<table>'.LF;
echo '<tr>'.LF;
echo '<th>Benutzername</th>'.LF;
echo '<th>Name</th>'.LF;
echo '<th>Email</th>'.LF;
echo '<th>Aktionen</th>'.LF; //(Rechte ändern) (de/aktivieren) (löschen)
echo '</tr>'.LF;

while(false !== ($row = $q->fetch())) {
    echo '<tr>'.LF;
    echo '<td>'.LF;
    echo $row->username;
    echo '</td>'.LF;
    echo '<td>'.LF;
    echo $row->name;
    echo '</td>'.LF;
    echo '<td>'.LF;
    echo $row->email;
    echo '</td>'.LF;
    echo '<td>'.LF;
    echo '(<a href="'.make_link(0, 'do=rights', 'id='.$row->id).'">Rechte ändern</a>)'.LF;
    if($row->active) {
        echo '(<a href="'.make_link(1, 'action=activate', 'id='.$row->id).'">deaktivieren</a>)'.LF;
    } else {
        echo '(<a href="'.make_link(1, 'action=activate', 'id='.$row->id).'">aktivieren</a>)'.LF;
    }
    echo '(<a href="'.make_link(1, 'action=delete', 'id='.$row->id, 'username='.$row->username).'">löschen</a>)'.LF;
    echo '</td>'.LF;
    echo '</tr>'.LF;
}

echo '</table>'.LF;

echo '<p>'.LF;

if($query_limit == 0) {
    echo '« Vorherige Seite ';
} else {
    printf('<a href="%s">« Vorherige Seite</a> ',
            make_link(1, 'limit='. ((($query_limit - 10) < 0)
            ? 0 : ($query_limit - 10))));
}
echo '|';
if(($query_limit + 10) < $error_count) {
    printf(' <a href="%s">Nächste Seite »</a> ',
            make_link(1, 'limit='.($query_limit + 10)));
} else {
    echo ' Nächste Seite »';
}

echo '</p>'.LF;

?>