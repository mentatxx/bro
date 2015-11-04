<?php
namespace Bro\push;


use Parse\ParseClient;
use Parse\ParseInstallation;
use Parse\ParsePush;
use Parse\ParseQuery;

class ParseComPush
{
    const DEFAULT_CHANNEL = 'default';

    // Parse.com accepts "ios", "android", "winrt", "winphone", or "dotnet"
    // as acceptable types
    private static $transformPlatform = array(
        'ios' => 'ios',
        'android' => 'android',
        'windows' => 'winrt'
    );
    public $parseComAppId;
    public $parseComRestKey;
    public $parseComMasterKey;

    private $parseClientInitialized = false;

    public function checkRegistration()
    {
        if (!$this->parseClientInitialized) {
            ParseClient::initialize($this->parseComAppId, $this->parseComRestKey, $this->parseComMasterKey);
            $this->parseClientInitialized = true;
        }
    }

    public function setCredentials($parseComAppId, $parseComRestKey, $parseComMasterKey)
    {
        $this->parseComAppId = $parseComAppId;
        $this->parseComRestKey = $parseComRestKey;
        $this->parseComMasterKey = $parseComMasterKey;
    }

    public function convertPlatform($platform)
    {
        if (!isset(self::$transformPlatform[$platform])) {
            throw new InvalidPlatformException('Unsupported platform for ParseCom: ' . $platform);
        }
        return self::$transformPlatform[$platform];
    }

    public function register($platform, $token)
    {
        $this->checkRegistration();
        $parsePlatform = $this->convertPlatform($platform);

        $installObj = ParseInstallation::create('_Installation');
        $installObj->set('deviceType', $parsePlatform);
        $installObj->set('deviceToken', $token);
        $installObj->setArray('channels', array(self::DEFAULT_CHANNEL));
        $installObj->save();
    }

    public function unregister($token)
    {
        $this->checkRegistration();

        $query = new ParseQuery("_Installation");
        $query->equalTo("deviceToken", $token);
        $results = $query->find();
        for ($i = 0; $i < count($results); $i++) {
            $object = $results[$i];
            $object->destroy();
        }
    }

    public function send($platform, $token, $data, $channel = self::DEFAULT_CHANNEL)
    {
        $this->checkRegistration();
        $parsePlatform = $this->convertPlatform($platform);

        $queryForPush = ParseInstallation::query();
        if ($platform) {
            $queryForPush->equalTo('deviceType', $parsePlatform);
        }
        if ($channel) {
            $queryForPush->equalTo('channel', $channel);
        }
        $queryForPush->equalTo('deviceToken', $token);
        ParsePush::send(
            array(
                "where" => $queryForPush,
                "data" => $data
            )
        );
    }

}