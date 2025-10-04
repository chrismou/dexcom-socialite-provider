<?php

declare(strict_types=1);

namespace SocialiteProviders\Dexcom;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DexcomExtentSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('dexcom', Provider::class);
    }
}
