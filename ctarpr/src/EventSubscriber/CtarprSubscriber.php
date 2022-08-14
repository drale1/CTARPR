<?php

namespace Drupal\ctarpr\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * CTARPR event subscriber.
 */
class CtarprSubscriber implements EventSubscriberInterface
{

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a CtarprSubscriber object.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(AccountInterface $account, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match, ConfigFactoryInterface $config_factory)
  {
    $this->account = $account;
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents()
  {
    return [
      KernelEvents::REQUEST => ['onKernelRequest'],
    ];
  }

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.
   */
  public function onKernelRequest(RequestEvent $event)
  {
    //Get current content type. If current page is not content, return null
    $content_type = $this->routeMatch->getParameter('node')->type->target_id ?? null;

    //Get saved configuration from CTARPRSettingsForm
    $config_factory = $this->configFactory->get('ctarpr.settings');

    //Get redirection for restricted content type as number of node
    $node_for_redirection = $config_factory->get('page_redirection_'.$content_type);

    //Get restricted user roles per current content type.
    $restricted_user_roles = $config_factory->get('restricted_user_roles_'.$content_type) ?? [];

    // array_map('intval',) converts array values as integer not as string in $restricted_user_roles
    $restricted_user_roles = array_map('intval', $restricted_user_roles);

    //Copy array values to their array keys
    $restricted_user_roles = array_combine($restricted_user_roles, $restricted_user_roles);

    //Get all existing user roles
    $user_roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();

    //Get all current user roles
    $current_user_roles = $this->account->getRoles();

    //Create all existing user roles as indexed array. Keys of this array refers to same role as $restricted_user_roles array keys
    $user_roles_indexed_array = [];
    foreach ($user_roles as $user_role)
    {
      $user_roles_indexed_array[] = $user_role->id();
    }

    //Get all restricted user roles by name (id) as values in array
    $restricted_user_roles = array_intersect_key($user_roles_indexed_array, $restricted_user_roles);

    //Check if $current_user_roles array values exists in $restricted_user_roles return true
    $do_content_restriction = !empty(array_intersect($current_user_roles, $restricted_user_roles));

    //If $do_content_restriction = true make redirection to $node_for_redirection
    if ($do_content_restriction)
    {
      $event->setResponse(new RedirectResponse('/node/'.$node_for_redirection));
    }
  }
}
