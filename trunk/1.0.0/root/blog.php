<?php
/**
* Main file
*
* @package Blog
* @version 1.0.1
* @copyright (c) 2010, 2011 Michael Cullum (Unknown Bliss of http://michaelcullum.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
*
*/

/**
* @ignore
*/
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_root_path . 'includes/functions_display.' . $phpEx);
include($phpbb_root_path . 'includes/functions_posting.' . $phpEx);
include($phpbb_root_path . 'includes/mods/functions_blog.' . $phpEx);
include($phpbb_root_path . 'includes/bbcode.' . $phpEx);

// Assigning custom bbcodes
display_custom_bbcodes();

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup('mods/blog');

// Initial var setup
$blog_id	= request_var('id', '');
$cat_id		= request_var('cid', '');
$act_name 	= $config['blog_act_name'];
$action		= request_var($act_name, 'index');
$sql_start = request_var('start', 0);
$sql_limit = request_var('limit', $config['blog_postlimit']);

$template->assign_vars(array(
	'SCRIPT_NAME'			=> 'blog',
	'U_NEW_POST' 			=> append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'post_blog')),
	'S_NEW_POST'	 		=> $auth->acl_get('u_blog_post') ? true : false,
	'ACT_NAME'   			=> $act_name,
	'U_BLOG_MANAGE'			=> $auth->acl_get('a_blog_manage') ? true : false,
	'U_BLOG_INDEX'			=> append_sid("{$phpbb_root_path}blog.$phpEx"),
	'NEW_POST'				=> $user->img('button_article_new', $user->lang('NEW_POST')),
	'OVERALL_BLOG_DESC'		=> $config['blog_description'],
	'U_BLOG_FEED'			=> append_sid("{$phpbb_root_path}blog.rss.$phpEx"),
	'S_CAT_ENABLED'			=> ($config['blog_cat_on']) ? true : false,
	'S_TAGCLOUD_ENABLED'	=> ($config['blog_tag_on']) ? true : false,
	'S_RSS_ENABLED'			=> ($config['blog_rss_feed_on']) ? true : false,
));
$template->assign_block_vars('navlinks', array(
	'FORUM_NAME'	=> $config['blog_title'],
	'U_VIEW_FORUM'	=> append_sid("{$phpbb_root_path}blog.$phpEx"),	
));
if(!$config['blog_on'])
{
	$message = (!empty($config['blog_off_msg'])) ? $config['blog_off_msg'] : $user->lang('BLOG_DISABLED');
	if(!$auth->acl_get('a_'))
	{
		trigger_error($message);
	}
	else
	{
		$template->assign_vars(array(
			'S_BLOG_DISABLED' => true,
			'BLOG_DISABLED'	=> $message,
		));
	}
}

if($config['blog_cat_on'])
{
	blog::get_category_list(5, 'catrow');
}
if($config['blog_tag_on'])
{
	blog::get_tag_cloud();
}

switch($action)
{
	default:
	case 'index':		
		$sql_limit = ($sql_limit > 100) ? 100 : $sql_limit;
		$pagination_url = append_sid("{$phpbb_root_path}blog.$phpEx");
		$sql_ary = array(
			'SELECT'	=> 'b.*, COUNT(bb.blog_id) as blog_count, COUNT(c.cmnt_id) as cmnts_approved, COUNT(cc.cmnt_id) as cmnts_unapproved, ct.cat_title, ct.cat_id
					u.username, u.user_colour, u.user_id',
			'FROM'		=> array(
				BLOGS_TABLE			=> 'b',
				BLOGS_TABLE			=> 'bb',
				BLOG_CMNTS_TABLE	=> 'c',
				BLOG_CMNTS_TABLE	=> 'cc',
				BLOG_CATS_TABLE		=> 'ct',
				USERS_TABLE			=> 'u',
			),
			'WHERE'		=> 'ct.cat_id = b.blog_cat_id
					AND bb.blog_id = b.blog_id
					AND c.cmnt_blog_id = b.blog_id
					AND cc.cmnt_blog_id = b.blog_id
					AND c.cmnt_approved = 1
					AND b.blog_poster_id = u.user_id',
			'ORDER_BY'	=> 'b.blog_id DESC',
		);
		$sql_ary['WHERE'] .= !$auth->acl_get('a_blog_manage') ? 'AND cmnt_approved = 1' : '';
		$sql = $db->sql_build_query('SELECT', $sql_ary);
		$result = $db->sql_query_limit($sql, $sql_limit, $sql_start);
		while($blogrow = $db->sql_fetchrow($result))
		{
			$blogrow['bbcode_options'] = (($blogrow['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) + (($blogrow['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + (($blogrow['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
			$text = generate_text_for_display($blogrow['blog_text'], $blogrow['bbcode_uid'], $blogrow['bbcode_bitfield'], $blogrow['bbcode_options']);
			
			//Generate the URL for the blog
			$url = append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'view', 'id' => $blogrow['blog_id']));
			$message = blog::truncate($text, $config['blog_short_msg'], '...<a href="' . $url . '">' . $user->lang['VIEW_MORE'] . '</a>', '[SNIP]', false, true);
			$template->assign_block_vars('blogrow', array(
				'S_ROW_COUNT'	=> count($blogrow['blog_id']),
				'BLOG_TITLE'	=> $blogrow['blog_title'],
				'CAT_TITLE'		=> $blogrow['cat_title'],
				'U_CAT'			=> append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'cat', 'cid' => $blogrow['blog_cat_id'])),
				'TIME'			=> $user->format_date($blogrow['blog_posted_time']),
				'BLOG_ID'		=> $blogrow['blog_id'],
				'BLOG_DESC'		=> $blogrow['blog_desc'],
				'U_BLOG'		=> append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'view', 'id' => $blogrow['blog_id'])),
				'CMNT_COUNT'	=> $blogrow['cmnts_approved'],
				'CMNT_VIEW'		=> ($blogrow['cmnts_approved'] == 1) ? $user->lang['CMNT'] : $user->lang['CMNTS'],
				'UNAPPROVED_CMNT_COUNT' => $blogrow['cmnts_unapproved'],
				'UNAPPROVED_CMNT_VIEW'	=> ($blogrow['cmnts_unapproved'] == 1) ? $user->lang['UCMNT'] : $user->lang['UCMNTS'],
				'BLOG_TEXT'		=> $message,
				'U_POSTER'		=> append_sid("{$phpbb_root_path}memberlist.$phpEx", array('mode' => 'viewprofile', 'u' => $blowrow['blog_poster_id'])),
				'BLOG_POSTER'	=> get_username_string('full', $blogrow['blog_poster_id'], $blogrow['username'], $blogrow['user_colour']),
			));
		}
		$db->sql_freeresult($result);
		//Start pagination
		$template->assign_vars(array(
			'PAGINATION'        => generate_pagination($pagination_url, $blogrow['blog_count'], $sql_limit, $sql_start),
			'PAGE_NUMBER'       => on_page($blogrow['blog_count'], $sql_limit, $sql_start),
			'TOTAL_BLOGS'       => ($blogrow['blog_count'] == 1) ? $user->lang['LIST_BLOG'] : sprintf($user->lang['LIST_BLOGS'], $blogrow['blog_count']),
		));
		//End pagination
		page_header($user->lang['BLOG']);
		$template->assign_vars(array(
			'S_ACTION' => 'index',
		));
		$template->set_filenames(array(
			'body' => 'blog_index_body.html')
		);
		page_footer();
	break;
	
	case 'cat':
		if(!$cat_id || !is_numeric($cat_id))
		{
			meta_refresh('3', append_sid("{$phpbb_root_path}blog.$phpEx"));
			trigger_error($user->lang['INVALID_CAT_ID'] . '<BR /><BR /><a href="' . append_sid("{$phpbb_root_path}blog.$phpEx") . '">' . $user->lang['RETURN'] . '</a>');
		}
		else
		{
			$pagination_url = append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'cat', 'cid' => $cat_id));
			$sql_ary = array(
				'SELECT'	=> 'b.*, COUNT(bb.blog_id) as blog_count, COUNT(c.cmnt_id) as cmnts_approved, COUNT(cc.cmnt_id) as cmnts_unapproved, ct.cat_id, ct.cat_title, ct.cat_desc, u.username, u.user_colour, u.user_id',
				'FROM'		=> array(
					BLOGS_TABLE			=> 'b',
					BLOGS_TABLE			=> 'bb',
					BLOG_CMNTS_TABLE	=> 'c',
					BLOG_CMNTS_TABLE	=> 'cc',
					BLOG_CATS_TABLE		=> 'ct',
					USERS_TABLE			=> 'u',
				),
				'WHERE'		=> 'b.blog_cat_id = ' . (int) $cat_id . '
						AND ct.cat_id = b.blog_cat_id,
						AND bb.blog_id = b.blog_id
						AND c.cmnt_blog_id = b.blog_id
						AND cc.cmnt_blog_id = b.blog_id
						AND c.cmnt_approved = 1
						AND b.blog_poster_id = u.user_id',
				'ORDER_BY'	=> 'b.blog_id DESC',
			);
			$sql_ary['WHERE'] .= !$auth->acl_get('a_blog_manage') ? ' AND cmnt_approved = 1' : '';
			$sql_limit = ($sql_limit > 100) ? 100 : $sql_limit;
			$sql = $db->sql_build_query('SELECT', $sql_ary);
			$result = $db->sql_query_limit($sql, $sql_limit, $sql_start);
			while($row = $db->sql_fetchrow($result))
			{				
				$blog_data = blog::get_blog_data($row['blog_id']);
				$blog_data['bbcode_options'] = (($blog_data['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
				(($blog_data['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + 
				(($blog_data['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
				$text = generate_text_for_display($blog_data['blog_text'], $blog_data['bbcode_uid'], $blog_data['bbcode_bitfield'], $blog_data['bbcode_options']);
				$url = append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'view', 'id' => $blog_data['blog_id']));
				$message = blog::truncate($text, $config['blog_short_msg'], '...<a href="' . $url . '">' . $user->lang['VIEW_MORE'] . '</a>', '[SNIP]', false, true);
				$template->assign_block_vars('blogrow', array(
					'S_ROW_COUNT'	=> count($blog_data['blog_id']),
					'BLOG_TITLE'	=> $blog_data['blog_title'],
					'BLOG_TEXT'		=> $message,
					'BLOG_DESC'		=> $blog_data['blog_desc'],
					'TIME'			=> $user->format_date($blog_data['blog_posted_time']),
					'CMNT_COUNT'	=> $blog_data['cmnts_approved'],
					'CMNT_VIEW'		=> ($blog_data['cmts_approved'] == 1) ? $user->lang['CMNT'] : $user->lang['CMNTS'],
					'BLOG_POSTER'	=> get_username_string('full', $blog_data['user_id'], $blog_data['username'], $blog_data['user_colour']),
					'UNAPPROVED_CMNT_COUNT' => $blog_data['cmnts_unapproved'],
					'UNAPPROVED_CMNT_VIEW'	=> ($blog_data['cmnts_unapproved'] == 1) ? $user->lang['UCMNT'] : $user->lang['UCMNTS'],
					'LAST_POST_TIME'=> $blog_data['blog_posted_time'],
					'U_BLOG'		=> append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'view', 'id' => $blog_data['blog_id'])),
				));
			}
			$db->sql_freeresult($result);
			//Start pagination
			$template->assign_vars(array(
				'PAGINATION'        => generate_pagination($pagination_url, $blog_data['blog_count'], $sql_limit, $sql_start),
				'PAGE_NUMBER'       => on_page($blog_data['blog_count'], $sql_limit, $sql_start),
				'TOTAL_BLOGS'       => ($blog_data['blog_count'] == 1) ? $user->lang['LIST_BLOG'] : sprintf($user->lang['LIST_BLOGS'], $blog_data['blog_count']),
			));
			//End pagination
			page_header($user->lang['BLOG']);
			$template->assign_vars(array(
				'S_ACTION' => 'cat',
			));
			$sql = 'SELECT * FROM ' . BLOG_CATS_TABLE . ' WHERE cat_id = ' . (int) $cat_id;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			$template->assign_block_vars('navlinks', array(
				'FORUM_NAME'   => $row['cat_title'],
				'U_VIEW_FORUM'   => append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'cat', 'id' => $cat_id)),
			));
			$template->set_filenames(array(
				'body' => 'blog_index_body.html')
			);
			page_footer();
		}
	break;
		
	case 'view':
		if(!$auth->acl_get('u_blog_view'))
		{
			trigger_error($user->lang['UNAUTHED']);
		}
		$blog_data 	= (isset($blog_id) && is_numeric($blog_id)) ? blog::get_blog_data($blog_id) : '';
		if(empty($blog_data))
		{
			meta_refresh('3', append_sid("{$phpbb_root_path}blog.$phpEx"));
			trigger_error($user->lang['INVALID_BLOG_ID'] . '<BR /><BR /><a href="' . $phpbb_root_path . append_sid("{$phpbb_root_path}blog.$phpEx") . '">' . $user->lang['RETURN'] . '</a>');
		}
		else
		{
			$blog_data['bbcode_options'] = (($blog_data['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
			(($blog_data['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + 
			(($blog_data['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
			$text = generate_text_for_display($blog_data['blog_text'], $blog_data['bbcode_uid'], $blog_data['bbcode_bitfield'], $blog_data['bbcode_options']);
			$text = str_replace('[SNIP]', '', $text);
			$edit_allow = ((($user->data['user_id'] == $blog_data['blog_poster_id']) && $auth->acl_get('u_blog_post_manage')) || $auth->acl_get('a_blog_manage')) ? true : false;
			
			blog::get_tag_cloud('tag', $blog_id);
			$template->assign_vars(array(
				'BLOG_TITLE' => $blog_data['blog_title'],
				'BLOG_DESC' => $blog_data['blog_desc'],
				'TIME' => $user->format_date($blog_data['blog_posted_time']),
				'EDIT_TIME'	=> $user->format_date($blog_data['blog_last_edited']),
				'S_EDITED' => ($blog_data['blog_last_edited'] != 0) ? true : false,
				'BLOG_ID' => $blog_data['blog_id'],
				'U_BLOG_LINK' => append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name = 'view', 'id' => $blog_data['blog_id'])),
				'BLOG_POSTER' => get_username_string('full', $blog_data['user_id'], $blog_data['username'], $blog_data['user_colour']),
				'POSTER_POSTS'	=> $blog_data['user_posts'],
				'POSTER_JOINED'	=> $user->format_date($blog_data['user_regdate']),
				'BLOG_TEXT'	=> $text,		
				'U_CAT' => append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'cat', 'cid' => $blog_data['cat_id'])),
				'BLOG_CAT'	=> $blog_data['cat_title'],
				'U_BLOG_CMNT' => append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'post_comment', 'id' => $blog_data['blog_id'])), 
				'CMNT_POSTER_ID' => $user->data['user_id'],
				'U_BLOG_EDIT' => append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'edit_blog', 'id' => $blog_data['blog_id'])),
				'U_DELETE' => append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'delete_blog', 'id' => $blog_data['blog_id'])),
				//AUTH
				'S_VIEW_COMMENTS' => ($auth->acl_get('u_blog_view_comments')) ? true : false,
				'S_POST_COMMENT' => ($auth->acl_get('u_blog_comment')) ? true : false,
				'S_CMNT_ALLOW'	=> ($blog_data['blog_allow_cmnt'] === '1') ? true : false,
				'S_EDIT_ALLOW'	=> $edit_allow,
				'S_TAGS_SHOW'	=> ($config['blog_tag_on'] == 1) ? true : false,
			  ));
			//Get comments
			$perm = ($auth->acl_get('a_blog_manage')) ? true : false;			
			$sql = 'SELECT COUNT(cmnt_id) AS cmnt_count
			FROM ' . BLOG_CMNTS_TABLE . '
			WHERE cmnt_blog_id = \'' . $blog_id . '\' ' . (($perm != true) ? 'AND cmnt_approved = \'1\'' : '');
			$result = $db->sql_query($sql);
			$total_cmnts = $db->sql_fetchfield('cmnt_count');
			$db->sql_freeresult($result);
			
			$sql_limit = ($sql_limit > 100) ? 100 : $sql_limit;
			$pagination_url = append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'view', 'id' => $blog_id));

			$sql_ary = array(
				'SELECT'	=> 'c.*, u.user_id, u.username, u.user_colour',
				'FROM'		=> array(
					BLOG_CMNTS_TABLE	=> 'c',
					USERS_TABLE			=> 'u',
				),
				'WHERE'		=> 'c.cmnt_blog_id = ' . (int) $blog_data['blog_id'] . '
							AND u.user_id = ct.cmnt_poster_id',
			);
			$sql_ary['WHERE'] .= (!$perm) ? ' AND c.cmnt_approved = 1' : '';
			$sql = $db->sql_build_query('SELECT', $sql_ary);
			$result = $db->sql_query_limit($sql, $sql_limit, $sql_start);
			$total_cmnts = 0;
			while($cmnt = $db->sql_fetchrow($result))
			{
				$total_cmnts++;
				$cmnt['bbcode_options'] = (($cmnt['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) + (($cmnt['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + (($cmnt['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
				$text = generate_text_for_display($cmnt['cmnt_text'], $cmnt['bbcode_uid'], $cmnt['bbcode_bitfield'], $cmnt['bbcode_options']);
				$apprv = append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'apprvcmnt', 'cmntid' => $cmnt['cmnt_id']));
				$template->assign_block_vars('commentrow', array(
					'CMNT_POSTER' 		=> get_username_string('full', $cmnt['cmnt_poster_id'], $cmnt['username'], $cmnt['user_colour']),
					'U_CMNT_POSTER' 	=> append_sid("{$phpbb_root_path}memberlist.$phpEx", array('mode' => 'viewprofile', 'u' => $cmnt['cmnt_poster_id'])),
					'TIME'				=> $user->format_date($cmnt['cmnt_posted_time']),
					'S_COMMENT_APPROVED'=> ($cmnt['cmnt_approved'] == 0) ? false : true,
					'CMNT_APPROVE'		=> sprintf($user->lang['APPROVE'], $apprv),
					'CMNT_TEXT'	  		=> $text,
					'S_EDIT_CMNT'		=> ($auth->acl_get('a_blog_manage') || ($user->data['user_id'] == $cmnt['cmnt_poster_id'] && $auth->acl_get('u_blog_comment_manage'))) ? true : false,
					'U_DELETE'			=> append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'delete_comment', 'id' => $blog_data['blog_id'], 'cid' => $cmnt['cmnt_id'])),
					'U_CMNT_EDIT'		=> append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'edit_comment', 'cid' => $cmnt['cmnt_id'])),
				));
			}
			$db->sql_freeresult($result);
			//Start pagination
			$template->assign_vars(array(
				'PAGINATION'        => generate_pagination($pagination_url, $total_cmnts, $sql_limit, $sql_start),
				'PAGE_NUMBER'       => on_page($total_cmnts, $sql_limit, $sql_start),
				'TOTAL_CMNTS'       => ($total_cmnts == 1) ? $user->lang['LIST_CMNT'] : sprintf($user->lang['LIST_CMNTS'], $total_cmnts),
			));
			//End pagination
			page_header($user->lang['BLOG']);
			$template->assign_vars(array(
				'S_ACTION' => 'view',
				'CMNT_POSTER_ID' => $user->data['user_id'],
			));
			$template->assign_block_vars('navlinks', array(
				'FORUM_NAME'   => $blog_data['cat_title'],
				'U_VIEW_FORUM'   => append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'cat', 'cid' => $blog_data['cat_id'])),
			));
			$template->set_filenames(array(
				'body' => 'blog_index_body.html')
			);
			page_footer();

		}
	break;
	
	case 'apprvcmnt':
		$comment_id = request_var('cmntid', (int) 0);
		//This is used to approve comments that are not yet approved.
		if(!$comment_id)
		{
			meta_refresh('3', append_sid($phpbb_root_path . 'blog.' . $phpEx));
			trigger_error($user->lang['INVALID_CMNT_ID'] . '<BR /><BR /><a href="' . append_sid($phpbb_root_path . 'blog.' . $phpEx) . '">' . $user->lang['RETURN'] . '</a>');
		}
		blog::toggle_comment_approval($comment_id);
	break;
	
	case 'post_blog':
		$submit = request_var('submit', '');
		$user->add_lang('posting');
		$template->assign_block_vars('navlinks', array(
			'FORUM_NAME'	=> $user->lang['NEW_POST'],
			'U_VIEW_FORUM'	=> append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'post_blog')),
		));
		if(!$auth->acl_get('u_blog_post'))
		{
			trigger_error($user->lang['UNAUTHED']);
		}
		if($submit)
		{
			$tag = request_var('tags', '', true);
			$tag = ($tag && substr($tag, -1) != ',') ? $tag . ',' : $tag;
			$tag = utf8_normalize_nfc($tag);
			$poll = $uid = $bitfield = $options = ''; 
			$allow_bbcode = ($config['blog_bbcode_on'] == 1) ? true : false;
			$allow_smilies = ($config['blog_emote_on'] == 1) ? true : false; 
			$message = request_var('message', '', true);
			generate_text_for_storage($message, $uid, $bitfield, $options, $allow_bbcode, true, $allow_smilies);
			$data = array(
				'blog_text'			=> $message,
				'blog_title'		=> request_var('title', '', true),
				'blog_desc'			=> request_var('desc', '', true),
				'blog_allow_cmnt'	=> request_var('allow_cmnt', (int) 0),
				'blog_cat_id'		=> request_var('cat_id', (int) 1),
				'blog_poster_id'	=> $user->data['user_id'],
				'blog_tags'			=> $tag,
				'bbcode_bitfield'	=> $bitfield,
				'bbcode_uid'		=> $uid,
				'enable_bbcode'		=> $allow_bbcode,
				'enable_smilies'	=> $allow_smilies,
			);
			$blog_id = blog::submit_blog('new', $data);
			if(!$blog_id)
			{
				trigger_error($user->lang['GEN_ERROR']);
			}
			$u_action = append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'view', 'id' => $blog_id));
			meta_refresh('3', $u_action);
			trigger_error($user->lang['BLOG_POST_SUCCESS'] . '<BR /><BR /><a href="' . $u_action . '">' . $user->lang['RETURN_POST'] . '</a>');
		}
		else
		{
			$bbcode = ($auth->acl_get('u_blog_bbcode') && ($config['blog_bbcode_on'] == 1)) ? true : false;
			$emote = ($auth->acl_get('u_blog_emote') && ($config['blog_emote_on'] == 1)) ? true : false;
			generate_smilies('inline', 3);
			$sql = 'SELECT *
					FROM '  . BLOG_CATS_TABLE . '
					ORDER BY cat_id ASC';
			$result = $db->sql_query($sql);
			while($row = $db->sql_fetchrow($result))
			{
				$template->assign_block_vars('cat', array(
					'CAT_OPTIONS' => '<option value="' . $row['cat_id'] . '">' . $row['cat_title'] . '</option>',
				));
			}
			
			$template->assign_vars(array(
				'ALLOW_CMNT'			=> 'checked="checked"',
				'S_POSTER_ID_HIDDEN'	=> $user->data['user_id'],
				'S_BBCODE_ALLOWED'		=>	$bbcode,
				'S_SMILIES_ALLOWED'		=> $emote,
				'U_ACTION'				=> append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'post_blog')),
			));
		}
		page_header($user->lang['BLOG']);
		$template->assign_var('S_ACTION', 'new');
		$template->set_filenames(array(
			'body' => 'blog_post_edit_body.html')
		);
		page_footer();
	break;
	
	case 'edit_blog':
		$user->add_lang('posting');
		$submit = request_var('submit', '');
		$bid = (int) $blog_id;
		if(!$auth->acl_get('u_blog_post'))
		{
			trigger_error($user->lang['UNAUTHED']);
		}
		if($submit)
		{
			$tag = request_var('tags', '', true);
			$tag = ($tag != '' && substr($tag, -1) != ',') ? $tag . ',' : $tag;
			$tag = utf8_normalize_nfc($tag);
			$poll = $uid = $bitfield = $options = '';
			$allow_bbcode = ($config['blog_bbcode_on'] == 1) ? true : false;
			$allow_smilies = ($config['blog_emote_on'] == 1) ? true : false; 
			$message = request_var('message', '', true);
			generate_text_for_storage($message, $uid, $bitfield, $options, $allow_bbcode, true, $allow_smilies);
			$data = array(
				'blog_text'			=> $message,
				'blog_title'		=> request_var('title', '', true),
				'blog_desc'			=> request_var('desc', '', true),
				'blog_allow_cmnt'	=> request_var('allow_cmnt', (int) 0),
				'blog_cat_id'		=> request_var('cat_id', (int) 1),
				'blog_poster_id'	=> $user->data['user_id'],
				'blog_tags'			=> $tag,
				'bbcode_bitfield'	=> $bitfield,
				'bbcode_uid'		=> $uid,
				'enable_bbcode'		=> $allow_bbcode,
				'enable_smilies'	=> $allow_smilies,
			);
			$blog_id = blog::submit_blog('update', $data, $blog_id);
			if(!$blog_id)
			{
				trigger_error($user->lang['GEN_ERROR']);
			}
			$u_action = append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'view', 'id' => $blog_id));;
			meta_refresh('3', $u_action);
			trigger_error($user->lang['BLOG_POST_SUCCESS'] . '<BR /><BR /><a href="' . $u_action . '">' . $user->lang['RETURN_POST'] . '</a>');
		}
		else
		{
			$blog_data = (isset($blog_id) && is_numeric($blog_id)) ? blog::get_blog_data($blog_id) : '';
			if($blog_data == '')
			{
				trigger_error($user->lang['GEN_ERROR']);
			}
			$bbcode = ($auth->acl_get('u_blog_bbcode') && $config['blog_bbcode_on']) ? true : false;
			$emote = ($auth->acl_get('u_blog_emote') && $config['blog_emote_on']) ? true : false;
			generate_smilies('inline', 3);
			$sql = 'SELECT *
					FROM '  . BLOG_CATS_TABLE . '
					ORDER BY cat_id ASC';
			$result = $db->sql_query($sql);
			while($row = $db->sql_fetchrow($result))
			{
				$selected = ($blog_data['blog_cat_id'] == $row['cat_id']) ? 'selected="selected"' : '';
				$template->assign_block_vars('cat', array(
					'CAT_OPTIONS' => '<option value="' . $row['cat_id'] . '" ' . $selected .'>' . utf8_normalize_nfc($row['cat_title']) . '</option>',
				));
			}
			decode_message($blog_data['blog_text'], $blog_data['bbcode_uid']);
			$template->assign_vars(array(
				'TITLE'					=> $blog_data['blog_title'],
				'TAGS'					=> $blog_data['blog_tags'],
				'DESC'					=> $blog_data['blog_desc'],
				'MESSAGE'				=> $blog_data['blog_text'],
				'ALLOW_CMNT'			=> ($blog_data['blog_allow_cmnt'] === '1') ? 'checked="checked"' : '',
				'S_POSTER_ID_HIDDEN'	=> $user->data['user_id'],
				'S_BBCODE_ALLOWED'		=> $bbcode,
				'S_SMILIES_ALLOWED'		=> $emote,
				'U_ACTION'				=> append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'edit_blog', 'id' => $blog_data['blog_id'])),
			));
		}
		page_header($user->lang['BLOG']);
		$template->assign_var('S_ACTION', 'edit');
		$template->set_filenames(array(
			'body' => 'blog_post_edit_body.html')
		);
		page_footer();
	break;
	
	case 'delete_blog':
		if(!$blog_id || !is_numeric($blog_id))
		{
			trigger_error($user->lang['INVALID_BLOG_ID']);
		}
		if(confirm_box(true))
		{
			blog::delete_blog($blog_id);
			trigger_error($user->lang['GENERIC_SUCCESS']);
		}
		else
		{
			$s_hidden_fields = build_hidden_fields(array(
				'submit' => true,
				'blog_id' => $blog_id,
				)
			);
			confirm_box(false, 'CONF_DEL_POST', $s_hidden_fields);
			trigger_error($user->lang['GENERIC_ERROR']);
		}
	break;
	
	case 'post_comment':
		if(!$auth->acl_get('u_blog_comment'))
		{
			trigger_error('UNAUTHED');
		}
		$comment_id = request_var('id', 0);
		if(!$comment_id || !isset($_POST['submit']))
		{
			trigger_error($user->lang['INVALID_BLOG_ID']);
		}
		if(request_var('submit', ''))
		{
			$id = request_var('id', (int) 0);
			$approved = ($auth->acl_get('u_blog_approved')) ? true : false;
			$data = array(
				'cmnt_text'			=> request_var('comment', '', true),
				'cmnt_blog_id'		=> $comment_id,
				'cmnt_poster_id'	=> $user->data['user_id'],
				'cmnt_approved'		=> $approved,
				'enable_bbcode'		=> ($config['blog_bbcode_on'] && $auth->acl_get('u_blog_bbcode')) ? true : false,
				'enable_smilies'	=> ($config['blog_emote_on'] && $auth->acl_get('u_block_emote')) ? true : false,
			);
			$comment = blog::submit_comment('new', $id, $data);
			
			$message = $approved ? $user->lang['CMNTSUCCESS'] : $user->lang['RETURN'];
			$submessage = $approved ? $user->lang['RETURN_CMNT'] : $user->lang['RETURN'];
			$u_action = append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'view', 'id' => $id)) . '#comment' . $comment;
			meta_refresh('3', $u_action);
			trigger_error($message . '<BR /><BR /><a href="' . $u_action . '">' . $submessage . '</a>');
		}
		page_header($user->lang['BLOG']);
		page_footer();
	break;
	
	case 'edit_comment':
		$submit = isset($_POST['submit']) ? true : false;
		$cid = request_var('cid', 0);
		if(!is_numeric($cid) || !$cid)
		{
			trigger_error($user->lang['INVALID_CMNT_ID'] . '<BR /><BR /><a href="' . append_sid("{$phpbb_root_path}blog.$phpEx") . '">' . $user->lang['RETURN'] . '</a>');
		}
		if(!$auth->acl_get('u_blog_comment_manage'))
		{
			trigger_error($user->lang['UNAUTHED']);
		}
		if(!$submit)
		{
			$cmnt_data = (isset($cid) && is_numeric($cid)) ? blog::get_comment_data($cid) : '';
			if($cmnt_data == '')
			{
				trigger_error($user->lang['INVALID_CMNT_ID'] . '<BR /><BR /><a href="' . append_sid("{$phpbb_root_path}blog.$phpEx") . '">' . $user->lang['RETURN'] . '</a>');
			}
			decode_message($cmnt_data['cmnt_text'], $cmnt_data['bbcode_uid']);
			$template->assign_vars(array(
				'MESSAGE'	=> $cmnt_data['cmnt_text'],
				'CMNT_BLOG_ID' => $cmnt_data['cmnt_blog_id'],
				'U_ACTION' => append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'edit_comment', 'cid' => $cid)),
			));
		}
		else
		{
			$text = utf8_normalize_nfc(request_var('message', '', true));
			blog::submit_blog_cmnt('update', request_var('poster_id', 2), request_var('blog_id', 1), $text, time(), $cid);
			$cmnt_data = (isset($cid) && is_numeric($cid)) ? blog::get_comment_data($cid) : '';
			meta_refresh('3', append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name = 'view', 'id' => $cmnt_data['cmnt_blog_id'])));
			trigger_error($user->lang['CMNTSUCCESS'] . '<BR /><BR /><a href="' . append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name = 'view', 'id' => $cmnt_data['cmnt_blog_id'])) . '">' . $user->lang['RETURN'] . '</a>');
		}
		page_header($user->lang['BLOG']);
		$template->assign_var('S_ACTION', 'edit');
		$template->set_filenames(array(
			'body' => 'blog_comment_edit_body.html')
		);
		page_footer();
	break;
	
	case 'delete_comment':
		$cid = request_var('cid', 0);
		$blog_id = request_var('id', 0);
		if(!$cid || !is_numeric($cid))
		{
			trigger_error($user->lang['INVALID_CMNT_ID']);
		}
		if(confirm_box(true))
		{
			blog::delete_cmnt($cid, $blog_id);
			trigger_error($user->lang['GENERIC_SUCCESS']);
		}
		else
		{
			$s_hidden_fields = build_hidden_fields(array(
				'submit'	=> true,
				'cid'		=> $cid,
				'blog_id'	=> $blog_id,
				)
			);
			confirm_box(false, 'CONF_DEL_CMNT', $s_hidden_fields);
			trigger_error($user->lang['GENERIC_ERROR']);
		}
	break;
	
	case 'tag':
		$tag = request_var('t', '', true);
		$tag = utf8_normalize_nfc($tag);
		$tag = $db->sql_escape($tag);

		$sql_ary = array(
			'SELECT'	=> 'b.*, COUNT(c.cmnt_id) AS cmnt_count, ct.cat_title, u.username, u.user_colour',
			'FROM'		=> array(
				BLOGS_TABLE			=> 'b',
				BLOG_CMNTS_TABLE	=> 'c',
				BLOG_CATS_TABLE		=> 'ct',
				USERS_TABLE			=> 'u',
			),
			'WHERE'		=> 'ct.cat_id = b.blog_cat_id
						AND c.cmnt_blog_id = b.blog_id
						AND u.user_id = b.blog_poster_id',
			'ORDER_BY'	=> 'b.blog_id DESC',
		);


		$sql = $db->sql_build_query('SELECT', $sql_ary);
		$result = $db->sql_query($sql);
		while($blogrow = $db->sql_fetchrow($result))
		{
			$blogrow['bbcode_options'] = (($blogrow['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) + (($blogrow['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + (($blogrow['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
			
			$text = generate_text_for_display($blogrow['blog_text'], $blogrow['bbcode_uid'], $blogrow['bbcode_bitfield'], $blogrow['bbcode_options']);
			
			$fetch = (!$auth->acl_get('a_blog_manage')) ? 'AND cmnt_approved = \'1\'' : '';
			$sql = 'SELECT COUNT(cmnt_id)
					FROM ' . BLOG_CMNTS_TABLE . '
					WHERE cmnt_blog_id = \'' . $blogrow['blog_id'] . '\' ' . $fetch;
			$res = $db->sql_query($sql);
			$brow = $db->sql_fetchrow($res);
			
			$sql = 'SELECT username,user_colour
					FROM ' . USERS_TABLE . '
					WHERE user_id = \'' . $blogrow['blog_poster_id'] . '\'';
			$res2 = $db->sql_query($sql);
			$urow = $db->sql_fetchrow($res2);
			
			$sql = 'SELECT cat_title
					FROM ' . BLOG_CATS_TABLE . '
					WHERE cat_id = \'' . $blogrow['blog_cat_id'] . '\'';
			$res3 = $db->sql_query($sql);
			$crow = $db->sql_fetchrow($sql);
			
			$url = append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'view', 'id' => $blogrow['blog_id']));
			$message = blog::truncate($text, $config['blog_short_msg'], '...<a href="' . $url . '">' . $user->lang['VIEW_MORE'] . '</a>', '[SNIP]', false, true);
			
			$template->assign_block_vars('blogrow', array(
				'S_ROW_COUNT'	=> count($blogrow['blog_id']),
				'BLOG_TITLE'	=> $blogrow['blog_title'],
				'CAT_TITLE'		=> $blogrow['cat_title'],
				'U_CAT'			=> append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'cat', 'cid' => $blogrow['blog_cat_id'])),
				'TIME'			=> $user->format_date($blogrow['blog_posted_time']),
				'BLOG_DESC'		=> $blogrow['blog_title'],
				'U_BLOG'		=> append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'view', 'id' => $blogrow['blog_id'])),
				'CMNT_COUNT'	=> $blogrow['cmnt_count'],
				'CMNT_VIEW'		=> ($brow['cmnt_count']) ? $user->lang['CMNT'] : $user->lang['CMNTS'],
				'BLOG_TEXT'		=> $message,
				'VIEW_MORE'		=> '...<a href="' . $url . '">' . $user->lang['VIEW_MORE'] . '</a>',
				'BLOG_POSTER'	=> generate_username_string('full', $blogrow['blog_poster_id'], $blogrow['username'], $blogrow['user_colour']),
			));
		}
		page_header($user->lang['BLOG']);
		$template->assign_vars(array(
			'S_ACTION' 		=> 'tag',
			'TAG'			=> $tag,
			'U_TAG'			=> append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'tag', 'tag' => $tag)),
		));
		$template->set_filenames(array(
			'body' => 'blog_index_body.html')
		);
		page_footer();
	break;
}
