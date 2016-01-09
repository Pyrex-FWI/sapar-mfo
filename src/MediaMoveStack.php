<?php

namespace Cpyree\Organizer;

use Cpyree\Entity\Media;
use Cpyree\Id3\Metadata\Id3Metadata;
use Deejay\Id3ToolBundle\Wrapper\Id3Manager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Class MediaMoveStack
 * @package Cpyree\Organizer
 */
class MediaMoveStack
{
    /** @var Id3Metadata  */
    private $id3metadata;
    /** @var array  */
    private $pathParts = [];
    /** @var array  */
    private $fileParts = [];
    /** @var bool  */
    private $partsIsIncomplete = false;
    /** @var  \SplFileInfo */
    private $targetDest;
    /** @var bool  */
    private $removeParentDirIfEmpty = false;
    /** @var bool  */
    private $buildNativeCommand = false;
    /** @var  string */
    private $nativeCommad;

    /**
     * @param boolean $partsIsIncomplete
     * @return MediaMoveStack
     */
    public function setPartsIsIncomplete($partsIsIncomplete)
    {
        $this->partsIsIncomplete = $partsIsIncomplete;
        return $this;
    }

    /**
     * MediaMoveStack constructor.
     * @param Id3Metadata $id3Metadata
     */
    public function __construct(Id3Metadata $id3Metadata)
    {
        $this->id3metadata = $id3Metadata;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->pathParts = [];
        $this->fileParts = [];
        $this->partsIsIncomplete = false;
        $this->nativeCommad = null;
        return $this;
    }

    /**
     * @return $this
     */
    public function pathAddMediaGenre()
    {
        $genre = $this->id3metadata->getGenre();
        if ($genre) {
            $this->pathParts[] = $genre;
        }
        if (!$genre) {
            $this->setPartsIsIncomplete(true);
        }
        return $this;
    }
    /**
     * @return $this
     */
    public function pathAddMediaYear()
    {
        $year = $this->id3metadata->getYear();
        if ($year) {
            $this->pathParts[] = $year;
        }
        if (!$year) {
            $this->setPartsIsIncomplete(true);
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function pathAddFileMonth()
    {
        $modifiedMonth = date('F', $this->id3metadata->getFile()->getCTime());
        if ($modifiedMonth) {
            $this->pathParts[] = $modifiedMonth;
        }
        if (!$modifiedMonth) {
            $this->setPartsIsIncomplete(true);
        }
        return $this;
    }

    /**
     * @param $outPath
     * @return bool
     */
    public function moveIn($outPath)
    {

        if (!is_dir($outPath) || $this->isPartsIsIncomplete() === true) {
            return false;
        }

        $outPath = rtrim(realpath($outPath), DIRECTORY_SEPARATOR);
        $targetPathParts = array_filter([$outPath, $this->getPathPart(), $this->getFilePart()]);
        $target = implode(DIRECTORY_SEPARATOR, $targetPathParts);
        $targetFileInfo = new \SplFileInfo(($target));
        if ($this->id3metadata->getFile()->getPathname() == $targetFileInfo->getPathname()) {
            return false;
        }
        if ($this->getBuildNativeCommand()) {
            $this->buildNativeCommand($this->id3metadata->getFile()->getPathname(), $targetFileInfo->getPathname());
            return true;
        }

        try {
            $fs = new Filesystem();
            if (!is_dir($targetFileInfo->getPath())) {
                $fs->mkdir($targetFileInfo->getPath());
            }
            $fs->rename($this->id3metadata->getFile()->getPathname(), $targetFileInfo->getPathname(), true);
            $this->targetDest = new \SplFileInfo($target);

            if ($this->removeParentDirIfEmpty() && $this->dir_empty($this->id3metadata->getFile()->getPath()) === true ) {
                rmdir($this->id3metadata->getFile()->getPath());
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $dir
     * @return bool|null
     */
    private function dir_empty($dir) {
        if (!is_readable($dir)) return NULL;
        $dirCount = Finder::create()->in($dir)->ignoreDotFiles(true)->directories()->depth(0)->count();
        $fileCount = Finder::create()->in($dir)->ignoreDotFiles(true)->files()->depth(0)->count();

        return $dirCount == 0  && $fileCount == 0;
    }

    /**
     * @return boolean
     */
    public function isPartsIsIncomplete()
    {
        return $this->partsIsIncomplete;
    }

    /**
     * @return string
     */
    private function getPathPart()
    {
        return implode(DIRECTORY_SEPARATOR, array_map([$this,'sanitize'], $this->pathParts));
    }

    /**
     * @return string
     */
    private function getFilePart()
    {
        $fileParts = array_map([$this,'sanitize'], $this->fileParts);
        if (empty($fileParts)) {
            $fileParts[] = $this->id3metadata->getFile()->getFilename();
        }
        return implode('', $fileParts);
    }

    /**
     * @param $string
     * @return mixed
     */
    private function sanitize($string)
    {
        $string = preg_replace('/[^a-zA-Z0-9\-\_&\s]/', '', $string);
        $string = ucwords(mb_strtolower($string));

        return $string;
    }

    /**
     * @return \SplFileInfo
     */
    public function getTargetDest()
    {
        return $this->targetDest;
    }

    /**
     * @return boolean
     */
    public function removeParentDirIfEmpty()
    {
        return $this->removeParentDirIfEmpty;
    }

    /**
     * @param $removeParentDirIfEmpty
     * @return $this
     */
    public function setRemoveParentDirIfEmpty($removeParentDirIfEmpty)
    {
        $this->removeParentDirIfEmpty = $removeParentDirIfEmpty;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getBuildNativeCommand()
    {
        return $this->buildNativeCommand;
    }

    /**
     * @param $buildNativeCommand
     * @return $this
     */
    public function setBuildNativeCommand($buildNativeCommand)
    {
        $this->buildNativeCommand = $buildNativeCommand;

        return $this;
    }

    private function buildNativeCommand($src, $dest)
    {
        $this->nativeCommad = sprintf('mv %s %s', escapeshellarg($src), escapeshellarg($dest));
    }

    /**
     * @return string
     */
    public function getNativeCommand()
    {
        return $this->nativeCommad;
    }


}