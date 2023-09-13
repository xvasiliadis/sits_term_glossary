<?php

namespace Drupal\sits_term_glossary\Controller;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Component\Utility\Html;

/**
 * Class TermGlossaryController defenition.
 */
class TermGlossaryController extends ControllerBase {

  /**
   * Drupal\Core\Database\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

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
   * Drupal\Core\Config\ConfigManagerInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entity_repository;

  /**
   * Constructs a new GlossaryController object.
   */
  public function __construct(
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigManagerInterface $config_manager,
    RequestStack $request_stack,
    EntityRepositoryInterface $entity_repository
  ) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->configManager = $config_manager;
    $this->requestStack = $request_stack;
    $this->entity_repository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('config.manager'),
      $container->get('request_stack'),
      $container->get('entity.repository')
    );
  }

  /**
   * Api Search Per Letter.
   *
   * @return string
   *   Return Hello string.
   */
  public function apiSearchPerLetter($letter) {
    $results = ['message' => 'No results found for ' . $letter];
    $status = 200;
    if (!empty($letter) && preg_match("/^[a-zA-Z]$/", mb_strtoupper($letter))) {
      $query = $this->entityQuery->get('taxonomy_term');
      $vid = $this->getTheCorrectVocab();
      $query->condition('name', $letter, 'STARTS_WITH');
      $query->condition('vid', $vid);
      $entity_ids = $query->execute();
      if (count($entity_ids) !== 0) {
        $results = [];
        $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
        $terms = $term_storage->loadMultiple($entity_ids);
        foreach ($terms as $term) {
          $results[] = [
            'name' => $term->getName(),
            'tid' => $term->id(),
            'description' => $term->getDescription(),
          ];
        }
      }
    }
    return new JsonResponse($results, $status);
  }

  /**
   * Helper to get term by id.
   */
  public function apiGetTermById($tid) {
    $results = ['message' => 'No results found for ' . $tid];
    $status = 200;
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $term = $term_storage->load($tid);
    $results = [
      'name' => $term->getName(),
      'tid' => $term->id(),
      'description' => $term->getDescription(),
    ];
    return new JsonResponse($results, $status);
  }

  /**
   * Helper to search per term.
   */
  public function apiSearchPerTerm() {
    $request = $this->requestStack->getCurrentRequest();
    // 200 because js is not checking for error.
    $status = 200;
    $results = ['message' => 'error bad request'];
    if (!empty($request->get('t'))) {
      // @todo make sure this is "safe" here.
      $term = Html::escape(trim($request->get('t')));
      if (!empty($term)) {
        $results = [];
        $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
        $query = $this->entityQuery->get('taxonomy_term');
        $vid = $this->getTheCorrectVocab();
        $query->condition('name', $term, 'CONTAINS');
        $query->condition('vid', $vid);
        $entity_ids = $query->execute();

        if (count($entity_ids) == 0) {
          $query = $this->entityQuery->get('taxonomy_term');
          $vid = $this->getTheCorrectVocab();
          $query->condition('name', "%" . $this->database->escapeLike($term) . "%", 'LIKE');
          $query->condition('vid', $vid);
          $entity_ids = $query->execute();
        }
        if (count($entity_ids) == 0) {
          $results = ['message' => 'No results found for search therm "' . $term . '"'];
        }
        else {
          $terms = $term_storage->loadMultiple($entity_ids);
          foreach ($terms as $term) {
            $results[$term->id()] = [
              'name' => $term->getName(),
              'tid' => $term->id(),
              'description' => $term->getDescription(),
            ];
            // Perhaps fire hook to include more.
          }
        }

      }
    }
    return new JsonResponse($results, $status);
  }

  /**
   * Function to get the vocab name form config.
   *
   * @return string
   *   the vocab name from config.
   */
  private function getTheCorrectVocab() {
    // @todo replace this with config.
    $config = $this->configManager->getConfigFactory();
    $config = $config->get('sits_term_glossary.glossaryconfig');
    if (empty($config->get('vocab'))) {
      throw new \Exception('glossary has not been configured yet');
    }
    return $config->get('vocab');
  }

}
