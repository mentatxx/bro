<?php
namespace Bro\push;


use Bro\core\Curl;

class AirNotifier
{
    private static $transformPlatform = array(
        'ios' => 'ios',
        'android' => 'android'
    );
    public $appName;
    public $appKey;
    public $endpointUrl = 'http://localhost:8801';

    public function setCredentials($appName, $appKey)
    {
        $this->appName = $appName;
        $this->appKey = $appKey;
    }

    public function convertPlatform($platform)
    {
        if (!isset(self::$transformPlatform[$platform])) {
            throw new InvalidPlatformException('Unsupported platform for AitNotifier: ' . $platform);
        }
        return self::$transformPlatform[$platform];
    }

    private function getHeaders()
    {
        return array('Accept: application/json',
            'X-AN-APP-NAME: '.$this->appName,
            'X-AN-APP-KEY: '.$this->appKey
        );
    }

    public function register($platform, $token)
    {
        if (!$this->appName || !$this->appKey) {
            throw new \Exception('Credentials are not set');
        }
        $curl = Curl::getInstance();
        $rq = array("device" => $platform,
            "token" => $token
        );
        $headers = $this->getHeaders();
        return $curl->fetch($this->endpointUrl . '/api/v2/tokens',
            'POST',
            json_encode($rq),
            $headers
        );
    }

    public function unregister($token)
    {
        if (!$this->appName || !$this->appKey) {
            throw new \Exception('Credentials are not set');
        }
        $curl = Curl::getInstance();
        $headers = $this->getHeaders();
        return $curl->fetch($this->endpointUrl . '/api/v2/tokens/' . $token,
            'DELETE',
            '',
            $headers
        );
    }

    public function send($platform, $token, $data, $channel = 'default')
    {
        if (!$this->appName || !$this->appKey) {
            throw new \Exception('Credentials are not set');
        }
        $curl = Curl::getInstance();
        if ($platform) {
            $data["device"] = $platform;
        }
        $data["token"] = $token;
        $data["chanel"] = $channel;

        $headers = $this->getHeaders();
        return $curl->fetch($this->endpointUrl . '/api/v2/push',
            'POST',
            json_encode($data, JSON_UNESCAPED_UNICODE),
            $headers
        );
    }

}