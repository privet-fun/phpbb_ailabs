<?php

/**
 *
 * AI Labs extension
 *
 * @copyright (c) 2024, privet.fun, https://privet.fun
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace privet\ailabs\migrations\v1x;

class release_1_0_8_schema extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array('\privet\ailabs\migrations\v1x\release_1_0_7_schema');
	}

	public function effectively_installed()
	{
		return isset($this->config['privet_ailabs_version']) && version_compare($this->config['privet_ailabs_version'], '1.0.8', '>=');
	}

	public function update_data()
	{
		return array(
			array('config.update', array('privet_ailabs_version', '1.0.8')),
		);
	}

	public function revert_data()
	{
		return array(
			array('config.update', array('privet_ailabs_version', '1.0.7')),
		);
	}

	public function update_schema()
	{
		return [
			'change_columns'	=> [
				$this->table_prefix . 'ailabs_jobs' => [
					'log'				=> ['MTEXT_UNI', null], // this will be **irreversible** change 
				],
			],
		];
	}

	public function revert_schema()
	{
		/* 
			We CAN NOT change ailabs_jobs.log type back to TEXT_UNI since new MTEXT_UNI is a lot bigger.
			If there were any new records inserted it will most certainly fail.
		*/
	}
}
