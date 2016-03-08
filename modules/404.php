<?php

header("HTTP/1.0 404 Not Found");

echo '<h1>Seite nicht gefunden</h1>';
echo '<p>Die angeforderte Seite konnte nicht gefunden werden!</p>';
echo '<p>Bitte überprüfen Sie die Adresse auf eventuelle Tippfehler!</p>';

$referrer = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '';

$query = $DB->createQueryBuilder()
    ->insert(DB_PRE . "sys_404")
    ->values([
        "site" => ":site",
        "referer" => ":referer",
        "time" => "now()"
    ])
    ->setParameters([
        ":site" => implode('/', $URL->getParameter()),
        ":referer" => $referrer
    ]);

if($query->execute() === 0) {
    echo 'Fehler beim Eintragen in die Datenbank!';
}
