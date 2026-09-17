<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Menjalankan SIMON

SIMON memakai Laravel, Inertia React/TypeScript, MySQL dan Tailwind. Status implementasi serta batas verifikasi dicatat dalam [audit perbaikan](docs/AUDIT_PERBAIKAN_SIMON.md). Belum dinyatakan siap produksi.

Jangan jalankan `migrate:fresh` atau seeder impor pada database berisi aset nyata. Simpan kredensial hanya di `.env`, bukan repository. Setelah dependensi tersedia dan konfigurasi database benar:

```sh
php artisan migrate --no-interaction
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Foto diproses asinkron. Jalankan worker dan scheduler pada terminal terpisah; keduanya perlu pengelola proses pada server produksi:

```sh
php artisan queue:work --queue=media,default --tries=3 --timeout=60
php artisan schedule:work
```

Tanpa worker, foto tetap berstatus menunggu dan belum dapat dibuka. Scheduler memulihkan antrean foto yang belum terkirim dan membuat pengingat inbox setiap 08.00 WIB. Pengingat tidak mengirim email. Sumber foto disimpan privat; WebP dan thumbnail hanya dibuka setelah pemrosesan berhasil. Foto gagal dapat dicoba ulang oleh pengunggah yang masih berwenang.

`SIMON_SCANNER=development` hanya untuk lokal/testing dan **tidak melakukan pemeriksaan antivirus**. Produksi memerlukan `SIMON_SCANNER=clamav`, executable `SIMON_SCANNER_BINARY` yang benar, serta basis signature ClamAV terbarui. Jika pemindai tidak tersedia, unggahan tidak diloloskan. Jangan menandai template resmi dengan `SIMON_TEMPLATES_APPROVED=true` sebelum formatnya disahkan instansi.

Email `MAIL_MAILER=log` tidak mengirim ke inbox. Konfigurasikan provider email sebelum menguji aktivasi/verifikasi/reset sungguhan. Jangan menonaktifkan kewajiban MFA PJ/Koordinator untuk mengatasi konfigurasi email. Cocokkan batas unggahan PHP/web server dengan batas aplikasi (10 MB per berkas, maksimal 4 foto per pengajuan bukti).

Verifikasi perubahan:

```sh
php artisan test --compact
npm run build
php vendor/bin/pint --dirty --format agent
```

Suite memakai SQLite in-memory; ini bukan pengganti uji konkurensi MySQL, backup/restore, atau uji penerimaan tiga role.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
