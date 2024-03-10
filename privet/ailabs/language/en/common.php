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
	$lang = array();
}

$lang = array_merge($lang, [
	'AILABS_MJ_BUTTONS'					=> 'Reply by quoting one of the supported actions [size=70][url=https://docs.midjourney.com/docs/quick-start#8-upscale-or-create-variations]1[/url] [url=https://docs.midjourney.com/docs/quick-start#9-enhance-or-modify-your-image]2[/url] [url=https://docs.midjourney.com/docs/zoom-out#custom-zoom]3[/url][/size]: ',
	'AILABS_MJ_BUTTON_ALREADY_USED'		=> 'Action %1s was already [url=%2$s?p=%3$d#p%3$d]executed[/url]',
	'AILABS_ERROR_CHECK_LOGS'			=> '[color=#FF0000]Error. Please check logs.[/color]',
	'AILABS_ERROR_UNABLE_DOWNLOAD_URL'	=> 'Unable to download ',
	'AILABS_ERROR_PROVIDE_URL' 			=> 'Please attach an image or provide an image URL for analysis.',
	'AILABS_POSTS_DISCARDED'  			=> ', posts starting from [url=%1$s?p=%2$d#p%2$d]this post[/url] were discarded',
	'AILABS_DISCARDED_INFO' 			=> '[size=75][url=%1$s?p=%2$d#p%2$d]Beginning[/url] of a conversation containing %3$d posts%4$s (%5$d tokens of %6$d were used)[/size]',
	'AILABS_THINKING' 					=> 'thinking',
	'AILABS_REPLYING' 					=> 'replying…',
	'AILABS_REPLIED' 					=> 'replied ↓',
	'AILABS_UNABLE_TO_REPLY' 			=> 'unable to reply',
	'AILABS_QUERY' 						=> 'querying',
	'L_AILABS_AI'						=> 'AI',
	'AILABS_SETTINGS_OVERRIDE'			=> '[size=75]%1$s[/size]'
]);
