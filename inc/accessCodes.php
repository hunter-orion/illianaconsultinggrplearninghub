<?php
// "Access Code" custom post type — each post is one redeemable code (the
// post title) tied to a single course. Redemption itself (validating a
// submitted code and granting course access) lives in
// illiana_redeem_access_code() in inc/registration.php; this file is just
// the CPT registration and its wp-admin editing UI.
//
// Note: code titles aren't enforced unique at the database level — WP
// doesn't do that for any post type out of the box. Redemption looks up
// the first published match, so avoid creating two codes with the same title.

function illiana_register_access_code_cpt() {
    register_post_type( 'illiana_access_code', array(
        'labels' => array(
            'name'          => 'Access Codes',
            'singular_name' => 'Access Code',
            'add_new_item'  => 'Add New Access Code',
            'edit_item'     => 'Edit Access Code',
            'new_item'      => 'New Access Code',
            'search_items'  => 'Search Access Codes',
            'not_found'     => 'No access codes found.',
        ),
        'public'          => false,
        'show_ui'         => true,
        'show_in_menu'    => true,
        'menu_icon'       => 'dashicons-tickets-alt',
        'menu_position'   => 26,
        'supports'        => array( 'title' ),
        'capability_type' => 'post',
    ) );
}
add_action( 'init', 'illiana_register_access_code_cpt' );

function illiana_access_code_meta_box() {
    add_meta_box(
        'illiana_access_code_details',
        'Redemption Details',
        'illiana_render_access_code_meta_box',
        'illiana_access_code',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'illiana_access_code_meta_box' );

function illiana_render_access_code_meta_box( $post ) {
    wp_nonce_field( 'illiana_save_access_code', 'illiana_access_code_nonce' );

    $course_id = get_post_meta( $post->ID, 'illiana_code_course_id', true );
    $used      = get_post_meta( $post->ID, 'illiana_code_used', true );
    $used_by   = get_post_meta( $post->ID, 'illiana_code_used_by', true );
    $used_at   = get_post_meta( $post->ID, 'illiana_code_used_at', true );

    $courses = get_posts( array(
        'post_type'      => 'sfwd-courses',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ) );
    ?>
    <p>
        <label for="illiana_code_course_id"><strong>Course this code unlocks</strong></label><br>
        <select name="illiana_code_course_id" id="illiana_code_course_id" style="width:100%;">
            <option value="">— Select a course —</option>
            <?php foreach ( $courses as $course ) : ?>
                <option value="<?php echo esc_attr( $course->ID ); ?>" <?php selected( $course_id, $course->ID ); ?>>
                    <?php echo esc_html( $course->post_title ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php if ( $used ) :
        $user = $used_by ? get_userdata( $used_by ) : false;
        ?>
        <p>
            <strong>Status:</strong> Redeemed<br>
            <strong>By:</strong> <?php echo $user ? esc_html( $user->user_email ) : 'Unknown user'; ?><br>
            <?php if ( $used_at ) : ?>
                <strong>On:</strong> <?php echo esc_html( date_i18n( 'M j, Y g:ia', $used_at ) ); ?>
            <?php endif; ?>
        </p>
    <?php else : ?>
        <p><strong>Status:</strong> Available</p>
    <?php endif; ?>
    <?php
}

function illiana_save_access_code_meta( $post_id ) {
    if ( ! isset( $_POST['illiana_access_code_nonce'] ) || ! wp_verify_nonce( $_POST['illiana_access_code_nonce'], 'illiana_save_access_code' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( isset( $_POST['illiana_code_course_id'] ) ) {
        update_post_meta( $post_id, 'illiana_code_course_id', absint( $_POST['illiana_code_course_id'] ) );
    }
}
add_action( 'save_post_illiana_access_code', 'illiana_save_access_code_meta' );

function illiana_access_code_columns( $columns ) {
    $columns['illiana_course']  = 'Course';
    $columns['illiana_status']  = 'Status';
    $columns['illiana_used_by'] = 'Redeemed By';
    return $columns;
}
add_filter( 'manage_illiana_access_code_posts_columns', 'illiana_access_code_columns' );

function illiana_render_access_code_column( $column, $post_id ) {
    if ( 'illiana_course' === $column ) {
        $course_id = get_post_meta( $post_id, 'illiana_code_course_id', true );
        echo $course_id ? esc_html( get_the_title( $course_id ) ) : '—';
        return;
    }
    if ( 'illiana_status' === $column ) {
        echo get_post_meta( $post_id, 'illiana_code_used', true ) ? 'Redeemed' : 'Available';
        return;
    }
    if ( 'illiana_used_by' === $column ) {
        $used_by = get_post_meta( $post_id, 'illiana_code_used_by', true );
        $user    = $used_by ? get_userdata( $used_by ) : false;
        echo $user ? esc_html( $user->user_email ) : '—';
    }
}
add_action( 'manage_illiana_access_code_posts_custom_column', 'illiana_render_access_code_column', 10, 2 );

// ---------------------------------------------------------------
// Bulk generation: "Access Codes → Generate Codes" — creates N unique,
// already-published codes for one chosen course in a single submit, instead
// of clicking through Add New for each one by hand.
// ---------------------------------------------------------------
function illiana_add_generate_codes_page() {
    add_submenu_page(
        'edit.php?post_type=illiana_access_code',
        'Generate Access Codes',
        'Generate Codes',
        'edit_posts',
        'illiana-generate-codes',
        'illiana_render_generate_codes_page'
    );
}
add_action( 'admin_menu', 'illiana_add_generate_codes_page' );

// Random unambiguous code (no 0/O or 1/I, so it's easy to read back over
// email/phone), regenerated on the rare chance of a collision with an
// existing code.
function illiana_generate_unique_code() {
    $charset = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    do {
        $code = 'ICG-';
        for ( $i = 0; $i < 8; $i++ ) {
            $code .= $charset[ random_int( 0, strlen( $charset ) - 1 ) ];
        }

        $exists = get_posts( array(
            'post_type'      => 'illiana_access_code',
            'post_status'    => 'any',
            'title'          => $code,
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ) );
    } while ( $exists );

    return $code;
}

function illiana_generate_access_codes( $quantity, $course_id ) {
    $codes = array();

    for ( $i = 0; $i < $quantity; $i++ ) {
        $code    = illiana_generate_unique_code();
        $post_id = wp_insert_post( array(
            'post_type'   => 'illiana_access_code',
            'post_title'  => $code,
            'post_status' => 'publish',
        ) );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, 'illiana_code_course_id', $course_id );
            $codes[] = $code;
        }
    }

    return $codes;
}

function illiana_render_generate_codes_page() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( 'You do not have permission to access this page.' );
    }

    $generated = array();

    if ( isset( $_POST['illiana_generate_codes_nonce'] ) && wp_verify_nonce( $_POST['illiana_generate_codes_nonce'], 'illiana_generate_codes' ) ) {
        $quantity  = max( 1, min( 500, (int) ( $_POST['quantity'] ?? 0 ) ) );
        $course_id = (int) ( $_POST['course_id'] ?? 0 );

        if ( $course_id && 'sfwd-courses' === get_post_type( $course_id ) ) {
            $generated = illiana_generate_access_codes( $quantity, $course_id );
        }
    }

    $courses = get_posts( array(
        'post_type'      => 'sfwd-courses',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ) );
    ?>
    <div class="wrap">
        <h1>Generate Access Codes</h1>

        <?php if ( $generated ) : ?>
            <div class="notice notice-success">
                <p><strong><?php echo count( $generated ); ?> code(s) generated</strong> for
                    "<?php echo esc_html( get_the_title( (int) $_POST['course_id'] ) ); ?>".
                    Copy them now — they're also listed individually on the Access Codes screen.</p>
            </div>
            <textarea readonly rows="<?php echo esc_attr( min( 20, count( $generated ) + 1 ) ); ?>"
                      style="width:100%;max-width:420px;font-family:monospace;"
                      onclick="this.select();"><?php echo esc_textarea( implode( "\n", $generated ) ); ?></textarea>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field( 'illiana_generate_codes', 'illiana_generate_codes_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="quantity">Generate</label></th>
                    <td>
                        <input type="number" id="quantity" name="quantity" min="1" max="500" value="10" required>
                        access codes
                    </td>
                </tr>
                <tr>
                    <th><label for="course_id">For course</label></th>
                    <td>
                        <select id="course_id" name="course_id" required>
                            <option value="">— Select a course —</option>
                            <?php foreach ( $courses as $course ) : ?>
                                <option value="<?php echo esc_attr( $course->ID ); ?>"><?php echo esc_html( $course->post_title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Generate Codes' ); ?>
        </form>
    </div>
    <?php
}
