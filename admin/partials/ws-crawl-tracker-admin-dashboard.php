<?php
defined( 'ABSPATH' ) || exit;
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Accès refusé.', 'ws-crawl-tracker' ) );
}
$wsct_settings = get_option( 'wsct_settings', [] );
$wsct_bots     = $wsct_settings['bots'] ?? [];
?>
<div class="wrap ws-admin-wrap">
  <?php include __DIR__ . '/ws-crawl-tracker-admin-header.php'; ?>
  <main class="ws-main">

    <header class="ws-page-head">
      <h1 class="ws-page-title">
        <svg class="ws-title-logo" viewBox="0 0 34 34" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
          <rect width="34" height="34" rx="9" fill="#221D32"/>
          <circle cx="15" cy="15" r="7" fill="none" stroke="#7C5CBF" stroke-width="2.4"/>
          <line x1="20" y1="20" x2="26" y2="26" stroke="#A899D4" stroke-width="2.6" stroke-linecap="round"/>
        </svg>
        <span class="ws-page-title__name"><?php esc_html_e( 'Crawl Tracker', 'ws-crawl-tracker' ); ?></span>
        <span class="ws-page-title__section"><?php esc_html_e( 'Tableau de bord', 'ws-crawl-tracker' ); ?></span>
      </h1>
      <p class="ws-page-sub"><?php esc_html_e( 'Suivez le passage de Googlebot et des robots SEO/IA : par où ils passent, à quelle fréquence, et quoi corriger.', 'ws-crawl-tracker' ); ?></p>
    </header>

    <!-- Filtres -->
    <section class="ws-card wsct-filters">
      <div class="wsct-filter-group">
        <label class="wsct-filter-label"><?php esc_html_e( 'Période', 'ws-crawl-tracker' ); ?></label>
        <select id="wsct-days" class="ws-select">
          <option value="1"><?php esc_html_e( '24 heures', 'ws-crawl-tracker' ); ?></option>
          <option value="7"><?php esc_html_e( '7 jours', 'ws-crawl-tracker' ); ?></option>
          <option value="30" selected><?php esc_html_e( '30 jours', 'ws-crawl-tracker' ); ?></option>
          <option value="90"><?php esc_html_e( '90 jours', 'ws-crawl-tracker' ); ?></option>
          <option value="365"><?php esc_html_e( '1 an', 'ws-crawl-tracker' ); ?></option>
        </select>
      </div>
      <div class="wsct-filter-group">
        <label class="wsct-filter-label"><?php esc_html_e( 'Robot', 'ws-crawl-tracker' ); ?></label>
        <select id="wsct-bot" class="ws-select">
          <option value=""><?php esc_html_e( 'Tous les robots', 'ws-crawl-tracker' ); ?></option>
          <?php foreach ( $wsct_bots as $wsct_k => $wsct_b ) : ?>
          <option value="<?php echo esc_attr( $wsct_k ); ?>"><?php echo esc_html( $wsct_b['name'] ); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="button" id="wsct-refresh" class="ws-btn-inline"><?php esc_html_e( 'Actualiser', 'ws-crawl-tracker' ); ?></button>
    </section>

    <!-- KPIs -->
    <section class="wsct-kpis" id="wsct-kpis">
      <div class="wsct-kpi">
        <span class="wsct-kpi__label"><?php esc_html_e( 'Hits total', 'ws-crawl-tracker' ); ?></span>
        <span class="wsct-kpi__value" data-kpi="total_hits">—</span>
      </div>
      <div class="wsct-kpi">
        <span class="wsct-kpi__label"><?php esc_html_e( 'URLs uniques', 'ws-crawl-tracker' ); ?></span>
        <span class="wsct-kpi__value" data-kpi="unique_urls">—</span>
      </div>
      <div class="wsct-kpi">
        <span class="wsct-kpi__label"><?php esc_html_e( 'Robots distincts', 'ws-crawl-tracker' ); ?></span>
        <span class="wsct-kpi__value" data-kpi="distinct_bots">—</span>
      </div>
      <div class="wsct-kpi wsct-kpi--err">
        <span class="wsct-kpi__label"><?php esc_html_e( 'Erreurs (4xx/5xx)', 'ws-crawl-tracker' ); ?></span>
        <span class="wsct-kpi__value" data-kpi="error_hits">—</span>
      </div>
      <div class="wsct-kpi wsct-kpi--ok">
        <span class="wsct-kpi__label"><?php esc_html_e( 'Hits vérifiés', 'ws-crawl-tracker' ); ?></span>
        <span class="wsct-kpi__value" data-kpi="verified_hits">—</span>
      </div>
    </section>

    <!-- Recommandations -->
    <section class="ws-card">
      <div class="ws-card-head">
        <span class="ws-card-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 00-4 12.7c.6.5 1 1.2 1 2h6c0-.8.4-1.5 1-2A7 7 0 0012 2z"/></svg>
        </span>
        <div>
          <h2 class="ws-card-title"><?php esc_html_e( 'Recommandations', 'ws-crawl-tracker' ); ?></h2>
          <p class="ws-card-desc"><?php esc_html_e( 'Analyse automatique du crawl et actions prioritaires.', 'ws-crawl-tracker' ); ?></p>
        </div>
      </div>
      <div id="wsct-recos" class="wsct-recos"></div>
    </section>

    <!-- Graphe activité dans le temps -->
    <section class="ws-card">
      <div class="ws-card-head">
        <span class="ws-card-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 3v18h18"/><path d="M7 14l4-4 3 3 5-6"/></svg>
        </span>
        <div>
          <h2 class="ws-card-title"><?php esc_html_e( 'Activité de crawl dans le temps', 'ws-crawl-tracker' ); ?></h2>
          <p class="ws-card-desc"><?php esc_html_e( 'Nombre de hits par jour sur la période.', 'ws-crawl-tracker' ); ?></p>
        </div>
      </div>
      <div class="wsct-chart-wrap"><canvas id="wsct-chart-timeline"></canvas></div>
    </section>

    <div class="wsct-grid-2">
      <!-- Répartition par bot -->
      <section class="ws-card">
        <div class="ws-card-head">
          <span class="ws-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M12 3v9l6 4"/></svg>
          </span>
          <div>
            <h2 class="ws-card-title"><?php esc_html_e( 'Répartition par robot', 'ws-crawl-tracker' ); ?></h2>
            <p class="ws-card-desc"><?php esc_html_e( 'Volume de crawl par bot.', 'ws-crawl-tracker' ); ?></p>
          </div>
        </div>
        <div class="wsct-chart-wrap"><canvas id="wsct-chart-bots"></canvas></div>
      </section>

      <!-- Répartition horaire -->
      <section class="ws-card">
        <div class="ws-card-head">
          <span class="ws-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
          </span>
          <div>
            <h2 class="ws-card-title"><?php esc_html_e( 'Répartition horaire', 'ws-crawl-tracker' ); ?></h2>
            <p class="ws-card-desc"><?php esc_html_e( 'Heures où les bots passent le plus.', 'ws-crawl-tracker' ); ?></p>
          </div>
        </div>
        <div class="wsct-chart-wrap"><canvas id="wsct-chart-hourly"></canvas></div>
      </section>
    </div>

    <!-- Codes de statut -->
    <section class="ws-card">
      <div class="ws-card-head">
        <span class="ws-card-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </span>
        <div>
          <h2 class="ws-card-title"><?php esc_html_e( 'Santé technique du crawl', 'ws-crawl-tracker' ); ?></h2>
          <p class="ws-card-desc"><?php esc_html_e( 'Codes HTTP renvoyés aux robots.', 'ws-crawl-tracker' ); ?></p>
        </div>
      </div>
      <div id="wsct-status" class="wsct-status-grid"></div>
    </section>

    <!-- Heatmap pages -->
    <section class="ws-card">
      <div class="ws-card-head">
        <span class="ws-card-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        </span>
        <div>
          <h2 class="ws-card-title"><?php esc_html_e( 'Pages les plus crawlées', 'ws-crawl-tracker' ); ?></h2>
          <p class="ws-card-desc"><?php esc_html_e( 'Où les robots concentrent leur budget de crawl.', 'ws-crawl-tracker' ); ?></p>
        </div>
      </div>
      <div id="wsct-heatmap" class="wsct-heatmap"></div>
    </section>

    <!-- Chemin de crawl (sessions) -->
    <section class="ws-card">
      <div class="ws-card-head">
        <span class="ws-card-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="5" cy="6" r="2.5"/><circle cx="19" cy="18" r="2.5"/><path d="M5 8.5v3a4 4 0 004 4h6"/></svg>
        </span>
        <div>
          <h2 class="ws-card-title"><?php esc_html_e( 'Chemin de crawl', 'ws-crawl-tracker' ); ?></h2>
          <p class="ws-card-desc"><?php esc_html_e( 'Sélectionnez une session pour visualiser le parcours du robot, étape par étape.', 'ws-crawl-tracker' ); ?></p>
        </div>
      </div>
      <div class="wsct-session-pick">
        <select id="wsct-session" class="ws-select"></select>
      </div>
      <div id="wsct-path" class="wsct-path"></div>
    </section>

    <!-- Flux récent -->
    <section class="ws-card">
      <div class="ws-card-head">
        <span class="ws-card-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></svg>
        </span>
        <div>
          <h2 class="ws-card-title"><?php esc_html_e( 'Derniers passages', 'ws-crawl-tracker' ); ?></h2>
          <p class="ws-card-desc"><?php esc_html_e( 'Flux chronologique des 100 derniers hits.', 'ws-crawl-tracker' ); ?></p>
        </div>
      </div>
      <div class="wsct-table-wrap">
        <table class="wsct-table">
          <thead>
            <tr>
              <th><?php esc_html_e( 'Date', 'ws-crawl-tracker' ); ?></th>
              <th><?php esc_html_e( 'Robot', 'ws-crawl-tracker' ); ?></th>
              <th><?php esc_html_e( 'URL', 'ws-crawl-tracker' ); ?></th>
              <th><?php esc_html_e( 'Code', 'ws-crawl-tracker' ); ?></th>
              <th><?php esc_html_e( 'Vérifié', 'ws-crawl-tracker' ); ?></th>
            </tr>
          </thead>
          <tbody id="wsct-recent"></tbody>
        </table>
      </div>
    </section>

    <div id="wsct-loading" class="wsct-loading" style="display:none;">
      <span class="wsct-spinner"></span>
      <span><?php esc_html_e( 'Chargement…', 'ws-crawl-tracker' ); ?></span>
    </div>

  </main>
</div>
