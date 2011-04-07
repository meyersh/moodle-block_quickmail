<?php
    defined('MOODLE_INTERNAL') || die;
    
    if($ADMIN->fulltree) {
        $settings->add(new admin_setting_configcheckbox('quickmail_deletehistory', get_string('delete_history', 'block_quickmail'), 
                           get_string('configdelete_history', 'block_quickmail'), 1));
    }
?>