<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Myorder extends MY_Controller {

    private $id;
    
    public function __construct()
    {
        parent::__construct();
        $is_login = $this->session->userdata('is_login');
        $this->id = $this->session->userdata('id');

        if (!$is_login) {
            redirect(base_url());
            return;
        }
    }

    public function index()
    {
        $data['title']   = 'Daftar Order';
        $data['content'] = $this->myorder->where('id_user', $this->id)->orderBy('date', 'DESC')->get();
        $data['page']    = 'pages/myorder/index';

        $this->view($data);
    }

    public function detail($invoice) {
        $data['order'] = $this->myorder->where('invoice', $invoice)->first();
        if (!$data['order']) {
            $this->session->set_flashdata('warning', 'Data tidak ditemukan.');
            redirect(base_url('/myorder'));
        }

        $this->myorder->table = 'orders_detail';
        $data['order_detail'] = $this->myorder->select([
            'orders_detail.id_orders', 'orders_detail.id_product', 'orders_detail.qty', 'orders_detail.subtotal', 'product.title', 'product.image', 'product.price'
            ])
            ->join('product')
            ->where('orders_detail.id_orders', $data['order']->id)
            ->get();
        $data['page']         = 'pages/myorder/detail';
        $this->view($data);

    }

    public function confirm($invoice) {
        $data['order'] = $this->myorder->where('invoice', $invoice)->first();
        if (!$data['order']) {
            $this->session->set_flashdata('warning', 'Data tidak ditemukan.');
            redirect(base_url('/myorder'));
        }

        if ($data['order']->status !== 'waiting') {
            $this->session->set_flashdata('warning', 'Bukti transfer sudah dikirm.');
            redirect(base_url("myorder/detail/$invoice"));
        }

        if (!$_POST) {
            $data['input'] = (object) $this->myorder->getDefaultValues();
        } else {
            $data['input'] = (object) $this->input->post(null, true);
        }

        if (!empty($_FILES) && $_FILES['image']['name'] !== '') {
            $imageName = url_title($invoice, '-', true) . '-' . date('YmdHis');
            $upload    = $this->myorder->uploadImage('image', $imageName);
            if ($upload) {
                $data['image'] = $upload['file_name'];
            } else {
                redirect(base_url("myorder/confirm/$invoice"));
            }
        }

        if (!$this->product->validate()) {
            $data['title'] = 'Konfirmasi Order';
            $data['form_action'] = base_url("myorder/confirm/$invoice");
            $data['page'] = 'pages/myorder/confirm';

            $this->view($data);
            return;
        }

        $this->myorder->table = 'orders_detail';

        if ($this->myorder->create($data['input'])) {
            $this->myorder->table = 'orders';
            $this->myorder->where('id', $data['input']->id_orders)->update(['status' => 'paid']);
            $this->session->set_flashdata('success', 'Data berhasil disimpan!');
        } else {
            $this->session->set_flashdata('error', 'Oops! Terjadi suatu kesalahan.');
        }

        redirect(base_url("myorder/detail/$invoice"));

    }

}

/* End of file Myorder.php */
