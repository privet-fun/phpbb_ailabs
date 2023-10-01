<?php

/**
 *
 * AI Labs extension
 *
 * @copyright (c) 2023, privet.fun, https://privet.fun
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace privet\ailabs\includes;

use Symfony\Component\HttpFoundation\JsonResponse;
use privet\ailabs\includes\AIController;
use privet\ailabs\includes\resultParse;
use privet\ailabs\includes\resultSubmit;

class GenericController extends AIController
{
    public $redactOpts = [];

    // Cleansing and setting back $this->job['request']
    // Return $opts or empty array
    protected function init()
    {
        $this->job['status'] = 'exec';

        $set = [
            'status'    => $this->job['status'],
            'log'       => json_encode($this->log)
        ];

        $this->job_update($set);
        $this->post_update($this->job);

        $total_replaced = 0;
        $original_request = $this->job['request'];
        $this->job['request'] = str_replace('@' . $this->job['ailabs_username'], '', $this->job['request'], $total_replaced);

        if ($total_replaced > 0) {
            $this->job['request'] = str_replace('  ', ' ', $this->job['request']);
            $this->log['request.original'] = $original_request;
            $this->log['request.adjusted'] = $this->job['request'];
        }

        return [];
    }

    protected function prepare($opts)
    {
        return $opts;
    }

    protected function submit($opts): resultSubmit
    {
        $result = new resultSubmit();
        $result->response = '';
        $result->responseCodes = [];
        return $result;
    }

    // Override this method to extract response image(s)/message(s) and set job status
    protected function parse(resultSubmit $resultSubmit): resultParse
    {
        $result = new resultParse();
        $result->json = json_decode($resultSubmit->response);
        return $result;
    }

    protected function process()
    {
        $opts = $this->init();
        $this->log_flush();

        $opts = $this->prepare($opts);

        $optsCloned = json_decode(json_encode($opts), true);

        foreach ($this->redactOpts as $key) {
            unset($optsCloned[$key]);
        }

        $this->log['request.json'] = $optsCloned;
        $this->log_flush();

        $this->job['status'] = 'fail';

        $resultSubmit = null;
        $resultParse = null;

        try {
            $resultSubmit = $this->submit($opts);

            if ($resultSubmit->ignore)
                return new JsonResponse('waiting for callback');

            $this->log['response.length'] = strlen($resultSubmit->response);
            $this->log['response.codes'] = $resultSubmit->responseCodes;
            $this->log_flush();

            $resultParse = $this->parse($resultSubmit);

            $this->log['response.json'] = $resultParse->json;
            $this->log_flush();
        } catch (\Exception $e) {
            $this->log['exception'] = $e->getMessage();
            $this->log_flush();

            $this->log['response.raw'] = $resultSubmit->response;
            $this->log_flush();
        }

        $this->log['finish'] = date('Y-m-d H:i:s');

        $response = $this->replace_vars($this->job, $resultParse);

        $data = $this->post_response($this->job, $response);

        $this->job['response_time'] = time();
        $this->job['response_post_id'] = $data['post_id'];

        if (!empty($resultParse->images)) {
            $this->log['attachments_start_time'] = date('Y-m-d H:i:s');
            $ind = 0;
            foreach ($resultParse->images as $url_or_filename) {
                $is_url = filter_var($url_or_filename, FILTER_VALIDATE_URL) !== false;
                $attachemnt_name = $is_url ? $this->image_filename($ind) : basename($url_or_filename);
                $attachment = $this->attach_to_post(
                    $is_url,
                    $url_or_filename,
                    +$data['post_id'],
                    +$this->job['topic_id'],
                    +$this->job['forum_id'],
                    +$this->job['ailabs_user_id'],
                    $attachemnt_name
                );

                // If you getting error REMOTE_UPLOAD_TIMEOUT try to adjust php.ini as described at https://stackoverflow.com/questions/52069439/upload-large-files-and-time-out-php-uploads
                // Edit /etc/php/8.2/fpm/php.ini, eg
                //      max_execution_time = 120
                //      max_input_time = 120
                //      upload_max_filesize = 10M

                $this->log['attachment_' . $attachemnt_name] = [
                    'is_url'            => $is_url,
                    'url_or_filename'   => $url_or_filename,
                    'result'            => $attachment
                ];

                $ind++;
            }
            $this->log['attachments_finish_time'] = date('Y-m-d H:i:s');
        }

        $set = [
            'status'            => $this->job['status'],
            'attempts'          => $this->job['attempts'] + 1,
            'response_time'     => $this->job['response_time'],
            'response'          => empty($resultParse->images) ? $resultParse->message : implode(PHP_EOL, $resultParse->images),
            'response_post_id'  => $this->job['response_post_id'],
            'log'               => json_encode($this->log)
        ];

        $this->job_update($set);
        $this->post_update($this->job);

        return new JsonResponse($this->log);
    }
}
