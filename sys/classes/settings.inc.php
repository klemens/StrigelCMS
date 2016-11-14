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

        $q = $this->database->query("SELECT `section`, `value`, `name` FROM `".DB_PRE."sys_settings`");

        if(0 == $q->num_rows()) {
            logit("class/settings/_loadSettings", "Not Settings!");
            $q = null;
            return true;
        }

        while($setting = $q->fetch()) {
            $this->settings[trim($setting->section)][trim($setting->name)] = trim($setting->value);
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

        $q = $this->database->query("SELECT value FROM ".DB_PRE."sys_settings WHERE section = '".$this->database->escape($section)."' AND name = '".$this->database->escape($name)."'");

        if($q->num_rows() == 0) {
            $query = "INSERT INTO ".DB_PRE."sys_settings(name, section, value) VALUES('".$this->database->escape($name)."', '".$this->database->escape($section)."', '".$this->database->escape($value)."')";
        } elseif($q->num_rows() == 1) {
            $query = "UPDATE ".DB_PRE."sys_settings SET value = '".$this->database->escape($value)."' WHERE section = '".$this->database->escape($section)."' AND name = '".$this->database->escape($name)."'";
        } else {
            logit("class/settings/set", "Too many results!");
            die("Call Admin!");
        }

        $q = null;

        $success = $this->database->execute($query);

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

        $q = $this->database->query("DELETE FROM ".DB_PRE."sys_settings WHERE section = '".$this->database->escape($section)."' AND name = '".$this->database->escape($name)."' LIMIT 1");

        $affected = $q->affected_rows();

        $q = null;

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
