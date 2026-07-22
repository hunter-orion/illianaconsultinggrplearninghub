<?php

function illiana_asset_manifest()
{
    static $manifest = null;
    if ($manifest !== null) return $manifest;

    $path = get_theme_file_path('/build/asset-manifest.json');
    $manifest = file_exists($path)
        ? (json_decode(file_get_contents($path), true) ?: [])
        : [];
    return $manifest;
}

// Enqueue a webpack entry by its key in webpack.config.js.
// Reads build/asset-manifest.json so content-hashed filenames resolve in production.
function illiana_enqueue_entry($entry, $deps = [])
{
    $manifest = illiana_asset_manifest();
    $handle   = 'illiana-' . $entry;

    if (isset($manifest[$entry . '.js'])) {
        wp_enqueue_script(
            $handle,
            get_theme_file_uri('/build/' . $manifest[$entry . '.js']),
            $deps,
            null,
            true
        );
    }
    if (isset($manifest[$entry . '.css'])) {
        wp_enqueue_style(
            $handle,
            get_theme_file_uri('/build/' . $manifest[$entry . '.css']),
            [],
            null
        );
    }
    return $handle;
}

function illiana_files()
{
    // Theme stylesheet (holds the per-page font-size floors). Versioned with the
    // file mtime so edits bust the browser cache instead of serving a stale copy.
    $style_path = get_theme_file_path('/style.css');
    wp_enqueue_style(
        'illiana_main_styles',
        get_stylesheet_uri(),
        [],
        file_exists($style_path) ? filemtime($style_path) : null
    );

    // Third-party libs used across the site.


    // ---------------------------------------------------------------
    // Icons are inline SVG (see inc/icons.php / illiana_icon()) — no
    // icon-font or icon-script CDN requests needed on any page.
    // ---------------------------------------------------------------
    // Per-page bundles. Each page loads exactly ONE entry from /build.
    // Map page → webpack entry name (defined in webpack.config.js).
    // ---------------------------------------------------------------
    $main_handle = null;


        if (is_home()) {
        $main_handle = illiana_enqueue_entry('home-v2', []);
    }

    // Workforce / training pages → training bundle
    if (!is_home()) {
        $main_handle = illiana_enqueue_entry('training-v2', []);
    }

    // Localize once for whichever entry actually got enqueued.
    if ($main_handle && wp_script_is($main_handle, 'enqueued')) {
        wp_localize_script($main_handle, 'illianaData', [
            'root_url' => get_site_url(),
            'nonce'    => wp_create_nonce('wp_rest'),
        ]);
        wp_localize_script($main_handle, 'themeData', [
            'themeDirectoryUri' => get_theme_file_uri(),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'illiana_files');


function illiana_features()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
}

add_action('after_setup_theme', 'illiana_features');

// ---------------------------------------------------------------
// Contact form (page-contact.php) — server-side submit handler.
// Posts to admin-post.php with action "illiana_contact", emails the
// site admin, then redirects back to /contact with a status flag.
// ---------------------------------------------------------------


function illiana_remove_default_image_sizes($sizes)
{
    unset($sizes['thumbnail']);     // 150x150
    unset($sizes['medium']);        // 300x300
    unset($sizes['medium_large']);  // 768px
    unset($sizes['large']);         // 1024px
    unset($sizes['1536x1536']);     // WP 5.3+
    unset($sizes['2048x2048']);     // WP 5.3+
    return $sizes;
}
add_filter('intermediate_image_sizes_advanced', 'illiana_remove_default_image_sizes');


add_filter('xmlrpc_enabled', '__return_false');

add_filter('rest_endpoints', function( $endpoints ) {
    if ( isset( $endpoints['/wp/v2/users'] ) ) {
        unset( $endpoints['/wp/v2/users'] );
    }
    if ( isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {
        unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
    }
    return $endpoints;
});