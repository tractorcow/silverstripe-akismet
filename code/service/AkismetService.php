<?php

/**
 * Describes TijsVerkoyen\Akismet\Akismet
 */
interface AkismetService
{
    /**
     * Check if the comment is spam or not
     * This is basically the core of everything. This call takes a number of
     * arguments and characteristics about the submitted content and then
     * returns a thumbs up or thumbs down.
     * Almost everything is optional, but performance can drop dramatically if
     * you exclude certain elements.
     * REMARK: If you are having trouble triggering you can send
     * "viagra-test-123" as the author and it will trigger a true response,
     * always.
     *
     * @param string[optional] $content   The content that was submitted.
     * @param string[optional] $author    The name.
     * @param string[optional] $email     The email address.
     * @param string[optional] $url       The URL.
     * @param string[optional] $permalink The permanent location of the entry
     *                                    the comment was submitted to.
     * @param string[optional] $type The type, can be blank, comment,
     *                                    trackback, pingback, or a made up
     *                                    value like "registration".
     * @return bool If the comment is spam true will be
     *                                    returned, otherwise false.
     */
    public function isSpam(
        $content,
        $author = null,
        $email = null,
        $url = null,
        $permalink = null,
        $type = null
    );
}
