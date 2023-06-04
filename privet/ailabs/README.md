# AI Labs v 1.0.4 RC
##### [Changelog](#changelog_link)  

Incorporate AI into your phpBB board and get ready for an exciting experience.  
Currently supported ChatGPT, DALL-E (OpenAI) and Stable Diffusion (Stability AI).  
Midjourney support coming soon.  

Examples:  
 - [ChatGPT](https://privet.fun/viewtopic.php?t=2802) 
 - [ChatGPT, custom prompt](https://privet.fun/viewtopic.php?t=2799) 
 - [DALL-E](https://privet.fun/viewtopic.php?t=2800)
 - [Stable Diffusion by Stability AI](https://privet.fun/viewtopic.php?t=2801)  
 - [Midjourney, coming soon 🚀](https://privet.fun/viewtopic.php?t=2718)
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

## Installation

Download https://github.com/privet-fun/phpbb_ailabs and copy `/privet/ailabs` to `phppp/ext` folder:  
![Attachment settings](../privet/ailabs/docs/ext_location.png) 

Go to `ACP` > `Customise` > `Manage extensions` and enable the `AI Labs` extension.

Finally go to `ACP` > `Extensions` > `AI Labs` > `Settings` and add desired AI configurations:
![Attachment settings](../privet/ailabs/docs/ailabs_settings.png) 

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

## ChatGPT advanced setup 

You can setup ChatGPT to pretend it is somebody else.  
Let's create new board user `Bender` and configure as shown below:  
![Attachment settings](../privet/ailabs/docs/chatgpt_bender_example.png)  
Notice we used `prefix` and `prefix_tokens` to fine-tune ChatGPT AI behaviour.    
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
If Log entry is empty it ususally means that `/ailabs/*` routes blocked by one of phpBB extensions (eg <a href="https://www.phpbb.com/customise/db/extension/login_required">Login Required</a>) and you will need to add `/ailabs/*` to extension whitelist.  
You can examine Log `response` (JSON) to see details for AI response.  
Please feel free to post your quesions or concerns at https://github.com/privet-fun/phpbb_ailabs/issues.
## Support and suggestions

This extension is currently being actively developed. For communication, please use https://github.com/privet-fun/phpbb_ailabs/issues.

## <a name="changelog_link"></a>Changelog 

* 1.0.4 June 4, 2023
  - Troubleshooting section added
  - Added cofiguration for reply in topics
  - Fixed links generation for cases where cookies disabled
  - AI Labs internal controlles (`/ailabs/*`) will attempt to establish session to deal with phpBB extensions like <a href="https://www.phpbb.com/customise/db/extension/login_required">Login Required</a> 
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
