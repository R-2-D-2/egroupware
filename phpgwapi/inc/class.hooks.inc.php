<?php
  /**************************************************************************\
  * phpGroupWare API - Hooks                                                 *
  * This file written by Dan Kuykendall <seek3r@phpgroupware.org>            *
  * Allows applications to "hook" into each other                            *
  * Copyright (C) 2000, 2001 Dan Kuykendall                                  *
  * -------------------------------------------------------------------------*
  * This library is part of the phpGroupWare API                             *
  * http://www.phpgroupware.org/api                                          * 
  * ------------------------------------------------------------------------ *
  * This library is free software; you can redistribute it and/or modify it  *
  * under the terms of the GNU Lesser General Public License as published by *
  * the Free Software Foundation; either version 2.1 of the License,         *
  * or any later version.                                                    *
  * This library is distributed in the hope that it will be useful, but      *
  * WITHOUT ANY WARRANTY; without even the implied warranty of               *
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     *
  * See the GNU Lesser General Public License for more details.              *
  * You should have received a copy of the GNU Lesser General Public License *
  * along with this library; if not, write to the Free Software Foundation,  *
  * Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA            *
  \**************************************************************************/

  /* $Id$ */

  $d1 = strtolower(substr($phpgw_info["server"]["api_inc"],0,3));
  $d2 = strtolower(substr($phpgw_info["server"]["server_root"],0,3));
  $d3 = strtolower(substr($phpgw_info["server"]["app_inc"],0,3));
  if($d1 == "htt" || $d1 == "ftp" || $d2 == "htt" || $d2 == "ftp" || $d3 == "htt" || $d3 == "ftp") {
    echo "Failed attempt to break in via an old Security Hole!<br>\n";
    exit;
  } unset($d1);unset($d2);unset($d3);

  class hooks
  {
     function read()
     {
        global $phpgw;
        $db = $phpgw->db;

        $db->query("select * from phpgw_hooks");
        while ($db->next_record()) {
           $return_array[$db->f("hook_id")]["app"]      = $db->f("hook_appname");
           $return_array[$db->f("hook_id")]["location"] = $db->f("hook_location");
           $return_array[$db->f("hook_id")]["filename"] = $db->f("hook_filename");
        }
        return $return_array;
     }
   
     function proccess($type,$where = "")
     {
        global $phpgw_info, $phpgw;

        $currentapp = $phpgw_info["flags"]["currentapp"];
        $type = strtolower($type);

        if ($type != "location" && $type != "app") {
           return False;
        }

        // Add a check to see if that location/app has a hook
        // This way it doesn't have to loop everytime

        while ($hook = each($phpgw_info["hooks"])) {
           if ($type == "app") {
              if ($hook[1]["app"] == $currentapp) {
                 $include_file = $phpgw_info["server"]["server_root"] . "/"
                               . $currentapp . "/hooks/"
                               . $hook[1]["app"] . $hook[1]["filename"];
                 include($include_file);
              }

           } else if ($type == "location") {
              if ($hook[1]["location"] == $where) {
                 $include_file = $phpgw_info["server"]["server_root"] . "/"
                               . $hook[1]["app"] . "/hooks/"
                               . $hook[1]["filename"];
                 if (! is_file($include_file)) {
                    $phpgw->common->phpgw_error("Failed to include hook: $include_file");
                 } else {
                    include($include_file);
                 }
              }
           }
       }
    }
  }
