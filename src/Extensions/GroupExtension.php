<?php

namespace Sunnysideup\MailchimpSyncGroupAndMembers\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Group;

/**
 * Class \Sunnysideup\MailchimpSyncGroupAndMembers\Extensions\GroupExtension
 *
 * @property Group|GroupExtension $owner
 */
class GroupExtension extends Extension
{
    private static $db = [
        'IncludeInMailChimp' => 'Boolean',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldsToTab(
            'Root.Mailchimp',
            [
                CheckboxField::create('IncludeInMailChimp')
                    ->setDescription('Include this group as a tag in synchronized Mailchimp lists')
            ]
        );
    }
}
