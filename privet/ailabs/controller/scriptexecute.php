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

use privet\ailabs\includes\GenericController;
use privet\ailabs\includes\resultSubmit;
use privet\ailabs\includes\resultParse;

/*

config
{
 "config": {
    "script": "<script name to execute>",
    "logs": "<folder where job config and responses go>"
 }
}

template
[quote={poster_name} post_id={post_id} user_id={poster_id}]{request}[/quote]
{response}{attachments}

*/

class scriptexecute extends GenericController
{
    protected function submit($opts): resultSubmit
    {
        $opts = (array)(clone $this->cfg);
        $opts['prompt'] = trim($this->job['request']);

        if (array_key_exists('model', $opts) && property_exists($opts['model'], 'prompt') && empty($opts['model']->prompt))
            $opts['model']->prompt = $opts['prompt'];

        if (array_key_exists('config', $opts) && property_exists($opts['config'], 'prompt') && empty($opts['config']->prompt))
            $opts['config']->prompt = $opts['prompt'];

        $result = new resultSubmit();

        $fileConfig = $this->cfg->config->logs . '/' . $this->job_id . '.json';
        $fileLog = $this->cfg->config->logs . '/' . $this->job_id . '.json.log';
        $fileResponse = $this->cfg->config->logs . '/' . $this->job_id . '.json.response';
        $execute = $this->cfg->config->script . ' ' . $this->job_id . ' ' . $fileConfig . ' > ' . $fileLog;

        $jsonConfig = json_encode($opts, JSON_PRETTY_PRINT);

        $result_put = file_put_contents($fileConfig, $jsonConfig);

        if (empty($result_put)) {
            $result->responseCodes[] = $result_put;
            $result->response = '{ "error" : "Unable to save .json config" }';
        } else {
            $result_code = null;
            try {
                // Make sure that exe is enabled in /etc/php/8.2/fpm/pool.d/phpbb_pool.conf file 
                // See line php_admin_value[disable_functions] = 
                unset($output);
                // Code removed, see 1.0.5
            } catch (\Exception $e) {
                $result->response = '{ "error" : "' . $e . '" }';
            }

            $result->responseCodes[] = $result_code;
            $result->response = file_get_contents($fileResponse);

            if ($result_code == 0) {
                unlink($fileLog);
                unlink($fileResponse);
                unlink($fileConfig);
            }
        }

        return $result;
    }

    protected function parse(resultSubmit $resultSubmit): resultParse
    {
        /*
        Response 
        {
            images: ['','',''],
            agentName: '0'..'j',
            subscriptionTokens: tokensLeft,
            error: null | 'error message'
        }
        */

        $json = empty($resultSubmit->response) ? false : json_decode($resultSubmit->response);
        $images = [];
        $message = null;

        if (
            empty($json) ||
            empty($json->images) ||
            !empty($json->error)
        ) {
            if (!empty($json->error)) {
                $message = $json->error;
            }
        } else {
            $this->job['status'] = 'ok';
            $images = [];
            foreach ($json->images as $item) {
                array_push($images, $item);
            }
            $json->images = $images;
        }

        $result = new resultParse();
        $result->json = $json;
        $result->images = $images;
        $result->message = $message;

        return $result;
    }
}
