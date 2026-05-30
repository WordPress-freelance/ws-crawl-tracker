<?php

defined( 'ABSPATH' ) || exit;

/**
 * Accès aux données de la table wp_wsct_hits.
 * Toute interaction $wpdb passe par ici (testée en BDD intégration).
 */
class WS_Crawl_Tracker_Repository {

    /**
     * Nom complet de la table.
     */
    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'wsct_hits';
    }

    /**
     * Insère un hit de crawl.
     *
     * @param array $data
     * @return int|false ID inséré ou false.
     */
    public function insert( array $data ) {
        global $wpdb;

        $row = [
            'bot_key'      => substr( (string) ( $data['bot_key'] ?? '' ), 0, 40 ),
            'bot_name'     => substr( (string) ( $data['bot_name'] ?? '' ), 0, 80 ),
            'url'          => substr( (string) ( $data['url'] ?? '' ), 0, 2048 ),
            'url_hash'     => md5( (string) ( $data['url'] ?? '' ) ),
            'method'       => substr( (string) ( $data['method'] ?? 'GET' ), 0, 10 ),
            'status_code'  => (int) ( $data['status_code'] ?? 0 ),
            'user_agent'   => substr( (string) ( $data['user_agent'] ?? '' ), 0, 512 ),
            'ip'           => substr( (string) ( $data['ip'] ?? '' ), 0, 45 ),
            'referer'      => substr( (string) ( $data['referer'] ?? '' ), 0, 2048 ),
            'is_verified'  => ! empty( $data['is_verified'] ) ? 1 : 0,
            'post_id'      => (int) ( $data['post_id'] ?? 0 ),
            'content_type' => substr( (string) ( $data['content_type'] ?? '' ), 0, 40 ),
            'session_id'   => substr( (string) ( $data['session_id'] ?? '' ), 0, 32 ),
            'hit_time'     => (string) ( $data['hit_time'] ?? current_time( 'mysql' ) ),
        ];

        $ok = $wpdb->insert(
            self::table(),
            $row,
            [ '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' ]
        );

        return $ok ? (int) $wpdb->insert_id : false;
    }

    /**
     * KPIs globaux sur une fenêtre temporelle (en jours).
     *
     * @param int         $days
     * @param string|null $bot_key
     * @return array
     */
    public function get_stats( $days = 30, $bot_key = null ) {
        global $wpdb;
        $table = self::table();
        $since = $this->since( $days );

        $where  = 'hit_time >= %s';
        $params = [ $since ];
        if ( $bot_key ) {
            $where   .= ' AND bot_key = %s';
            $params[] = $bot_key;
        }

        $sql = "SELECT
                    COUNT(*) AS total_hits,
                    COUNT(DISTINCT url_hash) AS unique_urls,
                    COUNT(DISTINCT bot_key) AS distinct_bots,
                    SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) AS error_hits,
                    SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) AS verified_hits,
                    MIN(hit_time) AS first_hit,
                    MAX(hit_time) AS last_hit
                FROM {$table} WHERE {$where}";

        $row = $wpdb->get_row( $wpdb->prepare( $sql, $params ), ARRAY_A );

        return [
            'total_hits'    => (int) ( $row['total_hits'] ?? 0 ),
            'unique_urls'   => (int) ( $row['unique_urls'] ?? 0 ),
            'distinct_bots' => (int) ( $row['distinct_bots'] ?? 0 ),
            'error_hits'    => (int) ( $row['error_hits'] ?? 0 ),
            'verified_hits' => (int) ( $row['verified_hits'] ?? 0 ),
            'first_hit'     => $row['first_hit'] ?? null,
            'last_hit'      => $row['last_hit'] ?? null,
        ];
    }

    /**
     * Répartition des hits par bot.
     */
    public function get_hits_by_bot( $days = 30 ) {
        global $wpdb;
        $table = self::table();
        $since = $this->since( $days );

        $sql = "SELECT bot_key, bot_name, COUNT(*) AS hits,
                       SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) AS verified
                FROM {$table}
                WHERE hit_time >= %s
                GROUP BY bot_key, bot_name
                ORDER BY hits DESC";

        return $wpdb->get_results( $wpdb->prepare( $sql, $since ), ARRAY_A ) ?: [];
    }

    /**
     * Série temporelle (hits par jour) pour le graphe d'activité.
     *
     * @param int         $days
     * @param string|null $bot_key
     * @return array [ ['day' => '2026-05-01', 'hits' => 12], ... ]
     */
    public function get_timeline_daily( $days = 30, $bot_key = null ) {
        global $wpdb;
        $table = self::table();
        $since = $this->since( $days );

        $where  = 'hit_time >= %s';
        $params = [ $since ];
        if ( $bot_key ) {
            $where   .= ' AND bot_key = %s';
            $params[] = $bot_key;
        }

        $sql = "SELECT DATE(hit_time) AS day, COUNT(*) AS hits
                FROM {$table}
                WHERE {$where}
                GROUP BY DATE(hit_time)
                ORDER BY day ASC";

        return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) ?: [];
    }

    /**
     * Pages les plus crawlées (heatmap).
     */
    public function get_top_pages( $days = 30, $limit = 25, $bot_key = null ) {
        global $wpdb;
        $table = self::table();
        $since = $this->since( $days );

        $where  = 'hit_time >= %s';
        $params = [ $since ];
        if ( $bot_key ) {
            $where   .= ' AND bot_key = %s';
            $params[] = $bot_key;
        }
        $params[] = (int) $limit;

        $sql = "SELECT url, COUNT(*) AS hits,
                       MAX(hit_time) AS last_hit,
                       MAX(status_code) AS last_status
                FROM {$table}
                WHERE {$where}
                GROUP BY url, url_hash
                ORDER BY hits DESC
                LIMIT %d";

        return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) ?: [];
    }

    /**
     * Derniers hits bruts (flux chronologique du crawl).
     */
    public function get_recent_hits( $limit = 100, $bot_key = null ) {
        global $wpdb;
        $table = self::table();

        $where  = '1=1';
        $params = [];
        if ( $bot_key ) {
            $where   .= ' AND bot_key = %s';
            $params[] = $bot_key;
        }
        $params[] = (int) $limit;

        $sql = "SELECT id, bot_key, bot_name, url, method, status_code,
                       ip, is_verified, session_id, hit_time
                FROM {$table}
                WHERE {$where}
                ORDER BY hit_time DESC, id DESC
                LIMIT %d";

        return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) ?: [];
    }

    /**
     * Derniers hits regroupés par jour (pour l'affichage en accordéons).
     * Retourne une structure ordonnée : [ '2026-05-29' => [ hits... ], ... ].
     *
     * @param int         $limit
     * @param string|null $bot_key
     * @return array
     */
    public function get_recent_hits_grouped( $limit = 200, $bot_key = null ) {
        $rows    = $this->get_recent_hits( $limit, $bot_key );
        $grouped = [];

        foreach ( $rows as $row ) {
            $day = substr( (string) $row['hit_time'], 0, 10 );
            if ( ! isset( $grouped[ $day ] ) ) {
                $grouped[ $day ] = [];
            }
            $grouped[ $day ][] = $row;
        }

        return $grouped;
    }

    /**
     * Toutes les colonnes d'une fenêtre temporelle, pour l'export CSV.
     * Pas de LIMIT : destiné à un téléchargement complet.
     *
     * @param int         $days
     * @param string|null $bot_key
     * @return array
     */
    public function get_all_for_export( $days = 30, $bot_key = null ) {
        global $wpdb;
        $table = self::table();
        $since = $this->since( $days );

        $where  = 'hit_time >= %s';
        $params = [ $since ];
        if ( $bot_key ) {
            $where   .= ' AND bot_key = %s';
            $params[] = $bot_key;
        }

        $sql = "SELECT hit_time, bot_name, bot_key, url, method, status_code,
                       content_type, is_verified, ip, referer, session_id, post_id
                FROM {$table}
                WHERE {$where}
                ORDER BY hit_time DESC, id DESC";

        return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) ?: [];
    }

    /**
     * Chemin de crawl d'une session : séquence ordonnée des URLs visitées.
     * Sert à reconstruire le graphe de navigation (from → to).
     *
     * @param string $session_id
     * @return array
     */
    public function get_session_path( $session_id ) {
        global $wpdb;
        $table = self::table();

        $sql = "SELECT url, status_code, hit_time
                FROM {$table}
                WHERE session_id = %s
                ORDER BY hit_time ASC, id ASC";

        return $wpdb->get_results( $wpdb->prepare( $sql, $session_id ), ARRAY_A ) ?: [];
    }

    /**
     * Sessions de crawl récentes (pour sélection dans le graphe de navigation).
     */
    public function get_recent_sessions( $days = 30, $limit = 30, $bot_key = null ) {
        global $wpdb;
        $table = self::table();
        $since = $this->since( $days );

        $where  = "hit_time >= %s AND session_id <> ''";
        $params = [ $since ];
        if ( $bot_key ) {
            $where   .= ' AND bot_key = %s';
            $params[] = $bot_key;
        }
        $params[] = (int) $limit;

        $sql = "SELECT session_id, bot_key, bot_name,
                       COUNT(*) AS hits,
                       MIN(hit_time) AS started,
                       MAX(hit_time) AS ended
                FROM {$table}
                WHERE {$where}
                GROUP BY session_id, bot_key, bot_name
                ORDER BY started DESC
                LIMIT %d";

        return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) ?: [];
    }

    /**
     * Codes de statut HTTP rencontrés par le crawl (santé technique).
     */
    public function get_status_breakdown( $days = 30, $bot_key = null ) {
        global $wpdb;
        $table = self::table();
        $since = $this->since( $days );

        $where  = 'hit_time >= %s';
        $params = [ $since ];
        if ( $bot_key ) {
            $where   .= ' AND bot_key = %s';
            $params[] = $bot_key;
        }

        $sql = "SELECT status_code, COUNT(*) AS hits
                FROM {$table}
                WHERE {$where}
                GROUP BY status_code
                ORDER BY hits DESC";

        return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) ?: [];
    }

    /**
     * Répartition horaire du crawl (heures de la journée 0-23).
     */
    public function get_hourly_distribution( $days = 30, $bot_key = null ) {
        global $wpdb;
        $table = self::table();
        $since = $this->since( $days );

        $where  = 'hit_time >= %s';
        $params = [ $since ];
        if ( $bot_key ) {
            $where   .= ' AND bot_key = %s';
            $params[] = $bot_key;
        }

        $sql = "SELECT HOUR(hit_time) AS hour, COUNT(*) AS hits
                FROM {$table}
                WHERE {$where}
                GROUP BY HOUR(hit_time)
                ORDER BY hour ASC";

        return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) ?: [];
    }

    /**
     * Purge les hits plus vieux que N jours. Flush du cache options après coup.
     *
     * @param int $retention_days
     * @return int Lignes supprimées.
     */
    public function purge_older_than( $retention_days ) {
        global $wpdb;
        $table  = self::table();
        $cutoff = gmdate( 'Y-m-d H:i:s', time() - ( (int) $retention_days * DAY_IN_SECONDS ) );

        $deleted = $wpdb->query(
            $wpdb->prepare( "DELETE FROM {$table} WHERE hit_time < %s", $cutoff )
        );

        if ( function_exists( 'wp_cache_flush' ) ) {
            wp_cache_flush();
        }

        return (int) $deleted;
    }

    /**
     * Vide intégralement la table.
     */
    public function truncate() {
        global $wpdb;
        $table = self::table();
        $wpdb->query( "TRUNCATE TABLE {$table}" );

        if ( function_exists( 'wp_cache_flush' ) ) {
            wp_cache_flush();
        }
    }

    /**
     * Nombre total de lignes en base.
     */
    public function count_all() {
        global $wpdb;
        $table = self::table();
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    }

    /**
     * Date MySQL à J-N.
     */
    private function since( $days ) {
        return gmdate( 'Y-m-d H:i:s', time() - ( (int) $days * DAY_IN_SECONDS ) );
    }
}
