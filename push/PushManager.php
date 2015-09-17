<?php
namespace Bro\push;

use Bro\core\Database;

class PushManager
{
    /**
     * Singleton instance storage
     * @var PushManager
     */
    private static $p_Instance;

    /**
     * @var AirNotifier
     */
    public $airNotifier;

    public function __construct()
    {
        $this->airNotifier = new AirNotifier();
    }

    /**
     *
     * Get singleton object
     *
     * @return PushManager
     */
    public static function getInstance()
    {
        if (!self::$p_Instance) {
            self::$p_Instance = new PushManager();
        }
        return self::$p_Instance;
    }


    private static $allowedPlatforms = ['android', 'ios'];

    public function isPlatformAllowed($platform)
    {
        return in_array($platform, self::$allowedPlatforms);
    }

    public function getInfoForUuid($uuid)
    {
        $db = Database::getInstance();
        return $db->queryOneRow('SELECT * FROM `clientDevices` WHERE `id` = :uuid',
            array(':uuid' => $uuid));
    }

    public function register($uuid, $platform, $token)
    {
        $platform = strtolower($platform);
        if (!$this->isPlatformAllowed($platform)) {
            throw new InvalidPlatformException('Invalid platform ' . $platform);
        }
        // store to database
        $db = Database::getInstance();
        $db->execute('REPLACE INTO `clientDevices`(`id`, `platform`, `token`) VALUES (:uuid, :platform, :token)',
            array(':uuid' => $uuid, ':platform' => $platform, ':token' => $token));
        // send to
        $airNotifierPlatform = $this->airNotifier->convertPlatform($platform);
        $this->airNotifier->register($airNotifierPlatform, $token);
    }

    public function unregister($uuid)
    {
        $info = $this->getInfoForUuid($uuid);
        if ($info) {
            $db = Database::getInstance();
            $db->execute('DELETE FROM `clientDevices` WHERE `id` = :uuid', array(':uuid' => $uuid));
            $this->airNotifier->unregister($info['token']);
        }
    }

    public function send($uuid, $data)
    {
        $info = $this->getInfoForUuid($uuid);
        if ($info) {
            $airNotifierPlatform = $this->airNotifier->convertPlatform($info['platform']);
            $this->airNotifier->send($airNotifierPlatform, $info['token'], $data);
        }
    }
}