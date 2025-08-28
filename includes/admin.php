<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function vanterra_forms_register_settings() {
    register_setting( 'vanterra_forms_settings', 'vanterra_enable_attribution', array(
        'type' => 'boolean',
        'sanitize_callback' => function( $value ) { return $value ? 1 : 0; },
        'default' => 1,
    ) );
    register_setting( 'vanterra_forms_settings', 'vanterra_forms_webhook_enabled', array(
        'type' => 'boolean',
        'sanitize_callback' => function( $value ) { return $value ? 1 : 0; },
        'default' => 0,
    ) );
    register_setting( 'vanterra_forms_settings', 'vanterra_forms_webhook_url', array(
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default' => '',
    ) );
}
add_action( 'admin_init', 'vanterra_forms_register_settings' );

function vanterra_forms_add_menu() {
    add_options_page(
        'Vanterra Forms',
        'Vanterra Forms',
        'manage_options',
        'vanterra-forms',
        'vanterra_forms_render_settings_page'
    );
}
add_action( 'admin_menu', 'vanterra_forms_add_menu' );

function vanterra_forms_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>Vanterra Forms</h1>
        <form action="options.php" method="post">
            <?php settings_fields( 'vanterra_forms_settings' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Enable Attribution Tracking</th>
                    <td>
                        <label>
                            <input type="checkbox" name="vanterra_enable_attribution" value="1" <?php checked( 1, (int) get_option( 'vanterra_enable_attribution', 1 ) ); ?> />
                            <span>Load front-end attribution tracker</span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Send Webhook on Submission</th>
                    <td>
                        <label>
                            <input type="checkbox" name="vanterra_forms_webhook_enabled" value="1" <?php checked( 1, (int) get_option( 'vanterra_forms_webhook_enabled', 0 ) ); ?> />
                            <span>POST submission JSON to the URL below</span>
                        </label>
                        <p>
                            <input type="url" class="regular-text code" name="vanterra_forms_webhook_url" value="<?php echo esc_attr( get_option( 'vanterra_forms_webhook_url', '' ) ); ?>" placeholder="https://example.com/webhook" />
                        </p>
                        <p class="description">Only submissions from the auto-created Vanterra Attribution Form are sent.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}


