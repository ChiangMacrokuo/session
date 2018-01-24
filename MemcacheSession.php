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
class MemcacheSession implements \SessionHandlerInterface
{
    /**
     * Session有效时间
     */
    protected $maxLifeTime;
    
    /**
     * 数据库句柄
     */
    protected $connectHandle;
    
    /**
     * 初始化session数据库链接配置
     * @param array $config
     */
    public function __construct($connectHandle, Array $config)
    {
        $this->connectHandle = $connectHandle;
        $this->maxLifeTime = $config['expire'] ? $config['expire'] : ini_get('session.gc_maxlifetime');
        ini_set('session.save_handler', 'memcache');
        session_set_save_handler(
            array(&$this, 'open'),
            array(&$this, 'close'),
            array(&$this, 'read'),
            array(&$this, 'write'),
            array(&$this, 'destroy'),
            array(&$this, 'gc')
        );
        session_start();
    }
    
    /**
     * 初始化session Memcache连接
     * {@inheritDoc}
     * @see SessionHandlerInterface::open()
     */
    public function open()
    {
        return true;
    }
    
    /**
     * 关闭session Memcache连接 
     * {@inheritDoc}
     * @see SessionHandlerInterface::close()
     */
    public function close()
    {
        return $this->connectHandle->close();
    }
    
    /**
     * 写入或者修改session数据
     * {@inheritDoc}
     * @see SessionHandlerInterface::write()
     */
    public function write($session_id, $session_data){
        $method = $session_data ? 'set' : 'replace';
        $session_expire = intval(time()) + intval($this->maxLifeTime);
        return $this->connectHandle->$method($session_id, $session_data, MEMCACHE_COMPRESSED, $session_expire);
    }
    
    
    /**
     * 读session数据
     * {@inheritDoc}
     * @see SessionHandlerInterface::read()
     */
    public function read($session_id)
    {
        return $this->connectHandle->get($session_id);
    }
    
    /**
     * 销毁一个session数据
     * {@inheritDoc}
     * @see SessionHandlerInterface::destroy()
     */
    public function destroy($session_id)
    {
        return $this->connectHandle->delete($session_id);
    }
    
    /**
     * 回收过期的session
     * {@inheritDoc}
     * @see SessionHandlerInterface::gc()
     */
    public function gc($maxlifetime)
    {
        return true;
    }
}