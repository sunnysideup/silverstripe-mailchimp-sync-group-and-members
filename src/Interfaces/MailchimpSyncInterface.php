<?php

use SilverStripe\Security\Member;

interface MailchimpSyncInterface
{
    public static function inst();

    public function addOrUpdateMember(Member $member): MailchimpSyncInterface;
    public function deleteMember(Member $member): MailchimpSyncInterface;
}
