<?php
if (! defined('ABSPATH')) {
    exit;
}

function vanterra_forms_get_registry()
{
    $reg = get_option('vanterra_forms_registry', array());
    return is_array($reg) ? $reg : array();
}

function vanterra_forms_set_registry($registry)
{
    if (is_array($registry)) {
        update_option('vanterra_forms_registry', $registry);
    }
}

function vanterra_forms_get_attribution_keys()
{
    $tracking_params = array(
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'utm_adgroup',
        'gclid',
        'msclkid',
        'fbclid',
        'ttclid',
        'yclid',
        'li_fat_id',
        'gbraid',
        'wbraid',
        'ga_client_id',
    );
    $fixed = array('landing_page', 'referrer', 'touch_time', 'client_id', 'session_id');

    $keys = array();
    // top-level values that may exist (client_id via vt_client_id cookie)
    $keys[] = 'client_id';
    // first/last variants
    foreach (array_merge($tracking_params, $fixed) as $k) {
        $keys[] = 'first_' . $k;
        $keys[] = 'last_' . $k;
    }
    // first touch marker
    $keys[] = 'first_touch_time';

    return $keys;
}

// Define multiple form schemas (filterable)
function vanterra_forms_get_schemas()
{
    $hidden = array();
    foreach (vanterra_forms_get_attribution_keys() as $name) {
        $hidden[] = array(
            'type'       => 'hidden',
            'label'      => $name,
            'adminLabel' => $name,
            'inputName'  => $name,
        );
    }

    // Enhanced field definitions with autocomplete and advanced features
    $enhanced_fields = array(
        'name' => array(
            'id' => 1,
            'type' => 'name',
            'label' => 'Name',
            'required' => true,
            'nameFormat' => 'advanced',
            'inputName' => 'name',
            'enableAutocomplete' => true,
            'inputs' => array(
                array('id' => '1.1', 'isHidden' => true),
                array('id' => '1.2', 'isHidden' => true),
                array('id' => '1.3', 'label' => 'First', 'autocompleteAttribute' => 'given-name'),
                array('id' => '1.4', 'isHidden' => true),
                array('id' => '1.5', 'isHidden' => true),
                array('id' => '1.6', 'label' => 'Last', 'autocompleteAttribute' => 'family-name'),
                array('id' => '1.7', 'isHidden' => true),
                array('id' => '1.8', 'isHidden' => true),
            )
        ),
        'email' => array(
            'id' => 2,
            'type' => 'email',
            'label' => 'Email',
            'required' => true,
            'inputName' => 'email',
            'autocompleteAttribute' => 'email',
            'enableAutocomplete' => true,
        ),
        'phone' => array(
            'id' => 3,
            'type' => 'phone',
            'label' => 'Phone',
            'required' => true,
            'inputName' => 'phone',
            'phoneFormat' => 'standard',
            'autocompleteAttribute' => 'tel',
            'enableAutocomplete' => true,
        ),
        'address' => array(
            'id' => 4,
            'type' => 'address',
            'label' => 'Address',
            'required' => true,
            'inputName' => 'address',
            'addressType' => 'us',
            'enableAutocomplete' => true,
            'gpaaEnable' => true,
            'inputs' => array(
                array('id' => '4.1', 'label' => 'Street Address', 'autocompleteAttribute' => 'address-line1 street-address'),
                array('id' => '4.2', 'label' => 'Address 2', 'autocompleteAttribute' => 'address-line2', 'isHidden' => true),
                array('id' => '4.3', 'label' => 'City', 'autocompleteAttribute' => 'address-level2'),
                array('id' => '4.4', 'label' => 'State', 'autocompleteAttribute' => 'address-level1'),
                array('id' => '4.5', 'label' => 'ZIP', 'autocompleteAttribute' => 'postal-code'),
                array('id' => '4.6', 'label' => 'Country', 'autocompleteAttribute' => 'country-name', 'isHidden' => true)
            )
        ),
        'service' => array(
            'id' => 5,
            'type' => 'select',
            'label' => 'Service',
            'required' => true,
            'inputName' => 'service',
            'choices' => array(
                array('text' => 'Foundation Repair', 'value' => 'foundation_repair'),
                array('text' => 'Basement Waterproofing', 'value' => 'basement_waterproofing'),
                array('text' => 'Crawl Space Encapsulation', 'value' => 'crawl_space'),
                array('text' => 'Concrete Lifting', 'value' => 'concrete_lifting'),
                array('text' => 'Other', 'value' => 'other')
            )
        ),
        'message' => array(
            'id' => 6,
            'type' => 'textarea',
            'label' => 'Tell us about your issue',
            'required' => false,
            'inputName' => 'message',
            'placeholder' => "Briefly describe what you're seeing…",
            'useRichTextEditor' => false,
        ),
        'turnstile' => array(
            'id' => 7,
            'type' => 'turnstile',
            'label' => 'Verify you are a human', 
            'displayOnly' => true,
        ),
        'email_optional' => array(
            'id' => 2,
            'type' => 'email',
            'label' => 'Email',
            'required' => false,
            'inputName' => 'email',
            'autocompleteAttribute' => 'email',
            'enableAutocomplete' => true,
        )
    );

    $base_contact = array(
        $enhanced_fields['name'],
        $enhanced_fields['email'],
        $enhanced_fields['phone'],
    );

    $schemas = array(
        'lead_full' => array(
            'slug' => 'lead_full',
            'version' => 2,
            'title' => 'Vanterra Lead Form',
            'fields' => array_merge(
                $base_contact,
                array(
                    $enhanced_fields['address'],
                    $enhanced_fields['service'],
                    $enhanced_fields['message'],
                    $enhanced_fields['turnstile'],
                ),
                $hidden  
            ),
        ),
        'lead_short' => array(
            'slug' => 'lead_short',
            'version' => 2,
            'title' => 'Vanterra Short Lead Form',
            'fields' => array_merge(array(
                $enhanced_fields['name'],
                $enhanced_fields['phone'],
                $enhanced_fields['email'],
                $enhanced_fields['service'],
                $enhanced_fields['turnstile'],
            ), $hidden),
        ),
        'lead_sms' => array(
            'slug' => 'lead_sms',
            'version' => 2,
            'title' => 'Vanterra SMS Lead Form',
            'fields' => array_merge(array(
                $enhanced_fields['phone'], 
                $enhanced_fields['message'],
                $enhanced_fields['turnstile'],
            ), $hidden),
        ),


        'lead_full_multi' => array(
            'slug' => 'lead_full_multi',
            'version' => 2,
            'title' => 'Vanterra Multistep Lead Form',
            'fields' => array_merge(
                array(
                    // Page 1: Address
                    $enhanced_fields['address'],
                    // Page 2: Contact
                    array(
                        'id' => 88,
                        'type' => 'page',
                        'title' => 'Contact',
                        'displayOnly' => true,
                        'nextButton' => array('type' => 'text', 'text' => 'Next', 'imageUrl' => '', 'id' => 88),
                        'previousButton' => array('type' => 'text', 'text' => 'Previous', 'imageUrl' => ''),
                        'layoutGroupId' => 'page88'
                    ),
                    $enhanced_fields['name'],
                    $enhanced_fields['phone'],
                    $enhanced_fields['email'],
                    // Page 3: Message
                    array(
                        'id' => 99,
                        'type' => 'page',
                        'title' => 'Message',
                        'displayOnly' => true,
                        'nextButton' => array('type' => 'text', 'text' => 'Next', 'imageUrl' => '', 'id' => 99),
                        'previousButton' => array('type' => 'text', 'text' => 'Previous', 'imageUrl' => ''),
                        'layoutGroupId' => 'page89'
                    ),
                    $enhanced_fields['service'],
                    $enhanced_fields['message'],
                    $enhanced_fields['turnstile'],
                    $hidden,
                ),
            ),
            'pagination' => array(
                'type' => 'steps',
                'pages' => array('Address', 'Contact', 'Message'),
                'gp-multi-page-navigation_enable' => true,
                'gp-multi-page-navigation_activation_type' => 'progression'
            ), 
            'lastPageButton' => array(
                'type' => 'text',
                'text' => 'Previous',
                'imageUrl' => ''
            ),
            'firstPageCssClass' => '',
            'markupVersion' => 2,
            'nextFieldId' => 11
        ),
    );

    return apply_filters('vanterra_forms_schemas', $schemas);
}

function vanterra_forms_maybe_create_gf_form()
{
    if (! class_exists('GFAPI')) {
        return; // Gravity Forms not active
    }

    $registry = vanterra_forms_get_registry();
    $schemas = vanterra_forms_get_schemas();

    foreach ($schemas as $slug => $schema) {
        $target_title = $schema['title'];
        $schema_version = (int) $schema['version'];
        $existing_id = isset($registry[$slug]['form_id']) ? (int) $registry[$slug]['form_id'] : 0;
        if ($existing_id) {
            $form = GFAPI::get_form($existing_id);
            if ($form && ! is_wp_error($form)) {
                $registry[$slug] = array('form_id' => (int) $existing_id, 'version' => $schema_version);
                continue;
            }
        }

        // find by title to avoid duplicates
        $found_id = 0;
        $forms = GFAPI::get_forms();
        foreach ($forms as $f) {
            if (isset($f['title']) && $f['title'] === $target_title) {
                $found_id = (int) $f['id'];
                break;
            }
        }
        if ($found_id) {
            $registry[$slug] = array('form_id' => $found_id, 'version' => $schema_version);
            continue;
        }

        // create new form from schema
        $form = array(
            'title'       => $target_title,
            'description' => 'Auto-created by Vanterra Forms plugin.',
            'fields'      => $schema['fields'],
        );
        $result = GFAPI::add_form($form);
        if (! is_wp_error($result) && $result) {
            $registry[$slug] = array('form_id' => (int) $result, 'version' => $schema_version);
        }
    }

    vanterra_forms_set_registry($registry);
}

// Render shortcode for the known form without hardcoding numeric ID
function vanterra_forms_shortcode_form($atts = array())
{
    if (! class_exists('GFAPI')) return '';
    $atts = shortcode_atts(array('slug' => 'attribution'), $atts, 'vanterra_form');
    $registry = vanterra_forms_get_registry();
    $form_id = isset($registry[$atts['slug']]['form_id']) ? (int) $registry[$atts['slug']]['form_id'] : (int) get_option('vanterra_forms_gf_form_id', 0);
    if (! $form_id) return '';
    if (function_exists('gravity_form')) {
        ob_start();
        gravity_form($form_id, false, false, false, null, true, 1);
        return ob_get_clean();
    }
    return '';
}
add_shortcode('vanterra_form', 'vanterra_forms_shortcode_form');

// Send webhook on submission (only for our form)
function vanterra_forms_gform_after_submission($entry, $form)
{
    $enabled = (int) get_option('vanterra_forms_webhook_enabled', 0);
    $url     = trim(get_option('vanterra_forms_webhook_url', ''));
    if (! $enabled || empty($url)) return;

    $registry = vanterra_forms_get_registry();
    $target_ids = array();
    foreach ($registry as $slug => $rec) {
        if (isset($rec['form_id'])) $target_ids[] = (int) $rec['form_id'];
    }
    if (empty($target_ids) || ! in_array((int) rgar($form, 'id'), $target_ids, true)) return;

    // Build normalized map keyed by inputName (and parts for composites)
    $normalized = array();
    if (is_array(rgar($form, 'fields'))) {
        foreach ($form['fields'] as $field) {
            $type = isset($field->type) ? $field->type : (isset($field['type']) ? $field['type'] : '');
            $inputName = isset($field->inputName) ? $field->inputName : (isset($field['inputName']) ? $field['inputName'] : '');
            $id = isset($field->id) ? $field->id : (isset($field['id']) ? $field['id'] : null);
            if (! $id) continue;

            if ($type === 'name') {
                $normalized['first_name'] = rgar($entry, (string) ($id . '.3'));
                $normalized['last_name']  = rgar($entry, (string) ($id . '.6'));
            } else if ($type === 'address') {
                $normalized['address_line1']   = rgar($entry, (string) ($id . '.1'));
                $normalized['address_line2']   = rgar($entry, (string) ($id . '.2'));
                $normalized['address_city']    = rgar($entry, (string) ($id . '.3'));
                $normalized['address_state']   = rgar($entry, (string) ($id . '.4'));
                $normalized['address_zip']     = rgar($entry, (string) ($id . '.5'));
                $normalized['address_country'] = rgar($entry, (string) ($id . '.6'));
            } else if ($inputName) {
                $normalized[$inputName] = rgar($entry, (string) $id);
            }
        }
    }

    $payload = array(
        'form_id' => (int) rgar($form, 'id'),
        'form_title' => rgar($form, 'title'),
        'entry_id' => (int) rgar($entry, 'id'),
        'submitted_at' => current_time('mysql', true),
        'fields' => $entry,
        'normalized' => $normalized,
    );

    wp_remote_post($url, array(
        'timeout' => 8,
        'headers' => array('Content-Type' => 'application/json'),
        'body'    => wp_json_encode($payload),
    ));
}
add_action('gform_after_submission', 'vanterra_forms_gform_after_submission', 10, 2);


/**
 * Gravity Perks // Address Autocomplete // Trim US Zip Codes to First 5 Digits
 * https://gravitywiz.com/documentation/gravity-forms-address-autocomplete/
 *
 * Instructions:
 *
 * 1. Install this snippet with our free Custom JavaScript plugin.
 *    https://gravitywiz.com/gravity-forms-code-chest/
 */
// gform.addFilter( 'gpaa_values', function( values, place ) {
// 	values.postcode = values.postcode.split( '-' )[0];
// 	return values;
// } );