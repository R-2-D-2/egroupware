<?php php_track_vars?>
<?php
  /**************************************************************************\
  * phpGroupWare - Calendar                                                  *
  * http://www.phpgroupware.org                                              *
  * Based on Webcalendar by Craig Knudsen <cknudsen@radix.net>               *
  *          http://www.radix.net/~cknudsen                                  *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

  /* $Id$ */

  $phpgw_info["flags"] = array("currentapp" => "calendar", "enable_calendar_class" => True, "enable_nextmatchs_class" => True);

  include("../header.inc.php");

  $cal_info = new calendar_item;

  if ($id > 0) {
    $cal = $phpgw->calendar->getevent((int)$id);
    $cal_info = $cal[0];

    $can_edit = false;
    if($cal_info->owner == $phpgw_info["user"]["account_id"]) 
      $can_edit = true;

    if(!$cal_info->rpt_end_use) {
      $rpt_date = $phpgw->calendar->splitdate(mktime(0,0,0,$cal_info->month,$cal_info->day + 1,$cal_info->year));
      $cal_info->rpt_year = $rpt_date["year"];
      $cal_info->rpt_month = $rpt_date["month"];
      $cal_info->rpt_day = $rpt_date["day"];
    }
  } else {
    $can_edit = true;

    if (!isset($day) || !$day)
      $thisday = (int)$phpgw->calendar->today["day"];
    else
      $thisday = $day;
    if (!isset($month) || !$month)
      $thismonth = (int)$phpgw->calendar->today["month"];
    else
      $thismonth = $month;
    if (!isset($year) || !$year)
      $thisyear = (int)$phpgw->calendar->today["year"];
    else
      $thisyear = $year;

    if (!isset($hour))
      $thishour = 0;
    else
      $thishour = (int)$hour;
    if (!isset($minute))
      $thisminute = 00;
    else
      $thisminute = (int)$minute;

    $time = $phpgw->calendar->splittime($phpgw->calendar->fixtime($thishour,$thisminute));

    $cal_info->name = "";
    $cal_info->description = "";

    $cal_info->day = $thisday;
    $cal_info->month = $thismonth;
    $cal_info->year = $thisyear;

    $cal_info->rpt_day = $thisday + 1;
    $cal_info->rpt_month = $thismonth;
    $cal_info->rpt_year = $thisyear;

    $cal_info->hour = (int)$time["hour"];
    $cal_info->minute = (!(int)$time["minute"]?"00":(int)$time["minute"]);
    $cal_info->ampm = "am";
    if($cal_info->hour > 12 && $phpgw_info["user"]["preferences"]["common"]["timeformat"] == "12") {
      $cal_info["hour"] = $cal_info["hour"] - 12;
      $cal_info["ampm"] = "pm";
    }
  }

  $phpgw->template->set_file(array("edit_entry_begin" => "edit.tpl",
				   "list"     	      => "list.tpl",
				   "edit_entry_end"   => "edit.tpl",
				   "form_button"      => "form_button_script.tpl"));

  $phpgw->template->set_block("edit_entry_begin","list","edit_entry_end","form_button");

  $phpgw->template->set_var("bg_color",$phpgw_info["theme"]["bg_text"]);
  $phpgw->template->set_var("name_error",lang("You have not entered a\\nBrief Description").".");
  $phpgw->template->set_var("time_error",lang("You have not entered a\\nvalid time of day."));
  if($id)
    $phpgw->template->set_var("calendar_action",lang("Calendar - Edit"));
  else
    $phpgw->template->set_var("calendar_action",lang("Calendar - Add"));

  if($can_edit) {
    $phpgw->template->set_var("action_url",$phpgw->link("edit_entry_handler.php"));

    $common_hidden = "<input type=\"hidden\" name=\"id\" value=\"".$id."\">\n";

    $phpgw->template->set_var("common_hidden",$common_hidden);

    $phpgw->template->parse("out","edit_entry_begin");

    $phpgw->template->set_var("field",lang("Brief Description"));
    $phpgw->template->set_var("data","<input name=\"name\" size=\"25\" value=\"".$cal_info->name."\">");
    $phpgw->template->parse("output","list",True);
    
    $phpgw->template->set_var("field",lang("Full Description"));
    $phpgw->template->set_var("data","<textarea name=\"description\" rows=\"5\" cols=\"40\" wrap=\"virtual\">".$cal_info->description."</textarea>");
    $phpgw->template->parse("output","list",True);

    $phpgw->template->set_var("field",lang("Date"));

    $day_html = "<select name=\"day\">";
    for ($i = 1; $i <= 31; $i++)
      $day_html .= "<option value=\"$i\"" . ($i == $cal_info->day ? " selected" : "") . ">$i"
	 	 . "</option>\n";
    $day_html .= "</select>";

    $month_html = "<select name=\"month\">";
    for ($i = 1; $i <= 12; $i++) {
      $m = lang(date("F", mktime(0,0,0,$i,1,$cal_info->year)));
      $month_html .= "<option value=\"$i\"" . ($i == $cal_info->month ? " selected" : "") . ">$m"
		   . "</option>\n";
    }
    $month_html .= "</select>";

    $year_html = "<select name=\"year\">";
    for ($i = ($cal_info->year - 1); $i < ($cal_info->year + 5); $i++) {
      $year_html .= "<option value=\"$i\"" . ($i == $cal_info->year ? " selected" : "") . ">$i"
		  . "</option>\n";
    }
    $year_html .= "</select>";

    $phpgw->template->set_var("data",$phpgw->common->dateformatorder($year_html,$month_html,$day_html));
    $phpgw->template->parse("output","list",True);

    $phpgw->template->set_var("field",lang("Time"));

    $amsel = "checked"; $pmsel = "";
    if ($phpgw_info["user"]["preferences"]["common"]["timeformat"] == "12") {
      if ($cal_info->ampm == "pm") {
	$amsel = ""; $pmsel = "checked";
      } else {
	$amsel = "checked"; $pmsel = "";
      }
    }
    $str = "<input name=\"hour\" size=\"2\" VALUE=\"".$cal_info->hour."\" maxlength=\"2\">:<input name=\"minute\" size=\"2\" value=\"".((int)$cal_info->minute>=0 && (int)$cal_info->minute<=9?"0".(int)$cal_info->minute:$cal_info->minute)."\" maxlength=\"2\">";
    if ($phpgw_info["user"]["preferences"]["common"]["timeformat"] == "12") {
      $str .= "<input type=\"radio\" name=\"ampm\" value=\"am\" $amsel>am";
      $str .= "<input type=\"radio\" name=\"ampm\" value=\"pm\" $pmsel>pm";
    }

    $phpgw->template->set_var("data",$str);
    $phpgw->template->parse("output","list",True);

    $phpgw->template->set_var("field",lang("Duration"));
    $phpgw->template->set_var("data","<input name=\"duration\" size=\"3\" value=\"".(!$cal_info->duration?0:$cal_info->duration)."\"> ".lang("minutes"));
    $phpgw->template->parse("output","list",True);

    $phpgw->template->set_var("field",lang("Priority"));
    $str = "<select name=\"priority\">";
    $str .= "<option value=\"1\"";
    if($cal_info->priority == 1) $str .= " selected";
    $str .= ">".lang("Low")."</option>";
    $str .= "<option value=\"2\"";
    if($cal_info->priority == 2 || $cal_info->priority == 0) $str .= " selected";
    $str .= ">".lang("Medium")."</option>";
    $str .= "<option value=\"3\"";
    if($cal_info->priority == 3) $str .= " selected";
    $str .= ">".lang("High")."</option>";
    $str .= "</select>";
    $phpgw->template->set_var("data",$str);
    $phpgw->template->parse("output","list",True);

    $phpgw->template->set_var("field",lang("Access"));
    $str = "<select name=\"access\">";
    $str .= "<option value=\"private\"";
    if ($cal_info->access == "private" || ! $id) $str .= " selected";
    $str .= ">".lang("Private")."</option>";
    $str .= "<option value=\"public\"";
    if ($cal_info->access == "public") $str .= " selected";
    $str .= ">".lang("Global Public")."</option>";
    $str .= "<option value=\"group\"";
    if ($cal_info->access == "public" || !strlen($cal_info->access)) $str .= " selected";
    $str .= ">".lang("Group Public")."</option>";
    $str .= "</select>";
    $phpgw->template->set_var("data",$str);
    $phpgw->template->parse("output","list",True);

    $phpgw->template->set_var("field",lang("Access"));
    $str = "<select name=\"n_groups[]\" multiple size=\"5\">";
    $user_groups = $phpgw->accounts->read_group_names();
    for ($i=0;$i<count($user_groups);$i++) {
      $str .= "<option value=\"" . $user_groups[$i][0] . "\"";
      if (ereg(",".$user_groups[$i][0].",",$cal_info->groups))
	$str .= " selected";
      $str .= ">" . $user_groups[$i][1] . "</option>";
    }
    $str .= "</select>";
    $phpgw->template->set_var("data",$str);
    $phpgw->template->parse("output","list",True);

    $phpgw->template->set_var("field",lang("Participants"));
    $phpgw->db->query("select account_id,account_lastname,account_firstname,account_lid "
		     . "from accounts where account_status !='L' and "
		     . "account_id != ".$phpgw_info["user"]["account_id"]." "
		     . "and account_permissions like '%:calendar:%' "
  		     . "order by account_lastname,account_firstname,account_lid");

    if ($phpgw->db->num_rows() > 50)
      $size = 15;
    else if ($phpgw->db->num_rows() > 5)
      $size = 5;
    else
      $size = $phpgw->db->num_rows();
    $str = "<select name=\"participants[]\" multiple size=\"5\">";
    for ($l=0;$l<count($cal_info->participants);$l++)
      $parts[$cal_info->participants[$l]] = True;
    while ($phpgw->db->next_record()) {
      $str .= "<option value=\"" . $phpgw->db->f("account_id") . "\"";  
      if ($parts[$phpgw->db->f("account_id")])
	$str .= " selected";
      $str .= ">".$phpgw->common->grab_owner_name($phpgw->db->f("account_id"))."</option>";
    }
    $str .= "</select>";
    $str .= "<input type=\"hidden\" name=\"participants[]\" value=\"".$phpgw_info["user"]["account_id"]."\">";
    $phpgw->template->set_var("data",$str);
    $phpgw->template->parse("output","list",True);

    $phpgw->template->set_var("field",lang("Repeat Type"));
    $str = "<select name=\"rpt_type\">";
    $rpt_type_str = Array("none","daily","weekly","monthlybyday","monthlybydate","yearly");
    $rpt_type_out = Array("none" => "None", "daily" => "Daily", "weekly" => "Weekly", "monthlybyday" => "Monthly (by day)", "monthlybydate" => "Monthly (by date)", "yearly" => "yearly");
    for($l=0;$l<count($rpt_type_str);$l++) {
      $str .= "<option value=\"".$rpt_type_str[$l]."\"";
      if(!strcmp($cal_info->rpt_type,$rpt_type_str[$l])) $str .= " selected";
      $str .= ">".lang($rpt_type_out[$rpt_type_str[$l]])."</option>";
    }
    $str .= "</select>";
    $phpgw->template->set_var("data",$str);
    $phpgw->template->parse("output","list",True);

    $phpgw->template->set_var("field",lang("Repeat End Date"));
    $str = "<input type=\"checkbox\" name=\"rpt_end_use\" value=\"y\"";
    if($cal_info->rpt_end_use) $str .= " checked";
    $str .= ">".lang("Use End Date")."  ";

    $day_html = "<select name=\"rpt_day\">";
    for($i=1;$i<=31;$i++) {
      $day_html .= "<option value=\"$i\"".($i == $cal_info->rpt_day ? " selected" : "").">$i</option>\n";
    }
    $day_html .= "</select>";

    $month_html = "<select name=\"rpt_month\">";
    for($i=1;$i<=12;$i++) {
      $m = lang(date("F", mktime(0,0,0,$i,1,$cal_info->rpt_year)));
      $month_html .= "<option value=\"$i\"".($i == $cal_info->rpt_month ? " selected" : "").">$m</option>\n";
    }
    $month_html .= "</select>";

    $year_html = "<select name=\"rpt_year\">";
    for ($i = ($cal_info->rpt_year - 1); $i < ($cal_info->rpt_year + 5); $i++) {
      $year_html .= "<option value=\"$i\"".($i == $cal_info->rpt_year ? " selected" : "").">$i</option>\n";
    }
    $year_html .= "</select>";

    $str .= $phpgw->common->dateformatorder($year_html,$month_html,$day_html);
    $phpgw->template->set_var("data",$str);
    $phpgw->template->parse("output","list",True);

    $phpgw->template->set_var("field",lang("Repeat Day")."<br>".lang("for weekly"));
    $str  = "<input type=\"checkbox\" name=\"rpt_sun\" value=\"y\"".($cal_info->rpt_sun?"checked":"")."> ".lang("Sunday")." ";
    $str .= "<input type=\"checkbox\" name=\"rpt_mon\" value=\"y\"".($cal_info->rpt_mon?"checked":"")."> ".lang("Monday")." ";
    $str .= "<input type=\"checkbox\" name=\"rpt_tue\" value=\"y\"".($cal_info->rpt_tue?"checked":"")."> ".lang("Tuesday")." ";
    $str .= "<input type=\"checkbox\" name=\"rpt_wed\" value=\"y\"".($cal_info->rpt_wed?"checked":"")."> ".lang("Wednesday")." ";
    $str .= "<input type=\"checkbox\" name=\"rpt_thu\" value=\"y\"".($cal_info->rpt_thu?"checked":"")."> ".lang("Thursday")." ";
    $str .= "<input type=\"checkbox\" name=\"rpt_fri\" value=\"y\"".($cal_info->rpt_fri?"checked":"")."> ".lang("Friday")." ";
    $str .= "<input type=\"checkbox\" name=\"rpt_sat\" value=\"y\"".($cal_info->rpt_sat?"checked":"")."> ".lang("Saturday")." ";
    $phpgw->template->set_var("data",$str);
    $phpgw->template->parse("output","list",True);

    $phpgw->template->set_var("field",lang("Frequency"));
    $phpgw->template->set_var("data","<input name=\"rpt_freq\" size=\"4\" maxlength=\"4\" value=\"".$cal_info->rpt_freq."\">");
    $phpgw->template->parse("output","list",True);

    $phpgw->template->set_var("submit_button",lang("Submit"));

    if ($id > 0) {
      $phpgw->template->set_var("action_url_button",$phpgw->link("delete.php","id=$id"));
      $phpgw->template->set_var("action_text_button",lang("Delete"));
      $phpgw->template->set_var("action_confirm_button","onClick=\"return confirm('".lang("Are you sure\\nyou want to\\ndelete this entry ?\\n\\nThis will delete\\nthis entry for all users.")."')\"");
      $phpgw->template->parse("delete_button","form_button");
      $phpgw->template->pparse("out","edit_entry_end");
    } else {
      $phpgw->template->set_var("delete_button","");
      $phpgw->template->pparse("out","edit_entry_end");
    }
    $phpgw->common->phpgw_footer();
  }
?>
