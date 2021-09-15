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

$reqardata = optional_param('ar_data', null, PARAM_RAW);
$reqemail = optional_param('email', null, PARAM_RAW);
$reqtagdata = optional_param('tags_data', null, PARAM_RAW);
$reqstandardtagdata = optional_param('standard_tags_data', null, PARAM_RAW);
$reqdeletedtagid = optional_param('deleted_tag_id', null, PARAM_RAW);
$reqorgtagid = optional_param('original_tag', null, PARAM_RAW);
$requpdatedtagdata = optional_param('updated_tags_data', null, PARAM_RAW);
$requpdatedtagflag = optional_param('updated_tag_flag_standard', null, PARAM_RAW);
$reqcombtagdata = optional_param('combine_tags_data', null, PARAM_RAW);

// Sync Course.
if (isset($reqardata)) {
    $ardata = (object) json_decode($reqardata, true);

    if (isset($reqemail)) {
        $email = (object) json_decode($reqemail, true);
    }

    $data = [];
    $moddata = [];

    if (isset($ardata->task_name)) {
        $taskname = $ardata->task_name;
        $moddata['name'] = $taskname;
    }

    if (isset($ardata->task_description)) {
        $taskdescription = $ardata->task_description;
        $moddata['intro'] = $taskdescription;
    }

    if (isset($ardata->m_showdescription)) {
        $mshowdescription = $ardata->m_showdescription;
        $data['showdescription'] = $mshowdescription;
    }

    if (isset($ardata->m_idnumber)) {
        $midnumber = $ardata->m_idnumber;
        $data['idnumber'] = $midnumber;
    }

    if (isset($ardata->m_completion)) {
        $mcompletion = $ardata->m_completion;
        $data['completion'] = $mcompletion;
    }

    if (isset($ardata->m_completionexpected)) {
        $mcompletionexpected = $ardata->m_completionexpected;
        if ($mcompletionexpected != 0) {
            $mcompletionexpected = $mcompletionexpected + (6 * 60 * 60);
        }
        $data['completionexpected'] = $mcompletionexpected;
    }

    if (isset($ardata->m_visible)) {
        $mvisible = $ardata->m_visible;
        $data['visible'] = $mvisible;
    }

    if (isset($ardata->m_availability)) {
        $mavailability = $ardata->m_availability;
        $mavailability = str_ireplace('&lt;', '<', $mavailability);
        $mavailability = str_ireplace('&gt;', '>', $mavailability);
        $data['availability'] = $mavailability;
    }

    if (isset($ardata->m_groupmode)) {
        $mgroupmode = $ardata->m_groupmode;
    }

    if (isset($ardata->m_groupingid)) {
        $mgroupingid = $ardata->m_groupingid;
    }

    $activityid = $ardata->activity_id;

    $countupdatescm = count($data);

    $data['id'] = $activityid;

    $data = (object) $data;

    if ($activityid != '') {
        if ($countupdatescm > 0) {
            $DB->update_record('course_modules', $data);
        }

        $ararr = $DB->get_record_sql("SELECT module,instance FROM {course_modules} where id = ?", [$activityid]);
        $module = $ararr->module;
        $modinstance = $ararr->instance;

        $modarr = $DB->get_record_sql("SELECT name FROM {modules} where id = ?", [$module]);
        $modulename = $modarr->name;

        $countupdatesmd = count($moddata);

        $moddata['id'] = $modinstance;

        $moddata = (object) $moddata;

        if ($countupdatesmd > 0) {
            $DB->update_record($modulename, $moddata);
        }

        if (!empty($email)) {
            $userdata = $DB->get_record('user', ['email' => $email->scalar], 'id');
        }

        if (!empty($userdata)) {
            $userid = $userdata->id;
            $tagsreturnarr = [];

            // tags_data
            if (isset($reqtagdata)) {
                $tagsdataarrobj = (object) json_decode($reqtagdata, true);
                // echo "<pre>";print_r($tagsdataarrobj);die;

                if (!empty($tagsdataarrobj)) {
                    foreach ($tagsdataarrobj as $key => $tagsdata) {
                        $istagexist = $DB->get_record('tag', ['name' => $tagsdata['name']], 'id');

                        $leelootagid = $tagsdata['id'];

                        if (empty($istagexist)) {
                            unset($tagsdata['moodleid']);
                            unset($tagsdata['id']);
                            unset($tagsdata['task_id']);

                            $tagsdata['tagcollid'] = 1;
                            $tagsdata['userid'] = $userid;
                            // echo "<pre>";print_r($tagsdata);die;

                            $returnid = $DB->insert_record('tag', $tagsdata);
                            array_push($tagsreturnarr, ['tag_id' => $leelootagid, 'moodleid' => $returnid]);
                        } else {
                            array_push($tagsreturnarr, ['tag_id' => $leelootagid, 'moodleid' => $istagexist->id]);
                        }
                    }
                }
            }

            // tags_data
            if (!empty($tagsreturnarr)) {
                $tagidsnotdelete = '';
                $j = 0;

                foreach ($tagsreturnarr as $key => $value) {
                    if ($j == 0) {
                        $tagidsnotdelete .= $value['moodleid'];
                    } else {
                        $tagidsnotdelete .= ',' . $value['moodleid'];
                    }
                    $j++;

                    $taginstanceexist = $DB->get_record('tag_instance', ['tagid' => $value['moodleid'], 'itemid' => $activityid], 'id');

                    if (empty($taginstanceexist)) {
                        $contextdata = $DB->get_record('context', ['instanceid' => $activityid], 'id');

                        if (!empty($contextdata)) {
                            $contextid = $contextdata->id;
                        } else {
                            $contextid = 0;
                        }

                        $taginstancedata = [
                            'tagid' => $value['moodleid'],
                            'component' => 'core',
                            'itemtype' => 'course_modules',
                            'itemid' => $activityid,
                            'contextid' => $contextid,
                            'tiuserid' => '0',
                            'ordering' => '1',
                            'timecreated' => strtotime(date('Y-m-d H:i:s')),
                            'timemodified' => strtotime(date('Y-m-d H:i:s')),
                        ];

                        $DB->insert_record('tag_instance', $taginstancedata);
                    }
                }

                $sql = "SELECT tagid FROM {tag_instance} WHERE itemid = ?";
                $tagsfordelete = $DB->get_records_sql($sql, [$activityid]);

                // $DB->delete_records('tag_instance', ['itemid' => $activityid]);

                $DB->execute("DELETE FROM {tag_instance} where itemid = ? AND tagid NOT IN (?) ", [$activityid, $tagidsnotdelete]);

                if (!empty($tagsfordelete)) {
                    $i = 0;

                    foreach ($tagsfordelete as $key => $value) {
                        $sql = "SELECT tagid FROM {tag_instance} WHERE tagid = ?";
                        $istagexistt = $DB->get_record_sql($sql, [$value->tagid]);

                        if (empty($istagexistt)) {
                            $DB->delete_records('tag', ['id' => $value->tagid, 'isstandard' => '0']);
                        }
                    }
                }
            } else {

                $sql = "SELECT tagid FROM {tag_instance} WHERE itemid = ?";
                $tagsfordelete = $DB->get_records_sql($sql, [$activityid]);

                $DB->delete_records('tag_instance', ['itemid' => $activityid]);

                if (!empty($tagsfordelete)) {
                    $i = 0;

                    foreach ($tagsfordelete as $key => $value) {
                        $sql = "SELECT tagid FROM {tag_instance} WHERE tagid = ?";
                        $istagexistt = $DB->get_record_sql($sql, [$value->tagid]);

                        if (empty($istagexistt)) {
                            $DB->delete_records('tag', ['id' => $value->tagid, 'isstandard' => '0']);
                        }
                    }
                }
            }
        } // $userdata end
    }

    if (!empty($tagsreturnarr)) {
        echo json_encode($tagsreturnarr);die;
    }
}

if (isset($reqstandardtagdata)) {
    if (!empty($reqemail)) {
        $email = (object) json_decode($reqemail, true);
        $userdata = $DB->get_record('user', ['email' => $email->scalar], 'id');
    }

    if (!empty($userdata)) {
        $userid = $userdata->id;

        $tagsreturnarr = [];

        $tagsdataarrobj = (object) json_decode($reqstandardtagdata, true);
        // echo "<pre>";print_r($tagsdataarrobj);die;

        if (!empty($tagsdataarrobj)) {
            foreach ($tagsdataarrobj as $key => $tagsdata) {
                $istagexist = $DB->get_record('tag', ['name' => $tagsdata['name']], 'id');

                $leelootagid = $tagsdata['id'];

                if (empty($istagexist)) {
                    unset($tagsdata['moodleid']);
                    unset($tagsdata['id']);
                    unset($tagsdata['task_id']);

                    $tagsdata['tagcollid'] = 1;
                    $tagsdata['userid'] = $userid;
                    // echo "<pre>";print_r($tagsdata);die;

                    $returnid = $DB->insert_record('tag', $tagsdata);
                    array_push($tagsreturnarr, ['tag_id' => $leelootagid, 'moodleid' => $returnid]);
                } else {
                    array_push($tagsreturnarr, ['tag_id' => $leelootagid, 'moodleid' => $istagexist->id]);
                }
            }
        }
    }

    if (!empty($tagsreturnarr)) {
        echo json_encode($tagsreturnarr);die;
    }
}

// Delete tags from leeloo to moodle.
if (isset($reqdeletedtagid)) {
    $id = json_decode($reqdeletedtagid, true);
    $conditions = array('id' => $id);
    $DB->delete_records('tag', $conditions);
    $conditions = array('tagid' => $id);
    $DB->delete_records('tag_instance', $conditions);
    die;
}

// Update tags from leeloo to moodle.
if (isset($reqorgtagid)) {
    $originaltag = json_decode($reqorgtagid, true);
    $id = $originaltag['id'];

    $istagexist = $DB->get_record('tag', ['id' => $id], 'id');

    if (!empty($istagexist)) {
        $DB->update_record('tag', $originaltag);

        if (!empty($requpdatedtagdata)) {
            $tagsdataarrobj = (object) json_decode($requpdatedtagdata, true);
        }

        if (!empty($reqemail)) {
            $email = (object) json_decode($reqemail, true);
            $userdata = $DB->get_record('user', ['email' => $email->scalar], 'id');
            $userid = $userdata->id;
        }

        $tagsreturnarr = [];
        $tagids = [];

        if (!empty($tagsdataarrobj) && !empty($userid)) {
            foreach ($tagsdataarrobj as $key => $tagsdata) {
                $istagexist = $DB->get_record('tag', ['name' => $tagsdata['name']], 'id');

                $leelootagid = $tagsdata['id'];

                if (empty($istagexist)) {
                    unset($tagsdata['id']);
                    unset($tagsdata['task_id']);

                    $tagsdata['tagcollid'] = 1;
                    $tagsdata['userid'] = $userid;

                    $returnid = $DB->insert_record('tag', $tagsdata);
                    array_push($tagsreturnarr, ['tag_id' => $leelootagid, 'moodleid' => $returnid]);
                } else {
                    $returnid = $istagexist->id;
                    array_push($tagsreturnarr, ['tag_id' => $leelootagid, 'moodleid' => $istagexist->id]);
                }
                array_push($tagids, $returnid);

                // insert tag instance
                $taginstanceexistclock = $DB->get_record('tag_instance', ['tagid' => $id, 'itemid' => $returnid], 'id');

                $taginstanceexistanticlock = $DB->get_record('tag_instance', ['tagid' => $returnid, 'itemid' => $id], 'id');

                if (empty($taginstanceexistclock) && empty($taginstanceexistanticlock)) {
                    $taginstancedata1 = [
                        'tagid' => $id,
                        'component' => 'core',
                        'itemtype' => 'tag',
                        'itemid' => $returnid,
                        'contextid' => '1',
                        'tiuserid' => '0',
                        'ordering' => '0',
                        'timecreated' => strtotime(date('Y-m-d H:i:s')),
                        'timemodified' => strtotime(date('Y-m-d H:i:s')),
                    ];

                    $taginstancedata2 = [
                        'tagid' => $returnid,
                        'component' => 'core',
                        'itemtype' => 'tag',
                        'itemid' => $id,
                        'contextid' => '1',
                        'tiuserid' => '0',
                        'ordering' => '0',
                        'timecreated' => strtotime(date('Y-m-d H:i:s')),
                        'timemodified' => strtotime(date('Y-m-d H:i:s')),
                    ];

                    $DB->insert_record('tag_instance', $taginstancedata1);
                    $DB->insert_record('tag_instance', $taginstancedata2);
                }
            }
        } //$tagsdataarrobj end

        if (!empty($tagids)) {
            $tagidsstr = implode(',', $tagids);

            $tagfordelete = $DB->get_records_sql("SELECT tagid FROM {tag_instance} where itemid = ? AND tagid NOT IN (?) ", [$id, $tagidsstr]);
        } else {

            $tagfordelete = $DB->get_records_sql("SELECT tagid FROM {tag_instance} where itemid = ?", [$id]);
        }

        if (!empty($tagfordelete)) {
            foreach ($tagfordelete as $key => $value) {
                $sql = "SELECT tagid FROM {tag_instance} WHERE itemid = ? or tagid = ?";
                $tagsfordelete = $DB->get_records_sql($sql, [$value->tagid, $value->tagid]);

                $DB->execute("DELETE FROM {tag_instance} where itemid = ? AND tagid = ?", [$value->tagid, $id]);
                $DB->execute("DELETE FROM {tag_instance} where itemid = ? AND tagid = ?", [$id, $value->tagid]);

                if (!empty($tagsfordelete)) {
                    $i = 0;

                    foreach ($tagsfordelete as $key => $value) {
                        $sql = "SELECT tagid FROM {tag_instance} WHERE tagid = ?";
                        $istagexistt = $DB->get_record_sql($sql, [$value->tagid]);

                        if (empty($istagexistt)) {
                            $DB->delete_records('tag', ['id' => $value->tagid, 'isstandard' => '0']);
                        }
                    }
                }
            }
        }

        if (!empty($tagsreturnarr)) {
            echo json_encode($tagsreturnarr);die;
        } else {
            echo "0";die;
        }
    }
}

// Update tags from leeloo to moodle.
if (isset($requpdatedtagflag)) {
    $tagdata = json_decode($requpdatedtagflag, true);
    $id = $tagdata['id'];

    if (!empty($reqemail)) {
        $email = (object) json_decode($reqemail, true);
        $userdata = $DB->get_record('user', ['email' => $email->scalar], 'id');
        $userid = $userdata->id;
    }

    $istagexist = $DB->get_record('tag', ['id' => $id], 'id');

    if (!empty($istagexist) && !empty($userid)) {
        $DB->update_record('tag', $tagdata);
    }
}

// Combine tags from leeloo to moodle.
if (isset($reqcombtagdata)) {
    $tagdata = json_decode($reqcombtagdata, true);
    $id = $tagdata['updated_id'];
    $deletedids = $tagdata['deleted_ids'];

    if (!empty($reqemail)) {
        $email = (object) json_decode($reqemail, true);
        $userdata = $DB->get_record('user', ['email' => $email->scalar], 'id');
        $userid = $userdata->id;
    }

    $istagexist = $DB->get_record('tag', ['id' => $id], 'id');

    if (!empty($istagexist) && !empty($userid)) {
        $DB->execute("DELETE FROM {tag} where id != ? AND id IN (?) ", [$id, $deletedids]);

        $sql = "SELECT itemid FROM {tag_instance} WHERE tagid IN (?) GROUP BY itemid ";
        $tagsforinsert = $DB->get_records_sql($sql, [$deletedids]);

        if (!empty($tagsforinsert)) {
            foreach ($tagsforinsert as $key => $value) {
                $taginstanceexistclock = $DB->get_record('tag_instance', ['tagid' => $id, 'itemid' => $value->itemid], 'id');

                $taginstanceexistanticlock = $DB->get_record('tag_instance', ['tagid' => $value->itemid, 'itemid' => $id], 'id');

                if (empty($taginstanceexistclock) && empty($taginstanceexistanticlock)) {
                    $taginstancedata1 = [
                        'tagid' => $id,
                        'component' => 'core',
                        'itemtype' => 'tag',
                        'itemid' => $value->itemid,
                        'contextid' => '1',
                        'tiuserid' => '0',
                        'ordering' => '0',
                        'timecreated' => strtotime(date('Y-m-d H:i:s')),
                        'timemodified' => strtotime(date('Y-m-d H:i:s')),
                    ];

                    $taginstancedata2 = [
                        'tagid' => $value->itemid,
                        'component' => 'core',
                        'itemtype' => 'tag',
                        'itemid' => $id,
                        'contextid' => '1',
                        'tiuserid' => '0',
                        'ordering' => '0',
                        'timecreated' => strtotime(date('Y-m-d H:i:s')),
                        'timemodified' => strtotime(date('Y-m-d H:i:s')),
                    ];

                    $DB->insert_record('tag_instance', $taginstancedata1);
                    $DB->insert_record('tag_instance', $taginstancedata2);
                }
            }
        }
    }
    echo '1';die;
}