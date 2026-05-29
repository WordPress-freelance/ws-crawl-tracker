=== WS Crawl Tracker ===
Contributors: webstrategy
Tags: seo, googlebot, crawl, bot, log, geo, search engine, ai bots
Requires at least: 5.6
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Tracez le passage de Googlebot et des robots SEO/IA sur votre site : timeline, chemin de crawl, pages les plus visitées et recommandations.

== Description ==

WS Crawl Tracker enregistre chaque passage des robots d'indexation sur votre site WordPress dans une table dédiée, puis vous offre un tableau de bord clair pour comprendre comment les moteurs explorent votre contenu.

Pensé pour le SEO **et** le GEO (Generative Engine Optimization), il suit aussi bien Googlebot et Bingbot que les robots des moteurs génératifs : GPTBot, OAI-SearchBot, ClaudeBot et PerplexityBot.

= Ce que vous voyez =

* **Activité dans le temps** — le volume de crawl jour par jour
* **Répartition par robot** — quels bots passent, et combien
* **Répartition horaire** — à quelles heures les robots explorent
* **Santé technique** — les codes HTTP renvoyés aux robots (200, 301, 404, 5xx)
* **Pages les plus crawlées** — où les bots concentrent leur budget de crawl
* **Chemin de crawl** — le parcours étape par étape d'une session de robot
* **Flux des derniers passages** — le détail en temps réel
* **Recommandations automatiques** — actions prioritaires déduites de vos données

= Vérification d'authenticité =

Chaque robot peut être vérifié par **reverse DNS** (PTR + forward lookup), pour distinguer les vrais Googlebot des faux user-agents. Le résultat est mis en cache une semaine par IP pour limiter le surcoût.

= Respect de la performance =

L'enregistrement se fait au `shutdown`, sans ralentir la page. Les anciennes données sont purgées automatiquement selon la durée de rétention que vous choisissez.

== Installation ==

1. Installez le plugin depuis le répertoire WordPress ou en téléversant le dossier dans `/wp-content/plugins/`.
2. Activez le plugin.
3. Rendez-vous dans **Crawl Tracker** dans le menu d'administration.
4. Ajustez les robots suivis et la rétention dans **Crawl Tracker → Réglages**.

== Frequently Asked Questions ==

= Est-ce que ça ralentit mon site ? =

Non. La détection se fait sur une requête déjà servie et l'écriture en base intervient au `shutdown`, après l'envoi de la page.

= Les données sont-elles fiables ? =

Activez la vérification reverse DNS dans les réglages : seuls les robots dont l'IP correspond réellement au domaine officiel sont marqués comme vérifiés.

= Quels robots IA sont suivis ? =

GPTBot, OAI-SearchBot, ClaudeBot et PerplexityBot sont activés par défaut. D'autres (Applebot, YandexBot, DuckDuckBot, Meta AI) sont disponibles et désactivés par défaut.

= Que se passe-t-il à la désinstallation ? =

La table de données et toutes les options sont supprimées proprement.

== Screenshots ==

1. Tableau de bord : KPIs, activité dans le temps et recommandations.
2. Pages les plus crawlées et santé technique.
3. Chemin de crawl d'une session, étape par étape.
4. Réglages : robots suivis, vérification DNS, rétention.

== Changelog ==

= 1.0.0 =
* Version initiale : tracking multi-robots, dashboard complet, vérification reverse DNS, recommandations, purge automatique.

== Upgrade Notice ==

= 1.0.0 =
Première version.
