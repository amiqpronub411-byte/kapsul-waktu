const modalInput = document.getElementById('modal-input');
const btnBukaModal = document.getElementById('btn-buka-modal');
const btnBatal = document.getElementById('btn-batal');
const btnSimpan = document.getElementById('btn-simpan');

const inputJudul = document.getElementById('judul');
const inputCerita = document.getElementById('cerita');
const inputFoto = document.getElementById('foto');
const previewContainer = document.getElementById('preview-container');
const previewFoto = document.getElementById('preview-foto');
const daftarMemoriDiv = document.getElementById('daftar-memori');

let fotoBase64 = "";

// 1. Fungsi Buka & Tutup Modal Pop-up
btnBukaModal.addEventListener('click', () => modalInput.classList.remove('hidden'));
btnBatal.addEventListener('click', tutupModal);

function tutupModal() {
    modalInput.classList.add('hidden');
    inputJudul.value = "";
    inputCerita.value = "";
    inputFoto.value = "";
    fotoBase64 = "";
    previewContainer.classList.add('hidden');
}

// 2. Membaca file gambar dan diubah ke teks (Base64) agar bisa disimpan di browser
inputFoto.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewFoto.src = e.target.result;
            fotoBase64 = e.target.result;
            previewContainer.classList.remove('hidden');
        }
        reader.readAsDataURL(file);
    }
});

// 3. Menyimpan Data Momen Baru
btnSimpan.addEventListener('click', function() {
    const judul = inputJudul.value.trim() || "Momen Spesial";
    const cerita = inputCerita.value.trim() || "Tidak ada catatan.";

    // Membuat format tanggal otomatis hari ini (Contoh: "10 Juni 2026")
    const opsiTanggal = { day: 'numeric', month: 'long', year: 'numeric' };
    const tanggalHariIni = new Date().toLocaleDateString('id-ID', opsiTanggal);

    let kumpulanMemori = JSON.parse(localStorage.getItem('kapsulWaktu')) || [];

    const memoriBaru = {
        id: +new Date(),
        judul: judul,
        cerita: cerita,
        tanggal: tanggalHariIni,
        foto: fotoBase64 || "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=500" // foto cadangan jika kosong
    };

    kumpulanMemori.push(memoriBaru);
    localStorage.setItem('kapsulWaktu', JSON.stringify(kumpulanMemori));

    tutupModal();
    tampilkanMemori();
});

// 4. Menampilkan Data Dari Browser ke Layar Grid
function tampilkanMemori() {
    daftarMemoriDiv.innerHTML = "";
    const kumpulanMemori = JSON.parse(localStorage.getItem('kapsulWaktu')) || [];

    if (kumpulanMemori.length === 0) {
        daftarMemoriDiv.innerHTML = `<p style="color:#555; grid-column: 1/-1; text-align:center; margin-top:30px;">Belum ada momen terkunci. Klik tombol di atas untuk menambah!</p>`;
        return;
    }

    // Mengulang dan memunculkan data dari yang terbaru di atas
    kumpulanMemori.reverse().forEach(memori => {
        const card = document.createElement('div');
        card.classList.add('card-memori');
        card.innerHTML = `
            <img src="${memori.foto}" alt="Foto Kenangan">
            <div class="card-content">
                <div class="card-date">📅 ${memori.tanggal}</div>
                <h3>${memori.judul}</h3>
                <p>${memori.cerita}</p>
                <button class="btn-hapus" onclick="hapusMemori(${memori.id})">🗑️ Hapus Momen</button>
            </div>
        `;
        daftarMemoriDiv.appendChild(card);
    });
}

// 5. Fitur Menghapus Foto / Kenangan
window.hapusMemori = function(id) {
    if(confirm("Apakah kamu yakin ingin menghapus kenangan ini dari kapsul waktu?")) {
        let kumpulanMemori = JSON.parse(localStorage.getItem('kapsulWaktu')) || [];
        kumpulanMemori = kumpulanMemori.filter(memori => memori.id !== id);
        localStorage.setItem('kapsulWaktu', JSON.stringify(kumpulanMemori));
        tampilkanMemori();
    }
}

// Jalankan otomatis saat web dimuat
tampilkanMemori();