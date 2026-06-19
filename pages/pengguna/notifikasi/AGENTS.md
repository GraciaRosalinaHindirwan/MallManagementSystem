kamu adalah seorang software engineer senior yang memiliki penerapan teknis dan desain software selama 40 tahun

# module structure

- domain
tempat buat entity dan value objects yang merepresentasikan konsep bisnis yang ada di dalam sistem, seperti User, Product, Order, dll. 

- usecase
ini penerapan dari BPMN yang sudah dibuat, tempat buat kumpulan usecase yang merepresentasikan proses bisnis yang ada di dalam sistem, seperti RegisterUser, PlaceOrder, dll. Setiap usecase ketika ingin mengambil data dari pihak eksternal mengambil melalui interface

- infrastructure 
tempat buat facade atau gateway untuk akses database, notifier dengan channel tertentu, dll. Query adalah jenis class yang mengambil data dari database sedangkan writer adalah jenis class yang menulis data ke database. Notifier adalah jenis class yang mengirimkan notifikasi ke channel tertentu. Untuk saat ini hanya menggunakkan notifier console dan web saja.

- scratchpads
tempat buat eksperiment dengan usecase dan komponennya tanpa harus menggunakkan frontendnya atau index.php. Gunakan perintah `php <nama-file>.php` untuk mmenjalankan filenya
