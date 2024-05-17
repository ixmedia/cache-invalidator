<?php
require_once __DIR__ . '/../interfaces/CacheInvalidationTarget.php';
require_once __DIR__ . '/../interfaces/CacheManager.php';

class GutenbergComponentTarget implements CacheInvalidationTarget {
    private string $componentName;
    private CacheManager $cacheManager;

    public function __construct(string $componentName, CacheManager $cacheManager) {
        $this->componentName = $componentName;
        $this->cacheManager = $cacheManager;
    }

    public function getTargetId(): string {
        return $this->componentName;
    }

    public function invalidate(): void {
        global $wpdb;

        // Construire la requête SQL pour récupérer les IDs des posts contenant le composant Gutenberg demandé dans leur contenu
        $sql = $wpdb->prepare("
            SELECT ID
            FROM {$wpdb->posts}
            WHERE post_status = 'publish'
            AND post_type IN ('post', 'page')
            AND post_content LIKE '%<!--%'
            AND post_content LIKE %s
        ", '%wp:' . $this->componentName . ' %' );
        // ", '%wp:' . $this->componentName . '%' );

        // Exécuter la requête
        $post_ids = $wpdb->get_col($sql);

        if (!empty($post_ids)) {
            foreach ($post_ids as $post_id) {
                $this->cacheManager->invalidateCache($post_id);
            }
        }
    }

}
