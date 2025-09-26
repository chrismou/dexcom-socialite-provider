<?php

namespace SocialiteProviders\Dexcom;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DexcomExtentSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('dexcom', Provider::class);
    }
}