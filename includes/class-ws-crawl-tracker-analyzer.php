<?php

defined( 'ABSPATH' ) || exit;

/**
 * Analyse les données de crawl et produit des recommandations actionnables.
 * Pur calcul sur des tableaux fournis par le Repository → testable unitairement.
 */
class WS_Crawl_Tracker_Analyzer {

    /**
     * Construit la liste des recommandations.
     *
     * @param array $stats      get_stats()
     * @param array $by_bot     get_hits_by_bot()
     * @param array $statuses   get_status_breakdown()
     * @param array $top_pages  get_top_pages()
     * @return array Liste de [ 'level' => ok|warn|err, 'title' => ..., 'detail' => ... ]
     */
    public function build( array $stats, array $by_bot, array $statuses, array $top_pages ) {
        $recos = [];

        $total = (int) ( $stats['total_hits'] ?? 0 );

        if ( 0 === $total ) {
            $recos[] = [
                'level'  => 'warn',
                'title'  => __( 'Aucun passage de bot enregistré', 'ws-crawl-tracker' ),
                'detail' => __( 'Aucun robot détecté sur la période. Vérifiez que le suivi est activé et que votre site est accessible aux crawlers (robots.txt, indexabilité).', 'ws-crawl-tracker' ),
            ];
            return $recos;
        }

        // 1. Taux d'erreurs rencontrées par les bots.
        $errors    = (int) ( $stats['error_hits'] ?? 0 );
        $error_pct = $total > 0 ? round( $errors / $total * 100, 1 ) : 0.0;
        if ( $error_pct >= 10 ) {
            $recos[] = [
                'level'  => 'err',
                'title'  => sprintf( __( '%s%% des hits bots renvoient une erreur', 'ws-crawl-tracker' ), $error_pct ),
                'detail' => __( 'Les robots rencontrent un volume anormal de 4xx/5xx. Inspectez la répartition des codes ci-dessous et corrigez les URLs cassées : chaque erreur gaspille votre budget de crawl.', 'ws-crawl-tracker' ),
            ];
        } elseif ( $error_pct >= 3 ) {
            $recos[] = [
                'level'  => 'warn',
                'title'  => sprintf( __( '%s%% d\'erreurs sur le crawl', 'ws-crawl-tracker' ), $error_pct ),
                'detail' => __( 'Quelques erreurs sont remontées aux bots. Vérifiez les redirections et les pages supprimées.', 'ws-crawl-tracker' ),
            ];
        }

        // 2. Présence de 404 spécifiquement.
        foreach ( $statuses as $s ) {
            if ( 404 === (int) $s['status_code'] && (int) $s['hits'] > 0 ) {
                $recos[] = [
                    'level'  => 'warn',
                    'title'  => sprintf( __( '%d hits en 404', 'ws-crawl-tracker' ), (int) $s['hits'] ),
                    'detail' => __( 'Des bots crawlent des URLs inexistantes. Mettez en place des redirections 301 ou nettoyez les liens internes et le sitemap.', 'ws-crawl-tracker' ),
                ];
                break;
            }
        }

        // 3. Vérification d'authenticité (reverse DNS).
        $verified     = (int) ( $stats['verified_hits'] ?? 0 );
        $verified_pct = $total > 0 ? round( $verified / $total * 100, 1 ) : 0.0;
        if ( $verified_pct < 50 ) {
            $recos[] = [
                'level'  => 'warn',
                'title'  => sprintf( __( 'Seuls %s%% des hits sont vérifiés', 'ws-crawl-tracker' ), $verified_pct ),
                'detail' => __( 'Beaucoup de visites se déclarent bot sans réussir la vérification reverse DNS — souvent des faux user-agents (scrapers). Activez la vérification DNS dans les réglages si ce n\'est pas déjà fait pour fiabiliser les statistiques.', 'ws-crawl-tracker' ),
            ];
        } else {
            $recos[] = [
                'level'  => 'ok',
                'title'  => sprintf( __( '%s%% des hits bots authentifiés', 'ws-crawl-tracker' ), $verified_pct ),
                'detail' => __( 'La majorité des robots détectés sont authentiques (reverse DNS validé).', 'ws-crawl-tracker' ),
            ];
        }

        // 4. Concentration du crawl sur trop peu de pages.
        $unique = (int) ( $stats['unique_urls'] ?? 0 );
        if ( $unique > 0 && ! empty( $top_pages ) ) {
            $top_hits = (int) ( $top_pages[0]['hits'] ?? 0 );
            $concentration = $total > 0 ? round( $top_hits / $total * 100, 1 ) : 0.0;
            if ( $concentration >= 40 ) {
                $recos[] = [
                    'level'  => 'warn',
                    'title'  => sprintf( __( 'Crawl concentré à %s%% sur une seule URL', 'ws-crawl-tracker' ), $concentration ),
                    'detail' => __( 'Les bots reviennent massivement sur la même page au détriment du reste du site. Améliorez le maillage interne pour répartir le crawl vers vos pages stratégiques.', 'ws-crawl-tracker' ),
                ];
            }
        }

        // 5. Couverture : peu d'URLs uniques crawlées.
        if ( $unique > 0 && $unique < 10 ) {
            $recos[] = [
                'level'  => 'warn',
                'title'  => sprintf( __( 'Faible couverture : %d URLs crawlées', 'ws-crawl-tracker' ), $unique ),
                'detail' => __( 'Les bots n\'explorent qu\'une portion réduite du site. Vérifiez votre sitemap XML, le maillage interne et l\'absence de blocages dans robots.txt.', 'ws-crawl-tracker' ),
            ];
        }

        // 6. Absence des bots IA (opportunité GEO).
        $ai_bots   = [ 'gptbot', 'oai-searchbot', 'claudebot', 'perplexitybot' ];
        $seen_keys = array_map( static function ( $b ) { return $b['bot_key']; }, $by_bot );
        $ai_seen   = array_intersect( $ai_bots, $seen_keys );
        if ( empty( $ai_seen ) ) {
            $recos[] = [
                'level'  => 'warn',
                'title'  => __( 'Aucun bot IA détecté', 'ws-crawl-tracker' ),
                'detail' => __( 'Ni GPTBot, ClaudeBot ni PerplexityBot n\'ont crawlé votre site. Pour exister dans les réponses des moteurs génératifs (GEO), assurez-vous de ne pas les bloquer dans robots.txt et structurez votre contenu (données structurées, réponses claires).', 'ws-crawl-tracker' ),
            ];
        } else {
            $recos[] = [
                'level'  => 'ok',
                'title'  => sprintf( __( '%d bot(s) IA actif(s) sur le site', 'ws-crawl-tracker' ), count( $ai_seen ) ),
                'detail' => __( 'Des moteurs génératifs explorent votre contenu — bon signal pour votre visibilité GEO.', 'ws-crawl-tracker' ),
            ];
        }

        return $recos;
    }
}
