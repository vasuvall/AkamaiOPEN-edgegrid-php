<?php
/**
 * Akamai {OPEN} EdgeGrid Auth for PHP
 *
 * Akamai\Open\EdgeGrid\Client wraps GuzzleHttp\Client
 * providing request authentication/signing for Akamai
 * {OPEN} APIs.
 *
 * This client works _identically_ to GuzzleHttp\Client
 *
 * However, if you try to call an Akamai {OPEN} API you *must*
 * first call {@see Akamai\Open\EdgeGrid\Client->setAuth()}.
 *
 * @author Davey Shafik <dshafik@akamai.com>
 * @copyright Copyright 2015 Akamai Technologies, Inc. All rights reserved.
 * @license Apache 2.0
 * @link https://github.com/akamai-open/edgegrid-auth-php
 * @link https://developer.akamai.com
 * @link https://developer.akamai.com/introduction/Client_Auth.html
 */
namespace Akamai\Open\EdgeGrid;

class Cli
{
    /**
     * @var \League\CLImate\CLImate
     */
    protected $climate;

    /**
     * @var ClientFactory
     */
    private $factory;

    public function __construct(ClientFactory $factory = null)
    {
        $this->factory = $factory;
        if ($factory === null) {
            $this->factory = new ClientFactory([static::class, 'factory']);
        }

        $this->climate = new \League\CLImate\CLImate();
        $this->climate->description("Akamai {OPEN} Edgegrid Auth for PHP Client");
    }

    public function run($args)
    {
        if ($this->parseArguments($args)) {
            $this->executeCommand($args);
        }
    }

    protected function parseArguments($inputArgs)
    {
        $args = [
            'help' => [
                'longPrefix' => 'help',
                'prefix' => 'h',
                'description' => 'Show this help output',
                'noValue' => true
            ],
            'auth-type' => [
                'longPrefix' => 'auth-type',
                'description' => "{basic, digest, edgegrid}"
            ],
            'auth' => [
                'longPrefix' => 'auth',
                'prefix' => 'a',
                'description' => '.edgerc section name, or user[:password]'
            ],
        ];

        $this->climate->arguments->add($args);

        if (sizeof($inputArgs) == 1) {
            $this->help($inputArgs);
            return false;
        }

        if ($this->climate->arguments->get('help')) {
            $this->help($inputArgs);
            return false;
        }

        try {
            $this->climate->arguments->parse($inputArgs);

            $padding = sizeof($args);
            foreach ($this->climate->arguments->toArray() as $arg) {
                if ($arg == null) {
                    $padding -= 1;
                }
            }
            $argSize = sizeof($inputArgs) - $padding - 1;
            for ($i = 0; $i < $argSize; $i++) {
                $args['arg-' . $i] = [];
            }
            $this->climate->arguments->add($args);
            $this->climate->arguments->parse($inputArgs);
        } catch (\Exception $e) {
        }

        return true;
    }

    protected function executeCommand()
    {
        static $methods = [
            'HEAD',
            'GET',
            'POST',
            'PUT',
            'DELETE'
        ];

        \Akamai\Open\EdgeGrid\Client::setDebug(true);
        \Akamai\Open\EdgeGrid\Client::setVerbose(true);

        $args = $this->climate->arguments->all();
        do {
            $last = array_pop($args);
        } while ($last->value() == null);

        $url = $last->value();

        $client = new Client();

        if ($this->climate->arguments->defined('auth-type')) {
            $auth = $this->climate->arguments->get('auth');
            if ($this->climate->arguments->get('auth-type') == 'edgegrid' ||
                (!$this->climate->arguments->defined('auth-type') && $url{0} == ':')) {
                $section = 'default';
                if ($this->climate->arguments->defined('auth')) {
                    $section = (substr($auth, -1) == ':') ? substr($auth, 0, -1) : $auth;
                }
                $client = $this->factory($section);
            }

            if (in_array($this->climate->arguments->get('auth-type'), ['basic', 'digest'])) {
                if (!$this->climate->arguments->defined('auth') || $this->climate->arguments->get('auth') === null) {
                    $this->help($this->climate->arguments->all());
                    return false;
                }

                $auth = [
                    $auth,
                    null,
                    $this->climate->arguments->get('auth-type')
                ];

                if (strpos(':', $auth[0]) !== false) {
                    list($auth[0], $auth[1]) = explode(':', $auth[0]);
                }

                $client = new Client(['auth' => $auth]);
            }
        }

        $method = 'GET';

        if ($url{0} == ':') {
            $url = substr($url, 1);
        }

        $options = [];
        $body = [];

        foreach ($args as $arg) {
            if (strpos($arg->name(), 'arg-') !== false) {
                if (in_array(strtoupper($arg->value()), $methods)) {
                    $method = $arg->value();
                    continue;
                }

                $matches = [];
                if (preg_match('/^(?<key>.*?):=(?<value>.*?)$/', $arg->value(), $matches)) {
                    $body[$matches['key']] = json_decode($matches['value']);
                    continue;
                }

                if (preg_match('/^(?<header>.*?):(?<value>.*?)$/', $arg->value(), $matches)) {
                    $options['headers'][$matches['header']] = $matches['value'];
                    continue;
                }

                if (preg_match('/^(?<key>.*?)=(?<value>.*?)$/', $arg->value(), $matches)) {
                    $body[$matches['key']] = $matches['value'];
                }
            }
        }

        if (sizeof($body)) {
            if (!isset($options['headers']['Content-Type'])) {
                $options['headers']['Content-Type'] = 'application/json';
            }
            if (!isset($options['headers']['Accept'])) {
                $options['headers']['Accept'] = 'application/json';
            }
            $options['body'] = json_encode($body);
        }

        $client->request($method, $url, $options);
    }

    public function help($inputArgs)
    {
        $this->climate->usage($inputArgs);
    }

    public static function factory($section)
    {
        return Client::createFromEdgeRcFile($section);
    }
}
