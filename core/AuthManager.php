<?php
namespace Bro\core;

use Bro\facebook\Facebook;
use Bro\facebook\FacebookApiException;

require_once __DIR__ . '/../google/Google_Client.php';
require_once __DIR__ . "/../google/Google_Client.php";
require_once __DIR__ . "/../google/contrib/Google_PlusService.php";
require_once __DIR__ . "/../google/contrib/Google_Oauth2Service.php";

class AuthManager
{

    /**
     * Singleton instance storage
     * @var AuthManager
     */
    private static $p_Instance;

    /**
     * User info
     * @var array
     */
    public $authData;

    /**
     * Store in session
     * @var bool
     */
    public $storeInSession;
    public $userId;
    public $CSRF;
    public $authKey;

    static function getInstance()
    {
        if (!self::$p_Instance) {
            self::$p_Instance = new AuthManager();
        }
        return self::$p_Instance;
    }

    /**
     * Private constructor for singleton
     */
    private function __construct()
    {
        if (isset($_SESSION['authData'])) {
            $this->authData = $_SESSION['authData'];
            $this->userId = $this->authData['id'];
            $this->CSRF = $this->authData['CSRF'];
            $this->authKey = $this->authData['authKey'];
        } else {
            $this->clear();
        }
        global $forceUser;
        if (isset($forceUser)) {
            $this->authData = array();
            $this->userId = $forceUser;
            $this->CSRF = 'FORCED';
            $this->authKey = $this->_makeAuthToken($forceUser);
        }
        $this->storeInSession = true;
    }

    public function clear()
    {
        $this->authData = array();
        $this->userId = 0;
        $this->CSRF = '';
        $this->authKey = $this->_makeAuthToken(0);
    }

    /**
     * Get existing or create new user id
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Begin Facebook authentication
     *
     * @param string $fbAppId
     * @param string $fbSecret
     */
    public function authFacebook($fbAppId, $fbSecret)
    {
        $facebook = new Facebook(array(
            'appId' => $fbAppId,
            'secret' => $fbSecret,
        ));

        // Get User ID
        $fbUser = $facebook->getUser();
        if ($fbUser) {
            try {
                // Proceed knowing you have a logged in user who's authenticated.
                $user_profile = $facebook->api('/me');
                $this->registerFBUser($user_profile['id'], $user_profile);
                if (isset($_SESSION['back'])) {
                    $backURI = urldecode($_SESSION['back']);
                    unset($_SESSION['back']);
                    header('Location: http://' . $_SERVER['HTTP_HOST'] . $backURI);
                } else {
                    header('Location: http://' . $_SERVER['HTTP_HOST'] . '/dashboard');
                }
                die();
            } catch (FacebookApiException $e) {
                error_log($e);
                $fbUser = null;
                die('Facebook has some problems. Please report.');
            }
            $logoutUrl = $facebook->getLogoutUrl();
            $data['logoutUrl'] = $logoutUrl;
        } else {
            $loginUrl = $facebook->getLoginUrl();
            header("Location: $loginUrl");
            die();
        }
    }

    /**
     * Begin Google authentication
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectURI
     * @param string $developerKey
     */
    public function authGoogle($clientId, $clientSecret, $redirectURI, $developerKey)
    {
        $client = new \Google_Client();
        $client->setApplicationName("JsLog.me");

        // Visit https://code.google.com/apis/console to generate your
        // oauth2_client_id, oauth2_client_secret, and to register your oauth2_redirect_uri.
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectURI);
        $client->setDeveloperKey($developerKey);

        $oauth2Service = new \Google_Oauth2Service($client);

        if (isset($_REQUEST['logout'])) {
            unset($_SESSION['access_token']);
            return;
        }

        if (isset($_GET['code'])) {
            $client->authenticate($_GET['code']);
            $_SESSION['access_token'] = $client->getAccessToken();
            header('Location: http://' . $_SERVER['HTTP_HOST'] . '/auth/google');
            return;
        }

        if (isset($_SESSION['access_token'])) {
            $client->setAccessToken($_SESSION['access_token']);
        }

        if ($client->getAccessToken()) {
            $me = $oauth2Service->userinfo->get();

            $this->registerGoogleUser($me['id'], $me);

            // The access token may have been updated lazily.
            $_SESSION['access_token'] = $client->getAccessToken();

            if (isset($_SESSION['back'])) {
                $backURI = urldecode($_SESSION['back']);
                unset($_SESSION['back']);
                header('Location: http://' . $_SERVER['HTTP_HOST'] . $backURI);
            } else {
                header('Location: http://' . $_SERVER['HTTP_HOST'] . '/dashboard');
            }
            return;
        } else {
            $authUrl = $client->createAuthUrl();
            header("Location: $authUrl");
            return;
        }
    }

    /**
     * Authenticate with given email, password
     * Returns FALSE on error, or userId on success
     *
     * @param string $email
     * @param string $password
     * @param $error
     * @throws \Exception
     * @return boolean|integer
     */
    public function authEmail($email, $password, &$error)
    {
        $db = Database::getInstance();
        $usersAuth = $db->queryOneRow('SELECT `userId`, `serviceKey3` FROM `usersAuth` WHERE `service` = 3 AND `serviceKey2` = :email', array(':email' => $email));
        $authenticated = FALSE;
        if ($usersAuth) {
            $payload = unserialize($usersAuth['serviceKey3']);
            $evaluatedHash = hash('sha256', $password . $payload['salt']);
            if ($payload['hash'] == $evaluatedHash) {
                if ($payload['confirmed']) {
                    $userId = $usersAuth['userId'];
                    $authenticated = $userId;
                    $this->registerAsUserId($userId);
                } else {
                    $error = 'Email address is not confirmed. Check your mail';
                }
            } else {
                $error = 'Wrong email or password';
            }
        } else {
            $error = 'Wrong email or password';
        }
        return $authenticated;
    }

    /**
     * Check password for authenticated user
     *
     * @param $password
     * @return bool
     * @throws \Exception
     */
    public function validPassword($password)
    {
        if (!$this->userId) {
            throw new \Exception('User is not authorized');
        }
        if ($this->authData['service'] != 3) {
            throw new \Exception('User is not using email authentication');
        }
        $db = Database::getInstance();
        $usersAuth = $db->queryOneRow('SELECT `serviceKey3` FROM `usersAuth` WHERE `userId` = :userId',
            array(':userId' => $this->userId));
        if (!$usersAuth) {
            throw new \Exception('User disappeared from database');
        }
        $payload = unserialize($usersAuth['serviceKey3']);
        $evaluatedHash = hash('sha256', $password . $payload['salt']);
        return $payload['hash'] == $evaluatedHash;
    }

    /**
     * Change password for authenticated user
     *
     * @param $password
     * @throws \Exception
     */
    public function changePassword($password)
    {
        if (!$this->userId) {
            throw new \Exception('User is not authorized');
        }
        if ($this->authData['service'] != 3) {
            throw new \Exception('User is not using email authentication');
        }
        $db = Database::getInstance();
        $usersAuth = $db->queryOneRow('SELECT `serviceKey3` FROM `usersAuth` WHERE `userId` = :userId',
            array(':userId' => $this->userId));
        if (!$usersAuth) {
            throw new \Exception('User disappeared from database');
        }
        $payload = unserialize($usersAuth['serviceKey3']);
        $evaluatedHash = hash('sha256', $password . $payload['salt']);
        $payload['hash'] = $evaluatedHash;
        $serviceKey = serialize($payload);
        $db->execute('UPDATE `usersAuth` SET `serviceKey3`=:serviceKey WHERE `userId` = :userId',
            array(':userId' => $this->userId, ':serviceKey' => $serviceKey));
    }

    /**
     * Bind OpenId account (Google) to client
     *
     * @param int $googleId
     * @param $userInfo
     */
    public function registerGoogleUser($googleId, $userInfo)
    {
        $db = Database::getInstance();

        $row = $db->queryOneRow('SELECT userId FROM usersAuth WHERE service = 1 AND serviceKey2 = :username', array(':username' => $googleId));
        if ($row) {
            $userId = $row['userId'];
            $this->registerAsUserId($userId);
        } else {
            if (!$this->userId)
                $this->registerNewUser();
            $db->execute('REPLACE INTO usersAuth(userId, service, serviceKey1, serviceKey2, serviceKey3) VALUES (:userId, 1, 0, :username, :email)', array(
                    ':userId' => $this->userId,
                    ':username' => $googleId,
                    ':email' => serialize($userInfo)
                )
            );
        }
    }

    /**
     * Bind Facebook account to client
     *
     * @param string $fbuserid
     * @param string $userInfo
     */
    public function registerFBUser($fbuserid, $userInfo)
    {
        $db = Database::getInstance();

        $row = $db->queryOneRow('SELECT userId FROM usersAuth WHERE service = 2 AND serviceKey2 = :fbuserid', array(':fbuserid' => $fbuserid));
        if ($row) {
            $userId = $row['userId'];
            $this->registerAsUserId($userId);
        } else {
            if (!$this->userId)
                $this->registerNewUser();
            // add auth to existing user
            $db->execute('REPLACE INTO usersAuth(userId, service, serviceKey1, serviceKey2, serviceKey3) VALUES (:userId, 2, 0, :fbuserid, :userinfo)', array(
                    ':userId' => $this->userId,
                    ':fbuserid' => $fbuserid,
                    ':userinfo' => serialize($userInfo)
                )
            );
        }
    }

    /**
     * Register user with specified email
     *
     * @param $email
     * @param $password
     * @param $confirmToken
     * @param $error
     * @return bool|integer
     * @throws \Exception
     */
    public function registerEmail($email, $password, &$confirmToken, &$error)
    {
        $db = Database::getInstance();
        $usersAuth = $db->queryOneRow('SELECT `userId`, `serviceKey3` FROM `usersAuth` WHERE `service` = 3 AND `serviceKey2` = :email', array(':email' => $email));
        if ($usersAuth) {
            $payload = unserialize($usersAuth['serviceKey3']);
            if ($payload['confirmed']) {
                $error = 'This e-mail already registered. Try to recover password';
            } else {
                $error = 'This e-mail is already registered, but not confirmed. Check your mail or resend confirmation by recovering password';
            }
            return FALSE;
        } else {
            if (!$this->userId)
                $this->registerNewUser();
            $salt = generateRandomString();
            $randomConfirmToken = generateRandomString(24);
            $hash = hash('sha256', $password . $salt);
            $payload = array('confirmed' => 0, 'hash' => $hash, 'confirmToken' => $randomConfirmToken, 'salt' => $salt);
            $db->execute('REPLACE INTO usersAuth(userId, service, serviceKey1, serviceKey2, serviceKey3) VALUES (:userId, 3, 0, :email, :payload)', array(
                ':userId' => $this->userId,
                ':email' => $email,
                ':payload' => serialize($payload)
            ));
            $confirmToken = $this->userId . '-' . $randomConfirmToken;
            return $this->userId;
        }
    }

    public function confirmEmail($packedToken)
    {
        $db = Database::getInstance();
        $expandedToken = explode('-', $packedToken);
        if (count($expandedToken) == 2) {
            list($userId, $confirmToken) = $expandedToken;
            $usersAuth = $db->queryOneRow('SELECT `serviceKey3` FROM `usersAuth` WHERE `service` = 3 AND `userId` = :userId',
                array(':userId' => $userId));
            if ($usersAuth) {
                $payload = unserialize($usersAuth['serviceKey3']);
                if ($payload['confirmToken'] === $confirmToken) {
                    $payload['confirmed'] = 1;
                    $db->execute('UPDATE `usersAuth` SET `serviceKey3` = :payload WHERE `userId` = :id AND `service` = 3',
                        array(':payload' => serialize($payload), ':id' => $userId));
                    $this->registerAsUserId($userId);
                    return TRUE;
                } else {
                    return FALSE;
                }
            } else {
                return FALSE;
            }
        } else {
            // invalid token
            return FALSE;
        }
    }

    public function prepareRecoveryToken($email, &$packedRecoveryToken)
    {
        $db = Database::getInstance();
        $usersAuth = $db->queryOneRow('SELECT `userId`, `serviceKey3` FROM `usersAuth` WHERE `service` = 3 AND `serviceKey2` = :email', array(':email' => $email));
        if ($usersAuth) {
            $payload = unserialize($usersAuth['serviceKey3']);
            $recoveryToken = generateRandomString(20);
            //
            $payload['recoveryToken'] = $recoveryToken;
            $payload['recoveryDate'] = time();
            $db->execute('UPDATE `usersAuth` SET `serviceKey3` = :payload WHERE `service` = 3 AND `userId` = :id', array(':payload' => serialize($payload), ':id' => $usersAuth['userId']));
            //
            $packedRecoveryToken = $usersAuth['userId'] . '-' . $recoveryToken;
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function recoverPassword($packedToken, $password)
    {
        $db = Database::getInstance();
        $expandedToken = explode('-', $packedToken);
        if (count($expandedToken) == 2) {
            list($userId, $recoveryToken) = $expandedToken;
            $usersAuth = $db->queryOneRow('SELECT `serviceKey3` FROM `usersAuth` WHERE `service` = 3 AND `userId` = :userId', array(':userId' => $userId));
            if ($usersAuth) {
                $payload = unserialize($usersAuth['serviceKey3']);
                if (isset($payload['recoveryToken']) && ($payload['recoveryToken'] == $recoveryToken)) {
                    //
                    $payload['confirmed'] = 1;
                    $payload['hash'] = hash('sha256', $password . $payload['salt']);
                    unset($payload['recoveryToken']);
                    $db->execute('UPDATE `usersAuth` SET `serviceKey3` = :payload WHERE `service` = 3 AND `userId` = :id', array(':payload' => serialize($payload), ':id' => $userId));
                    //
                    $this->registerAsUserId($userId);
                    return TRUE;
                } else {
                    return FALSE;
                }
            } else {
                return FALSE;
            }
        } else {
            // invalid token
            return FALSE;
        }
    }

    public function registerNewUser()
    {
        $db = Database::getInstance();
        $db->execute('INSERT INTO users(id, CSRF, apiToken) VALUES(NULL, :CSRF, :apiToken)',
            array(':CSRF' => uniqid(), ':apiToken' => sha1(uniqid() . 'JsLogApiToken')));
        $this->userId = $db->lastInsertId();
        $this->authData['id'] = $this->userId;
        $this->authData['CSRF'] = uniqid();
        $this->authData['authKey'] = $this->_makeAuthToken($this->userId);
        $this->CSRF = $this->authData['CSRF'];
        $this->authKey = $this->authData['authKey'];
        if ($this->storeInSession) {
            $this->saveToSession();
        }
    }

    /**
     * Авторизоваться как указанный пользователь и сохрнаить в сессию
     *
     * @param int $userId
     * @return bool
     */
    public function registerAsUserId($userId)
    {
        $result = $this->impersonateAsUser($userId);
        if ($this->storeInSession) {
            $this->saveToSession();
        }
        return $result;
    }

    /**
     * Impersonate by given API key
     *
     * @param $apiKey
     * @return bool
     * @throws \Exception
     */
    public function impersonateByApiKey($apiKey)
    {
        $db = Database::getInstance();
        $row = $db->queryOneRow('SELECT `id` FROM `users` WHERE `apiToken`=:apiKey',
            array(':apiKey' => $apiKey));
        if ($row) {
            return $this->impersonateAsUser($row['id']);
        } else {
            return false;
        }
    }

    /**
     * Авторизоваться как указанный пользователь (на время выполнения скрипта)
     *
     * @param int $userId
     * @return bool
     */
    public function impersonateAsUser($userId)
    {
        $db = Database::getInstance();

        $userId = intval($userId);
        $this->userId = $userId;
        $this->authData['id'] = intval($userId);
        $this->authData['CSRF'] = uniqid();
        $this->authData['authKey'] = $this->_makeAuthToken($userId);
        $this->CSRF = $this->authData['CSRF'];
        $this->authKey = $this->authData['authKey'];

        $row = $db->queryOneRow('SELECT * FROM users WHERE id = :id', array(':id' => $userId));
        foreach ($row as $key => $value)
            $this->authData[$key] = $value;
        $usersAuth = $db->queryOneRow('SELECT `service` FROM `usersAuth` WHERE `userId` = :id', array(':id' => $userId));
        $this->authData['service'] = $usersAuth['service'];
        return !!$row;
    }

    public function impersonateByToken($authToken)
    {
        $decoded64 = base64_decode($authToken);
        if (!$decoded64) throw new \Exception('Invalid authentication token');
        $token = json_decode($decoded64, true);
        if (!$token) throw new \Exception('Invalid authentication token');
        $auth = $token['auth'];
        if (!$auth) throw new \Exception('Invalid authentication token');
        $userId = $auth['id'];
        if (!$userId) throw new \Exception('Invalid authentication token');
        $hash = $token['hash'];
        if (!$hash) throw new \Exception('Invalid authentication token');
        $expectedHash = $this->_makeAuthToken($userId);
        if ($authToken === $expectedHash) {
            return $this->impersonateAsUser($userId);
        } else {
            return false;
        }
    }

    private function _makeAuthToken($userId)
    {
        global $authenticationKey;
        $auth = array('id' => $userId);
        $authString = json_encode($auth);
        $hash = hash_hmac('sha256', $authString, $authenticationKey);
        $token = array('auth' => $auth, 'hash' => $hash);
        return base64_encode(json_encode($token));
    }

    public function saveToSession()
    {
        global $_SESSION;
        $_SESSION['authData'] = $this->authData;
    }

    public function logout()
    {
        unset($_SESSION['authData']);
        unset($_SESSION['back']);
        header("Location: /");
        die();
    }

    public function checkCSRF($csrf)
    {
        // Dont allow empty csrf token
        if ($csrf == '')
            return FALSE;
        return $this->CSRF == $csrf;
    }
}

function generateRandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

