<?php
// US-08: quản lý đợt đánh giá và 4 mốc thời gian. Chỉ quản trị viên.
//
// Quy tắc (kiểm tra bằng PHP):
//  - Thứ tự mốc: bắt đầu <= hạn SV nộp <= hạn lớp chấm <= hạn GVCN duyệt.
//  - Khung điểm phải thuộc đúng học kỳ của đợt, đang được bật và hợp lệ.
//  - Đợt đã có sinh viên đánh giá: không đổi học kỳ/khung điểm (chỉ sửa tên, ghi chú, mốc thời gian) và không xóa.
//  - Mở khóa đợt đã quá hạn GVCN duyệt phải gia hạn trước (nếu không sẽ bị khóa lại ngay).
class PeriodController extends Controller
{
    public function index()
    {
        $this->requireRole([ROLE_ADMIN]);
        Period::lockExpired();
        $periods = Period::listAll();
        foreach ($periods as $k => $p) {
            $periods[$k]['phase'] = Period::phase($p);
            $periods[$k]['has_evaluations'] = Period::hasEvaluations($p['id']);
        }
        $this->render('admin/periods', ['pageTitle' => 'Đợt đánh giá', 'periods' => $periods]);
    }

    public function create()
    {
        $this->requireRole([ROLE_ADMIN]);
        $this->form(null);
    }

    public function store()
    {
        $user = $this->requireRole([ROLE_ADMIN]);
        $this->requirePost();
        [$data, $error] = $this->readForm(null, false);
        if ($error) {
            $this->failForm($error, 'period/create');
        }
        Period::create($data, $user['id']);
        flash('success', 'Đã tạo đợt đánh giá.');
        $this->redirect('period');
    }

    public function edit(int $id)
    {
        $this->requireRole([ROLE_ADMIN]);
        $this->form($this->findOr404($id));
    }

    public function update(int $id)
    {
        $this->requireRole([ROLE_ADMIN]);
        $this->requirePost();
        $period = $this->findOr404($id);
        [$data, $error] = $this->readForm($period, Period::hasEvaluations($id));
        if ($error) {
            $this->failForm($error, 'period/edit/' . $id);
        }
        Period::update($id, $data);
        flash('success', 'Đã cập nhật đợt đánh giá.');
        $this->redirect('period');
    }

    public function lock(int $id)
    {
        $this->requireRole([ROLE_ADMIN]);
        $this->requirePost();
        $this->findOr404($id);
        Period::lock($id);
        flash('success', 'Đã khóa đợt đánh giá. Sinh viên không thể nộp hay sửa bài trong đợt này.');
        $this->redirect('period');
    }

    public function unlock(int $id)
    {
        $this->requireRole([ROLE_ADMIN]);
        $this->requirePost();
        $period = $this->findOr404($id);
        if ($period['advisor_end_at'] <= now_str()) {
            flash('danger', 'Đợt đã quá hạn GVCN duyệt (' . fmt_datetime($period['advisor_end_at']) . '). Hãy gia hạn các mốc thời gian trước khi mở khóa.');
            $this->redirect('period/edit/' . $id);
        }
        Period::unlock($id);
        flash('success', 'Đã mở khóa đợt đánh giá.');
        $this->redirect('period');
    }

    public function delete(int $id)
    {
        $this->requireRole([ROLE_ADMIN]);
        $this->requirePost();
        $this->findOr404($id);
        if (Period::hasEvaluations($id)) {
            flash('danger', 'Đợt đã có sinh viên đánh giá nên không thể xóa. Hãy khóa đợt thay vì xóa.');
            $this->redirect('period');
        }
        Period::delete($id);
        flash('success', 'Đã xóa đợt đánh giá.');
        $this->redirect('period');
    }

    // ---------------------------------------------------------------- Hỗ trợ

    private function findOr404($id)
    {
        $period = Period::find($id);
        if (!$period) {
            $this->abort(404, 'Không tìm thấy đợt đánh giá.');
        }
        return $period;
    }

    private function form($period)
    {
        $frameworks = Criteria::activeFrameworks();
        // Đợt đang dùng một khung đã bị tắt thì vẫn phải hiện khung đó trong danh sách chọn
        if ($period && !in_array($period['framework_id'], array_column($frameworks, 'id'))) {
            $frameworks[] = Criteria::framework($period['framework_id']);
        }
        $this->render('admin/period-form', [
            'pageTitle'  => $period ? 'Sửa đợt đánh giá' : 'Tạo đợt đánh giá',
            'period'     => $period,
            'locked'     => $period && Period::hasEvaluations($period['id']),
            'semesters'  => Semester::listAll(),
            'frameworks' => $frameworks,
            'scripts'    => ['js/period.js'],
        ]);
    }

    // Báo lỗi và giữ lại dữ liệu đã nhập
    private function failForm($message, $backTo)
    {
        $_SESSION['old_period'] = $_POST;
        flash('danger', $message);
        $this->redirect($backTo);
    }

    // Đọc và kiểm tra form. Trả về [dữ liệu, null] hoặc [null, thông báo lỗi].
    private function readForm($existing, $hasEvaluations)
    {
        $name = $this->post('name');
        if ($name === '' || mb_strlen($name) > 150) {
            return [null, 'Tên đợt không được để trống và tối đa 150 ký tự.'];
        }
        if (mb_strlen($this->post('note')) > 500) {
            return [null, 'Ghi chú tối đa 500 ký tự.'];
        }

        $times = [];
        foreach (['start_at' => 'Thời gian bắt đầu', 'student_end_at' => 'Hạn sinh viên nộp',
                  'class_end_at' => 'Hạn lớp chấm', 'advisor_end_at' => 'Hạn GVCN duyệt'] as $key => $label) {
            $times[$key] = to_datetime($this->post($key));
            if ($times[$key] === null) {
                return [null, $label . ' không phải ngày giờ hợp lệ.'];
            }
        }
        if (!($times['start_at'] <= $times['student_end_at'] && $times['student_end_at'] <= $times['class_end_at']
              && $times['class_end_at'] <= $times['advisor_end_at'])) {
            return [null, 'Các mốc phải theo thứ tự: bắt đầu ≤ hạn sinh viên nộp ≤ hạn lớp chấm ≤ hạn GVCN duyệt.'];
        }
        if (!$existing && $times['advisor_end_at'] <= now_str()) {
            return [null, 'Hạn GVCN duyệt phải nằm trong tương lai.'];
        }

        if ($hasEvaluations) {            // đã có bài: giữ nguyên học kỳ và khung điểm
            $semesterId = (int) $existing['semester_id'];
            $frameworkId = (int) $existing['framework_id'];
        } else {
            $semesterId = (int) $this->post('semester_id');
            $frameworkId = (int) $this->post('framework_id');
            $fw = Criteria::framework($frameworkId);
            if (!Semester::find($semesterId)) {
                return [null, 'Vui lòng chọn học kỳ.'];
            }
            if (!$fw) {
                return [null, 'Vui lòng chọn khung điểm.'];
            }
            if ((int) $fw['semester_id'] !== $semesterId) {
                return [null, 'Khung điểm "' . $fw['name'] . '" không thuộc học kỳ đã chọn.'];
            }
            $unchanged = $existing && (int) $existing['framework_id'] === $frameworkId;
            if (!$unchanged) {
                if (!$fw['is_active']) {
                    return [null, 'Khung điểm phải đang được bật. Hãy bật khung ở trang Khung điểm & tiêu chí.'];
                }
                $problems = framework_problems($fw, Criteria::tree($frameworkId));
                if ($problems) {
                    return [null, "Khung điểm chưa hợp lệ:\n" . implode("\n", $problems)];
                }
            }
        }

        return [[
            'name' => $name, 'semester_id' => $semesterId, 'framework_id' => $frameworkId,
            'start_at' => $times['start_at'], 'student_end_at' => $times['student_end_at'],
            'class_end_at' => $times['class_end_at'], 'advisor_end_at' => $times['advisor_end_at'],
            'note' => $this->post('note') ?: null,
        ], null];
    }
}
