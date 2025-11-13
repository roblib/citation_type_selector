<?php

declare(strict_types=1);

namespace Drupal\citation_type_selector\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Citation type selector settings for this site.
 */
final class CitationSelectSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CitationSelectSettingsForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'citation_type_selector_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['citation_type_selector.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load the stored configuration.
    $config = $this->config('citation_type_selector.settings');

    // Get all vocabularies.
    $vocabularies = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
    $vocabulary_options = [];
    foreach ($vocabularies as $vocabulary) {
      $vocabulary_options[$vocabulary->id()] = $vocabulary->label();
    }

    // Get CSL types.
    $selected_csl_vocab = $form_state->getValue('csl_vocab', $config->get('csl_vocab'));
    $csl_terms = $terms = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadTree($selected_csl_vocab);
    $csl_options = [];
    foreach ($csl_terms as $cls_term) {
      $csl_options[$cls_term->tid] = $cls_term->name;
    }
    // Get fields from bundle.
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'islandora_object');
    $entity_reference_fields = [];
    foreach ($fields as $field_name => $field_definition) {
      if ($field_definition->getType() === 'entity_reference') {
        if (str_starts_with($field_name, 'field')) {
          $entity_reference_fields[$field_name] = $field_definition->getLabel();
        }
      }
    }
    asort($entity_reference_fields);

    $form['#attached']['library'][] = 'citation_type_selector/citation_type_selector_styles';
    $form['side_by_side'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['side-by-side-container'],
      ],
    ];

    // Islandora Object Field.
    $form['side_by_side']['genre_field'] = [
      '#type' => 'select',
      '#description' => $this->t("The content model's genre field."),
      '#title' => $this->t('Content Classification Field'),
      '#options' => $entity_reference_fields,
      '#default_value' => $config->get('genre_field') ?? '',
    ];
    // Vocabulary selection field.
    $form['side_by_side']['genre_vocabulary'] = [
      '#type' => 'select',
      '#title' => $this->t('Classification Vocabulary'),
      '#description' => $this->t("The object classification vocabulary."),
      '#options' => $vocabulary_options,
      '#default_value' => $config->get('genre_vocabulary') ?? '',
      '#ajax' => [
        'callback' => '::updateTermsTable',
        'wrapper' => 'terms-table-wrapper',
      ],
    ];

    $form['side_by_side']['csl_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Citation Type Field'),
      '#description' => $this->t("The content model's citation style field."),
      '#options' => $entity_reference_fields,
      '#default_value' => $config->get('csl_field') ?? '',
    ];
    // Field selection field.
    $form['side_by_side']['csl_vocab'] = [
      '#type' => 'select',
      '#title' => $this->t('Citation Type Vocabulary'),
      '#description' => $this->t("The object CSL Style vocabulary."),
      '#options' => $vocabulary_options,
      '#default_value' => $config->get('csl_vocab') ?? '',
      '#ajax' => [
        'callback' => '::updateTermsTable',
        'wrapper' => 'terms-table-wrapper',
      ],
    ];

    // Get the selected vocabulary.
    $selected_vocabulary = $form_state->getValue('genre_vocabulary', $config->get('genre_vocabulary'));

    // Display terms in a table format.
    $form['terms_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Type'),
        $this->t('CSL Style'),
      ],
      '#prefix' => '<div id="terms-table-wrapper">',
      '#suffix' => '</div>',
    ];

    if ($selected_vocabulary) {
      // Load terms for the selected vocabulary.
      $terms = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->loadTree($selected_vocabulary);

      foreach ($terms as $term) {
        $form['terms_table'][$term->tid]['term_name'] = [
          '#plain_text' => $term->name,
        ];

        // Add a dropdown for each term.
        $form['terms_table'][$term->tid]['dropdown'] = [
          '#type' => 'select',
          '#options' => $csl_options,
          '#default_value' => $config->get('term_settings')[$term->tid] ?? '',
        ];
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * AJAX callback to update the terms table.
   */
  public function updateTermsTable(array &$form, FormStateInterface $form_state) {
    return $form['terms_table'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the selected vocabulary and term dropdown settings.
    $selected_vocabulary = $form_state->getValue('genre_vocabulary');
    $selected_field = $form_state->getValue('csl_vocab');
    $genre_field = $form_state->getValue('genre_field');
    $csl_field = $form_state->getValue('csl_field');
    $term_settings = [];

    if (!empty($form_state->getValue('terms_table'))) {
      foreach ($form_state->getValue('terms_table') as $tid => $term_data) {
        $term_settings[$tid] = $term_data['dropdown'];
      }
    }

    $this->config('citation_type_selector.settings')
      ->set('genre_vocabulary', $selected_vocabulary)
      ->set('term_settings', $term_settings)
      ->set('csl_vocab', $selected_field)
      ->set('genre_field', $genre_field)
      ->set('csl_field', $csl_field)
      ->save();

    $this->messenger()->addMessage($this->t('The settings have been saved.'));
  }

}
