<?php

require_once('../../config.php');
require_once('lib.php');
require_once('email_form.php');

$courseid = required_param('courseid', PARAM_INT);
$type = optional_param('type', '', PARAM_ACTION);
$typeid = optional_param('typeid', 0, PARAM_INT);
$sigid = optional_param('sigid', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid));
if(!$course) {
    print_error('no_course', 'block_quickmail', '', $courseid);
}
require_login($course);

// They declared a type ... is it valid?
if(!empty($type) and !in_array($type, array('log', 'drafts'))){
    print_error('no_type', 'block_quickmail', '', $type);
}

// They declared a type ... does it have an id?
if(!empty($type) and empty($typeid)) {
    $string = new stdclass;
    $string->tpe = $type;
    $string->id = $typeid;
    print_error('no_typeid', 'block_quickmail', '', $string);
}

// Determined that the course exists, let's pull configs for it
$config = quickmail_load_config($courseid);

$context = get_context_instance(CONTEXT_COURSE, $courseid);
$has_permission = has_capability('block/quickmail:cansend', $context) ||
                  !empty($config['allowstudents']);

if(!$has_permission) {
    print_error('no_permission', 'block_quickmail');
}

// Quickmail signatures
$sigs = $DB->get_records('block_quickmail_signatures',
            array('userid' => $USER->id), 'default_flag DESC');

// Permission checks complete
$blockname = get_string('pluginname', 'block_quickmail');
$header = get_string('email', 'block_quickmail');

$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->navbar->add($blockname);
$PAGE->navbar->add($header);
$PAGE->set_title($blockname . ': '. $header);
$PAGE->set_heading($blockname . ': '.$header);
$PAGE->set_url('/course/view.php?id='.$courseid);

$internal_lib = '/lib/jquery.js';
$jquery = file_exists($CFG->dirroot.$internal_lib) ? $internal_lib :
    '/blocks/quickmail/js/jquery-1.6.min.js';

$PAGE->requires->js($jquery);
$PAGE->requires->js('/blocks/quickmail/js/selection.js');

// Roles in the course
$course_roles = get_roles_used_in_context($context);

// Selected filter roles
if (!empty($config['roleselection'])) {
    list($sql, $params) = $DB->get_in_or_equal(explode(',', $config['roleselection']));
    $filter_roles = $DB->get_records_select('role', "id $sql", $params);
} else {
    $filter_roles = array();
}

// These are the roles we want
$roles = quickmail_filter_roles($course_roles, $filter_roles);

// Get all the groups if user can edit groups
$allgroups = groups_get_all_groups($courseid);
if(!has_capability('moodle/site:accessallgroups', $context)) {
    // Get users groups for FERPA reasons
    $mastercap = false;
    $mygroups = groups_get_user_groups($courseid);
    $gids = array_values($mygroups['0']);
    list($sql, $params) = $DB->get_in_or_equal($gids);
    $groups = empty($gids) ? array() :
        $DB->get_records_select('groups', "id $sql", $params);
} else {
    $mastercap = true;
    $groups = $allgroups;
}

$globalaccess = empty($allgroups);

// Fill the course users by
$users = array();
$users_to_roles = array();
$users_to_groups = array();

$everyone = get_role_users(0, $context, false, 'u.id, u.firstname,
            u.lastname, u.email, u.mailformat, u.maildisplay,
            r.id AS roleid', 'u.lastname, u.firstname');

foreach ($everyone as $userid => $user) {
    $usergroups = groups_get_user_groups($courseid, $userid);

    $groupmapper = function($id) use ($allgroups) { return $allgroups[$id]; };
    $gids = ($globalaccess or $mastercap) ? array_values($usergroups['0']) :
            array_intersect(array_values($mygroups['0']), array_values($usergroups['0']));

    $userroles = get_user_roles($context, $userid);
    $filterd = quickmail_filter_roles($userroles, $roles);

    // Available groups
    if((!$globalaccess and !$mastercap) and
        empty($gids) or empty($filterd) or $userid == $USER->id)
        continue;
    $users_to_groups[$userid] = array_map($groupmapper, $gids);
    $users_to_roles[$userid] = $filterd;
    $users[$userid] = $user;
}

// Can't do anything without users
if(empty($users)) {
    print_error('no_users', 'block_quickmail');
}

// Send emails or save drafts
$warnings = array();
if(!empty($type)) {
    $email = $DB->get_record('block_quickmail_'.$type, array('id' => $typeid));
    $email->message = array(
        'text' => $email->message,
        'format' => $email->format
    );
} else {
    $email = new stdClass;
    $email->subject = '';
    $email->message = array(
        'text' => '',
        'format' => $USER->mailformat
    );
}

// Some setters for the form
$email->type = $type;
$email->typeid = $typeid;

// Fill emailed users
$selected = array();
if (($mailto = optional_param('mailto', '', PARAM_SEQUENCE)) != '') {
    $email->mailto = $mailto;
}
if(!empty($email->mailto)) {
    foreach(explode(',', $email->mailto) as $id) {
        if (!array_key_exists($id, $users)) {
            continue;
        }
        $selected[$id] = $users[$id];
        unset($users[$id]);
    }
}

$form = new email_form(null, array(
    'selected' => $selected,
    'users' => $users,
    'roles' => $roles,
    'groups' => $groups,
    'users_to_roles' => $users_to_roles,
    'users_to_groups' => $users_to_groups,
    'sigs' => array_map(function($sig) { return $sig->title; }, $sigs)
));

if ($form->is_cancelled()) {
    redirect(new moodle_url('/course/view.php?id='.$courseid));

} else if (($data = $form->get_data()) and (isset($data->send) or isset($data->draft))) {
    $email = $data;

    // Submitted data
    $email->time = time();
    $email->format = $email->message['format'];
    $email->message = $email->message['text'];
    $email->attachment = quickmail_attachment_names($email->attachments);

    // Store email; id is needed for file storage
    if(isset($email->send)) {
        $id = $DB->insert_record('block_quickmail_log', $email);
        $table = 'log';
    } else if(isset($email->draft)) {
        // Update draft
        $table = 'drafts';
        if(!empty($typeid)) {
            $id = $email->id = $typeid;
            $DB->update_record('block_quickmail_drafts', $email);
        } else {
            $id = $DB->insert_record('block_quickmail_drafts', $email);
        }
    }

    // An instance id is needed before storing the file repository
    file_save_draft_area_files($email->attachments, $context->id,
                               'block_quickmail_'.$table, 'attachment', $id);

    // Send emails
    if(isset($email->send)) {
        if($type == 'drafts') {
            quickmail_draft_cleanup($typeid);
        }

        list($zipname, $zip, $actual_zip) = quickmail_process_attachments($context, $email, $table, $id);
        // Setup subject
        if (!empty($config['courseinsubject'])) {
            $subject = format_string($course->shortname) . ': ' . $email->subject;
        } else {
            $subject = $email->subject;
        }
        $messagehtml = format_text($email->message, $email->format);
        if (!empty($config['breadcrumbsinbody'])) {
            $messagehtml = html_writer::link(new moodle_url('/'), format_string($SITE->fullname)).' &gt; '.
                           html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)), format_string($course->fullname)).
                           "<br /><br /><br />".
                           $messagehtml;
        }
        // Attempt to add signature
        if(!empty($sigs) and $email->sigid > -1) {
            $messagehtml .= $sigs[$email->sigid]->signature;
        }
        $messagetext = html_to_text($messagehtml, 0);

        foreach(explode(',', $email->mailto) as $userid) {
            $success = email_to_user($selected[$userid], $USER, $subject, $messagetext, $messagehtml, $zip, $zipname);

            if(!$success) {
                $warnings[] = get_string("no_email", 'block_quickmail', $selected[$userid]);
            }
        }

        // Send to self if they want
        if($email->receipt) {
            email_to_user($USER, $USER, $subject, $messagetext, $messagehtml, $zip, $zipname);
        }

        // We're done with the zip.
        if(!empty($zip)) {
            unlink($actual_zip);
        }
    }
}

if(empty($email->attachments)) {
    if(!empty($type)) {
        $attachid = file_get_submitted_draft_itemid('attachemnt');
        file_prepare_draft_area($attachid, $context->id, 'block_quickmail_'.$type, 'attachment', $typeid);
        $email->attachments = $attachid;
    }
}

$form->set_data($email);

//TODO: route the user back to course or notify or something
// when successful
// for now we route to their specific save action
if(empty($warnings)) {
    // Send, sends them back to the course
    if(isset($email->send))
        redirect($CFG->wwwroot.'/blocks/quickmail/emaillog.php?courseid='.$course->id);
    else if (isset($email->draft))
        $warnings[] = get_string("changessaved");
}

echo $OUTPUT->header();
echo $OUTPUT->heading($blockname);

// Print out warnings
foreach($warnings as $warning) {
    echo $OUTPUT->notification($warning);
}

$form->display();

echo $OUTPUT->footer();
