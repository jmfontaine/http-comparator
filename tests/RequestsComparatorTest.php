<?php
/**
 * @package HTTP Comparator
 * @author Jean-Marc Fontaine <jm@jmfontaine.net>
 * @copyright 2014 Jean-Marc Fontaine <jm@jmfontaine.net>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

namespace HttpComparator\Tests;

use Buzz\Message\Request as BuzzRequest;
use Guzzle\Http\Message\RequestFactory;
use HttpComparator\RequestsComparator;
use Zend\Http\Request as ZendRequest;

class RequestsComparatorTest  extends \PHPUnit_Framework_TestCase
{
    private $requestFactory;

    /*
     * ============================================================================================================
     * Helper methods
     * ============================================================================================================
     */

    protected function getRequestFactory()
    {
        if (null === $this->requestFactory) {
            $this->requestFactory = new RequestFactory();
        }

        return $this->requestFactory;
    }

    /**
     * @param array|null  $options Options
     *
     * @return Guzzle\Http\Message\Request|Guzzle\Http\Message\EntityEnclosingRequest
     */
    protected function createRequest(array $options = array())
    {
        $defaultOptions = array(
            'body'            => 'Example body',
            'headers'         => array('User-Agent' => 'HttpComparator'),
            'method'          => 'GET',
            'password'        => null,
            'port'            => null,
            'protocolVersion' => '1.1',
            'queryString'     => null,
            'url'             => 'http://www.example.com/',
            'username'        => null,
        );
        $options = array_merge($defaultOptions, $options);

        return $this->getRequestFactory()->create(
            $options['method'],
            $options['url'],
            $options['headers'],
            $options['body']
        )->setProtocolVersion($options['protocolVersion']);
    }

    protected function loadRequest($name)
    {
        $path = __DIR__ . '/_files/requests/' . $name . '.txt';

        if (!file_exists($path)) {
            throw new \InvalidArgumentException('Invalid request name: ' . $name);
        }

        $content = file_get_contents($path);
        if (false === $content) {
            throw new \InvalidArgumentException('Could not load request: ' . $name);
        }

        return $content;
    }

    protected function getMethods()
    {
        return array('CONNECT', 'DELETE', 'GET', 'HEAD', 'OPTIONS', 'PATCH', 'POST', 'PUT', 'TRACE');
    }

    /*
     * ============================================================================================================
     * Data providers
     * ============================================================================================================
     */

    public function provideIdenticalRequests()
    {
        $data = array();

        foreach ($this->getMethods() as $method) {
            $request = $this->createRequest(array('method' => $method));
            $data[] = array($request, $request);
        }

        return $data;
    }

    public function provideIdenticalGetRequestsInDifferentFormats()
    {
        $data = array();

        $textRequest   = $this->loadRequest('example.com');
        $guzzleRequest = $this->getRequestFactory()->fromMessage($textRequest);

        $buzzRequest = new BuzzRequest(
            $guzzleRequest->getMethod(),
            $guzzleRequest->getPath()
        );
        $buzzRequest->setHeaders($guzzleRequest->getHeaderLines());

        $zendRequest = ZendRequest::fromString($textRequest);

        $requests = array(
            $buzzRequest,
            $guzzleRequest,
            $textRequest,
            $zendRequest,
        );

        foreach ($requests as $request1) {
            foreach ($requests as $request2) {
                // Don't compare a request with itself
                if ($request1 !== $request2) {
                    continue;
                }

                $data[] = array($request1, $request2);
            }
        }

        return $data;
    }

    public function provideInvalidObjects()
    {
        return array(
            array(new \StdClass(), new \StdClass()),
        );
    }

    public function provideInvalidTypes()
    {
        return array(
            array(1234, 1234),
            array(1.234, 1.234),
        );
    }

    public function provideUnmatchingHosts()
    {
        $request1 = $this->createRequest();

        $request2 = clone $request1;
        $request2->setHost('example.org');

        return array(array($request1, $request2));
    }

    public function provideUnmatchingPorts()
    {
        $request1 = $this->createRequest();

        $request2 = clone $request1;
        $request2->setPort(81);

        return array(array($request1, $request2));
    }

    public function provideUnmatchingUsernames()
    {
        $request1 = $this->createRequest();

        $request2 = clone $request1;
        $request2->setAuth('dummy');

        return array(array($request1, $request2));
    }

    public function provideUnmatchingPasswords()
    {
        $request1 = $this->createRequest();

        $request2 = clone $request1;
        $request2->setAuth('dummy', 'dummy');

        return array(array($request1, $request2));
    }

    public function provideUnmatchingPaths()
    {
        $request1 = $this->createRequest();

        $request2 = clone $request1;
        $request2->setPath('/dummy');

        return array(array($request1, $request2));
    }

    public function provideUnmatchingSchemes()
    {
        $request1 = $this->createRequest();

        $request2 = clone $request1;
        $request2->setScheme('HTTPS');

        return array(array($request1, $request2));
    }

    public function provideUnmatchingProtocolVersions()
    {
        $request1 = $this->createRequest();

        $request2 = clone $request1;
        $request2->setProtocolVersion('1.0');

        return array(array($request1, $request2));
    }

    public function provideUnmatchingMethods()
    {
        $data = array();

        foreach ($this->getMethods() as $request1Method) {
            $request1 = $this->createRequest(array('method' => $request1Method));

            foreach ($this->getMethods() as $request2Method) {
                // We only want unmatching methods
                if ($request1Method === $request2Method) {
                    continue;
                }

                $request2 = $this->createRequest(array('method' => $request2Method));

                $data[] = array($request1, $request2);
            }
        }

        return $data;
    }

    public function provideUnmatchingHeaders()
    {
        $request1 = $this->createRequest();

        $request2 = clone $request1;
        $request2->setHeader('User-Agent', 'Dummy');

        return array(array($request1, $request2));
    }


    /*
     * ============================================================================================================
     * Tests
     * ============================================================================================================
     */

    /**
     * @test
     * @dataProvider provideIdenticalRequests
     */
    public function identicalRequestsMatch($request1, $request2)
    {
        $comparator = new RequestsComparator();
        $this->assertTrue($comparator->compare($request1, $request2));
    }

    /**
     * @test
     * @dataProvider provideUnmatchingHosts
     */
    public function differentHostsDoNotMatch($request1, $request2)
    {
        $comparator = new RequestsComparator();
        $this->assertFalse($comparator->compare($request1, $request2));
    }

    /**
     * @test
     * @dataProvider provideUnmatchingPorts
     */
    public function differentPortsDoNotMatch($request1, $request2)
    {
        $comparator = new RequestsComparator();
        $this->assertFalse($comparator->compare($request1, $request2));
    }

    /**
     * @test
     * @dataProvider provideUnmatchingUsernames
     */
    public function differentUsernamesDoNotMatch($request1, $request2)
    {
        $comparator = new RequestsComparator();
        $this->assertFalse($comparator->compare($request1, $request2));
    }

    /**
     * @test
     * @dataProvider provideUnmatchingPasswords
     */
    public function differentPasswordsDoNotMatch($request1, $request2)
    {
        $comparator = new RequestsComparator();
        $this->assertFalse($comparator->compare($request1, $request2));
    }

    /**
     * @test
     * @dataProvider provideUnmatchingPaths
     */
    public function differentPathsDoNotMatch($request1, $request2)
    {
        $comparator = new RequestsComparator();
        $this->assertFalse($comparator->compare($request1, $request2));
    }

    /**
     * @test
     * @dataProvider provideUnmatchingSchemes
     */
    public function differentSchemesDoNotMatch($request1, $request2)
    {
        $comparator = new RequestsComparator();
        $this->assertFalse($comparator->compare($request1, $request2));
    }

    /**
     * @test
     * @dataProvider provideUnmatchingProtocolVersions
     */
    public function differentProtocolVersionsDoNotMatch($request1, $request2)
    {
        $comparator = new RequestsComparator();
        $this->assertFalse($comparator->compare($request1, $request2));
    }

    /**
     * @test
     * @dataProvider provideUnmatchingMethods
     */
    public function differentMethodsDoNotMatch($request1, $request2)
    {
        $comparator = new RequestsComparator();
        $this->assertFalse($comparator->compare($request1, $request2));
    }

    /**
     * @test
     * @dataProvider provideUnmatchingHeaders
     */
    public function differentHeadersDoNotMatch($request1, $request2)
    {
        $comparator = new RequestsComparator();
        $this->assertFalse($comparator->compare($request1, $request2));
    }

    /**
     * @test
     * @dataProvider provideIdenticalGetRequestsInDifferentFormats
     */
    public function comparingTheSameGetRequestInDifferentFormatsSucceed($request1, $request2)
    {
        $comparator = new RequestsComparator();
        $this->assertTrue($comparator->compare($request1, $request2));
    }

    /**
     * @test
     * @dataProvider provideInvalidObjects
     * @expectedException \InvalidArgumentException
     */
    public function comparingInvalidObjectsFails($request1, $request2)
    {
        $comparator = new RequestsComparator();
        $this->assertTrue($comparator->compare($request1, $request2));
    }

    /**
     * @test
     * @dataProvider provideInvalidTypes
     * @expectedException \InvalidArgumentException
     */
    public function comparingInvalidTypesFails($request1, $request2)
    {
        $comparator = new RequestsComparator();
        $this->assertTrue($comparator->compare($request1, $request2));
    }
}
