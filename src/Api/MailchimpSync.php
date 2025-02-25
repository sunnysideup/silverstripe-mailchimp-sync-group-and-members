<?php

namespace Sunnysideup\MailchimpSyncGroupAndMembers\Api;

use SilverStripe\Core\Environment;

/**
 * Class \Sunnysideup\MailchimpSyncGroupAndMembers\Extensions\GroupExtension
 *
 * @property Group|GroupExtension $owner
 */
class MailchimpSync
{
    public static function get_mailchimp_api_key()
    {
        return Environment::getEnv('SS_MAILCHIMP_API_KEY');
    }
}
