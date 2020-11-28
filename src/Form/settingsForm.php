<?php


namespace Drupal\simplenews_cron\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


class settingsForm extends ConfigFormBase {


  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'simplenews_cron.settings';


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['send_date'] = [
      '#type' => 'radios',
      '#title' => $this->t('Frequency'),
      '#prefix' => t('The simplenews cron module is design to send all newsletters once on the selected day of the week from the options bellow.'),
      '#default_value' => $config->get('send_date'),
      '#options' => array(
        'Sun' => $this->t('Sunday'),
        'Mon' => $this->t('Monday'),
        'Tue' => $this->t('Tuesday'),
        'Wed' => $this->t('Wednesday'),
        'Thu' => $this->t('Thursday'),
        'Fri' => $this->t('Friday'),
        'Sat' => $this->t('Saturday (Default)'),
      ),
      '#description' => $this->t('Select the day of the week to send the newsletters'),
    ];


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      // Set the submitted configuration setting.
      ->set('send_date', $form_state->getValue('send_date'))
      // You can set multiple configurations at once by making
      // multiple calls to set().
      //->set('other_things', $form_state->getValue('other_things'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}