<?php

namespace Drupal\up1_pages_perso\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\up1_pages_perso\Entity\PagePersoInterface;

/**
 * Class PagePersoController.
 *
 *  Returns responses for Page perso routes.
 */
class PagePersoController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Page perso  revision.
   *
   * @param int $page_perso_revision
   *   The Page perso  revision ID.
   * @return array
   *  An array suitable for drupal_render().
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionShow($page_perso_revision) {
    $page_perso = $this->entityManager()->getStorage('page_perso')->loadRevision($page_perso_revision);
    $view_builder = $this->entityManager()->getViewBuilder('page_perso');

    return $view_builder->view($page_perso);
  }

  /**
   * Page title callback for a Page perso  revision.
   *
   * @param int $page_perso_revision
   *   The Page perso  revision ID.
   *
   * @return string
   *   The page title.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionPageTitle($page_perso_revision) {
    $page_perso = $this->entityManager()->getStorage('page_perso')->loadRevision($page_perso_revision);
    return $this->t('Revision of %title from %date', ['%title' => $page_perso->label(), '%date' => format_date($page_perso->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Page perso .
   *
   * @param \Drupal\up1_pages_perso\Entity\PagePersoInterface $page_perso
   *  A Page perso  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionOverview(PagePersoInterface $page_perso) {
    $account = $this->currentUser();
    $langcode = $page_perso->language()->getId();
    $langname = $page_perso->language()->getName();
    $languages = $page_perso->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $page_perso_storage = $this->entityManager()->getStorage('page_perso');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $page_perso->label()]) : $this->t('Revisions for %title', ['%title' => $page_perso->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all page perso revisions") || $account->hasPermission('administer page perso entities')));
    $delete_permission = (($account->hasPermission("delete all page perso revisions") || $account->hasPermission('administer page perso entities')));

    $rows = [];

    $vids = $page_perso_storage->revisionIds($page_perso);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\up1_pages_perso\PagePersoInterface $revision */
      $revision = $page_perso_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $page_perso->getRevisionId()) {
          $link = $this->l($date, new Url('entity.page_perso.revision', ['page_perso' => $page_perso->id(), 'page_perso_revision' => $vid]));
        }
        else {
          $link = $page_perso->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.page_perso.translation_revert', ['page_perso' => $page_perso->id(), 'page_perso_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.page_perso.revision_revert', ['page_perso' => $page_perso->id(), 'page_perso_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.page_perso.revision_delete', ['page_perso' => $page_perso->id(), 'page_perso_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['page_perso_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
