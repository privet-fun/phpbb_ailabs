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
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use privet\ailabs\includes\resultParse;

class AIController
{
    protected $auth;
    protected $config;
    protected $db;
    protected $helper;
    protected $language;
    protected $request;
    protected $template;
    protected $user;
    protected $phpbb_container;
    protected $php_ext;
    protected $root_path;
    protected $users_table;
    protected $jobs_table;

    protected $job_id;
    protected $start;
    protected $log;
    protected $job;
    protected $cfg;
    protected $lang;

    public function __construct(
        \phpbb\auth\auth $auth,
        \phpbb\config\config $config,
        \phpbb\db\driver\driver_interface $db,
        \phpbb\controller\helper $helper,
        \phpbb\language\language $language,
        \phpbb\request\request $request,
        \phpbb\template\template $template,
        \phpbb\user $user,
        ContainerInterface $phpbb_container,
        $php_ext,
        $root_path,
        $users_table,
        $jobs_table
    ) {
        $this->auth = $auth;
        $this->config = $config;
        $this->db = $db;
        $this->helper = $helper;
        $this->language = $language;
        $this->request = $request;
        $this->template = $template;
        $this->user = $user;
        $this->phpbb_container = $phpbb_container;
        $this->php_ext = $php_ext;
        $this->root_path = $root_path;
        $this->users_table = $users_table;
        $this->jobs_table = $jobs_table;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
     */
    public function execute()
    {
        $this->start = date('Y-m-d H:i:s');

        $this->job_id = (int) utf8_clean_string($this->request->variable('job_id', '', true));

        // https://symfony.com/doc/current/components/http_foundation.html#streaming-a-response
        $streamedResponse = new StreamedResponse();
        $streamedResponse->headers->set('X-Accel-Buffering', 'no');
        $streamedResponse->setCallback(function () {
            // To debug callback response
            echo 'Processing job ' . $this->job_id;
            flush();
        });
        $streamedResponse->send();

        if (empty($this->job_id)) {
            return new JsonResponse('job_id not provided');
        }

        $this->load_job();

        if (empty($this->job)) {
            return new JsonResponse('job_id not found in the database');
        }

        if (!empty($this->job['status'])) {
            return new JsonResponse('job_id already completed with ' . $this->job['status']);
        }

        $this->log = array('start' => $this->start);

        try {
            $this->cfg = json_decode($this->job['config']);
        } catch (\Exception $e) {
            $this->cfg = null;
            $this->log['exception'] = $e->getMessage();
        }

        if (empty($this->cfg)) {
            $this->job['status'] = 'fail';
            $this->log['error'] = 'config not provided';

            $set = [
                'status'            => $this->job['status'],
                'log'               => json_encode($this->log)
            ];

            $this->job_update($set);
            $this->post_update($this->job);

            return new JsonResponse($this->log);
        }

        return $this->process();
    }

    protected function load_job()
    {
        $where = [
            'job_id' => $this->job_id
        ];

        $sql = 'SELECT j.job_id, j.ailabs_user_id, j.status, j.attempts, j.post_mode, j.post_id, j.forum_id, j.poster_id, j.poster_name, j.request, j.response, j.log, j.ref, j.response_message_id, c.config, c.template, u.username as ailabs_username, p.topic_id, p.post_subject, p.post_text, p.post_time, f.forum_name ' .
            'FROM ' . $this->jobs_table . ' j ' .
            'JOIN ' . $this->users_table . ' c ON c.user_id = j.ailabs_user_id ' .
            'JOIN ' . USERS_TABLE . ' u ON u.user_id = j.ailabs_user_id ' .
            'JOIN ' . POSTS_TABLE . ' p ON p.post_id = j.post_id ' .
            'JOIN ' . FORUMS_TABLE . ' f ON f.forum_id = j.forum_id ' .
            'WHERE ' . $this->db->sql_build_array('SELECT', $where);
        $result = $this->db->sql_query($sql);
        $this->job = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        if (!empty($this->job) && !empty($this->job['request']))
            $this->job['request'] = utf8_decode_ncr($this->job['request']);
    }

    protected function process()
    {
        return new JsonResponse($this->log);
    }

    /**
     * Parse message template
     * @param string $template
     */
    protected function replace_vars($job, resultParse $resultParse)
    {
        $images = null;
        $attachments = null;
        if (!empty($resultParse->images)) {
            $images = [];
            $attachments = [];
            $ind = 0;
            foreach ($resultParse->images as $item) {
                array_push($images, '[img]' . $item . '[/img]' . PHP_EOL . PHP_EOL);
                array_push($attachments, '[attachment=' . $ind . '][/attachment]' . PHP_EOL);
                $ind = $ind + 1;
            }
            $images = implode("", $images);
            $attachments = implode("", $attachments);
        }

        $tokens = array(
            '{post_id}'         => $job['post_id'],
            '{request}'         => $job['request'],
            '{info}'            => $resultParse->info,
            '{response}'        => $resultParse->message,
            '{settings}'        => $resultParse->settings,
            '{images}'          => $images,
            '{attachments}'     => $attachments,
            '{poster_id}'       => $job['poster_id'],
            '{poster_name}'     => $job['poster_name'],
            '{ailabs_username}' => $job['ailabs_username'],
        );

        return str_ireplace(array_keys($tokens), array_values($tokens), $job['template']);
    }

    protected function job_update($set)
    {
        $where = ['job_id' => $this->job_id];

        $sql = 'UPDATE ' . $this->jobs_table .
            ' SET ' . $this->db->sql_build_array('UPDATE', $set) .
            ' WHERE ' . $this->db->sql_build_array('SELECT', $where);

        $result = $this->db->sql_query($sql);

        $this->db->sql_freeresult($result);
    }

    protected function log_flush()
    {
        $set = ['log' => json_encode($this->log)];

        $this->job_update($set);
    }

    protected function post_update($job)
    {
        /* 
            [
                job_id: <int>,                         
                ailabs_user_id: <int>,
                ailabs_username: <string>, 
                response_time: <int>,
                status: <string>, 
                response_post_id: <int>
            ]
        */
        $data = array(
            'job_id' => $job['job_id'],
            'ailabs_user_id' => $job['ailabs_user_id'],
            'ailabs_username' => $job['ailabs_username'],
            'status' => $job['status'],
            'response_time' => empty($job['response_time']) ? time() : $job['response_time'],
        );
        if (!empty($job['response_post_id'])) {
            $data['response_post_id'] = $job['response_post_id'];
        }
        $where = [
            'post_id' => $job['post_id']
        ];
        $set =  '\'' . json_encode($data) . ',\'';
        $concat = $this->db->sql_concatenate('post_ailabs_data', $set);
        $sql = 'UPDATE ' . POSTS_TABLE . ' SET post_ailabs_data = ' . $concat . ' WHERE ' . $this->db->sql_build_array('SELECT', $where);
        $result = $this->db->sql_query($sql);
        $this->db->sql_freeresult($result);
    }

    protected function post_response($job, $response)
    {
        // Prep posting
        $poll = $uid = $bitfield = $options = '';
        generate_text_for_storage($response, $uid, $bitfield, $options, true, true, true);

        // For some reason uid is not calculated by generate_text_for_storage 
        if (empty($uid)) {
            $message_parser = new \parse_message($response);
            $message_parser->parse(true, true, true);
            $uid = $message_parser->bbcode_uid;
        }

        $data = array(
            'poster_id'             => $job['ailabs_user_id'],
            // General Posting Settings
            'forum_id'              => $job['forum_id'], // The forum ID in which the post will be placed. (int)
            'topic_id'              => $job['topic_id'], // Post a new topic or in an existing one? Set to 0 to create a new one, if not, specify your topic ID here instead.
            'icon_id'               => false, // The Icon ID in which the post will be displayed with on the viewforum, set to false for icon_id. (int)
            // Defining Post Options
            'enable_bbcode'         => true, // Enable BBcode in this post. (bool)
            'enable_smilies'        => true, // Enabe smilies in this post. (bool)
            'enable_urls'           => true, // Enable self-parsing URL links in this post. (bool)
            'enable_sig'            => true, // Enable the signature of the poster to be displayed in the post. (bool)
            // Message Body
            'message'               => $response, // Your text you wish to have submitted. It should pass through generate_text_for_storage() before this. (string)
            'message_md5'           => md5($response), // The md5 hash of your message
            'post_checksum'         => md5($response), // The md5 hash of your message
            // Values from generate_text_for_storage()
            'bbcode_bitfield'       => $bitfield, // Value created from the generate_text_for_storage() function.
            'bbcode_uid'            => $uid, // Value created from the generate_text_for_storage() function.    
            // Other Options
            'post_edit_locked'      => 0, // Disallow post editing? 1 = Yes, 0 = No
            'topic_title'           => $job['post_subject'],
            'notify_set'            => true, // (bool)
            'notify'                => true, // (bool)
            'post_time'             => 0, // Set a specific time, use 0 to let submit_post() take care of getting the proper time (int)
            'forum_name'            => $job['forum_name'], // For identifying the name of the forum in a notification email. (string)    // Indexing
            'enable_indexing'       => true, // Allow indexing the post? (bool)    // 3.0.6
        );

        // Post as designated user and then switch back to original one
        $actual_user_id = $this->user->data['user_id'];
        $this->switch_user($job['ailabs_user_id']);
        $post_subject = ((strpos($job['post_subject'], 'Re: ') !== 0) ? 'Re: ' : '') . censor_text($job['post_subject']);

        include($this->root_path . 'includes/functions_posting.' . $this->php_ext);
        submit_post('reply', $post_subject, $job['ailabs_username'], POST_NORMAL, $poll, $data);

        $this->switch_user($actual_user_id);

        return $data;
    }

    /**
     * Switch to the AI Labs user
     * @param int $new_user_id
     * @return bool
     */
    protected function switch_user($new_user_id)
    {
        if ($this->user->data['user_id'] == $new_user_id) {
            // Nothing to do
            return true;
        }

        $sql = 'SELECT * FROM ' . USERS_TABLE . ' WHERE user_id = ' . (int) $new_user_id;
        $result = $this->db->sql_query($sql);
        $row = $this->db->sql_fetchrow($result);
        $this->db->sql_freeresult($result);

        $row['is_registered'] = true;
        $this->user->data = array_merge($this->user->data, $row);
        $this->auth->acl($this->user->data);

        return true;
    }

    /*
        Inspired by https://www.phpbb.com/community/viewtopic.php?t=2556226
    */
    protected function attach_to_post($isUrl, $urlOrFilename, $postId, $topicId = 0, $forumId = 0, $userId = 0, $realFileName = '', $comment = '', $dispatchEvent = true)
    {
        if (
            getType($isUrl) != 'boolean' ||
            getType($urlOrFilename) != 'string' ||
            getType($postId) != 'integer' ||
            getType($comment) != 'string' ||
            getType($dispatchEvent) != 'boolean' ||
            getType($topicId) != 'integer' ||
            getType($forumId) != 'integer' ||
            getType($userId) != 'integer'
        )
            throw 'Type Missmatch';

        if ($postId <= 0)
            throw 'Post ID cannot be zero!';

        // if not given, get missing IDs
        if ($topicId == 0 || $forumId == 0 || $userId == 0) {
            $idRow = $this->db->sql_fetchrow($this->db->sql_query('SELECT post_id, topic_id, forum_id, poster_id FROM ' . POSTS_TABLE . ' WHERE post_id = ' . $postId));
            if (!$idRow)
                return ['POST_NOT_EXISTS'];
            $topicId = intval($idRow['topic_id']);
            $forumId = intval($idRow['topic_id']);
            $userId = intval($idRow['poster_id']);
        }

        // get required classes
        $upload = $this->phpbb_container->get('files.upload');
        $cache = $this->phpbb_container->get('cache');
        $attach = $this->phpbb_container->get('attachment.upload');

        // load file from remote location to local server
        $upload->set_disallowed_content([]);
        $extensions = $cache->obtain_attach_extensions($forumId);
        $extensionArr = array_keys($extensions['_allowed_']);
        $upload->set_allowed_extensions($extensionArr);

        $tempFile = null;
        if ($isUrl) {
            $upload->set_allowed_extensions($extensionArr);
            $tempFile = $upload->handle_upload('files.types.remote', $urlOrFilename);
        } else {
            // TODO: There seems to be some kind of bug where phpBB unable to get extension for local file
            array_push($extensionArr, '');
            $upload->set_allowed_extensions($extensionArr);
            $local_filedata = array();
            $tempFile = $upload->handle_upload('files.types.local', $urlOrFilename, $local_filedata);
        }

        if (count($tempFile->error) > 0) {
            $ext = '.';
            if (strrpos($urlOrFilename, '.') !== false)
                $ext = substr($urlOrFilename, strrpos($urlOrFilename, '.') + 1);
            if ($tempFile->error[0] == 'URL_INVALID' && !in_array($ext, $extensionArr))
                return ['FILE_EXTENSION_NOT_ALLOWED', 'EXTENSION=.' . htmlspecialchars($ext)];
            return $tempFile->error;
        }

        $realFileNameExt = $isUrl ? '.' . $tempFile->get('extension') : '';

        $realFileName = $realFileName == '' ? $tempFile->get('realname') : htmlspecialchars($realFileName) . $realFileNameExt;

        $tempFileData = [
            'realname' => $realFileName,
            'size' => $tempFile->get('filesize'),
            'type' => $tempFile->get('mimetype'),
        ];

        // create attachment from temp file
        if (!function_exists('create_thumbnail'))
            require($this->root_path . 'includes/functions_posting.php');

        $attachFileName = $isUrl ? $tempFile->get('filename') : $urlOrFilename;

        $attachmentFileData = $attach->upload('', $forumId, true, $attachFileName, false, $tempFileData);

        if (!$attachmentFileData['post_attach'])
            return ['FILE_ATTACH_ERROR', $realFileName, $attachFileName, $tempFileData];

        if (count($attachmentFileData['error']) > 0)
            return $attachmentFileData['error'];

        $sql_ary = array(
            'physical_filename'     => $attachmentFileData['physical_filename'],
            'attach_comment'        => $comment,
            'real_filename'         => $attachmentFileData['real_filename'],
            'extension'             => $attachmentFileData['extension'],
            'mimetype'              => $attachmentFileData['mimetype'],
            'filesize'              => $attachmentFileData['filesize'],
            'filetime'              => $attachmentFileData['filetime'],
            'thumbnail'             => $attachmentFileData['thumbnail'],
            'is_orphan'             => 0,
            'in_message'            => 0,
            'poster_id'             => $userId,
            'post_msg_id'           => $postId,
            'topic_id'              => $topicId,
        );

        $this->db->sql_query('INSERT INTO ' . ATTACHMENTS_TABLE . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
        $newAttachmentID = intval($this->db->sql_nextid());

        if ($newAttachmentID == 0)
            return ['SQL_ATTACHMENT_INSERT_ERROR'];

        $this->db->sql_query('UPDATE ' . POSTS_TABLE . ' SET post_attachment = 1 WHERE post_id = ' . $postId);

        return ['SUCCESS', $newAttachmentID, $postId];
    }

    protected function image_filename($ind)
    {
        return 'ailabs_' . $this->job['ailabs_user_id'] . '_' . $this->job_id . '_' . $ind;
    }

    protected function save_base64_to_temp_file($base64, $ind, $ext = '.png')
    {
        $temp_dir_path = sys_get_temp_dir();

        if (substr($temp_dir_path, -1) != DIRECTORY_SEPARATOR)
            $temp_dir_path .= DIRECTORY_SEPARATOR;

        $filename = $temp_dir_path . $this->image_filename($ind) . $ext;

        $handle = fopen($filename, 'wb');
        fwrite($handle, base64_decode($base64));
        fclose($handle);

        return $filename;
    }

    protected function trim_words($inputString, $numWords)
    {
        $words = explode(' ', $inputString);

        if (count($words) <= $numWords) {
            return $inputString;
        }

        $trimmedWords = array_slice($words, 0, $numWords);

        return implode(' ', $trimmedWords) . '...'; //'…';
    }

    protected function load_first_attachment($postId)
    {
        $sql = 'SELECT physical_filename FROM ' . ATTACHMENTS_TABLE . ' WHERE post_msg_id = ' . $postId . ' AND is_orphan = 0 ORDER BY attach_id ASC';
        $result = $this->db->sql_query($sql);
        $attachments = $this->db->sql_fetchrowset($result);
        $this->db->sql_freeresult($result);

        foreach ($attachments as $attachment) {
            $filename = $this->root_path . $this->config['upload_path'] . '/' . utf8_basename($attachment['physical_filename']);

            if (file_exists($filename))
                return file_get_contents($filename);
        }

        return false;
    }

    function extract_numeric_settings(&$text, $fields_array, &$settings = [], &$info = "", $info_divider = " ")
    {
        $info = "";
        $new = [];

        foreach ((array)$fields_array as $key => $value) {
            preg_match("/(--|—)" . $key . "\s([0-9\.]+)/i", $text, $matches);

            if (isset($matches[2])) {
                $new[$value] = +$matches[2];
                $text = preg_replace("/(--|—)" . $key . "\s([0-9\.]+)/i", '', $text);
                $info .= "--" . $value . " " . $new[$value] . $info_divider;
            }
        }

        $info = rtrim($info, $info_divider);

        if (!empty($info)) {
            foreach ((array)$fields_array as $key => $value) {
                unset($settings[$value]);
                if (isset($new[$value]))
                    $settings[$value] = $new[$value];
            }
        }

        return !empty($info);
    }
}
