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

$move_arrow_action = false;
$move_arrow_id = false;
$_SESSION['admin_allowedIds'] = array();

$menu = new menu($DB, $CONFIG, null);
$root_id = $USER->getRightValue('admin_pages');
if(false === $root_id) {
    $root_id = -1;
} else if((false === $menu->getHrefById($root_id)) && ('0' !== $root_id)) {
    $root_id = -1;
} else if('0' === $root_id){
    $root_id = 0;
    $_SESSION['admin_allowedIds'][] = 0;
} else {
    $root_id = intval($root_id);
}

if(!empty($_GET['action'])) {
    switch($_GET['action']) {
        case 'delete':
            if(false === $menu->hasChild($_GET['id'])) {
                success_message(0, sprintf('Wollen Sie "%s" wirklich löschen? <a href="%s">Ja</a>!',
                                            $menu->getRealTitleById($_GET['id']),
                                            make_link(2, 'action=delete2')));
            } else if(true === $menu->hasChild($_GET['id'])) {
                success_message(0, sprintf('Sie können "%s" nicht löschen! Es existieren Unterseiten!',
                                            $menu->getRealTitleById($_GET['id'])));
            } else if(null === $menu->hasChild($_GET['id'])) {
                success_message(0, sprintf('Id "%s" wurde nicht gefunden!',
                                            htmlspecialchars($_GET['id'])));
            }
            break;

        case 'delete2':
            if($DB->execute(sprintf("DELETE FROM %ssys_content WHERE `id` = %s LIMIT 1",
                                        DB_PRE, intval($_GET['id'])))) {
                success_message(1, sprintf('"%s" erfolgreich gelöscht!',
                                            $menu->getRealTitleById($_GET['id'])));
            } else {
                success_message(0, sprintf('Eintrag mit id "%s" konnte nicht gelöscht werden!',
                                            htmlspecialchars($_GET['id'])));
            }
            $menu = null;
            $menu = new menu($DB, $CONFIG, null);
            break;

        case 'move':
            $move_arrow_action = 'move2';
            $move_arrow_id = $_GET['id'];
            success_message(2, 'Bitte das neue Elternelement für "'.
                                $menu->getRealTitleById($_GET['id']).'" auswählen! (»)');
            break;

        case 'move2':
            if($menu->moveNode((int)$_GET['id'], (int)$_GET['pid'])) {
                if(0 === (int)$_GET['pid']) {
                    success_message(1, sprintf('"%s" erfolgreich in die Hauptebene verschoben!',
                                                $menu->getRealTitleById($_GET['id'])));
                } else {
                    success_message(1, sprintf('"%s" erfolgreich unter "%s" verschoben!',
                                                $menu->getRealTitleById($_GET['id']),
                                                $menu->getRealTitleById($_GET['pid'])));
                }
            } else {
                success_message(0, 'Fehler beim Umhängen!');
            }
            break;

        case 'new':
            $move_arrow_action = 'new2';
            success_message(2, 'Bitte das Elternelement für die neue Seite auswählen!');
            break;

        case 'new2':
            header('Location: '.str_replace('&amp;', '&', make_link(0, 'do=edit_pages', 'action=new', 'id='.$_GET['pid'])));
            break;
    }
}


echo '<h2>Aufgaben</h2>'.LF;

echo '<h3><a href="'.make_link(1, 'action=new').'">Neue Seite hinzufügen</a></h3>'.LF;

echo '<h2>Seitenbaum</h2>'.LF;

if($move_arrow_action && ($root_id == 0)) {
    if($move_arrow_id)  {
        printf('<strong><a href="%s">»</a></strong> (Hauptebene)'.LF,
                make_link(1, 'action='.$move_arrow_action,
                            'pid=0', 'id='.$move_arrow_id));
    } else {
        printf('<strong><a href="%s">»</a></strong> (Hauptebene)'.LF,
                make_link(1, 'action='.$move_arrow_action, 'pid=0'));
    }
}
echo '<div class="pages_tree">'.LF;
admin_pages_show_tree($menu->getMenu($root_id), $move_arrow_action, $move_arrow_id);
echo '</div>'.LF;



function admin_pages_show_tree($array, $move_arrow_action = false, $move_arrow_id = false, $show_arrows = true)
{
    if(!is_array($array)) {
        return false;
    }

    echo "<ul>";

    foreach($array AS $entry) {
        $show_arrow_sub = true;
        echo "<li>".LF;

        if($move_arrow_action) {
            if(($entry['id'] == $move_arrow_id) || !$show_arrows) {
                echo '<strong>»</strong>'.SP;
                $show_arrow_sub = false;
            } else {
                if($move_arrow_id)  {
                    printf('<strong><a href="%s">»</a></strong>'.SP,
                            make_link(1, 'action='.$move_arrow_action,
                                        'pid='.$entry['id'], 'id='.$move_arrow_id));
                } else {
                    printf('<strong><a href="%s">»</a></strong>'.SP,
                            make_link(1, 'action='.$move_arrow_action, 'pid='.$entry['id']));
                }
            }
        }

        if($entry['active']) {
            echo $entry['real_title'].SP;
        } else {
            echo '<em>'.$entry['real_title'].'</em>'.SP;
        }

        if(!$move_arrow_action) {
            echo '<small class="pages_actions">';

            echo '<a href="'.make_link(1, 'action=move', 'id='.$entry['id']).'" class="linkMove" title="Seite umhängen"><img src="media/icons/move.png" alt="Seite umhängen" /></a>'.SP;
            echo '<a href="'.make_link(0, 'do=edit_pages', 'action=edit', 'id='.$entry['id']).'" class="linkEdit" title="Seite bearbeiten"><img src="media/icons/edit.png" alt="Seite bearbeiten" /></a>'.SP;
            echo '<a href="'.make_link(0, 'do=filemanager', 'id='.$entry['id']).'" class="linkFiles" title="Dateien verwalten"><img src="media/icons/folder.png" alt="Dateien verwalten" /></a>'.SP;
            echo '<a href="'.make_link(1, 'action=delete', 'id='.$entry['id']).'" class="linkDelete" title="Seite löschen"><img src="media/icons/delete.png" alt="Seite löschen" /></a>';

            echo '</small>';
        }

        //for module edit_page and filemanger
        $_SESSION['admin_allowedIds'][] = $entry['id'];

        if(!empty($entry['child'])) {
            admin_pages_show_tree($entry['child'], $move_arrow_action, $move_arrow_id, $show_arrow_sub);
        }

        echo "</li>";
    }

    echo "</ul>";
}

?>