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
 * @package    qtype
 * @subpackage turtipskupon
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Multichoice question type conversion handler
 */
class moodle1_qtype_turtipskupon_handler extends moodle1_qtype_handler {

    /**
     * @return array
     */
    public function get_question_subpaths() {
        return array(
            'ANSWERS/ANSWER',
            'TURTIPSKUPON',
        );
    }

    /**
     * Appends the turtipskupon specific information to the question
     */
    public function process_question(array $data, array $raw) {

        // convert and write the answers first
        if (isset($data['answers'])) {
            $this->write_answers($data['answers'], $this->pluginname);
        }

        // convert and write the turtipskupon
        if (!isset($data['turtipskupon'])) {
            // This should never happen, but it can do if the 1.9 site contained
            // corrupt data/
            $data['turtipskupon'] = array(array(
                'single'                         => 1,
                'shuffleanswers'                 => 1,
                'correctfeedback'                => '',
                'correctfeedbackformat'          => FORMAT_HTML,
                'partiallycorrectfeedback'       => '',
                'partiallycorrectfeedbackformat' => FORMAT_HTML,
                'incorrectfeedback'              => '',
                'incorrectfeedbackformat'        => FORMAT_HTML,
                'qdifficulty'                    => '0',
            ));
        }
        $this->write_turtipskupon($data['turtipskupon'], $data['oldquestiontextformat']);
    }

    /**
     * Converts the turtipskupon info and writes it into the question.xml
     *
     * @param array $turtipskupons the grouped structure
     * @param int $oldquestiontextformat - {@see moodle1_question_bank_handler::process_question()}
     */
    protected function write_turtipskupon(array $turtipskupons, $oldquestiontextformat) {
        global $CFG;

        // the grouped array is supposed to have just one element - let us use foreach anyway
        // just to be sure we do not loose anything
        foreach ($turtipskupons as $turtipskupon) {
            // append an artificial 'id' attribute (is not included in moodle.xml)
            $turtipskupon['id'] = $this->converter->get_nextid();

            // replay the upgrade step 2009021801
            $turtipskupon['correctfeedbackformat']               = 0;
            $turtipskupon['partiallycorrectfeedbackformat']      = 0;
            $turtipskupon['incorrectfeedbackformat']             = 0;

            if ($CFG->texteditors !== 'textarea' and $oldquestiontextformat == FORMAT_MOODLE) {
                $turtipskupon['correctfeedback']                 = text_to_html($turtipskupon['correctfeedback'], false, false, true);
                $turtipskupon['correctfeedbackformat']           = FORMAT_HTML;
                $turtipskupon['partiallycorrectfeedback']        = text_to_html($turtipskupon['partiallycorrectfeedback'], false, false, true);
                $turtipskupon['partiallycorrectfeedbackformat']  = FORMAT_HTML;
                $turtipskupon['incorrectfeedback']               = text_to_html($turtipskupon['incorrectfeedback'], false, false, true);
                $turtipskupon['incorrectfeedbackformat']         = FORMAT_HTML;
            } else {
                $turtipskupon['correctfeedbackformat']           = $oldquestiontextformat;
                $turtipskupon['partiallycorrectfeedbackformat']  = $oldquestiontextformat;
                $turtipskupon['incorrectfeedbackformat']         = $oldquestiontextformat;
            }

            $this->write_xml('turtipskupon', $turtipskupon, array('/turtipskupon/id'));
        }
    }
}
