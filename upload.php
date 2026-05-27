<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/layout.php';
require_auth();

$db = db();
$errors = []; $old = [];
$subjects = $db->query('SELECT * FROM subjects ORDER BY name')->fetchAll();
$schools  = $db->query('SELECT * FROM schools  ORDER BY name')->fetchAll();

$uploadSchools = ['UP Diliman','Ateneo de Manila','De La Salle','UST','PUP','MSU-IIT','USC Cebu','USJ-R','Mapua','Other'];
$uploadTypes   = ['Reviewer','Class notes','Transcript (trans)','Past exam / quiz','Problem set','Lecture slides','Study guide','Other'];
$semesters     = ['1st sem 24-25','2nd sem 24-25','Summer 25','1st sem 25-26','2nd sem 25-26'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old = [
        'title'       => trim($_POST['title']       ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'subject_id'  => (int)($_POST['subject_id'] ?? 0),
        'school_id'   => (int)($_POST['school_id']  ?? 0),
        'course_code' => trim($_POST['course_code'] ?? ''),
        'semester'    => trim($_POST['semester']    ?? ''),
        'year'        => (int)($_POST['year']       ?? 0),
        'type'        => trim($_POST['type']        ?? 'Reviewer'),
    ];
    if (empty($old['title']))       $errors['title']    = 'Title is required.';
    if (strlen($old['title'])>200)  $errors['title']    = 'Title must be under 200 characters.';
    if (!isset($_FILES['material']) || $_FILES['material']['error']===UPLOAD_ERR_NO_FILE) $errors['material'] = 'Please upload a file.';

    if (empty($errors)) {
        try {
            $upload = handle_upload($_FILES['material']);
            $db->prepare('INSERT INTO materials (uploader_id,subject_id,school_id,title,description,file_path,original_name,file_size,mime_type,file_type,course_code,semester,year) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
               ->execute([$_SESSION['user_id'],$old['subject_id']?:null,$old['school_id']?:null,$old['title'],$old['description']?:null,$upload['relative_path'],$upload['original_name'],$upload['file_size'],$upload['mime_type'],$upload['file_type'],$old['course_code']?:null,$old['semester']?:null,$old['year']?:null]);
            $newId = (int)$db->lastInsertId();
            flash('success','Uploaded! Salamat 🎉');
            header("Location: detail.php?id=$newId"); exit;
        } catch (RuntimeException $e) {
            $errors['material'] = $e->getMessage();
        }
    }
}

layout_start('Drop a reviewer','upload');
?>

<main style="flex:1;">
<div class="container-narrow" style="padding:48px 0 80px;">

  <div style="margin-bottom:28px;">
    <a href="index.php" style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--fg-3);font-weight:500;text-decoration:none;">← Back to browse</a>
    <h1 style="font-family:var(--font-display);font-weight:700;font-size:38px;line-height:1.1;letter-spacing:-0.02em;margin-top:8px;">Drop a reviewer</h1>
    <p style="font-size:16px;color:var(--fg-2);margin-top:6px;">Help your batchmates pass. We'll handle the file storage and search.</p>
  </div>

  <form method="POST" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

   <!-- Drop zone -->
    <label for="material" id="drop-zone"
           style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;
                  border:2px dashed var(--border-strong);border-radius:20px;padding:48px;
                  text-align:center;background:var(--bg-elevated);cursor:pointer;
                  transition:all 180ms var(--ease-out);margin-bottom:32px;">
      <?php if (file_exists(__DIR__.'/assets/illustration-upload.svg')): ?>
        <img src="assets/illustration-upload.svg" alt="" style="width:180px;height:auto;margin-bottom:4px;"/>
      <?php else: ?>
        <div style="width:72px;height:72px;background:var(--brand-blue-100);border-radius:18px;display:flex;align-items:center;justify-content:center;color:var(--brand-blue-500);">
          <?= icon('upload',32,1.75) ?>
        </div>
      <?php endif; ?>
      <div>
        <div style="font-family:var(--font-display);font-weight:700;font-size:20px;color:var(--fg-1);">Drop a file, or click to choose</div>
        <!-- ✅ FIX 2: updated format hint to include DOCX -->
        <div style="font-size:13px;color:var(--fg-3);margin-top:6px;font-family:var(--font-mono);">PDF · DOCX · PNG · JPEG · up to 10 MB</div>
      </div>
      <!-- ✅ FIX 2: added .docx to accept attribute -->
      <input type="file" id="material" name="material"
             accept=".pdf,.docx,.png,.jpg,.jpeg"
             style="display:none;" onchange="showFile(this)"/>
    </label>
    <p id="file-name" style="font-size:13px;color:var(--brand-blue-600);font-weight:600;margin-top:-20px;margin-bottom:20px;"></p>
    <?php if (isset($errors['material'])): ?><p class="field-error" style="margin-bottom:16px;"><?= e($errors['material']) ?></p><?php endif; ?>

    <!-- Metadata card -->
    <div class="card" style="padding:28px;">
      <h3 style="font-family:var(--font-display);font-weight:700;font-size:20px;margin-bottom:4px;">About this reviewer</h3>
      <p style="font-size:13px;color:var(--fg-3);margin-bottom:24px;">Quick metadata helps your batchmates find it.</p>

      <div style="display:flex;flex-direction:column;gap:18px;">

        <div class="field">
          <label class="field-label" for="title">Title</label>
          <input class="input" type="text" id="title" name="title" value="<?= e($old['title']??'') ?>"
                 placeholder="e.g. Midterms reviewer — CS 11 Programming Fundamentals" maxlength="200" required/>
          <?php if (isset($errors['title'])): ?><span class="field-error"><?= e($errors['title']) ?></span><?php endif; ?>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;" class="upload-cols">
          <div class="field">
            <label class="field-label" for="school_id">School</label>
            <select class="select" id="school_id" name="school_id">
              <option value="">Select school...</option>
              <?php foreach ($schools as $sc): ?>
              <option value="<?= (int)$sc['id'] ?>" <?= ($old['school_id']??0)===(int)$sc['id']?'selected':'' ?>><?= e($sc['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label class="field-label" for="course_code">Course code</label>
            <input class="input" type="text" id="course_code" name="course_code" value="<?= e($old['course_code']??'') ?>" placeholder="CS 11"/>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
          <div class="field">
            <label class="field-label" for="subject_id">Subject</label>
            <select class="select" id="subject_id" name="subject_id">
              <option value="">Select subject...</option>
              <?php foreach ($subjects as $sub): ?>
              <option value="<?= (int)$sub['id'] ?>" <?= ($old['subject_id']??0)===(int)$sub['id']?'selected':'' ?>><?= e($sub['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label class="field-label" for="type">Type</label>
            <select class="select" id="type" name="type">
              <?php foreach ($uploadTypes as $t): ?>
              <option value="<?= e($t) ?>" <?= ($old['type']??'Reviewer')===$t?'selected':'' ?>><?= e($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="field">
          <label class="field-label" for="semester">Semester</label>
          <select class="select" id="semester" name="semester" style="max-width:240px;">
            <option value="">Select semester...</option>
            <?php foreach ($semesters as $sem): ?>
            <option value="<?= e($sem) ?>" <?= ($old['semester']??'')===$sem?'selected':'' ?>><?= e($sem) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label class="field-label" for="description">Description <span style="font-weight:400;color:var(--fg-3);">(optional)</span></label>
          <textarea class="textarea" id="description" name="description" maxlength="5000"
                    placeholder="Covers chapters 1–4. Includes 12 sample problems with solutions. Compiled with my groupmates from Ms. Reyes's class — sobrang helpful sa long test."><?= e($old['description']??'') ?></textarea>
        </div>
      </div>

      <div style="display:flex;align-items:center;justify-content:space-between;margin-top:28px;padding-top:20px;border-top:1px solid var(--border-subtle);flex-wrap:wrap;gap:12px;">
        <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--fg-3);">
          <?= icon('info',15) ?>
          <span>By uploading, you confirm this is your own or shared with permission.</span>
        </div>
        <div style="display:flex;gap:10px;">
          <a href="index.php" class="btn btn-ghost">Cancel</a>
          <button type="submit" class="btn btn-accent">
            <?= icon('upload',16,2) ?> Publish reviewer
          </button>
        </div>
      </div>
    </div>
  </form>
</div>
</main>

<script>
// ✅ FIX 1: Full drag-and-drop + click-to-select support
(function () {
  const zone  = document.getElementById('drop-zone');
  const input = document.getElementById('material');
  const label = document.getElementById('file-name');

  // Drag visual feedback
  ['dragenter', 'dragover'].forEach(function (evt) {
    zone.addEventListener(evt, function (e) {
      e.preventDefault();
      e.stopPropagation();
      zone.style.borderColor = 'var(--brand-blue-500)';
      zone.style.background  = 'var(--brand-blue-50)';
    });
  });

  ['dragleave', 'dragend'].forEach(function (evt) {
    zone.addEventListener(evt, function (e) {
      e.preventDefault();
      e.stopPropagation();
      zone.style.borderColor = 'var(--border-strong)';
      zone.style.background  = 'var(--bg-elevated)';
    });
  });

  // Drop: assign file to the real input so the form submits it
  zone.addEventListener('drop', function (e) {
    e.preventDefault();
    e.stopPropagation();
    zone.style.borderColor = 'var(--border-strong)';
    zone.style.background  = 'var(--bg-elevated)';

    var files = e.dataTransfer.files;
    if (!files || files.length === 0) return;

    // Assign to input via DataTransfer (supported in all modern browsers)
    var dt = new DataTransfer();
    dt.items.add(files[0]);
    input.files = dt.files;

    showFile(input);
  });

  // Click-to-open (the <label for="material"> already does this,
  // but keeping an explicit handler avoids double-fire on the label itself)
  function showFile(inp) {
    if (inp.files && inp.files[0]) {
      var f = inp.files[0];
      label.textContent = '✓ ' + f.name + ' (' + (f.size / 1048576).toFixed(1) + ' MB)';
    }
  }

  // Expose to inline onchange="showFile(this)"
  window.showFile = showFile;
})();
</script>
</script>

<?php layout_end(); ?>