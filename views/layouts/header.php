<?php
// Đầu trang chung. $bare = true: trang trống không có menu (đăng nhập). Chưa đăng nhập cũng luôn là trang trống.
$user = Auth::user();
if ($user === null) {
    $bare = true;
}
$menu = [];
if (!$bare) {
    $menu = (function () { return require BASE_PATH . '/config/menu.php'; })()[$user['role']] ?? [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title><?= e((isset($pageTitle) ? $pageTitle . ' · ' : '') . app_config('name')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body>
<?php if (!$bare): ?>
    <header class="topbar">
        <a class="brand" href="<?= e(url(Auth::homeFor($user['role']))) ?>">
            <span class="brand-logo">RL</span> Quản lý rèn luyện
        </a>

        <div class="dropdown">
            <button class="user-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar"><?= e(initials($user['full_name'])) ?></span>
                <span class="user-info text-start">
                    <span class="user-name"><?= e($user['full_name']) ?></span>
                    <span class="role-chip role-<?= e($user['role']) ?>"><?= e(ROLE_LABELS[$user['role']] ?? $user['role']) ?></span>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li class="px-3 py-2 small text-muted">Đăng nhập với <strong><?= e($user['username']) ?></strong></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="post" action="<?= e(url('auth/logout')) ?>" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="dropdown-item text-danger">Đăng xuất</button>
                    </form>
                </li>
            </ul>
        </div>
    </header>

    <div class="app-shell">
        <aside class="sidebar">
            <nav class="side-nav">
                <?php foreach ($menu as $item): ?>
                    <?php [$label, $target, $prefix] = $item; ?>
                    <?php if ($target === null): ?>
                        <span class="side-link disabled"><?= e($label) ?> <small class="soon">Sắp có</small></span>
                    <?php else: ?>
                        <?php
                        // $prefix có thể gồm nhiều tiền tố ngăn bằng dấu |, ví dụ 'class/students|class/grade'
                        $active = false;
                        foreach (array_filter(explode('|', $prefix)) as $one) {
                            if (Router::path() === $one || strpos(Router::path(), $one . '/') === 0) {
                                $active = true;
                            }
                        }
                        ?>
                        <a class="side-link <?= $active ? 'active' : '' ?>" href="<?= e(url($target)) ?>"><?= e($label) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </aside>

        <main class="app-content">
            <?php require BASE_PATH . '/views/partials/flash.php'; ?>
<?php endif; ?>
