<?php

namespace NeoCube;

use NeoCube\Cache\CacheManager;
use NeoCube\Cache\CacheStoreInterface;

class Cache {

	protected CacheStoreInterface $store;

	//--$folder só se aplica ao backend em arquivo; com CACHE_DRIVER=redis
	//--ele é ignorado. Um $store explícito tem prioridade sobre os dois.
	public function __construct(
		?string $folder = null,
		protected string $time = '5 minutes',
		?CacheStoreInterface $store = null
	) {
		if (is_numeric($this->time))
			$this->time .= ' seconds';
		$this->store = $store ?? CacheManager::create($folder);
	}

	//--Converte o tempo ("10 minutes", "1 hour", ou segundos numéricos) em TTL.
	final protected function ttl(?string $time): int {
		$expires = ($time)
			? (is_numeric($time) ? strtotime($time . ' seconds') : strtotime($time))
			: strtotime($this->time);
		return max(1, (int) $expires - time());
	}

	final public function save(string $key, mixed $content, ?string $time = null): bool {
		return $this->store->set($key, $content, $this->ttl($time));
	}

	final public function read(string $key): mixed {
		return $this->store->get($key);
	}

	final public function clear(string $key): bool {
		return $this->store->delete($key);
	}
}
