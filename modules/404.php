<?php

header("HTTP/1.0 404 Not Found");

echo '<h1>Seite nicht gefunden</h1>';
echo '<p>Die angeforderte Seite konnte nicht gefunden werden!</p>';
echo '<p>Bitte überprüfen Sie die Adresse auf eventuelle Tippfehler!</p>';

$referrer = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '';

if(!$DB->execute(sprintf("INSERT INTO `%ssys_404` SET `site` = '%s', `referer` = '%s', `time` = NOW()",
                        DB_PRE, $DB->escape(implode('/', $URL->getParameter())), $DB->escape($referrer)))) {
    echo 'Fehler beim Eintragen in die Datenbank!';
}

?>