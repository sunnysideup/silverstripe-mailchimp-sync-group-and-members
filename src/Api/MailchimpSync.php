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
use Sunnysideup\MailchimpSyncGroupAndMembers\Extensions\GroupExtension;
use Sunnysideup\MailchimpSyncGroupAndMembers\Model\MailchimpLog;

/**
 */
class MailchimpSync implements MailchimpSyncInterface
{
    use Injectable;
    use Configurable;

    protected $mailchimp;

    public static function inst()
    {
        return Injector::inst()->get(self::class);
    }

    public function __construct()
    {
        $this->mailchimp = $this->MailchimpApiObject();
    }

    public static function get_mailchimp_api_key(): ?string
    {
        return Environment::getEnv('SS_MAILCHIMP_API_KEY');
    }

    public static function get_mailchimp_list_id():?string
    {
        return Environment::getEnv('SS_MAILCHIMP_LIST_ID');
    }

    public static function has_subscribe_permission(): bool
    {
        return Environment::getEnv('SS_HAS_MAILCHIMP_SUBSCRIBE_PERMISSION') ? true : false;
    }

    public static function subscribe_permission_style(): string
    {
        return self::has_subscribe_permission() ? 'subscribed' : 'pending';
    }

    public static function is_ready_to_sync(): bool
    {
        return self::get_mailchimp_api_key() && self::get_mailchimp_list_id();
    }

    protected static MailChimp $mailchimpHolder;

    protected function MailchimpApiObject(): MailChimp
    {
        if (! isset(self::$mailchimpHolder)) {
            $apiKey = self::get_mailchimp_api_key();
            self::$mailchimpHolder = new MailChimp($apiKey);
        }
        return self::$mailchimpHolder;
    }

    public function addOrUpdateMember(Member $member): static
    {
        $statusIfNew = self::subscribe_permission_style();
        $hash = MailChimp::subscriberHash($member->Email);
        $log = MailchimpLog::start_log($member, null, __FUNCTION__);
        $response = $this->MailchimpApiObject()
            ->put(
                "lists/" . self::get_mailchimp_list_id() . "/members/" . $hash,
                [
                    'email_address' => $member->Email,
                    'status_if_new' => $statusIfNew,
                    'merge_fields' => ['FNAME' => $member->FirstName, 'LNAME' => $member->Surname],
                ]
            );
        $log->endLog($response);
        $this->setMemberTags($member);
        if ($response['status'] === 'archived') {
            $this->unarchiveMember($member);
        }
        return $this;
    }

    public function setMemberTags(Member $member): static
    {
        $hash = MailChimp::subscriberHash($member->Email);
        $log = MailchimpLog::start_log($member, null, __FUNCTION__);
        $log->endLog(
            $this->MailchimpApiObject()
                ->post(
                    "lists/" . self::get_mailchimp_list_id() . "/members/" . $hash . "/tags",
                    [
                        'tags' => $member->getMailchimpGroupCodes(),
                    ]
                )
        );
        return $this;
    }

    public function deleteMember(Member $member): static
    {
        $hash = MailChimp::subscriberHash($member->Email);
        $log = MailchimpLog::start_log($member, null, __FUNCTION__);
        $log->endLog(
            $this->mailchimp
                ->delete('lists/' . self::get_mailchimp_list_id() . "/members/" . $hash)
        );

        return $this;
    }

    public function bulkAddOrUpdateMembers(DataList $members, ?Group $group = null): static
    {
        $batch = $this->mailchimp->new_batch();
        $log = MailchimpLog::start_log(null, $group, __FUNCTION__);
        foreach ($members as $member) {
            $hash = MailChimp::subscriberHash($member->Email);
            $batch->put(
                strval($member->ID),
                "lists/" . self::get_mailchimp_list_id() . "/members/" . $hash,
                [
                    'email_address' => $member->Email,
                    'status_if_new' => 'pending',
                    'merge_fields' => ['FNAME' => $member->FirstName, 'LNAME' => $member->Surname],
                ]
            );
            $batch->post(
                strval($member->ID) . '-tags',
                "lists/" . self::get_mailchimp_list_id() . "/members/" . $hash . "/tags",
                [
                    'tags' => $member->getMailchimpGroupCodes(),
                ]
            );
        }
        $result = $batch->execute();
        $log->endLog($result);
        return $this;
    }

    public function unarchiveMember(Member $member): static
    {
        $hash = MailChimp::subscriberHash($member->Email);
        $log = MailchimpLog::start_log($member, null, __FUNCTION__);
        $log->endLog(
            $this->mailchimp
                ->patch(
                    "lists/" . self::get_mailchimp_list_id() . "/members/" . $hash,
                    [
                        'status' => 'subscribed',
                    ]
                )
        );
        return $this;
    }

    public function addOrUpdateGroup(Group $group): MailchimpSyncInterface
    {
        // Tag will automatically be created if it does not exist
        $this->bulkAddOrUpdateMembers($group->Members()->filter('IncludeInMailChimp', true), $group);
        return $this;
    }

    public function deleteGroup(Group $group): MailchimpSyncInterface
    {
        $id = $this->getGroupTagID($group);
        if ($id) {
            $log = MailchimpLog::start_log(null, $group, __FUNCTION__);
            $log->endLog(
                $this->mailchimp
                    ->delete("lists/" . self::get_mailchimp_list_id() . "/segments/" . $id)
            );
        }
        return $this;
    }

    public function getAllCurrentGroupTagNames(): array
    {
        return Group::get()->filter('IncludeInMailChimp', true)->column('Code');
    }

    public function getGroupTagID(Group $group): ?int
    {
        $log = MailchimpLog::start_log(null, $group, __FUNCTION__);

        $response = $this->mailchimp->get(
            'lists/' . self::get_mailchimp_list_id() . '/tag-search',
            ['name' => $group->Code]
        );

        $log->endLog($response);
        if (! isset($response['tags'][0]['id'])) {
            return null;
        }
        $id = $response['tags'][0]['id'];
        return $id;
    }
}
