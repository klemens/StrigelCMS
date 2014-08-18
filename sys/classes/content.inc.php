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

class content
{
    protected $database;
    protected $config;
    protected $url;

    protected $raw_content;
    protected $parsed_content;
    protected $parsed_js;
    protected $parsed_css;

	protected $information = array();
	protected $content_id;


    public function __construct($database, $config, $url)
    {
        if($database) {
            $this->database = $database;
        } else {
            logit("class/content", "No Database Class given");
            die("Fatal Error! Call Admin!");
        }

        if($config) {
            $this->config = $config;
        } else {
            logit("class/content", "No Settings Class given");
            die("Fatal Error! Call Admin!");
        }

        if($url) {
            $this->url = $url;
        } else {
            logit("class/content", "No Url Class given");
            die("Fatal Error! Call Admin!");
        }
    }

    public function loadSite($link)
    {
        $date_format = ($date_format = $this->config->get('sys', 'date_format')) ? $date_format : '%Y/%m/%d';
        $time_format = ($time_format = $this->config->get('sys', 'time_format')) ? $time_format : '%k:%i';

        $query = "SELECT `id`, `title`, `link`, `file`, `content`, `author`, `description`, `keywords`, `css`, `js`, UNIX_TIMESTAMP(`date`) AS `timestamp`, `lang`, `files`,
                 DATE_FORMAT(`date`, '".$this->database->escape($date_format)."') AS `date`, DATE_FORMAT(`date`, '".$this->database->escape($time_format)."') AS `time`, `redirect`, `content_type`, `robot_visibility`, `header_image`
                 FROM ".DB_PRE."sys_content
                 WHERE `link` = '".$this->database->escape($link)."'
                 LIMIT 1";

        $result = $this->database->qquery($query, SQL_ASSOC_ARRAY);

        if(false === $result) {
            return false;
        }

        //Type of content
        $this->information['type'] = $result['content_type'];

        //Redirect if set
        if('redirect' === $this->information['type']) {
            if('first_child' === $result['redirect']) {
                $redirect = $this->database->qquery("SELECT `link` FROM `".DB_PRE."sys_content`
                                                    WHERE `m_pid` = ".$result['id']."
                                                    ORDER BY `m_pid` ASC, `m_sort` ASC, `m_title` ASC
                                                    LIMIT 1");

                if(false === $redirect) {
                    logit('content/redirect', '(first_child) - entry has no childs!');
                    die('Error occured, call admin!');
                }

                $redirect = $redirect->link;
            } else {
                $redirect = $result['redirect'];
            }

            header('Location: '.$this->url->makeLink($redirect));
            exit;
        }

        $this->content_id                       = $result['id'];
        //editable stuff
		$this->information['title']             = $result['title'];
		$this->information['link']              = $result['link'];
		$this->information['file']              = $result['file'];
		$this->information['content']           = $result['content'];
		$this->information['redirect']          = $result['redirect'];
		$this->information['timestamp']         = $result['timestamp'];
        $this->information['author']            = $result['author'];
		$this->information['robot_visibility']  = $result['robot_visibility'];
		$this->information['description']       = $result['description'];
		$this->information['keywords']          = $result['keywords'];
		$this->information['header_image']      = $result['header_image'];
		$this->information['css']               = $result['css'];
		$this->information['js']                = $result['js'];
		$this->information['files']             = $result['files'];
		$this->information['lang']              = $result['lang'];
        //this cannnot be edited
		$this->information['date']              = $result['date'];
		$this->information['time']              = $result['time'];

		$result = null;

		if('html' === $this->information['type']) {
			$this->raw_content = $this->information['content'];
			$this->parseContent();
		}
		$this->parseCss();
		$this->parseJs();

		return $this->getInformation('type');
    }

	public function isSetInformation($part)
	{
        if(!empty($this->information)) {
            return isset($this->information[$part]);
		} else {
			return false;
		}
	}

	public function getInformation($part = false)
	{
		if(!empty($this->information)) {
            if($part) {
                if(isset($this->information[$part])) {
                    return $this->information[$part];
                } else {
                    return false;
                }
            } else {
                return $this->information;
			}
		} else {
			return false;
		}
	}

	private function parseContent()
	{
        //first: the main content
        $tmp = new template($this->raw_content, true);

        if(!empty($this->information['files'])) {
            $tmp->setVar('file_path', $this->url->getServer().DIR_FILES.
                                     $this->information['files'].'/');
        }

        $tmp->setVar('pdf_path', $this->url->getServer().DIR_PDF);

        $func = create_function('$result', 'global $URL; return $URL->makeLink($result[1]);');
        $tmp->setVarCallback('href', $func);
        $func = null;

        $this->parsed_content = $tmp->getHTML();

        $tmp = null;

        return true;
	}

	private function parseCss()
	{
		//second: the css content
        if(!empty($this->information['css'])) {
            $tmp = new template($this->information['css'], true);

            $tmp->setVar('file_path', $this->url->getServer().DIR_FILES.
                                      $this->information['files'].'/');
            $tmp->setVar('js_path', $this->url->getServer().DIR_JS);
            $tmp->setVar('css_path', $this->url->getServer().DIR_CSS);

            $this->parsed_css = $tmp->getHTML();

            $tmp = null;
        }

        return true;
	}

	private function parseJs()
	{
        //Third: the js content / files
        if(!empty($this->information['js'])) {
            $tmp = new template($this->information['js'], true);

            $tmp->setVar('js_path', $this->url->getServer().DIR_JS);
            $tmp->setVar('file_path', $this->url->getServer().DIR_FILES.
                                      $this->information['files'].'/');

            $this->parsed_js = $tmp->getHTML();

            $js_head = array();

            foreach(explode('|', $this->parsed_js) AS $js_file) {
                if(empty($js_file)) continue;

                $js_head[] = '<script type="text/javascript" src="'.
                             $js_file.'"></script>';
            }

            $this->parsed_js = implode(LF, $js_head);

            $tmp = null;
        }

        return true;
	}

	public function getParsedContent()
	{
        if('file' === $this->getInformation('type')) {
            logit('classes/content/getContent', 'Trys to get file, althogh its just raw content');
            return false;
        }

        return $this->parsed_content;
	}

	public function getParsedCss()
	{
        if(empty($this->parsed_css)) {
            return false;
        } else {
            return $this->parsed_css;
        }
	}

	public function getParsedJs()
	{
        if(empty($this->parsed_js)) {
            return false;
        } else {
            return $this->parsed_js;
        }
	}

	public function getFilePath()
	{
		if(empty($this->information['files'])) {
			return false;
		} else {
			return $this->url->getServer().DIR_FILES.$this->information['files'].'/';
		}
	}

	public function getPdfPath()
	{
		return $this->url->getServer().DIR_PDF;
	}

	public function getCssPath()
	{
		return $this->url->getServer().DIR_CSS;
	}
}
