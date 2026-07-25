<?php
/*
Template Name: LearnDash Register
*/

get_header();

$is_logged_in = is_user_logged_in();
$user_id      = get_current_user_id();
$current_user = $is_logged_in ? wp_get_current_user() : null;
$display_name = $current_user ? illiana_get_user_display_name( $current_user ) : '';

// Reused for the "Explore All ICG Programs" catalog below.
$catalog = illiana_get_courses_catalog_data( $user_id );

// All form submissions on this page go through AJAX (see src/index.js) and
// respond with JSON instead of redirecting — #reg-messages starts empty and
// is only ever filled in by JS, so there's no URL/query-string round trip.
?>
<main id="main" class="register-page">
<div class="reg-wrap">

    <div class="reg-hero">
        <div class="reg-eyebrow">Illiana Consulting Group · Learning Hub</div>
        <?php if ( $is_logged_in ) : ?>
            <h1>Hello, <?php echo esc_html( $display_name ); ?>.</h1>
            <p class="reg-hero-sub">Manage your account, redeem another access code, or browse our programs below.</p>
        <?php else : ?>
            <h1>Welcome. Let's get you set up.</h1>
            <p class="reg-hero-sub">Enter the access code from your purchase confirmation or your community college program to create your account.</p>
        <?php endif; ?>
    </div>

    <div id="reg-messages"></div>

    <?php if ( $is_logged_in ) : ?>

        <div class="reg-panel">
            <h3>Your Info</h3>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="profile-form">
                <?php wp_nonce_field( 'illiana_update_profile', 'illiana_profile_nonce' ); ?>
                <input type="hidden" name="action" value="illiana_update_profile">

                <div class="reg-two-col">
                    <div class="reg-field">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name"
                               minlength="<?php echo esc_attr( ILLIANA_MIN_NAME_LEN ); ?>" maxlength="<?php echo esc_attr( ILLIANA_MAX_NAME_LEN ); ?>"
                               value="<?php echo esc_attr( trim( $current_user->first_name . ' ' . $current_user->last_name ) ); ?>">
                    </div>
                    <div class="reg-field">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" value="<?php echo esc_attr( $current_user->user_email ); ?>" disabled>
                        <div class="reg-helper">Your email doubles as your login and can't be changed here.</div>
                    </div>
                </div>

                <div class="reg-two-col">
                    <div class="reg-field">
                        <label for="employer">Employer / Organization</label>
                        <input type="text" id="employer" name="employer" maxlength="<?php echo esc_attr( ILLIANA_MAX_EMPLOYER_LEN ); ?>"
                               value="<?php echo esc_attr( get_user_meta( $user_id, 'illiana_employer', true ) ); ?>">
                    </div>
                    <div class="reg-field">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" maxlength="<?php echo esc_attr( ILLIANA_MAX_PHONE_LEN ); ?>"
                               value="<?php echo esc_attr( get_user_meta( $user_id, 'illiana_phone', true ) ); ?>">
                    </div>
                </div>

                <button type="submit" class="hub-btn reg-submit-btn">Save Changes</button>
            </form>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="password-reset-form">
                <?php wp_nonce_field( 'illiana_request_password_reset', 'illiana_reset_nonce' ); ?>
                <input type="hidden" name="action" value="illiana_request_password_reset">
                <button type="submit" class="hub-btn hub-btn-secondary reg-submit-btn">Reset Password</button>
            </form>
        </div>

        <div class="reg-panel">
            <h3>Your Enrollments</h3>
            <div id="reg-enrollments"><?php echo illiana_get_enrollment_list_html( $user_id ); ?></div>
        </div>

        <div class="reg-panel">
            <h3>Redeem Another Access Code</h3>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="redeem-code-form">
                <?php wp_nonce_field( 'illiana_redeem_code', 'illiana_redeem_nonce' ); ?>
                <input type="hidden" name="action" value="illiana_redeem_code">
                <div class="reg-field">
                    <label for="access_code">Access Code</label>
                    <input type="text" id="access_code" name="access_code" placeholder="e.g. ICG-CSSGB-4471" maxlength="<?php echo esc_attr( ILLIANA_MAX_CODE_LEN ); ?>">
                </div>
                <button type="submit" class="hub-btn reg-submit-btn">Redeem Code</button>
            </form>
        </div>

    <?php else : ?>

        <div class="reg-panel">
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="registration-form" novalidate>
                <?php wp_nonce_field( 'illiana_register', 'illiana_registration_nonce' ); ?>
                <input type="hidden" name="action" value="illiana_register">

                <div class="reg-field">
                    <label for="access_code">Access Code</label>
                    <input type="text" id="access_code" name="access_code" placeholder="e.g. ICG-CSSGB-4471" maxlength="<?php echo esc_attr( ILLIANA_MAX_CODE_LEN ); ?>">
                    <div class="reg-helper">Your code was included in your purchase confirmation email, or provided by your community college program coordinator. Contact support if you can't locate it.</div>
                </div>

                <div class="reg-field">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" placeholder="Jane Doe" required
                           minlength="<?php echo esc_attr( ILLIANA_MIN_NAME_LEN ); ?>" maxlength="<?php echo esc_attr( ILLIANA_MAX_NAME_LEN ); ?>">
                </div>

                <div class="reg-two-col">
                    <div class="reg-field">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="jane@company.com" required maxlength="<?php echo esc_attr( ILLIANA_MAX_EMAIL_LEN ); ?>">
                    </div>
                    <div class="reg-field">
                        <label for="email_confirm">Confirm Email</label>
                        <input type="email" id="email_confirm" name="email_confirm" placeholder="jane@company.com" required maxlength="<?php echo esc_attr( ILLIANA_MAX_EMAIL_LEN ); ?>">
                    </div>
                </div>

                <div class="reg-two-col">
                    <div class="reg-field">
                        <label for="employer">Employer / Organization</label>
                        <input type="text" id="employer" name="employer" placeholder="e.g. ThyssenKrupp Crankshaft, Ivy Tech, self-employed" maxlength="<?php echo esc_attr( ILLIANA_MAX_EMPLOYER_LEN ); ?>">
                    </div>
                    <div class="reg-field">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="(555) 123-4567" maxlength="<?php echo esc_attr( ILLIANA_MAX_PHONE_LEN ); ?>">
                    </div>
                </div>

                <div class="reg-two-col">
                    <div class="reg-field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required
                               minlength="<?php echo esc_attr( ILLIANA_MIN_PASSWORD_LEN ); ?>" maxlength="<?php echo esc_attr( ILLIANA_MAX_PASSWORD_LEN ); ?>">
                    </div>
                    <div class="reg-field">
                        <label for="password_confirm">Confirm Password</label>
                        <input type="password" id="password_confirm" name="password_confirm" required
                               minlength="<?php echo esc_attr( ILLIANA_MIN_PASSWORD_LEN ); ?>" maxlength="<?php echo esc_attr( ILLIANA_MAX_PASSWORD_LEN ); ?>">
                    </div>
                </div>

                <div class="reg-attest">
                    <input type="checkbox" id="agree_terms" name="agree_terms" value="1" required>
                    <label for="agree_terms">I understand that course completion and any certification are based on my own participation, and I agree to Illiana Consulting Group's <a href="#">Terms</a> and <a href="#">Privacy Policy</a>.</label>
                </div>

                <button type="submit" class="hub-btn reg-submit-btn">Create Account &amp; Continue →</button>
            </form>
        </div>

    <?php endif; ?>
</div>
<div class="m-auto">
    <?php
    illiana_render_course_catalog( array(
        'heading'    => 'Explore All ICG Programs',
        'subheading' => $is_logged_in
            ? 'Everything Illiana Consulting Group offers, organized by track. Browse anytime.'
            : 'Everything Illiana Consulting Group offers, organized by track. Your access code covers the course above — browse the rest anytime.',
        'courses'    => $catalog,
    ) );
    ?>
</div>
</main>

<?php get_footer(); ?>
