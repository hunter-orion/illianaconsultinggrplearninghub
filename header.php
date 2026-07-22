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
        
              <button type="button" class="hamburger-menu" id="hamburger" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="nav-list">
                    <div class="line line1"></div>
                    <div class="line line2"></div>
                    <div class="line line3"></div>
                </button>
<div class="nav-container">
    <div class="nav-logo">
        <a href="<?php echo home_url(); ?>"
          aria-label="Illiana Consulting Group home navigation">

            <img        loading="eager"
            fetchpriority="high"
            decoding="async"
     src=<?php echo get_theme_file_uri('/assets/logo-white.webp') ?>
      alt="Illiana Consulting Group, home navigation"  loading="eager"
  fetchpriority="high"
  decoding="async"/>
        </a>
    
    </div>
      <div id="nav-list" class=" nav-list">

       <a href="<?php echo esc_url( home_url('/') ); ?>"
          class="nav-items transition-colors<?php echo is_front_page() ? ' current-page' : ''; ?>">
          <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg></span>
          <span class="nav-label">Home</span></a>
                <!-- Services Dropdown (accessible disclosure) -->
                <div class="relative dropdown">
                    <button id="services-toggle" type="button"
                            class="nav-items transition-colors first-button dropdown-toggle<?php echo ( is_page('consulting') || is_page('legal-support') || is_page('pe-advisory') ) ? ' current-page' : ''; ?>"
                            aria-haspopup="true" aria-expanded="false" aria-controls="services-menu">
                        <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></span>
                        <span class="nav-label">Services</span>
                        <span class="nav-chevron" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></span>
                    </button>
                    <div id="services-menu" class="dropdown-menu absolute top-full left-0 pt-2 w-48" aria-labelledby="services-toggle">
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
  <a href="<?php echo esc_url( home_url('/consulting') ); ?>"
                            class="block px-4 py-2 text-sm text-[#00426A] hover:bg-gray-50 focus:bg-gray-50">Consulting</a>


                            <a href="<?php echo esc_url( home_url('/legal-support') ); ?>"
                            class="block px-4 py-2 text-sm text-[#00426A] hover:bg-gray-50 focus:bg-gray-50">Legal Support</a>

                            <a href="<?php echo esc_url( home_url('/pe-advisory') ); ?>"
                            class="block px-4 py-2 text-sm text-[#00426A] hover:bg-gray-50 focus:bg-gray-50">PE Advisory</a>
                        </div>
                    </div>
                </div>
                <!-- Workforce Dropdown (accessible disclosure) -->
                <div class="relative dropdown">
                    <button id="workforce-toggle" type="button"
                            class="nav-items transition-colors dropdown-toggle<?php echo ( is_page('workforce-development') || is_page('upcoming-programs') ) ? ' current-page' : ''; ?>"
                            aria-haspopup="true" aria-expanded="false" aria-controls="workforce-menu">
                        <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></span>
                        <span class="nav-label">Workforce</span>
                        <span class="nav-chevron" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></span>
                    </button>
                    <div id="workforce-menu" class="dropdown-menu absolute top-full left-0 pt-2 w-48" aria-labelledby="workforce-toggle">
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                            <a href="<?php echo esc_url( home_url('/workforce-development') ); ?>"
                            class="block px-4 py-2 text-sm text-[#00426A] hover:bg-gray-50 focus:bg-gray-50">Workforce Development</a>

                            <a href="<?php echo esc_url( home_url('/upcoming-programs') ); ?>"
                            class="block px-4 py-2 text-sm text-[#00426A] hover:bg-gray-50 focus:bg-gray-50">Upcoming Programs</a>
                        </div>
                    </div>
                </div>
                <a href="<?php echo esc_url( home_url('/about') ); ?>"
                class="nav-items transition-colors<?php echo is_page('about') ? ' current-page' : ''; ?>">
                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="16" height="20" x="4" y="2" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"/></svg></span>
                <span class="nav-label">About</span></a>

                      <a href="<?php echo esc_url( home_url('/products') ); ?>"
                class="nav-items transition-colors<?php echo is_page('products') ? ' current-page' : ''; ?>">
                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg></span>
                <span class="nav-label">Products</span></a>

                      <a href="<?php echo esc_url( home_url('/contact') ); ?>"
                class="nav-items transition-colors<?php echo is_page('contact') ? ' current-page' : ''; ?>">
                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></span>
                <span class="nav-label">Contact</span></a>

         <a href="<?php echo esc_url('https://courses.illianaconsultinggrp.com/'); ?>"
                class="nav-items nav-button transition-colors">
                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                <span class="nav-label">Learning Hub</span></a>

         <div class="nav-mobile-footer">
             <a href="<?php echo esc_url( home_url('/privacy-policy') ); ?>">Privacy Policy</a>
         </div>
</div>
    </nav>


