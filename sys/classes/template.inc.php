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

class template
{

    protected $content;
    protected $edited_content;
    
    protected $cleaned = false;
    
    protected $delmiter = array("left" => "{", "right" => "}", "row_l" => "start:", "row_r" => "end:");
    protected $additional_chars = array();
    
    public function __construct($file, $string = false)
    {
        if($string OR !is_file($file)) {
            $this->content = $file;
        } else  {
            $this->setFile($file);
        }
    }
    
    public function setFile($file)
    {
        if(is_file($file)) {
            $file_content = file_get_contents($file);
            if($file_content) {
                $this->content = $file_content;
            } else {
                throw new Exception('Could not open template file');
            }
        }
    }
    
    public function setDelimiter($left, $right, $row_l, $row_r)
    {
        if($left && $right && $row_l && $row_r &&
           strlen(trim($left)) === 1 && strlen(trim($right)) === 1) {
            $this->delmiter = array('left' => $left,   'right' => $right,
                                    'row_l' => $row_l, 'row_r' => $row_r);
            return true;
        } else
            return false;
    }
    
    public function addChars($chars)
    {
        if(empty($chars)) {
            return false;
        } else if(is_array($chars)) {
            $split = $chars;
        } else {
            $split = str_split($chars, 1);
        }
        
        foreach($split AS $single_char) {
            if(in_array(trim($single_char), $this->additional_chars)) {
                continue;
            } else if((strlen($single_char) !== 1) OR (':' == $single_char)) {
                return false;
            } else {
                $this->additional_chars[] = trim($single_char);
            }
        }
        
        return true;
    }
    
    public function setVar($name, $content)
    {
        if(true === $this->cleaned) {
            logit('class/template/setVar', 'Content already cleaned. Cannot add more vars!');
            return false;
        }
        
        $this->content = str_replace($this->delmiter['left'].$name.$this->delmiter['right'], $content, $this->content);
        
        return true;
    }
    
    public function setArray($array)
    {
        if(true === $this->cleaned) {
            logit('class/template/setArray', 'Content already cleaned. Cannot add more vars!');
            return false;
        }
        
        if(is_array($array)) {
            foreach($array AS $name => $content) {
                $this->setVar($name, $content);
            }
        } else {
            return false;
        }
        
        return true;
    }
    
    public function setRow($name, array $array)
    {
        if(true === $this->cleaned) {
            logit('class/template/setRow', 'Content already cleaned. Cannot add more vars!');
            return false;
        }
        
        list($part_a, $tmp) = explode($this->delmiter['left'].
                                    $this->delmiter['row_l'].
                                    $name.
                                    $this->delmiter['right'], $this->content);
        list($row_content, $part_b) = explode($this->delmiter['left'].
                                    $this->delmiter['row_r'].
                                    $name.
                                    $this->delmiter['right'], $tmp);
        $final_content = "";
        
        foreach($array AS $element) {
            $tmp_tmp = new template($row_content, true);
            $tmp_tmp->setArray($element);
            $final_content .= $tmp_tmp->getHTML();
            $tmp_tmp = null;
        }
        
        $this->content = $part_a.$final_content.$part_b;
        
        return true;
    }
    
    public function getHTML($cleanup = false)
    {
        if($cleanup) {
            $cleanup = true;
        } else {
            $cleanup = false;
        }
        if((true === $cleanup) AND (false === $this->cleaned)) {
            $this->cleaned = true;
            
            $this->edited_content = preg_replace("#\\".$this->delmiter['left'].
                                          $this->delmiter['row_l']."(.+)\\".
                                          $this->delmiter['right']."(.*)\\".
                                          $this->delmiter['left'].
                                          $this->delmiter['row_r']."\\1\\".
                                          $this->delmiter['right']."#sU", " ", $this->content);
            $this->edited_content = preg_replace("#\\".$this->delmiter['left']."([a-zA-Z0-9_:".
            
                                          (('' === implode('\\', $this->additional_chars)) ? ''
                                          : '\\'.implode('\\', $this->additional_chars))
                                          
                                          ."]+)\\".
                                          $this->delmiter['right']."#U", " ", $this->edited_content);

        }
        
        if($cleanup) {
            return $this->edited_content;
        } else {
            return $this->content;
        }
    }
    
    public function setVarCallback($name, $call_function)
    {
        if(!preg_match("#^[a-zA-Z0-9_".
                      
                      (('' === implode('\\', $this->additional_chars)) ? ''
                      : '\\'.implode('\\', $this->additional_chars)).
                                          
                      "]+\$#", trim($name))) {
            logit('classes/template/callback', '$name contained false chars!');
            return false;
        }
        
        $pattern = "#\\{".$name."\\:(.+)\\}#U";
        $this->content = preg_replace_callback($pattern, $call_function, $this->content);
        
        return true;
    }

}
