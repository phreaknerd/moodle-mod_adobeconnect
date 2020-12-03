<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    mod_adobeconnect
 * @author     Akinsaya Delamarre (adelamarre@remote-learner.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2015 Remote Learner.net Inc http://www.remote-learner.net
 */


require_once('connect_class.php');

class connect_class_dom extends connect_class {

    public function __construct($serverurl = '', $serverport = '',
                                $username = '', $password = '',
                                $cookie = '', $https = false, $timeout = 0) {
        parent::__construct($serverurl, $serverport, $username, $password, $cookie, $https, $timeout);

    }

    public function create_request($params = array(), $sentrequest = true) {
        if (empty($params)) {
            return false;
        }


        $dom = new DOMDocument('1.0', 'UTF-8');

        $root = $dom->createElement('params');
        $dom->appendChild($root);


        foreach($params as $key => $data) {

            $datahtmlent = htmlentities($data, ENT_COMPAT, 'UTF-8');
            $child = $dom->createElement('param', $datahtmlent);
            $root->appendChild($child);

            $attribute = $dom->createAttribute('name');
            $child->appendChild($attribute);

            $text = $dom->createTextNode($key);
            $attribute->appendChild($text);

        }

        $this->_xmlrequest = $dom->saveXML();

        if ($sentrequest) {
            $this->_xmlresponse = $this->send_request();
        }
    }

    /**
     * Parses through xml and looks for the 'cookie' parameter
     * @param string $xml the xml to parse through
     * @return string $sessoin returns the session id
     */
    public function read_cookie_xml($xml = '') {
        global $USER, $COURSE, $CFG;

        if (empty($xml)) {
            if (is_siteadmin($USER->id)) {
                notice(get_string('adminemptyxml', 'adobeconnect'),
                       $CFG->wwwroot . '/admin/settings.php?section=modsettingadobeconnect');
            } else {
                notice(get_string('emptyxml', 'adobeconnect'),
                       '', $COURSE);
            }
        }

        $dom = new DomDocument();
        $dom->loadXML($xml);
        $domnodelist = $dom->getElementsByTagName('cookie');

        if (isset($domnodelist->item(0)->nodeValue)) {
            $this->_cookie = $domnodelist->item(0)->nodeValue;
        } else {
            $this->_cookie = null;
        }

    }

    public function call_success() {
        global $USER, $COURSE, $CFG;

        if (empty($this->_xmlresponse)) {
            if (is_siteadmin($USER->id)) {
                notice(get_string('adminemptyxml', 'adobeconnect'),
                       $CFG->wwwroot . '/admin/settings.php?section=modsettingadobeconnect');
            } else {
                notice(get_string('emptyxml', 'adobeconnect'),
                       '', $COURSE);
            }
        }

        $dom = new DomDocument();
        $dom->loadXML($this->_xmlresponse);

        $domnodelist = $dom->getElementsByTagName('status');

        if (!is_object($domnodelist->item(0))) {
            if (is_siteadmin($USER->id)) {
                notice(get_string('adminemptyxml', 'adobeconnect'),
                       $CFG->wwwroot . '/admin/settings.php?section=modsettingadobeconnect');
            } else {
                notice(get_string('emptyxml', 'adobeconnect'),
                       '', $COURSE);
            }
        }

        if ($domnodelist->item(0)->hasAttributes()) {

            $domnode = $domnodelist->item(0)->attributes->getNamedItem('code');

            if (!is_null($domnode)) {
                if (0 == strcmp('ok', $domnode->nodeValue)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }

        } else {
            return false;
        }

    }

    /**
     * Sends the HTTP header login request and returns the response xml
     * @param string username username to use for header x-user-id
     */

    public function request_http_header_login($return_header = 0, $username = '', $stop = false) {
        global $CFG;

        $header = array();
        $this->create_http_head_login_xml();

        // The first parameter is 1 because we want to include the response header
        // to extract the session cookie
        if (!empty($username)) {
            $header = array("$CFG->adobeconnect_admin_httpauth: " . $username);
        }

        $this->_xmlresponse = $this->send_request($return_header, $header, $stop);

        $this->set_session_cookie($this->_xmlresponse);

        return $this->_xmlresponse;
    }
	/* * 
	 * Rewriten function to work with adobeconnect enchanced security
	 * Will log in the user set and after login get a new cookie and set the object cookie variable to 
	 * the new cookie which will be used in subsequent calls
	 * @param string $username username/email of the account
	 * @param string $password password of the account to be logged in
	 * @return string session cookie
	 * */
public function request_user_login($username,$password) {
	$https = $this->get_https();
	if($https){
		$url = 'https://'.$this->get_serverurl();
	}
	else{
		$url = 'http://'.$this->get_serverurl();
	}
	$call = $url."?action=login&login=".urlencode(trim($username))."&password=".urlencode(trim($password));

  $ch = $this->_curlconnection;  
  curl_setopt($ch, CURLOPT_URL, $call);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
	curl_setopt($ch, CURLOPT_HEADER, 1);  
	$response = curl_exec($ch);  

	$breeze_session_first_strip = strstr($response, 'BREEZESESSION');  
	$breeze_session_second_strip = strstr($breeze_session_first_strip, ';', true);  
	$breeze_session = str_replace('BREEZESESSION=', '', $breeze_session_second_strip);  
	$this->_cookie = $breeze_session;

	return $breeze_session;
}
/* *
 * This function sets the password of the specified user to a new one
 * @param int $username id of the user in question (principal_id)
 * @param password the new password to be set for the user
 */
public function set_new_user_password($username, $password){
	$params = array(
		'action' => 'user-update-pwd',
		'user-id' => $username,
		'password' => $password,
		'password-verify' => $password
	);

	$this->create_request($params, true);
	return;
}
   private function create_http_head_login_xml() {
        $params = array('action' => 'login',
                        'external-auth' => 'use',
                        );

        $this->create_request($params, false);
    }
}
