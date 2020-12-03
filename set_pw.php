<?php


require('../../config.php');
require_once('classes/event/adobeconnect_set_pw_form.php');
require_once('locallib.php');

global $DB, $USER, $CFG;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/mod/adobeconnect/set_pw.php');
$PAGE->set_title(get_string('setpass', 'mod_adobeconnect'));
$PAGE->set_heading(get_string('setpass', 'mod_adobeconnect'));
$thisid = optional_param('id', 0, PARAM_INT); // course_module ID, or

require_login();

$output = $PAGE->get_renderer('core');

$acuser = $USER->username;
if (isset($CFG->adobeconnect_email_login) and !empty($CFG->adobeconnect_email_login)) {
	$acuser = $USER->email;
}

$pass = check_if_userset_pw($acuser);

$values=array();
if($pass){
	$values['currentpassword']=$pass;
}
$values['currentid']=$thisid;
$pwform = new mod_adobeconnect_form(null, $values);
if($thisid>0){
	$_SESSION['toredi']=$CFG->wwwroot.'/mod/adobeconnect/view.php?id='.$thisid;
}

if($pwform->is_cancelled()){
	if(isset($_SESSION['toredi'])&&!empty($_SESSION['toredi'])){
		$sid = $_SESSION['toredi'];
		unset($_SESSION['toredi']);
		redirect(new moodle_url($sid));
		return;
	}
	else{
		redirect(new moodle_url('/index.php'));
		return;
	}
}
else if($data = $pwform->get_data()){

	rewrite_user_password($acuser, trim($data->acpass), 1);
	if(isset($_SESSION['toredi'])&&!empty($_SESSION['toredi'])){
		$sid = $_SESSION['toredi'];
		unset($_SESSION['toredi']);
		redirect(new moodle_url($sid));
		return;
	}
	else{
		redirect(new moodle_url('/index.php'));
		return;
	}
}

if (! $cm = get_coursemodule_from_id('adobeconnect', $thisid)) {
	print_error(get_string('invalidid','mod_adobeconnect'));
}
$context = context_module::instance($thisid);

echo $output->header();

if (has_capability('mod/adobeconnect:meetingpresenter', $context) or
    has_capability('mod/adobeconnect:meetinghost', $context) || $CFG->adobeconn_expose_pass) {
	$pwform->display();
}
else{
	print_error(get_string('nocapability','mod_adobeconnect'));
}

echo $output->footer();



