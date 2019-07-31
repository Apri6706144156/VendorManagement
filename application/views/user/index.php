                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800"><?= $title;  ?></h1>

                    <div class="row">
                        <div class="col-lg-6">
                            <?= $this->session->flashdata('message'); ?>
                            <form action="<?= base_url('user/index'); ?>" method="post">
                                <div class="form-group">
                                    <label for="kegiatan">Kegiatan</label>
                                    <input type="text" class="form-control" id="kegiatan" name="kegiatan">
                                    <?= form_error('kegiatan', '<small class="text-danger pl-3">', '</small>'); ?>
                                </div>

                                
                                    <div class="form-group">
                                        <label for="idvendor">ID Vendor</label>
                                        <input type="text" class="form-control" id="idvendor" name="idvendor" onkeydown="search(this)">
                                        <?= form_error('idvendor', '<small class="text-danger pl-3">', '</small>'); ?>
                                    </div>

                                    <div class="form-group">
                                
                                </div>

                                

                                <div class="form-group">
                                    <label for="namavendor">Nama Vendor</label>
                                    <input type="text" class="form-control" id="namavendor" name="namavendor">
                                    <?= form_error('namavendor', '<small class="text-danger pl-3">', '</small>'); ?>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Simpan Laporan</button>
                                </div>
                        </div>
                        </form>
                    </div>


                </div>
                <!-- /.container-fluid -->

                </div>
                <!-- End of Main Content -->

                <script type="text/javascript">
                        
                        function search(ele) {
                                if(event.key === 'Enter') {
                                    alert(ele.value);        
                                }
                            }

                            
                </script>