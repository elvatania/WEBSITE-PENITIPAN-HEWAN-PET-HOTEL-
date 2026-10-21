<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Heaven - A Place of Luxury</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brandPrimary: '#3368A0',   /* Biru Utama */
                        brandSecondary: '#66A3BF', /* Biru Muda */
                        brandMint: '#C8DFDB',      /* Mint Soft */
                        brandBg: '#F2EFE7',        /* Cream / Off-white */
                    }
                }
            }
        }
    </script>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-brandBg text-gray-800 font-sans">

    <!-- ================= NAVBAR ================= -->
    <header class="bg-white shadow-sm sticky top-0 z-50 py-2">
        <div class="max-w-7xl mx-auto px-6 flex items-center justify-between">
            <!-- Logo Brand -->
            <div class="flex items-center space-x-3">
                <img src="config/assets/images/logo.png" 
                     alt="Pet Heaven Logo" 
                     class="h-12 md:h-14 w-auto object-contain">
                <span class="text-xl font-bold text-brandPrimary tracking-wide">Pet Heaven</span>
            </div>

            <!-- Navigasi Menu -->
            <nav class="hidden md:flex space-x-8 text-sm font-medium text-gray-700">
                <a href="#services" class="hover:text-brandPrimary transition">Layanan</a>
                <a href="#rooms" class="hover:text-brandPrimary transition">Kamar</a>
                <a href="#gallery" class="hover:text-brandPrimary transition">Galeri</a>
                <a href="#testimonials" class="hover:text-brandPrimary transition">Testimoni</a>
            </nav>

            <!-- Tombol Aksi (Login & Daftar) -->
            <div class="flex items-center space-x-3 text-sm">
                <a href="login.php" class="px-5 py-1.5 text-brandPrimary border border-brandPrimary rounded-full hover:bg-brandMint transition font-semibold">
                    Login
                </a>
                <a href="daftar.php" class="px-5 py-1.5 bg-brandPrimary text-white rounded-full hover:bg-brandSecondary transition font-semibold shadow-sm">
                    Daftar
                </a>
            </div>
        </div>
    </header>

    <!-- ================= HERO SECTION ================= -->
    <section class="relative bg-cover bg-center bg-no-repeat py-20 md:py-32" style="background-image: url('config/assets/images/kucing.jpg');">
        <div class="absolute inset-0 bg-[#F2EFE7]/85"></div>
        <div class="relative z-10 max-w-7xl mx-auto px-6 text-center md:text-left">
            <div class="max-w-2xl">
                <span class="inline-block text-xs font-bold uppercase tracking-widest text-brandPrimary bg-brandMint px-3 py-1.5 rounded-full mb-3">
                    A Place of Luxury
                </span>

                <h1 class="text-4xl md:text-5xl font-extrabold text-brandPrimary leading-tight">
                    Penitipan & Perawatan Terbaik untuk Anabul Kesayangan
                </h1>

                <p class="mt-4 text-gray-700 text-lg font-medium">
                    Memberikan kenyamanan, keamanan, dan kasih sayang layaknya di rumah sendiri selama Anda bepergian.
                </p>

                <div class="mt-8 flex flex-wrap justify-center md:justify-start gap-4">
                    <a href="daftar.php" class="inline-flex items-center gap-2 px-6 py-3 bg-brandPrimary text-white rounded-lg font-bold hover:bg-brandSecondary transition shadow-lg">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span>Form Reservasi</span>
                    </a>
                    <a href="#services" class="inline-flex items-center px-6 py-3 bg-white/90 text-brandPrimary border border-brandSecondary rounded-lg font-bold hover:bg-brandMint transition">
                        Lihat Layanan
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= LAYANAN (Background: Putih) ================= -->
    <section id="services" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-12">
                <span class="text-xs font-bold uppercase tracking-widest text-brandPrimary bg-brandMint px-3 py-1 rounded-full">Fasilitas Utama</span>
                <h2 class="text-3xl font-bold text-brandPrimary mt-3">Layanan Terbaik Kami</h2>
                <p class="text-gray-600 mt-2">Berbagai perawatan dan kenyamanan ekstra untuk anabul kesayangan Anda.</p>
            </div>

            <div class="grid md:grid-cols-4 gap-6">
                <!-- Grooming -->
                <div class="bg-brandBg p-6 rounded-2xl shadow-sm hover:shadow-md transition text-center border border-brandMint">
                    <div class="w-14 h-14 mx-auto bg-brandMint text-brandPrimary rounded-full flex items-center justify-center text-2xl mb-4">
                        <i class="fa-solid fa-shower"></i>
                    </div>
                    <h3 class="text-lg font-bold text-brandPrimary">Grooming & Salon</h3>
                    <p class="text-sm text-gray-600 mt-2">Mandi sehat, potong kuku, bersihkan telinga, hingga styling bulu anabul.</p>
                </div>

                <!-- Cek Kesehatan -->
                <div class="bg-brandBg p-6 rounded-2xl shadow-sm hover:shadow-md transition text-center border border-brandMint">
                    <div class="w-14 h-14 mx-auto bg-brandMint text-brandPrimary rounded-full flex items-center justify-center text-2xl mb-4">
                        <i class="fa-solid fa-user-doctor"></i>
                    </div>
                    <h3 class="text-lg font-bold text-brandPrimary">Cek Dokter Hewan</h3>
                    <p class="text-sm text-gray-600 mt-2">Pemeriksaan rutin dan pemberian vitamin oleh dokter hewan berpengalaman.</p>
                </div>

                <!-- Pet Taxi -->
                <div class="bg-brandBg p-6 rounded-2xl shadow-sm hover:shadow-md transition text-center border border-brandMint">
                    <div class="w-14 h-14 mx-auto bg-brandMint text-brandPrimary rounded-full flex items-center justify-center text-2xl mb-4">
                        <i class="fa-solid fa-truck-pickup"></i>
                    </div>
                    <h3 class="text-lg font-bold text-brandPrimary">Antar - Jemput</h3>
                    <p class="text-sm text-gray-600 mt-2">Layanan penjemputan & pengantaran anabul dari rumah Anda secara aman.</p>
                </div>

                <!-- Pet Daycare -->
                <div class="bg-brandBg p-6 rounded-2xl shadow-sm hover:shadow-md transition text-center border border-brandMint">
                    <div class="w-14 h-14 mx-auto bg-brandMint text-brandPrimary rounded-full flex items-center justify-center text-2xl mb-4">
                        <i class="fa-solid fa-bone"></i>
                    </div>
                    <h3 class="text-lg font-bold text-brandPrimary">Pet Daycare</h3>
                    <p class="text-sm text-gray-600 mt-2">Penitipan harian saat Anda bekerja dengan area bermain dan snack gratis.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= JENIS KAMAR (Background: Cream/brandBg) ================= -->
    <section id="rooms" class="py-16 bg-brandBg">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-brandPrimary">Jenis Kamar Kami</h2>
                <p class="text-gray-600 mt-2">Pilihan akomodasi nyaman yang disesuaikan dengan kebutuhan anabul.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Kamar Standard -->
                <div class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition border border-gray-100">
                    <div class="h-48 overflow-hidden">
                        <img src="config/assets/images/standartroom.png" 
                             alt="Standard Room" 
                             class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-brandPrimary">Standard Room</h3>
                        <p class="text-sm text-gray-600 mt-2">Kamar bersih, AC 24 jam, dan makanan berkualitas 2x sehari.</p>
                        <div class="mt-4 flex justify-between items-center">
                            <span class="text-lg font-bold text-brandPrimary">Rp 150.000 <span class="text-xs text-gray-500">/malam</span></span>
                        </div>
                    </div>
                </div>

                <!-- Kamar Deluxe / VIP -->
                <div class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition border border-gray-100">
                    <div class="h-48 overflow-hidden">
                        <img src="config/assets/images/viproom.png" 
                             alt="VIP Room" 
                             class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-brandPrimary">VIP Room</h3>
                        <p class="text-sm text-gray-600 mt-2">Fasilitas CCTV live stream, area bermain khusus, dan grooming gratis.</p>
                        <div class="mt-4 flex justify-between items-center">
                            <span class="text-lg font-bold text-brandPrimary">Rp 250.000 <span class="text-xs text-gray-500">/malam</span></span>
                        </div>
                    </div>
                </div>

                <!-- Kamar Suite / VVIP -->
                <div class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition border border-gray-100">
                    <div class="h-48 overflow-hidden">
                        <img src="config/assets/images/vviproom.png" 
                             alt="VVIP Room" 
                             class="w-full h-full object-cover">
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-brandPrimary">VVIP Room</h3>
                        <p class="text-sm text-gray-600 mt-2">Ruangan sangat luas, kasur empuk mewah, akses playground pribadi.</p>
                        <div class="mt-4 flex justify-between items-center">
                            <span class="text-lg font-bold text-brandPrimary">Rp 350.000 <span class="text-xs text-gray-500">/malam</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= GALERI HEWAN ================= -->
    <section id="gallery" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-brandPrimary">Galeri Tamu Menginap</h2>
                <p class="text-gray-600 mt-2">Teman-teman bulu yang pernah menikmati layanan kami di Pet Heaven.</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="h-40 bg-brandBg rounded-xl border border-brandMint flex flex-col items-center justify-center p-2 text-center text-gray-500">
                    <i class="fa-solid fa-paw text-2xl text-brandSecondary mb-1"></i>
                    <span class="text-xs font-semibold text-brandPrimary">[Isi Foto Anabul 1]</span>
                </div>
                <div class="h-40 bg-brandBg rounded-xl border border-brandMint flex flex-col items-center justify-center p-2 text-center text-gray-500">
                    <i class="fa-solid fa-paw text-2xl text-brandSecondary mb-1"></i>
                    <span class="text-xs font-semibold text-brandPrimary">[Isi Foto Anabul 2]</span>
                </div>
                <div class="h-40 bg-brandBg rounded-xl border border-brandMint flex flex-col items-center justify-center p-2 text-center text-gray-500">
                    <i class="fa-solid fa-paw text-2xl text-brandSecondary mb-1"></i>
                    <span class="text-xs font-semibold text-brandPrimary">[Isi Foto Anabul 3]</span>
                </div>
                <div class="h-40 bg-brandBg rounded-xl border border-brandMint flex flex-col items-center justify-center p-2 text-center text-gray-500">
                    <i class="fa-solid fa-paw text-2xl text-brandSecondary mb-1"></i>
                    <span class="text-xs font-semibold text-brandPrimary">[Isi Foto Anabul 4]</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= FOOTER ================= -->
    <footer class="bg-brandPrimary text-white py-8">
        <div class="max-w-7xl mx-auto px-6 text-center md:flex md:justify-between md:items-center">
            <div>
                <p class="font-bold text-lg">Pet Heaven</p>
                <p class="p-0 text-sm text-brandMint">A Place of Luxury for Your Pets</p>
            </div>
            <p class="text-xs text-brandBg mt-4 md:mt-0">&copy; 2026 Pet Heaven. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>