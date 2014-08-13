<?php

/**
 * @package core
 */

 /**
  * The Cacheable class is used to store data in the cache provided by a provider.
  * It can be used by extensions and the core to store frequently accessed pieces of data.
  */

class Cacheable
{
    /**
     * An instance of the Cache provider class to communicate with the cache.
     *
     * @var iCache
     */
    private $cacheProvider = null;

    /**
     * @param iCache $cacheProvider
     *  An instance of a cacheprovider. This provider will be used for data storage.
     */
    public function __construct(iCache $cacheProvider = null)
    {
        if (is_null($cacheProvider)) {
            $default_provider = Symphony::Configuration()->get('default', 'cache_driver');
            // TO DO
            $cacheProvider = CacheProviderManager::create($default_provider);
        }
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * Returns the type of the internal caching provider
     *
     * @since Symphony 2.4
     * @return string
     */
    public function getType()
    {
        return get_class($this->cacheProvider);
    }

    /**
     * This function will compress data for storage in the cache store.
     * It is left to the user to define a unique hash for this data so that it can be
     * retrieved in the future. Optionally, a `$ttl` parameter can
     * be passed for this data. If this is omitted, it data is considered to be valid
     * forever.
     *
     * @param string $hash
     *  The hash of the Cached object, as defined by the user
     * @param string $data
     *  The data to be cached, this will be compressed prior to saving.
     * @param integer $ttl
     *  A integer representing how long the data should be valid for in minutes.
     *  By default this is null, meaning the data is valid forever
     * @return boolean
     *  If an error occurs, this function will return false otherwise true
     */
    public function write($hash, $data, $ttl = null)
    {
        return $this->cacheProvider->write($hash, $data, $ttl);
    }

    /**
     * Given the hash of a some data, check to see whether it exists in
     * the cache store. If no cached object is found, this function will return
     * false, otherwise the cached object will be returned as an array.
     *
     * @param string $hash
     *  The hash of the Cached object, as defined by the user
     * @return array|boolean
     *  An associative array of the cached object including the creation time,
     *  expiry time, the hash and the data. If the object is not found, false will
     *  be returned.
     */
    public function read($hash)
    {
        return $this->cacheProvider->read($hash);
    }

    /**
     * Given the hash of a cacheable object, remove it from the cache
     * regardless of if it has expired or not.
     *
     * @param string $hash
     *  The hash of the Cached object, as defined by the user
     */
    public function delete($hash = null)
    {
        return $this->cacheProvider->delete($hash);
    }

/*-------------------------------------------------------------------------
    Utilities:
-------------------------------------------------------------------------*/

    /**
     * Given some data, this function will compress it using `gzcompress`
     * and then the result is run through `base64_encode` If this fails,
     * false is returned otherwise the compressed data
     *
     * @param string $data
     *  The data to compress
     * @return string|boolean
     *  The compressed data, or false if an error occurred
     */
    public static function compressData($data)
    {
        if (!$data = base64_encode(gzcompress($data))) {
            return false;
        }

        return $data;
    }

    /**
     * Given compressed data, this function will decompress it and return
     * the output.
     *
     * @param string $data
     *  The data to decompress
     * @return string|boolean
     *  The decompressed data, or false if an error occurred
     */
    public static function decompressData($data)
    {
        if (!$data = gzuncompress(base64_decode($data))) {
            return false;
        }

        return $data;
    }

/*-------------------------------------------------------------------------
    Deprecated:
-------------------------------------------------------------------------*/

    /**
     * Given the hash of a some data, check to see whether it exists in
     * `tbl_cache`. If no cached object is found, this function will return
     * false, otherwise the cached object will be returned as an array.
     *
     * @deprecated This function will be removed in the next major
     *  version of Symphony. Use `read()` instead.
     * @param string $hash
     *  The hash of the Cached object, as defined by the user
     * @return array|boolean
     *  An associative array of the cached object including the creation time,
     *  expiry time, the hash and the data. If the object is not found, false will
     *  be returned.
     */
    public function check($hash)
    {
        return $this->read($hash);
    }

    /**
     * Given the hash of a cacheable object, remove it from `tbl_cache`
     * regardless of if it has expired or not.
     *
     * @deprecated This function will be removed in the next major
     *  version of Symphony. Use `delete()` instead.
     * @param string $hash
     *  The hash of the Cached object, as defined by the user
     */
    public function forceExpiry($hash)
    {
        return $this->delete($hash);
    }

    /**
     * Removes all cache objects from `tbl_cache` that have expired.
     * After removing, the function uses the optimise function
     *
     * @deprecated This function will be removed in the next major
     *  version of Symphony. Use `delete()` instead.
     * @see core.Cacheable#optimise()
     */
    public function clean()
    {
        return $this->delete($hash);
    }
}
