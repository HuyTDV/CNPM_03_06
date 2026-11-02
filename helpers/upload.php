<?php
// Nhận file minh chứng. File được lưu NGOÀI public/ (storage/uploads/evidences),
// chỉ xem được qua EvidenceController sau khi kiểm tra quyền.

function evidence_path($storedName)
{
    return BASE_PATH . '/storage/uploads/evidences/' . basename($storedName);
}

// Gom file từ các ô <input type="file" name="evidence[ID_KHOẢN][]" multiple>
// Trả về: [id_khoản => [file, file, ...]] (bỏ qua ô không chọn file)
function collect_uploads()
{
    $out = [];
    $f = $_FILES['evidence'] ?? null;
    if (!is_array($f) || !isset($f['name']) || !is_array($f['name'])) {
        return $out;
    }
    foreach ($f['name'] as $itemId => $names) {
        if (!is_array($names)) {
            continue;
        }
        foreach ($names as $i => $name) {
            $error = (int) ($f['error'][$itemId][$i] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[(int) $itemId][] = [
                'name'     => (string) $name,
                'tmp_name' => (string) ($f['tmp_name'][$itemId][$i] ?? ''),
                'error'    => $error,
                'size'     => (int) ($f['size'][$itemId][$i] ?? 0),
            ];
        }
    }
    return $out;
}

// Kiểm tra một file. Hợp lệ -> null; không hợp lệ -> chuỗi thông báo lỗi.
function check_upload($file)
{
    $name = $file['name'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return "Không tải được file \"$name\" (có thể file quá lớn).";
    }
    if ($file['size'] <= 0 || $file['size'] > MAX_UPLOAD_BYTES) {
        return "File \"$name\" phải có dung lượng từ 1 byte đến " . (MAX_UPLOAD_BYTES / 1048576) . ' MB.';
    }
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!isset(UPLOAD_TYPES[$ext])) {
        return "File \"$name\" không đúng định dạng (chỉ nhận PDF, JPG, PNG).";
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return "File \"$name\" không hợp lệ.";
    }
    // Kiểm tra NỘI DUNG THẬT của file, không tin đuôi file hay loại do trình duyệt báo
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if ($mime !== UPLOAD_TYPES[$ext]) {
        return "Nội dung file \"$name\" không khớp với định dạng .$ext.";
    }
    if ($ext !== 'pdf' && @getimagesize($file['tmp_name']) === false) {
        return "File \"$name\" không phải ảnh hợp lệ.";
    }
    return null;
}

// Lưu file đã qua check_upload() với tên ngẫu nhiên. Trả về thông tin để ghi vào bảng evidences.
function save_upload($file)
{
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $stored = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!is_dir(dirname(evidence_path($stored)))) {
        mkdir(dirname(evidence_path($stored)), 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], evidence_path($stored))) {
        throw new Exception('Không lưu được file minh chứng.');
    }
    $original = preg_replace('/[\x00-\x1F\x7F\/\\\\]+/u', '', basename($file['name']));
    return [
        'original_name' => mb_substr($original, 0, 200),
        'stored_name'   => $stored,
        'file_ext'      => $ext,
        'mime_type'     => UPLOAD_TYPES[$ext],
        'file_size'     => $file['size'],
    ];
}

function delete_upload($storedName)
{
    $path = evidence_path($storedName);
    if (is_file($path)) {
        @unlink($path);
    }
}
