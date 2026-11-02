<?php
// Khung trang "sẽ làm trong tương lai" dùng chung cho 4 vai trò.
// Cần: $heading (tiêu đề), $planned (mảng [emoji, tên chức năng, mô tả ngắn]).
$__user = Auth::user();
?>
<section class="welcome-card">
    <div>
        <h1 class="h3 mb-1">Xin chào, <?= e($__user['full_name']) ?> 👋</h1>
        <p class="mb-0 text-muted">
            Bạn đã đăng nhập thành công với vai trò
            <span class="role-chip role-<?= e($__user['role']) ?>"><?= e(ROLE_LABELS[$__user['role']] ?? '') ?></span>
        </p>
    </div>
</section>

<h2 class="h5 mt-4 mb-3"><?= e($heading) ?></h2>
<div class="row g-3">
    <?php foreach ($planned as [$icon, $title, $desc]): ?>
        <div class="col-md-6 col-xl-4">
            <div class="feature-card">
                <div class="feature-icon"><?= e($icon) ?></div>
                <div>
                    <div class="fw-semibold"><?= e($title) ?> <span class="soon-badge">Sắp có</span></div>
                    <div class="small text-muted"><?= e($desc) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<p class="text-muted small mt-4">Chức năng này sẽ được xây dựng trong các giai đoạn tiếp theo của đồ án.</p>
