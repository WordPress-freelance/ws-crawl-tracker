<?php

defined( 'ABSPATH' ) || exit;

class WS_Crawl_Tracker_Activator {

    /**
     * Crée la table de log des hits de crawl + initialise les réglages.
     */
    public static function activate() {
        self::create_table();

        if ( ! get_option( 'wsct_settings' ) ) {
            update_option( 'wsct_settings', [
                'enabled'         => 1,
                'verify_dns'      => 1,
                'retention_days'  => 90,
                'bots'            => self::default_bots(),
                'excluded_ua'     => self::default_excluded_ua(),
                'excluded_paths'  => self::default_excluded_paths(),
            ] );
        }

        // Marqueur de version de schéma pour migrations futures.
        update_option( 'wsct_db_version', '1.0.0' );
    }

    /**
     * User-agents exclus du tracking par défaut (matching « contient »).
     * Évite que les outils internes WebStrategy polluent les statistiques.
     */
    public static function default_excluded_ua() {
        return [
            'WS-Claude-Bridge',
            'WS-GEO-Audit',
        ];
    }

    /**
     * Chemins exclus du tracking par défaut (matching « commence par »).
     */
    public static function default_excluded_paths() {
        return [
            '/wp-json/ws-bridge/v1/',
        ];
    }

    /**
     * Schéma de la table wp_wsct_hits.
     */
    public static function create_table() {
        global $wpdb;

        $table           = $wpdb->prefix . 'wsct_hits';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            bot_key VARCHAR(40) NOT NULL DEFAULT '',
            bot_name VARCHAR(80) NOT NULL DEFAULT '',
            url VARCHAR(2048) NOT NULL DEFAULT '',
            url_hash CHAR(32) NOT NULL DEFAULT '',
            method VARCHAR(10) NOT NULL DEFAULT 'GET',
            status_code SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
            user_agent VARCHAR(512) NOT NULL DEFAULT '',
            ip VARCHAR(45) NOT NULL DEFAULT '',
            referer VARCHAR(2048) NOT NULL DEFAULT '',
            is_verified TINYINT(1) NOT NULL DEFAULT 0,
            post_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            content_type VARCHAR(40) NOT NULL DEFAULT '',
            session_id CHAR(32) NOT NULL DEFAULT '',
            hit_time DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY bot_key (bot_key),
            KEY hit_time (hit_time),
            KEY url_hash (url_hash),
            KEY session_id (session_id),
            KEY post_id (post_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Liste par défaut des bots tracés (clé => [name, ua_match, host_suffixes]).
     * host_suffixes sert à la vérification reverse DNS.
     */
    public static function default_bots() {
        return [
            'googlebot' => [
                'name'    => 'Googlebot',
                'enabled' => 1,
                'ua'      => [ 'Googlebot', 'Google-InspectionTool', 'Storebot-Google' ],
                'hosts'   => [ '.googlebot.com', '.google.com' ],
            ],
            'bingbot' => [
                'name'    => 'Bingbot',
                'enabled' => 1,
                'ua'      => [ 'bingbot', 'BingPreview' ],
                'hosts'   => [ '.search.msn.com' ],
            ],
            'gptbot' => [
                'name'    => 'GPTBot',
                'enabled' => 1,
                'ua'      => [ 'GPTBot' ],
                'hosts'   => [ '.openai.com' ],
            ],
            'oai-searchbot' => [
                'name'    => 'OAI-SearchBot',
                'enabled' => 1,
                'ua'      => [ 'OAI-SearchBot' ],
                'hosts'   => [ '.openai.com' ],
            ],
            'claudebot' => [
                'name'    => 'ClaudeBot',
                'enabled' => 1,
                'ua'      => [ 'ClaudeBot', 'Claude-Web', 'anthropic-ai' ],
                'hosts'   => [ '.anthropic.com' ],
            ],
            'perplexitybot' => [
                'name'    => 'PerplexityBot',
                'enabled' => 1,
                'ua'      => [ 'PerplexityBot', 'Perplexity-User' ],
                'hosts'   => [ '.perplexity.ai' ],
            ],
            'applebot' => [
                'name'    => 'Applebot',
                'enabled' => 0,
                'ua'      => [ 'Applebot' ],
                'hosts'   => [ '.applebot.apple.com' ],
            ],
            'yandexbot' => [
                'name'    => 'YandexBot',
                'enabled' => 0,
                'ua'      => [ 'YandexBot' ],
                'hosts'   => [ '.yandex.com', '.yandex.net', '.yandex.ru' ],
            ],
            'duckduckbot' => [
                'name'    => 'DuckDuckBot',
                'enabled' => 0,
                'ua'      => [ 'DuckDuckBot' ],
                'hosts'   => [ '.duckduckgo.com' ],
            ],
            'meta-externalagent' => [
                'name'    => 'Meta AI',
                'enabled' => 0,
                'ua'      => [ 'meta-externalagent', 'FacebookBot' ],
                'hosts'   => [ '.facebook.com' ],
            ],
        ];
    }
}
