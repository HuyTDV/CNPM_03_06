<section class="welcome-card">
    <h1 class="h3 mb-1">Xin chào, <?= e(Auth::user()['full_name']) ?> 👋</h1>
    <?php if ($student): ?>
        <p class="mb-0 text-muted">
            Mã sinh viên <strong><?= e($student['student_code']) ?></strong> · Lớp <strong><?= e($student['class_code']) ?></strong>
            · <?= e($student['faculty_name']) ?> · Khóa <?= e($student['cohort']) ?>
        </p>
    <?php endif; ?>
</section>

<?php if (!$student): ?>
    <div class="alert alert-warning mt-4">Tài khoản của bạn chưa gắn với hồ sơ sinh viên. Vui lòng liên hệ quản trị viên.</div>
<?php else: ?>
    <h2 class="h5 mt-4 mb-3">Các đợt đánh giá rèn luyện</h2>
    <?php if (!$periods): ?>
        <div class="alert alert-info">Chưa có đợt đánh giá nào được mở.</div>
    <?php else: ?>
        <div class="table-card">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Đợt đánh giá</th><th>Giai đoạn</th><th>Hạn nộp</th><th>Bài của bạn</th>
                    <th class="text-center">Điểm tự chấm</th><th class="text-center">Kết quả</th><th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($periods as $p): ?>
                    <tr>
                        <td><div class="fw-semibold"><?= e($p['name']) ?></div><small class="text-muted"><?= e($p['semester_name']) ?></small></td>
                        <td><?= phase_badge($p['phase']) ?></td>
                        <td class="text-nowrap"><?= e(fmt_datetime($p['student_end_at'])) ?></td>
                        <td><?= $p['evaluation_id'] ? eval_badge($p['evaluation_status']) : '<span class="text-muted">Chưa bắt đầu</span>' ?></td>
                        <td class="text-center"><?= e(fmt_score($p['self_total'])) ?></td>
                        <td class="text-center">
                            <?php if ($p['show_result']): ?>
                                <strong><?= e(fmt_score($p['final_total'] ?? $p['advisor_total'])) ?></strong>
                                <div class="small"><?= e($p['rating']) ?></div>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($p['can_edit']): ?>
                                <a class="btn btn-brand btn-sm px-3" href="<?= e(url('assessment/form/' . $p['id'])) ?>">
                                    <?= !$p['evaluation_id'] ? 'Bắt đầu đánh giá' : ($p['evaluation_status'] === EVAL_NEED_EDIT ? 'Chỉnh sửa lại' : 'Tiếp tục') ?>
                                </a>
                            <?php elseif ($p['evaluation_id']): ?>
                                <a class="btn btn-outline-secondary btn-sm px-3" href="<?= e(url('assessment/form/' . $p['id'])) ?>">Xem bài</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <p class="text-muted small mt-3">Xem điểm chi tiết các cấp và gửi khiếu nại sẽ có ở các giai đoạn sau.</p>
<?php endif; ?>
