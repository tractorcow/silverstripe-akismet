<?php

/**
 * Allows akismet to be configured via siteconfig instead of hard-coded configuration
 */
class AkismetProcessor implements RequestFilter
{
    public function postRequest(SS_HTTPRequest $request, SS_HTTPResponse $response, DataModel $model)
    {
    }

    public function preRequest(SS_HTTPRequest $request, Session $session, DataModel $model)
    {
        // Skip if database isn't ready
        if (!$this->isDBReady()) {
            return;
        }

        // Skip if SiteConfig doesn't have this extension
        if (!SiteConfig::has_extension('AkismetConfig')) {
            return;
        }

        // Check if key exists
        $akismetKey = SiteConfig::current_site_config()->AkismetKey;
        if ($akismetKey) {
            AkismetSpamProtector::set_api_key($akismetKey);
        }
    }

    /**
     * Make sure the DB is ready before accessing siteconfig db field
     *
     * @return bool
     */
    protected function isDBReady()
    {
        if (!DB::isActive()) {
            return false;
        }

        // Require table
        if (!DB::getConn()->hasTable('SiteConfig')) {
            return false;
        }

        // Ensure siteconfig has all fields necessary
        $dbFields = DB::fieldList('SiteConfig');
        if (empty($dbFields)) {
            return false;
        }

        // Ensure that SiteConfig has all fields
        $objFields = DataObject::database_fields('SiteConfig', false);
        $missingFields = array_diff_key($objFields, $dbFields);
        return empty($missingFields);
    }
}
