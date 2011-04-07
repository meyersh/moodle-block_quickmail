<?php
/**
 * emaillog.php - displays a log (or history) of all emails sent by
 *      a specific in a specific course.  Each email log can be viewed
 *      or deleted.
 *
 * @todo Add a print option?
 * @author Mark Nielsen
 * @author Charles Fulton
 * @version 2.00
 * @package quickmail
 **/
    
    require_once('../../config.php');
    require_once($CFG->libdir.'/blocklib.php');
    require_once($CFG->libdir.'/tablelib.php');
    
    $id = required_param('id', PARAM_INT);    // course id
    $action = optional_param('action', '', PARAM_ALPHA);
    $instanceid = optional_param('instanceid', 0, PARAM_INT);

    $instance = new stdClass;

    if (!$course = $DB->get_record('course', array('id' => $id))) {
        print_error('Course ID was incorrect');
    }

    require_login($course->id);

    if ($instanceid) {
        $instance = $DB->get_record('block_instances', array('id' => $instanceid));
    } else {
        if ($quickmailblock = $DB->get_record('block', array('name' => 'quickmail'))) {
            $instance = $DB->get_record('block_instance', array('blockid' => $quickmailblock->id, 'pageid' => $course->id));
        }
    }

/// This block of code ensures that Quickmail will run 
///     whether it is in the course or not
    if (empty($instance)) {
        if (has_capability('block/quickmail:cansend', get_context_instance(CONTEXT_BLOCK, $instanceid))) {
            $haspermission = true;
        } else {
            $haspermission = false;
        }
    } else {
        // create a quickmail block instance
        $quickmail = block_instance('quickmail', $instance);
        $haspermission = $quickmail->check_permission();
    }
    
    if (!$haspermission) {
        print_error('Sorry, you do not have the correct permissions to use Quickmail.');
    }

    $PAGE->set_url('/blocks/quickmail/emaillog.php', array('id' => $id, 'instanceid' => $instanceid));
    
    // log deleting happens here (NOTE: reporting is handled below)
    $dumpresult = false;
    if ($action == 'dump') {
        confirm_sesskey();
        
        // delete a single log or all of them
        if ($emailid = optional_param('emailid', 0, PARAM_INT)) {
            $dumpresult = $DB->delete_records('block_quickmail_log', array('id' => $emailid));
        } else {
            $dumpresult = $DB->delete_records('block_quickmail_log', array('userid' => $USER->id));
        }
    }

/// set table columns and headers
    $tablecolumns = array('timesent', 'subject', 'attachment', '');
    $tableheaders = array(get_string('date', 'block_quickmail'), get_string('subject', 'forum'),
                         get_string('attachment', 'block_quickmail'), get_string('action', 'block_quickmail'));

    $table = new flexible_table('bocks-quickmail-emaillog');

/// define table columns, headers, and base url
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
    $table->define_baseurl($CFG->wwwroot.'/blocks/quickmail/emaillog.php?id='.$course->id.'&amp;instanceid='.$instanceid);

/// table settings
    $table->sortable(true, 'timesent', SORT_DESC);
    $table->collapsible(true);
    $table->initialbars(false);
    $table->pageable(true);

/// column styles (make sure date does not wrap) NOTE: More table styles in styles.php
    $table->column_style('timesent', 'width', '40%');
    $table->column_style('timesent', 'white-space', 'nowrap');

/// set attributes in the table tag
    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', 'emaillog');
    $table->set_attribute('class', 'generaltable generalbox');
    $table->set_attribute('align', 'center');
    $table->set_attribute('width', '80%');

    $table->setup();  
    
/// SQL
    $sql = "SELECT * FROM {block_quickmail_log} WHERE courseid = ?  AND userid = ?";
    $query_params = array($course->id, $USER->id);
    $sql .= ' ORDER BY '. $table->get_sql_sort();

/// set page size
    $total = $DB->count_records('block_quickmail_log', array('courseid' => $course->id, 'userid' => $USER->id));
    $table->pagesize(10, $total);
  
    if ($pastemails = $DB->get_records_sql($sql, $query_params, $table->get_page_start(), $table->get_page_size())) {
        foreach ($pastemails as $pastemail) {
            $table->add_data( array(userdate($pastemail->timesent),
                                    s($pastemail->subject),
                                    format_string($pastemail->attachment, true),
                                    "<a href=\"email.php?id=$course->id&amp;instanceid=$instanceid&amp;emailid=$pastemail->id&amp;action=view\">".
                                    "<img src=\"".$OUTPUT->pix_url('/i/search')."\" height=\"14\" width=\"14\" alt=\"".get_string('view').'" /></a> '.
                                    "<a href=\"emaillog.php?id=$course->id&amp;instanceid=$instanceid&amp;sesskey=$USER->sesskey&amp;action=dump&amp;emailid=$pastemail->id\">".
                                    "<img src=\"".$OUTPUT->pix_url('t/delete')."\" height=\"11\" width=\"11\" alt=\"".get_string('delete').'" /></a>'));
        }
    }
    
/// Start printing everyting
    $strquickmail = get_string('blockname', 'block_quickmail');
    if (empty($pastemails)) {
        $disabled = 'disabled="disabled" ';
    } else {
        $disabled = '';
    }
    $button = "<form method=\"post\" action=\"$CFG->wwwroot/blocks/quickmail/emaillog.php\">
               <input type=\"hidden\" name=\"id\" value=\"$course->id\" />
               <input type=\"hidden\" name=\"instanceid\" value=\"$instanceid\" />
               <input type=\"hidden\" name=\"sesskey\" value=\"".sesskey().'" />
               <input type="hidden" name="action" value="confirm" />
               <input type="submit" name="submit" value="'.get_string('clearhistory', 'block_quickmail')."\" $disabled/>
               </form>";
    
/// Header setup
    if ($course->category) {
        $navigation = "<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> ->";
    } else {
        $navigation = '';
    }

    print_header("$course->fullname: $strquickmail", $course->fullname, "$navigation $strquickmail", '', '', true, $button);

    echo $OUTPUT->heading($strquickmail);
    
    $currenttab = 'history';
    include($CFG->dirroot.'/blocks/quickmail/tabs.php');
    
/// delete reporting happens here
    if ($action == 'dump') {
        if ($dumpresult) {
            notify(get_string('deletesuccess', 'block_quickmail'), 'notifysuccess');
        } else {
            notify(get_string('deletefail', 'block_quickmail'));
        }
    }
    
    if ($action == 'confirm') {
        echo $OUTPUT->confirm(
                      get_string('areyousure', 'block_quickmail'),
                      new single_button(new moodle_url("$CFG->wwwroot/blocks/quickmail/emaillog.php?id=$course->id&amp;instanceid=$instanceid&amp;sesskey=".sesskey()."&amp;action=dump"),get_string('yes')),
                      new single_button(new moodle_url("$CFG->wwwroot/blocks/quickmail/emaillog.php?id=$course->id&amp;instanceid=$instanceid"),get_string('no')));            
    } else {
        echo '<div id="tablecontainer">';
        $table->print_html();
        echo '</div>';
    }

    echo $OUTPUT->footer();
?>