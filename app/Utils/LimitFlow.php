<?php


namespace App\Utils;


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
    private $defaultQps = 50;

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
        $qps = $this->redis->command('get', ['qps:' . $name]);
        if ($qps === false) {
            $qps = $this->defaultQps;
        }
        
        $qps = (int)$qps;

        $limitKey = 'qps_throttle:' . $name;

        $currentTime = microtime(true);
        $beforeTime = $currentTime - 1;
        
        $member = \Str::uuid()->toString();

        $res = $this->redis->eval($this->zaddLua(), 6, $limitKey, 'min', 'max', 'qps', 'score', 'member', '', $beforeTime, $currentTime, $qps, $currentTime, $member);

        if (!$res) {
            $e = new LimitFlowException('Limit Exceeded');
            if ($failure) {
                return $failure($e);
            }
            throw $e;
        }

        return $callback();
    }

    /**
     * 获取Lua脚本以原子方式添加成员.
     * KEYS[1] 有序集合的名称
     * ARGV[1] 空字符
     * KEYS[2] min
     * ARGV[2] 最小分数值
     * KEYS[3] max
     * ARGV[3] 最大分数值
     * KEYS[4] qps
     * ARGV[4] qps值
     * KEYS[5] score
     * ARGV[5] 要添加的成员的分数
     * KEYS[6] member
     * ARGV[6] 要添加的成员
     * @return string
     */
    public static function zaddLua()
    {

        return <<<'LUA'
if redis.call("zcount",KEYS[1], ARGV[2], ARGV[3]) < tonumber(ARGV[4]) then
    return redis.call("zadd",KEYS[1], ARGV[5], ARGV[6])
else
    return 0
end
LUA;
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
