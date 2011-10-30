<?php

require_once('../../config.php');
require_once('lib.php');
require_once('email_form.php');

require_login();

$courseid = required_param('courseid', PARAM_INT);
$type = optional_param('type', '', PARAM_ACTION);
$typeid = optional_param('typeid', 0, PARAM_INT);
$sigid = optional_param('sigid', 0, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid));
if(!$course) {
    print_error('no_course', 'block_quickmail', '', $courseid);
}

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

$PAGE->requires->js('/lib/jquery.js');
$PAGE->requires->js('/blocks/quickmail/selection.js');

// Looking at a pure Moodle solution
$roles = get_roles_used_in_context($context);

// Get all the groups if user can edit groups 
if(has_capability('moodle/site:accessallgroups', $context)) {
    $groups = groups_get_all_groups($courseid);
} else {
    $groups = groups_get_user_groups($courseid);
}

// Fill the course users by
$users = array();
$users_to_roles = array();
$users_to_groups = array();

// Grab users by group, for FERPA reasons
foreach($groups as $groupid => $group) {
    $group_users = get_role_users(0, $context, false, 'u.id, u.firstname, u.lastname, 
                   u.email, u.mailformat, u.maildisplay, r.id AS roleid', 
                   'u.lastname, u.firstname', null, $groupid); 

    foreach($group_users as $userid => $user) {
        // Only need to grab the user roles in course once 
        if(!isset($users_to_roles[$userid])) {
            $users_to_roles[$userid] = get_user_roles($context, $userid); 
        }

        if(!isset($users_to_groups[$userid])) {
            $users_to_groups[$userid] = array();
        }
        $users_to_groups[$userid][$groupid] = $group;

        if(isset($users[$userid])) {
            continue;
        }

        $users[$userid] = $user;
    }
}

// Can't do anything without users
if(empty($users)) {
    print_error('no_users', 'block_quickmail');
}

// Send emails or save drafts
$warnings = array();
if($email = data_submitted()) {
    // Cancelled form
    if(isset($email->cancel)) {
        redirect(new moodle_url('/course/view.php?id='.$courseid));
    }

    // Got to have a subject
    if(empty($email->subject)) {
        $warnings[] = get_string('no_subject', 'block_quickmail'); 
    }

    // Got to have recipients
    if(empty($email->mailto)) {
        $warnings[] = get_string('no_users', 'block_quickmail');
    }

} else if(!empty($type)) {
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
if(!empty($email->mailto)) {
    foreach(explode(',', $email->mailto) as $id) {
        $selected[$id] = $users[$id];
        unset($users[$id]);
    }
}

// Empty warning and on submit
$submitted = (isset($email->send) or isset($email->draft));
if(empty($warnings) && $submitted) {
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

        list($zip, $zipname, $actual_zip) = quickmail_process_attachments($context, $email, $table, $id);
        // Attempt to add signature
        if(!empty($sigs) and $email->sigid > -1) {
            $email->message .= $sigs[$email->sigid]->signature;
        }

        foreach(explode(',', $email->mailto) as $userid) {
            $success = email_to_user($selected[$userid], $USER, $email->subject, 
                         strip_tags($email->message), $email->message, $zip, $zipname);

            if(!$success) {
                $warnings[] = get_string("no_email", 'block_quickmail', $selected[$userid]);
            }
        }

        // Send to self if they want
        if($email->receipt) {
            email_to_user($USER, $USER, $email->subject, strip_tags($email->message), $email->message, $zip, $zipname);
        }

        // We're done with the zip.
        if(!empty($zip)) {
            unlink($actual_zip);
        }
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
    if(isset($email->send)) {} 
        //redirect($CFG->wwwroot.'/blocks/quickmail/emaillog.php?courseid='.$course->id);
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
