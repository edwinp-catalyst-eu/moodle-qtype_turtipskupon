<?php

function xmldb_qtype_turtipskupon_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2015081100) {

        $table = new xmldb_table('question_turtipskupon');
        $field = new xmldb_field('questionimage', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'question');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2015081100, 'qtype', 'turtipskupon');
    }

    if ($oldversion < 2015082700) {

        $table = new xmldb_table('question_turtipskupon');

        $field = new xmldb_field('questionimage', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('questionsound', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2015082700, 'qtype', 'turtipskupon');
    }

    return true;
}
