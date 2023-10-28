<?php

namespace Drupal\sits_term_glossary\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class TermGlossaryConfigForm definition.
 */
class TermGlossaryConfigForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new GlossaryConfigForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    RequestStack $request_stack
  ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'glossary.glossaryconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'glossary_config_form';
  }

  /**
   * Sets default value.
   */
  public function setItemValue($extra) {
    foreach ($extra['start'] as $key => $val) {
      if (is_array($val)) {
        foreach ($val as $item_key => $item_value) {
          if (is_array($item_value) && !empty($item_value['#type']) && $item_value['#type'] == 'checkbox') {
            $extra['start'][$key][$item_key]['#default_value'] = 1;
          }
        }
      }
    }
    return $extra;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sits_term_glossary.glossaryconfig');

    $vocab_types = $this->entityTypeManager->getStorage('taxonomy_vocabulary')
      ->loadMultiple();
    $vocab_options = [];
    foreach ($vocab_types as $key => $vocab) {
      $vocab_options[$key] = $vocab->get('name');
    }

    $accept_only = [
      'text_with_summary',
      'text_long',
      'string_long',
      'text',
      'entity_reference',
      'entity_reference_revisions',
    ];

    $accept_string = implode(',', $accept_only);

    $form['details'] = [
      '#markup' => $this->t('Fields Accepted for auto scan and term replace are @fields <br/>', [
        '@fields' => $accept_string,
      ]),
    ];

    $form['vocab'] = [
      '#type' => 'select',
      '#title' => $this->t('which vocabulary to use for glossary'),
      '#description' => $this->t('please select which vocab to use for the glossary'),
      '#options' => $vocab_options,
      '#default_value' => $config->get('vocab') ?: [],
      '#required' => TRUE,
    ];

    $options = node_type_get_names();

    $form['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select which content types to auto scan for Glossary links'),
      '#description' => $this->t('Which node types to auto scan for Glossary links (on pre save).'),
      '#options' => $options,
      '#default_value' => $config->get('content_types') ?: [],
      '#suffix' => '<div id="content_ender_here">w</div>',
      '#ajax' => [
        'callback' => [$this, 'handleAjax'],
        'event' => 'change',
        'wrapper' => 'content_ender_here',
        'method' => 'replace',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('loading fields...'),
        ],
      ],
    ];

    if (!empty($config->get('selected_fields'))) {
      $field_array = $config->get('selected_fields');
      $form['content_types']['#suffix'] = '';
      $extra = $this->translateToCheckboxes($field_array);
      $extra = $this->setItemValue($extra);
      $form = array_merge($form, $extra);
    }

    $form['single_match'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Match only once'),
      '#default_value' => $config->get('single_match'),
      '#description' => $this->t('If enabled, will only match a single, case-sensitive occurrence of the term.'),
    ];

    $form['integration_type'] = [
      '#type' => 'radios',
      '#title' => 'Select integration type',
      '#default_value' => $config->get('integration_type') ?: '',
      '#options' => [
        'custom_js' => $this->t('CUSTOM JS: span class (glos-term) and Add "data-gterm=" which is the term id'),
        'default' => $this->t('Jquery ui dialog with the term name and description'),
      ],
      '#description' => $this->t('This sets what will happen when a glossary term is found in content if <br/> if you select "CUSTOM JS" its up to you to handle the javascript side of what happens when some one clicks a term'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Function to handle ajax call back.
   */
  public function handleAjax(array &$form, FormStateInterface $form_state) {
    $raw = $form_state->getValue('content_types');
    $selected = [];
    foreach ($raw as $key => $val) {
      if ($key === $val) {
        $selected[] = $val;
      }
    }

    $output = ['#markup' => '<div id="content_ender_here">Please select some content</div>'];

    if (count($selected) != 0) {
      // $output_string = '<div id="content_ender_here">';
      // $output_string .= $this->handleSelectedContent($selected);
      // $output_string .= '</div>';
      // $output = ['#markup' => $output_string];
      $output = $this->handleSelectedContent($selected);

    }
    return $output;
  }

  /**
   * Helper to handle field select.
   */
  public function handleSelectedContent($selected) {
    $return = 'thinking...';
    $field_stuff = [];

    $not_needed = [
      "nid",
      "uuid",
      "vid",
      "langcode",
      "type",
      "revision_timestamp",
      "revision_uid",
      "revision_log",
      "status",
      "uid",
      "created",
      "changed",
      "promote",
      "sticky",
      "default_langcode",
      "revision_default",
      "revision_translation_affected",
      "path",
      "menu_link",
      "moderation_state",
    ];

    $accept_only = [
      'text_with_summary',
      'text_long',
      'string_long',
      'text',
      'entity_reference',
      'entity_reference_revisions',
    ];

    foreach ($selected as $type) {
      $field_stuff[$type] = [];
      $bundle_fields = $this->entityFieldManager->getFieldDefinitions('node', $type);
      foreach ($bundle_fields as $key => $data) {
        if (!in_array($key, $not_needed)) {
          // @todo exclude by type here.
          // $field_stuff[$type][] = $key;
          if (get_class($data) == 'Drupal\field\Entity\FieldConfig') {
            try {
              $field_type = $data->get('field_type');
              if (in_array($field_type, $accept_only)) {
                $field_stuff[$type][] = $key;
              }
            }
            catch (\Exception $e) {
              // @todo probably log this.
            }
          }
        }
      }
    }

    if (!empty($field_stuff)) {
      $value = $this->translateToCheckboxes($field_stuff);
      $return = $value;
    }
    return $return;
  }

  /**
   * Helper to make Checkboxes.
   */
  public function translateToCheckboxes($field_array) {
    $results = [];
    $results['start'] = [
      '#type' => 'fieldset',
      '#title' => 'Fields',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#description' => $this->t('Each field selected will be scanned for glossary text on content save.'),
      '#prefix' => '<div id="content_ender_here">',
      '#suffix' => '</div>',
    ];

    foreach ($field_array as $key => $val) {
      $results['start'][$key] = [];
      $results['start'][$key] = [
        '#type' => 'fieldset',
        '#title' => $key,
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      ];
      foreach ($val as $field_name) {
        $results['start'][$key][$field_name] = [
          '#type' => 'checkbox',
          '#title' => $field_name,
          '#name' => $key . '~' . $field_name,
        ];
      }
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $types = $form_state->getValue('content_types');
    $selected = [];
    foreach ($types as $key => $val) {
      if ($key == $val) {
        $selected[] = $val;
      }
    }

    $data = [];
    if (!empty($selected)) {
      foreach ($selected as $content_type) {
        foreach ($this->requestStack->getCurrentRequest()->request->all() as $key => $value) {
          if ((!empty($content_type) && strpos($key, $content_type) !== FALSE)) {
            $explode = explode('~', $key);
            $data[$content_type][] = $explode[1];
          }
        }
      }
    }

    $this->configFactory->getEditable('sits_term_glossary.glossaryconfig')
      ->set('vocab', $form_state->getValue('vocab'))
      ->set('content_types', $form_state->getValue('content_types'))
      ->set('selected_fields', $data)
      ->set('integration_type', $form_state->getValue('integration_type'))
      ->set('single_match', $form_state->getValue('single_match'))
      ->save();
  }

}
