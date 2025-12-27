<?php

declare(strict_types=1);

namespace Drupal\masquerade_toolbar\EventSubscriber;

use Drupal\masquerade\Masquerade;
use Drupal\masquerade_toolbar\Service\MasqueradeToolbarManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber for masquerade events.
 */
class MasqueradeEventSubscriber implements EventSubscriberInterface {

  /**
   * The masquerade toolbar manager.
   */
  protected MasqueradeToolbarManager $toolbarManager;

  /**
   * The masquerade service.
   */
  protected Masquerade $masquerade;

  /**
   * Constructs a MasqueradeEventSubscriber.
   */
  public function __construct(
    MasqueradeToolbarManager $toolbar_manager,
    Masquerade $masquerade,
  ) {
    $this->toolbarManager = $toolbar_manager;
    $this->masquerade = $masquerade;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Use kernel request event to check masquerade state.
    // The masquerade module may not provide custom events,
    // so we'll check the state on each request and track accordingly.
    return [
      KernelEvents::REQUEST => ['onRequest', -100],
    ];
  }

  /**
   * Responds to kernel request event.
   */
  public function onRequest(RequestEvent $event): void {
    // Only process on the primary request.
    if (!$event->isMainRequest()) {
      return;
    }

    // Check if currently masquerading.
    $session = $event->getRequest()->getSession();
    if ($this->masquerade->isMasquerading()) {
      $last_tracked = $session->get('masquerade_toolbar_last_tracked');
      $current_uid = \Drupal::currentUser()->id();

      // Only track if this is a new masquerade (different from last tracked).
      if ($last_tracked !== $current_uid) {
        $this->toolbarManager->addRecentUser((int) $current_uid);
        $session->set('masquerade_toolbar_last_tracked', $current_uid);
      }
    }
    else {
      // Not masquerading, clear the tracking.
      $session->remove('masquerade_toolbar_last_tracked');
    }
  }

}
