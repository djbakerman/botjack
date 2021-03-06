<?php

/*
 * This file is part of Slim JSON Web Token Authentication middleware
 *
 * Copyright (c) 2015 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-jwt-auth
 *
 */

namespace Slim\Middleware;

use Slim\Middleware\JwtAuthentication\RequestMethodRule;
use Slim\Middleware\JwtAuthentication\RequestPathRule;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Firebase\JWT\JWT;

class JwtAuthentication
{
    protected $logger;
    protected $message; /* Last error message. */

    private $options = [
        "secure" => true,
        "relaxed" => ["localhost", "127.0.0.1"],
        "environment" => "HTTP_AUTHORIZATION",
        "cookie" => "token",
        "path" => null,
        "callback" => null,
        "error" => null
    ];

    /**
     * Create a new JwtAuthentication Instance
     */
    public function __construct(array $options = [])
    {
        /* Setup stack for rules */
        $this->rules = new \SplStack;

        /* Store passed in options overwriting any defaults. */
        $this->hydrate($options);

        /* If nothing was passed in options add default rules. */
        if (!isset($options["rules"])) {
            $this->addRule(new RequestMethodRule([
                "passthrough" => ["OPTIONS"]
            ]));
        }

        /* If path was given in easy mode add rule for it. */
        if (null !== ($this->options["path"])) {
            $this->addRule(new RequestPathRule([
                "path" => $this->options["path"]
            ]));
        }
    }

    /**
     * Call the middleware
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        $scheme = $request->getUri()->getScheme();
        $host = $request->getUri()->getHost();

        /* If rules say we should not authenticate call next and return. */
        if (false === $this->shouldAuthenticate($request)) {
            return $next($request, $response);
        }

        /* HTTP allowed only if secure is false or server is in relaxed array. */
        if ("https" !== $scheme && true === $this->options["secure"]) {
            if (!in_array($host, $this->options["relaxed"])) {
                $message = sprintf(
                    "Insecure use of middleware over %s denied by configuration.",
                    strtoupper($scheme)
                );
                throw new \RuntimeException($message);
            }
        }

        /* If token cannot be found return with 401 Unauthorized. */
        if (false === $token = $this->fetchToken($request)) {
            return $this->error($request, $response, [
                "message" => $this->message
            ])->withStatus(401);
        }

        /* If token cannot be decoded return with 401 Unauthorized. */
        if (false === $decoded = $this->decodeToken($token)) {
            return $this->error($request, $response, [
                "message" => $this->message
            ])->withStatus(401);
        }

        /* If callback returns false return with 401 Unauthorized. */
        if (is_callable($this->options["callback"])) {
            $params = ["decoded" => $decoded];
            if (false === $this->options["callback"]($request, $response, $params)) {
                return $this->error($request, $response, [
                    "message" => $this->message || "Callback returned false"
                ])->withStatus(401);
            }
        }

        /* Everything ok, call next middleware and return. */
        return $next($request, $response);
    }

    /**
     * Check if middleware should authenticate
     *
     * @return boolean True if middleware should authenticate.
     */
    public function shouldAuthenticate(RequestInterface $request)
    {
        /* If any of the rules in stack return false will not authenticate */
        foreach ($this->rules as $callable) {
            if (false === $callable($request)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Call the error handler if it exists
     *
     * @return void
     */
    public function error(RequestInterface $request, ResponseInterface $response, $arguments)
    {
        if (is_callable($this->options["error"])) {
            $handler_response = $this->options["error"]($request, $response, $arguments);
            if (is_a($handler_response, "\Psr\Http\Message\ResponseInterface")) {
                return $handler_response;
            }
        }
        return $response;
    }

    /**
     * Fetch the access token
     *
     * @return string|null Base64 encoded JSON Web Token or null if not found.
     */
    public function fetchToken(RequestInterface $request)
    {
        /* If using PHP in CGI mode and non standard environment */
        $server_params = $request->getServerParams();
        if (isset($server_params[$this->options["environment"]])) {
            $message = "Using token from environent";
            $header = $server_params[$this->options["environment"]];
        } else {
            $message = "Using token from request header";
            $header = $request->getHeader("Authorization");
            $header = isset($header[0]) ? $header[0] : "";
        }
        if (preg_match("/Bearer\s+(.*)$/i", $header, $matches)) {
            $this->log(LogLevel::DEBUG, $message);
            return $matches[1];
        }

        /* Bearer not found, try a cookie. */
        $cookie_params = $request->getCookieParams();

        if (isset($cookie_params[$this->options["cookie"]])) {
            $this->log(LogLevel::DEBUG, "Using token from cookie");
            $this->log(LogLevel::DEBUG, $cookie_params[$this->options["cookie"]]);
            return $cookie_params[$this->options["cookie"]];
        };

        /* If everything fails log and return false. */
        $this->message = "Token not found";
        $this->log(LogLevel::WARNING, $this->message);
        return false;
    }

    public function decodeToken($token)
    {
        try {
            return JWT::decode(
                $token,
                $this->options["secret"],
                ["HS256", "HS512", "HS384", "RS256"]
            );
        } catch (\Exception $exception) {
            $this->message = $exception->getMessage();
            $this->log(LogLevel::WARNING, $exception->getMessage(), [$token]);
            return false;
        }
    }

    /**
     * Hydate options from given array
     *
     * @param array $data Array of options.
     * @return self
     */
    private function hydrate(array $data = [])
    {
        foreach ($data as $key => $value) {
            $method = "set" . ucfirst($key);
            if (method_exists($this, $method)) {
                call_user_func(array($this, $method), $value);
            }
        }
        return $this;
    }


    /**
     * Get path where middleware is be binded to
     *
     * @return string
     */
    public function getPath()
    {
        return $this->options["path"];
    }

    /**
     * Set path where middleware should be binded to
     *
     * @return self
     */
    public function setPath($path)
    {
        $this->options["path"] = $path;
        return $this;
    }

    /**
     * Get the environment name where to search the token from
     *
     * @return string Name of environment variable.
     */
    public function getEnvironment()
    {
        return $this->options["environment"];
    }

    /**
     * Set the environment name where to search the token from
     *
     * @return self
     */
    public function setEnvironment($environment)
    {
        $this->options["environment"] = $environment;
        return $this;
    }

    /**
     * Get the cookie name where to search the token from
     *
     * @return string
     */
    public function getCookie()
    {
        return $this->options["cookie"];
    }

    /**
     * Set the cookie name where to search the token from
     *
     * @return self
     */
    public function setCookie($cookie)
    {
        $this->options["cookie"] = $cookie;
        return $this;
    }

    /**
     * Get the secure flag
     *
     * @return string
     */
    public function getSecure()
    {
        return $this->options["secure"];
    }

    /**
     * Set the secure flag
     *
     * @return self
     */
    public function setSecure($secure)
    {
        $this->options["secure"] = !!$secure;
        return $this;
    }


    /**
     * Get hosts where secure rule is relaxed
     *
     * @return string
     */
    public function getRelaxed()
    {
        return $this->options["relaxed"];
    }

    /**
     * Set hosts where secure rule is relaxed
     *
     * @return self
     */
    public function setRelaxed(array $relaxed)
    {
        $this->options["relaxed"] = $relaxed;
        return $this;
    }

    /**
     * Get the secret key
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->options["secret"];
    }

    /**
     * Set the secret key
     *
     * @return self
     */
    public function setSecret($secret)
    {
        $this->options["secret"] = $secret;
        return $this;
    }

    /**
     * Get the callback
     *
     * @return string
     */
    public function getCallback()
    {
        return $this->options["callback"];
    }

    /**
     * Set the callback
     *
     * @return self
     */
    public function setCallback($callback)
    {
        $this->options["callback"] = $callback->bindTo($this);
        return $this;
    }

    /**
     * Get the error handler
     *
     * @return string
     */
    public function getError()
    {
        return $this->options["error"];
    }

    /**
     * Set the error handler
     *
     * @return self
     */
    public function setError($error)
    {
        $this->options["error"] = $error;
        return $this;
    }

    /**
     * Get the rules stack
     *
     * @return \SplStack
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Set all rules in the stack
     *
     * @return self
     */
    public function setRules(array $rules)
    {
        /* Clear the stack */
        unset($this->rules);
        $this->rules = new \SplStack;
        /* Add the rules */
        foreach ($rules as $callable) {
            $this->addRule($callable);
        }
        return $this;
    }

    /**
     * Add rule to the stack
     *
     * @param callable $callable Callable which returns a boolean.
     * @return self
     */
    public function addRule($callable)
    {
        $this->rules->push($callable);
        return $this;
    }

    /* Cannot use traits since PHP 5.3 should be supported */

    /**
     * Get the logger
     *
     * @return Psr\Log\LoggerInterface $logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Set the logger
     *
     * @param Psr\Log\LoggerInterface $logger
     * @return self
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return null
     */
    public function log($level, $message, array $context = [])
    {
        if ($this->logger) {
            return $this->logger->log($level, $message, $context);
        }
    }

    /**
     * Get last error message
     *
     * @return String
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set the last error message
     *
     * @param String
     * @return self
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }
}
