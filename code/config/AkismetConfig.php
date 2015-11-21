<?php

/**
 * Extends {@see SiteConfig} to allow akismet key to be set via the CMS
 */
class AkismetConfig extends DataExtension
{
    private static $db = array(
        'AkismetKey' => 'Varchar'
    );

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Akismet', new PasswordField('AkismetKey', 'Akismet Key'));
    }
}
