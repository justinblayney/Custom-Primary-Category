<?php
/*
Plugin Name: Custom Primary Category for Permalinks
Plugin URI: https://github.com/justinblayney
Description: Allows manual selection of a primary category to use in post permalinks (replaces %category%).
Version: 1.1.1
Author: Justin Blayney
Author URI: https://www.darkstarmedia.net
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: custom-primary-category
Domain Path: /languages
*/

if (!defined('ABSPATH')) exit;

function cpc_load_textdomain()
{
    load_plugin_textdomain('custom-primary-category', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'cpc_load_textdomain');

function enqueue_custom_category_script($hook)
{
    if ('post.php' !== $hook && 'post-new.php' !== $hook) {
        return;
    }

    wp_enqueue_script('custom-primary-category-js', plugin_dir_url(__FILE__) . 'js/cpc-admin.js', array(), '1.1.1', true);
}
add_action('admin_enqueue_scripts', 'enqueue_custom_category_script');



function cpc_add_meta_box()
{
    add_meta_box(
        'cpc_primary_category',
        __('Primary Category for URL', 'custom-primary-category'),
        'cpc_meta_box_callback',
        'post',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'cpc_add_meta_box');

function cpc_meta_box_callback($post)
{
    $selected = get_post_meta($post->ID, '_cpc_primary_category', true);
    $categories = get_the_category($post->ID);

    if (empty($categories)) {
        echo esc_html__('No categories assigned to this post.', 'custom-primary-category');
        return;
    }

    echo '<label for="cpc_primary_category_select">' . esc_html__('Primary Category for URL', 'custom-primary-category') . '</label>';
    echo '<select name="cpc_primary_category" id="cpc_primary_category_select">';
    echo '<option value="">' . esc_html__('— Select Primary Category —', 'custom-primary-category') . '</option>';

    foreach ($categories as $category) {
        printf(
            '<option value="%s"%s>%s</option>',
            esc_attr($category->term_id),
            selected($selected, $category->term_id, false),
            esc_html($category->name)
        );
    }

    echo '</select>';

    wp_nonce_field('cpc_save_primary_category', 'cpc_primary_category_nonce');
}

function cpc_save_primary_category($post_id)
{
    if (
        !isset($_POST['cpc_primary_category_nonce']) ||
        !wp_verify_nonce($_POST['cpc_primary_category_nonce'], 'cpc_save_primary_category')
    ) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if (isset($_POST['cpc_primary_category'])) {
        update_post_meta($post_id, '_cpc_primary_category', sanitize_text_field($_POST['cpc_primary_category']));
    }
}
add_action('save_post', 'cpc_save_primary_category');

function cpc_add_rewrite_rules($post_id)
{
    if (get_post_type($post_id) !== 'post' || get_post_status($post_id) !== 'publish') {
        return;
    }

    $post = get_post($post_id);
    $slug = $post->post_name;

    $primary_term_id = get_post_meta($post_id, '_cpc_primary_category', true);
    if ($primary_term_id) {
        $term = get_term($primary_term_id, 'category');
        if (!is_wp_error($term) && $term && $term->slug) {
            add_rewrite_rule(
                '^' . $term->slug . '/' . $slug . '/?$',
                'index.php?name=' . $slug,
                'top'
            );
        }
    }
}
add_action('save_post', 'cpc_add_rewrite_rules');



function cpc_use_primary_category_in_permalink($permalink, $post, $leavename)
{
    if (strpos($permalink, '%category%') === false || $post->post_type !== 'post') {
        return $permalink;
    }

    $primary_term_id = get_post_meta($post->ID, '_cpc_primary_category', true);
    if ($primary_term_id) {
        $term = get_term($primary_term_id, 'category');
        if (!is_wp_error($term) && $term && $term->slug) {
            return str_replace('%category%', $term->slug, $permalink);
        }
    }

    $categories = get_the_category($post->ID);
    if (!empty($categories)) {
        return str_replace('%category%', $categories[0]->slug, $permalink);
    }

    return str_replace('%category%', 'uncategorized', $permalink);
}
add_filter('post_link', 'cpc_use_primary_category_in_permalink', 10, 3);
add_filter('pre_post_link', 'cpc_use_primary_category_in_permalink', 10, 3);

function cpc_flush_rewrite_rules()
{
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'cpc_flush_rewrite_rules');
register_deactivation_hook(__FILE__, 'cpc_flush_rewrite_rules');
