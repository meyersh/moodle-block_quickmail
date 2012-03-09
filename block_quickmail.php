<?php

// Written at Louisiana State University

require_once($CFG->dirroot . '/blocks/quickmail/lib.php');

class block_quickmail extends block_list {
    function init() {
        $this->title = quickmail::_s('pluginname');
    }

    function applicable_formats() {
        return array('site' => false, 'my' => false, 'course' => true);
    }

    function get_content() {
        global $CFG, $COURSE, $OUTPUT;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);

        $config = quickmail::load_config($COURSE->id);
        $permission = has_capability('block/quickmail:cansend', $context);

        if ($permission or !empty($config['allowstudents'])) {
            $cparam = array('courseid' => $COURSE->id);

            $send_email_str = quickmail::_s('composenew');
            $send_email = html_writer::link(
                new moodle_url('/blocks/quickmail/email.php', $cparam),
                $send_email_str
            );
            $this->content->items[] = $send_email;
            $this->content->icons[] = $OUTPUT->pix_icon('i/email', $send_email_str);

            $signature_str = quickmail::_s('signature');
            $signature = html_writer::link(
                new moodle_url('/blocks/quickmail/signature.php', $cparam),
                $signature_str
            );
            $this->content->items[] = $signature;
            $this->content->icons[] = $OUTPUT->pix_icon('i/edit', $signature_str);

            $draft_params = $cparam + array('type' => 'drafts');
            $drafts_email_str = quickmail::_s('drafts');
            $drafts = html_writer::link(
                new moodle_url('/blocks/quickmail/emaillog.php', $draft_params),
                $drafts_email_str
            );
            $this->content->items[] = $drafts;
            $this->content->icons[] = $OUTPUT->pix_icon('i/settings', $drafts_email_str);
        }

        // History can't be view by students
        if ($permission) {
            $history_str = quickmail::_s('history');
            $history = html_writer::link(
                new moodle_url('/blocks/quickmail/emaillog.php', $cparam),
                $history_str
            );
            $this->content->items[] = $history;
            $this->content->icons[] = $OUTPUT->pix_icon('i/settings', $history_str);
        }

        if (has_capability('block/quickmail:canconfig', $context)) {
            $config_str = quickmail::_s('config');
            $config = html_writer::link(
                new moodle_url('/blocks/quickmail/config.php', $cparam),
                $config_str
            );
            $this->content->items[] = $config;
            $this->content->icons[] = $OUTPUT->pix_icon('i/settings', $config_str);
        }

        return $this->content;
    }
}
