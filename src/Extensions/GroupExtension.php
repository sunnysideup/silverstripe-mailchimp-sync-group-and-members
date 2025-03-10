<?php

namespace Sunnysideup\MailchimpSyncGroupAndMembers\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Group;
use Sunnysideup\MailchimpSyncGroupAndMembers\Api\MailchimpSync;

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
        if (!MailchimpSync::is_ready_to_sync()) {
            $fields->addFieldsToTab(
                'Root.Mailchimp',
                [
                    LiteralField::create(
                        'MailchimpNotReady',
                        '<p>Mailchimp is not ready to sync. Please check your configuration with your developer.</p>'
                    )
                ]
            );
        }
    }

    public function onBeforeWrite()
    {
        if (MailchimpSync::is_ready_to_sync()) {
            /**
             * @var Group $owner
             */
            $owner = $this->getOwner();

            if ($owner->IncludeInMailChimp) {
                MailchimpSync::inst()->addOrUpdateGroup($owner);
            } else {
                MailchimpSync::inst()->deleteGroup($owner);
            }
        }
    }

    public function onBeforeDelete()
    {
        if (MailchimpSync::is_ready_to_sync()) {
            /**
             * @var Group $owner
             */
            $owner = $this->getOwner();
            if ($owner->IncludeInMailChimp) {
                MailchimpSync::inst()->deleteGroup($owner);
            }
        }
    }
}
