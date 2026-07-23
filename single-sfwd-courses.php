<?php
while ( have_posts() ) :
	the_post();

	$course_id = get_the_ID();
	$user_id   = get_current_user_id();

	$lessons  = learndash_get_course_lessons_list( $course_id, $user_id );
	$sections = function_exists( 'learndash_30_get_course_sections' ) ? learndash_30_get_course_sections( $course_id ) : array();

	$progress         = learndash_course_progress( array(
		'user_id'   => $user_id,
		'course_id' => $course_id,
		'array'     => true,
	) );
	$progress_percent = ! empty( $progress['percentage'] ) ? (int) $progress['percentage'] : 0;
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc_html( get_the_title( $course_id ) ); ?> — Course Home</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'ld-course-home' ); ?>>

<div class="app">

  <aside class="sidebar">
    <div class="sb-header">
      <img class="sb-logo" src="<?php echo esc_url( get_theme_file_uri( '/assets/logo-white.webp' ) ); ?>" alt="<?php bloginfo( 'name' ); ?>">
      <a class="sb-back" href="<?php echo esc_url( home_url( '/' ) ); ?>">← Learning Hub</a>
      <div class="sb-course-name"><?php echo esc_html( get_the_title( $course_id ) ); ?></div>
      <div class="sb-progress-label"><span>Course Progress</span><span><?php echo esc_html( $progress_percent ); ?>%</span></div>
      <div class="sb-progress-track"><div class="sb-progress-fill" style="width: <?php echo esc_attr( $progress_percent ); ?>%;"></div></div>
    </div>

    <a class="home-link" href="<?php echo esc_url( get_permalink( $course_id ) ); ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg>
      <span>Course Home</span>
    </a>

    <div class="phase-list">
      <?php
      $section_open = true;
      $in_section    = false;

      foreach ( $lessons as $lesson ) :
          $lesson_id = $lesson['post']->ID;

          if ( isset( $sections[ $lesson_id ] ) ) :
              if ( $in_section ) {
                  echo '</div></details>';
              }
              $in_section = true;
              ?>
              <details class="phaseA" <?php echo $section_open ? 'open' : ''; ?>>
                  <summary class="phaseA-head">
                      <span class="name"><?php echo esc_html( $sections[ $lesson_id ]->post_title ); ?></span>
                      <svg class="phaseA-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                  </summary>
                  <div class="phaseA-body">
              <?php
              $section_open = false;
          endif;

          $topics  = learndash_get_topic_list( $lesson_id, $course_id );
          $quizzes = learndash_get_lesson_quiz_list( $lesson['post'], $user_id, $course_id );
          ?>
          <div class="lesson-row">
              <a class="lesson-link" href="<?php echo esc_url( learndash_get_step_permalink( $lesson_id, $course_id ) ); ?>">
                  <span class="lesson-name"><?php echo esc_html( get_the_title( $lesson_id ) ); ?></span>
                  <?php if ( ! empty( $quizzes ) ) : ?>
                      <span class="lesson-sub"><?php echo count( $quizzes ); ?> Quiz<?php echo count( $quizzes ) > 1 ? 'zes' : ''; ?></span>
                  <?php endif; ?>
              </a>
              <?php if ( ! empty( $topics ) ) : ?>
                  <div class="topic-list">
                      <?php foreach ( $topics as $topic ) : ?>
                          <a class="topic-row" href="<?php echo esc_url( learndash_get_step_permalink( $topic->ID, $course_id ) ); ?>"><?php echo esc_html( $topic->post_title ); ?></a>
                      <?php endforeach; ?>
                  </div>
              <?php endif; ?>
          </div>
          <?php
      endforeach;

      if ( $in_section ) {
          echo '</div></details>';
      }
      ?>
    </div>
  </aside>

  <div class="main">

    <div class="hero">
      <div class="hero-inner">
        <div>
          <div class="eyebrow">Course Home</div>
          <h1><?php echo esc_html( get_the_title( $course_id ) ); ?></h1>
        </div>
        <div class="instructor-chip">
          <div class="avatar">JR</div>
          <div>
            <div class="name">Jordan Reyes, MBB</div>
            <div class="role">Your Instructor</div>
          </div>
        </div>
      </div>
    </div>

    <?php the_content(); ?>

  </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
	<?php
endwhile;
