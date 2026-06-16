# Catatan Fitur Jemaat (KKJ)

Dokumen ini menerjemahkan catatan kebutuhan dari user menjadi spesifikasi yang dapat dikerjakan, memetakannya ke data model saat ini, dan menyediakan daftar tugas (TODO) untuk implementasi.

> Konteks data model saat ini (lihat [CLAUDE.md](../CLAUDE.md)):
> - **Umat** punya: `nama_lengkap`, `nama_panggilan`, `nomor_telepon`, `jenis_kelamin`, `status_perkawinan`, `hub_kk`, `golongan_darah`, `tempat_lahir`, `tanggal_lahir`, `alamat`, `pendidikan`, `pekerjaan`, `domisili`, `kemah_id`, `area_id`, `keluarga_id`.
> - **Belum ada**: status siklus hidup (calon/aktif/keluar/meninggal), `tanggal_masuk`, `tanggal_keluar`, `keterangan`, audit log perubahan, manajemen user/role, dan export Excel.

---

## A. Siklus Hidup & Status Jemaat

Mencakup catatan: *"tanggal jemaat masuk/keluar"*, *"data yang pernah berjemaat / masuk-keluar / meninggal"*, *"pemantauan jemaat baru 6 bulan sebelum jadi keluarga Mahanaim"*.

**Kebutuhan**

- Field baru pada `umat`:
  - `status` — enum: `calon` (prospektif), `aktif`, `keluar`, `meninggal`.
  - `tanggal_masuk` (date) — tanggal pencatatan masuk.
  - `tanggal_keluar` (date, nullable) — diisi saat jemaat keluar atau meninggal.
  - `keterangan` (text, nullable) — catatan bebas (alasan keluar, dsb).
- **Jemaat yang pernah berjemaat tetap tersimpan** (jangan dihapus) — cukup ubah `status` menjadi `keluar`/`meninggal`. Default tampilan list menyaring hanya `aktif`, dengan toggle untuk melihat arsip.
- **Pemantauan calon (6 bulan)**: jemaat baru dimulai dengan `status = calon` dan `tanggal_masuk`. Perlu indikator/daftar "calon yang sudah ≥6 bulan" agar bisa dipromosikan menjadi keluarga Mahanaim (status `aktif`).

**Form penambahan jemaat** harus menyertakan `status`, `tanggal_masuk`, `tanggal_keluar`, dan `keterangan`.

---

## B. Logging / Riwayat Perubahan (Audit)

Catatan: *"logging data perubahan (alamat, jemaat keluar, meninggal, penambahan anak, pemindahan area) — last change / riwayat global tampil di dashboard; field keterangan"*.

**Kebutuhan**

- Tabel audit (mis. `umat_activities` atau pakai paket activity log) yang mencatat: jenis perubahan, field lama → baru, user pelaku, waktu, dan `keterangan`.
- Perubahan yang wajib tercatat: **alamat**, **status → keluar/meninggal**, **penambahan anggota/anak** dalam keluarga, **pemindahan area** (`area_id` berubah).
- **Dashboard**: widget "Riwayat Perubahan Terbaru" (global, lintas jemaat) menampilkan log terurut terbaru.
- Keputusan teknis: gunakan model `Observer`/event Eloquent untuk menangkap perubahan otomatis, atau paket seperti `spatie/laravel-activitylog` (perlu persetujuan user sebelum menambah dependency — lihat aturan di AGENTS.md).

---

## C. Pencarian, Filter, & Pemanggilan

Catatan: *"pencarian ulang tahun bulan ini"*, *"filtering by area / bulan ulang tahun / usia (anak, remaja, pemuda, dewasa)"*, *"custom filtering"*, *"pemanggilan jemaat (Sdr/Sdri/Bapak/Ibu/Anak)"*.

**Kebutuhan**

- **Ulang tahun bulan ini**: query `umat` berdasarkan `MONTH(tanggal_lahir) = bulan terpilih` (default bulan berjalan).
- **Kelompok usia** (dihitung dari `tanggal_lahir`): definisikan batas — saran awal `anak` (0–12), `remaja` (13–17), `pemuda` (18–30), `dewasa` (>30). **Konfirmasi batas usia ke user.**
- **Filter di halaman Umat**: by `area`, by bulan ulang tahun, by kelompok usia. Filter bisa dikombinasikan.
- **Custom filtering**: kombinasi beberapa kriteria (area + usia + status + kelompok usia, dll) dalam satu UI.
- **Pemanggilan otomatis (Sdr/Sdri/Bapak/Ibu/Anak)**: turunan (computed accessor, bukan kolom) dari kombinasi `jenis_kelamin` + `hub_kk` + `status_perkawinan`. Perlu tabel aturan yang dikonfirmasi user, contoh:
  - `hub_kk = Anak` → "Anak"
  - dewasa + kawin + laki-laki → "Bapak"; perempuan → "Ibu"
  - dewasa + belum kawin + laki-laki → "Sdr"; perempuan → "Sdri"

---

## D. Statistik & Demografi (Dashboard)

Catatan: *"pengelompokan demografi berdasarkan usia"*, *"statistik jumlah jemaat yang bertambah / keluar"*.

**Kebutuhan**

- **Statistik pertumbuhan**: jumlah jemaat **bertambah** (berdasarkan `tanggal_masuk` dalam periode) dan **keluar/meninggal** (berdasarkan `tanggal_keluar`/`status`) per bulan/tahun. Bergantung pada bagian A.
- **Demografi usia**: distribusi jemaat per kelompok usia (lihat C), bisa per area.
- Tampilkan sebagai kartu/grafik di dashboard.

---

## E. Export Data ke Excel

Catatan: *"export data ke excel"*.

**Kebutuhan**

- Export data Umat (dan opsional Keluarga) ke `.xlsx`, menghormati filter yang sedang aktif.
- **Catatan teknis**: import saat ini ditulis tangan tanpa PhpSpreadsheet ([KeluargaExcelImporter](../app/Services/KeluargaExcelImporter.php) memakai `ZipArchive` + `SimpleXMLElement`). Untuk konsistensi, export bisa dibuat dengan pendekatan serupa (menulis XML SpreadsheetML) atau CSV, **atau** ajukan menambah paket export ke user. Hindari menambah dependency tanpa persetujuan.

---

## F. Manajemen User & Login

Catatan: *"user lain untuk login"*.

**Kebutuhan**

- Memungkinkan beberapa user mengelola data (auth sudah ada via Fortify, namun registrasi terbuka).
- Tambahkan **peran/role** minimal (mis. admin vs operator) dan halaman manajemen user (buat/nonaktifkan user) — bukan registrasi publik.
- **Konfirmasi ke user**: role apa saja yang dibutuhkan dan siapa yang boleh membuat user baru.

---

## G. Tanda Tangan "Yang Mengetahui" pada PDF

Catatan: *"tanda tangan digital yang mengetahui (ttd jemaat tetap basah)"*.

**Kebutuhan**

- Pada dokumen PDF (kartu keluarga — [KeluargaCardPdf](../app/Services/KeluargaCardPdf.php)), sediakan area **tanda tangan digital untuk pihak yang mengetahui** (mis. ketua/gembala), sementara **tanda tangan jemaat tetap manual/basah** (kolom kosong untuk ditandatangani).
- Implementasi: gambar tanda tangan (upload/stored image) ditempel pada template PDF di posisi "Mengetahui". **Konfirmasi ke user** siapa penandatangan dan apakah satu tanda tangan tetap atau per-pejabat.

---

## TODO — Konteks Pengerjaan

Urutan disusun karena beberapa fitur bergantung pada fondasi data lifecycle (A).

### Fondasi (kerjakan lebih dulu — banyak fitur bergantung)
- [x] **A1** Migration: tambah `status`, `tanggal_masuk`, `tanggal_keluar`, `keterangan` ke tabel `umat`. Data lama di-backfill `status=aktif`, `tanggal_masuk=date(created_at)`.
- [x] **A2** Update `#[Fillable]` model `Umat`, factory, dan form penambahan/edit di halaman Umat. Jemaat baru default `status=calon`, `tanggal_masuk`=hari ini.
- [x] **A3** Default list Umat menampilkan `calon`+`aktif` + toggle lihat arsip (keluar/meninggal) + kolom Status.
- [x] **A4** Filter/indikator "calon ≥ 6 bulan" (badge jumlah) + aksi promosikan ke `aktif`.

### Audit (B)
- [ ] **B1** Putuskan pendekatan (Eloquent observer manual vs paket activity log) — minta persetujuan jika menambah dependency.
- [ ] **B2** Catat perubahan: alamat, status keluar/meninggal, penambahan anggota keluarga, pemindahan area.
- [ ] **B3** Widget "Riwayat Perubahan Terbaru" di dashboard.

### Pencarian & Filter (C)
- [ ] **C1** Konfirmasi batas kelompok usia & aturan pemanggilan ke user.
- [ ] **C2** Accessor `kelompok_usia` dan `pemanggilan` pada model `Umat`.
- [ ] **C3** Filter halaman Umat: area, bulan ulang tahun, kelompok usia, status (dapat dikombinasi).
- [ ] **C4** Pintasan "ulang tahun bulan ini".

### Statistik & Demografi (D)
- [ ] **D1** Kartu statistik pertumbuhan (masuk/keluar per periode) — butuh A.
- [ ] **D2** Distribusi demografi usia di dashboard.

### Export (E)
- [ ] **E1** Export Umat ke Excel/CSV menghormati filter aktif (putuskan pendekatan tanpa/with dependency).

### User Management (F)
- [ ] **F1** Konfirmasi kebutuhan role ke user.
- [ ] **F2** Role + halaman manajemen user; batasi registrasi publik.

### Tanda Tangan PDF (G)
- [ ] **G1** Konfirmasi penandatangan "yang mengetahui".
- [ ] **G2** Tambah area tanda tangan digital pada template PDF; sisakan kolom tanda tangan jemaat manual.

### Catatan lintas-tugas
- Setiap perubahan PHP: jalankan `vendor/bin/pint --dirty --format agent` dan tulis/perbarui test Pest (`php artisan test --compact`).
- Field & kolom baru memakai penamaan Bahasa Indonesia sesuai konvensi yang ada.
- Butuh keputusan user sebelum mulai: **batas usia (C1)**, **aturan pemanggilan (C1)**, **role user (F1)**, **penandatangan PDF (G1)**, dan **penambahan dependency apa pun (B1/E1)**.
