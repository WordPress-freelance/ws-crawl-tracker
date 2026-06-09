<?php
defined( 'ABSPATH' ) || exit;
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Accès refusé.', 'ws-crawl-tracker' ) );
}
$wsct_settings  = get_option( 'wsct_settings', [] );
$wsct_enabled   = ! empty( $wsct_settings['enabled'] );
$wsct_dns       = ! empty( $wsct_settings['verify_dns'] );
$wsct_retention = isset( $wsct_settings['retention_days'] ) ? absint( $wsct_settings['retention_days'] ) : 90;
$wsct_bots      = $wsct_settings['bots'] ?? WS_Crawl_Tracker_Activator::default_bots();
$wsct_excl_ua   = $wsct_settings['excluded_ua'] ?? WS_Crawl_Tracker_Activator::default_excluded_ua();
$wsct_excl_path = $wsct_settings['excluded_paths'] ?? WS_Crawl_Tracker_Activator::default_excluded_paths();
?>
<div class="wrap ws-admin-wrap">
  <?php include __DIR__ . '/ws-crawl-tracker-admin-header.php'; ?>
  <main class="ws-main">

    <header class="ws-page-head">
      <h1 class="ws-page-title">
        <svg class="ws-title-logo" viewBox="0 0 34 34" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
          <rect width="34" height="34" rx="9" fill="#221D32"/>
          <circle cx="17" cy="17" r="4" fill="none" stroke="#7C5CBF" stroke-width="2.2"/>
          <path d="M17 7v3M17 24v3M7 17h3M24 17h3M10 10l2 2M22 22l2 2M24 10l-2 2M12 22l-2 2" stroke="#A899D4" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <span class="ws-page-title__name"><?php esc_html_e( 'Crawl Tracker', 'ws-crawl-tracker' ); ?></span>
        <span class="ws-page-title__section"><?php esc_html_e( 'Réglages', 'ws-crawl-tracker' ); ?></span>
      </h1>
      <p class="ws-page-sub"><?php esc_html_e( 'Configuration du suivi de crawl et des robots tracés.', 'ws-crawl-tracker' ); ?></p>
    </header>

    <?php if ( isset( $_GET['saved'] ) ) : ?>
    <div class="ws-notice ws-notice-ok">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
      <?php esc_html_e( 'Réglages sauvegardés.', 'ws-crawl-tracker' ); ?>
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
      <?php wp_nonce_field( 'wsct_save_settings' ); ?>
      <input type="hidden" name="action" value="wsct_save_settings">

      <section class="ws-card">
        <div class="ws-card-head">
          <span class="ws-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 2v4M12 18v4M2 12h4M18 12h4"/><circle cx="12" cy="12" r="4"/></svg>
          </span>
          <div>
            <h2 class="ws-card-title"><?php esc_html_e( 'Suivi', 'ws-crawl-tracker' ); ?></h2>
            <p class="ws-card-desc"><?php esc_html_e( 'Active ou désactive l\'enregistrement des passages de robots.', 'ws-crawl-tracker' ); ?></p>
          </div>
        </div>
        <label class="ws-switch">
          <input type="checkbox" name="wsct_enabled" value="1" <?php checked( $wsct_enabled ); ?>>
          <span class="ws-switch__track"><span class="ws-switch__thumb"></span></span>
          <span class="ws-switch__label"><?php esc_html_e( 'Activer le suivi du crawl', 'ws-crawl-tracker' ); ?></span>
        </label>
      </section>

      <section class="ws-card">
        <div class="ws-card-head">
          <span class="ws-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 2l8 4v6c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6z"/><path d="M9 12l2 2 4-4"/></svg>
          </span>
          <div>
            <h2 class="ws-card-title"><?php esc_html_e( 'Vérification d\'authenticité', 'ws-crawl-tracker' ); ?></h2>
            <p class="ws-card-desc"><?php esc_html_e( 'Vérifie chaque robot par reverse DNS (PTR + forward). Élimine les faux user-agents. Léger surcoût DNS, mis en cache une semaine par IP.', 'ws-crawl-tracker' ); ?></p>
          </div>
        </div>
        <label class="ws-switch">
          <input type="checkbox" name="wsct_verify_dns" value="1" <?php checked( $wsct_dns ); ?>>
          <span class="ws-switch__track"><span class="ws-switch__thumb"></span></span>
          <span class="ws-switch__label"><?php esc_html_e( 'Vérifier les robots par reverse DNS', 'ws-crawl-tracker' ); ?></span>
        </label>
      </section>

      <section class="ws-card">
        <div class="ws-card-head">
          <span class="ws-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
          </span>
          <div>
            <h2 class="ws-card-title"><?php esc_html_e( 'Rétention des données', 'ws-crawl-tracker' ); ?></h2>
            <p class="ws-card-desc"><?php esc_html_e( 'Les hits plus anciens sont supprimés automatiquement chaque jour.', 'ws-crawl-tracker' ); ?></p>
          </div>
        </div>
        <div class="wsct-field-inline">
          <input type="number" min="1" max="3650" name="wsct_retention_days" value="<?php echo esc_attr( $wsct_retention ); ?>" class="ws-input wsct-input-num">
          <span class="wsct-field-suffix"><?php esc_html_e( 'jours', 'ws-crawl-tracker' ); ?></span>
        </div>
      </section>

      <section class="ws-card">
        <div class="ws-card-head">
          <span class="ws-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 9h6v6H9z"/></svg>
          </span>
          <div>
            <h2 class="ws-card-title"><?php esc_html_e( 'Robots tracés', 'ws-crawl-tracker' ); ?></h2>
            <p class="ws-card-desc"><?php esc_html_e( 'Sélectionnez les robots à enregistrer. Les bots IA (GPTBot, ClaudeBot, Perplexity) sont essentiels pour le suivi GEO.', 'ws-crawl-tracker' ); ?></p>
          </div>
        </div>
        <div class="ws-pills">
          <?php foreach ( $wsct_bots as $wsct_k => $wsct_b ) : ?>
          <label class="ws-pill">
            <input type="checkbox" name="wsct_bots[]" value="<?php echo esc_attr( $wsct_k ); ?>" <?php checked( ! empty( $wsct_b['enabled'] ) ); ?>>
            <span><?php echo esc_html( $wsct_b['name'] ); ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="ws-card">
        <div class="ws-card-head">
          <span class="ws-card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><line x1="5.6" y1="5.6" x2="18.4" y2="18.4"/></svg>
          </span>
          <div>
            <h2 class="ws-card-title"><?php esc_html_e( 'Exclusions', 'ws-crawl-tracker' ); ?></h2>
            <p class="ws-card-desc"><?php esc_html_e( 'Trafic à ne jamais enregistrer (outils internes, monitoring, endpoints d\'API). Une entrée par ligne.', 'ws-crawl-tracker' ); ?></p>
          </div>
        </div>

        <div class="wsct-field">
          <label class="wsct-field-label" for="wsct_excluded_ua"><?php esc_html_e( 'User-agents exclus', 'ws-crawl-tracker' ); ?></label>
          <p class="wsct-field-hint"><?php esc_html_e( 'Correspondance « contient », insensible à la casse. Ex. : un crawler maison ou un service de monitoring.', 'ws-crawl-tracker' ); ?></p>
          <textarea id="wsct_excluded_ua" name="wsct_excluded_ua" class="ws-input wsct-textarea" rows="4" spellcheck="false"><?php echo esc_textarea( implode( "\n", (array) $wsct_excl_ua ) ); ?></textarea>
        </div>

        <div class="wsct-field">
          <label class="wsct-field-label" for="wsct_excluded_paths"><?php esc_html_e( 'Chemins exclus', 'ws-crawl-tracker' ); ?></label>
          <p class="wsct-field-hint"><?php esc_html_e( 'Correspondance « commence par », sur le chemin de l\'URL (sans le domaine). Ex. : /wp-json/mon-api/.', 'ws-crawl-tracker' ); ?></p>
          <textarea id="wsct_excluded_paths" name="wsct_excluded_paths" class="ws-input wsct-textarea" rows="3" spellcheck="false"><?php echo esc_textarea( implode( "\n", (array) $wsct_excl_path ) ); ?></textarea>
        </div>
      </section>

      <button type="submit" class="ws-btn-save">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        <?php esc_html_e( 'Sauvegarder les réglages', 'ws-crawl-tracker' ); ?>
      </button>
    </form>

    <section class="ws-card ws-card--dk wsct-danger">
      <div class="ws-card-head">
        <span class="ws-card-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
        </span>
        <div>
          <h2 class="ws-card-title"><?php esc_html_e( 'Purge des données', 'ws-crawl-tracker' ); ?></h2>
          <p class="ws-card-desc"><?php esc_html_e( 'Supprime immédiatement tous les hits enregistrés. Irréversible.', 'ws-crawl-tracker' ); ?></p>
        </div>
      </div>
      <button type="button" id="wsct-purge" class="ws-btn-ghost wsct-btn-danger"><?php esc_html_e( 'Vider toutes les données', 'ws-crawl-tracker' ); ?></button>
    </section>

  </main>
</div>

<script>
(function() {
  var btn = document.getElementById('wsct-purge');
  if (!btn) return;
  btn.addEventListener('click', function() {
    var msg = (window.wsctData && wsctData.i18n && wsctData.i18n.confirmPurge) ? wsctData.i18n.confirmPurge : 'Confirmer ?';
    if (!window.confirm(msg)) return;
    btn.disabled = true;
    var form = new FormData();
    form.append('action', 'wsct_purge');
    form.append('nonce', (window.wsctData && wsctData.nonce) ? wsctData.nonce : '');
    fetch((window.wsctData && wsctData.ajaxUrl) ? wsctData.ajaxUrl : ajaxurl, { method: 'POST', body: form })
      .then(function(r) { return r.json(); })
      .then(function(res) { if (res.success) window.location.reload(); else btn.disabled = false; })
      .catch(function() { btn.disabled = false; });
  });
}());
</script>
