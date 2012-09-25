<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Service
 */

namespace ZendServiceTest\Audioscrobbler;

use ZendService\Audioscrobbler;

/**
 * @category   Zend
 * @package    Zend_Service_Audioscrobbler
 * @subpackage UnitTests
 * @group      Zend_Service
 * @group      Zend_Service_Audioscrobbler
 */
class AudioscrobblerTest extends AudioscrobblerTestCase
{
    public function testRequestThrowsRuntimeExceptionWithNoUserError()
    {
        $this->setExpectedException('ZendService\Audioscrobbler\Exception\RuntimeException', 'No user exists with this name');

        $this->setAudioscrobblerResponse(self::readTestResponse('errorNoUserExists'));
        $as = $this->getAudioscrobblerService();
        $as->set('user', 'foobarfoo');

        $response = $as->userGetProfileInformation();
    }

    public function testRequestThrowsRuntimeExceptionWithoutSuccessfulResponse()
    {
        $this->setExpectedException('ZendService\Audioscrobbler\Exception\RuntimeException', '404');

        $this->setAudioscrobblerResponse(self::readTestResponse('errorResponseStatusError'));
        $as = $this->getAudioscrobblerService();
        $as->set('user', 'foobarfoo');

        $response = $as->userGetProfileInformation();
    }

    /**
     * @group ZF-4509
     */
    public function testSetViaCallIntercept()
    {
        $as = new Audioscrobbler\Audioscrobbler();
        $as->setUser("foobar");
        $as->setAlbum("Baz");
        $this->assertEquals("foobar", $as->get("user"));
        $this->assertEquals("Baz",    $as->get("album"));
    }

    /**
     * @group ZF-6251
     */
    public function testUnknownMethodViaCallInterceptThrowsException()
    {
        $this->setExpectedException("ZendService\Audioscrobbler\Exception\BadMethodCallException", 'does not exist in class');

        $as = new Audioscrobbler\Audioscrobbler();
        $as->someInvalidMethod();
    }

    /**
     * @group ZF-6251
     */
    public function testCallInterceptMethodsRequireExactlyOneParameterAndThrowExceptionOtherwise()
    {
        $this->setExpectedException("ZendService\Audioscrobbler\Exception\InvalidArgumentException", 'A value is required for setting a parameter field');

        $as = new Audioscrobbler\Audioscrobbler();
        $as->setUser();
    }

    public static function readTestResponse($file)
    {
        return file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . $file);
    }

    /**
     * There is a bug that if a url contains the & in the link you end up with
     *
     * "simplexml_load_string(): Entity: line 297: parser error : xmlParseEntityRef: no name"
     *
     * Replacing the & with &amp; seems to fix this error.
     */
    public function testGetInfoCanHandleAmpersandsInUrlLinks()
    {
        $test_response = "HTTP/1.1 200 OK\r\nContent-type: text/xml\r\n\r\n" .
            '<?xml version="1.0" encoding="UTF-8"?>
            <toptracks user="benmatselby" type="overall">
                <track>
                    <artist mbid="cd71e6e9-42bb-4a1a-b5ce-17f41682b3e2">Sam Sparro</artist>
                    <name>Black &amp; Gold</name>
                    <mbid>1496da59-7c53-49ec-b2ee-7f9c04fb699d</mbid>
                    <playcount>54</playcount>
                    <rank>33</rank>
                    <url>http://www.last.fm/music/Sam+Sparro/_/Black+&+Gold</url>
                </track>
            </toptracks>';

        $this->setAudioscrobblerResponse($test_response);

        $as = $this->getAudioscrobblerService();
        $as->set('user', 'benmatselby');
        $response = $as->userGetTopTracks();
        $track = $response->track[0];

        $this->assertEquals((string)$track->url, 'http://www.last.fm/music/Sam+Sparro/_/Black+&+Gold');
    }
}
