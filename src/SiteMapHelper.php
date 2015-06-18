<?php

/**
 * @file
 * Contains \Drupal\site_map\SitemapHelper.
 */

namespace Drupal\site_map;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;

/**
 * Defines a helper class for stuff related to views data.
 */
class SiteMapHelper {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a SitemapHelper object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Sets options based on admin input paramaters for redering.
   *
   * @param array $options
   *   The array of options to the site map theme.
   * @param string $option_string
   *   The string index given from the admin form to match.
   * @param int $equal_param
   *   Result of param test, 0 or 1.
   * @param string $set_string
   *   Index of option to set, or the option name.
   * @param bool $set_value
   *   The option, on or off, or strings or ints for other options.
   */
  public function setOption(array &$options, $option_string, $equal_param, $set_string, $set_value) {
    $config = $this->configFactory->get('site_map.settings');
    if ($config->get($option_string) == $equal_param) {
      $options[$set_string] = $set_value;
    }
  }

  /**
   * Render the latest maps for the taxonomy tree.
   *
   * @param object $voc
   *   Vocabulary entity.
   *
   * @return string
   *   Returns HTML string of site map for taxonomies.
   */
  public function getTaxonomys($voc) {
    $output = '';
    $options = array();

    if (\Drupal::moduleHandler()->moduleExists('taxonomy') && !empty($voc)) {
      if (\Drupal::moduleHandler()->moduleExists('i18n_taxonomy')) {
        $voc->name = i18n_taxonomy_vocabulary_name($voc, $GLOBALS['language']->language);
      }

      $output .= $this->getTaxonomyTree($voc->vid, $voc->name, $voc->description);
      $this->setOption($options, 'site_map_show_titles', 1, 'show_titles', TRUE);
    }

    return $output;
  }

  /**
   * Render the taxonomy tree.
   *
   * @param string $vid
   *   Vocabulary id.
   * @param string $name
   *   An optional name for the tree. (Default: NULL).
   * @param string $description
   *   $description An optional description of the tree. (Default: NULL).
   *
   * @return string
   *   A string representing a rendered tree.
   */
  public function getTaxonomyTree($vid, $name = NULL, $description = NULL) {
    $output = '';
    $options = array();
    $class = array();
    $config = \Drupal::config('site_map.settings');

    if ($vid == \Drupal::config('forum.settings')->get('forum_nav_vocabulary')) {
      $title = \Drupal::l($name, Url::fromRoute('forum.index'));
      $threshold = $config->get('site_map_forum_threshold');
      $forum_link = TRUE;
    }
    else {
      $title = $name ? String::checkPlain($name) : '';
      $threshold = $config->get('site_map_term_threshold');
      $forum_link = FALSE;
    }
    if (\Drupal::service('module_handler')->moduleExists('commentrss') && \Drupal::config('commentrss.settings')->get('commentrss_term')) {
      $feed_icon = array(
        '#theme' => 'site_map_feed_icon',
        '#url' => "crss/vocab/$vid",
        '#name' => String::checkPlain($name),
        '#type' => 'comment',
      );
      $title .= ' ' . drupal_render($feed_icon);
    }

    $last_depth = -1;

    $output .= !empty($description) && $config->get('site_map_show_description') ? '<div class="description">' . Xss::filterAdmin($description) . "</div>\n" : '';

    // taxonomy_get_tree() honors access controls.
    $depth = $config->get('site_map_categories_depth');
    if ($depth <= -1) {
      $depth = NULL;
    }
    $tree = taxonomy_get_tree($vid, 0, $depth);
    foreach ($tree as $term) {
      $term->count = site_map_taxonomy_term_count_nodes($term->tid);
      if ($term->count <= $threshold) {
        continue;
      }

      if (\Drupal::service('module_handler')->moduleExists('i18n_taxonomy')) {
        $term->name = i18n_taxonomy_term_name($term, $GLOBALS['language']->language);
      }

      // Adjust the depth of the <ul> based on the change
      // in $term->depth since the $last_depth.
      if ($term->depth > $last_depth) {
        for ($i = 0; $i < ($term->depth - $last_depth); $i++) {
          $output .= "\n<ul>";
        }
      }
      elseif ($term->depth == $last_depth) {
        $output .= '</li>';
      }
      elseif ($term->depth < $last_depth) {
        for ($i = 0; $i < ($last_depth - $term->depth); $i++) {
          $output .= "</li>\n</ul>\n</li>";
        }
      }
      // Display the $term.
      $output .= "\n<li>";
      $term_item = '';
      if ($forum_link) {
        $term_item .= \Drupal::l($term->name, Url::fromRoute('forum.page', array('taxonomy_term' => $term->tid), array('attributes' => array('title' => $term->description__value))));
      }
      elseif ($term->count) {
        $term_item .= \Drupal::l($term->name, Url::fromRoute('entity.taxonomy_term.canonical', array('taxonomy_term' => $term->tid), array('attributes' => array('title' => $term->description__value))));
      }
      else {
        $term_item .= String::checkPlain($term->name);
      }
      if ($config->get('site_map_show_count')) {
        $term_item .= " <span title=\"" . format_plural($term->count, '1 item has this tag', '@count items have this tag') . "\">(" . $term->count . ")</span>";
      }

      // RSS depth.
      $rss_depth = $config->get('site_map_rss_depth');
      if ($config->get('site_map_show_rss_links') != 0 && ($rss_depth == -1 || $term->depth < $rss_depth)) {
        $feed_icon = array(
          '#theme' => 'site_map_feed_icon',
          '#url' => 'taxonomy/term/' . $term->tid . '/feed',
          '#name' => $term->name,
        );
        $rss_link = drupal_render($feed_icon);
        if (\Drupal::service('module_handler')->moduleExists('commentrss') && \Drupal::config('commentrss.settings')->get('commentrss_term')) {
          $feed_icon = array(
            '#theme' => 'site_map_feed_icon',
            '#url' => "crss/term/$term->tid",
            '#type' => 'comment',
            '#name' => $term->name . ' comments',
          );
          $rss_link .= drupal_render($feed_icon);
        }
        if ($config->get('site_map_show_rss_links') == 1) {
          $term_item .= ' ' . $rss_link;
        }
        else {
          $class[] = 'site-map-rss-left';
          $term_item = $rss_link . ' ' . $term_item;
        }
      }

      // Add an alter hook for modules to manipulate the taxonomy term output.
      \Drupal::moduleHandler()->alter(array('site_map_taxonomy_term', 'site_map_taxonomy_term_' . $term->tid), $term_item, $term);

      $output .= $term_item;

      // Reset $last_depth in preparation for the next $term.
      $last_depth = $term->depth;
    }

    // Bring the depth back to where it began, -1.
    if ($last_depth > -1) {
      for ($i = 0; $i < ($last_depth + 1); $i++) {
        $output .= "</li>\n</ul>\n";
      }
    }
    $this->setOption($options, 'site_map_show_titles', 1, 'show_titles', TRUE);

    $class[] = 'site-map-box-terms';
    $class[] = 'site-map-box-terms-' . $vid;
    $attributes = array('class' => $class);

    $site_map_box = array(
      '#theme' => 'site_map_box',
      '#title' => $title,
      '#content' => $output,
      '#attributes' => $attributes,
      '#options' => $options,
    );

    return drupal_render($site_map_box);
  }

}
