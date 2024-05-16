<?php
interface CacheInvalidationTarget {
    public function getTargetId(): string;
    public function invalidate(): void;
}
