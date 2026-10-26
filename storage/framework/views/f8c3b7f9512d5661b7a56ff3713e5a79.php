<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($title ?? 'Backup'); ?> · Backup Master <?php echo e(config('bm.lokasi')); ?></title>
    <script>document.documentElement.classList.add('js')</script>
    <link rel="stylesheet" href="<?php echo e(asset('assets/app.css')); ?>?v=<?php echo e(@filemtime(public_path('assets/app.css'))); ?>">
    <meta name="theme-color" content="#0f766e">
    <meta name="robots" content="noindex, nofollow">
    <link rel="apple-touch-icon" href="<?php echo e(asset('icons/apple-touch-icon.png')); ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo e(asset('icons/icon-192.png')); ?>">
    <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body class="<?php echo \Illuminate\Support\Arr::toCssClasses(['bare' => $bare ?? false]); ?>">
<a class="skip" href="#isi">Lompat ke isi halaman</a>
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <symbol id="i-home" viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
    <symbol id="i-check" viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="2.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="m8.5 12.5 2.5 2.5 4.5-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
    <symbol id="i-star" viewBox="0 0 24 24"><path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
    <symbol id="i-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c.6-3.4 3.2-5.5 6.5-5.5s5.9 2.1 6.5 5.5M16 4.8a3.5 3.5 0 0 1 0 6.4M18 14.8c1.9.7 3.2 2.5 3.5 5.2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-menu" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-heart" viewBox="0 0 24 24"><path d="M12 20s-7.5-4.4-7.5-10A4.3 4.3 0 0 1 12 7.6 4.3 4.3 0 0 1 19.5 10c0 5.6-7.5 10-7.5 10z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
    <symbol id="i-eye" viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/></symbol>
    <symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
    <symbol id="i-folder" viewBox="0 0 24 24"><path d="M3 7a2 2 0 0 1 2-2h4l2 2.5h8a2 2 0 0 1 2 2V18a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
    <symbol id="i-gear" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.3 5.3l2.1 2.1M16.6 16.6l2.1 2.1M5.3 18.7l2.1-2.1M16.6 7.4l2.1-2.1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
</svg>
<?php if(auth()->guard()->check()): ?>
    <?php if (! ($bare ?? false)): ?>
    <?php
        $me = auth()->user();
        $initial = mb_strtoupper(mb_substr(trim(preg_replace('/^(Dra?\.|Drs\.|Ir\.|H\.|Hj\.)\s*/i', '', $me->nama)), 0, 1));
        $tabs = [
            ['beranda', [], 'Ringkasan', 'i-home', request()->routeIs('beranda', 'aplikasi.*', 'berkas.*')],
            ['jelajah.index', [], 'Jelajah Data', 'i-eye', request()->routeIs('jelajah.*')],
            ['sumber.index', [], 'Sumber', 'i-folder', request()->routeIs('sumber.*')],
            ['audit', [], 'Log', 'i-clock', request()->routeIs('audit')],
        ];
    ?>
    <header class="nav"><div class="nav-in">
        <a class="brand" href="<?php echo e(route('beranda')); ?>"><img class="logo-img" src="<?php echo e(asset('icons/icon-192.png')); ?>" alt="" width="36" height="36"><span><b>Backup Master</b><small><?php echo e(config('bm.lokasi')); ?> · <?php echo e(config('bm.school_name')); ?></small></span></a>
        <nav class="tabs" aria-label="Menu utama">
            <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$r, $arg, $label, $icon, $on]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route($r, $arg)); ?>" <?php if($on): ?> class="on" aria-current="page" <?php endif; ?>><svg class="ic"><use href="#<?php echo e($icon); ?>"/></svg><?php echo e($label); ?></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </nav>
        <details class="acct" data-close-outside>
            <summary aria-label="Menu akun <?php echo e($me->nama); ?>"><span class="avatar" aria-hidden="true"><?php echo e($initial); ?></span><span class="acct-name"><?php echo e($me->nama); ?><small>Admin</small></span><span class="caret" aria-hidden="true">▾</span></summary>
            <div class="acct-menu" role="menu">
                <div class="acct-head"><b><?php echo e($me->nama); ?></b><small><?php echo e('@'.$me->username); ?></small></div>
                <a role="menuitem" href="<?php echo e(route('akun')); ?>">🔐 Akun &amp; verifikasi</a>
                <form method="post" action="<?php echo e(route('logout')); ?>"><?php echo csrf_field(); ?><button class="menu-btn danger" role="menuitem">⎋ Keluar</button></form>
            </div>
        </details>
    </div></header>
    <?php endif; ?>
<?php endif; ?>
<main id="isi" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['wrap', 'wrap-bare' => $bare ?? false]); ?>" <?php if(isset($width)): ?> style="max-width:<?php echo e($width); ?>" <?php endif; ?> tabindex="-1">
    <?php echo $__env->make('partials.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->yieldContent('content'); ?>
</main>
<script src="<?php echo e(asset('assets/app.js')); ?>?v=<?php echo e(@filemtime(public_path('assets/app.js'))); ?>"></script>
</body>
</html>
<?php /**PATH /home/claude/backup-master/resources/views/layouts/app.blade.php ENDPATH**/ ?>