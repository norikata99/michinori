<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 画面のテストが、ビルド済みの CSS・JS（public/build）の有無に左右されないようにする。
        // CI で npm run build をしなくてもテストを流せる
        $this->withoutVite();
    }
}
