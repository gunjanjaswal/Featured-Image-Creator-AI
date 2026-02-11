=== Featured Image Creator AI ===
Contributors: gunjanjaswal
Tags: AI, featured image, DALL-E, stable diffusion, gemini
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Auto-generate stunning AI-powered featured images using OpenAI (DALL-E 3), Google Gemini, or Stability AI. Supports bulk generation and more.

== Description ==

Featured Image Creator AI is the ultimate tool to automatically generate professional-quality featured images for your WordPress posts using cutting-edge AI technology. Whether you need a single image or want to process your entire library, this plugin handles it all with support for top-tier AI providers like **OpenAI (DALL-E 3, GPT Image models)**, **Google Gemini (Imagen)**, and **Stability AI (Stable Diffusion 3, SeeDream)**.

= Features =

* **Bring Your Own API Key**: Use your own OpenAI API key for complete control and transparency
* **Single Post Generation**: Generate featured images one at a time from the post editor
* **Bulk Generation**: Automatically generate featured images for all posts that don't have one
* **Customizable Prompts**: Customize the prompt template to match your brand and style
* **Secure Storage**: API keys are encrypted before storage in your database
* **WordPress Standards**: Built following WordPress.org coding and security standards
* **Flexible Dimensions**: Configure image dimensions (default: 1024x675px)
* **Scheduled Post Support**: Automatically generate featured images when scheduled posts are published

= Supported AI Providers =

* OpenAI DALL-E 3 - High-quality image generation
* Google Gemini (Imagen) - Google's advanced image model
* Stability AI (Stable Diffusion 3) - Open and flexible image generation

= How It Works =

1. Install and activate the plugin
2. Go to Featured Image Creator AI in the admin menu
3. Enter your OpenAI API key in Settings
4. Customize the prompt template (optional)
5. Generate images from the post editor or use bulk generation

= Use Cases =

* Bloggers who need featured images for every post
* Content marketers managing multiple websites
* Publishers with large content libraries
* Anyone who wants to save time on image creation

= Privacy & Data =

This plugin uses external AI services to generate images. When you generate an image:
* Your post title is sent to the AI provider (OpenAI)
* The AI provider generates an image based on your prompt
* The image is downloaded and stored in your WordPress media library
* No other data is sent to external services

Your API key is encrypted and stored securely in your WordPress database.

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Go to Plugins > Add New
3. Search for "AI Featured Image Generator"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Go to Plugins > Add New > Upload Plugin
4. Choose the ZIP file and click "Install Now"
5. Activate the plugin

= Configuration =

1. Go to Featured Image Creator AI > Settings in the admin menu
2. Get your API key from [OpenAI Platform](https://platform.openai.com/api-keys)
3. Enter your API key and save settings
4. (Optional) Customize the prompt template
5. Start generating images!

== Frequently Asked Questions ==

= Do I need an API account? =

Yes, you need an API account from your chosen provider:
* OpenAI: Sign up at [platform.openai.com](https://platform.openai.com/)
* Google Gemini: Get API key at [aistudio.google.com](https://aistudio.google.com/app/apikey)
* Stability AI: Sign up at [platform.stability.ai](https://platform.stability.ai/)

= How much does it cost? =

The plugin itself is free. API costs vary by provider:
* OpenAI DALL-E 3: ~$0.04-0.08 per image
* Google Gemini: Check current pricing at Google AI Studio
* Stability AI: ~$0.04 per image (varies by model)

= Can I use my own images instead of AI-generated ones? =

Yes! This plugin is optional. You can still upload and set featured images manually as usual.

= Will this work with custom post types? =

Currently, the plugin only supports standard WordPress posts. Support for custom post types may be added in future versions.

= What happens if image generation fails? =

The plugin will display an error message explaining what went wrong. Common issues include invalid API keys, network errors, or API rate limits.

= Can I customize the image dimensions? =

Yes! Go to Featured Image Creator AI > Settings and adjust the width and height settings.

= Is my API key secure? =

Yes, your API key is encrypted using WordPress security keys before being stored in the database.

= Can I generate images for existing posts? =

Yes! Use the bulk generation feature under Featured Image Creator AI > Bulk Generate to generate images for all posts without featured images.

= Does this plugin work with Gutenberg? =

Yes, the plugin works with both the Classic Editor and Gutenberg (Block Editor).

== Screenshots ==

1. Settings page - Configure your API key and preferences
2. Post editor meta box - Generate featured images with one click
3. Bulk generation page - Process multiple posts at once
4. Generated featured image example

== Changelog ==

= 1.0.2 =
* Added new image generation models: GPT Image 1, GPT Image 1 (Mini), GPT Image 1.5, GPT Image Latest
* Added SeaDream 4.5 support for Stability AI
* Added image quality settings (Standard/HD/Low) and output format selection (PNG/JPG/WEBP)
* Added Bulk Regeneration feature to regenerate images for all posts
* Fixed image dimension error for custom models

= 1.0.1 =
* Added automatic featured image generation for scheduled posts
* Fixed author name spelling
* When a scheduled post is published without a featured image, one is automatically generated

= 1.0.0 =
* Initial release
* OpenAI DALL-E 3 integration
* Google Gemini (Imagen) integration
* Stability AI (Stable Diffusion 3) integration
* Single post image generation
* Bulk generation for posts without featured images
* Customizable prompt templates
* Encrypted API key storage
* WordPress.org standards compliance

== Upgrade Notice ==

= 1.0.0 =
Initial release of Featured Image Creator AI.

== Third-Party Services ==

This plugin relies on the following third-party services:

= OpenAI API =
* Service: OpenAI DALL-E 3 Image Generation
* Website: https://openai.com/
* API Documentation: https://platform.openai.com/docs/api-reference/images
* Terms of Service: https://openai.com/terms/
* Privacy Policy: https://openai.com/privacy/

= Google Gemini API =
* Service: Google Gemini (Imagen) Image Generation
* Website: https://ai.google.dev/
* API Documentation: https://ai.google.dev/docs
* Terms of Service: https://policies.google.com/terms
* Privacy Policy: https://policies.google.com/privacy

= Stability AI API =
* Service: Stability AI (Stable Diffusion) Image Generation
* Website: https://stability.ai/
* API Documentation: https://platform.stability.ai/docs
* Terms of Service: https://stability.ai/terms-of-service
* Privacy Policy: https://stability.ai/privacy-policy

When you use this plugin to generate images, your post titles are sent to your chosen AI provider's servers to generate images. Please review the provider's terms and privacy policy before using this plugin.

== Support ==

For support, please visit the [plugin support forum](https://wordpress.org/support/plugin/ai-featured-image-generator/).

If you find this plugin helpful, consider [buying me a coffee](https://buymeacoffee.com/gunjanjaswal) ☕

== Contributing ==

Development happens on [GitHub](https://github.com/gunjanjaswal/Featured-Image-Creator-AI). Pull requests are welcome!
