<?php

require_once($CFG->libdir . '/formslib.php');

class email_form extends moodleform {
    private function reduce_users($in, $user) {
        return $in . '<option value="'.$this->option_value($user).'">'.
               $this->option_display($user).'</option>';
    }

    private function option_display($user) {
        $users_to_groups = $this->_customdata['users_to_groups'];

        $groups = (empty($users_to_groups[$user->id])) ? 
                  get_string('no_section', 'block_quickmail') :
                  implode(',', array_map(function($group) {
                    return $group->name;
                  },
                  $users_to_groups[$user->id]));

        return sprintf("%s (%s)", fullname($user), $groups);
    }

    private function option_value($user) {
        $users_to_groups = $this->_customdata['users_to_groups'];
        $users_to_roles = $this->_customdata['users_to_roles'];

        $roles = implode(',', array_map(function($role) {
            return $role->shortname;
        }, $users_to_roles[$user->id]));

        // everyone defaults to none
        $roles .= ',none';

        $groups = (empty($users_to_groups[$user->id])) ? 0 : implode(',', 
            array_map(function($group) {
            return $group->id;
        }, $users_to_groups[$user->id]));

        return sprintf("%s %s %s", $user->id, $groups, $roles); 
    }

    public function definition() {
        global $CFG, $USER, $COURSE, $OUTPUT; 

        $mform =& $this->_form;

        $mform->addElement('hidden', 'mailto', '');
        $mform->addElement('hidden', 'userid', $USER->id);
        $mform->addElement('hidden', 'courseid', $COURSE->id);
        $mform->addElement('hidden', 'type', '');
        $mform->addElement('hidden', 'typeid', 0);

        $email_link = 'emaillog.php?courseid='.$COURSE->id.'&amp;type=';
        $draft_link = '<center style="margin-left: -13%"><a href="'.$email_link.'drafts">'.
                        get_string('drafts', 'block_quickmail').'</a>';
        $history_link = '<a href="'.$email_link.'log">'.
                        get_string('history', 'block_quickmail').'</a></center>';
        $drafts =& $mform->createElement('static', 'draft_link', '', $draft_link);
        $history =& $mform->createElement('static', 'history_link', '', $history_link); 

        $mform->addGroup(array($drafts, $history), 'links', '&nbsp;', array(' | '), false);

        $mform->addElement('static', 'from', get_string('from', 'block_quickmail'), $USER->email);
        $mform->addElement('static', 'selectors', '', '
            <table>
                <tr>
                    <td>
                        <strong class="required">'.get_string('selected', 'block_quickmail').'
                            <img class="req" title="Required field" alt="Required field" src="'.$OUTPUT->pix_url('req').'"/>
                        </strong>
                    </td>
                    <td align="right" colspan="2">
                        <strong>'.get_string('role_filter', 'block_quickmail').'</strong>
                    </td>
                </tr>
                <tr>
                    <td width="300">
                        <select id="mail_users" multiple size="30">
                            '.array_reduce($this->_customdata['selected'], array($this, 'reduce_users'), '').'
                        </select>
                    </td>
                    <td width="100" align="center">
                        <p>
                            <input type="button" id="add_button" value="'.get_string('add_button', 'block_quickmail').'"/>
                        </p>
                        <p>
                            <input type="button" id="remove_button" value="'.get_string('remove_button', 'block_quickmail').'"/>
                        </p>
                        <p>
                            <input type="button" id="add_all" value="'.get_string('add_all', 'block_quickmail').'"/>
                        </p>
                        <p>
                            <input type="button" id="remove_all" value="'.get_string('remove_all', 'block_quickmail').'"/>
                        </p>
                    </td>
                    <td width="300" align="right">
                        <div>
                            <select id="roles">
                                <option value="none" selected>'.get_string('no_filter', 'block_quickmail').'</option>
                                '.array_reduce($this->_customdata['roles'], function($in, $role) {
                                    return $in . '<option value="'.$role->shortname.'">'.$role->name.'</option>';
                                 }, '').'
                            </select>
                        </div>
                        <div class="object_labels"><strong>'.get_string('potential_sections', 'block_quickmail').'</strong></div>
                        <div>
                            <select id="groups" multiple size="5">
                                '.array_reduce($this->_customdata['groups'], function($in, $group) {
                                    return $in . '<option value="'.$group->id.'">'.$group->name.'</option>';
                                 }, '').'
                                 <option value="0">'.get_string('no_section', 'block_quickmail').'</option>
                            </select>
                        </div>
                        <div class="object_labels"><strong>'.get_string('potential_users', 'block_quickmail').'</strong></div>
                        <div>
                            <select id="from_users" multiple size="20">
                                '.array_reduce($this->_customdata['users'], array($this, 'reduce_users'), '').'
                            </select>
                        </div>
                    </td>
                </tr>
            </table>
        ');

        $mform->addElement('filemanager', 'attachments', get_string('attachment', 'block_quickmail'));

        $mform->addElement('text', 'subject', get_string('subject', 'block_quickmail'));
        $mform->setType('subject', PARAM_TEXT);        
        $mform->addRule('subject', null, 'required');

        $mform->addElement('editor', 'message', get_string('message', 'block_quickmail'));

        $options = $this->_customdata['sigs'] + array(-1 => 'No '. get_string('sig', 'block_quickmail'));
        $mform->addElement('select', 'sigid', get_string('signature', 'block_quickmail'), $options);

        // TODO: add signature, receipts
        $buttons = array();
        $buttons[] =& $mform->createElement('submit', 'send', get_string('send_email', 'block_quickmail'));
        $buttons[] =& $mform->createElement('submit', 'draft', get_string('save_draft', 'block_quickmail'));
        $buttons[] =& $mform->createElement('submit', 'cancel', get_string('cancel'));

        $mform->addGroup($buttons, 'buttons', get_string('actions', 'block_quickmail'), array(' '), false);
    } 
}
