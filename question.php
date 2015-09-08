<?php

defined('MOODLE_INTERNAL') || die();

abstract class qtype_turtipskupon_base extends question_graded_automatically {
    const LAYOUT_DROPDOWN = 0;
    const LAYOUT_VERTICAL = 1;
    const LAYOUT_HORIZONTAL = 2;

    public $shuffleanswers;
    public $correctfeedback;
    public $correctfeedbackformat;
    public $partiallycorrectfeedback;
    public $partiallycorrectfeedbackformat;
    public $incorrectfeedback;
    public $incorrectfeedbackformat;

    protected $order = null;

    public function start_attempt(question_attempt_step $step, $variant) {
        $this->order = array_keys($this->answers);
        if ($this->shuffleanswers) {
            shuffle($this->order);
        }
        $step->set_qt_var('_order', implode(',', $this->order));
    }

    public function apply_attempt_state(question_attempt_step $step) {
        $this->order = explode(',', $step->get_qt_var('_order'));
    }

    public function get_order(question_attempt $qa) {
        $this->init_order($qa);
        return $this->order;
    }

    protected function init_order(question_attempt $qa) {
        if (is_null($this->order)) {
            $this->order = explode(',', $qa->get_step(0)->get_qt_var('_order'));
        }
    }

    public abstract function is_choice_selected($response, $value);

    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        if ($component == 'question' && in_array($filearea,
                array('correctfeedback', 'partiallycorrectfeedback', 'incorrectfeedback'))) {
            return $this->check_combined_feedback_file_access($qa, $options, $filearea);

        } else if ($component == 'question' && $filearea == 'answer') {
            $answerid = reset($args); // Itemid is answer id.
            return  in_array($answerid, $this->order);

        } else if ($component == 'question' && $filearea == 'answerfeedback') {
            $answerid = reset($args); // Itemid is answer id.
            $response = $this->get_response($qa);
            $isselected = false;
            foreach ($this->order as $value => $ansid) {
                if ($ansid == $answerid) {
                    $isselected = $this->is_choice_selected($response, $value);
                    break;
                }
            }
            // Param $options->suppresschoicefeedback is a hack specific to the
            // turtipskupon question type. It would be good to refactor to
            // avoid refering to it here.
            return $options->feedback && empty($options->suppresschoicefeedback) &&
                    $isselected;

        } else if ($component == 'question' && $filearea == 'hint') { // Not required ?
            return $this->check_hint_file_access($qa, $options, $args);

        } else if ($component == 'question' && $filearea == 'questionimage') {

            // TODO: check_questionimage_access
            return true;
        } else if ($component == 'question' && $filearea == 'questionsound') {

            // TODO: check_questionsound_access
            return true;
        } else if ($component == 'question' && $filearea == 'answersound') {

            // TODO: check_answersound_access
            return true;
        } else if ($component == 'question' && $filearea == 'feedbacksound') {

            // TODO: check_feedbacksound_access
            return true;
        } else {
            return parent::check_file_access($qa, $options, $component, $filearea,
                    $args, $forcedownload);
        }
    }
}

/**
 * Represents a multiple choice question where only one choice should be selected.
 */
class qtype_turtipskupon_single_question extends qtype_turtipskupon_base {

    public function get_renderer(moodle_page $page) {
        return $page->get_renderer('qtype_turtipskupon', 'single');
    }

    /**
     * Return an array of the question type variables that could be submitted
     * as part of a question of this type, with their types, so they can be
     * properly cleaned.
     * @return array variable name => PARAM_... constant.
     */
    public function get_expected_data() {
        return array('answer' => PARAM_INT);
    }

    public function summarise_response(array $response) {
        if (!array_key_exists('answer', $response) ||
                !array_key_exists($response['answer'], $this->order)) {
            return null;
        }
        $ansid = $this->order[$response['answer']];
        return $this->html_to_text($this->answers[$ansid]->answer,
                $this->answers[$ansid]->answerformat);
    }

    public function get_correct_response() {
        foreach ($this->order as $key => $answerid) {
            if (question_state::graded_state_for_fraction(
                    $this->answers[$answerid]->fraction)->is_correct()) {
                return array('answer' => $key);
            }
        }
        return array();
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        return question_utils::arrays_same_at_key($prevresponse, $newresponse, 'answer');
    }

    public function is_complete_response(array $response) {
        return array_key_exists('answer', $response) && $response['answer'] !== '';
    }

    public function grade_response(array $response) {
        if (array_key_exists('answer', $response) &&
                array_key_exists($response['answer'], $this->order)) {
            $fraction = $this->answers[$this->order[$response['answer']]]->fraction;
        } else {
            $fraction = 0;
        }
        return array($fraction, question_state::graded_state_for_fraction($fraction));
    }

    public function get_validation_error(array $response) {
        if ($this->is_gradable_response($response)) {
            return '';
        }
        return get_string('pleaseselectananswer', 'qtype_turtipskupon');
    }

    public function is_choice_selected($response, $value) {
        return (string) $response === (string) $value;
    }

    public function get_response(question_attempt $qa) {
        return $qa->get_last_qt_var('answer', -1);
    }
}

/**
 * Represents a multiple choice question where multiple choices can be selected.
 */
class qtype_turtipskupon_multi_question extends qtype_turtipskupon_base {

    public function get_renderer(moodle_page $page) {
        return $page->get_renderer('qtype_turtipskupon', 'multi');
    }

    /**
     * @param int $key choice number
     * @return string the question-type variable name.
     */
    protected function field($key) {
        return 'choice' . $key;
    }

    public function get_expected_data() {
        $expected = array();
        // Commented out to help with development
        //
        // foreach ($this->order as $key => $notused) {
        //     $expected[$this->field($key)] = PARAM_BOOL;
        // }
        return $expected;
    }

    public function summarise_response(array $response) {
        $selectedchoices = array();
        foreach ($this->order as $key => $ans) {
            $fieldname = $this->field($key);
            if (array_key_exists($fieldname, $response) && $response[$fieldname]) {
                $selectedchoices[] = $this->html_to_text($this->answers[$ans]->answer,
                        $this->answers[$ans]->answerformat);
            }
        }
        if (empty($selectedchoices)) {
            return null;
        }
        return implode('; ', $selectedchoices);
    }

    public function get_correct_response() {
        $response = array();
        foreach ($this->order as $key => $ans) {
            if (!question_state::graded_state_for_fraction(
                    $this->answers[$ans]->fraction)->is_incorrect()) {
                $response[$this->field($key)] = 1;
            }
        }
        return $response;
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        foreach ($this->order as $key => $notused) {
            $fieldname = $this->field($key);
            if (!question_utils::arrays_same_at_key($prevresponse, $newresponse, $fieldname)) {
                return false;
            }
        }
        return true;
    }

    public function is_complete_response(array $response) {
        foreach ($this->order as $key => $notused) {
            if (!empty($response[$this->field($key)])) {
                return true;
            }
        }
        return false;
    }

    public function grade_response(array $response) {
        $fraction = 0;
        foreach ($this->order as $key => $ansid) {
            if (!empty($response[$this->field($key)])) {
                $fraction += $this->answers[$ansid]->fraction;
            }
        }
        $fraction = min(max(0, $fraction), 1.0);
        return array($fraction, question_state::graded_state_for_fraction($fraction));
    }

    public function get_validation_error(array $response) {
        if ($this->is_gradable_response($response)) {
            return '';
        }
        return get_string('pleaseselectatleastoneanswer', 'qtype_turtipskupon');
    }

    public function get_response(question_attempt $qa) {
        return $qa->get_last_qt_data();
    }

    public function is_choice_selected($response, $value) {
        return !empty($response['choice' . $value]);
    }
}
