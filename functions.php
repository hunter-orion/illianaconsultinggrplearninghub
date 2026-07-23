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
    // Single sitewide bundle from /build (src/index.js).
    // ---------------------------------------------------------------
    $main_handle = illiana_enqueue_entry('home-v2', []);

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

function custom_theme_learndash_support() {
    add_theme_support( 'learndash' );
}
add_action( 'after_setup_theme', 'custom_theme_learndash_support' );

// Course pages render their own layout via single-sfwd-courses.php and expect
// the_content() to return the raw block content. Without this, LearnDash's
// `the_content` filter (SFWD_CPT_Instance::template_content()) replaces it
// with the full ld30 course shell (info bar, progress bar, [course_content]
// shortcode) wrapped in .learndash-wrapper/.learndash-shortcode-wrap markup.
function illiana_disable_learndash_course_content_filter() {
    global $sfwd_lms;

    if ( is_singular( 'sfwd-courses' ) && isset( $sfwd_lms->post_types['sfwd-courses'] ) ) {
        $sfwd_lms->post_types['sfwd-courses']->content_filter_control( false );
    }
}
add_action( 'wp', 'illiana_disable_learndash_course_content_filter' );

// Registers the course-content Gutenberg blocks (editor-only bundle built
// from src/courses-admin.js). Dependencies + version come from the
// courses-admin.asset.php file DependencyExtractionWebpackPlugin generates.
function illiana_enqueue_course_blocks_editor_assets() {
    $asset_path = get_theme_file_path( '/build/courses-admin.asset.php' );

    if ( ! file_exists( $asset_path ) ) {
        return;
    }

    $asset = include $asset_path;

    wp_enqueue_script(
        'illiana-course-blocks',
        get_theme_file_uri( '/build/courses-admin.js' ),
        $asset['dependencies'],
        $asset['version'],
        true
    );
}
add_action( 'enqueue_block_editor_assets', 'illiana_enqueue_course_blocks_editor_assets' );

// Frontend JS/CSS for the course page (src/courses-frontend.js, which pulls
// in css/courses.scss) — only loaded on single course pages, not sitewide.
// The JS side renders React (createRoot), so — unlike the editor, which
// always has React loaded regardless — this page needs the real
// react/react-dom/etc. script handles declared as dependencies, which is
// why this reads courses-frontend.asset.php instead of using the plain
// manifest-based illiana_enqueue_entry() helper for the JS half.
function illiana_enqueue_course_frontend_assets() {
    if ( ! is_singular( 'sfwd-courses' ) ) {
        return;
    }

    $manifest = illiana_asset_manifest();
    if ( isset( $manifest['courses-frontend.css'] ) ) {
        wp_enqueue_style(
            'illiana-courses-frontend',
            get_theme_file_uri( '/build/' . $manifest['courses-frontend.css'] ),
            [],
            null
        );
    }

    $asset_path = get_theme_file_path( '/build/courses-frontend.asset.php' );
    if ( ! file_exists( $asset_path ) ) {
        return;
    }

    $asset = include $asset_path;

    wp_enqueue_script(
        'illiana-courses-frontend',
        get_theme_file_uri( '/build/courses-frontend.js' ),
        $asset['dependencies'],
        $asset['version'],
        true
    );
}
add_action( 'wp_enqueue_scripts', 'illiana_enqueue_course_frontend_assets' );