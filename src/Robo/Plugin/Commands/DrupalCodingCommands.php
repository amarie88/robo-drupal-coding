<?php

namespace Amarie\RoboDrupalCoding\Robo\Plugin\Commands;

use Consolidation\Config\Loader\ConfigProcessor;
use Consolidation\Config\Loader\YamlConfigLoader;
use DrupalFinder\DrupalFinder;
use Robo\Tasks;
use Robo\Config\Config;

/**
 * Commands for apply Drupal Coding Standards.
 *
 * @package Amarie\RoboDrupalCoding\Robo\Plugin\Commands
 */
class DrupalCodingCommands extends Tasks {

  /**
   * Config.
   *
   * @var \Robo\Config
   */
  private $config;

  /**
   * Command to run drupal-check with options.
   *
   * @command drupal-coding:check
   */
  public function drupalCheck() {

    if ($this->loadConfig()) {

      // Get drupal-check config.
      $drupalCheckOptions = [];

      $drupalCheckType = $this->config->get('drupal.drupal_check.option');
      if (!empty($drupalCheckType)) {
        $drupalCheckOption = $drupalCheckType;
      }

      $drupalCheckOptions['format'] = $this->config->get('drupal.drupal_check.format', 'table');

      $drupalCheckExclude = $this->config->get('drupal.drupal_check.exclude_dir');
      if (!empty($drupalCheckExclude)) {
        $drupalCheckOptions['exclude-dir'] = implode(',', $drupalCheckExclude);
      }

      // Path(s) to inspect.
      $drupalCheckPaths = $this->config->get('drupal.drupal_check.paths');

      // Create collection builder.
      $collection = $this->collectionBuilder();
      $tasks = [];

      $tasks[] = $this->taskExec('vendor/mglaman/drupal-check/drupal-check')
        ->option($drupalCheckOption)
        ->options($drupalCheckOptions, '=')
        ->option('no-interaction')
        ->args($drupalCheckPaths);

      // Add tasks to collection.
      $collection->addTaskList($tasks);

      // Run collection.
      return $collection->run();
    }
    else {
      return FALSE;
    }
  }

  /**
   * Command to run phpcs with options.
   *
   * @param string $standard
   *   The name or path of the coding standard to use.
   *
   * @command drupal-coding:phpcs
   */
  public function codeSniffer($standard = NULL) {

    if ($this->loadConfig()) {

      // Get code_sniffer config.
      $phpSnifferOptions = [];

      $phpConfigOptions = [
        'report',
        'standard',
        'extensions',
        'ignore',
      ];
      foreach ($phpConfigOptions as $configOption) {
        $optionList = $this->config->get('php.code_sniffer.' . $configOption);
        if (!empty($optionList)) {
          $option = implode(',', $optionList);
          $phpSnifferOptions[$configOption] = $option;
        }
      }

      // Override the coding standard to use.
      if (!empty($standard)) {
        $phpSnifferOptions['standard'] = $standard;
      }

      // Path(s) to inspect.
      $phpSnifferPaths = $this->config->get('php.code_sniffer.paths');

      // Create collection builder.
      $collection = $this->collectionBuilder();
      $tasks = [];

      // Task for run PHPCS command.
      $tasks[] = $this->codeSnifferExec()
        ->option('colors')
        ->options($phpSnifferOptions, '=')
        ->args($phpSnifferPaths);

      // Add tasks in collection.
      $collection->addTaskList($tasks);

      // Run collection.
      return $collection->run();
    }
    else {
      return FALSE;
    }
  }

  /**
   * Return PHPCS with default arguments.
   *
   * @return \Robo\Task\Base\Exec
   *   A PHPCS exec command.
   */
  protected function codeSnifferExec() {
    return $this->taskExec('vendor/squizlabs/php_codesniffer/bin/phpcs');
  }

  /**
   * Return PHPCBF with default arguments.
   *
   * @return \Robo\Task\Base\Exec
   *   A PHPCBF exec command.
   */
  protected function codeBeautifierFixerExec() {
    return $this->taskExec('vendor/squizlabs/php_codesniffer/bin/phpcbf');
  }

  /**
   * Load config.
   */
  protected function loadConfig() {
    $this->config = new Config();
    $loader = new YamlConfigLoader();
    $processor = new ConfigProcessor();

    // Locate root directory.
    $drupalFinder = new DrupalFinder();
    if ($drupalFinder->locateRoot(rtrim('.', '/'))) {
      $composerRoot = $drupalFinder->getComposerRoot();
      $customConfig = $composerRoot . '/robo-drupal-coding.yml';
      // Init config file if not exist.
      if (!file_exists($customConfig)) {
        $this->taskFilesystemStack()->copy(dirname(__DIR__, 4) . '/config/robo-drupal-coding.yml', $customConfig)->run();
        $this->say("<error>Config init, please configure $customConfig in your project.</error>");
        return FALSE;
      }
      $processor->extend($loader->load($customConfig));
    }

    // Import configuration.
    $this->config->import($processor->export());

    return TRUE;
  }

}
