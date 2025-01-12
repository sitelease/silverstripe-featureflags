<?php

namespace Sitelease\FeatureFlags;

/**
 * Interface defining a provider for feature flag checks
 */
interface FeatureFlagCheckable
{
    public static function isEnabled(string $code, $context): bool;
}
