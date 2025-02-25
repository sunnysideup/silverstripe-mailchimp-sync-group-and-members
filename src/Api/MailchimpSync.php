<?php

namespace Sunnysideup\MailchimpSyncGroupAndMembers\Api;

use DrewM\MailChimp\MailChimp;
use MailchimpSyncInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\Member;

/**
 * Class \Sunnysideup\MailchimpSyncGroupAndMembers\Extensions\GroupExtension
 *
 * @property Group|GroupExtension $owner
 */
class MailchimpSync implements MailchimpSyncInterface
{
    use Injectable;
    use Configurable;

    public static function inst()
    {
        return Injector::inst()->get(self::class);
    }

    public static function get_mailchimp_api_key()
    {
        return Environment::getEnv('SS_MAILCHIMP_API_KEY');
    }

    public static function get_mailchimp_list_id()
    {
        return Environment::getEnv('SS_MAILCHIMP_LIST_ID');
    }

    protected static MailChimp $mailchimpHolder;

    protected function MailchimpApiObject(): MailChimp
    {
        if (! isset(self::$mailchimpHolder)) {
            $apiKey = self::get_mailchimp_api_key();
            if (! $apiKey) {
                user_error('Please add SS_MAILCHIMP_API_KEY to your .env file');
            }
            self::$mailchimpHolder = new MailChimp($apiKey);
        }
        return self::$mailchimpHolder;
    }

    public function addMember(Member $member): MailchimpSyncInterface
    {
        $response = $this->MailchimpApiObject()
            ->post(
                "lists/" . self::get_mailchimp_list_id() . "/members",
                [
                    'email_address' => $member->Email,
                    'status' => 'pending',
                    'merge_fields' => ['FNAME' => $member->FirstName, 'LNAME' => $member->Surname],
                    'tags' => [],
                ]
            );

        return $this;
    }

    public function updateMember(Member $member): MailchimpSyncInterface
    {
        $mailchimp = $this->MailchimpApiObject();
        $hash = $mailchimp::subscriberHash($member->Email);
        $response = $mailchimp
            ->patch(
                "lists/" . self::get_mailchimp_list_id() . "/members/" . $hash,
                [
                    'email_address' => $member->Email,
                    'merge_fields' => ['FNAME' => $member->FirstName, 'LNAME' => $member->Surname],
                    'tags' => [],
                ]
            );

        return $this;
    }

    public function deleteMember(Member $member): MailchimpSyncInterface
    {
        $mailchimp = $this->MailchimpApiObject();
        $hash = $mailchimp::subscriberHash($member->Email);
        $response = $mailchimp
            ->delete("lists/" . self::get_mailchimp_list_id() . "/members/" . $hash);

        return $this;
    }
}
