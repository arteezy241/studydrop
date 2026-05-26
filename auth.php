<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/layout.php';

if (is_logged_in()) { header('Location: dashboard.php'); exit; }

$mode   = in_array($_GET['mode'] ?? '', ['signup']) ? 'signup' : 'signin';
$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $mode = ($_POST['mode'] ?? '') === 'signup' ? 'signup' : 'signin';
    $old  = [
        'email'        => trim($_POST['email']        ?? ''),
        'name'         => trim($_POST['name']         ?? ''),
        'username'     => trim($_POST['username']     ?? ''),
        'school'       => trim($_POST['school']       ?? ''),
    ];

    if ($mode === 'signup') {
        $pass    = $_POST['password']         ?? '';
        $confirm = $_POST['password_confirm'] ?? '';

        if (!validate_email($old['email']))           $errors['email']    = 'Enter a valid email address.';
        if (empty($old['name']))                      $errors['name']     = 'Name is required.';
        $ue = validate_username($old['username'] ?: preg_replace('/[^a-z0-9]/','',strtolower($old['name'])));
        if ($ue) $errors['username'] = $ue[0];
        $pe = validate_password($pass);
        if ($pe) $errors['password'] = implode(' ', $pe);
        if ($pass !== $confirm) $errors['password_confirm'] = 'Passwords do not match.';

        if (empty($errors)) {
            $db = db();
            $chk = $db->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
            $chk->execute([$old['email']]);
            if ($chk->fetchColumn()) {
                $errors['email'] = 'Email already registered.';
            } else {
                $colors = ['#2C58F2','#1FA663','#E13A3A','#D9A300','#6B3AE1','#1E84D6','#7A0E1A'];
                $color  = $colors[array_rand($colors)];
                $uname  = preg_replace('/[^a-z0-9_]/','',strtolower(str_replace(' ','_',$old['name'])));
                if (empty($uname)) $uname = 'user' . rand(1000,9999);
                $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost'=>12]);
                $db->prepare('INSERT INTO users (email,username,display_name,password_hash,avatar_color) VALUES (?,?,?,?,?)')
                   ->execute([$old['email'], $uname, $old['name'], $hash, $color]);
                $_SESSION['user_id'] = (int)$db->lastInsertId();
                flash('success', 'Welcome to StudyDrop, ' . $old['name'] . '! 🎉');
                header('Location: dashboard.php'); exit;
            }
        }
    } else {
        $pass = $_POST['password'] ?? '';
        if (empty($old['email']))  $errors['email']    = 'Email is required.';
        if (empty($pass))          $errors['password'] = 'Password is required.';
        if (empty($errors)) {
            $stmt = db()->prepare('SELECT * FROM users WHERE email=? AND is_active=1 LIMIT 1');
            $stmt->execute([$old['email']]);
            $user = $stmt->fetch();
            if (!$user || !password_verify($pass, $user['password_hash'])) {
                $errors['email'] = 'Invalid email or password.';
            } else {
                $_SESSION['user_id'] = (int)$user['id'];
                header('Location: dashboard.php'); exit;
            }
        }
    }
}

$phSchools = ['UP Diliman','UP Manila','UP Los Baños','Ateneo de Manila',
              'De La Salle University','UST','PUP','MSU-IIT','USC Cebu','Mapua','Other'];

$features = [
    ['Libre kahit ilang download.',   'No paywall, no limits, no ads.'],
    ['Upload from your batchmates.',  'Reviewers tagged by school + course code.'],
    ['Sayang kasi solo mo lang.',     'Pay it forward to next year\'s juniors.'],
];

// Auth page — no layout_start/end (custom full-page layout)
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title><?= $mode==='signup'?'Create account':'Sign in' ?> — StudyDrop</title>
<link rel="stylesheet" href="colors_and_type.css"/>
<link rel="stylesheet" href="styles.css"/>
</head>
<body style="margin:0;min-height:100vh;display:flex;flex-direction:column;">
<div class="app">

<div style="flex:1;display:grid;grid-template-columns:1fr 1.05fr;min-height:100vh;" class="auth-grid">

  <!-- LEFT: brand panel -->
  <aside style="background:linear-gradient(180deg,var(--brand-blue-700) 0%,var(--brand-blue-800) 100%);color:white;padding:56px;display:flex;flex-direction:column;justify-content:space-between;position:relative;overflow:hidden;" class="auth-left">
    <div style="position:absolute;top:-80px;right:-80px;width:280px;height:280px;border-radius:50%;background:var(--brand-yellow-300);opacity:.2;"></div>
    <div style="position:absolute;top:-40px;right:-40px;width:200px;height:200px;border-radius:50%;background:var(--brand-yellow-300);opacity:.3;"></div>

    <div style="position:relative;">
      <?php if (file_exists(__DIR__.'/assets/logo-on-dark.svg')): ?>
        <img src="assets/logo-on-dark.svg" alt="StudyDrop" style="height:34px;"/>
      <?php else: ?>
        <span style="font-family:var(--font-display);font-weight:800;font-size:26px;color:white;letter-spacing:-0.02em;">StudyDrop</span>
      <?php endif; ?>
    </div>

    <div style="position:relative;">
      <div style="font-size:12px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--brand-yellow-300);margin-bottom:16px;">For Filipino college students</div>
      <h1 style="font-family:var(--font-display);font-weight:800;font-size:52px;line-height:1.05;letter-spacing:-0.025em;margin-bottom:18px;">
        Pasahan ng reviewer, libre forever.
      </h1>
      <p style="font-size:17px;line-height:1.55;color:rgba(255,255,255,.78);max-width:440px;">
        Join 24,000+ batchmates sharing class notes, transes, and exam reviewers — completely free.
      </p>
      <div style="display:flex;flex-direction:column;gap:14px;margin-top:32px;">
        <?php foreach ($features as [$h,$s]): ?>
        <div style="display:flex;gap:12px;align-items:flex-start;">
          <div style="width:22px;height:22px;border-radius:999px;background:var(--brand-yellow-300);color:var(--neutral-900);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
            <?= icon('check',13,3) ?>
          </div>
          <div>
            <div style="font-weight:600;font-size:15px;"><?= e($h) ?></div>
            <div style="font-size:14px;color:rgba(255,255,255,.65);margin-top:2px;"><?= e($s) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div style="position:relative;font-size:13px;color:rgba(255,255,255,.55);">© <?= date('Y') ?> StudyDrop · Made in Manila ☕</div>
  </aside>

  <!-- RIGHT: form -->
  <div style="display:flex;align-items:center;justify-content:center;padding:48px 56px;background:var(--bg-elevated);">
    <div style="width:100%;max-width:400px;">

      <!-- Mode tabs -->
      <div style="display:flex;background:var(--bg-muted);border-radius:12px;padding:4px;margin-bottom:32px;">
        <?php foreach (['signin'=>'Sign in','signup'=>'Create account'] as $m=>$lbl): ?>
        <a href="auth.php?mode=<?= $m ?>"
           style="flex:1;text-align:center;padding:10px 12px;font-size:14px;font-weight:600;border-radius:8px;text-decoration:none;transition:all 120ms var(--ease-out);
                  <?= $mode===$m ? 'background:var(--bg-elevated);color:var(--fg-1);box-shadow:var(--shadow-xs);' : 'color:var(--fg-3);' ?>">
          <?= $lbl ?>
        </a>
        <?php endforeach; ?>
      </div>

      <h2 style="font-family:var(--font-display);font-weight:700;font-size:28px;line-height:1.15;letter-spacing:-0.02em;margin-bottom:6px;">
        <?= $mode==='signin' ? 'Welcome back, ka-StudyDrop.' : 'Sumali sa community.' ?>
      </h2>
      <p style="font-size:14px;color:var(--fg-3);margin-bottom:28px;">
        <?= $mode==='signin' ? 'Sign in to access your saved library and uploads.' : 'Quick — under 30 seconds. We\'ll verify your school email later.' ?>
      </p>

      <!-- Social buttons -->
      <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:22px;">
        <button class="btn btn-ghost" style="width:100%;padding:12px 16px;justify-content:center;">
          <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4C12.955 4 4 12.955 4 24s8.955 20 20 20s20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/><path fill="#FF3D00" d="m6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4C16.318 4 9.656 8.337 6.306 14.691z"/><path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0 1 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/><path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 0 1-4.087 5.571l.003-.002l6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/></svg>
          Continue with Google
        </button>
        <button class="btn btn-ghost" style="width:100%;padding:12px 16px;justify-content:center;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669c1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
          Continue with Facebook
        </button>
      </div>

      <div style="display:flex;align-items:center;gap:12px;margin:18px 0;color:var(--fg-3);font-size:12px;">
        <div style="flex:1;height:1px;background:var(--border-subtle);"></div>
        <span>or with email</span>
        <div style="flex:1;height:1px;background:var(--border-subtle);"></div>
      </div>

      <form method="POST" action="auth.php?mode=<?= $mode ?>" novalidate style="display:flex;flex-direction:column;gap:14px;">
        <?= csrf_field() ?>
        <input type="hidden" name="mode" value="<?= e($mode) ?>">

        <?php if ($mode==='signup'): ?>
        <div class="field">
          <label class="field-label">Your name</label>
          <input class="input" type="text" name="name" value="<?= e($old['name']??'') ?>" placeholder="Liza Marasigan" autocomplete="name"/>
          <?php if (isset($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>
        </div>
        <div class="field">
          <label class="field-label">School</label>
          <select class="select" name="school">
            <option value="">Select your school...</option>
            <?php foreach ($phSchools as $sc): ?>
            <option value="<?= e($sc) ?>" <?= ($old['school']??'')===$sc?'selected':'' ?>><?= e($sc) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <div class="field">
          <label class="field-label">School email<?= $mode==='signup' ? ' <span style="font-weight:400;color:var(--fg-3);font-size:12px;">— we\'ll send a verification link</span>' : '' ?></label>
          <input class="input" type="email" name="email" value="<?= e($old['email']??'') ?>" placeholder="liza.marasigan@up.edu.ph" autocomplete="email"/>
          <?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
        </div>

        <div class="field">
          <label class="field-label">Password</label>
          <input class="input" type="password" name="password" placeholder="<?= $mode==='signup'?'At least 8 characters':'Your password' ?>" autocomplete="<?= $mode==='signup'?'new-password':'current-password' ?>"/>
          <?php if (isset($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>
        </div>

        <?php if ($mode==='signup'): ?>
        <div class="field">
          <label class="field-label">Confirm password</label>
          <input class="input" type="password" name="password_confirm" placeholder="Repeat password" autocomplete="new-password"/>
          <?php if (isset($errors['password_confirm'])): ?><span class="field-error"><?= e($errors['password_confirm']) ?></span><?php endif; ?>
        </div>
        <label style="display:flex;align-items:flex-start;gap:10px;font-size:13px;color:var(--fg-2);line-height:1.5;cursor:pointer;">
          <input type="checkbox" name="agree" style="margin-top:2px;accent-color:var(--brand-blue-500);" required/>
          <span>I agree to share reviewers I own or have permission to share, and to follow the <a href="#" style="color:var(--brand-blue-600);font-weight:600;">community guidelines</a>.</span>
        </label>
        <?php else: ?>
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;color:var(--fg-2);">
            <input type="checkbox" name="remember" style="accent-color:var(--brand-blue-500);"/> Remember me
          </label>
          <a href="#" style="color:var(--brand-blue-600);font-weight:600;">Forgot password?</a>
        </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center;margin-top:8px;">
          <?= $mode==='signin' ? 'Sign in' : 'Create my account' ?>
        </button>
      </form>

      <p style="text-align:center;margin-top:22px;font-size:14px;color:var(--fg-3);">
        <?= $mode==='signin' ? 'New here? ' : 'Already have an account? ' ?>
        <a href="auth.php?mode=<?= $mode==='signin'?'signup':'signin' ?>" style="color:var(--brand-blue-600);font-weight:600;">
          <?= $mode==='signin' ? 'Create an account' : 'Sign in' ?>
        </a>
      </p>

    </div>
  </div>
</div>
</div>
</body>
</html>