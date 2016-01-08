<?php

namespace Cpyree\Organizer\Command;

use Cpyree\Id3\Metadata\Id3Metadata;
use Cpyree\Id3\Wrapper\BinWrapper\MediainfoWrapper;
use Cpyree\Organizer\MediaMoveStack;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class OrganizeCommand extends Command
{

    /** @var  MediainfoWrapper */
    private $mediainfoWrapper;
    /** @var  InputInterface */
    private $input;

    private $movedFiles = 0;
    private $unMovedFiles = 0;
    private $untaggedFiles = 0;
    private $files = 0;
    /** @var  OutputInterface */
    private $ouput;

    protected function configure()
    {
        $this
            ->setName('organizer')
            ->setDescription('Organize media')
            ->addArgument(
                'output-dir',
                InputArgument::REQUIRED,
                'File you want to organize (move to correct place)'
            )
            ->addArgument(
                'file',
                InputArgument::OPTIONAL,
                'File you want to organize (move to correct place)'
            )
            ->addOption(
                'file-dir',
                null,
                InputOption::VALUE_REQUIRED,
                'Dir you want to organize (move file to correct place)'
            )
            ->addOption(
                'mediainfo-bin',
                null,
                InputOption::VALUE_OPTIONAL,
                'pathfile of mediainfo bin',
                '//usr/bin/mediainfo'
            )
            ->addOption(
                'move-untagged-to',
                null,
                InputOption::VALUE_REQUIRED,
                'Move File with insufisant tag to a specific directory'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->ouput = $output;
        $this->mediainfoWrapper = new MediainfoWrapper();
        $this->mediainfoWrapper->setBinPath($this->input->getOption('mediainfo-bin'));

        if ($input->getArgument('file')) {
            $this->doJob($input->getArgument('file'));
        }
        $dir = $input->getOption('file-dir');
        if (is_dir($dir)) {
            $finder = Finder::create();
            $finder->in($dir)->files()->name('/('.implode('|', $this->mediainfoWrapper->getSupportedExtensionsForRead()).')$/');
            $files = iterator_to_array($finder);
            foreach ($files as $file) {
                /** @var SplFileInfo $file */
                $this->doJob($file->getRealPath());
            }
            $output->writeln(sprintf('Files:%s', $this->files));
            $output->writeln(sprintf('Moved:%s', $this->movedFiles));
            $output->writeln(sprintf('UntaggedFiles:%s', $this->untaggedFiles));
            $output->writeln(sprintf('Unmoved:%s', $this->unMovedFiles));
        }

        return 0;
    }

    /**
     * @param $file
     * @throws \Exception
     */
    private function doJob($file)
    {
        $this->files++;
        $id3Meta = new Id3Metadata($file);
        $this->mediainfoWrapper->read($id3Meta);
        $mover = new MediaMoveStack($id3Meta);
        $return = $mover->pathAddMediaYear()->pathAddMediaGenre()->setRemoveParentDirIfEmpty(true)->moveIn($this->input->getArgument('output-dir'));
        if ($return) {
            $this->ouput->writeln($mover->getTargetDest()->getRealPath());
            $this->movedFiles++;
            return;
        }

        $moveToUntagged = $this->input->getOption('move-untagged-to');
        if ($mover->isPartsIsIncomplete() && $moveToUntagged) {
            if (!is_dir($moveToUntagged)) {
                mkdir($moveToUntagged, 0775, true);
            }
            if ($mover->reset()->moveIn($moveToUntagged)) {
                $this->untaggedFiles++;
                return;
            }
        }

        $this->unMovedFiles++;

    }
}