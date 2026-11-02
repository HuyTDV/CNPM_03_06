<?php
// US-07: quản lý khung điểm, điều (nhóm tiêu chí), khoản (tiêu chí chi tiết). Chỉ quản trị viên.
// Mọi thao tác ghi dùng form POST có CSRF, xong thì quay về trang khung đang xem.
//
// Quy tắc (kiểm tra bằng PHP):
//  - Tổng điểm các khoản <= điểm của điều; tổng điểm các điều <= tổng điểm của khung.
//  - Chỉ BẬT được khung khi các tổng trên khớp đúng (để sinh viên có thể đạt điểm tối đa).
//  - Bật khung nào thì tắt các khung khác của cùng học kỳ.
//  - Khung đã có sinh viên đánh giá thì KHÔNG được sửa/xóa (tránh làm sai điểm đã chấm).
class CriteriaController extends Controller
{
    public function index()
    {
        $this->requireRole([ROLE_ADMIN]);
        $frameworks = Criteria::frameworks();

        // Chọn khung để xem: theo ?framework=, không có thì khung đang bật của học kỳ hiện hành, rồi đến khung đầu tiên
        $selected = null;
        $wanted = (int) ($_GET['framework'] ?? 0);
        foreach ($frameworks as $f) {
            if ((int) $f['id'] === $wanted) {
                $selected = $f;
            }
        }
        if ($selected === null && $frameworks) {
            $current = Semester::current();
            foreach ($frameworks as $f) {
                if ($current && $f['semester_id'] == $current['id'] && $f['is_active']) {
                    $selected = $f;
                    break;
                }
            }
            $selected = $selected ?? $frameworks[0];
        }

        $tree = [];
        $locked = false;
        $problems = [];
        $categorySum = 0;
        if ($selected) {
            $tree = Criteria::tree($selected['id']);
            $locked = Criteria::hasEvaluations($selected['id']);
            $problems = framework_problems($selected, $tree);
            $categorySum = score_sum(array_column($tree, 'max_score'));
        }

        $this->render('admin/criteria', [
            'pageTitle'   => 'Khung điểm & tiêu chí',
            'frameworks'  => $frameworks,
            'selected'    => $selected,
            'tree'        => $tree,
            'locked'      => $locked,
            'problems'    => $problems,
            'categorySum' => $categorySum,
            'semesters'   => Semester::listAll(),
            'scripts'     => ['js/criteria.js'],
        ]);
    }

    // ---------------------------------------------------------------- Khung điểm

    public function saveFramework()
    {
        $user = $this->requireRole([ROLE_ADMIN]);
        $this->requirePost();

        $id = (int) $this->post('id');
        $old = $id ? Criteria::framework($id) : null;
        if ($id && !$old) {
            $this->abort(404, 'Không tìm thấy khung điểm.');
        }
        if ($old && Criteria::hasEvaluations($id)) {
            $this->fail('Khung điểm đã có sinh viên đánh giá nên không thể sửa.', $id);
        }

        $name = $this->post('name');
        $description = $this->post('description') ?: null;
        $semesterId = (int) $this->post('semester_id');
        $total = to_float($this->post('total_max_score'));

        if ($name === '' || mb_strlen($name) > 150) {
            $this->fail('Tên khung điểm không được để trống và tối đa 150 ký tự.', $id);
        }
        if (mb_strlen((string) $description) > 500) {
            $this->fail('Mô tả tối đa 500 ký tự.', $id);
        }
        if (!Semester::find($semesterId)) {
            $this->fail('Vui lòng chọn học kỳ.', $id);
        }
        if ($total === null || $total < 1 || $total > 1000) {
            $this->fail('Tổng điểm tối đa phải là một số từ 1 đến 1000.', $id);
        }

        if (!$old) {
            $newId = Criteria::createFramework($semesterId, $name, $description, $total, $user['id']);
            flash('success', 'Đã tạo khung điểm. Hãy thêm các điều và khoản, rồi bật khung để dùng.');
            $this->back($newId);
        }

        if ((int) $old['semester_id'] !== $semesterId) {
            if (Criteria::usedByPeriods($id)) {
                $this->fail('Khung đang được dùng bởi đợt đánh giá nên không đổi được học kỳ.', $id);
            }
            if ($old['is_active']) {
                Criteria::deactivate($id);   // tránh 2 khung cùng bật trong một học kỳ
                flash('warning', 'Khung điểm đã được tắt vì đổi sang học kỳ khác. Hãy bật lại nếu cần.');
            }
        }
        Criteria::updateFramework($id, $semesterId, $name, $description, $total);
        flash('success', 'Đã cập nhật khung điểm.');
        $this->back($id);
    }

    // Bật hoặc tắt khung
    public function toggleFramework()
    {
        $this->requireRole([ROLE_ADMIN]);
        $this->requirePost();
        $id = (int) $this->post('id');
        $fw = Criteria::framework($id);
        if (!$fw) {
            $this->abort(404, 'Không tìm thấy khung điểm.');
        }

        if ($fw['is_active']) {
            Criteria::deactivate($id);
            flash('success', 'Đã tắt khung điểm.');
            $this->back($id);
        }
        $problems = framework_problems($fw, Criteria::tree($id));
        if ($problems) {
            $this->fail("Chưa bật được khung điểm vì chưa hợp lệ:\n" . implode("\n", $problems), $id);
        }
        Criteria::activate($id, $fw['semester_id']);
        flash('success', 'Đã bật khung điểm cho ' . $fw['semester_name'] . ' (các khung khác của học kỳ này đã được tắt).');
        $this->back($id);
    }

    public function copyFramework()
    {
        $user = $this->requireRole([ROLE_ADMIN]);
        $this->requirePost();
        $srcId = (int) $this->post('id');
        if (!Criteria::framework($srcId)) {
            $this->abort(404, 'Không tìm thấy khung điểm.');
        }
        $name = $this->post('name');
        if ($name === '' || mb_strlen($name) > 150) {
            $this->fail('Tên khung mới không được để trống và tối đa 150 ký tự.', $srcId);
        }
        $semesterId = (int) $this->post('semester_id');
        if (!Semester::find($semesterId)) {
            $this->fail('Vui lòng chọn học kỳ.', $srcId);
        }
        $newId = Criteria::copyFramework($srcId, $semesterId, $name, $user['id']);
        flash('success', 'Đã sao chép khung điểm. Khung mới đang tắt; kiểm tra rồi bật khi sẵn sàng.');
        $this->back($newId);
    }

    public function deleteFramework()
    {
        $this->requireRole([ROLE_ADMIN]);
        $this->requirePost();
        $id = (int) $this->post('id');
        if (!Criteria::framework($id)) {
            $this->abort(404, 'Không tìm thấy khung điểm.');
        }
        if (Criteria::usedByPeriods($id)) {
            $this->fail('Khung điểm đang được dùng bởi đợt đánh giá nên không thể xóa.', $id);
        }
        Criteria::deleteFramework($id);
        flash('success', 'Đã xóa khung điểm.');
        $this->redirect('criteria');
    }

    // ---------------------------------------------------------------- Điều

    public function saveCategory()
    {
        $this->requireRole([ROLE_ADMIN]);
        $this->requirePost();

        $fid = (int) $this->post('framework_id');
        $fw = Criteria::framework($fid);
        if (!$fw) {
            $this->abort(404, 'Không tìm thấy khung điểm.');
        }
        $id = (int) $this->post('id');
        $cat = $id ? Criteria::category($id) : null;
        if ($id && (!$cat || (int) $cat['framework_id'] !== $fid)) {
            $this->abort(404, 'Không tìm thấy điều.');
        }
        $this->denyIfLocked($fid);

        $code = $this->post('code');
        $title = $this->post('title');
        $description = $this->post('description') ?: null;
        $max = to_float($this->post('max_score'));
        if ($code === '' || mb_strlen($code) > 10) {
            $this->fail('Mã điều không được để trống và tối đa 10 ký tự.', $fid);
        }
        if ($title === '' || mb_strlen($title) > 255) {
            $this->fail('Tiêu đề điều không được để trống và tối đa 255 ký tự.', $fid);
        }
        if ($max === null || $max < 0 || $max > $fw['total_max_score']) {
            $this->fail('Điểm tối đa của điều phải là một số từ 0 đến ' . fmt_score($fw['total_max_score']) . '.', $fid);
        }

        $others = 0.0;
        foreach (Criteria::categories($fid) as $c) {
            if ((int) $c['id'] !== $id) {
                $others += (float) $c['max_score'];
            }
        }
        if (round($others + $max, 2) > (float) $fw['total_max_score']) {
            $this->fail('Tổng điểm các điều (' . fmt_score($others + $max) . ') vượt tổng điểm của khung (' . fmt_score($fw['total_max_score']) . ').', $fid);
        }
        if ($cat) {
            $itemsSum = score_sum(array_column(Criteria::items($id), 'max_score'));
            if ($max + 0.0001 < $itemsSum) {
                $this->fail('Điểm tối đa của điều nhỏ hơn tổng điểm các khoản hiện có (' . fmt_score($itemsSum) . ').', $fid);
            }
        }

        try {
            if ($cat) {
                Criteria::updateCategory($id, $code, $title, $description, $max);
            } else {
                Criteria::createCategory($fid, $code, $title, $description, $max, count(Criteria::categories($fid)) + 1);
            }
        } catch (PDOException $e) {
            if (Model::errorCode($e) === 1062) {
                $this->fail('Mã điều "' . $code . '" đã tồn tại trong khung này.', $fid);
            }
            throw $e;
        }
        flash('success', $cat ? 'Đã cập nhật điều.' : 'Đã thêm điều.');
        $this->back($fid);
    }

    public function deleteCategory()
    {
        $this->requireRole([ROLE_ADMIN]);
        $this->requirePost();
        $cat = Criteria::category((int) $this->post('id'));
        if (!$cat) {
            $this->abort(404, 'Không tìm thấy điều.');
        }
        $fid = (int) $cat['framework_id'];
        $this->denyIfLocked($fid);
        Criteria::deleteCategory($cat['id']);
        flash('success', 'Đã xóa điều (và các khoản bên trong).');
        $this->back($fid);
    }

    // ---------------------------------------------------------------- Khoản

    public function saveItem()
    {
        $this->requireRole([ROLE_ADMIN]);
        $this->requirePost();

        $cat = Criteria::category((int) $this->post('category_id'));
        if (!$cat) {
            $this->abort(404, 'Không tìm thấy điều.');
        }
        $fid = (int) $cat['framework_id'];
        $id = (int) $this->post('id');
        $item = $id ? Criteria::item($id) : null;
        if ($id && (!$item || (int) $item['category_id'] !== (int) $cat['id'])) {
            $this->abort(404, 'Không tìm thấy khoản.');
        }
        $this->denyIfLocked($fid);

        $code = $this->post('code');
        $content = $this->post('content');
        $max = to_float($this->post('max_score'));
        $min = $this->post('min_score') === '' ? 0.0 : to_float($this->post('min_score'));
        if ($code === '' || mb_strlen($code) > 15) {
            $this->fail('Mã khoản không được để trống và tối đa 15 ký tự.', $fid);
        }
        if ($content === '' || mb_strlen($content) > 500) {
            $this->fail('Nội dung tiêu chí không được để trống và tối đa 500 ký tự.', $fid);
        }
        if ($max === null || $max < 0 || $max > 1000) {
            $this->fail('Điểm tối đa phải là một số từ 0 đến 1000.', $fid);
        }
        if ($min === null || $min < -1000) {
            $this->fail('Điểm tối thiểu phải là một số (để âm nếu là mục trừ điểm).', $fid);
        }
        if ($min > $max) {
            $this->fail('Điểm tối thiểu không được lớn hơn điểm tối đa.', $fid);
        }

        $others = 0.0;
        foreach (Criteria::items($cat['id']) as $i) {
            if ((int) $i['id'] !== $id) {
                $others += (float) $i['max_score'];
            }
        }
        if (round($others + $max, 2) > (float) $cat['max_score']) {
            $this->fail('Tổng điểm các khoản (' . fmt_score($others + $max) . ') vượt điểm tối đa của điều ' . $cat['code'] . ' (' . fmt_score($cat['max_score']) . ').', $fid);
        }

        $requireEvidence = isset($_POST['require_evidence']) ? 1 : 0;
        try {
            if ($item) {
                Criteria::updateItem($id, $code, $content, $max, $min, $requireEvidence);
            } else {
                Criteria::createItem($cat['id'], $code, $content, $max, $min, $requireEvidence, count(Criteria::items($cat['id'])) + 1);
            }
        } catch (PDOException $e) {
            if (Model::errorCode($e) === 1062) {
                $this->fail('Mã khoản "' . $code . '" đã tồn tại trong điều này.', $fid);
            }
            throw $e;
        }
        flash('success', $item ? 'Đã cập nhật khoản.' : 'Đã thêm khoản.');
        $this->back($fid);
    }

    public function deleteItem()
    {
        $this->requireRole([ROLE_ADMIN]);
        $this->requirePost();
        $item = Criteria::item((int) $this->post('id'));
        if (!$item) {
            $this->abort(404, 'Không tìm thấy khoản.');
        }
        $fid = (int) $item['framework_id'];
        $this->denyIfLocked($fid);
        Criteria::deleteItem($item['id']);
        flash('success', 'Đã xóa khoản.');
        $this->back($fid);
    }

    // ---------------------------------------------------------------- Hỗ trợ

    // Khung đã có bài đánh giá thì không cho sửa cấu trúc/điểm
    private function denyIfLocked($frameworkId)
    {
        if (Criteria::hasEvaluations($frameworkId)) {
            $this->fail('Khung đã có bài đánh giá nên không thể thêm, sửa hoặc xóa điều và khoản.', $frameworkId);
        }
    }

    // Quay về trang khung đang xem
    private function back($frameworkId)
    {
        $this->redirect($frameworkId ? 'criteria?framework=' . $frameworkId : 'criteria');
    }

    // Báo lỗi rồi quay về
    private function fail($message, $frameworkId)
    {
        flash('danger', $message);
        $this->back($frameworkId);
    }
}
