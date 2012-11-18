<?php
/**
* Main file
*
* @package Blog
* @version 1.0.3
* @copyright (c) 2010, 2011, 2012 phpBB Blog Team
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
$blog_id	= request_var('id', 0);
$cat_id		= request_var('cid', 0);
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

if (!$config['blog_on'])
{
	$message = (!empty($config['blog_off_msg'])) ? $config['blog_off_msg'] : $user->lang('BLOG_DISABLED');
	
	if (!$auth->acl_get('a_'))
	{
		trigger_error($message);
	}

	$template->assign_vars(array(
		'S_BLOG_DISABLED' => true,
		'BLOG_DISABLED'	=> $message,
	));
}

if ($config['blog_cat_on'])
{
	blog::get_category_list(5, 'catrow');
}

if ($config['blog_tag_on'])
{
	blog::get_tag_cloud();
}

switch($action)
{
	default:
	case 'index':
		$sql_limit = ($sql_limit > MAX_BLOG_CNT_DISPLAY) ? MAX_BLOG_CNT_DISPLAY : $sql_limit;
		$pagination_url = append_sid("{$phpbb_root_path}blog.$phpEx");

		$sql_ary = array(
			'SELECT'	=> 'b.*,
			ct.cat_title,ct.cat_id,
			u.username,u.user_colour,u.user_id',
			'FROM'		=> array(
				BLOGS_TABLE			=> 'b',
				BLOG_CATS_TABLE		=> 'ct',
				USERS_TABLE			=> 'u',

			),
			'WHERE'		=> 'ct.cat_id = b.blog_cat_id AND u.user_id = b.blog_poster_id',
			'ORDER_BY'	=> 'b.blog_id DESC',
		);

		$sql = $db->sql_build_query('SELECT', $sql_ary);
		$result = $db->sql_query_limit($sql, $sql_limit, $sql_start);

		while ($blogrow = $db->sql_fetchrow($result))
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
				'U_POSTER'		=> append_sid("{$phpbb_root_path}memberlist.$phpEx", array('mode' => 'viewprofile', 'u' => $blogrow['blog_poster_id'])),
				'BLOG_POSTER'	=> get_username_string('full', $blogrow['blog_poster_id'], $blogrow['username'], $blogrow['user_colour']),
			));
		}
		$db->sql_freeresult($result);

		// We need another query for the blog count
		$sql = 'SELECT COUNT(*) as blog_count FROM ' . BLOGS_TABLE;
		$result = $db->sql_query($sql);
		$blogrow['blog_count'] = $db->sql_fetchfield('blog_count');
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
		if (!$cat_id)
		{
			meta_refresh('3', append_sid("{$phpbb_root_path}blog.$phpEx"));
			trigger_error($user->lang['INVALID_CAT_ID'] . '<BR /><BR /><a href="' . append_sid("{$phpbb_root_path}blog.$phpEx") . '">' . $user->lang['RETURN'] . '</a>');
		}

		$pagination_url = append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'cat', 'cid' => $cat_id));
		$sql_ary = array(
			'SELECT'	=> 'b.*,
			ct.cat_title,ct.cat_id,
			u.username,u.user_colour,u.user_id',
			'FROM'		=> array(
				BLOGS_TABLE			=> 'b',
				BLOG_CATS_TABLE		=> 'ct',
				USERS_TABLE			=> 'u',

			),
			'WHERE'		=> 'ct.cat_id = ' . (int) $cat_id . ' AND b.blog_cat_id = ' . (int) $cat_id . ' AND u.user_id = b.blog_poster_id',
			'ORDER_BY'	=> 'b.blog_id DESC',
		);
		$sql_limit = ($sql_limit > MAX_BLOG_CNT_DISPLAY) ? MAX_BLOG_CNT_DISPLAY : $sql_limit;
		$sql = $db->sql_build_query('SELECT', $sql_ary);
		$result = $db->sql_query_limit($sql, $sql_limit, $sql_start);
		while ($row = $db->sql_fetchrow($result))
		{				
			if (false !== ($blog_data = blog::get_blog_data($row['blog_id'])))
			{
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
					'CMNT_VIEW'		=> ($blog_data['cmnts_approved'] == 1) ? $user->lang['CMNT'] : $user->lang['CMNTS'],
					'BLOG_POSTER'	=> get_username_string('full', $blog_data['user_id'], $blog_data['username'], $blog_data['user_colour']),
					'UNAPPROVED_CMNT_COUNT' => $blog_data['cmnts_unapproved'],
					'UNAPPROVED_CMNT_VIEW'	=> ($blog_data['cmnts_unapproved'] == 1) ? $user->lang['UCMNT'] : $user->lang['UCMNTS'],
					'LAST_POST_TIME'=> $blog_data['blog_posted_time'],
					'U_BLOG'		=> append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'view', 'id' => $blog_data['blog_id'])),
				));
			}
		}
		$db->sql_freeresult($result);

		// We need another query for the blog count
		$sql = 'SELECT COUNT(*) as blog_count FROM ' . BLOGS_TABLE;
		$result = $db->sql_query($sql);
		$blog_data['blog_count'] = $db->sql_fetchfield('blog_count');
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
			'U_VIEW_FORUM'   => append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'cat', 'cid' => $cat_id)),
		));
		$template->set_filenames(array(
			'body' => 'blog_index_body.html')
		);
		page_footer();
	break;
		
	case 'view':
		if (!$auth->acl_get('u_blog_view'))
		{
			trigger_error($user->lang['UNAUTHED']);
		}

		$blog_data = blog::get_blog_data($blog_id);

		if (empty($blog_data))
		{
			meta_refresh('3', append_sid("{$phpbb_root_path}blog.$phpEx"));
			trigger_error($user->lang['INVALID_BLOG_ID'] . '<BR /><BR /><a href="' . $phpbb_root_path . append_sid("{$phpbb_root_path}blog.$phpEx") . '">' . $user->lang['RETURN'] . '</a>');
		}
		
		$blog_data['bbcode_options'] = (($blog_data['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
		(($blog_data['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + 
		(($blog_data['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
		$text = generate_text_for_display($blog_data['blog_text'], $blog_data['bbcode_uid'], $blog_data['bbcode_bitfield'], $blog_data['bbcode_options']);
		$text = str_replace('[SNIP]', '', $text);
		$edit_allow = ((($user->data['user_id'] == $blog_data['blog_poster_id']) && $auth->acl_get('u_blog_post_manage')) || $auth->acl_get('a_blog_manage'));
		
		$template->assign_vars(array(
			'BLOG_TITLE' => $blog_data['blog_title'],
			'BLOG_DESC' => $blog_data['blog_desc'],
			'TIME' => $user->format_date($blog_data['blog_posted_time']),
			'EDIT_TIME'	=> $user->format_date($blog_data['blog_last_edited']),
			'S_EDITED' => ($blog_data['blog_last_edited']),
			'BLOG_ID' => $blog_data['blog_id'],
			'U_BLOG_LINK' => append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'view', 'id' => $blog_data['blog_id'])),
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
			'S_VIEW_COMMENTS' => ($auth->acl_get('u_blog_view_comments')),
			'S_POST_COMMENT' => ($auth->acl_get('u_blog_comment')),
			'S_CMNT_ALLOW'	=> ($blog_data['blog_allow_cmnt']),
			'S_EDIT_ALLOW'	=> $edit_allow,
			'S_TAGS_SHOW'	=> ($config['blog_tag_on']),
		  ));
		//Get comments
		$perm = ($auth->acl_get('a_blog_manage')) ? true : false;			
		$sql = 'SELECT COUNT(cmnt_id) AS cmnt_count
		FROM ' . BLOG_CMNTS_TABLE . '
		WHERE cmnt_blog_id = \'' . $blog_id . '\' ' . (($perm != true) ? 'AND cmnt_approved = \'1\'' : '');
		$result = $db->sql_query($sql);
		$total_cmnts = $db->sql_fetchfield('cmnt_count');
		$db->sql_freeresult($result);
		
		$sql_limit = ($sql_limit > MAX_BLOG_CNT_DISPLAY) ? MAX_BLOG_CNT_DISPLAY : $sql_limit;
		$pagination_url = append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'view', 'id' => $blog_id));

		$sql_ary = array(
			'SELECT'	=> 'c.*, u.user_id, u.username, u.user_colour',
			'FROM'		=> array(
				BLOG_CMNTS_TABLE	=> 'c',
				USERS_TABLE			=> 'u',
			),
			'WHERE'		=> 'c.cmnt_blog_id = ' . (int) $blog_data['blog_id'] . '
						AND u.user_id = c.cmnt_poster_id',
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

		if (!empty($blog_data['blog_tags']))
		{
			$tags = explode(',', $blog_data['blog_tags']);
			foreach ($tags as $tag)
			{
				$template->assign_block_vars('tag', array(
					'TAG'	=> $tag,
					'U_TAG'		=> append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'tag', 't' => $tag)),
				));
			}
		}

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
	break;
	
	case 'apprvcmnt':
		if (!$auth->acl_get('a_blog_manage'))
		{
			trigger_error('NO_PERMISSION_APPROVE');
		}
		$comment_id = request_var('cmntid', 0);
		//This is used to approve comments that are not yet approved.
		if(!$comment_id)
		{
			meta_refresh('3', append_sid($phpbb_root_path . 'blog.' . $phpEx));
			trigger_error($user->lang['INVALID_CMNT_ID'] . '<BR /><BR /><a href="' . append_sid($phpbb_root_path . 'blog.' . $phpEx) . '">' . $user->lang['RETURN'] . '</a>');
		}
		blog::toggle_comment_approval($comment_id);
	break;
	
	case 'post_blog':
		$user->add_lang('posting');
		$template->assign_block_vars('navlinks', array(
			'FORUM_NAME'	=> $user->lang['NEW_POST'],
			'U_VIEW_FORUM'	=> append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'post_blog')),
		));

		if (!$auth->acl_get('u_blog_post') && !$auth->acl_get('a_blog_manage'))
		{
			trigger_error($user->lang['UNAUTHED']);
		}

		if (isset($_POST['submit']))
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
				'blog_allow_cmnt'	=> request_var('allow_cmnt', 0),
				'blog_cat_id'		=> request_var('cat_id', 1),
				'blog_poster_id'	=> $user->data['user_id'],
				'blog_tags'			=> $tag,
				'blog_posted_time'	=> time(),
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
		
		$bbcode		= $config['blog_bbcode_on'] && $auth->acl_get('u_blog_bbcode');
		$emote		= $bbcode && $config['blog_emote_on'] && $auth->acl_get('u_blog_emote');
		$img_status	= $bbcode;
		$url_status	= $config['allow_post_links'];
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
			'S_BBCODE_ALLOWED'		=> $bbcode,
			'S_SMILIES_ALLOWED'		=> $emote,
			'S_BBCODE_IMG'			=> $img_status,
			'S_BBCODE_URL'			=> $url_status,
			'S_LINKS_ALLOWED'		=> $url_status,
			'U_ACTION'				=> append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'post_blog')),
		));
		page_header($user->lang['BLOG']);
		$template->assign_var('S_ACTION', 'new');
		$template->set_filenames(array(
			'body' => 'blog_post_edit_body.html')
		);
		page_footer();
	break;
	
	case 'edit_blog':
		$user->add_lang('posting');

		if (!$auth->acl_get('u_blog_post'))
		{
			trigger_error($user->lang['UNAUTHED']);
		}

		if (isset($_POST['submit']))
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
				'blog_allow_cmnt'	=> request_var('allow_cmnt', 0),
				'blog_cat_id'		=> request_var('cat_id', 1),
				'blog_poster_id'	=> $user->data['user_id'],
				'blog_tags'			=> $tag,
				'blog_last_edited'	=> time(),
				'bbcode_bitfield'	=> $bitfield,
				'bbcode_uid'		=> $uid,
				'enable_bbcode'		=> $allow_bbcode,
				'enable_smilies'	=> $allow_smilies,
			);

			$blog_id = blog::submit_blog('update', $data, $blog_id);

			if (!$blog_id)
			{
				trigger_error($user->lang['GEN_ERROR']);
			}

			$u_action = append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'view', 'id' => $blog_id));;
			meta_refresh('3', $u_action);

			trigger_error($user->lang['BLOG_POST_SUCCESS'] . '<BR /><BR /><a href="' . $u_action . '">' . $user->lang['RETURN_POST'] . '</a>');
		}

		if (!($blog_data = blog::get_blog_data($blog_id)))
		{
			trigger_error($user->lang['GEN_ERROR']);
		}

		$bbcode		= $config['blog_bbcode_on'] && $auth->acl_get('u_blog_bbcode');
		$emote		= $bbcode && $config['blog_emote_on'] && $auth->acl_get('u_blog_emote');
		$img_status	= $bbcode;
		$url_status	= $config['allow_post_links'];

		generate_smilies('inline', 3);

		$sql = 'SELECT *
				FROM '  . BLOG_CATS_TABLE . '
				ORDER BY cat_id ASC';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$selected = ($blog_data['blog_cat_id'] == $row['cat_id']) ? ' selected="selected"' : '';
			$template->assign_block_vars('cat', array(
				'CAT_OPTIONS' => '<option value="' . $row['cat_id'] . '"' . $selected .'>' . utf8_normalize_nfc($row['cat_title']) . '</option>',
			));
		}
		decode_message($blog_data['blog_text'], $blog_data['bbcode_uid']);
		$template->assign_vars(array(
			'TITLE'					=> $blog_data['blog_title'],
			'TAGS'					=> $blog_data['blog_tags'],
			'DESC'					=> $blog_data['blog_desc'],
			'MESSAGE'				=> $blog_data['blog_text'],
			'ALLOW_CMNT'			=> $blog_data['blog_allow_cmnt'] ? 'checked="checked"' : '',
			'S_POSTER_ID_HIDDEN'	=> $user->data['user_id'],
			'S_BBCODE_ALLOWED'		=> $bbcode,
			'S_SMILIES_ALLOWED'		=> $emote,
			'S_BBCODE_IMG'			=> $img_status,
			'S_BBCODE_URL'			=> $url_status,
			'S_LINKS_ALLOWED'		=> $url_status,
			'U_ACTION'				=> append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'edit_blog', 'id' => $blog_data['blog_id'])),
		));
		page_header($user->lang('BLOG'));
		$template->assign_var('S_ACTION', 'edit');
		$template->set_filenames(array(
			'body' => 'blog_post_edit_body.html',
		));
		page_footer();
	break;
	
	case 'delete_blog':
		if(!$blog_id)
		{
			trigger_error($user->lang['INVALID_BLOG_ID']);
		}
		if(confirm_box(true))
		{
			blog::delete_blog($blog_id);
			$u_action = append_sid("{$phpbb_root_path}blog.$phpEx");
			meta_refresh('3', $u_action);
			trigger_error($user->lang['GENERIC_SUCCESS'] . '<BR /><BR /><a href="' . $u_action . '">' . $user->lang['RETURN'] . '</a>');
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
	
	// These cases use almost the same code with only some extra stuff for the latter case
	case 'post_comment':
	case 'edit_comment':
		$cid = request_var('cid', 0);
		// we only need to check for comment managing and content data if we are editing a comment
		if ($action == 'edit_comment')
		{
			if (!$auth->acl_get('a_blog_manage') && !$auth->acl_get('u_blog_comment_manage'))
			{
				trigger_error($user->lang['UNAUTHED']);
			}
			else if (!($comment_data = blog::get_comment_data($cid)))
			{
				trigger_error($user->lang['INVALID_CMNT_ID'] . '<BR /><BR /><a href="' . append_sid("{$phpbb_root_path}blog.$phpEx") . '">' . $user->lang['RETURN'] . '</a>');
			}
		}

		if (!empty($_POST['submit']))
		{
			$id = ($action == 'edit_comment') ? $comment_data['cmnt_blog_id'] : request_var('id', 0);
			$approved = ($auth->acl_get('u_blog_approved')) ? true : false;
			$data = array(
				'cmnt_text'			=> utf8_normalize_nfc(request_var('message', '', true)),
				'cmnt_blog_id'		=> $id,
				'cmnt_poster_id'	=> $user->data['user_id'],
				'cmnt_approved'		=> $approved,
				'enable_bbcode'		=> ($config['blog_bbcode_on'] && $auth->acl_get('u_blog_bbcode')),
				'enable_smilies'	=> ($config['blog_emote_on'] && $auth->acl_get('u_block_emote')),
			);
			$mode = ($action == 'post_comment') ? 'new' : 'update';
			$comment = blog::submit_comment($mode, $id, $data, $cid);
			
			$message = $approved ? $user->lang['CMNTSUCCESS'] : $user->lang['RETURN'];
			$submessage = $approved ? $user->lang['RETURN_CMNT'] : $user->lang['RETURN'];
			$u_action = append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'view', 'id' => $id)) . '#comment' . $comment;
			meta_refresh('3', $u_action);
			trigger_error($message . '<BR /><BR /><a href="' . $u_action . '">' . $submessage . '</a>');
		}		

		// if the form has not been submit, display it.
		decode_message($comment_data['cmnt_text'], $comment_data['bbcode_uid']);
		$template->assign_vars(array(
			'MESSAGE'	=> $comment_data['cmnt_text'],
			'CMNT_BLOG_ID' => $comment_data['cmnt_blog_id'],
			'U_ACTION' => append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'edit_comment', 'cid' => $cid)),
		));

		page_header($user->lang('BLOG'));

		$template->set_filenames(array(
			'body' => 'blog_comment_edit_body.html')
		);
		page_footer();
	break;
	
	case 'delete_comment':
		$cid = request_var('cid', 0);
		$blog_id = request_var('id', 0);
		if(!$cid)
		{
			trigger_error($user->lang['INVALID_CMNT_ID']);
		}
		else if (!$auth->acl_get('a_blog_manage'))
		{
			trigger_error($user->lang('UNAUTHED'));
		}

		if (confirm_box(true))
		{
			blog::delete_cmnt($cid, $blog_id);
			$u_action = append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'view', 'id' => $blog_id));
			meta_refresh('3', $u_action);
			trigger_error($user->lang['GENERIC_SUCCESS'] . '<BR /><BR /><a href="' . $u_action . '">' . $user->lang['RETURN'] . '</a>');
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
			'WHERE'		=> "ct.cat_id = b.blog_cat_id
						AND c.cmnt_blog_id = b.blog_id
						AND u.user_id = b.blog_poster_id
						AND b.blog_tags LIKE '%$tag%'",
			'ORDER_BY'	=> 'b.blog_id DESC',
		);


		$sql = $db->sql_build_query('SELECT', $sql_ary);
		$result = $db->sql_query($sql);
		while($blogrow = $db->sql_fetchrow($result))
		{
			$blogrow['bbcode_options'] = (($blogrow['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) + (($blogrow['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + (($blogrow['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
			
			$text = generate_text_for_display($blogrow['blog_text'], $blogrow['bbcode_uid'], $blogrow['bbcode_bitfield'], $blogrow['bbcode_options']);
			
			$url = append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'view', 'id' => $blogrow['blog_id']));
			$message = blog::truncate($text, $config['blog_short_msg'], '...<a href="' . $url . '">' . $user->lang('VIEW_MORE') . '</a>', '[SNIP]', false, true);
			
			$template->assign_block_vars('blogrow', array(
				'S_ROW_COUNT'	=> count($blogrow['blog_id']),
				'BLOG_TITLE'	=> $blogrow['blog_title'],
				'CAT_TITLE'		=> $blogrow['cat_title'],
				'U_CAT'			=> append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'cat', 'cid' => $blogrow['blog_cat_id'])),
				'TIME'			=> $user->format_date($blogrow['blog_posted_time']),
				'BLOG_DESC'		=> $blogrow['blog_title'],
				'U_BLOG'		=> append_sid("{$phpbb_root_path}blog.$phpEx", array($act_name => 'view', 'id' => $blogrow['blog_id'])),
				'CMNT_COUNT'	=> $blogrow['cmnt_count'],
				'CMNT_VIEW'		=> ($blogrow['cmnt_count']) ? $user->lang['CMNT'] : $user->lang['CMNTS'],
				'BLOG_TEXT'		=> $message,
				'VIEW_MORE'		=> '...<a href="' . $url . '">' . $user->lang['VIEW_MORE'] . '</a>',
				'BLOG_POSTER'	=> get_username_string('full', $blogrow['blog_poster_id'], $blogrow['username'], $blogrow['user_colour']),
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
