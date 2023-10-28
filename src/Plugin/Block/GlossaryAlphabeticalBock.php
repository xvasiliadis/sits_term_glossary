<?php

namespace Drupal\sits_term_glossary\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'GlossaryAlphabeticalBock' block.
 *
 * @Block(
 *  id = "glossary_alphabetical_bock",
 *  admin_label = @Translation("Glossary alphabetical bock"),
 * )
 */
class GlossaryAlphabeticalBock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Symfony\Component\DependencyInjection\ContainerAwareInterface definition.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerAwareInterface
   */
  protected $entityQuery;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Drupal\Core\Config\ConfigManagerInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * Constructs a new GlossaryAlphabeticalBock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManagerInterface definition.
   * @param \Symfony\Component\DependencyInjection\ContainerAwareInterface $entity_query
   *   The ContainerAwareInterface definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The RequestStack definition.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The ConfigManagerInterface def.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ContainerAwareInterface $entity_query,
    RequestStack $request_stack,
    ConfigManagerInterface $config_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityQuery = $entity_query;
    $this->requestStack = $request_stack;
    $this->configManager = $config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity.query'),
      $container->get('request_stack'),
      $container->get('config.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'search_type' => 'all',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['search_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Search box only'),
      '#default_value' => $this->configuration['search_type'],
      '#required' => TRUE,
      '#options' => [
        'search_only' => $this->t('Search Box only'),
        'all' => $this->t('Search box and letters'),
        'only_letters' => $this->t('Only Letters'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['search_type'] = $form_state->getValue('search_type');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'glossary_alphabetical_bock';
    $build['#type'] = $this->configuration['search_type'];
    $build['#attached']['library'][] = 'sits_term_glossary/glossary.alpha';
    return $build;
  }

}
