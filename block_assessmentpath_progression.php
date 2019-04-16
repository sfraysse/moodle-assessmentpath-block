<?php

/* * *************************************************************
 *  This script has been developed for Moodle - http://moodle.org/
 *
 *  You can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
  *
 * ************************************************************* */

class block_assessmentpath_progression extends block_base {

	public function init() {
		$this->title = get_string('progress', 'assessmentpath');
	}

	public function get_content() {
		
		// Globals
		global $COURSE, $USER, $CFG, $DB;
	
		// Includes
		require_once($CFG->dirroot.'/mod/scormlite/report/reportlib.php');
		require_once($CFG->dirroot.'/mod/assessmentpath/report/reportlib.php');

		// Checks
		if ($this->content !== null) return $this->content; // Detect if module enabled
		if (empty($COURSE)) return null; // Should not happen as this block should be displayed only in the context of a course
		
		// Useful objects and vars
		$course = $COURSE;
		$courseid = $COURSE->id;
		$userid = $USER->id;
		
		// Permissions
		$context = context_course::instance($courseid, MUST_EXIST);
		$fullmode = has_capability('mod/scormlite:viewotherreport', $context);
		
		
		//
		// Groupings
		//

		if ($fullmode) $groupings = scormlite_report_get_course_groupings($courseid, "assessmentpath");
		else $groupings = scormlite_report_get_user_groupings($courseid, $userid, "assessmentpath");
		if (empty($groupings)) return null;
		
		$html = '';
		foreach ($groupings as $groupingid => $grouping) {
		
			//
			// Fetch data
			//
		
			$activities = array();
			$users = array();
			$scoids = assessmentpath_report_populate_activities($activities, $courseid, $groupingid);
			$userids = scormlite_report_populate_users($users, $courseid, $groupingid);
			$statistics = assessmentpath_report_populate_course_progress($activities, $users, $scoids, $userids);
			
			//
			// HTML
			//
		
			if ($statistics !== false) {
                
                // KD2015-AP01 - Moved here to avoid notice
                $progress = $statistics->progress;
                $progresslabel = sprintf("%01.1f", $progress).'%';
                
				$html .= '<div class="grouping">';
				
				// Title
				if ($fullmode) {
					$p2_url = assessmentpath_report_get_url_P2($courseid, $groupingid);
					$html .= "<div class='title'><a href='$p2_url'>$grouping->name</a></div>";
				} else {
					$html .= "<div class='title'>$grouping->name</div>";
				}
				
				// Progress bar
				$p0_url = $CFG->wwwroot.'/course/report/assessmentpath/report/P0.php?groupingid='.$groupingid.'&courseid='.$courseid;
				$html .= '
					<table class="progressbar_container"><tr>
						<td class="progressbar">
							<div class="bar" onClick="location.href=\''.$p0_url.'\'">
								<div class="progress" style="width:'.$progress.'%"></div>
							</div>
						</td>
						<td class="percent"><a href="'.$p0_url.'">'.$progresslabel.'</a></td>
					</tr></table>
				';
				
				// Link to P1 (for students only)
				if (!$fullmode) {
					$html .= '<a class="P1link" href="'.assessmentpath_report_get_url_P1($courseid, $userid, $groupingid).'">'.get_string('MyP1', 'assessmentpath').'</a>';
				}
				
				// End of group	
				$html .= '</div>';
			} 
		}
		$this->content = new stdClass;
		$this->content->text = $html;
		return $this->content;
	}

	public function applicable_formats() {
		return array(
			'course' => true,
			'mod-assessmentpath' => true);
	}
}

?>