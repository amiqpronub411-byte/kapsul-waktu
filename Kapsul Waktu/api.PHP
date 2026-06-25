<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$db_file = 'database_memori.json';
$upload_dir = 'uploads/';

// Otomatis buat folder uploads jika belum ada di hosting
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Ambil data lama jika file database sudah ada
$kumpulan_memori = [];
if (file_exists($db_file)) {
    $kumpulan_memori = json_decode(file_get_contents($db_file), true) ?? [];
}

$method = $_SERVER['REQUEST_METHOD'];

// 1. JALUR MENAMPILKAN DATA (GET)
if ($method === 'GET') {
    echo json_encode($kumpulan_memori);
    exit;
}

// 2. JALUR PROSES DATA (POST)
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? '';

    // A. FITUR SIMPAN KENANGAN BARU
    if ($action === 'save') {
        if (!isset($input['judul']) || !isset($input['foto'])) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap!']);
            exit;
        }

        // Proses decode foto Base64 menjadi file gambar asli (.png/.jpg)
        $foto_data = $input['foto'];
        if (preg_match('/^data:image\/(\w+);base64,/', $foto_data, $type)) {
            $foto_data = substr($foto_data, strpos($foto_data, ',') + 1);
            $type = strtolower($type[1]); // png, jpg, jpeg, gif
            
            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                echo json_encode(['success' => false, 'message' => 'Format gambar tidak valid!']);
                exit;
            }

            $foto_bytes = base64_decode($foto_data);
            if ($foto_bytes === false) {
                echo json_encode(['success' => false, 'message' => 'Gagal membaca data gambar!']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Data gambar rusak!']);
            exit;
        }

        // Beri nama file acak yang unik agar tidak bentrok
        $nama_file_gambar = 'kapsul_' . uniqid() . '.' . $type;
        $path_simpan = $upload_dir . $nama_file_gambar;

        // Simpan gambar fisik ke folder uploads/
        if (file_put_contents($path_simpan, $foto_bytes)) {
            // Susun data memori untuk disimpan ke JSON (hanya menyimpan nama jalurnya saja, filenya terpisah)
            $memori_baru = [
                'id' => $input['id'],
                'judul' => $input['judul'],
                'cerita' => $input['cerita'],
                'kategori' => $input['kategori'],
                'tanggal' => $input['tanggal'],
                'foto' => $path_simpan, // Disimpan sebagai path URL lokal server
                'isPinned' => false
            ];

            $kumpulan_memori[] = $memori_baru;
            file_put_contents($db_file, json_encode($kumpulan_memori, JSON_PRETTY_PRINT));
            
            echo json_encode(['success' => true, 'message' => 'Kenangan berhasil dikunci ke server!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan gambar ke hosting!']);
        }
        exit;
    }

    // B. FITUR PIN / UNPIN MEMORI
    if ($action === 'pin') {
        $id = $input['id'] ?? null;
        $kumpulan_memori = array_map(function($m) use ($id) {
            if ($m['id'] == $id) {
                $m['isPinned'] = !$m['isPinned'];
            }
            return $m;
        }, $kumpulan_memori);

        file_put_contents($db_file, json_encode($kumpulan_memori, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
        exit;
    }

    // C. FITUR HAPUS MEMORI
    if ($action === 'delete') {
        $id = $input['id'] ?? null;
        
        // Cari file gambar fisik di folder uploads/ lalu hapus dari server agar hosting tidak penuh
        foreach ($kumpulan_memori as $m) {
            if ($m['id'] == $id) {
                if (file_exists($m['foto'])) {
                    unlink($m['foto']); // Hapus file foto dari server
                }
                break;
            }
        }

        // Filter buang data dari database JSON
        $kumpulan_memori = array_values(array_filter($kumpulan_memori, function($m) use ($id) {
            return $m['id'] != $id;
        }));

        file_put_contents($db_file, json_encode($kumpulan_memori, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true, 'message' => 'Kenangan dihapus dari server!']);
        exit;
    }
}
?>