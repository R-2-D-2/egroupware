<?php
  /**************************************************************************\
  * phpGroupWare - Setup                                                     *
  * http://www.phpgroupware.org                                              *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

  /* $Id$ */

	include('./inc/class.setup_detection.inc.php');
	include('./inc/class.setup_process.inc.php');
	include('./inc/class.setup_lang.inc.php');
	include('./inc/class.setup_html.inc.php');
	class phpgw_setup extends phpgw_setup_html
	{
		var $db;
		var $oProc;
		var $tables;

		/*!
		@function loaddb
		@abstract include api db class for the ConfigDomain and connect to the db
		*/
		function loaddb()
		{
			$ConfigDomain = $GLOBALS['HTTP_COOKIE_VARS']['ConfigDomain'] ? $GLOBALS['HTTP_COOKIE_VARS']['ConfigDomain'] : $GLOBALS['HTTP_POST_VARS']['ConfigDomain'];
			if(empty($ConfigDomain))
			{
				/* This is to fix the reading of this value immediately after the cookie was set on login */
				$ConfigDomain = $GLOBALS['HTTP_POST_VARS']['FormDomain'];
			}

			/* Database setup */
			if (!isset($GLOBALS['phpgw_info']['server']['api_inc']))
			{
				$GLOBALS['phpgw_info']['server']['api_inc'] = PHPGW_SERVER_ROOT . '/phpgwapi/inc';
			}
			include($GLOBALS['phpgw_info']['server']['api_inc'] . '/class.db_'.$GLOBALS['phpgw_domain'][$ConfigDomain]['db_type'].'.inc.php');
			$this->db           = new db;
			$this->db->Host     = $GLOBALS['phpgw_domain'][$ConfigDomain]['db_host'];
			$this->db->Type     = $GLOBALS['phpgw_domain'][$ConfigDomain]['db_type'];
			$this->db->Database = $GLOBALS['phpgw_domain'][$ConfigDomain]['db_name'];
			$this->db->User     = $GLOBALS['phpgw_domain'][$ConfigDomain]['db_user'];
			$this->db->Password = $GLOBALS['phpgw_domain'][$ConfigDomain]['db_pass'];
		}

		/*!
		@function auth
		@abstract authenticate the setup user
		@param	$auth_type	???
		*/
		function auth($auth_type = "Config")
		{
			global $HTTP_POST_VARS, $HTTP_GET_VARS, $HTTP_COOKIE_VARS;

			$FormLogout   = $HTTP_GET_VARS['FormLogout']    ? $HTTP_GET_VARS['FormLogout']    : $HTTP_POST_VARS['FormLogout'];
			$ConfigLogin  = $HTTP_POST_VARS['ConfigLogin']  ? $HTTP_POST_VARS['ConfigLogin']  : $HTTP_COOKIE_VARS['ConfigLogin'];
			$HeaderLogin  = $HTTP_POST_VARS['HeaderLogin']  ? $HTTP_POST_VARS['HeaderLogin']  : $HTTP_COOKIE_VARS['HeaderLogin'];
			$FormDomain   = $HTTP_POST_VARS['FormDomain'];
			$FormPW       = $HTTP_POST_VARS['FormPW'];
			$ConfigDomain = $HTTP_POST_VARS['ConfigDomain'] ? $HTTP_POST_VARS['ConfigDomain'] : $HTTP_COOKIE_VARS['ConfigDomain'];
			$ConfigPW     = $HTTP_POST_VARS['ConfigPW']     ? $HTTP_POST_VARS['ConfigPW']     : $HTTP_COOKIE_VARS['ConfigPW'];
			$HeaderPW     = $HTTP_COOKIE_VARS['HeaderPW']   ? $HTTP_COOKIE_VARS['HeaderPW']   : $HTTP_POST_VARS['HeaderPW'];
			$ConfigLang   = $HTTP_POST_VARS['ConfigLang']   ? $HTTP_POST_VARS['ConfigLang']   : $HTTP_COOKIE_VARS['ConfigLang'];

			if (isset($FormLogout) && !empty($FormLogout))
			{
				if ($FormLogout == 'config' ||
					$FormLogout == 'ldap' ||
					$FormLogout == 'ldapexport' ||
					$FormLogout == 'ldapimport' ||
					$FormLogout == 'sqltoarray')
				{
					setcookie('ConfigPW');  /* scrub the old one */
					setcookie('ConfigDomain');  /* scrub the old one */
					setcookie('ConfigLang');
					$GLOBALS['phpgw_info']['setup']['ConfigLoginMSG'] = 'You have successfully logged out';
					return False;
				}
				elseif($FormLogout == 'header')
				{
					setcookie('HeaderPW');  /* scrub the old one */
					$GLOBALS['phpgw_info']['setup']['HeaderLoginMSG'] = 'You have successfully logged out';
					return False;
				}
			}
			elseif (isset($ConfigPW) && !empty($ConfigPW))
			{
				if ($ConfigPW != $GLOBALS['phpgw_domain'][$ConfigDomain]['config_passwd'] && $auth_type == 'Config')
				{
					setcookie('ConfigPW');  /* scrub the old one */
					setcookie('ConfigDomain');  /* scrub the old one */
					setcookie('ConfigLang');
					$GLOBALS['phpgw_info']['setup']['ConfigLoginMSG'] = 'Invalid session cookie (cookies must be enabled)';
					return False;
				}
				else
				{
					return True;
				}
			}
			elseif (isset($FormPW) && !empty($FormPW))
			{
				if (isset($ConfigLogin))
				{
					if ($FormPW == $GLOBALS['phpgw_domain'][$FormDomain]['config_passwd'] && $auth_type == 'Config')
					{
						setcookie('HeaderPW');  /* scrub the old one */
						setcookie('ConfigPW',$FormPW);
						setcookie('ConfigDomain',$FormDomain);
						setcookie('ConfigLang',$ConfigLang);
						$ConfigDomain = $FormDomain;
						return True;
					}
					else
					{
						$GLOBALS['phpgw_info']['setup']['ConfigLoginMSG'] = 'Invalid password';
						return False;
					}
				}
				elseif (isset($HeaderLogin) && !empty($HeaderLogin))
				{
					if ($FormPW == $GLOBALS['phpgw_info']['server']['header_admin_password'] && $auth_type == 'Header')
					{
						setcookie('HeaderPW',$FormPW);
						return True;
					}
					else
					{
						$GLOBALS['phpgw_info']['setup']['HeaderLoginMSG'] = 'Invalid password';
						return False;
					}
				}
			}
			elseif (isset($HeaderPW) && !empty($HeaderPW))
			{
				if ($HeaderPW != $GLOBALS['phpgw_info']['server']['header_admin_password'] && $auth_type == 'Header')
				{
					setcookie('HeaderPW');  /* scrub the old one */
					$GLOBALS['phpgw_info']['setup']['HeaderLoginMSG'] = 'Invalid session cookie (cookies must be enabled)';
					return False;
				}
				else
				{
					return True;
				}
			}
			else
			{
				return False;
			}
		}

		/*!
		@function isinarray
		@abstract php3/4 compliant in_array()
		@param	$needle		String to search for
		@param	$haystack	Array to search
		*/
		function isinarray($needle,$haystack='') 
		{
			if($haystack == '')
			{
				settype($haystack,'array');
				$haystack = Array();
			}
			for($i=0;$i<count($haystack) && $haystack[$i] !=$needle;$i++);
			return ($i!=count($haystack));
		}

		/*!
		@function get_major
		@abstract Return X.X.X major version from X.X.X.X versionstring
		@param	$
		*/
		function get_major($versionstring)
		{
			if (!$versionstring)
			{
				return False;
			}
			
			$version = ereg_replace('pre','.',$versionstring);
			$varray  = explode('.',$version);
			$major   = implode('.',array($varray[0],$varray[1],$varray[2]));

			return $major;
		}

		/*!
		@function clear_session_cache
		@abstract Clear system/user level cache so as to have it rebuilt with the next access
		@param	None
		*/
		function clear_session_cache()
		{
			
			$tablenames = @$this->db->table_names();
			while(list($key,$val) = @each($tablenames))
			{
				$tables[] = $val['table_name'];
			}
			if ($this->isinarray('phpgw_app_sessions',$tables))
			{
				$this->db->lock(array('phpgw_app_sessions'));
				@$this->db->query("DELETE FROM phpgw_app_sessions WHERE sessionid = '0' and loginid = '0' and app = 'phpgwapi' and location = 'config'",__LINE__,__FILE__);
				@$this->db->query("DELETE FROM phpgw_app_sessions WHERE app = 'phpgwapi' and location = 'phpgw_info_cache'",__LINE__,__FILE__);
				$this->db->unlock();
			}
		}

		/*!
		@function register_app
		@abstract Add an application to the phpgw_applications table
		@param	$appname	Application 'name' with a matching $setup_info[$appname] array slice
		@param	$enable		optional, set to True/False to override setup.inc.php setting
		*/
		function register_app($appname,$enable=99)
		{
			$setup_info = $GLOBALS['setup_info'];

			if(!$appname)
			{
				return False;
			}

			if ($enable==99)
			{
				$enable = $setup_info[$appname]['enable'];
			}
			$enable = intval($enable);

			/*
			 Use old applications table if the currentver is less than 0.9.10pre8,
			 but not if the currentver = '', which probably means new install.
			*/
			if ($this->alessthanb($setup_info['phpgwapi']['currentver'],'0.9.10pre8') && ($setup_info['phpgwapi']['currentver'] != ''))
			{
				$appstbl = 'applications';
			}
			else
			{
				$appstbl = 'phpgw_applications';
				if($this->amorethanb($setup_info['phpgwapi']['currentver'],'0.9.13.014'))
				{
					$use_appid = True;
				}
			}

			if($GLOBALS['DEBUG'])
			{
				echo '<br>register_app(): ' . $appname . ', version: ' . $setup_info[$appname]['version'] . ', table: ' . $appstbl . '<br>';
				// _debug_array($setup_info[$appname]);
			}

			if ($setup_info[$appname]['version'])
			{
				if ($setup_info[$appname]['tables'])
				{
					$tables = implode(',',$setup_info[$appname]['tables']);
				}
				if($use_appid)
				{
					$this->db->query("SELECT MAX(app_id) FROM $appstbl");
					$this->db->next_record();
					if($this->db->f(0))
					{
						$app_id = ($this->db->f(0) + 1) . ',';
						$app_idstr = 'app_id,';
					}
					else
					{
						srand(100000);
						$app_id = rand(1,100000) . ',';
						$app_idstr = 'app_id,';
					}
				}
				$this->db->query("INSERT INTO $appstbl "
					. "($app_idstr app_name,app_title,app_enabled,app_order,app_tables,app_version) "
					. "VALUES ("
					. $app_id
					. "'" . $setup_info[$appname]['name'] . "',"
					. "'" . $setup_info[$appname]['title'] . "',"
					. $enable . ","
					. intval($setup_info[$appname]['app_order']) . ","
					. "'" . $tables . "',"
					. "'" . $setup_info[$appname]['version'] . "');"
				);
				$this->clear_session_cache();
			}
		}

		/*!
		@function app_registered
		@abstract Check if an application has info in the db
		@param	$appname	Application 'name' with a matching $setup_info[$appname] array slice
		@param	$enabled	optional, set to False to not enable this app
		*/
		function app_registered($appname)
		{
			$setup_info = $GLOBALS['setup_info'];

			if(!$appname)
			{
				return False;
			}

			if ($this->alessthanb($setup_info['phpgwapi']['currentver'],'0.9.10pre8') && ($setup_info['phpgwapi']['currentver'] != ''))
			{
				$appstbl = 'applications';
			}
			else
			{
				$appstbl = 'phpgw_applications';
			}

			if($GLOBALS['DEBUG'])
			{
				echo '<br>app_registered(): checking ' . $appname . ', table: ' . $appstbl;
				// _debug_array($setup_info[$appname]);
			}

			$this->db->query("SELECT COUNT(app_name) FROM $appstbl WHERE app_name='".$appname."'");
			$this->db->next_record();
			if ($this->db->f(0))
			{
				if($GLOBALS['DEBUG'])
				{
					echo '... app previously registered.';
				}
				return True;
			}
			if($GLOBALS['DEBUG'])
			{
				echo '... app not registered';
			}
			return False;
		}

		/*!
		@function update_app
		@abstract Update application info in the db
		@param	$appname	Application 'name' with a matching $setup_info[$appname] array slice
		@param	$enabled	optional, set to False to not enable this app
		*/
		function update_app($appname)
		{
			$setup_info = $GLOBALS['setup_info'];

			if(!$appname)
			{
				return False;
			}

			if ($this->alessthanb($setup_info['phpgwapi']['currentver'],'0.9.10pre8') && ($setup_info['phpgwapi']['currentver'] != ''))
			{
				$appstbl = 'applications';
			}
			else
			{
				$appstbl = 'phpgw_applications';
			}

			if($GLOBALS['DEBUG'])
			{
				echo '<br>update_app(): ' . $appname . ', version: ' . $setup_info[$appname]['currentver'] . ', table: ' . $appstbl . '<br>';
				// _debug_array($setup_info[$appname]);
			}

			$this->db->query("SELECT COUNT(app_name) FROM $appstbl WHERE app_name='".$appname."'");
			$this->db->next_record();
			if (!$this->db->f(0))
			{
				return False;
			}

			if ($setup_info[$appname]['version'])
			{
				//echo '<br>' . $setup_info[$appname]['version'];
				if ($setup_info[$appname]['tables'])
				{
					$tables = implode(',',$setup_info[$appname]['tables']);
				}

				$sql = "UPDATE $appstbl "
					. "SET app_name='" . $setup_info[$appname]['name'] . "',"
					. " app_title='" . $setup_info[$appname]['title'] . "',"
					. " app_enabled=" . intval($setup_info[$appname]['enable']) . ","
					. " app_order=" . intval($setup_info[$appname]['app_order']) . ","
					. " app_tables='" . $tables . "',"
					. " app_version='" . $setup_info[$appname]['version'] . "'"
					. " WHERE app_name='" . $appname . "'";
				//echo $sql; exit;

				$this->db->query($sql);
			}
		}

		/*!
		@function update_app_version
		@abstract Update application version in applications table, post upgrade
		@param	$setup_info		Array of application information (multiple apps or single)
		@param	$appname		Application 'name' with a matching $setup_info[$appname] array slice
		@param	$tableschanged	???
		*/
		function update_app_version($setup_info, $appname, $tableschanged = True)
		{
			if(!$appname)
			{
				return False;
			}

			if ($this->alessthanb($setup_info['phpgwapi']['currentver'],'0.9.10pre8') && ($setup_info['phpgwapi']['currentver'] != ''))
			{
				$appstbl = 'applications';
			}
			else
			{
				$appstbl = 'phpgw_applications';
			}

			if ($tableschanged == True)
			{
				$GLOBALS['phpgw_info']['setup']['tableschanged'] = True;
			}
			if ($setup_info[$appname]['currentver'])
			{
				$this->db->query("UPDATE $appstbl SET app_version='" . $setup_info[$appname]['currentver'] . "' WHERE app_name='".$appname."'");
			}
			return $setup_info;
		}

		/*!
		@function deregister_app
		@abstract de-Register an application
		@param	$appname	Application 'name' with a matching $setup_info[$appname] array slice
		*/
		function deregister_app($appname)
		{
			if(!$appname)
			{
				return False;
			}
			$setup_info = $GLOBALS['setup_info'];

			if ($this->alessthanb($setup_info['phpgwapi']['currentver'],'0.9.10pre8') && ($setup_info['phpgwapi']['currentver'] != ''))
			{
				$appstbl = 'applications';
			}
			else
			{
				$appstbl = 'phpgw_applications';
			}

			//echo 'DELETING application: ' . $appname;
			$this->db->query("DELETE FROM $appstbl WHERE app_name='". $appname ."'");
			$this->clear_session_cache();
		}

		/*!
		@function register_hooks
		@abstract Register an application's hooks
		@param	$appname	Application 'name' with a matching $setup_info[$appname] array slice
		*/
		function register_hooks($appname)
		{
			$setup_info = $GLOBALS['setup_info'];

			if(!$appname)
			{
				return False;
			}

			if ($this->alessthanb($setup_info['phpgwapi']['currentver'],'0.9.8pre5') && ($setup_info['phpgwapi']['currentver'] != ''))
			{
				/* No phpgw_hooks table yet. */
				return False;
			}

			$this->db->query("SELECT COUNT(hook_appname) FROM phpgw_hooks WHERE hook_appname='".$appname."'");
			$this->db->next_record();
			if ($this->db->f(0))
			{
				$this->deregister_hooks($appname);
			}

			//echo "ADDING hooks for: " . $setup_info[$appname]['name'];
			if (is_array($setup_info[$appname]['hooks']))
			{
				while (list($key,$hook) = each($setup_info[$appname]['hooks']))
				{
					$this->db->query("INSERT INTO phpgw_hooks "
						. "(hook_appname,hook_location,hook_filename) "
						. "VALUES ("
						. "'" . $setup_info[$appname]['name']       . "',"
						. "'" . $hook . "',"
						. "'" . "hook_" . $hook . ".inc.php" . "');"
					);
				}
			}
		}

		/*!
		@function update_hooks
		@abstract Update an application's hooks
		@param	$appname	Application 'name' with a matching $setup_info[$appname] array slice
		*/
		function update_hooks($appname)
		{
			$setup_info = $GLOBALS['setup_info'];

			if(!$appname)
			{
				return False;
			}

			if ($this->alessthanb($setup_info['phpgwapi']['currentver'],'0.9.8pre5'))
			{
				/* No phpgw_hooks table yet. */
				return False;
			}

			$this->db->query("SELECT COUNT(*) FROM phpgw_hooks WHERE hook_appname='".$appname."'");
			$this->db->next_record();
			if (!$this->db->f(0))
			{
				return False;
			}

			if ($setup_info[$appname]['version'])
			{
				if (is_array($setup_info[$appname]['hooks']))
				{
					$this->deregister_hooks($appname);
					$this->register_hooks($appname);
				}
			}
		}

		/*!
		@function deregister_hooks
		@abstract de-Register an application's hooks
		@param	$appname	Application 'name' with a matching $setup_info[$appname] array slice
		*/
		function deregister_hooks($appname)
		{
			if(!$appname)
			{
				return False;
			}

			//echo "DELETING hooks for: " . $setup_info[$appname]['name'];
			$this->db->query("DELETE FROM phpgw_hooks WHERE hook_appname='". $appname ."'");
		}

		/*!
		  @function hook
		  @abstract call the hooks for a single application
		  @param $location hook location - required
		  @param $appname application name - optional
		 */
		function hook($location, $appname = '')
		{
			if (! $appname)
			{
				$appname = $GLOBALS['phpgw_info']['flags']['currentapp'];
			}
			$SEP = filesystem_separator();

			$f = PHPGW_SERVER_ROOT . $SEP . $appname . $SEP . 'inc' . $SEP . 'hook_' . $location . '.inc.php';
			if (file_exists($f))
			{
				include($f);
				return True;
			}
			else
			{
				return False;
			}
		}

		/*
		@function alessthanb
		@abstract phpgw version checking, is param 1 < param 2 in phpgw versionspeak?
		@param	$a	phpgw version number to check if less than $b
		@param	$b	phpgw version number to check $a against
		#return	True if $a < $b
		*/
		function alessthanb($a,$b,$DEBUG=False)
		{
			$num = array('1st','2nd','3rd','4th');

			if ($DEBUG)
			{
				echo'<br>Input values: '
					. 'A="'.$a.'", B="'.$b.'"';
			}
			$newa = ereg_replace('pre','.',$a);
			$newb = ereg_replace('pre','.',$b);
			$testa = explode('.',$newa);
			if(@$testa[3] == '')
			{
				$testa[3] = 0;
			}
			$testb = explode('.',$newb);
			if(@$testb[3] == '')
			{
				$testb[3] = 0;
			}
			$less = 0;

			for ($i=0;$i<count($testa);$i++)
			{
				if ($DEBUG) { echo'<br>Checking if '. intval($testa[$i]) . ' is less than ' . intval($testb[$i]) . ' ...'; }
				if (intval($testa[$i]) < intval($testb[$i]))
				{
					if ($DEBUG) { echo ' yes.'; }
					$less++;
					if ($i<3)
					{
						/* Ensure that this is definitely smaller */
						if ($DEBUG) { echo"  This is the $num[$i] octet, so A is definitely less than B."; }
						$less = 5;
						break;
					}
				}
				elseif(intval($testa[$i]) > intval($testb[$i]))
				{
					if ($DEBUG) { echo ' no.'; }
					$less--;
					if ($i<2)
					{
						/* Ensure that this is definitely greater */
						if ($DEBUG) { echo"  This is the $num[$i] octet, so A is definitely greater than B."; }
						$less = -5;
						break;
					}
				}
				else
				{
					if ($DEBUG) { echo ' no, they are equal.'; }
					$less = 0;
				}
			}
			if ($DEBUG) { echo '<br>Check value is: "'.$less.'"'; }
			if ($less>0)
			{
				if ($DEBUG) { echo '<br>A is less than B'; }
				return True;
			}
			elseif($less<0)
			{
				if ($DEBUG) { echo '<br>A is greater than B'; }
				return False;
			}
			else
			{
				if ($DEBUG) { echo '<br>A is equal to B'; }
				return False;
			}
		}

		/*!
		@function amorethanb
		@abstract phpgw version checking, is param 1 > param 2 in phpgw versionspeak?
		@param	$a	phpgw version number to check if more than $b
		@param	$b	phpgw version number to check $a against
		#return	True if $a < $b
		*/
		function amorethanb($a,$b,$DEBUG=False)
		{
			$num = array('1st','2nd','3rd','4th');

			if ($DEBUG)
			{
				echo'<br>Input values: '
					. 'A="'.$a.'", B="'.$b.'"';
			}
			$newa = ereg_replace('pre','.',$a);
			$newb = ereg_replace('pre','.',$b);
			$testa = explode('.',$newa);
			if($testa[3] == '')
			{
				$testa[3] = 0;
			}
			$testb = explode('.',$newb);
			if($testa[3] == '')
			{
				$testa[3] = 0;
			}
			$less = 0;

			for ($i=0;$i<count($testa);$i++)
			{
				if ($DEBUG) { echo'<br>Checking if '. intval($testa[$i]) . ' is more than ' . intval($testb[$i]) . ' ...'; }
				if (intval($testa[$i]) > intval($testb[$i]))
				{
					if ($DEBUG) { echo ' yes.'; }
					$less++;
					if ($i<3)
					{
						/* Ensure that this is definitely greater */
						if ($DEBUG) { echo"  This is the $num[$i] octet, so A is definitely greater than B."; }
						$less = 5;
						break;
					}
				}
				elseif(intval($testa[$i]) < intval($testb[$i]))
				{
					if ($DEBUG) { echo ' no.'; }
					$less--;
					if ($i<2)
					{
						/* Ensure that this is definitely smaller */
						if ($DEBUG) { echo"  This is the $num[$i] octet, so A is definitely less than B."; }
						$less = -5;
						break;
					}
				}
				else
				{
					if ($DEBUG) { echo ' no, they are equal.'; }
					$less = 0;
				}
			}
			if ($DEBUG) { echo '<br>Check value is: "'.$less.'"'; }
			if ($less>0)
			{
				if ($DEBUG) { echo '<br>A is greater than B'; }
				return True;
			}
			elseif($less<0)
			{
				if ($DEBUG) { echo '<br>A is less than B'; }
				return False;
			}
			else
			{
				if ($DEBUG) { echo '<br>A is equal to B'; }
				return False;
			}
		}
	}
?>
