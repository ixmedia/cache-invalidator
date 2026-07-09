<?php
require_once __DIR__ . '/../interfaces/CacheManager.php';

abstract class BaseCacheManager implements CacheManager {
  /**
   * The name of the cache manager.
   * @var string
   */
  protected $cacheManagerName;

  public function __construct(string $cacheManagerName) {
      $this->cacheManagerName = $cacheManagerName;
  }

  /**
   * Invalidates cache for the given cache key.
   * @param string $cacheKey The cache key to invalidate.
   * @return void
   */
  public function invalidateCache(string $cacheKey): void {
      $this->executeCacheFunction($this->getClearCacheFunction(), "cleared for key: " . $cacheKey, $cacheKey);
  }

  /**
   * Invalidates all caches.
   * @return void
   */
  public function invalidateAllCache(): void {
      $this->executeCacheFunction($this->getClearAllCacheFunction(), "All Cache cleared.");
  }

  /**
   * Invalidates cache for a specific front-end URL.
   * @param string $url The URL to invalidate.
   * @return void
   */
  public function invalidateUrl(string $url): void {
      $flushUrlFunction = $this->getFlushUrlFunction();
      if ($flushUrlFunction === null) {
          $this->log("URL-based invalidation not supported by this cache manager.");
          return;
      }
      $this->executeCacheFunction($flushUrlFunction, "URL flushed: " . $url, $url);
  }

  /**
   * Executes the cache function.
   * @param string $function The cache function to execute.
   * @param string $successMessage The success message to log.
   * @param string|null $parameter The parameter to pass to the function, if any.
   * @return void
   */
  protected function executeCacheFunction(string $function, string $successMessage, ?string $parameter = null): void {
      if (function_exists($function)) {
          if ($parameter) {
              $function($parameter);
          } else {
              $function();
          }
          $this->log($successMessage);
      } else {
          $this->log("not active or no compatible cache function available.", E_ERROR);
      }
  }

  /**
   * Gets the cache function to clear a specific cache key.
   * Needs to be implemented by subclasses.
   * @return string
   */
  abstract protected function getClearCacheFunction(): string;

  /**
   * Gets the cache function to clear all caches.
   * Needs to be implemented by subclasses.
   * @return string
   */
  abstract protected function getClearAllCacheFunction(): string;

  /**
   * Gets the cache function to flush a specific URL.
   * Returns null when the cache manager cannot invalidate by URL.
   * Needs to be implemented by subclasses.
   * @return string|null
   */
  abstract protected function getFlushUrlFunction(): ?string;

  /**
   * Logs a message using trigger_error.
   * @param string $message The message to log.
   * @param int $errorType The type of error.
   * @return void
   */
  protected function log(string $message, int $errorType = E_USER_NOTICE): void {
      trigger_error("{$this->cacheManagerName}: {$message}", $errorType);
  }
}
