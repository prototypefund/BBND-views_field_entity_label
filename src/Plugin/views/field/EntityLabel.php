<?php

namespace Drupal\views_field_entity_label\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\views\Entity\Render\EntityTranslationRenderTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Entity Label field handler.
 *
 * @ViewsField("views_field_entity_label")
 *
 * @see \Drupal\views\Plugin\views\field\RenderedEntity::render
 * @see \Drupal\views\Plugin\views\area\Entity
 */
class EntityLabel extends FieldPluginBase {

  use EntityTranslationRenderTrait;

  protected string $entityTypeId;

  protected LanguageManagerInterface $languageManager;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->languageManager = $container->get('language_manager');
    return $instance;
  }

  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->entityTypeId = $this->definition['entity_type'];
  }


  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_entity'] = ['default' => FALSE];
    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_entity'] = [
      '#title' => $this->t('Link to entity'),
      '#description' => $this->t('Make entity label a link to entity page.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_entity']),
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  public function render(ResultRow $values) {
    $entity = $this->getEntityTranslation($this->getEntity($values), $values);

    if (!empty($this->options['link_to_entity'])) {
      try {
        $this->options['alter']['url'] = $entity->toUrl();
        $this->options['alter']['make_link'] = TRUE;
      }
      catch (UndefinedLinkTemplateException $e) {
        $this->options['alter']['make_link'] = FALSE;
      }
      catch (EntityMalformedException $e) {
        $this->options['alter']['make_link'] = FALSE;
      }
    }

    $build = [
      '#access' => $entity->access('view label', NULL, TRUE),
      '#plain_text' => $entity->label(),
    ];
    (new CacheableMetadata())
      ->addCacheableDependency($entity)
      ->applyTo($build);
    return $build;
  }

  public function query() {}

  public function usesGroupBy() {
    return FALSE;
  }

  public function getEntityTypeId(): string {
    return $this->entityTypeId;
  }

  protected function getLanguageManager() {
    return $this->languageManager;
  }

  protected function getView() {
    return $this->view;
  }

}
