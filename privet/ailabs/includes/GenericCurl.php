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

class GenericCurl
{
    private $headers;
    private $contentTypes;
    private int $timeout = 0;
    private object $stream_method;
    private string $proxy = "";
    private $curlInfo = [];
    public int $retryCount;
    public int $timeoutBeforeRetrySec;
    public $responseCodes = [];

    public function __construct($API_KEY, $retryCount = 3, $timeoutBeforeRetrySec = 10)
    {
        $this->contentTypes = [
            "application/json"    => "Content-Type: application/json",
            "multipart/form-data" => "Content-Type: multipart/form-data",
        ];

        $this->headers = [
            $this->contentTypes["application/json"],
            "Authorization: Bearer $API_KEY",
        ];

        $this->retryCount = $retryCount;
        $this->timeoutBeforeRetrySec = $timeoutBeforeRetrySec;
    }

    /**
     * @return array
     * Remove this method from your code before deploying
     */
    public function getCURLInfo()
    {
        return $this->curlInfo;
    }

    /**
     * @param  int  $timeout
     */
    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @param  string  $proxy
     */
    public function setProxy(string $proxy)
    {
        if ($proxy && strpos($proxy, '://') === false) {
            $proxy = 'https://' . $proxy;
        }
        $this->proxy = $proxy;
    }

    /**
     * @param  array  $header
     * @return void
     */
    public function setHeader($header)
    {
        if ($header) {
            foreach ($header as $key => $value) {
                $this->headers[$key] = $value;
            }
        }
    }

    /**
     * @param  string  $url
     * @param  string  $method
     * @param  array   $opts
     * @return bool|string
     */
    public function sendRequest(string $url, string $method, $opts = [])
    {
        $this->responseCodes = [];

        $post_fields = json_encode($opts);

        if (array_key_exists('file', $opts) || array_key_exists('image', $opts)) {
            $this->headers[0] = $this->contentTypes["multipart/form-data"];
            $post_fields      = $opts;
        } else {
            $this->headers[0] = $this->contentTypes["application/json"];
        }

        $curl_info = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $post_fields,
            CURLOPT_HTTPHEADER     => $this->headers,
        ];

        if ($opts == []) {
            unset($curl_info[CURLOPT_POSTFIELDS]);
        }

        if (!empty($this->proxy)) {
            $curl_info[CURLOPT_PROXY] = $this->proxy;
        }

        if (array_key_exists('stream', $opts) && $opts['stream']) {
            $curl_info[CURLOPT_WRITEFUNCTION] = $this->stream_method;
        }

        $curl = curl_init();

        curl_setopt_array($curl, $curl_info);
        $retryCount = 0;
        $responseCode = 0;
        $response = null;

        do {
            $retryCount++;
            $response = curl_exec($curl);
            $responseCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            array_push($this->responseCodes, $responseCode);
        } while (
            $responseCode !== 200 &&
            $retryCount < $this->retryCount &&
            sleep($this->timeoutBeforeRetrySec) !== false
        );

        $this->curlInfo = curl_getinfo($curl);

        curl_close($curl);

        return $response;
    }
}
