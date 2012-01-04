<?php
/**
* Main file
*
* @package Blog
* @version 1.0.0
* @copyright (c) 2010, 2011 Michael Cullum (Unknown Bliss of http://michaelcullum.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
*
*/
if (!defined('IN_PHPBB'))
{
	exit;
}
define('BLOG_VERSION', '1.0.0');
/**
* acp_blog
* phpBB Blog System Administration
* @package acp
*/
class acp_blog
{
	var $u_action;
	var $new_config = array();
  	function main($id, $mode)
	{
      global $db, $user, $auth, $template;
      global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;
      switch ($mode)
      {
		 default:
         case 'index':
		 	$info = $this->obtain_latest_mod_version_info();
			$info = explode("\n", $info);
			$modversion = trim($info[0]);
			if(!$modversion)
			{
				$vermsg = $user->lang['SERVER_DOWN'];
				$version = '';
				$up2date = 0;
			}
			else
			{
				$up2date = (version_compare(BLOG_VERSION, $modversion, '>=') == 1) ? 1 : 0;
				$vermsg = ($up2date == 1) ? $user->lang['UP2DATE'] : $user->lang['NUP2DATE'];
			}
		 	$template->assign_vars(array(
				'CVER'		=> BLOG_VERSION,
				'LVER'		=> $modversion,
				'VERMSG'	=> $vermsg,
				'S_UP_TO_DATE' => $up2date,
				'UPDATE_TO'	=> sprintf($user->lang['UPDATE_TO'], $modversion),
				'S_VERSION_CHECK' => true,
				'U_DLUPDATE' => trim($info[1]),
			));
			$this->page_title = 'ACP_BLOG_OVERVIEW';
			$this->tpl_name = 'acp_blog_overview';
		 break;
		 case 'settings':
			$display_vars = array(
					'title'	=> 'ACP_BLOG_MANAGE',
					'vars'	=> array(
						'legend1'				=> 'ACP_BLOG_MANAGE',
						'blog_title'			=> array('lang' => 'ACP_BLOG_TITLE',		'validate' => 'string',	'type' => 'text:25:100',	'explain' => false),
						'blog_description'		=> array('lang' => 'ACP_BLOG_DESCRIPTION',	'validate' => 'string', 'type' => 'text:25:100',	'explain' => false),
						'blog_on'				=> array('lang' => 'BLOG_ON',				'validate' => 'int',	'type' => 'radio:yes_no',	'explain' => true),
						'blog_off_msg'			=> array('lang' => 'BLOG_OFF_MSG',			'validate' => 'string',	'type' => 'text:25:100',	'explain' => false),
						'blog_act_name'			=> array('lang' => 'BLOG_ACT_NAME',			'validate' => 'string', 'type' => 'text:25:100',	'explain' => true,	'lang_explain' => sprintf($user->lang['BLOG_ACT_NAME_EXPLAIN'], $config['blog_act_name'])),								
						'legend2'				=> 'ACP_BLOG_DISP',	
						'blog_bbcode_on'		=> array('lang'	=> 'BLOG_BBCODE_ON',		'validate' => 'int',	'type' => 'radio:yes_no',	'explain' => false),
						'blog_emote_on'			=> array('lang' => 'BLOG_EMOTE_ON',			'validate' => 'int',	'type' => 'radio:yes_no',	'explain' => false),
						'blog_postlimit'		=> array('lang' => 'BLOG_POSTLIMIT',		'validate' => 'int',	'type' => 'text:2:3',		'explain' => false),
						'blog_cmntlimit'		=> array('lang' => 'BLOG_CMNTLIMIT',		'validate' => 'int',	'type' => 'text:2:3',		'explain' => false),
						'blog_short_msg'		=> array('lang' => 'BLOG_SHORT_MSG',		'validate' => 'int',	'type' => 'text:3:4',		'explain' => true),
						'legend3'				=> 'ACP_BLOG_PLUGINS',
						'blog_cat_on'			=> array('lang' => 'BLOG_CAT_ON',			'validate' => 'int',	'type' => 'radio:yes_no',	'explain' => false),												
						'blog_rss_feed_on'		=> array('lang' => 'BLOG_RSS_FEED_ON',		'validate' => 'int',	'type' => 'radio:yes_no',	'explain' => false),
						'blog_tag_on'			=> array('lang' => 'BLOG_TAGCLOUD_ON',		'validate' => 'int',	'type' => 'radio:yes_no',	'explain' => false),
						'legend4'				=> 'ACP_BLOG_FPO',
						'blog_forum_post_on'	=> array('lang' => 'BLOG_FORUM_POST_ON',	'validate' => 'int',	'type' => 'radio:yes_no',	'explain' => true),
						'blog_forum_post_id'	=> array('lang' => 'BLOG_FORUM_POST_ID',	'validate' => 'string',	'type' => 'text:3:4',		'explain' => true),
					),
			);
			$this->page_title = 'ACP_BLOG_MANAGE';
			$this->tpl_name = 'acp_blog';
			$this->new_config = $config;
			$cfg_array = (isset($_REQUEST['config'])) ? utf8_normalize_nfc(request_var('config', array('' => ''), true)) : $this->new_config;
			$error = array();
			if (sizeof($error))
			{
				$submit = false;
			}
			validate_config_vars($display_vars['vars'], $cfg_array, $error);
			$submit = (isset($_POST['submit'])) ? true : false;
			$form_name = 'acp_blog';
			add_form_key('acp_blog');
			if ($submit && !check_form_key('acp_blog'))
			{
				$update = false;
				$errors[] = $user->lang['FORM_INVALID'];
			}
			foreach ($display_vars['vars'] as $config_name => $null)
			{
				if (!isset($cfg_array[$config_name]) || strpos($config_name, 'legend') !== false)
				{
					continue;
				}
				$this->new_config[$config_name] = $config_value = $cfg_array[$config_name];
				if ($submit)
				{
					if(!set_config($config_name, $config_value))
					{
						$error = true;
					}
				}
			}
			foreach ($display_vars['vars'] as $config_key => $vars)
			{
				if (!is_array($vars) && strpos($config_key, 'legend') === false)
				{
					continue;
				}
				if (strpos($config_key, 'legend') !== false)
				{
					$template->assign_block_vars('options', array(
						'S_LEGEND'		=> true,
						'LEGEND'		=> (isset($user->lang[$vars])) ? $user->lang[$vars] : $vars)
					);
	
					continue;
				}
				$type = explode(':', $vars['type']);
				$l_explain = '';
				if ($vars['explain'] && isset($vars['lang_explain']))
				{
					$l_explain = (isset($user->lang[$vars['lang_explain']])) ? $user->lang[$vars['lang_explain']] : $vars['lang_explain'];
				}
				else if ($vars['explain'])
				{
					$l_explain = (isset($user->lang[$vars['lang'] . '_EXPLAIN'])) ? $user->lang[$vars['lang'] . '_EXPLAIN'] : '';
				}
				$content = build_cfg_template($type, $config_key, $this->new_config, $config_key, $vars);
				if (empty($content))
				{
					continue;
				}
				$template->assign_block_vars('options', array(
					'KEY'			=> $config_key,
					'TITLE'			=> (isset($user->lang[$vars['lang']])) ? $user->lang[$vars['lang']] : $vars['lang'],
					'S_EXPLAIN'		=> $vars['explain'],
					'TITLE_EXPLAIN'	=> $l_explain,
					'CONTENT'		=> $content,
					)
				);
				unset($display_vars['vars'][$config_key]);
			}
			if($submit)
			{
				trigger_error($user->lang['ACP_GEN_BLOG_SUCCESS']);
			}
			$template->assign_vars(array(
				'U_ACTION'	=> $this->u_action,
			));	
		break;
		case 'cats':
			$this->page_title = 'ACP_BLOG_CATS_MANAGE';
			$this->tpl_name = 'acp_blog_cats';
			$action = request_var('action', '');
			$submit = (isset($_POST['submit'])) ? true : false;
			$create = (isset($_POST['create'])) ? true : false;
			$cat_id = request_var('id', 0);
			$template->assign_var('S_CATS_OFF',($config['blog_cat_on'] == 0) ? true : false);
			$form_name = 'acp_blogcat';
			$form_key = add_form_key('acp_blogcat');
			if ($submit && !check_form_key($form_key))
			{
				$update = false;
				$errors[] = $user->lang['FORM_INVALID'];
			}
			if($action == 'add')
			{
				if($submit)
				{
					$this->page_title = 'ACP_BLOG_CATS_MANAGE';
					$data = array(
						'title' => utf8_normalize_nfc(request_var('title', '', true)),
					);
					
					$template->assign_vars(array(
						'BLOG_CAT_TITLE' => $data['title'],
					));
				}
				else if($create)
				{
					$this->page_title = 'ACP_BLOG_CAT_MANAGE';
					$data = array(
						'title'		=> utf8_normalize_nfc(request_var('title', '', true)),
						'desc'		=> utf8_normalize_nfc(request_var('desc', '', true)),
					);
					$sql_ary = array(
						'cat_title'	=> (string) $db->sql_escape($data['title']),
						'cat_desc'	=> (string) $db->sql_escape($data['desc']),
					);
					$query = $db->sql_query('INSERT INTO ' . BLOG_CATS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary));
					if($query)
					{
						trigger_error($user->lang['ACP_GEN_BLOG_SUCCESS'] . adm_back_link($this->u_action));					}
					else
					{
						trigger_error($user->lang['ACP_GEN_BLOG_ERROR'] . adm_back_link($this->u_action));
					}
				}
				$template->assign_vars(array(
					'U_ACTION'	=> $this->u_action . '&amp;action=add',
					'S_EDIT'	=> true,
				));
			}
			else if($action == 'edit')
			{
				$id = request_var('id', '');
				$template->assign_vars(array(
					'ID' => $id,
					'U_ACTION' => $this->u_action . '&amp;action=edit&amp;id=' . $id,
				));
				if($create)
				{
					$id = request_var('id', '');
					$data = array(
						'cat_title'		=> utf8_normalize_nfc(request_var('title', '')),
						'cat_desc'		=> utf8_normalize_nfc(request_var('desc', '')),
					);
					$sql_ary = array(
						'cat_title'		=> (string) $data['cat_title'],
						'cat_desc'		=> (string) $data['cat_desc'],
					);
					$sql = 'UPDATE ' . BLOG_CATS_TABLE . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . ' 			
					WHERE cat_id = ' . $id;
					$result = $db->sql_query($sql);
					if($result)
					{
						trigger_error($user->lang['ACP_GEN_BLOG_SUCCESS'] . adm_back_link($this->u_action));
					}
					else
					{
						trigger_error($user->lang['ACP_GEN_BLOG_ERROR'] . adm_back_link($this->u_action));
					}
				}
				else
				{
					$sql = 'SELECT *
							FROM ' . BLOG_CATS_TABLE . '
							WHERE cat_id = ' . $id . '
							LIMIT 1';
					$result = $db->sql_query($sql);
					while($row = $db->sql_fetchrow($result))
					{
						$template->assign_vars(array(
							'BLOG_CAT_TITLE' 	=> $row['cat_title'],
							'BLOG_CAT_DESC'		=> $row['cat_desc'],
						));
					}
					$template->assign_vars(array(
						'U_ACTION'	=> $this->u_action . '&amp;action=edit',
						'S_EDIT'	=> true,
					));
				}
			}
			else
			{
				$template->assign_vars(array(
					'U_ACTION' 	=> $this->u_action . '&amp;action=add',
				));
				switch($action)
				{
					case 'delete':
						$id = request_var('id', '');
						$db->sql_query('DELETE FROM ' . BLOG_CATS_TABLE . ' WHERE cat_id = ' . $id);
						trigger_error($user->lang['ACP_GEN_BLOG_SUCCESS'] . adm_back_link($this->u_action));
					break;
				}
			}
			$sql = 'SELECT COUNT(cat_id) AS num_cats
			FROM ' . BLOG_CATS_TABLE;
			$result = $db->sql_query($sql);
			$topics_count = (int) $db->sql_fetchfield('num_cats');
			$db->sql_freeresult($result);

			if($topics_count < 1)
			{
				$template->assign_var('S_NOROWS_EXIST', true);
			}
			else
			{
				$sql = 'SELECT *
				FROM ' . BLOG_CATS_TABLE . '
				ORDER BY cat_id ASC';
				$result = $db->sql_query($sql);
				while ($row = $db->sql_fetchrow($result))
				{
					$template->assign_block_vars('catrow', array(
						'CAT_TITLE'			=> $row['cat_title'],
						'CAT_DESC'				=> $row['cat_desc'],
						'U_EDIT'			=> $this->u_action . '&amp;action=edit&amp;id=' . $row['cat_id'],
						'U_DELETE'			=> $this->u_action . '&amp;action=delete&amp;id=' . $row['cat_id'],
					));
				}
			}
			$db->sql_freeresult($result);
		break;
	  }
   }
	/**
	 * Obtains the latest MOD version information
	 *
	 * Code ported from phpBB's version check system.
     *
     * @return string | false Version info on success, false on failure.
	 */
	private function obtain_latest_mod_version_info()
	{
		$errstr = '';
		$errno = 0;

		$info = get_remote_file('michaelcullum.com', '/modsver',
				'blogver.txt', $errstr, $errno);
	
		if ($info === false)
		{
			return false;
		}
	
		return $info;
	}
}
?>