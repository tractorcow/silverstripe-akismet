<?php

/**
 * Form field to handle akismet error display and handling
 *
 * @author Damian Mooyman
 * @package akismet
 */
class AkismetField extends FormField
{
    /**
     * @var array
     */
    private $fieldMapping = array();

    /**
     *
     * @var boolean
     */
    protected $isSpam = null;
    
    /**
     * Get the nested confirmation checkbox field
     * 
     * @return CheckboxField
     */
    protected function confirmationField()
    {
        // Check if confirmation is required
        $requireConfirmation = Config::inst()->get('AkismetSpamProtector', 'require_confirmation');
        if (empty($requireConfirmation)) {
            return null;
        }
        
        // If confirmation is required then return a checkbox
        return CheckboxField::create(
            $this->getName(),
            _t('AkismetField.NOTIFICATION', 'I understand that, and give consent to, having this content submitted to '
                . 'a third party for automated spam detection')
        )
            ->setError($this->Message(), $this->MessageType())
            ->setForm($this->getForm());
    }
    
    public function Field($properties = array())
    {
        $checkbox = $this->confirmationField();
        if ($checkbox) {
            return $checkbox->Field($properties);
        }
    }
    
    public function FieldHolder($properties = array())
    {
        $checkbox = $this->confirmationField();
        if ($checkbox) {
            return $checkbox->FieldHolder($properties);
        }
    }
    
    /**
     * @return array
     */
    public function getSpamMappedData()
    {
        if (empty($this->fieldMapping)) {
            return null;
        }
        
        $result = array();
        $data = $this->form->getData();

        foreach ($this->fieldMapping as $fieldName => $mappedName) {
            $result[$mappedName] = (isset($data[$fieldName])) ? $data[$fieldName] : null;
        }

        return $result;
    }
    
    /**
     * This function first gets values from mapped fields and then check these values against
     * Mollom web service and then notify callback object with the spam checking result.
     * @param Validator $validator
     * @return 	boolean		- true when Mollom confirms that the submission is ham (not spam)
     *						- false when Mollom confirms that the submission is spam 
     * 						- false when Mollom say 'unsure'. 
     *						  In this case, 'mollom_captcha_requested' session is set to true 
     *       				  so that Field() knows it's time to display captcha 			
     */
    public function validate($validator)
    {
        
        // Check that, if necessary, the user has given permission to check for spam
        $requireConfirmation = Config::inst()->get('AkismetSpamProtector', 'require_confirmation');
        if ($requireConfirmation && !$this->Value()) {
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
        
        // Check result
        $isSpam = $this->getIsSpam();
        if (!$isSpam) {
            return true;
        }

        // Save error message
        $errorMessage = _t(
            'AkismetField.SPAM',
            "Your submission has been rejected because it was treated as spam."
        );

        // If spam should be allowed, let it pass and save it for later
        if (Config::inst()->get('AkismetSpamProtector', 'save_spam')) {
            // In order to save spam but still display the spam message, we must mock a form message
            // without failing the validation
            $errors = array(array(
                'fieldName' => $this->name,
                'message' => $errorMessage,
                'messageType' => 'error',
            ));
            $formName = $this->getForm()->FormName();
            Session::set("FormInfo.{$formName}.errors", $errors);
            return true;
        } else {
            // Mark as spam
            $validator->validationError($this->name, $errorMessage, "error");
            return false;
        }
    }

    /**
     * Determine if this field is spam or not
     *
     * @return boolean
     */
    public function getIsSpam()
    {
        // Prevent multiple API calls
        if ($this->isSpam !== null) {
            return $this->isSpam;
        }

        // Check bypass permission
        $permission = Config::inst()->get('AkismetSpamProtector', 'bypass_permission');
        if ($permission && Permission::check($permission)) {
            return false;
        }

        // if the user has logged and there's no force check on member
        $bypassMember = Config::inst()->get('AkismetSpamProtector', 'bypass_members');
        if ($bypassMember && Member::currentUser()) {
            return false;
        }

        // Map input fields to spam fields
        $mappedData = $this->getSpamMappedData();
        $content = isset($mappedData['body']) ? $mappedData['body'] : null;
        $author = isset($mappedData['authorName']) ? $mappedData['authorName'] : null;
        $email = isset($mappedData['authorMail']) ? $mappedData['authorMail'] : null;
        $url = isset($mappedData['authorUrl']) ? $mappedData['authorUrl'] : null;

        // Check result
        $api = AkismetSpamProtector::api();
        $this->isSpam = $api && $api->isSpam($content, $author, $email, $url);
        return $this->isSpam;
    }
    
    /**
     * Get the fields to map spam protection too
     *
     * @return array Associative array of Field Names, where the indexes of the array are
     * the field names of the form and the values are the standard spamprotection
     * fields used by the protector
     */
    public function getFieldMapping()
    {
        return $this->fieldMapping;
    }

    /**
     * Set the fields to map spam protection too
     *
     * @param array $fieldMapping array of Field Names, where the indexes of the array are
     * the field names of the form and the values are the standard spamprotection
     * fields used by the protector
     * @return self
     */
    public function setFieldMapping($fieldMapping)
    {
        $this->fieldMapping = $fieldMapping;
        return $this;
    }

    /**
     * Allow spam flag to be saved to the underlying data record
     *
     * @param \DataObjectInterface $record
     */
    public function saveInto(\DataObjectInterface $record)
    {
        if (Config::inst()->get('AkismetSpamProtector', 'save_spam')) {
            $dataValue = $this->getIsSpam() ? 1 : 0;
            $record->setCastedField($this->name, $dataValue);
        }
    }
}
