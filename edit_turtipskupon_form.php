<?php
/**
 * Defines the editing form for the turtipskupon question type.
 *
 * @package questions
 */
define('LOCAL_NUMANS_START',10); 
class question_edit_turtipskupon_form extends question_edit_form {
     /*
      * 2369:     function view_array($array_in)
      * 2397:     function print_array($array_in)
      * 2412:     function debug($var="",$brOrHeader=0)
     */
    function definition_inner(&$mform) {
      //print('definition_inner' . '<br />');
      global $COURSE, $CFG, $QTYPES;
		  
      $mform->removeElement('questiontext');
      $mform->removeElement('questiontextformat');
      /* Apply question difficulties*/
      $question_difficulties = array();
      $question_difficulties[0] = get_string('q_easy1', 'qtype_turprove');
      $question_difficulties[1] = get_string('q_easy2', 'qtype_turprove');
      $question_difficulties[2] = get_string('q_easy3', 'qtype_turprove');
      $question_difficulties[3] = get_string('q_medium1', 'qtype_turprove');
      $question_difficulties[4] = get_string('q_medium2', 'qtype_turprove');
      $question_difficulties[5] = get_string('q_medium3', 'qtype_turprove');
      $question_difficulties[6] = get_string('q_hard1', 'qtype_turprove');
      $question_difficulties[7] = get_string('q_hard2', 'qtype_turprove');
      $question_difficulties[8] = get_string('q_hard3', 'qtype_turprove');
      $mform->addElement('select', 'qdifficulty', get_string('qdifficulty', 'qtype_turprove'), $question_difficulties);
      /**/
      $mform->addElement('hidden', 'questiontext', get_string('questiontext', 'quiz'),array('rows' => 15, 'course' => $this->coursefilesid));
      $mform->addElement('hidden', 'questiontextformat', get_string('format'));
      /*
        $mform->addElement('htmleditor', 'questiontext', get_string('questiontext', 'quiz'),array('rows' => 15, 'course' => $this->coursefilesid));
        $mform->setType('questiontext', PARAM_RAW);
        $mform->setHelpButton('questiontext', array(array('questiontext', get_string('questiontext', 'quiz'), 'quiz'), 'richtext'), false, 'editorhelpbutton');
        $mform->addElement('format', 'questiontextformat', get_string('format'));
      */
      //  $mform->addElement('advcheckbox', 'autoplay', get_string('autoplay', 'qtype_turtipskupon'), null, null, array(0,1));
	    $mform->addElement('hidden', 'autoplay', get_string('autoplay', 'qtype_turtipskupon'), null, null, array(0,1));
	    
      $mform->removeElement('defaultgrade'); // fjerne 'standardkarakter for sp�rgsm�l' som tidligere er blevet tilf�jet
		  $mform->addElement('hidden', 'defaultgrade', get_string('defaultgrade', 'quiz'), array('size' => 3)); // og s� tilf�jer vi det igen, men denne gang som skjult felt.
		  $mform->setType('defaultgrade', PARAM_INT);
		  $mform->setDefault('defaultgrade', 1);

  		$mform->removeElement('penalty');
  		$mform->addElement('hidden', 'penalty', get_string('penaltyfactor', 'quiz'), array('size' => 3));
  		$mform->setType('penalty', PARAM_NUMBER);
  		$mform->addRule('penalty', null, 'required', null, 'client');
  		$mform->setDefault('penalty', 0);
  		$mform->removeElement('generalfeedback');
      $mform->removeElement('image');

/*
      if(isset($CFG->turimage)) {
        $mform->addElement('choosecoursefile', 'image', get_string('imagedisplay', 'quiz'), array('courseid'=>$CFG->turimage,'height'=>500, 'width'=>750, 'options'=>'none'));
      } else {
        make_upload_directory($this->coursefilesid);    // Just in case
        $coursefiles = get_directory_list("$CFG->dataroot/$this->coursefilesid", $CFG->moddata);
        foreach ($coursefiles as $filename) {
          if (mimeinfo("icon", $filename) == "image.gif") {
            $images["$filename"] = $filename;
          }
        }
        if (empty($images)) {
            $mform->addElement('static', 'image', get_string('imagedisplay', 'quiz'), get_string('noimagesyet'));
        } else {
            $mform->addElement('select', 'image', get_string('imagedisplay', 'quiz'), array_merge(array(''=>get_string('none')), $images));
        }
      }
*/
      //$menu = array(get_string('answersingleno', 'qtype_turtipskupon'), get_string('answersingleyes', 'qtype_turtipskupon'));
      //$mform->addElement('select', 'single', get_string('answerhowmany', 'qtype_turtipskupon'), $menu);
      $mform->addElement( 'hidden', 'single', 1 );
      $mform->setDefault('single', 0);
      $mform->addElement( 'hidden', 'shuffleanswers', 1 );
      $mform->addElement( 'hidden', 'answernumbering', 1 );
      /*
      $numberingoptions = $QTYPES[$this->qtype()]->get_numbering_styles();
      $menu = array();
      foreach ($numberingoptions as $numberingoption) {
          $menu[$numberingoption] = get_string('answernumbering' . $numberingoption, 'qtype_multichoice');
      }
      $mform->addElement('select', 'answernumbering', get_string('answernumbering', 'qtype_multichoice'), $menu);
      */
      $mform->setDefault('answernumbering', 'none');
      /*
      if(isset($CFG->tursound)) {
        $mform->addElement('choosecoursefile', 'questionsound', get_string('questionsound', 'qtype_turtipskupon'), array('courseid'=>$CFG->tursound,'height'=>500, 'width'=>750, 'options'=>'none'));
      } else {
        $mform->addElement('choosecoursefile', 'questionsound', get_string('questionsound', 'qtype_turtipskupon'));
      }
      */
      $mform->addElement('hidden', 'questionsound', '');
      $creategrades = get_grade_options();
      
      //t3lib_div::debug($creategrades);
      
      $gradeoptions = $creategrades->gradeoptionsfull;
      $repeated = array();
      $repeated[] =& $mform->createElement('header', 'choicehdr', get_string('choiceno', 'qtype_turtipskupon', '{no}'));
      $repeated[] =& $mform->createElement('htmleditor', 'answer', get_string('questionanswer', 'qtype_turtipskupon'));
      // Array til moodle-selectbox [value]-> 'label'

      $turgradeoptions = array();
      $turgradeoptions['0.1'] = '10%'; 

      //t3lib_div::print_array($turgradeoptions, '$gradeoptions');
      //$repeated[] =& $mform->createElement('select', 'fraction', get_string('grade'), $turgradeoptions);
      
      //  $repeated[] =& $mform->createElement('select', 'fraction', get_string('grade'), $gradeoptions);
      $repeated[] =& $mform->createElement('hidden', 'fraction', '0.1');
      $repeated[] =& $mform->createElement('select', 'tur_answer_truefalse', get_string('correctanswer', 'qtype_truefalse'), array(0 => 'Nej', 1 => 'Ja'));
      //  $repeated[] =& $mform->createElement('htmleditor', 'feedback', get_string('feedback', 'qtype_turtipskupon'));
      $repeated[] =& $mform->createElement('htmleditor', 'feedback', get_string('feedback', 'qtype_turtipskupon'));


/* MPL */
        if(isset($CFG->tursound)) {
          $repeated[] =& $mform->createElement('choosecoursefile', 'answersound', get_string('answersound', 'qtype_turtipskupon'), array('courseid'=>$CFG->tursound,'height'=>500, 'width'=>750, 'options'=>'none'));
        } else {
          $repeated[] =& $mform->createElement('choosecoursefile', 'answersound', get_string('answersound', 'qtype_turtipskupon'));
        }
/* MPL */ 

      if(isset($CFG->tursound)) {
        $repeated[] =& $mform->createElement('choosecoursefile', 'feedbacksound', get_string('feedbacksound', 'qtype_turtipskupon'), array('courseid'=>$CFG->tursound,'height'=>500, 'width'=>750, 'options'=>'none'));
      } else {
        $repeated[] =& $mform->createElement('choosecoursefile', 'feedbacksound', get_string('feedbacksound', 'qtype_turtipskupon'));        
      }
      if (isset($this->question->options)){
          $countanswers = count($this->question->options->answers);
      } else {
          $countanswers = 0;
      }
      $repeatsatstart = LOCAL_NUMANS_START;
      //$repeatsatstart = (LOCAL_NUMANS_START > ($countanswers + QUESTION_NUMANS_ADD)) ? LOCAL_NUMANS_START : ($countanswers + QUESTION_NUMANS_ADD);
      $repeatedoptions = array();
//      $repeatedoptions['fraction']['default'] = -1;
      $mform->setType('questionsound', PARAM_RAW);
      $mform->setType('autoplay', PARAM_INT);
      $mform->setType('answersound', PARAM_RAW);
      $mform->setType('feedbacksound', PARAM_RAW);
      $this->repeat_elements($repeated, $repeatsatstart, $repeatedoptions, 'noanswers', 'addanswers', QUESTION_NUMANS_ADD, get_string('addmorechoiceblanks', 'qtype_turtipskupon'));
      $mform->removeElement('addanswers');
      //  $mform->setDefault('fraction', 0,1);
}

function tur_setcustomfraction ($numAnswers) {
      $turfraction = 0;
      /*
          $grades = array(
        1,
        0.9,
        0.8,
        0.75,
        0.70,
        0.66666,
        0.60,
        0.50,
        0.40,
        0.33333,
        0.30,
        0.25,
        0.20,
        0.16666,
        0.142857,
        0.125,
        0.11111,
        0.10,
        0.05,
        0);
      */
      switch($numAnswers) {
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
        case 6:
        $turfraction = 0.16666;
        break;
        case 10:
        $turfraction = 0.1;
        break;
        default:
        $turfraction = round((1/$numAnswers),5);
        //print ($tempFrac . '    ' .$numAnswers . '-> Illegal number!');
      }
      return $turfraction;
    }


  function set_data($question) {
      //print('set_data' . '<br />');
      if (isset($question->options)){
          $answers = $question->options->answers;
          if (count($answers)) {
              $key = 0;
              foreach ($answers as $answer){
                  $default_values['answer['.$key.']'] = $answer->answer;
                  $default_values['answersound['.$key.']'] = $answer->answersound;
                  //  $default_values['fraction['.$key.']'] = $answer->fraction;
                  $default_values['fraction['.$key.']'] = $this->tur_setcustomfraction(count($answers));
                  $default_values['tur_answer_truefalse['.$key.']'] = $answer->tur_answer_truefalse;
                  $default_values['feedback['.$key.']'] = $answer->feedback;
                  $default_values['feedbacksound['.$key.']'] = $answer->feedbacksound;
                  $key++;
              }
          }
          $default_values['single'] =  $question->options->single;
          $default_values['shuffleanswers'] =  $question->options->shuffleanswers;
          $default_values['qdifficulty'] =  $question->options->qdifficulty;
  	      $default_values['autoplay'] =  $question->options->autoplay;
          $default_values['correctfeedback'] =  $question->options->correctfeedback;
          $default_values['partiallycorrectfeedback'] =  $question->options->partiallycorrectfeedback;
          $default_values['incorrectfeedback'] =  $question->options->incorrectfeedback;
          $default_values['questionsound'] =  $question->options->questionsound;
          $question = (object)((array)$question + $default_values);
      }
      parent::set_data($question);
  }
  
  function qtype() {
      return 'turtipskupon';
  }
    
  /*http://snippets.dzone.com/posts/show/2776*/
  function file_extension($filename) {
    $path_info = pathinfo($filename);
    $allowed = $path_info['extension'];
    if($allowed == 'mp3') {
      return true;
    }
   return false;
  }

  function validation($data){
    //  print('validation' . '<br />');
    $errors = array();
    $answers = $data['answer'];
    $answercount = 0;
    $numAnswers = 0;
    $totalfraction = 0;
    $maxfraction = -1;
    $answersounds = array();
    $feedbacksounds = array();
    $answercount = 0;
    $tur_num_true = 0;

    //  t3lib_div::debug($answers);

    foreach ($answers as $key => $answer){
        $trimmedanswer = trim($answer);
        if (!empty($trimmedanswer)){
            $numAnswers++;
        }
    }
    foreach ($answers as $key => $answer){
      $trimmedanswer = trim($answer);
      if (!empty($trimmedanswer)){
        $answercount++;
      }
      if ($answer != '') {
        if ($data['fraction'][$key] > 0) {
          $data['fraction'][$key] = $this->tur_setcustomfraction($numAnswers);
          $totalfraction += $data['fraction'][$key]; // l�gger alle fraktioner sammen
        }
        if ($data['fraction'][$key] > $maxfraction) {
          $maxfraction = $data['fraction'][$key];
        }
      }
      $answersounds[$key] = $data['answersound['.$key.']'];
      $feedbacksounds[$key] = $data['feedbacksound['.$key.']'];
    } //foreach
    foreach ($answersounds as $key => $value) {
      if($value !='' && !$this->file_extension($value)) {
        $errors['answersound['.$key.']'] = get_string('mp3only', 'qtype_turtipskupon', 2);
      }
    }
    /* Feedbacksound must be mp3 */
    foreach ($feedbacksounds as $key => $value) {
      if($value !='' && !$this->file_extension($value)) {
        $errors['feedbacksound['.$key.']'] = get_string('mp3only', 'qtype_turtipskupon', 2);
      }
    }
    
    /* Questionsound must be mp3 */
    if($data['questionsound'] <> '') {
      if(!$this->file_extension($data['questionsound'])) {
        $errors['questionsound'] = get_string('mp3only', 'qtype_turtipskupon', 2);
      }
    }
    if ($answercount==0){
        $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_turtipskupon', 2);
        $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_turtipskupon', 2);
    } elseif ($answercount==1){
        $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_turtipskupon', 2);
    }
    /// Perform sanity checks on fractional grades



    if ($data['single']) {
        if ($maxfraction != 1) {
            $maxfraction = $maxfraction * 100;
            $errors['fraction[0]'] = get_string('errfractionsnomax', 'qtype_turtipskupon', $maxfraction);
        }
    } else {
        $totalfraction = round($totalfraction,2);
        //  t3lib_div::debug($maxfraction, '$maxfraction');
        //  t3lib_div::debug($totalfraction, '$totalfraction');
        if ($totalfraction != 1) {
            $totalfraction = $totalfraction * 100;
		        $errors['fraction[0]'] = get_string('errfractionsaddwrong', 'qtype_turtipskupon', $totalfraction);
        }
    }
   return $errors;
  }
}
?>