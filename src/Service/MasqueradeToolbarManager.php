<?php

declare(strict_types=1);

namespace Drupal\masquerade_toolbar\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\masquerade\Masquerade;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Manages masquerade toolbar functionality.
 */
class MasqueradeToolbarManager {

  /**
   * The current user.
   */
  protected AccountInterface $currentUser;

  /**
   * The tempstore service.
   */
  protected PrivateTempStoreFactory $tempStore;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The masquerade service.
   */
  protected Masquerade $masquerade;

  /**
   * Config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The logger.
   */
  protected LoggerInterface $logger;

  /**
   * The request stack.
   */
  protected RequestStack $requestStack;

  /**
   * Constructs a MasqueradeToolbarManager.
   */
  public function __construct(
    AccountInterface $current_user,
    PrivateTempStoreFactory $temp_store,
    EntityTypeManagerInterface $entity_type_manager,
    Masquerade $masquerade,
    ConfigFactoryInterface $config_factory,
    LoggerInterface $logger,
    RequestStack $request_stack,
  ) {
    $this->currentUser = $current_user;
    $this->tempStore = $temp_store;
    $this->entityTypeManager = $entity_type_manager;
    $this->masquerade = $masquerade;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
    $this->requestStack = $request_stack;
  }

  /**
   * Adds a user to the recent users list.
   */
  public function addRecentUser(int $uid): void {
    $store = $this->tempStore->get('masquerade_toolbar');
    $recent = $store->get('recent_users') ?? [];

    // Remove the user if already in the list.
    $recent = array_filter($recent, fn($id) => $id !== $uid);

    // Add to the beginning.
    array_unshift($recent, $uid);

    // Limit to configure max.
    $limit = $this->getConfig()->get('recent_limit') ?? 5;
    $recent = array_slice($recent, 0, $limit);

    try {
      $store->set('recent_users', $recent);
    }
    catch (TempStoreException $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Gets recent users.
   */
  public function getRecentUsers(): array {
    $store = $this->tempStore->get('masquerade_toolbar');
    $recent_ids = $store->get('recent_users') ?? [];

    if (empty($recent_ids)) {
      return [];
    }

    try {
      $storage = $this->entityTypeManager->getStorage('user');
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $this->logger->error($e->getMessage());
      return [];
    }
    return $storage->loadMultiple($recent_ids);
  }

  /**
   * Clears recent users.
   */
  public function clearRecentUsers(): void {
    $store = $this->tempStore->get('masquerade_toolbar');
    try {
      $store->delete('recent_users');
    }
    catch (TempStoreException $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Checks if the user can use the toolbar.
   */
  public function canUseToolbar(): bool {
    // When masquerading, check the ORIGINAL user's permissions, not the
    // masqueraded user's permissions. This ensures the toolbar stays visible.
    $check_user = $this->currentUser;

    if ($this->masquerade->isMasquerading()) {
      // Get original user ID from masquerade session metadata.
      $original_uid = $this->requestStack->getCurrentRequest()->getSession()->getMetadataBag()->getMasquerade();
      if ($original_uid) {
        try {
          $storage = $this->entityTypeManager->getStorage('user');
        }
        catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
          $this->logger->error($e->getMessage());
          return FALSE;
        }
        $original_user = $storage->load($original_uid);
        if ($original_user) {
          $check_user = $original_user;
        }
      }
    }

    // Must have permission to use toolbar.
    if (!$check_user->hasPermission('use masquerade toolbar')) {
      return FALSE;
    }

    // Must have at least one masquerade permission.
    return $check_user->hasPermission('masquerade as any user')
      || $check_user->hasPermission('masquerade as super user');
  }

  /**
   * Gets the current masquerade state.
   */
  public function getMasqueradeState(): array {
    $is_masquerading = $this->masquerade->isMasquerading();

    $current_user = $this->loadUser((int) $this->currentUser->id());

    // Ensure we always have valid current user data.
    if (!$current_user) {
      $current_user = [
        'uid' => 0,
        'name' => 'Anonymous',
        'email' => '',
        'roles' => [],
      ];
    }

    $state = [
      'is_masquerading' => $is_masquerading,
      'current_user' => $current_user,
    ];

    if ($is_masquerading) {
      // Get original user ID from masquerade session metadata.
      $original_uid = $this->requestStack->getCurrentRequest()->getSession()->getMetadataBag()->getMasquerade();
      if ($original_uid) {
        $original_user = $this->loadUser((int) $original_uid);
        if ($original_user) {
          $state['original_user'] = $original_user;
        }
      }
    }

    return $state;
  }

  /**
   * Loads user with roles.
   */
  protected function loadUser(int $uid): ?array {
    try {
      $storage = $this->entityTypeManager->getStorage('user');
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $this->logger->error($e->getMessage());
      return NULL;
    }
    $user = $storage->load($uid);

    if (!$user instanceof UserInterface) {
      return NULL;
    }

    return [
      'uid' => $user->id(),
      'name' => $user->getAccountName(),
      'email' => $user->getEmail(),
      'roles' => $user->getRoles(),
    ];
  }

  /**
   * Gets module configuration.
   */
  protected function getConfig(): ImmutableConfig {
    return $this->configFactory->get('masquerade_toolbar.settings');
  }

}
