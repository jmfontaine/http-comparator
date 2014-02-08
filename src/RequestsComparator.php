<?php
/**
 * @package HTTP Comparator
 * @author Jean-Marc Fontaine <jm@jmfontaine.net>
 * @copyright 2014 Jean-Marc Fontaine <jm@jmfontaine.net>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */
namespace HttpComparator;

use Guzzle\Http\Message\Header\HeaderCollection;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\RequestFactory;

class RequestsComparator
{
    private $headersValidators = array();

    /**
     * @param mixed $request
     *
     * @return Guzzle\Http\Message\Request|Guzzle\\Http\\Message\\EntityEnclosingRequest
     *
     * @throws \InvalidArgumentException
     */
    protected function normalizeRequest($request)
    {
        if (is_object($request)) {
            $className = get_class($request);
            switch($className) {
                case 'Guzzle\\Http\\Message\\Request':
                case 'Guzzle\\Http\\Message\\EntityEnclosingRequest':
                    // Do nothing since we normalize to a Guzzle\Http\Message\Request or
                    // Guzzle\Http\Message\EntityEnclosingRequest object
                    break;

                default:
                    throw new \InvalidArgumentException('"' . $className . '" is not supported');
            }
        } elseif (is_string($request)) {
            $request = RequestFactory::getInstance()->fromMessage($request);

            if (false === $request) {
                throw new \InvalidArgumentException('String is not a valid HTTP request');
            }
        } else {
            throw new \InvalidArgumentException('HTTP request must be passed as an object or a string');
        }

        return $request;
    }

    protected function normalizeHeaders(array $headers)
    {
        asort($headers);

        return $headers;
    }

    public function compare($request1, $request2)
    {
        $request1 = $this->normalizeRequest($request1);
        $request2 = $this->normalizeRequest($request2);

        if (!$this->compareHost($request1->getHost(), $request2->getHost())) {
            return false;
        }

        if (!$this->comparePorts($request1->getPort(), $request2->getPort())) {
            return false;
        }

        if (!$this->compareUsernames($request1->getUsername(), $request2->getUsername())) {
            return false;
        }

        if (!$this->comparePasswords($request1->getPassword(), $request2->getPassword())) {
            return false;
        }

        if (!$this->comparePath($request1->getPath(), $request2->getPath())) {
            return false;
        }

        if (!$this->compareScheme($request1->getScheme(), $request2->getScheme())) {
            return false;
        }

        if (!$this->compareProtocolVersion($request1->getProtocolVersion(), $request2->getProtocolVersion())) {
            return false;
        }

        if (!$this->compareMethod($request1->getMethod(), $request2->getMethod())) {
            return false;
        }

        if (!$this->compareHeaders($request1->getHeaders()->toArray(), $request2->getHeaders()->toArray())) {
            return false;
        }

        return true;
    }

    public function compareHost($host1, $host2)
    {
        return $host1 === $host2;
    }

    public function comparePorts($port1, $port2)
    {
        return $port1 === $port2;
    }

    public function compareUsernames($username1, $username2)
    {
        return $username1 === $username2;
    }

    public function comparePasswords($password1, $password2)
    {
        return $password1 === $password2;
    }

    public function comparePath($path1, $path2)
    {
        return $path1 === $path2;
    }

    public function compareScheme($scheme1, $scheme2)
    {
        return $scheme1 === $scheme2;
    }

    public function compareProtocolVersion($version1, $version2)
    {
        return $version1 === $version2;
    }

    public function compareMethod($method1, $method2)
    {
        return $method1 === $method2;
    }

    public function compareHeaders(array $headers1, array $headers2)
    {
        $headers1 = $this->normalizeHeaders($headers1);
        $headers2 = $this->normalizeHeaders($headers2);

        return $headers1 === $headers2;
    }
}
