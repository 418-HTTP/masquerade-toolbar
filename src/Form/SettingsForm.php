<?php

declare(strict_types=1);

namespace Drupal\masquerade_toolbar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Masquerade Toolbar settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'masquerade_toolbar_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['masquerade_toolbar.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('masquerade_toolbar.settings');

    $form['appearance'] = [
      '#type' => 'details',
      '#title' => $this->t('Appearance'),
      '#open' => TRUE,
    ];

    $form['appearance']['position'] = [
      '#type' => 'radios',
      '#title' => $this->t('Toolbar position'),
      '#options' => [
        'bottom-left' => $this->t('Bottom left'),
        'bottom-right' => $this->t('Bottom right'),
        'top-left' => $this->t('Top left'),
        'top-right' => $this->t('Top right'),
      ],
      '#default_value' => $config->get('position') ?? 'bottom-right',
      '#description' => $this->t('Choose where the toolbar appears on the screen.'),
    ];

    $form['appearance']['collapsed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collapsed by default'),
      '#default_value' => $config->get('collapsed') ?? FALSE,
      '#description' => $this->t('If checked, the toolbar will be minimized by default.'),
    ];

    $form['appearance']['mobile_hide'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide on mobile devices'),
      '#default_value' => $config->get('mobile_hide') ?? TRUE,
      '#description' => $this->t('Hide the toolbar on screens smaller than 768px.'),
    ];

    $form['features'] = [
      '#type' => 'details',
      '#title' => $this->t('Features'),
      '#open' => TRUE,
    ];

    $form['features']['show_recent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show recent users'),
      '#default_value' => $config->get('show_recent') ?? TRUE,
      '#description' => $this->t('Display a list of recently masqueraded users.'),
    ];

    $form['features']['recent_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Recent users limit'),
      '#default_value' => $config->get('recent_limit') ?? 5,
      '#min' => 1,
      '#max' => 20,
      '#description' => $this->t('Maximum number of recent users to display.'),
      '#states' => [
        'visible' => [
          ':input[name="show_recent"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['features']['show_roles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show user roles'),
      '#default_value' => $config->get('show_roles') ?? TRUE,
      '#description' => $this->t('Display user roles in the toolbar.'),
    ];

    $form['features']['keyboard_shortcuts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable keyboard shortcuts'),
      '#default_value' => $config->get('keyboard_shortcuts') ?? TRUE,
      '#description' => $this->t('Enable keyboard shortcuts (Alt+M to toggle toolbar).'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('masquerade_toolbar.settings')
      ->set('position', $form_state->getValue('position'))
      ->set('collapsed', (bool) $form_state->getValue('collapsed'))
      ->set('mobile_hide', (bool) $form_state->getValue('mobile_hide'))
      ->set('show_recent', (bool) $form_state->getValue('show_recent'))
      ->set('recent_limit', (int) $form_state->getValue('recent_limit'))
      ->set('show_roles', (bool) $form_state->getValue('show_roles'))
      ->set('keyboard_shortcuts', (bool) $form_state->getValue('keyboard_shortcuts'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
