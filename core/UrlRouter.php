<?php
namespace Bro\core;

class UrlRouter
{
    private $answered = false;

    /**
     * Route call
     *
     * @param string $url
     * @param string $pattern
     * @param callable $handler
     * @param HttpResponse $response
     * @param string $operation
     * @param string|null $parameters
     * @return bool
     */
    public function route($url, $pattern, $handler, $response, $operation = 'GET', $parameters = null)
    {
        if ($_SERVER['REQUEST_METHOD'] !== $operation) {
            // unsupported HTTP method
            return false;
        }
        // Check the URL
        $matches = array();
        if (preg_match($pattern, $url, $matches)) {
            if ($this->answered) {
                error_log('ERROR: Already answered, but matched ' . $url);
                exit();
            }
            $callParameters = array();
            $callParameters[] = $response;
            for ($i = 1; $i < count($matches); $i++) {
                $callParameters[] = $matches[$i];
            }
            if ($operation === 'POST') {
                if ($parameters) {
                    foreach (explode(',', $parameters) as $key) {
                        $callParameters[] = isset($_POST[$key]) ? $_POST[$key] : '';
                    }
                } else {
                    $callParameters[] = $_POST;
                }
            }
            if ($operation === 'GET' || $operation === 'DELETE') {
                if ($parameters) {
                    foreach (explode(',', $parameters) as $key) {
                        $callParameters[] = isset($_GET[$key]) ? $_GET[$key] : '';
                    }
                } else {
                    $callParameters[] = $_GET;
                }
            }
            if (call_user_func_array($handler, $callParameters) === false) {
                error_log('Error calling handler: '.var_export($handler));
            }
            $this->answered = true;
            return true;
        } else {
            // unsupported URL
            return false;
        }
    }

    public function startRouting()
    {
        $this->answered = false;
    }
} 