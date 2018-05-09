<?php
namespace Core\Web\Debug\Lib;

class Profile
{
    const NO_PARENT = '__NO_PARENT__';

    private $data;

    private $keys = [
        'ct', // 调用次数
        'wt', // 调用的包括子函数所有花费时间，微秒
        'cpu', // 调用的包括子函数所有花费的cpu时间，微秒
        'mu',  // 包括子函数执行使用的内存，字节
        'pmu',  // 内存峰值
    ];

    private $indexed = [];

    private $collapsed = [];

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->process();
        $this->calculateSelf();
    }

    public function getMeta()
    {
        return $this->data['meta'];
    }

    /**
     * 返回SQL日志
     *
     * @return array|mixed
     */
    public function getSqlLogs()
    {
        return isset($this->data['sql']) ? $this->data['sql'] : [];
    }

    /**
     * 返回分析结果
     *
     * @return array
     */
    public function getProfile()
    {
        return $this->collapsed;
    }

    /**
     * 根据指标排序
     *
     * @param string $key 指标
     * @param array $data
     * @return mixed
     */
    public function sort($key, $data)
    {
        $sorter = function ($a, $b) use ($key) {
            if ($a[$key] == $b[$key]) {
                return 0;
            }
            return $a[$key] > $b[$key] ? -1 : 1;
        };
        uasort($data, $sorter);
        return $data;
    }

    private function process()
    {
        $result = [];
        foreach ($this->data['profile'] as $name => $values) {
            list($parent, $func) = $this->splitName($name);

            if (isset($result[$func])) {
                $result[$func] = $this->sumKeys($result[$func], $values);
                $result[$func]['parents'][] = $parent;
            } else {
                $result[$func] = $values;
                $result[$func]['parents'] = [$parent];
            }

            // main()函数
            if ($parent === null) {
                $parent = self::NO_PARENT;
            }
            if (!isset($this->indexed[$parent])) {
                $this->indexed[$parent] = [];
            }
            $this->indexed[$parent][$func] = $values;
        }
        $this->collapsed = $result;
    }

    private function splitName($name)
    {
        $a = explode("==>", $name);
        if (isset($a[1])) {
            return $a;
        }
        return [null, $a[0]];
    }

    private function sumKeys($a, $b)
    {
        foreach ($this->keys as $key) {
            if (!isset($a[$key])) {
                $a[$key] = 0;
            }
            $a[$key] += isset($b[$key]) ? $b[$key] : 0;
        }
        return $a;
    }

    protected function getChildren($symbol, $metric = null, $threshold = 0)
    {
        $children = [];
        if (!isset($this->indexed[$symbol])) {
            return $children;
        }

        $total = 0;
        if (isset($metric)) {
            $top = $this->indexed[self::NO_PARENT];
            // Not always 'main()'
            $mainFunc = current($top);
            $total = $mainFunc[$metric];
        }

        foreach ($this->indexed[$symbol] as $name => $data) {
            if (
                $metric && $total > 0 && $threshold > 0 &&
                ($this->indexed[$name][$metric] / $total) < $threshold
            ) {
                continue;
            }
            $children[] = $data + ['function' => $name];
        }
        return $children;
    }

    private function calculateSelf()
    {
        // 初始化函数自身的各项执行指标
        foreach ($this->collapsed as &$data) {
            $data['ewt'] = $data['wt'];
            $data['emu'] = $data['mu'];
            $data['ecpu'] = $data['cpu'];
            $data['ect'] = $data['ct'];
            $data['epmu'] = $data['pmu'];
        }
        unset($data);

        // 总值 - 所有子函数的值 = 自身的值
        foreach ($this->collapsed as $name => $data) {
            $children = $this->getChildren($name);
            foreach ($children as $child) {
                $this->collapsed[$name]['ewt'] -= $child['wt'];
                $this->collapsed[$name]['emu'] -= $child['mu'];
                $this->collapsed[$name]['ecpu'] -= $child['cpu'];
                $this->collapsed[$name]['ect'] -= $child['ct'];
                $this->collapsed[$name]['epmu'] -= $child['pmu'];
            }
        }
    }
}