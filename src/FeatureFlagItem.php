<?php

namespace Sitelease\FeatureFlags;

use SilverStripe\ORM\DataObject;

class FeatureFlagItem extends DataObject
{
    private static $db = [
        'ContextKey' => 'Varchar(50)',
        'ContextID' => 'Int',
    ];

    private static $has_one = [
        'FeatureFlag' => FeatureFlag::class,
    ];
}
