<?php
namespace Drupal\simplenews_cron;

use Drupal\Core\Config\ConfigFactoryInterface;

class ConfigData
{
    /**
     * Config settings.
     *
     * @var ConfigFactoryInterface
     */
    protected ConfigFactoryInterface $settings;


  /**
   * Get all config data
   * ConfigData constructor.
   */
    public function __construct(ConfigFactoryInterface $config_factory) {
        $this->settings =  $config_factory;
    }

    public function get_send_date(): string {
        return $this->settings->getEditable('simplenews_cron.settings')->get('send_date');
    }

    public function get_sent_time(): string {
        return $this->settings->getEditable('simplenews_cron.settings')->get('last_run');
    }


    public function set_sent_time($value): void {
        $this->settings->getEditable('simplenews_cron.settings')->set('last_run', $value)
          ->save();
    }

}
