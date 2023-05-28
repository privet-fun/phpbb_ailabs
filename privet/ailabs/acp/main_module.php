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

class main_module
{
	protected $phpbb_container;
	protected $request;
	protected $template;

	public $u_action;
	public $tpl_name;
	public $page_title;

	public function main($id, $mode)
	{
		global $phpbb_container, $request, $template;

		$this->phpbb_container = $phpbb_container;
		$this->request = $request;
		$this->template = $template;

		$this->tpl_name = 'acp_ailabs_body';

		$action = $request->variable('action', '');
		$submit = $request->is_set_post('submit');
		$user_id = $request->variable('user_id', 0);
		$username = utf8_normalize_nfc($request->variable('username', '', true));

		$language = $phpbb_container->get('language');
		$language->add_lang('info_acp_ailabs', 'privet/ailabs');

		$acp_controller = $this->phpbb_container->get('privet.ailabs.acp_controller');

		add_form_key('privet_ailabs_settings');
		if ($submit && !check_form_key('privet_ailabs_settings')) {
			trigger_error('FORM_INVALID' . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$acp_controller->get_acp_data($id, $mode, $action, $submit, $this->u_action);

		switch ($mode) {
			case 'settings':
				switch ($action) {
					case 'add':
					case 'edit':

						$this->page_title = ($action == 'add') ? 'ACP_AILABS_TITLE_ADD' : 'ACP_AILABS_TITLE_EDIT';
						$acp_controller->edit_add();

						return;
						break;

					case 'delete':
						if (confirm_box(true)) {
							$acp_controller->delete($user_id);
						} else {
							confirm_box(false, $language->lang('ACP_AILABS_DELETED_CONFIRM', $username), build_hidden_fields([
								'user_id'	=> $user_id,
								'mode'		=> $mode,
								'action'	=> $action,
							]));
						}
						break;
				}

				$this->page_title = 'ACP_AILABS_TITLE_VIEW';
				$acp_controller->acp_ailabs_main();
				break;
			default;
		}
	}
}
