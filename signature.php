<?php

require_once('../../config.php');
require_once('signature_form.php');

require_login();

$courseid = optional_param('courseid', 0, PARAM_INT);
$sigid = optional_param('id', 0, PARAM_INT);

// Try to get the course is it's there
if(!empty($courseid) and !$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('no_course', 'block_quickmail', $courseid);
}

$context = empty($courseid) ? get_context_instance(CONTEXT_SYSTEM) : 
                              get_context_instance(CONTEXT_COURSE, $courseid);

if($data = data_submitted()) {
    if(isset($data->cancel)) {
        $to = !empty($courseid) ? '/course/view.php?id='.$courseid : '/my';
        redirect($CFG->wwwroot.$to);
    }

    if(empty($data->title) or empty($data->signature_editor['text'])) {
        $warnings[] = get_string('required', 'block_quickmail');
    }

    if(empty($warnings)) {
        $data->signature = $data->signature_editor['text'];
        if(empty($data->id)) {
            $data->id = null;
            $data->id = $DB->insert_record('block_quickmail_signatures', $data);
        }
        // Grab default if there is one
        $default = $DB->get_record('block_quickmail_signatures', 
            array('userid'=>$USER->id, 'default_flag' => 1));

        // No default, force this one
        // Default exists, but this one was made default
        if($default and (!empty($data->default_flag) and $default->id != $data->id)) {
            $default->default_flag = 0;
            $DB->update_record('block_quickmail_signatures', $default);
        } else {
            $data->default_flag = 1;
        }
        $DB->update_record('block_quickmail_signatures', $data);
        $sigid = $data->id;
    }
}


// No permissions
$sigs = array_map(function($sig) {
    $sig->signature_editor = array(
        'text' => $sig->signature,
        'format' => 1
    );
    return $sig;
}, $DB->get_records('block_quickmail_signatures', array('userid' => $USER->id)));

$blockname = get_string('pluginname', 'block_quickmail');
$header = get_string('signature', 'block_quickmail');
$title = "{$blockname}: {$header}";

$PAGE->set_context($context);
if($course) {
    $PAGE->set_course($course);
    $PAGE->set_url('/course/view.php?id='.$courseid);
}
$PAGE->navbar->add($blockname);
$PAGE->navbar->add($header);
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

// TODO: dropdown
$sig_options = array_merge(array(0 => 'New '.get_string('sig', 'block_quickmail')),
    array_map(function($sig) { 
        if($sig->default_flag) 
            return $sig->title . ' (Default)';
        else
            return $sig->title; 
}, $sigs));

echo $OUTPUT->single_select('signature.php?courseid='.$courseid, 'id', $sig_options, $sigid);

$sig = (!empty($sigid) and isset($sigs[$sigid])) ? $sigs[$sigid] : new stdClass;
// Needed for form submission
$sig->courseid = $courseid;

$form = new signature_form();

$form->set_data($sig);
$form->display();

echo $OUTPUT->footer();

function grab_default() {
    global $USER, $DB;
    return $DB->get_field('block_quickmail_signatures', 'id', 
        array('userid' => $USER->id, 'default_flag' => 1));
}
