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
class RedisSession implements \SessionHandlerInterface
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
        ini_set('session.save_handler', 'redis');
        session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );
        session_start();
    }
    
    /**
     * 初始化session redis连接
     * {@inheritDoc}
     * @see SessionHandlerInterface::open()
     */
    public function open()
    {
        return true;
    }
    
    /**
     * 关闭session redis连接
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
        $session_expire = intval(time()) + intval($this->maxLifeTime);
        return $this->connectHandle->setex($session_id, $session_expire, $session_data);
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
        return $this->connectHandle->del($session_id);
    }
    
    /**
     * 回收过期的session数据
     * {@inheritDoc}
     * @see SessionHandlerInterface::gc()
     */
    public function gc($maxlifetime)
    {
        $keys = $this->connectHandle->keys("*");
        if (!empty($keys)){
            $this->connectHandle->delete($keys);
        }
        return true;
    }
    
    /**
     * 初始化系统sessin配置
     * @return void
     */
    public function execute()
    {

    }
}