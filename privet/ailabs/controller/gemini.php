<?php

/**
 *
 * AI Labs extension
 *
 * @copyright (c) 2024, privet.fun, https://privet.fun
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace privet\ailabs\controller;

use privet\ailabs\includes\GenericCurl;
use Symfony\Component\HttpFoundation\JsonResponse;
use privet\ailabs\includes\AIController;
use privet\ailabs\includes\resultParse;

/*

https://ai.google.dev/tutorials/rest_quickstart

config (example)

{
    "url_generateContent": "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.0-pro-latest:generateContent?key=<API_KEY>",
    "url_countTokens": "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.0-pro-latest:countTokens?key=<API_KEY>",
 	"max_tokens": 30720,
	"message_tokens": 2048,
	"max_quote_length": 10,
    "prefix": "Answer all my questions pretending you are a pirate.",
    "safety_settings": [
        {
            "category": "HARM_CATEGORY_SEXUALLY_EXPLICIT",
            "threshold": "BLOCK_NONE"
        },
        {
            "category": "HARM_CATEGORY_HATE_SPEECH",
            "threshold": "BLOCK_NONE"
        },
        {
            "category": "HARM_CATEGORY_HARASSMENT",
            "threshold": "BLOCK_NONE"
        },
        {
            "category": "HARM_CATEGORY_DANGEROUS_CONTENT",
            "threshold": "BLOCK_NONE"
        }
    ],
    "generation_config": {
        "temperature": 0.3,
        "topK": 40,
        "topP": 0.95,
        "candidateCount": 1,
        "maxOutputTokens": 30720
    }
}

template

{info}[quote={poster_name} post_id={post_id} user_id={poster_id}]{request}[/quote]{response}

*/

class gemini extends AIController
{
    /* 
    https://generativelanguage.googleapis.com/v1beta/models?key={{api_key}}
    {
        "name": "models/gemini-1.0-pro-latest",
        "version": "001",
        "displayName": "Gemini 1.0 Pro Latest",
        "description": "The best model for scaling across a wide range of tasks. This is the latest model.",
        "inputTokenLimit": 30720,
        "outputTokenLimit": 2048,
        "supportedGenerationMethods": [
            "generateContent",
            "countTokens"
        ],
        "temperature": 0.9,
        "topP": 1,
        "topK": 1
    }
    */
    protected $max_tokens = 30720;
    protected $message_tokens = 2048;
    protected $settings;

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

        $api = new GenericCurl();

        $this->job['status'] = 'fail';
        $response = $this->language->lang('AILABS_ERROR_CHECK_LOGS');
        $api_response = null;
        $request_tokens = null;
        $response_tokens = null;

        $contents = [];
        $info = null;
        $posts = [];
        $post_first_taken = null;
        $post_first_discarded = null;

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
                        'running_total_tokens'  => $history_tokens + $count_tokens,
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
                        $contents,
                        ['role' => 'user', 'parts' => [['text' => trim($history_decoded_request)]]],
                        ['role' => 'model', 'parts' => [['text' => trim($history_decoded_response)]]]
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

        $request_text = trim($this->job['request']);

        // Extract settings provided by user
        $configuration = (array) json_decode(json_encode($this->cfg->generation_config));

        if ($this->extract_numeric_settings($request_text, ['temperature' => 'temperature', "topk" => "topK", "topp" => "topP"], $configuration, $this->settings))
            $this->log['generation_config_override'] = $this->settings;

        $request_tokens = $this->countTokens($request_text, 'request.request_tokens');

        $contents[] =  ['role' => 'user', 'parts' => [['text' => $request_text]]];

        if (!empty($this->cfg->prefix))
            array_unshift($contents[0]['parts'], ['text' => $this->cfg->prefix]);

        /*
            https://ai.google.dev/tutorials/rest_quickstart
            {
                "contents": [
                    {
                        "role": "user",
                        "parts": [
                            {
                                "text": "You are a pirate. Talk like one. Answer all my questions."
                            },
                            {
                                "text": "Tell me a joke."
                            }
                        ]
                    },
                    {
                        "role": "model",
                        "parts": [
                            {
                                "text": "Pirate joke goes here."
                            }
                        ]
                    },
                    {
                        "role": "user",
                        "parts": [
                            {
                                "text": "Tell me more jokes"
                            }
                        ]
                    }
                ],
                "safety_settings": [
                    {
                        "category": "HARM_CATEGORY_SEXUALLY_EXPLICIT",
                        "threshold": "BLOCK_NONE"
                    },
                    {
                        "category": "HARM_CATEGORY_HATE_SPEECH",
                        "threshold": "BLOCK_NONE"
                    },
                    {
                        "category": "HARM_CATEGORY_HARASSMENT",
                        "threshold": "BLOCK_NONE"
                    },
                    {
                        "category": "HARM_CATEGORY_DANGEROUS_CONTENT",
                        "threshold": "BLOCK_NONE"
                    }
                ],
                "generation_config": {
                    "temperature": 0.5,
                    "topK": 32,
                    "topP": 0.8,
                    "candidateCount": 1,
                    "maxOutputTokens": 2048
                }
            }
        */

        $request_json =  [
            'contents'          => $contents,
            'safety_settings'   => $this->cfg->safety_settings,
            'generation_config' => $configuration
        ];

        $this->log['request.json'] = $request_json;
        $this->log_flush();

        try {
            // https://ai.google.dev/tutorials/rest_quickstart
            $api_result = $api->sendRequest($this->cfg->url_generateContent, 'POST', $request_json);

            /*
                Response example:
                {
                    "candidates": [
                        {
                            "content": {
                                "parts": [
                                    {
                                        "text": "What do you call a boomerang that doesn't come back?\n\nA stick."
                                    }
                                ],
                                "role": "model"
                            },
                            "finishReason": "STOP",
                            "index": 0,
                            "safetyRatings": [
                                {
                                    "category": "HARM_CATEGORY_SEXUALLY_EXPLICIT",
                                    "probability": "NEGLIGIBLE"
                                },
                                {
                                    "category": "HARM_CATEGORY_HATE_SPEECH",
                                    "probability": "NEGLIGIBLE"
                                },
                                {
                                    "category": "HARM_CATEGORY_HARASSMENT",
                                    "probability": "NEGLIGIBLE"
                                },
                                {
                                    "category": "HARM_CATEGORY_DANGEROUS_CONTENT",
                                    "probability": "NEGLIGIBLE"
                                }
                            ]
                        }
                    ],
                    "promptFeedback": {
                        "safetyRatings": [
                            {
                                "category": "HARM_CATEGORY_SEXUALLY_EXPLICIT",
                                "probability": "NEGLIGIBLE"
                            },
                            {
                                "category": "HARM_CATEGORY_HATE_SPEECH",
                                "probability": "NEGLIGIBLE"
                            },
                            {
                                "category": "HARM_CATEGORY_HARASSMENT",
                                "probability": "NEGLIGIBLE"
                            },
                            {
                                "category": "HARM_CATEGORY_DANGEROUS_CONTENT",
                                "probability": "NEGLIGIBLE"
                            }
                        ]
                    }
                }
            */

            $json = json_decode($api_result);
            $this->log['response'] = $json;
            $this->log['response.codes'] = $api->responseCodes;

            $this->log_flush();

            if (
                !in_array(200, $api->responseCodes) ||
                empty($json->candidates) ||
                empty($json->candidates[0]->content) ||
                empty($json->candidates[0]->content->parts) ||
                empty($json->candidates[0]->content->parts[0]->text)
            ) {
                if (!empty($json->candidates) && !empty($json->candidates[0]->finishReason))
                    $response = '[color=#FF0000]Gemini  Finish Reason: ' . $json->candidates[0]->finishReason . '[/color]';
            } else {
                $this->job['status'] = 'ok';
                $api_response = $json->candidates[0]->content->parts[0]->text;
                $response = $api_response;
                $response_tokens = $this->countTokens($response, 'response.response_tokens');
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
        $resultParse->settings = empty($this->settings) ? $this->settings : $this->language->lang('AILABS_SETTINGS_OVERRIDE', $this->settings);

        $response = $this->replace_vars($this->job, $resultParse);

        $data = $this->post_response($this->job, $response);

        $this->job['response_time'] = time();
        $this->job['response_post_id'] = $data['post_id'];

        $set = [
            'status'                        => $this->job['status'],
            'attempts'                      => $this->job['attempts'] + 1,
            'response_time'                 => $this->job['response_time'],
            'response'                      => utf8_encode_ucr($api_response),
            'request_tokens'                => $request_tokens,
            'response_post_id'              => $this->job['response_post_id'],
            'response_tokens'               => $response_tokens,
            'log'                           => json_encode($this->log)
        ];

        $this->job_update($set);
        $this->post_update($this->job);

        return new JsonResponse($this->log);
    }

    protected function countTokens($text, $info)
    {
        $curl = new GenericCurl();
        try {
            /*
                https://ai.google.dev/tutorials/rest_quickstart
                {
                    "contents": [
                        {
                            "parts": [
                                {
                                    "text": "Write a story about a magic backpack."
                                }
                            ]
                        }
                    ]
                }
            */
            $result = $curl->sendRequest($this->cfg->url_countTokens, 'POST', [
                'contents' => ['parts' => ['text' => $text]]
            ]);

            /*
                Response example:
                {
                    "totalTokens": 8
                }
            */
            $json = json_decode($result);
            $this->log[$info] = $json;
            $this->log[$info . '.codes'] = $curl->responseCodes;

            $this->log_flush();

            if (!empty($json->totalTokens) && in_array(200, $curl->responseCodes))
                return $json->totalTokens;
        } catch (\Exception $e) {
            $this->log['exception'] = $e->getMessage();
            $this->log_flush();
        }

        return 0;
    }
}
