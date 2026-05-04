<?php
$page_title = "Edit Data Anggota";
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Cek apakah ada ID di URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?pesan=ID anggota tidak valid&tipe=error");
    exit();
}

$id_anggota = (int)$_GET['id'];
$errors     = [];

// AMBIL DATA ANGGOTA UNTUK POPULATE FORM
$stmt = $conn->prepare("SELECT * FROM anggota WHERE id_anggota = ?");
$stmt->bind_param("i", $id_anggota);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt->close();
    closeConnection();
    header("Location: index.php?pesan=Anggota tidak ditemukan&tipe=error");
    exit();
}

$anggota = $result->fetch_assoc();
$stmt->close();

// Set variabel untuk populate form
$kode_anggota   = $anggota['kode_anggota'];
$nama           = $anggota['nama'];
$email          = $anggota['email'];
$telepon        = $anggota['telepon'];
$alamat         = $anggota['alamat'];
$tanggal_lahir  = $anggota['tanggal_lahir'];
$jenis_kelamin  = $anggota['jenis_kelamin'];
$pekerjaan      = $anggota['pekerjaan'];
$tanggal_daftar = $anggota['tanggal_daftar'];
$status         = $anggota['status'];
$foto_lama      = $anggota['foto']; // Simpan foto lama

// PROSES UPDATE JIKA FORM DI-SUBMIT
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Ambil dan sanitasi data
    $kode_anggota   = sanitize($_POST['kode_anggota']);
    $nama           = sanitize($_POST['nama']);
    $email          = sanitize($_POST['email']);
    $telepon        = sanitize($_POST['telepon']);
    $alamat         = sanitize($_POST['alamat']);
    $tanggal_lahir  = sanitize($_POST['tanggal_lahir']);
    $jenis_kelamin  = sanitize($_POST['jenis_kelamin']);
    $pekerjaan      = sanitize($_POST['pekerjaan']);
    $tanggal_daftar = sanitize($_POST['tanggal_daftar']);
    $status         = sanitize($_POST['status']);
    $foto_lama      = sanitize($_POST['foto_lama']);

    // VALIDASI

    // 1. Kode Anggota
    if (empty($kode_anggota)) {
        $errors[] = "Kode anggota wajib diisi";
    }

    // 2. Nama
    if (empty($nama)) {
        $errors[] = "Nama wajib diisi";
    } elseif (strlen($nama) < 3) {
        $errors[] = "Nama minimal 3 karakter";
    }

    // 3. Email
    if (empty($email)) {
        $errors[] = "Email wajib diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }

    // 4. Telepon
    if (empty($telepon)) {
        $errors[] = "Telepon wajib diisi";
    } elseif (!preg_match('/^08\d{8,11}$/', $telepon)) {
        $errors[] = "Format telepon tidak valid (contoh: 081234567890)";
    }

    // 5. Alamat
    if (empty($alamat)) {
        $errors[] = "Alamat wajib diisi";
    }

    // 6. Tanggal Lahir + Validasi Umur Minimal 10 Tahun
    if (empty($tanggal_lahir)) {
        $errors[] = "Tanggal lahir wajib diisi";
    } else {
        $tgl_lahir = new DateTime($tanggal_lahir);
        $hari_ini  = new DateTime();
        $umur      = $hari_ini->diff($tgl_lahir)->y;
        if ($umur < 10) {
            $errors[] = "Umur minimal 10 tahun";
        }
    }

    // 7. Jenis Kelamin
    if (empty($jenis_kelamin)) {
        $errors[] = "Jenis kelamin wajib dipilih";
    }

    // 8. Tanggal Daftar
    if (empty($tanggal_daftar)) {
        $errors[] = "Tanggal daftar wajib diisi";
    }

    // 9. Status
    if (empty($status)) {
        $errors[] = "Status wajib dipilih";
    }

    // Cek kode & email duplikat (kecuali untuk data sendiri)
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id_anggota FROM anggota
                                WHERE (kode_anggota = ? OR email = ?)
                                AND id_anggota != ?");
        $stmt->bind_param("ssi", $kode_anggota, $email, $id_anggota);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Kode anggota atau email sudah digunakan oleh anggota lain";
        }
        $stmt->close();
    }

    // UPLOAD FOTO
    $foto = $foto_lama;

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size    = 2 * 1024 * 1024; // 2MB
        $file_name   = $_FILES['foto']['name'];
        $file_size   = $_FILES['foto']['size'];
        $file_tmp    = $_FILES['foto']['tmp_name'];
        $file_ext    = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_ext)) {
            $errors[] = "Format foto tidak valid (JPG, JPEG, PNG, GIF)";
        } elseif ($file_size > $max_size) {
            $errors[] = "Ukuran foto maksimal 2MB";
        } else {
            // Hapus foto lama jika ada
            if (!empty($foto_lama) && file_exists('uploads/' . $foto_lama)) {
                unlink('uploads/' . $foto_lama);
            }
            // Upload foto baru
            $foto = 'foto_' . time() . '_' . uniqid() . '.' . $file_ext;
            move_uploaded_file($file_tmp, 'uploads/' . $foto);
        }
    }

    // UPDATE DATABASE
    if (count($errors) == 0) {
        $stmt = $conn->prepare("UPDATE anggota SET
            kode_anggota   = ?,
            nama           = ?,
            email          = ?,
            telepon        = ?,
            alamat         = ?,
            tanggal_lahir  = ?,
            jenis_kelamin  = ?,
            pekerjaan      = ?,
            tanggal_daftar = ?,
            status         = ?,
            foto           = ?
            WHERE id_anggota = ?");

        $stmt->bind_param(
            "sssssssssssi",
            $kode_anggota,
            $nama,
            $email,
            $telepon,
            $alamat,
            $tanggal_lahir,
            $jenis_kelamin,
            $pekerjaan,
            $tanggal_daftar,
            $status,
            $foto,
            $id_anggota
        );

        if ($stmt->execute()) {
            $stmt->close();
            closeConnection();
            header("Location: index.php?pesan=" . urlencode("Anggota '$nama' berhasil diupdate") . "&tipe=sukses");
            exit();
        } else {
            $errors[] = "Error database: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-warning">
                    <h4 class="mb-0">
                        <i class="bi bi-pencil"></i> Edit Data Anggota
                    </h4>
                </div>
                <div class="card-body">

                    <!-- Tampilkan Error -->
                    <?php if (count($errors) > 0): ?>
                        <div class="alert alert-danger">
                            <h6><i class="bi bi-exclamation-triangle"></i> Terdapat kesalahan:</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">

                        <!-- Hidden: simpan foto lama -->
                        <input type="hidden" name="foto_lama" value="<?php echo $foto_lama; ?>">

                        <!-- Kode Anggota & Nama -->
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="kode_anggota" class="form-label">
                                    Kode Anggota <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control"
                                    id="kode_anggota"
                                    name="kode_anggota"
                                    value="<?php echo htmlspecialchars($kode_anggota); ?>"
                                    required>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="nama" class="form-label">
                                    Nama Lengkap <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control"
                                    id="nama"
                                    name="nama"
                                    value="<?php echo htmlspecialchars($nama); ?>"
                                    required>
                            </div>
                        </div>

                        <!-- Email & Telepon -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email"
                                    class="form-control"
                                    id="email"
                                    name="email"
                                    value="<?php echo htmlspecialchars($email); ?>"
                                    required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="telepon" class="form-label">
                                    Telepon <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control"
                                    id="telepon"
                                    name="telepon"
                                    value="<?php echo htmlspecialchars($telepon); ?>"
                                    required>
                                <small class="text-muted">Format: 08xxxxxxxxxx (10-13 digit)</small>
                            </div>
                        </div>

                        <!-- Alamat -->
                        <div class="mb-3">
                            <label for="alamat" class="form-label">
                                Alamat <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control"
                                id="alamat"
                                name="alamat"
                                rows="3"
                                required><?php echo htmlspecialchars($alamat); ?></textarea>
                        </div>

                        <!-- Tanggal Lahir & Jenis Kelamin -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tanggal_lahir" class="form-label">
                                    Tanggal Lahir <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                    class="form-control"
                                    id="tanggal_lahir"
                                    name="tanggal_lahir"
                                    value="<?php echo $tanggal_lahir; ?>"
                                    max="<?php echo date('Y-m-d', strtotime('-10 years')); ?>"
                                    required>
                                <small class="text-muted">Umur minimal 10 tahun</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Jenis Kelamin <span class="text-danger">*</span>
                                </label>
                                <div class="mt-2">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input"
                                            type="radio"
                                            name="jenis_kelamin"
                                            id="laki_laki"
                                            value="Laki-laki"
                                            <?php echo ($jenis_kelamin == 'Laki-laki') ? 'checked' : ''; ?>
                                            required>
                                        <label class="form-check-label" for="laki_laki">
                                            <i class="bi bi-gender-male"></i> Laki-laki
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input"
                                            type="radio"
                                            name="jenis_kelamin"
                                            id="perempuan"
                                            value="Perempuan"
                                            <?php echo ($jenis_kelamin == 'Perempuan') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="perempuan">
                                            <i class="bi bi-gender-female"></i> Perempuan
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pekerjaan -->
                        <div class="mb-3">
                            <label for="pekerjaan" class="form-label">Pekerjaan</label>
                            <select class="form-select" id="pekerjaan" name="pekerjaan">
                                <option value="">-- Pilih Pekerjaan --</option>
                                <option value="Pelajar" <?php echo ($pekerjaan == 'Pelajar')   ? 'selected' : ''; ?>>Pelajar</option>
                                <option value="Mahasiswa" <?php echo ($pekerjaan == 'Mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                                <option value="Pegawai" <?php echo ($pekerjaan == 'Pegawai')   ? 'selected' : ''; ?>>Pegawai</option>
                                <option value="Lainnya" <?php echo ($pekerjaan == 'Lainnya')   ? 'selected' : ''; ?>>Lainnya</option>
                            </select>
                        </div>

                        <!-- Tanggal Daftar & Status -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tanggal_daftar" class="form-label">
                                    Tanggal Daftar <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                    class="form-control"
                                    id="tanggal_daftar"
                                    name="tanggal_daftar"
                                    value="<?php echo $tanggal_daftar; ?>"
                                    required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Aktif" <?php echo ($status == 'Aktif')    ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="Nonaktif" <?php echo ($status == 'Nonaktif') ? 'selected' : ''; ?>>Nonaktif</option>
                                </select>
                            </div>
                        </div>

                        <!-- Upload Foto -->
                        <div class="mb-3">
                            <label for="foto" class="form-label">
                                Foto <small class="text-muted">(Opsional - kosongkan jika tidak ingin mengubah)</small>
                            </label>

                            <!-- Tampilkan foto saat ini -->
                            <?php if (!empty($foto_lama) && file_exists('uploads/' . $foto_lama)): ?>
                                <div class="mb-2">
                                    <img src="uploads/<?php echo $foto_lama; ?>"
                                        alt="Foto saat ini"
                                        class="rounded"
                                        width="80" height="80"
                                        style="object-fit: cover;">
                                    <small class="text-muted ms-2">Foto saat ini</small>
                                </div>
                            <?php endif; ?>

                            <input type="file"
                                class="form-control"
                                id="foto"
                                name="foto"
                                accept=".jpg,.jpeg,.png,.gif">
                            <small class="text-muted">Format: JPG, JPEG, PNG, GIF | Maksimal: 2MB</small>
                        </div>

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-save"></i> Update Data Anggota
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
closeConnection();
require_once '../../includes/footer.php';
?>