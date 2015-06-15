<?php

/**
 * @file
 * Contains \Drupal\site_map\Form\SitemapSettingsForm.
 */

namespace Drupal\site_map\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\Context\ContextInterface;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form for sitemap.
 */
class SitemapSettingsForm extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a SitemapSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\Context\ContextInterface $context
   *   The configuration context.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactory $config_factory, ContextInterface $context, ModuleHandler $module_handler) {
    parent::__construct($config_factory, $context);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.context.free'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'site_map_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('site_map.settings');

    $form['site_map_page_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Page title'),
      '#default_value' => $config->get('site_map_page_title'),
      '#description' => $this->t('Page title that will be used on the <a href="@link">site map page</a>.', array('@link' => url('sitemap'))),
    );

    $site_map_message = $config->get('site_map_message');
    $form['site_map_message'] = array(
      '#type' => 'text_format',
      '#format' => isset($site_map_message['format']) ? $site_map_message['format'] : NULL,
      '#title' => $this->t('Site map message'),
      '#default_value' => $site_map_message['value'],
      '#description' => $this->t('Define a message to be displayed above the site map.'),
    );

    $form['site_map_content'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Site map content'),
    );
    $form['site_map_content']['site_map_show_front'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show front page'),
      '#default_value' => $config->get('site_map_show_front'),
      '#description' => $this->t('When enabled, this option will include the front page in the site map.'),
    );
    $form['site_map_content']['site_map_show_titles'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show titles'),
      '#default_value' => $config->get('site_map_show_titles'),
      '#description' => $this->t('When enabled, this option will show titles. Disable to not show section titles.'),
    );

    if ($this->moduleHandler->moduleExists('book')) {
      $book_options = array();
      foreach (book_get_books() as $book) {
        $book_options[$book['mlid']] = $book['title'];
      }
      $form['site_map_content']['site_map_show_books'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Books to include in the site map'),
        '#default_value' => $config->get('site_map_show_books'),
        '#options' => $book_options,
        '#multiple' => TRUE,
      );
      $form['site_map_content']['site_map_books_expanded'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Show books expanded'),
        '#default_value' => $config->get('site_map_books_expanded'),
        '#description' => $this->t('When enabled, this option will show all children pages for each book.'),
      );
    }

    $menu_options = array();
    $menu_options = menu_get_menus();
    $form['site_map_content']['site_map_show_menus'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Menus to include in the site map'),
      '#default_value' => $config->get('site_map_show_menus'),
      '#options' => $menu_options,
      '#multiple' => TRUE,
    );
    $form['site_map_content']['site_map_show_menus_hidden'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show disabled menu items'),
      '#default_value' => $config->get('site_map_show_menus_hidden'),
      '#description' => $this->t('When enabled, hidden menu links will also be shown.'),
    );

    if ($this->moduleHandler->moduleExists('faq')) {
      $form['site_map_content']['site_map_show_faq'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Show FAQ content'),
        '#default_value' => $config->get('site_map_show_faq'),
        '#description' => $this->t('When enabled, this option will include the content from the FAQ module in the site map.'),
      );
    }

    $vocab_options = array();
    if ($this->moduleHandler->moduleExists('taxonomy')) {
      foreach (taxonomy_vocabulary_load_multiple() as $vocabulary) {
        $vocab_options[$vocabulary->vid] = $vocabulary->name;
      }
    }
    $form['site_map_content']['site_map_show_vocabularies'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Categories to include in the site map'),
      '#default_value' => $config->get('site_map_show_vocabularies'),
      '#options' => $vocab_options,
      '#multiple' => TRUE,
    );
    $form['site_map_taxonomy_options'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Categories settings'),
    );
    $form['site_map_taxonomy_options']['site_map_show_description'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show category description'),
      '#default_value' => $config->get('site_map_show_description'),
      '#description' => $this->t('When enabled, this option will show the category description.'),
    );
    $form['site_map_taxonomy_options']['site_map_show_count'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show node counts by categories'),
      '#default_value' => $config->get('site_map_show_count'),
      '#description' => $this->t('When enabled, this option will show the number of nodes in each taxonomy term.'),
    );
    $form['site_map_taxonomy_options']['site_map_categories_depth'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Categories depth'),
      '#default_value' => $config->get('site_map_categories_depth'),
      '#size' => 3,
      '#maxlength' => 10,
      '#description' => $this->t('Specify how many subcategories should be included on the categorie page. Enter "all" to include all subcategories, "0" to include no subcategories, or "-1" not to append the depth at all.'),
    );
    $form['site_map_taxonomy_options']['site_map_term_threshold'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Category count threshold'),
      '#default_value' => $config->get('site_map_term_threshold'),
      '#size' => 3,
      '#description' => $this->t('Only show categories whose node counts are greater than this threshold. Set to -1 to disable.'),
    );
    $form['site_map_taxonomy_options']['site_map_forum_threshold'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Forum count threshold'),
      '#default_value' => $config->get('site_map_forum_threshold'),
      '#size' => 3,
      '#description' => $this->t('Only show forums whose node counts are greater than this threshold. Set to -1 to disable.'),
    );

    $form['site_map_rss_options'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('RSS settings'),
    );
    $form['site_map_rss_options']['site_map_rss_front'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('RSS feed for front page'),
      '#default_value' => $config->get('site_map_rss_front'),
      '#description' => $this->t('The RSS feed for the front page, default is rss.xml.'),
    );
    $form['site_map_rss_options']['site_map_show_rss_links'] = array(
      '#type' => 'select',
      '#title' => $this->t('Include RSS links'),
      '#default_value' => $config->get('site_map_show_rss_links'),
      '#options' => array(
        0 => $this->t('None'),
        1 => $this->t('Include on the right side'),
        2 => $this->t('Include on the left side'),
      ),
      '#description' => $this->t('When enabled, this option will show links to the RSS feeds for each category and blog.'),
    );
    $form['site_map_rss_options']['site_map_rss_depth'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('RSS feed depth'),
      '#default_value' => $config->get('site_map_rss_depth'),
      '#size' => 3,
      '#maxlength' => 10,
      '#description' => $this->t('Specify how many subcategories should be included in the RSS feed. Enter "all" to include all subcategories or "0" to include no subcategories.'),
    );

    $form['site_map_css_options'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('CSS settings'),
    );
    $form['site_map_css_options']['site_map_css'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Do not include site map CSS file'),
      '#default_value' => $config->get('site_map_css'),
      '#description' => $this->t("If you don't want to load the included CSS file you can check this box."),
    );

    // Make use of the Checkall module if it's installed.
    if ($this->moduleHandler->moduleExists('checkall')) {
      $form['site_map_content']['site_map_show_books']['#checkall'] = TRUE;
      $form['site_map_content']['site_map_show_menus']['#checkall'] = TRUE;
      $form['site_map_content']['site_map_show_vocabularies']['#checkall'] = TRUE;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $values = $form_state['values'];

    $this->configFactory->get('site_map.settings')
      ->set('site_map_page_title', $values['site_map_page_title'])
      ->set('site_map_message.value', $values['site_map_message']['value'])
      ->set('site_map_message.format', $values['site_map_message']['format'])
      ->set('site_map_show_front', $values['site_map_show_front'])
      ->set('site_map_show_titles', $values['site_map_show_titles'])
      ->set('site_map_show_menus', $values['site_map_show_menus'])
      ->set('site_map_show_menus_hidden', $values['site_map_show_menus_hidden'])
      ->set('site_map_show_vocabularies', $values['site_map_show_vocabularies'])
      ->set('site_map_show_description', $values['site_map_show_description'])
      ->set('site_map_show_count', $values['site_map_show_count'])
      ->set('site_map_categories_depth', $values['site_map_categories_depth'])
      ->set('site_map_term_threshold', $values['site_map_term_threshold'])
      ->set('site_map_forum_threshold', $values['site_map_forum_threshold'])
      ->set('site_map_rss_front', $values['site_map_rss_front'])
      ->set('site_map_show_rss_links', $values['site_map_show_rss_links'])
      ->set('site_map_rss_depth', $values['site_map_rss_depth'])
      ->set('site_map_css', $values['site_map_css']);

    if ($this->moduleHandler->moduleExists('book')) {
      $this->configFactory->get('site_map.settings')
        ->set('site_map_show_books', $values['site_map_show_books'])
        ->set('site_map_books_expanded', $values['site_map_books_expanded']);
    }

    if ($this->moduleHandler->moduleExists('faq')) {
      $this->configFactory->get('site_map.settings')
        ->set('site_map_show_faq', $values['site_map_show_faq']);
    }

    $this->configFactory->get('site_map.settings')
      ->save();

    parent::submitForm($form, $form_state);
  }

}
