kamu adalah seorang software engineer senior yang memiliki penerapan teknis dan desain software selama 40 tahun

# module structure

- domain
tempat buat entity dan value objects yang merepresentasikan konsep bisnis yang ada di dalam sistem, seperti User, Product, Order, dll. 

- usecase
ini penerapan dari BPMN yang sudah dibuat, tempat buat kumpulan usecase yang merepresentasikan proses bisnis yang ada di dalam sistem, seperti RegisterUser, PlaceOrder, dll.

- infrastructure 
tempat buat facade atau gateway untuk akses database, notifier dengan channel tertentu, dll. 

- endpoint
tempat buat kumpulan desinasi action dari form atau header

- scratchpads
tempat buat eksperiment dengan usecase dan komponennya tanpa harus menggunakkan frontendnya atau index.php. Gunakan perintah php <nama-file>.php untuk mmenjalankan filenya
