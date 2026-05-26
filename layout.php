<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

// Inline SVG icons matching components.jsx ICONS map
function icon(string $name, int $size = 20, float $sw = 1.75, string $extra = ''): string {
    static $icons = [
        'search'        => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'upload'        => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>',
        'download'      => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'file'          => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
        'star'          => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'bookmark'      => '<path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>',
        'bookmark-fill' => '<path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" fill="currentColor"/>',
        'bell'          => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'chevron-right' => '<polyline points="9 18 15 12 9 6"/>',
        'check'         => '<polyline points="20 6 9 17 4 12"/>',
        'close'         => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'info'          => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        'trash'         => '<polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/>',
        'more'          => '<circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>',
        'heart'         => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
        'user'          => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M7 20.66c.93-2.31 3.21-4 5.99-4s5.06 1.69 5.99 4"/>',
        'trending'      => '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',
        'filter'        => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
        'menu'          => '<path d="M3 12h18M3 6h18M3 18h18"/>',
        'spark'         => '<path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1"/>',
    ];
    $paths = $icons[$name] ?? '';
    return "<svg width=\"{$size}\" height=\"{$size}\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"{$sw}\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"flex-shrink:0;{$extra}\">{$paths}</svg>";
}

// Avatar color — port of JS avatarColor()
function avatar_bg(string $name): string {
    $colors = ['#2C58F2','#FFD43F','#1FA663','#1632AC','#F09000','#1E84D6','#7A0E1A'];
    $h = 0;
    foreach (str_split($name) as $c) $h = ($h * 31 + ord($c)) % count($colors);
    return $colors[$h];
}

function render_avatar(string $name, int $size = 32): string {
    $bg  = avatar_bg($name);
    $fg  = ($bg === '#FFD43F') ? '#10141F' : '#FFFFFF';
    $fs  = (int)($size * 0.42);
    $ini = strtoupper(mb_substr(trim($name), 0, 1)) ?: '?';
    return "<span class=\"avatar\" style=\"width:{$size}px;height:{$size}px;background:{$bg};color:{$fg};font-size:{$fs}px;\">{$ini}</span>";
}

// Reviewer thumb gradient helpers
function thumb_bg(int $index): string {
    return match($index % 3) {
        1 => 'linear-gradient(180deg,#FFE680 0%,#FFFBEA 100%)',
        2 => 'linear-gradient(180deg,#B6E8CB 0%,#E8F8EF 100%)',
        default => 'linear-gradient(180deg,#DCE6FF 0%,#EEF3FF 100%)',
    };
}

function thumb_line(int $index): array {
    return match($index % 3) {
        1 => ['rgba(122,92,0,0.55)', 'rgba(122,92,0,0.35)'],
        2 => ['rgba(21,109,67,0.52)', 'rgba(21,109,67,0.32)'],
        default => ['rgba(22,50,172,0.55)', 'rgba(22,50,172,0.32)'],
    };
}

function reviewer_card(array $mat, int $index = 0): string {
    $bg    = thumb_bg($index);
    [$lc1, $lc2] = thumb_line($index);
    $avBg  = $mat['avatar_color'] ?? avatar_bg($mat['display_name']);
    $avFg  = ($avBg === '#FFD43F') ? '#10141F' : '#FFFFFF';
    $avIni = strtoupper(mb_substr($mat['display_name'], 0, 1));
    $dl    = $mat['download_count'] >= 1000
             ? round($mat['download_count'] / 1000, 1) . 'k'
             : $mat['download_count'];
    $subj  = e($mat['subject_name'] ?? '');
    $course= e($mat['course_code']  ?? '');
    $id    = (int)$mat['id'];
    $title = e($mat['title']);
    $ft    = e(strtoupper($mat['file_type']));

    return <<<HTML
<a href="detail.php?id={$id}" class="rv-card" style="text-decoration:none;">
  <div class="rv-thumb" style="background:{$bg};">
    <div class="rv-thumb-lines">
      <div class="l" style="width:60%;background:{$lc1};"></div>
      <div class="l" style="width:92%;background:{$lc2};"></div>
      <div class="l" style="width:78%;background:{$lc2};"></div>
      <div class="l" style="width:85%;background:{$lc2};"></div>
      <div class="l" style="width:65%;background:{$lc2};"></div>
    </div>
    <span class="rv-thumb-badge">{$ft}</span>
  </div>
  <div class="rv-title">{$title}</div>
  <div class="rv-meta">
    {$subj}<span class="dot"></span>{$course}
  </div>
  <div class="rv-foot">
    <div class="rv-author">
      <span class="avatar" style="width:22px;height:22px;font-size:9px;background:{$avBg};color:{$avFg};">{$avIni}</span>
      <span>{$mat['display_name']}</span>
    </div>
    <div class="rv-stats">★ · ↓ {$dl}</div>
  </div>
</a>
HTML;
}

function layout_start(string $title, string $activePage = ''): void {
    $user = current_user();
    $logo = file_exists(__DIR__ . '/assets/logo.svg') ? 'assets/logo.svg' : null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title><?= e($title) ?> — StudyDrop</title>
<link rel="stylesheet" href="colors_and_type.css"/>
<link rel="stylesheet" href="styles.css"/>
</head>
<body>
<div class="app">

<nav class="nav">
  <div class="container">
    <div class="nav-inner">

      <a href="index.php" style="display:flex;align-items:center;flex-shrink:0;">
        <?php if ($logo): ?>
          <img src="<?= e($logo) ?>" alt="StudyDrop" class="nav-logo"/>
        <?php else: ?>
          <span style="font-family:var(--font-display);font-weight:800;font-size:22px;color:var(--brand-blue-600);letter-spacing:-0.02em;">StudyDrop</span>
        <?php endif; ?>
      </a>

      <div class="nav-links">
        <a class="nav-link <?= $activePage==='browse'?'active':'' ?>" href="index.php">Browse</a>
        <a class="nav-link <?= $activePage==='search'?'active':'' ?>" href="search.php">Subjects</a>
        <a class="nav-link" href="search.php?filter=schools">Schools</a>
      </div>

      <form class="nav-search" action="search.php" method="GET" role="search">
        <?= icon('search',16,2,'color:var(--fg-3)') ?>
        <input type="search" name="q"
               placeholder="Search reviewers, subjects, schools..."
               value="<?= e($_GET['q']??'') ?>" autocomplete="off"/>
        <kbd class="nav-kbd">⌘K</kbd>
      </form>

      <div class="nav-right">
        <?php if ($user): ?>
          <button class="icon-btn" aria-label="Notifications"><?= icon('bell',20) ?></button>
          <a href="upload.php" class="btn btn-accent btn-sm">
            <?= icon('upload',14,2) ?> Upload
          </a>
          <a href="dashboard.php" style="display:flex;">
            <?php
              $bg  = $user['avatar_color'];
              $fg  = ($bg==='#FFD43F') ? '#10141F' : '#FFFFFF';
              $ini = strtoupper(mb_substr($user['display_name'],0,1));
            ?>
            <span class="avatar" style="width:34px;height:34px;font-size:14px;background:<?= e($bg) ?>;color:<?= e($fg) ?>;cursor:pointer;"><?= e($ini) ?></span>
          </a>
        <?php else: ?>
          <a href="auth.php" class="btn btn-ghost btn-sm">Log in</a>
          <a href="auth.php?mode=signup" class="btn btn-primary btn-sm">Sign up free</a>
        <?php endif; ?>
      </div>

    </div>
  </div>
</nav>

<?php
  $fs = get_flash('success'); $fe = get_flash('error');
  if ($fs) echo "<div style='background:var(--color-success-50);border-bottom:1px solid #b7e7cf;padding:12px;text-align:center;font-size:14px;color:var(--color-success-700);font-weight:600;'>".e($fs)."</div>";
  if ($fe) echo "<div style='background:var(--color-danger-50);border-bottom:1px solid #f5c2c2;padding:12px;text-align:center;font-size:14px;color:var(--color-danger-700);font-weight:600;'>".e($fe)."</div>";
?>
<?php
}

function layout_end(): void {
    $logoDark = file_exists(__DIR__.'/assets/logo-on-dark.svg') ? 'assets/logo-on-dark.svg' : null;
?>
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <?php if ($logoDark): ?>
          <img src="<?= e($logoDark) ?>" alt="StudyDrop" style="height:32px;margin-bottom:12px;display:block;"/>
        <?php else: ?>
          <div style="font-family:var(--font-display);font-weight:800;font-size:24px;color:white;margin-bottom:12px;letter-spacing:-0.02em;">StudyDrop</div>
        <?php endif; ?>
        <p style="font-size:14px;line-height:1.55;color:var(--neutral-400);max-width:280px;">
          Free reviewers from Filipino college students, for Filipino college students. Libre forever.
        </p>
        <a href="auth.php" style="display:inline-flex;align-items:center;gap:6px;color:var(--brand-yellow-300);font-weight:600;font-size:14px;margin-top:18px;">
          Sign in / Sign up →
        </a>
      </div>
      <div>
        <h5>Product</h5>
        <a class="footer-link" href="index.php">Browse</a>
        <a class="footer-link" href="search.php">Search</a>
        <a class="footer-link" href="upload.php">Upload</a>
        <a class="footer-link" href="search.php">Subjects</a>
      </div>
      <div>
        <h5>Community</h5>
        <a class="footer-link" href="#">Code of conduct</a>
        <a class="footer-link" href="#">Contributors</a>
        <a class="footer-link" href="#">Discord</a>
        <a class="footer-link" href="#">Report content</a>
      </div>
      <div>
        <h5>About</h5>
        <a class="footer-link" href="#">Our story</a>
        <a class="footer-link" href="#">FAQ</a>
        <a class="footer-link" href="#">Privacy</a>
        <a class="footer-link" href="#">Terms</a>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> StudyDrop · Made in Manila ☕</span>
      <span style="font-family:var(--font-mono);font-size:12px;">v0.4.2</span>
    </div>
  </div>
</footer>
</div>
</body>
</html>
<?php
}