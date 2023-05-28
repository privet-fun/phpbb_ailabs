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
"url_texttoimage": "https://api.stability.ai/v1/generation/stable-diffusion-xl-beta-v2-2-2/text-to-image",
"cfg_scale": 7.5,
"clip_guidance_preset": "FAST_BLUE",
"height": 512,
"width": 512,
"samples": 1,
"steps": 30
}

template
[quote={poster_name} post_id={post_id} user_id={poster_id}]{request}[/quote]
{response}{attachments}

*/

class stablediffusion extends GenericController
{

    protected function prepare($opts)
    {
        return [
            'text_prompts'          => [
                ['text'  => trim($this->job['request'])],
            ],
            'cfg_scale'             => $this->cfg->cfg_scale,
            'clip_guidance_preset'  => $this->cfg->clip_guidance_preset,
            'height'                => $this->cfg->height,
            'width'                 => $this->cfg->width,
            'samples'               => $this->cfg->samples,
            'steps'                 => $this->cfg->steps,
        ];
    }

    protected function submit($opts): resultSubmit
    {
        $api = new GenericCurl($this->cfg->api_key);
        $this->cfg->api_key = null;

        $result = new resultSubmit();
        // https://api.stability.ai/docs#tag/v1generation/operation/textToImage
        // https://api.stability.ai/v1/generation/stable-diffusion-xl-beta-v2-2-2/text-to-image
        $result->response = $api->sendRequest($this->cfg->url_texttoimage, 'POST', $opts);
        $result->responseCodes = $api->responseCodes;
        return $result;
    }

    protected function parse(resultSubmit $resultSubmit): resultParse
    {
        /*
        Response headers:
            Content-Type required string
                Enum: "application/json" | "image/png"
                Finish-Reason string (FinishReason)
                Enum: "SUCCESS" | "ERROR" | "CONTENT_FILTERED"
                The result of the generation process.

                SUCCESS indicates success
                ERROR indicates an error
                CONTENT_FILTERED indicates the result affected by the content filter and may be blurred.
            This header is only present when the Accept is set to image/png. Otherwise it is returned in the response body.

            Seed integer
            Example: 3817857576
            The seed used to generate the image. This header is only present when the Accept is set to image/png. Otherwise it is returned in the response body.

        Response HTTP 200:
            {
                "artifacts":[
                    {
                        "base64":"<encoded>",
                        "seed":4188843142,
                        "finishReason":"SUCCESS"
                    }
                ]
            }

        Response HTTP 400, 401, 404, 500:
            {
                "id": "A unique identifier for this particular occurrence of the problem.",
                "name": "The short-name of this class of errors e.g. bad_request.",
                "message": "A human-readable explanation specific to this occurrence of the problem."                       
            }
        */

        $json = json_decode($resultSubmit->response);
        $images = null;
        $message = null;

        if (
            empty($json->artifacts) ||
            !empty($json->name) ||
            !empty($json->message) ||
            !in_array(200, $resultSubmit->responseCodes)
        ) {
            if (!empty($json->message)) {
                $message = $json->message;
            }
        } else {
            $this->job['status'] = 'ok';

            $images = [];

            $ind = 0;
            foreach ($json->artifacts as $item) {
                if ($item->finishReason !== 'SUCCESS') {
                    $message = $item->finishReason;
                    if (!empty($item->base64))
                        $item->base64 = '<reducted>';
                } else {
                    $filename = $this->save_base64_to_temp_file($item->base64, $ind);
                    array_push($images, $filename);
                    $item->base64 = '<reducted>';
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
