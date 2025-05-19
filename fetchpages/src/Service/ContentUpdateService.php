<?php

namespace Drupal\fetchpages\Service;

use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;

class ContentUpdateService {

  protected $httpClient;
  protected $entityTypeManager;

  public function __construct(ClientInterface $http_client, EntityTypeManagerInterface $entity_type_manager) {
    $this->httpClient = $http_client;
    $this->entityTypeManager = $entity_type_manager;
  }

  public function fetchAndUpdateContent(array $content_types) {
    try {
      $response = $this->httpClient->post('https://drupaldesk.ddev.site/custom-api/update-content', [
        'json' => ['content_types' => $content_types],
      ]);
      $data = json_decode($response->getBody(), TRUE);

      if (!empty($data['results'])) {
        foreach ($content_types as $type) {
          $nids = \Drupal::entityQuery('node')
            ->condition('type', $type)
            ->execute();
          $nodes = Node::loadMultiple($nids);

          foreach ($nodes as $node) {
            // Assuming the API returns update data per content type.
            if (!empty($data['results'][$type])) {
              $update_data = $data['results'][$type];

              // Example: update a custom field
              if (isset($update_data['field_summary']) && $node->hasField('field_summary')) {
                $node->set('field_summary', $update_data['field_summary']);
              }

              $node->save();
            }
          }
        }
      }
    } catch (\Exception $e) {
      \Drupal::logger('fetchpages')->error($e->getMessage());
    }
  }
}
