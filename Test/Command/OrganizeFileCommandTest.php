<?php

namespace AppBundle\Tests\Command;

use Sapar\Mfo\Command\OrganizeFileCommand;
use Sapar\Mfo\Test\Helper;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class OrganizeFileCommandTest extends \PHPUnit_Framework_TestCase
{
    /** @var  Container */
    protected $application;
    /** @var  CommandTester */
    protected $commandTester;
    /** @var  Command */
    protected $command;


    protected function setUp()
    {
        $this->application = new Application();
        $this->application->add(new OrganizeFileCommand());
        $this->command = $this->application->find('organizer:files');
        $this->commandTester = new CommandTester($this->command);
    }

    public function testCommandExecute()
    {
        /** @var OrganizeCommand $command */

        $this->commandTester->execute([
            'command'       => $this->command->getName(),
            'output-dir'    => __DIR__,
            'file'          => realpath(__DIR__.'/../toddle.mp3'),
            '-vvv'
        ]);

        $output = trim($this->commandTester->getDisplay());
        $dest = new \SplFileInfo($output);
        $this->assertContains('Celtic', $dest->getRealPath());
        $this->assertTrue(true, is_file($output));
        $this->assertContains('2003', $dest->getRealPath());
        rename($dest->getRealPath(), Helper::getSampeMp3File());
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/2003/');


        $noTagDir = __DIR__.'/NO_TAG';
        $this->commandTester->execute([
            'command'       => $this->command->getName(),
            'output-dir'    => __DIR__,
            'file'          => realpath(__DIR__.'/../wrong_file.mp3'),
            '--move-untagged-to'    => $noTagDir,
            '-vvv'
        ]);
        $noTagFile = $noTagDir.'/wrong_file.mp3';
        $this->assertTrue(is_file($noTagFile));
        rename($noTagFile, __DIR__.'/../wrong_file.mp3');
        $fs = new Filesystem();
        $fs->remove($noTagDir);
    }

    public function testDirCommandExecute()
    {
        /** @var OrganizeCommand $command */

        $this->commandTester->execute([
            'command'       => $this->command->getName(),
            'output-dir'    => __DIR__,
            '--file-dir'    => realpath(__DIR__.'/../'),
            '-vvv'
        ]);

        $output = $this->commandTester->getDisplay();
        $outputArray = array_map('trim', explode(PHP_EOL, $output));
        $dest = new \SplFileInfo($outputArray[0]);
        $this->assertContains('Celtic', $dest->getRealPath());
        $this->assertTrue(true, is_file($outputArray[0]));
        $this->assertContains('2003', $dest->getRealPath());
        rename($dest->getRealPath(), Helper::getSampeMp3File());
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/2003/');
    }

    public function testDumpCommandExecute()
    {
        /** @var OrganizeCommand $command */
        $noTagDir = __DIR__.'/NO_TAG';
        $this->commandTester->execute([
            'command'       => $this->command->getName(),
            'output-dir'    => __DIR__,
            '--file-dir'    => realpath(__DIR__.'/../'),
            '--dump-command'=> ' ',
            '--move-untagged-to'    => $noTagDir,

        ]);

        $output = $this->commandTester->getDisplay();
        $outputArray = array_map('trim', explode(PHP_EOL, $output));
        $this->assertEquals('mv \''.realpath(__DIR__.'/../').'/toddle.mp3\' \''.__DIR__.'/2003/Celtic/toddle.mp3\'', $outputArray[0]);
        $this->assertEquals('mv \''.realpath(__DIR__.'/../').'/wrong_file.mp3\' \''.__DIR__.'/NO_TAG/wrong_file.mp3\'', $outputArray[1]);
    }

    /**
     *
     */
    public function testCommandLineExecute()
    {
        /** @var OrganizeCommand $command */
        $outputDir = (__DIR__);
        $fildir = realpath(__DIR__.'/../');
        $bin = realpath(__DIR__.'/../../src/bin/organize');

        $cmd = ("find $fildir -type f -name '*.mp3' -exec $bin organizer:files $outputDir {} \\;");
        $output = trim(shell_exec($cmd));
        $dest = new \SplFileInfo($output);

        $this->assertContains('Celtic', $dest->getRealPath());
        $this->assertTrue(true, is_file($output));
        $this->assertContains('2003', $dest->getRealPath());

        rename($dest->getRealPath(), Helper::getSampeMp3File());
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/2003/');
    }

}

