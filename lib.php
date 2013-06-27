<?php

// Written at Louisiana State University

abstract class quickmail {
    public static function _s($key, $a = null) {
        return get_string($key, 'block_quickmail', $a);
    }

    static function format_time($time) {
        return userdate($time, '%A, %d %B %Y, %I:%M %P');
    }

    static function cleanup($table, $contextid, $itemid) {
        global $DB;

        // Clean up the files associated with this email
        // Fortunately, they are only db references, but
        // they shouldn't be there, nonetheless.
        $tablename = explode('_', $table);
        $filearea = end($tablename);

        $fs = get_file_storage();

        $fs->delete_area_files(
            $contextid, 'block_quickmail',
            'attachment_' . $filearea, $itemid
        );

        $fs->delete_area_files(
            $contextid, 'block_quickmail',
            $filearea, $itemid
        );

        return $DB->delete_records($table, array('id' => $itemid));
    }

    static function history_cleanup($contextid, $itemid) {
        return quickmail::cleanup('block_quickmail_log', $contextid, $itemid);
    }

    static function draft_cleanup($contextid, $itemid) {
        return quickmail::cleanup('block_quickmail_drafts', $contextid, $itemid);
    }

    private static function flatten_subdirs($tree, $gen_link, $level=0) {
        $attachments = $spaces = '';
        foreach (range(0, $level) as $space) {
            $spaces .= " - ";
        }
        foreach ($tree['files'] as $filename => $file) {
            $attachments .= $spaces . " " . $gen_link($filename) . "\n<br/>";
        }
        foreach ($tree['subdirs'] as $dirname => $subdir) {
            $attachments .= $spaces . " ". $dirname . "\n<br/>";
            $attachments .= self::flatten_subdirs($subdir, $gen_link, $level + 2);
        }

        return $attachments;
    }

    static function process_attachments($context, $email, $table, $id) {
        $attachments = '';
        $filename = '';

        if (empty($email->attachment)) {
            return $attachments;
        }

        $fs = get_file_storage();

        $tree = $fs->get_area_tree(
            $context->id, 'block_quickmail',
            'attachment_' . $table, $id, 'id'
        );

        $base_url = "/$context->id/block_quickmail/attachment_{$table}/$id";

        /**
         * @param string $filename name of the file for which we are generating a download link
         * @param string $text optional param sets the link text; if not given, filename is used
         * @param bool $plain if itrue, we will output a clean url for plain text email users
         *
         */
        $gen_link = function ($filename, $text = '', $plain=false) use ($base_url) {
            if (empty($text)) {
                $text = $filename;
            }
            $url = new moodle_url('/pluginfile.php', array(
                'forcedownload' => 1,
                'file' => "/$base_url/$filename"
            ));

            //to prevent double encoding of ampersands in urls for our plaintext users,
            //we use the out() method of moodle_url
            //@see http://phpdocs.moodle.org/HEAD/moodlecore/moodle_url.html
            if($plain){
                return $url->out(false);    
            }

            return html_writer::link($url, $text);
        };



        $link = $gen_link("{$email->time}_attachments.zip", self::_s('download_all'));

        //get a plain text version of the link
        //by calling gen_link with @param $plain set to true
        $tlink = $gen_link("{$email->time}_attachments.zip", '', true);

        $attachments .= "\n<br/>-------\n<br/>";
        $attachments .= self::_s('moodle_attachments', $link);
        $attachments .= "\n<br/>".$tlink;
        $attachments .= "\n<br/>-------\n<br/>";
        $attachments .= self::_s('qm_contents') . "\n<br />";

        return $attachments . self::flatten_subdirs($tree, $gen_link);
    }

    static function zip_attachments($context, $table, $id) {
        global $CFG, $USER;

        $base_path = "block_quickmail/{$USER->id}";
        $moodle_base = "$CFG->tempdir/$base_path";

        if (!file_exists($moodle_base)) {
            mkdir($moodle_base, $CFG->directorypermissions, true);
        }

        $zipname = "attachment.zip";
        $actual_zip = "$moodle_base/$zipname";

        $fs = get_file_storage();
        $packer = get_file_packer();

        $files = $fs->get_area_files(
            $context->id,
            'block_quickmail',
            'attachment_' . $table,
            $id,
            'id'
        );

        $stored_files = array();
        foreach ($files as $file) {
            if ($file->is_directory() and $file->get_filename() == '.') {
                continue;
            }

            $stored_files[$file->get_filepath().$file->get_filename()] = $file;
        }

        $packer->archive_to_pathname($stored_files, $actual_zip);

        return $actual_zip;
    }

    static function attachment_names($draft) {
        global $USER;

        $usercontext = get_context_instance(CONTEXT_USER, $USER->id);

        $fs = get_file_storage();
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draft, 'id');

        $only_files = array_filter($files, function($file) {
            return !$file->is_directory() and $file->get_filename() != '.';
        });

        $only_names = function ($file) { return $file->get_filename(); };

        $only_named_files = array_map($only_names, $only_files);

        return implode(',', $only_named_files);
    }

    static function filter_roles($user_roles, $master_roles) {
        return array_uintersect($master_roles, $user_roles, function($a, $b) {
            return strcmp($a->shortname, $b->shortname);
        });
    }

    static function load_config($courseid) {
        global $DB;

        $fields = 'name,value';
        $params = array('coursesid' => $courseid);
        $table = 'block_quickmail_config';

        $config = $DB->get_records_menu($table, $params, '', $fields);

        if (empty($config)) {
            $m = 'moodle';
            $allowstudents = get_config($m, 'block_quickmail_allowstudents');
            $roleselection = get_config($m, 'block_quickmail_roleselection');
            $prepender = get_config($m, 'block_quickmail_prepend_class');
            $receipt = get_config($m, 'block_quickmail_receipt');
            $ferpa = get_config($m, 'block_quickmail_ferpa');

            $config = array(
                'allowstudents' => $allowstudents,
                'roleselection' => $roleselection,
                'prepend_class' => $prepender,
                'receipt' => $receipt,
                'ferpa' => $ferpa
            );
        }

        return $config;
    }

    static function default_config($courseid) {
        global $DB;

        $params = array('coursesid' => $courseid);
        $DB->delete_records('block_quickmail_config', $params);
    }

    static function save_config($courseid, $data) {
        global $DB;

        quickmail::default_config($courseid);

        foreach ($data as $name => $value) {
            $config = new stdClass;
            $config->coursesid = $courseid;
            $config->name = $name;
            $config->value = $value;

            $DB->insert_record('block_quickmail_config', $config);
        }
    }

    function delete_dialog($courseid, $type, $typeid) {
        global $CFG, $DB, $USER, $OUTPUT;

        $email = $DB->get_record('block_quickmail_'.$type, array('id' => $typeid));

        if (empty($email))
            print_error('not_valid_typeid', 'block_quickmail', '', $typeid);

        $params = array('courseid' => $courseid, 'type' => $type);
        $yes_params = $params + array('typeid' => $typeid, 'action' => 'confirm');

        $optionyes = new moodle_url('/blocks/quickmail/emaillog.php', $yes_params);
        $optionno = new moodle_url('/blocks/quickmail/emaillog.php', $params);

        $table = new html_table();
        $table->head = array(get_string('date'), quickmail::_s('subject'));
        $table->data = array(
            new html_table_row(array(
                new html_table_cell(quickmail::format_time($email->time)),
                new html_table_cell($email->subject))
            )
        );

        $msg = quickmail::_s('delete_confirm', html_writer::table($table));

        $html = $OUTPUT->confirm($msg, $optionyes, $optionno);
        return $html;
    }

    static function list_entries($courseid, $type, $page, $perpage, $userid, $count, $can_delete) {
        global $CFG, $DB, $OUTPUT;

        $dbtable = 'block_quickmail_'.$type;

        $table = new html_table();

        $params = array('courseid' => $courseid, 'userid' => $userid);
        $logs = $DB->get_records($dbtable, $params,
            'time DESC', '*', $page * $perpage, $perpage * ($page + 1));

        $table->head= array(get_string('date'), quickmail::_s('subject'),
            quickmail::_s('attachment'), get_string('action'));

        $table->data = array();

        foreach ($logs as $log) {
            $date = quickmail::format_time($log->time);
            $subject = $log->subject;
            $attachments = $log->attachment;

            $params = array(
                'courseid' => $log->courseid,
                'type' => $type,
                'typeid' => $log->id
            );

            $actions = array();

            $open_link = html_writer::link(
                new moodle_url('/blocks/quickmail/email.php', $params),
                $OUTPUT->pix_icon('i/search', 'Open Email')
            );
            $actions[] = $open_link;

            if ($can_delete) {
                $delete_params = $params + array(
                    'userid' => $userid,
                    'action' => 'delete'
                );

                $delete_link = html_writer::link (
                    new moodle_url('/blocks/quickmail/emaillog.php', $delete_params),
                    $OUTPUT->pix_icon("i/cross_red_big", "Delete Email")
                );

                $actions[] = $delete_link;
            }

            $action_links = implode(' ', $actions);

            $table->data[] = array($date, $subject, $attachments, $action_links);
        }

        $paging = $OUTPUT->paging_bar($count, $page, $perpage,
            '/blocks/quickmail/emaillog.php?type='.$type.'&amp;courseid='.$courseid);

        $html = $paging;
        $html .= html_writer::table($table);
        $html .= $paging;
        return $html;
    }

    /**
     * get all users for a given context
     * @param $context a moodle context id
     * @return array of sparse user objects
     */
    public static function get_all_users($context){
        global $DB;
        // List everyone with role in course.
        //
        // Note that users with multiple roles will be squashed into one
        // record.

        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname,
        u.email, u.mailformat, u.suspended, u.maildisplay
        FROM {role_assignments} ra
        JOIN {user} u ON u.id = ra.userid
        JOIN {role} r ON ra.roleid = r.id
        WHERE (ra.contextid = ? ) ";
        $everyone = $DB->get_records_sql($sql, array($context->id));
        
        return $everyone;
    }
    

    /**
     * @TODO this function relies on self::get_all_users, it should not have to
     *
     * returns all users enrolled in a gived coure EXCEPT for those whose 
     * mdl_user_enrolments.status field is 1 (suspended)
     * @param $context  moodle context id
     * @param $courseid the course id
     */
    public static function get_non_suspended_users($context, $courseid){
        global $DB;
        $everyone = self::get_all_users($context);
        $sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.mailformat, u.suspended, u.maildisplay, ue.status  
            FROM {user} as u  
                JOIN {user_enrolments} as ue                 
                    ON u.id = ue.userid 
                JOIN {enrol} as en
                    ON en.id = ue.enrolid                     
                WHERE en.courseid = ?
                    AND ue.status = ?
                ORDER BY u.lastname, u.firstname"; 

        //let's use a recordset in case the enrollment is huge
        $rs_valids = $DB->get_recordset_sql($sql, array($courseid, 0));

        //container for user_enrolments records
        $valids = array();

        /**
         * @TODO use a cleaner mechanism from std lib to do this without iterating over the array
         * for each chunk of the recordset,
         * insert the record into the valids container
         * using the id number as the array key;
         * this matches the format used by self::get_all_users
         */
        foreach($rs_valids as $rsv){
            $valids[$rsv->id] = $rsv;
        }
        //required to close the recordset
        $rs_valids->close();
        
        //get the intersection of self::all_users and this potentially shorter list
        $evryone_not_suspended = array_intersect_key($valids, $everyone);

        return $evryone_not_suspended;
    }
}

function block_quickmail_pluginfile($course, $record, $context, $filearea, $args, $forcedownload) {
    $fs = get_file_storage();
    global $DB;

    require_course_login($course, true, $record);

    list($itemid, $filename) = $args;

    if ($filearea == 'attachment_log') {
        $time = $DB->get_field('block_quickmail_log', 'time', array(
            'id' => $itemid
        ));

        if ("{$time}_attachments.zip" == $filename) {
            $path = quickmail::zip_attachments($context, 'log', $itemid);
            send_temp_file($path, 'attachments.zip');
        }
    }

    $params = array(
        'component' => 'block_quickmail',
        'filearea' => $filearea,
        'itemid' => $itemid,
        'filename' => $filename
    );

    $instanceid = $DB->get_field('files', 'id', $params);

    if (empty($instanceid)) {
        send_file_not_found();
    } else {
        $file = $fs->get_file_by_id($instanceid);
        send_stored_file($file);
    }
}
