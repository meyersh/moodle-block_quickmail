<?php

// Written at Louisiana State University

require_once('../../config.php');
require_once('lib.php');
require_once('signature_form.php');

require_login();

$courseid = required_param('courseid', PARAM_INT);
$sigid = optional_param('id', 0, PARAM_INT);
$flash = optional_param('flash', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

if ($courseid and !$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('no_course', 'block_quickmail', '', $courseid);
}

$config = quickmail::load_config($courseid);

$context = get_context_instance(CONTEXT_COURSE, $courseid);

$has_permission = (
    has_capability('block/quickmail:cansend', $context) or
    !empty($config['allowstudents'])
);

if (!$has_permission) {
    print_error('no_permission', 'block_quickmail');
}

$blockname = quickmail::_s('pluginname');
$header = quickmail::_s('signature');

$title = "{$blockname}: {$header}";

$PAGE->set_context($context);

$PAGE->set_course($course);
$PAGE->set_url('/blocks/quickmail/signature.php', array(
    'courseid' => $courseid, 'id' => $sigid
));

$PAGE->navbar->add($blockname);
$PAGE->navbar->add($header);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagetype($blockname);

$params = array('userid' => $USER->id);
$dbsigs = $DB->get_records('block_quickmail_signatures', $params);

$sig = (!empty($sigid) and isset($sigs[$sigid])) ? $sigs[$sigid] : new stdClass;

if (empty($sigid) or !isset($dbsigs[$sigid])) {
    $sig = new stdClass;
    $sig->id = null;
    $sig->title = '';
    $sig->signature = '';
} else {
    $sig = $dbsigs[$sigid];
}

$sig->courseid = $courseid;
$sig->signatureformat = $USER->mailformat;

$options = array(
    'trusttext' => true,
    'subdirs' => true,
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'context' => $context
);

$sig = file_prepare_standard_editor($sig, 'signature', $options, $context,
    'block_quickmail', 'signature', $sig->id);

$form = new signature_form(null, array('signature_options' => $options));

if ($confirm) {
    $DB->delete_records('block_quickmail_signatures', array('id' => $sigid));
    redirect(new moodle_url('/blocks/quickmail/signature.php', array(
        'courseid' => $courseid,
        'flash' => 1
    )));
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
} else if ($data = $form->get_data()) {
    if (isset($data->delete)) {
        $delete = true;
    }

    if (empty($data->title)) {
        $warnings[] = quickmail::_s('required');
    }

    if (empty($warnings) and empty($delete)) {
        $data->signature = $data->signature_editor['text'];

        if (empty($data->default_flag)) {
            $data->default_flag = 0;
        }

        $params = array('userid' => $USER->id, 'default_flag' => 1);
        $default = $DB->get_record('block_quickmail_signatures', $params);

        if ($default and !empty($data->default_flag)) {
            $default->default_flag = 0;
            $DB->update_record('block_quickmail_signatures', $default);
        }

        if (!$default) {
            $data->default_flag = 1;
        }

        if (empty($data->id)) {
            $data->id = null;
            $data->id = $DB->insert_record('block_quickmail_signatures', $data);
        }

        // Persist relative links
        $data = file_postupdate_standard_editor($data, 'signature', $options,
            $context, 'block_quickmail', 'signature', $data->id);

        $DB->update_record('block_quickmail_signatures', $data);

        $url = new moodle_url('signature.php', array(
            'id' => $data->id, 'courseid' => $course->id, 'flash' => 1
        ));
        redirect($url);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

$first = array(0 => 'New '.quickmail::_s('sig'));
$only_names = function ($sig) {
    return ($sig->default_flag) ? $sig->title . ' (Default)': $sig->title;
};
$sig_options = $first + array_map($only_names, $dbsigs);

$form->set_data($sig);

if ($flash) {
    echo $OUTPUT->notification(get_string('changessaved'), 'notifysuccess');
}

if (!empty($delete)) {
    $msg = get_string('are_you_sure', 'block_quickmail', $sig);
    $confirm_url = new moodle_url('/blocks/quickmail/signature.php', array(
        'id' => $sig->id,
        'courseid' => $courseid,
        'confirm' => 1
    ));
    $cancel_url = new moodle_url('/blocks/quickmail/signature.php', array(
        'id' => $sig->id,
        'courseid' => $courseid
    ));
    echo $OUTPUT->confirm($msg, $confirm_url, $cancel_url);
} else {
    echo $OUTPUT->single_select('signature.php?courseid='.$courseid, 'id', $sig_options, $sigid);

    $form->display();
}

echo $OUTPUT->footer();
