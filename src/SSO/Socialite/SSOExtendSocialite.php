<?php
namespace FroxlorGmbH\SSO\Socialite;

use SocialiteProviders\Manager\SocialiteWasCalled;

/**
 * @author René Preuß <rene@preuss.io>
 */
class SSOExtendSocialite
{

    /**
     * Register the provider.
     *
     * @param SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'sso', __NAMESPACE__ . '\Provider'
        );
    }
}