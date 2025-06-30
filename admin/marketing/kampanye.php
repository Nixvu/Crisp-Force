<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

checkRole(['Admin']);

// Data untuk Tab "Semua Kampanye"
$semua_kampanye = $conn->query("SELECT c.*, u.nama_lengkap as marketing_name FROM Campaign c JOIN User u ON c.id_marketing = u.id_user WHERE c.deleted_at IS NULL ORDER BY c.created_at DESC");

// Data untuk Tab "Menunggu Persetujuan"
$menunggu_persetujuan = $conn->query("SELECT c.*, u.nama_lengkap as marketing_name FROM Campaign c JOIN User u ON c.id_marketing = u.id_user WHERE c.status = 'menunggu_acc' ORDER BY c.created_at ASC");

// Data untuk Tab "Template"
$templates = $conn->query("SELECT t.*, u.nama_lengkap as creator_name FROM CampaignTemplate t JOIN User u ON t.created_by = u.id_user ORDER BY t.created_at DESC");

// Variabel untuk menentukan tab aktif
$active_tab = $_GET['tab'] ?? 'all';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kampanye</title>
</head>
<body>

<?php include '../../includes/header.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manajemen Kampanye</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#campaignModal">
                <i class="bi bi-plus-lg"></i> Buat Kampanye Baru
            </button>
        </div>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <ul class="nav nav-tabs" id="campaignTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link <?= $active_tab == 'all' ? 'active' : '' ?>" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">Semua Kampanye</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link <?= $active_tab == 'approval' ? 'active' : '' ?>" id="approval-tab" data-bs-toggle="tab" data-bs-target="#approval" type="button" role="tab">Menunggu Persetujuan <span class="badge bg-danger"><?= $menunggu_persetujuan->num_rows ?></span></button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link <?= $active_tab == 'template' ? 'active' : '' ?>" id="template-tab" data-bs-toggle="tab" data-bs-target="#template" type="button" role="tab">Template</button>
      </li>
    </ul>

    <div class="tab-content" id="myTabContent">
      <div class="tab-pane fade <?= $active_tab == 'all' ? 'show active' : '' ?>" id="all" role="tabpanel">
        <div class="table-responsive mt-3">
            <table class="table table-striped table-sm">
                <thead>
                    <tr><th>Nama Kampanye</th><th>Channel</th><th>Tanggal</th><th>Dibuat Oleh</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php mysqli_data_seek($semua_kampanye, 0); while($c = $semua_kampanye->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['nama_kampanye']) ?></td>
                        <td><?= ucfirst(str_replace('_', ' ', $c['jenis_kampanye'])) ?></td>
                        <td><?= formatDate($c['tanggal_mulai']) ?></td>
                        <td><?= htmlspecialchars($c['marketing_name']) ?></td>
                        <td><span class="badge bg-<?= $c['status']=='aktif' ? 'success' : ($c['status']=='menunggu_acc' ? 'warning' : 'secondary') ?>"><?= ucfirst($c['status']) ?></span></td>
                        <td><button class="btn btn-sm btn-info" onclick="viewCampaign(<?= htmlspecialchars(json_encode($c)) ?>)">Lihat</button></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
      </div>
      <div class="tab-pane fade <?= $active_tab == 'approval' ? 'show active' : '' ?>" id="approval" role="tabpanel">
         <div class="table-responsive mt-3">
            <table class="table table-striped table-sm">
                <thead>
                    <tr><th>Nama Kampanye</th><th>Diajukan Oleh</th><th>Tanggal</th><th>Channel</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php mysqli_data_seek($menunggu_persetujuan, 0); while($a = $menunggu_persetujuan->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['nama_kampanye']) ?></td>
                        <td><?= htmlspecialchars($a['marketing_name']) ?></td>
                        <td><?= formatDate($a['tanggal_mulai']) ?></td>
                        <td><?= ucfirst(str_replace('_', ' ', $a['jenis_kampanye'])) ?></td>
                        <td><button class="btn btn-sm btn-primary" onclick="reviewCampaign(<?= htmlspecialchars(json_encode($a)) ?>)">Lihat & Setujui</button></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
      </div>
      <div class="tab-pane fade <?= $active_tab == 'template' ? 'show active' : '' ?>" id="template" role="tabpanel">
          <div class="card mt-3">
              <div class="card-header d-flex justify-content-between">
                  <span>Daftar Template Kampanye</span>
                  <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#templateModal" onclick="resetTemplateForm()">Buat Template Baru</button>
              </div>
              <div class="card-body">
                  <table class="table table-sm">
                      <thead><tr><th>Nama Template</th><th>Subjek</th><th>Dibuat Oleh</th><th>Aksi</th></tr></thead>
                      <tbody>
                          <?php while($temp = $templates->fetch_assoc()): ?>
                          <tr>
                              <td><?= htmlspecialchars($temp['nama_template']) ?></td>
                              <td><?= htmlspecialchars($temp['subjek']) ?></td>
                              <td><?= htmlspecialchars($temp['creator_name']) ?></td>
                              <td>
                                  <button class="btn btn-sm btn-warning" onclick='editTemplate(<?= htmlspecialchars(json_encode($temp)) ?>)'>Edit</button>
                                  <a href="../actions/proses_template_kampanye.php?action=delete&id=<?= $temp['id_template'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus template ini?')">Hapus</a>
                              </td>
                          </tr>
                          <?php endwhile; ?>
                      </tbody>
                  </table>
              </div>
          </div>
      </div>
    </div>
</main>

<!-- Modal Buat Kampanye Baru -->
<div class="modal fade" id="campaignModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Buat Kampanye Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form action="../actions/proses_kampanye.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
                <label class="form-label">Gunakan Template (Opsional)</label>
                <select class="form-select" onchange="applyTemplate(this.value)">
                    <option value="">Pilih Template</option>
                    <?php mysqli_data_seek($templates, 0); while($temp = $templates->fetch_assoc()): ?>
                    <option value="<?= $temp['id_template'] ?>"><?= htmlspecialchars($temp['nama_template']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3"><label class="form-label">Nama Kampanye</label><input type="text" class="form-control" name="nama_kampanye" required></div>
            <div class="mb-3"><label class="form-label">Subjek</label><input type="text" class="form-control" id="campaign_subjek" name="subjek"></div>
            <div class="mb-3"><label class="form-label">Deskripsi/Konten</label><textarea class="form-control" id="campaign_deskripsi" name="deskripsi" rows="4" required></textarea></div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Jenis Kampanye</label><select class="form-select" name="jenis_kampanye"><option value="internal_platform">Platform Internal</option><option value="email">Email</option><option value="semua">Semua</option></select></div>
                <div class="col-md-6 mb-3"><label class="form-label">Target Segmentasi</label><select class="form-select" name="target_segmentasi"><option value="semua">Semua</option><option value="baru">Baru</option><option value="loyal">Loyal</option><option value="perusahaan">Perusahaan</option></select></div>
            </div>
            <div class="mb-3"><label class="form-label">Tanggal Mulai</label><input type="date" class="form-control" name="tanggal_mulai" required></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button><button type="submit" class="btn btn-primary">Simpan Kampanye</button></div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Add/Edit Template -->
<div class="modal fade" id="templateModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="templateModalLabel">Buat Template Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form id="templateForm" action="../actions/proses_template_kampanye.php" method="POST">
        <div class="modal-body">
            <input type="hidden" name="action" id="template_action" value="add">
            <input type="hidden" name="id_template" id="id_template">
            <div class="mb-3"><label class="form-label">Nama Template</label><input type="text" class="form-control" id="nama_template" name="nama_template" required></div>
            <div class="mb-3"><label class="form-label">Subjek</label><input type="text" class="form-control" id="subjek_template" name="subjek"></div>
            <div class="mb-3"><label class="form-label">Konten</label><textarea class="form-control" id="konten_template" name="konten" rows="5" required></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button><button type="submit" class="btn btn-primary">Simpan Template</button></div>
      </form>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>
<script>
    // Fungsi untuk menampilkan modal review
    function reviewCampaign(campaign) {
        document.getElementById('review_id_campaign').value = campaign.id_campaign;
        document.getElementById('review_nama_kampanye').innerText = campaign.nama_kampanye;
        document.getElementById('review_deskripsi').innerText = campaign.deskripsi;
        // Tampilkan field alasan penolakan dan tombol aksi untuk review
        document.getElementById('rejection-reason-field').style.display = 'block';
        document.querySelector('#approvalModal form').style.display = 'block'; // Pastikan form terlihat
        document.querySelector('#approvalModal .d-flex.justify-content-end').style.display = 'flex'; // Pastikan tombol aksi terlihat
        new bootstrap.Modal(document.getElementById('approvalModal')).show();
    }

    // Fungsi untuk menampilkan detail kampanye (untuk tab "Semua Kampanye")
    function viewCampaign(campaign) {
        document.getElementById('review_id_campaign').value = campaign.id_campaign; // Tetap gunakan ID untuk konsistensi
        document.getElementById('review_nama_kampanye').innerText = campaign.nama_kampanye;
        document.getElementById('review_deskripsi').innerText = campaign.deskripsi;
        // Sembunyikan field alasan penolakan dan tombol aksi untuk mode lihat
        document.getElementById('rejection-reason-field').style.display = 'none';
        document.querySelector('#approvalModal form').style.display = 'none'; // Sembunyikan form/tombol
        new bootstrap.Modal(document.getElementById('approvalModal')).show();
    }

    // Fungsi untuk mengatur aksi (approve/reject) dan menampilkan/menyembunyikan field alasan
    function setApprovalAction(action) {
        document.getElementById('approvalAction').value = action;
        if (action === 'reject') {
            document.getElementById('rejection-reason-field').style.display = 'block';
            if(document.querySelector('[name=rejection_reason]').value.trim() === '') {
                alert('Mohon isi alasan penolakan.');
                return false;
            }
        } else {
            document.getElementById('rejection-reason-field').style.display = 'none';
        }
        return true;
    }

function resetTemplateForm() {
    document.getElementById('templateForm').reset();
    document.getElementById('templateModalLabel').innerText = 'Buat Template Baru';
    document.getElementById('template_action').value = 'add';
    document.getElementById('id_template').value = '';
}

function editTemplate(template) {
    resetTemplateForm();
    document.getElementById('templateModalLabel').innerText = 'Edit Template';
    document.getElementById('template_action').value = 'edit';
    document.getElementById('id_template').value = template.id_template;
    document.getElementById('nama_template').value = template.nama_template;
    document.getElementById('subjek_template').value = template.subjek;
    document.getElementById('konten_template').value = template.konten;
    new bootstrap.Modal(document.getElementById('templateModal')).show();
}

function applyTemplate(templateId) {
    if (!templateId) {
        document.getElementById('campaign_subjek').value = '';
        document.getElementById('campaign_deskripsi').value = '';
        return;
    }
    fetch(`../actions/proses_template_kampanye.php?action=get_content&id=${templateId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
            } else {
                document.getElementById('campaign_subjek').value = data.subjek;
                document.getElementById('campaign_deskripsi').value = data.konten;
            }
        })
        .catch(err => console.error(err));
}
</script>
</body>
</html>
