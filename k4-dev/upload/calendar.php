<?php
/**
* k4 Bulletin Board, files.php
*
* Copyright (c) 2005, Peter Goodman
*
* Permission is hereby granted, free of charge, to any person obtaining
* a copy of this software and associated documentation files (the
* "Software"), to deal in the Software without restriction, including
* without limitation the rights to use, copy, modify, merge, publish,
* distribute, sublicense, and/or sell copies of the Software, and to
* permit persons to whom the Software is furnished to do so, subject to
* the following conditions:
*
* The above copyright notice and this permission notice shall be
* included in all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
* MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
* BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
* ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
* CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*
* @author Peter Goodman
* @version $Id: calendar.php,v 1.1 2005/05/24 20:09:16 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

require 'forum.inc.php';

define('CURR_YEAR',		strftime("%Y", bbtime()));
define('CURR_MONTH',	strftime("%m", bbtime()));
define('CURR_DAY',		strftime("%d", bbtime()));

class Calendar {
	var $year;
	var $month;
	var $day;
	var $days_in_month = array();

	function Calendar($year = CURR_YEAR, $month = CURR_MONTH, $day = CURR_DAY) {
		$this->year						= $year;
		$this->month					= $month;
		$this->day						= $day;
		
		for($i = 1; $i <= 12; $i++) {
			$this->days_in_month[$i] = date("t", bbtime( mktime(0, 0, 0, $i, 0, $this->year) ) );	
		}
	}

	function first_day_of_month($year, $month) {
		$seconds	= mktime(0, 0, 0, $month, 0, $year);
		return(date("%u", bbtime($seconds)));
	}
	function convert_day_number($day, $month, $year) {
		$seconds	= mktime(0,0,0, $month, $day, $year);
		return(strftime("%e", bbtime($seconds)));
	}

	/**
	 * month_array
	 * @author Peter Goodman
	 * @param month int 1-12 month
	 * @param year int year of whatever month the person is looking at
	 */
	function month_array($month, $year) {
		
		$prev_month				= bbtime( mktime(0, 0, 0, $month-1, 0, $year) );
		$this_month				= bbtime( mktime(0, 0, 0, $month, 0, $year) );
		$next_month				= bbtime( mktime(0, 0, 0, $month+1, 0, $year) );
		
		$days					= array();
		$num_days				= date("t", $month );
		$num_days_prev_month	= date("t", $prev_month );
		$num_days_next_month	= date("t", $next_month );
		
		/* Pad the start of the days array with the last days of the previous month */
		if(strftime("%w", $this_month) > 0) {
			
			for($i = ($num_days_prev_month - (strftime("%w", $this_month) + 1) ); $i <= (strftime("%w", $this_month) + 1); $i++) {
				$days[] = array('day' => strftime("%d", bbtime( mktime(0, 0, 0, $month-1, $i, $year) )), 'week' => strftime("%U", $prev_month),'month' => strftime("%m", $prev_month), 'year' => strftime("%Y", $prev_month));
			}
			
		}
		
		/* Go through the normal days of our month */
		for($i = 1; $i <= $num_days; $i++) {
			for($i = 1; $i <= $num_days; $i++) {
				$days[] = array('day' => strftime("%d", bbtime( mktime(0, 0, 0, $month, $i, $year) )), 'week' => strftime("%U", $this_month),'month' => strftime("%m", $this_month), 'year' => strftime("%Y", $this_month));
			}
		}

		/* Pad the end of the days array with the first days of the next month */
		if(strftime("%w", bbtime( mktime(0, 0, 0, $month, $num_days, $year) )) < 6) {
			
			for($i = 1; $i <= (7 - (strftime("%w", $next_month) + 1)); $i++) {
				$days[] = array('day' => strftime("%d", bbtime( mktime(0, 0, 0, $month+1, $i, $year) )), 'week' => strftime("%U", $next_month),'month' => strftime("%m", $next_month), 'year' => strftime("%Y", $next_month));
			}
			
		}

		return $days;
	}

}


class DefaultEvent extends Event {
	function Execute(&$template, $request, &$dba, &$session, &$user) {
		/*
		$timer		= &new Timer();
		
		for($i = 0; $i < 20; $i ++) {
			mail("peter.goodman@gmail.com", "test", "test test", "From: \"k4 BB Forums\" <noreply@k4bb.org>");
		}

		echo $timer->__toString();
		*/

		//print_r(Calendar::month_array(3, 2005));
	}
}

$app	= new Forum_Controller('forum_base.html');

$app->ExecutePage();

?>