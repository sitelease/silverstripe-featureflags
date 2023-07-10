<?php

namespace Sitelease\FeatureFlags;

/**
 * Default implementation fo FeatureFlagCheckable.
 * Uses the FeatureFlag / FeatureFlagItem data objects
 */
class FeatureFlagChecker implements FeatureFlagCheckable
{
    public static function isEnabled(string $code, $context = null): bool
    {
        $feature = FeatureFlag::get()->filter([ 'Code' => $code ])->first();

        // Simple modes
        if ($feature->EnableMode === 'On') {
            return true;
        }
        if ($feature->EnableMode === 'Off') {
            return false;
        }

        // TODO: validate context
        if (isset($context)) {
            // Check each context value against the selections
            foreach ($context as $key => $obj) {
                $contextTest = $feature->Items()->filter([
                    'ContextKey' => $key,
                    'ContextID' => $obj ? $obj->ID : 0,
                ]);

                // Any context match will result in the feature being enabled
                if ($contextTest->count() > 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
