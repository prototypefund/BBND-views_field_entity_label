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
    $options['link_only_if_access'] = ['default' => FALSE];
    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_entity'] = [
      '#title' => $this->t('Link to entity'),
      '#description' => $this->t('Make entity label a link to entity page.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_entity']),
    ];
    $form['link_only_if_access'] = [
      '#title' => $this->t('Link only if access'),
      '#description' => $this->t('Only create a link if the user can access the link target.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_only_if_access']),
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  public function render(ResultRow $values) {
    $entity = $this->getEntityTranslation($this->getEntity($values), $values);

    $cacheability = (new CacheableMetadata())
      ->addCacheableDependency($entity);

    if (!empty($this->options['link_to_entity'])) {
      try {
        $url = $entity->toUrl();
        if ($this->options['link_only_if_access']) {
          $urlAccess = $url->access(NULL, TRUE);
          $cacheability->addCacheableDependency($urlAccess);
          $makeLink = $urlAccess->isAllowed();
        }
        else {
          $makeLink = TRUE;
        }
        $this->options['alter']['url'] = $url;
        $this->options['alter']['make_link'] = $makeLink;
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
    $cacheability->applyTo($build);
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
