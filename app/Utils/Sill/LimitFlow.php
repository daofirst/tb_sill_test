<?php


namespace App\Utils\Sill;


use App\Exceptions\LimitFlowException;
use Exception;
use Illuminate\Cache\RedisLock;
use Illuminate\Contracts\Cache\LockTimeoutException;

class LimitFlow
{

    /** @var self|null $_instance */
    private static $_instance = null;

    /**
     * @var \Illuminate\Redis\Connections\Connection $redis
     */
    private $redis;

    /**
     * @var int
     */
    private $defaultQps = 100;

    /**
     * LimitFlow constructor.
     */
    private function __construct()
    {
        $this->redis = \RedisManager::connection('default');
    }

    /**
     * getInstance
     * @return LimitFlow
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * 设置阀值
     * @param string $name
     * @param integer $qps
     * @return void
     */
    public function setQps($name, $qps)
    {
        $this->redis->command('set', ['qps:' . $name, $qps]);
    }

    /**
     * 限流
     * @param string $name
     * @param callable $callback
     * @param callable|null $failure
     * @return mixed
     * @throws LimitFlowException
     * @throws LockTimeoutException
     */
    public function throttle($name, callable $callback, callable $failure = null)
    {

    //    $lock = \Cache::lock('lock:qps_throttle:' . $name, 2);

    //    try {
    //        $lock->block(3);

            $qps = $this->redis->command('get', ['qps:' . $name]);
            if ($qps === false) {
                $qps = $this->defaultQps;
            }
            $qps = (int)$qps;

            $limitKey = 'qps_throttle:' . $name;

            $currentTime = microtime(true);
            $beforeTime = $currentTime - 1;
            $count = $this->redis->command('ZCOUNT', [$limitKey, $beforeTime, $currentTime]);

            if ($count >= $qps) {
                $e = new LimitFlowException('Limit Exceeded');
                if ($failure) {
                    return $failure($e);
                }
                throw $e;
            }

            $this->redis->command('ZADD', [$limitKey, $currentTime, \Str::uuid()->toString()]);

            return $callback();

    //    } catch (LockTimeoutException $e) {
    //        if ($failure) {
    //            return $failure($e);
    //        }
    //        throw $e;
    //    } finally {
    //        optional($lock)->release();
    //    }
    }


    /**
     * _clone
     * @throws Exception
     */
    public function __clone()
    {
        throw new Exception("This class does not allow cloning");
    }

}
