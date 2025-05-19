<?php

namespace Drupal\fetchpages\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\node\Entity\Node;

/**
 * Controller for displaying rendered nodes from selected content types.
 */
class ViewNodesController extends ControllerBase {

  /**
   * Renders nodes from selected content types using the selected view mode.
   *
   * @param string $view_mode
   *   The view mode to use ('full' or 'teaser').
   *
   * @return array
   *   A render array.
   */
  public function view($view_mode = 'full') {
    $config = $this->config('fetchpages.settings');
    $content_types = $config->get('content_types') ?? [];

    $build = [
      '#title' => $this->t('View Nodes in @mode mode', ['@mode' => ucfirst($view_mode)]),
    ];

    // Add local tabs.
    $tabs = [
      'full' => [
        'title' => $this->t('Full View'),
        'url' => Url::fromRoute('fetchpages.view_nodes', ['view_mode' => 'full']),
      ],
      'teaser' => [
        'title' => $this->t('Teaser View'),
        'url' => Url::fromRoute('fetchpages.view_nodes', ['view_mode' => 'teaser']),
      ],
    ];

    $build['tabs'] = [
      '#theme' => 'links',
      '#links' => $tabs,
      '#attributes' => ['class' => ['tabs', 'secondary']],
    ];

    // Load and render nodes from selected content types.
    foreach ($content_types as $type) {
      if (!$type) {
        continue;
      }

      $nids = \Drupal::entityQuery('node')
        ->accessCheck(TRUE)
        ->condition('type', $type)
        ->condition('status', 1)
        ->execute();

      $nodes = Node::loadMultiple($nids);

      foreach ($nodes as $node) {
        $build[] = \Drupal::entityTypeManager()
          ->getViewBuilder('node')
          ->view($node, $view_mode);
      }
    }

    return $build;
  }

}
