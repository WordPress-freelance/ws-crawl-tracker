"""Génère les assets PNG de WS Crawl Tracker via cairosvg.

Icône = loupe violette (motif du header dashboard) sur fond charte,
avec le triple-pic WebStrategy intégré dans la lentille.
Banner = fond dark + montagne WebStrategy + titre + tagline.
"""
from pathlib import Path
import cairosvg

ASSETS = Path(__file__).parent / "assets"
ASSETS.mkdir(exist_ok=True)

# Palette WebStrategy (charte.md)
BG      = "#14121C"
BG_DEEP = "#221D32"
ACCENT  = "#7C5CBF"
ACC_MID = "#9B8EC4"
ACC_L   = "#A899D4"
TEXT    = "#F0EDE8"
TEXT_S  = "#C4BFDA"
S_DARK  = "#5B4D9C"
S_DEEP  = "#463A78"


def write_png(svg, name, w, h):
    cairosvg.svg2png(bytestring=svg.encode("utf-8"),
                     output_width=w, output_height=h,
                     write_to=str(ASSETS / name))
    print(f"  ✓ {name} ({w}×{h})")


# Triple-pic WebStrategy réutilisable (viewBox 46×30 d'origine, repositionnable)
def mountain(scale, tx, ty, idp):
    return f"""
  <g transform="translate({tx},{ty}) scale({scale})">
    <defs>
      <linearGradient id="{idp}C" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="{ACC_L}"/><stop offset="1" stop-color="{ACCENT}"/>
      </linearGradient>
      <linearGradient id="{idp}S" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="#6E5FC0"/><stop offset="1" stop-color="{S_DEEP}"/>
      </linearGradient>
    </defs>
    <polygon points="2,26 10,8 10,26"   fill="{S_DEEP}"/>
    <polygon points="10,8 10,26 18,26"  fill="{S_DARK}"/>
    <polygon points="13,26 23,4 23,26"  fill="url(#{idp}S)"/>
    <polygon points="23,4 23,26 33,26"  fill="url(#{idp}C)"/>
    <polygon points="28,26 36,8 36,26"  fill="{S_DARK}"/>
    <polygon points="36,8 36,26 44,26"  fill="{S_DEEP}"/>
  </g>"""


# ── Icône : loupe (le motif identitaire du plugin) ─────────────────────────────
# Fond arrondi charte, anneau de loupe violet, manche, et triple-pic dans la lentille.
def icon_svg():
    return f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256">
  <defs>
    <linearGradient id="ring" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{ACC_L}"/><stop offset="1" stop-color="{ACCENT}"/>
    </linearGradient>
    <radialGradient id="lens" cx="0.5" cy="0.42" r="0.6">
      <stop offset="0" stop-color="{BG_DEEP}"/><stop offset="1" stop-color="{BG}"/>
    </radialGradient>
  </defs>

  <!-- fond -->
  <rect width="256" height="256" rx="56" fill="{BG}"/>
  <rect x="3" y="3" width="250" height="250" rx="54" fill="none" stroke="{BG_DEEP}" stroke-width="2"/>

  <!-- manche de la loupe -->
  <line x1="150" y1="150" x2="205" y2="205" stroke="{ACCENT}" stroke-width="26" stroke-linecap="round"/>
  <line x1="150" y1="150" x2="205" y2="205" stroke="{ACC_L}" stroke-width="10" stroke-linecap="round"/>

  <!-- lentille -->
  <circle cx="108" cy="108" r="68" fill="url(#lens)" stroke="url(#ring)" stroke-width="14"/>

  <!-- triple-pic WebStrategy dans la lentille -->
  {mountain(2.05, 63, 78, "ic")}
</svg>"""


write_png(icon_svg(), "icon-256x256.png", 256, 256)
write_png(icon_svg(), "icon-128x128.png", 128, 128)


# ── Banner 1544×500 ────────────────────────────────────────────────────────────
def banner_svg():
    return f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1544 500">
  <defs>
    <linearGradient id="bgGrad" x1="0" x2="1" y1="0" y2="1">
      <stop offset="0" stop-color="{ACCENT}" stop-opacity="0.16"/>
      <stop offset="0.6" stop-color="{BG}" stop-opacity="0"/>
    </linearGradient>
    <radialGradient id="glow" cx="0.78" cy="0.4" r="0.5">
      <stop offset="0" stop-color="{ACCENT}" stop-opacity="0.22"/>
      <stop offset="1" stop-color="{BG}" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="ringB" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{ACC_L}"/><stop offset="1" stop-color="{ACCENT}"/>
    </linearGradient>
  </defs>

  <rect width="1544" height="500" fill="{BG}"/>
  <rect width="1544" height="500" fill="url(#bgGrad)"/>
  <rect width="1544" height="500" fill="url(#glow)"/>

  <!-- montagne décorative en filigrane à droite -->
  <g opacity="0.18">{mountain(9.5, 1060, 30, "bn")}</g>

  <!-- loupe à gauche -->
  <g transform="translate(96,140)">
    <line x1="150" y1="150" x2="205" y2="205" stroke="{ACCENT}" stroke-width="24" stroke-linecap="round"/>
    <line x1="150" y1="150" x2="205" y2="205" stroke="{ACC_L}" stroke-width="9" stroke-linecap="round"/>
    <circle cx="108" cy="108" r="66" fill="{BG_DEEP}" stroke="url(#ringB)" stroke-width="13"/>
    {mountain(1.95, 65, 80, "bl")}
  </g>

  <!-- texte -->
  <text x="430" y="232" font-family="Lora,Georgia,serif" font-weight="700"
        font-size="78" fill="{TEXT}">WS Crawl Tracker</text>
  <text x="432" y="292" font-family="Inter,Arial,sans-serif" font-weight="400"
        font-size="30" fill="{TEXT_S}">Suivez Googlebot et les robots SEO / IA sur votre site.</text>
  <g transform="translate(432,330)">
    <rect width="148" height="50" rx="9" fill="{ACCENT}"/>
    <text x="74" y="33" text-anchor="middle" font-family="Inter,Arial,sans-serif"
          font-weight="600" font-size="18" fill="{TEXT}">WebStrategy</text>
  </g>
</svg>"""


write_png(banner_svg(), "banner-1544x500.png", 1544, 500)
write_png(banner_svg(), "banner-772x250.png", 772, 250)

print("\n✅ Assets générés dans " + str(ASSETS))
