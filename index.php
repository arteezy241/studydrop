<?php


require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/layout.php';

$db = db();

$totalMats  = (int)$db->query('SELECT COUNT(*) FROM materials WHERE is_active=1 AND is_approved=1')->fetchColumn();
$totalSch   = (int)$db->query('SELECT COUNT(*) FROM schools')->fetchColumn();
$totalSubj  = (int)$db->query('SELECT COUNT(*) FROM subjects')->fetchColumn();

$recentStmt = $db->prepare('
    SELECT m.*,u.display_name,u.avatar_color,s.name AS subject_name
    FROM materials m
    JOIN users u ON u.id=m.uploader_id
    LEFT JOIN subjects s ON s.id=m.subject_id
    WHERE m.is_active=1 AND m.is_approved=1
    ORDER BY m.created_at DESC LIMIT 8
');
$recentStmt->execute();
$recent = $recentStmt->fetchAll();

$subjects = $db->query('SELECT * FROM subjects ORDER BY name')->fetchAll();

$schools = [
    ['code'=>'UP',   'name'=>'UP Diliman',       'color'=>'#1632AC'],
    ['code'=>'ADMU', 'name'=>'Ateneo de Manila', 'color'=>'#0B1A57'],
    ['code'=>'DLSU', 'name'=>'De La Salle',      'color'=>'#7A0E1A'],
    ['code'=>'UST',  'name'=>'UST',               'color'=>'#8B5A00'],
    ['code'=>'PUP',  'name'=>'PUP',               'color'=>'#A41E22'],
    ['code'=>'MSU',  'name'=>'MSU-IIT',           'color'=>'#1FA663'],
    ['code'=>'USC',  'name'=>'USC Cebu',          'color'=>'#323A4B'],
];

layout_start('Pasahan ng reviewer, libre forever', 'browse');
?>

<main style="flex:1;">

<!-- HERO -->
<section style="background:linear-gradient(180deg,#EEF3FF 0%,#FBFCFD 100%);padding:64px 0 56px;">
  <div class="container">
    <div style="display:grid;grid-template-columns:1.05fr 1fr;gap:48px;align-items:center;" class="hero-grid">
      

      <div>
        <div style="display:inline-flex;align-items:center;gap:8px;background:var(--brand-yellow-100);padding:6px 14px;border-radius:999px;margin-bottom:20px;">
          <span style="width:6px;height:6px;border-radius:999px;background:var(--brand-yellow-500);display:inline-block;flex-shrink:0;"></span>
          <span style="font-size:13px;font-weight:600;color:var(--brand-yellow-700);">Libre forever. No paywall, no signup wall.</span>
        </div>

        <h1 style="font-family:var(--font-display);font-weight:800;font-size:clamp(38px,5vw,60px);line-height:1.05;letter-spacing:-0.025em;color:var(--fg-1);margin-bottom:20px;">
          Pasahan ng reviewer,<br>libre forever.
        </h1>

        <p style="font-size:18px;line-height:1.55;color:var(--fg-2);max-width:480px;margin-bottom:28px;">
          A community library of class notes, transes, and exam reviewers — uploaded by Filipino college students, free for everyone.
        </p>

        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:28px;">
          <a href="search.php" class="btn btn-primary btn-lg"><?= icon('search',16,2) ?> Browse reviewers</a>
          <a href="upload.php" class="btn btn-accent btn-lg"><?= icon('upload',16,2) ?> Upload yours</a>
        </div>

        <div style="display:flex;gap:24px;font-size:13px;color:var(--fg-3);">
          <div><strong style="color:var(--fg-1);font-size:16px;font-family:var(--font-display);"><?= number_format($totalMats) ?></strong> &nbsp;reviewers</div>
          <div><strong style="color:var(--fg-1);font-size:16px;font-family:var(--font-display);"><?= number_format($totalSubj) ?></strong> &nbsp;subjects</div>
          <div><strong style="color:var(--fg-1);font-size:16px;font-family:var(--font-display);"><?= number_format($totalSch) ?></strong> &nbsp;schools</div>
        </div>
      </div>

      <!-- Illustration -->
      <div style="display:flex;justify-content:center;" class="hero-illustration">
        <?php if (file_exists(__DIR__.'/assets/illustration-hero.svg')): ?>
          <img src="assets/illustration-hero.svg" alt="" style="width:100%;max-width:460px;height:auto;"/>
        <?php else: ?>
        <div style="position:relative;width:340px;height:280px;">
          <div style="position:absolute;top:-20px;right:-20px;width:90px;height:90px;background:var(--brand-yellow-200);border-radius:999px;opacity:.7;"></div>
          <div style="position:absolute;bottom:0;right:10px;width:200px;height:230px;background:white;border-radius:16px;box-shadow:var(--shadow-lg);border:1px solid var(--border-default);transform:rotate(5deg);"></div>
          <div style="position:absolute;bottom:10px;left:10px;width:210px;height:240px;background:white;border-radius:16px;box-shadow:var(--shadow-xl);border:1px solid var(--border-default);">
            <div style="padding:24px;display:flex;flex-direction:column;gap:10px;">
              <div style="height:10px;width:60%;background:var(--brand-blue-500);border-radius:5px;"></div>
              <div style="height:4px;width:100%;background:var(--brand-yellow-300);border-radius:2px;"></div>
              <?php for($i=0;$i<7;$i++): ?>
              <div style="height:6px;width:<?= [88,76,92,68,84,72,90][$i] ?>%;background:var(--neutral-200);border-radius:3px;"></div>
              <?php endfor; ?>
            </div>
          </div>
          <div style="position:absolute;bottom:-12px;right:20px;width:52px;height:52px;background:var(--brand-yellow-300);border-radius:14px;display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-md);">
            <?= icon('download',22,2.5,'color:#10141F') ?>
          </div>
          <div style="position:absolute;top:30px;right:30px;width:10px;height:10px;background:var(--brand-blue-400);border-radius:999px;"></div>
          <div style="position:absolute;top:70px;left:0;width:8px;height:8px;background:var(--brand-yellow-400);border-radius:999px;"></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- SCHOOLS STRIP -->
<section style="padding:32px 0;border-bottom:1px solid var(--border-subtle);">
  <div class="container">
    <div style="display:flex;align-items:center;gap:32px;flex-wrap:wrap;">
      <div style="font-size:13px;font-weight:600;color:var(--fg-3);letter-spacing:.04em;text-transform:uppercase;white-space:nowrap;">Reviewers from</div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <?php foreach ($schools as $sc): ?>
        <a href="search.php?q=<?= urlencode($sc['name']) ?>"
           style="display:flex;align-items:center;gap:8px;padding:6px 12px 6px 6px;border-radius:999px;background:var(--bg-elevated);border:1px solid var(--border-default);text-decoration:none;transition:box-shadow 120ms var(--ease-out);"
           onmouseover="this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.boxShadow='none'">
          <span style="width:28px;height:28px;border-radius:999px;background:<?= e($sc['color']) ?>;color:white;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:800;font-family:var(--font-display);flex-shrink:0;">
            <?= e($sc['code']) ?>
          </span>
          <span style="font-size:13px;font-weight:500;color:var(--fg-2);"><?= e($sc['name']) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- BROWSE REVIEWERS -->
<section style="padding:48px 0 80px;">
  <div class="container">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin-bottom:24px;">
      <div>
        <h2 style="font-family:var(--font-display);font-weight:700;font-size:32px;letter-spacing:-0.02em;color:var(--fg-1);">Browse reviewers</h2>
        <p style="font-size:15px;color:var(--fg-2);margin-top:4px;"><?= $totalMats ?> reviewer<?= $totalMats!==1?'s':'' ?> · updated every few minutes</p>
      </div>
      <form method="GET" action="search.php" style="display:flex;align-items:center;gap:10px;">
        <span style="font-size:13px;color:var(--fg-3);">Sort by</span>
        <select name="sort" class="select" style="width:160px;" onchange="this.form.submit()">
          <option value="recent">Most recent</option>
          <option value="popular">Most downloaded</option>
        </select>
      </form>
    </div>

    <!-- Subject pills -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:32px;">
      <a href="search.php" class="tag active">All subjects</a>
      <?php foreach ($subjects as $sub): ?>
      <a href="search.php?subject=<?= (int)$sub['id'] ?>" class="tag"><?= e($sub['name']) ?></a>
      <?php endforeach; ?>
    </div>

    <!-- 4-col grid -->
    <?php if (empty($recent)): ?>
    <div style="background:var(--bg-elevated);border:1px solid var(--border-default);border-radius:14px;padding:48px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:14px;">
      <?php if (file_exists(__DIR__.'/assets/illustration-empty.svg')): ?>
        <img src="assets/illustration-empty.svg" style="width:180px;" alt=""/>
      <?php else: ?>
        <div style="font-size:64px;">📭</div>
      <?php endif; ?>
      <div>
        <div style="font-family:var(--font-display);font-weight:700;font-size:20px;color:var(--fg-1);">Wala pang reviewer dito.</div>
        <div style="font-size:14px;color:var(--fg-2);margin-top:4px;">Be the first to drop one for this subject.</div>
      </div>
      <a href="upload.php" class="btn btn-accent"><?= icon('upload',14,2) ?> Upload yours</a>
    </div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:18px;" class="rv-grid-4">
      <?php foreach ($recent as $i => $mat): echo reviewer_card($mat, $i); endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- PROMO BAND -->
<section style="background:var(--brand-blue-700);color:white;padding:56px 0;">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr auto;gap:32px;align-items:center;" class="promo-grid">
      <div>
        <div style="font-size:12px;font-weight:600;letter-spacing:.08em;color:var(--brand-yellow-300);text-transform:uppercase;margin-bottom:12px;">Help your batchmates</div>
        <h3 style="font-family:var(--font-display);font-weight:700;font-size:32px;letter-spacing:-0.02em;line-height:1.15;max-width:640px;">
          Got a reviewer that saved your sem? Drop it here. <span style="color:var(--brand-yellow-300);">Pay it forward.</span>
        </h3>
      </div>
      <a href="upload.php" class="btn btn-accent btn-lg"><?= icon('upload',16,2) ?> Upload yours</a>
    </div>
  </div>
</section>

</main>
<?php layout_end(); ?>