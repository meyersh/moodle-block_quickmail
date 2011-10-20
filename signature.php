<?php

require_once('../../config.php');
require_once('signature_form.php');

$courseid = optional_param('courseid', 0, PARAM_INT);
$sigid = optional_param('id', 0, PARAM_INT);

// Try to get the course is it's there
if(!empty($courseid) and !$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('no_course', 'block_quickmail', $courseid);
}

$context = empty($courseid) ? get_context_instance(CONTEXT_SYSTEM) :
                              get_context_instance(CONTEXT_COURSE, $courseid);

require_login(empty($courseid) ? null : $courseid);

$form = new signature_form();

if ($form->is_cancelled()) {
    $to = !empty($courseid) ? '/course/view.php?id='.$courseid : '/my';
    redirect($CFG->wwwroot.$to);

} else if ($data = $form->get_data()) {
    $data->signature = $data->signature_editor['text'];

    if (!empty($data->default_flag)) {
        $DB->set_field('block_quickmail_signatures', 'default_flag', 0, array('userid'=>$USER->id, 'default_flag' => 1));
    }
    if(empty($data->id)) {
        $data->id = null;
        $data->id = $DB->insert_record('block_quickmail_signatures', $data);
    } else {
        $DB->update_record('block_quickmail_signatures', $data);
    }
    redirect(new moodle_url('/blocks/quickmail/signature.php', array('courseid' => $courseid, 'id' => $data->id)));
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
    $PAGE->set_url('/course/view.php?id='.$courseid);
}
$PAGE->navbar->add($blockname);
$PAGE->navbar->add($header);
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

$sig_options = array(0 => 'New '.get_string('sig', 'block_quickmail'));
foreach ($sigs as $sig) {
    $sig_options[$sig->id] = $sig->title;

    if ($sig->default_flag) {
        $sig_options[$sig->id] = $sig->title . ' (Default)';;
    }
}
echo $OUTPUT->single_select('signature.php?courseid='.$courseid, 'id', $sig_options, $sigid);

$sig = (!empty($sigid) and isset($sigs[$sigid])) ? $sigs[$sigid] : new stdClass;
// Needed for form submission
$sig->courseid = $courseid;

$form->set_data($sig);
$form->display();

echo $OUTPUT->footer();