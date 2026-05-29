<?php

defined( 'ABSPATH' ) || exit;

/**
 * Identifie un visiteur comme bot connu à partir du user-agent,
 * et vérifie optionnellement l'authenticité via reverse DNS (PTR + forward).
 */
class WS_Crawl_Tracker_Detector {

    /** @var array Config des bots (depuis les réglages). */
    private $bots;

    /** @var bool Vérification reverse DNS activée. */
    private $verify_dns;

    const DNS_CACHE_TTL = WEEK_IN_SECONDS;

    public function __construct( array $bots, $verify_dns = true ) {
        $this->bots       = $bots;
        $this->verify_dns = (bool) $verify_dns;
    }

    /**
     * Retourne la clé du bot détecté ou null.
     * Ne considère que les bots activés dans la config.
     *
     * @param string $user_agent
     * @return string|null
     */
    public function match_bot_key( $user_agent ) {
        if ( '' === trim( (string) $user_agent ) ) {
            return null;
        }

        foreach ( $this->bots as $key => $bot ) {
            if ( empty( $bot['enabled'] ) ) {
                continue;
            }
            foreach ( (array) $bot['ua'] as $needle ) {
                if ( '' !== $needle && false !== stripos( $user_agent, $needle ) ) {
                    return $key;
                }
            }
        }

        return null;
    }

    /**
     * Vérifie qu'une IP appartient réellement au bot annoncé via reverse DNS.
     * PTR lookup → le hostname doit finir par un des suffixes officiels →
     * forward lookup du hostname doit redonner l'IP d'origine.
     *
     * Résultat mis en cache par couple (ip|bot_key) pour éviter le lookup répété.
     *
     * @param string $ip
     * @param string $bot_key
     * @return bool
     */
    public function verify_ip( $ip, $bot_key ) {
        if ( ! $this->verify_dns ) {
            return false;
        }
        if ( '' === $ip || ! isset( $this->bots[ $bot_key ]['hosts'] ) ) {
            return false;
        }

        $cache_key = 'wsct_dns_' . md5( $ip . '|' . $bot_key );
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            return '1' === $cached;
        }

        $verified = $this->reverse_dns_check( $ip, (array) $this->bots[ $bot_key ]['hosts'] );
        set_transient( $cache_key, $verified ? '1' : '0', self::DNS_CACHE_TTL );

        return $verified;
    }

    /**
     * Cœur de la vérification reverse DNS.
     *
     * @param string $ip
     * @param array  $host_suffixes
     * @return bool
     */
    private function reverse_dns_check( $ip, array $host_suffixes ) {
        $host = @gethostbyaddr( $ip );
        if ( ! $host || $host === $ip ) {
            return false;
        }

        $host        = strtolower( rtrim( $host, '.' ) );
        $suffix_ok   = false;
        foreach ( $host_suffixes as $suffix ) {
            $suffix = strtolower( $suffix );
            if ( substr( $host, -strlen( $suffix ) ) === $suffix ) {
                $suffix_ok = true;
                break;
            }
        }
        if ( ! $suffix_ok ) {
            return false;
        }

        // Forward lookup : le hostname doit re-résoudre vers l'IP d'origine.
        if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
            $records   = @dns_get_record( $host, DNS_AAAA );
            $resolved  = array_map( static function ( $r ) {
                return isset( $r['ipv6'] ) ? self::normalize_ipv6( $r['ipv6'] ) : '';
            }, $records ?: [] );
            return in_array( self::normalize_ipv6( $ip ), $resolved, true );
        }

        $forward = @gethostbyname( $host );
        return $forward === $ip;
    }

    /**
     * Normalise une IPv6 pour comparaison fiable.
     */
    private static function normalize_ipv6( $ip ) {
        $packed = @inet_pton( $ip );
        return $packed ? inet_ntop( $packed ) : $ip;
    }

    /**
     * Extrait l'IP cliente réelle en tenant compte des proxies courants.
     * Priorité aux headers de confiance, fallback REMOTE_ADDR.
     *
     * @param array $server Superglobale $_SERVER (injectable pour tests).
     * @return string
     */
    public static function resolve_ip( array $server ) {
        $candidates = [];

        if ( ! empty( $server['HTTP_CF_CONNECTING_IP'] ) ) {
            $candidates[] = $server['HTTP_CF_CONNECTING_IP'];
        }
        if ( ! empty( $server['HTTP_X_FORWARDED_FOR'] ) ) {
            $parts = explode( ',', $server['HTTP_X_FORWARDED_FOR'] );
            $candidates[] = trim( $parts[0] );
        }
        if ( ! empty( $server['REMOTE_ADDR'] ) ) {
            $candidates[] = $server['REMOTE_ADDR'];
        }

        foreach ( $candidates as $ip ) {
            $ip = trim( $ip );
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                return $ip;
            }
        }

        return '';
    }
}
