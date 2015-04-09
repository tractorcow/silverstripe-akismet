<?php

/**
 * Allows akismet to be configured via siteconfig instead of hard-coded configuration
 */
class AkismetProcessor implements RequestFilter {
	
	public function postRequest(SS_HTTPRequest $request, SS_HTTPResponse $response, DataModel $model) {

	}

	public function preRequest(SS_HTTPRequest $request, Session $session, DataModel $model) {
		// Skip if database isn't ready
		if(!DB::isActive() || !DB::getConn()->hasField('SiteConfig', 'AkismetKey')) return;

		// Skip if SiteConfig doesn't have this extension
		if(!SiteConfig::has_extension('AkismetConfig')) return;

		// Check if key exists
		$akismetKey = SiteConfig::current_site_config()->AkismetKey;
		if($akismetKey) {
			AkismetSpamProtector::set_api_key($akismetKey);
		}
	}

}
