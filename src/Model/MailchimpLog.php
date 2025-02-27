<?php

namespace Sunnysideup\MailchimpSyncGroupAndMembers\Model;

use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;

class MailchimpLog extends DataObject
{
    private static $table_name = 'MailchimpLog';

    private static $db = [
        'Request' => 'Text',
        'Response' => 'Text',
    ];

    private static $has_one = [
        'Member' => Member::class,
        'Group' => Group::class,
    ];

    private static $casting = [
        'Title' => 'Varchar(255)',
    ];

    private static $summary_fields = [
        'Created' => 'Created',
        'Member.Email' => 'Member Email',
        'Group.Title' => 'Group',
        'Request' => 'Request',
    ];

    private static $default_sort = 'ID DESC';

    public static function start_log(?Member $member, ?Group $group, string $request)
    {
        $log = new MailchimpLog();
        $log->MemberID = $member?->ID;
        $log->GroupID = $group?->ID;
        $log->Request = $request;
        $log->write();
        return $log;
    }

    public function endLog(string|array $response)
    {
        if (is_array($response)) {
            $response = json_encode($response);
        }
        $this->Response = $response;
        $this->write();
    }

    public function getTitle()
    {
        return implode(' - ', [
            $this->Created,
            $this->Member()->Email,
            $this->Group()->Title,
        ]);
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->replaceField(
            'Response',
            LiteralField::create('Response', '<pre>' . print_r(json_decode($this->Response, true), 1) . '</pre>')
        );
        return $fields;
    }

    public function canCreate($member = null, $context = [])
    {
        return false;
    }

    public function canEdit($member = null)
    {
        return false;
    }

    public function canDelete($member = null)
    {
        return false;
    }
}
