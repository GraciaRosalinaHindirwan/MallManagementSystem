Berikut versi **README.md siap GitHub (clean, to-the-point, profesional)** untuk Notification Service kamu.

---

```md
# 🔔 Notification Center Module (In-App Event-Based)

Sistem notifikasi ini adalah modul **in-app notification berbasis event** untuk aplikasi Mall Management System.

---

## 🎯 Tujuan

Memberikan notifikasi kepada user ketika terjadi event pada sistem, seperti:

- Payment Success
- Payment Due
- Payment Failed

Semua notifikasi ditampilkan dalam **in-app notification center (web inbox)**.

---

## 🧠 Arsitektur Singkat

```

Payment Module
↓
NotificationService (CONTRACT LAYER)
↓
Domain (NotificationLog)
↓
Writer (MySQL In-App Writer)
↓
Database (08_notification_logs)
↓
UI (index.php / detail.php)

````

---

## 🚀 Cara Integrasi (WAJIB)

Modul lain **cukup memanggil service ini**:

```php
$notificationService->handlePaymentEvent($payload);
````

---

## 📦 Format Payload

```php
$payload = [
    'type' => 'payment_success', // payment_success | payment_due | payment_failed
    'user_id' => 1,
    'email' => 'user@mail.com',
    'name' => 'Budi',
    'invoice' => 'INV-001',
    'amount' => 150000,       // optional (payment_due)
    'due_date' => '2026-06-30' // optional (payment_due)
];
```

---

## ⚡ Supported Events

| Event Type      | Deskripsi            |
| --------------- | -------------------- |
| payment_success | Pembayaran berhasil  |
| payment_due     | Reminder jatuh tempo |
| payment_failed  | Pembayaran gagal     |

---

## 📤 Output Sistem

Setiap event akan menghasilkan:

### ✔ Database Insert

Tabel:

```
08_notification_logs
```

### ✔ Data yang disimpan:

* user_id
* recipient_email
* recipient_name
* notification_type
* subject
* message
* channel (inapp)
* status (pending)

### ✔ Return Value (optional)

```php
NotificationLog object
```

---

## 🔁 Flow Sistem

```
Event dari Payment Module
        ↓
NotificationService
        ↓
Validation + Routing
        ↓
NotificationLog (Domain)
        ↓
MySQL Writer
        ↓
Database
        ↓
Notification Center UI
```

---

## ❌ Aturan Penting

Modul lain **DILARANG**:

* ❌ Insert langsung ke tabel notification
* ❌ Panggil writer langsung
* ❌ Membuat query notification sendiri
* ❌ Membuat template notification sendiri

---

## ✔ Aturan Benar

Modul lain hanya:

* ✔ Kirim payload event
* ✔ Panggil NotificationService
* ✔ Tidak tahu struktur internal notification

---

## 🧠 Responsibility

| Layer               | Tanggung Jawab           |
| ------------------- | ------------------------ |
| Payment Module      | Trigger event            |
| NotificationService | Build notification logic |
| Domain              | Data structure           |
| Writer              | Database persistence     |
| UI                  | Display inbox            |

---

## 🖥️ UI Module

### Inbox

```
index.php
```

### Detail Notification

```
detail.php?id={id}
```

---

## 🔐 Security Rules

* User hanya bisa melihat notifikasi miliknya (`user_id`)
* Semua input divalidasi melalui DTO
* Tidak ada akses langsung ke data user lain

---

## 🧪 Example Usage (Payment Success)

```php
$notificationService->handlePaymentEvent([
    'type' => 'payment_success',
    'user_id' => 1,
    'email' => 'test@mail.com',
    'name' => 'Budi',
    'invoice' => 'INV-001'
]);
```

---

## 📊 Status Project

* ✔ Event-driven architecture
* ✔ Multi-user support
* ✔ In-app notification system
* ✔ Contract-based integration
* ✔ Production-ready (demo safe)

---

## 🚀 Summary

> Modul lain hanya mengirim event.
> NotificationService yang membangun dan menyimpan notifikasi.

---

```

---

Kalau kamu mau, aku bisa lanjutkan ke:

👉 :contentReference[oaicite:0]{index=0}  
👉 atau **:contentReference[oaicite:1]{index=1}**  
👉 atau **:contentReference[oaicite:2]{index=2}**
```
