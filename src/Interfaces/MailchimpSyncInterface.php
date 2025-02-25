<?php

use SilverStripe\Security\Member;

interface MailchimpSyncInterface
{
    public static function inst();

    public function addMember(Member $member): MailchimpSyncInterface;
    public function updateMember(Member $member): MailchimpSyncInterface;
    public function deleteMember(Member $member): MailchimpSyncInterface;
}
