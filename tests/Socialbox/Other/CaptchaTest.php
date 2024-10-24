<?php

namespace Socialbox\Other;

use Gregwar\Captcha\CaptchaBuilder;
use PHPUnit\Framework\TestCase;

class CaptchaTest extends TestCase
{
    public function testCaptchaRendering()
    {
        $builder = new CaptchaBuilder("Foo Bar");
        $builder->build();

        $builder->save(__DIR__ . DIRECTORY_SEPARATOR . 'test.png');
    }
}