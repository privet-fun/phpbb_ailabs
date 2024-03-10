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
	'AILABS_SPECIFY_FORUM'				=> 'Please select at least one forum',

	'LOG_ACP_AILABS_ADDED' 				=> 'AI Labs configuration added',
	'LOG_ACP_AILABS_EDITED' 			=> 'AI Labs configuration updated',
	'LOG_ACP_AILABS_DELETED' 			=> 'AI Labs configuration deleted',

	'ACP_AILABS_ADDED' 				=> 'Configuration successfully created',
	'ACP_AILABS_UPDATED' 			=> 'Configuration successfully updated',
	'ACP_AILABS_DELETED_CONFIRM'	=> 'Are you sure that you wish to delete the configuration associated with user %1$s?',

	'LBL_AILABS_SETTINGS_DESC'		=> 'Please visit 👉 <a href="https://github.com/privet-fun/phpbb_ailabs" target="_blank" rel="nofollow">https://github.com/privet-fun/phpbb_ailabs</a> for detailed configuration instructions, troubleshooting and examples.',
	'LBL_AILABS_USERNAME'			=> 'AI bot',
	'LBL_AILABS_CONTROLLER'			=> 'AI',
	'LBL_AILABS_CONFIG'             => 'Configuration JSON',
	'LBL_AILABS_TEMPLATE'           => 'Template',

	'LBL_AILABS_REPLY_TO'			=> 'Forums where AI bot reply to',
	'LBL_AILABS_POST_FORUMS'		=> 'New topic',
	'LBL_AILABS_REPLY_FORUMS'		=> 'Reply in a topic',
	'LBL_AILABS_QUOTE_FORUMS'		=> 'Quote or <a href="https://www.phpbb.com/customise/db/extension/simple_mentions/" target="_blank" rel="nofollow">mention</a>',
	'LBL_AILABS_ENABLED'			=> 'Enabled',
	'LBL_AILABS_SELECT_FORUMS'		=> 'Select forums...',

	'LBL_AILABS_BOT_URL'			=> 'Bot URL (test)',
	'LBL_AILABS_BOT_URL_EXPLAIN'	=> 'Click on the provided URL, and you should see a new tab open with the response "Processing job 0". <a href="https://github.com/privet-fun/phpbb_ailabs?tab=readme-ov-file#troubleshooting" target="_blank" rel="nofollow">Troubleshooting</a>',

	'LBL_AILABS_CONFIG_EXPLAIN'				=> 'Must be valid JSON, please refer to documnetation for details',
	'LBL_AILABS_TEMPLATE_EXPLAIN'			=> 'Valid variables: {post_id}, {request}, {info}, {response}, {images}, {attachments}, {poster_id}, {poster_name}, {ailabs_username}, {settings}',
	'LBL_AILABS_POST_FORUMS_EXPLAIN'		=> 'Specify forums where AI will reply to new topic',
	'LBL_AILABS_REPLY_FORUMS_EXPLAIN'		=> 'Specify forums where AI will reply to reply in the topic',
	'LBL_AILABS_QUOTE_FORUMS_EXPLAIN'		=> 'Specify forums where AI will reply when quoted or <a href="https://www.phpbb.com/customise/db/extension/simple_mentions/" target="_blank" rel="nofollow">mentioned</a>',
	'LBL_AILABS_IP_VALIDATION'				=> '⚠️ Warning: Your ACP > General > Server Configuration > Security Settings > ' .
		'<a href="%1$s">Session IP validation setting NOT set to None</a>, ' .
		'this may prevent AI Labs  to reply if you are using phpBB extensions which force user to be logged in ' .
		'(eg <a href="https://www.phpbb.com/customise/db/extension/login_required" target="_blank" rel="nofollow">Login Required</a>). ' .
		'Set Session IP validation to None or add "/ailabs/*" to extension whitelist. ' .
		'Please refer to <a href="https://github.com/privet-fun/phpbb_ailabs#troubleshooting" target="_blank" rel="nofollow">troubleshooting section</a> for more details.',

	'LBL_AILABS_CONFIG_DEFAULT'				=> 'Load default configuration',
	'LBL_AILABS_TEMPLATE_DEFAULT'			=> 'Load default template',
	
	'LBL_AILABS_API_DOCS'			=> 'API Documentation',
]);
