<?php
/**********************************************************************************
 *     k4 Bulletin Board
 *     calendar.class.php
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

class Config {
	
	public $days = array();
	public $num_months = 1;
	public $display_extras = TRUE;

	public function __construct() {
		$this->days = Array('S', 'M', 'T', 'W', 'T', 'F', 'S');
		$this->num_months = 1;
	}
}

class Calendar {
	
	protected $config			= '';
	protected $last_year		= '';
	protected $last_month		= '';
	protected $next_month		= '';
	protected $next_year		= '';
	protected $dba;
	public $year;
	public $month;

	public function __construct() {
		$url = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
		$this->config		= new Config;
		$this->last_year	= new Url($url);
		$this->last_month	= new Url($url);
		$this->next_year	= new Url($url);
		$this->next_month	= new Url($url);
		$this->dba			= DBA::Open();

	}

	public function draw_calendar($num_months = 1, $size = 2, $month = FALSE, $year = FALSE, $override = FALSE) {
		
		if(!$override) {
			$month	= !isset($month) || !$month ? date("m") : $month;
			$year	= !isset($year) || !$year ? date("Y") : $year;

			$year	= isset($_GET['year']) ? $_GET['year'] : $year;
			$month	= isset($_GET['month']) ? $_GET['month'] : $month;
		}
		$this->year = $year; $this->month = $month;
		
		$r_m = $this->rewind_month($month, $year);
		$a_m = $this->advance_month($month, $year);
		$a_y = $year != date("Y") ? $year+1 : $year;
		
		// rewinding		
		$this->last_year['year'] = $year-1;		
		$this->last_month['year'] = $r_m['y'];
		$this->last_month['month'] = $r_m['m'];
		
		// advancing		
		$this->next_year['year'] = $a_y;		
		$this->next_month['year'] = $a_m['y'];
		$this->next_month['month'] = $a_m['m'];
		
		// Size things
		$cell_height	= $size == 2 ? 30 : 15; 
		$font_size		= $size == 2 ? 12 : 10;
		$table_width	= $size == 2 ? '100%' : '200px';
		$cell_style		= $size == 2 ? 'border-bottom:1px dashed #C6C6C6;width:95%;' : 'text-align:center;';
		$img_dir		= get_setting('template', 'imgfolder');
		
		global $lang;

		// Making some links
		$rewind = '<a href="'. $this->last_year->__toString() .'" title="'. $lang['L_LASTYEAR'] .'">&laquo;</a>&nbsp;';
		$rewind .= '<a href="'. $this->last_month->__toString() .'" title="'. $lang['L_LASTMONTH'] .'">&lt;</a>';
		
		$advance = '<a href="'. $this->next_month->__toString() .'" title="'. $lang['L_NEXTMONTH'] .'">&gt;</a>&nbsp;';
		$advance .= '<a href="'. $this->next_year->__toString() .'" title="'. $lang['L_NEXTYEAR'] .'">&raquo;</a>'; 

		// Getting month numbers
		$prev_month = date("t", mktime(0,0,0,$month,0,$year));
		$this_month = date("F", mktime(0,0,0,$month+1,0,$year));
		
		// Getting today in seconds
		$today = mktime(0,0,0,date("m"),date("j"),date("Y"));

		$var = NULL;
		$end_month = $month + $num_months;
		for ($a = $month; $a < $end_month; $a++ ) {
			if($a > 12) {
				$a = $a-12;
				$end_month = $end_month - (12-$a);
				$year++;
			}
			$max_days = date("t", mktime(0, 0, 0, $a));
			//$m = (strlen($a) == 1 && $var) ? "0". $a : $a;
			
			$var .= '<table width="'. $table_width .'" cellpadding="0" cellspacing="1" border="0">';
			if($size == 2)
				$var .= '<tr class="thead"><td colspan="2" align="left" style="font-size:'. $font_size .'px;">'. $rewind .'</td><td colspan="4" align="center" style="font-size:'. $font_size .'px;"><strong>'. $this_month .' - '. $year .'</strong></td><td colspan="2" align="right" style="font-size:'. $font_size .'px;">'. $advance .'</td></tr>';
			else
				$var .= '<tr class="thead"><td colspan="8" align="center" style="font-size:'. $font_size .'px;"><strong>'. $this_month .' - '. $year .'</strong></td></tr>';
			$var .= '<tr class="panel">';
			$var .= '<td align="center" style="font-size:'. $font_size .'px;">&nbsp;</td>';
			foreach($this->config->days as $key => $val) { 
				$var .= '<td align="center" style="font-size:'. $font_size .'px;">'. $val .'</td>'; 
			}
			
			$var .= '</tr><tr>';

			/* Get the numeric first week of this month, and then just increment it */
			$week = date("W", mktime(0,0,0,$a,1,$year)) > 52 ? 1 : date("W", mktime(0,0,0,$a,1,$year));

			for($day = 1; $day <= $max_days; $day++ ) { 
				
				$day_of_week = date("w", mktime(0,0,0,$a,$day,$year));
				$end_filler_days = 6-$day_of_week;
				
				if ($day == 1) {
					
					$b = ($prev_month - ($day_of_week - 1) );
					if($day_of_week != 0) {
						$curr_week = mktime(0,0,0,$r_m['m'],$b,$r_m['y']);
						$var.= "<td class=\"alt1\"><a href=\"calendar.php?act=week&amp;week=". $curr_week ."&amp;month=". $a ."&amp;year=". $year ."\"><img src=\"Images/". $img_dir ."/Icons/arrow_right.gif\" border=\"0\" /></a></td>";
						/* Increment the week number */
						$week = $week == 52 ? 1 : $week+1;

						for ($i = 0; $i < $day_of_week; $i++ ) {
							$val = ($this->config->display_extras == TRUE) ? $b : '&nbsp;';
							$var .= '<td align="center" class="alt1" style="font-size:'. $font_size .'px;"><em>'. $val .'</em></td>';
							$b++;
						}
					}
				}
				
				$this_day = mktime(0,0,0,$month,$day,$year);
				
				/* Make a new row if we need to */
				if($day_of_week == 0) {
					$curr_week = mktime(0,0,0,$a,$day,$year);
					$var .= "</tr>\n<tr><td class=\"alt1\"><a href=\"calendar.php?act=week&amp;week=". $curr_week ."&amp;month=". $a ."&amp;year=". $year ."\"><img src=\"Images/". $img_dir ."/Icons/arrow_right.gif\" border=\"0\" /></a></td>";
				}

				/* Draw out the current day */
				$var .= '<td align="left" width="14%" class="alt2" valign="top" style="font-size:'. $font_size .'px;height:'. $cell_height .'px;"><div style="'. $cell_style .'">';
				$var .= ($this_day == $today) ? '<strong style="color:#FF0000;">'. $day .'</strong>' : $day;
				$var .= '</div>';

				$var .= '</td>';
				
				/* If weve reached the end of the month, display our end of month filler days */
				if($day == $max_days) {
					
					/* This sets up the numbers for the end filler days */
					for ($i = 1; $i < $end_filler_days+1; $i++ ) {
						$val = ($this->config->display_extras == TRUE) ? $i : '&nbsp;';
						$var .= '<td align="center" class="alt1" style="font-size:'. $font_size .'px;"><em>'. $val .'</em></td>';
					}

					$var .= "</tr>\n"; 
				}
			} 
			$var.="</table>\n"; 
		}

		return $var;
	}
	/* Go to the previous month */
	public function rewind_month($month, $year) {
		$m = ($month != 1) ? $month-1 : 12;
		$y = ($month != 1) ? $year : $year-1;
		return(array('m'=>$m,'y'=>$y));
	}
	/* go forward a month, but not if the current month is this month */
	public function advance_month($month, $year, $safe = FALSE) {
		if((mktime(0,0,0, $month,0,$year) < mktime(0,0,0,date("n"),0,date("Y"))) || isset($safe)) {
			$m = ($month != 12) ? $month+1 : 1;
			$y = ($month != 12) ? $year : $year+1;
			return(array('m'=>$m,'y'=>$y));
		} else {
			return(array('m'=>date("n"),'y'=>date("Y")));
		}
	}
	/* Get the current Week */
	public function week_range($week, $month, $year) {
		$end_of_week = $week+(3600*24*7);
		$days = array();
		for($i = 1; $i <= 7; $i++) {
			$day = $week + $i*3600*24;
			$days[] = array('day' => date("j", $day), 'month' => date("n", $day), 'year' => date("Y", $day), 'textual' => date("l jS of F Y", $day));
		}
		return $days;
	}
}

?>