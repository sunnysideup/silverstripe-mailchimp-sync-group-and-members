<?php

namespace Sunnysideup\MailchimpSyncGroupAndMembers\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
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
        /**
         * @var Member $owner
         */
        if (MailchimpSync::is_ready_to_sync()) {
            $owner = $this->getOwner();
            if ($owner->IncludeInMailChimp) {
                MailchimpSync::inst()->addOrUpdateMember($owner);
            } else {
                MailchimpSync::inst()->deleteMember($owner);
            }
        }
    }

    public function onBeforeDelete()
    {
        /**
         * @var Member $owner
         */
        if (MailchimpSync::is_ready_to_sync()) {
            $owner = $this->getOwner();
            if ($owner->IncludeInMailChimp) {
                MailchimpSync::inst()->deleteMember($owner);
            }
        }
    }

    public function getMailchimpGroupCodes()
    {
        $owner = $this->getOwner();
        $allGroupNames = MailchimpSync::inst()->getAllCurrentGroupTagNames();
        $ownGroupNames = $owner->Groups()->filter('IncludeInMailChimp', true)->column('Code');
        $tags = [];
        foreach ($allGroupNames as $groupName) {
            if (in_array($groupName, $ownGroupNames, true)) {
                $tags[] = [
                    'name' => $groupName,
                    'status' => 'active',
                ];
            } else {
                $tags[] = [
                    'name' => $groupName,
                    'status' => 'inactive',
                ];
            }
        }
        return $tags;
    }
}
