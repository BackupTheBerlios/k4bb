<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     calendar.php
 *     Copyright (c) 2004, Peter Goodman

 *     Permission is hereby granted, free of charge, to any person obtaining 
 *     a copy of this software and associated documentation files (the 
 *     "Software"), to deal in the Software without restriction, including 
 *     without limitation the rights to use, copy, modify, merge, publish, 
 *     distribute, sublicense, and/or sell copies of the Software, and to 
 *     permit persons to whom the Software is furnished to do so, subject to 
 *     the following conditions:

 *     The above copyright notice and this permission notice shall be 
 *     included in all copies or substantial portions of the Software.

 *     THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, 
 *     EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF 
 *     MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND 
 *     NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS 
 *     BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN 
 *     ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN 
 *     CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE 
 *     SOFTWARE.
 *********************************************************************************/

error_reporting(E_STRICT | E_ALL);

require 'forum.inc.php';

class DefaultEvent extends Event {
	public function Execute(Template $template, Session $session, $request) {
		$template = CreateAncestors($template, $template['L_CALENDAR'] .' - '. $template['L_MONTHLY']);
		
		if($template['calendarenabled'] == 1) {
			$cal = new Calendar;
			$template->content = array('file' => 'calendar.html');
			
			$year = isset($_GET['year']) ? $_GET['year'] : date("Y");
			$month = isset($_GET['month']) ? $_GET['month'] : date("m");
			
			$template['main_calendar'] = $cal->draw_calendar();

			//$year = isset($_GET['year']) ? $_GET['year'] : date("Y");
			//$month = isset($_GET['month']) ? $_GET['month'] :date("m");

			$lm = $cal->rewind_month($cal->month, $cal->year);
			$template['prev_month'] = $cal->draw_calendar(1, 1, $lm['m'], $lm['y'], TRUE);

			$nm = $cal->advance_month($cal->month+1, $cal->year, TRUE);
			$template['next_month'] = $cal->draw_calendar(1, 1, $nm['m'], $nm['y'], TRUE);
		} else {
			return new Error($template['L_FEATUREDENIED'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class YearlyView extends Event {
	public function Execute(Template $template, Session $session, $request) {
		$template = CreateAncestors($template, $template['L_CALENDAR'] .' - '. $template['L_YEARLY']);
		
		if($template['calendarenabled'] == 1) {
		
			$cal = new Calendar;
			
			$year	= isset($request['year']) ? $request['year'] : date("Y");

			$table = '<div class="forum_content"><table width="100%" cellpadding="0" cellspacing="1" border="0"><tr class="panel">';
			for($x = 0; $x < 12; $x++) {
				if(($x % 3 == 0) && $x != 0)
					$table .= '</tr><tr class="panel">';
				$table .= '<td align="center">'. $cal->draw_calendar(1, 1, $x+1, $year, TRUE) .'</td>';
			}
			$table .= '</tr></table></div>';
			
			$template->content = array('file' => 'calendar_year.html');
			$template['yearly_table'] = $table;
		} else {
			return new Error($template['L_FEATUREDENIED'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

class WeeklyView extends Event {
	public function Execute(Template $template, Session $session, $request) {
		$template = CreateAncestors($template, $template['L_CALENDAR'] .' - '. $template['L_YEARLY']);
		
		if($template['calendarenabled'] == 1) {

			$cal = new Calendar;

			$template->content = array('file' => 'calendar_week.html');
			
			/* ------------------------------------------------------------
					THIS IS ALL TO FIND THE WEEK IF ONE WAS NOT GIVEN
			--------------------------------------------------------------*/
			if(!isset($request['week']) || !$request['week'] || intval($request['week']) == 0) {
				$day_of_week = date("w", mktime(0,0,0,date("n"),1,date("Y"))); // the day of the week 0 - 6
				$last_month = (date("n") != 1) ? date("n")-1 : 12; // the number of last month 1- 12

				$day_of_month = date("j"); // the day of this month 1 - 31
				$year = ($last_month != 1) ? date("Y") : date("Y")-1; // year of the previous month
				$prev_month = date("t", mktime(0,0,0,$last_month,1,$year)); // number of days in previous month

				/* First week of a month */
				if($day_of_month >= 1 && ($day_of_week <= 7)) {
					$start = ($prev_month - ($day_of_week - 1) ); // start of the week
					$week = mktime(0,0,0,$last_month,$start,$year);
				} else {
					$week = mktime(0,0,0,date("n"),1,date("Y"));
				}
			}
			/* -----------------------------------------------------------
				DO THE OTHER LESS COMPLICATED STUFF
			-------------------------------------------------------------*/

			$month = !isset($request['month']) ? date("n") : intval($request['month']);
			$year = !isset($request['year']) ? date("Y") : intval($request['year']);
			$week = !isset($request['week']) ? $week : intval($request['week']);
			
			$lm = $cal->rewind_month($month, $year);
			$template['prev_month'] = $cal->draw_calendar(1, 1, $lm['m'], $lm['y'], TRUE);

			$nm = $cal->advance_month($month, $year, TRUE);
			$template['next_month'] = $cal->draw_calendar(1, 1, $nm['m'], $nm['y'], TRUE);
			
			$weeks = $cal->week_range($week, $month, $year);

			//$template->week = !isset($weeks[$week-1]) ? @$weeks[$week+1] : @$weeks[$week-1];
			$template->week = $weeks;
		} else {
			return new Error($template['L_FEATUREDENIED'], $template);
		}
		/* Set the number of queries */
		$template['num_queries'] = $session->dba->num_queries;
		return TRUE;
	}
}

$app	= new Forum_Controller('forum_base.html');

$app->AddEvent('year', new YearlyView);
$app->AddEvent('week', new WeeklyView);

$app->ExecutePage();

?>