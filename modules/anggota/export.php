<?php
require_once '../../config/database.php';

// Mengambil Parameter Filter dari GET
$search        = isset($_GET['search'])        ? sanitize($_GET['search'])        : '';
$filter_status = isset($_GET['status'])        ? sanitize($_GET['status'])        : '';
$filter_gender = isset($_GET['jenis_kelamin']) ? sanitize($_GET['jenis_kelamin']) : '';

// Build query
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

$query  = "SELECT kode_anggota, nama, email, telepon, alamat,
                  tanggal_lahir, jenis_kelamin, pekerjaan,
                  tanggal_daftar, status
           FROM anggota $where
           ORDER BY created_at DESC";
$result = $conn->query($query);

// Set header download
$filename = 'data_anggota_' . date('d-m-Y') . '.xls';
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");
?>
<html>

<head>
    <meta charset="UTF-8">
</head>

<body>
    <table border="1" cellpadding="5" cellspacing="0">

        <!-- Judul -->
        <tr>
            <td colspan="11"><strong>Data Anggota Perpustakaan</strong></td>
        </tr>
        <tr>
            <td colspan="11">Diekspor pada: <?php echo date('d-m-Y H:i:s'); ?></td>
        </tr>
        <tr></tr>

        <!-- Header Tabel -->
        <tr>
            <td><strong>No</strong></td>
            <td><strong>Kode Anggota</strong></td>
            <td><strong>Nama</strong></td>
            <td><strong>Email</strong></td>
            <td><strong>Telepon</strong></td>
            <td><strong>Alamat</strong></td>
            <td><strong>Tanggal Lahir</strong></td>
            <td><strong>Jenis Kelamin</strong></td>
            <td><strong>Pekerjaan</strong></td>
            <td><strong>Tanggal Daftar</strong></td>
            <td><strong>Status</strong></td>
        </tr>

        <!-- Data -->
        <?php
        if ($result->num_rows > 0):
            $no = 1;
            while ($row = $result->fetch_assoc()):
        ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo $row['kode_anggota']; ?></td>
                    <td><?php echo $row['nama']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['telepon']; ?></td>
                    <td><?php echo $row['alamat']; ?></td>
                    <td><?php echo date('d-m-Y', strtotime($row['tanggal_lahir'])); ?></td>
                    <td><?php echo $row['jenis_kelamin']; ?></td>
                    <td><?php echo $row['pekerjaan']; ?></td>
                    <td><?php echo date('d-m-Y', strtotime($row['tanggal_daftar'])); ?></td>
                    <td><?php echo $row['status']; ?></td>
                </tr>
            <?php
            endwhile;
        else:
            ?>
            <tr>
                <td colspan="11">Tidak ada data anggota</td>
            </tr>
        <?php endif; ?>

        <!-- Total -->
        <tr></tr>
        <tr>
            <td colspan="11">
                <strong>Total Anggota: <?php echo $result->num_rows; ?> orang</strong>
            </td>
        </tr>

    </table>
</body>

</html>
<?php closeConnection(); ?>