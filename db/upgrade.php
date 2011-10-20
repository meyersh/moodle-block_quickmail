<?php
function xmldb_block_quickmail_upgrade($oldversion, $block = NULL) {
    global $DB;

    $dbman = $DB->get_manager();

    // 1.9 to 2.0 upgrade
    if ($oldversion < 2011021812) {
        // Changing type of field attachment on table block_quickmail_log to text
        $table = new xmldb_table('block_quickmail_log');
        $field = new xmldb_field('attachment', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, 'message');

        // Launch change of type for field attachment
        $dbman->change_field_type($table, $field);

        // Rename field timesent on table block_quickmail_log to time
        $table = new xmldb_table('block_quickmail_log');
        $field = new xmldb_field('timesent', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'format');

        // Conditionally launch rename field timesent
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'time');
        }

        // Define table block_quickmail_signatures to be created
        $table = new xmldb_table('block_quickmail_signatures');

        // Adding fields to table block_quickmail_signatures
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('title', XMLDB_TYPE_CHAR, '125', null, null, null, null);
        $table->add_field('signature', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('default_flag', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');

        // Adding keys to table block_quickmail_signatures
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_quickmail_signatures
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_quickmail_drafts to be created
        $table = new xmldb_table('block_quickmail_drafts');

        // Adding fields to table block_quickmail_drafts
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('mailto', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('subject', XMLDB_TYPE_TEXT, 'small', null, null, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('attachment', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null);
        $table->add_field('format', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');
        $table->add_field('time', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null);

        // Adding keys to table block_quickmail_drafts
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_quickmail_drafts
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_quickmail_config to be created
        $table = new xmldb_table('block_quickmail_config');

        // Adding fields to table block_quickmail_config
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('coursesid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '25', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_CHAR, '125', null, null, null, null);

        // Adding keys to table block_quickmail_config
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_quickmail_config
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // quickmail savepoint reached
        upgrade_block_savepoint(true, 2011021812, 'quickmail');
    }

    return true;
}