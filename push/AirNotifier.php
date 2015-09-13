<?php
namespace bro\push;


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

    public function register($platform, $token)
    {
        if (!$this->appName || !$this->appKey) {
            throw new \Exception('Credentials are not set');
        }
        $curl = Curl::getInstance();
        $rq = array("device" => $platform,
            "token" => $token,
            "chanel" => "default"
        );
        $headers = array('Accept' => 'application/json',
            'X-AN-APP-NAME' => $this->appName,
            'X-AN-APP-KEY' => $this->appKey
        );
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
        $headers = array('Accept' => 'application/json',
            'X-AN-APP-NAME' => $this->appName,
            'X-AN-APP-KEY' => $this->appKey
        );
        return $curl->fetch($this->endpointUrl . '/api/v2/tokens/' . $token,
            'DELETE',
            '',
            $headers
        );
    }

    public function send($platform, $token, $data)
    {
        if (!$this->appName || !$this->appKey) {
            throw new \Exception('Credentials are not set');
        }
        $curl = Curl::getInstance();
        $data["device"] = $platform;
        $data["token"] = $token;

        $headers = array('Accept' => 'application/json',
            'X-AN-APP-NAME' => $this->appName,
            'X-AN-APP-KEY' => $this->appKey
        );
        return $curl->fetch($this->endpointUrl . '/api/v2/push',
            'POST',
            json_encode($rq),
            $headers
        );
    }

}