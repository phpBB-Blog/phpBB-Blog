<?php
/**
* Main file
*
* @package Blog
* @version 1.0.2
* @copyright (c) 2010, 2011 Michael Cullum (Unknown Bliss of http://michaelcullum.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
*
*/
if (!defined('IN_PHPBB'))
{
	exit;
}
/**
* @package module_install
*/
class acp_blog_info
{
    function module()
    {
        return array(
            'filename'	=> 'acp_blog',
            'title'		=> 'phpBB Blog',
            'version'	=> '1.0.2',
            'modes'		=> array(
				'index'		=> array('title' => 'ACP_BLOG_OVERVIEW',	'auth' => 'acl_a_blog_manage',	'cat' => array('ACP_CAT_BLOG')),
                'settings'	=> array('title' => 'ACP_BLOG_MANAGE',		'auth' => 'acl_a_blog_manage',	'cat' => array('ACP_CAT_BLOG')),
				'cats'		=> array('title' => 'ACP_BLOG_CATS_MANAGE',	'auth' => 'acl_a_blog_manage',	'cat' => array('ACP_CAT_BLOG')),
            ),
        );
    }

    function install()
    {
    }

    function uninstall()
    {
    }
}
