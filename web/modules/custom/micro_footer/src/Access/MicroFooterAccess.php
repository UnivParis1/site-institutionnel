<?php
/**
 * Created by PhpStorm.
 * User: ede16590
 * Date: 29/05/2019
 * Time: 09:44
 */

namespace Drupal\micro_footer\Access;


use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\migrate\Plugin\migrate\process\Route;

class MicroFooterAccess
{

  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, SiteInterface $site = NULL){
    if(\Drupal::currentUser()->hasPermission('manage microsite footer')){
      return AccessResult::neutral();
    }else{
      return AccessResult::forbidden();
    }

  }

}