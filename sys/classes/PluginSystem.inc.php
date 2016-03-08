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

abstract class Event {
    //Stop the Event (do not hand it to other plugins)
    //returns true if event was stopped or false if it can't
    public function Stop() {
        if($this->CanBeStopped()) {
            $this->stopped = true;
            return true;
        } else return false;
    }

    //Checks if the event is stopped!
    public function IsStopped() {
        return $this->stopped;
    }

    //Get the type of the event
    public function GetType() {
        return $this->type;
    }

    //Set the type of the event
    public function SetType($type) {
        if(empty($this->type)) {
            $this->type = $type;
        } else throw new exception('You must no set the type a second time!');
    }

    //If the event can be stopped
    protected abstract function CanBeStopped();

    //The stop state of the event
    private $stopped = false;

    //The type of the event
    private $type;
}

//Event Types
define('EVT_START', 0); //ContainerEvent
define('EVT_SITE', 1); //SiteEvent
define('EVT_CONTENT', 2); //TextModifyEvent : TextEvent
define('EVT_MENU', 3); //MenuEvent
define('EVT_TEMPLATE', 4); //TemplateEvent
define('EVT_HTML_HEAD', 5); //TextAddEvent : TextEvent
define('EVT_HTML', 6); //TextModifyEvent : TextEvent
define('EVT_EXIT', 7); //NullEvent

class NullEvent extends Event {
    protected function CanBeStopped() {
        return false;
    }
}

class ContainerEvent extends Event {
    public function __construct(Doctrine\DBAL\Connection $db, settings $config, url $url) {
        $this->db = $db;
        $this->config = $config;
        $this->url = $url;
    }

    protected function CanBeStopped() {
        return false;
    }

    public $db;
    public $config;
    public $url;
}

class MenuEvent extends Event {
    public function __construct(menu $menu) {
        $this->menu = $menu;
    }

    protected function CanBeStopped() {
        return false;
    }

    public $menu;
}

class SiteEvent extends Event {
    public function __construct($component) {
        $this->component = $component;
    }

    public function GetComponent() {
        return $this->component;
    }

    public function RegisterFile($file) {
        $this->file = $file;
        $this->Stop();
    }

    public function GetFile() {
        return $this->file;
    }

    public function SetTitle($title) {
        $this->title = $title;
    }

    public function GetTitle() {
        return $this->title;
    }

    protected function CanBeStopped() {
        return true;
    }

    private $component;

    private $file;
    private $title;
}

abstract class TextEvent extends Event {
    public function __construct($text) {
        $this->text = $text;
    }

    public function GetText() {
        return $this->text;
    }

    protected $text;
}

class TextModifyEvent extends TextEvent {
    public function SetText($text) {
        $this->text = $text;
    }

    protected function CanBeStopped() {
        return true;
    }
}

class TextAddEvent extends TextEvent {
    public function AddText($text) {
        $this->text .= $text;
    }

    public function AddLine($line) {
        $this->AddText(LF.$line);
    }

    protected function CanBeStopped() {
        return false;
    }
}

class TemplateEvent extends Event {
    public function __construct(template $tmp) {
        $this->tmp = $tmp;
    }

    protected function CanBeStopped() {
        return false;
    }

    public $tmp;
}

abstract class Plugin {
    //Set the absolute web path to the plugin dir
    public function SetWebHost($path) {
        $this->pathWeb = (string)$path;
    }
    public function GetWebHost() {
        return $this->pathWeb;
    }

    //Set the relative filesystem path to the plugin dir
    public function SetFilePath($path) {
        $this->pathFile = (string)$path;
    }
    public function GetFilePath() {
        return $this->pathFile;
    }

    //Get some information from the plugin
    public abstract function GetName();
    public abstract function GetVersion();
    public abstract function GetAuthor();
    public abstract function GetLicence();
    public abstract function GetWebsite();

    //Needed plugins for this plugin to work
    public abstract function GetDependencies();

    //Initiate plugin an connect to events
    public abstract function Init(PluginSystem $pluginSystem);

    //The (only) instance of this class
    private static $instance;

    //Web host (with trailing slash)
    private $host;

    //Relative filesystem web path to plugin dir (with trailing slash)
    private $pathFile;
}

class PluginSystem {
    //Connects a plugin to an event
    public function Connect($eventType, Plugin $plugin, $function, $priority = 0) {
        $this->eventPlugins[$eventType][] = array($plugin, $function, (int)$priority);
    }

    //Send the given event to all connected plugins
    public function TriggerEvent(Event $event) {
        $eventType = $event->GetType();

        if(!isset($this->eventPlugins[$eventType]))
            return false;

        usort($this->eventPlugins[$eventType], 'PluginSystem::CallbackSort');

        foreach($this->eventPlugins[$eventType] AS $plugin) {
            $plugin[0]->{$plugin[1]}($event);

            if($event->IsStopped())
                break;
        }

        return true;
    }

    public function LoadPlugins($dir, $webhost) {
        if(!is_dir($dir))
            throw new exception('The given plugin dir does not exist!');

        $d = dir($dir);

        while(($plugin = $d->read()) !== false) {
            if($plugin == '..' || $plugin == '.' || !is_dir($dir.$plugin))
                continue;

            if(is_file($dir.$plugin.'/plugin.php'))
                require($dir.$plugin.'/plugin.php');
            else
                continue;

            if(isset($this->pluginInstances[$plugin]))
                throw new exception('The two plugins "'.$plugin.'" must no have the same names!');

            $pluginObject = new $plugin;
            $pluginObject->SetWebHost($webhost);
            $pluginObject->SetFilePath($dir.$plugin.'/');

            $this->pluginInstances[$plugin] = array($pluginObject, !is_file($dir.$plugin.'/disabled')); //second: active?
        }

        $this->CheckDependencies();

        $this->InitPlugins();
    }

    //Callback for sorting events by priority
    private static function CallbackSort(array $a, array $b) {
        return $a[2] - $b[2];
    }

    //Deactivate plugins whose dependencies aren't fulfilled
    private function CheckDependencies() {
        $change;
        do {
            $change = false;

            foreach($this->pluginInstances AS $pluginName => &$plugin) {
                if(!is_array($plugin[0]->GetDependencies()) || $plugin[1] === false)
                    continue;

                foreach($plugin[0]->GetDependencies() AS $dependency) {
                    if(!isset($this->pluginInstances[$dependency]) || $this->pluginInstances[$dependency][1] === false) {
                        if(!isset($this->missingDependencies[$pluginName]))
                            $this->missingDependencies[$pluginName] = array();
                        if(!in_array($dependency, $this->missingDependencies[$pluginName]))
                            $this->missingDependencies[$pluginName][] = $dependency;

                        $plugin[1] = false; //disable
                        $change = true; //there could be plugins that need this plugin
                    }
                }
            } unset($pluginName, $plugin);
        } while($change === true);
    }

    //Init all active plugins
    private function InitPlugins() {
        foreach($this->pluginInstances AS &$plugin) {
            if($plugin[1])
                $plugin[0]->Init($this);
        } unset($plugin);
    }

    //The List of the connected plugins for every event type
    private $eventPlugins = array();

    //Instances of all loaded plugins
    private $pluginInstances = array();

    //Missing dependencies for all plugins
    private $missingDependencies = array();
}
