<?php

declare(strict_types=1);

namespace Drupal\masquerade_toolbar\Access;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\masquerade\Masquerade;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * Checks access for masquerade toolbar routes.
 *
 * When masquerading, checks the ORIGINAL user's permissions.
 */
class ToolbarAccessCheck implements AccessInterface {

  /**
   * The masquerade service.
   */
  protected Masquerade $masquerade;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The logger.
   */
  protected LoggerInterface $logger;

  /**
   * The request stack.
   */
  protected RequestStack $requestStack;

  /**
   * Constructs a ToolbarAccessCheck.
   */
  public function __construct(
    Masquerade $masquerade,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger,
    RequestStack $request_stack,
  ) {
    $this->masquerade = $masquerade;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->requestStack = $request_stack;
  }

  /**
   * Checks access to toolbar routes.
   */
  public function access(Route $route, AccountInterface $account): AccessResultForbidden|AccessResultNeutral|AccessResult|AccessResultAllowed {
    // Get the user to check permissions for.
    $check_account = $account;

    // If masquerading, check the original user's permissions.
    if ($this->masquerade->isMasquerading()) {
      $original_uid = $this->requestStack->getCurrentRequest()->getSession()->getMetadataBag()->getMasquerade();
      if ($original_uid) {
        try {
          $storage = $this->entityTypeManager->getStorage('user');
        }
        catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
          $this->logger->error($e->getMessage());
          return AccessResult::forbidden();
        }
        $original_user = $storage->load($original_uid);
        if ($original_user) {
          $check_account = $original_user;
        }
      }
    }

    // Check if the user has permission to use the toolbar.
    if (!$check_account->hasPermission('use masquerade toolbar')) {
      return AccessResult::forbidden();
    }

    // Check if a user has at least one masquerade permission.
    $has_masquerade_permission = $check_account->hasPermission('masquerade as any user')
      || $check_account->hasPermission('masquerade as super user');

    return AccessResult::allowedIf($has_masquerade_permission);
  }

}
