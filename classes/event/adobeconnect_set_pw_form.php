<?php
require_once("$CFG->libdir/formslib.php");
class mod_adobeconnect_form extends moodleform{
	function definition() {
		global $CFG;

		$mform = $this->_form;
		$mform->addElement('html', "<div class='onlyforac'>".get_string('onlyforexplain','mod_adobeconnect')." </div>");
		$mform->addElement('passwordunmask', 'acpass', get_string('myacpass', 'mod_adobeconnect'), array('class' => 'aconsetpw'));

		if(isset($this->_customdata['currentpassword'])){
			$mform->setDefault('acpass', $this->_customdata['currentpassword']);
		}

		$mform->addElement('html',"<div class='acaction'>");
		$this->add_action_buttons();
		$mform->addElement('html',"</div>");
		$conid=$this->_customdata['currentid'];

		$mform->addElement('html', "<a href='reset_pw.php?id=$conid'><div>".get_string('respass','mod_adobeconnect')." </div></a>");
	}
}
class mod_ac_reset_form extends moodleform{
	function definition(){
		global $CFG;
		$mform = $this->_form;
		$mform->addElement('html', "<div class='resetstring'>".get_string('resetwarning','mod_adobeconnect')."</div>");
		$mform->addElement('html', "<div class='onlyforac'>".get_string('resetdont','mod_adobeconnect')."</div>");

		$mform->addElement('html',"<div class='acaction'>");
		$this->add_action_buttons(true, get_string('reset'));
		$mform->addElement('html',"</div>");
	}
}
