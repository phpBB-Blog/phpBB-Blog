<?php
/**
* Main file
*
* @package Blog
* @version 0.0.1
* @copyright (c) 2010 Michael Cullum (Unknown Bliss of http://unknownbliss.co.uk)
* @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
*
*/
if(!defined('IN_PHPBB'))
{
	exit;
}
class blog
{
	/*
	submit_blog - Create a new blog post.
	Parameters:
		$mode - Possible values: 'new', 'update'
		$data - Array of information to be added to DB (e.g. title, description, time, etc.)
		$blog_id - ID of blog to update; (optional) Default: 0 - leave blank for new.
	Return:
		False if it did not go through, ID of blog if it did
	*/
	function submit_blog( $mode = 'new', $data = array(), $blog_id = 0)
	{
		global $db, $user, $auth, $config, $template, $phpbb_root_path;
		
		if(!is_array($data))
		{
			trigger_error($user->lang['GEN_ERROR']);
		}
		if($mode == 'update' && $blog_id == 0)
		{
			trigger_error($user->lang['GEN_ERROR']);
		}
		foreach($data as $key => $value)
		{
			$data[$key] = $db->sql_escape($value);
			if(is_string($value))
			{
				$data[$key] = utf8_normalize_nfc($value);
			}
		}
		switch($mode)
		{
			default:
			case 'new':
				$sql = 'INSERT INTO ' . BLOGS_TABLE . ' ' . $db->sql_build_array('INSERT', $data);
				$result = $db->sql_query($sql);
				$next_id = ($result) ? $db->sql_nextid() : false;
				$db->sql_freeresult($result);
				
				//handle posting to forum.
				$sql = 'SELECT forum_post_bool,forum_post_id
					FROM ' . BLOG_CATS_TABLE . '
					WHERE cat_id = ' . $db->sql_escape($data['blog_cat_id']);
				$result = $db->sql_query($sql);
				$row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);
				if( ($row['forum_post_bool'] == true) && ($config['blog_forum_post_on'] == true) )
				{
					$forum_id = ($row['forum_post_id'] !== 0) ? $row['forum_post_id'] : $config['blog_forum_post_id'];
					$post_data = array(
						'blog_id'    => $next_id,
						'blog_title' => $data['blog_title'],
						'blog_text'  => $data['blog_text']);
					$post_to_forum = $this->blog_post_to_forum($row['forum_post_id'], $post_data);
					if(!$post_to_forum)
					{
						return false;
					}
				}
				return ($next_id != false) ? $next_id : false;
			break;
			
			case 'update':
				$sql = 'UPDATE ' . BLOGS_TABLE . '
					SET ' . $db->sql_build_array('UPDATE', $data) . "
					WHERE blog_id = $blog_id";
				$result = $db->sql_query($sql);
				$blog_id = ($result) ? $blog_id : false;
				$db->sql_freeresult($result);
				return ($blog_id != false) ? $blog_id : false;
			break;
		}
	}
	
	/*
	blog_post_to_forum - Posts a new topic when a new blog is created in the selected forum
	
	Parameters:
		$forum_id - The ID of the forum to which the topic should be posted
		$data - Array of data for post
	Return:
		true on success, false on failure
	*/
	function blog_post_to_forum($forum_id, $data)
	{
		global $db, $user, $auth, $template, $phpbb_root_path, $config;
		if(!$forum_id || !is_numeric($forum_id))
		{
			trigger_error($user->lang['GEN_ERROR']);
		}
		foreach($data AS $key => $value)
		{
			$data[$key] = $db->sql_escape($value);
			if(is_string($value))
			{
				$data[$key] = utf8_normalize_nfc($value);
			}
		}		
		$url = append_sid(generate_board_url() . '/blog.' . $phpEx . '?' . $config['blog_act_name'] . '=view&id=' . $data['blog_id']);
		$subject = sprintf($user->lang['FP_SUBJECT'], $data['blog_title']);
		$message = sprintf($user->lang['FP_MESSAGE'], $url, $this->truncate($data['blog_text'], 1000, '...', NULL, false, false));
		// variables to hold the parameters for submit_post
		$poll = $uid = $bitfield = $options = ''; 
		$allow_bbcode = ($config['blog_bbcode_on'] == 1) ? true : false;
		$allow_smilies = ($config['blog_emote_on'] == 1) ? true : false;

		generate_text_for_storage($message, $uid, $bitfield, $options, $allow_bbcode, true, $allow_smilies);
		// New Topic Example
		$data = array( 
			// General Posting Settings
			'forum_id'            => $forum_id,
			'topic_id'            => 0,
			'icon_id'            => false,
		
			// Defining Post Options
			'enable_bbcode'    => $allow_bbcode,
			'enable_smilies'    => $allow_smilies,
			'enable_urls'        => true,
			'enable_sig'        => true,
		
			// Message Body
			'message'            => $message,
			'message_md5'    => md5($message),
		
			// Values from generate_text_for_storage()
			'bbcode_bitfield'    => $bitfield,
			'bbcode_uid'        => $uid,
		
			// Other Options
			'post_edit_locked'    => 0,
			'topic_title'        => $subject,
		
			// Email Notification Settings
			'notify_set'        => false,
			'notify'            => false,
			'post_time'         => 0,
			'forum_name'        => '',
		
			// Indexing
			'enable_indexing'    => true,
			'topic_status'		 => 0,
		);
		$s = submit_post('post', $subject, $user->data['username'], POST_NORMAL, &$poll, &$data);
		return ($s) ? true : false;
	}

	/*
	get_blog_data - Returns an array of data for given $blog_id
	
	Parameters:
		$blog_id - ID of blog
	Return:
		False if query is unsuccessful
		Array $row - contains array of data for given $blog_id
	*/
	function get_blog_data($blog_id)
	{
		global $db, $user, $auth, $config, $template, $phpbb_root_path;
		if(!$blog_id)
		{
			trigger_error($user->lang['GEN_ERROR']);
		}
		$sql_ary = array(
			'SELECT'    => 'b.*,c.*,u.*',
		
			'FROM'      => array(
				BLOGS_TABLE				=> 'b',
				BLOG_CATS_TABLE			=> 'c',
				USERS_TABLE				=> 'u',
			),
		
			'WHERE'     => 'b.blog_id = ' . $db->sql_escape($blog_id) . '
				AND c.cat_id = b.blog_cat_id
				AND u.user_id = b.blog_poster_id',
		);
		$sql = $db->sql_build_query('SELECT', $sql_ary);
		$result = $db->sql_query($sql);
		if(!$result)
		{
			return false;
		}
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		return $row;
	}
	
	/*
	get_blog_count - Returns number of blogs (total or in $category)
	
	Parameters:
		$category - (Optional) Category to look inside. Default: 0
	Return:
		$blogs - Number of blogs with the specified criteria
	*/
	function get_blog_count($category = 0)
	{
		global $db;
		$sql_ary = array(
			'SELECT'    => 'COUNT(blog_id) AS count',
			'FROM'      => array(BLOGS_TABLE),
		);
		if($category)
		{
			$sql_ary['WHERE'] = "blog_cat_id = $category";
		}
		$sql = $db->sql_build_query('SELECT', $sql_ary);
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		$blogs = $row['count'];
		return $blogs;
	}
	
	/*
	get_category_list - Assigns template variables for a template $loop of categories
	*/
	function get_category_list($limit = 0, $loop)
	{
		global $db, $template, $auth, $user, $config;
		global $phpbb_root_path, $phpEx;
		if(!$config['blog_cat_on'])
		{
			$template->assign_var('S_CAT_ENABLED', false);
		}
		else
		{
			$template->assign_var('S_CAT_ENABLED', true);
		}
		$sql = 'SELECT *
				FROM ' . BLOG_CATS_TABLE . '
				ORDER BY cat_id';
		$result = $db->sql_query($sql);
		while($catrow = $db->sql_fetchrow($result))
		{
			$sql = 'SELECT COUNT(blog_id) AS num
					FROM ' . BLOGS_TABLE . '
					WHERE blog_cat_id = \'' . $catrow['cat_id'] . '\'';
			$res = $db->sql_query($sql);
			$crow = $db->sql_fetchrow($res);
			$cat_id = $catrow['cat_id'];
			$template->assign_block_vars($loop, array(
				'CAT_ID'	=> $cat_id,
				'CAT_TITLE'	=> $catrow['cat_title'],
				'CAT_DESC'	=> ($catrow['cat_desc'] != '') ? $catrow['cat_desc'] . '<BR />' : '',
				'U_CAT'		=> append_sid('blog.php?' . $config['blog_act_name'] . '=cat&cid=' . $catrow['cat_id']),
				'BLOG_COUNT'=> $crow['num'],
				'S_SHOW_BLOGS'=> ($crow['num'] >= 1) ? true : false,
			));
			$template->assign_var('S_SHOW_BLOGS', ($crow['num'] >= 1) ? true : false);
			$db->sql_freeresult($res);
			$limit = ($limit != '0') ? "LIMIT $limit" : '';
			$sql = 'SELECT *
					FROM ' . BLOGS_TABLE . '
					WHERE blog_cat_id = \'' . $cat_id . '\' ORDER BY blog_id DESC ' . $limit;
			$res = $db->sql_query($sql);
			while($brow = $db->sql_fetchrow($res))
			{
				$template->assign_block_vars('catrow.blogrow', array(
					'BLOG_TITLE' => $brow['blog_title'],
					'U_CAT_BLOG' => append_sid("{$phpbb_root_path}blog.$phpEx?" . $config['blog_act_name'] . '=view&id=' . $brow['blog_id']),
				));
			}
		}
	}
	
	/*
	get_comment_data - Returns an array of data for given $comment_id
	
	Parameters:
		$comment_id - ID of comment
	Return:
		False if query is unsuccessful
		Array $row - contains array of data for given $comment_id
	*/
	function get_comment_data($comment_id)
	{
		global $db, $user, $auth, $config, $template, $phpbb_root_path;
		if(!$comment_id)
		{
			trigger_error($user->lang['GEN_ERROR']);
		}
		$sql_ary = array(
			'SELECT'    => 'c.*,b.*,ct.*,u.*',
		
			'FROM'      => array(
				BLOG_CMNTS_TABLE		=> 'c',
				BLOGS_TABLE				=> 'b',
				BLOG_CATS_TABLE			=> 'ct',
				USERS_TABLE				=> 'u',
			),
		
			'WHERE'     => 'c.cmnt_id = ' . $db->sql_escape($comment_id) . '
				AND b.blog_id = c.cmnt_blog_id
				AND c.cat_id = b.blog_cat_id
				AND u.user_id = b.blog_poster_id',
		);
		$sql = $db->sql_build_query($sql_ary);
		$result = $db->sql_query($sql);
		if(!$result)
		{
			return false;
		}
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		return $row;
	}	
	
	/*
	submit_comment - Create a comment on the selected blog
	
	Parameters:
		$mode - possible values: 'new', 'update'
		$blog_id - ID of blog
		$data - Array of information to be added to DB
		$comment_id - ID of comment (for updating; optional) Default: 0
	Return:
		false if it did not go through, comment ID if it did
	*/
	function submit_comment($mode = 'new', $blog_id, $data = array(), $comment_id = 0)
	{
		foreach($data as $key => $value)
		{
			$data[$key] = $db->sql_escape($value);
			if(is_string($value))
			{
				$data[$key] = utf8_normalize_nfc($value);
			}
		}
		switch($mode)
		{
			default:
			case 'new':
				$sql = 'INSERT INTO ' . BLOG_CMNTS_TABLE . ' ' . $db->sql_build_array('INSERT', $data);
				$result = $db->sql_query($sql);
				$next_id = ($result) ? $db->sql_nextid() : false;
				$db->sql_freeresult($result);
				return ($next_id != false) ? $next_id : false;
			break;
			
			case 'update':
				if($comment_id == 0)
				{
					return false;
				}
				$sql = 'UPDATE ' . BLOG_CMNTS_TABLE . '
					SET ' . $db->sql_build_array('UPDATE', $data) . "
					WHERE comment_id = $comment_id";
				$result = $db->sql_query($sql);
				$comment_id = ($result) ? $comment_id : false;
				$db->sql_freeresult($result);
				return ($comment_id != false) ? $comment_id : false;
			break;
		}
	}
	
	/*
	toggle_comment_approval - Approve/disapprove comment
	
	Parameters:
		$comment_id - ID of comment
	Return:
		true if success, fail if not success
	*/
	function toggle_comment_approval($comment_id)
	{
		global $db, $user, $template, $auth, $phpbb_root_path;
		$sql_ary = array(
			'SELECT'    => 'cmnt_approved',
			'FROM'      => constant('BLOG_COMMENTS TABLE'),
			'WHERE'     =>  'cmnt_id = ' . $db->sql_escape($comment_id),
		);
		$sql = $db->sql_build_query('SELECT', $sql_ary);
		$result = $db->sql_query($sql);
		$end = ($result) ? true : false;
		$db->sql_freeresult($result);
		return ($end) ? true : false;
	}
	
	/*
	delete_blog - Deletes the selected blog & all related comments
	
	NOTE: No authentication is checked here. Check before running!
	
	Parameters:
		(int) $blog_id - ID of blog
	
	Return:
		true if success, false if not success
	*/
	function delete_blog( $blog_id)
	{
		global $db, $user, $phpbb_root_path, $auth, $template;
		if(!$blog_id)
		{
			return false;
		}
		//Make sure it's safe
		$blog_id = $db->sql_escape($blog_id);
		//Delete the blog itself
		$sql = 'DELETE FROM ' . BLOGS_TABLE . "
			WHERE blog_id = $blog_id";
		$result = $db->sql_query($sql);
		if(!$result)
		{
			return false;
		}
		$db->sql_freeresult($result);
		//Delete the comments
		$sql = 'DELETE FROM ' . BLOG_CMNTS_TABLE . "
			WHERE cmnt_blog_id = $blog_id";
		$result = $db->sql_query($sql);
		if(!$result)
		{
			return false;
		}
		$db->sql_freeresult($result);
		return true;
	}
	
	/*
	delete_cmnt - Deletes the selected comment
	
	NOTE: No authentication is checked here. Check before running!
	
	Parameters:
		(int) $comment_id - ID of comment
	Return:
		true if success, false if not success
	*/
	function delete_cmnt($comment_id)
	{
		global $db, $user, $auth, $template, $phpbb_root_path;
		if(!$comment_id)
		{
			return false;
		}
		
		$comment_id = $db->sql_escape($comment_id);
		$sql = 'DELETE FROM ' . BLOG_CMNTS_TABLE . "
			WHERE cmnt_id = $comment_id";
		$result = $db->sql_query($sql);
		if(!$result)
		{
			return false;
		}
		return true;
	}
	
	/*
	getrssfeed - Publish blog posts to RSS feed
	
	Parameters:
		N/A
	Return:
		$feed = XML-formetted feed for use in RSS Feed readers
	*/
	function getrssfeed()
	{
		global $db, $user, $auth, $config, $template, $phpbb_root_path;
		
		$rss_info = '<?xml version="1.0" encoding="ISO-8859-1" ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel> 
<title>'. $config['sitename'] .'</title> 
<link>'. $config['server_protocol'] . $config['server_name'] . $config['script_path'] . '</link>
<atom:link href="'. $config['server_protocol'] . $config['server_name'] . $config['script_path'] . '" rel="self" type="application/rss+xml" />
<description>'. $config['site_desc'] .'</description> 
<language>'. $user->data['user_lang'] .'</language>';
		$sql = 'SELECT *
				FROM ' . BLOGS_TABLE;
		$result = $db->sql_query($sql);
		$rss_item = '';
		while($row = $db->sql_fetchrow($result))
		{
			$data = $this->get_blog_data($row['blog_id']);
			$rss_item .= '
<item>
<title>'. utf8_normalize_nfc($data['blog_title']) .'</title> 
<guid>'. $config['server_protocol'] . $config['server_name'] . $config['script_path'] . '/blog.php?' . $config['blog_act_name'] . '=view&amp;id=' . $data['blog_id'] . '</guid> 
<description><![CDATA['. utf8_normalize_nfc($data['blog_desc']) .']]></description> 
</item>';
		}
		 $rss_end = '</channel> 
	                </rss>'; 
		$feed = $rss_info . $rss_item . $rss_end;
		return $feed;
	}
	
	/*
	get_tag_cloud - Gets and assigns template variables for a tag cloud
	
	Parameters:
		$tagrow - Template loop name
		$blog_id - Tags from specific blog
	Return:
		N/A
	*/
	function get_tag_cloud($tagrow = 'tagrow', $blog_id = '0')
	{
		global $db, $user, $auth, $template, $config;
		//This function's job is to get all of the tags submitted in blog posts
		//and making a cloud based off of them. Maybe it will work, maybe not...
		$whereclause = ($blog_id != '0') ? ' WHERE blog_id = \'' . $blog_id .'\'' : '';
		$sql = 'SELECT blog_tags AS tags
				FROM ' . BLOGS_TABLE . $whereclause;
		$result = $db->sql_query($sql);
		$tags = '';
		while($row = $db->sql_fetchrow($result))
		{
			if($row['tags'] != '\'\'' && $row['tags'] != '')
			{
				$tags .= utf8_normalize_nfc($row['tags']);
			}
		}
		$tags = explode(",",utf8_normalize_nfc($tags));
		$tags = array_unique(utf8_normalize_nfc($tags));
		$factor = 0.5;
		$start_size = 12;

		//Now get the total number of blogs in the database:
		$sql = 'SELECT COUNT(blog_id) AS numblog
				FROM ' . BLOGS_TABLE;
		$result = $db->sql_query($sql);
		$bl = $db->sql_fetchrow($result);
		$num_blogs = $bl['numblog'];
		//Now do another query on each tag to see how many times it occurs in all
		//articles combined.
		foreach($tags AS $tag)
		{
			if($tag != '')
			{
				$sql = 'SELECT COUNT(blog_tags) AS numtag
						FROM ' . BLOGS_TABLE . '
						WHERE blog_tags LIKE \'%' . utf8_normalize_nfc($tag) . '%\'';
				$result = $db->sql_query($sql);
				$row = $db->sql_fetchrow($result);
				
				$count = $row['numtag'];
				if($num_blogs == 0)
				{
					$size = 10;
				}
				elseif($num_blogs == 1)
				{
					$size = 10;
				}
				else
				{
					$size = round(($count * 100) / $num_blogs) * $factor;
					$size = $size/2;
					$size = ($size > 20) ? 20 : $size;
					$size = ($size < 5) ? 5 : $size;
					$size = floor($size);
				}
				$full_tag = trim($tag);
				$template->assign_block_vars($tagrow, array(
					'TAG' => utf8_normalize_nfc($tag),
					'NUM' => $user->lang['BLOGS'] . ': ' . $count,
					'TAGSIZE' => floor($size) . 'pt',
					'U_TAG' => append_sid('blog.php?' . $config['blog_act_name'] . '=tag&t=' . $full_tag),
				));
			}
		}
	}
	
	/*
	trucate - Shortens a post to $length with optional $ending and $splitter. Does nothing to text shorter than $length if
		no $splitter is provided.
	
	Parameters:
		$text - The text to truncate
		$length - The length to shorten $text to
		$ending - (optional) Append this to text after shortened. Default: '...'
		$splitter - (optional) Ignore $length and split at this point. Default NULL
		$exact - (optional) (bool) If true, stop at exactly 100. Otherwise, stop at end of next full word. Default: true
		$considerHtml - (optional) (bool) If true, close all tags before shortening. Otherwise, ignore possible tags. Default: false
	Return:
		$truncate - The shortened text
	*/
	function truncate($text, $length = 100, $ending = '...', $splitter = NULL, $exact = true, $considerHtml = false)
	{
		global $template;
		if(is_array($ending))
		{
			extract($ending);
		}
		$truncate = '';
		if($splitter != NULL)
		{
			if(strpos($text, $splitter, 0))
			{
				$leftcount = strpos($text, $splitter, 0);
				$truncate .= mb_substr($text, 0, $leftcount);
				$template->assign_var('S_SHOW_FULL', false);
				return $truncate . $ending;
			}			
		}
		if($considerHtml)
		{
			if(mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length)
			{
				$template->assign_var('S_SHOW_FULL', true);
				return $text;
			}
			$totalLength = mb_strlen($ending);
			$openTags = array();
			//HTML Tag handling
			preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
			foreach($tags as $tag)
			{
				if(!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2]))
				{
					if(preg_match('/<[\w]+[^>]*>/s', $tag[0]))
					{
						array_unshift($openTags, $tag[2]);
					}
					elseif(preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag))
					{
						$pos = array_search($closeTag[1], $openTags);
						if($pos !== false)
						{
						array_splice($openTags, $pos, 1);
						}
					}
				}
				$truncate .= $tag[1];
				$contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
				if($contentLength + $totalLength > $length)
				{
					$left = $length - $totalLength;
					$entitiesLength = 0;
					if(preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE))
					{
						foreach($entities[0] as $entity)
						{
							if($entity[1] + 1 - $entitiesLength <= $left)
							{
								$left--;
								$entitiesLength += mb_strlen($entity[0]);
							}
							else
							{
								break;
							}
						}
					}
					$truncate .= mb_substr($tag[3], 0 , $left + $entitiesLength);
					break;
				}
				else
				{
					$truncate .= $tag[3];
					$totalLength += $contentLength;
				}
				if($totalLength >= $length)
				{
					break;
				}
			}
		}
		else
		{
			if(mb_strlen($text) <= $length)
			{
				$template->assign_var('S_SHOW_FULL', true);
				return $text;
			}
			else
			{
				$truncate = mb_substr($text, 0, $length - strlen($ending));
			}
		}
		if(!$exact)
		{
			$spacepos = mb_strrpos($truncate, ' ');
			if(isset($spacepos))
			{
				if($considerHtml)
				{
					$bits = mb_substr($truncate, $spacepos);
					preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
					if(!empty($droppedTags))
					{
						foreach($droppedTags as $closingTag)
						{
							if(!in_array($closingTag[1], $openTags))
							{
							array_unshift($openTags, $closingTag[1]);
							}
						}
					}
				}
				$truncate = mb_substr($truncate, 0, $spacepos);
			}
		}
		$truncate .= $ending;
		if($considerHtml)
		{
			foreach($openTags as $tag)
			{
				$truncate .= '</'.$tag.'>';
			}
		}
		$template->assign_var('S_SHOW_FULL', true);
		return $truncate;
	}
}
?>