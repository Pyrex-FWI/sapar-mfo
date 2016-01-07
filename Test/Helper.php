<?php

namespace Cpyree\Organizer\Test;


use Cpyree\Id3\Metadata\Id3Metadata;
use Cpyree\Id3\Wrapper\BinWrapper\MediainfoWrapper;

class Helper
{
	public static function getSampeMp3File()
	{
		return __DIR__ . '/toddle.mp3';
	}

	public static function getWrongMp3File()
	{
		return __DIR__ . '/wrong_file.mp3';
	}

	public static function getMediainfoPath()
	{
		return '/usr/bin/mediainfo';
	}

	public static function getEyed3Path()
	{
		return '/usr/local/bin/eyeD3';
	}

	public static function getId3Metadata($file)
	{
		return new Id3Metadata($file);
	}

	public static function getMedianfoWrapper()
	{
		return (new MediainfoWrapper())->setBinPath(Helper::getMediainfoPath());
	}
}