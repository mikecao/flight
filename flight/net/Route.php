<?php
/**
 * Flight: An extensible micro-framework.
 *
 * @copyright   Copyright (c) 2011, Mike Cao <mike@mikecao.com>
 * @license     MIT, http://flightphp.com/license
 */

namespace flight\net;

/**
 * The Route class is responsible for routing an HTTP request to
 * an assigned callback function. The Router tries to match the
 * requested URL against a series of URL patterns.
 */
class Route {
    /**
     * @var string URL pattern
     */
    public $pattern;

    /**
     * @var mixed Callback function
     */
    public $callback;

    /**
     * @var array HTTP methods
     */
    public $methods = array();

    /**
     * @var array Route parameters
     */
    public $params = array();

    /**
     * @var string Matching regular expression
     */
    public $regex;

    /**
     * @var string URL splat content
     */
    public $splat;

    /**
     * @var boolean Pass self in callback parameters
     */
    public $pass = false;

    /**
     * @var array Route extra parameters
     */
    public $extras = array();

    /**
     * Constructor.
     *
     * @param string $pattern URL pattern
     * @param mixed $callback Callback function
     * @param array $methods HTTP methods
     * @param boolean $pass Pass self in callback parameters
     * @param array $extras Pass extra parameters in route
     */
    public function __construct($pattern, $callback, $methods, $pass, $extras) {
        $this->pattern = $pattern;
        $this->callback = $callback;
        $this->methods = $methods;
        $this->pass = $pass;
        $this->extras = $extras;
    }

    /**
     * Checks if a URL matches the route pattern. Also parses named parameters in the URL.
     *
     * @param string $url Requested URL
     * @return boolean Match status
     */
    public function matchUrl($url) {
        // Wildcard or exact match
        if ($this->pattern === '*' || $this->pattern === $url) {
            if ($this->pass) {
                array_push($this->params, $this);
            }
            return true;
        }

        $ids = array();
        $char = substr($this->pattern, -1);

        $this->splat = substr($url, strpos($this->pattern, '*'));
        $this->pattern = str_replace(array(')','*'), array(')?','.*?'), $this->pattern);

        // Build the regex for matching
        $regex = preg_replace_callback(
            '#@([\w]+)(:([^/\(\)]*))?#',
            function($matches) use (&$ids) {
                $ids[$matches[1]] = null;
                if (isset($matches[3])) {
                    return '(?P<'.$matches[1].'>'.$matches[3].')';
                }
                return '(?P<'.$matches[1].'>[^/\?]+)';
            },
            $this->pattern
        );

        // Fix trailing slash
        if ($char === '/') {
            $regex .= '?';
        }
        // Allow trailing slash
        else {
            $regex .= '/?';
        }

        // Attempt to match route and named parameters
        if (preg_match('#^'.$regex.'(?:\?.*)?$#i', $url, $matches)) {
            foreach ($ids as $k => $v) {
                $this->params[$k] = (array_key_exists($k, $matches)) ? urldecode($matches[$k]) : null;
            }

            if ($this->pass) {
                array_push($this->params, $this);
            }

            $this->regex = $regex;

            return true;
        }

        return false;
    }

    /**
     * Checks if an HTTP method matches the route methods.
     *
     * @param string $method HTTP method
     * @return bool Match status
     */
    public function matchMethod($method) {
        return count(array_intersect(array($method, '*'), $this->methods)) > 0;
    }
}