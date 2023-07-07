<?php

namespace Sitelease\FeatureFlags;

use SilverStripe\Security\Member;

use SilverStripe\ORM\DataObject;

class FeatureFlagHistory extends DataObject
{
    private static $table_name = 'FeatureFlagHistory';

    /**
     * @var array
     */
    private static $db = [
        'EnableMode' => 'Enum("Off, On, Partial", "Off")',
    ];

    /**
     * @var string
     */
    private static $default_sort = '"LastEdited" DESC';

    /**
     * @var array
     */
    private static $has_one = [
        'Author' => Member::class,
        'Flag' => FeatureFlag::class,
    ];

    /**
     * @var array
     */
    private static $searchable_fields = [
        'Enabled',
        'LastEdited',
    ];

    /**
     * @var array
     */
    private static $summary_fields = [
        'Enabled' => 'Enabled',
        'LastEdited' => 'Last edited',
        'Author.Name' => 'Author name',
    ];

    /**
     * @param \SilverStripe\Security\Member $member
     * @param array $context
     * @return boolean
     */
    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    /**
     * @param \SilverStripe\Security\Member $member
     * @return boolean
     */
    public function canDelete($member = null)
    {
        return false;
    }

    /**
     * @param \SilverStripe\Security\Member $member
     * @return boolean
     */
    public function canEdit($member = null)
    {
        return false;
    }

    /**
     * @param \SilverStripe\Security\Member $member
     * @return boolean
     */
    public function canView($member = null)
    {
        return $this->Flag()->canView($member);
    }
}