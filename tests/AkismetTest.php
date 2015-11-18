<?php

class AkismetTest extends FunctionalTest {

	protected $extraDataObjects = array('AkismetTest_Submission');

	protected $usesDatabase = true;

	protected $requiredExtensions = array(
		'SiteConfig' => array('AkismetConfig')
	);

	public function setUp() {
		parent::setUp();
		Injector::nest();
		Injector::inst()->unregisterAllObjects();

		// Mock service
		Config::nest();
		Config::inst()->update('Injector', 'AkismetService', 'AkismetTest_Service');
		Config::inst()->update('AkismetSpamProtector', 'api_key', 'dummykey');
		AkismetSpamProtector::set_api_key(null);

		// Reset options to reasonable default
		Config::inst()->remove('AkismetSpamProtector', 'save_spam');
		Config::inst()->remove('AkismetSpamProtector', 'require_confirmation');
		Config::inst()->remove('AkismetSpamProtector', 'bypass_members');
		Config::inst()->update('AkismetSpamProtector', 'bypass_permission', 'ADMIN');
	}

	public function tearDown() {
		Config::unnest();
		Injector::unnest();
		parent::tearDown();
	}

	public function testSpamDetectionForm() {
		
		// Test "nice" setting
		$result = $this->post('AkismetTest_Controller/Form', array(
			'Name' => 'person',
			'Email' => 'person@domain.com',
			'Content' => 'what a nice comment',
			'action_doSubmit' => 'Submit',
		));

		$this->assertContains('Thanks for your submission, person', $result->getBody());
		$saved = AkismetTest_Submission::get()->last();
		$this->assertNotEmpty($saved);
		$this->assertEquals('person', $saved->Name);
		$this->assertEquals('person@domain.com', $saved->Email);
		$this->assertEquals('what a nice comment', $saved->Content);
		$this->assertEquals(false, (bool)$saved->IsSpam);
		$saved->delete();

		// Test failed setting
		$result = $this->post('AkismetTest_Controller/Form', array(
			'Name' => 'spam',
			'Email' => 'spam@spam.com',
			'Content' => 'spam',
			'action_doSubmit' => 'Submit',
		));

		$errorMessage = _t(
			'AkismetField.SPAM',
			"Your submission has been rejected because it was treated as spam."
		);
		$this->assertContains($errorMessage, $result->getBody());
		$saved = AkismetTest_Submission::get()->last();
		$this->assertEmpty($saved);
	}

	public function testSaveSpam() {
		Config::inst()->update('AkismetSpamProtector', 'save_spam', 'true');

		// Test "nice" setting
		$result = $this->post('AkismetTest_Controller/Form', array(
			'Name' => 'person',
			'Email' => 'person@domain.com',
			'Content' => 'what a nice comment',
			'action_doSubmit' => 'Submit',
		));

		$this->assertContains('Thanks for your submission, person', $result->getBody());
		$saved = AkismetTest_Submission::get()->last();
		$this->assertNotEmpty($saved);
		$this->assertEquals('person', $saved->Name);
		$this->assertEquals('person@domain.com', $saved->Email);
		$this->assertEquals('what a nice comment', $saved->Content);
		$this->assertEquals(false, (bool)$saved->IsSpam);
		$saved->delete();

		// Test failed setting
		$result = $this->post('AkismetTest_Controller/Form', array(
			'Name' => 'spam',
			'Email' => 'spam@spam.com',
			'Content' => 'spam',
			'action_doSubmit' => 'Submit',
		));

		$errorMessage = _t(
			'AkismetField.SPAM',
			"Your submission has been rejected because it was treated as spam."
		);
		$this->assertContains($errorMessage, $result->getBody());
		$saved = AkismetTest_Submission::get()->last();
		$this->assertNotEmpty($saved);
		$this->assertEquals('spam', $saved->Name);
		$this->assertEquals('spam@spam.com', $saved->Email);
		$this->assertEquals('spam', $saved->Content);
		$this->assertEquals(true, (bool)$saved->IsSpam);
	}

	/**
	 * Test that the request processor can safely activate when able (and only then)
	 */
	public function testProcessor() {
		$siteconfig = SiteConfig::current_site_config();
		$siteconfig->write();

		// Test assignment via request filter
		$processor = new AkismetTest_TestProcessor();
		$this->assertTrue($processor->publicIsDBReady());

		// Remove AkismetKey field
		DB::query('ALTER TABLE "SiteConfig" DROP COLUMN "AkismetKey"');
		$this->assertFalse($processor->publicIsDBReady());
	}

}

class AkismetTest_Submission extends DataObject implements TestOnly {

	private static $db = array(
		'Name' => 'Varchar',
		'Email' => 'Varchar',
		'Content' => 'Text',
		'IsSpam' => 'Boolean',
	);

	private static $default_sort = 'ID';
}

class AkismetTest_Controller extends Controller implements TestOnly {
	
	private static $allowed_actions = array(
		'Form'
	);

	public function Form() {
		$fields = new FieldList(
			new TextField('Name'),
			new EmailField('Email'),
			new TextareaField('Content')
		);
		$actions = new FieldList(new FormAction('doSubmit', 'Submit'));
		$validator = new RequiredFields('Name', 'Content');
		$form = new Form($this, 'Form', $fields, $actions, $validator);

		$form->enableSpamProtection(array(
			'protector' => 'AkismetSpamProtector',
			'name' => 'IsSpam',
			'mapping' => array(
				'Content' => 'body',
				'Name' => 'authorName',
				'Email' => 'authorMail',
			)
		));

		// Because we don't want to be testing this
		$form->disableSecurityToken();
		return $form;
	}

	public function doSubmit($data, Form $form) {
		$item = new AkismetTest_Submission();
		$form->saveInto($item);
		$item->write();
		$form->sessionMessage('Thanks for your submission, '. $data['Name'], 'good');
		return $this->redirect($this->Link());
	}
}

class AkismetTest_Service implements TestOnly, AkismetService {

	public function __construct($apiKey, $url) {
		if($apiKey !== 'dummykey') {
			throw new Exception("Invalid key");
		}
	}
	
	public function isSpam($content, $author = null, $email = null, $url = null, $permalink = null, $type = null) {
		// This dummy service only checks the content
		return $content === 'spam';
	}
}

class AkismetTest_TestProcessor extends AkismetProcessor implements TestOnly {
	public function publicIsDBReady() {
		return $this->isDBReady();
	}
}