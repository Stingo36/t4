<?php

namespace Drupal\twilio\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // @todo Parts of your hook_menu_alter() logic should be moved in here.
    if ($route = $collection->get('user.register')) {
      $route->setDefault('_form', '\Drupal\twilio\Form\NewUserRegisterForm');
    }
  }

}
