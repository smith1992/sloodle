<?php  // $Id: locallib.php,v 1.95 2006/05/03 12:27:06 thepurpleblob Exp $
/**
* Library of functions used by the quiz module.
*
* This contains functions that are called from within the quiz module only
* Functions that are also called by core Moodle are in {@link lib.php}
* This script also loads the code in {@link questionlib.php} which holds
* the module-indpendent code for handling questions and which in turn
* initialises all the questiontype classes.
* @version $Id: locallib.php,v 1.95 2006/05/03 12:27:06 thepurpleblob Exp $
* @author Martin Dougiamas and many others. This has recently been completely
*         rewritten by Alex Smith, Julian Sedding and Gustav Delius as part of
*         the Serving Mathematics project
*         {@link http://maths.york.ac.uk/serving_maths}
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package quiz
*
* @ignore
*
*/

/**
* Include those library functions that are also used by core Moodle or other modules
*/
require_once("$CFG->dirroot/mod/quiz/lib.php");
//require_once($CFG->libdir.'/questionlib.php');
require_once("{$CFG->dirroot}/question/editlib.php");

/// CONSTANTS ///////////////////////////////////////////////////////////////////

/**#@+
* Options determining how the grades from individual attempts are combined to give
* the overall grade for a user
*/
define("QUIZ_GRADEHIGHEST", "1");
define("QUIZ_GRADEAVERAGE", "2");
define("QUIZ_ATTEMPTFIRST", "3");
define("QUIZ_ATTEMPTLAST",  "4");
$QUIZ_GRADE_METHOD = array ( QUIZ_GRADEHIGHEST => get_string("gradehighest", "quiz"),
                             QUIZ_GRADEAVERAGE => get_string("gradeaverage", "quiz"),
                             QUIZ_ATTEMPTFIRST => get_string("attemptfirst", "quiz"),
                             QUIZ_ATTEMPTLAST  => get_string("attemptlast", "quiz"));
/**#@-*/


/// FUNCTIONS RELATED TO ATTEMPTS /////////////////////////////////////////

/**
* Creates an object to represent a new attempt at a quiz
*
* Creates an attempt object to represent an attempt at the quiz by the current
* user starting at the current time. The ->id field is not set. The object is
* NOT written to the database.
* @return object                The newly created attempt object.
* @param object $quiz           The quiz to create an attempt for.
* @param integer $attemptnumber The sequence number for the attempt.
*/
function quiz_create_attempt($quiz, $attemptnumber) {
    global $USER, $CFG;

    if (!$attemptnumber > 1 or !$quiz->attemptonlast or !$attempt = get_record('quiz_attempts', 'quiz', $quiz->id, 'userid', $USER->id, 'attempt', $attemptnumber-1)) {
        // we are not building on last attempt so create a new attempt
        $attempt->quiz = $quiz->id;
        $attempt->userid = $USER->id;
        $attempt->preview = 0;
        if ($quiz->shufflequestions) {
            $attempt->layout = quiz_repaginate($quiz->questions, $quiz->questionsperpage, true);
        } else {
            $attempt->layout = $quiz->questions;
        }
    }

    $timenow = time();
    $attempt->attempt = $attemptnumber;
    $attempt->sumgrades = 0.0;
    $attempt->timestart = $timenow;
    $attempt->timefinish = 0;
    $attempt->timemodified = $timenow;
    $attempt->uniqueid = question_new_attempt_uniqueid();

    return $attempt;
}

function quiz_get_user_attempt_unfinished($quizid, $userid) {
// Returns an object containing an unfinished attempt (if there is one)
    return get_record("quiz_attempts", "quiz", $quizid, "userid", $userid, "timefinish", 0);
}

function quiz_get_user_attempts($quizid, $userid) {
// Returns a list of all attempts by a user
    return get_records_select("quiz_attempts", "quiz = '$quizid' AND userid = '$userid' AND timefinish > 0",
                              "attempt ASC");
}


/// FUNCTIONS TO DO WITH QUIZ LAYOUT AND PAGES ////////////////////////////////

/**
* Returns a comma separated list of question ids for the current page
*
* @return string         Comma separated list of question ids
* @param string $layout  The string representing the quiz layout. Each page is represented as a
*                        comma separated list of question ids and 0 indicating page breaks.
*                        So 5,2,0,3,0 means questions 5 and 2 on page 1 and question 3 on page 2
* @param integer $page   The number of the current page.
*/
function quiz_questions_on_page($layout, $page) {
    $pages = explode(',0', $layout);
    return trim($pages[$page], ',');
}

/**
* Returns a comma separated list of question ids for the quiz
*
* @return string         Comma separated list of question ids
* @param string $layout  The string representing the quiz layout. Each page is represented as a
*                        comma separated list of question ids and 0 indicating page breaks.
*                        So 5,2,0,3,0 means questions 5 and 2 on page 1 and question 3 on page 2
*/
function quiz_questions_in_quiz($layout) {
    return str_replace(',0', '', $layout);
}

/**
* Returns the number of pages in the quiz layout
*
* @return integer         Comma separated list of question ids
* @param string $layout  The string representing the quiz layout.
*/
function quiz_number_of_pages($layout) {
    return substr_count($layout, ',0');
}

/**
* Returns the first question number for the current quiz page
*
* @return integer  The number of the first question
* @param string $quizlayout The string representing the layout for the whole quiz
* @param string $pagelayout The string representing the layout for the current page
*/
function quiz_first_questionnumber($quizlayout, $pagelayout) {
    // this works by finding all the questions from the quizlayout that
    // come before the current page and then adding up their lengths.
    global $CFG;
    $start = strpos($quizlayout, ','.$pagelayout.',')-2;
    if ($start > 0) {
        $prevlist = substr($quizlayout, 0, $start);
        return get_field_sql("SELECT sum(length)+1 FROM {$CFG->prefix}question
         WHERE id IN ($prevlist)");
    } else {
        return 1;
    }
}

/**
* Re-paginates the quiz layout
*
* @return string         The new layout string
* @param string $layout  The string representing the quiz layout.
* @param integer $perpage The number of questions per page
* @param boolean $shuffle Should the questions be reordered randomly?
*/
function quiz_repaginate($layout, $perpage, $shuffle=false) {
    $layout = str_replace(',0', '', $layout); // remove existing page breaks
    $questions = explode(',', $layout);
    if ($shuffle) {
        srand((float)microtime() * 1000000); // for php < 4.2
        shuffle($questions);
    }
    $i = 1;
    $layout = '';
    foreach ($questions as $question) {
        if ($perpage and $i > $perpage) {
            $layout .= '0,';
            $i = 1;
        }
        $layout .= $question.',';
        $i++;
    }
    return $layout.'0';
}

/**
* Print navigation panel for quiz attempt and review pages
*
* @param integer $page     The number of the current page (counting from 0).
* @param integer $pages    The total number of pages.
*/
function quiz_print_navigation_panel($page, $pages) {
    //$page++;
    echo '<div class="pagingbar">';
    echo '<span class="title">' . get_string('page') . ':</span>';
    if ($page > 0) {
        // Print previous link
        $strprev = get_string('previous');
        echo '<a href="javascript:navigate(' . ($page - 1) . ');" title="'
         . $strprev . '">(' . $strprev . ')</a>';
    }
    for ($i = 0; $i < $pages; $i++) {
        if ($i == $page) {
            echo '<span class="thispage">'.($i+1).'</span>';
        } else {
            echo '<a href="javascript:navigate(' . ($i) . ');">'.($i+1).'</a>';
        }
    }

    if ($page < $pages - 1) {
        // Print next link
        $strnext = get_string('next');
        echo '<a href="javascript:navigate(' . ($page + 1) . ');" title="'
         . $strnext . '">(' . $strnext . ')</a>';
    }
    echo '</div>';
}


/// FUNCTIONS TO DO WITH QUIZ GRADES //////////////////////////////////////////

/**
* Creates an array of maximum grades for a quiz
*
* The grades are extracted from the quiz_question_instances table.
* @return array        Array of grades indexed by question id
*                      These are the maximum possible grades that
*                      students can achieve for each of the questions
* @param integer $quiz The quiz object
*/
function quiz_get_all_question_grades($quiz) {
    global $CFG;

    $questionlist = quiz_questions_in_quiz($quiz->questions);
    if (empty($questionlist)) {
        return array();
    }

    $instances = get_records_sql("SELECT question,grade,id
                            FROM {$CFG->prefix}quiz_question_instances
                            WHERE quiz = '$quiz->id'" .
                            (is_null($questionlist) ? '' :
                            "AND question IN ($questionlist)"));

    $list = explode(",", $questionlist);
    $grades = array();

    foreach ($list as $qid) {
        if (isset($instances[$qid])) {
            $grades[$qid] = $instances[$qid]->grade;
        } else {
            $grades[$qid] = 1;
        }
    }
    return $grades;
}


function quiz_get_best_grade($quiz, $userid) {
/// Get the best current grade for a particular user in a quiz
if (!$grade = get_record('quiz_grades', 'quiz', $quiz->id, 'userid', $userid)) {
        return NULL;
    }

    return (round($grade->grade,$quiz->decimalpoints));
}

/**
* Save the overall grade for a user at a quiz in the quiz_grades table
*
* @return boolean        Indicates success or failure.
* @param object $quiz    The quiz for which the best grade is to be calculated
*                        and then saved.
* @param integer $userid The id of the user to save the best grade for. Can be
*                        null in which case the current user is assumed.
*/
function quiz_save_best_grade($quiz, $userid=null) {
    global $USER;

    // Assume the current user if $userid is null
    if (is_null($userid)) {
        $userid = $USER->id;
    }

    // Get all the attempts made by the user
    if (!$attempts = quiz_get_user_attempts($quiz->id, $userid)) {
        notify('Could not find any user attempts');
        return false;
    }

    // Calculate the best grade
    $bestgrade = quiz_calculate_best_grade($quiz, $attempts);
    $bestgrade = $quiz->sumgrades ? (($bestgrade / $quiz->sumgrades) * $quiz->grade) : 0;
    $bestgrade = round($bestgrade, $quiz->decimalpoints);

    // Save the best grade in the database
    if ($grade = get_record('quiz_grades', 'quiz', $quiz->id, 'userid',
     $userid)) {
        $grade->grade = $bestgrade;
        $grade->timemodified = time();
        if (!update_record('quiz_grades', $grade)) {
            notify('Could not update best grade');
            return false;
        }
    } else {
        $grade->quiz = $quiz->id;
        $grade->userid = $userid;
        $grade->grade = $bestgrade;
        $grade->timemodified = time();
        if (!insert_record('quiz_grades', $grade)) {
            notify('Could not insert new best grade');
            return false;
        }
    }
    return true;
}

/**
* Calculate the overall grade for a quiz given a number of attempts by a particular user.
*
* @return float          The overall grade
* @param object $quiz    The quiz for which the best grade is to be calculated
* @param array $attempts An array of all the attempts of the user at the quiz
*/
function quiz_calculate_best_grade($quiz, $attempts) {

    switch ($quiz->grademethod) {

        case QUIZ_ATTEMPTFIRST:
            foreach ($attempts as $attempt) {
                return $attempt->sumgrades;
            }
            break;

        case QUIZ_ATTEMPTLAST:
            foreach ($attempts as $attempt) {
                $final = $attempt->sumgrades;
            }
            return $final;

        case QUIZ_GRADEAVERAGE:
            $sum = 0;
            $count = 0;
            foreach ($attempts as $attempt) {
                $sum += $attempt->sumgrades;
                $count++;
            }
            return (float)$sum/$count;

        default:
        case QUIZ_GRADEHIGHEST:
            $max = 0;
            foreach ($attempts as $attempt) {
                if ($attempt->sumgrades > $max) {
                    $max = $attempt->sumgrades;
                }
            }
            return $max;
    }
}

/**
* Return the attempt with the best grade for a quiz
*
* Which attempt is the best depends on $quiz->grademethod. If the grade
* method is GRADEAVERAGE then this function simply returns the last attempt.
* @return object         The attempt with the best grade
* @param object $quiz    The quiz for which the best grade is to be calculated
* @param array $attempts An array of all the attempts of the user at the quiz
*/
function quiz_calculate_best_attempt($quiz, $attempts) {

    switch ($quiz->grademethod) {

        case QUIZ_ATTEMPTFIRST:
            foreach ($attempts as $attempt) {
                return $attempt;
            }
            break;

        case QUIZ_GRADEAVERAGE: // need to do something with it :-)
        case QUIZ_ATTEMPTLAST:
            foreach ($attempts as $attempt) {
                $final = $attempt;
            }
            return $final;

        default:
        case QUIZ_GRADEHIGHEST:
            $max = -1;
            foreach ($attempts as $attempt) {
                if ($attempt->sumgrades > $max) {
                    $max = $attempt->sumgrades;
                    $maxattempt = $attempt;
                }
            }
            return $maxattempt;
    }
}


/// OTHER QUIZ FUNCTIONS ////////////////////////////////////////////////////

/**
* Print a box with quiz start and due dates
*
* @param object $quiz
*/
function quiz_view_dates($quiz) {
    if (!$quiz->timeopen && !$quiz->timeclose) {
        return;
    }

    print_simple_box_start('center', '', '', '', 'generalbox', 'dates');
    echo '<table>';
    if ($quiz->timeopen) {
        echo '<tr><td class="c0">'.get_string("quizopen", "quiz").':</td>';
        echo '    <td class="c1">'.userdate($quiz->timeopen).'</td></tr>';
    }
    if ($quiz->timeclose) {
        echo '<tr><td class="c0">'.get_string("quizclose", "quiz").':</td>';
        echo '    <td class="c1">'.userdate($quiz->timeclose).'</td></tr>';
    }
    echo '</table>';
    print_simple_box_end();
}


/**
* Parse field names used for the replace options on question edit forms
*/
function quiz_parse_fieldname($name, $nameprefix='question') {
    $reg = array();
    if (preg_match("/$nameprefix(\\d+)(\w+)/", $name, $reg)) {
        return array('mode' => $reg[2], 'id' => (int)$reg[1]);
    } else {
        return false;
    }
}


/**
* Upgrade states for an attempt to Moodle 1.5 model
*
* Any state that does not yet have its timestamp set to nonzero has not yet been upgraded from Moodle 1.4
* The reason these are still around is that for large sites it would have taken too long to
* upgrade all states at once. This function sets the timestamp field and creates an entry in the
* question_sessions table.
* @param object $attempt  The attempt whose states need upgrading
*/
function quiz_upgrade_states($attempt) {
    global $CFG;
    // The old quiz model only allowed a single response per quiz attempt so that there will be
    // only one state record per question for this attempt.

    // We set the timestamp of all states to the timemodified field of the attempt.
    execute_sql("UPDATE {$CFG->prefix}question_states SET timestamp = '$attempt->timemodified' WHERE attempt = '$attempt->uniqueid'", false);

    // For each state we create an entry in the question_sessions table, with both newest and
    // newgraded pointing to this state.
    // Actually we only do this for states whose question is actually listed in $attempt->layout.
    // We do not do it for states associated to wrapped questions like for example the questions
    // used by a RANDOM question
    $newest->attemptid = $attempt->uniqueid;
    $questionlist = quiz_questions_in_quiz($attempt->layout);
    if ($questionlist and $states = get_records_select('question_states', "attempt = '$attempt->uniqueid' AND question IN ($questionlist)")) {
        foreach ($states as $state) {
            $session->newgraded = $state->id;
            $session->newest = $state->id;
            $session->questionid = $state->question;
            insert_record('question_sessions', $session, false);
        }
    }
}

// ULPGc ecastro
function quiz_get_question_review($quiz, $question) {
// returns a question icon
    $qnum = $question->id;
    $strpreview = get_string('previewquestion', 'quiz');
    $context = $quiz->id ? '&amp;contextquiz='.$quiz->id : '';
    $quiz_id = $quiz->id ? '&amp;quizid=' . $quiz->id : '';
    return "<a title=\"$strpreview\" href=\"javascript:void(0)\" onClick=\"openpopup('/question/preview.php?id=$qnum$quiz_id','questionpreview','scrollbars=yes,resizable=yes,width=700,height=480', false)\">
          <img src=\"../../pix/t/preview.gif\" border=\"0\" alt=\"$strpreview\" /></a>";
}


/**
 * Determine render options
 *
 * @param int $reviewoptions
 * @param object $state
 */
function quiz_get_renderoptions($reviewoptions, $state) {
    // Show the question in readonly (review) mode if the question is in
    // the closed state
    $options->readonly = question_state_is_closed($state);

    // Show feedback once the question has been graded (if allowed by the quiz)
    $options->feedback = question_state_is_graded($state) && ($reviewoptions & QUIZ_REVIEW_FEEDBACK & QUIZ_REVIEW_IMMEDIATELY);

    // Show validation only after a validation event
    $options->validation = QUESTION_EVENTVALIDATE === $state->event;

    // Show correct responses in readonly mode if the quiz allows it
    $options->correct_responses = $options->readonly && ($reviewoptions & QUIZ_REVIEW_ANSWERS & QUIZ_REVIEW_IMMEDIATELY);

    // Always show responses and scores
    $options->responses = true;
    $options->scores = true;

    return $options;
}


/**
* Determine review options
*/
function quiz_get_reviewoptions($quiz, $attempt, $isteacher=false) {
    $options->readonly = true;
    // Provide the links to the question review and comment script
    $options->questionreviewlink = '/mod/quiz/reviewquestion.php';

    if ($isteacher and !$attempt->preview) {
        // The teacher should be shown everything except during preview when the teachers
        // wants to see just what the students see
        $options->responses = true;
        $options->scores = true;
        $options->feedback = true;
        $options->correct_responses = true;
        $options->solutions = false;
        // Show a link to the comment box only for closed attempts
        if ($attempt->timefinish) {
            $options->questioncommentlink = '/mod/quiz/comment.php';
        }
        return $options;
    }
    if (((time() - $attempt->timefinish) < 120) || $attempt->timefinish==0) {
        $options->responses = ($quiz->review & QUIZ_REVIEW_IMMEDIATELY & QUIZ_REVIEW_RESPONSES) ? 1 : 0;
        $options->scores = ($quiz->review & QUIZ_REVIEW_IMMEDIATELY & QUIZ_REVIEW_SCORES) ? 1 : 0;
        $options->feedback = ($quiz->review & QUIZ_REVIEW_IMMEDIATELY & QUIZ_REVIEW_FEEDBACK) ? 1 : 0;
        $options->correct_responses = ($quiz->review & QUIZ_REVIEW_IMMEDIATELY & QUIZ_REVIEW_ANSWERS) ? 1 : 0;
        $options->solutions = ($quiz->review & QUIZ_REVIEW_IMMEDIATELY & QUIZ_REVIEW_SOLUTIONS) ? 1 : 0;
    } else if (!$quiz->timeclose or time() < $quiz->timeclose) {
        $options->responses = ($quiz->review & QUIZ_REVIEW_OPEN & QUIZ_REVIEW_RESPONSES) ? 1 : 0;
        $options->scores = ($quiz->review & QUIZ_REVIEW_OPEN & QUIZ_REVIEW_SCORES) ? 1 : 0;
        $options->feedback = ($quiz->review & QUIZ_REVIEW_OPEN & QUIZ_REVIEW_FEEDBACK) ? 1 : 0;
        $options->correct_responses = ($quiz->review & QUIZ_REVIEW_OPEN & QUIZ_REVIEW_ANSWERS) ? 1 : 0;
        $options->solutions = ($quiz->review & QUIZ_REVIEW_OPEN & QUIZ_REVIEW_SOLUTIONS) ? 1 : 0;
    } else {
        $options->responses = ($quiz->review & QUIZ_REVIEW_CLOSED & QUIZ_REVIEW_RESPONSES) ? 1 : 0;
        $options->scores = ($quiz->review & QUIZ_REVIEW_CLOSED & QUIZ_REVIEW_SCORES) ? 1 : 0;
        $options->feedback = ($quiz->review & QUIZ_REVIEW_CLOSED & QUIZ_REVIEW_FEEDBACK) ? 1 : 0;
        $options->correct_responses = ($quiz->review & QUIZ_REVIEW_CLOSED & QUIZ_REVIEW_ANSWERS) ? 1 : 0;
        $options->solutions = ($quiz->review & QUIZ_REVIEW_CLOSED & QUIZ_REVIEW_SOLUTIONS) ? 1 : 0;
    }

    return $options;
}
////////////////////////////////////////////////////////////////////////////////
/**
* Return boolean indicating if the quiz has attempts with hidden grades
*
* Selects all attempts matching specified quiz & user, and examines each to
* check they all have visible results.
* @return boolean        If the quiz has attempts without visible results
* @param object $quiz    The quiz being examined
* @param object $user    The user concerned
*/
function all_attempt_results_visible($quiz, $user) {
    global $CFG;
    $sql = 'SELECT timefinish, preview FROM '.$CFG->prefix.'quiz_attempts qa'.
        ' WHERE qa.quiz='.$quiz->id.' AND qa.userid='.$user->id.
        ' ORDER BY id DESC';
    if ($attempts = get_records_sql($sql)) {
        foreach ($attempts as $attempt) {            
            $attemptoptions = quiz_get_reviewoptions($quiz, $attempt);
            //if any attempt has scores option not set, not all attempt results are
            //visible
            if (!$attemptoptions->scores) {
                return false;
            }
        }
    }
    return true;
}

?>
