<?php

namespace Drupal\fetchpages\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class UpdateContentApiController extends ControllerBase {

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  public function handle(Request $request) {
    if ($request->getMethod() !== 'POST') {
      return new JsonResponse(['error' => 'Only POST requests are allowed'], 405);
    }

    $data = json_decode($request->getContent(), TRUE);

    if (!isset($data['content_types']) || !is_array($data['content_types']) || empty($data['content_types'])) {
      return new JsonResponse(['error' => 'Invalid content_types provided'], 400);
    }

    $fields = $data['fields'] ?? [];

    $response = ['results' => []];
    $valid_bundles = array_keys($this->entityTypeManager->getStorage('node_type')->loadMultiple());

    foreach ($data['content_types'] as $ct) {
      if (!in_array($ct, $valid_bundles)) {
        continue;
      }

      $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties(['type' => $ct]);

      foreach ($nodes as $node) {
        $item = [
          'nid' => $node->id(),
          'title' => $node->label(),
        ];

        if (!empty($fields[$ct])) {
          foreach ($fields[$ct] as $field_name) {
            if ($node->hasField($field_name)) {
              $item[$field_name] = $node->get($field_name)->value ?? '';
            }
          }
        }

        $response['results'][$ct][] = $item;
      }
    }

    return new JsonResponse($response);
  }
}
