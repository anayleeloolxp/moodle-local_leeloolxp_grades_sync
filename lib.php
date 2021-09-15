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
 * Plugin administration pages are defined here.
 *
 * @package     local_leeloolxp_grades_sync
 * @category    admin
 * @copyright   2020 Leeloo LXP <info@leeloolxp.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(dirname(dirname(__DIR__)) . '/config.php');

/**
 * Plugin to sync user's  tracking on activity to LeelooLXP account of the Moodle Admin
 */
function local_leeloolxp_grades_sync_before_footer() {
	$configtat = get_config('local_leeloolxp_grades_sync');

	$licensekey = $configtat->gradelicensekey;

	$tatenabled = $configtat->grade_enabled;

	if ($tatenabled == 0) {

		return true;

	}
	$allroles = get_all_roles();
	$roles = json_encode($allroles);
	global $USER;
	global $PAGE;
	global $CFG;
	global $DB;
	$baseurl = $CFG->wwwroot;
	$useremail = $USER->email;
	$postdata = array('license_key' => $licensekey);
	$url = 'https://leeloolxp.com/api_moodle.php/?action=page_info';
	$curl = new curl;
	$options = array(
		'CURLOPT_RETURNTRANSFER' => true,
		'CURLOPT_HEADER' => false,
		'CURLOPT_POST' => count($postdata),
	);


	if (!$output = $curl->post($url, $postdata, $options)) {
		return true;
	}
	$infoteamnio = json_decode($output);
	if ($infoteamnio->status != 'false') {
		$teamniourl = $infoteamnio->data->install_url;
	} else {
		return true;
	}

	$postdata = '&roles=' . $roles;
	$url = $teamniourl.'/admin/sync_moodle_course/sync_moodle_roles/';
	$curl = new curl;
	$options = array(
		'CURLOPT_RETURNTRANSFER' => true,
		'CURLOPT_HEADER' => false,
		'CURLOPT_POST' => 1,
	);

	$output = $curl->post($url, $postdata, $options);

	$PAGE->requires->jquery();
	// echo $PAGE->pagetype;
	if($PAGE->pagetype == 'admin-setting-gradessettings'||
	$PAGE->pagetype == 'admin-setting-gradecategorysettings' ||
	$PAGE->pagetype == 'admin-setting-gradeitemsettings' ||
	$PAGE->pagetype == 'admin-grade-edit-scale-edit' ||
	$PAGE->pagetype == 'grade-edit-scale-edit' ||
	$PAGE->pagetype == 'admin-grade-edit-letter-index' ||
	$PAGE->pagetype == 'grade-edit-letter-index' ||
	$PAGE->pagetype == 'admin-setting-gradereportgrader' ||
	$PAGE->pagetype == 'admin-setting-gradereporthistory' ||
	$PAGE->pagetype == 'admin-setting-gradereportoverview' ||
	$PAGE->pagetype == 'admin-setting-gradereportuser' ||
	$PAGE->pagetype == 'grade-edit-settings-index' ||
	$PAGE->pagetype == 'grade-report-grader-preferences' ||
	$PAGE->pagetype == 'admin-grade-edit-scale-index' ||
	$PAGE->pagetype == 'grade-edit-scale-index' ||
	$PAGE->pagetype == 'grade-edit-tree-index' ||
	$PAGE->pagetype == 'grade-edit-tree-category' ||
	$PAGE->pagetype == 'mod-workshop-submission' ||
	$PAGE->pagetype == 'course-togglecompletion') {

	$table_cat = $CFG->prefix.'scale';
	$sql = " SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$CFG->dbname' AND TABLE_NAME = '$table_cat' ";
	$auto_inc = $DB->get_record_sql($sql);
	$auto_increment = $auto_inc->auto_increment;

	$table_cat = $CFG->prefix.'course_completions';
	$sql = " SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$CFG->dbname' AND TABLE_NAME = '$table_cat' ";
	$auto_inc = $DB->get_record_sql($sql);
	$auto_increment_course_completions = $auto_inc->auto_increment;

	$modulerecords = $DB->get_record_sql("SELECT MAX(id) as max_id FROM {scale}");
	if(!empty($modulerecords)) {
		$scale_max_id =  $modulerecords->max_id;
	}  else {
		$scale_max_id =0;
	}

	$workshopgardearsyncid = 0;

	if ($PAGE->pagetype == 'mod-workshop-submission') {

		$idid = $_REQUEST['id'];

		$maindata = $DB->get_record_sql("SELECT wg.id FROM {workshop_grades}

            as wg JOIN {workshop_assessments} as wa  ON wa.id = wg.assessmentid

            WHERE wa.submissionid = '$idid' ");

		if (!empty($maindata) && !empty($maindata->id)) {
			$workshopgardearsyncid = $maindata->id;
		}
	}

	 ?>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	   <script type="text/javascript">

	   	var workshopgardearsyncid = "<?php echo $workshopgardearsyncid; ?>"


		// delete  workshop ar grades
	   	if (workshopgardearsyncid != 0) {

		   	$( "#page-mod-workshop-submission .btn-primary" ).click(function() {

			   var datas = {};

			   var dataArray = $('form').serialize();

			   $.ajax({

					async:false,

					url: "<?php echo $teamniourl; ?>/admin/sync_moodle_course/delete_workshop_grade/?"+dataArray+'&id='+workshopgardearsyncid,

					type: "post",

					data: {},

					success: function(tdata){   }

				});

			});
		}


		// auto_increment_course_completions
		$( "#page-course-togglecompletion .btn-primary" ).click(function(e) {

		   // e.preventDefault();
		   var datas = {};

		   var dataArray = $('form').serialize()+'&email='+"<?php echo base64_encode($useremail); ?> "+'&id='+"<?php echo $auto_increment_course_completions; ?> ";

		   $.ajax({

				async:false,

				url: "<?php echo $teamniourl; ?>/admin/sync_moodle_course/insert_update_course_completion/?"+dataArray,

				type: "post",

				data: {},

				success: function(tdata){    }

			});

		});

		$( "#page-grade-edit-tree-category .btn-primary" ).click(function() {

		   var datas = {};

		   var dataArray = $('form').serialize();

		   $.ajax({

				async:false,

				url: "<?php echo $teamniourl; ?>/admin/sync_moodle_course/add_grade_category/?"+dataArray,

				type: "post",

				data: {},

				success: function(tdata){  /*console.log(tdata);alert(tdata)*/  }

			});

		});

		$( "#page-grade-edit-tree-index .btn-primary" ).click(function() {

		   var datas = {};

		   var dataArray = $('form').serialize();

		   $.ajax({

				async:false,

				url: "<?php echo $teamniourl; ?>/admin/sync_moodle_course/delete_grade_items/?"+dataArray,

				type: "post",

				data: {},

				success: function(tdata){   }

			});

		});


		$( "#page-admin-setting-gradessettings .btn-primary" ).click(function() {

		   var datas = {};

		   var dataArray = $('form').serialize();

		   $.ajax({

				async:false,

				url: "<?php echo $teamniourl; ?>/admin/sync_moodle_course/sync_grades/?"+dataArray,

				type: "post",

				data: {},

				success: function(tdata){}

			});
		});

		$( "#page-admin-setting-gradecategorysettings .btn-primary" ).click(function() {

		   var datas = {};

		   var dataArray = $('form').serialize();

		   $.ajax({

				async:false,

				url: "<?php echo $teamniourl; ?>/admin/sync_moodle_course/gradecategorysettings/?"+dataArray,

				type: "post",

				data: {},

				success: function(tdata){

				}

			});
			});
		$( "#page-admin-setting-gradeitemsettings .btn-primary" ).click(function() {

		   var datas = {};

		   var dataArray = $('form').serialize();

		   $.ajax({

				async:false,

				url: "<?php echo $teamniourl; ?>/admin/sync_moodle_course/gradeitemsettings/?"+dataArray,

				type: "post",

				data: {},

				success: function(tdata){

				}

			});
		});

		$( "#page-admin-grade-edit-scale-edit .btn-primary" ).click(function() {
			//alert();
		var datas = {};
		var scale_max_id = '<?php echo $scale_max_id; ?>';
		var auto_increment = '<?php echo $auto_increment; ?>';

		var dataArray = $('form').serialize()+'&email='+"<?php echo $useremail; ?>";
		$.ajax({
			async:false,
			url: "<?php echo $teamniourl; ?>/admin/sync_moodle_course/scale/?"+dataArray+'&max_id='+scale_max_id,
			type: "post",
			data: {auto_increment:auto_increment},
			success: function(tdata){
				//alert(tdata);
			}

		});

	});

	$( "#page-grade-edit-scale-edit .btn-primary" ).click(function() {
		var datas = {};
		var scale_max_id = '<?php echo $scale_max_id; ?>';
		var auto_increment = '<?php echo $auto_increment; ?>';

		var dataArray = $('form').serialize()+'&email='+"<?php echo $useremail; ?>";
		$.ajax({
			async:false,
			url: "<?php echo $teamniourl; ?>/admin/sync_moodle_course/scale/?"+dataArray+'&max_id='+scale_max_id,
			type: "post",
			data: {auto_increment:auto_increment},
			success: function(tdata){
				//alert(tdata);
			}

		});
	});
	$( "#page-admin-grade-edit-letter-index .btn-primary" ).click(function() {
		var datas = {};
		var dataArray = $('form').serialize();
		$.ajax({

			async:false,

			url: "<?php echo $teamniourl; ?>/admin/sync_moodle_course/gradeeditletter/?"+dataArray,

			type: "post",

			data: {},

			success: function(tdata){

				console.log(tdata)
				//alert(tdata)

			}

		});
	});

	$( "#page-grade-edit-letter-index .btn-primary" ).click(function() {
		var datas = {};
		var dataArray = $('form').serialize()+'&course_id='+"<?php echo $PAGE->course->id; ?>";
		$.ajax({

			async:false,

			url: "<?php echo $teamniourl; ?>/admin/sync_moodle_course/gradeeditletter/?"+dataArray,

			type: "post",

			data: {},

			success: function(tdata){

				console.log(tdata)

			}

		});
	});


	$( "#page-admin-setting-gradereportgrader .btn-primary" ).click(function() {
		var datas = {};
		var dataArray = $('form').serialize();
		$.ajax({

			async:false,
			url: "<?php echo $teamniourl; ?>/admin/sync_moodle_course/gradereportgrader/?"+dataArray,

			type: "post",

			data: {},

			success: function(tdata){

			}

		});
	});

	$( "#page-admin-setting-gradereporthistory .btn-primary" ).click(function() {
		var datas = {};
		var dataArray = $('form').serialize();
		$.ajax({

			async:false,

			url: "<?php echo $teamniourl; ?>/admin/sync_moodle_course/gradereporthistory/?"+dataArray,

			type: "post",

			data: {},

			success: function(tdata){

			}

		});
	});
	$( "#page-admin-setting-gradereportoverview .btn-primary" ).click(function() {
		var datas = {};
		var dataArray = $('form').serialize();
		$.ajax({

			async:false,

			url: "<?php echo $teamniourl; ?>/admin/sync_moodle_course/gradereportoverview/?"+dataArray,

			type: "post",

			data: {},

			success: function(tdata){

			}

		});
	});

	$( "#page-admin-setting-gradereportuser .btn-primary" ).click(function() {
		var datas = {};
		var dataArray = $('form').serialize();
		$.ajax({

			async:false,

			url: "<?php echo $teamniourl; ?>/admin/sync_moodle_course/gradereportuser/?"+dataArray,

			type: "post",

			data: {},

			success: function(tdata){

			}
		});

	});


	$( "#page-grade-edit-settings-index .btn-primary" ).click(function() {
		var datas = {};
		var dataArray = $('form').serialize();
		$.ajax({

			async:false,

			url: "<?php echo $teamniourl; ?>/admin/sync_moodle_course/course_grade_setting/?"+dataArray,

			type: "post",

			data: {},

			success: function(tdata){

			}
		});

	});

	$( "#page-grade-report-grader-preferences .btn-primary" ).click(function() {
		var datas = {};
		var dataArray = $('form').serialize()+'&email='+"<?php echo $useremail; ?>";
		$.ajax({

			async:false,

			url: "<?php echo $teamniourl; ?>/admin/sync_moodle_course/grade_report_preferences/?"+dataArray,

			type: "post",

			data: {},

			success: function(tdata){

			}
		});

	});

	//delete scale from moodle also
	$( "#page-admin-grade-edit-scale-index .btn-primary ,#page-grade-edit-scale-index .btn-primary " ).click(function() {

		var dataArray = $('form').serialize();

		$.ajax({

			async:false,

			url: "<?php echo $teamniourl; ?>/admin/sync_moodle_course/delete_global_scale/?"+dataArray,

			type: "post",

			data: {},

			success: function(tdata){

			}
		});

	});
	</script>

<?php } 

}
