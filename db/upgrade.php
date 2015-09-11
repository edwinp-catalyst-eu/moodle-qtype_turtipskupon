<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Multiple choice question type upgrade code.
 *
 * @package    qtype
 * @subpackage turtipskupon
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade code for the multiple choice question type.
 * @param int $oldversion the version we are upgrading from.
 */
function xmldb_qtype_turtipskupon_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2009021801) {

        // Define field correctfeedbackformat to be added to question_turtipskupon
        $table = new xmldb_table('question_turtipskupon');
        $field = new xmldb_field('correctfeedbackformat', XMLDB_TYPE_INTEGER, '2', null,
                XMLDB_NOTNULL, null, '0', 'correctfeedback');

        // Conditionally launch add field correctfeedbackformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field partiallycorrectfeedbackformat to be added to question_turtipskupon
        $field = new xmldb_field('partiallycorrectfeedbackformat', XMLDB_TYPE_INTEGER, '2', null,
                XMLDB_NOTNULL, null, '0', 'partiallycorrectfeedback');

        // Conditionally launch add field partiallycorrectfeedbackformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field incorrectfeedbackformat to be added to question_turtipskupon
        $field = new xmldb_field('incorrectfeedbackformat', XMLDB_TYPE_INTEGER, '2', null,
                XMLDB_NOTNULL, null, '0', 'incorrectfeedback');

        // Conditionally launch add field incorrectfeedbackformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // In the past, the correctfeedback, partiallycorrectfeedback,
        // incorrectfeedback columns were assumed to contain content of the same
        // form as questiontextformat. If we are using the HTML editor, then
        // convert FORMAT_MOODLE content to FORMAT_HTML.
        $sql = "SELECT qtm.*, q.oldquestiontextformat
                  FROM {question_turtipskupon} qtm
                  JOIN {question} q ON qtm.question = q.id";
        $rs = $DB->get_recordset_sql($sql);

        foreach ($rs as $record) {
            if ($CFG->texteditors !== 'textarea' &&
                    $record->oldquestiontextformat == FORMAT_MOODLE) {
                $record->correctfeedback = text_to_html(
                        $record->correctfeedback, false, false, true);
                $record->correctfeedbackformat = FORMAT_HTML;
                $record->partiallycorrectfeedback = text_to_html(
                        $record->partiallycorrectfeedback, false, false, true);
                $record->partiallycorrectfeedbackformat = FORMAT_HTML;
                $record->incorrectfeedback = text_to_html(
                        $record->incorrectfeedback, false, false, true);
                $record->incorrectfeedbackformat = FORMAT_HTML;
            } else {
                $record->correctfeedbackformat = $record->oldquestiontextformat;
                $record->partiallycorrectfeedbackformat = $record->oldquestiontextformat;
                $record->incorrectfeedbackformat = $record->oldquestiontextformat;
            }
            $DB->update_record('question_turtipskupon', $record);
        }

        $rs->close();

        // turtipskupon savepoint reached
        upgrade_plugin_savepoint(true, 2009021801, 'qtype', 'turtipskupon');
    }

    // Add new shownumcorrect field. If this is true, then when the user gets a
    // multiple-response question partially correct, tell them how many choices
    // they got correct alongside the feedback.
    if ($oldversion < 2011011200) {

        // Define field shownumcorrect to be added to question_turtipskupon
        $table = new xmldb_table('question_turtipskupon');
        $field = new xmldb_field('shownumcorrect', XMLDB_TYPE_INTEGER, '2', null,
                XMLDB_NOTNULL, null, '0', 'qdifficulty');

        // Launch add field shownumcorrect
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // turtipskupon savepoint reached
        upgrade_plugin_savepoint(true, 2011011200, 'qtype', 'turtipskupon');
    }

    // Moodle v2.1.0 release upgrade line
    // Put any upgrade step following this

    // Moodle v2.2.0 release upgrade line
    // Put any upgrade step following this

    // Migrate assets
    if ($oldversion < 2013010101) {

        $audiofolder = $CFG->olddataroot . '/' . $CFG->tursound . '/audio/';
        $fs = get_file_storage();
        $file_record = array(
            'contextid' => 1,
            'component' => 'question',
            'filepath' => '/'
        );

        // Question sounds first, then answer sounds
        // Just do the question sounds for this question type
        $sql = "SELECT q.id, qtm.questionsound
                  FROM {question} q
                  JOIN {question_turtipskupon} qtm ON qtm.question = q.id
                 WHERE q.qtype = ?";
        $params = array('turtipskupon');
        $questions = $DB->get_records_sql($sql, $params);

        foreach ($questions as $question) {

            $file_record['itemid'] = $question->id;

            $filename = substr($question->questionsound, 6);
            if (file_exists($audiofolder . $filename)) {
                $file_record['filearea'] = 'questionsound';
                $file_record['filename'] = $filename;
                $file_record['timecreated'] = time();
                $file_record['timemodified'] = time();
                $fs->create_file_from_pathname($file_record, $audiofolder . $filename);
            }
        }

        // Question sounds done, answer sounds next.
        // Do answer sounds for question types 'turprove' and 'turtipskupon' also
        // as the question_answers.answersound and question_answers.feedbacksound
        // fields are dropped now also
        $sql = "SELECT qa.id, qa.answersound, qa.feedbacksound
                  FROM {question_answers} qa
                  JOIN {question} q ON q.id = qa.question
                 WHERE q.qtype = ?
                    OR q.qtype = ?
                    OR q.qtype = ?";
        $params = array('turtipskupon', 'turprove', 'turtipskupon');
        $answers = $DB->get_records_sql($sql, $params);

        foreach ($answers as $answer) {

            $file_record['itemid'] = $answer->id;

            $filename = substr($answer->answersound, 6);
            if (file_exists($audiofolder . $filename)) {
                $file_record['filearea'] = 'answersound';
                $file_record['filename'] = $filename;
                $file_record['timecreated'] = time();
                $file_record['timemodified'] = time();
                $fs->create_file_from_pathname($file_record, $audiofolder . $filename);
            }

            $filename = substr($answer->feedbacksound, 6);
            if (file_exists($audiofolder . $filename)) {
                $file_record['filearea'] = 'feedbacksound';
                $file_record['filename'] = $filename;
                $file_record['timecreated'] = time();
                $file_record['timemodified'] = time();
                $fs->create_file_from_pathname($file_record, $audiofolder . $filename);
            }
        }

        // Define table question_answers.
        $table = new xmldb_table('question_answers');

        // Define field answersound to be dropped from question_answers.
        $field = new xmldb_field('answersound');

        // Conditionally launch drop field answersound.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field feedbacksound to be dropped from question_answers.
        $field = new xmldb_field('feedbacksound');

        // Conditionally launch drop field feedbacksound.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // turtipskupon savepoint reached
        upgrade_plugin_savepoint(true, 2013010101, 'qtype', 'turtipskupon');
    }

    return true;
}
