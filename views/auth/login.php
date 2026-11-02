<?php
$oldUsername = pull_old_username();
$demo = [
    ['admin',    'Quản trị viên'],
    ['gv001',    'Giáo viên chủ nhiệm'],
    ['22110001', 'Cán bộ lớp'],
    ['22110002', 'Sinh viên'],
];
?>
<div class="auth-wrap">
    <section class="auth-hero">
        <div class="hero-inner">
            <div class="brand-logo brand-logo-lg">RL</div>
            <h1>Hệ thống quản lý<br>kết quả rèn luyện sinh viên</h1>
            <p class="lead-text">Tự đánh giá, chấm điểm và xét duyệt rèn luyện minh bạch, nhanh gọn trên cùng một nơi.</p>
            <ul class="hero-list">
                <li>Sinh viên tự đánh giá và đính kèm minh chứng</li>
                <li>Cán bộ lớp và giáo viên chủ nhiệm xét duyệt theo từng cấp</li>
                <li>Theo dõi kết quả qua từng học kỳ</li>
            </ul>
        </div>
    </section>

    <section class="auth-panel">
        <div class="auth-card">
            <h2 class="auth-title">Chào mừng trở lại 👋</h2>
            <p class="text-muted mb-4">Đăng nhập để tiếp tục</p>

            <?php require BASE_PATH . '/views/partials/flash.php'; ?>

            <form method="post" action="<?= e(url('auth/login')) ?>" novalidate>
                <?= csrf_field() ?>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Tài khoản"
                           value="<?= e($oldUsername) ?>" autocomplete="username" autofocus required>
                    <label for="username">Tài khoản (mã sinh viên / mã cán bộ)</label>
                </div>

                <div class="input-group mb-4 password-group">
                    <div class="form-floating flex-grow-1">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Mật khẩu"
                               autocomplete="current-password" required>
                        <label for="password">Mật khẩu</label>
                    </div>
                    <button type="button" class="btn btn-toggle" data-toggle-password="#password" aria-label="Hiện hoặc ẩn mật khẩu">Hiện</button>
                </div>

                <button type="submit" class="btn btn-brand btn-lg w-100">Đăng nhập</button>
            </form>

            <?php if (app_config('debug')): ?>
                <div class="demo-box mt-4">
                    <div class="demo-title">Tài khoản thử (mật khẩu: <code>123456</code>)</div>
                    <div class="demo-list">
                        <?php foreach ($demo as [$u, $label]): ?>
                            <button type="button" class="demo-item" data-fill-user="<?= e($u) ?>" data-fill-pass="123456">
                                <strong><?= e($u) ?></strong><span><?= e($label) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="small text-muted mt-2">Bấm vào một dòng để điền sẵn. Ẩn khung này bằng debug = false trong config/app.php.</div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
