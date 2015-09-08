<?php

// $Id: questiontype.php,v 1.2 2007/09/11 09:35:04 thepurpleblob Exp $
/// QUESTION TYPE CLASS //////////////////
///
/// This class contains some special features in order to make the
/// question type embeddable within a multianswer (cloze) question
///
class question_turtipskupon_qtype extends default_questiontype {

    var $soundcounter = 0;
    var $feedsoundcounter = 0;

    function name() {
        return 'turtipskupon';
    }

    function has_html_answers() {
        return true;
    }

    var $already_done = false;

    function get_html_head_contributions(&$question, &$state) {
        global $CFG;
        if ($this->already_done) {
            return array();
        }
        $this->already_done = true;
        $plugindir = $this->plugin_dir();
        $baseurl = $this->plugin_baseurl();
        $stylesheets = array();
        if (file_exists($plugindir . '/styles.css')) {
            $stylesheets[] = 'styles.css';
        }
        if (file_exists($plugindir . '/styles.php')) {
            $stylesheets[] = 'styles.php';
        }
        if (file_exists($plugindir . '/script.js')) {
            require_js($baseurl . '/script.js');
        }
        if (file_exists($plugindir . '/script.php')) {
            require_js($baseurl . '/script.php');
        }

        // YUI depencies: http://developer.yahoo.com/yui/articles/hosting/?animation&dragdrop&element&layout&reset&MIN
        require_js(array($CFG->wwwroot . '/question/type/turtipskupon/js/yui-combo.js'));
        $stylesheets[] = 'css/yui-combo.css';
        $stylesheets[] = 'css/display.css';
        $stylesheets[] = 'css/hideElements.css';

        // BEGIN: audio.sj / http://kolber.github.com/audiojs/
        require_js($baseurl . '/audiojs/audio.min.js');
        require_js($baseurl . '/js/audiojs-local-120303.js');
        $stylesheets[] = 'css/audiojs-120303.css';
        // END: audio.js

        $contributions = array();
        foreach ($stylesheets as $stylesheet) {
            $contributions[] = '<link rel="stylesheet" type="text/css" href="' . $baseurl . '/' . $stylesheet . '" />';
        }
        return $contributions;
    }

    function tur_setcustomfraction($numAnswers) {
        $turfraction = 0;
        switch ($numAnswers) {
            case 1:
                $turfraction = 1;
                break;
            case 2:
                $turfraction = 0.5;
                break;
            case 3:
                $turfraction = 0.33333;
                break;
            case 4:
                $turfraction = 0.25;
                break;
            case 5:
                $turfraction = 0.20;
                break;
            case 10:
                $turfraction = 0.1;
                break;
            default:
                $turfraction = round((1 / $numAnswers), 5);
        }
        return $turfraction;
    }

    function get_question_options(&$question) {
        //print('questiontype.php: get_question_options' . '<br />');
        // Get additional information from database
        // and attach it to the question object
        if (!$question->options = get_record('question_turtipskupon', 'question', $question->id)) {
            notify('Error: Missing question options for turtipskupon question' . $question->id . '!');
            return false;
        }
        if (!$question->options->answers = get_records_select('question_answers', 'id IN (' . $question->options->answers . ')', 'id')) {
            notify('Error: Missing question answers for turtipskupon question' . $question->id . '!');
            return false;
        }
        return true;
    }

    function save_question_options($question) {
        $numAnswers = 0;

        $result = new stdClass;
        if (!$oldanswers = get_records("question_answers", "question", $question->id, "id ASC")) {
            $oldanswers = array();
        }
        // following hack to check at least two answers exist
        $answercount = 0;
        foreach ($question->answer as $key => $dataanswer) {
            if ($dataanswer != "") {
                $answercount++;
            }
        }
        $answercount += count($oldanswers);
        if ($answercount < 2) {
            // check there are at lest 2 answers for multiple choice
            $result->notice = get_string("notenoughanswers", "qtype_turtipskupon", "2");
            return $result;
        }

        /* DMD */
        foreach ($question->answer as $key => $answer) {
            $trimmedanswer = trim($answer);
            if (!empty($trimmedanswer)) {
                $numAnswers++;
            }
        }

        // Insert all the new answers
        $totalfraction = 0;
        $maxfraction = -1;
        $answers = array();

        foreach ($question->answer as $key => $dataanswer) {
            if ($dataanswer != "") {
                if ($answer = array_shift($oldanswers)) {
                    // Existing answer, so reuse it
                    $answer->answer = $dataanswer;
                    //$answer->answersound = $question->answersound[$key];
                    //$answer->answersound = $question->{'answersound[' . $key . ']'};
                    //$answer->feedbacksound = $question->feedbacksound[$key];
                    //$answer->feedbacksound = $question->{'feedbacksound[' . $key . ']'};
                    // answersound
                    $answersound = $question->{'answersound[' . $key . ']'};
                    if ($answersound == "") {
                        $answersound = $question->answersound[$key];
                    }
                    $answer->answersound = $answersound;

                    //feedback sound
                    $feedbacksound = $question->{'feedbacksound[' . $key . ']'};
                    if ($feedbacksound == "") {
                        $feedbacksound = $question->feedbacksound[$key];
                    }
                    $answer->feedbacksound = $feedbacksound;

                    $answer->fraction = $this->tur_setcustomfraction($numAnswers);
                    $answer->tur_answer_truefalse = $question->tur_answer_truefalse[$key];
                    $answer->feedback = $question->feedback[$key];


                    if (!update_record("question_answers", $answer)) {
                        $result->error = "Could not update quiz answer! (id=$answer->id)";
                        return $result;
                    }
                } else {
                    // nyt svar

                    unset($answer);
                    $answer->answer = $dataanswer;

                    // answersound
                    $answersound = $question->{'answersound[' . $key . ']'};
                    if ($answersound == "") {
                        $answersound = $question->answersound[$key];
                    }
                    $answer->answersound = $answersound;

                    //feedback sound
                    $feedbacksound = $question->{'feedbacksound[' . $key . ']'};
                    if ($feedbacksound == "") {
                        $feedbacksound = $question->feedbacksound[$key];
                    }
                    $answer->feedbacksound = $feedbacksound;

                    $answer->question = $question->id;
                    $answer->fraction = $this->tur_setcustomfraction($numAnswers);
                    $answer->tur_answer_truefalse = $question->tur_answer_truefalse[$key];
                    $answer->feedback = $question->feedback[$key];
                    if (!$answer->id = insert_record("question_answers", $answer)) {
                        $result->error = "Could not insert quiz answer! ";
                        return $result;
                    }
                }
                $answers[] = $answer->id;

                if ($question->fraction[$key] > 0) {
                    // Sanity checks
                    $totalfraction += $answer->fraction;
                }

                if ($question->fraction[$key] > $maxfraction) {
                    $maxfraction = $question->fraction[$key];
                }
            }
        }
        $update = true;
        $options = get_record("question_turtipskupon", "question", $question->id);
        if (!$options) {
            $update = false;
            $options = new stdClass;
            $options->question = $question->id;
        }

        $options->questionsound = $question->questionsound;
        $options->answers = implode(",", $answers);
        $options->single = $question->single;
        $options->autoplay = $question->autoplay;
        $options->qdifficulty = $question->qdifficulty;
        $options->answernumbering = $question->answernumbering;
        $options->shuffleanswers = $question->shuffleanswers;

        if ($update) {
            if (!update_record("question_turtipskupon", $options)) {
                $result->error = "Could not update quiz turtipskupon options! (id=$options->id)";
                return $result;
            }
        } else {
            if (!insert_record("question_turtipskupon", $options)) {
                $result->error = "Could not insert quiz turtipskupon options!";
                return $result;
            }
        }

        // delete old answer records
        if (!empty($oldanswers)) {
            foreach ($oldanswers as $oa) {
                delete_records('question_answers', 'id', $oa->id);
            }
        }

        /* MPL ******************************** */
        /// Perform sanity checks on fractional grades

        if ($options->single) {
            if ($maxfraction != 1) {
                $maxfraction = $maxfraction * 100;
                $result->noticeyesno = get_string("fractionsnomax", "qtype_turtipskupon", $maxfraction);
                return $result;
            }
        } else {
            $totalfraction = round($totalfraction, 2);
            if ($totalfraction != 1) {
                $totalfraction = $totalfraction * 100;
                $result->noticeyesno = get_string("fractionsaddwrong", "qtype_turtipskupon", $totalfraction);
                return $result;
            }
        }
        /* MPL ******************************** */
        return true;
    }

    /**
     * Deletes question from the question-type specific tables
     *
     * @return boolean Success/Failure
     * @param object $question  The question being deleted
     */
    function delete_question($questionid) {
        delete_records("question_turtipskupon", "question", $questionid);
        return true;
    }

    function create_session_and_responses(&$question, &$state, $cmoptions, $attempt) {
        //print('create_session_and_responses <br/>');
        // create an array of answerids ??? why so complicated ???
        $answerids = array_values(array_map(create_function('$val', 'return $val->id;'), $question->options->answers));
        // Shuffle the answers if required
        if ($cmoptions->shuffleanswers and $question->options->shuffleanswers) {
            $answerids = swapshuffle($answerids);
        }
        $state->options->order = $answerids;
        // Create empty responses
        if ($question->options->single) {
            $state->responses = array('' => '');
        } else {
            //$state->responses = array();
            $state->responses = array('' => '');
        }
        return true;
    }

    function extra_question_fields() {
        //print('extra_question_fields' . '<br />');
        return array('question_turtipskupon', 'questionsound, autoplay, qdifficulty');
    }

    function restore_session_and_responses(&$question, &$state) {
        //  print('restore_session_and_responses' . '<br />');
        //t3lib_div::debug($state, '1');
        // The serialized format for multiple choice quetsions
        // is an optional comma separated list of answer ids (the order of the
        // answers) followed by a colon, followed by another comma separated
        // list of answer ids, which are the radio/checkboxes that were
        // ticked.
        // E.g. 1,3,2,4:2,4 means that the answers were shown in the order
        // 1, 3, 2 and then 4 and the answers 2 and 4 were checked.
        $pos = strpos($state->responses[''], ':');
        if (false === $pos) {
            // No order of answers is given, so use the default
            $state->options->order = array_keys($question->options->answers);
        } else {
            // Restore the order of the answers
            $state->options->order = explode(',', substr($state->responses[''], 0, $pos));
            $state->responses[''] = substr($state->responses[''], $pos + 1);
        }

        // Restore the responses
        // This is done in different ways if only a single answer is allowed or
        // if multiple answers are allowed. For single answers the answer id is
        // saved in $state->responses[''], whereas for the multiple answers case
        // the $state->responses array is indexed by the answer ids and the
        // values are also the answer ids (i.e. key = value).
        if (empty($state->responses[''])) {
            // No previous responses
            if ($question->options->single) {
                $state->responses = array('' => '');
            } else {
                $state->responses = array();
            }
        } else {
            if ($question->options->single) {
                $state->responses = array('' => $state->responses['']);
            } else {
                // Get array of answer ids

                $a = array_flip($state->options->order);
                $a = array_keys($a);
                //  t3lib_div::debug($a);
                $b = explode(',', $state->responses['']);
                //  t3lib_div::debug($b);
                $d = array();
                for ($i = 0; $i <= count($a) - 1; $i++) {
                    $flag = false;
                    foreach ($b as $actualanswer) {
                        if ($a[$i] == $this->stripAnswerid($actualanswer)) {
                            $d[$i] = $actualanswer;
                            $flag = true;
                        }
                    }

                    if (!$flag) {
                        $d[$i] = $a[$i] . '_0';
                    }
                }
                $c = array_combine($a, $d);
                $state->responses = $c;
            }
        }
        return true;
    }

    function save_session_and_responses(&$question, &$state) {
        // Bundle the answer order and the responses into the legacy answer
        // field.
        // The serialized format for multiple choice quetsions
        // is (optionally) a comma separated list of answer ids
        // followed by a colon, followed by another comma separated
        // list of answer ids, which are the radio/checkboxes that were
        // ticked.
        // E.g. 1,3,2,4:2,4 means that the answers were shown in the order
        // 1, 3, 2 and then 4 and the answers 2 and 4 were checked.

        $tempVal = $state->responses;
        $tempAnswers = array();
        $tempOrder = $state->options->order;
        $tempOrder = array_flip($tempOrder);

        ksort($state->responses);

        $responses = implode(',', $state->options->order) . ':';
        $responses .= implode(',', $state->responses);

        // Set the legacy answer field
        if (!set_field('question_states', 'answer', $responses, 'id', $state->id)) {
            return false;
        }
        return true;
    }

    function get_correct_responses(&$question, &$state) {
        //print('get_correct_responses' . '<br />');
        if ($question->options->single) {
            foreach ($question->options->answers as $answer) {
                if (((int) $answer->fraction) === 1) {
                    return array('' => $answer->id);
                }
            }
            return null;
        } else {
            $responses = array();
            foreach ($question->options->answers as $answer) {
                if (((float) $answer->fraction) > 0.0) {
                    $responses[$answer->id] = (string) $answer->id;
                }
            }
            return empty($responses) ? null : $responses;
        }
    }

    function tipskupon_get_correct_responses(&$question, &$state) {
        // print('tipskupon_get_correct_responses' . '<br />');
        if ($question->options->single) {
            foreach ($question->options->answers as $answer) {
                if (((int) $answer->fraction) === 1) {
                    return array('' => $answer->id);
                }
            }
            return null;
        } else {
            $responses = array();
            foreach ($question->options->answers as $answer) {
                //print_r($answer);
                $responses[$answer->id] = (string) $answer->id;
            }
            return empty($responses) ? null : $responses;
        }
    }

    /* MPL */

    function isreview() {
        $attempt = optional_param('attempt', 0, PARAM_INT);
        if ($attempt) {
            return true;
        } else {
            return false;
        }
    }

    function question_numbering() {
        global $COURSE;
        // Course Module ID, or
        $id = optional_param('id', 0, PARAM_INT);
        // quiz ID
        $q = optional_param('q', 0, PARAM_INT);
        // quiz ID
        $page = optional_param('page', 0, PARAM_INT);
        $attempt = optional_param('attempt', 0, PARAM_INT);
        $showall = optional_param('showall', 0);
        if ($id) {
            if (!$cm = get_coursemodule_from_id('quiz', $id)) {
                //  error("There is no coursemodule with id $id");
            }
            if (!$course = get_record("course", "id", $cm->course)) {
                //  error("Course is misconfigured");
            }
            if (!$quiz = get_record("quiz", "id", $cm->instance)) {
                //  error("The quiz with id $cm->instance corresponding to this coursemodule $id is missing");
            }
        } else {
            if ($q) {
                if (!$quiz = get_record("quiz", "id", $q)) {
                    //  error("There is no quiz with id $q");
                }
                if (!$course = get_record("course", "id", $quiz->course)) {
                    //  error("The course with id $quiz->course that the quiz with id $q belongs to is missing");
                }
                if (!$cm = get_coursemodule_from_instance("quiz", $quiz->id, $course->id)) {
                    //  error("The course module for the quiz with id $q is missing");
                }
            } else {
                if (!$quizID = get_record("quiz_attempts", "id", $attempt)) {
                    //error("There is no quiz with id $attempt");
                }
                if (!$quiz = get_record("quiz", "id", $quizID->quiz)) {
                    //  error("There is no quiz with id $q");
                }
                if (!$course = get_record("course", "id", $quiz->course)) {
                    //  error("The course with id $quiz->course that the quiz with id $q belongs to is missing");
                }
                if (!$cm = get_coursemodule_from_instance("quiz", $quiz->id, $course->id)) {
                    //  error("The course module for the quiz with id $q is missing");
                }
            }
        }
        if (!$showall) {
            $Qs = array($quiz->questions);
            $QsP = $Qs[0];
            $QsT = t3lib_div::trimExplode(',', $QsP, 1);

            // remove zeroes
            $QsT = t3lib_div::removeArrayEntryByValue($QsT, '0');

            $noQ = count($QsT);
            $noP = $page + 1;
            //  print_r('Spørgsmål '.$noP.' ud af '.$noQ);
            return 'Spørgsmål ' . $noP . ' ud af ' . $noQ;
        }
    }

    function return_question_image($question) {
        global $CFG;
        $img = '';
        $coursefilesdir = $CFG->turimage;
        if ($question->image) {
            if (substr(strtolower($question->image), 0, 7) == 'http://') {
                $img .= $question->image;
            } elseif ($CFG->turquizimageurl) {
                // override the file.php scheme, and load direct from the webserver
                $img .= "$CFG->turquizimageurl/$question->image";
            } elseif ($CFG->slasharguments) {
                // Use this method if possible for better caching
                $img .= "$CFG->wwwroot/file.php/$coursefilesdir/$question->image";
            } else {
                $img .= "$CFG->wwwroot/file.php?file=/$coursefilesdir/$question->image";
            }
        }
        return $img;
    }

    function stripValue($string) {
        $a = split("_", $string);
        return $a[1];
    }

    function stripAnswerid($string) {
        $a = split("_", $string);
        return $a[0];
    }

    function user_setting_text_in_quiz() {
        global $USER, $COURSE;
        $selected_course = (($COURSE->id) > 1) ? ($COURSE->id) : -1;
        if ($selected_course && $selected_course != -1 && $USER->id) {
            $getcoursesetting = get_record('block_kursus_afvikling', 'userid', $USER->id, 'courseid', $selected_course);
        }
        if (!empty($getcoursesetting)) {
            return $getcoursesetting->showtext;
        }
    }

    function print_question_formulation_and_controls(&$question, &$state, $cmoptions, $options) {
        //print('print_question_formulation_and_controls' . '<br />');
        global $CFG, $COURSE;
        $answers = &$question->options->answers;

        $isreview = $this->isreview();


        // write a javascirpt var that holds if the sound should autoplay
        //echo '<script type="text/javascript">soundAutoPlay = false; </script>';
        // output a javascript var that holds if the audio should autoplay
        if ($isreview) {
            echo '<script type="text/javascript">var soundAutoPlay = false; </script>';
            echo '<script type="text/javascript">var quizAutoProgress = false; </script>';
            echo '<script type="text/javascript">var quizShowAudioControls = true; </script>';
        } else {
            if ($question->options->autoplay) {
                echo '<script type="text/javascript">var soundAutoPlay = true; </script>';
                echo '<script type="text/javascript">var quizAutoProgress = false; </script>';
                echo '<script type="text/javascript">var quizShowAudioControls = true; </script>';
            } else {
                echo '<script type="text/javascript">var soundAutoPlay = false; </script>';
                echo '<script type="text/javascript">var quizAutoProgress = false; </script>';
                echo '<script type="text/javascript">var quizShowAudioControls = true; </script>';
            }
        }

        $correctanswers = $this->tipskupon_get_correct_responses($question, $state);

        $readonly = empty($options->readonly) ? '' : 'disabled="disabled"';
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->para = false;
        $correctcount = 0;
        // Print formulation
        $questiontext = format_text($question->questiontext, $question->questiontextformat, $formatoptions, $cmoptions->course);

        if (isset($CFG->turimage)) {
            $image = $this->return_question_image($question);
        } else {
            $image = get_question_image($question, $cmoptions->course);
        }

        $questionspeak = '';
        if (!empty($question->options->questionsound)) {
            $questionspeak = $this->user_get_sound($question->options->questionsound, $question->category, true, '', true);
        }

        $qnumbering = $this->question_numbering();


        if (!$isreview) {
            $thumb_w = '512';
            $thumb_h = '384';
        } else {
            $thumb_w = '300';
            $thumb_h = '225';
        }

        $answerprompt = ($question->options->single) ? get_string('singleanswer', 'quiz') : get_string('multipleanswers', 'quiz');
        // Print each answer in a separate row
        $answercount = count($state->options->order);
        $count = 1;

        foreach ($state->options->order as $key => $aid) {
            $answer = &$answers[$aid];
            $checked = '';
            $chosen = false;

            if ($question->options->single) {
                $type = 'type="radio"';
                $name = "name=\"{$question->name_prefix}\"";
                if (isset($state->responses['']) and $aid == $state->responses['']) {
                    //$checked = 'checked="checked"';
                    $chosen = true;
                }
            } else {
                $type = ' type="radio" ';
                $name = "name=\"{$question->name_prefix}{$aid}\"";
                if (isset($state->responses[$aid])) {
                    $chosen = true;
                }
            }
            $a = new stdClass;
            $a->id = $question->name_prefix . $aid;
            $a->class = '';
            $a->feedbackimg = '';
            $a->answersound = '';
            $a->feedbacksound = '';
            $a->useranswer = -1;

            $checked1 = '';
            $checked2 = '';

            if (isset($state->responses[$key])) {
                
            }
            if (isset($state->responses[$aid])) {
                if ($state->responses[$aid] == $aid . '_2') {
                    $a->useranswer = 1;
                    $checked1 = 'checked="checked"';
                    $checked2 = '';
                } elseif ($state->responses[$aid] == $aid . '_3') {
                    $a->useranswer = 0;
                    $checked1 = '';
                    $checked2 = 'checked="checked"';
                } elseif ($state->responses[$aid] == -1) {
                    $a->useranswer = null;
                    $checked1 = '';
                    $checked2 = '';
                }
            }
            $a->fraction = $answer->fraction;
            $a->control = "<input class=\"proeve_chbx\" $name $checked1 $readonly $type value=\"$aid" . "_2\" /><input class=\"proeve_chbx\" $name $checked2 $type $readonly value=\"$aid" . "_3\" />";
            $a->answersound = $this->user_get_sound($answer->answersound, $question->category, false, '', true);
            $a->feedbacksound = $this->user_get_sound($answer->feedbacksound, $question->category, false, '1', true);

            if (($options->feedback || $options->correct_responses) && (($checked1 || $checked2) || $options->readonly)) {
                if ($a->useranswer == $answer->tur_answer_truefalse) {
                    $a->feedbackimg = $this->dkmd_question_get_feedback_image(true);
                    $correctcount++;
                } else {
                    $a->feedbackimg = $this->dkmd_question_get_feedback_image(false);
                }
            }
            $a->text = format_text($answer->answer, FORMAT_MOODLE, $formatoptions, $cmoptions->course);
            if (($options->feedback || $options->correct_responses) && (($checked1 || $checked2) || $options->readonly)) {
                if ($a->useranswer == $answer->tur_answer_truefalse) {
                    $a->feedback = "<span class='correct'>$answer->feedback</span>";
                } else {
                    $a->feedback = "<span class='wrong'>$answer->feedback</span>";
                }
            } else {
                $a->feedback = '';
            }

            $anss[] = clone($a);
        }

        $feedback = '';
        if ($options->feedback) {
            $questionspeak = '';
            if (!empty($question->options->questionsound)) {
                $questionspeak = $this->user_get_sound($question->options->questionsound, $question->category, false, '' . true);
            }
            //t3lib_div::debug($question->options,'sdklkjfl');
            if ($state->raw_grade >= $question->maxgrade / 1.01) {
                //  print('1 Ja');
                $feedback = $question->options->correctfeedback;
            } elseif ($state->raw_grade > 0) {
                //  print('2 Ja');
                $feedback = $question->options->partiallycorrectfeedback;
            } else {
                $feedback = $question->options->incorrectfeedback;
            }
            $feedback = format_text($feedback, $question->questiontextformat, $formatoptions, $cmoptions->course);
        }

        // different strings
        if ($correctcount == 1) {
            $correctfbtext = get_string('correct_answers_feedback_single', 'qtype_turtipskupon', $correctcount);
        } else {
            $correctfbtext = get_string('correct_answers_feedback', 'qtype_turtipskupon', $correctcount);
        }

        $usersetting = $anss;
        include("$CFG->dirroot/question/type/turtipskupon/display.html");
    }

    function question_get_feedback_image($fraction, $selected = true) {
        global $CFG;
        //print('question_get_feedback_image: ' + $fraction);
        if ($fraction >= 0.25) {
            if ($selected) {
                $feedbackimg = '<img src="' . $CFG->wwwroot . '/question/type/turtipskupon/images/ok.gif" ' . 'alt="' . get_string('correct', 'quiz') . '" class="icon" />';
            } else {
                $feedbackimg = '<img src="' . $CFG->wwwroot . '/question/type/turtipskupon/images/ok.gif" ' . 'alt="' . get_string('correct', 'quiz') . '" class="icon" />';
            }
        } else {
            if ($fraction == -1) {
                $feedbackimg = '';
            } else {
                if ($selected) {
                    $feedbackimg = '<img src="' . $CFG->wwwroot . '/question/type/turtipskupon/images/no.gif"' . 'alt="' . get_string('incorrect', 'quiz') . '" class="icon" />';
                } else {
                    $feedbackimg = '<img src="' . $CFG->wwwroot . '/question/type/turtipskupon/images/no.gif" ' . 'alt="' . get_string('incorrect', 'quiz') . '" class="icon" />';
                }
            }
        }
        return $feedbackimg;
    }

    function dkmd_question_get_feedback_image($answercorrect) {
        global $CFG;
        if ($answercorrect) {
            $feedbackimg = '<img src="' . $CFG->wwwroot . '/question/type/turtipskupon/images/ok.gif" ' . 'alt="' . get_string('correct', 'quiz') . '" class="icon" />';
        } else {
            $feedbackimg = '<img src="' . $CFG->wwwroot . '/question/type/turtipskupon/images/no.gif"' . 'alt="' . get_string('incorrect', 'quiz') . '" class="icon" />';
        }
        return $feedbackimg;
    }

    /**
     *
     * @global <type> $CFG
     * @param <type> $user_sound
     * @param <type> $user_question_category
     * @param <type> $autoplay
     * @param <type> $isfeedback
     * @param <bool> $isvisible
     * @return <type> html code, that includes the sound on the page
     */
    function user_get_sound($user_sound, $user_question_category, $autoplay, $isfeedback, $isvisible) {
        global $CFG;

        // resolve path to the sound file
        $sound = '';
        if (!$category = get_record('question_categories', 'id', $user_question_category)) {
            error('invalid category id ' . $user_question_category);
        }
        if (isset($CFG->tursound)) {
            $coursefilesdir = $CFG->tursound;
        } else {
            $coursefilesdir = get_filesdir_from_context(get_context_instance_by_id($category->contextid));
        }
        if (substr(strtolower($user_sound), 0, 7) == 'http://') {
            $sound .= $user_sound;
        } elseif ($CFG->turquizaudiourl) {
            // override the file.php scheme, and load direct from the webserver
            $sound .= "$CFG->turquizaudiourl/$user_sound";
        } elseif ($CFG->slasharguments) {
            $sound .= "$CFG->wwwroot/file.php/$coursefilesdir/$user_sound";
        } else {
            $sound .= "$CFG->wwwroot/file.php?file=/$coursefilesdir/$user_sound";
        }

        // build the html code
        $html = '';
        $soundfilepath = $CFG->dataroot . '/' . $coursefilesdir . '/' . $user_sound;
        if (file_exists($soundfilepath) && $user_sound != '') {
            $html = '<div class="audioplay" data-src="' . $sound . '" />';
        }

        return $html;
    }

    function user_get_feedback_sound($user_sound, $user_question_category) {
        global $CFG;
        $html = '';
        $sound = '';
        if (!$category = get_record('question_categories', 'id', $user_question_category)) {
            error('invalid category id ' . $user_question_category);
        }
        if (isset($CFG->tursound)) {
            $coursefilesdir = $CFG->tursound;
        } else {
            $coursefilesdir = get_filesdir_from_context(get_context_instance_by_id($category->contextid));
        }
        if ($user_sound) {
            if (substr(strtolower($user_sound), 0, 7) == 'http://') {
                $sound .= $user_sound;
            } elseif ($CFG->slasharguments) {
                // Use this method if possible for better caching
                $sound .= "$CFG->wwwroot/file.php/$coursefilesdir/$user_sound";
            } else {
                $sound .= "$CFG->wwwroot/file.php?file=/$coursefilesdir/$user_sound";
            }

            //  t3lib_div::debug($sound, 'Sound');

            $html = '<div id="feedsound' . $this->feedsoundcounter . '">Alternativt</div>';
            $html .= '<script type="text/javascript">';
            $html .= 'swfobject.embedSWF("' . $CFG->wwwroot . '/question/type/turtipskupon/swf/player_as3.swf", "feedsound' . $this->feedsoundcounter . '", "22", "22", "8.0.0", "' . $CFG->wwwroot . '/question/type/turtipskupon/swf/expressInstall.swf", {soundFile:"' . $CFG->wwwroot . '/question/type/turtipskupon/source/4.mp3",soundid:"feedsound' . $this->feedsoundcounter . '"}, {menu:"false",allowscriptaccess:"always", allownetworking:"all"}, false);';
            $html .= '</script>';
            $this->feedsoundcounter++;
        } else {
            $html = 'No sound';
        }
        return $html;
    }

    function grade_responses(&$question, &$state, $cmoptions) {
        $state->raw_grade = 0;
        if ($question->options->single) {
            $response = reset($state->responses);
            if ($response) {
                $state->raw_grade = $question->options->answers[$response]->fraction;
            }
        } else {
            /* MPL */
            $tmp = array_keys($question->options->answers);
            foreach ($tmp as $key => $value) {
                if (isset($state->responses[$value])) {
                    if ($state->responses[$value] == $value . '_2' && $question->options->answers[$value]->tur_answer_truefalse == '1') {
                        $state->raw_grade += $question->options->answers[$value]->fraction;
                    }
                    if ($state->responses[$value] == $value . '_3' && $question->options->answers[$value]->tur_answer_truefalse == '0') {
                        $state->raw_grade += $question->options->answers[$value]->fraction;
                    }
                } else {
                    // Brugeren har valgt at gå videre uden at angive et svar
                }
            }
        }

        $state->raw_grade = min(max((float) $state->raw_grade, 0.0), 1.0) * $question->maxgrade;
        $state->raw_grade = $state->raw_grade * $question->maxgrade;
        //  Apply the penalty for this attempt
        $state->penalty = $question->penalty * $question->maxgrade;
        // mark the state as graded
        $state->event = ($state->event == QUESTION_EVENTCLOSE) ? QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;
        return true;
    }

    function get_actual_response($question, $state) {
        $answers = $question->options->answers;
        $responses = array();
        foreach ($answers as $key => $value) {
            $temp = $answers[$key]->answer . ' ';
            //$temp .= 'Rigtigt svar: ';
            //$temp .= ($answers[$key]->tur_answer_truefalse == 1) ? 'Ja' : 'Nej';
            $temp .= ' Dit svar: ';
            $temp .= ( $state->responses[$key] == 2) ? 'Ja' : 'Nej';
            $responses[] = $temp;
            $temp = '';
        }
        return $responses;
    }

    function response_summary($question, $state, $length = 80) {
        //print('response_summary' . '<br />');
        return implode(',', $this->get_actual_response($question, $state));
    }

    /// BACKUP FUNCTIONS ////////////////////////////

    /*
     * Backup the data in the question
     *
     * This is used in question/backuplib.php
     */
    function backup($bf, $preferences, $question, $level = 6) {
        $status = true;
        $multichoices = get_records("question_turtipskupon", "question", $question, "id");
        //If there are multichoices
        if ($multichoices) {
            //Iterate over each multichoice
            foreach ($multichoices as $multichoice) {
                $status = fwrite($bf, start_tag("turtipskupon", $level, true));
                //Print multichoice contents
                fwrite($bf, full_tag("LAYOUT", $level + 1, false, $multichoice->layout));
                fwrite($bf, full_tag("ANSWERS", $level + 1, false, $multichoice->answers));
                fwrite($bf, full_tag("SINGLE", $level + 1, false, $multichoice->single));
                fwrite($bf, full_tag("SHUFFLEANSWERS", $level + 1, false, $multichoice->shuffleanswers));
                fwrite($bf, full_tag("CORRECTFEEDBACK", $level + 1, false, $multichoice->correctfeedback));
                fwrite($bf, full_tag("PARTIALLYCORRECTFEEDBACK", $level + 1, false, $multichoice->partiallycorrectfeedback));
                fwrite($bf, full_tag("INCORRECTFEEDBACK", $level + 1, false, $multichoice->incorrectfeedback));

                fwrite($bf, full_tag("QUESTIONSOUND", $level + 1, false, $multichoice->questionsound));
                fwrite($bf, full_tag("AUTOPLAY", $level + 1, false, $multichoice->autoplay));
                fwrite($bf, full_tag("QDIFFICULTY", $level + 1, false, $multichoice->qdifficulty));

                $status = fwrite($bf, end_tag("turtipskupon", $level, true));
            }

            //Now print question_answers
            //   $status = question_backup_answers($bf, $preferences, $question);
        }


        //Now print question_answers
        //  $status = backup_answers();
        //  $status = question_backup_answers_tur($bf,$preferences,$question);
        $answers = get_records("question_answers", "question", $question, "id");
        //If there are answers
        if ($answers) {
            print_object($answer);
            $status = $status && fwrite($bf, start_tag("ANSWERS", $level, true));
            //Iterate over each answer
            foreach ($answers as $answer) {
                $status = $status && fwrite($bf, start_tag("ANSWER", $level + 1, true));
                //Print answer contents
                fwrite($bf, full_tag("ID", $level + 2, false, $answer->id));
                fwrite($bf, full_tag("ANSWER_TEXT", $level + 2, false, $answer->answer));
                fwrite($bf, full_tag("FRACTION", $level + 2, false, $answer->fraction));
                fwrite($bf, full_tag("FEEDBACK", $level + 2, false, $answer->feedback));

                if ($answer->answersound == null) {
                    fwrite($bf, full_tag("ANSWERSOUND", $level + 2, false, ''));
                } else {
                    fwrite($bf, full_tag("ANSWERSOUND", $level + 2, false, $answer->answersound));
                }

                if ($answer->feedbacksound == null) {
                    fwrite($bf, full_tag("FEEDBACKSOUND", $level + 2, false, ''));
                } else {
                    fwrite($bf, full_tag("FEEDBACKSOUND", $level + 2, false, $answer->feedbacksound));
                }

                if ($answer->tur_answer_truefalse == null) {
                    fwrite($bf, full_tag("TUR_ANSWER_TRUEFALSE", $level + 2, false, ''));
                } else {
                    fwrite($bf, full_tag("TUR_ANSWER_TRUEFALSE", $level + 2, false, $answer->tur_answer_truefalse));
                }

                $status = $status && fwrite($bf, end_tag("ANSWER", $level + 1, true));
            }
            $status = $status && fwrite($bf, end_tag("ANSWERS", $level, true));
        }

        return $status;
    }

    /// RESTORE FUNCTIONS /////////////////

    /*
     * Restores the data in the question
     *
     * This is used in question/restorelib.php
     */
    function restore($old_question_id, $new_question_id, $info, $restore) {
        //print('restore' . '<br />');
        $status = true;

        // update the question_answers table with the additional information
        if ($new_question_id > 0) {
            $answers = $info['#']['ANSWERS']['0']['#']['ANSWER'];
            for ($i = 0; $i < sizeof($answers); $i++) {
                $ans_info = $answers[$i];

                $answer = new stdClass;
                $answer->question = $new_question_id;
                $answer->answer = backup_todb($ans_info['#']['ANSWER_TEXT']['0']['#']);
                $answer->fraction = backup_todb($ans_info['#']['FRACTION']['0']['#']);
                $answer->feedback = backup_todb($ans_info['#']['FEEDBACK']['0']['#']);
                $answer->answersound = backup_todb($ans_info['#']['ANSWERSOUND']['0']['#']);
                $answer->feedbacksound = backup_todb($ans_info['#']['FEEDBACKSOUND']['0']['#']);
                $answer->tur_answer_truefalse = backup_todb($ans_info['#']['TUR_ANSWER_TRUEFALSE']['0']['#']);

                $ansid = get_record('question_answers', 'question', $new_question_id, 'answer', $answer->answer, 'fraction', $answer->fraction, 'id');
                $answer->id = $ansid->id;

                $chckans = update_record(question_answers, $answer);
            }
        }

        //Get the multichoices array
        $multichoices = $info['#']['TURTIPSKUPON'];

        //Iterate over multichoices
        for ($i = 0; $i < sizeof($multichoices); $i++) {
            $mul_info = $multichoices[$i];

            //Now, build the question_multichoice record structure
            $multichoice = new stdClass;
            $multichoice->question = $new_question_id;
            $multichoice->layout = backup_todb($mul_info['#']['LAYOUT']['0']['#']);
            $multichoice->answers = backup_todb($mul_info['#']['ANSWERS']['0']['#']);
            $multichoice->single = backup_todb($mul_info['#']['SINGLE']['0']['#']);
            $multichoice->shuffleanswers = isset($mul_info['#']['SHUFFLEANSWERS']['0']['#']) ? backup_todb($mul_info['#']['SHUFFLEANSWERS']['0']['#']) : '';
            if (array_key_exists("CORRECTFEEDBACK", $mul_info['#'])) {
                $multichoice->correctfeedback = backup_todb($mul_info['#']['CORRECTFEEDBACK']['0']['#']);
            } else {
                $multichoice->correctfeedback = '';
            }
            if (array_key_exists("PARTIALLYCORRECTFEEDBACK", $mul_info['#'])) {
                $multichoice->partiallycorrectfeedback = backup_todb($mul_info['#']['PARTIALLYCORRECTFEEDBACK']['0']['#']);
            } else {
                $multichoice->partiallycorrectfeedback = '';
            }
            if (array_key_exists("INCORRECTFEEDBACK", $mul_info['#'])) {
                $multichoice->incorrectfeedback = backup_todb($mul_info['#']['INCORRECTFEEDBACK']['0']['#']);
            } else {
                $multichoice->incorrectfeedback = '';
            }

            if (array_key_exists("QUESTIONSOUND", $mul_info['#'])) {
                $multichoice->questionsound = backup_todb($mul_info['#']['QUESTIONSOUND']['0']['#']);
            } else {
                $multichoice->questionsound = '';
            }

            if (array_key_exists("AUTOPLAY", $mul_info['#'])) {
                $multichoice->autoplay = backup_todb($mul_info['#']['AUTOPLAY']['0']['#']);
            } else {
                $multichoice->autoplay = '';
            }

            if (array_key_exists("QDIFFICULTY", $mul_info['#'])) {
                $multichoice->qdifficulty = backup_todb($mul_info['#']['QDIFFICULTY']['0']['#']);
            } else {
                $multichoice->qdifficulty = '';
            }

            //We have to recode the answers field (a list of answers id)
            //Extracts answer id from sequence
            $answers_field = "";
            $in_first = true;
            $tok = strtok($multichoice->answers, ",");
            while ($tok) {
                //Get the answer from backup_ids
                $answer = backup_getid($restore->backup_unique_code, "question_answers", $tok);
                if ($answer) {
                    if ($in_first) {
                        $answers_field .= $answer->new_id;
                        $in_first = false;
                    } else {
                        $answers_field .= "," . $answer->new_id;
                    }
                }
                //check for next
                $tok = strtok(",");
            }
            //We have the answers field recoded to its new ids
            $multichoice->answers = $answers_field;

            //The structure is equal to the db, so insert the question_shortanswer
            $newid = insert_record("question_turtipskupon", $multichoice);

            //Do some output
            if (($i + 1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i + 1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }

            if (!$newid) {
                $status = false;
            }
        }
        return $status;
    }

    function restore_recode_answer($state, $restore) {
        $pos = strpos($state->answer, ':');
        $order = array();
        $responses = array();
        if (false === $pos) {
            // No order of answers is given, so use the default
            if ($state->answer) {
                $responses = explode(',', $state->answer);
            }
        } else {
            $order = explode(',', substr($state->answer, 0, $pos));
            if ($responsestring = substr($state->answer, $pos + 1)) {
                $responses = explode(',', $responsestring);
            }
        }
        if ($order) {
            foreach ($order as $key => $oldansid) {
                $answer = backup_getid($restore->backup_unique_code, "question_answers", $oldansid);
                if ($answer) {
                    $order[$key] = $answer->new_id;
                } else {
                    echo 'Could not recode turtipskupon answer id ' . $oldansid . ' for state ' . $state->oldid . '<br />';
                }
            }
        }
        if ($responses) {
            foreach ($responses as $key => $oldansid) {
                $answer = backup_getid($restore->backup_unique_code, "question_answers", $oldansid);
                if ($answer) {
                    $responses[$key] = $answer->new_id;
                } else {
                    echo 'Could not recode turtipskupon response answer id ' . $oldansid . ' for state ' . $state->oldid . '<br />';
                }
            }
        }
        return implode(',', $order) . ':' . implode(',', $responses);
    }

    /**
     * Decode links in question type specific tables.
     * @return bool success or failure.
     */
    function decode_content_links_caller($questionids, $restore, &$i) {
        $status = true;

        // Decode links in the question_turtipskupon table.
        if ($multichoices = get_records_list('question_turtipskupon', 'question', implode(',', $questionids), '', 'id, correctfeedback, partiallycorrectfeedback, incorrectfeedback')) {
            foreach ($multichoices as $multichoice) {
                $correctfeedback = restore_decode_content_links_worker($multichoice->correctfeedback, $restore);
                $partiallycorrectfeedback = restore_decode_content_links_worker($multichoice->partiallycorrectfeedback, $restore);
                $incorrectfeedback = restore_decode_content_links_worker($multichoice->incorrectfeedback, $restore);
                if ($correctfeedback != $multichoice->correctfeedback || $partiallycorrectfeedback != $multichoice->partiallycorrectfeedback || $incorrectfeedback != $multichoice->incorrectfeedback) {
                    $subquestion->correctfeedback = addslashes($correctfeedback);
                    $subquestion->partiallycorrectfeedback = addslashes($partiallycorrectfeedback);
                    $subquestion->incorrectfeedback = addslashes($incorrectfeedback);
                    if (!update_record('question_turtipskupon', $multichoice)) {
                        $status = false;
                    }
                }

                // Do some output.
                if (++$i % 5 == 0 && !defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if ($i % 100 == 0) {
                        echo "<br />";
                    }
                    backup_flush(300);
                }
            }
        }

        return $status;
    }

    function export_to_xml($question, $format, $extra = null) {
        $expout = '';
        $expout .= "    <questionsound>" . $question->options->questionsound . "</questionsound>\n";
        $expout .= "    <correctfeedback>" . $format->writetext($question->options->correctfeedback, 3) . "</correctfeedback>\n";
        $expout .= "    <partiallycorrectfeedback>" . $format->writetext($question->options->partiallycorrectfeedback, 3) . "</partiallycorrectfeedback>\n";
        $expout .= "    <incorrectfeedback>" . $format->writetext($question->options->incorrectfeedback, 3) . "</incorrectfeedback>\n";
        $expout .= "    <answernumbering>{$question->options->answernumbering}</answernumbering>\n";



        // find the number of correct answers
        $correctcount = 0;
        foreach ($question->options->answers as $answer) {
            if ($answer->tur_answer_truefalse != 0) {
                $correctcount++;
            }
        }


        foreach ($question->options->answers as $answer) {

            print_object($answer);


            // calculate the answer fraction
            if ($answer->tur_answer_truefalse != 0) {
                $percent = ((1 / $correctcount) * 100);
            } else {
                $percent = 0;
            }

            $expout .= "      <answer fraction=\"$percent\">\n";
            $expout .= "      <answerstatus>" . $answer->tur_answer_truefalse . "</answerstatus>\n";
            $expout .= $format->writetext($answer->answer, 4, false);
            $expout .= "      <answersound>" . $answer->answersound . "</answersound>\n";
            $expout .= "      <feedback>\n";
            $expout .= $format->writetext($answer->feedback, 5, false);
            $expout .= "          <feedbacksound>" . $answer->feedbacksound . "</feedbacksound>\n";
            $expout .= "      </feedback>\n";
            $expout .= "    </answer>\n";
        }
        return $expout;
    }

    function import_from_xml($data, $question, $format, $extra = null) {
        // check that this is for us
        $qtype = $data['@']['type'];
        if ($qtype != 'turtipskupon') {
            return false;
        }

        // get common parts
        $qo = $format->import_headers($data);

        // 'header' parts particular to multichoice
        $qo->qtype = 'turtipskupon';
        $qo->single = $format->getpath($data, array('#', 'single', 0, '#'), '', true);
        $qo->answernumbering = $format->getpath($data, array('#', 'answernumbering', 0, '#'), 'abc');
        $qo->correctfeedback = $format->getpath($data, array('#', 'correctfeedback', 0, '#', 'text', 0, '#'), '', true);
        $qo->partiallycorrectfeedback = $format->getpath($data, array('#', 'partiallycorrectfeedback', 0, '#', 'text', 0, '#'), '', true);
        $qo->incorrectfeedback = $format->getpath($data, array('#', 'incorrectfeedback', 0, '#', 'text', 0, '#'), '', true);

        // Custom fields
        $autoplay = $format->getpath($data, array('#', 'autoplay', 0, '#'), '', true);
        $qo->autoplay = $autoplay;

        $qdifficulty = $format->getpath($data, array('#', 'qdifficulty', 0, '#'), '', true);
        $qo->qdifficulty = $qdifficulty;

        $questionsound = $format->getpath($data, array('#', 'questionsound', 0, '#'), '', true);
        $qo->questionsound = $questionsound;

        // run through the answers
        $answers = $data['#']['answer'];
        $a_count = 0;
        foreach ($answers as $answer) {
            $ans = $format->import_answer($answer);

            $feedbacksound = $format->getpath($answer, array('#', 'feedback', 0, '#', 'feedbacksound', 0, '#'), '', true);
            $answersound = $format->getpath($answer, array('#', 'answersound', 0, '#'), '', true);
            $answerstatus = $format->getpath($answer, array('#', 'answerstatus', 0, '#'), '', true);

            $qo->answer[$a_count] = $ans->answer;
            $qo->tur_answer_truefalse[$a_count] = $answerstatus;
            $qo->fraction[$a_count] = $ans->fraction;
            $qo->feedback[$a_count] = $ans->feedback;
            $qo->feedbacksound[$a_count] = $feedbacksound;
            $qo->answersound[$a_count] = $answersound;
            ++$a_count;
        }
        return $qo;
    }

    function get_numbering_styles() {
        return array('abc', 'ABCD', '123', 'none');
    }

}

//// END OF CLASS ////
//////////////////////////////////////////////////////////////////////////
//// INITIATION - Without this line the question type is not in use... ///
//////////////////////////////////////////////////////////////////////////
question_register_questiontype(new question_turtipskupon_qtype());
?>