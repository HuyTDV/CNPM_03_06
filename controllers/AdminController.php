<?php
// Khu vực quản trị viên.
class AdminController extends Controller
{
    public function dashboard()
    {
        $this->requireRole([ROLE_ADMIN]);
        $this->render('admin/dashboard', ['pageTitle' => 'Khu vực quản trị']);
    }
}
