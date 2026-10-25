<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private ?string $originalPortal = null;

    /**
     * 指定したポータルとしてアプリケーションを作り直す。
     * ルートはブート時に APP_PORTAL で分岐するため、環境変数を書き換えてから再生成する。
     */
    protected function usePortal(string $portal): void
    {
        $this->originalPortal ??= (string) env('APP_PORTAL');

        $this->setPortalEnv($portal);
        $this->refreshApplication();
    }

    protected function tearDown(): void
    {
        if ($this->originalPortal !== null) {
            $this->setPortalEnv($this->originalPortal);
            $this->originalPortal = null;
        }

        parent::tearDown();
    }

    private function setPortalEnv(string $portal): void
    {
        putenv("APP_PORTAL={$portal}");
        $_ENV['APP_PORTAL'] = $portal;
        $_SERVER['APP_PORTAL'] = $portal;
    }
}
