<?php
// Petite bibliothèque SVG pour dessiner des diagrammes UML / Merise sur fond blanc.

const ENCRE = '#1E2B24';
const COUL_TRAIT = '#2F5D46';
const FOND_BOITE = '#F1F7F3';
const FOND_ENTETE = '#DCEDE2';
const POLICE = "Arial, 'Segoe UI', sans-serif";

function esc($s) { return htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }
function largeur_texte($s, $taille = 13) { return mb_strlen((string) $s) * $taille * 0.56; }

class Diagramme {
    public array $els = []; public int $decalage = 0;
    public function __construct(public int $w, public int $h, public string $titre) {}
    public function add(string $s): void { $this->els[] = $s; }
    public function svg(): string {
        $defs = '<defs>'
            . '<marker id="fleche" viewBox="0 0 12 12" refX="11" refY="6" markerWidth="11" markerHeight="11" orient="auto-start-reverse"><path d="M0,0 L12,6 L0,12 z" fill="' . COUL_TRAIT . '"/></marker>'
            . '<marker id="ouverte" viewBox="0 0 12 12" refX="11" refY="6" markerWidth="11" markerHeight="11" orient="auto-start-reverse"><path d="M0,0 L12,6 L0,12" fill="none" stroke="' . COUL_TRAIT . '" stroke-width="1.6"/></marker>'
            . '<marker id="heritage" viewBox="0 0 16 16" refX="15" refY="8" markerWidth="16" markerHeight="16" orient="auto-start-reverse"><path d="M0,0 L16,8 L0,16 z" fill="#fff" stroke="' . COUL_TRAIT . '" stroke-width="1.4"/></marker>'
            . '<marker id="losange" viewBox="0 0 20 12" refX="1" refY="6" markerWidth="20" markerHeight="12" orient="auto"><path d="M1,6 L10,1 L19,6 L10,11 z" fill="' . COUL_TRAIT . '"/></marker>'
            . '</defs>';
        $titre = '<text x="' . ($this->w / 2) . '" y="30" text-anchor="middle" font-family="' . POLICE . '" font-size="19" font-weight="bold" fill="' . ENCRE . '">' . esc($this->titre) . '</text>';
        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $this->w . '" height="' . ($this->h + $this->decalage) . '" viewBox="0 0 ' . $this->w . ' ' . ($this->h + $this->decalage) . '">'
            . $defs . '<rect width="100%" height="100%" fill="#ffffff"/>' . $titre . '<g transform="translate(0,' . $this->decalage . ')">' . implode("\n", $this->els) . '</g></svg>';
    }
}

function texte(Diagramme $d, $x, $y, $s, array $o = []) {
    $taille = $o['taille'] ?? 13; $ancre = $o['ancre'] ?? 'start';
    $poids = !empty($o['gras']) ? ' font-weight="bold"' : '';
    $ital = !empty($o['italique']) ? ' font-style="italic"' : '';
    $soul = !empty($o['souligne']) ? ' text-decoration="underline"' : '';
    $coul = $o['couleur'] ?? ENCRE;
    $fond = '';
    if (!empty($o['fond'])) {
        $lw = largeur_texte($s, $taille) + 6;
        $fx = $ancre === 'middle' ? $x - $lw / 2 : ($ancre === 'end' ? $x - $lw + 3 : $x - 3);
        $fond = '<rect x="' . $fx . '" y="' . ($y - $taille + 1) . '" width="' . $lw . '" height="' . ($taille + 4) . '" fill="#ffffff"/>';
    }
    $d->add($fond . '<text x="' . $x . '" y="' . $y . '" text-anchor="' . $ancre . '" font-family="' . POLICE . '" font-size="' . $taille . '"' . $poids . $ital . $soul . ' fill="' . $coul . '">' . esc($s) . '</text>');
}

/** Ligne brisée. $o : pointille, debut / fin = nom de marqueur, epaisseur */
function ligne(Diagramme $d, array $pts, array $o = []) {
    $p = implode(' ', array_map(fn($q) => $q[0] . ',' . $q[1], $pts));
    $dash = !empty($o['pointille']) ? ' stroke-dasharray="7,5"' : '';
    $deb = !empty($o['debut']) ? ' marker-start="url(#' . $o['debut'] . ')"' : '';
    $fin = !empty($o['fin']) ? ' marker-end="url(#' . $o['fin'] . ')"' : '';
    $ep = $o['epaisseur'] ?? 1.5;
    $d->add('<polyline points="' . $p . '" fill="none" stroke="' . ($o['couleur'] ?? COUL_TRAIT) . '" stroke-width="' . $ep . '"' . $dash . $deb . $fin . '/>');
}

/** Boîte de classe UML. Retourne [x, y, w, h]. */
function classe(Diagramme $d, $x, $y, $w, $nom, array $attrs, array $o = []) {
    $entete = 30 + (!empty($o['stereo']) ? 15 : 0);
    $h = $entete + 10 + count($attrs) * 17 + (empty($attrs) ? 6 : 0);
    $d->add('<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" rx="4" fill="' . FOND_BOITE . '" stroke="' . COUL_TRAIT . '" stroke-width="1.5"/>');
    $d->add('<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $entete . '" rx="4" fill="' . FOND_ENTETE . '" stroke="' . COUL_TRAIT . '" stroke-width="1.5"/>');
    $ty = $y + 20;
    if (!empty($o['stereo'])) { texte($d, $x + $w / 2, $y + 15, '«' . $o['stereo'] . '»', ['ancre' => 'middle', 'taille' => 11]); $ty = $y + 33; }
    texte($d, $x + $w / 2, $ty, $nom, ['ancre' => 'middle', 'gras' => true, 'taille' => 14, 'italique' => !empty($o['abstraite'])]);
    foreach ($attrs as $i => $a) {
        $opt = ['taille' => 12];
        if (is_array($a)) { $opt += $a[1]; $a = $a[0]; }
        texte($d, $x + 9, $y + $entete + 18 + $i * 17, $a, $opt);
    }
    return [$x, $y, $w, $h];
}

function haut($b, $f = .5) { return [$b[0] + $b[2] * $f, $b[1]]; }
function bas($b, $f = .5) { return [$b[0] + $b[2] * $f, $b[1] + $b[3]]; }
function gauche($b, $f = .5) { return [$b[0], $b[1] + $b[3] * $f]; }
function droite($b, $f = .5) { return [$b[0] + $b[2], $b[1] + $b[3] * $f]; }

function acteur(Diagramme $d, $cx, $y, $nom) {
    $s = 'stroke="' . COUL_TRAIT . '" stroke-width="1.8" fill="none"';
    $d->add('<circle cx="' . $cx . '" cy="' . ($y + 10) . '" r="10" ' . $s . ' fill="#fff"/>'
        . '<line x1="' . $cx . '" y1="' . ($y + 20) . '" x2="' . $cx . '" y2="' . ($y + 48) . '" ' . $s . '/>'
        . '<line x1="' . ($cx - 18) . '" y1="' . ($y + 30) . '" x2="' . ($cx + 18) . '" y2="' . ($y + 30) . '" ' . $s . '/>'
        . '<line x1="' . $cx . '" y1="' . ($y + 48) . '" x2="' . ($cx - 15) . '" y2="' . ($y + 70) . '" ' . $s . '/>'
        . '<line x1="' . $cx . '" y1="' . ($y + 48) . '" x2="' . ($cx + 15) . '" y2="' . ($y + 70) . '" ' . $s . '/>');
    foreach ((array) $nom as $i => $l) texte($d, $cx, $y + 88 + $i * 15, $l, ['ancre' => 'middle', 'gras' => true, 'fond' => true]);
}

function cas(Diagramme $d, $cx, $cy, $texte, $rx = 150, $ry = 23) {
    $d->add('<ellipse cx="' . $cx . '" cy="' . $cy . '" rx="' . $rx . '" ry="' . $ry . '" fill="' . FOND_BOITE . '" stroke="' . COUL_TRAIT . '" stroke-width="1.5"/>');
    $lignes = (array) $texte; $n = count($lignes);
    foreach ($lignes as $i => $l) texte($d, $cx, $cy + 5 + ($i - ($n - 1) / 2) * 15, $l, ['ancre' => 'middle']);
    return [$cx - $rx, $cy - $ry, 2 * $rx, 2 * $ry];
}

/** Action (rectangle arrondi) centrée. */
function action(Diagramme $d, $cx, $cy, $texte, $w = 240, $h = null) {
    $lignes = (array) $texte; $h ??= 22 + 16 * count($lignes);
    $d->add('<rect x="' . ($cx - $w / 2) . '" y="' . ($cy - $h / 2) . '" width="' . $w . '" height="' . $h . '" rx="14" fill="' . FOND_BOITE . '" stroke="' . COUL_TRAIT . '" stroke-width="1.5"/>');
    foreach ($lignes as $i => $l) texte($d, $cx, $cy + 5 + ($i - (count($lignes) - 1) / 2) * 16, $l, ['ancre' => 'middle']);
    return [$cx - $w / 2, $cy - $h / 2, $w, $h];
}

function decision(Diagramme $d, $cx, $cy, $r = 18) {
    $d->add('<polygon points="' . $cx . ',' . ($cy - $r) . ' ' . ($cx + $r) . ',' . $cy . ' ' . $cx . ',' . ($cy + $r) . ' ' . ($cx - $r) . ',' . $cy . '" fill="#FFF7E0" stroke="#B07F0E" stroke-width="1.5"/>');
    return [$cx - $r, $cy - $r, 2 * $r, 2 * $r];
}
function debut(Diagramme $d, $cx, $cy) { $d->add('<circle cx="' . $cx . '" cy="' . $cy . '" r="11" fill="' . ENCRE . '"/>'); return [$cx - 11, $cy - 11, 22, 22]; }
function fin(Diagramme $d, $cx, $cy) { $d->add('<circle cx="' . $cx . '" cy="' . $cy . '" r="13" fill="#fff" stroke="' . ENCRE . '" stroke-width="2"/><circle cx="' . $cx . '" cy="' . $cy . '" r="8" fill="' . ENCRE . '"/>'); return [$cx - 13, $cy - 13, 26, 26]; }

/** Nœud de déploiement (boîte 3D). */
function noeud(Diagramme $d, $x, $y, $w, $h, $stereo, $nom) {
    $e = 12;
    $d->add('<polygon points="' . $x . ',' . $y . ' ' . ($x + $e) . ',' . ($y - $e) . ' ' . ($x + $w + $e) . ',' . ($y - $e) . ' ' . ($x + $w + $e) . ',' . ($y + $h - $e) . ' ' . ($x + $w) . ',' . ($y + $h) . ' ' . ($x + $w) . ',' . $y . '" fill="' . FOND_ENTETE . '" stroke="' . COUL_TRAIT . '" stroke-width="1.5"/>');
    $d->add('<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" fill="' . FOND_BOITE . '" stroke="' . COUL_TRAIT . '" stroke-width="1.5"/>');
    texte($d, $x + $w / 2, $y + 18, '«' . $stereo . '»', ['ancre' => 'middle', 'taille' => 11]);
    texte($d, $x + $w / 2, $y + 35, $nom, ['ancre' => 'middle', 'gras' => true]);
    return [$x, $y, $w, $h];
}
function artefact(Diagramme $d, $x, $y, $w, $lignes, $stereo = 'artifact') {
    $lignes = (array) $lignes; $h = 26 + 16 * count($lignes);
    $d->add('<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" rx="3" fill="#ffffff" stroke="' . COUL_TRAIT . '" stroke-width="1.3"/>');
    texte($d, $x + $w / 2, $y + 15, '«' . $stereo . '»', ['ancre' => 'middle', 'taille' => 10]);
    foreach ($lignes as $i => $l) texte($d, $x + $w / 2, $y + 32 + $i * 16, $l, ['ancre' => 'middle', 'taille' => 12, 'gras' => $i === 0]);
    return [$x, $y, $w, $h];
}

/** État (rectangle arrondi) pour les diagrammes d'états. */
function etat(Diagramme $d, $cx, $cy, $nom, $detail = null, $w = 190) {
    $h = $detail ? 56 : 40;
    $d->add('<rect x="' . ($cx - $w / 2) . '" y="' . ($cy - $h / 2) . '" width="' . $w . '" height="' . $h . '" rx="16" fill="' . FOND_BOITE . '" stroke="' . COUL_TRAIT . '" stroke-width="1.6"/>');
    texte($d, $cx, $cy + ($detail ? -4 : 5), $nom, ['ancre' => 'middle', 'gras' => true, 'taille' => 14]);
    if ($detail) texte($d, $cx, $cy + 16, $detail, ['ancre' => 'middle', 'taille' => 11, 'couleur' => '#55635A']);
    return [$cx - $w / 2, $cy - $h / 2, $w, $h];
}

/** Entité Merise (MCD). */
function entite(Diagramme $d, $x, $y, $w, $nom, array $attrs, $note = null) {
    $h = 32 + 8 + count($attrs) * 17 + ($note ? 17 : 0);
    $d->add('<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" fill="' . FOND_BOITE . '" stroke="' . COUL_TRAIT . '" stroke-width="1.6"/>');
    $d->add('<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="32" fill="' . FOND_ENTETE . '" stroke="' . COUL_TRAIT . '" stroke-width="1.6"/>');
    texte($d, $x + $w / 2, $y + 21, $nom, ['ancre' => 'middle', 'gras' => true, 'taille' => 14]);
    foreach ($attrs as $i => $a) texte($d, $x + 9, $y + 52 + $i * 17, $a, ['taille' => 12, 'souligne' => $i === 0 && $a !== '' && !str_starts_with($a, '(')]);
    if ($note) texte($d, $x + 9, $y + 52 + count($attrs) * 17, $note, ['taille' => 11, 'italique' => true, 'couleur' => '#55635A']);
    return [$x, $y, $w, $h];
}
/** Association Merise (ellipse) avec attributs éventuels. */
function association(Diagramme $d, $cx, $cy, $nom, array $attrs = [], $rx = 62) {
    $ry = 20 + count($attrs) * 8;
    $d->add('<ellipse cx="' . $cx . '" cy="' . $cy . '" rx="' . $rx . '" ry="' . $ry . '" fill="#FFF7E0" stroke="#B07F0E" stroke-width="1.5"/>');
    $y0 = $cy + 5 - count($attrs) * 8;
    texte($d, $cx, $y0, $nom, ['ancre' => 'middle', 'gras' => true, 'taille' => 12]);
    foreach ($attrs as $i => $a) texte($d, $cx, $y0 + 16 + $i * 15, $a, ['ancre' => 'middle', 'taille' => 11]);
    return [$cx - $rx, $cy - $ry, 2 * $rx, 2 * $ry];
}

// ---------------- Diagramme de séquence ----------------
class Sequence {
    public array $x = []; public int $y; public int $n = 0;
    public function __construct(public Diagramme $d, array $participants, int $y0 = 60) {
        $this->y = $y0;
        foreach ($participants as $cle => [$cx, $nom, $type]) {
            $this->x[$cle] = $cx;
            if ($type === 'acteur') { acteur($d, $cx, $y0, $nom); }
            else {
                $l = (array) $nom; $w = max(array_map(fn($s) => largeur_texte($s, 13), $l)) + 26; $h = 18 + 16 * count($l);
                $d->add('<rect x="' . ($cx - $w / 2) . '" y="' . ($y0 + 20) . '" width="' . $w . '" height="' . $h . '" rx="4" fill="' . ($type === 'base' ? '#E6EEF8' : FOND_ENTETE) . '" stroke="' . COUL_TRAIT . '" stroke-width="1.5"/>');
                foreach ($l as $i => $s) texte($d, $cx, $y0 + 40 + $i * 16, $s, ['ancre' => 'middle', 'gras' => $i === 0, 'taille' => $i === 0 ? 13 : 11]);
            }
        }
        $this->y = $y0 + 125;
    }
    public function lignes_de_vie(int $yfin): void {
        foreach ($this->x as $cx) $this->d->els = array_merge(['<line x1="' . $cx . '" y1="' . ($this->y0debut ?? 175) . '" x2="' . $cx . '" y2="' . $yfin . '" stroke="#7A8F82" stroke-width="1.2" stroke-dasharray="6,5"/>'], $this->d->els);
    }
    public ?int $y0debut = null;
    public function msg($de, $a, $texte, $retour = false, $pas = 34) {
        $this->n++; $x1 = $this->x[$de]; $x2 = $this->x[$a]; $y = $this->y;
        $lib = $this->n . '. ' . $texte;
        if ($de === $a) {
            ligne($this->d, [[$x1, $y - 6], [$x1 + 40, $y - 6], [$x1 + 40, $y + 10], [$x1 + 3, $y + 10]], ['fin' => 'fleche']);
            texte($this->d, $x1 + 48, $y + 6, $lib, ['taille' => 12, 'fond' => true]);
            $this->y += $pas + 8; return;
        }
        ligne($this->d, [[$x1, $y], [$x2, $y]], ['fin' => $retour ? 'ouverte' : 'fleche', 'pointille' => $retour]);
        $mx = $x1 < $x2 ? $x1 + 10 : $x2 + 10;
        texte($this->d, $mx, $y - 7, $lib, ['taille' => 12, 'fond' => true]);
        $this->y += $pas;
    }
    public array $pile = [];
    public function fragment($type, $garde, $x1, $x2) { $this->pile[] = [$type, $garde, $x1, $x2, $this->y - 8, []]; $this->y += 36; }
    public function sinon($garde) {
        $i = count($this->pile) - 1; $this->pile[$i][5][] = [$this->y - 10, $garde]; $this->y += 30;
    }
    public function fin_fragment() {
        [$type, $garde, $x1, $x2, $y1, $sinon] = array_pop($this->pile);
        $y2 = $this->y - 8;
        $this->d->add('<rect x="' . $x1 . '" y="' . $y1 . '" width="' . ($x2 - $x1) . '" height="' . ($y2 - $y1) . '" fill="none" stroke="#4A5A50" stroke-width="1.3"/>');
        $lw = largeur_texte($type, 12) + 18;
        $this->d->add('<path d="M' . $x1 . ',' . $y1 . ' h' . $lw . ' v12 l-8,8 h-' . ($lw - 8) . ' z" fill="#EEF2EF" stroke="#4A5A50" stroke-width="1.2"/>');
        texte($this->d, $x1 + 6, $y1 + 15, $type, ['taille' => 12, 'gras' => true]);
        texte($this->d, $x1 + $lw + 8, $y1 + 15, '[' . $garde . ']', ['taille' => 12, 'italique' => true]);
        foreach ($sinon as [$ys, $g]) {
            $this->d->add('<line x1="' . $x1 . '" y1="' . $ys . '" x2="' . $x2 . '" y2="' . $ys . '" stroke="#4A5A50" stroke-width="1.2" stroke-dasharray="6,4"/>');
            texte($this->d, $x1 + 8, $ys + 15, '[' . $g . ']', ['taille' => 12, 'italique' => true]);
        }
        $this->y += 20;
    }
    public function separateur($texte) {
        $y = $this->y;
        $this->d->add('<line x1="20" y1="' . $y . '" x2="' . ($this->d->w - 20) . '" y2="' . $y . '" stroke="#4A5A50" stroke-width="1"/>');
        $w = largeur_texte($texte, 12) + 24;
        $this->d->add('<rect x="' . ($this->d->w / 2 - $w / 2) . '" y="' . ($y - 11) . '" width="' . $w . '" height="22" rx="4" fill="#EEF2EF" stroke="#4A5A50"/>');
        texte($this->d, $this->d->w / 2, $y + 5, $texte, ['ancre' => 'middle', 'taille' => 12, 'gras' => true]);
        $this->y += 36;
    }
}
