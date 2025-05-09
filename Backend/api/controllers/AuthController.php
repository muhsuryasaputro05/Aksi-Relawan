<?php

// Memuat file konfigurasi database dari direktori config
// Nama file: database.php (lowercase d)
require_once __DIR__ . '/../config/database.php';

class AuthController {
    private $db;
    private $pdo;

    public function __construct() {
        $database = new Database();
        $this->pdo = $database->getConnection();

        // Tambahkan pengecekan jika koneksi gagal
        if ($this->pdo === null) {
            // Hentikan eksekusi atau lempar exception jika koneksi gagal
            // Ini akan menghasilkan respons error 500 yang lebih terkontrol
            http_response_code(503); // Service Unavailable
            echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
            exit; // Hentikan eksekusi controller
            // Atau: throw new Exception("Database connection failed");
        }
        // Properti $this->db mungkin tidak diperlukan lagi jika hanya butuh $pdo
        // $this->db = $database;
    }

    /**
     * Menangani registrasi pengguna baru.
     */
    public function register() {
        // 1. Ambil data JSON dari body request
        $data = json_decode(file_get_contents('php://input'), true);

        // 2. Validasi data (username, email, password)
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            http_response_code(400); // Bad Request
            return ['status' => 'error', 'message' => 'Username (Nama Lengkap), email, dan password wajib diisi'];
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Format email tidak valid'];
        }

        // 3. Cek apakah username atau email sudah terdaftar
        try {
            $sqlCheck = "SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1";
            $stmtCheck = $this->pdo->prepare($sqlCheck);
            $stmtCheck->bindParam(':username', $data['username']);
            $stmtCheck->bindParam(':email', $data['email']);
            $stmtCheck->execute();

            if ($stmtCheck->fetch()) {
                // Username atau email sudah ada
                http_response_code(409); // Conflict
                // Beri pesan yang lebih spesifik jika perlu (misal, cek mana yang duplikat)
                return ['status' => 'error', 'message' => 'Username atau Email sudah terdaftar'];
            }
        } catch (PDOException $e) {
            http_response_code(500); // Internal Server Error
            // Sebaiknya log error ini daripada menampilkannya ke user
            error_log("Database error check user: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Terjadi masalah saat memeriksa data pengguna'];
        }

        // 4. Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

        // 5. Simpan pengguna ke database
        $username = $data['username'];
        $email = $data['email'];
        // Kolom full_name akan NULL karena skema diubah jadi NULLABLE

        try {
            // Query INSERT tanpa menyertakan full_name
            $sqlInsert = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
            $stmtInsert = $this->pdo->prepare($sqlInsert);
            $stmtInsert->bindParam(':username', $username);
            $stmtInsert->bindParam(':email', $email);
            $stmtInsert->bindParam(':password', $hashedPassword);

            if ($stmtInsert->execute()) {
                http_response_code(201); // Created
                // Ambil ID user baru jika perlu: $newUserId = $this->pdo->lastInsertId();
                return ['status' => 'success', 'message' => 'Registrasi berhasil'];
            } else {
                http_response_code(500);
                // Ambil info error jika ada
                $errorInfo = $stmtInsert->errorInfo();
                error_log("Database error insert user: " . ($errorInfo[2] ?? 'Unknown error'));
                return ['status' => 'error', 'message' => 'Registrasi gagal, terjadi kesalahan saat menyimpan data'];
            }
        } catch (PDOException $e) {
            http_response_code(500);
            error_log("Database error insert user: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Terjadi masalah saat menyimpan pengguna baru'];
        }
    }

    /**
     * Menangani login pengguna.
     */
    public function login() {
        // 1. Ambil data JSON dari body request
        $data = json_decode(file_get_contents('php://input'), true);

        // 2. Validasi data (email, password)
        if (empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Email dan password wajib diisi'];
        }

        // 3. TODO: Cari pengguna berdasarkan email

        // 4. TODO: Verifikasi password

        // 5. TODO: Generate token (misalnya JWT)

        // 6. Berikan response sukses (sementara)
        return ['status' => 'success', 'message' => 'Login berhasil (implementasi database dan token belum selesai)'];
    }
}
?> 