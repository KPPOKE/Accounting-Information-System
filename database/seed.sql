USE finacore_db;

INSERT INTO roles (name, description) VALUES
('Admin', 'Administrator dengan akses penuh ke seluruh sistem'),
('Akuntan', 'Mengelola jurnal umum dan laporan akuntansi'),
('Staff Keuangan', 'Mengelola transaksi kas masuk dan keluar'),
('Auditor', 'Melihat dan mengaudit seluruh laporan'),
('Manajer Keuangan', 'Approval transaksi dan monitoring laporan');

INSERT INTO permissions (name, module, action, description) VALUES
('users_view', 'users', 'view', 'Melihat data pengguna'),
('users_create', 'users', 'create', 'Membuat pengguna baru'),
('users_edit', 'users', 'edit', 'Mengedit data pengguna'),
('users_delete', 'users', 'delete', 'Menghapus pengguna'),
('roles_manage', 'roles', 'manage', 'Mengelola role dan permission'),
('accounts_view', 'accounts', 'view', 'Melihat chart of accounts'),
('accounts_create', 'accounts', 'create', 'Membuat akun baru'),
('accounts_edit', 'accounts', 'edit', 'Mengedit akun'),
('accounts_delete', 'accounts', 'delete', 'Menghapus akun'),
('journal_view', 'journal', 'view', 'Melihat jurnal umum'),
('journal_create', 'journal', 'create', 'Membuat jurnal baru'),
('journal_edit', 'journal', 'edit', 'Mengedit jurnal'),
('journal_delete', 'journal', 'delete', 'Menghapus jurnal'),
('journal_approve', 'journal', 'approve', 'Menyetujui jurnal'),
('cash_view', 'cash', 'view', 'Melihat transaksi kas'),
('cash_create', 'cash', 'create', 'Membuat transaksi kas'),
('cash_edit', 'cash', 'edit', 'Mengedit transaksi kas'),
('cash_delete', 'cash', 'delete', 'Menghapus transaksi kas'),
('reports_view', 'reports', 'view', 'Melihat laporan'),
('reports_export', 'reports', 'export', 'Export laporan'),
('logs_view', 'logs', 'view', 'Melihat activity logs');

INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE module IN ('accounts', 'journal', 'reports') OR name = 'logs_view';

INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE module IN ('cash', 'reports') AND action IN ('view', 'create', 'edit');

INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE action = 'view' OR name = 'reports_export';

INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions WHERE action IN ('view', 'approve') OR name = 'reports_export';

INSERT INTO users (username, email, password, full_name, role_id) VALUES
('admin', 'admin@finacore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 1),
('akuntan1', 'akuntan@finacore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budi Santoso', 2),
('staff1', 'staff@finacore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Siti Rahayu', 3),
('auditor1', 'auditor@finacore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ahmad Wijaya', 4),
('manajer1', 'manajer@finacore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dewi Lestari', 5);

INSERT INTO account_categories (name, code, type, normal_balance) VALUES
('Kas & Bank', '1', 'aset', 'debit'),
('Piutang', '2', 'aset', 'debit'),
('Persediaan', '3', 'aset', 'debit'),
('Aset Tetap', '4', 'aset', 'debit'),
('Utang Jangka Pendek', '5', 'liabilitas', 'credit'),
('Utang Jangka Panjang', '6', 'liabilitas', 'credit'),
('Modal', '7', 'ekuitas', 'credit'),
('Pendapatan Usaha', '8', 'pendapatan', 'credit'),
('Beban Operasional', '9', 'beban', 'debit'),
('Beban Lain-lain', '10', 'beban', 'debit');

INSERT INTO accounts (code, name, category_id, opening_balance, current_balance, description) VALUES
('1-1001', 'Kas Kecil', 1, 5000000.00, 5000000.00, 'Kas kecil untuk operasional harian'),
('1-1002', 'Bank BCA', 1, 150000000.00, 150000000.00, 'Rekening utama perusahaan'),
('1-1003', 'Bank Mandiri', 1, 75000000.00, 75000000.00, 'Rekening operasional'),
('2-1001', 'Piutang Usaha', 2, 25000000.00, 25000000.00, 'Piutang dari pelanggan'),
('2-1002', 'Piutang Karyawan', 2, 0.00, 0.00, 'Pinjaman karyawan'),
('3-1001', 'Persediaan Barang Dagang', 3, 100000000.00, 100000000.00, 'Stok barang dagangan'),
('4-1001', 'Peralatan Kantor', 4, 50000000.00, 50000000.00, 'Komputer, printer, furniture'),
('4-1002', 'Kendaraan', 4, 200000000.00, 200000000.00, 'Kendaraan operasional'),
('4-1003', 'Akumulasi Penyusutan', 4, -25000000.00, -25000000.00, 'Akumulasi penyusutan aset'),
('5-1001', 'Utang Usaha', 5, 30000000.00, 30000000.00, 'Utang kepada supplier'),
('5-1002', 'Utang Gaji', 5, 0.00, 0.00, 'Gaji yang belum dibayar'),
('5-1003', 'Utang Pajak', 5, 5000000.00, 5000000.00, 'Pajak yang belum dibayar'),
('6-1001', 'Utang Bank', 6, 100000000.00, 100000000.00, 'Pinjaman bank jangka panjang'),
('7-1001', 'Modal Disetor', 7, 400000000.00, 400000000.00, 'Modal awal pemegang saham'),
('7-1002', 'Laba Ditahan', 7, 45000000.00, 45000000.00, 'Laba yang tidak dibagikan'),
('8-1001', 'Pendapatan Penjualan', 8, 0.00, 0.00, 'Pendapatan dari penjualan'),
('8-1002', 'Pendapatan Jasa', 8, 0.00, 0.00, 'Pendapatan dari jasa'),
('8-1003', 'Pendapatan Lain-lain', 8, 0.00, 0.00, 'Pendapatan diluar usaha'),
('9-1001', 'Beban Gaji', 9, 0.00, 0.00, 'Gaji karyawan'),
('9-1002', 'Beban Listrik & Air', 9, 0.00, 0.00, 'Biaya utilitas'),
('9-1003', 'Beban Sewa', 9, 0.00, 0.00, 'Biaya sewa kantor'),
('9-1004', 'Beban Perlengkapan', 9, 0.00, 0.00, 'Biaya perlengkapan kantor'),
('9-1005', 'Beban Penyusutan', 9, 0.00, 0.00, 'Beban penyusutan aset'),
('10-1001', 'Beban Bunga', 10, 0.00, 0.00, 'Bunga pinjaman'),
('10-1002', 'Beban Administrasi Bank', 10, 0.00, 0.00, 'Biaya admin bank');

INSERT INTO journal_entries (entry_number, entry_date, description, total_amount, created_by, approved_by, status) VALUES
('JU-202412-0001', '2024-12-01', 'Penjualan barang dagangan kepada PT ABC', 15000000.00, 2, 1, 'approved'),
('JU-202412-0002', '2024-12-02', 'Pembayaran gaji karyawan bulan November', 25000000.00, 2, 1, 'approved'),
('JU-202412-0003', '2024-12-03', 'Pembelian perlengkapan kantor', 2500000.00, 2, NULL, 'pending'),
('JU-202412-0004', '2024-12-05', 'Penerimaan pembayaran piutang dari pelanggan', 10000000.00, 2, 1, 'approved');

INSERT INTO journal_details (journal_entry_id, account_id, debit, credit, description) VALUES
(1, 2, 15000000.00, 0.00, 'Piutang dari penjualan'),
(1, 16, 0.00, 15000000.00, 'Pendapatan penjualan'),
(2, 19, 25000000.00, 0.00, 'Beban gaji November'),
(2, 2, 0.00, 25000000.00, 'Pembayaran via Bank BCA'),
(3, 22, 2500000.00, 0.00, 'Perlengkapan kantor'),
(3, 1, 0.00, 2500000.00, 'Pembayaran tunai'),
(4, 2, 10000000.00, 0.00, 'Terima dari Bank BCA'),
(4, 4, 0.00, 10000000.00, 'Pelunasan piutang');

INSERT INTO cash_transactions (transaction_number, transaction_date, type, account_id, amount, description, created_by, status) VALUES
('KM-202412-0001', '2024-12-01', 'masuk', 2, 50000000.00, 'Setoran modal tambahan', 3, 'approved'),
('KK-202412-0001', '2024-12-02', 'keluar', 2, 5000000.00, 'Pengisian kas kecil', 3, 'approved'),
('KM-202412-0002', '2024-12-03', 'masuk', 1, 3000000.00, 'Penjualan tunai', 3, 'approved'),
('KK-202412-0002', '2024-12-04', 'keluar', 1, 500000.00, 'Beli ATK', 3, 'pending'),
('KK-202412-0003', '2024-12-05', 'keluar', 2, 15000000.00, 'Pembayaran supplier', 3, 'approved');

INSERT INTO activity_logs (user_id, action, module, record_id, description, ip_address) VALUES
(1, 'login', 'auth', NULL, 'User admin berhasil login', '127.0.0.1'),
(2, 'create', 'journal', 1, 'Membuat jurnal JU-202412-0001', '127.0.0.1'),
(1, 'approve', 'journal', 1, 'Menyetujui jurnal JU-202412-0001', '127.0.0.1'),
(3, 'create', 'cash', 1, 'Membuat transaksi KM-202412-0001', '127.0.0.1'),
(2, 'create', 'journal', 2, 'Membuat jurnal JU-202412-0002', '127.0.0.1');
