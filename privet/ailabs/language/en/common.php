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
	'AILABS_ERROR_CHECK_LOGS'	=> '[color=#FF0000]Error. Pelase check logs.[/color]',
	'AILABS_POSTS_DISCARDED'  	=> ', posts starting from [url=%1$s?p=%2$d#p%2$d]this post[/url] were discarded',
	'AILABS_DISCARDED_INFO' 	=> '[size=75][url=%1$s?p=%2$d#p%2$d]Beginning[/url] of a conversation containing %3$d posts%4$s (%5$d tokens of %6$d were used)[/size]',
	'AILABS_THINKING' 			=> 'thinking',
	'AILABS_REPLYING' 			=> 'replying…',
	'AILABS_REPLIED' 			=> 'replied ↓',
	'AILABS_UNABLE_TO_REPLY' 	=> 'unable to reply',
	'L_AILABS_AI'				=> 'AI'
]);
