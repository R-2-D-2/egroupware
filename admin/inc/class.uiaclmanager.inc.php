<?php
	/**************************************************************************\
	* phpGroupWare - Administration                                            *
	* http://www.phpgroupware.org                                              *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id$ */

	class uiaclmanager
	{
		var $template;
		var $nextmatchs;
		var $public_functions = array(
			'list_apps'    => True,
			'access_form'  => True,
			'account_list' => True
		);

		function uiaclmanager()
		{
			$this->template = createobject('phpgwapi.Template',PHPGW_APP_TPL);
		}

		function common_header()
		{
			$GLOBALS['phpgw']->common->phpgw_header();
			echo parse_navbar();
		}

		function list_apps()
		{
			$this->common_header();

			$GLOBALS['phpgw']->common->hook('acl_manager',array('preferences'));

			$this->template->set_file(array(
				'app_list'   => 'acl_applist.tpl'
			));
			$this->template->set_block('app_list','list');
			$this->template->set_block('app_list','app_row');
			$this->template->set_block('app_list','app_row_noicon');
			$this->template->set_block('app_list','link_row');
			$this->template->set_block('app_list','spacer_row');

			$this->template->set_var('lang_header',lang('ACL Manager'));

			while (is_array($GLOBALS['acl_manager']) && list($app,$locations) = each($GLOBALS['acl_manager']))
			{

				$icon = $GLOBALS['phpgw']->common->image($app,array('navbar.gif',$app.'.gif'));
				$this->template->set_var('icon_backcolor',$GLOBALS['phpgw_info']['theme']['row_off']);
				$this->template->set_var('link_backcolor',$GLOBALS['phpgw_info']['theme']['row_off']);
				$this->template->set_var('app_name',lang($GLOBALS['phpgw_info']['navbar'][$app]['title']));
				$this->template->set_var('a_name',$appname);
				$this->template->set_var('app_icon',$icon);

				if ($icon)
				{
					$this->template->fp('rows','app_row',True);
				}
				else
				{
					$this->template->fp('rows','app_row_noicon',True);
				}

				if (is_array($locations['deny']))
				{
					$link_values = array(
						'menuaction' => 'admin.uiaclmanager.access_form',
						'location'   => urlencode(base64_encode('deny')),
						'acl_app'    => $app,
						'account_id' => $GLOBALS['account_id']
					);

					$this->template->set_var('link_location',$GLOBALS['phpgw']->link('/index.php',$link_values));
					$this->template->set_var('lang_location',lang('Deny access'));
					$this->template->fp('rows','link_row',True);
				}

				while (is_array($locations) && list($loc,$value) = each($locations))
				{
					$link_values = array(
						'menuaction' => 'admin.uiaclmanager.access_form',
						'location'   => urlencode(base64_encode($loc)),
						'acl_app'    => $app,
						'account_id' => $GLOBALS['account_id']
					);

					$this->template->set_var('link_location',$GLOBALS['phpgw']->link('/index.php',$link_values));
					$this->template->set_var('lang_location',lang($value['name']));
					$this->template->fp('rows','link_row',True);
				}

				$this->template->parse('rows','spacer_row',True);
			}
			$this->template->pfp('out','list');
		}

/*	FIX ME! Remove after first commit, I didn't wanna rewrite this (if I had to), so I am keeping a
   copy in cvs as a backup
		function account_list()
		{
			global $phpgw_info, $phpgw, $sort, $start, $order, $query; // REMOVE ME!

			if (! $GLOBALS['acl_app'] || ! $GLOBALS['location'])
			{
				$this->list_apps();
				return False;
			}
			$location = base64_decode($GLOBALS['location']);

			$this->common_header();

			// REMOVE ME!
			$phpgw->nextmatchs = createobject('phpgwapi.nextmatchs');
			$this->nextmatchs  = createobject('phpgwapi.nextmatchs');

			$this->template->set_file(array(
				'accounts'   => 'acl_accounts.tpl'
			));
			$this->template->set_block('accounts','list','list');
			$this->template->set_block('accounts','row','row');
			$this->template->set_block('accounts','row_empty','row_empty');

			$total = $this->account_total($GLOBALS['query']);
		
			$this->template->set_var('bg_color',$phpgw_info['theme']['bg_color']);
			$this->template->set_var('th_bg',$phpgw_info['theme']['th_bg']);
		
			$this->template->set_var('left_next_matchs',$phpgw->nextmatchs->left('/admin/accounts.php',$start,$total));
			$this->template->set_var('lang_header',lang('ACL Manager - %1 - %2',$GLOBALS['phpgw_info']['navbar'][$GLOBALS['acl_app']]['title'],$location));
			$this->template->set_var('right_next_matchs',$phpgw->nextmatchs->right('/admin/accounts.php',$start,$total));
		
			$this->template->set_var('lang_loginid',$phpgw->nextmatchs->show_sort_order($sort,'account_lid',$order,'/index.php',lang('LoginID'),'&acl_app=' . $GLOBALS['acl_app'] . '&location=' . $GLOBALS['location']));
			$this->template->set_var('lang_lastname',$phpgw->nextmatchs->show_sort_order($sort,'account_lastname',$order,'/index.php',lang('last name'),'&acl_app=' . $GLOBALS['acl_app'] . '&location=' . $GLOBALS['location']));
			$this->template->set_var('lang_firstname',$phpgw->nextmatchs->show_sort_order($sort,'account_firstname',$order,'/index.php',lang('first name'),'&acl_app=' . $GLOBALS['acl_app'] . '&location=' . $GLOBALS['location']));
		
			$this->template->set_var('lang_access',lang('Access'));
		
			$account_info = $phpgw->accounts->get_list('accounts',$start,$sort,$order,$query);
		
			if (! count($account_info))
			{
				$this->template->set_var('message',lang('No matchs found'));
				$this->template->parse('rows','row_empty',True);
			}
			else
			{
				while (list($null,$account) = each($account_info))
				{
					$lastname   = $account['account_lastname'];
					$firstname  = $account['account_firstname'];
					$account_id = $account['account_id'];
					$loginid    = $account['account_lid'];
			
					$phpgw->nextmatchs->template_alternate_row_color(&$this->template);
			
					if (! $lastname)
					{
						$lastname  = '&nbsp;';
					}
			
					if (! $firstname)
					{
						$firstname = '&nbsp;';
					}
			
					$this->template->set_var('row_loginid',$loginid);
					$this->template->set_var('row_firstname',$firstname);
					$this->template->set_var('row_lastname',$lastname);

					$link_values = array(
						'menuaction' => 'admin.uiaclmanager.access_form',
						'location'   => $GLOBALS['location'],
						'acl_app'    => $GLOBALS['acl_app'],
						'account_id' => $account_id
					);
					$this->template->set_var('row_access','<a href="'.$phpgw->link('/index.php',$link_values)
						. '"> ' . lang('Access') . ' </a>');

					$this->template->fp('rows','row',True);
				}
			}		// End else

			$link_values = array(
				'menuaction' => 'admin.uiaclmanager.access_form',
				'location'   => urlencode(base64_encode($location)),
				'acl_app'    => $acl_app
			);
			$this->template->set_var('actionurl',$phpgw->link('/index.php',$link_values));
			$this->template->set_var('lang_search',lang('Search'));
		
			$this->template->pfp('out','list');


		} */

		function access_form()
		{
			$GLOBALS['phpgw']->common->hook_single('acl_manager',$GLOBALS['acl_app']);
			$location = base64_decode($GLOBALS['location']);

			$acl_manager = $GLOBALS['acl_manager'][$GLOBALS['acl_app']][$location];

			$this->common_header();
			$this->template->set_file('form','acl_manager_form.tpl');

			$acc = createobject('phpgwapi.accounts',$GLOBALS['account_id']);
			$acc->read_repository();
			$afn = $GLOBALS['phpgw']->common->display_fullname($acc->data['account_lid'],$acc->data['firstname'],$acc->data['lastname']);

			$this->template->set_var('lang_message',lang('Check items to <b>%1</b> to %2 for %3',$acl_manager['name'],$GLOBALS['acl_app'],$afn));
			$link_values = array(
				'menuaction' => 'admin.boaclmanager.submit',
				'acl_app'    => $GLOBALS['acl_app'],
				'location'   => urlencode($GLOBALS['location']),
				'account_id' => $GLOBALS['account_id']
			);

			$acl    = createobject('phpgwapi.acl',$GLOBALS['account_id']);

			$this->template->set_var('form_action',$GLOBALS['phpgw']->link('/index.php',$link_values));
			$this->template->set_var('lang_title',lang('ACL Manager'));

			while (list($name,$value) = each($acl_manager['rights']))
			{
				$grants = $acl->get_rights($location,$GLOBALS['acl_app']);

				$s .= '<option value="' . $value . '"';
				$s .= (($grants & $value)?' selected':'');
				$s .= '>' . lang($name) . '</option>';
			}

			$size = 7;
			if (count($acl_manager['rights']) < 7)
			{
				$size = count($acl_manager['rights']);
			}
			$this->template->set_var('select_values','<select name="acl_rights[]" multiple size="' . $size . '">' . $s . '</select>');
			$this->template->set_var('lang_submit',lang('Submit'));
			$this->template->set_var('lang_cancel',lang('Cancel'));

			$this->template->pfp('out','form');
		}

	}
