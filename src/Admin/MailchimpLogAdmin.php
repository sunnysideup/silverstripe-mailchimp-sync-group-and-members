<?php

namespace Sunnysideup\MailchimpSyncGroupAndMembers\Admin;

use SilverStripe\Admin\ModelAdmin;
use Sunnysideup\MailchimpSyncGroupAndMembers\Model\MailchimpLog;

class MailchimpLogAdmin extends ModelAdmin
{
    private static $managed_models = [
        MailchimpLog::class,
    ];

    private static $menu_title = 'Mailchimp Logs';

    private static $url_segment = 'mailchimp-log-admin';
}
