<?php

/**
 * Spam protector for Akismet
 *
 * @author Damian Mooyman
 * @package akismet
 */
class AkismetSpamProtector implements SpamProtector {

	/**
	 * Set this to your API key
	 * 
	 * @var string
	 * @config
	 */
	private static $api_key = null;

	/**
	 * Permission required to bypass check
	 *
	 * @var string
	 * @config
	 */
	private static $bypass_permission = 'ADMIN';

	/**
	 * Set to try to bypass check for all logged in users
	 *
	 * @var boolean
	 * @config
	 */
	private static $bypass_members = false;

	/**
	 * IMPORTANT: If you are operating in a country (such as Germany) that has content transmission disclosure
	 * requirements, set this to true in order to require a user prompt prior to submission of user data
	 * to the Akismet servers
	 *
	 * @var boolean
	 * @config
	 */
	private static $require_confirmation = false;

	/**
	 * Set to true to disable spam errors, instead saving this field to the dataobject with the spam
	 * detection as a flag. This will disable validation errors when spam is encountered.
	 * The flag will be saved to the same field specified by the 'name' option in enableSpamProtection()
	 *
	 * @var boolean
	 * @config
	 */
	private static $save_spam = false;
	
	/**
	 * @var array
	 */
	private $fieldMapping = array();
	
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
	 * @return AkismetService
	 */
	public static function api() {
		// Get API key and URL
		$key = self::get_api_key();
		if(empty($key)) {
			throw new Exception("AkismetSpamProtector is incorrectly configured. Please specify an API key.");
		}
		$url = Director::protocolAndHost();
		
		// Generate API object
		return Injector::inst()->get('AkismetService', true, array($key, $url));
	}
	
	public function getFormField($name = null, $title = null, $value = null, $form = null, $rightTitle = null) {
		return AkismetField::create($name, $title, $value, $form, $rightTitle)
			->setFieldMapping($this->fieldMapping);
	}

	public function setFieldMapping($fieldMapping) {
		$this->fieldMapping = $fieldMapping;
	}

}
