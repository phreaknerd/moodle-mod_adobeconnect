<?php



require('../../config.php');
require_once('classes/event/adobeconnect_set_pw_form.php');
require_once('locallib.php');

global $DB, $USER, $CFG;

$PAGE->set_url('/mod/adobeconnect/reset_pw.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('resetpass', 'mod_adobeconnect'));
$PAGE->set_heading(get_string('resetpass', 'mod_adobeconnect'));
require_login();
$thisid = optional_param('id', 0, PARAM_INT); // course_module ID, or
if($thisid>0)
	$_SESSION['rediid']= $thisid;

$output = $PAGE->get_renderer('core');

$pwform = new mod_ac_reset_form();
if($pwform->is_cancelled()){
	redirect(new moodle_url('set_pw.php?id='.$_SESSION['rediid']));
	return;
}
else if($data = $pwform->get_data()){

	if($data->submitbutton){
		init_password_reset();
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

