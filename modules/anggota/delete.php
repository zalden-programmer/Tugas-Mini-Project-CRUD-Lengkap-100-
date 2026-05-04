<?php
require_once '../../config/database.php';

// Cek apakah ada ID di URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php?pesan=ID anggota tidak valid&tipe=error");
    exit();
}

$id_anggota = (int)$_GET['id'];

// Ambil data anggota untuk konfirmasi + cek foto
$stmt = $conn->prepare("SELECT nama, foto FROM anggota WHERE id_anggota = ?");
$stmt->bind_param("i", $id_anggota);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt->close();
    closeConnection();
    header("Location: index.php?pesan=Anggota tidak ditemukan&tipe=error");
    exit();
}

$anggota     = $result->fetch_assoc();
$nama        = $anggota['nama'];
$foto        = $anggota['foto'];
$stmt->close();

// Proses delete
$stmt = $conn->prepare("DELETE FROM anggota WHERE id_anggota = ?");
$stmt->bind_param("i", $id_anggota);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {

        // Hapus foto jika ada
        if (!empty($foto) && file_exists('uploads/' . $foto)) {
            unlink('uploads/' . $foto);
        }

        $stmt->close();
        closeConnection();
        header("Location: index.php?pesan=" . urlencode("Anggota '$nama' berhasil dihapus") . "&tipe=sukses");
        exit();
    } else {
        $stmt->close();
        closeConnection();
        header("Location: index.php?pesan=Gagal menghapus data&tipe=error");
        exit();
    }
} else {
    $error = $stmt->error;
    $stmt->close();
    closeConnection();
    header("Location: index.php?pesan=" . urlencode("Error database: $error") . "&tipe=error");
    exit();
}
