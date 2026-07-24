<?php
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
