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

class menu
{

    private $database;
    private $CONFIG;
    private $URL;

    private $flat_menu = array();
    public $tree_menu = array();
    private $path = array();

    private $activeList;

    private $output_allowed = true;

    private $ref_child_by_id = array();
    private $ref_data_by_id = array();
    private $ref_data_by_title = array();
    private $ref_data_by_href = array();

    public function __construct($database, $CONFIG, $URL)
    {
        if($database) {
            $this->database = $database;
        } else {
            logit("class/menu", "No Database given");
            die("Fatal Error! Call Admin!");
        }
        if($CONFIG) {
            $this->CONFIG = $CONFIG;
        } else {
            logit("class/menu", "No Settings Class given");
            die("Fatal Error! Call Admin!");
        }
        if($URL) {
            $this->URL = $URL;
        } else {
            //Cannot use output functions!
            $this->output_allowed = false;
        }

        $this->_loadMenu();
    }

    protected function _loadMenu()
    {
        //Protection from endless loop
        $last_insert_id = 0;
        //Get menu from database
        $query = "SELECT `id`, `m_pid` AS `pid`, `m_title`, `title`,
                    `link` AS `href`, `m_sort` AS `sort`, `m_active` AS `active`
                    FROM `".DB_PRE."sys_content`
                    ORDER BY `pid` ASC, `sort` ASC, `m_title` ASC";

        $q = $this->database->query($query);
        while($row = $q->fetch(SQL_ASSOC_ARRAY))
        {
            $this->flat_menu[] = $row;
        }
        $q = null;

        //For all the nodes with pid = 0
        $this->ref_child_by_id['0'] = &$this->tree_menu;

        //Transform flat menu into tree
        foreach($this->flat_menu AS &$flat_menu_row) {
            //Prepare the data to be inserted into final Array
            $insert = array('title'     => $flat_menu_row['m_title'],
                            'href'      => $flat_menu_row['href'],
                            'id'        => $flat_menu_row['id'],
                            'pid'       => $flat_menu_row['pid'],
                            'sort'      => $flat_menu_row['sort'],
                            'active'    => $flat_menu_row['active'],
                            'real_title'=> $flat_menu_row['title'],
                            'child'     => array());
            //Make reference to the child array of that special data block (**)
            $tmp_ref1 = &$insert['child'];
            //Reference to the data itself
            $tmp_ref2 = &$insert;
            //Only insert, if parent exists (*)
            if(!isset($this->ref_child_by_id[$flat_menu_row['pid']])) {
                if(isset($flat_menu_row['last_insert_id']) &&
                   ($flat_menu_row['last_insert_id'] === $last_insert_id)) {
                    continue;
                }
                $flat_menu_row_insert = $flat_menu_row;
                $flat_menu_row_insert['last_insert_id'] = $last_insert_id;
                $this->flat_menu[] = $flat_menu_row_insert;
                continue;
            }
            //Find out the reference of the child array of the parent data block (see **)
            $this->ref_child_by_id[$flat_menu_row['pid']][] = &$insert;
            //Make the references of that data block (see **) avalible to all following data blocks
            $this->ref_child_by_id[$flat_menu_row['id']] = &$tmp_ref1;
            $this->ref_data_by_id[$flat_menu_row['id']] = &$tmp_ref2;
            $this->ref_data_by_title[$flat_menu_row['m_title']] = &$tmp_ref2;
            $this->ref_data_by_href[$flat_menu_row['href']] = &$tmp_ref2;
            //Save the last inserted id to prevent infinite loop
            $last_insert_id = (int)$flat_menu_row['id'];
            //Clean up
            unset($insert);
            unset($tmp_ref1);
            unset($tmp_ref2);
        }
    }

    private function _reloadMenu()
    {
        //delete the menu..
        $this->flat_menu = array();
        $this->tree_menu = array();
        $this->path = array();

        $this->ref_child_by_id = array();
        $this->ref_data_by_id = array();
        $this->ref_data_by_title = array();
        $this->ref_data_by_href = array();

        //..and load it again
        $this->_loadMenu();
    }

    public function getMenu($id = 0)
    {
        if(0 === $id) {
            return $this->tree_menu;
        } else {
            if(isset($this->ref_data_by_id[$id])) {
                return array($this->ref_data_by_id[$id]);
            } else {
                return false;
            }
        }
    }

    private function getNextHigherActive($id)
    {
        if(!isset($this->ref_data_by_id[$id]))
            return false;

        if($this->ref_data_by_id[$id]['active'] == 1)
            return $id;

        while(1) {
            $id = $this->ref_data_by_id[$id]['pid'];

            if($id == 0)
                return 0;

            if($this->ref_data_by_id[$id]['active'] == 1)
                return $id;
        }
    }

    public function setActive(array $active) {
        $this->activeList = array_map(create_function('$a', 'return (string)$a;'), $active);
    }

    public function getPath($target = '', $separator = false, $string = '', $string_active = '', $full_path = false)
    {
        if(!$this->output_allowed) {
            logit('classes/menu/getPath', 'Try to output, but not all necessary funktions available!');
            die('Fatal Error! Call Admin!');
        }
        //get id of target
        if(isset($this->ref_data_by_href[$target])) {
            if($full_path) {
                $target_id = $this->ref_data_by_href[$target]['id'];
            } else {
                $target_id = $this->getNextHigherActive($this->ref_data_by_href[$target]['id']);
            }
        } else {
            if($separator === false)
                return array();
            else
                return '';
        }

        //Create the path from the element to the top through every level
        $path = array();
        $current_id = $target_id;
        while(true) {
            //If we're already on top, break the loop
            if(!isset($this->ref_data_by_id[$current_id]) || $this->ref_data_by_id[$current_id]['id'] == 0) break;
            //add current level to path list
            $path[] = $this->ref_data_by_id[$current_id]['id'];
            //increase the level for the next run of the loop
            $current_id = $this->ref_data_by_id[$current_id]['pid'];
        }

        //Make the highest level of the menu the highest level of path list
        $path = array_reverse($path);

        //If the user has not given proper strings
        if(!$string) {
            $string = '<a href="%2$s">%1$s</a>';
        }
        if(!$string_active) {
            $string_active = '%1$s';
        }
        if($separator !== false) {
            //Create the path of words (just to see, if its right)
            $path_text = array();
            $i = 1;
            foreach($path AS $node) {
                $href = $this->URL->makeLink(explode('/', $this->ref_data_by_id[$node]['href']));
                //If its the last element, use different parsing string
                $path_text[] = sprintf(((count($path) !== $i) ? $string : $string_active),
                                                $this->ref_data_by_id[$node]['title'],
                                                $href,
                                                $this->ref_data_by_id[$node]['id'],
                                                $this->ref_data_by_id[$node]['pid']);
                $i++;
            }
            $path_text = implode($separator, $path_text);
            return $path_text;
        } else {
            return $path;
        }
    }

    public function getMenuHtml($method, $string = '', $target = '')
    {
        if(!$this->output_allowed) {
            logit('classes/menu/getPath', 'Try to output, but not all necessary funktions available!');
            die('Fatal Error! Call Admin!');
        }

        if(!$string) {
            $string = '%4$s | %3$s: %1$s (%2$s)';
        }
        if($method == 'full') {
            return $this->_createTreeHtmlComplete($this->tree_menu, $string);
        } else if($method == 'path') {
            if(!$target) {
                return false;
            } else {
                return $this->_createTreeHtmlPath($this->tree_menu, $this->getPath($target), $string);
            }
        } else {
            return false;
        }
    }

    private function _createTreeHtmlComplete(&$data, $string)
    {
        $html = '<ul>';

        //Get all neighbours of current element
        foreach($data AS $data_row) {

            $href = $this->URL->makeLink(explode('/', $data_row['href']));

            $html .= '<li>'.LF.sprintf($string, $data_row['title'],
                                             $href,
                                             $data_row['id'],
                                             $data_row['pid']);
             //If it has a child, call to tihs function with one level lower
             if(!empty($data_row['child'])) {
                $html .= $this->_createTreeHtmlComplete($data_row['child'], $string);
             }
             $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    private function _createTreeHtmlPath(&$data, $path, $string)
    {
        $lb = '-->';
        $le = '<!--'.LF;
        $html = '<ul>'.$le;

        //Get all neighbours of current element
        foreach($data AS $data_row) {
            if($data_row['active'] == 0)
                continue;

            $href = $this->URL->makeLink($data_row['href']);

            $destination = end($path);
            reset($path);
            if(!isset($this->activeList)) {
                $html_open_tag = ($destination === $data_row['id'])
                                 ? '<li class="active">' : '<li>';
            } else {
                if(in_array($data_row['id'], $this->activeList))
                    $html_open_tag = '<li class="active">';
                else
                    $html_open_tag = '<li>';
            }

            $html_open_tag = $lb.$html_open_tag;

            $html .= $html_open_tag.sprintf($string, $data_row['title'],
                                                     $href,
                                                     $data_row['id'],
                                                     $data_row['pid']);
             //If it has a child in path list, call to this function with one level lower
             if(!empty($data_row['child']) && in_array($data_row['id'], $path)) {
                $html .= $this->_createTreeHtmlPath($data_row['child'], $path, $string);
             }
             $html .= '</li>'.$le;
        }
        $html .= $lb.'</ul>';

        if($html == '<ul><!--'.LF.'--></ul>')
            return '';
        else
            return $html;
    }

    public function getIdByHref($href)
    {
        if(isset($this->ref_data_by_href[$href])) {
            return $this->ref_data_by_href[$href]['id'];
        } else {
            return false;
        }
    }

    public function getHrefById($id)
    {
        if(isset($this->ref_data_by_id[$id])) {
            return $this->ref_data_by_id[$id]['href'];
        } else {
            return false;
        }
    }

    public function getTitleById($id)
    {
        if(isset($this->ref_data_by_id[$id])) {
            return $this->ref_data_by_id[$id]['title'];
        } else {
            return false;
        }
    }

    public function getRealTitleById($id)
    {
        if(isset($this->ref_data_by_id[$id])) {
            return $this->ref_data_by_id[$id]['real_title'];
        } else {
            return false;
        }
    }

    public function getPidById($id)
    {
        if(isset($this->ref_data_by_id[$id])) {
            return $this->ref_data_by_id[$id]['pid'];
        } else {
            return false;
        }
    }

    public function hasChild($id) {
        if(isset($this->ref_data_by_id[$id])) {
            return !empty($this->ref_data_by_id[$id]['child']);
        } else {
            return null;
        }
    }

    public function hasVisibleChild($id) {
        if(isset($this->ref_data_by_id[$id])) {
            foreach($this->ref_data_by_id[$id]['child'] AS $child) {
                if($child['active'] == 1) {
                    return true;
                }
            }
            return false;
        } else {
            return null;
        }
    }

    public function isChildIf($child, $parent)
    {
        return $this->_isChildOf($child, $parent);
    }

    protected function _isChildOf($child, $parent)
    {
        $success = false;

        //if we're already on top, there are no parents
        if($child == 0) {
            return false;
        }
        //is the parent is the highest node, child id certainly unter parent
        if((0 == $parent) && (0 != $child)) {
            return true;
        }
        //should be clear
        if($child == $parent)
        {
            return false;
        }

        //begin walk from parent to child
        foreach($this->ref_child_by_id[$parent] AS $children) {
            //the child we were seaching for! there it is!
            if($child == $children['id']) {
                return true;
            }

            //walk trough children of child, ....
            $success = $this->_isChildOf($child, $children['id']);
            //if this walk was successful
            if(true === $success) {
                break;
            }
        }

        return $success;
    }

    public function moveNode($node, $destination)
    {
        //destination not under childs of node? lets go...
        if($this->_isChildOf($destination, $node)) {
            return false;
        //you cannot move sth under itself
        } else if ($node == $destination) {
            return false;
        }

        $query = "UPDATE `".DB_PRE."sys_content`
                  SET `m_pid` = ".intval($destination)."
                  WHERE `id` = ".intval($node)."
                  LIMIT 1";

        $success = $this->database->execute($query);

        $this->_reloadMenu();

        return $success;
    }
}
