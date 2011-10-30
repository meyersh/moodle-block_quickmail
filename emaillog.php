<?php

require_once('../../config.php');
require_once('lib.php');

require_login();

$courseid = required_param('courseid', PARAM_INT);
$type = optional_param('type', 'log', PARAM_ACTION);
$typeid = optional_param('typeid', 0, PARAM_INT);
$action = optional_param('action', null, PARAM_ACTION);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 10, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid));
if(!$course) {
    print_error('no_course', 'block_quickmail', '', $courseid);
}

$context = get_context_instance(CONTEXT_COURSE, $courseid);

// Has to be in on of these
if(!in_array($type, array('log', 'drafts'))) {
    print_error('not_valid', 'block_quickmail', '', $type);
}

// load configs... student should be able to view drafts
$config = quickmail_load_config($courseid);

$proper_permission = (has_capability('block/quickmail:cansend', $context) or
                     (!empty($config['allowstudents']) and $type == 'drafts'));

if(!$proper_permission) {
    print_error('no_permission', 'block_quickmail');
}

if(isset($action) and !in_array($action, array('delete', 'confirm'))) {
    print_error('not_valid_action', 'block_quickmail', '', $action);
}

if(isset($action) and empty($typeid)) {
    print_error('not_valid_typeid', 'block_quickmail', '', $action);
}

$blockname= get_string('pluginname', 'block_quickmail');
$header = get_string($type, 'block_quickmail');

$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->navbar->add($blockname);
$PAGE->navbar->add($header);
$PAGE->set_title($blockname . ': '. $header);
$PAGE->set_heading($blockname . ': '.$header);
$PAGE->set_url('/course/view.php?id='.$courseid);

$dbtable = 'block_quickmail_' . $type;
$count = $DB->count_records($dbtable,
                            array('courseid' => $courseid, 'userid' => $USER->id));

// Perform actions
switch ($action) {
    // Confirm deletion, routes back to itself
    case "confirm":
        if(quickmail_cleanup($dbtable, $typeid))
            redirect($CFG->wwwroot.'/blocks/quickmail/emaillog.php?courseid='.
            $courseid.'&amp;type='.$type);
        else 
            print_error('delete_failed', 'block_quickmail');
    // Prints delete dialog
    case "delete":
        $html = delete_dialog($courseid, $type, $typeid);
        break;
    // List entries as default
    default:
        $html = list_entries($courseid, $type, $page, $perpage, $count);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

if(empty($count)) {
    echo $OUTPUT->notification(get_string('no_'.$type, 'block_quickmail'));
    echo $OUTPUT->continue_button('/blocks/quickmail/email.php?courseid='.$courseid);
    echo $OUTPUT->footer();
    exit;
}

echo $html;

echo $OUTPUT->footer();

// Display functions
function delete_dialog($courseid, $type, $typeid) {
    global $CFG, $DB, $USER, $OUTPUT;

    $email = $DB->get_record('block_quickmail_'.$type, array('id' => $typeid));
    if(empty($email))
        print_error('not_valid_typeid', 'block_quickmail', '', $typeid);

    // Readable time
    $optionyes = '/blocks/quickmail/emaillog.php?courseid='.$courseid.
                 '&amp;type='.$type.'&amp;typeid='.$typeid.'&amp;action=confirm';
    $optionno = '/blocks/quickmail/emaillog.php?courseid='.$courseid.
                '&amp;type='.$type;

    $table = new html_table();
    $table->head = array(get_string('date'), get_string('subject', 'block_quickmail'));
    $table->data = array(
        new html_table_row(array(
            new html_table_cell(quickmail_format_time($email->time)),
            new html_table_cell($email->subject))
        )
    );
    $msg = get_string('delete_confirm', 'block_quickmail', html_writer::table($table)); 
    // Dialog box
    $html = $OUTPUT->confirm($msg, $optionyes, $optionno);
    return $html;
}

function list_entries($courseid, $type, $page, $perpage, $count) {
    global $CFG, $DB, $USER, $OUTPUT;

    $dbtable = 'block_quickmail_'.$type;

    $table = new html_table();
    $logs = $DB->get_records($dbtable, array('courseid' => $courseid, 'userid' => $USER->id), 
                            'time DESC', '*', $page * $perpage, $perpage * ($page + 1));

    $table->head= array(get_string('date'), get_string('subject', 'block_quickmail'), 
        get_string('attachment', 'block_quickmail'), get_string('action'));
    $table->data = array_map(function($log) use ($OUTPUT, $type) {
        $date = new html_table_cell(quickmail_format_time($log->time));
        $subject = new html_table_cell($log->subject);
        $attachments = new html_table_cell($log->attachment);
        $actions = new html_table_cell(
            implode(' ', array(
                html_writer::link(
                    new moodle_url('/blocks/quickmail/email.php?courseid='.
                                $log->courseid.'&amp;type='.$type.'&amp;typeid='.$log->id),
                    $OUTPUT->pix_icon("i/search", "Open Email") 
                ),
                // TODO: make this work
                html_writer::link(
                    new moodle_url('/blocks/quickmail/emaillog.php?courseid='.
                    $log->courseid.'&amp;type='.$type.'&amp;typeid='.
                    $log->id.'&amp;action=delete'),
                    $OUTPUT->pix_icon("i/cross_red_big", "Delete Email")
                )
            ))
        );
        return new html_table_row(array($date, $subject, $attachments, $actions));
    }, $logs);


    $html = $OUTPUT->paging_bar($count, $page, $perpage, '/blocks/quickmail/emaillog.php?courseid='.$courseid);
    $html .= html_writer::table($table);
    return $html;
}
