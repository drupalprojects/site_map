<?php

/**
 * @file
 * Contains \Drupal\site_map\Controller\SitemapController.
 */

namespace Drupal\site_map\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\String;

/**
 * Controller routines for update routes.
 */
class SitemapController implements ContainerInjectionInterface {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs update status data.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module Handler Service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * Render the latest maps for the taxonomy tree.
   *
   * @return string
   *   Returns HTML string of site map for taxonomies.
   */
  public function getTaxonomys() {
    $output = '';
    $config = \Drupal::config('site_map.settings');
    $vids = array_filter($config->get('site_map_show_vocabularies'));
    if (!empty($vids)) {
      $vocabularies = entity_load_multiple('taxonomy_vocabulary', $vids);
      foreach ($vocabularies as $vocabulary) {
        $output .= $this->getTaxonomyTree($vocabulary->vid, $vocabulary->name, $vocabulary->description);
      }
    }

    return $output;
  }

  /**
   * Render the taxonomy tree.
   *
   * @param string $vid
   *   The results of taxonomy_get_tree() with optional 'count' fields.
   * @param string $name
   *   An optional name for the tree. (Default: NULL)
   * @param string $description
   *   $description An optional description of the tree. (Default: NULL)
   *
   * @return string
   *   A string representing a rendered tree.
   */
  public function getTaxonomyTree($vid, $name = NULL, $description = NULL) {
    $output = '';
    $options = array();
    $class = array();

    $title = $name ? String::checkPlain(t($name)) : '';

    $config = \Drupal::config('site_map.settings');
    $threshold = $config->get('site_map_term_threshold');

    $forum_link = FALSE;

    $last_depth = -1;

    $output .= !empty($description) && $config->get('site_map_show_description') ? '<div class="description">' . filter_xss_admin($description) . "</div>\n" : '';

    // taxonomy_get_tree() honors access controls.
    $tree = taxonomy_get_tree($vid);
    foreach ($tree as $term) {
      $term->count = count(taxonomy_select_nodes($term->tid));
      if ($term->count <= $threshold) {
        continue;
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
        $term_item .= l($term->name, 'forum/' . $term->tid, array('attributes' => array('title' => $term->description__value)));
      }
      elseif ($term->count) {
        $term_item .= l($term->name, 'taxonomy/term/' . $term->tid, array('attributes' => array('title' => $term->description__value)));
      }
      else {
        $term_item .= String::checkPlain($term->name);
      }
      if ($config->get('site_map_show_count')) {
        $term_item .= " ($term->count)";
      }

      if ($config->get('site_map_show_rss_links') != 0) {
        $rss_link = theme('site_map_feed_icon', array('url' => 'taxonomy/term/' . $term->tid . '/feed'));
        if ($config->get('site_map_show_rss_links') == 1) {
          $term_item .= ' ' . $rss_link;
        }
        else {
          $class[] = 'site-map-rss-left';
          $term_item = $rss_link . ' ' . $term_item;
        }
      }

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

    $output = theme('site_map_box', array(
      'title' => $title,
      'content' => $output,
      'attributes' => $attributes,
      'options' => $options,
    ));

    return $output;
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
  public function setOption(&$options, $option_string, $equal_param, $set_string, $set_value) {
    $config = \Drupal::config('site_map.settings');
    if ($config->get($option_string) == $equal_param) {
      $options[$set_string] = $set_value;
    }
  }

  public function buildPage() {
    $config = \Drupal::config('site_map.settings');
    drupal_set_title($config->get('site_map_page_title'));
    if ($config->get('site_map_css') != 1) {
      drupal_add_css(drupal_get_path('module', 'site_map') . '/site_map.theme.css');
    }

    return theme('site_map');
  }

}
