<?php

/**
 * Spam protector for Akismet
 *
 * @author Damian Mooyman
 * @package akismet
 */
class AkismetSpamProtector implements SpamProtector {
	
	/**
	 * Cached API object
	 *
	 * @var TijsVerkoyen\Akismet\Akismet
	 */
	protected static $_api = null;
	
	/**
	 * Overridden API key
	 *
	 * @var string
	 */
	protected static $_api_key = null;
	
	/**
	 * Set the API key
	 * 
	 * @param string $key
	 */
	public static function set_api_key($key) {
		self::$_api_key = $key;
	}
	
	/**
	 * Get the API key
	 * 
	 * @return string
	 */
	protected static function get_api_key() {
		if(self::$_api_key) return self::$_api_key;
		
		// Check config
		$key = Config::inst()->get('AkismetSpamProtector', 'api_key');
		if(!empty($key)) return $key;
		
		// Check environment
		if(defined('SS_AKISMET_API_KEY')) return SS_AKISMET_API_KEY;
	}
	
	/**
	 * Retrieves Akismet API object singleton
	 * 
	 * @return TijsVerkoyen\Akismet\Akismet
	 */
	public static function api() {
		if(self::$_api) return self::$_api;
		
		// Get API key and URL
		$key = self::get_api_key();
		if(empty($key)) throw new Exception("AkismetSpamProtector is incorrectly configured. Please specify an API key.");
		$url = Director::protocolAndHost();
		
		// Generate API object
		return self::$_api = new TijsVerkoyen\Akismet\Akismet($key, $url);
	}
	
	public function getFormField($name = null, $title = null, $value = null, $form = null, $rightTitle = null) {
		return AkismetField::create($name, $title, $value, $form, $rightTitle);
	}

	public function sendFeedback($object = null, $feedback = "") {
		return true;
	}	
}
