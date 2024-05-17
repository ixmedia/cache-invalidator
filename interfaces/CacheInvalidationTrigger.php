<?php
interface CacheInvalidationTrigger {
    /**
     * @return CacheInvalidationTarget[]
     */
    public function getTargetsToInvalidate(): array;
    public function shouldInvalidate($elementId): bool;
    public function getTriggerId(): string;
}
