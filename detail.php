<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/layout.php';

$db = db();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: search.php'); exit; }

$stmt = $db->prepare('SELECT m.*,u.id AS uid,u.display_name,u.username,u.avatar_color,u.bio,
    s.name AS subject_name,sc.name AS school_name
    FROM materials m JOIN users u ON u.id=m.uploader_id
    LEFT JOIN subjects s ON s.id=m.subject_id LEFT JOIN schools sc ON sc.id=m.school_id
    WHERE m.id=? AND m.is_active=1 LIMIT 1');
$stmt->execute([$id]); $mat = $stmt->fetch();
if (!$mat) { http_response_code(404); layout_start('Not Found'); echo '<main style="flex:1;display:flex;align-items:center;justify-content:center;padding:80px;"><div style="text-align:center;"><h2 class="h3">404 — Not found</h2><a href="search.php" class="btn btn-primary" style="margin-top:24px;">Back to browse</a></div></main>'; layout_end(); exit; }

$ratStmt = $db->prepare('SELECT AVG(rating) AS avg, COUNT(*) AS cnt FROM reviews WHERE material_id=?');
$ratStmt->execute([$id]); $rating = $ratStmt->fetch();

$revStmt = $db->prepare('SELECT r.*,u.display_name,u.avatar_color FROM reviews r JOIN users u ON u.id=r.reviewer_id WHERE r.material_id=? ORDER BY r.created_at DESC LIMIT 20');
$revStmt->execute([$id]); $reviews = $revStmt->fetchAll();

$relStmt = $db->prepare('SELECT m.*,u.display_name,u.avatar_color,s.name AS subject_name FROM materials m JOIN users u ON u.id=m.uploader_id LEFT JOIN subjects s ON s.id=m.subject_id WHERE m.subject_id=? AND m.id!=? AND m.is_active=1 ORDER BY m.download_count DESC LIMIT 3');
$relStmt->execute([$mat['subject_id'],$id]); $related = $relStmt->fetchAll();

$isSaved = false;
if (is_logged_in()) {
    $ss = $db->prepare('SELECT 1 FROM saved_materials WHERE user_id=? AND material_id=?');
    $ss->execute([$_SESSION['user_id'],$id]); $isSaved = (bool)$ss->fetchColumn();
}

$reviewErrors = [];
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    require_auth();
    verify_csrf();
    if ($_POST['action']==='save_toggle') {
        if ($isSaved) $db->prepare('DELETE FROM saved_materials WHERE user_id=? AND material_id=?')->execute([$_SESSION['user_id'],$id]);
        else          $db->prepare('INSERT IGNORE INTO saved_materials (user_id,material_id) VALUES (?,?)')->execute([$_SESSION['user_id'],$id]);
        header("Location: detail.php?id=$id"); exit;
    }
    if ($_POST['action']==='review') {
        $rat = (int)($_POST['rating']??0); $com = trim($_POST['comment']??'');
        if ($rat<1||$rat>5) $reviewErrors['rating']='Please select a rating.';
        if (strlen($com)>2000) $reviewErrors['comment']='Max 2000 characters.';
        if (empty($reviewErrors)) {
            $db->prepare('INSERT INTO reviews (material_id,reviewer_id,rating,comment) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating),comment=VALUES(comment)')
               ->execute([$id,$_SESSION['user_id'],$rat,$com?:null]);
            flash('success','Review saved!'); header("Location: detail.php?id=$id"); exit;
        }
    }
}
if (isset($_GET['download']) && $_GET['download']==='1') {
    $db->prepare('UPDATE materials SET download_count=download_count+1 WHERE id=?')->execute([$id]);
    $fp = UPLOAD_DIR . $mat['file_path'];
    if (is_file($fp)) { header('Content-Type: '.$mat['mime_type']); header('Content-Disposition: attachment; filename="'.rawurlencode($mat['original_name']).'"'); header('Content-Length: '.filesize($fp)); readfile($fp); exit; }
    flash('error','File not found.'); header("Location: detail.php?id=$id"); exit;
}

$avgRat = $rating['cnt'] > 0 ? (float)$rating['avg'] : 0;
$avBg = $mat['avatar_color']; $avFg = ($avBg==='#FFD43F')?'#10141F':'#FFFFFF';
$avIn = strtoupper(mb_substr($mat['display_name'],0,1));

layout_start(e($mat['title']),'search');
?>

<main style="flex:1;">
<div class="container" style="padding:32px 0 80px;">

  <!-- Breadcrumb -->
  <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--fg-3);margin-bottom:20px;flex-wrap:wrap;">
    <a href="index.php"  style="font-weight:500;color:var(--fg-3);">Browse</a>
    <?= icon('chevron-right',14) ?>
    <a href="search.php" style="font-weight:500;color:var(--fg-3);"><?= e($mat['subject_name']??'Materials') ?></a>
    <?= icon('chevron-right',14) ?>
    <?php if ($mat['course_code']): ?>
    <span style="color:var(--fg-2);font-family:var(--font-mono);"><?= e($mat['course_code']) ?></span>
    <?= icon('chevron-right',14) ?>
    <?php endif; ?>
    <span style="color:var(--fg-1);font-weight:500;"><?= e(mb_substr($mat['title'],0,40)) ?>…</span>
  </div>

 <div style="display:grid;grid-template-columns:1fr 340px;gap:32px;align-items:start;" class="detail-grid">

    <!-- LEFT -->
    <div>
      <!-- Header -->
      <div style="margin-bottom:24px;">
        <div style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;">
          <?php if ($mat['course_code']): ?><span class="badge badge-blue"><?= e($mat['course_code']) ?></span><?php endif; ?>
          <?php if ($mat['subject_name']): ?><span class="badge badge-yellow"><?= icon('star',11,2.5) ?> <?= e($mat['subject_name']) ?></span><?php endif; ?>
          <span class="badge badge-neutral"><?= e(strtoupper($mat['file_type'])) ?></span>
        </div>
        <h1 style="font-family:var(--font-display);font-weight:800;font-size:clamp(24px,4vw,38px);line-height:1.1;letter-spacing:-0.02em;">
          <?= e($mat['title']) ?>
        </h1>
        <div style="display:flex;align-items:center;gap:18px;margin-top:16px;flex-wrap:wrap;">
          <div style="display:flex;align-items:center;gap:10px;">
            <span class="avatar" style="width:36px;height:36px;font-size:15px;background:<?= e($avBg) ?>;color:<?= e($avFg) ?>;"><?= e($avIn) ?></span>
            <div>
              <div style="font-size:14px;font-weight:600;color:var(--fg-1);"><?= e($mat['display_name']) ?></div>
              <div style="font-size:12px;color:var(--fg-3);"><?= e($mat['school_name']??'') ?></div>
            </div>
          </div>
          <span style="width:1px;height:32px;background:var(--border-subtle);display:inline-block;"></span>
          <?php if ($avgRat > 0): ?>
          <div style="display:flex;align-items:center;gap:4px;">
            <span style="color:var(--brand-yellow-400);font-size:16px;">★</span>
            <span style="font-size:14px;font-weight:600;"><?= number_format($avgRat,1) ?></span>
            <span style="font-size:13px;color:var(--fg-3);">(<?= $rating['cnt'] ?> rating<?= $rating['cnt']!==1?'s':'' ?>)</span>
          </div>
          <?php endif; ?>
          <div style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--fg-3);">
            <?= icon('download',15) ?> <?= number_format($mat['download_count']) ?> downloads
          </div>
          <div style="font-size:13px;color:var(--fg-3);">Uploaded <?= time_ago($mat['created_at']) ?></div>
        </div>
      </div>

      <!-- Fake PDF preview -->
      <div class="card" style="padding:0;overflow:hidden;margin-bottom:28px;">
        <div style="background:var(--bg-muted);padding:10px 16px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border-subtle);">
          <div style="display:flex;align-items:center;gap:10px;font-family:var(--font-mono);font-size:12px;color:var(--fg-2);">
            <?= icon('file',15) ?>
            <span><?= e($mat['original_name']) ?></span>
            <span style="color:var(--fg-3);">· <?= e(strtoupper($mat['file_type'])) ?> · <?= e(format_file_size($mat['file_size'])) ?></span>
          </div>
          <div style="display:flex;align-items:center;gap:6px;font-family:var(--font-mono);font-size:12px;color:var(--fg-2);">
            <span>1 / <?= max(1,(int)ceil($mat['file_size']/40000)) ?> pages</span>
          </div>
        </div>
        <div style="background:var(--bg-sunken);padding:32px;display:flex;justify-content:center;">
          <div style="width:100%;max-width:520px;aspect-ratio:8.5/11;background:white;border-radius:6px;box-shadow:var(--shadow-lg);padding:40px;display:flex;flex-direction:column;gap:12px;">
            <div style="font-family:var(--font-display);font-weight:800;font-size:20px;color:var(--neutral-900);letter-spacing:-0.01em;margin-bottom:4px;"><?= e($mat['title']) ?></div>
            <div style="font-size:10px;color:var(--fg-3);font-family:var(--font-mono);margin-bottom:14px;">Uploaded by <?= e($mat['display_name']) ?></div>
            <?php if ($mat['subject_name']): ?>
            <div style="font-family:var(--font-display);font-weight:700;font-size:14px;color:var(--brand-blue-700);"><?= e($mat['subject_name']) ?></div>
            <?php endif; ?>
            <div style="height:4px;width:100%;background:var(--brand-yellow-300);border-radius:2px;margin-bottom:4px;"></div>
            <?php for($i=0;$i<10;$i++): $w=60+(($i*37)%40); ?>
            <div style="height:5px;background:var(--neutral-200);width:<?= $w ?>%;border-radius:2px;"></div>
            <?php endfor; ?>
            <div style="background:var(--brand-blue-50);border:1px solid var(--brand-blue-100);border-radius:4px;padding:8px;font-family:var(--font-mono);font-size:9px;color:var(--brand-blue-800);line-height:1.8;margin-top:8px;">
              // Preview only — download to read full content
            </div>
          </div>
        </div>
      </div>

      <!-- About -->
      <div style="margin-bottom:36px;">
        <h3 style="font-family:var(--font-display);font-weight:700;font-size:20px;letter-spacing:-0.01em;margin-bottom:14px;">About this reviewer</h3>
        <?php if ($mat['description']): ?>
        <p style="font-size:15px;line-height:1.7;color:var(--fg-2);white-space:pre-wrap;"><?= e($mat['description']) ?></p>
        <?php else: ?>
        <p style="font-size:15px;color:var(--fg-3);font-style:italic;">No description provided.</p>
        <?php endif; ?>
      </div>

      <!-- Details grid -->
      <div style="margin-bottom:36px;">
        <h3 style="font-family:var(--font-display);font-weight:700;font-size:20px;letter-spacing:-0.01em;margin-bottom:14px;">Details</h3>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
          <?php
          $deets = [
            'Course'    => $mat['course_code']  ?? 'N/A',
            'School'    => $mat['school_name']  ?? 'N/A',
            'Semester'  => $mat['semester']     ?? 'N/A',
            'Year'      => $mat['year']         ?? 'N/A',
            'File type' => strtoupper($mat['file_type']),
            'File size' => format_file_size($mat['file_size']),
          ];
          foreach ($deets as $lbl=>$val): ?>
          <div class="card" style="padding:12px 14px;">
            <div style="font-size:11px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:var(--fg-3);margin-bottom:4px;"><?= e($lbl) ?></div>
            <div style="font-size:14px;font-weight:600;color:var(--fg-1);"><?= e($val) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Comments / Reviews -->
      <div style="margin-bottom:36px;">
        <h3 style="font-family:var(--font-display);font-weight:700;font-size:20px;letter-spacing:-0.01em;margin-bottom:14px;">
          Comments (<?= count($reviews) ?>)
        </h3>

        <!-- Rating prompt -->
        <?php if (is_logged_in() && $_SESSION['user_id'] !== (int)$mat['uid']): ?>
        <div class="card" style="padding:18px;margin-bottom:18px;background:var(--brand-blue-50);border:1px solid var(--brand-blue-100);">
          <div style="font-weight:600;font-size:14px;color:var(--fg-1);margin-bottom:12px;">Helpful? Rate this reviewer to help your batchmates.</div>
          <form method="POST" style="display:flex;flex-direction:column;gap:12px;">
            <?= csrf_field() ?><input type="hidden" name="action" value="review">
            <div style="display:flex;gap:6px;">
              <?php for ($r=1;$r<=5;$r++): ?>
              <label style="cursor:pointer;font-size:28px;color:var(--brand-yellow-400);">
                <input type="radio" name="rating" value="<?= $r ?>" style="display:none;" <?= (isset($_POST['rating'])&&(int)$_POST['rating']===$r)?'checked':'' ?>>★
              </label>
              <?php endfor; ?>
            </div>
            <?php if (isset($reviewErrors['rating'])): ?><span class="field-error"><?= e($reviewErrors['rating']) ?></span><?php endif; ?>
            <textarea class="textarea" name="comment" placeholder="What did you find helpful? (optional)" style="min-height:72px;"><?= e($_POST['comment']??'') ?></textarea>
            <button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-start;">Submit review</button>
          </form>
        </div>
        <?php elseif (!is_logged_in()): ?>
        <div class="card" style="padding:16px;text-align:center;margin-bottom:18px;background:var(--brand-blue-50);border:1px solid var(--brand-blue-100);">
          <p style="font-size:13px;color:var(--fg-2);"><a href="auth.php?redirect=<?= urlencode("detail.php?id=$id") ?>" style="color:var(--brand-blue-600);font-weight:600;">Sign in</a> to rate and comment on this reviewer.</p>
        </div>
        <?php endif; ?>

        <div style="display:flex;flex-direction:column;gap:16px;">
          <?php foreach ($reviews as $rev):
            $rBg = $rev['avatar_color']; $rFg=($rBg==='#FFD43F')?'#10141F':'#FFFFFF';
            $rIn = strtoupper(mb_substr($rev['display_name'],0,1));
          ?>
          <div style="display:flex;gap:12px;">
            <span class="avatar" style="width:36px;height:36px;font-size:15px;background:<?= e($rBg) ?>;color:<?= e($rFg) ?>;flex-shrink:0;"><?= e($rIn) ?></span>
            <div style="flex:1;">
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <span style="font-size:14px;font-weight:600;"><?= e($rev['display_name']) ?></span>
                <span style="font-size:12px;color:var(--fg-3);"><?= time_ago($rev['created_at']) ?></span>
                <span style="color:var(--brand-yellow-400);"><?= str_repeat('★',(int)$rev['rating']).str_repeat('☆',5-(int)$rev['rating']) ?></span>
              </div>
              <?php if ($rev['comment']): ?>
              <p style="font-size:14px;line-height:1.55;color:var(--fg-2);margin-top:4px;"><?= nl2br(e($rev['comment'])) ?></p>
              <?php endif; ?>
              <div style="display:flex;gap:16px;margin-top:6px;font-size:12px;color:var(--fg-3);">
                <button style="display:inline-flex;align-items:center;gap:5px;font-weight:500;"><?= icon('heart',13) ?> Helpful</button>
                <button style="font-weight:500;">Reply</button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Related -->
      <?php if (!empty($related)): ?>
      <div>
        <h3 style="font-family:var(--font-display);font-weight:700;font-size:20px;letter-spacing:-0.01em;margin-bottom:14px;">More from <?= e($mat['course_code']??$mat['subject_name']??'this subject') ?></h3>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
          <?php foreach ($related as $i=>$rel): echo reviewer_card($rel,$i); endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- RIGHT: sticky panel -->
    <aside style="position:sticky;top:90px;display:flex;flex-direction:column;gap:14px;" class="detail-aside">

      <!-- Download card -->
      <div class="card" style="padding:20px;">
        <a href="detail.php?id=<?= $id ?>&download=1" class="btn btn-accent btn-lg" style="width:100%;justify-content:center;margin-bottom:10px;">
          <?= icon('download',16,2) ?> Download · <?= e(format_file_size($mat['file_size'])) ?>
        </a>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
          <form method="POST" style="display:contents;">
            <?= csrf_field() ?><input type="hidden" name="action" value="save_toggle">
            <button type="submit" class="btn btn-ghost" style="width:100%;">
              <?= $isSaved ? icon('bookmark-fill',16).'Saved' : icon('bookmark',16).'Save' ?>
            </button>
          </form>
          <button class="btn btn-ghost" style="width:100%;"><?= icon('user',16) ?> Share</button>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:4px;margin-top:18px;padding-top:18px;border-top:1px solid var(--border-subtle);text-align:center;">
          <?php foreach (['Downloads'=>number_format($mat['download_count']),'Saves'=>'—','Views'=>'—'] as $lbl=>$val): ?>
          <div>
            <div style="font-family:var(--font-display);font-weight:700;font-size:18px;color:var(--fg-1);"><?= $val ?></div>
            <div style="font-size:11px;color:var(--fg-3);font-weight:500;"><?= $lbl ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Author card -->
      <div class="card" style="padding:18px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
          <span class="avatar" style="width:44px;height:44px;font-size:18px;background:<?= e($avBg) ?>;color:<?= e($avFg) ?>;"><?= e($avIn) ?></span>
          <div style="flex:1;min-width:0;">
            <div style="font-size:14px;font-weight:600;"><?= e($mat['display_name']) ?></div>
            <div style="font-size:12px;color:var(--fg-3);">@<?= e($mat['username']) ?> · <?= e($mat['school_name']??'') ?></div>
          </div>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px;">
          <span class="badge badge-yellow"><?= icon('star',11,2.5) ?> Top uploader</span>
          <span class="badge badge-success"><?= icon('check',11,2.5) ?> Verified</span>
        </div>
        <button class="btn btn-ghost btn-sm" style="width:100%;">Follow</button>
      </div>

      <button style="font-size:12px;color:var(--fg-3);text-align:center;padding:8px;display:flex;align-items:center;justify-content:center;gap:6px;width:100%;">
        <?= icon('info',13) ?> Report this reviewer
      </button>
    </aside>
  </div>
</div>
</main>

<?php layout_end(); ?>