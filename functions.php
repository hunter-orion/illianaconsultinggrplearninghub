<?php
// Course tabs Gutenberg block (illiana/course-panels): registration, render
// callback, and the discussion-thread data it pulls in.
require_once get_theme_file_path( '/inc/courseTabs.php' );

// Reusable "all courses" catalog grid (front-page.php + page-registration.php).
require_once get_theme_file_path( '/inc/courseCatalog.php' );

// "Access Code" custom post type (wp-admin UI for creating/managing codes).
// Loaded before registration.php since illiana_redeem_access_code() there
// queries this post type.
require_once get_theme_file_path( '/inc/accessCodes.php' );

// Registration page form handlers (signup, profile edit, code redemption,
// password reset) — see page-registration.php.
require_once get_theme_file_path( '/inc/registration.php' );

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

    // Needed for the Discussion tab's reply box, which posts new comments
    // via the core REST API (POST /wp/v2/comments) from courses-frontend.js.
    // userName is used for the optimistic UI append after posting, instead
    // of trusting the REST response's author_name — that field is just
    // WP_Comment's stored comment_author (display_name at post time), which
    // on this site defaults to the user's login email. See
    // illiana_get_user_display_name().
    wp_localize_script( 'illiana-courses-frontend', 'illianaCoursesData', array(
        'restUrl'  => esc_url_raw( rest_url( 'wp/v2/' ) ),
        'nonce'    => wp_create_nonce( 'wp_rest' ),
        'userName' => is_user_logged_in() ? illiana_get_user_display_name( wp_get_current_user() ) : '',
    ) );
}
add_action( 'wp_enqueue_scripts', 'illiana_enqueue_course_frontend_assets' );

// Same "prefer first name over display name" preference already used in
// front-page.php — display_name on this site tends to default to the
// user's login email, which isn't fit to show publicly on comments.
function illiana_get_user_display_name( $user ) {
    if ( ! $user instanceof WP_User ) {
        return '';
    }
    return $user->first_name ?: $user->display_name;
}

// Two-letter avatar badge for the header's user menu — first + last initial,
// falling back to just the first initial (or the first letter of display_name)
// when last_name isn't set.
function illiana_get_user_initials( $user ) {
    if ( ! $user instanceof WP_User ) {
        return '';
    }

    $first = mb_substr( $user->first_name, 0, 1 );
    $last  = mb_substr( $user->last_name, 0, 1 );

    if ( $first && $last ) {
        return mb_strtoupper( $first . $last );
    }
    if ( $first ) {
        return mb_strtoupper( $first );
    }

    return mb_strtoupper( mb_substr( $user->display_name, 0, 1 ) );
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

// Disable public self-registration. The option filter covers register_new_user()'s
// own check (used by wp-login.php and any plugin that respects it); the login_init
// redirect is defense-in-depth for anyone hitting wp-login.php?action=register directly.
add_filter( 'option_users_can_register', '__return_false' );

function illiana_block_registration_page() {
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'register' ) {
        wp_safe_redirect( home_url( '/' ) );
        exit;
    }
}
add_action( 'login_init', 'illiana_block_registration_page' );

// Subscribers (and any other role without real dashboard access) have no
// reason to see wp-admin — send them to the homepage instead. Gated on
// edit_posts rather than the "subscriber" role by name so it also covers
// any other low-privilege/custom role, and skips AJAX/REST/admin-post.php
// requests since those route through wp-admin too but aren't actual
// dashboard page loads — subscribers legitimately POST to admin-post.php
// from the registration page (see inc/registration.php).
function illiana_redirect_subscribers_from_admin() {
    global $pagenow;

    if ( ! is_admin() || wp_doing_ajax() || 'admin-post.php' === $pagenow ) {
        return;
    }

    if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) ) {
        wp_safe_redirect( home_url( '/' ) );
        exit;
    }
}
add_action( 'admin_init', 'illiana_redirect_subscribers_from_admin' );

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

// One-time migration: enabling "comments" support on the sfwd-lessons CPT
// only affects lessons created from now on — WordPress sets comment_status
// per-post at creation time, so every lesson that already existed is still
// closed and returns rest_comment_closed from the Discussion tab's reply
// box. This reopens all of them, once, then gets out of the way for good.
// Safe to delete this function (and the add_action call) once confirmed.
function illiana_reopen_lesson_comments_once() {
    if ( get_option( 'illiana_lesson_comments_reopened' ) ) {
        return;
    }

    $lesson_ids = get_posts( array(
        'post_type'      => 'sfwd-lessons',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ) );

    foreach ( $lesson_ids as $lesson_id ) {
        wp_update_post( array(
            'ID'             => $lesson_id,
            'comment_status' => 'open',
        ) );
    }

    update_option( 'illiana_lesson_comments_reopened', 1 );
}
add_action( 'init', 'illiana_reopen_lesson_comments_once' );

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

