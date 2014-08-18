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

$edit_form_data = array();
if((!empty($_GET['action'])) && (isset($_GET['id']))) {
    $edit_action = $_GET['action'];
    $edit_id = $_GET['id'];

    if(!isset($_SESSION['admin_allowedIds'])) {
        success_message(0, 'Bitte erst \'Seiten verwalten\' aufrufen!');
        return false;
    }
    if(!in_array($edit_id, $_SESSION['admin_allowedIds'])) {
        success_message(0, 'Sie haben keine Berechtigung, diese Seite zu bearbeiten!');
        return false;
    }

    $edit_set = true;
} else {
    $edit_set = false;
}

if(isset($_POST['page_submit'])) {
    if(!empty($_POST['page_name']) && !empty($_POST['page_link']) && !empty($_POST['page_content_type']) &&
       isset($_POST['page_id']) && isset($_POST['page_m_title']) && isset($_POST['page_include']) &&
       isset($_POST['page_description']) && isset($_POST['page_keywords']) && isset($_POST['page_css']) &&
       isset($_POST['page_js']) && isset($_POST['page_language']) && isset($_POST['page_html']) &&
       isset($_POST['page_file']) && isset($_POST['page_redirect']) && isset($_POST['page_type']) &&
       isset($_POST['page_m_sort'])) {
        if(!empty($_POST['page_'.$_POST['page_content_type']])) {
            $sets = array();

            $sets[] = '`date` = NOW()';
            $sets[] = '`title` = \''.$DB->escape($_POST['page_name']).'\'';
            $sets[] = '`link` = \''.$DB->escape($_POST['page_link']).'\'';
            $sets[] = '`files` = \''.$DB->escape($_POST['page_include']).'\'';
            $sets[] = '`robot_visibility` = '.(isset($_POST['page_robot_visibility']) ? 1 : 0);
            $sets[] = '`description` = \''.$DB->escape($_POST['page_description']).'\'';
            $sets[] = '`keywords` = \''.$DB->escape($_POST['page_keywords']).'\'';
            $sets[] = '`header_image` = \''.$DB->escape($_POST['page_header_image']).'\'';
            $sets[] = '`css` = \''.$DB->escape($_POST['page_css']).'\'';
            $sets[] = '`js` = \''.$DB->escape($_POST['page_js']).'\'';
            $sets[] = '`author` = \''.$DB->escape($_POST['page_author']).'\'';
            $sets[] = '`content` = \''.$DB->escape($_POST['page_html']).'\'';
            $sets[] = '`file` = \''.$DB->escape($_POST['page_file']).'\'';
            $sets[] = '`redirect` = \''.$DB->escape($_POST['page_redirect']).'\'';
            $sets[] = '`showEditor` = '.(isset($_POST['page_editor_set']) ? 1 : 0);
            $sets[] = '`content_type` = \''.(in_array($_POST['page_content_type'], array('html', 'file', 'redirect'))
                                            ? $DB->escape($_POST['page_content_type']) : 'html').'\'';
            $sets[] = '`lang` = \''.((5 === strlen($_POST['page_language']))
                                            ? $DB->escape($_POST['page_language']) : 'de-de').'\'';
            $sets[] = '`m_active` = '.(isset($_POST['page_m_active']) ? 1 : 0);
            $sets[] = '`m_title` = \''.$DB->escape((empty($_POST['page_m_title'])
                                            ? $_POST['page_name'] : $_POST['page_m_title'])).'\'';
            $sets[] = '`m_sort` = '.(empty($_POST['page_m_sort']) ? 10 : intval($_POST['page_m_sort']));

            $query_insert = "INSERT INTO `".DB_PRE."sys_content` SET %s";
            $query_update = "UPDATE `".DB_PRE."sys_content` SET %s WHERE `id`=%s";

            if('edit' === $_POST['page_type']) {
                $query = sprintf($query_update, implode(', ', $sets), intval($_POST['page_id']));
            } else if('new' === $_POST['page_type']) {
                $sets[] = '`m_pid` = '.(empty($_POST['page_id']) ? 0 : intval($_POST['page_id']));
                $query = sprintf($query_insert, implode(', ', $sets));
            }

            //Clean up (empty strings -> NULL)
            $query = str_replace("` = '',", "` = NULL,", $query);

            if($DB->execute($query)) {
                success_message(1, "Die Seite wurde erfolgreich gespeichert!");
                if('new' === $_POST['page_type']) {
                    $edit_action = 'edit';
                    $edit_id = $DB->insert_id();
                    $_SESSION['admin_allowedIds'][] = (string) $edit_id;
                }
                if(!empty($_POST['page_include'])) {
                    if(!is_dir('../'.DIR_FILES.$_POST['page_include']))
                        mkdir('../'.DIR_FILES.$_POST['page_include']);
                }
            } else {
                success_message(0, "Die Seite konnte nicht erfolgreich gespeichert werden! (s. DB-Log)");
            }
        } else {
            switch($_POST['page_content_type']) {
                case 'html': success_message(0, 'Sie müssen den Inhalt der Seite angeben!'); break;
                case 'file': success_message(0, 'Sie müssen die einzubindende Datei angeben!'); break;
                case 'redirect': success_message(0, 'Sie müssen die Seite, auf die weitergeleitet wird, angeben!'); break;
            }
        }
    } else {
        success_message(0, 'Sie müssen den Namen, Link, und den Typ angeben!');
    }
}

if($edit_set && ('edit' === $edit_action)) {
    $query = "SELECT `id`, `title` AS `name`, `link`, `content_type`, `file`, `redirect`,
                     `content` AS `html`, `description`, `keywords`, `css`, `js`, `author`,
                     `files` AS `include`, `lang` AS `language`, `m_title`, `m_active`, `m_sort`,
                     `robot_visibility`, `header_image`, `showEditor`
              FROM `".DB_PRE."sys_content`
              WHERE `id` = ".intval($edit_id);

    $result = $DB->qquery($query);

    if(!$result) {
        success_message(0, 'Seite existiert nicht oder Fehler beim Abruf der Seite. (s. DB-Log)');
        return 1;
    }

    $edit_form_data['name'] = $result->name;
    $edit_form_data['link'] = $result->link;
    $edit_form_data['m_active'] = $result->m_active;
    $edit_form_data['content_type'] = $result->content_type;
    $edit_form_data['page_editor_set'] = $result->showEditor;

    $edit_form_data['m_title'] = $result->m_title;
    $edit_form_data['m_sort'] = $result->m_sort;
    $edit_form_data['include'] = $result->include;
    $edit_form_data['robot_visibility'] = $result->robot_visibility;
    $edit_form_data['description'] = $result->description;
    $edit_form_data['keywords'] = $result->keywords;
    $edit_form_data['header_image'] = $result->header_image;
    $edit_form_data['css'] = $result->css;
    $edit_form_data['js'] = $result->js;
    $edit_form_data['language'] = $result->language;
    $edit_form_data['author'] = $result->author;

    $edit_form_data['type'] = 'edit';
    $edit_form_data['id'] = $edit_id;

    $edit_form_data['html'] = $result->html;
    $edit_form_data['file'] = $result->file;
    $edit_form_data['redirect'] = $result->redirect;
} else if($edit_set && ('new' === $edit_action)){
    $edit_form_data['name'] = '';
    $edit_form_data['link'] = '';
    $edit_form_data['m_active'] = 1;
    $edit_form_data['content_type'] = 'html';
    $edit_form_data['page_editor_set'] = 1;

    $edit_form_data['m_title'] = '';
    $edit_form_data['m_sort'] = '10';
    $edit_form_data['include'] = '';
    $edit_form_data['robot_visibility'] = 1;
    $edit_form_data['description'] = '';
    $edit_form_data['keywords'] = '';
    $edit_form_data['header_image'] = '';
    $edit_form_data['css'] = '';
    $edit_form_data['js'] = '';
    $edit_form_data['language'] = 'de-de';
    $edit_form_data['author'] = '';

    $edit_form_data['type'] = 'new';
    $edit_form_data['id'] = $edit_id;

    $edit_form_data['html'] = 'Text..';
    $edit_form_data['file'] = '';
    $edit_form_data['redirect'] = '';
} else {
    success_message(2, 'Um eine neue Seite hinzuzufügen oder zu bearbeiten, öffnen Sie \'Seiten verwalten\' im Menü!');
    return 1;
}





$edit_form = new ffphp(1, make_link(2, 'action='.$edit_action, 'id='.$edit_id), 'post', 0);

######
$edit_form->addFieldset('Allgemeine Einstellungen');

$edit_form->addField('input_singleline',
    array('id'    => 'page_name',
          'label' => 'Seitentitel',
          'value' => $edit_form_data['name']));

$edit_form->addField('input_singleline',
    array('id'    => 'page_link',
          'label' => 'Link',
          'value' => $edit_form_data['link']));

$edit_form->addField('select_check',
    array('label' => '',
          'name'  => 'page_m_active',
          'elements' =>
                array(
                    array('label' => 'Im Menü anzeigen',
                          'value' => '1',
                          'flag' => ($edit_form_data['m_active'] ? 'checked' : null)))));

//----
$elements =  array( array('title' => 'Standardseite',
                          'value' => 'html'),
                    array('title' => 'Modul',
                          'value' => 'file'),
                    array('title' => 'Weiterleitung',
                          'value' => 'redirect'));
foreach($elements AS &$element) {
    if($edit_form_data['content_type'] === $element['value']) {
        $element['flag'] = 'selected';
    }
} unset($element);
//----
$edit_form->addField('select_list',
    array('id' => 'page_content_type',
          'label' => 'Typ',
          'elements' => $elements));

$edit_form->addField('select_check',
    array('label' => '',
          'name'  => 'page_adv_set',
          'elements' =>
                array(
                    array('label' => 'Erweiterte Einstellungen anzeigen',
                          'value' => '1',
                          'flag' => (!empty($_POST['page_adv_set']) ? 'checked' : null)))));


######
$edit_form->addFieldset('Erweiterte Einstellungen', 'page_adv_set');

$edit_form->addField('input_singleline',
    array('id'    => 'page_m_title',
          'label' => 'Menütitel',
          'value' => $edit_form_data['m_title']));
$edit_form->addField('input_singleline',
    array('id'    => 'page_m_sort',
          'label' => 'Sortierung',
          'value' => $edit_form_data['m_sort']));
$edit_form->addField('input_singleline',
    array('id'    => 'page_include',
          'label' => 'Include-Ordner',
          'value' => $edit_form_data['include']));

$edit_form->addField('select_check',
    array('label' => '',
          'name'  => 'page_robot_visibility',
          'elements' =>
                array(
                    array('label' => 'Sichtbar für Suchmaschinen',
                          'value' => '1',
                          'flag' => ($edit_form_data['robot_visibility'] ? 'checked' : null)))));
$edit_form->addField('input_multiline',
    array('id'    => 'page_description',
          'label' => '(Meta-)Beschreibung',
          'text'  => $edit_form_data['description']));
$edit_form->addField('input_singleline',
    array('id'    => 'page_keywords',
          'label' => '(Meta-)Keywörter',
          'value' => $edit_form_data['keywords']));

//----
$elements = array();
$elements[] = array('title' => 'Standardbild', 'value' => '');
foreach(scandir('../'.DIR_HEADER) AS $file) {
    $file_info = pathinfo($file);
    $file_ext = isset($file_info['extension']) ? $file_info['extension'] : '';
    if(($file_ext === 'jpg') ||($file_ext === 'png') ||($file_ext === 'jpeg')) {
        if($edit_form_data['header_image'] === $file) {
            $elements[] = array('title' => $file, 'value' => $file, 'flag' => 'selected');
        } else {
            $elements[] = array('title' => $file, 'value' => $file);
        }
    }
}
//----
$edit_form->addField('select_list',
    array('id' => 'page_header_image',
          'label' => 'Header Bild',
          'elements' => $elements));

$edit_form->addField('input_multiline',
    array('id'    => 'page_css',
          'label' => 'CSS',
          'text'  => $edit_form_data['css']));
$edit_form->addField('input_multiline',
    array('id'    => 'page_js',
          'label' => 'JavaScript',
          'text'  => $edit_form_data['js']));

$edit_form->addField('input_singleline',
    array('id'    => 'page_language',
          'label' => 'Sprache',
          'value' => $edit_form_data['language']));
$edit_form->addField('input_singleline',
    array('id'    => 'page_author',
          'label' => 'Author',
          'value' => $edit_form_data['author']));



######
$edit_form->addFieldset('Standardseite', 'page_content_type0');
$edit_form->addField('select_check',
    array('label' => '',
          'name'  => 'page_editor_set',
          'elements' =>
                array(
                    array('label' => 'Editor anzeigen',
                          'value' => '0',
                          'flag' => ($edit_form_data['page_editor_set']) ? 'checked' : null))));

$edit_form->addField('input_multiline',
    array('id'    => 'page_html',
          'label' => 'Inhalt',
          'text'  => $edit_form_data['html']));


######
$edit_form->addFieldset('Modul', 'page_content_type1');
$edit_form->addField('input_singleline',
    array('id'    => 'page_file',
          'label' => 'Einzubindendes Modul',
          'value' => $edit_form_data['file']));



######
$edit_form->addFieldset('Weiterleitung', 'page_content_type2');
$edit_form->addField('input_singleline',
    array('id'    => 'page_redirect',
          'label' => 'Seite, auf die weitergeleitet wird',
          'value' => $edit_form_data['redirect']));

######
$edit_form->addField('mixed_hidden',
    array('id'    => 'page_type',
          'value' => $edit_form_data['type']));

$edit_form->addField('mixed_hidden',
    array('id'    => 'page_id',
          'value' => $edit_form_data['id']));

######
$edit_form->addField('button',
    array('id'    => 'page_submit',
          'type'  => 'submit',
          'text'  => 'Speichern'));



$css = '../'.DIR_TEMP.$CONFIG->get('sys', 'current_template').'/'.'style.css';

if(!empty($edit_form_data['include'])) {
    $picture_path = '../'.DIR_FILES.$edit_form_data['include'].'/';
    $replace_picture_path = 1;
} else {
    $picture_path = '{file_path}';
    $replace_picture_path = 0;
}

echo '<script type="text/javascript">
/* <![CDATA[ */
var page_html_link_replace_do = false;
if('.$replace_picture_path.') {
    page_html_link_replace_do = true;
    var page_html_link_search = "{file_path}";
    var page_html_link_replace = "'.$picture_path.'";
}
tinyMCE.init({
    mode : "none",
    theme : "advanced",
    skin : "o2k7",
    skin_variant : "black",
    entities : "38,amp,34,quot,60,lt,62,gt",
    language : "de",
    plugins : "inlinepopups,paste,print,safari,table,xhtmlxtras",
    theme_advanced_toolbar_location : "top",
    theme_advanced_toolbar_align : "left",
    theme_advanced_statusbar_location : "bottom",
    theme_advanced_resizing : true,
    theme_advanced_buttons1 : "paste,copy,cut,pasteword,pastetext,|,undo,redo,|,link,unlink,anchor,image,|,tablecontrols,|,visualaid,cleanup,removeformat,|,attribs,code,print",
    theme_advanced_buttons2 : "formatselect,bold,italic,underline,|,justifyleft,justifycenter,justifyright,justifyfull,|,sub,sup,|,bullist,numlist,|,outdent,indent,blockquote,|,hr,charmap,|,cite,abbr,acronym,del,ins,|,forecolor",
    theme_advanced_buttons3 : "",
    paste_auto_cleanup_on_paste: true,
    external_image_list_url : "ajax.php?get=tinymce_image_list&include='.$edit_form_data['include'].'",
    content_css: "../'.DIR_TEMP.$CONFIG->get('sys', 'current_template').'/tinymce.css, ajax.php?get=tinymce_content_css&site='.$edit_form_data['link'].'"
});
/* ]]> */
</script>
';

echo $edit_form->getHTML();

?>