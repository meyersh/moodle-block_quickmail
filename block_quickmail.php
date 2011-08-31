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
        global $USER, $CFG, $COURSE, $OUTPUT;

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
            $this->content->icons[] = $OUTPUT->pix_icon('i/email', $send_email_str);

            $signature_str = get_string('signature', 'block_quickmail');
            $signature = '<a href="'.$CFG->wwwroot.'/blocks/quickmail/signature.php'.
                         '?courseid='.$COURSE->id.'">'.$signature_str.'</a>'; 
            $this->content->items[] = $signature;
            $this->content->icons[] = $OUTPUT->pix_icon('i/edit', $signature_str);

            // Drafts
            $drafts_email_str = get_string('drafts', 'block_quickmail');
            $drafts = '<a href="'.$CFG->wwwroot.'/blocks/quickmail/emaillog.php?courseid='.
                        $COURSE->id.'&amp;type=drafts">'.$drafts_email_str.'</a>';
            $this->content->items[] = $drafts;
            $this->content->icons[] = $OUTPUT->pix_icon('i/settings', $drafts_email_str);
        }

        // History can't be view by students
        if($permission) {
            $history_str = get_string('history', 'block_quickmail');
            $history = '<a href="'.$CFG->wwwroot.'/blocks/quickmail/emaillog.php?courseid='.
                        $COURSE->id.'">'.$history_str.'</a>';
            $this->content->items[] = $history;
            $this->content->icons[] = $OUTPUT->pix_icon('i/settings', $history_str);
        }

        // Can config?
        if (has_capability('block/quickmail:canconfig', $context)) {
            $config_str = get_string('config', 'block_quickmail');
            $config = html_writer::link(
                new moodle_url('/blocks/quickmail/config.php', array (
                    'courseid' => $COURSE->id
                )), $config_str
            );
            $this->content->items[] = $config;
            $this->content->icons[] = $OUTPUT->pix_icon('i/settings', $config_str);
        }

        return $this->content;
    }
}
