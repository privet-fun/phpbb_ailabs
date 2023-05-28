<?php

/**
 *
 * AI Labs extension
 *
 * @copyright (c) 2023, privet.fun, https://privet.fun
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace privet\ailabs\migrations\v1x;

class release_1_0_0_schema extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v320\v320');
	}

	public function update_schema()
	{
		return [
			'add_tables'	=> [
				// Config table
				$this->table_prefix . 'ailabs_users'	=> [
					'COLUMNS'		=> [
						'user_id'				=> ['UINT', 0],
						'controller'			=> ['VCHAR', ''],  		// eg /ailabs/chatgpt
						'config'				=> ['TEXT_UNI', ''],	// JSON
						'template'				=> ['TEXT_UNI', ''], 	// eg [quote={poster_name} post_id={post_id} user_id={poster_id}]{request}[/quote]{response}{attachments}
						'forums_post'    		=> ['VCHAR', ''], 		// eg ["forum_id1","forum_id2"]
						'forums_mention'		=> ['VCHAR', ''], 		// eg ["forum_id1","forum_id2"]
						'enabled'				=> ['BOOL', 0],
					],
					'PRIMARY_KEY'	=> 'user_id',
				],
				// Jobs table
				$this->table_prefix . 'ailabs_jobs'		=> [
					'COLUMNS'		=> [
						'job_id'			=> ['UINT', null, 'auto_increment'],
						'ailabs_user_id'	=> ['UINT', 0],
						'ailabs_username'	=> ['VCHAR', ''],
						'status'			=> ['VCHAR:10', null], 	// exec, ok, fail (null -> exec -> ok | fail, null -> exec -> null)
						'attempts'			=> ['UINT', 0],
						'request_time'		=> ['UINT:11', 0],
						'response_time'		=> ['UINT:11', null],
						'post_mode'			=> ['VCHAR:10', ''], 	// post, reply, quote
						'post_id'			=> ['UINT:10', 0],
						'forum_id'			=> ['UINT:8', 0],
						'poster_id'			=> ['UINT:10', 0],
						'poster_name'		=> ['VCHAR', ''],
						'request'			=> ['TEXT_UNI', ''],
						'request_tokens'	=> ['UINT:8', null],
						'response'			=> ['TEXT_UNI', null],
						'response_tokens'	=> ['UINT:8', null],
						'response_post_id'	=> ['UINT:10', null],
						'log'				=> ['TEXT_UNI', null],
					],
					'PRIMARY_KEY'	=> 'job_id',
					'KEYS' => [
						'idx_ailabs_jobs' 		=> [null, ['response_post_id', 'status']],
						'idx_ailabs_post_id' 	=> [null, ['post_id']],
					],
				],
			],
			'add_columns'	=> [
				$this->table_prefix . 'posts' => [
					'post_ailabs_data' => ['TEXT_UNI', null],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns'	=> [
				$this->table_prefix . 'posts' => [
					'post_ailabs_data',
				],
			],
			'drop_tables' => [
				$this->table_prefix . 'ailabs_users',
				$this->table_prefix . 'ailabs_jobs',
			],
		];
	}

	// https://area51.phpbb.com/docs/dev/master/extensions/tutorial_modules.html
	public function update_data()
	{
		return [
			['module.add', [
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_AILABS_TITLE'
			]],

			['module.add', [
				'acp',
				'ACP_AILABS_TITLE',
				[
					'module_basename'	=> '\privet\ailabs\acp\main_module',
					'modes'				=> ['settings'],
				],
			]],
		];
	}
}
