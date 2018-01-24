<?php
/**********************************************************************
 * JUST LOVE EIPHP
 ***********************************************************************
 * Copyright (c) 2017 http://www.eiphp.com All rights reserved.
 ***********************************************************************
 * Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
 ***********************************************************************
 * Author: ChiangMacrokuo <420921698@qq.com>
 ***********************************************************************/
namespace Kernel\Session;
use Kernel\Exception\KernelException;
class SessionHandler
{
    /**
     * 单例对象
     * @var object
     */
    private static $instance = NULL;
    /**
     * session链接句柄
     * @var resource
     */
    public static $connectHandle = NULL;
    
    /**
     * session驱动句柄
     * @var object
     */
    public static $sessionHandler = null;
    
    /**
     * 初始化配置参数
     */
    private function __construct()
    {
        $sessionConfig = C('session');
        $sessionType = '\Kernel\Session\\'.ucwords($sessionConfig['type']).'Session';
        switch ($sessionConfig['type']){
            case 'db':
                $dsn = "mysql:dbname={$sessionConfig['db_name']};host={$sessionConfig['db_host']};";
                self::$connectHandle = new \PDO($dsn, $sessionConfig['db_username'], $sessionConfig['db_password'], array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$sessionConfig['db_character']};"));
                if (!self::$connectHandle){
                    throw new KernelException('SESSION 数据库链接失败！');
                }
                break;
            case 'redis':
                if (!class_exists('redis',false)){
                    throw new KernelException('缺少Redis扩张！');
                }
                self::$connectHandle = new \Redis();
                self::$connectHandle->connect(self::$sessionConfig['redis_host'], self::$sessionConfig['redis_port']);
                if (!self::$connectHandle){
                    throw new KernelException('SESSION 连接Redis主机失败！');
                }
                if (!empty(self::$sessionConfig['redis_password'])) {
                    self::$connectHandle->auth(self::$sessionConfig['redis_password']);
                }
                if (!empty(self::$sessionConfig['redis_dbname'])){
                    self::$connectHandle->select(intval(self::$sessionConfig['redis_dbname']));
                }
                break;
            case 'memcache':
                if (!class_exists('memcache')){
                    throw new KernelException('缺少Memcache扩张！');
                }
                $this->connectHandle = new \Memcache();
                $this->connectHandle->connect($this->sessionDbConfig['memcache_host'], $this->sessionDbConfig['memcache_port']);
                if (!$this->connectHandle){
                    throw new KernelException('SESSION 连接Memcache主机失败！');
                }
                break;
        }
        self::$sessionHandler = new $sessionType(self::$connectHandle,$sessionConfig);
    }
    
    /**
     * 获取单例
     * @return object
     */
    public static function getInstance()
    {
        if (is_null(self::$instance) || !self::$instance instanceof self){
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 写入session
     * @see \Kernel\Session\SessionInterface::write()
     */
    public static function write($session_id, $session_data)
    {
        return self::$sessionHandler->write($session_id, $session_data);
    }
    
    /**
     * 读取session
     * @see \Kernel\Session\SessionInterface::read()
     */
    public static function read($session_id)
    {
        return self::$sessionHandler->read($session_id);
    }
    
    /**
     * 删除指定session
     * @see \Kernel\Session\SessionInterface::destroy()
     */
    public static function destroy($session_id)
    {
        return self::$sessionHandler->destroy($session_id);
    }
 }