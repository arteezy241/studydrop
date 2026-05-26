<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/layout.php';
require_auth();

$db   = db();
$user = current_user();
$uid  = (int)$user['id'];
$tab  = in_array($_GET['tab']??'',['saved','settings']) ? $_GET['tab'] : 'uploads';

// Stats
$statsStmt = $db->prepare('SELECT COUNT(*) AS uploads, COALESCE(SUM(download_count),0) AS downloads FROM materials WHERE uploader_id=? AND is_active=1');
$statsStmt->execute([$uid]); $stats = $statsStmt->fetch();

$savesStmt = $db->prepare('SELECT COUNT(*) FROM saved_materials WHERE user_id=?');
$savesStmt->execute([$uid]); $savedCount = (int)$savesStmt->fetchColumn();

$ratStmt = $db->prepare('SELECT AVG(r.rating) AS avg, COUNT(*) AS cnt FROM reviews r JOIN materials m ON m.id=r.material_id WHERE m.uploader_id=?');
$ratStmt->execute([$uid]); $myRating = $ratStmt->fetch();

// My uploads
$upStmt = $db->prepare('SELECT m.*,s.name AS subject_name FROM materials m LEFT JOIN subjects s ON s.id=m.subject_id WHERE m.uploader_id=? AND m.is_active=1 ORDER BY m.created_at DESC LIMIT 20');
$upStmt->execute([$uid]); $uploads = $upStmt->fetchAll();

// Saved
$svStmt = $db->prepare('SELECT m.*,u.display_name,u.avatar_color,s.name AS subject_name FROM saved_materials sm JOIN materials m ON m.id=sm.material_id JOIN users u ON u.id=m.uploader_id LEFT JOIN subjects s ON s.id=m.subject_id WHERE sm.user_id=? AND m.is_active=1 ORDER BY sm.saved_at DESC LIMIT 12');
$svStmt->execute([$uid]); $savedItems = $svStmt->fetchAll();

// Handle actions
$settingsErrors = [];
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    verify_csrf();
    if ($_POST['action']==='update_profile') {
        $name = trim($_POST['display_name']??''); $bio = trim($_POST['bio']??'');
        if (empty($name)||strlen($name)>100) $settingsErrors['display_name']='Name must be 1–100 characters.';
        if (strlen($bio)>500) $settingsErrors['bio']='Bio must be under 500 characters.';
        if (empty($settingsErrors)) {
            $db->prepare('UPDATE users SET display_name=?,bio=? WHERE id=?')->execute([$name,$bio?:null,$uid]);
            flash('success','Profile updated.'); header('Location: dashboard.php?tab=settings'); exit;
        }
        $tab='settings';
    }
    if ($_POST['action']==='delete_material') {
        $mid=(int)($_POST['material_id']??0);
        $db->prepare('UPDATE materials SET is_active=0 WHERE id=? AND uploader_id=?')->execute([$mid,$uid]);
        flash('success','Material removed.'); header('Location: dashboard.php'); exit;
    }
}

$avBg = $user['avatar_color']; $avFg=($avBg==='#FFD43F')?'#10141F':'#FFFFFF';
$avIn = strtoupper(mb_substr($user['display_name'],0,1));

layout_start('Dashboard','dashboard');
?>

<main style="flex:1;background:var(--bg-muted);padding:32px 0 80px;">
<div class="container">

  <!-- Header -->
  <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:28px;gap:24px;flex-wrap:wrap;">
    <div>
      <div style="font-size:13px;font-weight:600;color:var(--brand-blue-600);letter-spacing:.04em;text-transform:uppercase;">Dashboard</div>
      <h1 style="font-family:var(--font-display);font-weight:700;font-size:clamp(24px,4vw,38px);line-height:1.1;letter-spacing:-0.02em;margin-top:4px;">
        Hey <?= e(explode(' ',$user['display_name'])[0]) ?> — welcome back!
      </h1>
    </div>
    <a href="upload.php" class="btn btn-accent"><?= icon('upload',14,2) ?> Upload new</a>
  </div>

  <!-- Stat tiles -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:32px;" class="stat-grid">
    <div class="stat-tile"><div class="lbl">Total downloads</div><div class="num"><?= number_format($stats['downloads']) ?></div></div>
    <div class="stat-tile"><div class="lbl">Total uploads</div><div class="num"><?= number_format($stats['uploads']) ?></div></div>
    <div class="stat-tile"><div class="lbl">Avg rating</div><div class="num"><?= $myRating['cnt']>0 ? number_format($myRating['avg'],1).' ★' : '—' ?></div></div>
    <div class="stat-tile"><div class="lbl">Saved items</div><div class="num"><?= number_format($savedCount) ?></div></div>
  </div>

  <!-- Main grid -->
  <div style="display:grid;grid-template-columns:1fr 340px;gap:32px;align-items:start;" class="dash-grid">


    <!-- Left: tabs -->
    <div>
      <!-- Tab nav -->
      <div style="display:flex;gap:4px;border-bottom:1px solid var(--border-default);margin-bottom:16px;">
        <?php
        $tabs = [
            'uploads' => ['My uploads', count($uploads)],
            'saved'   => ['Saved library', $savedCount],
            'settings'=> ['Settings', null],
        ];
        foreach ($tabs as $tid=>[$tlbl,$tcnt]): ?>
        <a href="dashboard.php?tab=<?= $tid ?>"
           style="padding:10px 14px;font-size:14px;font-weight:600;text-decoration:none;margin-bottom:-1px;
                  color:<?= $tab===$tid?'var(--brand-blue-600)':'var(--fg-3)' ?>;
                  border-bottom:2px solid <?= $tab===$tid?'var(--brand-blue-500)':'transparent' ?>;
                  display:flex;align-items:center;gap:8px;">
          <?= $tlbl ?>
          <?php if ($tcnt!==null): ?>
          <span style="font-size:11px;padding:2px 7px;border-radius:999px;font-weight:700;
                       background:<?= $tab===$tid?'var(--brand-blue-100)':'var(--bg-sunken)' ?>;
                       color:<?= $tab===$tid?'var(--brand-blue-700)':'var(--fg-3)' ?>;"><?= $tcnt ?></span>
          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>

      <!-- MY UPLOADS -->
      <?php if ($tab==='uploads'): ?>
      <?php if (empty($uploads)): ?>
      <div class="card" style="padding:60px;text-align:center;">
        <p style="color:var(--fg-3);">You haven't uploaded anything yet.</p>
        <a href="upload.php" class="btn btn-accent" style="margin-top:20px;display:inline-flex;"><?= icon('upload',14,2) ?> Upload your first reviewer</a>
      </div>
      <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:10px;">
        <?php foreach ($uploads as $u): ?>
        <div class="card card-hover" style="padding:16px;display:grid;grid-template-columns:auto 1fr auto;gap:16px;align-items:center;">
          <div style="width:52px;height:52px;border-radius:10px;background:var(--brand-blue-100);display:flex;align-items:center;justify-content:center;color:var(--brand-blue-600);">
            <?= icon('file',24) ?>
          </div>
          <div style="min-width:0;">
            <a href="detail.php?id=<?= (int)$u['id'] ?>" style="font-family:var(--font-display);font-weight:700;font-size:15px;color:var(--fg-1);"><?= e($u['title']) ?></a>
            <div style="display:flex;gap:10px;margin-top:5px;font-size:12px;color:var(--fg-3);align-items:center;flex-wrap:wrap;">
              <span style="font-family:var(--font-mono);font-weight:600;color:var(--fg-2);"><?= e($u['course_code']??$u['subject_name']??'') ?></span>
              <span>·</span><span style="font-family:var(--font-mono);"><?= e(strtoupper($u['file_type'])) ?></span>
              <span>·</span><span><?= time_ago($u['created_at']) ?></span>
              <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:3px 8px;border-radius:999px;background:var(--color-success-50);color:var(--color-success-700);">
                <span style="width:6px;height:6px;border-radius:999px;background:var(--color-success-500);display:inline-block;"></span> Live
              </span>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:14px;">
            <div style="display:flex;align-items:center;gap:5px;font-size:13px;font-weight:600;font-family:var(--font-mono);color:var(--fg-2);">
              <?= icon('download',14) ?> <?= number_format($u['download_count']) ?>
            </div>
            <a href="detail.php?id=<?= (int)$u['id'] ?>" class="btn btn-ghost btn-sm">View</a>
            <form method="POST" onsubmit="return confirm('Remove this material?');" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete_material">
              <input type="hidden" name="material_id" value="<?= (int)$u['id'] ?>">
              <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--color-danger-500);"><?= icon('trash',14) ?></button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- SAVED LIBRARY -->
      <?php elseif ($tab==='saved'): ?>
      <?php if (empty($savedItems)): ?>
      <div class="card" style="padding:60px;text-align:center;">
        <p style="color:var(--fg-3);">No saved materials yet. Tap the bookmark on any reviewer to save it.</p>
        <a href="search.php" class="btn btn-primary" style="margin-top:20px;display:inline-flex;">Browse reviewers</a>
      </div>
      <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;">
        <?php foreach ($savedItems as $i=>$mat): echo reviewer_card($mat,$i); endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- SETTINGS -->
      <?php elseif ($tab==='settings'): ?>
      <div class="card" style="padding:28px;max-width:520px;">
        <h3 style="font-family:var(--font-display);font-weight:700;font-size:20px;margin-bottom:20px;">Profile settings</h3>
        <form method="POST" style="display:flex;flex-direction:column;gap:16px;">
          <?= csrf_field() ?><input type="hidden" name="action" value="update_profile">
          <div class="field">
            <label class="field-label" for="display_name">Display name</label>
            <input class="input" type="text" id="display_name" name="display_name"
                   value="<?= e($_POST['display_name']??$user['display_name']) ?>" maxlength="100" required/>
            <?php if (isset($settingsErrors['display_name'])): ?><span class="field-error"><?= e($settingsErrors['display_name']) ?></span><?php endif; ?>
          </div>
          <div class="field">
            <label class="field-label" for="bio">Bio</label>
            <textarea class="textarea" id="bio" name="bio" maxlength="500"
                      placeholder="Tell other students about yourself…"><?= e($_POST['bio']??$user['bio']??'') ?></textarea>
            <?php if (isset($settingsErrors['bio'])): ?><span class="field-error"><?= e($settingsErrors['bio']) ?></span><?php endif; ?>
          </div>
          <div class="field">
            <label class="field-label">Email</label>
            <input class="input" type="email" value="<?= e($user['email']) ?>" disabled style="opacity:.6;cursor:not-allowed;"/>
            <span class="field-help">Email cannot be changed here. Contact support.</span>
          </div>
          <button type="submit" class="btn btn-primary" style="align-self:flex-start;">Save changes</button>
        </form>
      </div>
      <?php endif; ?>
    </div>

    <!-- Right: profile + activity -->
    <aside style="display:flex;flex-direction:column;gap:18px;" class="dash-aside">

      <!-- Profile card -->
      <div class="card" style="padding:20px;display:flex;flex-direction:column;gap:14px;">
        <div style="display:flex;align-items:center;gap:14px;">
          <span class="avatar" style="width:56px;height:56px;font-size:22px;background:<?= e($avBg) ?>;color:<?= e($avFg) ?>;"><?= e($avIn) ?></span>
          <div>
            <div style="font-family:var(--font-display);font-weight:700;font-size:17px;"><?= e($user['display_name']) ?></div>
            <div style="font-size:13px;color:var(--fg-3);margin-top:2px;">@<?= e($user['username']) ?></div>
          </div>
        </div>
        <!-- XP bar -->
        <div>
          <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--fg-3);margin-bottom:6px;">
            <span style="font-weight:600;">Level <?= min(10,(int)($stats['uploads']/3)+1) ?> Contributor</span>
            <span style="font-family:var(--font-mono);"><?= number_format($stats['downloads']) ?> XP</span>
          </div>
          <div style="height:8px;background:var(--bg-sunken);border-radius:999px;overflow:hidden;">
            <div style="width:<?= min(100,(int)(($stats['downloads']%500)/5)) ?>%;height:100%;background:linear-gradient(90deg,var(--brand-blue-500),var(--brand-yellow-300));border-radius:999px;"></div>
          </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <?php if ($stats['uploads']>=5): ?><span class="badge badge-yellow"><?= icon('star',11,2.5) ?> Top uploader</span><?php endif; ?>
          <span class="badge badge-blue"><?= icon('check',11,2.5) ?> Verified student</span>
        </div>
      </div>

      <!-- Quick stats -->
      <div class="card" style="padding:20px;">
        <h4 style="font-family:var(--font-display);font-weight:700;font-size:16px;margin-bottom:14px;">Quick stats</h4>
        <div style="display:flex;flex-direction:column;gap:12px;">
          <?php
          $quickStats = [
              [icon('upload',15),'Total uploads', number_format($stats['uploads'])],
              [icon('download',15),'Total downloads', number_format($stats['downloads'])],
              [icon('bookmark',15),'Saved reviewers', number_format($savedCount)],
              [icon('star',15),'Avg rating', $myRating['cnt']>0 ? number_format($myRating['avg'],1).' ★' : '—'],
          ];
          foreach ($quickStats as [$ico,$lbl,$val]): ?>
          <div style="display:flex;align-items:center;gap:10px;">
            <div style="color:var(--fg-3);"><?= $ico ?></div>
            <span style="flex:1;font-size:13px;color:var(--fg-2);"><?= $lbl ?></span>
            <span style="font-family:var(--font-mono);font-size:13px;font-weight:600;color:var(--fg-1);"><?= $val ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <a href="logout.php" class="btn btn-ghost btn-sm" style="text-align:center;justify-content:center;">Log out</a>
    </aside>

  </div>
</div>
</main>

<?php layout_end(); ?>