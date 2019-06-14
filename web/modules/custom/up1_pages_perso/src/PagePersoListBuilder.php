<?php

namespace Drupal\up1_pages_perso;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Page perso entities.
 *
 * @ingroup up1_pages_perso
 */
class PagePersoListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Page perso ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\up1_pages_perso\Entity\PagePerso */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.page_perso.edit_form',
      ['page_perso' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
