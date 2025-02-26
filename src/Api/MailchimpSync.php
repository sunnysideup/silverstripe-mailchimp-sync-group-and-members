<?php

namespace Sunnysideup\MailchimpSyncGroupAndMembers\Api;

use DrewM\MailChimp\MailChimp;
use MailchimpSyncInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Group;
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

    public function addOrUpdateMember(Member $member): MailchimpSyncInterface
    {
        $mailchimp = $this->MailchimpApiObject();
        $hash = $mailchimp::subscriberHash($member->Email);
        $response = $this->MailchimpApiObject()
            ->put(
                "lists/" . self::get_mailchimp_list_id() . "/members/" . $hash,
                [
                    'email_address' => $member->Email,
                    'status_if_new' => 'pending',
                    'merge_fields' => ['FNAME' => $member->FirstName, 'LNAME' => $member->Surname],
                    'tags' => $member->getGroupCodes(),
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

    public function bulkAddOrUpdateMembers(DataList $members)
    {
        $mailchimp = $this->MailchimpApiObject();
        $batch = $mailchimp->new_batch();
        foreach ($members as $member) {
            $hash = $mailchimp::subscriberHash($member->Email);
            $batch->put(
                strval($member->ID),
                "lists/" . self::get_mailchimp_list_id() . "/members/" . $hash,
                [
                    'email_address' => $member->Email,
                    'status_if_new' => 'pending',
                    'merge_fields' => ['FNAME' => $member->FirstName, 'LNAME' => $member->Surname],
                    'tags' => $member->getGroupCodes(),
                ]
            );
        }
        $result = $batch->execute();
        return $this;
    }

    public function addOrUpdateGroup(Group $group): MailchimpSyncInterface
    {
        // Tag will automatically be created if it does not exist
        $this->bulkAddOrUpdateMembers($group->Members()->filter('IncludeInMailChimp', true));
        return $this;
    }

    public function deleteGroup(Group $group): MailchimpSyncInterface
    {
        // TODO: Need to get the ID of the tag in order to completely delete using the Static Segment API Endpoint
        return $this;
    }
}
