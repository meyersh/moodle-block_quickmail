<?php

require_once '../../config.php';
require_once 'lib.php';
require_once 'config_form.php';

$courseid = required_param('courseid', PARAM_INT);
$reset = optional_param('reset', 0, PARAM_INT);

if(!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('no_course', 'block_quickmail', '', $courseid);
}
require_login($course);

$context= get_context_instance(CONTEXT_COURSE, $courseid);

if(!has_capability('block/quickmail:canconfig', $context)) {
    print_error('no_permission', 'block_quickmail');
}

$blockname = get_string('pluginname', 'block_quickmail');
$header = get_string('config', 'block_quickmail');

$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_url('/blocks/quickmail/config.php', array('courseid' => $courseid));
$PAGE->set_title($blockname . ': '. $header);
$PAGE->set_heading($blockname. ': '. $header);
$PAGE->navbar->add($header);

$changed = false;

// Blow away what they set
if($reset) {
    $changed = true;
    quickmail_default_config($courseid);
}

$roles = $DB->get_records_menu('role', null, 'sortorder ASC', 'id, name');
$form = new config_form(null, array(
    'courseid' => $courseid,
    'roles' => $roles
));

if($data = $form->get_data()) {
    $config = get_object_vars($data);
    // Don't need these
    unset($config['save'], $config['courseid']);
    $config['roleselection'] = implode(',', $config['roleselection']);
    quickmail_save_config($courseid, $config);
    $changed = true;
}

$config = quickmail_load_config($courseid);
$config['roleselection'] = explode(',', $config['roleselection']);

$form->set_data($config);

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

echo $OUTPUT->box_start();
if($changed) {
    echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}
$form->display();
echo $OUTPUT->box_end();

echo $OUTPUT->footer();
