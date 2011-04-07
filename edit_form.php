<?php
/**
 * @author Charles Fulton
 * @package quickmail
 * @version 2.00
 */

class block_quickmail_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        $mform->addElement('header','configheader',get_string('blocksettings', 'block'));
        
        // Configure groups handling
        $options = array(
            NOGROUPS =>       get_string('groupsnone'),
            SEPARATEGROUPS => get_string('groupsseparate'),
            VISIBLEGROUPS =>  get_string('groupsvisible')
        );
                
        if($this->page->course->groupmodeforce) {
            $mform->addElement('select','config_groupmode',get_string('groupmode'), $options, array('disabled' => 'disabled'));
            $mform->setDefault('config_groupmode', $this->page->course->groupmode);
        } else {
            $mform->addElement('select','config_groupmode',get_string('groupmode'), $options);
            $mform->setDefault('config_groupmode', NOGROUPS);
        }
        
        // Configure message type--HTML or plaintext
        $options = array(
            FORMAT_HTML   => get_string('formathtml'),
            FORMAT_PLAIN => get_string('formatplain')
        );
        $mform->addElement('select','config_defaultformat',get_string('emailformat'), $options);
        
    }
}