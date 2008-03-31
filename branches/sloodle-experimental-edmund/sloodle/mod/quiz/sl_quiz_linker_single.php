<?php
//sleep(rand(0,20)); // sleep to test what happens to the script if the response doesn't come
    // Sloodle quiz linker
    // Allows in-world objects to interact with Moodle quizzes
    // Part of the Sloodle project (www.sloodle.org)
    //
    // Copyright (c) 2006-7 Sloodle
    // Released under the GNU GPL
    //
    // Contributors:
    //   (various Moodle authors) - original quiz functionality
    //   Edmund Edgar - specialized for Sloodle
    //   Peter R. Bloomfield - updated (partially) to use new API and communications format
    //

    // This script is expected to be requested by an in-world object.
    // The following parameters are required:
    //
    //   sloodlepwd = prim password to authenticate the request
    //   sloodleuuid = UUID of the avatar making the request (optional if 'sloodleavname' is specified)
    //   sloodleavname = avatar name of the user making the request (optional if 'sloodleuuid' is specified)
    //
    // The following parameters are optional, depending on the mode we are in:
    //
    //   q = ID of the quiz course module instance
    //   id = ID of the course which this request relates to
    //   page = ?
    //   questionids = ?
    //   finishattempt = ?
    //   timeup = true if submission was by timer
    //    forcenew = teacher has requested a new preview
    //    action = ??
    


/**
* This page prints a particular instance of quiz
*
* @version $Id: attempt.php,v 1.87.2.5 2006/08/10 15:31:00 skodak Exp $
* @author Martin Dougiamas and many others. This has recently been completely
*         rewritten by Alex Smith, Julian Sedding and Gustav Delius as part of
*         the Serving Mathematics project
*         {@link http://maths.york.ac.uk/serving_maths}
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package quiz
*
* Sloodlized by Edmund Edgar, 2006-11
* Changed to give responses to an lsl script
*
*/

    require_once('../../config.php');
    require_once(SLOODLE_DIRROOT.'/sl_debug.php');
    require_once(SLOODLE_DIRROOT.'/lib/sl_lsllib.php');

    // We're using a copy of the locallib.php file from the quiz module itself.
    // Linking directly to it seems to break - it would be better to find out why and see if there's a way we can reference it directly.
    //2007101509 is the 1.9 release, which has a new locallib file
    if ( $CFG->version >= 2007101509 ) {
       require_once("locallib.1.9.php");
    } else {
       require_once("locallib.php");
    }

    // Authenticate the request and login the user
    $lsl = new SloodleLSLHandler();
    $lsl->request->process_request_data();
    $lsl->request->authenticate_request();
    $lsl->login_by_request();
    
    $limittoquestion = optional_param('ltq',0,PARAM_INT);

	$output = array();

    $courseid = optional_param('courseid', 0, PARAM_INT);               // Course Module ID
    $id = optional_param('id', 0, PARAM_INT);               // Course Module ID
    $q = optional_param('q', 0, PARAM_INT);                 // or quiz ID
    $page = optional_param('page', 0, PARAM_INT);
    $questionids = optional_param('questionids', '');
    $finishattempt = optional_param('finishattempt', 0, PARAM_BOOL);
    $timeup = optional_param('timeup', 0, PARAM_BOOL); // True if form was submitted by timer.
    $forcenew = optional_param('forcenew', false, PARAM_BOOL); // Teacher has requested new preview

	$isnotify = ( optional_param( 'action', false, PARAM_RAW ) == 'notify' ) ;

    // remember the current time as the time any responses were submitted
    // (so as to make sure students don't get penalized for slow processing on this page)
    $timestamp = time();

    // We treat automatically closed attempts just like normally closed attempts
    if ($timeup) {
        $finishattempt = 1;
    }

	if ( ($courseid != 0) && ($id == 0) && ($q == 0) ) {
		// fetch a quiz with the id for the course
		 if (! $mod = get_record("modules", "name", "quiz") ) {
			 $lsl->response->quick_output(-712, 'MODULE_INSTANCE', 'Could not find quiz module', FALSE);
			 exit;
		 }

		 //if (! $coursemod = get_record("course_modules", "courseid", $courseid, "module", $mod->id)) {
		 if (! $coursemod = get_record("course_modules", "course", $courseid, "module", $mod->id)) {
			 $lsl->response->quick_output(-712, 'MODULE_INSTANCE', 'Could not find course module instance', FALSE);
			 exit;
		 }

		 $id = $coursemod->id;

	}
    if ($id) {
        if (! $cm = get_coursemodule_from_id('quiz', $id)) {
            $lsl->response->quick_output(-701, 'MODULE_INSTANCE', 'Identified module instance is not a quiz', FALSE);
            exit();
        }

        if (! $course = get_record("course", "id", $cm->course)) {
            $lsl->response->quick_output(-701, 'MODULE_INSTANCE', 'Quiz module is misconfigured', FALSE);
            exit();
        }

        if (! $quiz = get_record("quiz", "id", $cm->instance)) {
            $lsl->response->quick_output(-712, 'MODULE_INSTANCE', 'Part of quiz module instance is missing', FALSE);
            exit();
        }

    } else {
        if (! $quiz = get_record("quiz", "id", $q)) {
            $lsl->response->quick_output(-712, 'MODULE_INSTANCE', 'Could not find quiz module', FALSE);
            exit();
        }
        if (! $course = get_record("course", "id", $quiz->course)) {
            $lsl->response->quick_output(-712, 'MODULE_INSTANCE', 'Part of quiz module instance is missing', FALSE);
            exit();
        }
        if (! $cm = get_coursemodule_from_instance("quiz", $quiz->id, $course->id)) {
            $lsl->response->quick_output(-701, 'MODULE_INSTANCE', 'Unable to resolve course module instance', FALSE);
            exit();
        }
    }

    //require_login($course->id, false, $cm); // I don't think this is a good idea! -PRB


// Get number for the next or unfinished attempt
    if(!$attemptnumber = (int)get_field_sql('SELECT MAX(attempt)+1 FROM ' .
     "{$CFG->prefix}quiz_attempts WHERE quiz = '{$quiz->id}' AND " .
     "userid = '{$USER->id}' AND timefinish > 0 AND preview != 1")) {
        $attemptnumber = 1;
    }

    $strattemptnum = get_string('attempt', 'quiz', $attemptnumber);
    $strquizzes = get_string("modulenameplural", "quiz");

	// course id: $course->id
	// course id: $course->id
	// course name: $quiz->id
	// attempts: $quiz->attempts
	$output[] = array('course',$course->id,$course->name);

    $numberofpreviousattempts = count_records_select('quiz_attempts', "quiz = '{$quiz->id}' AND " .
        "userid = '{$USER->id}' AND timefinish > 0 AND preview != 1");
    if ($quiz->attempts and $numberofpreviousattempts >= $quiz->attempts) {
        $lsl->response->quick_output(-701, 'QUIZ', 'You do not have any attempts left', FALSE);
        exit();
    }

/// Check subnet access
    if ($quiz->subnet and !address_in_subnet(getremoteaddr(), $quiz->subnet)) {
        $lsl->response->quick_output(-1, 'MISC', 'A subnet error occurred', FALSE);
        exit();
    }

/// Check password access
    if ($quiz->password) {
        $lsl->response->quick_output(-701, 'QUIZ', 'Quiz requires password - not supported by Sloodle', FALSE);
        exit();
	}

    if ($quiz->delay1 or $quiz->delay2) {
        //quiz enforced time delay
        if ($attempts = quiz_get_user_attempts($quiz->id, $USER->id)) {
            $numattempts = count($attempts);
        } else {
            $numattempts = 0;
        }
        $timenow = time();
        $lastattempt_obj = get_record_select('quiz_attempts', "quiz = $quiz->id AND attempt = $numattempts AND userid = $USER->id", 'timefinish');
        if ($lastattempt_obj) {
            $lastattempt = $lastattempt_obj->timefinish;
        }
        if ($numattempts == 1 && $quiz->delay1) {
            if ($timenow - $quiz->delay1 < $lastattempt) {
                $lsl->response->quick_output(-701, 'QUIZ', 'You need to wait until the time delay has expired.', FALSE);
                exit();
            }
        } else if($numattempts > 1 && $quiz->delay2) {
            if ($timenow - $quiz->delay2 < $lastattempt) {
                $lsl->response->quick_output(-701, 'QUIZ', 'You need to wait until the time delay has expired.', FALSE);
                exit();
            }
        }
    }

//	sloodle_prim_render_output($output);
//	exit;

/// Load attempt or create a new attempt if there is no unfinished one

    $attempt = get_record('quiz_attempts', 'quiz', $quiz->id,
     'userid', $USER->id, 'timefinish', 0);

    $newattempt = false;
    if (!$attempt) {
        $newattempt = true;
        // Start a new attempt and initialize the question sessions
        $attempt = quiz_create_attempt($quiz, $attemptnumber);
        // If this is an attempt by a teacher mark it as a preview
        // Save the attempt
        if (!$attempt->id = insert_record('quiz_attempts', $attempt)) {
            $lsl->response->quick_output(-701, 'QUIZ', 'Could not create new attempt.', FALSE);
            exit();
        }
        // make log entries
		add_to_log($course->id, 'quiz', 'attempt',
					   "review.php?attempt=$attempt->id",
					   "$quiz->id", $cm->id);
    } else {
        // log continuation of attempt only if some time has lapsed
        if (($timestamp - $attempt->timemodified) > 600) { // 10 minutes have elapsed
             add_to_log($course->id, 'quiz', 'continue attemp', // this action used to be called 'continue attempt' but the database field has only 15 characters
                           "review.php?attempt=$attempt->id",
                           "$quiz->id", $cm->id);
        }
    }
    if (!$attempt->timestart) { // shouldn't really happen, just for robustness
        $attempt->timestart = time();
    }

/// Load all the questions and states needed by this script

    // list of questions needed by page
    $pagelist = quiz_questions_on_page($attempt->layout, $page);

    if ($newattempt) {
        $questionlist = quiz_questions_in_quiz($attempt->layout);
    } else {
        $questionlist = $pagelist;
    }

    // add all questions that are on the submitted form
    if ($questionids) {
        $questionlist .= ','.$questionids;
    }

    if (!$questionlist) {
        $lsl->response->quick_output(-701, 'QUIZ', 'No questions found.', FALSE);
        exit();
    }

    $sql = "SELECT q.*, i.grade AS maxgrade, i.id AS instance".
           "  FROM {$CFG->prefix}question q,".
           "       {$CFG->prefix}quiz_question_instances i".
           " WHERE i.quiz = '$quiz->id' AND q.id = i.question".
           "   AND q.id IN ($questionlist)";

    // Load the questions
    if (!$questions = get_records_sql($sql)) {
        $lsl->response->quick_output(-701, 'QUIZ', 'No questions found.', FALSE);
        exit();
    }

    // Load the question type specific information
    if (!get_question_options($questions)) {
        $lsl->response->quick_output(-701, 'QUIZ', 'Could not load question options.', FALSE);
        exit();
    }

    // Restore the question sessions to their most recent states
    // creating new sessions where required
    if (!$states = get_question_states($questions, $quiz, $attempt)) {
        $lsl->response->quick_output(-701, 'QUIZ', 'Could not restore questions sessions.', FALSE);
        exit();
    }

    // Save all the newly created states
    if ($newattempt) {
        foreach ($questions as $i => $question) {
            save_question_session($questions[$i], $states[$i]);
        }
    }

    // If the new attempt is to be based on a previous attempt copy responses over
    if ($newattempt and $attempt->attempt > 1 and $quiz->attemptonlast and !$attempt->preview) {
        // Find the previous attempt
        if (!$lastattemptid = get_field('quiz_attempts', 'uniqueid', 'quiz', $attempt->quiz, 'userid', $attempt->userid, 'attempt', $attempt->attempt-1)) {
            $lsl->response->quick_output(-701, 'QUIZ', 'Could not find previous attempt to build on.', FALSE);
            exit();
        }
        // For each question find the responses from the previous attempt and save them to the new session
        foreach ($questions as $i => $question) {
            // Load the last graded state for the question
            $statefields = 'n.questionid as question, s.*, n.sumpenalty';
            $sql = "SELECT $statefields".
                   "  FROM {$CFG->prefix}question_states s,".
                   "       {$CFG->prefix}question_sessions n".
                   " WHERE s.id = n.newgraded".
                   "   AND n.attemptid = '$lastattemptid'".
                   "   AND n.questionid = '$i'";
            if (!$laststate = get_record_sql($sql)) {
                // Only restore previous responses that have been graded
                continue;
            }
            // Restore the state so that the responses will be restored
            restore_question_state($questions[$i], $laststate);
            // prepare the previous responses for new processing
            $action = new stdClass;
            $action->responses = $laststate->responses;
            $action->timestamp = $laststate->timestamp;
            $action->event = QUESTION_EVENTOPEN;

            // Process these responses ...
            question_process_responses($questions[$i], $states[$i], $action, $quiz, $attempt);

            // Fix for Bug #5506: When each attempt is built on the last one,
            // preserve the options from any previous attempt. 
            if ( isset($laststate->options) ) {
                $states[$i]->options = $laststate->options;
            }

            // ... and save the new states
            save_question_session($questions[$i], $states[$i]);
        }
    }

/// Process form data /////////////////////////////////////////////////

    if ($isnotify) {

		$responses = (object)$_GET; // GET version of data_submitted (see lib/weblib) used in original web version

        // set the default event. This can be overruled by individual buttons.
        $event = (array_key_exists('markall', $responses)) ? QUESTION_EVENTSUBMIT :
         ($finishattempt ? QUESTION_EVENTCLOSE : QUESTION_EVENTSAVE);

        // Unset any variables we know are not responses
        unset($responses->id);
        unset($responses->q);
        unset($responses->oldpage);
        unset($responses->newpage);
        unset($responses->review);
        unset($responses->questionids);
        unset($responses->saveattempt); // responses get saved anway
        unset($responses->finishattempt); // same as $finishattempt
        unset($responses->markall);
        unset($responses->forcenewattempt);

        // extract responses
        // $actions is an array indexed by the questions ids
        $actions = question_extract_responses($questions, $responses, $event);

        // Process each question in turn

        $questionidarray = explode(',', $questionids);
        foreach($questionidarray as $i) {
            if (!isset($actions[$i])) {
                $actions[$i]->responses = array('' => '');
            }
            $actions[$i]->timestamp = $timestamp;
            question_process_responses($questions[$i], $states[$i], $actions[$i], $quiz, $attempt);
            save_question_session($questions[$i], $states[$i]);
        }

        $attempt->timemodified = $timestamp;

    // We have now finished processing form data
    }


/// Finish attempt if requested
    if ($finishattempt) {

        // Set the attempt to be finished
        $attempt->timefinish = $timestamp;

        // Find all the questions for this attempt for which the newest
        // state is not also the newest graded state
        if ($closequestions = get_records_select('question_sessions',
         "attemptid = $attempt->uniqueid AND newest != newgraded", '', 'questionid, questionid')) {

            // load all the questions
            $closequestionlist = implode(',', array_keys($closequestions));
            $sql = "SELECT q.*, i.grade AS maxgrade, i.id AS instance".
                   "  FROM {$CFG->prefix}question q,".
                   "       {$CFG->prefix}quiz_question_instances i".
                   " WHERE i.quiz = '$quiz->id' AND q.id = i.question".
                   "   AND q.id IN ($closequestionlist)";
            if (!$closequestions = get_records_sql($sql)) {
                $lsl->response->quick_output(-701, 'QUIZ', 'Questions missing.', FALSE);
                exit();
            }

            // Load the question type specific information
            if (!get_question_options($closequestions)) {
                $lsl->response->quick_output(-701, 'QUIZ', 'Could not load question options.', FALSE);
                exit();
            }

            // Restore the question sessions
            if (!$closestates = get_question_states($closequestions, $quiz, $attempt)) {
                $lsl->response->quick_output(-701, 'QUIZ', 'Could not restore question sessions.', FALSE);
                exit();
            }

            foreach($closequestions as $key => $question) {
                $action->event = QUESTION_EVENTCLOSE;
                $action->responses = $closestates[$key]->responses;
                $action->timestamp = $closestates[$key]->timestamp;
                question_process_responses($question, $closestates[$key], $action, $quiz, $attempt);
                            save_question_session($question, $closestates[$key]);
            }
        }
        add_to_log($course->id, 'quiz', 'close attempt',
                           "review.php?attempt=$attempt->id",
                           "$quiz->id", $cm->id);
    }

/// Update the quiz attempt and the overall grade for the quiz
    if ($responses || $finishattempt) {
        if (!update_record('quiz_attempts', $attempt)) {
            $lsl->response->quick_output(-701, 'QUIZ', 'Failed to save current quiz attempt.', FALSE);
            exit();
        }
        if (($attempt->attempt > 1 || $attempt->timefinish > 0) and !$attempt->preview) {
            quiz_save_best_grade($quiz);
        }
    }

/// Check access to quiz page

    // check the quiz times
	//TODO: Figure out what this does...
    if ($timestamp < $quiz->timeopen || ($quiz->timeclose and $timestamp > $quiz->timeclose)) {
        $lsl->response->quick_output(-701, 'QUIZ', 'Quiz not available.', FALSE);
        exit();
    }

    if ($finishattempt) {
        // redirect('review.php?attempt='.$attempt->id);
        $lsl->response->quick_output(-701, 'QUIZ', 'Got to finishattempt - but do not yet have Sloodle code to handle it.', FALSE);
        exit();
    }

/// Print the quiz page ////////////////////////////////////////////////////////

	if (!$isnotify) {

		$pagequestions = explode(',', $pagelist);
		$lastquestion = count($pagequestions);

		$output[] = array('quiz',$quiz->attempts,$quiz->name,$quiz->timelimit,$quiz->id,$lastquestion);
		$output[] = array('quizpages',quiz_number_of_pages($attempt->layout),$page,$pagelist);

		/// Print all the questions

		$number = quiz_first_questionnumber($attempt->layout, $pagelist);
		foreach ($pagequestions as $i) {
			$options = quiz_get_renderoptions($quiz->review, $states[$i]);
			// Print the question
			// var_dump($questions[$i]);
			$q = $questions[$i];

			if ( ($limittoquestion == 0) || ($limittoquestion == $q->id) ) {
			
				$output[] = array(
					'question',
					$i,
					$q->id,
					$q->parent,
					$q->questiontext,
					$q->defaultgrade,
					$q->penalty,
					$q->qtype,
					$q->hidden,
					$q->maxgrade,
					$q->single,
					$q->shuffleanswers,
					$deferred	
				);

				$ops = $q->options;
				foreach($ops as $opkey=>$op) {
				//print "<h1>$opkey is $op</h1>";
				   foreach($op as $ok=>$ov) {
					  //print '<h1>   '."$ok=>$ov</h1>";
					  //var_dump($ov);
					  $output[] = array(
						'questionoption',
						$i,
						$ov->id,
						$ov->question,
						$ov->answer,
						$ov->fraction,
						$ov->feedback
					  );
				   }
				}

			}
			//print_question($questions[$i], $states[$i], $number, $quiz, $options);
			save_question_session($questions[$i], $states[$i]);
			$number += $questions[$i]->length;
		}

		$secondsleft = ($quiz->timeclose ? $quiz->timeclose : 999999999999) - time();
		if ($isteacher) {
			// For teachers ignore the quiz closing time
			$secondsleft = 999999999999;
		}
		// If time limit is set include floating timer.
		if ($quiz->timelimit > 0) {
			$output[] = array('seconds left',$secondsleft);
		}
    }

    $lsl->response->set_status_code(1);
    $lsl->response->set_status_descriptor('QUIZ');
    $lsl->response->set_data($output);
    $lsl->response->render_to_output();
	exit;

?>
