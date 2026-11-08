<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Heaven - A Place of Luxury</title>
    <script>document.documentElement.classList.add('js')</script>

    <!-- Google Fonts: Bricolage Grotesque (judul) + Figtree (teks) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --brand-primary: #3368A0;   /* Biru Utama */
            --brand-secondary: #66A3BF; /* Biru Muda */
            --brand-mint: #C8DFDB;      /* Mint Soft */
            --brand-bg: #F2EFE7;        /* Cream / Off-white */

            /* Hubungkan ke variabel Bootstrap */
            --bs-body-font-family: 'Figtree', system-ui, sans-serif;
            --bs-body-color: #1f2937;
            --bs-body-bg: #F2EFE7;
        }

        html { scroll-behavior: smooth; }
        /*Supaya judul section tidak tertutup navbar sticky */
        section[id] { scroll-margin-top: 80px; }

        /*Semua judul memakai Bricolage Grotesque */
        h1, h2, h3, h4, h5, h6, .font-brand {
            font-family: "Bricolage Grotesque", system-ui, sans-serif;
        }

        /* Warna brand */
        .text-brand { color: var(--brand-primary) !important; }
        .bg-brand { background-color: var(--brand-primary) !important; }
        .bg-mint { background-color: var(--brand-mint) !important; }
        .bg-cream { background-color: var(--brand-bg) !important; }
        .border-mint { border-color: var(--brand-mint) !important; }

        /* Ukuran teks kecil */
        .text-sm { font-size: .875rem; }
        .text-xs { font-size: .75rem; }

        /* Tombol brand */
        .btn-brand {
            background-color: var(--brand-primary);
            border: 1px solid var(--brand-primary);
            color: #fff;
            transition: background-color .2s, border-color .2s;
        }
        .btn-brand:hover, .btn-brand:focus {
            background-color: var(--brand-secondary);
            border-color: var(--brand-secondary);
            color: #fff;
        }
        .btn-brand-outline {
            background-color: transparent;
            border: 1px solid var(--brand-primary);
            color: var(--brand-primary);
            transition: background-color .2s;
        }
        .btn-brand-outline:hover, .btn-brand-outline:focus {
            background-color: var(--brand-mint);
            border-color: var(--brand-primary);
            color: var(--brand-primary);
        }
        .btn-brand-light {
            background-color: rgba(255,255,255,.9);
            border: 1px solid var(--brand-secondary);
            color: var(--brand-primary);
            transition: background-color .2s;
        }
        .btn-brand-light:hover, .btn-brand-light:focus {
            background-color: var(--brand-mint);
            border-color: var(--brand-secondary);
            color: var(--brand-primary);
        }

        /* Navbar */
        .navbar .nav-link {
            color: #374151;
            font-size: .875rem;
            font-weight: 500;
            transition: color .2s;
        }
        .navbar .nav-link:hover { color: var(--brand-primary); }

        /* Label kecil (badge) */
        .badge-soft {
            display: inline-block;
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--brand-primary);
            background-color: var(--brand-mint);
            padding: .375rem .75rem;
            border-radius: 50rem;
        }

        /* Hero */
        .hero {
            position: relative;
            background: url('config/assets/images/kucing.jpg') center / cover no-repeat;
            padding: 5rem 0;
        }
        @media (min-width: 768px) { .hero { padding: 8rem 0; } }
        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(242, 239, 231, .85);
        }
        .hero > .container { position: relative; z-index: 1; }

        /* Ikon layanan */
        .icon-circle {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background-color: var(--brand-mint);
            color: var(--brand-primary);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Kartu kamar */
        .room-card {
            border-radius: 1rem;
            overflow: hidden;
            transition: box-shadow .2s;
        }
        .room-card:hover { box-shadow: 0 .5rem 1rem rgba(0,0,0,.12) !important; }
        .room-img { height: 192px; width: 100%; object-fit: cover; }

        /* Kartu testimoni */
        .testi-card {
            background-color: var(--brand-bg);
            border: 1px solid var(--brand-mint);
            border-radius: 1rem;
            overflow: hidden;
        }
        .testi-card img { height: 192px; width: 100%; object-fit: cover; }
        .rating { color: #fbbf24; font-size: .875rem; }

        /* Kontak di footer */
        .contact-link {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            color: #fff;
            text-decoration: none;
            font-size: .875rem;
            transition: color .2s;
        }
        .contact-link:hover { color: var(--brand-mint); }
        .contact-link img {
            width: 28px;
            height: 28px;
            object-fit: cover;
            border-radius: 50%;
        }

        /* Animasi muncul saat scroll */
        .js .reveal { opacity: 0; transform: translateY(32px); transition: opacity .7s ease, transform .7s ease; }
        .js .reveal.show { opacity: 1; transform: none; }
        @media (prefers-reduced-motion: reduce) {
            .js .reveal { opacity: 1; transform: none; transition: none; }
        }
    </style>
</head>
<body>

    <!-- ================= NAVBAR ================= -->
    <header class="sticky-top bg-white shadow-sm">
        <nav class="navbar navbar-expand-md py-2">
            <div class="container">
                <!-- Logo Brand -->
                <a class="navbar-brand d-flex align-items-center gap-2 m-0" href="#dashboard">
                    <img src="config/assets/images/logo.png" alt="Pet Heaven Logo" style="height:56px; width:auto; object-fit:contain;">
                    <span class="font-brand fs-5 fw-bold text-brand">Pet Heaven</span>
                </a>

                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Buka menu">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navMenu">
                    <!-- Navigasi Menu -->
                    <ul class="navbar-nav mx-auto gap-md-3">
                        <li class="nav-item"><a class="nav-link" href="#dashboard">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="#services">Layanan</a></li>
                        <li class="nav-item"><a class="nav-link" href="#rooms">Kamar</a></li>
                        <li class="nav-item"><a class="nav-link" href="#gallery">Galeri &amp; Testimoni</a></li>
                        <li class="nav-item"><a class="nav-link" href="user/form_reservasi.php">Reservasi</a></li>
                    </ul>

                    <!-- Tombol Aksi (Login & Daftar) -->
                    <div class="d-flex align-items-center gap-2 text-sm pb-2 pb-md-0">
                        <a href="login.php" class="btn btn-brand-outline btn-sm rounded-pill px-4 fw-semibold">Login</a>
                        <a href="daftar.php" class="btn btn-brand btn-sm rounded-pill px-4 fw-semibold shadow-sm">Daftar</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- ================= HERO SECTION ================= -->
    <section id="dashboard" class="hero">
        <div class="container text-center text-md-start">
            <div class="reveal" style="max-width: 42rem;">
                <span class="badge-soft mb-3">A Place of Luxury</span>

                <h1 class="display-5 fw-bolder text-brand lh-sm">
                    Penitipan &amp; Perawatan Terbaik untuk Anabul Kesayangan
                </h1>

                <p class="mt-3 fs-5 fw-medium text-secondary-emphasis">
                    Memberikan kenyamanan, keamanan, dan kasih sayang layaknya di rumah sendiri selama Anda bepergian.
                </p>

                <div class="mt-4 d-flex flex-wrap justify-content-center justify-content-md-start gap-3">
                    <a href="user/form_reservasi.php" class="btn btn-brand btn-lg fw-bold d-inline-flex align-items-center gap-2 shadow">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span>Form Reservasi</span>
                    </a>
                    <a href="#services" class="btn btn-brand-light btn-lg fw-bold">
                        Lihat Layanan
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= LAYANAN (Background: Putih) ================= -->
    <section id="services" class="py-5 bg-white">
        <div class="container py-3">
            <div class="reveal text-center mb-5">
                <span class="badge-soft">Fasilitas Utama</span>
                <h2 class="fw-bold text-brand mt-3 fs-1">Layanan Terbaik Kami</h2>
                <p class="text-secondary mt-2 mb-0">Berbagai perawatan dan kenyamanan ekstra untuk anabul kesayangan Anda.</p>
            </div>

            <div class="row g-4">
                <!-- Grooming -->
                <div class="col-sm-6 col-md-3">
                    <div class="reveal p-3 text-center">
                        <div class="icon-circle mx-auto mb-3">
                            <i class="fa-solid fa-shower"></i>
                        </div>
                        <h3 class="fs-5 fw-bold text-brand">Grooming &amp; Salon</h3>
                        <p class="text-sm text-secondary mt-2 mb-0">Mandi sehat, potong kuku, bersihkan telinga, hingga styling bulu anabul.</p>
                    </div>
                </div>

                <!-- Cek Kesehatan -->
                <div class="col-sm-6 col-md-3">
                    <div class="reveal p-3 text-center">
                        <div class="icon-circle mx-auto mb-3">
                            <i class="fa-solid fa-user-doctor"></i>
                        </div>
                        <h3 class="fs-5 fw-bold text-brand">Cek Dokter Hewan</h3>
                        <p class="text-sm text-secondary mt-2 mb-0">Pemeriksaan rutin dan pemberian vitamin oleh dokter hewan berpengalaman.</p>
                    </div>
                </div>

                <!-- Pet Taxi -->
                <div class="col-sm-6 col-md-3">
                    <div class="reveal p-3 text-center">
                        <div class="icon-circle mx-auto mb-3">
                            <i class="fa-solid fa-truck-pickup"></i>
                        </div>
                        <h3 class="fs-5 fw-bold text-brand">Antar - Jemput</h3>
                        <p class="text-sm text-secondary mt-2 mb-0">Layanan penjemputan &amp; pengantaran anabul dari rumah Anda secara aman.</p>
                    </div>
                </div>

                <!-- Pet Daycare -->
                <div class="col-sm-6 col-md-3">
                    <div class="reveal p-3 text-center">
                        <div class="icon-circle mx-auto mb-3">
                            <i class="fa-solid fa-bone"></i>
                        </div>
                        <h3 class="fs-5 fw-bold text-brand">Pet Daycare</h3>
                        <p class="text-sm text-secondary mt-2 mb-0">Penitipan harian saat Anda bekerja dengan area bermain dan snack gratis.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= JENIS KAMAR (Background: Cream) ================= -->
    <section id="rooms" class="py-5 bg-cream">
        <div class="container py-3">
            <div class="reveal text-center mb-5">
                <h2 class="fw-bold text-brand fs-1">Jenis Kamar Kami</h2>
                <p class="text-secondary mt-2 mb-0">Pilihan akomodasi nyaman yang disesuaikan dengan kebutuhan anabul.</p>
            </div>

            <div class="row g-4">
                <!-- Kamar Standard -->
                <div class="col-md-4">
                    <div class="reveal room-card bg-white border shadow-sm h-100">
                        <img src="config/assets/images/standart.jpeg" alt="Standard Room" class="room-img">
                        <div class="p-4">
                            <h3 class="fs-4 fw-bold text-brand">Standard Room</h3>
                            <p class="text-sm text-secondary mt-2">Kamar bersih, AC 24 jam, dan makanan berkualitas 2x sehari.</p>
                            <div class="mt-3">
                                <span class="fs-5 fw-bold text-brand">Rp 150.000 <span class="text-xs text-secondary fw-normal">/malam</span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kamar Deluxe / VIP -->
                <div class="col-md-4">
                    <div class="reveal room-card bg-white border shadow-sm h-100">
                        <img src="config/assets/images/vip.jpeg" alt="VIP Room" class="room-img">
                        <div class="p-4">
                            <h3 class="fs-4 fw-bold text-brand">VIP Room</h3>
                            <p class="text-sm text-secondary mt-2">Fasilitas CCTV live stream, area bermain khusus, dan grooming gratis.</p>
                            <div class="mt-3">
                                <span class="fs-5 fw-bold text-brand">Rp 250.000 <span class="text-xs text-secondary fw-normal">/malam</span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kamar Suite / VVIP -->
                <div class="col-md-4">
                    <div class="reveal room-card bg-white border shadow-sm h-100">
                        <img src="config/assets/images/vvip.jpeg" alt="VVIP Room" class="room-img">
                        <div class="p-4">
                            <h3 class="fs-4 fw-bold text-brand">VVIP Room</h3>
                            <p class="text-sm text-secondary mt-2">Ruangan sangat luas, kasur empuk mewah, akses playground pribadi.</p>
                            <div class="mt-3">
                                <span class="fs-5 fw-bold text-brand">Rp 350.000 <span class="text-xs text-secondary fw-normal">/malam</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= GALERI & TESTIMONI ================= -->
    <section id="gallery" class="py-5 bg-white">
        <div class="container py-3">
            <div class="reveal text-center mb-5">
                <h2 class="fw-bold text-brand fs-1">Galeri &amp; Testimoni Tamu Menginap</h2>
                <p class="text-secondary mt-2 mb-0">Cerita dari pemilik yang sudah menitipkan anabulnya di Pet Heaven.</p>
            </div>

            <div class="row g-4">
                <div class="col-sm-6 col-lg-3">
                    <figure class="reveal testi-card d-flex flex-column h-100 m-0">
                        <img src="config/assets/images/testimoni1.jpg" alt="Kucing menginap di Pet Heaven">
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="rating d-flex gap-1 mb-3" data-rating="3"></div>
                            <blockquote class="text-sm text-secondary-emphasis flex-grow-1 mb-0" style="line-height:1.65;">
                                &ldquo;Waktu saya pulang dari luar kota, kucing saya tetap makan lahap dan kelihatan santai. Dikirimi foto tiap hari jadi saya tidak khawatir.&rdquo;
                            </blockquote>
                            <figcaption class="mt-3 pt-3 border-top border-mint">
                                <p class="font-brand fw-bold text-brand mb-0">Milo</p>
                                <p class="text-xs text-secondary mb-0">Menginap 5 malam, Standard Room</p>
                            </figcaption>
                        </div>
                    </figure>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <figure class="reveal testi-card d-flex flex-column h-100 m-0">
                        <img src="config/assets/images/testimoni2.jpg" alt="Anjing selesai grooming">
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="rating d-flex gap-1 mb-3" data-rating="5"></div>
                            <blockquote class="text-sm text-secondary-emphasis flex-grow-1 mb-0" style="line-height:1.65;">
                                &ldquo;Bulunya jadi wangi dan rapi. Petugasnya sabar, anjing saya yang biasanya takut dimandikan malah tenang.&rdquo;
                            </blockquote>
                            <figcaption class="mt-3 pt-3 border-top border-mint">
                                <p class="font-brand fw-bold text-brand mb-0">Nenes</p>
                                <p class="text-xs text-secondary mb-0">Grooming &amp; Salon</p>
                            </figcaption>
                        </div>
                    </figure>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <figure class="reveal testi-card d-flex flex-column h-100 m-0">
                        <img src="config/assets/images/testimoni4.png" alt="Kucing dipotong kukunya">
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="rating d-flex gap-1 mb-3" data-rating="5"></div>
                            <blockquote class="text-sm text-secondary-emphasis flex-grow-1 mb-0" style="line-height:1.65;">
                                &ldquo;Kuku dipotong dan telinga dibersihkan dengan hati-hati. Dokter hewannya menjelaskan kondisi kucing saya dengan jelas.&rdquo;
                            </blockquote>
                            <figcaption class="mt-3 pt-3 border-top border-mint">
                                <p class="font-brand fw-bold text-brand mb-0">Milkshake</p>
                                <p class="text-xs text-secondary mb-0">Cek Dokter Hewan</p>
                            </figcaption>
                        </div>
                    </figure>
                </div>

                <div class="col-sm-6 col-lg-3">
                    <figure class="reveal testi-card d-flex flex-column h-100 m-0">
                        <img src="config/assets/images/testimoni6.jpg" alt="Anak anjing dirawat petugas">
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="rating d-flex gap-1 mb-3" data-rating="3"></div>
                            <blockquote class="text-sm text-secondary-emphasis flex-grow-1 mb-0" style="line-height:1.65;">
                                &ldquo;Saya titip anak anjing saya dari pagi sampai sore. Pulang kerja dia capek karena puas bermain.&rdquo;
                            </blockquote>
                            <figcaption class="mt-3 pt-3 border-top border-mint">
                                <p class="font-brand fw-bold text-brand mb-0">Ciro</p>
                                <p class="text-xs text-secondary mb-0">Pet Daycare</p>
                            </figcaption>
                        </div>
                    </figure>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= FOOTER ================= -->
    <footer class="bg-brand text-white py-4">
        <div class="reveal container">
            <div class="row align-items-center g-4">
                <!-- Kiri: nama brand -->
                <div class="col-md-5 text-center text-md-start">
                    <p class="font-brand fw-bold fs-5 mb-0">Pet Heaven</p>
                    <p class="text-sm mb-0" style="color: var(--brand-mint);">A Place of Luxury for Your Pets</p>
                </div>

                <!-- Kanan: Contact Us -->
                <div class="col-md-7">
                    <p class="font-brand fw-bold mb-3 text-center text-md-start">Contact Us</p>
                    <div class="d-flex flex-wrap justify-content-center justify-content-md-start gap-3 gap-md-4">
                        <a href="https://wa.me/6285198177701?s=t" target="_blank" rel="noopener" class="contact-link">
                            <img src="config/assets/images/wa.jpg" alt="WhatsApp">
                            <span>WhatsApp</span>
                        </a>
                        <a href="https://instagram.com/username_kamu" target="_blank" rel="noopener" class="contact-link">
                            <img src="config/assets/images/ig.jpg" alt="Instagram">
                            <span>Instagram</span>
                        </a>
                        <a href="mailto:emailkamu@gmail.com" class="contact-link">
                            <img src="config/assets/images/email.jpg" alt="Email">
                            <span>Email</span>
                        </a>
                        <a href="https://maps.app.goo.gl/NbavqgbRc8Y9BhmCA" target="_blank" rel="noopener" class="contact-link">
                            <img src="config/assets/images/maps.jpg" alt="Lokasi">
                            <span>Lokasi</span>
                        </a>
                    </div>
                </div>
            </div>

            <hr class="my-3 opacity-25">
            <p class="text-xs mb-0 text-center" style="color: var(--brand-bg);">&copy; 2026 Pet Heaven. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS (untuk menu hamburger di HP) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Rating bintang: jumlah bintang penuh sesuai data-rating pada tiap testimoni
        document.querySelectorAll('.rating').forEach(el => {
            const n = parseInt(el.dataset.rating, 10);
            el.setAttribute('role', 'img');
            el.setAttribute('aria-label', 'Rating ' + n + ' dari 5');
            el.innerHTML = Array.from({ length: 5 }, (_, i) =>
                '<i class="' + (i < n ? 'fa-solid' : 'fa-regular') + ' fa-star"></i>'
            ).join('');
        });

        // Animasi scroll untuk semua elemen ber-class "reveal"
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('show');
                    observer.unobserve(entry.target);
                    setTimeout(() => { entry.target.style.transitionDelay = '0ms'; }, 1200);
                }
            });
        }, { threshold: 0.15 });

        document.querySelectorAll('.reveal').forEach(el => {
            // jeda bertahap antar elemen yang bersaudara (kartu muncul berurutan)
            const container = el.closest('.row') || el.parentElement;
            const sibs = [...container.querySelectorAll('.reveal')];
            el.style.transitionDelay = (sibs.indexOf(el) * 120) + 'ms';
            observer.observe(el);
        });
    </script>

</body>
</html>