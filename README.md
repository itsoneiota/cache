One iota Cache Library
======================

Overview
--------
A simple interface to cache sources.

The `Cache` class wraps a `Memcached` instance, adding key and value mapping, and default expiry times.

Installation
------------
The best way to autoload this package and its dependencies is to include the standard Composer autoloader, `vendor/autoload.php`.

Testing
-------
The library's suite of unit tests can be run by calling `vendor/bin/phpunit` from the root of the repository.

Basic Usage
-----------
	
	use \itsoneiota\cache;

	$mc = new \Memcached();
	// Configure memcached…

	$cache = new Cache($mc);

	$cache->set('foo', 'bar'); // Caches a value of 'bar' against the key of 'foo'.
	$cache->get('foo'); // Returns 'bar'.

Prefixing Cache Keys
--------------------

If you wish to avoid cache key collisions, you can initialise your cache with a key prefix, this will be added to all keys when getting, setting and deleting. For example:

	use \itsoneiota\cache;

	$mc = new \Memcached();
	// Configure memcached…

	$cache = new Cache($mc, 'myStore');

	$cache->set('foo', 'bar'); // Caches a value of 'bar' against the key of 'myStore.foo'.
	$cache->get('foo'); // Returns 'bar'.

Default Expiry Times
--------------------

When creating a cache, you can specify a default time to live that can be used when adding and setting. This can be overridden when adding and setting by specifying an explicit expiry time.

	use \itsoneiota\cache;

	$mc = new \Memcached();
	// Configure memcached…

	$cache = new Cache($mc, 'myStore', 120);

	$cache->set('foo', 'bar'); // Caches for 2 minutes (the default).
	$cache->set('bat', 'baz', 30); // Caches for 30 seconds.
	
Encrypting Cache Contents
-------------------------

`SecureCache` is a subclass of `Cache` that encrypts its contents using two-way encryption.

	use \itsoneiota\cache;

	$mc = new \Memcached();
	// Configure memcached…

	$cache = new SecureCache($mc, 'myTopSecretEncryptionKey');

If you need to prefix keys in SecureCache, you can always call `$cache->setKeyPrefix('myPrefix')`.

Bonus Caches (Cacheback)
------------------------

### `InMemoryCacheFront`
If you're likely to make several calls to a cache within a request, possibly to the same value, `InMemoryCacheFront` can prevent unnecessary network calls to the Memcached server, by providing a read- and write-through cache on top of a `Cache` instance. The number of items held in memory is limited to 100 by default, but that can be changed with a constructor argument.

	use \itsoneiota\cache;

	$mc = new \Memcached();
	// Configure memcached…

	$cache = new Cache($mc, 'myStore', 120);

	$superFastCache = new InMemoryCacheFront($cache, 1000); // Store 1000 items in local memory.

### `InMemoryCache`
If you need to simulate a cache, without a Memcached server, `InMemoryCache` will do the job. It looks just like a `Cache`, but it holds everything in a plain-old PHP array.

### `MockCache`
For testing purposes, `MockCache` simulates a cache, and allows you to check that values have been set, and what their expiry times are, without having to go through all the hassle of using PHPUnit mocks. `getExpiration()` allows you to check the expiration of a key. `timePasses()` allows you to simulate the passage of time, advancing by a given number of seconds and expiring cache items accordingly.

	use \itsoneiota\cache;

	$cache = new MockCache();

	$cache->set('foo', 'bar', 60);
	$cache->get('foo'); // Returns 'bar'.
	
	$cache->timePasses(61);
	
	$cache->get('foo'); // Returns NULL.

To Do…
------

Things I might add, soon-ish:

- Check-and-set operations
- Cache pile-on prevention