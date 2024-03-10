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
use privet\ailabs\includes\GenericController;
use privet\ailabs\includes\resultSubmit;
use privet\ailabs\includes\resultParse;

/*

https://ai.google.dev/tutorials/rest_quickstart

config (example)

{
    "url_generateContent": "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro-vision:generateContent?key=<API_KEY>",
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
    }
}

template

{info}[quote={poster_name} post_id={post_id} user_id={poster_id}]{request}[/quote]{response}

*/

class gemini_vision extends GenericController
{
    protected $settings;

    protected function prepare($opts)
    {
        // Remove all BBCodes
        $text = $this->job['request'];
        $text = preg_replace('/\[(.*?)=?.*?\](.*?)\[\/\\1\]/i', '$2', $text);

        // Check for attachments first
        $fileContent = $this->load_first_attachment($this->job['post_id']);

        // If none found attempt to find URLs in the post body
        if ($fileContent == false) {
            $url_pattern = '/\bhttps?:\/\/[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|\/))/i';

            preg_match($url_pattern, $text, $urls);

            if (isset($urls[0])) {
                $text = str_replace($urls[0], '', $text);

                $this->log['url'] = $urls[0];
                $this->log['board_url'] = generate_board_url();

                $context = null;

                // If link is pointing to board download URL attempt to pass user's cookie 
                if (stripos($urls[0], generate_board_url()) === 0) {
                    $cookie_name = $this->config['cookie_name'];
                    $headers = [];

                    $copy_headers = ['X-Forwarded-For', 'User-Agent'];
                    foreach ($copy_headers as $header_name) {
                        if (!empty($this->request->header($header_name))) {
                            array_push($headers, "$header_name: " . $this->request->header($header_name));
                        }
                    }

                    $cookies = [];

                    if ($this->request->is_set($cookie_name . '_sid', \phpbb\request\request_interface::COOKIE) || $this->request->is_set($cookie_name . '_u', \phpbb\request\request_interface::COOKIE)) {
                        array_push($cookies, $cookie_name . '_u=' . $this->request->variable($cookie_name . '_u', 0, false, \phpbb\request\request_interface::COOKIE));
                        array_push($cookies, $cookie_name . '_k=' . $this->request->variable($cookie_name . '_k', '', false, \phpbb\request\request_interface::COOKIE));
                        array_push($cookies, $cookie_name . '_sid=' . $this->request->variable($cookie_name . '_sid', '', false, \phpbb\request\request_interface::COOKIE));

                        array_push($headers, "Cookie: " . implode('; ', $cookies));

                        $this->log['cookie'] = $cookie_name;
                    }

                    if (!empty($headers)) {
                        $context = stream_context_create(
                            array(
                                'http' => array(
                                    'method' => "GET",
                                    'header' => implode("\r\n", $headers)
                                )
                            )
                        );
                    }
                }

                $fileContent = file_get_contents($urls[0], false, $context);

                if ($fileContent == false)
                    $opts = ['error_message' => $this->language->lang('AILABS_ERROR_UNABLE_DOWNLOAD_URL') . $urls[0]];
            } else {
                $opts = ['error_message' => $this->language->lang('AILABS_ERROR_PROVIDE_URL')];
            }
        }

        if ($fileContent !== false) {
            /*
                {
                    "contents":[
                        {
                            "parts":[
                                {"text": "What is this picture?"},
                                {
                                "inline_data": {
                                    "mime_type":"image/jpeg",
                                    "data": "base_64 encoded image"
                                }
                            ]
                        }
                    ]
                }
                */

            // Extract settings provided by user
            $configuration = (array) json_decode(json_encode($this->cfg->generation_config));

            if ($this->extract_numeric_settings($text, ['temperature' => 'temperature', "topk" => "topK", "topp" => "topP"], $configuration, $info)) {
                $this->log['generation_config_override'] = $info;
                $this->settings = $info;
            }

            $this->log['text'] = $text;

            $opts = [
                'contents' =>
                [[
                    'parts' => [
                        ['text' => $text],
                        [
                            'inline_data' => [
                                'mime_type' => 'image/jpeg',
                                'data' => base64_encode($fileContent)
                            ]
                        ]
                    ]
                ]],
                'safety_settings'   => $this->cfg->safety_settings,
                'generation_config' => $configuration
            ];

            // Prevent from saving contents to logs
            $this->redactOpts = ['contents'];
        }

        return $opts;
    }

    protected function submit($opts): resultSubmit
    {
        $result = new resultSubmit();

        if (!empty($opts['contents'])) {
            $api = new GenericCurl();

            // https://ai.google.dev/tutorials/rest_quickstart
            $result->response =  $api->sendRequest($this->cfg->url_generateContent, 'POST', $opts);

            $result->responseCodes = $api->responseCodes;
        } else {
            $result->response = json_encode($opts);
        }

        return $result;
    }

    protected function parse(resultSubmit $resultSubmit): resultParse
    {
        /*
        {
            "candidates": [
                {
                "content": {
                    "parts": [
                        {
                            "text": "Gemini Vision reply goes here"
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
                    "probability": "LOW"
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

        $this->job['status'] = 'fail';
        $message = $this->language->lang('AILABS_ERROR_CHECK_LOGS');

        $json = json_decode($resultSubmit->response);

        if (
            empty($json) ||
            !empty($json->error_message) ||
            !in_array(200, $resultSubmit->responseCodes)
        ) {
            if (!empty($json->error_message)) {
                $message = '[color=#FF0000]' . $json->error_message . '[/color]';
            }
        } else {
            if (
                empty($json->candidates) ||
                empty($json->candidates[0]->content) ||
                empty($json->candidates[0]->content->parts) ||
                empty($json->candidates[0]->content->parts[0]->text)
            ) {
                if (!empty($json->candidates) && !empty($json->candidates[0]->finishReason))
                    $message = '[color=#FF0000]Gemini Finish Reason: ' . $json->candidates[0]->finishReason . '[/color]';
                if (!empty($json->promptFeedback) && !empty($json->promptFeedback->blockReason))
                    $message = '[color=#FF0000]Gemini Block Reason: ' . $json->promptFeedback->blockReason . '[/color]';
            } else {
                $this->job['status'] = 'ok';
                $message = $json->candidates[0]->content->parts[0]->text;
            }
        }

        $result = new resultParse();
        $result->json = $json;
        $result->message = $message;
        $result->settings = empty($this->settings) ? $this->settings : $this->language->lang('AILABS_SETTINGS_OVERRIDE', $this->settings);

        return $result;
    }
}
