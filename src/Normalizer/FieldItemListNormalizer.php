<?php

namespace Drupal\citation_type_selector\Normalizer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\islandora_citations\Normalizer\NormalizerBase;

/**
 * Normalizes field item list into an array structure.
 */
class FieldItemListNormalizer extends NormalizerBase {


  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = FieldItemListInterface::class;

  /**
   * Constructs a CustomNormalizer object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The path alias manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item_list, $format = NULL, array $context = []) {
    $field_item_values = [];
    $config = $this->configFactory->getEditable('citation_type_selector.settings');
    $csl_field = $config->get('csl_field');
    if ($field_item_list->getName() == $csl_field) {
      $node = $field_item_list->getEntity();
      $genre_field = $config->get('genre_field');
      if (!$node->get($genre_field)->isEmpty()) {
        $genre_id = $node->get($genre_field)->first()->getValue()['target_id'];
      }
      $genre_id = $genre_id ?? NULL;
      $term_settings = $config->get('term_settings');
      if (!$node->get($csl_field)->isEmpty()) {
        $csl_id = $node->get($csl_field)->first()->getValue()['target_id'];
      }
      $csl_id = $csl_id ?? NULL;
      $csl_val = $term_settings[$genre_id];
      if ($csl_val != $csl_id) {
        $node->set($csl_field, $csl_val);
        $node->save();
      }
    }
    $field_item_values = [];

    /** @var \Drupal\Core\Field\FieldItemListInterface $field_item_list */
    foreach ($field_item_list as $field_item) {
      // If value is empty, do not process.
      if ($field_item->isEmpty()) {
        continue;
      }

      $context['normalized-field-list'] = $field_item_values;

      // If there are multiple csl fields mapped, get array values for each one.
      if ($context['csl-map']) {
        foreach ($context['csl-map'] as $cslField) {
          /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
          $field_item_values[$cslField][] = $this->serializer->normalize($field_item, $format, $context);
        }
      }
      else {
        $field_item_values = $this->serializer->normalize($field_item, $format, $context);
      }
    }

    return $field_item_values;
  }

}
