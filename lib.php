<?php

function quickmail_format_time($time) {
    return date("l, d F Y, h:i A", $time);
}

function quickmail_cleanup($table, $itemid) {
    global $DB;

    // Clean up the files associated with this email 
    // Fortunately, they are only db references, but
    // they shouldn't be there, nonetheless.
    return ($DB->delete_records('files', array('component' => $table, 
                                              'itemid' => $itemid)) and 
            $DB->delete_records($table, array('id' => $itemid)));
}

function quickmail_history_cleanup($itemid) {
    return quickmail_cleanup('block_quickmail_log', $itemid);
}

function quickmail_draft_cleanup($itemid) {
    return quickmail_cleanup('block_quickmail_drafts', $itemid);
}

function quickmail_process_attachments($context, $email, $table, $id) {
    global $CFG, $USER;

    $base_path = "temp/block_quickmail/{$USER->id}";
    $moodle_base = "$CFG->dataroot/$base_path";
    if(!file_exists($moodle_base)) {
        mkdir($moodle_base, 0777, true);
    }

    $zipname = $zip = $actual_zip = '';
    if(!empty($email->attachment)) {
        $zipname = "attachment.zip";
        $zip = "$base_path/$zipname";
        $actual_zip = "$moodle_base/$zipname";

        $packer = get_file_packer();
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'block_quickmail_'.$table, 'attachment', $id, 'id');
        $stored_files = array();
        foreach($files as $file) {
            if($file->is_directory() and $file->get_filename() == '.') 
                continue;

            $stored_files[$file->get_filepath().$file->get_filename()] = $file;
        }

        $packer->archive_to_pathname($stored_files, $actual_zip);
    }

    return array($zipname, $zip, $actual_zip);
}

function quickmail_attachment_names($draft) {
    global $USER;

    $usercontext = get_context_instance(CONTEXT_USER, $USER->id);

    $fs = get_file_storage();
    $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draft, 'id');
    $only_files = array_filter($files, function($file) { 
        return !$file->is_directory() and $file->get_filename() != '.'; 
    });

    return implode(',', array_map(function($file) { return $file->get_filename(); }, $only_files));
}

function quickmail_filter_roles($user_roles, $master_roles) {
    return array_uintersect($master_roles, $user_roles, function($a, $b) {
        return strcmp($a->shortname, $b->shortname);
    });
}

function quickmail_load_config($courseid) {
    global $DB;

    $config = $DB->get_records_menu('block_quickmail_config', 
                                    array('coursesid' => $courseid), '', 'name,value');

    if(empty($config)) {
        $allowstudents = get_config(null, 'block_quickmail_allowstudents');
        $roleselection = get_config(null, 'block_quickmail_roleselection');
        $config = array('allowstudents' => $allowstudents, 
                        'roleselection' => $roleselection); 
    }

    return $config;
}

function quickmail_default_config($courseid) {
    global $DB;
    $DB->delete_records('block_quickmail_config', array('coursesid' => $courseid));
}

function quickmail_save_config($courseid, $data) {
    global $DB;

    quickmail_default_config($courseid);

    foreach ($data as $name => $value) {
        $config = new stdClass;
        $config->coursesid = $courseid;
        $config->name = $name;
        $config->value = $value;
        $DB->insert_record('block_quickmail_config', $config);
    }
}
