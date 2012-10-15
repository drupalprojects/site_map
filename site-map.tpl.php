<?php

/**
 * @file
 * site-map.tpl.php
 *
 * Theme implementation to display the site map.
 *
 * Available variables:
 * - $message:
 * - $rss_legend:
 * - $front_page:
 * - $blogs:
 * - $books:
 * - $menus:
 * - $faq:
 * - $taxonomys:
 * - $additional:
 *
 * @see template_preprocess()
 * @see template_preprocess_site_map()
 */
?>

<div id="site-map">
  <?php if (!empty($message)): ?>
    <div class="site-map-message">
      <?php print $message; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($rss_legend)): ?>
    <div class="site-map-rss-legend">
      <?php print $rss_legend; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($front_page)): ?>
    <div class="site-map-front-page">
      <?php print $front_page; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($blogs)): ?>
    <div class="site-map-blogs">
      <?php print $blogs; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($books)): ?>
    <div class="site-map-books">
      <?php print $books; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($menus)): ?>
    <div class="site-map-menus">
      <?php print $menus; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($faq)): ?>
    <div class="site-map-faq">
      <?php print $faq; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($taxonomys)): ?>
    <div class="site-map-taxonomys">
      <?php print $taxonomys; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($additional)): ?>
    <div class="site-map-additional">
      <?php print $additional; ?>
    </div>
  <?php endif; ?>
</div>
