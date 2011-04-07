<?php
/**
 * Quickmail - Allows teachers and students to email one another
 *      at a course level.  Also supports group mode so students
 *      can only email their group members if desired.  Both group
 *      mode and student access to Quickmail are configurable by
 *      editing a Quickmail instance.
 *
 * @author Mark Nielsen
 * @author Charles Fulton
 * @version 2.00
 * @package quickmail
 **/ 

/**
 * This is the Quickmail block class.  Contains the necessary
 * functions for a Moodle block.  Has some extra functions as well
 * to increase its flexibility and useability
 *
 * @package quickmail
 * @todo Make a global config so that admins can set the defaults (default for student (yes/no) default for groupmode (select a groupmode or use the courses groupmode)) NOTE: make sure email.php and emaillog.php use the global config settings
 **/
class block_quickmail extends block_list {
    
    /**
     * Sets the block name and version number
     *
     * @return void
     **/
    function init() {
        $this->title = get_string('blockname', 'block_quickmail');
    }
    
    /**
     * Gets the contents of the block (course view)
     *
     * @return object An object with an array of items, an array of icons, and a string for the footer
     **/
    function get_content() {
        global $CFG, $OUTPUT;

        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->footer = '';
        $this->content->items = array();
        $this->content->icons = array();
        
        if (empty($this->instance) or !$this->check_permission()) {
            return $this->content;
        }

    /// link to composing an email
        $this->content->items[] = "<a href=\"$CFG->wwwroot/blocks/quickmail/email.php?id={$this->page->course->id}&amp;instanceid={$this->instance->id}\">".
                                    get_string('compose', 'block_quickmail').'</a>';

        $this->content->icons[] = '<img src="'.$OUTPUT->pix_url('/i/email'). '" height="16" width="16" alt="'.get_string('email').'" />';

    /// link to history log
        $this->content->items[] = "<a href=\"$CFG->wwwroot/blocks/quickmail/emaillog.php?id={$this->page->course->id}&amp;instanceid={$this->instance->id}\">".
                                    get_string('history', 'block_quickmail').'</a>';

        $this->content->icons[] = '<img src="'.$OUTPUT->pix_url('t/log'). '" height="14" width="14" alt="'.get_string('log', 'admin').'" />';

        return $this->content;
    }

    /**
     * Cleanup the history
     *
     * @return boolean
     **/
    function instance_delete() {
        global $CFG, $DB;
        if($CFG->quickmail_deletehistory) {
            return $DB->delete_records('block_quickmail_log', array('courseid' => $this->page->course->id));
        } else return true;
    }

    /**
     * Set defaults for new instances
     *
     * @return boolean
     **/
    function instance_create() {
        $this->config = new stdClass;
        $this->config->groupmode = $this->page->course->groupmode;
        $this->config->defaultformat = (can_use_html_editor()) ? FORMAT_HTML : FORMAT_PLAIN;
        $pinned = (!isset($this->instance->pageid));
        return $this->instance_config_commit($pinned);
    }

    /**
     * Allows the block to be configurable at an instance level.
     *
     * @return boolean
     **/
    function instance_allow_config() {
        return true;
    }

    /**
     * Check to make sure that the current user is allowed to use Quickmail.
     *
     * @return boolean True for access / False for denied
     **/
    function check_permission() {
        return has_capability('block/quickmail:cansend', get_context_instance(CONTEXT_BLOCK, $this->instance->id));
    }
}
?>
