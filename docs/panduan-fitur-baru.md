# Panduan Fitur Baru — Sistem Data Jemaat (KKJ)

Dokumen ini menjelaskan perubahan yang baru ditambahkan dan cara mencobanya. Disusun agar mudah diikuti, termasuk untuk yang bukan teknis.

---

## 1. Beberapa Pengguna & Hak Akses (Admin / Pendeta)

**Apa yang berubah:** Sekarang ada dua tingkat akses.
- **Admin** — dapat menambah, mengubah, menghapus, mengimpor, dan mengekspor data.
- **Pendeta** — hanya **melihat** seluruh data dan dashboard (tidak ada tombol ubah/hapus).

**Cara mencoba:**
1. Sebagai Admin, buka menu **Pengguna** di sidebar (hanya muncul untuk Admin).
2. Klik **Tambah pengguna**, isi nama, email, kata sandi, lalu pilih peran **Admin** atau **Pendeta**. Simpan.
3. Keluar (log out), lalu masuk memakai akun **Pendeta** tadi.
4. Perhatikan: semua halaman (Umat, Keluarga, Area, Kemah) bisa dibuka, tetapi tombol Tambah/Ubah/Hapus/Impor/Ekspor **tidak muncul** — hanya bisa melihat.

---

## 2. Status & Riwayat Hidup Jemaat

**Apa yang berubah:** Setiap jemaat punya **status** (Calon, Aktif, Keluar, Meninggal), serta **tanggal masuk**, **tanggal keluar**, dan **keterangan**. Data yang keluar/meninggal **tidak dihapus**, hanya diarsipkan.

**Cara mencoba:**
1. Buka menu **Umat** → **Tambah umat**.
2. Isi data, lalu pada bagian **Status & siklus hidup** pilih status dan tanggal masuk. Simpan.
3. Di daftar Umat, secara default tampil jemaat **Calon + Aktif**. Nyalakan **"Tampilkan arsip"** untuk melihat yang Keluar/Meninggal.
4. Untuk calon yang sudah dipantau ≥ 6 bulan, akan muncul penanda jumlah; gunakan tombol **Promosikan** untuk menjadikannya Aktif.

---

## 3. Sebutan Otomatis & Umur

**Apa yang berubah:** Pada daftar Umat kini muncul **sebutan** (Sdr, Sdri, Bapak, Ibu, Anak) dan **umur**, dihitung otomatis dari jenis kelamin, status nikah, hubungan keluarga, dan tanggal lahir.

**Cara mencoba:** Buka menu **Umat** dan lihat kolom **Nama** — sebutan tampil di depan nama, umur di bawahnya. Tidak perlu mengisi apa pun secara manual.

---

## 4. Pencarian & Penyaringan (Filter)

**Apa yang berubah:** Daftar Umat bisa disaring dengan cepat.

**Cara mencoba:** Di menu **Umat**, gunakan baris filter:
- **Area** — tampilkan jemaat dari area tertentu.
- **Kelompok usia** — Anak (0–12), Remaja (13–17), Pemuda (18–30), Dewasa (di atas 30).
- **Bulan ulang tahun** — pilih bulan tertentu.
- Tombol **"Ulang tahun bulan ini"** — langsung menampilkan yang berulang tahun bulan berjalan.

Filter bisa digabung (mis. Pemuda di Area tertentu). Tombol **Atur ulang filter** mengosongkan kembali.

---

## 5. Ekspor Data ke Excel

**Apa yang berubah:** Data Umat bisa diunduh ke berkas Excel.

**Cara mencoba (Admin):**
1. Di menu **Umat**, atur filter sesuai kebutuhan (mis. hanya satu area).
2. Klik tombol **Ekspor Excel**. Berkas `.xlsx` akan terunduh dan **isinya mengikuti filter yang sedang aktif**.

---

## 6. Statistik & Demografi di Dashboard

**Apa yang berubah:** Halaman utama (Dashboard) kini menampilkan ringkasan kondisi jemaat.

**Cara mencoba:** Buka **Dashboard** dan perhatikan:
- **Pertumbuhan jemaat** — jumlah yang masuk dan keluar pada bulan & tahun ini.
- **Demografi usia** — sebaran jemaat aktif per kelompok usia.

---

## 7. Riwayat Perubahan Data

**Apa yang berubah:** Perubahan penting (alamat, area, status, penambahan jemaat) tercatat otomatis beserta **siapa** yang mengubah dan **kapan**.

**Cara mencoba:**
1. Ubah data seorang jemaat (mis. alamat atau area), lalu simpan.
2. Buka **Dashboard** → lihat panel **"Riwayat perubahan terbaru"**. Perubahan tadi akan tampil di bagian atas.

---

## 8. Tanda Tangan Gembala pada PDF Kartu Keluarga

**Apa yang berubah:** Tiap **Area** dapat menyimpan **nama gembala** dan **gambar tanda tangan**. Tanda tangan ini tampil pada kolom "Mengetahui" di PDF kartu keluarga, sementara tanda tangan **Kepala Keluarga tetap kosong** untuk ditandatangani manual.

**Cara mencoba:**
1. Sebagai Admin, buka menu **Area**, pilih/ubah sebuah area.
2. Isi **Gembala area** dan unggah **Gambar tanda tangan**. Simpan.
3. Buka menu **Keluarga**, pada keluarga yang anggotanya berada di area tersebut klik tombol **PDF**.
4. Pada dokumen, bagian **"Mengetahui"** akan memuat nama gembala dan gambar tanda tangannya; kolom **Kepala Keluarga** dibiarkan kosong.
