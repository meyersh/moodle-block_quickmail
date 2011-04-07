<?php
/**
 * email.php - Used by Quickmail for sending emails to users enrolled in a specific course.
 *      Calls email.hmtl at the end.
 *
 * @author Mark Nielsen
 * @author Charles Fulton
 * @version 2.00
 * @package quickmail
 **/
    
    require_once('../../config.php');
    require_once($CFG->libdir.'/blocklib.php');
    require_once($CFG->dirroot.'/blocks/quickmail/email_form.php');

    $id         = required_param('id', PARAM_INT);  // course ID
    $instanceid = optional_param('instanceid', 0, PARAM_INT);
    $action     = optional_param('action', '', PARAM_ALPHA);

    $instance = new stdClass;

    if (!$course = $DB->get_record('course', array('id' => $id))) {
        print_error('Course ID was incorrect');
    }

    require_login($course->id);
    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    if ($instanceid) {
        $instance = $DB->get_record('block_instances', array('id' => $instanceid));
    } else {
        if ($quickmailblock = $DB->get_record('block', array('name' => 'quickmail'))) {
            $instance = $DB->get_record('block_instances', array('id' => $quickmailblock->id, 'parentcontextid' => $context->id));
        }
    }

    $PAGE->set_url('/blocks/quickmail/email.php', array('id' => $id, 'instanceid' => $instanceid));

/// This block of code ensures that Quickmail will run 
///     whether it is in the course or not
    if (empty($instance)) {
        $groupmode = groupmode($course);
        if (has_capability('block/quickmail:cansend', get_context_instance(CONTEXT_BLOCK, $instanceid))) {
            $haspermission = true;
        } else {
            $haspermission = false;
        }
    } else {
        // create a quickmail block instance
        $quickmail = block_instance('quickmail', $instance);
        $groupmode     = $quickmail->config->groupmode;
        $haspermission = $quickmail->check_permission();
    }
    
    if (!$haspermission) {
        print_error('Sorry, you do not have the correct permissions to use Quickmail.');
    }

    if (!$courseusers = get_enrolled_users($context)) {
        print_error('No course users found to email');
    }

    // Groups mode handling
    // Separate groups only makes sense for a student with the cansend capability
    if($groupmode == SEPARATEGROUPS && has_capability('moodle/site:accessallgroups', $context)) {
        $groupmode = VISIBLEGROUPS;
    }

    // Build groups list
    // To make processing easier we do this even if we're not in groups mode
    $nogroup = new stdClass;
    $nogroup->id = 0;
    $nogroup->name = get_string('notingroup', 'block_quickmail');
    $userlist = array();
    switch($groupmode) {
        case NOGROUPS:
            $groups = array('0' => $nogroup);
            $userlist[0] = array('' => $groups[0]->name);
            break;
        case VISIBLEGROUPS:
            $groups = groups_get_all_groups($id);
            $groups[0] = $nogroup;
            $userlist[0] = array('' => $groups[0]->name);
            break;
        case SEPARATEGROUPS:
            $groups = array();
            $grouplist = groups_get_user_groups($id, $USER->id);
            foreach($grouplist[0] as $group) {
                $groups[$group] = groups_get_group($group);
            }
            break;
    }

    // Build user lists
    $userlist = array();
    foreach($courseusers as $user) {
        $nonmembership = true;
        foreach($groups as $groupid => $group) {
            if(groups_is_member($groupid, $user->id)) {
                $nonmembership = false;
                if(empty($userlist[$groupid])) {
                    $userlist[$groupid] = array('' => $group->name);
                }
                $userlist[$groupid][$user->id] = fullname($user);
                break;
            }
        }
        if($nonmembership && ($groupmode != SEPARATEGROUPS)) {
            $userlist[0][$user->id] = fullname($user);
        }
    }

    $mform = new quickmail_email_form($userlist);        
    if($mform->is_cancelled()) {
        // Form was cancelled; redirect to course
        redirect("$CFG->wwwroot/course/view.php?id=$course->id");        
    } elseif ($fromform = $mform->get_data()) {

        // Form was submitted and validated
        $fromform->subject = clean_param(strip_tags($fromform->subject, '<lang><span>'), PARAM_RAW);
        $fromform->message = clean_param($fromform->message, PARAM_CLEANHTML);
        $fromform->plaintxt = format_text_email($fromform->message, FORMAT_HTML);
        
        // If we're doing plaintext then we don't want to send along an HTML formatted message
        $fromform->html = ($fromform->format == FORMAT_HTML) ? format_text($fromform->message, FORMAT_HTML) : '';

        // $fromform->mailto will have arrays of arrays; we need to merge these down
        $temp = array();
        foreach($fromform->mailto as $group) {
            $temp = array_merge($temp, $group);
        }
        $fromform->mailto = $temp;
        
        // Get the attachment
        $attachment = $mform->save_temp_file('attachment');
        if($attachment) {
            // email_to_user() supplies the dataroot, so we remove it
            $attachment = str_replace($CFG->dataroot,'',$attachment);
            $attachname = $mform->get_new_filename('attachment');
        } else {
            $attachment = null;
            $attachname = '';
        }

        // Store the successful emails
        $mailedto = array();
        
        foreach($fromform->mailto as $userid) {
            if(empty($userid)) {
                continue;
            }
            set_time_limit(300);
            $mailresult = email_to_user($courseusers[$userid], $USER, $fromform->subject, $fromform->plaintxt, $fromform->html, $attachment, $attachname);
            if(!$mailresult) {
                $fromform->error = get_string('emailfailerror', 'block_quickmail');
                $fromform->usersfail['emailfail'][] = $courseusers[$userid]->lastname . ', '. $courseusers[$userid]->firstname;
            } else {
                $mailedto[] = $userid;
            }
        }
        
        // if it exists, delete the attached file
        if(!empty($attachment)) {
            if(!is_writable($CFG->dataroot . $attachment)) {
                print_error("No write access to ".$CFG->dataroot.$attachment);
            } else {
                if(!unlink($CFG->dataroot . $attachment)) {
                    print_error("Failed to delete ".$CFG->dataroot.$attachment);
                }
            }
        }
        
        // log email to {block_quickmail_log} table
        $log = new stdClass;
        $log->courseid   = $course->id;
        $log->userid     = $USER->id;
        $log->mailto     = implode(',', $mailedto);
        $log->subject    = $fromform->subject;
        $log->message    = $fromform->message;
        $log->attachment = $attachname;
        $log->format     = $fromform->format;
        $log->timesent   = time();
        if (!$DB->insert_record('block_quickmail_log', $log)) {
            print_error('Email not logged.');
        }
        
        if(!isset($form->error)) {  // if no emailing errors, we are done
            // inform of success and continue
            redirect("$CFG->wwwroot/course/view.php?id=$course->id", get_string('successfulemail', 'block_quickmail'));
        }

        
    } else {
        // Data didn't validate OR first load of form
        $data = new stdClass;
        $data->format = $quickmail->config->defaultformat;
        if($action == 'view') {
            // viewing old email
            $emailid = required_param('emailid', PARAM_INT);
            $data = $DB->get_record('block_quickmail_log', array('id' => $emailid));
            
            // $data->mailto isn't very useful because it needs to be broken down by group
            $data->mailto = explode(',', $data->mailto);
            foreach($userlist as $groupid => $list) {
                $data->{"mailto[$groupid]"} = array_intersect($data->mailto,array_keys($list));
            }            
        }
        $data->id = $id;
        $data->instanceid = $instanceid;
        $data->maxbytes = $course->maxbytes;

        $mform->set_data($data);
    }
    
    // set up some strings
    $strquickmail   = get_string('blockname', 'block_quickmail');

/// Header setup
    $navigation = ($course->category) ? "<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$course->shortname</a> ->" : '';
    print_header($course->fullname.': '.$strquickmail, $course->fullname, "$navigation $strquickmail", '', '', true);

    // print the email form START
    echo $OUTPUT->heading($strquickmail);

    // error printing
    if (isset($form->error)) {
        notify($form->error);
        if (isset($form->usersfail)) {
            $errorstring = '';

            if (isset($form->usersfail['emailfail'])) {
                $errorstring .= get_string('emailfail', 'block_quickmail').'<br />';
                foreach($form->usersfail['emailfail'] as $user) {
                    $errorstring .= $user.'<br />';
                }               
            }

            notice($errorstring, "$CFG->wwwroot/course/view.php?id=$course->id", $course);
        }
    }

    $currenttab = 'compose';
    include($CFG->dirroot.'/blocks/quickmail/tabs.php');
    $mform->display();
    echo $OUTPUT->footer();
?>
