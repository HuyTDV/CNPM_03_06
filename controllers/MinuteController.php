<?php
// US-05: biên bản họp lớp xét điểm rèn luyện. Chỉ cán bộ lớp, chỉ lớp của mình.
// Xuất biên bản: xem bản in (in ra giấy hoặc lưu PDF từ trình duyệt) và tải file Word (.doc).
class MinuteController extends Controller
{
    // Menu "Biên bản họp lớp": chuyển tới đợt mới nhất
    public function current()
    {
        $this->mine();
        $period = Period::latest();
        if (!$period) {
            flash('info', 'Hiện chưa có đợt đánh giá nào.');
            $this->redirect('class/dashboard');
        }
        $this->redirect('minute/show/' . $period['id']);
    }

    // Form soạn biên bản
    public function show(int $periodId)
    {
        [$user, $student, $class] = $this->mine();
        $period = $this->periodOr404($periodId);
        $minute = ClassMinute::find($class['id'], $periodId);
        $list = Review::students($class['id'], $periodId);

        $old = $_SESSION['old_minute'] ?? [];
        unset($_SESSION['old_minute']);
        $this->render('class/minutes', [
            'pageTitle' => 'Biên bản họp lớp',
            'class'     => $class,
            'period'    => $period,
            'minute'    => $minute,
            'old'       => $old,
            'user'      => $user,
            'total'     => count($list),
        ]);
    }

    // POST: lưu biên bản (có rồi thì cập nhật)
    public function save(int $periodId)
    {
        [$user, $student, $class] = $this->mine();
        $this->requirePost();
        $this->periodOr404($periodId);
        $back = 'minute/show/' . $periodId;

        $meetingAt = to_datetime($this->post('meeting_at'));
        $venue = $this->post('venue');
        $chair = $this->post('chairperson');
        $secretary = $this->post('secretary');
        $present = $this->post('present_count');
        $total = $this->post('total_count');
        $content = $this->post('content');
        $conclusion = $this->post('conclusion');

        $error = null;
        if ($meetingAt === null) {
            $error = 'Thời gian họp không hợp lệ.';
        } elseif ($venue === '' || mb_strlen($venue) > 200) {
            $error = 'Địa điểm không được để trống và tối đa 200 ký tự.';
        } elseif ($chair === '' || mb_strlen($chair) > 100 || $secretary === '' || mb_strlen($secretary) > 100) {
            $error = 'Chủ trì và thư ký không được để trống (tối đa 100 ký tự).';
        } elseif (!ctype_digit($present) || !ctype_digit($total) || (int) $present > (int) $total) {
            $error = 'Số sinh viên có mặt và tổng số phải là số nguyên, có mặt không được lớn hơn tổng số.';
        } elseif ($content === '' || mb_strlen($content) > 5000 || mb_strlen($conclusion) > 2000) {
            $error = 'Nội dung cuộc họp không được để trống (tối đa 5000 ký tự); kết luận tối đa 2000 ký tự.';
        }
        if ($error) {
            $_SESSION['old_minute'] = $_POST;
            flash('danger', $error);
            $this->redirect($back);
        }

        ClassMinute::save($class['id'], $periodId, [
            'meeting_at' => $meetingAt, 'venue' => $venue, 'chairperson' => $chair, 'secretary' => $secretary,
            'present_count' => (int) $present, 'total_count' => (int) $total,
            'content' => $content, 'conclusion' => $conclusion !== '' ? $conclusion : null,
        ], $user['id']);
        flash('success', 'Đã lưu biên bản họp lớp.');
        $this->redirect($back);
    }

    // Bản xem trước / in ra giấy (hoặc lưu PDF bằng chức năng In của trình duyệt)
    public function preview(int $periodId)
    {
        $this->renderRaw('class/minutes-document', $this->documentData($periodId, false));
    }

    // Tải file Word (.doc): Word mở được file HTML có đuôi .doc
    public function word(int $periodId)
    {
        $data = $this->documentData($periodId, true);
        header('Content-Type: application/msword; charset=utf-8');
        header('Content-Disposition: attachment; filename="bien-ban-hop-lop-' . $data['class']['code'] . '.doc"');
        $this->renderRaw('class/minutes-document', $data);
    }

    // ---------------------------------------------------------------- Hỗ trợ

    private function documentData($periodId, $forWord)
    {
        [$user, $student, $class] = $this->mine();
        $period = $this->periodOr404($periodId);
        $minute = ClassMinute::find($class['id'], $periodId);
        if (!$minute) {
            flash('warning', 'Hãy điền và lưu biên bản trước khi xuất.');
            $this->redirect('minute/show/' . $periodId);
        }
        return ['class' => $class, 'period' => $period, 'minute' => $minute, 'forWord' => $forWord,
                'list' => Review::students($class['id'], $periodId)];
    }

    private function mine()
    {
        $user = $this->requireRole([ROLE_MONITOR]);
        $student = Student::findByUserId($user['id']);
        $class = $student ? Classroom::find($student['class_id']) : null;
        if (!$class) {
            $this->abort(403, 'Tài khoản của bạn chưa gắn với hồ sơ sinh viên và lớp. Vui lòng liên hệ quản trị viên.');
        }
        return [$user, $student, $class];
    }

    private function periodOr404($periodId)
    {
        $period = Period::find($periodId);
        if (!$period || now_str() < $period['start_at']) {
            $this->abort(404, 'Không tìm thấy đợt đánh giá.');
        }
        return $period;
    }
}
