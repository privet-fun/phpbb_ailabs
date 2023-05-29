<?php

/**
 *
 * AI Labs extension
 *
 * @copyright (c) 2023, privet.fun, https://privet.fun
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace privet\ailabs\controller;

class acp_controller //implements acp_interface 
{
	protected $config;
	protected $db;
	protected $language;
	protected $log;
	protected $notification_manager;
	protected $pagination;
	protected $request;
	protected $template;
	protected $user;
	protected $root_path;
	protected $ailabs_users_table;

	protected $id;
	protected $mode;
	protected $action;
	protected $submit;
	protected $u_action;
	protected $user_id;
	protected $tpr_ailabs;

	protected $desc_contollers = [
		'/ailabs/chatgpt',
		'/ailabs/dalle',
		'/ailabs/stablediffusion',
		'/ailabs/scriptexecute'
	];

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\language\language $language,
		\phpbb\log\log $log,
		\phpbb\notification\manager $notification_manager,
		\phpbb\pagination $pagination,
		\phpbb\request\request $request,
		\phpbb\template\template $template,
		\phpbb\user $user,
		$root_path,
		$ailabs_users_table
	) {
		$this->config = $config;
		$this->db = $db;
		$this->language = $language;
		$this->log = $log;
		$this->notification_manager = $notification_manager;
		$this->pagination = $pagination;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->root_path = $root_path;
		$this->ailabs_users_table = $ailabs_users_table;
	}

	public function get_acp_data($id, $mode, $action, $submit, $u_action)
	{
		$this->id = $id;
		$this->mode = $mode;
		$this->action = $action;
		$this->submit = $submit;
		$this->u_action = $u_action;
		$this->user_id = $this->request->variable('user_id', 0);
	}

	public function edit_add()
	{
		$username = $this->request->variable('ailabs_username', '', true);
		$new_user_id = $this->find_user_id($username);

		if ($this->action == 'edit' && empty($this->user_id)) {
			trigger_error($this->language->lang('AILABS_USER_EMPTY') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$edit = [];

		$data = [
			'user_id'			=> $new_user_id,
			'controller'		=> $this->request->variable('ailabs_controller', ''),
			'config'			=> $this->request->variable('ailabs_config', '', true),
			'template'			=> $this->request->variable('ailabs_template', '', true),
			'forums_post'		=> $this->request->variable('ailabs_forums_post', ''),
			'forums_mention'	=> $this->request->variable('ailabs_forums_mention', ''),
			'enabled'			=> $this->request->variable('ailabs_enabled', true),
		];

		if ($this->submit) {

			if (empty($new_user_id)) {
				trigger_error($this->language->lang('AILABS_USER_NOT_FOUND', $username) . adm_back_link($this->u_action), E_USER_WARNING);
			}

			$configs_count = $this->count_configs($new_user_id);

			if (($this->action == 'add' && $configs_count > 0) ||
				($this->action == 'edit' && $new_user_id != $this->user_id && $configs_count > 0)
			) {
				trigger_error($this->language->lang('AILABS_USER_ALREADY_CONFIGURED', $username) . adm_back_link($this->u_action), E_USER_WARNING);
			}

			if (empty($data['forums_post']) && empty($data['forums_mention'])) {
				trigger_error($this->language->lang('AILABS_SPECIFY_POST_OR_MENTION') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			if (!isset($error)) {
				$sql_ary = [
					'user_id'			=> (int) $data['user_id'],
					'controller'		=> (string) $data['controller'],
					'config'			=> (string) html_entity_decode($data['config']),
					'template'			=> (string) html_entity_decode($data['template']),
					'forums_post'		=> (string) html_entity_decode($data['forums_post']),
					'forums_mention'	=> (string) html_entity_decode($data['forums_mention']),
					'enabled'			=> (bool) $data['enabled']
				];

				if ($this->action == 'add') {
					$sql = 'INSERT INTO ' .  $this->ailabs_users_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
					$this->db->sql_query($sql);

					$log_lang = 'LOG_ACP_AILABS_ADDED';
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, $log_lang, false, [$data['user_id']]);

					trigger_error($this->language->lang('ACP_AILABS_ADDED') . adm_back_link($this->u_action), E_USER_NOTICE);
				} else if ($this->action == 'edit') {
					$sql = 'UPDATE ' .  $this->ailabs_users_table . '
						SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
						WHERE user_id = ' . (int) $this->user_id;
					$this->db->sql_query($sql);

					$log_lang = 'LOG_ACP_AILABS_EDITED';
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, $log_lang, false, [$data['user_id']]);

					trigger_error($this->language->lang('ACP_AILABS_UPDATED') . adm_back_link($this->u_action), E_USER_NOTICE);
				}
			}
		} else {
			if ($this->action == 'edit') {
				$sql = 'SELECT * FROM ' . $this->ailabs_users_table . ' WHERE user_id = ' . (int) $this->user_id;
				$result = $this->db->sql_query($sql);
				$row = $this->db->sql_fetchrow($result);

				$edit = [
					'ailabs_user_id'		=> (int) $row['user_id'],
					'ailabs_username'		=> (string) $this->find_user_name((int) $this->user_id),
					'ailabs_controller'		=> (string) $row['controller'],
					'ailabs_config'			=> (string) $row['config'],
					'ailabs_template'		=> (string) $row['template'],
					'ailabs_forums_post'	=> (string) $row['forums_post'],
					'ailabs_forums_mention'	=> (string) $row['forums_mention'],
					'ailabs_enabled'		=> (bool) $row['enabled']
				];

				$this->db->sql_freeresult($result);
			}
		}

		foreach ($this->desc_contollers as $key => $value) {
			$controller = explode("/", $value);
			$name = end($controller);
			$this->template->assign_block_vars('AILABS_CONTROLLER_DESC', [
				'NAME' => $name,
				'VALUE' => $value,
				'SELECTED' => (!empty($edit) && $value === $edit['ailabs_controller'])
			]);
		}

		global $phpbb_root_path, $phpEx;

		$this->template->assign_vars(
			array_merge(
				$edit,
				[
					'S_ERROR'				=> isset($error) ? $error : '',
					'U_AILABS_ADD_EDIT'		=> true,
					'U_ACTION'				=> $this->action == 'add' ? $this->u_action . '&amp;action=add' : $this->u_action . '&amp;action=edit&amp;user_id=' . $this->user_id,
					'U_BACK'				=> $this->u_action,
					'U_FIND_USERNAME'		=> append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=searchuser&amp;form=ailabs_configuration&amp;field=ailabs_username&amp;select_single=true'),
					'AILABS_FORUMS_LIST'	=> $this->build_forums_list(),
				]
			)
		);
	}

	public function delete($user_id)
	{
		if (empty($user_id)) {
			trigger_error('AILABS_USER_EMPTY' . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$sql = 'DELETE FROM ' . $this->ailabs_users_table . ' WHERE user_id = ' . (int) $user_id;
		$result = $this->db->sql_query($sql);
		$this->db->sql_freeresult($result);

		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_AILABS_DELETED');

		return $this;
	}

	public function acp_ailabs_main()
	{
		$sql = 'SELECT a.*, u.username ' .
			'FROM ' . $this->ailabs_users_table . ' a ' .
			'LEFT JOIN ' . USERS_TABLE . ' u ON u.user_id = a.user_id ' .
			'ORDER BY u.username';

		$result = $this->db->sql_query($sql);

		$forums = $this->build_forums_list();

		$ailabs_users = [];

		while ($row = $this->db->sql_fetchrow($result)) {
			if (empty($row)) {
				continue;
			}

			$controller = explode("/", $row['controller']);
			$row['controller'] = end($controller);
			$row['forums_post_names'] = $this->get_forums_names($row['forums_post'], $forums);
			$row['forums_mention_names'] = $this->get_forums_names($row['forums_mention'], $forums);
			$row['U_EDIT'] = $this->u_action . '&amp;action=edit&amp;user_id=' . $row['user_id'] . '&amp;hash=' . generate_link_hash('acp_ailabs');
			$row['U_DELETE'] = $this->u_action . '&amp;action=delete&amp;user_id=' . $row['user_id'] . '&amp;username=' . $row['username'] . '&amp;hash=' . generate_link_hash('acp_ailabs');

			$ailabs_users[] = (array) $row;
		}

		$this->db->sql_freeresult($result);

		$template_vars = [
			'U_AILABS_USERS'		=> $ailabs_users,
			'U_ADD'					=> $this->u_action . '&amp;action=add',
			'U_ACTION'				=> $this->u_action,
			'U_AILABS_VEIW'			=> true
		];

		return $this->template->assign_vars($template_vars);
	}

	protected function find_user_id($username)
	{
		$user_id = null;
		if (!empty($username)) {
			$where = ['username' => $username];
			$sql = 'SELECT user_id FROM ' . USERS_TABLE . ' WHERE ' . $this->db->sql_build_array('SELECT', $where);
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			if (!empty($row) && !empty($row['user_id']))
				$user_id = $row['user_id'];
			$this->db->sql_freeresult($result);
		}
		return $user_id;
	}

	protected function find_user_name($user_id)
	{
		$username = null;
		if (!empty($user_id)) {
			$where = ['user_id' => $user_id];
			$sql = 'SELECT username FROM ' . USERS_TABLE . ' WHERE ' . $this->db->sql_build_array('SELECT', $where);
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			if (!empty($row) && !empty($row['username']))
				$username = $row['username'];
			$this->db->sql_freeresult($result);
		}
		return $username;
	}

	protected function count_configs($user_id)
	{
		$count = 0;
		if (!empty($user_id)) {
			$where = ['user_id' => $user_id];
			$sql = 'SELECT count(*) as cnt FROM ' . $this->ailabs_users_table . ' WHERE ' . $this->db->sql_build_array('SELECT', $where);
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			if (!empty($row) && !empty($row['cnt']))
				$count = $row['cnt'];
			$this->db->sql_freeresult($result);
		}
		return $count;
	}

	protected function build_forums_list()
	{
		$return = [];
		$sql = 'SELECT forum_id, forum_name FROM ' . FORUMS_TABLE . ' ORDER BY left_id';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result)) {
			$return[$row['forum_id']] = $row['forum_name'];
		}
		$this->db->sql_freeresult($result);
		return $return;
	}

	protected function get_forums_names($str, $forums) {
		$result = [];
		if(!empty($str)) {
			$arr = json_decode($str);
			if(!empty($arr) && is_array($arr)) {
				foreach($arr as $id)
				{
					 $name = empty($forums[$id]) ? $id : $forums[$id];
					 array_push($result, $name);
				}
			}
		}
		return join(', ', $result);
	}

}
