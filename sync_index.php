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
 * Admin settings and defaults
 *
 * @package tool_leeloolxp_sync
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @author Leeloo LXP <info@leeloolxp.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require_once(dirname(dirname(__DIR__)) . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/lib/filelib.php');

global $DB;

$reqcatdatadelete = optional_param('categories_data_delete', null, PARAM_RAW);
$reqcatsdata = optional_param('categories_data', null, PARAM_RAW);
$reqmoodleuserid = optional_param('get_moodle_user_id', null, PARAM_RAW);
$reqemail = optional_param('email', null, PARAM_RAW);
$reqgetmoodleuser = optional_param('get_moodle_user', null, PARAM_RAW);
$requserid = optional_param('userid', null, PARAM_RAW);
$reqgradeletter1 = optional_param('gradeletter1', null, PARAM_RAW);
$reqgetuserid = optional_param('get_userid', null, PARAM_RAW);
$reqleelooactdata = optional_param('leelo_activity_data', null, PARAM_RAW);
$reqcourseid = optional_param('course_id', null, PARAM_RAW);
$reqleeloodata = optional_param('leelo_data', null, PARAM_RAW);
$reqredirect = optional_param('redirect', null, PARAM_RAW);
$reqcourseid1 = optional_param('courseid', null, PARAM_RAW);
$reqaction = optional_param('action', null, PARAM_RAW);
$reqredirecthidden = optional_param('redirecthidden', null, PARAM_RAW);
$reqquizsync = optional_param_array('quiz_sync', null, PARAM_RAW);
$reqallactivities = optional_param_array('all_activities', null, PARAM_RAW);
$reqsyncactivities = optional_param('sync_activities', null, PARAM_RAW);
$reqcourseiddate = optional_param('course_id_date', null, PARAM_RAW);
$reqid = optional_param('id', null, PARAM_RAW);
$requnsyncid = optional_param('unsync_id', null, PARAM_RAW);
$reqcourseidresync = optional_param('courseid_resync', null, PARAM_RAW);
$reqresync = optional_param('resync', null, PARAM_RAW);
$reqactivityname = optional_param('activity_name', null, PARAM_RAW);
$reactivityid = optional_param('activity_id', null, PARAM_RAW);
$reqresyncactivity = optional_param('resync_activity', null, PARAM_RAW);
$reqsyncategory = optional_param('syncategory', null, PARAM_RAW);
$reqprojectstartdate = optional_param('project_start_date', null, PARAM_RAW);
$reqprojectenddate = optional_param('project_end_date', null, PARAM_RAW);

// Delete category from leeloo to moodle.
if (isset($reqcatdatadelete)) {
    $id = json_decode($reqcatdatadelete, true);
    $conditions = array('id' => $id);
    $DB->delete_records('course_categories', $conditions);
    die;
}
// Sync categories from leeloo to moodle.
if (isset($reqcatsdata)) {
    $value = (object) json_decode($reqcatsdata, true);

    $tablecat = $CFG->prefix . 'course_categories';
    /* $sql = " SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
    $autoinc = $DB->get_record_sql($sql, [$CFG->dbname, $tablecat]); */
    $isinsert = 1;

    if ($value->is_update) {
        // $autoinc->auto_increment = $value->moodle_cat_id;
        $returnid = $value->moodle_cat_id;

        $isinsert = 0;
    }
    if ($value->depth != '1') {
        $value->moodle_cat_id = $value->moodle_parent_cat_id;
    }

    $sql = "SELECT * FROM {course_categories} where id = ?";
    $catdetail = $DB->get_record_sql($sql, [$value->moodle_cat_id]);
    $sql = "SELECT * FROM {course_categories} where id = ?";
    $parentcatdetail = $DB->get_record_sql($sql, [$value->moodle_parent_cat_id]);
    // echo "<pre>";print_r($parentcatdetail);

    if (!empty($value->moodle_parent_cat_id) && !empty($parentcatdetail)) {
        // insert/update child cat

        if ($value->depth == 1 || $value->depth == '1') {
            $value->path = '/' . $catdetail->id;
            $value->parent = '0';
        } else {
            // $value->path = $catdetail->path . '/' . $autoinc->auto_increment;
            $value->path = $catdetail->path;
            $value->parent = $value->moodle_cat_id;
        }
        if (!empty($returnid)) {
            // $value->id = $autoinc->auto_increment;
            $value->id = 1;
        }
    } else if (!empty($value->moodle_cat_id) && !empty($catdetail)) {
        // update cat

        if ($value->depth == 1 || $value->depth == '1') {
            $value->path = '/' . $catdetail->id;
            $value->parent = '0';
        } else {
            // $value->path = $catdetail->path . '/' . $autoinc->auto_increment;
            $value->path = $catdetail->path;
            $value->parent = $value->moodle_cat_id;
        }
        $returnid = $value->id = $value->moodle_cat_id;

        $isinsert = 0;
    } else {
        // insert top cat

        if ($value->depth == 1 || $value->depth == '1') {
            // $value->path = '/' . $autoinc->auto_increment;
            $value->path = '/';
        } else {
            if (!empty($catdetail)) {
                // $value->path = $catdetail->path . '/' . $autoinc->auto_increment;
                $value->path = $catdetail->path;
                $value->parent = $value->moodle_cat_id;
            } else {
                // $value->path = '/' . $autoinc->auto_increment;
                $value->path = '/';
                $value->parent = 0;
            }
        }
    }

    $value->sortorder = 10000;
    unset($value->moodle_cat_id);
    unset($value->moodle_parent_cat_id);
    unset($value->is_update);

    if ($isinsert) {
        $returnid = $DB->insert_record('course_categories', $value);
    } else {
        $DB->update_record('course_categories', $value);
    }

    echo $returnid;
    die;
}

if (isset($reqmoodleuserid)) {
    $email = $reqemail;
    $sql = "SELECT * FROM {user} where email = ?";
    $userdetail = $DB->get_record_sql($sql, [$email]);
    // print_r($userdetail);
    echo $userdetail->id;
    die;
}

if (isset($reqgetmoodleuser)) {
    $userid = $requserid;
    $sql = "SELECT * FROM {user} where id = ?";
    $userdetail = $DB->get_record_sql($sql, [$userid]);
    // print_r($userdetail);
    echo json_encode($userdetail);
    die;
}

if (isset($reqgradeletter1)) {
    for ($i = 1; $i <= 11; $i++) {
        $indexl = 'gradeletter' . $i;
        $indexb = 'gradeboundary' . $i;
        $lowerboundary = optional_param($indexb, null, PARAM_RAW);
        $letter = optional_param($indexl, null, PARAM_RAW);
        $DB->execute("update {grade_letters} set lowerboundary = ?, letter = ? where id = ?", [$lowerboundary, $letter, $i]);
    }
    die;
}

if (isset($reqgetuserid)) {
    $email = $reqemail;
    $res = $DB->get_record_sql("SELECT * FROM {user} where email = ?", [$email]);
    if (!empty($res)) {
        echo $res->id;
    } else {
        echo 0;
    }
    die;
}

if (isset($reqleelooactdata)) {
    $courseid = $reqcourseid;

    $activities = json_decode($reqleelooactdata, true);

    if (!empty($activities)) {
        foreach ($activities as $key => $value) {
            $activityid = $value['activity_id'];

            $startdate = strtotime($value['start_date']);

            $enddate = strtotime($value['end_date']);

            $type = $value['type'];

            $modulerecords = $DB->get_record_sql("SELECT module,instance FROM {course_modules} where id = ?", [$activityid]);

            $moduleid = $modulerecords->module;

            $isntanceid = $modulerecords->instance;

            $modulenames = $DB->get_record_sql("SELECT name FROM {modules} where id = ?", [$moduleid]);

            $modulename = $modulenames->name;

            if ($modulename == 'lesson') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->deadline = $enddate;

                $obj->available = $startdate;

                $DB->update_record('lesson', $obj);
            } else if ($modulename == 'quiz') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->timeopen = $startdate;

                $obj->timeclose = $enddate;

                $DB->update_record('quiz', $obj);
            } else if ($modulename == 'assign') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->allowsubmissionsfromdate = $startdate;

                $obj->duedate = $enddate;

                $DB->update_record('assign', $obj);
            } else if ($modulename == 'chat') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->chattime = $startdate;

                $DB->update_record('chat', $obj);
            } else if ($modulename == 'choice') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->timeopen = $startdate;

                $obj->timeclose = $enddate;

                $DB->update_record('choice', $obj);
            } else if ($modulename == 'data') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->timeavailablefrom = $startdate;

                $obj->timeavailableto = $enddate;

                $DB->update_record('data', $obj);
            } else if ($modulename == 'feedback') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->timeopen = $startdate;

                $obj->timeclose = $enddate;

                $DB->update_record('feedback', $obj);
            } else if ($modulename == 'forum') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->duedate = $startdate;

                $obj->cutoffdate = $enddate;

                $DB->update_record('forum', $obj);
            } else if ($modulename == 'wespher') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->timeopen = $startdate;

                $DB->update_record('wespher', $obj);
            } else if ($modulename == 'workshop') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->submissionstart = $startdate;

                $obj->submissionend = $enddate;

                $DB->update_record('workshop', $obj);
            } else if ($modulename == 'scorm') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->timeopen = $startdate;

                $obj->timeclose = $enddate;

                $DB->update_record('scorm', $obj);
            }
        }
    }

    echo "success";die;
}

if (isset($reqleeloodata)) {
    $courseid = $reqcourseid;

    $cobj = new stdClass();

    $cobj->id = $courseid;

    $cobj->startdate = strtotime($reqprojectstartdate);

    $cobj->enddate = strtotime($reqprojectenddate);

    $DB->update_record('course', $cobj);

    $activities = json_decode($reqleeloodata, true);

    if (!empty($activities)) {
        foreach ($activities as $key => $value) {
            $activityid = $value['activity_id'];

            $startdate = strtotime($value['start_date']);

            $enddate = strtotime($value['end_date']);

            $type = $value['type'];

            $modulerecords = $DB->get_record_sql("SELECT module,instance FROM {course_modules} where id = ?", [$activityid]);

            $moduleid = $modulerecords->module;

            $isntanceid = $modulerecords->instance;

            $modulenames = $DB->get_record_sql("SELECT name FROM {modules} where id = ?", [$moduleid]);

            $modulename = $modulenames->name;

            $tbl = $modulename;

            if ($modulename == 'lesson') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->deadline = $enddate;

                $obj->available = $startdate;

                $DB->update_record('lesson', $obj);
            } else if ($tbl == 'quiz') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->timeopen = $startdate;

                $obj->timeclose = $enddate;

                $DB->update_record('quiz', $obj);
            } else if ($tbl == 'assign') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->allowsubmissionsfromdate = $startdate;

                $obj->duedate = $enddate;

                $DB->update_record('assign', $obj);
            } else if ($tbl == 'chat') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->chattime = $startdate;

                $DB->update_record('chat', $obj);
            } else if ($tbl == 'choice') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->timeopen = $startdate;

                $obj->timeclose = $enddate;

                $DB->update_record('choice', $obj);
            } else if ($tbl == 'data') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->timeavailablefrom = $startdate;

                $obj->timeavailableto = $enddate;

                $DB->update_record('data', $obj);
            } else if ($tbl == 'feedback') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->timeopen = $startdate;

                $obj->timeclose = $enddate;

                $DB->update_record('feedback', $obj);
            } else if ($tbl == 'forum') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->duedate = $startdate;

                $obj->cutoffdate = $enddate;

                $DB->update_record('forum', $obj);
            } else if ($tbl == 'wespher') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->timeopen = $startdate;

                $DB->update_record('wespher', $obj);
            } else if ($tbl == 'workshop') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->submissionstart = $startdate;

                $obj->submissionend = $enddate;

                $DB->update_record('workshop', $obj);
            } else if ($tbl == 'scorm') {
                $obj = new stdClass();

                $obj->id = $isntanceid;

                $obj->timeopen = $startdate;

                $obj->timeclose = $enddate;

                $DB->update_record('scorm', $obj);
            }
        }
    }

    echo "success";die;
}