# Akismet Silverstripe Module

Simple spam filter for Silverstripe using Akismet

Also, please [report any issues](https://github.com/tractorcow/silverstripe-akismet/issues)
you may encounter, as it helps us all out!

## Credits and Authors

 * Damian Mooyman - <https://github.com/tractorcow/silverstripe-akismet>
 * Attribution to Tijs Verkoyen for his Akismet API wrapper - <https://github.com/tijsverkoyen/Akismet>

## Requirements

 * SilverStripe 3.1
 * Silverstripe SpamProtection module - <https://github.com/silverstripe/silverstripe-spamprotection>
 * Tijs Verkoyen's Akismet API wrapper - <https://github.com/tijsverkoyen/Akismet>
 * PHP 5.3

## Installation Instructions

This module can be easily installed on any already-developed website

 * Either extract the module into the `akismet` folder, or install using composer

```bash
# Dependencies
composer require tijsverkoyen/akismet "1.1.0"
composer require silverstripe/spamprotection "dev-master"
# Akismet Module
composer require tractorcow/silverstripe-akismet "3.1.*@dev"
```

 * Configure your environment to set 'AkismetSpamProtector' as the protector class, and get an API key from
[akismet.com](http://akismet.com/) and set in the site against one of the following ways.

config.yml:

```yml
---
Name: myspamprotection
---
FormSpamProtectionExtension:
  default_spam_protector: AkismetSpamProtector
AkismetSpamProtector:
  api_key: 5555dddd55d5d
```

_config.php:

```php
Config::inst()->update('FormSpamProtectorExtension', 'default_spam_protector', 'AkismetSpamProtector');
AkismetSpamProtector::set_api_key('5555dddd55d5d');
```

_ss_environment.php:

```
define('SS_AKISMET_API_KEY', '5555dddd55d5d');
// and set AkismetSpamProtector as your spam protector using one of the above methods
```

## Testing

By default, spam protection is disabled for users with ADMIN priviliges. There is also an option to disable
spam protection for all logged in users. In order to disable this for testing purposes, you can temporarily
modify these options in your development environment as below:

```php
if(!Director::isLive()) {
	Config::inst()->remove('AkismetSpamProtector', 'bypass_permission');
	Config::inst()->remove('AkismetSpamProtector', 'bypass_members');
}
```

In order to check that your form is blocking spam correctly, you can always set 'viagra-test-123' as 
the author and Akismet will always mark this as spam.

## Comments

If you're using Comments module you can quickly set akismet to filter these out by adding the `CommentSpamProtection`
extension to the `CommentingController`

config.yml

```yml
CommentingController:
  extensions:
    - CommentSpamProtection
```

_config.php

```php
CommentingController::add_extension('CommentSpamProtection');
```

## Important notes for those in the EU

Because of the way Akismet works (message, author, and other information sent to a third party) in some countries
it's legally necessary to notify and gain the user's permission prior to verification.

To create a checkbox style authorisation prompt for this field set the following configuration option:

config.yml

```yml
AkismetSpamProtector:
  require_confirmation: true
```

_config.php

```php
Config::inst()->update('AkismetSpamProtector', 'require_confirmation', true);
```

## License

Revised BSD License

Copyright (c) 2013, Damian Mooyman
All rights reserved.

All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

 * Redistributions of source code must retain the above copyright
   notice, this list of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright
   notice, this list of conditions and the following disclaimer in the
   documentation and/or other materials provided with the distribution.
 * The name of Damian Mooyman may not be used to endorse or promote products
   derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
