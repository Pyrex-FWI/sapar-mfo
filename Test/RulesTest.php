<?php

namespace Cpyree\Organizer\Test;


use Cpyree\Organizer\MediaMoveStack;
use Cpyree\Organizer\Test\Helper;

class RulesTest extends \PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
    }

    /**
     * @throws \Exception
     */
    public function testFileGenreRule()
    {
        $mediaInfoWrapper = Helper::getMedianfoWrapper();
        $id3meta = \Cpyree\Organizer\Test\Helper::getId3Metadata(\Cpyree\Organizer\Test\Helper::getSampeMp3File());
        $mediaInfoWrapper->read($id3meta);
        $mediaMove = new MediaMoveStack($id3meta);
        $moveResult = $mediaMove->reset()->pathAddMediaGenre()->pathAddMediaYear()->pathAddFileMonth()->moveIn(__DIR__.'/..//');
        $this->assertEquals(true, $moveResult);
        $this->assertEquals(true, $moveResult);
        $this->assertContains('Celtic', ($mediaMove->getTargetDest()->__toString()));
        $this->assertContains('2003', ($mediaMove->getTargetDest()->__toString()));

        rename($mediaMove->getTargetDest(), Helper::getSampeMp3File());
        rmdir($mediaMove->getTargetDest()->getPath());

    }

}
