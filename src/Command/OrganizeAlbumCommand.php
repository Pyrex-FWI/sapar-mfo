<?php

/*
 * This file is part of the Audio Api.
 *
 * (c) Christophe Pyree
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sapar\Mfo\Command;

use AppBundle\Event\DirectoryEvent;
use AppBundle\Service\TempDir;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 *
 */
class OrganizeAlbumCommand extends Command
{
    /** @var  OutputInterface */
    private $output;
    /** @var  InputInterface */
    private $input;

    const NAME = 'organizer:album';
    /** @var  EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Dispatch Release album dirs')
            ->addArgument('albums-dir', InputArgument::REQUIRED, 'Dir to Analyze')
            ->addArgument('dir-output', InputArgument::REQUIRED, 'Root path to move dir if all files genre are same')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run this command in test mode')
                  ->setHelp(<<<EOF
The <info>%command.name%</info>
<info>php %command.full_name%</info>

<info>php %command.full_name%</info>
EOF
            );
    }

    private function init(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;

        $this->checkParameters();
        $this->eventDispatcher = $this->getContainer()->get('event_dispatcher');
    }

    /**
     * @return sring
     */
    public function getRootOutput()
    {
        return rtrim($this->input->getArgument('dir-output'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    }

    /**
     * @return string
     */
    public function getTempDir()
    {
        return rtrim($this->input->getArgument('albums-dir'), DIRECTORY_SEPARATOR);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init($input, $output);

        /** @var TempDir $tmpDirManager */
        $tmpDirManager = ($this->getContainer()->get('albums-dir'));
        $tmpDirManager
            ->setTempDirectory($this->getTempDir())
            ->setRootDirectory($this->getRootOutput())
            ->setStrictMode(true);
        if (!$tmpDirManager->readTempDirectoryMeta()) {
            return 1;
        }

        if (!$tmpDirManager->canBeMoved()) {
            $this->output->writeln('Skip: '.$this->getTempDir());
            $this->output->writeln(implode(PHP_EOL, $tmpDirManager->getErrors()));

            return 1;
        }

        $this->output->writeln(sprintf('[%s - %s] Move %s to %s', $tmpDirManager->getTempDirectoryId3Years()[0], $tmpDirManager->getTempDirectoryId3Genres()[0], $tmpDirManager->getTempDirectoryId3Albums()[0], $tmpDirManager->getNewPath()));

        if (!$this->isDryMode()) {
            $tmpDirManager->move();
        }

        $year = implode(' ,', $tmpDirManager->getTempDirectoryId3Years());
        $genres = implode(' ,', $tmpDirManager->getTempDirectoryId3Genres());
        $artists = implode(' ,', $tmpDirManager->getTempDirectoryId3Artists());
        $albums = implode(' ,', $tmpDirManager->getTempDirectoryId3Albums());
        $dirEvent = new DirectoryEvent(new \SplFileInfo($this->getTempDir()), $genres, $albums, $artists, $year);
        $this->eventDispatcher->dispatch(\AppBundle\Event\Event::DIRECTORY_POST_MOVE, $dirEvent);

        return 0;
    }

    protected function checkParameters()
    {
        if (!is_dir($this->getTempDir())) {
            throw  new \Exception(sprintf('%s is not valid temp-dir', $this->getTempDir()));
        }
        if (!is_dir($this->getRootOutput())) {
            throw  new \Exception(sprintf('%s is not valid root-dir', $this->getRootOutput()));
        }
    }

    /**
     * @return bool
     */
    private function isDryMode()
    {
        return (bool) $this->input->getOption('dry-run');
    }
}
