<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation'); //ini ambil data dari library CI untuk validasi
    }

    public function index() //index itu tampilan awal ketika web dibuka
    {
        //ini supaya setelah login tidak bisa balik kehalaman auth/login
        if ($this->session->userdata('email')) {
            redirect('user');
        }

        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');

        $this->form_validation->set_rules('password', 'Password', 'trim|required');

        if ($this->form_validation->run() == false) {
            $data['title'] = 'Login Vendor Management';
            $this->load->view('templates/auth_header', $data); //ini link untuk ke view header page
            $this->load->view('auth/login');
            $this->load->view('templates/auth_footer');
        } else {
            // validasinya success
            $this->_login(); //private methodnya
        }
    }

    private function _login()
    {
        $email = $this->input->post('email'); //email ambil dari name form email di halaman login
        $password = $this->input->post('password');
        $user = $this->db->get_where('user', ['email' => $email])->row_array(); //ini untuk menyocokan email password dengan table di database yang ada
        // jika usernya ada
        if ($user) {
            // jika usernya aktif
            if ($user['is_active'] == 1) {
                // cek password
                // var_dump($user['password']);
                // var_dump($password);
                // var_dump(password_verify($password, $user['password']));

                if (password_verify($password, $user['password'])) {
                    $data = [
                        'email' => $user['email'],
                        'role_id' => $user['role_id']
                    ];

                    $this->session->set_userdata($data);
                    //user access
                    if ($user['role_id'] == 1) {
                        redirect('admin');
                    } else {
                        redirect('user');
                    }
                    // var_dump($data);
                    // die;
                } else {
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Wrong password!</div>');
                    redirect('auth');
                }
            } else {
                if (password_verify($password, $user['password'])) {
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">This email has been not activated!</div>');
                    redirect('auth');
                } else {
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Wrong password!</div>');
                    redirect('auth');
                }
            }
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Email is not registered!</div>');
            redirect('auth');
        }
    }
    public function registration()
    {
        if ($this->session->userdata('email')) {
            redirect('user');
        }

        $this->form_validation->set_rules('name', 'Name', 'required|trim'); //ini untuk validasi pengisian form tidak boleh kosong, trim = menghapus spasi kedatabase
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|is_unique[user.email]', [
            'is_unique' => 'This email has already registered!'
        ]); //isunique untuk validasi email sudah ada didatabase
        $this->form_validation->set_rules('password1', 'Password', 'required|trim|min_length[3]|matches[password2]', [
            'matches' => 'password dont match!',
            'min_length' => 'Password too short!'
        ]);
        $this->form_validation->set_rules('password2', 'Password', 'required|trim|min_length[3]|matches[password1]');

        if ($this->form_validation->run() == false) {
            $data['title'] = 'Register Akun Vendor Management';
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/registration');
            $this->load->view('templates/auth_footer');
        } else {
            $email = $this->input->post('email', true);
            $data = [
                'name' => htmlspecialchars($this->input->post('name', true)),
                'email' => htmlspecialchars($email),
                'image' => 'default.jpg',
                'password' => password_hash($this->input->post('password1'), PASSWORD_DEFAULT),
                'role_id' => 2,
                'is_active' => 0,
                'date_created' => time()
            ];


            // siapkan token, fungsi php
            $token = base64_encode(random_bytes(32));
            $user_token = [
                'email' => $email,
                'token' => $token,
                'date_created' => time()
            ];

            $this->db->insert('user', $data); //data disimpen ditable user
            $this->db->insert('user_token', $user_token); //data disimpen ditable user

            //mengaktifkan fitur kirim email
            $this->_sendEmail($token, 'verify');

            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Congratulation! your account has been created. Please activate yout account</div>');
            redirect('auth'); //memindahkan halaman ke login
        }
    }

    private function _sendEmail($token, $type)
    {
        // konfigurasi si pengirim email sebagai parameter
        $config = [
            'protocol' => 'smtp',
            'smtp_host' => 'ssl://smtp.googlemail.com',
            'smtp_user' => 'haidaraldi19@gmail.com',
            'smtp_pass' => 'duapuluhdua',
            'smtp_port' => 465,
            'mailtype' => 'html',
            'charset' => 'utf-8',
            'newline' =>  "\r\n"
        ];

        $this->email->initialize($config);

        // email dikirim dari siapa
        $this->email->from('haidaraldi19@gmail.com', 'Haidar Aldi Wintoro');
        $this->email->to($this->input->post('email'));

        if ($type == 'verify') {
            $this->email->subject('Account Verification');
            $this->email->message('Click this link to verify your account: <a href="' . base_url() . 'auth/verify?email=' . $this->input->post('email') . '&token=' . urlencode($token) . '">Activate</a>');
        } else if ($type == 'forgot') {
            $this->email->subject('Reset Password');
            $this->email->message('Click this link to reset your password: <a href="' . base_url() . 'auth/resetpassword?email=' . $this->input->post('email') . '&token=' . urlencode($token) . '">Reset Password</a>');
        }
        if ($this->email->send()) {
            return true;
        } else {
            echo $this->email->print_debugger();
            die;
        }
    }

    //setelah verifikasi diemail ubah jadi 1 is activenya
    public function verify()
    {
        // ambil email dan token user di url
        $email = $this->input->get('email');
        $token = $this->input->get('token');

        // cocokan email didatabase
        $user = $this->db->get_where('user', ['email' => $email])->row_array();
        if ($user) {
            // cek token didatabase
            $user_token = $this->db->get_where('user_token', ['token' => $token])->row_array();

            if ($user_token) {
                // token dibuat 24 jam untuk aktivasinya
                if (time() - $user_token['date_created'] < (60 * 60 * 24)) {
                    $this->db->set('is_active', 1);
                    $this->db->where('email', $email);
                    $this->db->update('user');

                    //apus tokennya
                    $this->db->delete('user_token', ['email' => $email]);
                    // pesan sukses
                    $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">' . $email . ' has been activated! Please login.</div>');
                    redirect('auth');
                } else {
                    //hapus email user dan token
                    $this->db->delete('user', ['email' => $email]);
                    $this->db->delete('user_token', ['email' => $email]);
                    $this->session->set_flashdata('message', '<div class=" alert alert-danger" role= " alert">Account activation failed! Token Expired.</div>');
                    redirect('auth');
                }
            } else {
                $this->session->set_flashdata('message', '<div class=" alert alert-danger" role= " alert">Account activation failed! Wrong token.</div>');
                redirect('auth');
            }
        } else {
            //kalo email diurl diganti
            $this->session->set_flashdata('message', '<div class=" alert alert-danger" role= " alert">Account activation failed! Wrong email.</div>');
            redirect('auth');
        }
    }

    public function logout()
    {
        $this->session->unset_userdata('email');
        $this->session->unset_userdata('role_id');

        $this->session->set_flashdata('message', '<div class=" alert alert-success" role= " alert">You have been logged out!</div>');
        redirect('auth');
    }

    public function blocked()
    {
        $this->load->view('auth/blocked');
    }
    public function forgotPassword()
    {
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
        if ($this->form_validation->run() == false) {
            $data['title'] = 'Forgot Password';
            $this->load->view('templates/auth_header', $data); //ini link untuk ke view header page
            $this->load->view('auth/forgot-password');
            $this->load->view('templates/auth_footer');
        } else {
            $email = $this->input->post('email'); //ambil data dari input email
            //kalo email ada cek ke database
            $user = $this->db->get_where('user', ['email' => $email, 'is_active' => 1])->row_array();

            if ($user) {
                //membuat random token
                $token = base64_encode(random_bytes(32));
                //simpan data ke variabel untuk dimasukan kedatabase
                $user_token = [
                    'email' => $email,
                    'token' => $token,
                    'date_created' => time()
                ];
                $this->db->insert('user_token', $user_token);
                $this->_sendEmail($token, 'forgot'); //kirim token ke email, typenya forgot
                $this->session->set_flashdata('message', '<div class=" alert alert-success" role= " alert">Please check your email to reset your password!</div>');
                redirect('auth/forgotpassword');
            } else {
                $this->session->set_flashdata('message', '<div class=" alert alert-danger" role= " alert">Email is not registered or activated!</div>');
                redirect('auth/forgotpassword');
            }
        }
    }

    public function resetPassword()
    {
        $email = $this->input->get('email');
        $token = $this->input->get('token');

        $user = $this->db->get_where('user', ['email' => $email])->row_array();

        if ($user) {
            //kalo ada email ambil tokennya
            $user_token = $this->db->get_where('user_token', ['token' => $token])->row_array();
            if ($user_token) {
                $this->session->set_userdata('reset_email', $email); //session hanya ada ketika reset saja

                $this->changePassword();
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role= "alert">Reset password failed! Wrong token.</div>');
                redirect('auth');
            }
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Reset password failed! Wrong email.</div>');
            redirect('auth');
        }
    }

    public function changePassword()
    {

        if (!$this->session->userdata('reset_email')) {
            redirect('auth');
        }
        $this->form_validation->set_rules('password1', 'Password', 'trim|required|min_length[3]|matches[password2]');
        $this->form_validation->set_rules('password2', 'Password', 'trim|required|min_length[3]|matches[password1]');

        if ($this->form_validation->run() == false) {
            $data['title'] = 'Change Password';
            $this->load->view('templates/auth_header', $data); //ini link untuk ke view header page
            $this->load->view('auth/change-password');
            $this->load->view('templates/auth_footer');
        } else {
            $password = password_hash($this->input->post('password1'), PASSWORD_DEFAULT);
            $email = $this->session->userdata('reset_email');

            $this->db->set('password', $password);
            $this->db->where('email', $email);
            $this->db->update('user');

            $this->session->unset_userdata('reset_email');

            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Password has been changed! Please login.</div>');
            redirect('auth');
        }
    }
}
