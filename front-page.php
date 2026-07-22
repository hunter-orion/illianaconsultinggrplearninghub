<?php
get_header();

$user_id      = get_current_user_id();
$is_logged_in = is_user_logged_in();
$current_user = $is_logged_in ? wp_get_current_user() : null;
$display_name = $current_user ? ( $current_user->first_name ?: $current_user->display_name ) : '';

// "Continue Learning" card — the course the user most recently interacted with.
$continue = null;

if ( $is_logged_in && function_exists( 'learndash_get_last_active_course' ) ) {
    $last_course_id = learndash_get_last_active_course( $user_id );

    if ( $last_course_id ) {
        $progress = learndash_course_progress( array(
            'user_id'   => $user_id,
            'course_id' => $last_course_id,
            'array'     => true,
        ) );

        $activity = learndash_get_user_activity( array(
            'course_id'     => $last_course_id,
            'user_id'       => $user_id,
            'post_id'       => $last_course_id,
            'activity_type' => 'course',
        ) );

        $days_ago = ! empty( $activity->activity_updated )
            ? (int) floor( ( time() - (int) $activity->activity_updated ) / DAY_IN_SECONDS )
            : null;

        $resume_step_id = learndash_user_progress_get_first_incomplete_step( $user_id, $last_course_id );
        $resume_url      = $resume_step_id
            ? learndash_get_step_permalink( $resume_step_id, $last_course_id )
            : get_permalink( $last_course_id );

        $continue = array(
            'title'     => get_the_title( $last_course_id ),
            'step'      => $resume_step_id ? get_the_title( $resume_step_id ) : '',
            'completed' => (int) $progress['completed'],
            'total'     => (int) $progress['total'],
            'percent'   => (int) $progress['percentage'],
            'days_ago'  => $days_ago,
            'url'       => $resume_url,
        );
    }
}

// All published courses, flagged with the current user's enrollment status.
$all_courses      = get_posts( array(
    'post_type'      => 'sfwd-courses',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'menu_order title',
    'order'          => 'ASC',
) );
$can_check_access = function_exists( 'sfwd_lms_has_access' ) && function_exists( 'learndash_course_progress' );
?>

<main id="main">

<section class="hub-hero">
    <div class="hub-eyebrow">Your Learning Hub</div>
    <?php if ( $is_logged_in ) : ?>
        <h1>Welcome back, <?php echo esc_html( $display_name ); ?>.</h1>
        <p class="hub-hero-sub">Pick up where you left off, or explore a certification track you haven't started yet.</p>
    <?php else : ?>
        <h1>Welcome to your Learning Hub</h1>
        <p class="hub-hero-sub">Log in to track your progress, or explore our certification tracks below.</p>
    <?php endif; ?>
</section>

<?php if ( $continue ) : ?>
<section class="hub-continue">
    <div class="hub-continue-inner">
        <div class="hub-continue-text">
            <div class="hub-label">Continue Learning</div>
            <h2><?php echo esc_html( $continue['title'] ); ?></h2>
            <div class="hub-meta">
                <?php if ( $continue['step'] ) : ?>
                    <?php echo esc_html( $continue['step'] ); ?> ·
                <?php endif; ?>
                Module <?php echo esc_html( $continue['completed'] ); ?> of <?php echo esc_html( $continue['total'] ); ?>
                <?php if ( null !== $continue['days_ago'] ) : ?>
                    · Last activity <?php echo esc_html( $continue['days_ago'] ); ?> day<?php echo 1 === $continue['days_ago'] ? '' : 's'; ?> ago
                <?php endif; ?>
            </div>
            <div class="hub-progress-track">
                <div class="hub-progress-fill" style="width: <?php echo esc_attr( $continue['percent'] ); ?>%;"></div>
            </div>
        </div>
        <a href="<?php echo esc_url( $continue['url'] ); ?>" class="hub-btn">Resume Course →</a>
    </div>
</section>
<?php endif; ?>

<section class="hub-programs">
    <h3>Your Programs</h3>
    <div class="hub-grid">
        <?php foreach ( $all_courses as $course ) :
            $course_id = $course->ID;
            $enrolled  = $is_logged_in && $can_check_access && sfwd_lms_has_access( $course_id, $user_id );

            $status_label = 'Not Enrolled';
            $status_class = '';

            if ( $enrolled ) {
                $course_progress = learndash_course_progress( array(
                    'user_id'   => $user_id,
                    'course_id' => $course_id,
                    'array'     => true,
                ) );
                if ( 100 <= (int) $course_progress['percentage'] ) {
                    $status_label = 'Completed';
                } elseif ( 0 < (int) $course_progress['percentage'] ) {
                    $status_label = 'In Progress';
                    $status_class = 'active';
                } else {
                    $status_label = 'Enrolled';
                }
            }

            $excerpt = get_field( 'course_description', $course_id );
        ?>
        <div class="hub-card<?php echo $enrolled ? '' : ' locked'; ?>">
            <div class="hub-card-art">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                <span class="hub-status<?php echo $status_class ? ' ' . esc_attr( $status_class ) : ''; ?>"><?php echo esc_html( $status_label ); ?></span>
            </div>
            <div class="hub-card-body">
                <h4><?php echo esc_html( get_the_title( $course_id ) ); ?></h4>
                <?php if ( $excerpt ) : ?>
                    <div class="hub-card-desc text-[#AA7EDD]"><?php echo esc_html( $excerpt ); ?></div>
                <?php endif; ?>
                <a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>" class="hub-btn hub-btn-outline">
                    <?php echo $enrolled ? 'Enter Course' : 'View Program'; ?> →
                </a>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if ( ! $all_courses ) : ?>
            <p>No courses have been published yet.</p>
        <?php endif; ?>
    </div>
</section>

</main>

<?php
get_footer();
