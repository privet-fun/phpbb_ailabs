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

use phpbb\exception\http_exception;
use privet\ailabs\includes\AIController;

class log extends AIController
{
    public function view_log($post_id)
    {
		if ($this->user->data['user_id'] == ANONYMOUS || $this->user->data['is_bot']) {
			throw new http_exception(401);
		}

        $where = [
            'post_id' => $post_id
        ];

        $sql = 'SELECT * ' . 'FROM ' . $this->jobs_table . ' WHERE ' . $this->db->sql_build_array('SELECT', $where);
        $result = $this->db->sql_query($sql);
        $data = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);

        if (!empty($data)) {
            foreach($data as &$row)
            {
                $row['poster_user_url'] = '/' . append_sid("memberlist.$this->php_ext", 'mode=viewprofile&amp;u=' . $row['poster_id'], true, '');
                $row['ailabs_user_url'] = '/' . append_sid("memberlist.$this->php_ext", 'mode=viewprofile&amp;u=' . $row['ailabs_user_id'], true, '');
                if (!empty($row['response_post_id'])) {
                    $row['response_url'] = '/viewtopic.php?p=' . $row['response_post_id'] . '#p' . $row['response_post_id'];
                }    
            }

            $this->template->assign_block_vars('ailabs_log', [
                'LOGS'  => $data
            ]);
        }

        return $this->helper->render('post_ailabs_log.html', 'AI Labs Log');
    }
}
