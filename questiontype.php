<?php

defined('MOODLE_INTERNAL') || die();

class qtype_turtipskupon extends question_type {

    public function find_standard_scripts() {
        global $PAGE;

        $PAGE->requires->jquery();

        parent::find_standard_scripts();
    }

    public function get_question_options($question) {
        global $DB;

        $question->options = $DB->get_record('question_turtipskupon',
                array('question' => $question->id), '*', MUST_EXIST);

        parent::get_question_options($question);
    }

    public function save_question_options($question) {
        global $DB;

        $context = $question->context;
        $result = new stdClass();

        $oldanswers = $DB->get_records('question_answers',
                array('question' => $question->id), 'id ASC');

        // Following hack to check at least two answers exist
        if (count($question->answer) < 2) { // Check there are at lest 2 answers for multiple choice.
            $result->notice = get_string('notenoughanswers', 'qtype_turtipskupon', '2');
            return $result;
        }

        // Insert all the new answers.
        $totalfraction = 0;
        $maxfraction = -1;
        foreach ($question->answer as $key => $answerdata) {
            if (trim($answerdata['text']) == '') {
                continue;
            }

            // Update an existing answer if possible.
            $answer = array_shift($oldanswers);

            if (!$answer) {
                $answer = new stdClass();
                $answer->question = $question->id;
                $answer->answer = '';
                $answer->feedback = '';
                $answer->id = $DB->insert_record('question_answers', $answer);
            }

            $answer->answer = $this->import_or_save_files($answerdata,
                    $context, 'question', 'answer', $answer->id);
            $answer->answerformat = $answerdata['format'];

            // Save answer 'answersound'
            file_save_draft_area_files($question->answersound[$key], $context->id,
                    'question', 'answersound', $answer->id, $this->fileoptions);

            $answer->fraction = $question->fraction[$key];
            $answer->feedback = $this->import_or_save_files($question->feedback[$key],
                    $context, 'question', 'answerfeedback', $answer->id);
            $answer->feedbackformat = $question->feedback[$key]['format'];

            // Save answer 'feedbacksound'
            file_save_draft_area_files($question->feedbacksound[$key], $context->id,
                    'question', 'feedbacksound', $answer->id, $this->fileoptions);

            $DB->update_record('question_answers', $answer);

            if ($question->fraction[$key] > 0) {
                $totalfraction += $question->fraction[$key];
            }
            if ($question->fraction[$key] > $maxfraction) {
                $maxfraction = $question->fraction[$key];
            }
        }

        // Delete any left over old answer records.
        $fs = get_file_storage();
        foreach ($oldanswers as $oldanswer) {
            $fs->delete_area_files($context->id, 'question', 'answerfeedback', $oldanswer->id);
            $DB->delete_records('question_answers', array('id' => $oldanswer->id));
        }

        $options = $DB->get_record('question_turtipskupon', array('question' => $question->id));
        if (!$options) {
            $options = new stdClass();
            $options->question = $question->id;
            $options->correctfeedback = '';
            $options->partiallycorrectfeedback = '';
            $options->incorrectfeedback = '';
            $options->id = $DB->insert_record('question_turtipskupon', $options);
        }

        $options->single = $question->single;
        $options->autoplay = $question->autoplay;
        $options->qdifficulty = $question->qdifficulty;
        $options->shuffleanswers = $question->shuffleanswers;

        if (isset($question->layout)) {
            $options->layout = $question->layout;
        }

        $options = $this->save_combined_feedback_helper($options, $question, $context, true);
        $DB->update_record('question_turtipskupon', $options);

        $this->save_hints($question, true);

        // Save question 'questionimage'
        file_save_draft_area_files($question->questionimage, $context->id,
                'question', 'questionimage', $question->id, $this->fileoptions);

        // Save question 'questionsound'
        file_save_draft_area_files($question->questionsound, $context->id,
                'question', 'questionsound', $question->id, $this->fileoptions);

        // Perform sanity checks on fractional grades.
        if ($options->single) {
            if ($maxfraction != 1) {
                // $result->noticeyesno = get_string('fractionsnomax', 'qtype_turtipskupon', $maxfraction * 100);
                //$result->notice = get_string('fractionsnomax', 'qtype_turtipskupon', $maxfraction * 100);
                //return $result;
            }
        } else {
            $totalfraction = round($totalfraction, 2);
            if ($totalfraction != 1) {
                // $result->noticeyesno = get_string('fractionsaddwrong', 'qtype_turtipskupon', $totalfraction * 100);
                //$result->notice = get_string('fractionsaddwrong', 'qtype_turtipskupon', $totalfraction * 100);
                //return $result;
            }
        }
    }

    public function save_question($question, $form) {

        return parent::save_question($question, $form);
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {

        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {

        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
    }

    protected function make_question_instance($questiondata) {

        question_bank::load_question_definition_classes($this->name());

        if ($questiondata->options->single) {
            $class = 'qtype_turtipskupon_single_question';
        } else {
            $class = 'qtype_turtipskupon_multi_question';
        }

        return new $class();
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {

        parent::initialise_question_instance($question, $questiondata);

        $question->shuffleanswers = $questiondata->options->shuffleanswers;
        $question->qdifficulty = $questiondata->options->qdifficulty;
        $question->autoplay = $questiondata->options->autoplay;

        $questiondata->options->correctfeedbackformat = 1;
        $questiondata->options->partiallycorrectfeedbackformat = 1;
        $questiondata->options->incorrectfeedbackformat = 1;
        $questiondata->options->shownumcorrect = 1;

        if (!empty($questiondata->options->layout)) {
            $question->layout = $questiondata->options->layout;
        } else {
            $question->layout = qtype_turtipskupon_single_question::LAYOUT_VERTICAL;
        }

        $this->initialise_combined_feedback($question, $questiondata, true);
        $this->initialise_question_answers($question, $questiondata, false);
    }

    public function delete_question($questionid, $contextid) {
        global $DB;

        $DB->delete_records('question_turtipskupon', array('question' => $questionid));
        parent::delete_question($questionid, $contextid);
    }

    public function get_random_guess_score($questiondata) {
        // TODO.
        return 0;
    }

    public function get_possible_responses($questiondata) {
        // TODO.
        return array();
    }
}
