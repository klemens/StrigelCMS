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

class url
{

	protected $server;
	protected $parameters;

	protected $startpage;

	protected $mod_rewrite = false;

	protected $CONFIG;

	public function __construct(&$CONFIG, $host = false)
	{
		if($CONFIG) {
			$this->CONFIG = &$CONFIG;
		} else {
			logit("class/menu", "No Settings Class given");
			die("Fatal Error! Call Admin!");
		}

		//decide using mod_rewrite or not
		//if(!isset($_GET['mod_rewrite'])) {
        //    $this->no_mod_rewrite = true;
		//} -- deprecated!

		//absolute script-path for links
		if(!$host) {
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $path = dirname($_SERVER['SCRIPT_NAME']);
            $host = $scheme.'://'.$_SERVER['SERVER_NAME'].
                    (($path == '\\' OR $path == '/') ? '' : $path).'/';
			$this->server = $host;
		} else {
			if(substr($host, -1) == '/') {
				$this->server = trim($host);
			} else {
				$this->server = trim($host.'/');
			}
		}

		//extract parameters from url

		//Get the real path to the script
        $url_dir_remove = dirname($_SERVER['SCRIPT_NAME']);

        //If its the root
        if($url_dir_remove === '/' OR $url_dir_remove === '\\') {
            $url_dir_remove = false;
        } else {
            $url_dir_remove = array_filter(explode('/', $url_dir_remove));
        }

        //Put real path AND virtual path (=parameters) into array..
        $parameters_unfiltered = explode('/', $_SERVER['REQUEST_URI']);
        //..and remove empty elements
        $parameters_unfiltered = array_filter($parameters_unfiltered);

        //Where the final parameters will go ;D
        $parameters = array();
        $i = 1;
        foreach($parameters_unfiltered AS $single_parameter) {
            //If the current parameter (=folder) is a real folder,
            //do not add it to the parameter list
            if(is_array($url_dir_remove) AND in_array(trim($single_parameter), $url_dir_remove)) {
                continue;
            }
            //If the currnet folder is the script itself ( foo/index.php/bar ),
            //do not add it to the parameter list (occurs when no mod_rewrite is used)
            if(($i++ === 1) AND (basename($_SERVER['SCRIPT_NAME']) === $single_parameter)) {
                continue;
            }
            //Add the (virtual) folder as a parameter to the list
            //and remove the virtual .html
            $parameters[] = str_replace('.html', '', $single_parameter);
        }

		$this->parameters = $parameters;

	}

	public function setStartpage($startpage)
	{
        $this->startpage = $startpage;
        $this->setComponent($startpage);
	}

	public function getParameter($number = false)
	{
		if($number !== false) {
			if(isset($this->parameters[$number])) {
				return $this->parameters[$number];
			} else {
				return false;
			}
		} else {
			return $this->parameters;
		}
	}

	public function getComponent()
	{
        $parameters = $this->parameters;
        if(null !== ($component = array_shift($parameters))) {
            return trim($component);
        } else {
            return false;
        }
	}

	private function setComponent($component)
	{
        if(count($this->parameters) == 0) {
            $this->parameters[] = $component;
            return true;
        }
        return false;
	}

	public function getArgument($number = false)
	{
        if(count($this->parameters) <= 1) {
            return array();
        } else {
            $arguments = $this->parameters;
            array_shift($arguments);

            if(empty($arguments)) {
                return array();
            }

            if($number !== false) {
                if(isset($arguments[$number])) {
                    return $arguments[$number];
                } else {
                    return false;
                }
            } else {
                return $arguments;
            }
        }
    }

	public function getServer()
	{
		return $this->server;
	}

	public function setModRewrite($bool)
	{
        $this->mod_rewrite = $bool;
	}

	public function makeLink($parameters, $force_mod_rewrite = false)
	{
        $link = '';
        if(is_array($parameters)) {
            $tmp = array();
            foreach($parameters AS $param) {
                $param = explode('/', $param);
                $tmp = array_merge($tmp, $param);
            }
            $parameters = $tmp;
            unset($tmp);
            $count_parameters = count($parameters);
        } else {
            $parameters = explode('/', $parameters);
            $count_parameters = count($parameters);
        }

		$host = $this->server;
		if(substr($host, -1) != '/') {
			$host .= '/';
		}

		$link .= $host;

		if(!$this->mod_rewrite) {
            $link .= 'index.php/';
		}

		if($count_parameters == 0) {
			return false;
		} elseif($count_parameters == 1) {
            if($parameters[0] != $this->startpage) {
                $link .= implode("", $parameters);
                $link .= "/";
			}
		} else {
			$link .= implode("/", $parameters);
			$link .= ".html";
		}

		if($link) {
			return $link;
		} else {
			return false;
		}
	}
}
