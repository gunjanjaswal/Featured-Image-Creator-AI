=== Featured Image Creator AI ===
Contributors: gunjanjaswal
Tags: AI, featured image, DALL-E, stable diffusion, gemini
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://ko-fi.com/gunjanjaswal

Auto-generate stunning AI-powered featured images using OpenAI (DALL-E 3), Google Gemini, or Stability AI. Style presets, text/logo overlays, variations, auto alt text and social/Open Graph sizes.

== Description ==

Featured Image Creator AI is the ultimate tool to automatically generate professional-quality featured images for your WordPress posts using cutting-edge AI technology. Whether you need a single image or want to process your entire library, this plugin handles it all with support for top-tier AI providers like **OpenAI (DALL-E 3, GPT Image models)**, **Google Gemini (Imagen)**, and **Stability AI (Stable Diffusion 3, SeeDream)**.

= Features =

* **Style Presets**: **NEW!** One-click looks — Photographic, Flat Illustration, 3D Render, Watercolor, Minimal, Isometric, Cyberpunk, Paper-cut and more. No prompt-writing needed.
* **Text & Logo Overlay**: **NEW!** Burn the post title and/or your logo straight onto the image — auto-wrapped headline, font weight, color, position, readability scrim, and corner watermark. Bundled Poppins font; uses Imagick or GD.
* **Image Variations**: **NEW!** Generate several options at once and pick your favorite from a grid. The unchosen images are cleaned up automatically.
* **Auto Alt Text**: **NEW!** Writes accessible, SEO-friendly alt text by actually describing the generated image with the provider's vision model (OpenAI / Gemini), with a title-based fallback.
* **Social & Open Graph Images**: **NEW!** Create Facebook/Open Graph (1200×630), Twitter, square and Pinterest sizes from the same generated image — cropped locally with no extra API cost. Optionally sets the Open Graph share image for Yoast SEO / Rank Math, or outputs og:image tags itself.
* **Bring Your Own API Key**: Use your own OpenAI API key for complete control and transparency
* **Single Post Generation**: Generate featured images one at a time from the post editor
* **Bulk Generation**: Automatically generate featured images for all posts that don't have one
* **Bulk Regeneration**: Regenerate all featured images with a single click to refresh your site's look
* **Image Quality Control**: Choose between Standard, HD, or Low quality generation (OpenAI models only)
* **Multiple Output Formats**: Support for PNG, JPG, and WEBP formats (All Providers)
* **Customizable Prompts**: Customize the prompt template to match your brand and style
* **Secure Storage**: API keys are encrypted before storage in your database
* **WordPress Standards**: Built following WordPress.org coding and security standards
* **Flexible Dimensions**: Configure image dimensions (default: 1024x675px)
* **Scheduled Post Support**: Automatically generate featured images when scheduled posts are published

= Supported AI Providers & Options =

* **OpenAI**
    * Models: DALL-E 3, GPT Image 1, GPT Image 1 (Mini), GPT Image 1.5, GPT Image Latest
    * Quality: Standard, HD, Low (varies by model)
    * Format: PNG, JPG, WEBP (Autoconverted)
* **Stability AI**
    * Models: Stable Diffusion 3, SeeDream 4.5
    * Format: PNG, JPG, WEBP (Native)
* **Google Gemini**
    * Models: Imagen 3.0
    * Format: PNG (Default)

= How It Works =

1. Install and activate the plugin
2. Go to Featured Image Creator AI in the admin menu
3. Enter your OpenAI API key in Settings
4. Customize the prompt template (optional)
5. Generate images from the post editor or use bulk generation

= Using the Creative Features =

**Styles** — Under *Settings > AI Enhancements*, pick a default look (Photographic, Flat Illustration, Watercolor, Cyberpunk, and more). In the post editor, the Style dropdown lets you override it for a single image. No prompt writing required.

**Text & Logo Overlay** — Enable it under *Settings > Text & Logo Overlay*. The post title is drawn on the image by default (use the {title} placeholder), and you can set the font weight, size, color, position, background scrim, and add a logo/watermark in any corner. Rendering happens on your server with Imagick or GD, so it costs nothing extra.

**Variations** — In the post editor, click *Generate Options* to create several images at once, then click your favorite to set it. The other options are deleted automatically. The number of options is set under *Settings > AI Enhancements* (2–8). Each option uses one API credit.

**Auto Alt Text** — Turn it on under *Settings > AI Enhancements*. After an image is set, OpenAI or Gemini describes it and saves the text as the image's alt attribute for SEO and accessibility. Stability AI falls back to the post title.

**Social & Open Graph Images** — Enable it under *Settings > Social & Open Graph Images* and tick the sizes you want (Open Graph 1200×630, Twitter, square, Pinterest). Each generated image is cropped locally into those sizes and saved to the media library — no extra API credits. With *Open Graph Image* enabled, the share image is set for Yoast SEO / Rank Math, or output as og:image / twitter:image tags when no SEO plugin is active.

= Use Cases =

* Bloggers who need featured images for every post
* Content marketers managing multiple websites
* Publishers with large content libraries
* Anyone who wants to save time on image creation

= Privacy & Data =

This plugin uses external AI services to generate images. When you generate an image:
* Your post title (built into the prompt) is sent to the AI provider
* The AI provider generates an image based on your prompt
* The image is downloaded and stored in your WordPress media library
* If **Auto Alt Text** is enabled, the generated image is additionally sent to the provider's vision model (OpenAI / Gemini) to produce a description. This is off by default.
* The text and logo overlay is rendered locally on your server — no image data leaves your site for that step
* Social / Open Graph sizes are cropped locally from the generated image — no image data leaves your site for that step
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

= How do I put the post title text on the image? =

Enable **Text & Logo Overlay** in Settings. The post title is drawn on the image by default (you can change the text, font weight, color, position and background scrim, and add a logo/watermark). Rendering uses the Imagick or GD PHP extension and a bundled Poppins font — no extra service required.

= What are "Variations"? =

In the post editor, click **Generate Options** to create several images at once, then pick your favorite from a grid. The images you don't choose are removed automatically to keep your media library clean. Each option uses one API credit, so 4 options cost 4 generations.

= Does Auto Alt Text cost extra? =

It makes one additional API call per image to the provider's vision model (OpenAI or Gemini) to describe the picture. It is disabled by default. Providers without vision (e.g. Stability AI) fall back to using the post title as the alt text at no extra cost.

= How do I get a Facebook / Twitter / Pinterest sized image? =

Enable **Social & Open Graph Images** in Settings and tick the sizes you want. Each time an image is generated, the plugin crops it locally into those sizes and saves them to your media library. This uses no extra API credits because it reuses the image you already generated.

= Does this set my Open Graph (social share) image automatically? =

If you enable the **Open Graph Image** option, the plugin sets the share image for Yoast SEO and Rank Math automatically. If neither plugin is active, it outputs `og:image` and `twitter:image` tags itself. This requires the "Open Graph" size to be enabled.

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

= 1.1.0 =
* **New: Style Presets.** Pick a ready-made look (Photographic, Flat Illustration, Digital Art, 3D Render, Watercolor, Minimal, Isometric, Cyberpunk, Paper-cut, Corporate, Retro) as a site default or per post — no prompt engineering required. Extendable via the `aifig_style_presets` filter.
* **New: Text & Logo Overlay.** Burn an auto-wrapped headline (the post title by default) and/or a logo/watermark directly onto generated images. Configurable font weight, size, color, vertical position, readability scrim (gradient/dark/light) and logo corner + size. Renders locally with Imagick or GD using a bundled Poppins (SIL OFL) font.
* **New: Image Variations.** Generate multiple options at once from the editor and choose your favorite from a grid; unchosen images are deleted automatically. Count is configurable (2–8).
* **New: Auto Alt Text.** Optionally describe each generated image with the provider's vision model (OpenAI `gpt-4o-mini` / Gemini) and save it as the attachment alt text for SEO and accessibility. Falls back to the post title for providers without vision. Models are filterable (`aifig_openai_vision_model`, `aifig_gemini_vision_model`).
* **New: Social & Open Graph Images.** Generate Facebook/Open Graph (1200×630), Twitter/X, square (1080×1080) and Pinterest (1000×1500) sizes from each generated image. Cropping is done locally — no extra API credits. Optionally sets the Open Graph share image for Yoast SEO and Rank Math, with an `og:image` / `twitter:image` fallback when no SEO plugin is active. Sizes are extendable via the `aifig_social_variants` filter.
* **New: "How to use" guide** added to the settings screen and the documentation, covering styles, overlays, variations, alt text and social images.
* **New: "What's New" panel** shown once after updating the plugin, summarizing the latest features. It is dismissible per user and never appears on a fresh install.
* Refactored the generator into reusable attachment/variation/alt-text steps; all generation paths (single, bulk, scheduled auto-publish) share the new style, overlay and social handling. Social variants are cleaned up and regenerated whenever the featured image changes.

= 1.0.6 =
* WordPress 7.0 integration:
  * Iframed editor: documented the meta-box-only integration. The meta box renders in the parent admin chrome (not the editor iframe), so no asset changes were required.
  * AI Client API: added `aifig_is_wp_ai_client_available()` (uses core `wp_supports_ai()` + `wp_ai_client_prompt()`) and `aifig_wp_ai_client_prompt()` helpers. Routing through the core client is opt-in via the `aifig_use_wp_ai_client` filter; bundled OpenAI / Gemini / Stability providers remain the default image-generation path.
  * Connectors API: registers an `ai_provider` connector on the `wp_connectors_init` action so the encrypted `aifig_api_key` option surfaces on the central Connections screen alongside core's auto-discovered providers. Falls back silently on WP 6.x.

= 1.0.5 =
* Updated "Tested up to" to WordPress 7.0.
* Replaced Buy Me a Coffee donation link with Ko-fi (https://ko-fi.com/gunjanjaswal).
* Added "Contact Developer" link to plugin row meta on the Plugins screen.
* Replaced raw error_log() calls on scheduled-publish auto-generate with a new `aifig_auto_generate_result` action hook so site owners can log results themselves.
* Corrected GitHub repository slug in Documentation row-meta link.

= 1.0.4 =
* Fixed fatal error by requiring file.php before calling wp_tempnam() in OpenAI provider

= 1.0.3 =
* Enable Output Format selection (PNG/JPG/WEBP) for OpenAI models (images are automatically converted)
* Updated documentation with full list of supported models and options
* Improved compatibility with GPT Image 1 (Mini)

= 1.0.2 =
* Added new image generation models: GPT Image 1, GPT Image 1 (Mini), GPT Image 1.5, GPT Image Latest
* Added SeaDream 4.5 support for Stability AI
* Added image quality settings (Standard/HD/Low) and output format selection (PNG/JPG/WEBP)
* Added Bulk Regeneration feature to regenerate images for all posts
* Fixed image dimension error for custom models
* Improved code quality and security (nonces, escaping)

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

= 1.1.0 =
Big creative update: style presets, text/logo overlays, multi-image variations, auto alt text, and social/Open Graph image sizes (generated locally from one image, no extra API cost). All new features are opt-in and default off, so existing behavior is unchanged.

= 1.0.6 =
WordPress 7.0 readiness: forward-compat shims for the AI Client and Connectors APIs (graceful fallback on older WP). No breaking changes.

= 1.0.5 =
Compatibility with WordPress 7.0; donation link moved to Ko-fi; Contact Developer row meta added; debug error_log calls replaced with a do_action hook.

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

If you find this plugin helpful, consider [supporting on Ko-fi](https://ko-fi.com/gunjanjaswal) to back the development.

== Contributing ==

Development happens on [GitHub](https://github.com/gunjanjaswal/Featured-Image-Creator-AI). Pull requests are welcome!
