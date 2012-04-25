<?php
/**
* Main file
*
* @package Blog
* @version 1.0.2
* @copyright (c) 2010, 2011, 2012 Michael Cullum (Unknown Bliss of http://michaelcullum.com), David King (imkingdavid)
* @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
*
*/

/**
 * @ignore
 */
if(!defined('IN_PHPBB'))
{
	exit;
}

class blog
{
	/**
	 * Create new blog post
	 *
	 * @param string $mode What to do: 'new' || 'update'
	 * @param array $data Blog data
	 * @param int $blog_id Only needed when updating.
	 *
	 * @return mixed
	 */
	static function submit_blog($mode = 'new', $data = array(), $blog_id = 0)
	{
		global $db, $user, $config;
		
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
					$post_to_forum = self::blog_post_to_forum($row['forum_post_id'], $post_data);
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
	
	/**
	 * If enabled when a new blog is created, a new topic is posted in a specified forum
	 *
	 * @param int $forum_id Forum ID
	 * @param array $data Post data
	 *
	 * @return bool
	 */
	static function blog_post_to_forum($forum_id, $data)
	{
		global $db, $user, $config;
		global $phpEx;

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
		$url = append_sid(generate_board_url() . '/blog.' . $phpEx . '?' . $config['blog_act_name'] . '=view&amp;id=' . $data['blog_id']);
		$subject = sprintf($user->lang['FP_SUBJECT'], $data['blog_title']);
		$message = sprintf($user->lang['FP_MESSAGE'], $url, self::truncate($data['blog_text'], 1000, '...', NULL, false, false));
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

	/**
	 * Returns an array of data for given $blog_id
	 *
	 * @param int $blog_id ID of blog
	 *
	 * @return mixed
	 */
	static function get_blog_data($blog_id)
	{
			global $db;
			if(!$blog_id)
			{
				return false;
			}
			$sql_ary = array(
				'SELECT'    => 'b.*,c.*,u.*',
			
				'FROM'      => array(
					BLOGS_TABLE				=> 'b',
					BLOG_CATS_TABLE			=> 'c',
					USERS_TABLE				=> 'u',
				),
			
				'WHERE'     => 'b.blog_id = ' . (int) $blog_id . '
					AND c.cat_id = b.blog_cat_id
					AND u.user_id = b.blog_poster_id',
			);
			$sql = $db->sql_build_query('SELECT', $sql_ary);
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			return $row;
	}

	/**
	 * Returns number of blogs (total or in $category)
	 *
	 * @param int $category Category to look inside, if provided (optional)
	 *
	 * @return int Number of blogs with the specified criteria
	 */
	static function get_blog_count($category = 0)
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
	
	/**
	 * Assigns template variables to template block
	 *
	 * @param int $limit Number of categories to list
	 * @param string $block Block name
	 */
	static function get_category_list($limit, $block)
	{
		global $db, $template, $config;
		global $phpbb_root_path, $phpEx;
		$template->assign_var('S_CAT_ENABLED', ($config['blog_cat_on'] ? true : false));

		$sql = 'SELECT *
				FROM ' . BLOG_CATS_TABLE . '
				ORDER BY cat_id';
		$result = $db->sql_query($sql);
		while($catrow = $db->sql_fetchrow($result))
		{
			// at some point we should look into removing the following query/queries from the loop
			// and perhaps store the needed data somewhere else.
			// for instance, for this one, store the blog_count on the category table
			$sql = 'SELECT COUNT(blog_id) AS num
					FROM ' . BLOGS_TABLE . '
					WHERE blog_cat_id = ' . $catrow['cat_id'];
			$res = $db->sql_query($sql);
			$crow = $db->sql_fetchrow($res);
			$cat_id = $catrow['cat_id'];
			$template->assign_block_vars($block, array(
				'CAT_ID'	=> $cat_id,
				'CAT_TITLE'	=> $catrow['cat_title'],
				'CAT_DESC'	=> ($catrow['cat_desc']) ? $catrow['cat_desc'] . '<BR />' : '',
				'U_CAT'		=> append_sid($phpbb_root_path . 'blog.' . $phpEx, array($config['blog_act_name'] => 'cat', 'cid' => $catrow['cat_id'])),
				'BLOG_COUNT'=> $crow['num'],
				'S_SHOW_BLOGS'=> ($crow['num']) ? true : false,
			));
			$db->sql_freeresult($res);
			$sql_limit = $limit ? "LIMIT $limit" : '';
			$sql = 'SELECT *
					FROM ' . BLOGS_TABLE . "
					WHERE blog_cat_id = $cat_id ORDER BY blog_id DESC $sql_limit";
			$res = $db->sql_query($sql);
			while($brow = $db->sql_fetchrow($res))
			{
				$template->assign_block_vars('catrow.blogrow', array(
					'BLOG_TITLE' => $brow['blog_title'],
					'U_CAT_BLOG' => append_sid("{$phpbb_root_path}blog.$phpEx", array($config['blog_act_name'] => 'view', 'id' => $brow['blog_id'])),
				));
			}
		}
	}

	/**
	 * Returns an array of data for given $comment_id
	 *
	 * @param int $comment_id ID of comment
	 *
	 * @return mixed
	 */
	static function get_comment_data($comment_id)
	{
		global $db;
		if(!$comment_id)
		{
			return false;
		}
		$sql_ary = array(
			'SELECT'    => 'c.*,b.*,ct.*,u.*',

			'FROM'      => array(
				BLOG_CMNTS_TABLE		=> 'c',
				BLOGS_TABLE				=> 'b',
				BLOG_CATS_TABLE			=> 'ct',
				USERS_TABLE				=> 'u',
			),

			'WHERE'     => "c.cmnt_id = $comment_id
				AND b.blog_id = c.cmnt_blog_id
				AND ct.cat_id = b.blog_cat_id
				AND u.user_id = b.blog_poster_id",
		);
		$sql = $db->sql_build_query('SELECT', $sql_ary);
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		if(empty($row))
		{
			return false;
		}
		$db->sql_freeresult($result);
		return $row;
	}

	/**
	 * Create a comment on the selected blog
	 *
	 * @param string $mode What to do (values: 'new' || 'update')
	 * @param int $blog_id ID of blog
	 * @param array $data Information to be added to DB
	 * @param id $comment_id ID of comment (only if updating)
	 *
	 * @return mixed
	*/
	static function submit_comment($mode, $blog_id, $data, $comment_id = 0)
	{
		global $db;
		switch($mode)
		{
			default:
			case 'new':
				$sql = 'INSERT INTO ' . BLOG_CMNTS_TABLE . ' ' . $db->sql_build_array('INSERT', $data);
				$db->sql_query($sql);

				$sql_ary = array('cmnts_unapproved' => 'cmnts_unapproved + 1');
				if ($data['cmnt_approved'])
				{
					$sql_ary['cmnts_approved'] = 'cmnts_approved + 1';
				}
				$sql = 'UPDATE ' . BLOGS_TABLE . '
					SET cmnts_unapproved = cmnts_unapproved + 1' .
					($data['cmnt_approved'] ? ', cmnts_approved = cmnts_approved + 1' : '') .
					' WHERE blog_id = ' . (int) $blog_id;
				$db->sql_query($sql);
			break;
			
			case 'update':
				if (!$comment_id)
				{
					return false;
				}
				$sql = 'UPDATE ' . BLOG_CMNTS_TABLE . '
					SET ' . $db->sql_build_array('UPDATE', $data) . "
					WHERE cmnt_id = $comment_id";
				$db->sql_query($sql);
			break;
		}
		
		// if this were PHP 5.3 I could simply do:
		// return (($comment_id) ?: $db->sql_nextid()) ?: false;
		// much more compact
		$return_id = $comment_id ? $comment_id : $db->sql_nextid();
		return $return_id ? $return_id : false;
	}
	
	/**
	 * Approve/disapprove comment
	 *
	 * @param int $comment_id ID of comment
	 *
	 * @return boolean
	 */
	static function toggle_comment_approval($comment_id)
	{
		global $db;
		$sql = 'SELECT cmnt_approved FROM ' . BLOG_CMNTS_TABLE . " WHERE cmnt_id = $comment_id";
		$sql = $db->sql_build_query('SELECT', $sql_ary);
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		if(empty($row))
		{
			return false;
		}
		// Set the comment to the opposite of its current state
		// the ! returns the opposite value
		$sql = 'UPDATE ' . BLOG_CMNTS_TABLE . ' SET cmnt_approved = ' . !$row['cmnt_approved'];
		$db->sql_query($sql);
		return true;
	}
	
	/**
	 * Deletes the selected blog & all related comments
	 * No authentication is checked here. Check before running!
	 *
	 * @param int $blog_id ID of blog
	 *
	 * @return boolean
	 */
	static function delete_blog($blog_id)
	{
		global $db;
		if(!$blog_id)
		{
			return false;
		}
		//Delete the blog itself
		$sql = 'DELETE FROM ' . BLOGS_TABLE . "
			WHERE blog_id = $blog_id";
		$db->sql_query($sql);
		
		//Delete the comments
		$sql = 'DELETE FROM ' . BLOG_CMNTS_TABLE . "
			WHERE cmnt_blog_id = $blog_id";
		$db->sql_query($sql);

		return true;
	}
	
	/**
	 * Deletes the selected comment
	 * No authentication is checked here. Check before running!
	 *
	 * @param int $comment_id ID of comment
	 * 
	 * @return boolean
	*/
	static function delete_cmnt($comment_id)
	{
		global $db;
		if(!$comment_id)
		{
			return false;
		}
		$sql = 'SELECT cmnt_blog_id FROM ' . BLOG_CMNTS_TABLE . "
			WHERE cmnt_id = $comment_id";
		$result = $db->sql_query($sql);
		$blog_id = $db->sql_fetchfield('cmnt_blog_id');
		$db->sql_freeresult($result);

		$sql = 'DELETE FROM ' . BLOG_CMNTS_TABLE . "
			WHERE cmnt_id = $comment_id";
		$db->sql_query($sql);

		$sql = 'UPDATE ' . BLOGS_TABLE . ' SET cmnts_approved = cmnts_approved - 1, cmnts_unapproved = cmnts_unapproved - 1 WHERE blog_id = ' . $blog_id;
		$db->sql_query($sql);


		return true;
	}
	
	/**
	 * Publish blog posts to RSS feed
	 *
	 * @return RSS-formatted feed
	 */
	static function getrssfeed()
	{
		global $config, $db, $user;
		
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
			$data = self::get_blog_data($row['blog_id']);
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
	
	/**
	 * Gets and assigns template variables for a tag cloud
	 *
	 * @param string $tagrow Template loop name
	 * @param int $blog_id Blog ID to get tags from (optional)
	 *
	 * @return void
	 */
	function get_tag_cloud($tagrow = 'tagrow', $blog_id = 0)
	{
		global $db, $template, $config, $user;
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
		$db->sql_freeresult($result);
		$tags = explode(",",utf8_normalize_nfc($tags));
		$tags = array_unique(utf8_normalize_nfc($tags));
		$factor = 0.5;

		//Now get the total number of blogs in the database:
		$sql = 'SELECT COUNT(blog_id) AS numblog
				FROM ' . BLOGS_TABLE;
		$result = $db->sql_query($sql);
		$bl = $db->sql_fetchrow($result);
		$num_blogs = $bl['numblog'];
		// Now do another query on each tag to see how many times it occurs in all articles combined.

		// NOTE: This is not the optimal way to do this, having a query in a loop
		// The best way I can think of is to add a new column on the tags table that
		// gets updated with the number of times it has been used.
		foreach($tags AS $tag)
		{
			if($tag)
			{
				$sql = 'SELECT COUNT(blog_tags) AS numtag
						FROM ' . BLOGS_TABLE . '
						WHERE blog_tags LIKE \'%' . utf8_normalize_nfc($tag) . '%\'';
				$result = $db->sql_query($sql);
				$count = $db->sql_fetchfield('numtag');
				$db->sql_freeresult($result);
				if($num_blogs <= 1)
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
					'NUM' => $count,
					'TAGSIZE' => floor($size) . 'pt',
					'U_TAG' => append_sid('blog.php?' . $config['blog_act_name'] . '=tag&amp;t=' . $full_tag),
				));
			}
		}
	}
	
	/**
	 * Shortens a post to $length with optional $ending and $splitter. Does nothing to text shorter than $length if
	 *	no $splitter is provided.
	 *
	 * @param string $text The text to truncate
	 * @param int $length The length to shorten $text to (in individual characters)
	 * @param string $ending (optional) Append this to text after shortened. Default: '...'
	 * @param string $splitter If found, the post will be split at this point rather than at a certain length
	 * @param bool $exact Stop at exactly $length characters or at end of current word
	 * @param bool $considerHtml If true, close all tags before shortening.
	 *
	 * @return Shortened text
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
