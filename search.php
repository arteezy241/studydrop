<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/layout.php';

$db   = db();
$q    = trim($_GET['q']       ?? '');
$subj = (int)($_GET['subject'] ?? 0);
$sch  = (int)($_GET['school']  ?? 0);
$sort = in_array($_GET['sort']??'',['popular','oldest','rating']) ? $_GET['sort'] : 'recent';
$view = ($_GET['view']??'list') === 'grid' ? 'grid' : 'list';
$page = max(1,(int)($_GET['page']??1));
$per  = 12; $off = ($page-1)*$per;

$where = ['m.is_active=1','m.is_approved=1']; $params = [];
if ($q!=='') { $where[]='MATCH(m.title,m.description,m.course_code) AGAINST(? IN BOOLEAN MODE)'; $params[]=$q.'*'; }
if ($subj)   { $where[]='m.subject_id=?'; $params[]=$subj; }
if ($sch)    { $where[]='m.school_id=?';  $params[]=$sch; }
$wSQL = implode(' AND ',$where);
$oSQL = match($sort){ 'popular'=>'m.download_count DESC','oldest'=>'m.created_at ASC', default=>'m.created_at DESC' };

$cntStmt = $db->prepare("SELECT COUNT(*) FROM materials m WHERE $wSQL");
$cntStmt->execute($params);
$total = (int)$cntStmt->fetchColumn();
$lastPage = max(1,(int)ceil($total/$per));

$stmt = $db->prepare("SELECT m.*,u.display_name,u.avatar_color,s.name AS subject_name,sc.name AS school_name
    FROM materials m JOIN users u ON u.id=m.uploader_id
    LEFT JOIN subjects s ON s.id=m.subject_id LEFT JOIN schools sc ON sc.id=m.school_id
    WHERE $wSQL ORDER BY $oSQL LIMIT $per OFFSET $off");
$stmt->execute($params);
$materials = $stmt->fetchAll();

$subjects = $db->query('SELECT * FROM subjects ORDER BY name')->fetchAll();
$schools  = $db->query('SELECT * FROM schools  ORDER BY name')->fetchAll();

$thumbColors = ['blue','yellow','green'];

layout_start('Browse — StudyDrop','search');
?>

<main style="flex:1;">
<div class="container" style="padding:24px 0 80px;">

  <!-- Search bar -->
  <form method="GET" action="search.php">
    <div style="background:var(--bg-elevated);border:1px solid var(--border-default);border-radius:14px;padding:8px;display:flex;align-items:center;gap:10px;margin-bottom:24px;">
      <?= icon('search',20,2,'color:var(--fg-3);margin-left:10px;flex-shrink:0;') ?>
      <input name="q" value="<?= e($q) ?>"
             style="flex:1;border:0;outline:0;font-size:17px;font-weight:500;background:transparent;padding:10px 4px;color:var(--fg-1);font-family:var(--font-body);"
             placeholder="Search reviewers, subjects, schools..."/>
      <?php if ($subj): ?><input type="hidden" name="subject" value="<?= $subj ?>"/><?php endif; ?>
      <?php if ($sch):  ?><input type="hidden" name="school"  value="<?= $sch ?>"/><?php endif; ?>
      <input type="hidden" name="sort" value="<?= e($sort) ?>"/>
      <button type="submit" class="btn btn-primary"><?= icon('search',16,2) ?> Search</button>
    </div>
  </form>

  <div style="display:grid;grid-template-columns:240px 1fr;gap:32px;align-items:start;" class="search-grid">

    <!-- SIDEBAR FILTERS -->
    <aside style="position:sticky;top:90px;" class="search-sidebar">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
        <h3 style="font-family:var(--font-display);font-weight:700;font-size:16px;">Filters</h3>
        <?php if ($subj||$sch): ?>
        <a href="search.php?q=<?= urlencode($q) ?>" style="font-size:12px;color:var(--brand-blue-600);font-weight:600;">Clear all</a>
        <?php endif; ?>
      </div>

      <!-- School -->
      <div style="margin-bottom:22px;">
        <h4 style="font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--fg-3);margin-bottom:10px;">School</h4>
        <div style="display:flex;flex-direction:column;gap:2px;">
          <?php foreach ($schools as $sc): ?>
          <a href="search.php?<?= http_build_query(array_filter(['q'=>$q,'subject'=>$subj,'school'=>(int)$sc['id'],'sort'=>$sort])) ?>"
             style="display:flex;align-items:center;gap:10px;padding:7px 8px;border-radius:8px;cursor:pointer;text-decoration:none;
                    background:<?= $sch===(int)$sc['id']?'var(--brand-blue-50)':'transparent' ?>;transition:background 120ms;">
            <span style="width:14px;height:14px;border-radius:3px;border:1.5px solid <?= $sch===(int)$sc['id']?'var(--brand-blue-500)':'var(--border-strong)' ?>;
                          background:<?= $sch===(int)$sc['id']?'var(--brand-blue-500)':'transparent' ?>;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
              <?= $sch===(int)$sc['id'] ? icon('check',10,3,'color:white') : '' ?>
            </span>
            <span style="flex:1;font-size:13px;font-weight:500;color:<?= $sch===(int)$sc['id']?'var(--brand-blue-700)':'var(--fg-2)' ?>;"><?= e($sc['name']) ?></span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Subject -->
      <div style="margin-bottom:22px;">
        <h4 style="font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--fg-3);margin-bottom:10px;">Subject</h4>
        <div style="display:flex;flex-direction:column;gap:2px;">
          <?php foreach ($subjects as $sub): ?>
          <a href="search.php?<?= http_build_query(array_filter(['q'=>$q,'subject'=>(int)$sub['id'],'school'=>$sch,'sort'=>$sort])) ?>"
             style="display:flex;align-items:center;gap:10px;padding:7px 8px;border-radius:8px;cursor:pointer;text-decoration:none;
                    background:<?= $subj===(int)$sub['id']?'var(--brand-blue-50)':'transparent' ?>;transition:background 120ms;">
            <span style="width:14px;height:14px;border-radius:3px;border:1.5px solid <?= $subj===(int)$sub['id']?'var(--brand-blue-500)':'var(--border-strong)' ?>;
                          background:<?= $subj===(int)$sub['id']?'var(--brand-blue-500)':'transparent' ?>;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
              <?= $subj===(int)$sub['id'] ? icon('check',10,3,'color:white') : '' ?>
            </span>
            <span style="flex:1;font-size:13px;font-weight:500;color:<?= $subj===(int)$sub['id']?'var(--brand-blue-700)':'var(--fg-2)' ?>;"><?= e($sub['name']) ?></span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Min rating -->
      <div>
        <h4 style="font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--fg-3);margin-bottom:10px;">Min. rating</h4>
        <div style="display:flex;gap:4px;">
          <?php foreach ([3,4,4.5,5] as $v): ?>
          <button style="flex:1;padding:6px 4px;font-size:12px;font-weight:600;border:1px solid var(--border-default);border-radius:8px;background:var(--bg-elevated);color:var(--fg-2);display:inline-flex;align-items:center;justify-content:center;gap:3px;cursor:pointer;">
            <?= $v ?>+ ★
          </button>
          <?php endforeach; ?>
        </div>
      </div>
    </aside>

    <!-- RESULTS -->
    <div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:12px;">
        <div>
          <h2 style="font-family:var(--font-display);font-weight:700;font-size:22px;letter-spacing:-0.01em;">
            <?= $total ?> result<?= $total!==1?'s':'' ?>
            <?= $q ? ' for "<span style="color:var(--brand-blue-600);">'.e($q).'</span>"' : '' ?>
          </h2>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
          <!-- View toggle -->
          <div style="display:flex;background:var(--bg-muted);border-radius:8px;padding:2px;">
            <?php foreach (['list'=>'List','grid'=>'Grid'] as $vid=>$vlbl): ?>
            <a href="search.php?<?= http_build_query(array_filter(['q'=>$q,'subject'=>$subj,'school'=>$sch,'sort'=>$sort,'view'=>$vid])) ?>"
               style="padding:6px 10px;border-radius:6px;background:<?= $view===$vid?'var(--bg-elevated)':'transparent' ?>;
                      color:<?= $view===$vid?'var(--fg-1)':'var(--fg-3)' ?>;box-shadow:<?= $view===$vid?'var(--shadow-xs)':'none' ?>;
                      display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;text-decoration:none;">
              <?= $vlbl ?>
            </a>
            <?php endforeach; ?>
          </div>
          <form method="GET">
            <?php foreach (['q'=>$q,'subject'=>$subj,'school'=>$sch,'view'=>$view] as $k=>$v): ?>
            <?php if ($v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"/><?php endif; ?>
            <?php endforeach; ?>
            <select name="sort" class="select" style="width:170px;" onchange="this.form.submit()">
              <option value="recent"   <?= $sort==='recent'  ?'selected':'' ?>>Most recent</option>
              <option value="popular"  <?= $sort==='popular' ?'selected':'' ?>>Most downloaded</option>
              <option value="rating"   <?= $sort==='rating'  ?'selected':'' ?>>Highest rated</option>
              <option value="oldest"   <?= $sort==='oldest'  ?'selected':'' ?>>Oldest first</option>
            </select>
          </form>
        </div>
      </div>

      <?php if (empty($materials)): ?>
      <div style="background:var(--bg-elevated);border:1px solid var(--border-default);border-radius:14px;padding:60px;text-align:center;">
        <div style="font-size:48px;margin-bottom:16px;">📭</div>
        <div style="font-family:var(--font-display);font-weight:700;font-size:20px;color:var(--fg-1);">Wala pang reviewer dito.</div>
        <div style="font-size:14px;color:var(--fg-2);margin-top:4px;">Try different keywords or filters.</div>
        <a href="upload.php" class="btn btn-accent" style="margin-top:24px;display:inline-flex;"><?= icon('upload',14,2) ?> Upload the first one</a>
      </div>

      <?php elseif ($view==='list'): ?>
      <!-- LIST VIEW -->
      <div style="display:flex;flex-direction:column;gap:12px;">
        <?php foreach ($materials as $i=>$mat):
          $tbg = match($i%3){1=>'linear-gradient(180deg,#FFE680 0%,#FFFBEA 100%)',2=>'linear-gradient(180deg,#B6E8CB 0%,#E8F8EF 100%)',default=>'linear-gradient(180deg,#DCE6FF 0%,#EEF3FF 100%)'};
        ?>
        <a href="detail.php?id=<?= (int)$mat['id'] ?>" class="card card-hover"
           style="padding:16px;display:grid;grid-template-columns:100px 1fr auto;gap:18px;align-items:center;text-decoration:none;">
          <!-- Thumb -->
          <div style="aspect-ratio:4/3;border-radius:10px;background:<?= $tbg ?>;position:relative;overflow:hidden;">
            <div style="position:absolute;inset:10px 10px auto 10px;display:flex;flex-direction:column;gap:3px;">
              <?php foreach ([60,92,78,85] as $w): ?>
              <div style="height:3px;width:<?= $w ?>%;background:rgba(16,20,31,0.25);border-radius:2px;"></div>
              <?php endforeach; ?>
            </div>
            <span style="position:absolute;top:6px;right:6px;background:rgba(255,255,255,.95);border-radius:999px;padding:2px 6px;font-family:var(--font-mono);font-size:9px;font-weight:600;color:var(--brand-blue-700);"><?= e(strtoupper($mat['file_type'])) ?></span>
          </div>
          <!-- Body -->
          <div style="min-width:0;">
            <div style="display:flex;gap:6px;align-items:center;margin-bottom:6px;flex-wrap:wrap;">
              <?php if ($mat['course_code']): ?>
              <span class="badge badge-blue"><?= e($mat['course_code']) ?></span>
              <?php endif; ?>
              <?php if ($mat['subject_name']): ?>
              <span style="font-size:12px;color:var(--fg-3);">· <?= e($mat['subject_name']) ?></span>
              <?php endif; ?>
              <?php if ($mat['semester']): ?>
              <span style="font-size:12px;color:var(--fg-3);">· <?= e($mat['semester']) ?></span>
              <?php endif; ?>
            </div>
            <div style="font-family:var(--font-display);font-weight:700;font-size:17px;color:var(--fg-1);line-height:1.3;"><?= e($mat['title']) ?></div>
            <?php if ($mat['description']): ?>
            <div style="font-size:13px;line-height:1.5;color:var(--fg-2);margin-top:6px;"><?= e(mb_substr($mat['description'],0,120)) ?>…</div>
            <?php endif; ?>
            <div style="display:flex;gap:16px;align-items:center;margin-top:10px;font-size:12px;color:var(--fg-3);">
              <?php
                $avBg = $mat['avatar_color']; $avFg = ($avBg==='#FFD43F')?'#10141F':'#FFFFFF';
                $avIn = strtoupper(mb_substr($mat['display_name'],0,1));
              ?>
              <span style="display:inline-flex;align-items:center;gap:6px;">
                <span class="avatar" style="width:18px;height:18px;font-size:7px;background:<?= e($avBg) ?>;color:<?= e($avFg) ?>;"><?= e($avIn) ?></span>
                <?= e($mat['display_name']) ?>
              </span>
              <span style="display:inline-flex;align-items:center;gap:4px;"><?= icon('download',13) ?> <?= number_format($mat['download_count']) ?></span>
              <span><?= time_ago($mat['created_at']) ?></span>
            </div>
          </div>
          <!-- Actions -->
          <div style="display:flex;flex-direction:column;gap:6px;">
            <a href="detail.php?id=<?= (int)$mat['id'] ?>&download=1" class="btn btn-primary btn-sm"><?= icon('download',14,2) ?> Download</a>
            <button class="btn btn-ghost btn-sm"><?= icon('bookmark',14) ?> Save</button>
          </div>
        </a>
        <?php endforeach; ?>
      </div>

      <?php else: ?>
      <!-- GRID VIEW -->
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
        <?php foreach ($materials as $i=>$mat): echo reviewer_card($mat,$i); endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- PAGINATION -->
      <?php if ($lastPage>1): ?>
      <?php $buildUrl=function(int $p) use ($q,$subj,$sch,$sort,$view){ return 'search.php?'.http_build_query(array_filter(['q'=>$q,'subject'=>$subj,'school'=>$sch,'sort'=>$sort,'view'=>$view,'page'=>$p])); }; ?>
      <div style="display:flex;justify-content:center;align-items:center;gap:6px;margin-top:32px;">
        <?php if ($page>1): ?><a href="<?= e($buildUrl($page-1)) ?>" class="btn btn-ghost btn-sm">‹ Prev</a><?php endif; ?>
        <?php for ($p=max(1,$page-2);$p<=min($lastPage,$page+2);$p++): ?>
        <a href="<?= e($buildUrl($p)) ?>" class="btn <?= $p===$page?'btn-primary':'btn-ghost' ?> btn-sm" style="min-width:36px;"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($page<$lastPage): ?><a href="<?= e($buildUrl($page+1)) ?>" class="btn btn-ghost btn-sm">Next ›</a><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</main>

<?php layout_end(); ?>