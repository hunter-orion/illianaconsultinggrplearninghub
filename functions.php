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

// Registers the course-panels Gutenberg block. It's a dynamic block:
// save() returns null (attributes are stored directly in the block comment),
// and render_callback outputs the tab-bar shell plus, for each tab, a small
// PHP mount shell — the tab's slice of attributes as a JSON blob inside a
// hidden <pre> — which courses-frontend.js then hydrates into the real
// visual output via React. Discussion is the one exception: no attributes,
// no mount point, just the static demo markup from the mockup, since
// comments aren't built yet. render_callback has to be registered on every
// request (not just the editor), which is why this runs on 'init' rather
// than 'enqueue_block_editor_assets'.
function illiana_register_course_blocks() {
    $asset_path = get_theme_file_path( '/build/courses-admin.asset.php' );

    if ( ! file_exists( $asset_path ) ) {
        return;
    }

    $asset = include $asset_path;

    wp_register_script(
        'illiana-course-blocks',
        get_theme_file_uri( '/build/courses-admin.js' ),
        $asset['dependencies'],
        $asset['version'],
        true
    );

    register_block_type( 'illiana/course-panels', array(
        'editor_script'   => 'illiana-course-blocks',
        'render_callback' => 'illiana_render_course_panels_block',
        'attributes'      => array(
            'announcements'      => array( 'type' => 'array', 'default' => array() ),
            'howItWorks'         => array( 'type' => 'string', 'default' => '' ),
            'instructorName'     => array( 'type' => 'string', 'default' => '' ),
            'instructorInitials' => array( 'type' => 'string', 'default' => '' ),
            'instructorCred'     => array( 'type' => 'string', 'default' => '' ),
            'instructorBio'      => array( 'type' => 'string', 'default' => '' ),
            'completeRequires'   => array( 'type' => 'array', 'default' => array() ),
            'timeCommitment'     => array( 'type' => 'string', 'default' => '' ),
            'needHelp'           => array( 'type' => 'string', 'default' => '' ),
            'dates'              => array( 'type' => 'array', 'default' => array() ),
            'templates'          => array( 'type' => 'array', 'default' => array() ),
        ),
    ) );
}
add_action( 'init', 'illiana_register_course_blocks' );

// Outputs a courses-frontend.js mount point: attribute data as a hidden JSON
// blob, keyed to a block name courses-frontend.js's blockComponents map
// knows how to hydrate. Deliberately no visible markup here — that's
// React's job on the frontend.
function illiana_course_block_mount( $block_name, $data ) {
    ob_start();
    ?>
    <div class="courses-block-mount" data-courses-block="<?php echo esc_attr( $block_name ); ?>">
        <pre style="display:none;"><?php echo wp_json_encode( $data ); ?></pre>
    </div>
    <?php
    return ob_get_clean();
}

// One discussion thread per lesson, backed by real WordPress comments on
// the lesson post (now that comments are enabled for sfwd-lessons). Each
// comment is flagged with whether it belongs to the current user, so the
// frontend can show "you participated" without guessing off author name.
function illiana_get_course_discussion_threads( $course_id ) {
    $lessons     = learndash_get_course_lessons_list( $course_id );
    $current_uid = get_current_user_id();
    $threads     = array();

    foreach ( $lessons as $lesson ) {
        $lesson_id = $lesson['post']->ID;

        $comments = get_comments( array(
            'post_id' => $lesson_id,
            'status'  => 'approve',
            'type'    => 'comment',
            'order'   => 'ASC',
        ) );

        $participated = false;
        $comment_data = array();

        foreach ( $comments as $comment ) {
            $is_mine = $current_uid && (int) $comment->user_id === $current_uid;
            if ( $is_mine ) {
                $participated = true;
            }

            // comment_author is whatever display_name was at post time —
            // on this site that tends to be the login email. Re-resolve
            // from the WP_User record so it shows a real name instead.
            $comment_user = $comment->user_id ? get_userdata( $comment->user_id ) : false;
            $author_name  = $comment_user ? illiana_get_user_display_name( $comment_user ) : $comment->comment_author;

            $comment_data[] = array(
                'id'      => (int) $comment->comment_ID,
                'author'  => $author_name,
                'content' => $comment->comment_content,
                'time'    => human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp' ) ) . ' ago',
                'isMine'  => $is_mine,
            );
        }

        $threads[] = array(
            'lessonId'     => $lesson_id,
            'title'        => get_the_title( $lesson_id ),
            'commentCount' => count( $comment_data ),
            'participated' => $participated,
            'comments'     => $comment_data,
        );
    }

    return $threads;
}

function illiana_render_course_panels_block( $attributes ) {
    $course_id = get_the_ID();
    ob_start();
    ?>
    <div class="tab-bar">
        <div class="tab active" onclick="showTab('announcements', this)">Announcements</div>
        <div class="tab" onclick="showTab('syllabus', this)">Syllabus</div>
        <div class="tab" onclick="showTab('dates', this)">Key Dates</div>
        <div class="tab" onclick="showTab('discussion', this)">Discussion</div>
        <div class="tab" onclick="showTab('templates', this)">Templates</div>
    </div>

    <div class="tab-content dark-panel active" id="announcements">
        <div class="panel-inner">
            <?php
            echo illiana_course_block_mount( 'course-announcements', array(
                'announcements' => $attributes['announcements'],
            ) );
            ?>
        </div>
    </div>

    <div class="tab-content light-panel" id="syllabus">
        <div class="panel-inner">
            <?php
            echo illiana_course_block_mount( 'course-syllabus', array(
                'instructorName'     => $attributes['instructorName'],
                'instructorInitials' => $attributes['instructorInitials'],
                'instructorCred'     => $attributes['instructorCred'],
                'instructorBio'      => $attributes['instructorBio'],
                'howItWorks'         => $attributes['howItWorks'],
                'completeRequires'   => $attributes['completeRequires'],
                'timeCommitment'     => $attributes['timeCommitment'],
                'needHelp'           => $attributes['needHelp'],
            ) );
            ?>
        </div>
    </div>

    <div class="tab-content dark-panel" id="dates">
        <div class="panel-inner">
            <?php
            echo illiana_course_block_mount( 'course-dates', array(
                'dates' => $attributes['dates'],
            ) );
            ?>
        </div>
    </div>

    <div class="tab-content light-panel" id="discussion">
        <div class="panel-inner">
            <?php
            echo illiana_course_block_mount( 'course-discussion', array(
                'threads'    => illiana_get_course_discussion_threads( $course_id ),
                'isLoggedIn' => is_user_logged_in(),
            ) );
            ?>
        </div>
    </div>

    <div class="tab-content dark-panel" id="templates">
        <div class="panel-inner">
            <?php
            echo illiana_course_block_mount( 'course-templates', array(
                'templates' => $attributes['templates'],
            ) );
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
