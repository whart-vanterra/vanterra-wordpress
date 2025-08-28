<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue front-end assets (Attribution Tracker)
 */
function vanterra_forms_enqueue_assets() {
    if ( is_admin() ) {
        return;
    }
    $enabled = (int) get_option( 'vanterra_enable_attribution', 1 );
    if ( ! $enabled ) {
        return;
    }

    $handle     = 'vanterra-attribution-tracker';
    $script_rel = 'assets/js/attribution-tracker.js';
    $script_abs = VANTERRA_FORMS_DIR . $script_rel;
    $script_url = plugins_url( $script_rel, VANTERRA_FORMS_FILE );
    $version    = file_exists( $script_abs ) ? filemtime( $script_abs ) : '1.0.0';

    wp_register_script( $handle, $script_url, array(), $version, array( 'in_footer' => true ) );

    if ( function_exists( 'wp_script_add_data' ) ) {
        @wp_script_add_data( $handle, 'strategy', 'defer' );
    }

    wp_enqueue_script( $handle );
}
add_action( 'wp_enqueue_scripts', 'vanterra_forms_enqueue_assets', 5 );

/**
 * Fallback to add defer attribute on older WP versions.
 */
function vanterra_forms_script_loader_tag( $tag, $handle, $src ) {
    if ( 'vanterra-attribution-tracker' === $handle && false === strpos( $tag, 'defer' ) ) {
        $tag = str_replace( '<script ', '<script defer ', $tag );
    }
    return $tag;
}
add_filter( 'script_loader_tag', 'vanterra_forms_script_loader_tag', 10, 3 );

/**
 * Shortcode: [vanterra_attribution_test]
 */
function vanterra_forms_shortcode_attribution_test() {
    if ( is_admin() ) {
        return '';
    }

    vanterra_forms_enqueue_assets();

    $uid = wp_generate_uuid4();
    $wrap_id = 'vt-attr-wrap-' . $uid;
    $btn_id  = 'vt-attr-refresh-' . $uid;
    $out_id  = 'vt-attr-output-' . $uid;

    ob_start();
    ?>
    <div id="<?php echo esc_attr( $wrap_id ); ?>" class="vt-attribution-test" style="padding:12px;border:1px solid #ddd;border-radius:6px;margin:12px 0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">
        <div style="display:flex;align-items:center;gap:8px;justify-content:space-between;flex-wrap:wrap;">
            <strong>Vanterra Attribution Test</strong>
            <div>
                <button type="button" id="<?php echo esc_attr( $btn_id ); ?>" style="cursor:pointer;padding:6px 10px;border:1px solid #ccc;border-radius:4px;background:#f7f7f7;">Refresh</button>
            </div>
        </div>
        <pre id="<?php echo esc_attr( $out_id ); ?>" style="white-space:pre-wrap;background:#fafafa;border:1px solid #eee;border-radius:4px;padding:8px;margin-top:8px;">Loading attribution data...</pre>
    </div>
    <script>
    (function(){
        var outId = <?php echo wp_json_encode( $out_id ); ?>;
        var btnId = <?php echo wp_json_encode( $btn_id ); ?>;

        function render(){
            try {
                if (!window.VT_Attribution || typeof window.VT_Attribution.getAllAttributionData !== 'function') {
                    return setTimeout(render, 50);
                }
                var data = window.VT_Attribution.getAllAttributionData();
                var pretty = JSON.stringify(data, null, 2);
                var out = document.getElementById(outId);
                if (out) out.textContent = pretty;
                if (window.console && console.info) {
                    console.info('[Vanterra] Attribution Data:', data);
                }
            } catch (e) {
                var outErr = document.getElementById(outId);
                if (outErr) outErr.textContent = 'Error reading attribution data: ' + (e && e.message ? e.message : e);
            }
        }
        function ready(fn){
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn); else fn();
        }
        ready(function(){
            var btn = document.getElementById(btnId);
            if (btn) btn.addEventListener('click', function(e){ e.preventDefault(); render(); });
            render();
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'vanterra_attribution_test', 'vanterra_forms_shortcode_attribution_test' );


