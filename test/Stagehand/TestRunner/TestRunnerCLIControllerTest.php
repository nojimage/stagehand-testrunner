<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5
 *
 * Copyright (c) 2010-2011 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Stagehand_TestRunner
 * @copyright  2010-2011 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 2.13.0
 */

/**
 * @package    Stagehand_TestRunner
 * @copyright  2010-2011 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 2.13.0
 */
class Stagehand_TestRunner_TestRunnerCLIControllerTest extends PHPUnit_Framework_TestCase
{
    private $commandForAlterationMonitor;
    private $optionsForAlterationMonitor;

    /**
     * @test
     * @link http://redmine.piece-framework.com/issues/197
     */
    public function treatsTheCurrentDirectoryAsTheTestDirectoryIfNoDirectoriesOrFilesAreSpecified()
    {
        $_SERVER['argv'] = $GLOBALS['argv'] = array('bin/phpunitrunner', '-p', 'tests/prepare.php', '-R');
        $_SERVER['argc'] = $GLOBALS['argc'] = count($_SERVER['argv']);
        $oldWorkingDirectory = getcwd();
        chdir(dirname(__FILE__));
        $runner = $this->getMock(
                      'Stagehand_TestRunner_TestRunnerCLIController',
                      array('runTests'),
                      array(Stagehand_TestRunner_Framework::PHPUNIT)
                  );
        $runner->expects($this->any())
               ->method('runTests')
               ->will($this->returnValue(null));
        chdir($oldWorkingDirectory);
        $runner->run();
        $config = $this->readAttribute($runner, 'config');
        $this->assertEquals(1, count($config->testingResources));
        $this->assertEquals(dirname(__FILE__), $config->testingResources[0]);
    }

    /**
     * @test
     * @dataProvider provideLauncherScript
     * @param string $launcherScript
     * @link http://redmine.piece-framework.com/issues/196
     */
    public function buildsACommandStringCorrectlyWhenLaunchingByALauncherScriptWithAlterationMonitoring($launcherScript)
    {
        $_SERVER['_'] = '/usr/bin/php';
        $_SERVER['argv'] = $GLOBALS['argv'] = array($launcherScript, '-a', dirname(__FILE__));
        $_SERVER['argc'] = $GLOBALS['argc'] = count($_SERVER['argv']);
        $runner = $this->getMock(
                      'Stagehand_TestRunner_TestRunnerCLIController',
                      array('createAlterationMonitor'),
                      array(Stagehand_TestRunner_Framework::PHPUNIT)
                  );
        $runner->expects($this->any())
               ->method('createAlterationMonitor')
               ->will($this->returnCallback(array($this, 'createAlterationMonitor')));
        $runner->run();
        $this->assertEquals($_SERVER['_'], $this->commandForAlterationMonitor);
        $this->assertEquals(5, count($this->optionsForAlterationMonitor));
        $this->assertEquals('-c', $this->optionsForAlterationMonitor[0]);
        $this->assertEquals($launcherScript, $this->optionsForAlterationMonitor[2]);
        $this->assertEquals('-R', $this->optionsForAlterationMonitor[3]);
        $this->assertEquals(dirname(__FILE__), $this->optionsForAlterationMonitor[4]);
    }

    public function createAlterationMonitor(array $monitoringDirectories, $command, array $options)
    {
        $this->commandForAlterationMonitor = $command;
        $this->optionsForAlterationMonitor = $options;
        $monitor = $this->getMock(
                       'Stagehand_AlterationMonitor',
                       array('monitor'),
                       array($monitoringDirectories, null)
                   );
        $monitor->expects($this->any())
                ->method('monitor')
                ->will($this->returnValue(null));
        return $monitor;
    }

    public function provideLauncherScript()
    {
        return array(
                   array('phpspecrunner'),
                   array('phptrunner'),
                   array('phpunitrunner'),
                   array('simpletestrunner')
               );
    }

    /**
     * @test
     * @link http://redmine.piece-framework.com/issues/202
     * @since Method available since Release 2.14.0
     */
    public function supportsPhpunitXmlConfigurationFile()
    {
        $phpunitConfigFile = 'phpunit.xml';
        $_SERVER['argv'] = $GLOBALS['argv'] =
            array(
                'bin/phpunitrunner',
                '-p', 'tests/prepare.php',
                '--phpunit-config=' . $phpunitConfigFile
            );
        $_SERVER['argc'] = $GLOBALS['argc'] = count($_SERVER['argv']);
        $oldWorkingDirectory = getcwd();
        chdir(dirname(__FILE__));
        $runner = $this->getMock(
                      'Stagehand_TestRunner_TestRunnerCLIController',
                      array('runTests'),
                      array(Stagehand_TestRunner_Framework::PHPUNIT)
                  );
        $runner->expects($this->any())
               ->method('runTests')
               ->will($this->returnValue(null));
        chdir($oldWorkingDirectory);
        $runner->run();
        $config = $this->readAttribute($runner, 'config');
        $this->assertNotNull($config->phpunitConfigFile);
        $this->assertEquals($phpunitConfigFile, $config->phpunitConfigFile);
    }

    /**
     * @test
     * @link http://redmine.piece-framework.com/issues/230
     * @since Method available since Release 2.16.0
     */
    public function supportsTestFilesWithAnyPattern()
    {
        $testFilePattern = '^test_';
        $_SERVER['argv'] = $GLOBALS['argv'] =
            array(
                'bin/phpunitrunner',
                '-p', 'tests/prepare.php',
                '--test-file-pattern=' . $testFilePattern
            );
        $_SERVER['argc'] = $GLOBALS['argc'] = count($_SERVER['argv']);
        $runner = $this->getMock(
                      'Stagehand_TestRunner_TestRunnerCLIController',
                      array('runTests'),
                      array(Stagehand_TestRunner_Framework::PHPUNIT)
                  );
        $runner->expects($this->any())
               ->method('runTests')
               ->will($this->returnValue(null));
        $runner->run();
        $this->assertEquals($testFilePattern, $this->readAttribute($runner, 'config')->testFilePattern);
    }

    /**
     * @test
     * @link http://redmine.piece-framework.com/issues/211
     * @since Method available since Release 2.14.0
     */
    public function supportsTestFilesWithAnySuffix()
    {
        $testFileSuffix = '_test_';
        $_SERVER['argv'] = $GLOBALS['argv'] =
            array(
                'bin/phpunitrunner',
                '-p', 'tests/prepare.php',
                '--test-file-suffix=' . $testFileSuffix
            );
        $_SERVER['argc'] = $GLOBALS['argc'] = count($_SERVER['argv']);
        $runner = $this->getMock(
                      'Stagehand_TestRunner_TestRunnerCLIController',
                      array('runTests'),
                      array(Stagehand_TestRunner_Framework::PHPUNIT)
                  );
        $runner->expects($this->any())
               ->method('runTests')
               ->will($this->returnValue(null));
        $runner->run();
        $this->assertEquals($testFileSuffix, $this->readAttribute($runner, 'config')->testFileSuffix);
    }
}

/*
 * Local Variables:
 * mode: php
 * coding: iso-8859-1
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */
