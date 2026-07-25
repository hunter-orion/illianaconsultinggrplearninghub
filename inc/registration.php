<?php
// Handlers backing page-registration.php: new-account signup (logged-out),
// profile editing + access-code redemption (logged-in), and a password-reset
// trigger. All are submitted via fetch() (src/index.js) to admin-post.php
// and respond with JSON — no redirect, no page reload, no query string.
// The one exception is a successful signup: the page has an entirely
// different logged-out vs. logged-in layout, so the client does a plain
// same-URL refresh after that one succeeds (see src/index.js).

// Shared field-length limits — enforced here (server) and mirrored as
// maxlength/JS checks in page-registration.php + src/index.js (client).
// Name/email caps match wp_users' user_login (60) and user_email (100)
// column widths; the rest are just sane UX ceilings.
define( 'ILLIANA_MIN_NAME_LEN', 2 );
define( 'ILLIANA_MAX_NAME_LEN', 100 );
define( 'ILLIANA_MAX_EMAIL_LEN', 100 );
define( 'ILLIANA_MAX_EMPLOYER_LEN', 150 );
define( 'ILLIANA_MAX_PHONE_LEN', 20 );
define( 'ILLIANA_MAX_CODE_LEN', 50 );
define( 'ILLIANA_MIN_PASSWORD_LEN', 8 );
define( 'ILLIANA_MAX_PASSWORD_LEN', 100 );

function illiana_send_registration_response( $success, $message, $extra = array() ) {
    wp_send_json( array_merge( array(
        'success' => $success,
        'message' => $message,
    ), $extra ) );
}

// ---------------------------------------------------------------
// Access codes. Deliberately kept separate from the form handlers below,
// so redemption logic can change without touching the
// registration/profile/redeem handlers that call it.
//
// Each code is an illiana_access_code post (see inc/accessCodes.php) whose
// title is the code string and whose illiana_code_course_id meta names the
// course it unlocks. Codes are single-use: once redeemed, illiana_code_used
// is set and the same code is rejected on any further attempt.
// ---------------------------------------------------------------
function illiana_redeem_access_code( $user_id, $code ) {
    $code = trim( $code );

    if ( '' === $code ) {
        return array( 'success' => false, 'message' => 'Enter an access code.' );
    }
    if ( mb_strlen( $code ) > ILLIANA_MAX_CODE_LEN ) {
        return array( 'success' => false, 'message' => 'Access code must be ' . ILLIANA_MAX_CODE_LEN . ' characters or fewer.' );
    }

    $matches = get_posts( array(
        'post_type'      => 'illiana_access_code',
        'post_status'    => 'publish',
        'title'          => $code,
        'posts_per_page' => 1,
    ) );

    if ( ! $matches ) {
        return array( 'success' => false, 'message' => "That access code isn't valid — check for typos or contact support." );
    }

    $code_post_id = $matches[0]->ID;

    if ( get_post_meta( $code_post_id, 'illiana_code_used', true ) ) {
        return array( 'success' => false, 'message' => 'That access code has already been used.' );
    }

    $course_id = (int) get_post_meta( $code_post_id, 'illiana_code_course_id', true );

    if ( ! $course_id || 'sfwd-courses' !== get_post_type( $course_id ) || 'publish' !== get_post_status( $course_id ) ) {
        return array( 'success' => false, 'message' => "This code isn't configured correctly — contact support." );
    }

    ld_update_course_access( $user_id, $course_id );

    update_post_meta( $code_post_id, 'illiana_code_used', 1 );
    update_post_meta( $code_post_id, 'illiana_code_used_by', $user_id );
    update_post_meta( $code_post_id, 'illiana_code_used_at', time() );

    add_user_meta( $user_id, 'illiana_redeemed_code', array(
        'code'        => $code,
        'course_id'   => $course_id,
        'redeemed_at' => time(),
    ) );

    return array(
        'success' => true,
        'message' => 'Code redeemed — you now have access to "' . get_the_title( $course_id ) . '".',
    );
}

function illiana_get_redeemed_codes( $user_id ) {
    return get_user_meta( $user_id, 'illiana_redeemed_code', false );
}

// ---------------------------------------------------------------
// Surfaces illiana_phone / illiana_employer on the wp-admin Users list
// table (Users → All Users) so admins don't have to open each profile.
// ---------------------------------------------------------------
function illiana_add_user_admin_columns( $columns ) {
    $columns['illiana_phone']    = 'Phone';
    $columns['illiana_employer'] = 'Employer';
    return $columns;
}
add_filter( 'manage_users_columns', 'illiana_add_user_admin_columns' );

function illiana_render_user_admin_column( $value, $column_name, $user_id ) {
    if ( 'illiana_phone' === $column_name ) {
        return esc_html( get_user_meta( $user_id, 'illiana_phone', true ) );
    }
    if ( 'illiana_employer' === $column_name ) {
        return esc_html( get_user_meta( $user_id, 'illiana_employer', true ) );
    }
    return $value;
}
add_filter( 'manage_users_custom_column', 'illiana_render_user_admin_column', 10, 3 );

// Native WP registration is disabled (see option_users_can_register in
// functions.php), so wp-login.php has no "Register" link of its own. This
// adds one pointing at the custom /registration page, printed in the same
// spot wp-login.php prints its own "Lost your password?" line.
function illiana_login_page_registration_link() {
    ?>
    <p style="text-align: center; margin-top: 16px;">
        <a href="<?php echo esc_url( home_url( '/registration' ) ); ?>">Need an account? Register here</a>
    </p>
    <?php
}
add_action( 'login_footer', 'illiana_login_page_registration_link' );

// Swaps wp-login.php's default WordPress-logo header for the theme's own
// logo, linked to the site home instead of wordpress.org.
function illiana_login_logo() {
    ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url(<?php echo esc_url( get_theme_file_uri( '/assets/logo-black.png' ) ); ?>);
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            /* logo.png is a square 2560x2560 mark — matching width/height so
               it isn't stretched or left with empty space in the box. */
            width: 100%;
            height: 120px;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
    <?php
}
add_action( 'login_enqueue_scripts', 'illiana_login_logo' );

function illiana_login_logo_url() {
    return home_url();
}
add_filter( 'login_headerurl', 'illiana_login_logo_url' );

function illiana_login_logo_title() {
    return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'illiana_login_logo_title' );

// Splits a "Full Name" field into first/last for WP_User's first_name/last_name —
// the last word is the last name, everything before it is the first name.
function illiana_split_full_name( $full_name ) {
    $full_name = trim( preg_replace( '/\s+/', ' ', $full_name ) );
    $parts     = explode( ' ', $full_name );
    $last      = array_pop( $parts );
    $first     = implode( ' ', $parts );

    return array( $first ?: $last, $first ? $last : '' );
}

// ---------------------------------------------------------------
// New account signup (logged-out only).
// ---------------------------------------------------------------
function illiana_handle_registration_submit() {
    if ( is_user_logged_in() ) {
        illiana_send_registration_response( false, "You're already logged in." );
    }

    if ( ! isset( $_POST['illiana_registration_nonce'] ) || ! wp_verify_nonce( $_POST['illiana_registration_nonce'], 'illiana_register' ) ) {
        illiana_send_registration_response( false, 'Your session expired — please refresh and try again.' );
    }

    $full_name     = sanitize_text_field( wp_unslash( $_POST['full_name'] ?? '' ) );
    $email         = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
    $email_confirm = sanitize_email( wp_unslash( $_POST['email_confirm'] ?? '' ) );
    $phone         = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
    $employer      = sanitize_text_field( wp_unslash( $_POST['employer'] ?? '' ) );
    $access_code   = sanitize_text_field( wp_unslash( $_POST['access_code'] ?? '' ) );
    $password      = (string) ( $_POST['password'] ?? '' );
    $password_conf = (string) ( $_POST['password_confirm'] ?? '' );
    $agreed_terms  = ! empty( $_POST['agree_terms'] );

    if ( ! $full_name || ! is_email( $email ) ) {
        illiana_send_registration_response( false, 'Enter your name and a valid email address.' );
    }
    if ( mb_strlen( $full_name ) < ILLIANA_MIN_NAME_LEN || mb_strlen( $full_name ) > ILLIANA_MAX_NAME_LEN ) {
        illiana_send_registration_response( false, 'Full name must be between ' . ILLIANA_MIN_NAME_LEN . ' and ' . ILLIANA_MAX_NAME_LEN . ' characters.' );
    }
    if ( strlen( $email ) > ILLIANA_MAX_EMAIL_LEN ) {
        illiana_send_registration_response( false, 'Email address must be ' . ILLIANA_MAX_EMAIL_LEN . ' characters or fewer.' );
    }
    if ( $email !== $email_confirm ) {
        illiana_send_registration_response( false, 'Email addresses do not match.' );
    }
    if ( $employer && mb_strlen( $employer ) > ILLIANA_MAX_EMPLOYER_LEN ) {
        illiana_send_registration_response( false, 'Employer/Organization must be ' . ILLIANA_MAX_EMPLOYER_LEN . ' characters or fewer.' );
    }
    if ( $phone && mb_strlen( $phone ) > ILLIANA_MAX_PHONE_LEN ) {
        illiana_send_registration_response( false, 'Phone number must be ' . ILLIANA_MAX_PHONE_LEN . ' characters or fewer.' );
    }
    if ( $access_code && mb_strlen( $access_code ) > ILLIANA_MAX_CODE_LEN ) {
        illiana_send_registration_response( false, 'Access code must be ' . ILLIANA_MAX_CODE_LEN . ' characters or fewer.' );
    }
    if ( strlen( $password ) < ILLIANA_MIN_PASSWORD_LEN || strlen( $password ) > ILLIANA_MAX_PASSWORD_LEN ) {
        illiana_send_registration_response( false, 'Password must be between ' . ILLIANA_MIN_PASSWORD_LEN . ' and ' . ILLIANA_MAX_PASSWORD_LEN . ' characters.' );
    }
    if ( $password !== $password_conf ) {
        illiana_send_registration_response( false, 'Passwords do not match.' );
    }
    if ( ! $agreed_terms ) {
        illiana_send_registration_response( false, 'Please agree to the Terms and Privacy Policy.' );
    }
    if ( email_exists( $email ) ) {
        illiana_send_registration_response( false, 'An account with that email already exists — try logging in instead.' );
    }

    list( $first_name, $last_name ) = illiana_split_full_name( $full_name );

    // user_login is the email, matching the convention already relied on
    // elsewhere in this theme (see illiana_get_user_display_name()).
    $user_id = wp_insert_user( array(
        'user_login' => $email,
        'user_email' => $email,
        'user_pass'  => $password,
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'display_name' => $first_name ?: $email,
        'role'       => 'subscriber',
    ) );

    if ( is_wp_error( $user_id ) ) {
        illiana_send_registration_response( false, $user_id->get_error_message() );
    }

    if ( $phone ) {
        update_user_meta( $user_id, 'illiana_phone', $phone );
    }
    if ( $employer ) {
        update_user_meta( $user_id, 'illiana_employer', $employer );
    }

    // The access code is optional at signup (someone might redeem one later
    // from the logged-in "Redeem Another Access Code" panel instead), so a
    // blank code here isn't an error — only report back on one that was
    // actually submitted, success or failure, alongside the account-created
    // message. The client shows both, then refreshes the page.
    $code_result = $access_code ? illiana_redeem_access_code( $user_id, $access_code ) : null;

    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, true );

    illiana_send_registration_response( true, 'Welcome! Your account has been created.', array(
        'code' => $code_result,
    ) );
}
add_action( 'admin_post_nopriv_illiana_register', 'illiana_handle_registration_submit' );
add_action( 'admin_post_illiana_register', 'illiana_handle_registration_submit' );

// ---------------------------------------------------------------
// Profile edits (logged-in only): name, phone, employer. Deliberately no
// email/password fields here — those are handled by the password-reset
// button below instead.
// ---------------------------------------------------------------
function illiana_handle_profile_update() {
    if ( ! is_user_logged_in() ) {
        illiana_send_registration_response( false, 'You must be logged in.' );
    }
    if ( ! isset( $_POST['illiana_profile_nonce'] ) || ! wp_verify_nonce( $_POST['illiana_profile_nonce'], 'illiana_update_profile' ) ) {
        illiana_send_registration_response( false, 'Your session expired — please refresh and try again.' );
    }

    $user_id   = get_current_user_id();
    $full_name = sanitize_text_field( wp_unslash( $_POST['full_name'] ?? '' ) );
    $phone     = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
    $employer  = sanitize_text_field( wp_unslash( $_POST['employer'] ?? '' ) );

    if ( $full_name && ( mb_strlen( $full_name ) < ILLIANA_MIN_NAME_LEN || mb_strlen( $full_name ) > ILLIANA_MAX_NAME_LEN ) ) {
        illiana_send_registration_response( false, 'Full name must be between ' . ILLIANA_MIN_NAME_LEN . ' and ' . ILLIANA_MAX_NAME_LEN . ' characters.' );
    }
    if ( $employer && mb_strlen( $employer ) > ILLIANA_MAX_EMPLOYER_LEN ) {
        illiana_send_registration_response( false, 'Employer/Organization must be ' . ILLIANA_MAX_EMPLOYER_LEN . ' characters or fewer.' );
    }
    if ( $phone && mb_strlen( $phone ) > ILLIANA_MAX_PHONE_LEN ) {
        illiana_send_registration_response( false, 'Phone number must be ' . ILLIANA_MAX_PHONE_LEN . ' characters or fewer.' );
    }

    if ( $full_name ) {
        list( $first_name, $last_name ) = illiana_split_full_name( $full_name );
        wp_update_user( array(
            'ID'         => $user_id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
        ) );
    }

    update_user_meta( $user_id, 'illiana_phone', $phone );
    update_user_meta( $user_id, 'illiana_employer', $employer );

    illiana_send_registration_response( true, 'Your profile has been updated.' );
}
add_action( 'admin_post_illiana_update_profile', 'illiana_handle_profile_update' );

// ---------------------------------------------------------------
// Redeem another access code (logged-in only) — a separate form/action from
// profile editing, so submitting one never touches the other's fields.
// ---------------------------------------------------------------
function illiana_handle_redeem_code_submit() {
    if ( ! is_user_logged_in() ) {
        illiana_send_registration_response( false, 'You must be logged in.' );
    }
    if ( ! isset( $_POST['illiana_redeem_nonce'] ) || ! wp_verify_nonce( $_POST['illiana_redeem_nonce'], 'illiana_redeem_code' ) ) {
        illiana_send_registration_response( false, 'Your session expired — please refresh and try again.' );
    }

    $user_id = get_current_user_id();
    $result  = illiana_redeem_access_code( $user_id, sanitize_text_field( wp_unslash( $_POST['access_code'] ?? '' ) ) );

    // On success, hand back a freshly rendered enrollments list so the
    // client can swap it into #reg-enrollments without a page reload.
    illiana_send_registration_response( $result['success'], $result['message'], $result['success']
        ? array( 'enrollment_html' => illiana_get_enrollment_list_html( $user_id ) )
        : array()
    );
}
add_action( 'admin_post_illiana_redeem_code', 'illiana_handle_redeem_code_submit' );

// ---------------------------------------------------------------
// Password reset (logged-in only): sends the standard WP "reset your
// password" email rather than collecting/changing a password inline.
// ---------------------------------------------------------------
function illiana_handle_password_reset_request() {
    if ( ! is_user_logged_in() ) {
        illiana_send_registration_response( false, 'You must be logged in.' );
    }
    if ( ! isset( $_POST['illiana_reset_nonce'] ) || ! wp_verify_nonce( $_POST['illiana_reset_nonce'], 'illiana_request_password_reset' ) ) {
        illiana_send_registration_response( false, 'Your session expired — please refresh and try again.' );
    }

    $user = wp_get_current_user();
    $key  = get_password_reset_key( $user );

    if ( is_wp_error( $key ) ) {
        illiana_send_registration_response( false, 'Could not send the reset email — please try again.' );
    }

    $reset_url = network_site_url( 'wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode( $user->user_login ), 'login' );

    wp_mail(
        $user->user_email,
        'Reset your password — ' . get_bloginfo( 'name' ),
        "Someone requested a password reset for your account.\n\nReset it here: {$reset_url}\n\nIf you didn't request this, you can ignore this email."
    );

    illiana_send_registration_response( true, 'Check your email for a link to reset your password.' );
}
add_action( 'admin_post_illiana_request_password_reset', 'illiana_handle_password_reset_request' );
