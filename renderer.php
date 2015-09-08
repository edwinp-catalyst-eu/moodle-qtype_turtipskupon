<?php

defined('MOODLE_INTERNAL') || die();

abstract class qtype_turtipskupon_renderer_base extends qtype_with_combined_feedback_renderer {

    protected abstract function get_input_type();

    protected abstract function get_input_name(question_attempt $qa, $value);

    protected abstract function get_input_value($value);

    protected abstract function get_input_id(question_attempt $qa, $value);

    protected function get_answersound(question_answer $ans, $contextid, $slot, $usageid) {

        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, 'question', 'answersound', $ans->id);
        $file = end($files);
        $filename = $file->get_filename();

        return moodle_url::make_file_url('/pluginfile.php',
                "/$contextid/question/answersound/$usageid/$slot/$ans->id/$filename");
    }

    protected function get_questionimage($questionid, $contextid, $slot, $usageid) {

        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, 'question', 'questionimage', $questionid);
        $file = end($files);
        $filename = $file->get_filename();

        return moodle_url::make_file_url('/pluginfile.php',
                "/$contextid/question/questionimage/$usageid/$slot/$questionid/$filename");
    }


    protected function get_questionsound($questionid, $contextid, $slot, $usageid) {

        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, 'question', 'questionsound', $questionid);
        $file = end($files);
        $filename = $file->get_filename();

        return moodle_url::make_file_url('/pluginfile.php',
                "/$contextid/question/questionsound/$usageid/$slot/$questionid/$filename");
    }

    /**
     * Whether a choice should be considered right, wrong or partially right.
     * @param question_answer $ans representing one of the choices.
     * @return fload 1.0, 0.0 or something in between, respectively.
     */
    protected abstract function is_right(question_answer $ans);

    protected abstract function prompt();

    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {

        $question = $qa->get_question();
        $response = $question->get_response($qa);

        $inputname = $qa->get_qt_field_name('answer');
        $inputattributes = array(
            'type' => $this->get_input_type(),
            'name' => $inputname,
        );

        if ($options->readonly) {
            $inputattributes['disabled'] = 'disabled';
        }

        $radiobuttons = array();
        $feedbackimg = array();
        $feedback = array();
        $classes = array();
        foreach ($question->get_order($qa) as $value => $ansid) {
            $ans = $question->answers[$ansid];
            $inputattributes['name'] = $this->get_input_name($qa, $value);
            $inputattributes['value'] = $this->get_input_value($value);
            $inputattributes['id'] = $this->get_input_id($qa, $value);
            $isselected = $question->is_choice_selected($response, $value);
            if ($isselected) {
                $inputattributes['checked'] = 'checked';
            } else {
                unset($inputattributes['checked']);
            }

            $answersound = html_writer::div('', 'audioplay',
                    array('data-src' => $this->get_answersound($ans,
                            $question->contextid, $qa->get_slot(), $qa->get_usage_id())));

            $hidden = '';
            if (!$options->readonly && $this->get_input_type() == 'checkbox') {
                $hidden = html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => $inputattributes['name'],
                    'value' => 0,
                ));
            }
            $radiobuttons[] = $answersound . $hidden .
                    html_writer::tag('label',
                        $question->make_html_inline(
                                        $question->format_text(
                                            $ans->answer,
                                            $ans->answerformat,
                                            $qa,
                                            'question',
                                            'answer',
                                            $ansid
                                        )
                                    ),
                    array('for' => $inputattributes['id'])) . html_writer::empty_tag('input', $inputattributes);

            // Param $options->suppresschoicefeedback is a hack specific to the
            // oumultiresponse question type. It would be good to refactor to
            // avoid refering to it here.
            if ($options->feedback && empty($options->suppresschoicefeedback) &&
                    $isselected && trim($ans->feedback)) {
                $feedback[] = html_writer::tag('div',
                        $question->make_html_inline($question->format_text(
                                $ans->feedback, $ans->feedbackformat,
                                $qa, 'question', 'answerfeedback', $ansid)),
                        array('class' => 'specificfeedback'));
            } else {
                $feedback[] = '';
            }
            $class = 'r' . ($value % 2);
            if ($options->correctness && $isselected) {
                $feedbackimg[] = $this->feedback_image($this->is_right($ans));
                $class .= ' ' . $this->feedback_class($this->is_right($ans));
            } else {
                $feedbackimg[] = '';
            }
            $classes[] = $class;
        }

        $result = '';
        $result .= html_writer::div('', 'audioplay',
                array('data-src' => $this->get_questionsound($question->id,
                        $question->contextid, $qa->get_slot(), $qa->get_usage_id())));
        $result .= html_writer::tag('div', $question->format_questiontext($qa),
                array('class' => 'qtext'));

        $result .= html_writer::start_tag('div', array('class' => 'ablock'));
        $result .= html_writer::tag('div', $this->prompt(), array('class' => 'prompt'));

        $result .= html_writer::start_tag('div', array('class' => 'answer'));
        foreach ($radiobuttons as $key => $radio) {
            $result .= html_writer::tag('div', $radio . ' ' . $feedbackimg[$key] . $feedback[$key],
                    array('class' => $classes[$key])) . "\n";
        }
        $result .= html_writer::end_tag('div'); // Answer.

        $questionimage = html_writer::empty_tag('img', array(
            'src' => $this->get_questionimage($question->id, $question->contextid, $qa->get_slot(), $qa->get_usage_id())));
        $result .= html_writer::div($questionimage, 'questionimage');

        $result .= html_writer::end_tag('div'); // Ablock.

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error($qa->get_last_qt_data()),
                    array('class' => 'validationerror'));
        }

        $this->page->requires->js_init_call('M.qtype_turtipskupon.init',
                array('#q' . $qa->get_slot()), false, array(
                    'name'     => 'qtype_turtipskupon',
                    'fullpath' => '/question/type/turtipskupon/module.js',
                    'requires' => array('base', 'node', 'event', 'overlay'),
                ));

        return $result;
    }
}

class qtype_turtipskupon_single_renderer extends qtype_turtipskupon_renderer_base {

    protected function get_input_type() {
        return 'radio';
    }

    protected function get_input_name(question_attempt $qa, $value) {
        return $qa->get_qt_field_name('answer');
    }

    protected function get_input_value($value) {
        return $value;
    }

    protected function get_input_id(question_attempt $qa, $value) {
        return $qa->get_qt_field_name('answer' . $value);
    }

    protected function is_right(question_answer $ans) {
        return $ans->fraction;
    }

    protected function prompt() {
        return get_string('selectone', 'qtype_turtipskupon');
    }
}

class qtype_turtipskupon_multi_renderer extends qtype_turtipskupon_renderer_base {

    protected function get_input_type() {
        return 'checkbox';
    }

    protected function get_input_name(question_attempt $qa, $value) {
        return $qa->get_qt_field_name('answer');
    }

    protected function get_input_value($value) {
        return $value;
    }

    protected function get_input_id(question_attempt $qa, $value) {
        return $qa->get_qt_field_name('answer' . $value);
    }

    protected function is_right(question_answer $ans) {
        return $ans->fraction;
    }

    protected function prompt() {
        return get_string('selectmultiple', 'qtype_turtipskupon');
    }
}
