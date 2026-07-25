<?php
// Reusable "all courses" catalog — originally the Your Programs grid on
// front-page.php, pulled out here so the registration page (Explore All ICG
// Programs) can render the same grid without duplicating the query/markup.

// All published courses, each flagged with the given user's enrollment
// status. $user_id of 0 (logged-out) marks everything Not Enrolled.
function illiana_get_courses_catalog_data( $user_id = 0 ) {
    $courses = get_posts( array(
        'post_type'      => 'sfwd-courses',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
    ) );

    $can_check_access = $user_id && function_exists( 'sfwd_lms_has_access' ) && function_exists( 'learndash_course_progress' );
    $data             = array();

    foreach ( $courses as $course ) {
        $course_id = $course->ID;
        $enrolled  = $can_check_access && sfwd_lms_has_access( $course_id, $user_id );

        $status_label = 'Not Enrolled';
        $status_class = '';

        if ( $enrolled ) {
            $progress = learndash_course_progress( array(
                'user_id'   => $user_id,
                'course_id' => $course_id,
                'array'     => true,
            ) );

            if ( 100 <= (int) $progress['percentage'] ) {
                $status_label = 'Completed';
            } elseif ( 0 < (int) $progress['percentage'] ) {
                $status_label = 'In Progress';
                $status_class = 'active';
            } else {
                $status_label = 'Enrolled';
            }
        }

        $data[] = array(
            'id'            => $course_id,
            'title'         => get_the_title( $course_id ),
            'excerpt'       => get_field( 'course_description', $course_id ),
            'permalink'     => get_permalink( $course_id ),
            'enrolled'      => $enrolled,
            'status_label'  => $status_label,
            'status_class'  => $status_class,
        );
    }

    return $data;
}

// Renders the .hub-programs/.hub-grid markup (see css/modules/home.scss).
// $args: heading (string), subheading (string|''), user_id (defaults to
// the current user), courses (pre-fetched data, defaults to fetching fresh).
function illiana_render_course_catalog( $args = array() ) {
    $args = wp_parse_args( $args, array(
        'heading'    => 'Your Programs',
        'subheading' => '',
        'user_id'    => get_current_user_id(),
        'courses'    => null,
    ) );

    $courses = null !== $args['courses']
        ? $args['courses']
        : illiana_get_courses_catalog_data( $args['user_id'] );
    ?>
    <section class="hub-programs">
        <h3><?php echo esc_html( $args['heading'] ); ?></h3>
        <?php if ( $args['subheading'] ) : ?>
            <p class="hub-programs-sub"><?php echo esc_html( $args['subheading'] ); ?></p>
        <?php endif; ?>
        <div class="hub-grid">
            <?php foreach ( $courses as $course ) : ?>
                <div class="hub-card<?php echo $course['enrolled'] ? '' : ' locked'; ?>">
                    <div class="hub-card-art">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        <span class="hub-status<?php echo $course['status_class'] ? ' ' . esc_attr( $course['status_class'] ) : ''; ?>"><?php echo esc_html( $course['status_label'] ); ?></span>
                    </div>
                    <div class="hub-card-body">
                        <h4><?php echo esc_html( $course['title'] ); ?></h4>
                        <?php if ( $course['excerpt'] ) : ?>
                            <div class="hub-card-desc"><?php echo esc_html( $course['excerpt'] ); ?></div>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( $course['permalink'] ); ?>" class="hub-btn hub-btn-outline">
                            <?php echo $course['enrolled'] ? 'Enter Course' : 'View Program'; ?> →
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ( ! $courses ) : ?>
                <p>No courses have been published yet.</p>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

// Renders page-registration.php's "Your Enrollments" list as an HTML string
// (not echoed) — used both for that page's initial render and by
// illiana_handle_redeem_code_submit()'s AJAX response, so a successful
// redemption can refresh the list in place without duplicating this markup.
function illiana_get_enrollment_list_html( $user_id ) {
    $enrollments = array_values( array_filter(
        illiana_get_courses_catalog_data( $user_id ),
        function ( $course ) {
            return $course['enrolled'];
        }
    ) );

    ob_start();
    if ( $enrollments ) :
        ?>
        <ul class="reg-enrollment-list">
            <?php foreach ( $enrollments as $course ) : ?>
                <li class="reg-enrollment-row">
                    <span><?php echo esc_html( $course['title'] ); ?></span>
                    <a href="<?php echo esc_url( $course['permalink'] ); ?>" class="hub-btn hub-btn-outline">
                        <?php echo esc_html( $course['status_label'] ); ?> →
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    else :
        ?>
        <p class="reg-enrollment-empty">You're not enrolled in any programs yet — redeem an access code below, or browse programs to get started.</p>
        <?php
    endif;
    return ob_get_clean();
}
