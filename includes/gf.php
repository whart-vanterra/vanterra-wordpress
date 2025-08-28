<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function vanterra_forms_get_registry() {
    $reg = get_option( 'vanterra_forms_registry', array() );
    return is_array( $reg ) ? $reg : array();
}

function vanterra_forms_set_registry( $registry ) {
    if ( is_array( $registry ) ) {
        update_option( 'vanterra_forms_registry', $registry );
    }
}

function vanterra_forms_get_attribution_keys() {
    $tracking_params = array(
        'utm_source','utm_medium','utm_campaign','utm_content','utm_term','utm_adgroup',
        'gclid','msclkid','fbclid','ttclid','yclid','li_fat_id','gbraid','wbraid','ga_client_id',
    );
    $fixed = array('landing_page','referrer','touch_time','client_id','session_id');

    $keys = array();
    // top-level values that may exist (client_id via vt_client_id cookie)
    $keys[] = 'client_id';
    // first/last variants
    foreach ( array_merge($tracking_params, $fixed) as $k ) {
        $keys[] = 'first_' . $k;
        $keys[] = 'last_' . $k;
    }
    // first touch marker
    $keys[] = 'first_touch_time';

    return $keys;
}

function vanterra_forms_maybe_create_gf_form() {
    if ( ! class_exists( 'GFAPI' ) ) {
        return; // Gravity Forms not active
    }

    $registry = vanterra_forms_get_registry();
    $target_title = 'Vanterra Attribution Form';
    $schema_version = 1;
    $existing_id = isset( $registry['attribution']['form_id'] ) ? (int) $registry['attribution']['form_id'] : (int) get_option( 'vanterra_forms_gf_form_id', 0 );
    if ( $existing_id > 0 ) {
        $form = GFAPI::get_form( $existing_id );
        if ( $form && ! is_wp_error( $form ) ) {
            $registry['attribution'] = array( 'form_id' => (int) $existing_id, 'version' => $schema_version );
            vanterra_forms_set_registry( $registry );
            return; // already created
        }
    }

    // Try to prevent duplicates by checking by title
    $target_title = 'Vanterra Attribution Form';
    $forms = GFAPI::get_forms();
    foreach ( $forms as $f ) {
        if ( isset($f['title']) && $f['title'] === $target_title ) {
            update_option( 'vanterra_forms_gf_form_id', (int) $f['id'] );
            $registry['attribution'] = array( 'form_id' => (int) $f['id'], 'version' => $schema_version );
            vanterra_forms_set_registry( $registry );
            return;
        }
    }

    // Visible fields first
    $fields = array(
        // Name (composite)
        array(  
            'id' => 1,
            'type'   => 'name',
            'label'  => 'Name',
            'required' => true,
            'inputName' => 'name',
            'inputs' => array(
                array( 'id' => 1.3, 'label' => 'First' ),
                array( 'id' => 1.6, 'label' => 'Last' ),
            ),
        ),
        // Email
        array(
            'id' => 2,
            'type'     => 'email',
            'label'    => 'Email',
            'required' => true,
            'inputName' => 'email',
        ),
        // Phone
        array(
            'id' => 3,
            'type'        => 'phone',
            'label'       => 'Phone',
            'required'    => true,
            'phoneFormat' => 'standard',
            'inputName'   => 'phone',
        ),
        // Address (composite)
        array(
            'id' => 4,
            'type'        => 'address',
            'label'       => 'Address',
            'required'    => true,
            'addressType' => 'us',
            'inputName'   => 'address',
            'inputs' => [
                [ 'id' => 4.1, 'label' => 'Street Address' ],
                [ 'id' => 4.2, 'label' => 'Address Line 2' ],
                [ 'id' => 4.3, 'label' => 'City' ],
                [ 'id' => 4.4, 'label' => 'State / Province' ],
                [ 'id' => 4.5, 'label' => 'ZIP / Postal Code' ],
                [ 'id' => 4.6, 'label' => 'Country' ],
            ],
        ),
        // Service select
        array(
            'id' => 5,
            'type'     => 'select',
            'label'    => 'Service',
            'required' => true,
            'choices'  => array(
                array( 'text' => 'Foundation Repair',      'value' => 'foundation_repair' ),
                array( 'text' => 'Basement Waterproofing', 'value' => 'basement_waterproofing' ),
                array( 'text' => 'Crawl Space Encapsulation', 'value' => 'crawl_space' ),
                array( 'text' => 'Concrete Lifting',       'value' => 'concrete_lifting' ),
            ),
            'inputName' => 'service',
        ),
        // Comment
        array(
            'id' => 6,
            'type'        => 'textarea',
            'label'       => 'Tell us about your issue',
            'required'    => false,
            'placeholder' => "Briefly describe what you're seeing…",
            'inputName'   => 'comment',
        ),
    );

    // Hidden attribution fields
    foreach ( vanterra_forms_get_attribution_keys() as $name ) {
        $fields[] = array(
            'type'       => 'hidden',
            'label'      => $name,
            'adminLabel' => $name,
            'inputName'  => $name,
        );
    }

    $form = array(
        'title'       => $target_title,
        'description' => 'Auto-created by Vanterra Forms plugin. Contains hidden attribution fields.',
        'fields'      => $fields,
    );

    $result = GFAPI::add_form( $form );
    if ( ! is_wp_error( $result ) && $result ) {
        update_option( 'vanterra_forms_gf_form_id', (int) $result );
        $registry['attribution'] = array( 'form_id' => (int) $result, 'version' => $schema_version );
        vanterra_forms_set_registry( $registry );
    }
}

// Render shortcode for the known form without hardcoding numeric ID
function vanterra_forms_shortcode_form( $atts = array() ) {
    if ( ! class_exists( 'GFAPI' ) ) return '';
    $atts = shortcode_atts( array( 'slug' => 'attribution' ), $atts, 'vanterra_form' );
    $registry = vanterra_forms_get_registry();
    $form_id = isset( $registry[ $atts['slug'] ]['form_id'] ) ? (int) $registry[ $atts['slug'] ]['form_id'] : (int) get_option( 'vanterra_forms_gf_form_id', 0 );
    if ( ! $form_id ) return '';
    if ( function_exists( 'gravity_form' ) ) {
        ob_start();
        gravity_form( $form_id, false, false, false, null, true, 1 );
        return ob_get_clean();
    }
    return '';
}
add_shortcode( 'vanterra_form', 'vanterra_forms_shortcode_form' );

// Send webhook on submission (only for our form)
function vanterra_forms_gform_after_submission( $entry, $form ) {
    $enabled = (int) get_option( 'vanterra_forms_webhook_enabled', 0 );
    $url     = trim( get_option( 'vanterra_forms_webhook_url', '' ) );
    if ( ! $enabled || empty( $url ) ) return;

    $registry = vanterra_forms_get_registry();
    $target_id = isset( $registry['attribution']['form_id'] ) ? (int) $registry['attribution']['form_id'] : (int) get_option( 'vanterra_forms_gf_form_id', 0 );
    if ( ! $target_id || (int) rgar( $form, 'id' ) !== $target_id ) return;

    // Build normalized map keyed by inputName (and parts for composites)
    $normalized = array();
    if ( is_array( rgar( $form, 'fields' ) ) ) {
        foreach ( $form['fields'] as $field ) {
            $type = isset( $field->type ) ? $field->type : ( isset( $field['type'] ) ? $field['type'] : '' );
            $inputName = isset( $field->inputName ) ? $field->inputName : ( isset( $field['inputName'] ) ? $field['inputName'] : '' );
            $id = isset( $field->id ) ? $field->id : ( isset( $field['id'] ) ? $field['id'] : null );
            if ( ! $id ) continue;

            if ( $type === 'name' ) {
                $normalized['name_first'] = rgar( $entry, (string) ( $id . '.3' ) );
                $normalized['name_last']  = rgar( $entry, (string) ( $id . '.6' ) );
            } else if ( $type === 'address' ) {
                $normalized['address_line1']   = rgar( $entry, (string) ( $id . '.1' ) );
                $normalized['address_line2']   = rgar( $entry, (string) ( $id . '.2' ) );
                $normalized['address_city']    = rgar( $entry, (string) ( $id . '.3' ) );
                $normalized['address_state']   = rgar( $entry, (string) ( $id . '.4' ) );
                $normalized['address_zip']     = rgar( $entry, (string) ( $id . '.5' ) );
                $normalized['address_country'] = rgar( $entry, (string) ( $id . '.6' ) );
            } else if ( $inputName ) {
                $normalized[ $inputName ] = rgar( $entry, (string) $id );
            }
        }
    }

    $payload = array(
        'form_id' => (int) rgar( $form, 'id' ),
        'form_title' => rgar( $form, 'title' ),
        'entry_id' => (int) rgar( $entry, 'id' ),
        'submitted_at' => current_time( 'mysql', true ),
        'fields' => $entry,
        'normalized' => $normalized,
    );

    wp_remote_post( $url, array(
        'timeout' => 8,
        'headers' => array('Content-Type' => 'application/json'),
        'body'    => wp_json_encode( $payload ),
    ) );
}
add_action( 'gform_after_submission', 'vanterra_forms_gform_after_submission', 10, 2 );


