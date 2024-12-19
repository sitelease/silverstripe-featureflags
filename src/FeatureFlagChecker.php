<?php

namespace Sitelease\FeatureFlags;

/**
 * Default implementation fo FeatureFlagCheckable.
 * Uses the FeatureFlag / FeatureFlagItem data objects
 */
class FeatureFlagChecker implements FeatureFlagCheckable
{
    public static $featureRecordCache;

    /**
     * Returns a cached array of features
     *
     * If the cache is empty this will also hydrate it
     *
     * @return array
     */
    public static function getCachedFeatures(): array
    {
        $cache = self::$featureRecordCache;
        if (empty($cache)) {
            $cache = FeatureFlag::get()->map('Code', 'EnableMode')->toArray();
            self::$featureRecordCache = $cache;
        }
        return $cache;
    }

    public static function isEnabled(string $code, $context = null): bool
    {
        $features = self::getCachedFeatures();
        if (isset($features[$code])) {
            $feature = $features[$code];
            // Simple modes
            if ($feature === 'On') {
                return true;
            } elseif ($feature === 'Off') {
                return false;
            } else {
                throw new \LogicException(
                    "Feature mode not supported - The $code feature is set to $feature which is not supported."
                    .' You must set it to "On" or "Off" in the feature manager'
                );
            }

            // TODO: validate context
            // if (isset($context)) {
            //     // Check each context value against the selections
            //     foreach ($context as $key => $obj) {
            //         $contextTest = $feature->Items()->filter([
            //             'ContextKey' => $key,
            //             'ContextID' => $obj ? $obj->ID : 0,
            //         ]);

            //         // Any context match will result in the feature being enabled
            //         if ($contextTest->count() > 0) {
            //             return true;
            //         }
            //     }
            // }
        } else {
            throw new \LogicException(
                "Unknown feature check requested - A feature check was requested for a feature code of: $code."
                . ' No feature record exists for that code in the system.'
            );
        }

        return false;
    }
}
