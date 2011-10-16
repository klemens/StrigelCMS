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

if(!empty($_GET['action']) && ($_GET['action'] === 'create')) {
    $f_name = 'backup/backup_'.date('Y-m-d_H-i-s').'.sql';
    
    $f = fopen($f_name, "w");

    $q = $DB->query("SHOW TABLES FROM ".MYSQL_DB);

    while (false !== ($cells = $q->fetch(SQL_ARRAY))) {
        $table = $cells[0];
        
        fwrite($f,"\nDROP TABLE `$table`;\n"); 
        
        $q2 = $DB->query("SHOW CREATE TABLE `$table`");
        if ($q2) {
            $create = $q2->fetch(SQL_ARRAY);
            $create[1] .= ";";

            fwrite($f, $create[1]."\n\n");
            
            $q3 = $DB->query("SELECT * FROM `$table`");
            $num = $q3->num_fields();
            
            while ($row = $q3->fetch(SQL_ARRAY)){
                $line = "INSERT INTO `$table` VALUES(";
                for ($i=1;$i<=$num;$i++) {
                    $line .= "'".$DB->escape($row[$i-1])."', ";
                }
                $line = substr($line,0,-2);
                fwrite($f, $line.");\n");
            }
        }
    }
    $q = null;
    $q2 = null;
    $q3 = null;

    fclose($f);
    
    success_message(1, 'Backup wurde erfolgreich angelegt!');
}

echo '<h2>Aufgaben</h2>'.LF;
echo '<h3><a href="'.make_link(1, 'action=create').'">Datenbank Backup anlegen</a></h3>';

echo '<h2>Backups</h2>'.LF;
echo '<table><tr><th>Vorhandene Backups</th></tr>'.LF;
foreach(scandir('backup/', 1) AS $file) {
    if($file === '.' OR $file === '..') continue;
    
    if(substr($file, -4) === '.sql')
        echo '<tr><td>'.$file.'</td></tr>'.LF;
}

echo '</table>'.LF;

?>