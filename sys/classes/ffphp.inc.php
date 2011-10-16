<?php

/**
 * ffphp
 *
 * Creates valid xHTML forms and ckecks
 * them for integrity and correctness.
 * You can add your own error messages
 * and they will be added directly into
 * the form. The data, the user has already
 * entered, can also be imported. Go to the
 * website for examples.
 * 
 * @author Klemens Schölhorn <klemens@bayern-mail.de>
 * @copyright 2009 Klemens Schölhorn
 * @license http://creativecommons.org/licenses/by-sa/3.0/
 * @version 0.1
 */
class ffphp
{
    protected $current_id = 0;
    
    protected $form_info = array();
    protected $highlight_row;
    protected $hidden_info_send = false;
    protected $used_mixed_file = false;
    
    protected $required_options = array();
    protected $allowed_types = array(   'input_singleline',
                                        'input_multiline',
                                        'select_list',
                                        'select_list_group',
                                        'select_radio',
                                        'select_check',
                                        'mixed_file',
                                        'mixed_hidden',
                                        'button');
    
    protected $elements = array();
    
    protected $error = false;
    
    protected $IDs = array();
    
    protected $lang = 'en';
    protected $locate = array('en' => array(  1 => 'You have to fill in this field!',
                                              2 => 'Your input does not have the right format!',
                                              3 => 'You have to select an item!',
                                              4 => 'You have to select %s items!',
                                              5 => 'You have to select less than %s items!',
                                              6 => 'You have to select more than %s items!',
                                              7 => 'Your file upload was not successful! Try a second time or contact Administrator!',
                                              8 => 'You have to upload a file!'),
                              'de' => array(  1 => 'Sie müssen dieses Feld ausfüllen!',
                                              2 => 'Ihre Eingabe hat nicht das passende Format!',
                                              3 => 'Sie müssen einen Eintrag auswählen!',
                                              4 => 'Sie müssen %s Einträge auswählen!',
                                              5 => 'Sie müssen weniger als %s Einträge auswählen!',
                                              6 => 'Sie müssen mehr als %s Einträge auswählen!',
                                              7 => 'Ihr Datei-Upload war nicht erfolgreich! Probieren Sie es ein zweites Mal oder kontaktieren Sie den Administrator!',
                                              8 => 'Sie müssen eine Datei hochladen!'));
    
    const LF = "\n";
    const NL = "\r\n";
    
    /**
     * Setting of some params for the form
     *
     * The constructor sets the basic parameters
     * for the form. If you have more the one
     * form on one xHTML-site, kepp in mind that
     * it will only work, if the $unq_ip is really
     * unique! The $highlight_row setting will
     * highlight every second line of the form,
     * so that it can be filled in more easily.
     * The form created has th xHTML-class 'ffphp'
     * by default. If you want to add another,
     * provide them in $html_class.
     *
     * @param int $unq_id A unique id for this form
     * @param string $action The action attribute of the xHTML form (eg $_SERVER['PHP_SELF'])
     * @param string $method The transmission method (either 'get' or 'post')
     * @param bool $highlight_row Turn on highlighting of every sevond row
     * @param array $html_class Additional xHTML classes
     * @return void
     * @throws exception
     */
    public function __construct($unq_id, $action, $method = 'post', $highlight_row = true, $html_class = array())
    {
        if(empty($action)) {
            throw new exception('ffphp: Target url (action) not set correctly!');
        }
        if(!is_int($unq_id) OR (0 === $unq_id) OR (0 > $unq_id)) {
            throw new exception('ffpph: Unique ID not set correctly!');
        }
        
        $this->current_id = 100 * $unq_id;
        
        $this->form_info['action']           = trim($action);
        $this->form_info['method']           = trim($method);
        $this->highlight_row                 = $highlight_row ? true : false;
        $this->form_info['html_class']       = (!empty($html_class) AND is_array($html_class)) ? $html_class : array();
        $this->form_info['unq_id']           = $unq_id;
                
        $this->_setRequiredOptions();
    }
    
    /**
     * Select language
     *
     * Sets the language the error messages will
     * appear in. By default there are two available
     * langs: 'de' for german and 'en' for english.
     * Others can be added with ffphp::addLang().
     * The method will return false if there is no
     * such language else true.
     *
     * @param string $lang The language abbreviation (eg 'en' or 'de')
     * @return bool Success of the operation
     */
    public function setLang($lang)
    {
        if(!isset($this->locate[trim($lang)])) {
            return false;
        }
        
        $this->lang = trim($lang);
        
        return true;
    }
    
    /**
     * Add another language
     *
     * With this method you can add additional languages.
     * The lang array has to consist of exactly 9 messages
     * with keys from 1 to 8.
     *
     * @param string $name The abbreviation of the language (eg 'en' oder 'de')
     * @return bool Success of the operation
     */
    public function addLang($name, $array)
    {
        $name = trim($name);
        
        if(empty($name)) {
            return false;
        }
        if(!is_array($array) OR empty($array)) {
            return false;
        }
        if(isset($this->locate[$name])) {
            return false;
        }
        if(count($array) !== count($this->locate['en'])) {
            return false;
        }
        
        $this->locate[$name] = $array;
        return true;
    }
    
    /**
     * Add a new fieldset
     *
     * With this method you can add a xHTML fieldset
     * to the form. The first element of a form has
     * to be a fieldset. It is possible to add
     * a fieldset without a name. Then it will not
     * have a legend. If you add xHTML form elements
     * before adding a fieldset, such a fieldset
     * without a legend will be added automatically.
     * The function will return a unique id of that
     * form element, but in this version this does
     * not have any relevance.
     *
     * @param string $legend The legend for the filedset
     * @param array $html_class Additional xHTML classes
     * @return int Unique id
     */
    public function addFieldset($legend = '', $id = null, $html_class = array())
    {
        $legend = trim($legend);
        
        if('' === $legend) {
            $legend = false;
        }
        
        $entry = array();
        $entry['type']        = 'fieldset';
        $entry['legend']      = $legend;
        $entry['id']          = $id;
        $entry['html_class']  = (!empty($html_class) AND is_array($html_class)) ? $html_class : array();
        $entry['options']     = null;
        
        $this->elements[++$this->current_id] = $entry;
        
        return $this->current_id;
    }
    
    /**
     * Add a new form field
     *
     * With this method you can add every kind of
     * xHTML input, button or select field to the form.
     * For the syntax of the $options array, look into
     * the examples! If you set $required, the user has,
     * to input or select something. and if you want to
     * check this input or selection, you can use the
     * $check array. For the syntax also look into the
     * examples. This function returns a unique id of
     * that form field, with that you can add your own
     * errors later.
     *
     * @param string $type The type of field (eg 'input_singleline' or 'button')
     * @param array $options The parameters that describe the xHTML form element
     * @param bool $required Whether the user has to take action or not
     * @param bool|array $ckeck Flase or an array with check instructions
     * @return int Unique id
     * @throws exception
     */
    public function addField($type, $options, $required = false, $check = false)
    {
        $type = trim($type);
    
        if(empty($type) OR empty($options)) {
            throw new exception('ffphp: Type and options array can not be empty');
        }
        
        if(!in_array($type, $this->allowed_types)) {
            throw new exception('ffphp: This type doesnt exist!');
        }
        
        if(!$this->_isComplete($options, $type)) {
            throw new exception('ffphp: The options array isnt complete!');
        }
        
        
        if(empty($this->elements)) {
            $this->addFieldset();
        }
        
        $entry = array();
        $entry['type'] = $type;
        $entry['options'] = $options;
        $entry['required'] = $required;
        $entry['check'] = empty($check) ? array() : $check;
        $entry['error'] = false;
        $entry['error_message'] = '';
        
        $this->elements[++$this->current_id] = $entry;
        
        if('mixed_file' == $type) {
            $this->used_mixed_file = true;
        }
        
        return $this->current_id;
    }
    
    /**
     * Add your own error
     *
     * With this method you cann add errors to the
     * form element specified with the $unq_id you got
     * from ffphp::addField(). For example if you have
     * two password fiels and they are not matching,
     * you can show a error message in the form.
     *
     * @param int $unq_id The unique id from ffphp::addField
     * @param string $error_message Your personal error message
     * @return bool Whether the $unq_id existed or not
     */
    public function addError($unq_id, $error_message)
    {
        if(!isset($this->elements[$unq_id])) {
            return false;
        }
        
        $this->elements[$unq_id]['error'] = true;
        $this->elements[$unq_id]['error_message'] = $error_message;
        
        $this->error = true;
        
        return true;
    }
    
    /**
     * Was the form sent?
     *
     * This method checks whether the user has submitted
     * the form or not. This works with the unique form
     * id you specified in ffphp::_contruct().
     *
     * @return bool Whether the form was sent or not
     */
    public function checkFormSent()
    {
        return (isset($_REQUEST['ffphp-form-sent']) AND
                (md5('ffphp'.$this->form_info['unq_id']) === $_REQUEST['ffphp-form-sent']));
    }
    
    /**
     * Check whether the form is complete
     *
     * This method ckecks if the user has entered all
     * the information you required and if the user's
     * inputs match your requirements (the check array
     * in ffphp::addField()).
     * 
     * @throws exception
     * @return bool Whether the form is complete or not
     */
    public function checkFormComplete()
    {
        $error = array();
        
        foreach($this->elements AS &$element) {
            unset($select_id);
            unset($select_check);
            unset($select_array_count);
            unset($select_count);
            
            if(empty($element['required']) AND empty($element['check'])) {
                continue;
            }
                        
            switch($element['type']) {
                case 'fieldset':
                case 'mixed_hidden':
                case 'button':
                    continue 2;
                
                case 'input_singleline':
                case 'input_multiline':
                    if(empty($_REQUEST[$element['options']['id']])) {
                        $element['error'] = true;
                        $element['error_message'] = $this->_errorMessage(1);
                        
                        $this->error = true;
                        
                        continue 2;
                    }
                    
                    
                    
                    if(isset($element['check']['regex'])) {
                        if(!preg_match($element['check']['regex'],
                                       $_REQUEST[$element['options']['id']])) {
                            $element['error'] = true;
                            $element['error_message'] = !empty($element['check']['message'])
                                                        ? $element['check']['message'] : $this->_errorMessage(2);
                            
                            $this->error = true;
                            
                            continue 2;
                        }
                    }
                    break;
                
                case 'select_radio':
                case 'select_check':
                case 'select_list':
                case 'select_list_group':
                    
                    if(('select_radio' === $element['type'])
                       OR ('select_check' === $element['type'])) {
                        $select_id = $element['options']['name'];
                    } else {
                        $select_id = $element['options']['id'];
                    }
                    
                    if(empty($_REQUEST[$select_id])) {
                        $element['error'] = true;
                        $element['error_message'] = $this->_errorMessage(3);
                        
                        $this->error = true;
                        
                        continue 2;
                    }
                    if(isset($element['check']['count'])) {
                        $select_check = substr($element['check']['count'], 0, 1);
                        $select_count = (int)substr($element['check']['count'], 1);
                        
                        $select_array_count = count($_REQUEST[$select_id]);
                        
                        switch($select_check) {
                            case '=':
                                if($select_count !== $select_array_count) {
                                    $element['error'] = true;
                                    $element['error_message'] = !empty($element['check']['message'])
                                                                ? $element['check']['message'] : $this->_errorMessage(4, $select_count);
                                    
                                    $this->error = true;
                                }
                                break;
                            
                            case '<':
                                if($select_array_count >= $select_count) {
                                    $element['error'] = true;
                                    $element['error_message'] = !empty($element['check']['message'])
                                                                ? $element['check']['message'] : $this->_errorMessage(5, $select_count);
                                    
                                    $this->error = true;
                                }
                                break;
                            
                            case '>':
                                if($select_array_count <= $select_count) {
                                    $element['error'] = true;
                                    $element['error_message'] = !empty($element['check']['message'])
                                                                ? $element['check']['message'] : $this->_errorMessage(6, $select_count);
                                    
                                    $this->error = true;
                                }
                                break;
                            
                            default:
                                throw new exception('ffphp: No such comparator like: '.$select_check);
                        }
                        
                        continue 2;
                    }
                    break;
                
                case 'mixed_file':
                    if(isset($_FILES[$element['options']['id']])) {
                        if(0 === $_FILES[$element['options']['id']]['error']) {
                            continue 2;
                        } else if(4 === $_FILES[$element['options']['id']]['error']) {
                            $element['error'] = true;
                            $element['error_message'] = $this->_errorMessage(8);
                        } else {
                            $element['error'] = true;
                            $element['error_message'] = $this->_errorMessage(7);
                        }
                    } else {
                        $element['error'] = true;
                        $element['error_message'] = $this->_errorMessage(8);
                    }
                    
                    $this->error = true;
                    
                    break;
                
                default:
                    throw new exception('ffphp: Code Error! Contact Admin! (Debug: '.$element['type'].')');
            }
        }
        
        return !$this->error;
    }
    
    /**
     * Assign user's input and selection to the form
     * 
     * If you display a form a second time, because
     * is was not complete, you can assign the input
     * and selection of the user to the form.
     *
     * @throws exception
     * @return void
     */
    public function assignSelection()
    {
        if(!$this->checkFormSent()) {
            throw new exception('ffphp: Dont try to assign Selection if form was not sent!');
        }
        
        foreach($this->elements AS &$element) {
            if(isset($element['options'])) {
                $options = &$element['options'];
            }
            
            switch($element['type']) {
                
                case 'mixed_hidden':
                case 'input_singleline':
                    if(isset($options['password']) && (true == $options['password'])) {
                        break;
                    }
                    if(isset($_REQUEST[$options['id']])) {
                        $options['value'] = $_REQUEST[$options['id']];
                    }
                    break;
                
                case 'input_multiline':
                    if(isset($_REQUEST[$options['id']])) {
                        $options['text'] = $_REQUEST[$options['id']];
                    }
                    break;
                
                case 'select_radio':
                    foreach($options['elements'] AS &$element_radio) {
                        if(isset($_REQUEST[$options['name']]) AND ($_REQUEST[$options['name']] == $element_radio['value'])) {
                            $element_radio['flag'] =
                                $this->_addFlag(isset($element_radio['flag']) ?  $element_radio['flag'] : '', 'checked');
                        } else {
                            $element_radio['flag'] =
                                $this->_removeFlag(isset($element_radio['flag']) ? $element_radio['flag'] : '', 'checked');
                        }
                    }
                    break;
                
                case 'select_check':
                    $clear_check = false;
                    if(empty($_REQUEST[$options['name']])) {
                        $clear_check = true;
                    }
                    
                    foreach($options['elements'] AS &$element_check) {
                        if($clear_check) {
                            $element_check['flag'] =
                                $this->_removeFlag(isset($element_check['flag']) ? $element_check['flag'] : '', 'checked');
                            continue;
                        }
                        if(in_array($element_check['value'], $_REQUEST[$options['name']])) {
                            $element_check['flag'] =
                                $this->_addFlag(isset($element_check['flag']) ?  $element_check['flag'] : '', 'checked');
                        } else {
                            $element_check['flag'] =
                                $this->_removeFlag(isset($element_check['flag']) ? $element_check['flag'] : '', 'checked');
                        }
                    }
                    break;
                
                case 'select_list':
                    if(isset($options['flag']) AND $this->_hasFlag($options['flag'], 'multiple')) {
                        $clear_list = false;
                        if(empty($_REQUEST[$options['id']])) {
                            $clear_list = true;
                        }
                        
                        foreach($options['elements'] AS &$element_list) {
                            if($clear_list) {
                                $element_list['flag'] =
                                    $this->_removeFlag(isset($element_list['flag']) ? $element_list['flag'] : '', 'selected');
                                continue;
                            }
                            if(in_array($element_list['value'], $_REQUEST[$options['id']])) {
                                $element_list['flag'] =
                                    $this->_addFlag(isset($element_list['flag']) ?  $element_list['flag'] : '', 'selected');
                            } else {
                                $element_list['flag'] =
                                    $this->_removeFlag(isset($element_list['flag']) ? $element_list['flag'] : '', 'selected');
                            }
                        }
                    } else {
                        foreach($options['elements'] AS &$element_list) {
                            if(isset($_REQUEST[$options['id']]) AND ($_REQUEST[$options['id']] == $element_list['value'])) {
                                $element_list['flag'] =
                                    $this->_addFlag(isset($element_list['flag']) ?  $element_list['flag'] : '', 'selected');
                            } else {
                                $element_list['flag'] =
                                    $this->_removeFlag(isset($element_list['flag']) ? $element_list['flag'] : '', 'selected');
                            }
                        }
                    }
                    break;
                
                case 'select_list_group':
                    if(isset($options['flag']) AND $this->_hasFlag($options['flag'], 'multiple')) {
                        $clear_list_group = false;
                        if(empty($_REQUEST[$options['id']])) {
                            $clear_list_group = true;
                        }
                        
                        foreach($options['elements'] AS &$element_list_group) {
                            foreach($element_list_group['options'] AS &$element_list_group2) {
                                if($clear_list_group) {
                                    $element_list_group2['flag'] =
                                        $this->_removeFlag(isset($element_list_group2['flag']) ? $element_list_group2['flag'] : '', 'selected');
                                    continue;
                                }
                                if(in_array($element_list_group2['value'], $_REQUEST[$options['id']])) {
                                    $element_list_group2['flag'] =
                                        $this->_addFlag(isset($element_list_group2['flag']) ?  $element_list_group2['flag'] : '', 'selected');
                                } else {
                                    $element_list_group2['flag'] =
                                        $this->_removeFlag(isset($element_list_group2['flag']) ? $element_list_group2['flag'] : '', 'selected');
                                }
                            }
                        }

                    } else {
                        foreach($options['elements'] AS &$element_list_group) {
                            foreach($element_list_group['options'] AS &$element_list_group2) {
                                if(isset($_REQUEST[$options['id']]) AND ($_REQUEST[$options['id']] == $element_list_group2['value'])) {
                                    $element_list_group2['flag'] =
                                        $this->_addFlag(isset($element_list_group2['flag']) ?  $element_list_group2['flag'] : '', 'selected');
                                } else {
                                    $element_list_group2['flag'] =
                                        $this->_removeFlag(isset($element_list_group2['flag']) ? $element_list_group2['flag'] : '', 'selected');
                                }
                            }
                        }

                    }
                    break;
                
                case 'fieldset':
                case 'button':
                case 'mixed_file':
                    break;
                
                default:
                    throw new exception('ffphp: Code Error! Contact Admin! (Debug: '.$element['type'].')');
            }
        }
    }
    
    /**
     * Get Error
     *
     * This method returns whether the is an error
     * with the data of the form or not.
     * Both the check arrays and ffphp::addError()
     * influence to the error.
     *
     * @return bool Whether an error occured or not.
     */
    public function getError()
    {
        return $this->error;
    }
    
    /**
     * Get the copmlete form in xHTML
     *
     * This method returns the complete form in valid
     * xHTML including the errors. If file fields are used
     * in the form, 'enctype="multipart/form-data"' is
     * automaticly addad to the form element and the method
     * is set to POST.
     * 
     * @return string Complete form in xHTML
     */
    public function getHTML()
    {
        if(!$this->hidden_info_send) {
            $this->hidden_info_send = true;
            $this->addField('mixed_hidden', array('id' => 'ffphp-form-sent',
                                                  'value' => md5('ffphp'.$this->form_info['unq_id'])));
        }
        
        if($this->used_mixed_file) {
            $this->form_info['method'] = 'post';
        }
        
        $ret  = sprintf('<form%s method="%s" action="%s" accept-charset="UTF-8" class="%s">'.self::LF,
                            $this->used_mixed_file ? ' enctype="multipart/form-data"' : '',
                            $this->form_info['method'],
                            $this->form_info['action'],
                            trim('ffphp '.implode(' ', $this->form_info['html_class'])));
        
        $ret .= $this->_parseElements();
        
        $ret .= '</form>'."\n";
        
        return $ret;
    }
    
    /**
     * @ignore
     */
    protected function _checkForRequired($options, $check)
    {
        $valid = true;
        
        foreach($check AS $key => $value) {
            
            if(is_array($value)) {
                foreach($options[$key] AS $sub_array) {
                    $valid = $this->_checkForRequired($sub_array, $value[0]);
                    if(false === $valid) {
                        return false;
                    }
                }
                continue;
            }
        
            if(!isset($options[$value])) {
                return false;
            } 
        }
        
        return $valid;
    }
    
    /**
     * Checks whether the options array is complete
     *
     * This method checks, if the options array is
     * complete. It uses the array specified in
     * ffphp::_setRequiredOptions()
     *
     * @see ffphp::_setRequiredOptions()
     * @return bool 
     */
    protected function _isComplete($options, $type)
    {
        $type = trim($type);
        if(!isset($this->required_options[$type])) {
            return false;
        }
        
        return $this->_checkForRequired($options, $this->required_options[$type]);
    }
    
    /**
     * Creates the xHTML code
     * 
     * This method is called by ffphp::getHTML().
     *
     * @see ffphp::getHTML()
     * @return string The xHTML code without the form element
     */
    protected function _parseElements()
    {
        if(empty($this->elements)) {
            return false;
        }
                
        static $open_fieldset;
        static $highlight = false;
        $ret = '';
        $add = '';
        $but = '';
        
        foreach($this->elements AS $element) {
            $type = $element['type'];
            $options = $element['options'];
            
            if(isset($element['required']) && $element['required']) {
                if(isset($options['label'])) {
                    $options['label'] .= ' <em title="Eingabe benötigt!">*</em>';
                }
            }
            
            if('mixed_hidden' === $type) {
                $tid = $this->_ID($options['id']);
                
                $add .= '<input type="hidden" id="'.$tid.'" name="'.$tid.'"';
                
                if(isset($options['value'])) {
                    $add .= ' value="'.$this->_htmlChars($options['value']).'"';
                }
                
                $add .= ' />'.self::LF;
                
                continue;
            }
            
            if('button' === $type) {
                $tid = $this->_ID($options['id']);
                
                $but .= '<fieldset class="ffphp-button">'.self::LF.'<ol>';
                $but .= self::LF.'<li>'.self::LF;
                
                $but .= '<button id="'.$tid.'" name="'.$tid.'"';
                
                $action_type = ('reset' === $options['type']) ? 'reset' : 'submit';
                
                $but .= ' type="'.$action_type.'"';
                
                if(isset($options['value'])) {
                    $but .= ' value="'.$options['value'].'"';
                }
                
                if(isset($options['flag'])) {
                    $but .= $this->_flags($options['flag']);
                }
                
                $but .= '>';
                
                $but .= $options['text'];
                
                $but .= '</button>';
                
                $but .= '</li>'.self::LF.'</ol>'.self::LF;
                $but .= '</fieldset>'.self::LF;
                
                continue;
            }
            
            
            if((true === $this->highlight_row) && ('fieldset' !== $type)) {
                $ret .= '<li';
                
                if($highlight) {
                    $highlight = false;
                    $ret .= ' class="ffphp-r2"';
                } else {
                    $highlight = true;
                    $ret .= ' class="ffphp-r1"';
                }
                
                $ret .= '>'.self::LF;
            } else if('fieldset' !== $type) {
                $ret .= '<li>'.self::LF;
            }
            
            
            switch($type) {
                case 'fieldset':
                    if($open_fieldset) {
                        $ret .= '</ol>'.self::LF;
                        $ret .= '</fieldset>'.self::LF;
                    }
                    $ret .= '<fieldset';
                    
                    if($element['html_class']) {
                        $ret .= ' class="'.implode(' ', $element['html_class']).'"';
                    }
                    
                    if($element['id']) {
                        $ret .= ' id="'.$element['id'].'"';
                    }
                    
                    $ret .= '>'.self::LF;
                    
                    if($element['legend']) {
                        $ret .= '<legend>'.$element['legend'].'</legend>'.self::LF;
                    }
                    
                    $ret .= '<ol>'.self::LF;
                    
                    $open_fieldset = true;
                    break;
                
                case 'input_singleline':
                    $tid = $this->_ID($options['id']);
                    
                    $ret .= '<label for="'.$tid.'">'.$options['label'].'</label>'.self::LF;
                    $ret .= '<input id="'.$tid.'" name="'.$tid.'"';
                    
                    if(isset($options['password']) && (true == $options['password'])) {
                        $ret .= ' type="password"';
                    } else {
                        $ret .= ' type="text"';
                    }
                    
                    if(isset($options['value'])) {
                        $ret .= ' value="'.$this->_htmlChars($options['value']).'"';
                    }
                    
                    if(isset($options['maxlength'])) {
                        $ret .= ' maxlength="'.$options['maxlength'].'"';
                    }
                    
                    if(isset($options['flag'])) {
                        $ret .= $this->_flags($options['flag']);
                    }
                    
                    if($element['error']) {
                        $ret .= ' class="ffphp-error"';
                    }
                    
                    $ret .= ' />'.self::LF;
                    
                    if($element['error_message']) {
                        $ret .= '<em class="ffphp-error">'.$element['error_message'].'</em>'.self::LF;
                    }
                    
                    break;
                
                case 'input_multiline':
                    $tid = $this->_ID($options['id']);
                    
                    $ret .= '<label for="'.$tid.'">'.$options['label'].'</label>'.self::LF;
                    $ret .= '<textarea id="'.$tid.'" name="'.$tid.'"';
                    
                    $cols = isset($options['cols']) ? $options['cols'] : 15;
                    $rows = isset($options['rows']) ? $options['rows'] : 4;
                    
                    $ret .= ' cols="'.$cols.'" rows="'.$rows.'"';
                    
                    if(isset($options['flag'])) {
                        $ret .= $this->_flags($options['flag']);
                    }
                    
                    if($element['error']) {
                        $ret .= ' class="ffphp-error"';
                    }
                    
                    $ret .= '>';
                    
                    if(isset($options['text'])) {
                        $ret .= $this->_htmlChars($options['text']);
                    }
                    
                    $ret .= '</textarea>'.self::LF;
                    
                    if($element['error_message']) {
                        $ret .= '<em class="ffphp-error">'.$element['error_message'].'</em>'.self::LF;
                    }
                    
                    break;
                
                case 'select_list':
                    $tid = $this->_ID($options['id']);
                    
                    $ret .= '<label for="'.$tid.'">'.$options['label'].'</label>'.self::LF;
                    
                    $ret .= '<select id="'.$tid.'" name="'.
                            ((isset($options['flag']) && $this->_hasFlag($options['flag'], 'multiple')) ? $tid.'[]' : $tid).
                            '"';
                    
                    $size = isset($options['size']) ? $options['size'] : 1;
                    
                    $ret .= ' size="'.$size.'"';
                    
                    if(isset($options['flag'])) {
                        $ret .= $this->_flags($options['flag']);
                    }
                    
                    if($element['error']) {
                        $ret .= ' class="ffphp-error"';
                    }
                    
                    $ret .= '>'.self::LF;
                    
                    foreach($options['elements'] AS $select_option) {
                        $ret .= '<option value="'.$select_option['value'].'"';
                        
                        if(isset($select_option['flag'])) {
                            $ret .= $this->_flags($select_option['flag']);
                        }
                        
                        $ret .= '>'.$select_option['title'].'</option>'.self::LF;
                    }
                    
                    $ret .= '</select>'.self::LF;
                    
                    if($element['error_message']) {
                        $ret .= '<em class="ffphp-error">'.$element['error_message'].'</em>'.self::LF;
                    }
                    
                    break;
                
                case 'select_list_group':
                    $tid = $this->_ID($options['id']);
                    
                    $ret .= '<label for="'.$tid.'">'.$options['label'].'</label>'.self::LF;
                    
                    $ret .= '<select id="'.$tid.'" name="'.
                            ((isset($options['flag']) && $this->_hasFlag($options['flag'], 'multiple')) ? $tid.'[]' : $tid).
                            '"';
                    
                    $size = isset($options['size']) ? $options['size'] : 1;
                    
                    $ret .= ' size="'.$size.'"';
                    
                    if(isset($options['flag'])) {
                        $ret .= $this->_flags($options['flag']);
                    }
                    
                    if($element['error']) {
                        $ret .= ' class="ffphp-error"';
                    }
                    
                    $ret .= '>'.self::LF;
                    
                    foreach($options['elements'] AS $select_group_group) {
                        $ret .= '<optgroup label="'.$select_group_group['label'].'"';
                        
                        if(isset($select_group_group['flag'])) {
                            $ret .= $this->_flags($select_group_group['flag']);
                        }
                        
                        $ret .= '>'.self::LF;
                        
                        foreach($select_group_group['options'] AS $select_group_option) {
                            $ret .= '<option value="'.$select_group_option['value'].'"';
                            
                            if(isset($select_group_option['flag'])) {
                                $ret .= $this->_flags($select_group_option['flag']);
                            }
                            
                            $ret .= '>'.$select_group_option['title'].'</option>'.self::LF;
                            }
                        
                        $ret .= '</optgroup>'.self::LF;
                    }
                    
                    $ret .= '</select>'.self::LF;
                    
                    if($element['error_message']) {
                        $ret .= '<em class="ffphp-error">'.$element['error_message'].'</em>'.self::LF;
                    }
                    
                    break;
                
                case 'select_radio':
                    $ret .= '<fieldset>'.self::LF;
                    
                    $ret .= '<legend';
                    
                    if($element['error']) {
                        $ret .= ' class="ffphp-error"';
                    }
                    
                    $ret .= '>'.$options['label'].'</legend>'.self::LF;
                    
                    if($element['error_message']) {
                        $ret .= '<em class="ffphp-error">'.$element['error_message'].'</em>'.self::LF;
                    }
                    
                    $name = $options['name'];
                    $count = 1;
                    
                    foreach($options['elements'] AS $input_radio) {
                        $tid = $this->_ID($name.'-'.$count++);
                        
                        $ret .= '<label for="'.$tid.'">'.self::LF;
                        
                        $ret .= '<input type="radio" name="'.$name.'" id="'.$tid.'" value="'.$input_radio['value'].'"';
                        
                        if(isset($input_radio['flag'])) {
                            $ret .= $this->_flags($input_radio['flag']);
                        }
                        
                        $ret .= ' />'.self::LF;
                        
                        $ret .= $input_radio['label'].self::LF;
                        
                        $ret .= '</label>'.self::LF;
                    }
                    
                    $ret .= '</fieldset>';
                    break;
                
                case 'select_check':
                    $ret .= '<fieldset>'.self::LF;
                    
                    $ret .= '<legend';
                    
                    if($element['error']) {
                        $ret .= ' class="ffphp-error"';
                    }
                    
                    $ret .= '>'.$options['label'].'</legend>'.self::LF;
                    
                    if($element['error_message']) {
                        $ret .= '<em class="ffphp-error">'.$element['error_message'].'</em>'.self::LF;
                    }
                    
                    $name = $options['name'];
                    $count = 1;
                    
                    foreach($options['elements'] AS $input_radio) {
                        $tid = $this->_ID($name.'-'.$count++);
                        
                        $ret .= '<label for="'.$tid.'">'.self::LF;
                        
                        $ret .= '<input type="checkbox" name="'.$name.'[]" id="'.$tid.'" value="'.$input_radio['value'].'"';
                        
                        if(isset($input_radio['flag'])) {
                            $ret .= $this->_flags($input_radio['flag']);
                        }
                        
                        $ret .= ' />'.self::LF;
                        
                        $ret .= $input_radio['label'].self::LF;
                        
                        $ret .= '</label>'.self::LF;
                    }
                    
                    $ret .= '</fieldset>';
                    break;
                
                case 'mixed_file':
                    $tid = $this->_ID($options['id']);
                    
                    $ret .= '<label for="'.$tid.'"';
                    
                    $ret .= '>'.$options['label'].'</label>'.self::LF;
                    
                    $ret .= '<input type="file" id="'.$tid.'" name="'.$tid.'"';
                    
                    if(isset($options['flag'])) {
                        $ret .= $this->_flags($options['flag']);
                    }
                    
                    $ret .= ' />'.self::LF;
                    
                    if($element['error_message']) {
                        $ret .= '<em class="ffphp-error">'.$element['error_message'].'</em>'.self::LF;
                    }
                    
                    break;
            }
            
            if((true === $this->highlight_row) && ('fieldset' !== $type)) {
                $ret .= '</li>'.self::LF;
            } else if('fieldset' !== $type) {
                $ret .= '</li>'.self::LF;
            }
        }
        
        if($open_fieldset) {
            $ret .= '</ol>'.self::LF;
            $ret .= '</fieldset>'.self::LF;
        }
        
        if($but) {
            $ret .= $but;
        }
        
        if($add) {
            $ret .= '<div>'.self::LF.$add.'</div>'.self::LF;
        }
        
        return $ret;
    }
    
    /**
     * Create an error message
     * 
     * This method retuns a readable error message
     * when you give it the id of the error. It can
     * also replace one placeholder.
     *
     * @see ffphp::$locate
     * @param int $error The id of the error
     * @param string $replace The replacement for the placeholder
     * @return string The readable error message
     * @throws exception
     */
    protected function _errorMessage($error, $replace = null)
    {
        if(!isset($this->locate[$this->lang][$error])) {
            throw new exception('ffphp: Could not create error message: '.$error);
        }
        
        if(empty($replace)) {
            return $this->locate[$this->lang][$error];
        } else {
            return sprintf($this->locate[$this->lang][$error], $replace);
        }
    }
    
    /**
     * Set the check routines for the options array
     *
     * This method sets the required_options array.
     * 
     * @see ffphp::$required_options
     * @return void
     */
    protected function _setRequiredOptions()
    {
        $req['input_singleline']  = array('label', 'id');
        $req['input_multiline']   = array('label', 'id');
        $req['select_list']       = array('label', 'id', 'elements' => array(array('title', 'value')));
        $req['select_list_group'] = array('label', 'id', 'elements' => array(array('label', 'options' => array(array('title', 'value')))));
        $req['select_radio']      = array('label', 'name', 'elements' => array(array('label', 'value')));
        $req['select_check']      = array('label', 'name', 'elements' => array(array('label', 'value')));
        $req['mixed_file']        = array('label', 'id');
        $req['mixed_hidden']      = array('id');
        $req['button']            = array('id', 'type', 'text');
        
        $this->required_options = $req;
    }
    
    /**
     * Save and check the ids of the form elements
     *
     * This method adds ids to a list and checks if
     * they are used several times. Every id can
     * only be used one time.
     * 
     * @param string $id The id
     * @return string The id
     * @throws exception
     */
    protected function _ID($id)
    {
        if(in_array($id, $this->IDs)) {
            throw new exception('ffphp: Dont use id two times!');
        }
        $this->IDs[] = $id;
        return $id;
    }
    
    /**
     * Create xHTML form flag group
     *
     * This method makes out of a flag group
     * (eg: 'checked|disabled') valid xHTML
     * flags (eg: 'checked="checked"'.
     * 
     * @param string $flags Flag group
     * @return string xHTML flags
     */
    protected function _flags($flags)
    {
        $ret = '';
                
        $flags = explode('|', $flags);
        
        foreach($flags AS $flag) {
            if(empty($flag)) {
                continue;
            }
            $ret .= ' '.$flag.'="'.$flag.'"';
        }
        
        return $ret;
    }
    
    /**
     * Check the flag group for the specified flag
     *
     * This method checks, if the flag $flag is in the
     * flag group $flags.
     *
     * @param string $flags The flag group
     * @param string $flag The flag th check for
     * @return bool Whether the flag group has the flag or not
     */
    protected function _hasFlag($flags, $flag)
    {
        $flags = explode('|', $flags);
        
        return in_array($flag, $flags);
    }
    
    /**
     * Add a flag to a flag group
     *
     * This method adds the specified flag to the flag group
     *
     * @param string $flags The flag group
     * @param string $flag_add The flag to add
     * @return string The modified flag group
     */
    protected function _addFlag($flags, $flag_add)
    {
        $flags = explode('|', $flags);
        
        if(!in_array($flag_add, $flags)) {
            $flags[] = $flag_add;
        }
        
        return implode('|', array_filter($flags));
    }
    
    /**
     * Remove a flag from a flag group
     *
     * This method removes the specified flag from flag group
     *
     * @param string $flags The flag group
     * @param string $flag_remove The flag to remove
     * @return string The modified flag group
     */
    protected function _removeFlag($flags, $flag_remove)
    {
        $flags = explode('|', $flags);
        
        if(in_array($flag_remove, $flags)) {
            unset($flags[array_search($flag_remove, $flags)]);
        }
        
        return implode('|', array_filter($flags));
    }
    
    /**
     * Alias for htmlspecialchars()
     *
     * This method calls htmlspecialchars() with
     * ENT_COMPAT and UTF-8 as encoding.
     *
     * @see htmlspecialchars()
     * @param string $string The string to escape
     * @return string The escaped string
     */
    protected function _htmlChars($string)
    {
        return htmlspecialchars($string, ENT_COMPAT, 'UTF-8');
    }
}

?>