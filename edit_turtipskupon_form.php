<?php

defined('MOODLE_INTERNAL') || die();

class qtype_turtipskupon_edit_form extends question_edit_form {

    protected function definition_inner($mform) {

        // Remove 'Default mark'
        $mform->removeElement('defaultmark');

        // 'Autoplay' checkbox
        $mform->addElement('advcheckbox', 'autoplay',
                get_string('autoplay', 'qtype_turtipskupon'), null, null, array(0, 1));
        $mform->setDefault('autoplay', 1); // TODO: Use/set constant

        // 'One or multiple answers?' select menu
        $menu = array(
            get_string('answersingleno', 'qtype_turtipskupon'),
            get_string('answersingleyes', 'qtype_turtipskupon'),
        );
        $mform->addElement('select', 'single',
                get_string('answerhowmany', 'qtype_turtipskupon'), $menu);
        $mform->setDefault('single', 0); // TODO: Use constant

        // 'Image to display' filemanager
        $mform->addElement('filemanager', 'questionimage', 'Image to display', null,
            array('maxfiles' => 1)); // TODO: Use lang string

        // 'Choose soundfile for question' filemanager
        $mform->addElement('filemanager', 'questionsound', 'Choose soundfile for question', null,
            array('maxfiles' => 1, 'accepted_types' => array('mp3'))); // TODO: Use lang string

        // 'Difficulty' select menu
        $question_difficulties = array();
        $question_difficulties[0] = get_string('q_easy1', 'qtype_turtipskupon');
        $question_difficulties[1] = get_string('q_easy2', 'qtype_turtipskupon');
        $question_difficulties[2] = get_string('q_easy3', 'qtype_turtipskupon');
        $question_difficulties[3] = get_string('q_medium1', 'qtype_turtipskupon');
        $question_difficulties[4] = get_string('q_medium2', 'qtype_turtipskupon');
        $question_difficulties[5] = get_string('q_medium3', 'qtype_turtipskupon');
        $question_difficulties[6] = get_string('q_hard1', 'qtype_turtipskupon');
        $question_difficulties[7] = get_string('q_hard2', 'qtype_turtipskupon');
        $question_difficulties[8] = get_string('q_hard3', 'qtype_turtipskupon');
        $mform->addElement('select', 'qdifficulty', get_string('qdifficulty', 'qtype_turtipskupon'), $question_difficulties);

        // 'Shuffle the choices?' checkbox
        $mform->addElement('advcheckbox', 'shuffleanswers',
                get_string('shuffleanswers', 'qtype_turtipskupon'), null, null, array(0, 1));
        $mform->addHelpButton('shuffleanswers', 'shuffleanswers', 'qtype_turtipskupon');
        $mform->setDefault('shuffleanswers', 1); // TODO: Use constant
        $this->add_per_answer_fields($mform, get_string('choiceno', 'qtype_turtipskupon', '{no}'),
                question_bank::fraction_options_full(), max(4, QUESTION_NUMANS_START)); // TODO: Set as a constant the number of answers (4 here)

        $this->add_combined_feedback_fields(true);
        $this->add_interactive_settings(true, true);
    }

    protected function get_per_answer_fields($mform, $label, $gradeoptions,
            &$repeatedoptions, &$answersoption) {

        $filemanageroptions = $this->editoroptions;
        $filemanageroptions['maxfiles'] = 1;
        $filemanageroptions['accepted_types'] = array('mp3');
        $filemanageroptions['return_types'] = FILE_INTERNAL | FILE_EXTERNAL;

        $repeated = array();
        $repeated[] = $mform->createElement('header', 'choicehdr', $label);
        $repeated[] = $mform->createElement('editor', 'answer', $label,
            array('rows' => 1), $this->editoroptions);

        $repeated[] = $mform->createElement('filemanager', 'answersound',
            'Choose soundfile for answer', null, $filemanageroptions); // TODO: use lang string
        $repeated[] = $mform->createElement('select', 'fraction',
                get_string('grade'), $gradeoptions);
        $repeated[] = $mform->createElement('editor', 'feedback',
                get_string('feedback', 'question'), array('rows' => 1), $this->editoroptions);
        $repeated[] = $mform->createElement('filemanager', 'feedbacksound',
            'Choose soundfile for answerfeedback', null, $filemanageroptions); // TODO: use lang string

        $repeatedoptions['answer']['type'] = PARAM_RAW;
        $repeatedoptions['fraction']['default'] = 0;
        $answersoption = 'answers';

        return $repeated;
    }

    /**
     * Add a set of form fields, obtained from get_per_answer_fields, to the form,
     * one for each existing answer, with some blanks for some new ones.
     * @param object $mform the form being built.
     * @param $label the label to use for each option.
     * @param $gradeoptions the possible grades for each answer.
     * @param $minoptions the minimum number of answer blanks to display.
     *      Default QUESTION_NUMANS_START.
     * @param $addoptions the number of answer blanks to add. Default QUESTION_NUMANS_ADD.
     */
    protected function add_per_answer_fields(&$mform, $label, $gradeoptions,
            $minoptions = QUESTION_NUMANS_START, $addoptions = QUESTION_NUMANS_ADD) {

        $answersoption = '';
        $repeatedoptions = array();
        $repeated = $this->get_per_answer_fields($mform, $label, $gradeoptions, $repeatedoptions, $answersoption);

        if (isset($this->question->options)) {
            $repeatsatstart = count($this->question->options->$answersoption);
        } else {
            $repeatsatstart = $minoptions;
        }

        $this->repeat_elements($repeated, $repeatsatstart, $repeatedoptions,
                'noanswers', 'addanswers', $addoptions,
                $this->get_more_choices_string(), true);
    }


    /**
     * Perform preprocessing needed on the data passed to {@link set_data()}
     * before it is used to initialise the form.
     * @param object $question the data being passed to the form.
     * @return object $question the modified data.
     */
    protected function data_preprocessing($question) {

        if (!isset($question->options)) {
            $question->options = new stdClass();
            $question->options->single = 0; // TODO: Use constant defined above
            $question->options->shuffleanswers = 1; // TODO: Use constant defined above
            $question->options->qdifficulty = 0;
            $question->options->autoplay = 1;
            $question->options->correctfeedbackformat = 1;
            $question->options->partiallycorrectfeedbackformat = 1;
            $question->options->incorrectfeedbackformat = 1;
            $question->options->shownumcorrect = 1;
        }

        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_answers($question, true);
        $question = $this->data_preprocessing_combined_feedback($question, true);
        $question = $this->data_preprocessing_hints($question, true, true);

        if (!empty($question->options)) {  // Warnings/notices when creating new question type
            $question->single = $question->options->single;
            $question->shuffleanswers = $question->options->shuffleanswers;
            $question->qdifficulty = $question->options->qdifficulty;
            $question->autoplay = $question->options->autoplay;
        }

        // Prepare the questionimage filemanager to display files in draft area.
        $draftitemid = file_get_submitted_draft_itemid('questionimage');
        file_prepare_draft_area($draftitemid, $this->context->id,
                'question', 'questionimage', $question->id);
        $question->questionimage = $draftitemid;

        // Prepare the questionsound filemanager to display files in draft area.
        $draftitemid = file_get_submitted_draft_itemid('questionsound');
        file_prepare_draft_area($draftitemid, $this->context->id,
                'question', 'questionsound', $question->id);
        $question->questionsound = $draftitemid;

        return $question;
    }

    protected function data_preprocessing_combined_feedback($question, $withshownumcorrect = false) {

        return $question;
    }

    /**
     * Perform the necessary preprocessing for the fields added by
     * {@link add_per_answer_fields()}.
     * @param object $question the data being passed to the form.
     * @return object $question the modified data.
     */
    protected function data_preprocessing_answers($question, $withanswerfiles = false) {

        if (empty($question->options->answers)) {
            return $question;
        }

        $key = 0;
        foreach ($question->options->answers as $answer) {
            if ($withanswerfiles) {
                // Prepare the feedback editor to display files in draft area.
                $draftitemid = file_get_submitted_draft_itemid('answer['.$key.']');
                $question->answer[$key]['text'] = file_prepare_draft_area(
                    $draftitemid,          // Draftid
                    $this->context->id,    // context
                    'question',            // component
                    'answer',              // filarea
                    !empty($answer->id) ? (int) $answer->id : null, // itemid
                    $this->fileoptions,    // options
                    $answer->answer        // text.
                );
                $question->answer[$key]['itemid'] = $draftitemid;
                $question->answer[$key]['format'] = $answer->answerformat;

            } else {
                $question->answer[$key] = $answer->answer;
            }

            $question->fraction[$key] = 0 + $answer->fraction;
            $question->feedback[$key] = array();

            // Evil hack alert. Formslib can store defaults in two ways for
            // repeat elements:
            //   ->_defaultValues['fraction[0]'] and
            //   ->_defaultValues['fraction'][0].
            // The $repeatedoptions['fraction']['default'] = 0 bit above means
            // that ->_defaultValues['fraction[0]'] has already been set, but we
            // are using object notation here, so we will be setting
            // ->_defaultValues['fraction'][0]. That does not work, so we have
            // to unset ->_defaultValues['fraction[0]'].
            unset($this->_form->_defaultValues["fraction[$key]"]);

            // Prepare the feedback editor to display files in draft area.
            $draftitemid = file_get_submitted_draft_itemid('feedback['.$key.']');
            $question->feedback[$key]['text'] = file_prepare_draft_area(
                $draftitemid,          // Draftid
                $this->context->id,    // context
                'question',            // component
                'answerfeedback',      // filarea
                !empty($answer->id) ? (int) $answer->id : null, // itemid
                $this->fileoptions,    // options
                $answer->feedback      // text.
            );
            $question->feedback[$key]['itemid'] = $draftitemid;
            $question->feedback[$key]['format'] = $answer->feedbackformat;

            // Prepare the answersound filemanager to display files in draft area.
            $draftitemid = file_get_submitted_draft_itemid('answersound['.$key.']');
            file_prepare_draft_area($draftitemid, $this->context->id,
                    'question', 'answersound', $answer->id);
            $question->answersound[$key] = $draftitemid;

            // Prepare the feedbacksound filemanager to display files in draft area.
            $draftitemid = file_get_submitted_draft_itemid('feedbacksound['.$key.']');
            file_prepare_draft_area($draftitemid, $this->context->id,
                    'question', 'feedbacksound', $answer->id);
            $question->feedbacksound[$key] = $draftitemid;

            $key++;
        }

        // Now process extra answer fields.
        $extraanswerfields = question_bank::get_qtype($question->qtype)->extra_answer_fields();
        if (is_array($extraanswerfields)) {
            // Omit table name.
            array_shift($extraanswerfields);
            $question = $this->data_preprocessing_extra_answer_fields($question, $extraanswerfields);
        }

        return $question;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $answers = $data['answer'];
        $answercount = 0;

        $totalfraction = 0;
        $maxfraction = -1;

        foreach ($answers as $key => $answer) {
            // Check no of choices.
            $trimmedanswer = trim($answer['text']);
            $fraction = (float) $data['fraction'][$key];
            if ($trimmedanswer === '' && empty($fraction)) {
                continue;
            }

            if ($trimmedanswer === '') {
                $errors['fraction['.$key.']'] = get_string('errgradesetanswerblank', 'qtype_turtipskupon');
            }

             $answercount++;

            // Check grades.
            if ($data['fraction'][$key] > 0) {
                $totalfraction += $data['fraction'][$key];
            }
            if ($data['fraction'][$key] > $maxfraction) {
                $maxfraction = $data['fraction'][$key];
            }
        }

        if ($answercount == 0) {
            $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_turtipskupon', 2);
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_turtipskupon', 2);
        } else if ($answercount == 1) {
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_turtipskupon', 2);
        }

        // Perform sanity checks on fractional grades.
        if ($data['single']) {
            if ($maxfraction != 1) {
                $errors['fraction[0]'] = get_string('errfractionsnomax', 'qtype_turtipskupon', $maxfraction * 100);
            }
        } else {
            $totalfraction = round($totalfraction, 2);
            if ($totalfraction != 1) {
                $errors['fraction[0]'] = get_string('errfractionsaddwrong', 'qtype_turtipskupon', $totalfraction * 100);
            }
        }

        return $errors;
    }

    public function qtype() {
        return 'turtipskupon';
    }

    public function set_data($question) {
        parent::set_data($question);
    }
}
