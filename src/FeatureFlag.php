<?php

namespace Sitelease\FeatureFlags;

use Sitelease\FeatureFlags\Context\FieldProvider;

use SilverStripe\ORM\DB;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Security\Security;
use SilverStripe\Core\Config\Config;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;

class FeatureFlag extends DataObject implements PermissionProvider
{
    private static $table_name = 'FeatureFlag';

    private static $db = [
        'Code' => 'Varchar(50)',
        'Title' => 'Varchar',
        'Status' => 'Varchar',
        'Description' => 'Text',
        'EnableMode' => 'Enum("Off, On, Partial", "Off")',
    ];

    private static $indexes = [
        'Code' => true,
        'EnableMode' => true,
    ];

    private static $has_many = [
        'Items' => FeatureFlagItem::class,
    ];

    private static $summary_fields = [
        'Title',
        'Status',
        'Description',
        'EnableMode',
    ];

    private static $searchable_fields = [
        'Title',
        'Code',
        'Status',
        'Description',
        'EnableMode',
    ];

    private static $default_sort = '"Title" ASC';

    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    public function canEdit($member = null)
    {
        return Permission::check('EDIT_FEATURE_FLAGS', 'any', $member);
    }

    public function canDelete($member = null)
    {
        return false;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root.Main', new ReadonlyField('Code'), 'EnableMode');
        $fields->removeFieldFromTab('Root', 'Items');

        $fieldProviders = $this->getFieldProviders();
        foreach ($fieldProviders as $fieldProvider) {
            $this->addContextFields($fields, $fieldProvider);
        }

        // If there are no context field providers, Partial mode is not allowed
        if (!$fieldProviders) {
            $fields->dataFieldByName('EnableMode')->setSource([
                'On' => 'On',
                'Off' => 'Off',
            ]);
        }

        return $fields;
    }

    public function saveContextFromForm(Form $form)
    {
        foreach ($this->getFieldProviders() as $fieldProvider) {
            $this->saveContext($form, $fieldProvider);
        }
    }

    /**
     * Add fields for the given context key, using the field provider for the given class
     */
    protected function addContextFields(FieldList $fields, FieldProvider $fieldProvider)
    {

        foreach ($fieldProvider->getCMSFields() as $field) {
            $fields->addFieldToTab('Root.Main', $field);
        }

        $ids = $this->Items()->filter([ 'ContextKey' => $fieldProvider->getKey() ])->column('ContextID');
        $formData = $fieldProvider->convertItemsToFormData($ids);
        $fields->setValues($formData);
    }

    /**
     * Add fields for the given field provider
     */
    protected function saveContext(Form $form, FieldProvider $fieldProvider)
    {
        $items = $fieldProvider->convertFormDataToItems($form->getData());
        $key = $fieldProvider->getKey();

        // Remove bad items
        $badItems = $this->Items()->filter([ 'ContextKey' => $key ]);
        if ($items) {
            $badItems = $badItems->filter([ 'ContextID:not' => $items ]);
        }

        foreach ($badItems as $item) {
            $item->delete();
        }

        if ($items) {
            // Itentify existing items to neither add nor delete
            $existingItems = $this->Items()
                ->filter([ 'ContextKey' => $key, 'ContextID' => $items ])
                ->column('ContextID');

            // Add new items
            foreach (array_diff($items, $existingItems) as $itemID) {
                $item = new FeatureFlagItem();
                $item->ContextKey = $key;
                $item->ContextID = $itemID;
                $this->Items()->add($item);
            }
        }
    }

    /**
     * Return the FieldProvider instances for selecting the context of this feature flag
     */
    protected function getFieldProviders()
    {
        $feature = FeatureProvider::getFeature($this->Code);
        if (empty($feature['context'])) {
            return [];
        }

        $fieldProviderMap = Config::inst()->get(FeatureFlagAdmin::class, 'context_field_providers');
        $fieldProviders = [];

        foreach ($feature['context'] as $key => $className) {
            if (empty($fieldProviderMap[$className])) {
                throw new \LogicException('Can\'t find context field provider for ' . $className);
            }
            $fieldProviderClass = $fieldProviderMap[$className];
            $fieldProvider = new $fieldProviderClass();
            $fieldProvider->setKey($key);
            $fieldProviders[] = $fieldProvider;
        }

        return $fieldProviders;
    }

    public function requireDefaultRecords()
    {
        $features = FeatureProvider::allFeatures();
        if (!empty($features)) {
            foreach ($features as $feature) {
                $alteration = false;
                $record = self::get()->filter('Code', $feature['code'])->first();

                if (!$record) {
                    $alteration = 'created';
                    $record = new FeatureFlag();
                    $record->Code = $feature['code'];
                    $record->Status = $feature['status'];
                    $record->Title = $feature['title'];
                    if (isset($feature['description'])) {
                        $record->Description = $feature['description'];
                    }
                    $record->EnableMode = $feature['enabled'];
                } else {
                    if (
                        array_key_exists('description', $feature)
                        && $record->Description != $feature['description']
                    ) {
                        $alteration = 'changed';
                        $record->Description = $feature['description'];
                    }
                    if (
                        array_key_exists('status', $feature)
                        && $record->Status != $feature['status']
                    ) {
                        $alteration = 'changed';
                        $record->Status = $feature['status'];
                    }
                    if (
                        array_key_exists('title', $feature)
                        && $record->Title != $feature['title']
                    ) {
                        $alteration = 'changed';
                        $record->Title = $feature['title'];
                    }
                }

                if ($alteration) {
                    $record->write();
                    DB::alteration_message("Feature '$feature[code]' $alteration", $alteration);
                }
            }

            $featuresNames = array_map(
                function ($feature) {
                    return $feature['code'];
                },
                $features
            );

            $flagsToDelete = self::get()->exclude('Code', $featuresNames);

            foreach ($flagsToDelete as $feature) {
                $feature->delete();
                DB::alteration_message("Flag '$feature->Code' deleted", 'deleted');
            }
        }
    }

    /**
     * @return void
     */
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        $member = Security::getCurrentUser();

        $historyRecord = FeatureFlagHistory::create();
        $historyRecord->EnableMode = $this->EnableMode;
        $historyRecord->AuthorID = $member->ID;
        $historyRecord->FeatureFlagID = $this->ID;
        $historyRecord->write();
    }

    public function providePermissions()
    {
        return [
            'EDIT_FEATURE_FLAGS' => [
                'name' => 'Modify Feature Flags',
                'category' => 'Feature Flags',
            ],
        ];
    }
}
