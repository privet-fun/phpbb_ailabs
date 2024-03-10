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
	'AILABS_MJ_BUTTONS'					=> 'Ответьте, процитировав одну из поддерживаемых команд [size=70][url=https://docs.midjourney.com/docs/quick-start#8-upscale-or-create-variations]1[/url] [url=https://docs.midjourney.com/docs/quick-start#9-enhance-or-modify-your-image]2[/url] [url=https://docs.midjourney.com/docs/zoom-out#custom-zoom]3[/url][/size]: ',
	'AILABS_MJ_BUTTON_ALREADY_USED'		=> 'Команда %1s уже была [url=%2$s?p=%3$d#p%3$d]выполнена[/url]',
	'AILABS_ERROR_CHECK_LOGS'			=> '[color=#FF0000]Ошибка. Лог содержит детальную информацию.[/color]',
	'AILABS_ERROR_UNABLE_DOWNLOAD_URL'	=> 'Не могу скачать ',
	'AILABS_ERROR_PROVIDE_URL' 			=> 'Пожалуйста прикрепите изображение или укажите URL-адрес изображения для анализа.',
	'AILABS_POSTS_DISCARDED'  			=> ', сообщения начиная с [url=%1$s?p=%2$d#p%2$d]этого[/url] не включены',
	'AILABS_DISCARDED_INFO' 			=> '[size=75][url=%1$s?p=%2$d#p%2$d]Начало[/url] беседы из %3$d сообщений%4$s (%5$d токенов из %6$d использовано)[/size]',
	'AILABS_THINKING' 					=> 'думает',
	'AILABS_REPLYING' 					=> 'отвечает…',
	'AILABS_REPLIED' 					=> 'ответил ↓',
	'AILABS_UNABLE_TO_REPLY' 			=> 'ответить не смог',
	'AILABS_QUERY' 						=> 'в очереди',
	'L_AILABS_AI'						=> 'AI',
	'AILABS_SETTINGS_OVERRIDE'			=> '[size=75]%1$s[/size]'
]);
