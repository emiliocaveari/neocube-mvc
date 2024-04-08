<?php

namespace NeoCube;

class Cache {


	public function __construct(
		protected ?string $folder = null,
		protected string $time = '5 minutes'
	) {
		if (!$this->folder)
			$this->folder = Env::getValue('CACHE_PATH') ?: sys_get_temp_dir();
		if (is_numeric($this->time)) 
			$this->time .= ' minutes';
	}

	final protected function generateFileLocation(string $key): string {
		return $this->folder . DIRECTORY_SEPARATOR . sha1($key) . '.tmp';
	}

	final protected function createCacheFile(string $key, string $content): bool {
		if (!file_exists($this->folder) or !is_dir($this->folder) or !is_writable($this->folder)) {
			Application::ErrorReporting()->dispatch([
				'message' => 'Não foi possível acessar a pasta de cache "' . $this->folder . '"',
				'file'    => __FILE__,
				'line'    => 20,
			]);
			return false;
		}
		$filename = $this->generateFileLocation($key);
		try {
			$create = (bool) file_put_contents($filename, $content);
		} catch (\Throwable $th) {
			Application::ErrorReporting()->dispatch($th);
			$create = false;
		}
		return $create;
	}

	final public function save(string $key, mixed $content, ?string $time = null): bool {
		$expires = ($time) 
			? (is_numeric($time) ? strtotime($time . ' minutes') : strtotime($time))
			: strtotime($this->time);
		
		$content = serialize(array(
			'expires' => $expires,
			'content' => $content
		));
		return $this->createCacheFile($key, $content);
	}

	final public function read(string $key): mixed {
		$filename = $this->generateFileLocation($key);
		if (file_exists($filename) && is_readable($filename)) {
			$cache = unserialize(file_get_contents($filename));
			if ($cache['expires'] > time()) {
				return $cache['content'];
			} else {
				unlink($filename);
			}
		}
		return false;
	}

	final public function clear(string $key): bool {
		$filename = $this->generateFileLocation($key);
		if (file_exists($filename) && is_readable($filename)) {
			return unlink($filename);
		}
		return true;
	}
}
