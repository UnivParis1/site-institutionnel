<?php

namespace Drupal\cmis_extensions\Plugin\Block;

use Drupal\cmis_extensions\Nxql;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Block(
 *   id = "cmis_last_items_block",
 *   admin_label = @Translation("CMIS last items block"),
 *   category = @Translation("CMIS"),
 * )
 */
class LastItemsBlock extends BlockBase implements ContainerFactoryPluginInterface {
  private $foldersProvider;
  private $nuxeoService;

  const FOLDER_LIST  = 'cmis_last_items_block_folder_list';
  const FOLDER_VALUE = 'cmis_last_items_block_folder_value';

  public function __construct(array $config, $id, $definition, $foldersProvider, $nuxeoService) {
    parent::__construct($config, $id, $definition);

    $this->foldersProvider = $foldersProvider;
    $this->nuxeoService    = $nuxeoService;
  }

  public function build() {
    $query = new Nxql\Query();

    $query->where("ecm:ancestorId")
          ->eq($this->configuration['cmis_last_items_block_folder']);

    $query->orderBy("dc:modified")
          ->desc();

    $resultBag = $this->nuxeoService->get($query, 10);

    return [
      '#theme' => 'cmis_extensions_last_items',
      '#items' => $resultBag->getResults()
    ];
  }

  public static function create(ContainerInterface $container, array $config, $id, $definition) {

    return new static(
      $config,
      $id,
      $definition,
      $container->get('cmis_extensions.folders_provider'),
      $container->get('cmis_extensions.nuxeo_service'));
  }

  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();
    $value  = $config['cmis_last_items_block_folder'] ?? '';

    if ($this->foldersProvider->hasEntityFolderFor($value)) {
      [$folder, $id] = [$value, ''];
    } else {
      [$folder, $id] = ['', $value];
    }

    $form[self::FOLDER_LIST] = [
      '#type'          => 'select',
      '#title'         => $this->t('Existing folder'),
      '#default_value' => $folder,
      '#options' => array_merge(
        ['' => $this->t('Manual')],
        $this->foldersProvider->entitiesWithIds()
      ),
      '#attributes' => [
        'id' => 'folder_list'
      ]
    ];

    $form[self::FOLDER_VALUE] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Nuxeo folder ID'),
      '#description'   => $this->t('Specify an ID manually'),
      '#default_value' => $id,
      '#states' => [
        'visible' => [
          ':input[id="folder_list"]' => ['value' => '']
        ]
      ]
    ];

    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();

    if (array_key_exists(self::FOLDER_LIST, $values)) {
      $value = $values[self::FOLDER_LIST];
    } else {
      $value = $values[self::FOLDER_VALUE];
    }

    $this->configuration['cmis_last_items_block_folder'] = $value;
  }

  public function blockValidate($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if (!array_key_exists(self::FOLDER_LIST, $values)
        && !array_key_exists(self::FOLDER_VALUE, $values)) {

      $form_state->setErrorByName(self::FOLDER_LIST,
          $this->t('A folder ID must be provided'));
    }
  }
}
