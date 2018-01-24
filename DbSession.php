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
class DbSession implements \SessionHandlerInterface
{
    /**
     * session 数据库表名
     * @var string
     */
    protected $sessionTable = '';
    
    /**
     * Session有效时间
     */
    protected $maxLifeTime;
    
    /**
     * 数据库句柄
     */
    protected $connectHandle = NULL;
    
    /**
     * 初始化session数据库链接配置
     * @param array $config
     */
    public function __construct($connectHandle, Array $config)
    {
        $this->connectHandle = $connectHandle;
        $this->sessionTable = $config['db_table'] ? $config['db_prefix'].$config['db_table'] : $config['db_prefix'].'session';
        $this->maxLifeTime = $config['expire'] ? $config['expire'] : ini_get('session.gc_maxlifetime');
        ini_set('session.save_handler','user');
        session_set_save_handler(
            array(&$this,"open"),
            array(&$this,"close"),
            array(&$this,"read"),
            array(&$this,"write"),
            array(&$this,"destroy"),
            array(&$this,"gc")
        );
        session_start();
    }

    /**
     * 初始化Session db数据库连接和选库
     * {@inheritDoc}
     * @see SessionHandlerInterface::open()
     */
    public function open($save_path, $session_name) 
    {
       return true;
    }

    /**
     * 关闭session db数据库连接 
     * {@inheritDoc}
     * @see SessionHandlerInterface::close()
     */
    public function close() 
    {
       $this->gc($this->maxLifeTime); 
       return $this->connectHandle = null;
    }
    
    /**
     * 读session数据
     * {@inheritDoc}
     * @see SessionHandlerInterface::read()
     */
    public function read($session_id)
    {
        $res = [];
        $sql = sprintf("SELECT * FROM `%s` WHERE session_id = '%s' AND session_expire > '%s'", $this->sessionTable, $session_id, time());
        L($sql,'sql');
        $result = $this->connectHandle->query($sql, \PDO::FETCH_ASSOC);
        if ($result){
            foreach ($result as $v) {
                $res[] = $v;
            }
        }
        return $res;
    }
    
    /**
     * 写入seesion数据
     * {@inheritDoc}
     * @see SessionHandlerInterface::write()
     */
    public function write($session_id, $session_data)
    {
        $session_expire = intval(time()) + intval($this->maxLifeTime);
        $sql = sprintf("INSERT INTO `%s` (`session_id`, `session_data`, `session_expire`) VALUES ('%s', '%s', '%s') ON DUPLICATE KEY UPDATE session_data = '%s', session_expire = '%s'", $this->sessionTable,$session_id, $session_data, $session_expire, $session_data, $session_expire);
        L($sql,'sql');
        if ($this->connectHandle->exec($sql)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 销毁一个session数据
     * {@inheritDoc}
     * @see SessionHandlerInterface::destroy()
     */
    public function destroy($session_id)
    {
        $sql = sprintf("DELETE FROM `%s` WHERE session_id = '%s'", $this->sessionTable, $session_id);
        L($sql,'sql');
        if ($this->connectHandle->exec($sql)){
            return true;
        }else {
            return false;
        }
    }
    
    /**
     * 回收过期的session
     * {@inheritDoc}
     * @see SessionHandlerInterface::gc()
     */
    public function gc($maxlifetime) 
    {
        $sql = sprintf("DELETE FROM `%s` WHERE session_expire < '%s'", $this->sessionTable,time());
        L($sql,'sql');
        if ($this->connectHandle->exec($sql)){
            return true;
        } else {
            return false;
        }
    }
}