<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
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

    $existing_id = (int) get_option( 'vanterra_forms_gf_form_id', 0 );
    if ( $existing_id > 0 ) {
        $form = GFAPI::get_form( $existing_id );
        if ( $form && ! is_wp_error( $form ) ) {
            return; // already created
        }
    }

    // Try to prevent duplicates by checking by title
    $target_title = 'Vanterra Attribution Form';
    $forms = GFAPI::get_forms();
    foreach ( $forms as $f ) {
        if ( isset($f['title']) && $f['title'] === $target_title ) {
            update_option( 'vanterra_forms_gf_form_id', (int) $f['id'] );
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
        ),
        // Phone
        array(
            'id' => 3,
            'type'        => 'phone',
            'label'       => 'Phone',
            'required'    => true,
            'phoneFormat' => 'standard',
        ),
        // Address (composite)
        array(
            'id' => 4,
            'type'        => 'address',
            'label'       => 'Address',
            'required'    => true,
            'addressType' => 'us',
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
        ),
        // Comment
        array(
            'id' => 6,
            'type'        => 'textarea',
            'label'       => 'Tell us about your issue',
            'required'    => false,
            'placeholder' => "Briefly describe what you're seeing…",
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
    }
}


