<?php

/**
 *
 * AI Labs extension
 *
 * @copyright (c) 2023, privet.fun, https://privet.fun
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace privet\ailabs\acp;

class main_info
{
	public function module()
	{
		return [
			'filename'	=> '\privet\ailabs\acp\main_module',
			'title'		=> 'ACP_AILABS_TITLE',
			'modes'		=> [
				'settings'	=> [
					'title'		=> 'ACP_AILABS_SETTINGS',
					'auth'		=> 'ext_privet/ailabs && acl_a_board',
					'cat'		=> ['ACP_AILABS_TITLE']
				],
			],
		];
	}
}
