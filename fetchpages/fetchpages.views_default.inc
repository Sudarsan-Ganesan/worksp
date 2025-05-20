<?php

use Drupal\views\ViewEntityInterface;

/**
 * Implements hook_views_default_views().
 */
function fetchpages_views_default_views() {
  $view = [
    'id' => 'fetchpages_view_nodes',
    'label' => 'FetchPages View Nodes',
    'module' => 'fetchpages',
    'description' => 'Displays nodes from selected content types',
    'base_table' => 'node_field_data',
    'core' => '10.x',
    'display' => [
      'default' => [
        'display_plugin' => 'default',
        'id' => 'default',
        'display_title' => 'Master',
        'position' => 0,
        'display_options' => [],
      ],
      'page_1' => [
        'display_plugin' => 'page',
        'id' => 'page_1',
        'display_title' => 'Page',
        'position' => 1,
        'display_options' => [
          'path' => 'fetchpages/view-nodes',
          'access' => [
            'type' => 'perm',
            'options' => ['perm' => 'access content'],
          ],
          'filters' => [
            'status' => [
              'id' => 'status',
              'table' => 'node_field_data',
              'field' => 'status',
              'value' => '1',
              'plugin_id' => 'boolean',
            ],
          ],
          'pager' => [
            'type' => 'full',
            'options' => ['items_per_page' => 10],
          ],
          'style' => [
            'type' => 'default',
            'options' => [],
          ],
          'row' => [
            'type' => 'entity:node',
            'options' => [
              'view_mode' => 'teaser',
            ],
          ],
        ],
      ],
    ],
  ];

  return ['fetchpages_view_nodes' => $view];
}