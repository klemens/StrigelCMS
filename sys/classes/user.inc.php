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

class user
{
    private $loggedIn = false;

    private $userInformation;
    private $userInformationDef;
    
    private $rights = array();
    private $rightsLoaded = false;

    private $globalSalt;

    public function __construct($DB)
    {
        if($DB) {
            $this->DB = $DB;
        } else {
            throw new exception('no database given');
        }

        $this->userInformationDef = array('id'         => '',
                                          'username'   => '',
                                          'name'       => '',
                                          'email'      => '',
                                          'active'     => '');
        $this->userInformation = $this->userInformationDef;
        
        $this->globalSalt = 'salz';
    }

    public function __destruct()
    {
    }
    
    public function setSalt($salt)
    {
        $this->globalSalt = $salt;
        return true;
    }
    
    public function reLogin()
    {
        if($this->loggedIn)
            throw new exception('reLogin: already logged in');
        
        if(!$this->sessionLogin()) {
            $this->cookieLogin();
        }
        return $this->loggedIn;
    }

    public function login($username, $password, $autologin = false)
    {
        $q = $this->DB->query(sprintf("SELECT `%s` FROM `%ssys_user` WHERE `password` = SHA1(CONCAT(`salt`, '%s', '%s')) AND `username` = '%s' LIMIT 1",
                                      implode('`, `', array_keys($this->userInformation)), DB_PRE, $this->DB->escape($password),
                                      $this->DB->escape($this->globalSalt), $this->DB->escape($username)));

        if(0 === $q->num_rows()) {
            $q = null;
            throw new exception('login: nothing found');
        } else if(1 === $q->num_rows()) {
            $r = $q->fetch(SQL_ASSOC_ARRAY);
            if(!$r['active']) {
                $q = null;
                throw new exception('login: not active');
            }
        }
        
        $this->loggedIn = true;
        $this->userInformation = $r;
        
        session_regenerate_id(true);
        $_SESSION['loggedIn'] = true;
        $_SESSION['userID'] = $r['id'];
        $_SESSION['userName'] = $r['username'];
        $_SESSION['securityToken'] = sha1($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
        
        $q = null;
        
        if($autologin) {
            $expire = time() + 60*60*24*60;
            do {
                $uniqid = sha1(uniqid(mt_rand(), true));
                $this->DB->execute(sprintf("INSERT IGNORE INTO `%ssys_autologin` SET `uniqid` = '%s', `userID` = %s, `expire` = %s",
                                           DB_PRE, $uniqid, $this->userInformation['id'], $expire));
            } while(!$this->DB->affected_rows());
            
            setcookie('autologin', $uniqid, $expire, parse_url(SCRIPT, PHP_URL_PATH), '', false, true);
        }
        
        return true;
    }
    
    private function sessionLogin()
    {
        if(!isset($_SESSION['loggedIn']) || !isset($_SESSION['userID']) ||
           !isset($_SESSION['securityToken']) || !isset($_SESSION['userName']))
            return false;
        
        if($_SESSION['securityToken'] !== sha1($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'])) {
            $this->logout();
            throw new exception('sessionLogin: wrong securityToken');
        }
        
        $q = $this->DB->query(sprintf("SELECT `%s` FROM `%ssys_user` WHERE `id` = %s AND `username` = '%s'",
                                      implode('`, `', array_keys($this->userInformation)), DB_PRE,
                                      intval($_SESSION['userID']), $this->DB->escape($_SESSION['userName'])));
        
        if(0 === $q->num_rows()) {
            $q = null;
            throw new exception('sessionLogin: not found');
        }
        
        $r = $q->fetch(SQL_ASSOC_ARRAY);
        
        $this->loggedIn = true;
        $this->userInformation = $r;
        
        session_regenerate_id(true);
        $_SESSION['loggedIn'] = true;
        $_SESSION['userID'] = $r['id'];
        $_SESSION['userName'] = $r['username'];
        $_SESSION['securityToken'] = sha1($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
        
        $q = null;
        return true;
    }
    
    private function cookieLogin()
    {
        if(empty($_COOKIE['autologin']))
            return false;
        
        $q = $this->DB->query(sprintf("SELECT `userID` FROM `%ssys_autologin` WHERE `uniqid` = '%s' AND `expire` > UNIX_TIMESTAMP()",
                                      DB_PRE, $_COOKIE['autologin']));
        
        if(0 === $q->num_rows()) {
            $q = null;
            return false;
        }
        
        $id = $q->fetch()->userID;
        $q = $this->DB->query(sprintf("SELECT `%s` FROM `%ssys_user` WHERE `id` = %s",
                                      implode('`, `', array_keys($this->userInformation)), DB_PRE, $id));
        
        if(0 === $q->num_rows()) {
            $q = null;
            throw new exception('cookieLogin: not found');
        }
        
        $r = $q->fetch(SQL_ASSOC_ARRAY);
        
        $this->loggedIn = true;
        $this->userInformation = $r;
        
        session_regenerate_id(true);
        $_SESSION['loggedIn'] = true;
        $_SESSION['userID'] = $r['id'];
        $_SESSION['userName'] = $r['username'];
        $_SESSION['securityToken'] = sha1($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
        
        $q = null;
        return true;
    }
    
    public function logout()
    {
        if(!empty($_COOKIE['autologin'])) {
            $this->DB->execute(sprintf("DELETE FROM `%ssys_autologin` WHERE (`userID` = %s AND `uniqid` = '%s') OR (`expire` < UNIX_TIMESTAMP())",
                                       DB_PRE, $this->userInformation['id'], $_COOKIE['autologin']));
            setcookie('autologin', '', time() - 3600*25, parse_url(SCRIPT, PHP_URL_PATH), '', false, true);
        }
        
        $this->loggedIn = false;
        $this->userInformation = $this->userInformationDef;

        session_regenerate_id(true);
        unset($_SESSION['loggedIn']);
        unset($_SESSION['userID']);
        unset($_SESSION['userName']);
        unset($_SESSION['securityToken']);
        
        return true;
    }
    
    public function register($username, $name, $password, $email, $active = 0)
    {
        if($this->loggedIn)
            throw new exception('register: already registered');
        
        $username = trim($username);
        $name = trim($name);
        $email = trim($email);
        $active = $active ? 1 : 0;
        if(empty($username) || empty($password) || empty($email))
            throw new exception('register: corrupt data');
        
        $salt = $this->genSalt();
        $activationCode = $this->genSalt(15);
        
        $success = $this->DB->execute(sprintf("INSERT INTO `%ssys_user` SET `username` = '%s', `name` = '%s', `password` = SHA1('%s'), `email` = '%s', `active` = %s, `activation_code` = SHA1('%s'), `salt` = '%s'",
                                              DB_PRE, $this->DB->escape($username), $this->DB->escape($name), $this->DB->escape($salt.$password.$this->globalSalt), $this->DB->escape($email), $active,
                                              $this->DB->escape($activationCode), $this->DB->escape($salt)));
        
        return $success;
    }

    public function hasRight($right)
    {
        if(!$this->loggedIn || empty($this->userInformation['id']))
            return false;
        
        if(!$this->rightsLoaded) {
            $q = $this->DB->query(sprintf("SELECT `right`, `value` FROM %ssys_rights WHERE `userID` = %s",
                                          DB_PRE, $this->userInformation['id']));
            
            while(false !== ($r = $q->fetch())) {
                if(empty($r->right))
                    continue;
                
                $this->rights[$r->right] = ($r->value === null) ? true : $r->value;
            }
            
            $this->rightsLoaded = true;
        }
        
        if(isset($this->rights[$right]))
            return true;
        else
            return false;
    }
    
    public function getRightValue($right)
    {
        if($this->hasRight($right)) {
            if(true !== $this->rights[$right]) {
                return $this->rights[$right];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    public function loggedIn()
    {
        return $this->loggedIn;
    }
    
    public function getUsername()
    {
        if($this->loggedIn) {
            return $this->userInformation['username'];
        } else {
            return false;
        }
    }
    
    public function getName()
    {
        if($this->loggedIn) {
            return $this->userInformation['name'];
        } else {
            return false;
        }
    }
    
    public static function genSalt($length = 3)
    {
        static $source = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789,.-;:_!&/()=?*+'#";
        $return = '';
        
        for($i=0;$i<$length;$i++) {
            $return .= $source{mt_rand(0, strlen($source)-1)};
        }
        
        return $return;
    }
}
