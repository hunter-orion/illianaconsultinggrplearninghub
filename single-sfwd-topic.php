<?php
get_header();

while (have_posts()) :
    the_post();

    the_title('<h1>', '</h1>');
echo 'topic';

    the_content();

endwhile;

get_footer();