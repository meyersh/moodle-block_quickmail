<?php

$string['pluginname'] = 'Quickmail';
$string['quickmail:cansend'] = "��������� ������������� ���������� email ����� Quickmail";
$string['quickmail:canconfig'] = "��������� ������������� ����������� Quickmail.";
$string['quickmail:canimpersonate'] = "��������� ������������� ������ � ����� � �������.";
$string['quickmail:allowalternate'] = "��������� ������������� ��������� �������������� email � �����.";
$string['alternate'] = '�������������� Email';
$string['composenew'] = '��������� Email';
$string['email'] = 'Email';
$string['drafts'] = '����������� ������';
$string['history'] = '���������� �������';
$string['log'] = $string['history'];
$string['from'] = '��';
$string['selected'] = '�������� �����������';
$string['add_button'] = '��������';
$string['remove_button'] = '�������';
$string['add_all'] = '�������� ���';
$string['remove_all'] = '������� ���';
$string['role_filter'] = '������ ����';
$string['no_filter'] = '��� ��������';
$string['potential_users'] = '������������� ����������';
$string['potential_sections'] = '������������� �������';
$string['no_section'] = '��� � �������';
$string['all_sections'] = '��� �������';
$string['attachment'] = '��������(�)';
$string['subject'] = '����';
$string['message'] = '���������';
$string['send_email'] = '��������� Email';
$string['save_draft'] = '��������� ������';
$string['actions'] = '��������';
$string['signature'] = '�������';
$string['delete_confirm'] = '�� ������������� ������ ������� ��������� �� ���������� ��������: {$a}';
$string['title'] = '���������';
$string['sig'] ='�������';
$string['default_flag'] = '��-���������';
$string['config'] = '���������';
$string['receipt'] = '��������� �����';

$string['no_alternates'] = '��� {$a->fullname} �������������� emails �� �������. ��������� ������������.';

$string['select_users'] = '�������� ������������� ...';
$string['select_groups'] = '�������� ������ ...';

// Config form strings
$string['allowstudents'] = '��������� ��������� ������������ Quickmail';
$string['select_roles'] = '������ ��� ����';
$string['reset'] = '������������ ��������� ������� ��-���������';

$string['no_type'] = '{$a} �� ���������� ��� ���������. ����������, ����������� ���������� ���������.';
$string['no_email'] = '��� email {$a->firstname} {$a->lastname}.';
$string['no_log'] = '� ��� ��� ������� ������������ ���������.';
$string['no_drafts'] = '� ��� ��� �������� email.';
$string['no_subject'] = '������� ����';
$string['no_course'] = '������������ ���� ��� {$a}';
$string['no_permission'] = '� ��� ���� ��� �������� emails � Quickmail.';
$string['no_users'] = '��� �������������, ������� �� ������ �������� email.';
$string['no_selected'] = '�� ������ ������� ������������� ��� ������� ���������.';
$string['not_valid'] = '���������������� ����� email ��� ��������� �����: {$a}';
$string['not_valid_user'] = '�� �� ������ ����������� ������ ������� email.';
$string['not_valid_action'] = '�� ������ ������� ��������: {$a}';
$string['not_valid_typeid'] = '�� ������ ������� ���������� email ��� {$a}';
$string['delete_failed'] = '������ �������� email';
$string['required'] = '����������, ��������� ����������� ����.';
$string['prepend_class'] = '�������� �������� �����';
$string['prepend_class_desc'] = '�������� �������� �������� ����� � ���� ����� email.';
$string['courselayout'] = '����� �����';
$string['courselayout_desc'] = '����������� ����� ����� (Use _Course_ page layout) ��� ����������� ������� Quickmail. ������������� ����� ��������� �������� ������������ ����� Moodle ������������� ������.';

$string['are_you_sure'] = '�� ������������� ������ �������  {$a->title}? ��� �������� ������ ��������.';

// Alternate Email strings
$string['alternate_new'] = '�������� �������������� �����';
$string['sure'] = '�� ������������� ������ �������  {$a->address}? ��� �������� ������ ��������.';
$string['valid'] = '��������� �������';
$string['approved'] = '��������';
$string['waiting'] = '��������';
$string['entry_activated'] = '�������������� email {$a->address} �� ����� ���� ����������� ��� {$a->course}.';
$string['entry_key_not_valid'] = '��������� ������ ������ ��������������� {$a->address}. ���������� ��������� ������.';
$string['entry_saved'] = '�������������� ����� {$a->address} ��������.';
$string['entry_success'] = '��� ��������� ���� ������� �� {$a->address}, ����� �����������, ��� ����� email �������������  ����������. 
���������� �� ��������� ������ ���������� � ��� ����������.';
$string['entry_failure'] = '��������� �� ����� ���� ���������� �� {$a->address}. ����������, �������� {$a->address} ���������, ��� ���������, ��� ���� ����� ������������� ����������. ����� ��������� �������.';
$string['alternate_from'] = 'Moodle: Quickmail';
$string['alternate_subject'] = '�������� ��������������� email';
$string['alternate_body'] = '
<p>
{$a->fullname} ��� �������� {$a->address} ��� �������������� ����� ���  {$a->course}.
</p>

<p>
��� ������ ���� ���������� � ����� ���������, ��� ���� ����� ����������, � ��������
����� ������ ����� ��������������� ����� � Moodle.
</p>

<p>
���� �� ������  ��������� ������� ��������, ����������, ��������� �� ���������� ������ (��� ���������� � �������� ������ ������ ��������): {$a->url}.
</p>

<p>
���� ��� ������ �� ����� �������� ��������� � ���, �� ������ �������������� ��� ���������.
</p>

�������.
';
