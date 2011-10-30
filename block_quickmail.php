<?php

/**
 * @author Philip Cali
 * Louisiana State University
 */

require_once(dirname(__FILE__) . '/lib.php');

class block_quickmail extends block_list {
    function init() {
        $this->title = get_string('pluginname', 'block_quickmail');
    }

    function applicable_formats() {
        return array('site' => false, 'my' => false, 'course' => true);
    }

    function get_content() {
        global $USER, $CFG, $COURSE;

        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);

        // TODO: maybe look into removing the need for $USER
        $config = quickmail_load_config($COURSE->id);
        $permission = has_capability('block/quickmail:cansend', $context);

        if($permission || !empty($config['allowstudents'])) {
            // TODO: put email icon on here
            $send_email_str = get_string('composenew', 'block_quickmail');
            $send_email = '<a href="'.$CFG->wwwroot.'/blocks/quickmail/email.php?courseid='.
                          $COURSE->id.'">'.$send_email_str.'</a>';
            $this->content->items[] = $send_email; 

            $signature_str = get_string('signature', 'block_quickmail');
            $signature = '<a href="'.$CFG->wwwroot.'/blocks/quickmail/signature.php'.
                         '?courseid='.$COURSE->id.'">'.$signature_str.'</a>'; 
            $this->content->items[] = $signature;

            // Drafts
            $drafts_email_str = get_string('drafts', 'block_quickmail');
            $drafts = '<a href="'.$CFG->wwwroot.'/blocks/quickmail/emaillog.php?courseid='.
                        $COURSE->id.'&amp;type=drafts">'.$drafts_email_str.'</a>';
            $this->content->items[] = $drafts;
        }

        // History can't be view by students
        if($permission) {
            $history_str = get_string('history', 'block_quickmail');
            $history = '<a href="'.$CFG->wwwroot.'/blocks/quickmail/emaillog.php?courseid='.
                        $COURSE->id.'">'.$history_str.'</a>';
            $this->content->items[] = $history;
        }

        return $this->content;
    }
}
