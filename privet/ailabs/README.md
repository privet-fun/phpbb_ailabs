# AI Labs v 1.0.8

Incorporate AI into your phpBB board and get ready for an exciting experience.  
Currently supported Midjourney, ChatGPT and DALL-E (OpenAI), Gemini and Gemini Vision (Google), Stable Diffusion (Stability AI).  

# Table of Contents
1. [Examples](#examples)
2. [Requirements](#requirements)
3. [Important notes](#important-notes)
4. [Installation](#installation)
5. [Midjourney setup](#midjourney-setup)
6. [Gemini setup ](#gemini-setup)
6. [Gemini Vision setup ](#gemini-vision-setup)
6. [ChatGPT setup ](#chatgpt-setup)
7. [ChatGPT advanced setup](#chatgpt-advanced-setup)
8. [DALL-E setup](#dall-e-setup)
9. [DALL-E advanced features](#dall-e-advanced-features)
10. [Stable Diffusion setup](#stable-diffusion-setup)
11. [Troubleshooting](#troubleshooting)
12. [Support and suggestions](#support-and-suggestions)
13. [Changelog](#changelog)
14. [License](#license)

## Examples

 - [Midjourney](https://privet-fun.translate.goog/viewtopic.php?t=3404&_x_tr_sl=auto&_x_tr_tl=en&_x_tr_hl=en&_x_tr_pto=wapp) 
 - [Gemini](https://privet.fun/viewtopic.php?t=4088)  
 - [Gemini Vision](https://privet.fun/viewtopic.php?t=4089)  
 - [ChatGPT](https://privet.fun/viewtopic.php?t=2802) 
 - [ChatGPT, custom prompt](https://privet.fun/viewtopic.php?t=2799) 
 - [DALL-E](https://privet.fun/viewtopic.php?t=2800)
 - [Stable Diffusion by Stability AI](https://privet.fun/viewtopic.php?t=2801)  
 - [Stable Diffusion by Leonardo AI, coming soon 🚀](https://privet.fun/viewtopic.php?t=2605)  
    Also available as Telegram bot https://t.me/stable_diffusion_superbot

## Requirements
* php >=7.4
* phpbb >= 3.2

## Important notes

* Installing of [Simple mentions phpBB extension](https://www.phpbb.com/customise/db/extension/simple_mentions/) strongly suggested.  
  [@mention]() feature makes it really easy to talk to AI bots and other board users.

* If you are planning to use image generation AI (eg DALL-E or Stable Diffusion) make sure to adjust attachment settings to support large images and verify that `webp` image extension configured.  

  Go to `ACP` > `General` > `Attachment settings` and adjust `Total attachment quota`, `Maximum file size` and `Maximum file size messaging`:
  ![Attachment settings](../privet/ailabs/docs/attachment_settings.png)  

  Go to `ACP` > `Posting` > `Manage attachment extensions`, look for `webp`, add it if missing:  
  ![Attachment settings](../privet/ailabs/docs/attachment_webp.png)  

  Above does not apply to Midjourney, as all generated images are actually stored on your Discord account and served via the Discord CDN.

* If you have extensions installed that require users to log in, such as [Login Required](https://www.phpbb.com/customise/db/extension/login_required) you will need to whitelist `/ailabs/*` and `/app.php/ailabs/*` since AI Labs extension uses callbacks.

* Adjust [PHP configuration](https://www.php.net/manual/en/info.configuration.php) to allow longer script execution. ChatGPT API responses may take up to 90 seconds to respond in certain cases. If you have the default settings, your SQL connection will be closed after 30 seconds, preventing the extension from functioning properly.  
  Suggested values for `php.ini`:  
  > max_execution_time = 180  
  > max_input_time = 90  

## Installation

Download https://github.com/privet-fun/phpbb_ailabs and copy `/privet/ailabs` to `phppp/ext` folder:  
![Attachment settings](../privet/ailabs/docs/ext_location.png) 

If you have a previous version of this extension installed, you will need to disable it and then enable it again after the new version has been copied over.  

Go to `ACP` > `Customise` > `Manage extensions` and enable the `AI Labs` extension.

Finally go to `ACP` > `Extensions` > `AI Labs` > `Settings` and add desired AI configurations:
![Attachment settings](../privet/ailabs/docs/ailabs_settings.png) 

## Midjourney setup 

*  You'll need Midjourney Discord and useapi.net accounts with active subscriptions.   
   Follow instructions at https://www.useapi.net/docs/start-here to setup and verify both.     

* Create new board user who will act as AI bot, for our example we will use user `Midjourney`.  
  Make sure this user account is activated and fully functional.  

* Got to `ACP` > `Extensions` > `AI Labs` > `Settings` and add new configuration, select `midjourney` from AI dropdown:  
  ![Attachment settings](../privet/ailabs/docs/midjourney_setup.png)  
  
  - Use `Load default configuration/template` to get defaults.  
    Replace Configuration JSON `api-key`, `discord`, `server` and `channel` with your values.  
  - Select forums where you want `Midjourney` AI user to reply to new posts and/or to quoted and [@mention](https://www.phpbb.com/customise/db/extension/simple_mentions) (if you are using Simple mentions extension) posts. 

* Save changes, navigate to forum configured above and create new post (if you configured `Reply on a post`) or quote/[@mention]() `Midjourney` user:  
  ![Attachment settings](../privet/ailabs/docs/midjourney_example.png)

* Images generated by Midjourney Discord bot via useapi.net stored and served from Discord CDN.  

## Gemini setup 

* Please follow the Google [instructions](https://ai.google.dev/tutorials/rest_quickstart) to create and activate a Gemini API key in Google AI Studio.  
   Note the Gemini API key you create, you will need it later to set up the Gemini and Gemini Vision bots.  

* Create a new board user who will act as the AI bot; for our example, we will use the user `Gemini`.  
  Ensure this user account is activated and fully functional.  

* Go to `ACP` > `Extensions` > `AI Labs` > `Settings` and add a new configuration, selecting `gemini` from the AI dropdown:    
  ![](../privet/ailabs/docs/gemini_setup.png)  
  
  - Use `Load default configuration/template` to load the defaults.  
    Replace `<API-KEY>` in the Configuration JSON with your Gemini API key.  
  - Select the forums where you want the `Gemini` AI user to reply to new posts and/or to quoted and [@mention](https://www.phpbb.com/customise/db/extension/simple_mentions) posts (if you are using the Simple Mentions extension). 

* Save the changes, navigate to the forum configured above, and create a new post (if you configured `Reply on a post`) or quote/[@mention]() the `Gemini` user to verify that it is working as expected. Refer to the [troubleshooting](#troubleshooting) section if you encounter any issues.

* Fine-tuning can be achieved by adjusting the following Gemini API configuration parameters:
  - `model` can be found here: https://ai.google.dev/models/gemini#model-versions, `model` is part of `url_generateContent` and `url_countTokens`.
  - `temperature`, `topK`, `topP` can be found here: https://ai.google.dev/docs/concepts#model_parameters, these should be placed in the `generation_config` node.
    Users can override the above parameters by providing a hint in the message using the `--param value` notation, where `--param` is case-insensitive.
    E.g. `--temperature 0` or `--temperature 0.5 --topk 1 --topp 0.8` 

* Additional settings used by the Gemini API:
  - `message_tokens`, default 2048; this is the maximum size of a single user message.
  - `max_tokens`, default 30720; this is the maximum size of the entire conversation.
  - `prefix`, default is empty; it can be used to prompt the model.  
  - `max_quote_length`, if provided, the quoted response text will be truncated to the number of words defined by the `max_quote_length` value. Set it to 0 to remove all quoted text entirely. 

For an examples of how to use Gemini bot please refer to [Gemini](https://privet.fun/viewtopic.php?t=4088).

## Gemini Vision setup 

The setup for Gemini Vision follows the same steps as the above-mentioned Gemini bot. You will need to create a separate board user, e.g. `GeminiVision` and select `gemini_vision` from the AI dropdown.

The Gemini Vision bot does not support conversations, you will need to provide a prompt along with an image every time. You can attach an image to the post or provide an image URL directly in the prompt. For an examples of how to use Gemini Vision bot please refer to [Gemini Vision](https://privet.fun/viewtopic.php?t=4089).

## ChatGPT setup 

*  You will need OpenAI account, sign up at https://platform.openai.com/.  
   To obtain API key go to https://platform.openai.com/account/api-keys, click on `Create new secret key`, copy and save in a safe place generated API key.  
   Open AI key starts with `sk-` and look something like this `sk-rb5yW9j6Nm2kP3Fhe7CPzT1QczwDZ5LvnlBfYU2EoqyX1dWs`.  

* Create new board user who will act as AI bot, for our example we will use user `ChatGPT`.  
  Make sure this user account is activated and fully functional.  

* Got to `ACP` > `Extensions` > `AI Labs` > `Settings` and add new configuration, select `chatgpt` from AI dropdown:  
  ![Attachment settings](../privet/ailabs/docs/chatgpt_setup.png)  
  
  - Use `Load default configuration/template` to get defaults.  
    Replace Configuration JSON `api-key` with your Open AI key.  
  - Select forums where you want `ChatGPT` AI user to reply to new posts and/or to quoted and [@mention](https://www.phpbb.com/customise/db/extension/simple_mentions) (if you are using Simple mentions extension) posts. 

* Save changes, navigate to forum configured above and create new post (if you configured `Reply on a post`) or quote/[@mention]() `ChatGPT` user:  
  ![Attachment settings](../privet/ailabs/docs/chatgpt_example.png)

* Fine-tuning can be done by adjusting following OpenAI API chat parameters https://platform.openai.com/docs/api-reference/chat
  - `model`, default `gpt-3.5-turbo`, full list of models available at https://platform.openai.com/docs/models
  - `temperature`, `top_p`, `frequency_penalty` and `presence_penalty` - see https://platform.openai.com/docs/api-reference/chat/create

* Additional setting used by ChatGPT AI 
  - `message_tokens`, default 4096, limit maximum size of the entire conversation thread  
  - `max_tokens`, default 1024, define size reserved for AI reply when quoted  
  - `prefix`, default empty, can be used to prompt model  
  - `prefix_tokens`, default 0, copy above `prefix` to https://platform.openai.com/tokenizer to get size of your `prefix` in tokens and update `prefix_tokens` with number returned by tokenizer  
  - `max_quote_length`, if provided, the quoted response text will be truncated to the number of words defined by the max_quote_length value. Set it to 0 to remove all quoted text entirely.  

## ChatGPT advanced setup 

You can setup ChatGPT to pretend it is somebody else.  
Let's create new board user `Bender` and configure as shown below:  
![Attachment settings](../privet/ailabs/docs/chatgpt_bender_example.png)  
Notice we used `prefix` and `prefix_tokens` to fine-tune ChatGPT AI behavior.    
Our AI bot `Bender` will provide responses like [this](https://privet.fun/viewtopic.php?t=2799), mostly staying in a character.  
  
## DALL-E setup 

Setup mostly the same as for ChatGPT above:  
![Attachment settings](../privet/ailabs/docs/dalle_setup.png)    

Refer to https://platform.openai.com/docs/api-reference/images/create to learn more about `n` and `size` parameters.  
[Examples](https://privet.fun/viewtopic.php?p=355594)

## DALL-E advanced features

 * To generate an image of the desired size, you can specify one of the following sizes anywhere within the prompt, [example](https://privet.fun/viewtopic.php?p=355600#p355600):  
   - 1024x1024  
   - 512x512  
   - 256x256  

 * To create [variations](https://platform.openai.com/docs/api-reference/images/create-variation) of the image simply post image url to the prompt, [example](https://privet.fun/viewtopic.php?p=355596#p355596)

## Stable Diffusion setup 

*  You will need Stability AI account, follow official instructions https://platform.stability.ai/docs/getting-started/authentication to create account and obtain API key.  

* Create new board user, let's say `Stable Diffusion` and create configuration:  
  ![Attachment settings](../privet/ailabs/docs/stablediffusion_setup.png)     
  [Examples](https://privet.fun/viewtopic.php?t=2801)  

* Refer to https://api.stability.ai/docs#tag/v1generation/operation/textToImage to learn more about configuration JSON parameters.  

## Troubleshooting
AI Labs extension maintains internal logs, you should have admin or moderator rights to see log icon:  
![Attachment settings](../privet/ailabs/docs/debugging_post_icon.png)  

You can see entire AI communication history in the log:  
![Attachment settings](../privet/ailabs/docs/debugging_log.png)  
If Log entry is empty it usually means that `/ailabs/*` or `/app.php/ailabs/*` routes blocked by one of phpBB extensions (eg <a href="https://www.phpbb.com/customise/db/extension/login_required">Login Required</a>) and you will need to add `/ailabs/*` or `/app.php/ailabs/*` to extension whitelist.  
You can examine Log `response` (JSON) to see details for AI response.  
Please feel free to post your questions or concerns at https://github.com/privet-fun/phpbb_ailabs/issues.

When setting up your bot, you will be able to test the bot URL by referring to the `Bot URL (test)` link below:
![](../privet/ailabs/docs/gemini_setup.png)  
If you do not see the bot response `Processing job 0`, you will need to investigate what is preventing access to that URL.

## Support and suggestions

This extension is currently being actively developed. For communication, please use https://github.com/privet-fun/phpbb_ailabs/issues.

## Changelog 

* 1.0.8 March 10, 2024
  - Added support for Gemini and Gemini Vision by Google 
  - Added support for [Simple mentions phpBB extension](https://www.phpbb.com/customise/db/extension/simple_mentions/) version 2.x

* 1.0.7 December 26, 2023
  - Updated the Midjourney Bot to support the v2 API from https://useapi.net  
    Make sure to update your Midjourney bot [configuration](https://github.com/privet-fun/phpbb_ailabs/blob/main/privet/ailabs/docs/midjourney_setup.png):
    ```   
      "url_imagine": "https://api.useapi.net/v2/jobs/imagine",
      "url_button": "https://api.useapi.net/v2/jobs/button",
    ```
  - All messages and warnings from the Midjourney Bot will now be relayed back
  - [Custom Zoom](https://docs.midjourney.com/docs/zoom-out) support added
  - Added support for Midjourney v6, including quoted text and new buttons 

* 1.0.6 October 7, 2023
  - Minor internal changes to address phpBB extension certification

* 1.0.5 October 1, 2023
  - Midjourney support added
  - `max_quote_length` option added for ChatGPT 

* 1.0.4 June 4, 2023
  - Troubleshooting section added
  - Added configuration for reply in topics
  - Fixed links generation for cases where cookies disabled
  - AI Labs internal controllers (`/ailabs/*`) will attempt to establish session to deal with phpBB extensions like <a href="https://www.phpbb.com/customise/db/extension/login_required">Login Required</a> 
  - Better descriptions added to help with setup
  - Minor bugfixes

* 1.0.3 June 1, 2023
  - bumped php requirements to >= 7.4
  - Comma removed, reported by [Vlad__](https://www.phpbbguru.net/community/viewtopic.php?p=561224#p561224)  

* 1.0.2 June 1, 2023
  - Only apply `utf8_encode_ucr` if present, reported by [Vlad__](https://www.phpbbguru.net/community/viewtopic.php?p=561158#p561158)  
   This will allow phpBB 3.2.1 support without any modifications. 
  - Removed `...` and `array` to support php 7.x, reported by [Vlad__](https://www.phpbbguru.net/community/viewtopic.php?p=561163#p561163)
  - Added missing  `reply` processing for chatgpt controller, reported by [Vlad__](https://www.phpbbguru.net/community/viewtopic.php?p=561205#p561205)
  - Added board prefix to all links, reported by [Miri4ever](https://www.phpbb.com/community/viewtopic.php?p=15958961#p15958961)

* 1.0.1 May 29, 2023
  - Fixed issues reported by [Miri4ever](https://www.phpbb.com/community/viewtopic.php?p=15958523#p15958523)
  - Removed all MySQL specific SQL, going forward extension should be SQL server agnostic 
  - Better language management 
  - Minor code cleanup

* 1.0.0 May 28, 2023
  - Public release

## License

[GPLv2](../privet/ailabs/license.txt)
