<?php

function is_logged_in()
{
    $ci = get_instance(); //untuk memanggil library CI penganti $this

    if (!$ci->session->userdata('email')) {
        redirect('auth');
    } else {
        // cek role nya apa
        $role_id = $ci->session->userdata('role_id');
        $menu = $ci->uri->segment(1); //segmen diambil dari urutan url nya

        $queryMenu = $ci->db->get_where('user_menu', ['menu' => $menu])->row_array();
        $menu_id = $queryMenu['id']; //ini nyocokin untuk mendapatkan id menu 

        $userAccess = $ci->db->get_where('user_access_menu', [
            'role_id' => $role_id,
            'menu_id' => $menu_id
        ]);

        if ($userAccess->num_rows() < 1) {
            redirect('auth/blocked');
        }
    }
}

function check_access($role_id, $menu_id)
{
    $ci = get_instance();
    // $ci->db->where('role_id', $role_id);
    // $ci->db->where('menu_id', $menu_id);
    // $ci->db->get('user_access_menu'); //mencari semua data dari user access menu yang role id nya berapa, menu id nya berapa

    // cara query lain
    $result = $ci->db->get_where('user_access_menu', [
        'role_id' => $role_id,
        'menu_id' => $menu_id
    ]);

    // kalo result ada isinya/lebih dari 0
    if ($result->num_rows() > 0) {
        return "checked='checked'";
    }
}
