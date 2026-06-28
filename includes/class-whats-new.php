<?php
/**
 * "What's New" on-update notice
 *
 * @package AI_Featured_Image_Generator
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class AIFIG_Whats_New
 *
 * Shows a one-time, per-user "What's New" panel after the plugin is updated.
 * A fresh install records the current version during activation, so the panel
 * only appears on a genuine update (or a legacy install that predates version
 * tracking).
 */
class AIFIG_Whats_New
{

    /**
     * Option storing the last-seen installed version.
     */
    const VERSION_OPTION = 'aifig_version';

    /**
     * Option flagging the version a "What's New" panel is available for.
     */
    const FLAG_OPTION = 'aifig_whats_new';

    /**
     * User meta recording the version a user has dismissed.
     */
    const USER_META = 'aifig_whats_new_seen';

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_init', array($this, 'detect_update'));
        add_action('admin_init', array($this, 'handle_dismiss'));
        add_action('admin_notices', array($this, 'render'));
    }

    /**
     * Detect a version change and flag the "What's New" panel.
     *
     * Fresh installs set VERSION_OPTION in the activation hook, so the stored
     * value already matches and no panel is flagged. On update (or a legacy
     * install with no stored version) the values differ and we flag it.
     *
     * @return void
     */
    public function detect_update()
    {
        $stored = get_option(self::VERSION_OPTION);

        if ($stored === AIFIG_VERSION) {
            return;
        }

        update_option(self::FLAG_OPTION, AIFIG_VERSION);
        update_option(self::VERSION_OPTION, AIFIG_VERSION);
    }

    /**
     * Persist a per-user dismissal of the current version's panel.
     *
     * @return void
     */
    public function handle_dismiss()
    {
        if (empty($_GET['aifig_whats_new_dismiss'])) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }

        $nonce = isset($_GET['_aifig_nonce']) ? sanitize_text_field(wp_unslash($_GET['_aifig_nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'aifig_whats_new_dismiss')) {
            return;
        }

        update_user_meta(get_current_user_id(), self::USER_META, AIFIG_VERSION);

        wp_safe_redirect(remove_query_arg(array('aifig_whats_new_dismiss', '_aifig_nonce')));
        exit;
    }

    /**
     * Whether the panel should be shown to the current user.
     *
     * @return bool
     */
    private function should_show()
    {
        if (!current_user_can('manage_options')) {
            return false;
        }
        if (get_option(self::FLAG_OPTION) !== AIFIG_VERSION) {
            return false;
        }
        if (get_user_meta(get_current_user_id(), self::USER_META, true) === AIFIG_VERSION) {
            return false;
        }
        return true;
    }

    /**
     * Highlights shown in the current version's panel.
     *
     * @return array<array{icon:string,title:string,text:string}>
     */
    private function get_highlights()
    {
        return array(
            array(
                'icon'  => '🖌️',
                'title' => __('Style Presets', 'featured-image-creator-ai'),
                'text'  => __('Pick a ready-made look (Photographic, Flat Illustration, Watercolor, Cyberpunk and more) — no prompt writing.', 'featured-image-creator-ai'),
            ),
            array(
                'icon'  => '🏷️',
                'title' => __('Text & Logo Overlay', 'featured-image-creator-ai'),
                'text'  => __('Burn the post title and your logo straight onto the image, rendered locally.', 'featured-image-creator-ai'),
            ),
            array(
                'icon'  => '🔀',
                'title' => __('Image Variations', 'featured-image-creator-ai'),
                'text'  => __('Generate several options at once and pick your favorite from a grid.', 'featured-image-creator-ai'),
            ),
            array(
                'icon'  => '♿',
                'title' => __('Auto Alt Text', 'featured-image-creator-ai'),
                'text'  => __('Describe each image with a vision model for SEO and accessibility.', 'featured-image-creator-ai'),
            ),
            array(
                'icon'  => '📣',
                'title' => __('Social & Open Graph Images', 'featured-image-creator-ai'),
                'text'  => __('Create Facebook, Twitter, square and Pinterest sizes from one image — no extra API cost.', 'featured-image-creator-ai'),
            ),
        );
    }

    /**
     * Render the "What's New" panel.
     *
     * @return void
     */
    public function render()
    {
        if (!$this->should_show()) {
            return;
        }

        $highlights   = $this->get_highlights();
        $settings_url = admin_url('admin.php?page=aifig-settings');
        $dismiss_url  = wp_nonce_url(
            add_query_arg('aifig_whats_new_dismiss', '1'),
            'aifig_whats_new_dismiss',
            '_aifig_nonce'
        );
        ?>
        <div class="notice aifig-whats-new" style="padding:0;border:0;background:transparent;box-shadow:none;">
            <div style="background:#fff;border:1px solid #dcdcde;border-left:4px solid #764ba2;border-radius:8px;overflow:hidden;margin:10px 0;">
                <div style="background:#764ba2;color:#fff;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                    <strong style="font-size:16px;">
                        <?php
                        printf(
                            /* translators: 1: plugin name, 2: version number. */
                            esc_html__('🎉 What\'s new in %1$s %2$s', 'featured-image-creator-ai'),
                            esc_html__('Featured Image Creator AI', 'featured-image-creator-ai'),
                            esc_html(AIFIG_VERSION)
                        );
                        ?>
                    </strong>
                    <a href="<?php echo esc_url($dismiss_url); ?>" style="color:#fff;opacity:0.85;text-decoration:none;font-size:13px;">
                        <?php esc_html_e('Dismiss', 'featured-image-creator-ai'); ?> ✕
                    </a>
                </div>
                <div style="padding:16px 20px;">
                    <ul style="margin:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px 24px;list-style:none;">
                        <?php foreach ($highlights as $item): ?>
                            <li style="margin:0;">
                                <span style="font-size:15px;"><?php echo esc_html($item['icon']); ?> <strong><?php echo esc_html($item['title']); ?></strong></span>
                                <div style="color:#50575e;font-size:13px;margin-top:2px;"><?php echo esc_html($item['text']); ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p style="margin:16px 0 0;">
                        <a href="<?php echo esc_url($settings_url); ?>" class="button button-primary">
                            <?php esc_html_e('Review settings', 'featured-image-creator-ai'); ?>
                        </a>
                        <a href="<?php echo esc_url($dismiss_url); ?>" class="button" style="margin-left:6px;">
                            <?php esc_html_e('Got it', 'featured-image-creator-ai'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
}
