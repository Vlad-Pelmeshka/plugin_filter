<?php namespace Premmerce\Filter\Cache;

class Cache
{
    /**
     * Cache Dir
     *
     * @var string
     */
    private $cacheDir;

    /**
     * Cache constructor.
     */
    public function __construct()
    {
        $this->cacheDir = wp_upload_dir()['basedir'] . '/cache/premmerce_filter/';
    }

    /**
     * Get Cache Dir
     *
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * Get cached data
     *
     * @param string $name
     *
     * @return array
     */
    public function get($name)
    {
        $file = $this->cacheDir . $name;

        if (file_exists($file)) {
            $cache = file_get_contents($file);
            if ($cache) {
                $cache = json_decode($cache, true);
                if ($cache) {
                    return $cache;
                }
            }
        }
    }

    /**
     * Set data
     *
     * @param string $name
     * @param array  $value
     *
     * @return bool|int
     */
    public function set($name, $value)
    {
        $file = $this->cacheDir . $name;

        if (! file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
            chmod($this->cacheDir, 0777);
        }


        $result = file_put_contents($file, json_encode($value));

        return $result;
    }

    /**
     * Clear cache files
     */
    public function clear()
    {
        foreach (glob($this->cacheDir . '/*') as $file) {
            unlink($file);
        }
    }
}
