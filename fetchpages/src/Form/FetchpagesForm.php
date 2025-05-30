<?php

namespace Drupal\fetchpages\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldConfig;
use Drupal\fetchpages\Service\ContentUpdateService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Entity\View;
use Drupal\Core\Entity\EntityFieldManagerInterface;

class FetchpagesForm extends ConfigFormBase {

  protected $contentUpdateService;

  public function __construct(ContentUpdateService $contentUpdateService) {
    $this->contentUpdateService = $contentUpdateService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('fetchpages.content_update')
    );
  }

  public function getFormId() {
    return 'fetchpages_form';
  }

  protected function getEditableConfigNames() {
    return ['fetchpages.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fetchpages.settings');
    $content_types = NodeType::loadMultiple();
    $options = [];

    foreach ($content_types as $type) {
      $options[$type->id()] = $type->label();
    }

    $form['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select content types'),
      '#options' => $options,
      '#default_value' => $config->get('content_types') ?: [],
      '#ajax' => [
        'callback' => '::updateFieldsCallback',
        'wrapper' => 'fields-wrapper',
      ],
    ];

    $form['fields_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'fields-wrapper'],
    ];

    $selected_content_types = array_filter($form_state->getValue('content_types') ?? $config->get('content_types') ?? []);

    foreach ($selected_content_types as $ct) {
      if (!isset($content_types[$ct])) {
        continue;
      }

      $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $ct);
      $field_options = [];

      foreach ($field_definitions as $field_name => $definition) {
        if ($definition->getFieldStorageDefinition()->isBaseField()) {
          continue;
        }
        $field_options[$field_name] = $definition->getLabel();
      }

      $form['fields_wrapper']["fields_{$ct}"] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Select fields for @type', ['@type' => $content_types[$ct]->label()]),
        '#options' => $field_options,
        '#default_value' => $form_state->getValue("fields_{$ct}") ?: [],
      ];
    }

    if ($results = $form_state->get('api_results')) {
      $rows = [];

      // Pre-calculate node counts per content type.
      $type_counts = [];
      foreach ($results as $type => $nodes) {
        $type_counts[$type] = count($nodes);
      }

      // Generate rows.
      foreach ($results as $type => $nodes) {
        foreach ($nodes as $node) {
          $row = [
            $type,
            $node['title'] ?? 'Untitled',
            $node['nid'] ?? 'N/A',
            $type_counts[$type], // Add node count per row
          ];
          foreach ($node as $key => $value) {
            if (!in_array($key, ['nid', 'title']) && is_scalar($value)) {
              $row[] = $value;
            }
          }
          $rows[] = ['data' => $row];
        }
      }

      // Add summary block.
      if (!empty($type_counts)) {
        $summary = [];
        foreach ($type_counts as $type => $count) {
          $type_label = isset($content_types[$type]) ? $content_types[$type]->label() : $type;
          $summary[] = $type_label . ': ' . $count . ' node(s)';
        }

        $form['selected_summary'] = [
          '#type' => 'item',
          '#title' => $this->t('Selected Content Types and Node Counts'),
          '#markup' => '<ul><li>' . implode('</li><li>', $summary) . '</li></ul>',
        ];
      }

      // Build headers
      $headers = [
        $this->t('Content Type'),
        $this->t('Title'),
        $this->t('Node ID'),
        $this->t('Node Count'),
      ];

      if (!empty($rows)) {
        $extra_fields = array_keys($results[array_key_first($results)][0]);
        $extra_fields = array_filter($extra_fields, fn($k) => !in_array($k, ['nid', 'title']));
        $headers = array_merge($headers, array_map([$this, 't'], $extra_fields));
      }

      $form['fetched_nodes'] = [
        '#type' => 'table',
        '#header' => $headers,
        '#rows' => $rows,
        '#empty' => $this->t('No nodes found.'),
      ];
    }
    
      $form['view_nodes_link'] = [
        '#type' => 'link',
        '#title' => $this->t('View Selected Nodes'),
        '#url' => \Drupal\Core\Url::fromRoute('fetchpages.view_nodes'),
        '#attributes' => ['class' => ['button', 'button--primary']],
      ];

      $form['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset selections'),
        '#submit' => ['::resetForm'],
        '#limit_validation_errors' => [], // So it doesn't validate required fields on reset
      ];

    return parent::buildForm($form, $form_state);
  }

  public function updateFieldsCallback(array &$form, FormStateInterface $form_state) {
    return $form['fields_wrapper'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_content_types = array_filter($form_state->getValue('content_types') ?? []);
    $this->config('fetchpages.settings')->set('content_types', $selected_content_types)->save();

    $this->configFactory->getEditable('fetchpages.settings')
    ->set('content_types', array_filter($form_state->getValue('content_types')))
    ->save();

    $fields_per_type = [];
    foreach ($selected_content_types as $ct) {
      $fields = array_filter($form_state->getValue("fields_{$ct}") ?? []);
      $fields_per_type[$ct] = array_values($fields);
    }

    try {
      $client = \Drupal::httpClient();
      $url = \Drupal::request()->getSchemeAndHttpHost() . '/custom-api/update-content';

      $response = $client->post($url, [
        'json' => [
          'content_types' => array_values($selected_content_types),
          'fields' => $fields_per_type,
        ],
      ]);

      $data = json_decode($response->getBody(), true);

      if (!empty($data['results'])) {
        $form_state->set('api_results', $data['results']);
        $form_state->setRebuild();
        $this->messenger()->addStatus($this->t('Fetched node data successfully.'));
      }
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Failed to fetch from API: @msg', ['@msg' => $e->getMessage()]));
    }

      // Views dynamically after the config form is saved
    $field_manager = \Drupal::service('entity_field.manager');

    foreach ($selected_content_types as $ct) {
      $view_id = 'fetchpages_' . $ct . '_view';
      $view_exists = View::load($view_id);

      if (!$view_exists) {
        $view = View::create([
          'id' => $view_id,
          'label' => 'FetchPages - ' . ucfirst($ct) . ' View',
          'description' => 'Auto-generated view for ' . $ct . ' content type.',
          'base_table' => 'node_field_data',
          'core' => '10.x',
        ]);

        $view->addDisplay('default', 'Defaults', 'default');
        $view->addDisplay('page', 'Page', 'page_1');

        $displays = $view->get('display');

        // Add path and filters to page display
        $displays['page_1']['display_options']['path'] = 'fetchpages/' . $ct;
        $displays['page_1']['display_options']['menu'] = [
          'type' => 'normal',
          'title' => ucfirst($ct) . ' List',
          'description' => '',
          'weight' => 0,
          'name' => 'main',
          'context' => 0,
        ];
        $displays['page_1']['display_options']['filters']['type'] = [
          'id' => 'type',
          'table' => 'node_field_data',
          'field' => 'type',
          'value' => $ct,
          'group' => 1,
          'exposed' => FALSE,
        ];
        $displays['page_1']['display_options']['filters']['status'] = [
          'id' => 'status',
          'table' => 'node_field_data',
          'field' => 'status',
          'value' => '1',
          'group' => 1,
          'exposed' => FALSE,
        ];

        // ✅ Add relationship to uid → users_field_data
        $displays['default']['display_options']['relationships']['uid'] = [
          'id' => 'uid',
          'table' => 'node_field_data',
          'field' => 'uid',
          'relationship' => 'none',
          'plugin_id' => 'standard',
          'required' => 0,
        ];

        // ✅ Add fields
        $fields_config = [];

        $entity_fields = $field_manager->getFieldDefinitions('node', $ct);
        foreach ($fields_per_type[$ct] as $field_name) {
          if (isset($entity_fields[$field_name])) {
            if ($field_name === 'title') {
              $fields_config['title'] = [
                'id' => 'title',
                'table' => 'node_field_data',
                'field' => 'title',
                'label' => 'Title',
                'plugin_id' => 'standard',
                'entity_type' => 'node',
                'entity_field' => 'title',
                'link_to_entity' => TRUE,
              ];
            }
            else {
              $fields_config[$field_name] = [
                'id' => $field_name,
                'table' => "node__{$field_name}",
                'field' => $field_name,
                'label' => $entity_fields[$field_name]->getLabel(),
                'plugin_id' => 'field',
                'entity_type' => 'node',
                'entity_field' => $field_name,
              ];
            }
          }
        }

        // ✅ Optionally, show the author name from the user entity using the relationship
        $fields_config['uid_name'] = [
          'id' => 'name',
          'table' => 'users_field_data',
          'field' => 'name',
          'relationship' => 'uid',
          'label' => 'Author',
          'plugin_id' => 'standard',
        ];

        $displays['default']['display_options']['fields'] = $fields_config;

        $view->set('display', $displays);
        $view->save();

        $this->messenger()->addStatus($this->t('View "@label" has been created.', ['@label' => $view->label()]));
      }
    }
    parent::submitForm($form, $form_state);
  }

  public function resetForm(array &$form, FormStateInterface $form_state) {
    // Clear the saved config values for your content types and fields.
    $this->config('fetchpages.settings')
      ->clear('content_types')
      ->save();

    // Clear any stored form state values.
    $form_state->setValue('content_types', []);
    
    // Clear fields selections for all content types.
    $content_types = NodeType::loadMultiple();
    foreach ($content_types as $type) {
      $form_state->setValue("fields_{$type->id()}", []);
    }

    // Clear API results stored in form state if any.
    $form_state->set('api_results', NULL);

    // Rebuild the form to show empty selections.
    $form_state->setRebuild(TRUE);
  }

}
