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

use privet\ailabs\includes\GenericCurl;
use Symfony\Component\HttpFoundation\JsonResponse;
use privet\ailabs\includes\AIController;
use privet\ailabs\includes\resultParse;

/*

config (example)

{
"api_key": "<api-key>",
"url_chat": "https://api.openai.com/v1/chat/completions",
"model": "gpt-3.5-turbo",
"temperature": 0.9,
"max_tokens": 4096,
"message_tokens": 1024,
"top_p": 1,
"frequency_penalty": 0,
"presence_penalty": 0.6,
"prefix": "This is optional field you can remove it or populate with something like this -> Pretend your are Bender from Futurma",
"prefix_tokens": 16
"max_quote_length": 10
}

template

{info}[quote={poster_name} post_id={post_id} user_id={poster_id}]{request}[/quote]{response}

*/

class chatgpt extends AIController
{
    // https://platform.openai.com/docs/api-reference/chat/create#chat/create-max_tokens
    // By default, the number of tokens the model can return will be (4096 - prompt tokens).
    protected $max_tokens = 4096;
    protected $message_tokens = 2048;

    protected function process()
    {
        $this->job['status'] = 'exec';

        $set = [
            'status'            => $this->job['status'],
            'log'               => json_encode($this->log)
        ];

        $this->job_update($set);
        $this->post_update($this->job);

        if (!empty($this->cfg->message_tokens)) {
            $this->message_tokens = $this->cfg->message_tokens;
        }

        if (!empty($this->cfg->max_tokens)) {
            $this->max_tokens = (int)$this->cfg->max_tokens;
        }

        $prefix_tokens = empty($this->cfg->prefix_tokens) ? 0 : $this->cfg->prefix_tokens;

        $api_key = $this->cfg->api_key;
        $this->cfg->api_key = null;
        $api = new GenericCurl($api_key);

        $this->job['status'] = 'fail';
        $response = $this->language->lang('AILABS_ERROR_CHECK_LOGS');
        $api_response = null;
        $request_tokens = null;
        $response_tokens = null;

        $total_replaced = 0;
        $original_request = $this->job['request'];

        if ($total_replaced > 0) {
            $this->job['request'] = trim(str_replace('  ', ' ', $this->job['request']));
            $this->log['request.original'] = $original_request;
            $this->log['request.adjusted'] = $this->job['request'];
        }

        $messages = [];
        $info = null;
        $posts = [];
        $post_first_taken = null;
        $post_first_discarded = null;
        $mode = $this->job['post_mode'];

        $history = ['post_text' =>  $this->job['post_text']];

        $pattern = '/<QUOTE\sauthor="' . $this->job['ailabs_username'] . '"\spost_id="(.*)"\stime="(.*)"\suser_id="' . $this->job['ailabs_user_id'] . '">/';

        $this->log['history.pattern'] = $pattern;
        $this->log_flush();

        // Attempt to unwind history using quoted posts
        $history_tokens = 0;
        $round = -1;
        do {
            $round++;
            $matches = null;
            preg_match_all(
                $pattern,
                $history['post_text'],
                $matches
            );

            $history = null;

            if ($matches != null && !empty($matches) && !empty($matches[1][0])) {
                $postid = (int) $matches[1][0];

                $sql = 'SELECT j.job_id, j.post_id, j.response_post_id, j.request, j.response, p.post_text, p.post_time, j.request_tokens, j.response_tokens ' .
                    'FROM ' . $this->jobs_table . ' j ' .
                    'JOIN ' . POSTS_TABLE . ' p ON p.post_id = j.post_id ' .
                    'WHERE ' . $this->db->sql_build_array('SELECT', ['response_post_id' => $postid]);
                $result = $this->db->sql_query($sql);
                $history = $this->db->sql_fetchrow($result);
                $this->db->sql_freeresult($result);

                if (!empty($history)) {
                    $count_tokens = $history['request_tokens'] + $history['response_tokens'];

                    $discard = $this->max_tokens < ($this->message_tokens + $history_tokens + $count_tokens);

                    $posts[] = [
                        'postid'                => $postid,
                        'request_tokens'        => $history['request_tokens'],
                        'response_tokens'       => $history['response_tokens'],
                        'runnig_total_tokens'   => $history_tokens + $count_tokens,
                        'discard'               => $discard
                    ];

                    if ($discard) {
                        $post_first_discarded = $postid;
                        break;
                    }

                    $post_first_taken = $postid;
                    $history_tokens += $count_tokens;

                    $history_decoded_request = utf8_decode_ncr($history['request']);
                    $history_decoded_response = utf8_decode_ncr($history['response']);

                    array_unshift(
                        $messages,
                        ['role' => 'user', 'content' => trim($history_decoded_request)],
                        ['role' => 'assistant', 'content' => trim($history_decoded_response)]
                    );

                    if ($round == 0) {
                        // Remove quoted content from the quoted post
                        $post_text = sprintf(
                            '<r><QUOTE author="%1$s" post_id="%2$s" time="%3$s" user_id="%4$s"><s>[quote=%1$s post_id=%2$s time=%3$s user_id=%4$s]</s>%6$s<e>[/quote]</e></QUOTE>%5$s</r>',
                            $this->job['ailabs_username'],
                            (string) $postid,
                            (string) $this->job['post_time'],
                            (string) $this->job['ailabs_user_id'],
                            $this->job['request'],
                            property_exists($this->cfg, 'max_quote_length') ?
                                $this->trim_words($history_decoded_response, (int) $this->cfg->max_quote_length) : $history_decoded_response
                        );

                        $sql = 'UPDATE ' . POSTS_TABLE .
                            ' SET ' . $this->db->sql_build_array('UPDATE', ['post_text' => utf8_encode_ucr($post_text)]) .
                            ' WHERE post_id = ' . (int) $this->job['post_id'];
                        $result = $this->db->sql_query($sql);
                        $this->db->sql_freeresult($result);
                    }
                }
            }
        } while (!empty($history));

        if (!empty($posts)) {
            $this->log['history.posts'] = $posts;
            $this->log_flush();
        }

        if (!empty($this->cfg->prefix)) {
            array_unshift(
                $messages,
                ['role' => 'system', 'content' => $this->cfg->prefix]
            );
        }

        $messages[] =  ['role' => 'user', 'content' => trim($this->job['request'])];

        $this->log['request.messages'] = $messages;
        $this->log_flush();

        try {
            // https://api.openai.com/v1/chat/completions
            $api_result = $api->sendRequest($this->cfg->url_chat, 'POST', [
                'model'             => $this->cfg->model,
                'messages'          => $messages,
                'temperature'       => (float) $this->cfg->temperature,
                // https://platform.openai.com/docs/api-reference/chat/create#chat/create-max_tokens
                // By default, the number of tokens the model can return will be (4096 - prompt tokens).
                // 'max_tokens'        => (int) $this->cfg->max_tokens,
                'frequency_penalty' => (float) $this->cfg->frequency_penalty,
                'presence_penalty'  => (float)$this->cfg->presence_penalty,
            ]);

            /*
                Response example:
                {
                    'id': 'chatcmpl-1p2RTPYSDSRi0xRviKjjilqrWU5Vr',
                    'object': 'chat.completion',
                    'created': 1677649420,
                    'model': 'gpt-3.5-turbo',
                    'usage': {'prompt_tokens': 56, 'completion_tokens': 31, 'total_tokens': 87},
                    'choices': [
                        {
                            'message': {
                                'role': 'assistant',
                                'content': 'The 2020 World Series was played in Arlington, Texas at the Globe Life Field, which was the new home stadium for the Texas Rangers.'
                            },
                            'finish_reason': 'stop',
                            'index': 0
                        }
                    ]
                }
            */

            $json = json_decode($api_result);
            $this->log['response'] = $json;
            $this->log['response.codes'] = $api->responseCodes;

            $this->log_flush();

            if (
                empty($json->object) ||
                empty($json->choices) ||
                $json->object != 'chat.completion' ||
                !in_array(200, $api->responseCodes)
            ) {
            } else {
                $this->job['status'] = 'ok';
                $api_response = $json->choices[0]->message->content;
                $response = $api_response;
                $request_tokens = $json->usage->prompt_tokens;
                $response_tokens = $json->usage->completion_tokens;
                if ($history_tokens > 0 || $prefix_tokens > 0) {
                    $this->log['request.tokens.raw'] = $request_tokens;
                    $this->log['request.tokens.adjusted'] = $request_tokens - $history_tokens - $prefix_tokens;
                }
            }
        } catch (\Exception $e) {
            $this->log['exception'] = $e->getMessage();
            $this->log_flush();
        }

        $this->log['finish'] = date('Y-m-d H:i:s');

        if (!empty($posts)) {
            $viewtopic = "{$this->root_path}viewtopic.{$this->php_ext}";
            $discarded = '';
            if ($post_first_discarded != null) {
                $discarded = $this->language->lang('AILABS_POSTS_DISCARDED', $viewtopic, $post_first_discarded);
            }
            $total_posts_count = count($posts) * 2 + 2;
            $total_tokens_used_count = $request_tokens + $response_tokens;
            $info = $this->language->lang(
                'AILABS_DISCARDED_INFO',
                $viewtopic,
                $post_first_taken,
                $total_posts_count,
                $discarded,
                $total_tokens_used_count,
                $this->max_tokens
            );
        }

        $resultParse = new resultParse();
        $resultParse->message = $response;
        $resultParse->info = $info;

        $response = $this->replace_vars($this->job, $resultParse);

        $data = $this->post_response($this->job, $response);

        $this->job['response_time'] = time();
        $this->job['response_post_id'] = $data['post_id'];

        $set = [
            'status'                        => $this->job['status'],
            'attempts'                      => $this->job['attempts'] + 1,
            'response_time'                 => $this->job['response_time'],
            'response'                      => utf8_encode_ucr($api_response),
            'request_tokens'                => $request_tokens - $history_tokens - $prefix_tokens,
            'response_post_id'              => $this->job['response_post_id'],
            'response_tokens'               => $response_tokens,
            'log'                           => json_encode($this->log)
        ];

        $this->job_update($set);
        $this->post_update($this->job);

        return new JsonResponse($this->log);
    }
}
