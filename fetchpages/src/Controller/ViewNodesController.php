<?php

namespace Drupal\fetchpages\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Link;

class ViewNodesController extends ControllerBase {

  public function view() {
    $config = $this->config('fetchpages.settings');
    $content_types = array_filter($config->get('content_types') ?? []);

    if (empty($content_types)) {
      return [
        '#markup' => $this->t('No content types selected.'),
      ];
    }

    $header = [
      $this->t('Title'),
      $this->t('Type'),
      $this->t('Published'),
      $this->t('Operations'),
    ];

    $rows = [];
    $nids = \Drupal::entityQuery('node')
      ->condition('type', $content_types, 'IN')
      ->accessCheck(TRUE)
      ->execute();

    $nodes = Node::loadMultiple($nids);

    foreach ($nodes as $node) {
      $rows[] = [
        $node->toLink()->toString(),
        $node->bundle(),
        $node->isPublished() ? $this->t('Yes') : $this->t('No'),
        Link::fromTextAndUrl('Edit', Url::fromRoute('entity.node.edit_form', ['node' => $node->id()]))->toString(),
      ];
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No nodes found for the selected content types.'),
    ];
  }
}
