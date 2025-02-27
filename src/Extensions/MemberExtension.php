<?php

namespace Sunnysideup\MailchimpSyncGroupAndMembers\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
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
    }

    public function onBeforeWrite()
    {
        /**
         * @var Member $owner
         */
        $owner = $this->getOwner();
        if ($owner->IncludeInMailChimp) {
            MailchimpSync::inst()->addOrUpdateMember($owner);
        } else {
            MailchimpSync::inst()->deleteMember($owner);
        }
    }

    public function onBeforeDelete()
    {
        /**
         * @var Member $owner
         */
        $owner = $this->getOwner();
        if ($owner->IncludeInMailChimp) {
            MailchimpSync::inst()->deleteMember($owner);
        }
    }

    public function getGroupCodes()
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
