<?php

namespace Amarie\RoboDrupalCoding\Robo\Plugin\Commands;

use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;
use DrupalFinder\DrupalFinder;
use Robo\Tasks;
use Robo\Result;
use Robo\Config\Config;

/**
 * Commands for apply Drupal Coding Standards.
 *
 * @package Amarie\RoboDrupalCoding\Robo\Plugin\Commands
 */
class DrupalCodingCommands extends Tasks {

  /**
   * Command to run drupal-check.
   *
   * @command drupal-coding:check
   *
   * @return \Robo\Result
   *   The result of the collection of tasks.
   */
  public function drupalCheck(): Result {
    $collection = $this->collectionBuilder();
    $tasks = [];

    $tasks[] = $this->taskExec('vendor/mglaman/drupal-check/drupal-check')
      ->arg('-d')
      ->option('exclude-dir=node_modules,vendor')
      ->args('web/modules/contrib/token');

    $collection->addTaskList($tasks);

    return $collection->run();
  }

  /**
   * Command to run phpcs with standard options.
   *
   * @command drupal-coding:phpcs
   *
   * @return \Robo\Result
   *   The result of the collection of tasks.
   */
  public function drupalCoder(): Result {

    // Get code_sniffer config.
    $php_sniffer_options = [];
    $php_config_options = ['standard', 'extensions', 'ignore', 'report'];
    foreach ($php_config_options as $config_option) {
      $option_list = $this->loadConfig()->get('php.code_sniffer.' . $config_option);
      if (!empty($option_list)) {
        $option = implode(',', $option_list);
        $php_sniffer_options[$config_option] = $option;
      }
    }
    $php_sniffer_folders = $this->loadConfig()->get('php.code_sniffer.folders');

    $collection = $this->collectionBuilder();
    $tasks = [];

    $tasks[] = $this->codeSniffer()
      ->option('config-set')
      ->args('installed_paths', 'vendor/drupal/coder/coder_sniffer');

    $tasks[] = $this->codeSniffer()
      ->options($php_sniffer_options, '=')
      ->option('colors')
      ->args($php_sniffer_folders);

    $collection->addTaskList($tasks);

    return $collection->run();
  }

  /**
   * Return phpcs with default arguments.
   *
   * @return \Robo\Task\Base\Exec
   *   A phpcs exec command.
   */
  protected function codeSniffer() {
    return $this->taskExec('vendor/squizlabs/php_codesniffer/bin/phpcs');
  }

  /**
   * Load config.
   */
  protected function loadConfig() {
    $config = new Config();
    $loader = new YamlConfigLoader();
    $processor = new ConfigProcessor();

    // Load config.
    $drupalFinder = new DrupalFinder();
    if ($drupalFinder->locateRoot(rtrim('.', '/'))) {
      $composerRoot = $drupalFinder->getComposerRoot();
      $custom_config = $composerRoot . '/robo-drupal-coding.yml';
      if (file_exists($custom_config)) {
        $processor->extend($loader->load($custom_config));
      }
    }

    return $config->import($processor->export());
  }

}
