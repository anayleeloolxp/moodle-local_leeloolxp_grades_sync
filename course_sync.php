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
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/lib/filelib.php');
require_once($CFG->dirroot . '/course/lib.php');

global $DB;

$reqcoursedata = optional_param('course_data', null, PARAM_RAW);
$reqcategoriesdata = optional_param('categories_data', null, PARAM_RAW);
$reqgradedata = optional_param('grade_data', null, PARAM_RAW);

// Sync Course.
if (isset($reqcoursedata)) {
    $value = (object) json_decode($reqcoursedata, true);

    $value->summaryformat = 0;

    if (!empty($value->description)) {
        $value->summaryformat = 1;
    }

    if (!empty($value->course_image)) {
        $courseimage = $value->course_image;
    } else {
        $courseimage = '';
    }

    if (empty($value->course_id_number)) {
        $value->course_id_number = '';
    }

    $data = [
        'category' => $value->category,
        'sortorder' => 10000,
        'fullname' => $value->project_name,
        'shortname' => $value->project_sort_name,
        'idnumber' => $value->course_id_number,
        'summary' => $value->description,
        'summaryformat' => $value->summaryformat,
        'format' => 'topics',
        'showgrades' => 1,
        'newsitems' => 5,
        'startdate' => strtotime($value->start_date),
        'enddate' => strtotime($value->end_date),
        'relativedatesmode' => 0,
        'marker' => 0,
        'maxbytes' => 0,
        'legacyfiles' => 0,
        'showreports' => 0,
        'visible' => $value->visible,
        'visibleold' => 1,
        'groupmode' => 0,
        'groupmodeforce' => 0,
        'defaultgroupingid' => 0,
        'lang' => '',
        'calendartype' => '',
        'theme' => '',
        'timecreated' => time(),
        'timemodified' => time(),
        'requested' => 0,
        'enablecompletion' => 1,
        'completionnotify' => 0,
        'cacherev' => 1607419438,
    ];

    $data = (object) $data;

    $isinsert = 1;
    if (!empty($value->course_id)) {
        $isinsert = 0;
        $table = $CFG->prefix . 'course';
        $data->id = $value->course_id;
        if (!empty($value->project_sort_name)) {
            $sql = "SELECT * FROM $table WHERE shortname = ? AND id != ?";
            if ($DB->record_exists_sql($sql, [$value->project_sort_name, $value->course_id])) {
                echo 0;
                die;
            }
        }

        if (!empty($value->course_id_number)) {
            $sql = "SELECT * FROM $table WHERE idnumber = ? AND id != ?";
            if ($DB->record_exists_sql($sql, [$value->course_id_number, $value->course_id])) {
                echo 0;
                die;
            }
        }
    } else {
        if (!empty($value->project_sort_name)) {
            if ($DB->record_exists('course', array('shortname' => $value->project_sort_name))) {
                echo 0;
                die;
            }
        }

        if (!empty($value->course_id_number)) {
            if ($DB->record_exists('course', array('idnumber' => $value->course_id_number))) {
                echo 0;
                die;
            }
        }
    }

    if ($isinsert) {
        $returnid = $DB->insert_record('course', $data);
    } else {
        $DB->update_record('course', $data);
        $returnid = $value->course_id;
    }

    $catreturnid = 0;
    $itemreturnid = 0;
    $categoriesdata = (object) json_decode($reqcategoriesdata, true);
    $gradedata = (object) json_decode($reqgradedata, true);

    // If not empty , then insert category  , no need to check for update.
    if (!empty($categoriesdata)) {
        /* $tablecat = $CFG->prefix . 'grade_categories';
        $sql = " SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
        $autoinc = $DB->get_record_sql($sql, [$CFG->dbname, $tablecat]);
        $autoinc->auto_increment;
        $categoriesdata->path = '/' . $autoinc->auto_increment . '/'; */
        $categoriesdata->path = '/';
        $categoriesdata->courseid = $returnid;
        $catreturnid = $DB->insert_record('grade_categories', $categoriesdata);
    }

    if (!empty($gradedata) && !empty($catreturnid)) {
        $gradedata->iteminstance = $catreturnid;
        $gradedata->courseid = $returnid;
        $itemreturnid = $DB->insert_record('grade_items', $gradedata);
    }

    echo $returnid . ',' . $catreturnid . ',' . $itemreturnid;
    die;
}
