<?php

declare(strict_types=1);

namespace Drupal\up1_pages\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\up1_pages\Form\SearchPagePersoForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Recherche d'une page personnelle.
 *
 * Même identifiant que dans up1_pages_persos : les blocs déjà placés sont
 * repris tels quels.
 */
#[Block(
  id: 'search_pages_persos_block',
  admin_label: new TranslatableMarkup('Recherche pages personnelles'),
  category: new TranslatableMarkup('Panthéon-Sorbonne'),
)]
final class SearchPagesPersosBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly FormBuilderInterface $formBuilder,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('form_builder'));
  }

  public function build(): array {
    return $this->formBuilder->getForm(SearchPagePersoForm::class);
  }

}
