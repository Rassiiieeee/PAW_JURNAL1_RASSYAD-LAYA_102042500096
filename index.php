<?php
// ===== KONFIGURASI =====
$fileData = __DIR__ . "/pendaftar.json";

$daftarMatkul = [
    "Pengembangan Aplikasi Website",
    "Pemrograman Java",
    "Basis Data",
    "Rekayasa Perangkat Lunak",
    "Jaringan Komputer",
];

// ===== FUNGSI BANTU =====
function e($teks) {
    return htmlspecialchars($teks, ENT_QUOTES, "UTF-8");
}

function bacaData($fileData) {
    if (!file_exists($fileData)) return [];
    $isi = json_decode(file_get_contents($fileData), true);
    return is_array($isi) ? $isi : [];
}

function simpanData($fileData, $data) {
    file_put_contents($fileData, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

// ===== STATE =====
$halaman = isset($_GET["page"]) && $_GET["page"] === "data" ? "data" : "form";
$input   = ["nama" => "", "wa" => "", "email" => "", "matkul" => "", "motivasi" => ""];
$errors  = [];
$kartu   = null;

// ===== PROSES FORM (VALIDASI BACKEND) =====
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input["nama"]     = trim($_POST["nama"] ?? "");
    $input["wa"]       = trim($_POST["wa"] ?? "");
    $input["email"]    = trim($_POST["email"] ?? "");
    $input["matkul"]   = trim($_POST["matkul"] ?? "");
    $input["motivasi"] = trim($_POST["motivasi"] ?? "");

    // 1. Cek kolom kosong
    if ($input["nama"] === "")     $errors["nama"]     = "Nama lengkap tidak boleh kosong";
    if ($input["wa"] === "")       $errors["wa"]       = "Nomor WhatsApp tidak boleh kosong";
    if ($input["email"] === "")    $errors["email"]    = "Email institusi tidak boleh kosong";
    if ($input["matkul"] === "")   $errors["matkul"]   = "Pilihan mata kuliah praktikum wajib dipilih";
    if ($input["motivasi"] === "") $errors["motivasi"] = "Motivasi tidak boleh kosong";

    // 2. Cek format (hanya untuk kolom yang sudah terisi)
    if (!isset($errors["nama"]) && !preg_match("/^[\p{L}\s]+$/u", $input["nama"])) {
        $errors["nama"] = "Nama hanya boleh berupa huruf";
    }
    if (!isset($errors["wa"]) && !preg_match("/^(0|62)[0-9]{8,14}$/", $input["wa"])) {
        $errors["wa"] = "Nomor WhatsApp harus diawali 0 atau +62";
    }
    if (!isset($errors["email"]) && !filter_var($input["email"], FILTER_VALIDATE_EMAIL)) {
        $errors["email"] = "Format email institusi tidak valid";
    }
    if (!isset($errors["matkul"]) && !in_array($input["matkul"], $daftarMatkul, true)) {
        $errors["matkul"] = "Pilihan mata kuliah tidak valid";
    }

    // 3. Jika lolos semua validasi: simpan dan terbitkan Kartu Registrasi
    if (empty($errors)) {
        $semua = bacaData($fileData);
        $kartu = [
            "no_reg"   => "REG-" . date("Y") . "-" . str_pad(count($semua) + 1, 3, "0", STR_PAD_LEFT),
            "nama"     => $input["nama"],
            "wa"       => $input["wa"],
            "email"    => $input["email"],
            "matkul"   => $input["matkul"],
            "motivasi" => $input["motivasi"],
            "waktu"    => date("d-m-Y H:i"),
        ];
        $semua[] = $kartu;
        simpanData($fileData, $semua);
        $input = ["nama" => "", "wa" => "", "email" => "", "matkul" => "", "motivasi" => ""];
    }
}

$dataPendaftar = ($halaman === "data") ? bacaData($fileData) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Asisten Praktikum</title>
    <style>
        :root { --primary: #e03f3f; --primary-dark: #9e0000; --danger: #dc2626; --danger-bg: #fef2f2; --muted: #6b7280; --border: #d9dce3; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: "Segoe UI", Arial, sans-serif; min-height: 100vh; display: flex; justify-content: center; align-items: flex-start; padding: 40px 16px;
               background: url("Telkom University Background.WEBP") center / cover no-repeat fixed; }
        .box { background: #fff; width: 100%; max-width: 440px; border-radius: 16px; padding: 32px 28px; box-shadow: 0 20px 50px rgba(0,0,0,.35); }
        .box.lebar { max-width: 900px; }
        h1 { text-align: center; font-size: 1.3rem; }
        .sub { text-align: center; color: var(--muted); font-size: .8rem; margin-bottom: 22px; }
        label { display: block; font-size: .8rem; font-weight: 600; margin: 14px 0 6px; }
        label span { color: var(--danger); }
        input, select, textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font: inherit; font-size: .85rem; }
        input:focus, select:focus, textarea:focus { outline: 2px solid #c4b5fd; border-color: var(--primary); }
        textarea { resize: vertical; min-height: 90px; }
        .invalid { border-color: var(--danger); }
        .err { color: var(--danger); font-size: .72rem; margin-top: 4px; }
        .alert { background: var(--danger-bg); border: 1px solid #fecaca; color: #991b1b; padding: 10px 12px; border-radius: 8px; font-size: .8rem; margin-bottom: 6px; }
        .btn { display: block; width: 100%; text-align: center; text-decoration: none; padding: 11px; border-radius: 8px; font-weight: 700; font-size: .85rem; cursor: pointer; margin-top: 14px; }
        .btn-primary { background: var(--primary); color: #fff; border: none; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-outline { background: #fff; color: var(--primary); border: 1px solid var(--primary); }
        .btn-outline:hover { background: #f5f3ff; }

        /* Kartu registrasi */
        .sukses { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 10px 12px; border-radius: 8px; font-size: .8rem; margin-bottom: 14px; }
        .kartu dl { display: grid; grid-template-columns: 100px 1fr; gap: 0; font-size: .82rem; }
        .kartu dt, .kartu dd { padding: 9px 0; border-bottom: 1px solid #eee; }
        .kartu dt { color: var(--muted); }
        .kartu dd { word-break: break-word; }
        .badge-ok { display: inline-block; background: #dcfce7; color: #166534; font-size: .7rem; font-weight: 700; padding: 4px 14px; border-radius: 999px; }
        .center { text-align: center; margin-top: 14px; }
        .footer-kartu { text-align: center; color: var(--muted); font-size: .68rem; margin-top: 14px; }

        /* Tabel data */
        .tabel-wrap { overflow-x: auto; margin-top: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: .8rem; }
        th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; vertical-align: top; }
        th { background: #f5f3ff; color: var(--primary-dark); }

        @media (max-width: 480px) { .box { padding: 24px 18px; } .kartu dl { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php if ($halaman === "data"): ?>
    <!-- ===== HALAMAN DATA PENDAFTAR ===== -->
    <div class="box lebar">
        <h1>Data Pendaftar</h1>
        <p class="sub">Total pendaftar: <?= count($dataPendaftar); ?></p>

        <?php if (empty($dataPendaftar)): ?>
            <p style="text-align:center;color:var(--muted);">Belum ada data pendaftar.</p>
        <?php else: ?>
            <div class="tabel-wrap">
                <table>
                    <thead>
                        <tr><th>No. Reg</th><th>Nama</th><th>WhatsApp</th><th>Email</th><th>Mata Kuliah</th><th>Motivasi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dataPendaftar as $d): ?>
                            <tr>
                                <td><?= e($d["no_reg"]); ?></td>
                                <td><?= e($d["nama"]); ?></td>
                                <td><?= e($d["wa"]); ?></td>
                                <td><?= e($d["email"]); ?></td>
                                <td><?= e($d["matkul"]); ?></td>
                                <td><?= e($d["motivasi"]); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <a href="?" class="btn btn-primary">Kembali ke Formulir</a>
    </div>

<?php elseif ($kartu): ?>
    <!-- ===== KARTU REGISTRASI ===== -->
    <div class="box">
        <h1>Kartu Registrasi</h1>
        <p class="sub">Calon Asisten Praktikum Laboratorium</p>

        <div class="sukses"><strong>Berhasil!</strong> Data pendaftaran telah diterima.</div>

        <div class="kartu">
            <dl>
                <dt>Nama Lengkap</dt><dd><?= e($kartu["nama"]); ?></dd>
                <dt>No. WhatsApp</dt><dd><?= e($kartu["wa"]); ?></dd>
                <dt>Email Institusi</dt><dd><?= e($kartu["email"]); ?></dd>
                <dt>Mata Kuliah</dt><dd><?= e($kartu["matkul"]); ?></dd>
                <dt>Motivasi</dt><dd><?= nl2br(e($kartu["motivasi"])); ?></dd>
            </dl>
        </div>

        <div class="center"><span class="badge-ok">Pendaftaran Berhasil</span></div>

        <a href="?" class="btn btn-primary">Kembali ke Form</a>
        <p class="footer-kartu">Nomor Registrasi: <?= e($kartu["no_reg"]); ?> &bull; Dicetak otomatis oleh sistem</p>
    </div>

<?php else: ?>
    <!-- ===== FORMULIR ===== -->
    <div class="box">
        <h1>Pendaftaran Asisten Praktikum</h1>
        <p class="sub">Laboratorium Enterprise Application Development</p>

        <?php if (!empty($errors)): ?>
            <div class="alert"><strong>Pendaftaran gagal!</strong> Harap perbaiki data yang salah.</div>
        <?php endif; ?>

        <form method="POST" action=""    novalidate>
            <label for="nama">Nama Lengkap <span>*</span></label>
            <input type="text" id="nama" name="nama" placeholder="Contoh: Rassyad Laya"
                   value="<?= e($input["nama"]); ?>" class="<?= isset($errors["nama"]) ? "invalid" : ""; ?>">
            <?php if (isset($errors["nama"])): ?><div class="err"><?= e($errors["nama"]); ?></div><?php endif; ?>

            <label for="wa">Nomor WhatsApp <span>*</span></label>
            <input type="text" id="wa" name="wa" placeholder="Contoh: 085142012348"
                   value="<?= e($input["wa"]); ?>" class="<?= isset($errors["wa"]) ? "invalid" : ""; ?>">
            <?php if (isset($errors["wa"])): ?><div class="err"><?= e($errors["wa"]); ?></div><?php endif; ?>

            <label for="email">Email Institusi <span>*</span></label>
            <input type="text" id="email" name="email" placeholder="Contoh: rassyadlaya@student.telkomuniversity.ac.id"
                   value="<?= e($input["email"]); ?>" class="<?= isset($errors["email"]) ? "invalid" : ""; ?>">
            <?php if (isset($errors["email"])): ?><div class="err"><?= e($errors["email"]); ?></div><?php endif; ?>

            <label for="matkul">Mata Kuliah Praktikum <span>*</span></label>
            <select id="matkul" name="matkul" class="<?= isset($errors["matkul"]) ? "invalid" : ""; ?>">
                <option value="">-- Pilih Mata Kuliah --</option>
                <?php foreach ($daftarMatkul as $mk): ?>
                    <option value="<?= e($mk); ?>" <?= $input["matkul"] === $mk ? "selected" : ""; ?>><?= e($mk); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors["matkul"])): ?><div class="err"><?= e($errors["matkul"]); ?></div><?php endif; ?>

            <label for="motivasi">Motivasi Mendaftar <span>*</span></label>
            <textarea id="motivasi" name="motivasi" placeholder="Tuliskan alasan kamu ingin menjadi asisten praktikum..."
                      class="<?= isset($errors["motivasi"]) ? "invalid" : ""; ?>"><?= e($input["motivasi"]); ?></textarea>
            <?php if (isset($errors["motivasi"])): ?><div class="err"><?= e($errors["motivasi"]); ?></div><?php endif; ?>

            <button type="submit" class="btn btn-primary">Daftar Sekarang</button>
            <a href="?page=data" class="btn btn-outline">Lihat Data Pendaftar</a>
        </form>
    </div>
<?php endif; ?>

</body>
</html>
