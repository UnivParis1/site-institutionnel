<?php

namespace Drupal\up1_search_api\Plugin\facets\processor;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MergeTaxonomyTerms : Group Taxonomy terms for a better search engine.
 *
 * @package Drupal\up1_search_api\Plugin\facets\processor
 *
 * @FacetsProcessor(
 *   id = "up1_search_api_merge_taxonomy_terms",
 *   label = @Translation("Merge Taxonomy Terms."),
 *   description = @Translation("Group several taxonomy terms in order to lighten the search engine."),
 *   stages = {
 *    "build" = 1
 *   }
 * )
 */
class MergeTaxonomyTerms extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface {
  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Extract all available taxonomy terms then map them as valid options.
   *
   * @return array
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getTaxonomyTerms(): array {
    $terms = array_map(function ($term) {
      return $term->label();
    }, $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple());
    asort($terms);
    return $terms;
  }
  /**
   * Pre define Groups, create groups of taxonomy terms grouping.
   *
   * @return array $groups
   *
   */
  protected function preDefineGroups(){

    $terms = array_filter($this->getTaxonomyTerms()); //remove null, 0 and "" values.
    if (empty($terms)) { //no taxonomy terms to show
      return;
    }

    $groupings = [];

    //Array with values and their number of iterations.
    $similar_terms = self::arrayCountValues($terms);

    //remove items with number of iterations < = 1.
    $filtered_similar_terms = array_filter($similar_terms, function ($value){
      return $value > 1;
    });

    //Compare and group similar labels.
    foreach ($filtered_similar_terms as $value=>$number_iterations){
      foreach($terms as $tid=>$tname){
        if(strcmp(self::transliterateString($value), self::transliterateString($tname)) == 0){
          $groupings[$value][] = $tid;
        }
      }
    }

    //Build the mapping array.
    $groups = [];
    foreach($groupings as $grouping_name => $array_tids){
      $taxonomy_terms = [];
      $taxonomy_terms_values = [];
      foreach($array_tids as $tid){
        $taxonomy_terms[$tid] = $terms[$tid];
        $taxonomy_terms_values[$tid] = $tid;
      }
      $groups[] = [
        'facet_name' => $grouping_name,
        'taxonomy_terms' => $taxonomy_terms,
        'taxonomy_terms_values'=> $taxonomy_terms_values,
      ];
    }

    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    /** @var array $config */
    $config = $this->getConfiguration()['facet_terms_groups'];

    // Gather the number of groups in the form already.
    $groups = $form_state->get('groups');

    /** Create groups automatically if there are taxonomy terms with the same name **/
    $config = $this->preDefineGroups();

    $groups = count($config);
    $form_state->set('groups', $groups);

    // Prepare form widget.
    $build['#tree'] = TRUE;
    $build['container_open']['#markup'] = '<div id="facet-group-fieldset-wrapper">';

    // Iterate same times as groups available.
    for ($i = 0; $i < $groups; $i++) {

      // Build details wrapper on each group.
      $build['facet_terms_groups'][$i] = [
        '#type' => 'details',
        //'#title' => $this->t('Facet group'),
        '#title' => $config[$i]['facet_name'],
        '#open' => FALSE,
      ];

      // Include field to overwrite facet name.
      $build['facet_terms_groups'][$i]['facet_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('New Facet name'),
        '#default_value' => $config[$i]['facet_name'] ?? NULL,
      ];

      // Expose all possible content types available.
      $build['facet_terms_groups'][$i]['taxonomy_terms'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Taxonomy terms to be grouped.'),
        '#options' => $config[$i]['taxonomy_terms'],
        '#default_value' => $config[$i]['taxonomy_terms_values'] ?? [],
      ];
    }

    // Close container element.
    $build['container_close']['#markup'] = '</div>';

    return $build;
  }

  /*
   * Function to count elements in an array and return an Array with every value and its number of iterations.
   * Transliteration insensitive and case insensitive.
  */
  public static function arrayCountValues($array){

    foreach ($array as $k=>$v){
      $array[$k] = self::transliterateString($v);
    }
    return array_count_values($array);
  }

  /* Function to transfom a string containing special characters and transliteration */
  public static function transliterateString($txt) {
    $transliterationTable = array('á' => 'a', 'Á' => 'A', 'à' => 'a', 'À' => 'A', 'ă' => 'a', 'Ă' => 'A', 'â' => 'a', 'Â' => 'A', 'å' => 'a', 'Å' => 'A', 'ã' => 'a', 'Ã' => 'A', 'ą' => 'a', 'Ą' => 'A', 'ā' => 'a', 'Ā' => 'A', 'ä' => 'ae', 'Ä' => 'AE', 'æ' => 'ae', 'Æ' => 'AE', 'ḃ' => 'b', 'Ḃ' => 'B', 'ć' => 'c', 'Ć' => 'C', 'ĉ' => 'c', 'Ĉ' => 'C', 'č' => 'c', 'Č' => 'C', 'ċ' => 'c', 'Ċ' => 'C', 'ç' => 'c', 'Ç' => 'C', 'ď' => 'd', 'Ď' => 'D', 'ḋ' => 'd', 'Ḋ' => 'D', 'đ' => 'd', 'Đ' => 'D', 'ð' => 'dh', 'Ð' => 'Dh', 'é' => 'e', 'É' => 'E', 'è' => 'e', 'È' => 'E', 'ĕ' => 'e', 'Ĕ' => 'E', 'ê' => 'e', 'Ê' => 'E', 'ě' => 'e', 'Ě' => 'E', 'ë' => 'e', 'Ë' => 'E', 'ė' => 'e', 'Ė' => 'E', 'ę' => 'e', 'Ę' => 'E', 'ē' => 'e', 'Ē' => 'E', 'ḟ' => 'terms', 'Ḟ' => 'terms', 'ƒ' => 'terms', 'Ƒ' => 'terms', 'ğ' => 'g', 'Ğ' => 'G', 'ĝ' => 'g', 'Ĝ' => 'G', 'ġ' => 'g', 'Ġ' => 'G', 'ģ' => 'g', 'Ģ' => 'G', 'ĥ' => 'h', 'Ĥ' => 'H', 'ħ' => 'h', 'Ħ' => 'H', 'í' => 'i', 'Í' => 'I', 'ì' => 'i', 'Ì' => 'I', 'î' => 'i', 'Î' => 'I', 'ï' => 'i', 'Ï' => 'I', 'ĩ' => 'i', 'Ĩ' => 'I', 'į' => 'i', 'Į' => 'I', 'ī' => 'i', 'Ī' => 'I', 'ĵ' => 'j', 'Ĵ' => 'J', 'ķ' => 'k', 'Ķ' => 'K', 'ĺ' => 'l', 'Ĺ' => 'L', 'ľ' => 'l', 'Ľ' => 'L', 'ļ' => 'l', 'Ļ' => 'L', 'ł' => 'l', 'Ł' => 'L', 'ṁ' => 'm', 'Ṁ' => 'M', 'ń' => 'n', 'Ń' => 'N', 'ň' => 'n', 'Ň' => 'N', 'ñ' => 'n', 'Ñ' => 'N', 'ņ' => 'n', 'Ņ' => 'N', 'ó' => 'o', 'Ó' => 'O', 'ò' => 'o', 'Ò' => 'O', 'ô' => 'o', 'Ô' => 'O', 'ő' => 'o', 'Ő' => 'O', 'õ' => 'o', 'Õ' => 'O', 'ø' => 'oe', 'Ø' => 'OE', 'ō' => 'o', 'Ō' => 'O', 'ơ' => 'o', 'Ơ' => 'O', 'ö' => 'oe', 'Ö' => 'OE', 'ṗ' => 'p', 'Ṗ' => 'P', 'ŕ' => 'r', 'Ŕ' => 'R', 'ř' => 'r', 'Ř' => 'R', 'ŗ' => 'r', 'Ŗ' => 'R', 'ś' => 's', 'Ś' => 'S', 'ŝ' => 's', 'Ŝ' => 'S', 'š' => 's', 'Š' => 'S', 'ṡ' => 's', 'Ṡ' => 'S', 'ş' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ß' => 'SS', 'ť' => 't', 'Ť' => 'T', 'ṫ' => 't', 'Ṫ' => 'T', 'ţ' => 't', 'Ţ' => 'T', 'ț' => 't', 'Ț' => 'T', 'ŧ' => 't', 'Ŧ' => 'T', 'ú' => 'u', 'Ú' => 'U', 'ù' => 'u', 'Ù' => 'U', 'ŭ' => 'u', 'Ŭ' => 'U', 'û' => 'u', 'Û' => 'U', 'ů' => 'u', 'Ů' => 'U', 'ű' => 'u', 'Ű' => 'U', 'ũ' => 'u', 'Ũ' => 'U', 'ų' => 'u', 'Ų' => 'U', 'ū' => 'u', 'Ū' => 'U', 'ư' => 'u', 'Ư' => 'U', 'ü' => 'ue', 'Ü' => 'UE', 'ẃ' => 'w', 'Ẃ' => 'W', 'ẁ' => 'w', 'Ẁ' => 'W', 'ŵ' => 'w', 'Ŵ' => 'W', 'ẅ' => 'w', 'Ẅ' => 'W', 'ý' => 'y', 'Ý' => 'Y', 'ỳ' => 'y', 'Ỳ' => 'Y', 'ŷ' => 'y', 'Ŷ' => 'Y', 'ÿ' => 'y', 'Ÿ' => 'Y', 'ź' => 'z', 'Ź' => 'Z', 'ž' => 'z', 'Ž' => 'Z', 'ż' => 'z', 'Ż' => 'Z', 'þ' => 'th', 'Þ' => 'Th', 'µ' => 'u', 'а' => 'a', 'А' => 'a', 'б' => 'b', 'Б' => 'b', 'в' => 'v', 'В' => 'v', 'г' => 'g', 'Г' => 'g', 'д' => 'd', 'Д' => 'd', 'е' => 'e', 'Е' => 'e', 'ё' => 'e', 'Ё' => 'e', 'ж' => 'zh', 'Ж' => 'zh', 'з' => 'z', 'З' => 'z', 'и' => 'i', 'И' => 'i', 'й' => 'j', 'Й' => 'j', 'к' => 'k', 'К' => 'k', 'л' => 'l', 'Л' => 'l', 'м' => 'm', 'М' => 'm', 'н' => 'n', 'Н' => 'n', 'о' => 'o', 'О' => 'o', 'п' => 'p', 'П' => 'p', 'р' => 'r', 'Р' => 'r', 'с' => 's', 'С' => 's', 'т' => 't', 'Т' => 't', 'у' => 'u', 'У' => 'u', 'ф' => 'terms', 'Ф' => 'terms', 'х' => 'h', 'Х' => 'h', 'ц' => 'c', 'Ц' => 'c', 'ч' => 'ch', 'Ч' => 'ch', 'ш' => 'sh', 'Ш' => 'sh', 'щ' => 'sch', 'Щ' => 'sch', 'ъ' => '', 'Ъ' => '', 'ы' => 'y', 'Ы' => 'y', 'ь' => '', 'Ь' => '', 'э' => 'e', 'Э' => 'e', 'ю' => 'ju', 'Ю' => 'ju', 'я' => 'ja', 'Я' => 'ja');
    $txt = str_replace(array_keys($transliterationTable), array_values($transliterationTable), $txt);
    return trim(strtolower($txt));
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $form_state->unsetValue('actions');
    parent::submitConfigurationForm($form, $form_state, $facet);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'facet_terms_groups' => [
        [
          'facet_name' => '',
          'taxonomy_terms' => [],
          'taxonomy_terms_values' => [],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    /** @var array $facet_groups */
    $facet_groups = $this->getConfiguration()['facet_terms_groups'];

    /** @var \Drupal\facets\Result\Result[] $facets */
    $facets = array_reduce($results, function ($carry, $item) {
      /** @var \Drupal\facets\Result\Result $item */
      $carry[$item->getRawValue()] = $item;
      return $carry;
    }, []);

    array_walk($facet_groups, function ($config) use ($results, &$facets) {
      /** @var array $terms */

      $terms = array_filter($config['taxonomy_terms']); //remove null, 0 and "" values.
      if (empty($terms)) {
        return;
      }

      //keep only taxonomy terms enabled chosen in facet.
      /** @var array $filtered */
      $filtered = array_filter($terms, function ($term) use ($facets) {
        return array_key_exists($term, $facets);
      });

      if (empty($filtered)) {
        return;
      }

      /** @var string $key */
      $key = array_shift($filtered); //get first element's key (term_id)
      /** @var \Drupal\facets\Result\Result $first */
      $first = &$facets[$key];

      // Overwrite label if new facet name was defined.
      if (!empty($config['facet_name'])) {
        $first->setDisplayValue($config['facet_name']);
      }

      // Init flag variables.
      $updated = FALSE;

      /** @var \Drupal\Core\Url $url */
      $url = $first->getUrl();
      /** @var array $query */
      $query = $url->getOption('query');
      // Walk-through all remain filtered types.
      foreach ($filtered as $item) {
        // Setup dynamic filter.
        $filter = "thematiques:{$item}"; //thematique should be change, check point de vigilance.

        // Look-up for query string.
        //"terms" parameter should be dynamic, check point de vigilance.
        if (!in_array($filter, $query['terms'])) {
          // Inject filter to current query.
          $updated = TRUE;
          $query['terms'][] = $filter;
        }
        // Verify when current facet is active.
        elseif ($first->isActive()) {
          // Remove duplication filter values.
          $updated = TRUE;
          $query['terms'] = array_filter($query['terms'], function ($param) use ($filter) {
            return $param != $filter;
          });

          // Remove whole query string when there are not filters.
          if (empty($query['terms'])) {
            unset($query['terms']);
          }
        }

        // Overwrite URL options then define it back to facet.
        if ($updated) {
          $url->setOption('query', $query);
          $first->setUrl($url);
        }

        // Update facet count value when lab facet was found.
        $first->setCount($first->getCount() + $facets[$item]->getCount());

        // Remove facet instance.
        unset($facets[$item]);
      }
    });

    return array_values($facets);
  }

  /**
   * Setting entity type manager property.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   */
  public function setEntityTypeManager(EntityTypeManager $entityTypeManager): void {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    // Inject dependency into current plugin's instance.
    $plugin->setEntityTypeManager($container->get('entity_type.manager'));

    return $plugin;
  }
}
