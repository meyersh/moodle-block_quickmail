<?php

// Written at Louisiana State University

require_once($CFG->libdir . '/formslib.php');

class signature_form extends moodleform {
    public function definition() {
        global $USER;

        $mform =& $this->_form;

        $mform->addElement('hidden', 'courseid', '');
        $mform->addElement('hidden', 'id', '');
        $mform->addElement('hidden', 'userid', $USER->id);

        $mform->addElement('text', 'title', quickmail::_s('title'));
        $mform->addElement('editor', 'signature_editor', quickmail::_s('sig'));
        $mform->addElement('checkbox', 'default_flag', quickmail::_s('default_flag'));

        $buttons = array(
            $mform->createElement('submit', 'save', get_string('savechanges')),
            $mform->createElement('cancel')
        );

        $mform->addGroup($buttons, 'buttons', quickmail::_s('actions'), array(' '), false);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->addRule('signature_editor', null, 'required', null);
    }
}
