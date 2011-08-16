<?php
/**
 * @see Zend_Cache_Backend
 */
//require_once 'Zend/Cache/Backend.php';

/**
 * @see Zend_Cache_Backend_ExtendedInterface
 */
//require_once 'Zend/Cache/Backend/ExtendedInterface.php';


/**
 * @author	   Olivier Bregeras (Stunti) (olivier.bregeras@gmail.com)
 * @category   Stunti
 * @package    Stunti_Cache
 * @subpackage Stunti_Cache_Backend
 * @copyright  Copyright (c) 2009 Stunti. (http://www.stunti.org)
 * @license    http://stunti.org/license/new-bsd     New BSD License
 */
class Stunti_Cache_Backend_Redis extends Zend_Cache_Backend 
{

    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT =  6379;
    const DEFAULT_TIMEOUT = 1;

    const DELIMITER = "\r\n";
    
   /**
     * Log message
     */
    const TAGS_UNSUPPORTED_BY_CLEAN_OF_REDIS_BACKEND = 'Zend_Cache_Backend_Redis::clean() : tags are unsupported by the Redis backend';
    const TAGS_UNSUPPORTED_BY_SAVE_OF_REDIS_BACKEND =  'Zend_Cache_Backend_Redis::save() : tags are unsupported by the Redis backend';
    
    

    
    /**
     * Available options
     *
     * =====> (array) servers :
     * an array of mongodb server ; each mongodb server is described by an associative array :
     * 'host' => (string) : the name of the mongodb server
     * 'port' => (int) : the port of the mongodb server
     * 'persistent' => (bool) : use or not persistent connections to this mongodb server
     * 'collection' => (string) : name of the collection to use
     * 'dbname' => (string) : name of the database to use
     *
     * @var array available options
     */
    protected $_options = array(
        'host' => self::DEFAULT_HOST,
        'port' => self::DEFAULT_PORT,
        'timeout' => self::DEFAULT_TIMEOUT,
        'lifetime' => 0,
        'prefix' => '',
    );
    
    /**
     * Socket
     *
     * @var resource
     */
    protected $_connection = null;

    protected $_lifetime = null;

    protected $_prefix = null;
    
    /**
     * Redis object
     *
     * @var resource
     */
    protected $_redis = null;    
    
    /**
     * @return void
     */
    public function __construct($options)
    {
        if (!extension_loaded('redis')) {
            Zend_Cache::throwException('The MongoDB extension must be loaded for using this backend !');
        }
        if (!empty($options['lifetime'])) {
            $this->_lifetime = $options['lifetime'];
        }
        if (!empty($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }
        parent::__construct($options);
        $this->_connect();
    }
    
    public function __destruct()
    {
        if (is_resource($this->_connection)) {
            fclose($this->_connection);
        }
    }    
    
    /**
     * @return void
     */
    protected function _connect()
    {
        if ($this->_connection && $this->_redis) {
            return ;
        }
        /*
        $this->_connection = stream_socket_client('tcp://' . $this->_options['host'] . ':' . $this->_options['port'],
            $errno, $errstr, $this->_options['timeout']);
         */
        $this->_redis = new Redis();
        $this->_connection = $this->_redis->connect($this->_options['host'], $this->_options['port']);
        
    }    
    
    public function save($data, $id, $tags = array(), $specificLifetime = false)
    {
        $lifetime = $specificLifetime;
        if (empty($lifetime) && !empty($this->_lifetime)) {
            $lifetime = $this->_lifetime;
        }
        if (!empty($this->_prefix)) {
            $id = $this->_prefix . '-'.$id;
        }
        $result = $this->_redis->set($id, $data);
        /*
        $lengthId = strlen($id);
        $lengthData = strlen($data);
        $result = $this->_call('*3' . self::DELIMITER
            . '$3' . self::DELIMITER . 'SET' . self::DELIMITER .
            '$' . $lengthId . self::DELIMITER . $id . self::DELIMITER .
            '$' . $lengthData . self::DELIMITER . $data . self::DELIMITER);
         * 
         */
        if ($lifetime) {
            $this->_redis->setTimeout($id, $lifetime);
            //$this->_call('EXPIRE '. $id . ' ' . $lifetime . self::DELIMITER);
        }

        if (count($tags) > 0) {
            $this->_log(self::TAGS_UNSUPPORTED_BY_SAVE_OF_REDIS_BACKEND);
        }

        return $result;
    }

    public function remove($id)
    {
        if (!empty($this->_prefix)) {
            $id = $this->_prefix . '-'.$id;
        }
        return $this->_redis->del($id);
        //return $this->_call('DEL ' . $id . self::DELIMITER);
    }

    public function test($id)
    {
        if (!empty($this->_prefix)) {
            $id = $this->_prefix . '-'.$id;
        }
        return $this->_redis->exists($id);
        //return $this->_call('EXISTS ' . $id . self::DELIMITER);
    }

    public function load($id, $doNotTestCacheValidity = false)
    {
        if (!empty($this->_prefix)) {
            $id = $this->_prefix . '-'.$id;
        }
        return $this->_redis->get($id);
        //return $this->_call('GET '. $id . self::DELIMITER);
    }

    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        switch ($mode) {
            case Zend_Cache::CLEANING_MODE_ALL:
                //return $this->_call('FLUSHDB' . self::DELIMITER);
                return $this_redis->flushDB();
                break;
            case Zend_Cache::CLEANING_MODE_OLD:
                $this->_log("Zend_Cache_Backend_Redis::clean() : CLEANING_MODE_OLD is unsupported by the Redis backend");
                break;
            case Zend_Cache::CLEANING_MODE_MATCHING_TAG:
            case Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG:
            case Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG:
                $this->_log(self::TAGS_UNSUPPORTED_BY_CLEAN_OF_REDIS_BACKEND);
                break;
               default:
                throw new \Exception('Invalid mode for clean() method');
                   break;
        }
    }

    

    public function ___expire()
    {}
}
