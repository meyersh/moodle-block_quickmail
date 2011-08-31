<?php

require_once($CFG->libdir . '/formslib.php');

class signature_form extends moodleform {
    public function definition() {
        global $USER;

        $mform =& $this->_form;

        $mform->addElement('hidden', 'courseid', '');
        $mform->addElement('hidden', 'id', '');
        $mform->addElement('hidden', 'userid', $USER->id);

        $mform->addElement('text', 'title', get_string('title', 'block_quickmail'));
        $mform->addElement('editor', 'signature_editor', get_string('sig', 'block_quickmail'));
        $mform->addElement('checkbox', 'default_flag', get_string('default_flag', 'block_quickmail'));
        $buttons = array(
            $mform->createElement('submit', 'save', get_string('savechanges')),
            $mform->createElement('cancel')
        );
        $mform->addGroup($buttons, 'buttons', get_string('actions', 'block_quickmail'), array(' '), false);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('signature_editor', null, 'required', null);
    }
}
