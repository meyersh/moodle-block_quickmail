<?php

require_once $CFG->libdir . '/formslib.php';

class config_form extends moodleform {
    public function definition() {
        $mform =& $this->_form;

        $reset_link = html_writer::link(
            new moodle_url('/blocks/quickmail/config.php', array(
                'courseid' => $this->_customdata['courseid'],
                'reset' => 1
            )), get_string('reset', 'block_quickmail')
        ); 
        $mform->addElement('static', 'reset', '', $reset_link); 

        $student_select = array(0 => get_string('no'), 1 => get_string('yes'));
        $mform->addElement('select', 'allowstudents', get_string('allowstudents', 
                           'block_quickmail'), $student_select);
        $roles =& $mform->addElement('select', 'roleselection', get_string('select_roles', 
                           'block_quickmail'), $this->_customdata['roles']);
        $roles->setMultiple(true);
        $mform->addElement('submit', 'save', get_string('savechanges'));
        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);

        $mform->addRule('roleselection', null, 'required');
    }
}
