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
require_once('connect_class_dom.php');

define('ADOBE_VIEW_ROLE', 'view');
define('ADOBE_HOST_ROLE', 'host');
define('ADOBE_MINIADMIN_ROLE', 'mini-host');
define('ADOBE_REMOVE_ROLE', 'remove');

define('ADOBE_PARTICIPANT', 1);
define('ADOBE_PRESENTER', 2);
define('ADOBE_REMOVE', 3);
define('ADOBE_HOST', 4);

define('ADOBE_TEMPLATE_POSTFIX', '- Template');
define('ADOBE_MEETING_POSTFIX', '- Meeting');

define('ADOBE_MEETPERM_PUBLIC', 0); //means the Acrobat Connect meeting is public, and anyone who has the URL for the meeting can enter the room.
define('ADOBE_MEETPERM_PROTECTED', 1); //means the meeting is protected, and only registered users and accepted guests can enter the room.
define('ADOBE_MEETPERM_PRIVATE', 2); // means the meeting is private, and only registered users and participants can enter the room

define('ADOBE_TMZ_LENGTH', 6);

function adobe_connection_test($host = '', $port = 80, $username = '',
                               $password = '', $httpheader = '',
                               $emaillogin, $https = false) {

    if (empty($host) or
        empty($port) or (0 == $port) or
        empty($username) or
        empty($password) or
        empty($httpheader)) {

        echo "</p>One of the required parameters is blank or incorrect: <br />".
             "Host: $host<br /> Port: $port<br /> Username: $username<br /> Password: $password".
             "<br /> HTTP Header: $httpheader</p>";

        die();
    }

    $messages = array();

    $aconnectDOM = new connect_class_dom($host,
                                         $port,
                                         $username,
                                         $password,
                                         '',
                                         $https);

    $params = array(
        'action' => 'common-info'
    );

    // Send common-info call to obtain the session key
    echo '<p>Sending common-info call:</p>';
    $aconnectDOM->create_request($params);

    if (!empty($aconnectDOM->_xmlresponse)) {

        // Get the session key from the XML response
        $aconnectDOM->read_cookie_xml($aconnectDOM->_xmlresponse);

        $cookie = $aconnectDOM->get_cookie();
        if (empty($cookie)) {

            echo '<p>unable to obtain session key from common-info call</p>';
            echo '<p>xmlrequest:</p>';
            $doc = new DOMDocument();

            if ($doc->loadXML($aconnectDOM->_xmlrequest)) {
                echo '<p>' . htmlspecialchars($doc->saveXML()) . '</p>';
            } else {
                echo '<p>unable to display the XML request</p>';
            }

            echo '<p>xmlresponse:</p>';
            $doc = new DOMDocument();

            if ($doc->loadXML($aconnectDOM->_xmlresponse)) {
                echo '<p>' . htmlspecialchars($doc->saveHTML()) . '</p>';
            } else {
                echo '<p>unable to display the XML response</p>';
            }

        } else {

            // print success
            echo '<p style="color:#006633">successfully obtained the session key: ' . $aconnectDOM->get_cookie() . '</p>';

			$test = $aconnectDOM->request_user_login($aconnectDOM->get_username(), $aconnectDOM->get_password());
			$cookie = $aconnectDOM->get_cookie();

//            if ($aconnectDOM->call_success()) {
                echo '<p style="color:#006633">successfully logged in as admin user</p>';
				echo "<p style='color:#006633'>New session cookie: $cookie</p>";
                //$username

                //Test retrevial of folders
                echo '<p>Testing retrevial of shared content, recording and meeting folders:</p>';
                $folderscoid = aconnect_get_folder($aconnectDOM, 'content');

                if ($folderscoid) {
                    echo '<p style="color:#006633">successfully obtained shared content folder scoid: '. $folderscoid . '</p>';
                } else {

                    echo '<p>error obtaining shared content folder</p>';
                    echo '<p style="color:#680000">XML request:<br />'. htmlspecialchars($aconnectDOM->_xmlrequest). '</p>';
                    echo '<p style="color:#680000">XML response:<br />'. htmlspecialchars($aconnectDOM->_xmlresponse). '</p>';

                }

                $folderscoid = aconnect_get_folder($aconnectDOM, 'forced-archives');

                if ($folderscoid) {
                    echo '<p style="color:#006633">successfully obtained forced-archives (meeting recordings) folder scoid: '. $folderscoid . '</p>';
                } else {

                    echo '<p>error obtaining forced-archives (meeting recordings) folder</p>';
                    echo '<p style="color:#680000">XML request:<br />'. htmlspecialchars($aconnectDOM->_xmlrequest). '</p>';
                    echo '<p style="color:#680000">XML response:<br />'. htmlspecialchars($aconnectDOM->_xmlresponse). '</p>';

                }

                $folderscoid = aconnect_get_folder($aconnectDOM, 'meetings');

                if ($folderscoid) {
                    echo '<p style="color:#006633">successfully obtained meetings folder scoid: '. $folderscoid . '</p>';
                } else {

                    echo '<p>error obtaining meetings folder</p>';
                    echo '<p style="color:#680000">XML request:<br />'. htmlspecialchars($aconnectDOM->_xmlrequest). '</p>';
                    echo '<p style="color:#680000">XML response:<br />'. htmlspecialchars($aconnectDOM->_xmlresponse). '</p>';

                }

                //Test creating a meeting
                $folderscoid = aconnect_get_folder($aconnectDOM, 'meetings');

                $meeting = new stdClass();
                $meeting->name = 'testmeetingtest';
                $time = time();
                $meeting->starttime = $time;
                $time = $time + (60 * 60);
                $meeting->endtime = $time;
				$meetingscoid = aconnect_create_meeting($aconnectDOM, $meeting, $folderscoid);

                if (($meetingscoid )) {
                    echo '<p style="color:#006633">successfully created meeting <b>testmeetingtest</b> scoid: '. $meetingscoid . '</p>';
                } else {

                    echo '<p>error creating meeting <b>testmeetingtest</b> folder</p>';
                    echo '<p style="color:#680000">XML request:<br />'. htmlspecialchars($aconnectDOM->_xmlrequest). '</p>';
                    echo '<p style="color:#680000">XML response:<br />'. htmlspecialchars($aconnectDOM->_xmlresponse). '</p>';
                }

                //Test creating a user
                $user = new stdClass();
                $user->username = 'testusertest';
                $user->firstname = 'testusertest';
                $user->lastname = 'testusertest';
                $user->email = 'testusertest@test.com';
                //============ START Auto-Login===================>
				try{
                $user->password = aconnect_create_user_password($user->email);
				}catch(Exception $e){
					print_r($e);
					$user->password = "testpassword";
				}

                //============ ENDE Auto-Login ===================|
                if (!empty($emaillogin)) {
                    $user->username = $user->email;
                }

                $skipdeletetest = false;

                if (!($usrprincipal = aconnect_user_exists($aconnectDOM, $user))) {
                      $usrprincipal = aconnect_create_user($aconnectDOM, $user);
                    if ($usrprincipal) {
                        echo '<p style="color:#006633">successfully created user <b>testusertest</b> principal-id: '. $usrprincipal . '</p>';
                    } else {
                        echo '<p>error creating user  <b>testusertest</b></p>';
                        echo '<p style="color:#680000">XML request:<br />'. htmlspecialchars($aconnectDOM->_xmlrequest). '</p>';
                        echo '<p style="color:#680000">XML response:<br />'. htmlspecialchars($aconnectDOM->_xmlresponse). '</p>';

                        aconnect_logout($aconnectDOM);
                        die();
                    }
                } else {

                    echo '<p>user <b>testusertest</b> already exists skipping delete user test</p>';
                    $skipdeletetest = true;
                }

                //Test assigning a user a role to the meeting
                if (aconnect_check_user_perm($aconnectDOM, $usrprincipal, $meetingscoid, ADOBE_PRESENTER, true)) {
                    echo '<p style="color:#006633">successfully assigned user <b>testusertest</b>'.
                         ' presenter role in meeting <b>testmeetingtest</b>: '. $usrprincipal . '</p>';
                } else {
                        echo '<p>error assigning user <b>testusertest</b> presenter role in meeting <b>testmeetingtest</b></p>';
                        echo '<p style="color:#680000">XML request:<br />'. htmlspecialchars($aconnectDOM->_xmlrequest). '</p>';
                        echo '<p style="color:#680000">XML response:<br />'. htmlspecialchars($aconnectDOM->_xmlresponse). '</p>';
                }

                //Test removing role from meeting
                if (aconnect_check_user_perm($aconnectDOM, $usrprincipal, $meetingscoid, ADOBE_REMOVE_ROLE, true)) {
                    echo '<p style="color:#006633">successfully removed presenter role for user <b>testusertest</b>'.
                         ' in meeting <b>testmeetingtest</b>: '. $usrprincipal . '</p>';
                } else {
                        echo '<p>error remove presenter role for user <b>testusertest</b> in meeting <b>testmeetingtest</b></p>';
                        echo '<p style="color:#680000">XML request:<br />'. htmlspecialchars($aconnectDOM->_xmlrequest). '</p>';
                        echo '<p style="color:#680000">XML response:<br />'. htmlspecialchars($aconnectDOM->_xmlresponse). '</p>';
                }

                //Test removing user from server
                if (!$skipdeletetest) {
                    if (aconnect_delete_user($aconnectDOM, $usrprincipal)) {
                        echo '<p style="color:#006633">successfully removed user <b>testusertest</b> principal-id: '. $usrprincipal . '</p>';
                    } else {
                        echo '<p>error removing user <b>testusertest</b></p>';
                        echo '<p style="color:#680000">XML request:<br />'. htmlspecialchars($aconnectDOM->_xmlrequest). '</p>';
                        echo '<p style="color:#680000">XML response:<br />'. htmlspecialchars($aconnectDOM->_xmlresponse). '</p>';
                    }
                }

                //Test removing meeting from server
                if ($meetingscoid) {
                    if (aconnect_remove_meeting($aconnectDOM, $meetingscoid)) {
                        echo '<p style="color:#006633">successfully removed meeting <b>testmeetingtest</b> scoid: '. $meetingscoid . '</p>';
                    } else {
                        echo '<p>error removing meeting <b>testmeetingtest</b> folder</p>';
                        echo '<p style="color:#680000">XML request:<br />'. htmlspecialchars($aconnectDOM->_xmlrequest). '</p>';
                        echo '<p style="color:#680000">XML response:<br />'. htmlspecialchars($aconnectDOM->_xmlresponse). '</p>';
                    }
                }


            // } else {
            //     echo '<p style="color:#680000">logging in as '. $username . ' was not successful, check to see if the username and password are correct </p>';
            // }

       }

    } else {
        echo '<p style="color:#680000">common-info API call returned an empty document.  Please check your settings and try again </p>';
    }

    aconnect_logout($aconnectDOM);

}

/**
 * Returns the folder sco-id
 * @param object an adobe connection_class object
 * @param string $folder name of the folder to get
 * (ex. forced-archives = recording folder | meetings = meetings folder
 * | content = shared content folder)
 * @return mixed adobe connect folder sco-id || false if there was an error
 *
 */
function aconnect_get_folder($aconnect, $folder = '') {
    $folderscoid = false;
    $params = array('action' => 'sco-shortcuts');

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        $folderscoid = aconnect_get_folder_sco_id($aconnect->_xmlresponse, $folder);
//        $params = array('action' => 'sco-contents', 'sco-id' => $folderscoid);
    }

    return $folderscoid;
}

/**
 * TODO: comment function and return something meaningful
 */
function aconnect_get_folder_sco_id($xml, $folder) {
    $scoid = false;

    $dom = new DomDocument();
    $dom->loadXML($xml);

    $domnodelist = $dom->getElementsByTagName('sco');

    if (!empty($domnodelist->length)) {

        for ($i = 0; $i < $domnodelist->length; $i++) {

            $domnode = $domnodelist->item($i)->attributes->getNamedItem('type');

            if (!is_null($domnode)) {

                if (0 == strcmp($folder, $domnode->nodeValue)) {
                    $domnode = $domnodelist->item($i)->attributes->getNamedItem('sco-id');

                    if (!is_null($domnode)) {
                        $scoid = (int) $domnode->nodeValue;

                    }
                }
            }
        }
    }

    return $scoid;

}

/**
 * Log in as the admin user.  This should only be used to conduct API calls.
 * *Edit* This function is updated due to Enchanced security on Adobeconnect, and uses
 * the function request_user_login inside the aconnect_class_dom class
 */
function aconnect_login() {
    global $CFG, $USER, $COURSE;

    if (!isset($CFG->adobeconnect_host) or
        !isset($CFG->adobeconnect_admin_login) or
        !isset($CFG->adobeconnect_admin_password)) {
            if (is_siteadmin($USER->id)) {
                notice(get_string('adminnotsetupproperty', 'adobeconnect'),
                       $CFG->wwwroot . '/admin/settings.php?section=modsettingadobeconnect');
            } else {
                notice(get_string('notsetupproperty', 'adobeconnect'),
                       '', $COURSE);
            }
    }

    if (isset($CFG->adobeconnect_port) and
        !empty($CFG->adobeconnect_port) and
        ((80 != $CFG->adobeconnect_port) and (0 != $CFG->adobeconnect_port))) {
        $port = $CFG->adobeconnect_port;
    } else {
        $port = 80;
    }

    $https = false;

    if (isset($CFG->adobeconnect_https) and (!empty($CFG->adobeconnect_https))) {
        $https = true;
    }


    $aconnect = new connect_class_dom($CFG->adobeconnect_host,
                                      $CFG->adobeconnect_port,
                                      $CFG->adobeconnect_admin_login,
                                      $CFG->adobeconnect_admin_password,
                                      '',
                                      $https);

    // $params = array(
    //     'action' => 'common-info'
    // );

    // $aconnect->create_request($params);

    // $aconnect->read_cookie_xml($aconnect->_xmlresponse);

    // $params = array(
    //       'action' => 'login',
    //       'login' => $aconnect->get_username(),
    //       'password' => $aconnect->get_password(),
    // );
    // $aconnect->create_request($params);
	$cookie = $aconnect->request_user_login($aconnect->get_username(), $aconnect->get_password());

    if ($cookie) {
        $aconnect->set_connection(1);
    } else {
        $aconnect->set_connection(0);
    }

    return $aconnect;
}


/**
 * Logout
 * @param object $aconnect - connection object
 * @return true on success else false
 */
function aconnect_logout(&$aconnect) {
    if (!$aconnect->get_connection()) {
        return true;
    }

    $params = array('action' => 'logout');
    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        $aconnect->set_connection(0);
        return true;
    } else {
        $aconnect->set_connection(1);
        return false;
    }
}

/**
 * Calls all operations needed to retrieve and return all
 * templates defined in the shared templates folder and meetings
 * @param object $aconnect connection object
 * @return array $templates an array of templates
 */
function aconnect_get_templates_meetings($aconnect) {
    $templates = array();
    $meetings = array();
    $meetfldscoid = false;
    $tempfldscoid = false;

    $params = array(
        'action' => 'sco-shortcuts',
    );

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        // Get shared templates folder sco-id
        $tempfldscoid = aconnect_get_shared_templates($aconnect->_xmlresponse);
    }

    if (false !== $tempfldscoid) {
        $params = array(
            'action' => 'sco-expanded-contents',
            'sco-id' => $tempfldscoid,
        );

        $aconnect->create_request($params);

        if ($aconnect->call_success()) {
            $templates = aconnect_return_all_templates($aconnect->_xmlresponse);
        }
    }

//    if (false !== $meetfldscoid) {
//        $params = array(
//            'action' => 'sco-expanded-contents',
//            'sco-id' => $meetfldscoid,
//            'filter-type' => 'meeting',
//        );
//
//        $aconnect->create_request($params);
//
//        if ($aconnect->call_success()) {
//            $meetings = aconnect_return_all_meetings($aconnect->_xmlresponse);
//        }
//
//    }

    return $templates + $meetings;
}

/**
 * Parse XML looking for shared-meeting-templates attribute
 * and returning the sco-id of the folder
 * @param string $xml returned XML from a sco-shortcuts call
 * @return mixed sco-id if found or false if not found or error
 */
function aconnect_get_shared_templates($xml) {
    $scoid = false;

    $dom = new DomDocument();
    $dom->loadXML($xml);

    $domnodelist = $dom->getElementsByTagName('shortcuts');

    if (!empty($domnodelist->length)) {

//        for ($i = 0; $i < $domnodelist->length; $i++) {

            $innerlist = $domnodelist->item(0)->getElementsByTagName('sco');

            if (!empty($innerlist->length)) {

                for ($x = 0; $x < $innerlist->length; $x++) {

                    if ($innerlist->item($x)->hasAttributes()) {

                        $domnode = $innerlist->item($x)->attributes->getNamedItem('type');

                        if (!is_null($domnode)) {

                            if (0 == strcmp('shared-meeting-templates', $domnode->nodeValue)) {
                                $domnode = $innerlist->item($x)->attributes->getNamedItem('sco-id');

                                if (!is_null($domnode)) {
                                    $scoid = (int) $domnode->nodeValue;
                                }
                            }
                        }
                    }
                }
            }
//        }

    }

    return $scoid;
}

function aconnect_return_all_meetings($xml) {
    $meetings = array();
    $xml = new SimpleXMLElement($xml);

    if (empty($xml)) {
        return $meetings;
    }

    foreach($xml->{'expanded-scos'}[0]->sco as $key => $sco) {
        if (0 == strcmp('meeting', $sco['type'])) {
            $mkey = (int) $sco['sco-id'];
            $meetings[$mkey] = (string) current($sco->name) .' '. ADOBE_MEETING_POSTFIX;
        }
    }

    return $meetings;
}

/**
 * Parses XML for meeting templates and returns an array
 * with sco-id as the key and template name as the value
 * @param strimg $xml XML returned from a sco-expanded-contents call
 * @return array of templates sco-id -> key, name -> value
 */
function aconnect_return_all_templates($xml) {
    $templates = array();

    $dom = new DomDocument();
    $dom->loadXML($xml);

    $domnodelist = $dom->getElementsByTagName('expanded-scos');

    if (!empty($domnodelist->length)) {

        $innerlist = $domnodelist->item(0)->getElementsByTagName('sco');

        if (!empty($innerlist->length)) {

            for ($i = 0; $i < $innerlist->length; $i++) {

                if ($innerlist->item($i)->hasAttributes()) {
                    $domnode = $innerlist->item($i)->attributes->getNamedItem('type');

                    if (!is_null($domnode) and 0 == strcmp('meeting', $domnode->nodeValue)) {
                        $domnode = $innerlist->item($i)->attributes->getNamedItem('sco-id');

                        if (!is_null($domnode)) {
                            $tkey = (int) $domnode->nodeValue;
                            $namelistnode = $innerlist->item($i)->getElementsByTagName('name');

                            if (!is_null($namelistnode)) {
                                $name = $namelistnode->item(0)->nodeValue;
                                $templates[$tkey] = (string) $name .' ' . ADOBE_TEMPLATE_POSTFIX;
                            }
                        }
                    }
                }
            }
        }
    }

    return $templates;
}

/**
 * Returns information about all recordings that belong to a specific
 * meeting sco-id
 *
 * @param obj $aconnect a connect_class object
 * @param int $folderscoid the recordings folder sco-id
 * @param int $sourcescoid the meeting sco-id
 *
 * @return mixed array an array of object with the recording sco-id
 * as the key and the recording properties as properties
 */
 function aconnect_get_recordings($aconnect, $folderscoid, $sourcescoid) {
     $params = array('action' => 'sco-contents',
                     'sco-id' => $folderscoid,
                     //'filter-source-sco-id' => $sourcescoid,
                     'sort-name' => 'asc',
                     );

     // Check if meeting scoid and folder scoid are the same
     // If hey are the same then that means that forced recordings is not
     // enabled filter-source-sco-id should not be included.  If the
     // meeting scoid and folder scoid are not equal then forced recordings
     // are enabled and we can use filter by filter-source-sco-id
     // Thanks to A. gtdino
     if ($sourcescoid != $folderscoid) {
         $params['filter-source-sco-id'] = $sourcescoid;
     }

     $aconnect->create_request($params);

     $recordings = array();

     if ($aconnect->call_success()) {
         $dom = new DomDocument();
         $dom->loadXML($aconnect->_xmlresponse);

         $domnodelist = $dom->getElementsByTagName('scos');

         if (!empty($domnodelist->length)) {

 //            for ($i = 0; $i < $domnodelist->length; $i++) {

                 $innernodelist = $domnodelist->item(0)->getElementsByTagName('sco');

                 if (!empty($innernodelist->length)) {

                     for ($x = 0; $x < $innernodelist->length; $x++) {

                         if ($innernodelist->item($x)->hasAttributes()) {

                             $domnode = $innernodelist->item($x)->attributes->getNamedItem('sco-id');

                             if (!is_null($domnode)) {
                                 $meetingdetail = $innernodelist->item($x);

                                 // Check if the SCO item is a recording or uploaded document.  We only want to display recordings
                                 //In AC9, the recording length info is stored as an attributed of 'sco'
                                 $recordingvac9 = $innernodelist->item($x)->attributes->getNamedItem('duration');
                                 //In AC-8 and before, the recording length info is stored as its own element
                                 $recordingvac8 = $meetingdetail->getElementsByTagName('duration')->item(0);
                                 //In AC9, many objects have a 'recording' attribute defined, but only recordings have a non-empty value.
                                 // So check the attribute has a value (in minutes, can be rounded to 0 if short, so can't use !empty())
                                 if ((!is_null($recordingvac9) && $recordingvac9->nodeValue !== '') || !is_null($recordingvac8)) {

                                     $j = (int) $domnode->nodeValue;
                                     $value = (!is_null($meetingdetail->getElementsByTagName('name'))) ?
                                              $meetingdetail->getElementsByTagName('name')->item(0)->nodeValue : '';

                                     $recordings[$j]->name = (string) $value;

                                     $value = (!is_null($meetingdetail->getElementsByTagName('url-path'))) ?
                                              $meetingdetail->getElementsByTagName('url-path')->item(0)->nodeValue : '';

                                     $recordings[$j]->url = (string) $value;

                                     $value = (!is_null($meetingdetail->getElementsByTagName('date-begin'))) ?
                                              $meetingdetail->getElementsByTagName('date-begin')->item(0)->nodeValue : '';

                                     $recordings[$j]->startdate = (string) $value;

                                     $value = (!is_null($meetingdetail->getElementsByTagName('date-end'))) ?
                                              $meetingdetail->getElementsByTagName('date-end')->item(0)->nodeValue : '';

                                     $recordings[$j]->enddate = (string) $value;

                                     $value = (!is_null($meetingdetail->getElementsByTagName('date-created'))) ?
                                              $meetingdetail->getElementsByTagName('date-created')->item(0)->nodeValue : '';

                                     $recordings[$j]->createdate = (string) $value;

                                     $value = (!is_null($meetingdetail->getElementsByTagName('date-modified'))) ?
                                              $meetingdetail->getElementsByTagName('date-modified')->item(0)->nodeValue : '';

                                     $recordings[$j]->modified = (string) $value;

                                     $value = (!is_null($recordingvac9) ?
                                              $recordingvac9->nodeValue : $recordingvac8->nodeValue);

                                     $recordings[$j]->duration = (string) $value;

                                     $recordings[$j]->sourcesco = (int) $sourcescoid;
                                 }

                             }
                         }
                     }
                 }
 //            }

             return $recordings;
         } else {
             return false;
         }
     } else {
         return false;
     }

 }

/**
 * Parses XML and returns the meeting sco-id
 * @param string XML obtained from a sco-update call
 */
function aconnect_get_meeting_scoid($xml) {
    $scoid = false;

    $dom = new DomDocument();
    $dom->loadXML($xml);

    $domnodelist = $dom->getElementsByTagName('sco');

    if (!empty($domnodelist->length)) {
        if ($domnodelist->item(0)->hasAttributes()) {
            $domnode = $domnodelist->item(0)->attributes->getNamedItem('sco-id');

            if (!is_null($domnode)) {
                $scoid = (int) $domnode->nodeValue;
            }
        }
    }

    return $scoid;
}

/**
 * Update meeting
 * @param obj $aconnect connect_class object
 * @param obj $meetingobj an adobeconnect module object
 * @param int $meetingfdl adobe connect meeting folder sco-id
 * @return bool true if call was successful else false
 */
function aconnect_update_meeting($aconnect, $meetingobj, $meetingfdl) {
    $params = array('action' => 'sco-update',
                    'sco-id' => $meetingobj->scoid,
                    'name' => aconnect_clear_name($meetingobj->name),
//                    'name' => htmlentities($meetingobj->name, ENT_COMPAT, 'UTF-8'),
                    'folder-id' => $meetingfdl,
// updating meeting URL using the API corrupts the meeting for some reason
//                    'url-path' => '/'.$meetingobj->meeturl,
                    'date-begin' => $meetingobj->starttime,
                    'date-end' => $meetingobj->endtime,
                    );

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        return true;
    } else {
        return false;
    }

}

/**
 * Update a meeting's access permissions
 * @param obj $aconnect connect_class object
 * @param int $meetingscoid meeting sco-id
 * @param int $perm meeting permission id
 * @return bool true if call was successful else false
 */
function aconnect_update_meeting_perm($aconnect, $meetingscoid, $perm) {
     $params = array('action' => 'permissions-update',
                     'acl-id' => $meetingscoid,
                     'principal-id' => 'public-access',
                    );

     switch ($perm) {
         case ADOBE_MEETPERM_PUBLIC:
            $params['permission-id'] = 'view-hidden';
            break;
         case ADOBE_MEETPERM_PROTECTED:
            $params['permission-id'] = 'remove';
            break;
         case ADOBE_MEETPERM_PRIVATE:
         default:
            $params['permission-id'] = 'denied';
            break;
     }

     $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        return true;
    } else {
        return false;
    }


 }

/** CONTRIB-1976, CONTRIB-1992
 * This function adds a fraction of a second to the ISO 8601 date
 * @param int $time unix timestamp
 * @return mixed a string (ISO 8601) containing the decimal fraction of a second
 * or false if it was not able to determine where to put it
 */
function aconnect_format_date_seconds($time) {

    $newdate = false;
    $date = date("c", $time);

    $pos = strrpos($date, '-');
    $length = strlen($date);

    $diff = $length - $pos;

    if ((0 < $diff) and (ADOBE_TMZ_LENGTH == $diff)) {
        $firstpart = substr($date, 0, $pos);
        $lastpart = substr($date, $pos);
        $newdate = $firstpart . '.000' . $lastpart;

        return $newdate;
    }

    $pos = strrpos($date, '+');
    $length = strlen($date);

    $diff = $length - $pos;

    if ((0 < $diff) and (ADOBE_TMZ_LENGTH == $diff)) {
        $firstpart = substr($date, 0, $pos);
        $lastpart = substr($date, $pos);
        $newdate = $firstpart . '.000' . $lastpart;

        return $newdate;

    }

    return false;
}

/**
 * Creates a meeting
 * @param obj $aconnect connect_class object
 * @param obj $meetingobj an adobeconnect module object
 * @param int $meetingfdl adobe connect meeting folder sco-id
 * @return mixed meeting sco-id on success || false on error
 */
function aconnect_create_meeting($aconnect, $meetingobj, $meetingfdl) {
    //date("Y-m-d\TH:i

    $starttime = aconnect_format_date_seconds($meetingobj->starttime);
    $endtime = aconnect_format_date_seconds($meetingobj->endtime);

    if (empty($starttime) or empty($endtime)) {
        $message = 'Failure (aconnect_find_timezone) in finding the +/- sign in the date timezone'.
                    "\n".date("c", $meetingobj->starttime)."\n".date("c", $meetingobj->endtime);
        debugging($message, DEBUG_DEVELOPER);
        return false;
    }

    $params = array('action' => 'sco-update',
                    'type' => 'meeting',
                    'name' => aconnect_clear_name($meetingobj->name),
                    //'name' => htmlentities($meetingobj->name, ENT_COMPAT, 'UTF-8'),
                    'folder-id' => $meetingfdl,
                    'date-begin' => $starttime,
                    'date-end' => $endtime,
                    );

    if (!empty($meetingobj->meeturl)) {
        $params['url-path'] = $meetingobj->meeturl;
    }

    if (!empty($meetingobj->templatescoid)) {
        $params['source-sco-id'] = $meetingobj->templatescoid;
    }

    $aconnect->create_request($params);


    if ($aconnect->call_success()) {
        return aconnect_get_meeting_scoid($aconnect->_xmlresponse);
    } else {
        return false;
    }
}

/**
 * Finds a matching meeting sco-id
 * @param object $aconnect a connect_class object
 * @param int $meetfldscoid Meeting folder sco-id
 * @param array $filter array key is the filter and array value is the value
 * (ex. array('filter-name' => 'meeting101'))
 * @return mixed array of objects with sco-id as key and meeting name and url as object
 * properties as value || false if not found or error occured
 */
function aconnect_meeting_exists($aconnect, $meetfldscoid, $filter = array()) {
    $matches = array();

    $params = array(
        'action' => 'sco-contents',
        'sco-id' => $meetfldscoid,
        'filter-type' => 'meeting',
    );

    if (empty($filter)) {
        return false;
    }

    $params = array_merge($params, $filter);
    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        $dom = new DomDocument();
        $dom->loadXML($aconnect->_xmlresponse);

        $domnodelist = $dom->getElementsByTagName('scos');

        if (!empty($domnodelist->length)) {

            $innernodelist = $domnodelist->item(0)->getElementsByTagName('sco');

            if (!empty($innernodelist->length)) {

                for ($i = 0; $i < $innernodelist->length; $i++) {

                    if ($innernodelist->item($i)->hasAttributes()) {

                        $domnode = $innernodelist->item($i)->attributes->getNamedItem('sco-id');

                        if (!is_null($domnode)) {

                            $key = (int) $domnode->nodeValue;

                            $meetingdetail = $innernodelist->item($i);

                            $value = (!is_null($meetingdetail->getElementsByTagName('name'))) ?
                                     $meetingdetail->getElementsByTagName('name')->item(0)->nodeValue : '';

                            if (!isset($matches[$key])) {
                                $matches[$key] = new stdClass();
                            }

                            $matches[$key]->name = (string) $value;

                            $value = (!is_null($meetingdetail->getElementsByTagName('url-path'))) ?
                                     $meetingdetail->getElementsByTagName('url-path')->item(0)->nodeValue : '';

                            $matches[$key]->url = (string) $value;

                            $matches[$key]->scoid = (int) $key;

                            $value = (!is_null($meetingdetail->getElementsByTagName('date-begin'))) ?
                                     $meetingdetail->getElementsByTagName('date-begin')->item(0)->nodeValue : '';

                            $matches[$key]->starttime = (string) $value;

                            $value = (!is_null($meetingdetail->getElementsByTagName('date-end'))) ?
                                     $meetingdetail->getElementsByTagName('date-end')->item(0)->nodeValue : '';

                            $matches[$key]->endtime = (string) $value;

                        }

                    }
                }
            }
        } else {
            return false;
        }

    } else {
        return false;
    }

    return $matches;
}

/**
 * Parse XML and returns the user's principal-id
 * @param string $xml XML returned from call to principal-list
 * @param mixed user's principal-id or false
 */
function aconnect_get_user_principal_id($xml) {
    $usrprincipalid = false;

    $dom = new DomDocument();
    $dom->loadXML($xml);

    $domnodelist = $dom->getElementsByTagName('principal-list');

    if (!empty($domnodelist->length)) {
        $domnodelist = $domnodelist->item(0)->getElementsByTagName('principal');

        if (!empty($domnodelist->length)) {
            if ($domnodelist->item(0)->hasAttributes()) {
                $domnode = $domnodelist->item(0)->attributes->getNamedItem('principal-id');

                if (!is_null($domnode)) {
                    $usrprincipalid = (int) $domnode->nodeValue;
                }
            }
        }
    }

    return $usrprincipalid;
}

/**
 * Check to see if a user exists on the Adobe connect server
 * searching by username
 * @param object $aconnect a connection_class object
 * @param object $userdata an object with username as a property
 * @return mixed user's principal-id of match is found || false if not
 * found or error occured
 */
function aconnect_user_exists($aconnect, $usrdata) {
  $params = array(
      'action' => 'principal-list',
      'filter-login' => $usrdata->username,
//            'filter-type' => 'meeting',
// add more filters if this process begins to get slow
  );

  $aconnect->create_request($params);

  if ($aconnect->call_success()) {
      $principal_id = aconnect_get_user_principal_id($aconnect->_xmlresponse);


      global $CFG, $USER, $DB;

      $user_table = $DB->get_record('user',array('id'=> $USER->id));
      $acinfo=$DB->get_record('acusers',array('acuser'=>$USER->email));
      $user_pass=$acinfo->password;

	$key = $CFG->adobeconnectkey;

	$password=rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($user_pass), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
           return $principal_id;

  } else {
      return false;
  }


}
/**
 * This function deletes a user from adobeconnect.
 * @param $aconnect Valid adobeconnect object from the adobeconnect_class_dom with initialized login
 * @param $principalid Id number of the user to be removed
 * @return true on successful deletion false on failure
 * */
function aconnect_delete_user($aconnect, $principalid = 0) {

    if (empty($principalid)) {
        return false;
    }

    $params = array(
        'action' => 'principals-delete',
        'principal-id' => $principalid,
    );

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        return true;
    } else {
        return false;
    }

}

/**
 * Creates a new user on the Adobe Connect server.
 * Parses XML from a principal-update call and returns
 * the principal-id of the new user.
 *
 * @param object $aconnet a connect_class object
 * @param object $usrdata an object with firstname,lastname,
 * username and email properties.
 * @return mixed principal-id of the new user or false
 */
function aconnect_create_user($aconnect, $usrdata) {
    $principal_id = false;

	$params = array(
	'action' => 'principal-update',
	//============ START Auto-Login ===================>
	'first-name' => urlencode($usrdata->firstname),
	'last-name' => urlencode($usrdata->lastname),
	//============ END Auto-Login ===================|
	'login' => $usrdata->username,
	//============ START Auto-Login ===================>
	'password' => $usrdata->password,//strtoupper(md5($usrdata->username.time())),
	//============ END Auto-Login ===================|
	'extlogin' => $usrdata->username,
	'type' => 'user',
	'send-email' => 'false',
	'has-children' => 0,
	'email' => $usrdata->email,
	);
    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        $dom = new DomDocument();
        $dom->loadXML($aconnect->_xmlresponse);

        $domnodelist = $dom->getElementsByTagName('principal');

        if (!empty($domnodelist->length)) {
            if ($domnodelist->item(0)->hasAttributes()) {
                $domnode = $domnodelist->item(0)->attributes->getNamedItem('principal-id');

                if (!is_null($domnode)) {
                    $principal_id = (int) $domnode->nodeValue;
                }
            }
        }
    }

    return $principal_id;
}

function aconnect_assign_user_perm($aconnect, $usrprincipal, $meetingscoid, $type) {
    $params = array(
        'action' => 'permissions-update',
        'acl-id' => $meetingscoid, //sco-id of meeting || principal id of user 11209,
        'permission-id' => $type, //  host, mini-host, view
        'principal-id' => $usrprincipal, // principal id of user you are looking at
    );

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
          return true;
    } else {
          return false;
    }
}
/*
 * function aconnect_remove_user_perm
 *
 * @param $aconnect Valid adobeconnect object from adobeconnect_class_dom
 * @param $usrprincipal principalid of the user in question
 * @param $meetingscoid scoid of the meeting
 * @return true on success false on failure
 *
 * */
function aconnect_remove_user_perm($aconnect, $usrprincipal, $meetingscoid) {
    $params = array(
        'action' => 'permissions-update',
        'acl-id' => $meetingscoid, //sco-id of meeting || principal id of user 11209,
        'permission-id' => ADOBE_REMOVE_ROLE, //  host, mini-host, view
        'principal-id' => $usrprincipal, // principal id of user you are looking at
    );

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
//        print_object($aconnect->_xmlresponse);
    } else {
//        print_object($aconnect->_xmlresponse);
    }

}


/**
 * Check if a user has a permission
 * @param object $aconnect a connect_class object
 * @param int $usrprincipal user principal-id
 * @param int $meetingscoid meeting sco-id
 * @param int $roletype can be ADOBE_PRESENTER, ADOBE_PARTICIPANT or ADOBE_REMOVE
 * @param bool $assign set to true if you want to assign the user the role type
 * set to false to just check the user's permission.  $assign parameter is ignored
 * if $roletype is ADOBE_REMOVE
 * @return TODO
 *
 */
function aconnect_check_user_perm($aconnect, $usrprincipal, $meetingscoid, $roletype, $assign = false) {
    $perm_type = '';
    $hasperm = false;

    switch ($roletype) {
        case ADOBE_PRESENTER:
            $perm_type = ADOBE_MINIADMIN_ROLE;
            break;
        case ADOBE_PARTICIPANT:
            $perm_type = ADOBE_VIEW_ROLE;
            break;
        case ADOBE_HOST:
            $perm_type = ADOBE_HOST_ROLE;
            break;
        case ADOBE_REMOVE:
            $perm_type = ADOBE_REMOVE_ROLE;
            break;
        default:
            break;
    }

    $params = array(
        'action' => 'permissions-info',
    //  'filter-permission-id' => 'mini-host',
        'acl-id' => $meetingscoid, //sco-id of meeting || principal id of user 11209,
//        'filter-permission-id' => $perm_type, //  host, mini-host, view
        'filter-principal-id' => $usrprincipal, // principal id of user you are looking at
    );

    if (ADOBE_REMOVE_ROLE != $perm_type) {
        $params['filter-permission-id'] = $perm_type;
    }

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        $dom = new DomDocument();
        $dom->loadXML($aconnect->_xmlresponse);

        $domnodelist = $dom->getElementsByTagName('permissions');

        if (!empty($domnodelist->length)) {
            $domnodelist = $domnodelist->item(0)->getElementsByTagName('principal');

            if (!empty($domnodelist->length)) {
                $hasperm = true;
            }
        }

        if (ADOBE_REMOVE_ROLE != $perm_type and $assign and !$hasperm) {
            // TODO: check return values of the two functions below
            // Assign permission to user
            return aconnect_assign_user_perm($aconnect, $usrprincipal, $meetingscoid, $perm_type);
        } elseif (ADOBE_REMOVE_ROLE == $perm_type) {
            // Remove user's permission
            return aconnect_remove_user_perm($aconnect, $usrprincipal, $meetingscoid);
        } else {
            return $hasperm;
        }
    }
}

/**
 * Remove a meeting
 * @param obj $aconnect adobe connection object
 * @param int $scoid sco-id of the meeting
 * @return bool true of success false on failure
 */
function aconnect_remove_meeting($aconnect, $scoid) {
    $params = array(
        'action' => 'sco-delete',
        'sco-id' => $scoid,
    );

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {
        return true;
    } else {
        return false;
    }
}

/**
 * Move SCOs to the shared content folder
 * @param obj $aconnect a connect_class object
 * @param array sco-ids as array keys
 * @return bool false if error or nothing to move true if a move occured
 */
function aconnect_move_to_shared($aconnect, $scolist) {
    // Get shared folder sco-id
    $shscoid = aconnect_get_folder($aconnect, 'content');

    // Iterate through list of sco and move them all to the shared folder
    if (!empty($shscoid)) {

        foreach ($scolist as $scoid => $data) {
            $params = array(
                'action' => 'sco-move',
                'folder-id' => $shscoid,
                'sco-id' => $scoid,
            );

            $aconnect->create_request($params);

        }

        return true;
    } else {
        return false;
    }
}

/**
 * Gets a list of roles that this user can assign in this context
 *
 * @param object $context the context.
 * @param int $rolenamedisplay the type of role name to display. One of the
 *      ROLENAME_X constants. Default ROLENAME_ALIAS.
 * @param bool $withusercounts if true, count the number of users with each role.
 * @param integer|object $user A user id or object. By default (null) checks the permissions of the current user.
 * @return array if $withusercounts is false, then an array $roleid => $rolename.
 *      if $withusercounts is true, returns a list of three arrays,
 *      $rolenames, $rolecounts, and $nameswithcounts.
 */
function adobeconnect_get_assignable_roles($context, $rolenamedisplay = ROLENAME_ALIAS, $withusercounts = false, $user = null) {
    global $USER, $DB;

    // make sure there is a real user specified
    if ($user === null) {
        $userid = !empty($USER->id) ? $USER->id : 0;
    } else {
        $userid = !empty($user->id) ? $user->id : $user;
    }

    if (!has_capability('moodle/role:assign', $context, $userid)) {
        if ($withusercounts) {
            return array(array(), array(), array());
        } else {
            return array();
        }
    }

    $parents = $context->get_parent_context_ids(true);
    $contexts = implode(',' , $parents);

    $params = array();
    $extrafields = '';
    if ($rolenamedisplay == ROLENAME_ORIGINALANDSHORT or $rolenamedisplay == ROLENAME_SHORT) {
        $extrafields .= ', r.shortname';
    }

    if ($withusercounts) {
        $extrafields = ', (SELECT count(u.id)
                             FROM {role_assignments} cra JOIN {user} u ON cra.userid = u.id
                            WHERE cra.roleid = r.id AND cra.contextid = :conid AND u.deleted = 0
                          ) AS usercount';
        $params['conid'] = $context->id;
    }

    if (is_siteadmin($userid)) {
        // show all roles allowed in this context to admins
        $assignrestriction = "";
    } else {
        $assignrestriction = "JOIN (SELECT DISTINCT raa.allowassign AS id
                                      FROM {role_allow_assign} raa
                                      JOIN {role_assignments} ra ON ra.roleid = raa.roleid
                                     WHERE ra.userid = :userid AND ra.contextid IN ($contexts)
                                   ) ar ON ar.id = r.id";
        $params['userid'] = $userid;
    }
    $params['contextlevel'] = $context->contextlevel;
    $sql = "SELECT r.id, r.name $extrafields
              FROM {role} r
              $assignrestriction
              JOIN {role_context_levels} rcl ON r.id = rcl.roleid
             WHERE rcl.contextlevel = :contextlevel
          ORDER BY r.sortorder ASC";
    $roles = $DB->get_records_sql($sql, $params);

    // Only include Adobe Connect roles
    $param = array('shortname' => 'adobeconnectpresenter');
    $presenterid    = $DB->get_field('role', 'id', $param);

    $param = array('shortname' => 'adobeconnectparticipant');
    $participantid  = $DB->get_field('role', 'id', $param);

    $param = array('shortname' => 'adobeconnecthost');
    $hostid         = $DB->get_field('role', 'id', $param);

    foreach ($roles as $key => $data) {
        if ($key != $participantid and $key != $presenterid and $key != $hostid) {
            unset($roles[$key]);
        }
    }

    $rolenames = array();
    foreach ($roles as $role) {
        if ($rolenamedisplay == ROLENAME_SHORT) {
            $rolenames[$role->id] = $role->shortname;
            continue;
        }
        $rolenames[$role->id] = $role->name;
        if ($rolenamedisplay == ROLENAME_ORIGINALANDSHORT) {
            $rolenames[$role->id] .= ' (' . $role->shortname . ')';
        }
    }
    if ($rolenamedisplay != ROLENAME_ORIGINALANDSHORT and $rolenamedisplay != ROLENAME_SHORT) {
        $rolenames = role_fix_names($rolenames, $context, $rolenamedisplay);
    }

    if (!$withusercounts) {
        return $rolenames;
    }

    $rolecounts = array();
    $nameswithcounts = array();
    foreach ($roles as $role) {
        $nameswithcounts[$role->id] = $rolenames[$role->id] . ' (' . $roles[$role->id]->usercount . ')';
        $rolecounts[$role->id] = $roles[$role->id]->usercount;
    }
    return array($rolenames, $rolecounts, $nameswithcounts);
}

/**
 * This function accepts a username and an email and returns the user's
 * adobe connect user name, depending on the module's configuration settings
 *
 * @param string - moodle username
 * @param string - moodle email
 *
 * @return string - user's adobe connect user name
 */
function set_username($username, $email) {
    global $CFG;

    if (isset($CFG->adobeconnect_email_login) and !empty($CFG->adobeconnect_email_login)) {
        return $email;
    } else {
        return $username;
    }
}

/**
 * This function search through the user-meetings folder for a folder named
 * after the user's login name and returns the sco-id of the user's folder
 *
 * @param obj - adobe connection connection object
 * @param string - the name of the user's folder
 * @return mixed - sco-id of the user folder (int) or false if no folder exists
 *
 */
function aconnect_get_user_folder_sco_id($aconnect, $folder_name) {

    $scoid   = false;
    $usr_meet_scoid = aconnect_get_folder($aconnect, 'user-meetings');

    if (empty($usr_meet_scoid)) {
        return $scoid;
    }

    $params = array('action' => 'sco-expanded-contents',
                    'sco-id' => $usr_meet_scoid,
                    'filter-name' => $folder_name);

    $aconnect->create_request($params);

    if ($aconnect->call_success()) {

        $dom = new DomDocument();
        $dom->loadXML($aconnect->_xmlresponse);

        $domnodelist = $dom->getElementsByTagName('sco');

        if (!empty($domnodelist->length)) {
            if ($domnodelist->item(0)->hasAttributes()) {
                $domnode = $domnodelist->item(0)->attributes->getNamedItem('sco-id');

                if (!is_null($domnode)) {
                    $scoid = (int) $domnode->nodeValue;
                }
            }
        }
    }

    return $scoid;
}

/**
 * This function returns the user's adobe connect login username based off of
 * the adobe connect module's login configuration settings (Moodle username or
 * Moodle email)
 *
 * @param int userid
 * @return mixed - user's login username or false if something bad happened
 */
function get_connect_username($userid) {
    global $DB;

    $username = '';
    $param    = array('id' => $userid);
    $record   = $DB->get_record('user', $param, 'id,username,email');

    if (!empty($userid) && !empty($record)) {
        $username = set_username($record->username, $record->email);
    }

    return $username;
}
//============ START Auto-Login ===================>
/**
 * This function grabs the user's password from the internal database for the given user. If the user
 * does not have a password, it is automatically generated for him.
 * @param string $inputString The username/email of the account in question
 * @return the String of the password in plain
 */
function aconnect_create_user_password($inputString) {
  global $CFG, $USER, $DB;
  $key=$CFG->adobeconnectkey;

  //$user_table = $DB->get_record('user',array('id'=> $USER->id));
  if($password_exists = $DB->get_record('acusers',array('acuser'=> $inputString))){
    $password=$password_exists->password;
    $seed=mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5("$key"), base64_decode($password), MCRYPT_MODE_CBC, md5(md5("$key")));
  }
  else{ //generate password
    $eseed=explode("@",$USER->email);
    $seed=$eseed[0].time();
    $password=base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5("$key"), $seed, MCRYPT_MODE_CBC, md5(md5("$key"))));
    $params->password=$password;
    $params->acuser=$inputString;
	$params->userset = 0;
    $DB->insert_record('acusers',$params);
  }

  //AC server rejects to create accounts if the password is longer than 32 characters
  $eseed=explode("@",$USER->email);
  if(empty(trim($seed))) $seed = $eseed[0].time();
  if(strlen($seed) > 32) $seed = substr($seed, 0, 32); //if string is longer than 32 chars, remove the rest
  return $seed;
}
/*
 * This function rewrites the user password in our database. The passwords are stored encrypted inside the database
 * so all the encryption is handled by this function
 * @param $acname the adobeconnect username/email of the account
 * @param $newpass the new password for the user to be set in the database
 * @param $bool is the password user set or randomly generated? True if set by user and should be flagged as such and displayed in the field
 * @return returns true on success DB connection failures may result in exceptions thrown
 */
function rewrite_user_password($acname, $newpass, $bool=0){
	global $CFG, $USER, $DB;
	$key = $CFG->adobeconnectkey;
	if($record_exists = $DB->get_record('acusers', array('acuser'=>$acname))){
		$record_exists->password=base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5("$key"), $newpass, MCRYPT_MODE_CBC, md5(md5("$key"))));
		$record_exists->userset= ($bool ? 1 : 0);
		$is_updated = $DB->update_record('acusers', $record_exists);
	}
	else {
		$obj = new stdClass();
		$obj->acuser= $acname;
		$obj->password=base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5("$key"), $newpass, MCRYPT_MODE_CBC, md5(md5("$key"))));
		$obj->userset= ($bool ? 1 : 0);
		$DB->insert_record('acusers', $obj);
	}
	return true;
}
/* This function generates a password for the user
 * it is used to generate a random password for the users that don't/shouldn't know their passwords.
 * it does not take any input parameters
 * @return String of letters and numbers (not that hard to see how it works)
 * */
function generate_seed(){
  global $CFG, $USER, $DB;
  $eseed=explode("@",$USER->email);
  $seed=$eseed[0].time();
  return $seed;
}
/* This function is called to initiate password reset for the logged in user
 * with adobeconnect. It uses the adobeconnect services to reset the password of the logged
 * user on adobeconnect and set that password inside the moodle database
 * does not take any input parameters.
 * May throw exception on database/services Failure
 * @return true on success
 * */
function init_password_reset(){
	global $USER, $CFG;

	$password = generate_seed();
	$acuser = $USER->username;
	if (isset($CFG->adobeconnect_email_login) and !empty($CFG->adobeconnect_email_login)) {
		$acuser = $USER->email;
	}
    $aconnect = new connect_class_dom($CFG->adobeconnect_host, $CFG->adobeconnect_port,'', '', '', $CFG->adobeconnect_https);
    $aconnect->request_user_login($CFG->adobeconnect_admin_login, $CFG->adobeconnect_admin_password);
	$usrdata->username=$acuser;
	$principal_id = aconnect_user_exists($aconnect, $usrdata);

	$params = array(
		'action' => 'user-update-pwd',
		'user-id' => $principal_id,
		'password' => $password,
		'password-verify' => $password,
	);
	$aconnect->create_request($params);
	rewrite_user_password($acuser, $password, 0);
	return true;

}
/* This function checks if a meeting exists with the specific url on adobeconnect
 * if it does it returns the id number of the meeting
 * @param Object $aconnect Object of the adobeconnect_class_dom class
 * @param $uri - the uri of the meeting name taken from the meeting path https://myac.adobeconnect.com/$uri/
 * @return returns integer of the id number or false on failure
 * */
function aconnect_meeting_by_url($aconnect, $uri){
	$params = array(
		'action' => 'sco-by-url',
		'url-path' => $uri,
	);
	$aconnect->create_request($params);
    if ($aconnect->call_success()) {
        $dom = new DomDocument();
        $dom->loadXML($aconnect->_xmlresponse);

        $domnodelist = $dom->getElementsByTagName('sco')->item(0);
		if(!empty($domnodelist)){
			$number = $domnodelist->getAttribute('sco-id');
			return $number;
		}
		else{
			return 0;
		}
	}
	else{
		return 0;
	}
	return 0;
}
/* *
 * This function checks if the user has a valid login session or not
 * @param object $aconnect
 * @return integer $userscoid
 * */
function check_if_user_logged_in($aconnect){
	$params = array('action' => 'common-info',
				);
	$aconnect->create_request($params);

    if ($aconnect->call_success()) {
        $dom = new DomDocument();
        $dom->loadXML($aconnect->_xmlresponse);

        $domnodelist = $dom->getElementsByTagName('user')->item(0);
		if(!empty($domnodelist)){
			$number = $domnodelist->getAttribute('user-id');
			return $number;
		}
		else{
			return 0;
		}
	}
	else{
		return 0;
	}
	return 0;


}
/* This function checks if the user is the one who set the password and
 * it is not auto generated for him. If it's set by him return the pass else return false
 * @param string $acname Username/email of the user in question
 * @return string(the user password) or false
 */
function check_if_userset_pw($acname){
	global $CFG, $DB;
	$key = $CFG->adobeconnectkey;
	if($record_exists = $DB->get_record('acusers', array('acuser'=>$acname))){
		if($record_exists->userset){
    		$password=mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5("$key"), base64_decode($record_exists->password), MCRYPT_MODE_CBC, md5(md5("$key")));
			return trim($password);
		}
	}
	return 0;

}



/**
* Replaces some characters in user's name
*
* @param string $inputString the string that holds the name
* @return the String with replaced characters
*/
function aconnect_clear_name($inputString) {
  $s = array( "ä", "ö", "ü", "æ", "ø", "å", "ß", "Ä", "Ö", "Ü", "Æ", "Ø", "Å" );
	$r = array( "ae", "oe", "ue", "ae", "o", "aa", "ss", "Ae", "Oe", "Ue", "Ae", "O", "Aa");
	return str_replace($s,$r,$inputString);
}
