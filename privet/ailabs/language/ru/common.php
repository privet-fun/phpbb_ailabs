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
	'AILABS_ERROR_CHECK_LOGS'	=> '[color=#FF0000]Ошибка. Лог содержит детальную информацию.[/color]',
	'AILABS_POSTS_DISCARDED'  	=> ', сообщения начиная с [url=%1$s?p=%2$d#p%2$d]этого[/url] не включены',
	'AILABS_DISCARDED_INFO' 	=> '[size=75][url=%1$s?p=%2$d#p%2$d]Начало[/url] беседы из %3$d сообщений%4$s (%5$d токенов из %6$d использовано)[/size]',
	'AILABS_THINKING' 			=> 'думает',
	'AILABS_REPLYING' 			=> 'отвечает…',
	'AILABS_REPLIED' 			=> 'ответил ↓',
	'AILABS_UNABLE_TO_REPLY' 	=> 'ответить не смог',
	'L_AILABS_AI'				=> 'AI'
]);
