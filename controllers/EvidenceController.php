<?php
// File minh chứng nằm ngoài public/, chỉ xem được qua controller này sau khi kiểm tra quyền:
//  - Chủ bài (sinh viên đã tải lên); cán bộ lớp CÙNG lớp; GVCN của lớp đó; quản trị viên.
// (Việc tải file lên được xử lý chung với điểm trong AssessmentController::save.)
class EvidenceController extends Controller
{
    // Xem trực tiếp (PDF/ảnh). Thêm ?download=1 để tải về.
    public function view(int $id)
    {
        $user = $this->requireLogin();
        $file = Assessment::evidence($id);
        $ctx = $file ? Assessment::context($file['evaluation_id']) : null;
        if (!$ctx) {
            $this->abort(404, 'Không tìm thấy minh chứng.');
        }
        if (!$this->canAccess($user, $ctx)) {
            $this->abort(403, 'Bạn không có quyền xem minh chứng này.');
        }
        $path = evidence_path($file['stored_name']);
        if (!is_file($path)) {
            $this->abort(404, 'File minh chứng không còn tồn tại trên máy chủ.');
        }

        session_write_close();                                 // không giữ khóa session trong lúc gửi file
        $mode = isset($_GET['download']) ? 'attachment' : 'inline';
        header('Content-Type: ' . $file['mime_type']);
        header('Content-Length: ' . filesize($path));
        header("Content-Disposition: $mode; filename=\"minh-chung." . $file['file_ext'] . "\"; filename*=UTF-8''" . rawurlencode($file['original_name']));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($path);
        exit;
    }

    // POST: chủ bài xóa minh chứng (chỉ khi bài còn sửa được)
    public function delete(int $id)
    {
        $user = $this->requireRole([ROLE_STUDENT, ROLE_MONITOR]);
        $this->requirePost();
        $file = Assessment::evidence($id);
        $ctx = $file ? Assessment::context($file['evaluation_id']) : null;
        if (!$ctx) {
            $this->abort(404, 'Không tìm thấy minh chứng.');
        }
        if ((int) $ctx['user_id'] !== (int) $user['id']) {
            $this->abort(403, 'Bạn chỉ được xóa minh chứng của chính mình.');
        }
        $period = Period::find($ctx['period_id']);
        $eval = Assessment::find($file['evaluation_id']);
        $formUrl = 'assessment/form/' . $ctx['period_id'];

        if (!Assessment::canEdit($eval, $period)) {
            flash('danger', 'Bài đánh giá hiện không thể chỉnh sửa nên không xóa được minh chứng.');
            $this->redirect($formUrl);
        }
        Assessment::deleteEvidence($id);
        delete_upload($file['stored_name']);
        flash('success', 'Đã xóa minh chứng.');
        $this->redirect($formUrl);
    }

    private function canAccess($user, $ctx)
    {
        switch ($user['role']) {
            case ROLE_ADMIN:
                return true;
            case ROLE_STUDENT:
                return (int) $ctx['user_id'] === (int) $user['id'];
            case ROLE_MONITOR:   // cán bộ lớp: bài của chính mình hoặc của bạn trong cùng lớp
                $mine = Student::findByUserId($user['id']);
                return (int) $ctx['user_id'] === (int) $user['id'] || ($mine && (int) $mine['class_id'] === (int) $ctx['class_id']);
            case ROLE_ADVISOR:
                return (int) $ctx['advisor_id'] === (int) $user['id'];
        }
        return false;
    }
}
