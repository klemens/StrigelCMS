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

$pass_form = new ffphp(13, make_link(1));
$pass_form->setLang('de');
$id_pass0 = $pass_form->addField('input_singleline', array('label'=>'Altes Passwort','id'=>'pass_old','password'=>true), true);
$id_pass1 = $pass_form->addField('input_singleline', array('label'=>'Neues Passwort','id'=>'pass_new1','password'=>true), true);
$id_pass2 = $pass_form->addField('input_singleline', array('label'=>'Neues Passwort wiederholen','id'=>'pass_new2','password'=>true), true);
$pass_form->addField('button', array('text'=>'Passwort ändern','id'=>'pass_submit','type'=>'submit'), true);

if($pass_form->checkFormSent() && $pass_form->checkFormComplete()) {
    if($_POST['pass_new1'] === $_POST['pass_new2']) {
        $q = $DB->query(sprintf("SELECT `id` FROM `%ssys_user` WHERE `password` = SHA1(CONCAT(`salt`, '%s', '%s')) LIMIT 1",
                                 DB_PRE, $DB->escape($_POST['pass_old']), $DB->escape(CRYPT_SALT)));
        if($q->num_rows() !== 0) {
            if(strlen($_POST['pass_new1']) >= 6) {
                $id = intval($q->fetch()->id);
                $DB->execute(sprintf("UPDATE `%ssys_user` SET `password` = SHA1(CONCAT(`salt`, '%s', '%s')) WHERE `id` = %s LIMIT 1",
                                     DB_PRE, $DB->escape($_POST['pass_new1']), $DB->escape(CRYPT_SALT), $id));
                if($DB->affected_rows() === 1) {
                    success_message(1, 'Passwort wurde erfolgreich geändert!');
                } else {
                    success_message(0, 'Fehler beim Ändern des Passwortes. Bitte an die Administrator wenden!');
                }
            } else {
                $pass_form->addError($id_pass1, 'Das neue Passwort muss mindestens 6 Zeichen lang sein!');
            }
        } else {
            $pass_form->addError($id_pass0, 'Das eigegebene Passwort ist falsch!');
        }
    } else {
        $pass_form->addError($id_pass2, 'Die beiden Passwörter müssen übereinstimmen.');
    }
}


echo $pass_form->getHTML();

?>