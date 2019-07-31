<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User extends CI_Controller
{

    public $idvendor;


    public function __construct()
    {

        parent::__construct();
        is_logged_in(); //fungsi helper


    }
    public function __toString()
    {
        return (string) $this->idvendor;
    }

    public function getId()
    {
        echo $this->post('idvendor');
    }
    public function index()
    {
        $data['title'] = 'Laporan Harian';
        $data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array(); //ambil data disession auth

        $this->form_validation->set_rules('kegiatan', 'Kegiatan', 'required|trim');
        $this->form_validation->set_rules('idvendor', 'ID Vendor', 'required|trim');
        // |min_length[6]
        $this->form_validation->set_rules('namavendor', 'Nama Vendor', 'required|trim');
        if ($this->form_validation->run() == false) {
            $this->load->view('templates/header', $data);
            $this->load->view('templates/sidebar', $data);
            $this->load->view('templates/topbar', $data);
            $this->load->view('user/index', $data);
            $this->load->view('templates/footer');
        } else {
            // // $query = "SELECT id FROM `vendor` WHERE id_vendor = '$idvendor '";
            // // $query = $this->db->get('vendor');
            // $idvendor = $this->input->post('idvendor', true);
            // $query = $this->db->query("SELECT id FROM vendor WHERE id_vendor= '$idvendor' ");


            // $this->db->select('id')->from('vendor')->where('id_vendor', $idvendor);
            // $query = $this->db->get();
            $data = [
                'kegiatan' => htmlspecialchars($this->input->post('kegiatan', true)),
                'idvendor' => htmlspecialchars($this->input->post('idvendor', true)),
                'nama_vendor' => htmlspecialchars($this->input->post('namavendor', true)),
                'tanggal' => time()
            ];


            $this->db->insert('laporanharian', $data); //data disimpen ditable user

            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Data Laporan Berhasil disimpan!</div>');
            redirect('user/index');
        }
    }
}
