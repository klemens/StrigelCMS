<?php

/**************************************************************\
 *     _____ _        _            _ _____ ___  ___ _____     *
 *    /  ___| |      (_)          | /  __ \|  \/  |/  ___|    *
 *    \ `--.| |_ _ __ _  __ _  ___| | /  \/| .  . |\ `--.     *
 *     `--. \ __| '__| |/ _` |/ _ \ | |    | |\/| | `--. \    *
 *    /\__/ / |_| |  | | (_| |  __/ | \__/\| |  | |/\__/ /    *
 *    \____/ \__|_|  |_|\__, |\___|_|\____/\_|  |_/\____/     *
 *                       __/ |                                *
 *                      |___/                >> StrigelCMS << *
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

class settings
{

    protected $database;

    protected $settings = array();
    protected $loaded = false;

    public function __construct(&$database)
    {
        if($database) {
            $this->database = &$database;
        } else {
            logit("class/settings", "No Database given");
            die("Fatal Error! Call Admin!");
        }

        $this->_loadSettings();
    }

    public function _loadSettings()
    {
        if($this->loaded) {
            return true;
        }
        $this->loaded = true;

        $q = $this->database->createQueryBuilder()
            ->select("section", "value", "name")
            ->from(DB_PRE . "sys_settings")
            ->execute();

        if(0 == $q->rowCount()) {
            logit("class/settings/_loadSettings", "No Settings!");
            $q = null;
            return true;
        }

        while($setting = $q->fetch()) {
            $this->settings[$setting['section']][$setting['name']] = $setting['value'];
        }

        $q = null;

        return true;
    }

    public function get($section, $name)
    {
        if(!($section && $name)) {
            logit("class/settings/get", "Not both name/section given!");
            return false;
        }

        if(isset($this->settings[trim($section)][trim($name)])) {
            return $this->settings[trim($section)][trim($name)];
        } else {
            logit("class/settings/get", "Not such Setting ".$section." - ".$name."!");
            return false;
        }
    }

    public function set($section, $name, $value)
    {
        if(!($section && $name && $value)) {
            logit("class/settings/set", "Not both name/section given!");
            return false;
        }

        $q = $this->database->createQueryBuilder()
            ->select("value")
            ->from(DB_PRE . "sys_settings")
            ->where("section = :section")
            ->andWhere("name = :name")
            ->setParameters([
                ":section" => $section,
                ":name" => $name
            ])
            ->execute();

        if($q->rowCount() == 0) {
            $query = $this->database->createQueryBuilder()
                ->insert(DB_PRE . "sys_settings")
                ->values([
                    "name" => ":name",
                    "section" => ":section",
                    "value" => ":value"
                ])
                ->setParameters([
                    ":name" => $name,
                    ":section" => $section,
                    ":value" => $value
                ]);
        } elseif($q->rowCount() == 1) {
            $query = $this->database->createQueryBuilder()
                ->update(DB_PRE . "sys_settings")
                ->set("value", ":value")
                ->where("section = :section")
                ->andWhere("name = :name")
                ->setParameters([
                    ":name" => $name,
                    ":section" => $section,
                    ":value" => $value
                ]);
        } else {
            logit("class/settings/set", "Too many results!");
            die("Call Admin!");
        }

        $q = null;

        $success = $query->execute() > 0;

        if($success) {
            $this->settings[trim($section)][trim($name)] = $value;
        }

        return $success;
    }

    public function delete($section, $name)
    {
        if(!($section && $name)) {
            logit("class/settings/delete", "Not both name/section given!");
            return false;
        }

        $affected = $this->database->createQueryBuilder()
            ->delete(DB_PRE . "sys_settings")
            ->where("section = :section")
            ->andWhere("name = :name")
            ->setParameters([
                ":name" => $name,
                ":section" => $section
            ])
            ->execute();


        if(1 == $affected) {
            $this->settings[trim($section)][trim($name)] = null;
            unset($this->settings[trim($section)][trim($name)]);
        }

        return $affected;
    }

    public function getAll($section = false)
    {
        if(!$section) {
            return $this->settings;
        } else {
            if(isset($this->settings[$section])) {
                return $this->settings[$section];
            } else {
                return false;
            }
        }
    }

}
