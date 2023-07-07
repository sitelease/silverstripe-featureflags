<?php

namespace Sitelease\FeatureFlags;

use Sitelease\FeatureFlags\GridField\FeatureContextItem;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridFieldDetailForm;

class FeatureFlagAdmin extends ModelAdmin
{
    private static $managed_models = [
        FeatureFlag::class => [
            'title' => 'Features'
        ],
        FeatureFlagHistory::class => [
            'title' => 'History'
        ],
    ];

    private static $url_segment = 'feature-flags';

    private static $menu_title = 'Feature Flags';

    private static $menu_icon_class = 'font-icon-check-mark-2';

    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        if ($gridField = $form->Fields()->dataFieldByName('SilverStripe-FeatureFlags-FeatureFlag')) {
            $gridField->getConfig()
                ->getComponentByType(GridFieldDetailForm::class)
                ->setItemRequestClass(FeatureContextItem::class);
        }

        return $form;
    }
}
