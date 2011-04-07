<?php
/**
 * @author Charles Fulton
 * @version 2.00
 * @package quickmail
 */
    require_once("$CFG->libdir/formslib.php");
    
    class quickmail_email_form extends moodleform {
        
        var $userlist;		// special user table

        function __construct($userlist) {
            $this->userlist = $userlist;
            moodleform::moodleform();
        }
        
        function definition() {
            global $CFG;
            
            $mform =& $this->_form;

            // Recipients                
            // Display lists
            $select_lists = array();
            foreach($this->userlist as $groupid => $list) {
                $select =& $mform->createElement('select',$groupid,'',$list);
                $select->setMultiple(true);
                $select_lists[] = $select;
            }
            $mform->addGroup($select_lists, 'mailto', get_string('to', 'block_quickmail'), false);
            $mform->addRule('mailto', null, 'required', null);
            
            // Subject
            $mform->addElement('text', 'subject', get_string('subject', 'forum'), array('size' => '60'));
            $mform->addRule('subject', null, 'required');
            
            // Message
            $mform->addElement('htmleditor', 'message', get_string('message', 'forum'));
            $mform->setType('message', PARAM_RAW);
            $mform->addRule('message', null, 'required', null, 'client');
            
            // Formatting
            $options = array(
                FORMAT_HTML   => get_string('formathtml'),
            	FORMAT_PLAIN => get_string('formatplain')
            );
            $mform->addElement('select','format',get_string('emailformat'), $options);
            
            // Attachment
            $maxbytes = get_max_upload_file_size($CFG->maxbytes, $this->_customdata['maxbytes']);
            $mform->addElement('filepicker', 'attachment', get_string('attachmentoptional', 'block_quickmail'), null, array('maxbytes' => $maxbytes, 'accepted_types' => '*'));
            
            // Hidden stuff
            $mform->addElement('hidden', 'id');
            $mform->addElement('hidden', 'instanceid');
            $mform->addElement('hidden', 'groupmode');
            
            // Submit
            $this->add_action_buttons(true, get_string('sendemail', 'block_quickmail'));
        }
    }
?>