<?php
/**
 * This file is part of the Ikarus Framework.
 * The Ikarus Framework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * The Ikarus Framework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public License
 * along with the Ikarus Framework. If not, see <http://www.gnu.org/licenses/>.
 */
namespace ikarus\system\cache\adapter;

use Couchbase;
use ikarus\system\exception\cache\ConnectionException;

/**
 * Provides a cache adapter which stores it's contents on a Couchbase server.
 * @author                    Johannes Donath
 * @copyright                 2012 Evil-Co.de
 * @package                   de.ikarus-framework.core
 * @subpackage                system
 * @category                  Ikarus Framework
 * @license                   GNU Lesser Public License <http://www.gnu.org/licenses/lgpl.txt>
 * @version                   2.0.0-0001
 * @todo                      Check for bugs
 */
class CouchbaseAdapter implements ICacheAdapter {

	/**
	 * This string is used to test the connection.
	 * @var                        string
	 */
	const TEST_STRING = 'This cache item was created by Satan';

	/**
	 * Contains all stored cache resources.
	 * @var                        array
	 */
	protected $cacheResources = array();

	/**
	 * Contains the couchbase connection instance.
	 * @var                        Memcache
	 */
	protected $couchbase = null;

	/**
	 * @see ikarus\system\cache\adapter.ICacheAdapter::__construct()
	 */
	public function __construct (array $adapterParameters = array()) {
		// validate server list
		if (!isset($adapterParameters['server']) and !isset($adapterParameters['serverList'])) throw new ConnectionException("No server for couchbase connection specified");
		if (isset($adapterParameters['server']) and !isset($adapterParameters['bucket'])) throw new ConnectionException("No information about connection's bucket specified");

		// split serverList
		if (isset($adapterParameters['serverList'])) {
			// create cache instance
			$this->couchbase = new Couchbase();

			foreach ($adapterParameters['serverList'] as $server) {
				// split server details
				list($hostname, $port, $weight) = explode (':', $server);

				// add server to connection pool
				$this->couchbase->addServer ($hostname, intval ($port), $weight);
			}
		} else
			// create instance
			$this->couchbase = new Couchbase($adapterParameters['server'], (isset($adapterParameters['username']) ? $adapterParameters['username'] : null), (isset($adapterParameters['password']) ? $adapterParameters['password'] : null), $adapterParameters['bucket']);

		// check connection
		@$this->couchbase->add ('test', serialize (array('creationTimestamp' => TIME_NOW, 'content' => static::TEST_STRING)));
		if (@$this->couchbase->get ('test', MEMCACHE_COMPRESSED) != static::TEST_STRING) throw new ConnectionException("Cannot create a correct couchbase connection");
	}

	/**
	 * Checks wheter a cache entry should be rebuilt.
	 * @param                        string  $cacheKey
	 * @param                        string  $cacheBuilderClass
	 * @param                        integer $minimalLifetime
	 * @param                        integer $maximalLifetime
	 * @return                        boolean
	 */
	protected function cacheNeedsRebuild ($cacheKey, $cacheBuilderClass, $minimalLifetime, $maximalLifetime) {
		// non-existant cache
		if ($this->couchbase->get ($cacheKey) === false) true;

		// check lifetime
		$content = unserialize ($this->couchbase->get ($cacheKey));

		// minimal lifetime
		if ($content['creationTimestamp'] + $minimalLifetime > TIME_NOW) return false;

		// maximal lifetime
		if ($content['creationTimestamp'] + $maximalLifetime < TIME_NOW) return true;

		// all ok
		return false;
	}

	/**
	 * @see ikarus\system\cache\adapter.ICacheAdapter::createResource()
	 */
	public function createResource ($resourceName, $cacheBuilderClass, $minimalLifetime = 0, $maximalLifetime = 0, array $additionalCacheBuilderParameters = array()) {
		try {
			$this->storeCacheResource ($resourceName, $this->loadCache ($resourceName, $cacheBuilderClass, $minimalLifetime, $maximalLifetime));
		} Catch (SystemException $ex) {
			$this->storeCacheResource ($resourceName, $this->storeCacheData ($resourceName, $this->getCacheData ($cacheBuilderClass, $resourceName, $additionalCacheBuilderParameters)));
		}

		return true;
	}

	/**
	 * @see ikarus\system\cache\adapter.ICacheAdapter::get()
	 */
	public function get ($resourceName) {
		// validate cache resource
		if (!array_key_exists ($resourceName, $this->cacheResources)) throw new StrictStandardException("Tried to access unknown cache resource '%s'", $resourceName);

		// return data
		return $this->cacheResources[$resourceName];
	}

	/**
	 * Reads data from cache builders.
	 * @param                        string $cacheBuilderClass
	 * @param                        string $resourceName
	 * @return                        mixed
	 */
	protected function getCacheData ($cacheBuilderClass, $resourceName, $additionalCacheBuilderParameters) {
		// validate class
		if (!class_exists ($cacheBuilderClass, true)) throw new SystemException("Cannot use cache builder class '%s': The class does not exist!", $cacheBuilderClass);

		// load data
		return call_user_func (array($cacheBuilderClass, 'getData'), $resourceName, $additionalCacheBuilderParameters);
	}

	/**
	 * @see ikarus\system\cache\adapter.ICacheAdapter::isSupported()
	 */
	public static function isSupported () {
		return (class_exists ('Couchbase'));
	}

	/**
	 * Loads cache data from memcache.
	 * @param                        string  $cacheKey
	 * @param                        string  $cacheBuilderClass
	 * @param                        integer $minimalLifetime
	 * @param                        integer $maximalLifetime
	 * @throws                        SystemException
	 * @returns                        mixed
	 */
	protected function loadCache ($cacheKey, $cacheBuilderClass, $minimalLifetime, $maximalLifetime) {
		// rebuild if needed
		if ($this->cacheNeedsRebuild ($cacheKey, $cacheBuilderClass, $minimalLifetime, $maximalLifetime)) throw new SystemException("A rebuild is needed for the cache item '%s'", $cacheKey);

		// load information from server
		$cacheContent = unserialize ($this->couchbase->get ($cacheKey));

		// return cache content
		return $cacheContent['content'];
	}

	/**
	 * @see ikarus\system\cache\adapter.ICacheAdapter::shutdown()
	 */
	public function shutdown () {
	}

	/**
	 * Stores data on memcached servers.
	 * @param                        string $cacheKey
	 * @param                        mixed  $data
	 * @return                        mixed
	 */
	protected function storeCacheData ($cacheKey, $data) {
		$this->couchbase->add ($cacheKey, serialize (array('creationTimestamp' => TIME_NOW, 'content' => $data)));

		return $data;
	}

	/**
	 * Stores cache data for this script instance.
	 * @param                        string $resourceName
	 * @param                        mixed  $content
	 * @return                        void
	 */
	protected function storeCacheResource ($resourceName, $content) {
		$this->cacheResources[$resourceName] = $content;
	}
}

?>