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
    public static function isFeatureEnabled($code, $context)
    {
        return Injector::inst()->get(FeatureFlagChecker::class)->isEnabled($code, $context);
    }

    public static function allFeatures()
    {
        return (array)Config::inst()->get(self::class, 'features');
    }

    public static function getFeature($code)
    {
        foreach (self::allFeatures() as $feature) {
            if ($feature['code'] == $code) {
                return $feature;
            }
        }
    }

    public static function get_template_global_variables()
    {
        return [
            'FeatureEnabled' => 'isFeatureEnabled',
        ];
    }
}
