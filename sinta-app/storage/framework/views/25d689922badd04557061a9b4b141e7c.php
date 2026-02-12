<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'SINTA Dashboard'); ?> - Sekolah Vokasi IPB</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('css/style.css')); ?>">
    
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-logo"><img src="<?php echo e(asset('images/logo_sinta.png')); ?>" alt="Logo SINTA" class="logo"></div>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="<?php echo e(route('dashboard')); ?>" class="<?php echo e(Request::routeIs('dashboard') ? 'active' : ''); ?>">
                    <span class="icon">📈</span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?php echo e(route('beranda')); ?>" class="<?php echo e(Request::routeIs('beranda') ? 'active' : ''); ?>">
                    <span class="icon">🔍</span>
                    <span>Cari Data</span>
                </a>
            </li>
            <li>
                <a href="<?php echo e(route('daftar-dosen')); ?>" class="<?php echo e(Request::routeIs('daftar-dosen') ? 'active' : ''); ?>">
                    <span class="icon">👥</span>
                    <span>Daftar Dosen</span>
                </a>
            </li>
        </ul>
    </aside>

    <div class="container">
        <div class="main-content">
            <main class="content">
                <?php echo $__env->yieldContent('content'); ?>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Setup CSRF token untuk AJAX requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    </script>
    
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html><?php /**PATH C:\Users\Siti Farah Fakhirah\Documents\projects\website-sinta\sinta-app\resources\views/app.blade.php ENDPATH**/ ?>