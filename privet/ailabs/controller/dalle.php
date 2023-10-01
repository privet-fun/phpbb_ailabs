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
use privet\ailabs\includes\GenericController;
use privet\ailabs\includes\resultSubmit;
use privet\ailabs\includes\resultParse;

/*

config
{
"api_key": "<api-key>",
"url_generations": "https://api.openai.com/v1/images/generations",
"url_variations": "https://api.openai.com/v1/images/variations",
"n": 1,
"size": "1024x1024",
"response_format": "url"
}

template
[quote={poster_name} post_id={post_id} user_id={poster_id}]{request}[/quote]
{response}{attachments}

*/

class dalle extends GenericController
{
    protected function init()
    {
        $opts = parent::init();

        $opts += ['size' => $this->cfg->size];

        $count_replaced = 0;

        // User can explicitly override image size.
        // Comment out code below to disable this feature.
        foreach (['256x256', '512x512', '1024x1024'] as $known_size) {
            $count_replaced = 0;
            $this->job['request'] = trim(str_replace($known_size, '', $this->job['request'], $count_replaced));
            if ($count_replaced > 0) {
                if ($opts['size'] != $known_size) {
                    $this->log['size.adjusted'] = $known_size;
                    $opts['size'] = $known_size;
                }
            }
        }

        if ($count_replaced > 0) {
            $this->log['request.adjusted'] = $this->job['request'];
        }

        return $opts;
    }

    protected function prepare($opts)
    {
        if (filter_var($this->job['request'], FILTER_VALIDATE_URL)) {
            // https://platform.openai.com/docs/api-reference/images/create-variation
            // The image to use as the basis for the variation(s). Must be a valid PNG file, less than 4MB, and square.
            $image = curl_file_create($this->job['request'], 'image/png');
            $opts += [
                'image'             => $image,
                'n'                 => $this->cfg->n,
                'response_format'   => $this->cfg->response_format,
            ];
        } else {
            $opts += [
                'prompt'            => trim($this->job['request']),
                'n'                 => $this->cfg->n,
                'response_format'   => $this->cfg->response_format,
            ];
        }

        return $opts;
    }

    protected function submit($opts): resultSubmit
    {
        $api = new GenericCurl($this->cfg->api_key);
        $this->cfg->api_key = null;

        $result = new resultSubmit();

        if (empty($opts['image'])) {
            // https://api.openai.com/v1/images/generations
            $result->response = $api->sendRequest($this->cfg->url_generations, 'POST', $opts);
        } else {
            // https://api.openai.com/v1/images/variations
            $result->response = $api->sendRequest($this->cfg->url_variations, 'POST', $opts);
        }

        $result->responseCodes = $api->responseCodes;

        return $result;
    }

    protected function parse(resultSubmit $resultSubmit): resultParse
    {
        /*
        Response example for response_format="url":
        {
            "created": 1589478378,
            "data": [
                {
                "url": "https://..."
                },
                {
                "url": "https://..."
                }
            ]
        }

        Response example for response_format="b64_json":
        {
            "created": 1589478378,
            "data": [
                {
                    "b64_json": "..."
                },
                {
                    "b64_json": "..."
                }
            ]
        }
        */

        $json = json_decode($resultSubmit->response);
        $images = null;
        $message = null;

        if (
            empty($json->data) ||
            !empty($json->error) ||
            !in_array(200, $resultSubmit->responseCodes)
        ) {
            if (!empty($json->error)) {
                $message = $json->error->message;
            }
        } else {
            $this->job['status'] = 'ok';
            $images = [];
            $ind = 0;
            foreach ($json->data as $item) {
                // Image name returned back by Open AI API in url is not always can be parsed by internal phpBB routines.
                // Use b64_json instead
                if ($this->cfg->response_format == 'url') {
                    array_push($images, $item->url);
                } else {
                    $filename = $this->save_base64_to_temp_file($item->b64_json, $ind);
                    $item->b64_json = '<redacted>';
                    array_push($images, $filename);
                }
                $ind++;
            }
        }

        $result = new resultParse();
        $result->json = $json;
        $result->images = $images;
        $result->message = $message;

        return $result;
    }
}
