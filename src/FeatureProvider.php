<?php

namespace Sitelease\FeatureFlags;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Config\Config;

use SilverStripe\View\TemplateGlobalProvider;

/**
 * Class for interacting with the available feature flags
 */
class FeatureProvider implements TemplateGlobalProvider
{
    /**
     * Check to see if a feature flag is enabled in the
     * current silverstripe instance
     *
     * @param string $code The feature code to check
     * @param null|array $context An context object or array that the feature will check
     * @return boolean
     */
    public static function isFeatureEnabled(string $code, $context = null): bool
    {
        return Injector::inst()->get(FeatureFlagChecker::class)->isEnabled($code, $context);
    }

    public static function allFeatures(): array
    {
        return (array)Config::inst()->get(self::class, 'features');
    }

    public static function getFeature(string $code): array
    {
        foreach (self::allFeatures() as $feature) {
            if ($feature['code'] == $code) {
                return $feature;
            }
        }
        return array();
    }

    public static function get_template_global_variables()
    {
        return [
            'FeatureEnabled' => 'isFeatureEnabled',
        ];
    }
}
