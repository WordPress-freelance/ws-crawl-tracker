<?php

defined( 'ABSPATH' ) || exit;

/**
 * Côté public : détecte les bots sur le front et enregistre leurs hits.
 * Une "session de crawl" regroupe les hits d'un même couple (bot, IP)
 * espacés de moins de WSCT_SESSION_GAP secondes.
 */
class WS_Crawl_Tracker_Public {

    private $plugin_name;
    private $version;

    /** @var array Réglages. */
    private $settings;

    /** @var array|null Contexte du hit courant (bot détecté). */
    private $current = null;

    /** Fenêtre d'inactivité (sec) au-delà de laquelle une nouvelle session démarre. */
    const SESSION_GAP = 1800; // 30 min

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
        $this->settings    = get_option( 'wsct_settings', [] );
    }

    /**
     * Détection précoce du bot (hook template_redirect).
     * On ne logge pas encore : on attend shutdown pour avoir le status code.
     */
    public function detect() {
        if ( empty( $this->settings['enabled'] ) ) {
            return;
        }
        if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
            return;
        }

        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '';
        if ( '' === $ua ) {
            return;
        }

        // Exclusions (outils internes, endpoints REST du bridge, etc.).
        $uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
        if ( $this->is_excluded( $ua, $uri ) ) {
            return;
        }

        $bots     = $this->settings['bots'] ?? [];
        $detector = new WS_Crawl_Tracker_Detector( $bots, ! empty( $this->settings['verify_dns'] ) );

        $bot_key = $detector->match_bot_key( $ua );
        if ( null === $bot_key ) {
            return;
        }

        $ip       = WS_Crawl_Tracker_Detector::resolve_ip( $_SERVER );
        $verified = $detector->verify_ip( $ip, $bot_key );

        $this->current = [
            'bot_key'    => $bot_key,
            'bot_name'   => $bots[ $bot_key ]['name'] ?? $bot_key,
            'ua'         => $ua,
            'ip'         => $ip,
            'verified'   => $verified,
            'url'        => $this->current_url(),
            'referer'    => isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : '',
            'method'     => isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : 'GET',
            'post_id'    => $this->resolve_post_id(),
            'session_id' => $this->session_id( $bot_key, $ip ),
        ];
    }

    /**
     * Enregistrement effectif au shutdown — le status code HTTP est connu ici.
     */
    public function record() {
        if ( null === $this->current ) {
            return;
        }

        $status = function_exists( 'http_response_code' ) ? (int) http_response_code() : 200;
        if ( $status <= 0 ) {
            $status = 200;
        }

        $repo = new WS_Crawl_Tracker_Repository();
        $repo->insert( [
            'bot_key'      => $this->current['bot_key'],
            'bot_name'     => $this->current['bot_name'],
            'url'          => $this->current['url'],
            'method'       => $this->current['method'],
            'status_code'  => $status,
            'user_agent'   => $this->current['ua'],
            'ip'           => $this->current['ip'],
            'referer'      => $this->current['referer'],
            'is_verified'  => $this->current['verified'],
            'post_id'      => $this->current['post_id'],
            'content_type' => $this->detect_content_type(),
            'session_id'   => $this->current['session_id'],
            'hit_time'     => current_time( 'mysql' ),
        ] );
    }

    /**
     * Identifiant de session de crawl. On réutilise la dernière session connue
     * pour ce bot si le dernier hit date de moins de SESSION_GAP.
     *
     * NB : la session NE dépend PAS de l'IP. Les crawlers (Googlebot en tête)
     * opèrent depuis un pool d'IP et changent d'adresse d'une requête à l'autre ;
     * inclure l'IP dans la clé fragmentait chaque passage en sessions de 1 page.
     */
    private function session_id( $bot_key, $ip ) {
        $key  = 'wsct_sess_' . md5( (string) $bot_key );
        $sess = get_transient( $key );

        if ( ! $sess ) {
            $sess = md5( $bot_key . '|' . microtime( true ) . '|' . wp_rand() );
        }

        // Rafraîchit la fenêtre d'inactivité.
        set_transient( $key, $sess, self::SESSION_GAP );

        return $sess;
    }

    /**
     * Détermine si une requête doit être ignorée (outils internes, REST du bridge…).
     * UA : matching « contient » (insensible à la casse). Chemin : « commence par ».
     *
     * @param string $ua  User-agent.
     * @param string $uri REQUEST_URI (chemin + query).
     * @return bool
     */
    public function is_excluded( $ua, $uri ) {
        $ua  = (string) $ua;
        $uri = (string) $uri;

        $ua_list = $this->settings['excluded_ua'] ?? [];
        foreach ( (array) $ua_list as $needle ) {
            $needle = trim( (string) $needle );
            if ( '' !== $needle && false !== stripos( $ua, $needle ) ) {
                return true;
            }
        }

        // Compare sur le chemin seul (sans la query string).
        $path = $uri;
        $qpos = strpos( $path, '?' );
        if ( false !== $qpos ) {
            $path = substr( $path, 0, $qpos );
        }

        $path_list = $this->settings['excluded_paths'] ?? [];
        foreach ( (array) $path_list as $prefix ) {
            $prefix = trim( (string) $prefix );
            if ( '' !== $prefix && 0 === strpos( $path, $prefix ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * URL courante complète.
     */
    private function current_url() {
        $host = isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : wp_parse_url( home_url(), PHP_URL_HOST );
        $uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
        $https = ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] ) || is_ssl();
        $scheme = $https ? 'https' : 'http';

        return esc_url_raw( $scheme . '://' . $host . $uri );
    }

    /**
     * Post ID associé à la requête, si singulier.
     */
    private function resolve_post_id() {
        if ( is_singular() ) {
            $id = get_queried_object_id();
            return $id ? (int) $id : 0;
        }
        return 0;
    }

    /**
     * Type de contenu logique de la requête.
     */
    private function detect_content_type() {
        if ( is_singular() )      { return 'singular'; }
        if ( is_front_page() )    { return 'front'; }
        if ( is_home() )          { return 'blog'; }
        if ( is_category() || is_tag() || is_tax() ) { return 'taxonomy'; }
        if ( is_archive() )       { return 'archive'; }
        if ( is_search() )        { return 'search'; }
        if ( is_404() )           { return '404'; }
        return 'other';
    }
}
