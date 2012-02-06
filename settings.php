<?php

defined('MOODLE_INTERNAL') || die;

if($ADMIN->fulltree) {
    $select = array(0 => get_string('no'), 1 => get_string('yes'));
    $settings->add(new admin_setting_configselect('block_quickmail_allowstudents',
        get_string('allowstudents', 'block_quickmail'), get_string('allowstudents',
        'block_quickmail'), 0, $select));

    $roles = $DB->get_records_menu('role', null, 'sortorder ASC', 'id, name');
    $defaults = array_map(function ($sn) {
        global $DB;
        return $DB->get_field('role', 'id', array('shortname' => $sn));
    }, array('editingteacher', 'teacher', 'student'));
    $settings->add(new admin_setting_configmultiselect('block_quickmail_roleselection',
        get_string('select_roles', 'block_quickmail'), get_string('select_roles',
        'block_quickmail'), $defaults, $roles));

    // Option for prefixing the message subject with the course shortname
    $settings->add(new admin_setting_configcheckbox('block_quickmail_courseinsubject', get_string('courseinsubject', 'block_quickmail'),
                                        get_string('courseinsubjectdesc', 'block_quickmail'), 0));

    // Option for adding breadcrumb links to the top of the message body
    $settings->add(new admin_setting_configcheckbox('block_quickmail_breadcrumbsinbody', get_string('breadcrumbsinbody', 'block_quickmail'),
                                    get_string('breadcrumbsinbodydesc', 'block_quickmail'), 0));
}
