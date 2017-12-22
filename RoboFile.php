<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */

use Robo\Tasks;

class RoboFile extends Tasks {

  /**
   * Assets install (NPM).
   */
  function frontendPull() {
      $this->taskGitStack()
          ->stopOnFail()
          ->pull()
          ->run();
      $frontends = [
          "DAP Client" => "src/DAPClientBundle/Resources/public",
      ];
      foreach ($frontends as $frontend => $path) {
          $this->say("Installing frontend of site '".$frontend."'");
          $this->taskNpmInstall()
              ->dir($path)
              ->noDev()
              ->run();
      }
  }

  /**
   * Assets update (NPM).
   */
  function frontendUpdate() {
      $frontends = [
        "DAP Client" => "src/DAPClientBundle/Resources/public",
      ];
      foreach ($frontends as $frontend => $path) {
          $this->say("Updating frontend of site '".$frontend."'");
          $this->taskNpmUpdate()
              ->dir($path)
              ->noDev()
              ->run();
      }
  }

  /**
   * Assets generate.
   */
  function frontendGenerate() {
      $this->frontendPull();
      $this->taskExecStack()
          ->stopOnFail()
          ->exec('bin/console assets:install --relative --symlink')
          ->exec('bin/console assetic:dump')
          ->exec('bin/console cache:clear')
          ->run();
  }

  /**
   * PHP Coding Standards Fixer (All CCB projects)
   */
  function backendCs() {
  	$this->taskParallelExec()
  		->process('php-cs-fixer fix src/DAPClientBundle/Controller')
  		->process('php-cs-fixer fix src/DAPClientBundle/Services')
      ->run();
  }
}
