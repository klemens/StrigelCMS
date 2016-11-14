<?php header("Content-Type: text/html; charset=UTF-8");
?><!DOCTYPE html>
<html>
<head>
    <title>StrigelCMS Installation</title>
    <style type="text/css">
    /* <![CDATA[ */
    body {
        font-family: Arial, Verdana, sans-serif;
        background-color: #FBFBEF;
    }
    #example_form_message {
        color: orange;
        font-weight: bold;
    }
    #container {
        max-width: 850px;
        margin: auto;
        padding: 10px;
        border: 1px solid #BBBBBB;
        box-shadow: 0 0 5px #888888;
        background-color: #FFFFFF;
    }
    /* ]]> */
    </style>
    <link rel="stylesheet" type="text/css" href="ffPhp/css/all.css" />
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
    <script type="text/javascript" src="ffPhp/js/ffPhp.js"></script>
</head>
<body onload="ffPhp.Init();">
<div id="container">
<h1>StrigelCMS Installation</h1>
<?php

define('SCMS', 1);
function logit($part, $error_text) { }

require_once 'ffPhp/lib/ffPhp/ffPhp.php';
require_once '../sys/classes/template.inc.php';
require_once '../sys/classes/mysql.inc.php';
require_once '../sys/classes/user.inc.php';

$form = new ffPhp;

$form->Add(new ffFieldset('Datenbank'));
$dbServer = $form->Add(new ffInput('Server'));
$dbUser = $form->Add(new ffInput('Benutzer'));
$dbPassword = $form->Add(new ffInput('Passwort'));
$dbDatabase = $form->Add(new ffInput('Datenbank'));
$dbPrefix = $form->Add(new ffInput('Präfix'));

$form->Add(new ffFieldset('Administrator'));
$adminUser = $form->Add(new ffInput('Benutzername'));
$adminName = $form->Add(new ffInput('Name', 'adminName'));
$adminMail = $form->Add(new ffInput('E-Mail'));
$adminPassword = $form->Add(new ffInput('Passwort', 'adpw'));
$adminPassword2 = $form->Add(new ffInput('Passwort wiederholen', 'adpw2'));
$adminPassword->password = true;
$adminPassword2->password = true;

$form->Add(new ffFieldset('Website'));
$websiteName = $form->Add(new ffInput('Name'));
$websiteDescription = $form->Add(new ffInput('Beschreibung'));
$websiteKeywords = $form->Add(new ffInput('Schlüsselwörter'));

$form->Add(new ffFieldset('Erweiterte Einstellungen'));
$advTimezone = $form->Add(new ffList('Zeitzone'));
$advSalt = $form->Add(new ffInput('Globaler Salt'));
$advRewrite = $form->Add(new ffCheckbox('URL-Rewrite benutzen'));
$advRewrite->AddChoices('Aktivieren');

$form->Add(new ffButton('Installieren'));


//Set required
$dbServer->required = true;
$dbUser->required = true;
$dbDatabase->required = true;
$dbPrefix->required = true;
$adminUser->required = true;
$adminName->required = true;
$adminMail->required = true;
$adminPassword->required = true;
$adminPassword2->required = true;
$websiteName->required = true;
$websiteDescription->required = true;
$advTimezone->required = true;
$advSalt->required = true;

//Add timezones
foreach(file('timezones') AS $timezone) {
    $timezone = trim($timezone);
    $advTimezone->choices->Add($timezone);
}

//Add info texts
$advSalt->description = 'Der Salt wird dazu benutzt, die Hashes der Passwörter gegen Angriffe durch Rainbow-Tabellen abzusichern. Falls Sie StrigelCMS zu ersten mal installieren, können Sie den voreingestellten Wert verwenden, falls Sie jedoch eine Datenbank importieren, müssen Sie den gleichen Salt eingeben, der bei der Erstellung der zu importierender Datenbank eingestellt war, da sonst die Passwörter nicht mehr funktionieren (kein Login möglich).';
$advRewrite->description = 'Die Rewrite-Funktion lenkt die Seitenaufrufe so um, dass zum Beispiel anstatt http://domain.tld/index.php/kontakt/ in der Adresszeile des Browsers http://domain.tld/kontakt/ angezeigt wird. Dies funktioniert zur Zeit nur mit Apache-Servern mit aktiviertem mod_rewrite Modul.';
$dbPrefix->description = 'Dieses Präfix wird allen Tabellennamen vorangestellt, um Überschneidungen mit anderen Anwendungen zu verhindern. Meistens können Sie den voreingestellten Wert verwenden.';


if(isset($_GET['delete'])) {
    if(deleteDir('./')) {
        echo '<p>Das "install"-Verzeichnis wurde erfolgreich gelöscht!
                 Viel Spaß mit Ihrer neuen Website!</p>
              <p><a href="..">Hier gehts zur Website</a></p>';
    } else {
        echo '<p>Das "install"-Verzeichnis konnte leider nicht vollständig gelöscht werden!
                 <b>Bitte löschen Sie das Verzeichnis manuell.</b></p>
              <p><a href="..">Hier gehts zur Website</a> (Vorher "install"-Verzeichnis löschen!)</p>';
    }
} else if($form->IsSent()) {
    if($form->IsComplete()) {
        if($adminPassword->GetValue() === $adminPassword2->GetValue()) {
            try {
                //Check DB-Connection
                $testConnection = mysqli_connect($dbServer->GetValue(), $dbUser->GetValue(), $dbPassword->GetValue(), $dbDatabase->GetValue());
                if(!$testConnection)
                    throw new Exception('Es konnte keine Datenbankverbindung hergestellt werden: '.mysqli_connect_error());
                mysqli_close($testConnection);

                $db = new mysql($dbServer->GetValue(), $dbUser->GetValue(), $dbPassword->GetValue(), $dbDatabase->GetValue());

                //Write settings file
                $settings = new template('settings.php');
                $settings->setVar('adminMail', $adminMail->GetValue());
                $settings->setVar('timezone', $advTimezone->GetSingleValue());
                $settings->setVar('dbServer', $dbServer->GetValue());
                $settings->setVar('dbUser', $dbUser->GetValue());
                $settings->setVar('dbPass', $dbPassword->GetValue());
                $settings->setVar('dbDatabase', $dbDatabase->GetValue());
                $settings->setVar('dbPrefix', $dbPrefix->GetValue());
                $settings->setVar('salt', $advSalt->GetValue());
                if(false === file_put_contents('../settings.php', $settings->getHTML()))
                    throw new Exception('Konnte die Einstellungsdatei nicht abspeichern. (Fehlende Schreibrechte?)');
                $settings = null;

                //Write data into Database
                $dump = new template('scms.sql');
                $dump->setVar('dbPrefix', $dbPrefix->GetValue());
                $dump->setVar('websiteName', $websiteName->GetValue());
                $dump->setVar('advRewrite', $advRewrite->IsChecked('Aktivieren') ? 1 : 0);
                $dump->setVar('advTimezone', $advTimezone->GetSingleValue());
                $dump->setVar('websiteDescription', $websiteDescription->GetValue());
                $dump->setVar('websiteKeywords', $websiteKeywords->GetValue());
                $query = '';
                foreach(explode("\n", $dump->getHTML()) AS $line) {
                    $line = trim($line);
                    if(empty($line) || substr($line, 0, 2) === '--')
                        continue;

                    $query .= $line;
                    if(substr($query, -1) === ';') {
                        $query = substr($query, 0, -1);

                        if(!$db->execute($query))
                            throw new Exception('Fehler beim Eintragen der Daten in die Datenbank: '.$db->get_last_error());

                        $query = '';
                    }
                }

                //Add the admin user
                define('DB_PRE', $dbPrefix->GetValue());
                $admin = new user($db);
                $admin->setSalt($advSalt->GetValue());
                if(!$admin->register($adminUser->GetValue(), $adminName->GetValue(), $adminPassword->GetValue(), $adminMail->GetValue(), 1))
                    throw new Exception('Der Admin-Benutzer konnte nicht angelegt werden.');


                //Finished!
                echo '<p>Herzlichen Glückwunsch! Die Installation wurde erfolgreich abgeschlossen.</p>';
                echo '<p><a href="./?delete">Löschen Sie nun das Installationsverzeichnis</a>, um die Installation abzuschließen.</p>';
            } catch(Exception $e) {
                echo '<p style="color:red;font-weight:bold;">'.$e->GetMessage().'</p>';
                $form->ApplySent();
                $form->Show();
            }
        } else {
            $adminPassword->error = 'Sie haben zwei verschiedene Passwörter eingegeben!';
            $adminPassword2->error = '';
            $form->ApplySent();
            $form->Show();
        }
    } else {
        $form->ApplySent();
        $form->Show();
    }
} else {
    //Add some default data
    $dbServer->value = 'localhost';

    $adminUser->value = 'admin';

    $advTimezone->choices->Select('Europe/Berlin');

    $advSalt->value = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',10)),0,10);;

    $websiteName->value = $_SERVER['SERVER_NAME'];

    $dbPrefix->value = 'scms_';

    if((isset($_SERVER['SERVER_SOFTWARE']) && stristr($_SERVER['SERVER_SOFTWARE'], 'apache') !== false) ||
       (isset($_SERVER['SERVER_SIGNATURE']) && stristr($_SERVER['SERVER_SIGNATURE'], 'apache') !== false))
        $advRewrite->CheckChoices('Aktivieren');

    $form->Show();
}



//Delete directory and all children
function deleteDir($folder) {
    foreach(scandir($folder) AS $entry) {
        if($entry == '.' || $entry == '..') continue;

        if(is_dir($folder.$entry)) {
            deleteDir($folder.$entry.'/');
        } else {
            unlink($folder.$entry);
        }
    }

    if(substr($folder, 0, -1) == '.')
        return rmdir('../install');
    else
        return rmdir(substr($folder, 0, -1));
}

?>
</div>
</body>
</html>
