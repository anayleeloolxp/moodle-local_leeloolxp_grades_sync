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

/*require(__DIR__ . '/../../../config.php');

require_once($CFG->libdir . '/adminlib.php');

require_once($CFG->dirroot . '/lib/filelib.php');

require_once($CFG->dirroot.'/course/lib.php');*/

require(__DIR__ . '/../../config.php');

// Include adminlib.php.
require_once($CFG->libdir.'/adminlib.php');

// Include lib.php.
require_once(__DIR__ . '/lib.php');


global $DB;



 
 
//Sync Grade - letter
if (isset($_REQUEST['sync_grade_letter'])) { 

    $response = (object)json_decode($_REQUEST['sync_grade_letter'], true);
    //echo "<pre>";print_r($response);die;

    $last_inserted_id = 0;
    if (empty($response->contextid)) { // courseid
        $response->contextid =1;
    } 

    $override = $response->override; 
    unset($response->override);

    if (!empty($response->contextid) && $response->contextid != 1) { 

        $contextid = context_course::instance($response->contextid);
        $response->contextid = $contextid->id;

        /*$context_data = $DB->get_record('context', ['contextlevel'=>'50' ,'instanceid' => $response->contextid],'id');
        echo "<pre>";print_r($context_data);die;*/
    }    

    $DB->delete_records('grade_letters', ['contextid' => $response->contextid]);

    if (!empty($response)) {   
        

        if ($override != 0) { 

            $contextid = $response->contextid;
            unset($response->contextid);

            foreach ($response as $key => $value) { 

                $value['contextid'] = $contextid;

                $data = (object)$value; 

                if (!empty($last_inserted_id) && $data->lowerboundary == "0.00000") { 

                    $data->id = $last_inserted_id;              
                 
                    $DB->update_record('grade_letters', $data);

                } else { 

                    if ($data->lowerboundary == "0.00000") { 
                     
                        $last_inserted_id = $DB->insert_record('grade_letters', $data);

                    } else {               
                     
                        $DB->insert_record('grade_letters', $data);

                    }
                }
            }
        }
    }     
    
    echo $last_inserted_id;
    die; 

} 

//Sync Course Grade Settings
if (isset($_REQUEST['sync_course_grade_settings'])) { 

    $response = (object)json_decode($_REQUEST['sync_course_grade_settings'], true);
    //print_r($response);die;
    $courseid = $response->courseid;
    unset($response->courseid); 
    $last_inserted_id = 0;

    if (!empty($response)) { 

        $DB->delete_records('grade_settings', ['courseid' => $courseid]);

        foreach ($response as $key => $value) {   

            if ($value != '-1') {

                $insert_data = [
                    'courseid' => $courseid,
                    'name' => $key,
                    'value' => $value
                ]; 
                $last_inserted_id = $DB->insert_record('grade_settings', $insert_data);
            } 
        }
    }     
    
    echo $last_inserted_id;
    die; 
} 

//Sync User Preference Grade report
if (isset($_REQUEST['sync_prefrence_grader_report'])) { 

    $response = (object)json_decode($_REQUEST['sync_prefrence_grader_report'], true);
    //echo "<pre>";print_r($response); die;
    $email = $response->email;  
    unset($response->email);
    $last_inserted_id = 0; 

    $user_data = $DB->get_record('user', ['email'=>$email],'id'); 

    if (!empty($response) && !empty($user_data)) { 

        $user_id = $user_data->id;  

        foreach ($response as $key => $value) {   

            $DB->delete_records('user_preferences', ['userid' => $user_id,'name' => $key]);
            
            if ($value != 'default') { 

                $insert_data = [
                    'userid' => $user_id,
                    'name' => $key,
                    'value' => $value
                ]; 
                $last_inserted_id = $DB->insert_record('user_preferences', $insert_data);
            } 
        }
    }     
    
    echo $last_inserted_id;
    die; 
} 

//sync Scales from leeloo to moodle
if (isset($_REQUEST['sync_scales'])) { 

    $value = (object)json_decode($_REQUEST['sync_scales'], true); 
    
    $return_id = 0;
    $email = $value->email;  
    $user_data = $DB->get_record('user', ['email'=>$email],'id'); 
    if (!empty($user_data)) { 

        $user_id = $user_data->id;  

        $data = [ 
            'courseid' => $value->courseid,
            'userid' => $user_id,
            'name' => $value->name,
            'scale' => $value->scale,
            'description' => $value->description,
            'descriptionformat' => 1
        ];

        $data['timemodified'] = strtotime("now");

        if (!empty($value->moodle_scale_id)) {
            $sql = "SELECT * FROM {scale} where id = '$value->moodle_scale_id'";
            $scale_detail = $DB->get_record_sql($sql);  
            if (!empty($scale_detail)) {
                $data['id'] = $value->moodle_scale_id;
                $DB->update_record('scale', $data); 
                $return_id = $value->moodle_scale_id;
            } else { 
                $return_id = $DB->insert_record('scale', $data);
            }
        } else { 
            $return_id = $DB->insert_record('scale', $data);
        } 
    }
    
    echo $return_id;
    die; 

}

//sync Grade items and Category
if (isset($_REQUEST['categories_data'])) {  
    
    $cat_return_id = 0;
    $item_return_id = 0;
    $categories_data = (object) json_decode($_REQUEST['categories_data'], true);
    $grade_data = (object) json_decode($_REQUEST['grade_data'], true);

    $moodle_parent_id = $categories_data->moodle_parent_id; 
    unset($categories_data->moodle_parent_id); 

    if (!empty($categories_data) && !empty($moodle_parent_id)) {
        $parent_cat_data = $DB->get_record('grade_categories', ['id'=>$moodle_parent_id],'*');
        
        if (!empty($categories_data->old_cat_id)) {

            unset($categories_data->path);
            unset($categories_data->parent);
            $categories_data->id = $categories_data->old_cat_id;              
            unset($categories_data->old_cat_id);
                 
            $DB->update_record('grade_categories', $categories_data);

            $cat_return_id = $categories_data->id;

        } else {

            $table_cat = $CFG->prefix.'grade_categories';
            $sql = " SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$CFG->dbname' AND TABLE_NAME = '$table_cat' ";
            $auto_inc = $DB->get_record_sql($sql); 
            $categories_data->path = $parent_cat_data->path.$auto_inc->auto_increment.'/';
            $categories_data->parent = $moodle_parent_id;
            $cat_return_id = $DB->insert_record('grade_categories', $categories_data); 

        } 

    }

    if (!empty($grade_data)) {


        if (!empty($grade_data->item_moodle_cat_id)) {

            $grade_data->categoryid = $grade_data->item_moodle_cat_id; 

        } else {

            unset($grade_data->categoryid);

        }

        unset($grade_data->item_moodle_cat_id);

        if (!empty($grade_data->old_item_id)) {
            //echo "<pre>";print_r($grade_data);die;
            $grade_data->id = $grade_data->old_item_id;       

            unset($grade_data->old_item_id);
            unset($grade_data->weightoverride);
                 
            $DB->update_record('grade_items', $grade_data);

            $item_return_id =  $grade_data->id;

        } else {

            if (!empty($cat_return_id)) {

                $grade_data->iteminstance = $cat_return_id; 

                $item_return_id = $DB->insert_record('grade_items', $grade_data); 

            } elseif (!empty($grade_data->categoryid)) {

                unset($grade_data->iteminstance);

                $item_return_id = $DB->insert_record('grade_items', $grade_data); 

            }

        }
        
    }

    echo $cat_return_id.','.$item_return_id;die;

}

//delete grade item 
if (isset($_REQUEST['delete_grade_item'])) { 

    $response = (object)json_decode($_REQUEST['delete_grade_item'], true); 

    if (!empty($response->id)) { 

        $DB->delete_records('grade_items', ['id' => $response->id]); 
    }     
    
    echo '1';
    die; 
}

function change_cate_order($child_cats,$current_cat,$depth = null)
{
    global $DB;

    foreach ($child_cats as $key => $value) {

        $path = str_replace('/'.$current_cat->id, '', $value->path);
        
        if (empty($depth)) {
            $depth = $current_cat->depth;
            $parent = $current_cat->parent;
        } else {
            $parent = $value->parent;
        }
        $categories_data = [
            'parent' => $parent,
            'depth' => $depth,
            'path' => $path
        ]; 
        $categories_data = (object)$categories_data;  

        $categories_data->id = $value->id;   
             
        $DB->update_record('grade_categories', $categories_data);

        $child_cat_current = $DB->get_records('grade_categories', ['parent'=>$value->id]);

        if (!empty($child_cat_current)) {
            
            change_cate_order($child_cat_current,$current_cat,$value->depth);
        }
    }
}
//delete grade category 
if (isset($_REQUEST['delete_grade_category'])) { 

    $response = (object)json_decode($_REQUEST['delete_grade_category'], true);  
 
    if (!empty($response->id)) { 

        //re-arrenge category parent child relation
        $parent_cat_data = $DB->get_records('grade_categories', ['parent'=>$response->id]);
        
        if (!empty($parent_cat_data)) { 

            $current_cat = $DB->get_record('grade_categories', ['id'=>$response->id],'*');
            change_cate_order($parent_cat_data,$current_cat);

        } 

        //update parent of grade item 
        $sql = "SELECT id FROM {grade_items} WHERE categoryid = '$response->id' ";
        $child_items = $DB->get_records_sql($sql);
        $current_cat = $DB->get_record('grade_categories', ['id'=>$response->id],'*');

        if (!empty($child_items)) { 
            foreach ($child_items as $key => $value) { 

                $itemms_data = [
                    'categoryid' => $current_cat->parent
                ]; 
                $itemms_data = (object)$itemms_data;

                $itemms_data->id = $value->id; 
             
                $DB->update_record('grade_items', $itemms_data); 
                
            }
        }

        //delete category items 
        $sql = "SELECT * FROM {grade_items} WHERE iteminstance = '$response->id' AND itemtype != 'mod' ";

        $result = $DB->get_records_sql($sql);

        foreach ($result as $key => $value) {
            $DB->delete_records('grade_items', ['id' => $value->id]); 
        }       

        $DB->delete_records('grade_categories', ['id' => $response->id]); 
    }     
    
    echo '1';
    die; 
}  

//Hide/Show grade item and category 
if (isset($_REQUEST['hidden_data'])) { 

    $response = (object)json_decode($_REQUEST['hidden_data'], true); 

    if (!empty($response->id) && isset($response->hidden)) { 
        
        $data = [
            'hidden' => $response->hidden
        ];

        $data = (object)$data;

        $data->id = $response->id; 

        if (!empty($response->is_item)) { 

            $DB->update_record('grade_items', $data); 

        } else { 

            $DB->update_record('grade_categories', $data); 

            $sql = "UPDATE {grade_items} SET `hidden` = '$response->hidden' WHERE `categoryid` = '$response->id' or  `iteminstance` = '$response->id' "; 

            $DB->execute($sql);

        }

    }     
    
    echo '1';
    die; 
}


//Duplicate grade item  
if (isset($_REQUEST['duplicate_data'])) { 

    $response = (object)json_decode($_REQUEST['duplicate_data'], true); 

    $return_id = 0;

    if (!empty($response->id)) { 
        
        $grade_data = $DB->get_record('grade_items', ['id'=>$response->id]);

        unset($grade_data->id); 

        $grade_data->itemname = $grade_data->itemname.' (copy)'; 

        $return_id = $DB->insert_record('grade_items', $grade_data);

    }     
    
    echo $return_id;
    die; 
}


//Hide/Show grade item and category 
if (isset($_REQUEST['gradeitem_order_change_data'])) { 

    $response = (object)json_decode($_REQUEST['gradeitem_order_change_data'], true); 
    $cat_id = (object)json_decode($_REQUEST['category_id'], true); 

    if (!empty($response) && !empty($cat_id->moodle_cat_id)) { 
        
        foreach ($response as $key => $value) { 
            //echo " <pre>";print_r($value['moodle_tbl_id']); die; 

            $items_data = [ 'categoryid' => $cat_id->moodle_cat_id ];

            $items_data = (object)$items_data; 
 

            $items_data->id = $value['moodle_tbl_id'];              
         
            $DB->update_record('grade_items', $items_data); 
        }

    }     
    
    echo '1';
    die; 
}
