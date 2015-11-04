<?php
namespace Bro\core;
/**
 *
 * Database wrapper for PDO with memcached
 * Singleton pattern used.
 *
 */

class Database
{
    /**
     * Singleton instance storage
     * @var Database
     */
    private static $p_Instance;
    /**
     * PDO wrapper for database
     * @var \PDO
     */
    private $dbh;
    /**
     * @var \Memcached
     */
    private $memh;
    private $connected;

    /** Number of rows affected by last command*/
    private $affectedRowCount;

    public function __construct()
    {
        $this->dbh = NULL;
        $this->memh = NULL;
        $this->connected = FALSE;
    }

    /**
     *
     * Get singleton object
     *
     * @return Database
     */
    public static function getInstance()
    {
        if (!self::$p_Instance) {
            self::$p_Instance = new Database();
        }
        return self::$p_Instance;
    }

    private $_dsn;
    private $_username;
    private $_password;
    /**
     *
     * Connect to database & memcache
     *
     * @param string $dsn - PDO source
     * @param string $username - PDO username
     * @param string $password - PDO password
     * @param string $memcacheId - Memcache connection Id
     * @param string $memcacheIp - Memcache connection Ip
     * @param string $memcachePort - Memcache connection Port
     * @param string $memcachePrefix - Memcache connection Prefix
     */
    public function connect($dsn, $username, $password, $memcacheId, $memcacheIp, $memcachePort, $memcachePrefix = 'mf_')
    {
        $this->_dsn = $dsn;
        $this->_username = $username;
        $this->_password = $password;
        // Set UTF-8, php 5.3 bug workaround
        $dbOptions = array(1002 => 'SET NAMES utf8', \PDO::ATTR_PERSISTENT => false);
        //
        $this->dbh = new \PDO($dsn, $username, $password, $dbOptions);

        $mem = new \Memcached($memcacheId);
        $mem->setOption(\Memcached::OPT_RECV_TIMEOUT, 1000);
        $mem->setOption(\Memcached::OPT_SEND_TIMEOUT, 3000);
        $mem->setOption(\Memcached::OPT_TCP_NODELAY, true);
        $mem->setOption(\Memcached::OPT_PREFIX_KEY, $memcachePrefix);
        $mem->addServer($memcacheIp, $memcachePort);
        $this->memh = $mem;
        $this->connected = TRUE;
    }

    /**
     * Выполняет SQL запрос, возвращающий одну строку
     *
     * @param string $query
     * @param array $parameters
     * @param array $types
     * @throws \Exception
     * @return array|bool
     */
    public function queryOneRow($query, $parameters = array(), $types = array())
    {
        if (!$this->connected) throw new \Exception('Connection not initiated');

        $stmt = $this->dbh->prepare($query);
        if (count($types)) {
            if (count($parameters) != count($types)) {
                error_log('Database error, wrong types count: ' . $query . "\n");
            }
            foreach ($parameters as $k => $v) {
                $stmt->bindParam($k, $parameters[$k], $types[$k]);
            }
            $okay = $stmt->execute();
        } else {
            $okay = $stmt->execute($parameters);
        }
        if (!$okay) {
            $err = $stmt->errorInfo();
            error_log('Database error: ' . $query . "\n" . var_export($err, TRUE));
        };
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * Выполняет запрос, возвращающий несколько строк
     *
     * @param string $query SQL with placeholders
     * @param array $parameters Parameters
     * @param array $types Types of bind parameters
     * @throws \Exception
     * @return array
     */
    public function queryRows($query, $parameters = array(), $types = array())
    {
        if (!$this->connected) throw new \Exception('Connection not initiated');

        $stmt = $this->dbh->prepare($query);
        if (count($types)) {
            if (count($parameters) != count($types)) {
                error_log('Database error, wrong types count: ' . $query . "\n");
            }
            foreach ($parameters as $k => $v) {
                $stmt->bindParam($k, $parameters[$k], $types[$k]);
            }
            $okay = $stmt->execute();
        } else {
            $okay = $stmt->execute($parameters);
        }
        if (!$okay) {
            $err = $stmt->errorInfo();
            error_log('Database error: ' . $query . "\n" . var_export($err, TRUE));
        };
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * Выполняет запрос, возвращающий несколько строк
     *
     * @param string $query SQL with placeholders
     * @param array $parameters Parameters
     * @param array $types Types of bind parameters
     * @return \Generator
     * @throws \Exception
     */
    public function queryRowsGen($query, $parameters = array(), $types = array())
    {
        if (!$this->connected) throw new \Exception('Connection not initiated');

        $stmt = $this->dbh->prepare($query);
        if (count($types)) {
            if (count($parameters) != count($types)) {
                error_log('Database error, wrong types count: ' . $query . "\n");
            }
            foreach ($parameters as $k => $v) {
                $stmt->bindParam($k, $parameters[$k], $types[$k]);
            }
            $okay = $stmt->execute();
        } else {
            $okay = $stmt->execute($parameters);
        }
        if (!$okay) {
            $err = $stmt->errorInfo();
            error_log('Database error: ' . $query . "\n" . var_export($err, TRUE));
        };
        while (($result = $stmt->fetch(\PDO::FETCH_ASSOC))) {
            yield $result;
        }
    }


    /**
     * Выполняет запрос, изменяющий состояние базы, не возвращающий значение
     *
     * @param string $query SQL with placeholders
     * @param Array $parameters Parameters
     * @param array $types
     * @throws \Exception
     * @return bool
     */
    public function execute($query, $parameters = array(), $types = array())
    {
        if (!$this->connected) throw new \Exception('Connection not initiated');

        $stmt = $this->dbh->prepare($query);
        if (count($types)) {
            if (count($parameters) != count($types)) {
                error_log('Database error, wrong types count: ' . $query . "\n");
            }
            foreach ($parameters as $k => $v) {
                $stmt->bindParam($k, $parameters[$k], $types[$k]);
            }
            $okay = $stmt->execute();
        } else {
            $okay = $stmt->execute($parameters);
        }
        $this->affectedRowCount = $stmt->rowCount();
        if (!$okay) {
            $err = $stmt->errorInfo();
            error_log('Database error: ' . $query . "\n" . var_export($err, TRUE));
        };
        return $okay;
    }

    /**
     * Get last insert id
     *
     */
    public function lastInsertId()
    {
        if (!$this->connected) throw new \Exception('Connection not initiated');
        return $this->dbh->lastInsertId();
    }

    public function getAffectedRowCount()
    {
        if (!$this->connected) throw new \Exception('Connection not initiated');
        return $this->affectedRowCount;
    }


    /**
     * Делает маппинг результатов по указнному ключу
     *
     * @param Array $r
     * @param string $key
     * @return bool
     */
    public function mapResults($r, $key)
    {
        $result = array();
        foreach ($r as $row) {
            $result[$row[$key]] = $row;
        }
        return $result;
    }

    /**
     * Делает маппинг результатов по указнному ключу
     *
     * @param Array $r
     * @param string $key
     * @return bool
     */
    public function mapKeyValue($r, $key, $value)
    {
        $result = array();
        foreach ($r as $row) {
            $result[$row[$key]] = $row[$value];
        }
        return $result;
    }

    /**
     * Делает маппинг результатов по указнному ключу
     *
     * @param Array $r
     * @param string $key
     * @return bool
     */
    public function groupResults($r, $key)
    {
        $result = array();
        foreach ($r as $row) {
            $result[$row[$key]][] = $row;
        }
        return $result;
    }

    /**
     * Convert query results column to array
     *
     * @param array $rows
     * @param string $fieldName
     * @return array
     */
    public function convertToArray($rows, $fieldName)
    {
        $result = [];
        foreach ($rows as $row) {
            $result[] = $row[$fieldName];
        }
        return $result;
    }

    public function joinId($r, $fieldName)
    {
        $arr = array();
        foreach ($r as $row) {
            $arr[] = $row[$fieldName];
        }
        if (count($arr)) {
            return join(',', $arr);
        } else {
            return '';
        }
    }

    public function joinValues($values)
    {
        $arr = array();
        foreach ($values as $value) {
            $arr[] = $this->quote($value);
        }
        if (count($arr)) {
            return join(',', $arr);
        } else {
            return '';
        }
    }

    public function beginTransaction()
    {
        if (!$this->connected) throw new \Exception('Connection not initiated');
        $this->dbh->beginTransaction();
    }

    public function commit()
    {
        if (!$this->connected) throw new \Exception('Connection not initiated');
        $this->dbh->commit();
    }

    public function rollBack()
    {
        if (!$this->connected) throw new \Exception('Connection not initiated');
        $this->dbh->rollBack();
    }

    public function quote($text)
    {
        return $this->dbh->quote($text);
    }

    public function checkConnectivity() {
        if ($this->dbh->getAttribute(\PDO::ATTR_SERVER_INFO)=='MySQL server has gone away')
        {
            $dbOptions = array(1002 => 'SET NAMES utf8', \PDO::ATTR_PERSISTENT => false);
            $this->dbh = new \PDO($this->_dsn, $this->_username, $this->_password, $dbOptions);
        }
    }

    /**
     * Get a value from memcached
     *
     * @param string $key
     * @throws \Exception
     * @return mixed|bool
     */
    public function getValue($key)
    {
        if (!$this->connected) throw new \Exception('Connection not initiated');
        return $this->memh->get($key);
    }

    /**
     * Set value for memcached
     *
     * @param string $key
     * @param mixed $value
     * @param int $expire
     * @throws \Exception
     * @return bool
     */
    public function setValue($key, $value, $expire = 30)
    {
        if (!$this->connected) throw new \Exception('Connection not initiated');
        return $this->memh->set($key, $value, $expire);
    }

    /**
     * Delete value from memcached
     *
     * @param string $key
     * @throws \Exception
     * @return bool
     */
    public function deleteValue($key)
    {
        if (!$this->connected) throw new \Exception('Connection not initiated');
        return $this->memh->delete($key);
    }

    /**
     * Сбросить memcache
     *
     * @return bool
     */
    public function flushCache()
    {
        return $this->memh->flush();
    }
}

