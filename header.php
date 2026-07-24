<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset=' <?php bloginfo('charset'); ?>'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel="icon" href="<?php echo get_theme_file_uri('images/logo.png'); ?>" sizes="192x192"/>
    <meta name="author" content="Optimized Webs optimizedwebs.design">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <nav id="top" class="nav-top">
        <a class="skip z-57" href="#main">Skip to main content</a>

        <div class="nav-container">
            <div class="nav-logo">
                <a href="<?php echo esc_url( home_url() ); ?>"
                  aria-label="Illiana Consulting Group home navigation">
                    <img loading="eager"
                        fetchpriority="high"
                        decoding="async"
                        src="<?php echo esc_url( get_theme_file_uri( '/assets/logo-white.webp' ) ); ?>"
                        alt="Illiana Consulting Group, home navigation" />
                </a>
            </div>

            <div id="nav-list" class="nav-list">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>"
                   class="nav-items transition-colors<?php echo is_front_page() ? ' current-page' : ''; ?>">
                    Home
                </a>

                <?php if ( is_user_logged_in() ) :
                    $current_user = wp_get_current_user();
                    $display_name = illiana_get_user_display_name( $current_user );
                    $initials     = illiana_get_user_initials( $current_user );
             
                    ?>
                    <div class="relative dropdown">
                        <button id="user-menu-toggle" type="button"
                                class="nav-items transition-colors dropdown-toggle user-menu-toggle"
                                aria-haspopup="true" aria-expanded="false" aria-controls="user-menu">
                            <span class="user-avatar" aria-hidden="true"><?php echo esc_html( $initials ); ?></span>
                            <span class="nav-label">Hello, <?php echo esc_html( $display_name ); ?></span>
                            <span class="nav-chevron" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></span>
                        </button>
                        <div id="user-menu" class="dropdown-menu" aria-labelledby="user-menu-toggle">
                            <a href="<?php echo esc_url( home_url( '/registration' )); ?>">My Profile</a>
                            <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">Log Out</a>
                        </div>
                    </div>
                <?php else : ?>
                    <a href="<?php echo esc_url(home_url( '/wp-login.php' ) ); ?>"
                       class="nav-items nav-button transition-colors">
                        Log In
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

