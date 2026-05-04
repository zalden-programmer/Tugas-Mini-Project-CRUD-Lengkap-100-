<?php
$page_title = "Data Anggota";
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Pagination
$limit  = 10;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search & Filter
$search        = isset($_GET['search'])        ? sanitize($_GET['search'])        : '';
$filter_status = isset($_GET['status'])        ? sanitize($_GET['status'])        : '';
$filter_gender = isset($_GET['jenis_kelamin']) ? sanitize($_GET['jenis_kelamin']) : '';

// BUILD QUERY DENGAN FILTER
$where = "WHERE 1=1";

if (!empty($search)) {
    $where .= " AND (nama LIKE '%$search%'
                OR email LIKE '%$search%'
                OR telepon LIKE '%$search%')";
}

if (!empty($filter_status)) {
    $where .= " AND status = '$filter_status'";
}

if (!empty($filter_gender)) {
    $where .= " AND jenis_kelamin = '$filter_gender'";
}

// Query data anggota
$query  = "SELECT * FROM anggota $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt   = $conn->prepare($query);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Untuk Hitung total untuk pagination
$count_query = "SELECT COUNT(*) as total FROM anggota $where";
$total_rows  = $conn->query($count_query)->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// STATISTIK DASHBOARD

$stat_total     = $conn->query("SELECT COUNT(*) AS total FROM anggota")->fetch_assoc()['total'];
$stat_aktif     = $conn->query("SELECT COUNT(*) AS total FROM anggota WHERE status = 'Aktif'")->fetch_assoc()['total'];
$stat_nonaktif  = $conn->query("SELECT COUNT(*) AS total FROM anggota WHERE status = 'Nonaktif'")->fetch_assoc()['total'];
$stat_laki      = $conn->query("SELECT COUNT(*) AS total FROM anggota WHERE jenis_kelamin = 'Laki-laki'")->fetch_assoc()['total'];
$stat_perempuan = $conn->query("SELECT COUNT(*) AS total FROM anggota WHERE jenis_kelamin = 'Perempuan'")->fetch_assoc()['total'];
?>

<div class="container">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2><i class="bi bi-people"></i> Data Anggota Perpustakaan</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="export.php?<?php echo http_build_query($_GET); ?>"
                class="btn btn-success me-2">
                <i class="bi bi-file-excel"></i> Export Excel
            </a>
            <a href="create.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Tambah Anggota
            </a>
        </div>
    </div>

    <!--STATISTIK DASHBOARD-->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body text-center">
                    <i class="bi bi-people fs-2"></i>
                    <h5 class="mt-2">Total Anggota</h5>
                    <h2><?php echo $stat_total; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body text-center">
                    <i class="bi bi-person-check fs-2"></i>
                    <h5 class="mt-2">Aktif</h5>
                    <h2><?php echo $stat_aktif; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-danger h-100">
                <div class="card-body text-center">
                    <i class="bi bi-person-x fs-2"></i>
                    <h5 class="mt-2">Nonaktif</h5>
                    <h2><?php echo $stat_nonaktif; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-info h-100">
                <div class="card-body text-center">
                    <i class="bi bi-gender-ambiguous fs-2"></i>
                    <h5 class="mt-2">L / P</h5>
                    <h2><?php echo $stat_laki; ?> / <?php echo $stat_perempuan; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!--FORM SEARCH & FILTER-->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET">
                <div class="row">
                    <!-- Search Keyword -->
                    <div class="col-md-4 mb-2">
                        <input type="text"
                            class="form-control"
                            name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Cari nama, email, atau telepon...">
                    </div>

                    <!-- Filter Status -->
                    <div class="col-md-3 mb-2">
                        <select class="form-select" name="status">
                            <option value="">-- Semua Status --</option>
                            <option value="Aktif" <?php echo ($filter_status == 'Aktif')    ? 'selected' : ''; ?>>Aktif</option>
                            <option value="Nonaktif" <?php echo ($filter_status == 'Nonaktif') ? 'selected' : ''; ?>>Nonaktif</option>
                        </select>
                    </div>

                    <!-- Filter Jenis Kelamin -->
                    <div class="col-md-3 mb-2">
                        <select class="form-select" name="jenis_kelamin">
                            <option value="">-- Semua Jenis Kelamin --</option>
                            <option value="Laki-laki" <?php echo ($filter_gender == 'Laki-laki')  ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="Perempuan" <?php echo ($filter_gender == 'Perempuan')  ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>

                    <!-- Tombol -->
                    <div class="col-md-2 mb-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Cari
                        </button>
                        <a href="index.php" class="btn btn-secondary w-100">
                            <i class="bi bi-x"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- TABEL ANGGOTA -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                Daftar Anggota
                <span class="badge bg-white text-primary"><?php echo $total_rows; ?> anggota</span>
            </h5>
        </div>
        <div class="card-body">

            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Foto</th>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Jenis Kelamin</th>
                                <th>Status</th>
                                <th>Tanggal Daftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = $offset + 1; ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>

                                    <!-- Foto -->
                                    <td class="text-center">
                                        <?php if (!empty($row['foto']) && file_exists('uploads/' . $row['foto'])): ?>
                                            <img src="uploads/<?php echo $row['foto']; ?>"
                                                alt="Foto"
                                                class="rounded-circle"
                                                width="40" height="40"
                                                style="object-fit: cover;">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-secondary d-inline-flex
                                                        align-items-center justify-content-center"
                                                style="width:40px; height:40px;">
                                                <i class="bi bi-person text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td><code><?php echo $row['kode_anggota']; ?></code></td>
                                    <td><?php echo $row['nama']; ?></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['telepon']; ?></td>

                                    <!-- Badge Jenis Kelamin -->
                                    <td>
                                        <?php if ($row['jenis_kelamin'] == 'Laki-laki'): ?>
                                            <span class="badge bg-primary">
                                                <i class="bi bi-gender-male"></i> Laki-laki
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-gender-female"></i> Perempuan
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Badge Status -->
                                    <td>
                                        <?php if ($row['status'] == 'Aktif'): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>

                                    <td><?php echo date('d-m-Y', strtotime($row['tanggal_daftar'])); ?></td>

                                    <!-- Aksi -->
                                    <td>
                                        <a href="edit.php?id=<?php echo $row['id_anggota']; ?>"
                                            class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="delete.php?id=<?php echo $row['id_anggota']; ?>"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Yakin hapus anggota <?php echo addslashes($row['nama']); ?>?')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <?php
                    $query_params = $_GET;
                    unset($query_params['page']);
                    $query_string = http_build_query($query_params);
                    ?>
                    <ul class="pagination justify-content-center mt-3">
                        <li class="page-item <?php echo ($page == 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo $query_string; ?>&page=<?php echo $page - 1; ?>">
                                <i class="bi bi-chevron-left"></i> Prev
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo $query_string; ?>&page=<?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo ($page == $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo $query_string; ?>&page=<?php echo $page + 1; ?>">
                                Next <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                    <p class="text-center text-muted small">
                        Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>
                        (<?php echo $total_rows; ?> total anggota)
                    </p>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-inbox"></i>
                    <?php echo !empty($search) ? "Tidak ada anggota dengan kata kunci \"$search\"" : "Belum ada data anggota"; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php
if (isset($stmt))       $stmt->close();
closeConnection();
require_once '../../includes/footer.php';
?>