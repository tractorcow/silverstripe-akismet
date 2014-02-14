<?php

/**
 * Form field to handle akismet error display and handling
 *
 * @author Damian Mooyman
 * @package akismet
 */
class AkismetField extends SpamProtectorField {
	
	/**
	 * Get the nested confirmation checkbox field
	 * 
	 * @return CheckboxField
	 */
	protected function confirmationField() {
		// Check if confirmation is required
		$requireConfirmation = Config::inst()->get('AkismetSpamProtector', 'require_confirmation');
		if(empty($requireConfirmation)) return null;
		
		// If confirmation is required then return a checkbox
		return CheckboxField::create(
			$this->getName(),
			_t('AkismetField.NOTIFICATION', 'I understand that, and give consent to, having this content submitted to
				a third party for automated spam detection')
		)
			->setError($this->Message(), $this->MessageType())
			->setForm($this->getForm());
	}
	
	public function Field($properties = array()) {
		$checkbox = $this->confirmationField();
		if($checkbox) return $checkbox->Field($properties);
	}
	
	function FieldHolder($properties = array()) {
		$checkbox = $this->confirmationField();
		if($checkbox) return $checkbox->FieldHolder($properties);
	}
	
	/**
	 * Determines the field value submitted
	 * 
	 * @param array $mapping Mapping of field descriptor to the form field name
	 * @param string $field Field descriptor to extract
	 * @return string Resulting value
	 */
	protected function submittedValue($mapping, $field) {
		if(isset($mapping[$field])) $field = $mapping[$field];
		return isset($_REQUEST[$field])
			? $_REQUEST[$field]
			: "";
	}
	
	/**
	 * This function first gets values from mapped fields and then check these values against
	 * Mollom web service and then notify callback object with the spam checking result. 
	 * @return 	boolean		- true when Mollom confirms that the submission is ham (not spam)
	 *						- false when Mollom confirms that the submission is spam 
	 * 						- false when Mollom say 'unsure'. 
	 *						  In this case, 'mollom_captcha_requested' session is set to true 
	 *       				  so that Field() knows it's time to display captcha 			
	 */
	public function validate($validator) {
		
		// Check that, if necessary, the user has given permission to check for spam
		$requireConfirmation = Config::inst()->get('AkismetSpamProtector', 'require_confirmation');
		if($requireConfirmation && !$this->Value()) {
			$validator->validationError(
				$this->name,
				_t(
					'AkismetField.NOTIFICATIONREQUIRED', 
					'You must give consent to submit this content to spam detection'
				),
				"error"
			);
			return false;
		}
		
		// Check bypass permission
		$permission = Config::inst()->get('AkismetSpamProtector', 'bypass_permission');
		if($permission && Permission::check($permission)) return true;
		
		// if the user has logged and there's no force check on member
		$bypassMember = Config::inst()->get('AkismetSpamProtector', 'bypass_members');
		if($bypassMember && Member::currentUser()) return true;
		
		// Map input fields to spam fields
		$mapping = array_flip($this->getFieldMapping());
		$content = $this->submittedValue($mapping, 'post_body');
		$author = $this->submittedValue($mapping, 'author_name');
		$email = $this->submittedValue($mapping, 'author_mail');
		$url = $this->submittedValue($mapping, 'author_url');
		
		// Check result
		$isSpam = AkismetSpamProtector::api()->isSpam($content, $author, $email, $url);
		if(!$isSpam) return true;
	
		// Mark as spam
		$validator->validationError(
			$this->name,
			_t(
				'AkismetField.SPAM', 
				"Your submission has been rejected because it was treated as spam."
			),
			"error"
		);
		return false;
	}
}
