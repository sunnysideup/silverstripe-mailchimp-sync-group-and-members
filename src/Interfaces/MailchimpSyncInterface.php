<?php

use SilverStripe\Security\Group;
use SilverStripe\Security\Member;

interface MailchimpSyncInterface
{
    public static function inst();

    public function addOrUpdateMember(Member $member): MailchimpSyncInterface;

    public function deleteMember(Member $member): MailchimpSyncInterface;

    public function addOrUpdateGroup(Group $group): MailchimpSyncInterface;

    public function deleteGroup(Group $group): MailchimpSyncInterface;
}
