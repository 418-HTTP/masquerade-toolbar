<?php

declare(strict_types=1);

namespace Drupal\masquerade_toolbar\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\masquerade\Masquerade;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns autocomplete responses for user search.
 */
class AutocompleteController extends ControllerBase {

  /**
   * The masquerade service.
   */
  protected Masquerade $masquerade;

  /**
   * The logger.
   */
  protected LoggerInterface $logger;

  /**
   * Constructs an AutocompleteController.
   */
  public function __construct(
    Masquerade $masquerade,
    LoggerInterface $logger,
  ) {
    $this->masquerade = $masquerade;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('masquerade'),
      $container->get('logger.channel.masquerade_toolbar')
    );
  }

  /**
   * Returns response for user autocomplete.
   */
  public function autocomplete(Request $request): JsonResponse {
    $matches = [];
    $string = $request->query->get('q');

    if (!$string || strlen($string) < 2) {
      return new JsonResponse($matches);
    }

    // Build query.
    try {
      $storage = $this->entityTypeManager()->getStorage('user');
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $this->logger->error($e->getMessage());
      return new JsonResponse($matches);
    }
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 1)
      ->range(0, 10);

    // Search by username or email.
    $or = $query->orConditionGroup()
      ->condition('name', $string, 'CONTAINS')
      ->condition('mail', $string, 'CONTAINS');
    $query->condition($or);

    // Execute query.
    $uids = $query->execute();

    if (empty($uids)) {
      return new JsonResponse($matches);
    }

    // Load users.
    $users = $storage->loadMultiple($uids);

    // Get original user ID if masquerading.
    $original_uid = NULL;
    if ($this->masquerade->isMasquerading()) {
      $original_uid = \Drupal::request()->getSession()->getMetadataBag()->getMasquerade();
    }

    foreach ($users as $user) {
      // Skip anonymous.
      if ($user->isAnonymous()) {
        continue;
      }

      // Skip current user (can't masquerade as self).
      if ($user->id() == $this->currentUser()->id()) {
        continue;
      }

      // Skip the original user when masquerading (use "Switch Back" instead).
      if ($original_uid && $user->id() == $original_uid) {
        continue;
      }

      $roles = array_diff($user->getRoles(), ['authenticated']);
      $roles_label = !empty($roles) ? ' (' . implode(', ', $roles) . ')' : '';

      // Generate masquerade URL with CSRF token.
      $url = Url::fromRoute('entity.user.masquerade', [
        'user' => $user->id(),
      ]);
      $token = \Drupal::csrfToken()->get($url->getInternalPath());
      $url->setOption('query', ['token' => $token]);
      $masquerade_url = $url->toString();

      $matches[] = [
        'value' => $user->getAccountName(),
        'label' => $user->getAccountName() . $roles_label . ' - ' . $user->getEmail(),
        'uid' => $user->id(),
        'name' => $user->getAccountName(),
        'email' => $user->getEmail(),
        'roles' => $roles,
        'url' => $masquerade_url,
      ];
    }

    return new JsonResponse($matches);
  }

}
