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
//Thanks to Dustin - php.net comment
function getDirectoryTree($outerDir, $onlyDirs = false, $filters = array()) {
    $dirs = array_diff(scandir($outerDir), array_merge(array( ".", ".." ), $filters));
    $dir_array = array();
    $file_array = array();
    foreach( $dirs AS $d ) {
        if(is_dir($outerDir."/".$d)) {
            $dir_array[$d] = getDirectoryTree($outerDir."/".$d, $onlyDirs, $filters);
        } else if(!$onlyDirs) {
            $file_array[$d] = $d;
        }
    }
    return $dir_array+$file_array;
}

function printDirectoryTree(array $array, $id, $fileSizeCheck = '', $path = '') {
    $ret = '';
    $ret .= '<ul>'.LF;
    foreach($array AS $key => $value) {
        if(is_array($value) && !empty($value)) {
            $ret .= '<li class="listDir">'.LF;
            $ret .= '<input type="checkbox" disabled="disabled"/>'.LF;
            $ret .= '<img src="media/icons/'.getIcon('~folder').'" alt="folder" />'.LF;
            $ret .= '<strong>'.htmlspecialchars($key).'</strong>'.LF;
            $ret .= '<a class="linkEdit" title="Datei umbenennen" href="'.make_link(1, 'id='.$id, 'action=rename', 'file=d'.$path.'/'.$key).'"><img src="media/icons/edit.png" alt="Umbenennen" /></a>'.LF;
            $ret .= printDirectoryTree($value, $id, $fileSizeCheck, $path.'/'.$key);
        } else if(is_array($value) && empty($value)) {
            $ret .= '<li class="listDir">'.LF;
            $ret .= '<input type="checkbox" value="'.htmlspecialchars('d'.$path.'/'.$key).'" name="filesDelete[]" />'.LF;
            $ret .= '<img src="media/icons/'.getIcon('~folder').'" alt="folder" />'.LF;
            $ret .= '<strong>'.htmlspecialchars($key).'</strong>'.LF;
            $ret .= '<a class="linkEdit" title="Datei umbenennen" href="'.make_link(1, 'id='.$id, 'action=rename', 'file=d'.$path.'/'.$key).'"><img src="media/icons/edit.png" alt="Umbenennen" /></a>'.LF;
        } else {
            $ret .= '<li class="listFile">'.LF;
            $ret .= '<input type="checkbox" value="'.htmlspecialchars('f'.$path.'/'.$key).'" name="filesDelete[]" />'.LF;
            $ret .= '<img src="media/icons/'.getIcon(pathinfo($key, PATHINFO_EXTENSION)).'" alt="'.pathinfo($key, PATHINFO_EXTENSION).' file" />'.LF;
            $ret .= htmlspecialchars($key).LF;
            if($fileSizeCheck)
                $ret .= ' <small>('.convert(filesize($fileSizeCheck.'/'.$path.'/'.$key)).')</small>'.LF;
            $ret .= '<a class="linkEdit" title="Datei umbenennen" href="'.make_link(1, 'id='.$id, 'action=rename', 'file=f'.$path.'/'.$key).'"><img src="media/icons/edit.png" alt="Umbenennen" /></a>'.LF;
            $ret .= '<a class="linkMove" title="Datei verschieben" href="'.make_link(1, 'id='.$id, 'action=move', 'file=f'.$path.'/'.$key).'"><img src="media/icons/move_sw.png" alt="Verschieben" /></a>'.LF;
        }
        $ret .= '</li>'.LF;
    }
    $ret .= '</ul>'.LF;
    return $ret;
}

function printFolderList(array $array, $valueToSelect = '', $path = '', $depth = 0) {
    $ret = '';
    foreach($array AS $key => $value) {
        if($valueToSelect == 'd'.$path.'/'.$key)
            $ret .= '<option selected="selected" value="d'.$path.'/'.$key.'">'.str_repeat('&nbsp;|&nbsp;&nbsp;', $depth).$key.'</option>'.LF;
        else
            $ret .= '<option value="d'.$path.'/'.$key.'">'.str_repeat('&nbsp;|&nbsp;&nbsp;', $depth).$key.'</option>'.LF;
        if(!empty($value))
            $ret .= printFolderList($value, $valueToSelect, $path.'/'.$key, $depth+1);
    }
    return $ret;
}

function getIcon($extension = '') {
    $extension = trim(strtolower($extension));

    if(in_array($extension, array('mp3', 'wav', 'ogg', 'wma', 'flac', 'aac', 'rm', 'mka')))
        return 'audio.png';

    if(in_array($extension, array('pdf', 'xps', 'tex', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pps', 'ppsx', 'ods', 'odt', 'odp', 'rtf')))
        return 'document.png';

    if(in_array($extension, array('exe', 'cmd', 'bat', 'sh', 'dll', 'so')))
        return 'exe.png';

    if(in_array($extension, array('~folder')))
        return 'folder.png';

    if(in_array($extension, array('bmp', 'png', 'gif', 'jpg', 'tiff', 'ico', 'jpeg')))
        return 'image.png';

    if(in_array($extension, array('zip', '7z', 'rar', 'gz')))
        return 'pack.png';

    if(in_array($extension, array('txt')))
        return 'text.png';

    if(in_array($extension, array('mpg', 'mpeg', 'avi', 'mp4', 'wmv', 'rm', 'mkv', 'mov', 'hdmov', 'webm', 'flv', 'swf', 'divx')))
        return 'video.png';

    if(in_array($extension, array('html', 'htm', 'php', 'php3', 'php4', 'php5', 'php6', 'xml', 'css', 'asp', 'js', 'rhtml', 'rjs', 'shtml', 'shtm')))
        return 'web.png';

    if(in_array($extension, array('')))
        return 'unknown.png';

    return 'unknown.png';
}

function convert($size){
    $unit=array('B','KB','MB','GB','TB','PB');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),$i>1?2:0).' '.$unit[$i];
}
//functions END

if(isset($_GET['id'])) {
    $page_id = $_GET['id'];

    if($page_id == 0) {
        success_message(0, 'Sie haben keine Berechtigung, auf diese Dateien zuzugreifen!');
        return false;
    }

    if(!isset($_SESSION['admin_allowedIds'])) {
        success_message(0, 'Bitte erst einmal \'Seiten verwalten\' aufrufen!');
        return false;
    }
    if(!in_array($page_id, $_SESSION['admin_allowedIds'])) {
        success_message(0, 'Sie haben keine Berechtigung, auf diese Dateien zuzugreifen!');
        return false;
    }
} else {
    success_message(0, 'Sie können diese Seite nicht direkt aufrufen!');
    return false;
}

$folder = $DB->qquery("SELECT `files` FROM `".DB_PRE."sys_content` WHERE `id` = ".intval($page_id))->files;

if(empty($folder)) {
    success_message(2, 'Bitte zuerst bei den Seiteneinstellungen einen Ordner eintragen!');
    return;
}

if(!is_dir('../'.DIR_FILES.$folder)) {
    mkdir('../'.DIR_FILES.$folder);
    if(!is_dir('../'.DIR_FILES.$folder)) {
        success_message(0, 'Das Verzeichnis, um die Dateien zu speichern, konnte nicht erstellt werden!');
        return false;
    }
}

$showRenameForm = false;
$showMoveForm = false;
if(isset($_GET['action'])) {
    switch($_GET['action']) {
        case 'upload':
            if(!isset($_FILES['fileUpload'])) {
                success_message(0, 'Fehler beim Hochladen der Datei! Eventuell ist die Datei zu groß oder Sie müssen erst eine Datei auswählen!');
                break;
            }
            if($_FILES['fileUpload']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = !empty($_POST['pathUpload']) ? $_POST['pathUpload'] : '.';
                $uploadDir = str_replace('d/Rootverzeichnis', '.', $uploadDir);
                $uploadDir = str_replace('..', '', $uploadDir); //Prevent attacs!
                $uploadDir = '../'.DIR_FILES.$folder.'/'.$uploadDir;

                $uploadFile = $uploadDir.'/'.$_FILES['fileUpload']['name'];
                $messageFile = $_FILES['fileUpload']['name'];
                $count = 1;
                while(is_file($uploadFile)) {
                    $uploadFile = $uploadDir.'/'.pathinfo($_FILES['fileUpload']['name'], PATHINFO_FILENAME).
                                  ' ('.$count.').'.pathinfo($_FILES['fileUpload']['name'], PATHINFO_EXTENSION);
                    $messageFile = pathinfo($_FILES['fileUpload']['name'], PATHINFO_FILENAME).
                                  ' ('.($count++).').'.pathinfo($_FILES['fileUpload']['name'], PATHINFO_EXTENSION);
                }
                if(is_dir($uploadDir)) {
                    if(move_uploaded_file($_FILES['fileUpload']['tmp_name'], $uploadFile)) {
                        success_message(1, 'Die Datei "'.htmlspecialchars($messageFile).'" wurde erfolgreich hochgeladen!');
                    } else {
                        success_message(0, 'Fehler beim Kopieren der Datei!');
                    }
                }
            } else {
                switch($_FILES['fileUpload']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        success_message(0, 'Die hochgeladene Datei ist zu groß!');
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        success_message(0, 'Der Upload wurde abgebrochen!');
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        success_message(0, 'Sie müssen eine Datei zum Hochladen auswählen!');
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                    case UPLOAD_ERR_NO_TMP_DIR:
                    case UPLOAD_ERR_EXTENSION:
                        success_message(0, 'Es ist ein interner Fehler aufgetreten: '.$_FILES['fileUpload']['error']);
                        break;
                    default:
                        success_message(0, 'Es ist ein unbekannter Fehler beim Hochladen der Datei aufgetreten!');
                }
            }
            break;

        case 'delete':
            if(!empty($_POST['filesDelete'])) {
                foreach($_POST['filesDelete'] AS $file) {
                    $file = str_replace('..', '', $file); //Prevent attacs!
                    switch(substr($file, 0, 1)) {
                        case 'f':
                            $file = substr($file, 2);
                            if(@unlink('../'.DIR_FILES.$folder.'/'.$file)) {
                                success_message(1, 'Die Datei "'.htmlspecialchars($file).'" wurde erfolgreich gelöscht!');
                            } else {
                                success_message(0, 'Fehler beim Löschen der Datei "'.htmlspecialchars($file).'"!');
                            }
                            break;
                        case 'd':
                            $dir = substr($file, 2);
                            if(@rmdir('../'.DIR_FILES.$folder.'/'.$dir)) {
                                success_message(1, 'Der Ordner "'.htmlspecialchars($dir).'" wurde erfolgreich gelöscht!');
                            } else {
                                success_message(0, 'Fehler beim Löschen des Ordners "'.htmlspecialchars($dir).'"! Eventuell ist der Ordner nicht leer.');
                            }
                            break;
                    }
                }
            }
            break;

        case 'createFolder':
            if(!empty($_POST['folderName']) && !empty($_POST['pathFolder'])) {
                $folderParent = str_replace('d/Rootverzeichnis', '.', $_POST['pathFolder']);
                $folderParent = str_replace('..', '', $folderParent); //Prevent attacs!
                $folderParent = '../'.DIR_FILES.$folder.'/'.$folderParent;

                $folderNew = $_POST['folderName'];
                $folderNew = str_replace('/', '', $folderNew);

                $folderCreate = $folderParent.'/'.$folderNew;
                if(!is_dir($folderCreate)) {
                    if(@mkdir($folderCreate)) {
                        success_message(1, 'Der Ordner "'.htmlspecialchars($folderNew).'" wurde erfolgreich erstellt.');
                    } else {
                        success_message(0, 'Fehler beim Erstellen des Ordners "'.htmlspecialchars($folderNew).'"! Eventuell beinhaltet der Name unerlaubte Zeichen!');
                    }
                } else {
                    success_message(0, 'Der Ordner "'.htmlspecialchars($folderNew).'" existiert bereits!');
                }
            }
            break;

        case 'rename':
            $showRenameForm = true;
            break;

        case 'move':
            $showMoveForm = true;
            break;

        case 'doRename':
            $oldFileName = substr($_GET['file'], 2);
            $newFileName = pathinfo($oldFileName, PATHINFO_DIRNAME).'/'.str_replace('/', '', $_POST['renameNewName']);
            $oldFileName = '../'.DIR_FILES.$folder.'/'.str_replace('..', '', $oldFileName);
            $newFileName = '../'.DIR_FILES.$folder.'/'.str_replace('..', '', $newFileName);

            if(is_file($newFileName) || is_dir($newFileName)) {
                success_message(0, '"'.htmlspecialchars(basename(substr($_GET['file'], 2))).'" existiert bereits!');
                break;
            }

            if(rename($oldFileName, $newFileName)) {
                success_message(1, '"'.htmlspecialchars(basename(substr($_GET['file'], 2))).'" wurde erfolgreich in "'.htmlspecialchars($_POST['renameNewName']).'" umbenannt.');
            } else {
                success_message(0, 'Fehler beim Umbenennen von "'.htmlspecialchars(basename(substr($_GET['file'], 2))).'" in "'.htmlspecialchars($_POST['renameNewName']).'"!');
            }
            break;

        case 'doMove':
            $oldFileName = substr($_GET['file'], 2);
            $newFileName = str_replace('d/Rootverzeichnis', '.', $_POST['moveNewName']).'/'.basename($oldFileName);
            $messageInfo = substr($newFileName, 2);
            $oldFileName = '../'.DIR_FILES.$folder.'/'.str_replace('..', '', $oldFileName);
            $newFileName = '../'.DIR_FILES.$folder.'/'.str_replace('..', '', $newFileName);
            //echo $oldFileName.'<br />'.$newFileName;

            if(is_file($newFileName)) {
                success_message(0, '"'.htmlspecialchars(substr($_GET['file'], 2)).'" existiert bereits!');
                break;
            }

            if(rename($oldFileName, $newFileName)) {
                success_message(1, '"'.htmlspecialchars(substr($_GET['file'], 2)).'" wurde erfolgreich nach "'.htmlspecialchars($messageInfo).'" verschoben.');
            } else {
                success_message(0, 'Fehler beim Verschieben von "'.htmlspecialchars(substr($_GET['file'], 2)).'" nach "'.htmlspecialchars($messageInfo).'"!');
            }
            break;
    }
}

if($showRenameForm) {
    $folderfile = ('d' == substr($_GET['file'], 0, 1)) ? 'Ordner' : 'Datei';
    success_message(2, 'Bitte geben Sie den neuen Namen für "'.htmlspecialchars(substr($_GET['file'], 2)).'" ein:'.
    '<form method="post" action="'.make_link(1, 'id='.$page_id, 'action=doRename', 'file='.$_GET['file']).'">
    <div><input type="text" name="renameNewName" value="'.htmlspecialchars(basename(substr($_GET['file'], 2))).'" />
    <button name="doFolder">'.$folderfile.' umbenennen</button></div></form>'.LF);
} else if($showMoveForm) {
    success_message(2, 'Bitte geben Sie an, wohin "'.htmlspecialchars(substr($_GET['file'], 2)).'" verschoben werden soll:'.
    '<form method="post" action="'.make_link(1, 'id='.$page_id, 'action=doMove', 'file='.$_GET['file']).'">
    <div><select name="moveNewName">'.printFolderList(array('Rootverzeichnis' => getDirectoryTree('../'.DIR_FILES.$folder, true))).'</select>
    <button name="doFolder">Datei verschieben</button></div></form>'.LF);
}

echo '<h2>Ordner erstellen</h2>'.LF;
echo '<form method="post" action="'.make_link(1, 'id='.$page_id, 'action=createFolder').'">
<div><select name="pathFolder">'.printFolderList(array('Rootverzeichnis' => getDirectoryTree('../'.DIR_FILES.$folder, true))).'</select>
<input type="text" name="folderName" />
<button name="doFolder">Ordner erstellen</button></div></form>'.LF;

echo '<h2>Datei Hochladen</h2>'.LF;
echo '<form method="post" enctype="multipart/form-data" action="'.make_link(1, 'id='.$page_id, 'action=upload').'">
<div><select name="pathUpload">'.printFolderList(array('Rootverzeichnis' => getDirectoryTree('../'.DIR_FILES.$folder, true)), (isset($_POST['pathUpload']) ? $_POST['pathUpload'] : '')).'</select>
<input type="file" name="fileUpload" />
<button name="doUpload">Datei Hochladen</button></div></form>'.LF;

echo '<h2>Dateien verwalten</h2>'.LF;

echo '<form method="post" action="'.make_link(1, 'id='.$page_id, 'action=delete').'">'.LF;

echo '<div class="dirList">'.LF;
echo printDirectoryTree(getDirectoryTree('../'.DIR_FILES.$folder), $page_id, '../'.DIR_FILES.$folder);
echo '</div>'.LF;

echo '<p><button name="doDelete">Markierte löschen</button></p>'.LF;

echo '</form>';

?>