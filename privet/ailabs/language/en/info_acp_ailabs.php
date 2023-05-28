<?php

/**
 *
 * AI Labs extension
 *
 * @copyright (c) 2023, privet.fun, https://privet.fun
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB')) {
	exit;
}

if (empty($lang) || !is_array($lang)) {
	$lang = [];
}

$lang = array_merge($lang, [
	'ACP_AILABS_TITLE' 			=> 'AI Labs',
	'ACP_AILABS_TITLE_VIEW' 	=> 'AI Labs View Configuration',
	'ACP_AILABS_TITLE_ADD' 		=> 'AI Labs Add Configuration',
	'ACP_AILABS_TITLE_EDIT'		=> 'AI Labs Edit Configuration',
	'ACP_AILABS_SETTINGS' 		=> 'Settings',

	'ACP_AILABS_ADD' 			=> 'Add Configuration',

	'AILABS_USER_EMPTY' 				=> 'Please select user',
	'AILABS_USER_NOT_FOUND'				=> 'Unable to locate user %1$s',
	'AILABS_USER_ALREADY_CONFIGURED'	=> 'User %1$s already configured, only one configuration per user supported',
	'AILABS_SPECIFY_POST_OR_MENTION'	=> 'Both Reply on a post and Reply when quoted can\'t be empty, please specify at least one',

	'LOG_ACP_AILABS_ADDED' 				=> 'AI Labs configuration added',
	'LOG_ACP_AILABS_EDITED' 			=> 'AI Labs configuration updated',
	'LOG_ACP_AILABS_DELETED' 			=> 'AI Labs configuration deleted',

	'ACP_AILABS_ADDED' 				=> 'Configuration successfully created',
	'ACP_AILABS_UPDATED' 			=> 'Configuration successfully updated',
	'ACP_AILABS_DELETED_CONFIRM'	=> 'Are you sure that you wish to delete the configuration associated with user %1$s?',

	'LBL_AILABS_SETTINGS_DESC'		=> 'Please visit 👉 <a href="https://github.com/privet-fun/phpbb_ailabs">https://github.com/privet-fun/phpbb_ailabs</a> for detailed configuration instructions and examples',
	'LBL_AILABS_USERNAME'			=> 'User Name',
	'LBL_AILABS_CONTROLLER'			=> 'AI',
	'LBL_AILABS_CONFIG'             => 'Configuration JSON',
	'LBL_AILABS_TEMPLATE'           => 'Template',
	'LBL_AILABS_REPLY_POST_FORUMS'	=> 'Reply on a post',
	'LBL_AILABS_REPLY_QUOTE_FORUMS'	=> 'Reply when quoted',
	'LBL_AILABS_ENABLED'			=> 'Enabled',
	'LBL_AILABS_SELECT_FORUMS'		=> 'Select forums...',

	'LBL_AILABS_CONFIG_EXPLAIN'				=> 'Must be valid JSON, please refer to documnetation for details',
	'LBL_AILABS_TEMPLATE_EXPLAIN'			=> 'Valid variables: {post_id}, {request}, {info}, {response}, {images}, {attachments}, {poster_id}, {poster_name}, {ailabs_username}',
	'LBL_AILABS_REPLY_POST_FORUMS_EXPLAIN'	=> 'Specify forums where AI will reply to new posts',
	'LBL_AILABS_REPLY_QUOTE_FORUMS_EXPLAIN'	=> 'Specify forums where AI will reply to quoted posts',
	'LBL_AILABS_CONFIG_DEFAULT'				=> 'Load default configuration',
	'LBL_AILABS_TEMPLATE_DEFAULT'			=> 'Load default template',
]);
