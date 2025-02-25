<?php

namespace Sunnysideup\MailchimpSyncGroupAndMembers\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Group;
use Sunnysideup\MailchimpSyncGroupAndMembers\Api\MailchimpSync;

/**
 * Class \Sunnysideup\MailchimpSyncGroupAndMembers\Extensions\MemberExtension
 *
 * @property Group|MemberExtension $owner
 */
class MemberExtension extends Extension
{
    private static $db = [
        'IncludeInMailChimp' => 'Boolean(0)',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldsToTab(
            'Root.Mailchimp',
            [
                CheckboxField::create('IncludeInMailChimp')
                    ->setDescription('Include this member as a subscriber in synchronized Mailchimp lists')
            ]
        );
    }

    public function onBeforeWrite()
    {
        if ($this->owner->IncludeInMailChimp) {
            MailchimpSync::inst()->addMember($this->owner);
        }
    }
}
