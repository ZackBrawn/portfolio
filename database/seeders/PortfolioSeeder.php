<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;

class PortfolioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::updateOrCreate(
            ['email' => 'owner@portfolio.com'],
            [
                'name' => 'Owner Zack',
                'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            ]
        );

        $projects = [
        [
            'title' => 'Gerakan Hijau Kota —   Penataan Lingkungan Urban',
            'slug' => 'gerakan-hijau-kota- -penataan-lingkungan-urban',
            'summary' => 'Program yang fokus pada advokasi kebijakan ruang terbuka hijau dan reboisasi lahan kritis di perkotaan.',
            'description' => '  ini menggalang dukungan konstituen urban untuk mendorong pemerintah daerah memperluas taman kota dan hutan kota. Melalui pendekatan legislatif, gerakan ini memperjuangkan anggaran bibit gratis dan fasilitas irigasi bagi komunitas lokal, serta memetakan wilayah krisis ekologis guna menekan polusi udara.',
            'focus_areas' => 'Mobilisasi Massa, Advokasi Kebijakan, Pemetaan Ekologis, Tata Kota',
            'image_url' => '/projects/lumina.png',
            'images_gallery' => '/projects/lumina.png,/projects/lumina-detail-1.png,/projects/lumina-detail-2.png',
        ],
        [
            'title' => 'Pesisir Berdaya — Advokasi Nelayan dan Kebersihan Pantai',
            'slug' => 'pesisir-berdaya-advokasi-nelayan-dan-kebersihan-pantai',
            'summary' => '  tingkat nasional untuk memperjuangkan hak masyarakat pesisir dan perlindungan ekosistem laut.',
            'description' => 'Pesisir Berdaya membangun koalisi relawan di daerah pemilihan pesisir untuk mendesak regulasi ketat terkait limbah industri. Berfokus pada penyediaan infrastruktur pengelolaan sampah berbasis komunitas di desa nelayan dan mendorong investasi industri daur ulang lokal.',
            'focus_areas' => 'Pengembangan Dapil, Regulasi Lingkungan, Logistik  , Ekonomi Sirkular',
            'image_url' => '/projects/hermes.png',
            'images_gallery' => '/projects/hermes.png,/projects/hermes-detail-1.png,/projects/hermes-detail-2.png',
        ],
        [
            'title' => 'Ketahanan Pangan — Program Pekarangan Mandiri',
            'slug' => 'ketahanan-pangan-program-pekarangan-mandiri',
            'summary' => '  berbasis kerakyatan untuk pemanfaatan lahan tidur demi kedaulatan pangan keluarga prasejahtera.',
            'description' => 'Melalui program ini, paslon turun langsung (blusukan) mengedukasi warga mengenai pertanian perkotaan organik.   ini menjanjikan penyediaan bantuan alat tani, uji kelayakan tanah, serta distribusi pupuk gratis untuk menekan angka inflasi pangan di tingkat akar rumput (grassroots).',
            'focus_areas' => 'Pertanian Urban, Kedaulatan Pangan, Kesejahteraan Sosial, Konsolidasi Warga',
            'image_url' => '/projects/stitch.png',
            'images_gallery' => '/projects/stitch.png,/projects/stitch-detail-1.png,/projects/stitch-detail-2.png',
        ],
        [
            'title' => 'Sungai Bersih — Solusi Regulasi Sampah Plastik',
            'slug' => 'sungai-bersih-solusi-regulasi-sampah-plastik',
            'summary' => 'Pemasangan sekat sampah berbasis komunitas sebagai bentuk aksi nyata penanganan banjir di wilayah padat penduduk.',
            'description' => '  ini mengintegrasikan aksi nyata lapangan dengan advokasi perda pembatasan plastik sekali pakai. Relawan lintas kecamatan dikerahkan untuk memantau titik tumpukan sampah sungai, mengumpulkan data otentik, dan mendesak eksekutif memperbaiki tata kelola limbah.',
            'focus_areas' => 'Restorasi Sungai, Pengendalian Banjir, Riset Kebijakan, Keterlibatan Publik',
            'image_url' => '/projects/lumina.png',
            'images_gallery' => '/projects/lumina.png,/projects/lumina-detail-1.png,/projects/lumina-detail-2.png',
        ],
        [
            'title' => 'Eko Edukasi — Reformasi Kurikulum Literasi Ekologis',
            'slug' => 'eko-edukasi-reformasi-kurikulum-literasi-ekologis',
            'summary' => 'Inisiatif pemuda yang memperjuangkan integrasi sains iklim dan pengelolaan sampah ke sekolah negeri.',
            'description' => 'Gerakan ini menjembatani kebijakan publik dan sektor pendidikan melalui lobi di komisi terkait untuk memasukkan modul literasi lingkungan.   ini juga menggalang dukungan dari kalangan guru dan mahasiswa untuk mewujudkan program sekolah nihil sampah (zero waste).',
            'focus_areas' => 'Pendidikan Pemuda, Kebijakan Publik, Gerakan Mahasiswa, Reformasi Kurikulum',
            'image_url' => '/projects/hermes.png',
            'images_gallery' => '/projects/hermes.png,/projects/hermes-detail-1.png,/projects/hermes-detail-2.png',
        ],
        [
            'title' => 'Lajur Aman —   Transportasi Umum Terintegrasi',
            'slug' => 'lajur-aman- -transportasi-umum-terintegrasi',
            'summary' => 'Advokasi infrastruktur ramah pejalan kaki dan jalur sepeda guna mengurangi ketergantungan bahan bakar fosil.',
            'description' => '  ini mendesak dinas perhubungan kota untuk mereklamasi lajur kendaraan pribadi menjadi fasilitas transportasi publik yang aman dan inklusif. Pendekatan dilakukan dengan membagikan data efisiensi mobilitas urban dan mengampanyekan insentif bagi pengguna kendaraan listrik.',
            'focus_areas' => 'Infrastruktur Urban, Advokasi Anggaran, Energi Terbarukan, Keselamatan Publik',
            'image_url' => '/projects/stitch.png',
            'images_gallery' => '/projects/stitch.png,/projects/stitch-detail-1.png,/projects/stitch-detail-2.png',
        ],
        [
            'title' => 'Suaka Alam — Kemitraan Petani untuk Biodiversitas',
            'slug' => 'suaka-alam-kemitraan-petani-untuk-biodiversitas',
            'summary' => '  perlindungan kawasan penyangga desa untuk memulihkan ekosistem dan habitat satwa liar.',
            'description' => 'Program ini menggalang aliansi dengan kelompok tani di perbatasan hutan untuk menerapkan sistem pertanian ramah lingkungan tanpa pestisida kimia berlebih.   ini memperjuangkan skema subsidi hijau bagi petani yang menjaga kelestarian keanekaragaman hayati.',
            'focus_areas' => 'Aliansi Kelompok Tani, Subsidi Hijau, Perlindungan Alam, Pemberdayaan Desa',
            'image_url' => '/projects/lumina.png',
            'images_gallery' => '/projects/lumina.png,/projects/lumina-detail-1.png,/projects/lumina-detail-2.png',
        ],
        [
            'title' => 'Udara Bersih — Transparansi Data Polusi Industri',
            'slug' => 'udara-bersih-transparansi-data-polusi-industri',
            'summary' => 'Penyusunan regulasi pemantauan kualitas udara berbasis warga di sekitar kawasan industri dan penyangga.',
            'description' => '  ini membagikan alat sensor udara gratis kepada konstituen di area terdampak polusi pabrik. Data telemetri yang terkumpul secara real-time digunakan sebagai instrumen untuk mendesak parlemen mengesahkan sanksi berat bagi industri pelanggar ambang batas emisi.',
            'focus_areas' => 'Transparansi Data, Advokasi Regulasi, Hak Kesehatan Warga, Pengawasan Industri',
            'image_url' => '/projects/hermes.png',
            'images_gallery' => '/projects/hermes.png,/projects/hermes-detail-1.png,/projects/hermes-detail-2.png',
        ],
        [
            'title' => 'Kompos Rakyat — Pengelolaan Sampah Berbasis RT/RW',
            'slug' => 'kompos-rakyat-pengelolaan-sampah-berbasis-rtrw',
            'summary' => 'Penguatan ketahanan ekonomi lokal lewat program pemilahan dan pengolahan sampah organik di pemukiman.',
            'description' => '  akar rumput yang melatih koordinator wilayah (Koorwil) tingkat RT/RW dalam mengelola pos komposting mandiri. Hasil kompos disalurkan kembali ke warga untuk pertanian mandiri, sekaligus mengampanyekan gaya hidup minim sampah guna mengurangi beban APBD di sektor TPA.',
            'focus_areas' => 'Penguatan Akar Rumput, Edukasi Rumah Tangga, Manajemen Limbah, Efisiensi APBD',
            'image_url' => '/projects/stitch.png',
            'images_gallery' => '/projects/stitch.png,/projects/stitch-detail-1.png,/projects/stitch-detail-2.png',
        ],
        [
            'title' => 'Desa Terang — Pemerataan Energi Terbarukan Mandiri',
            'slug' => 'desa-terang-pemerataan-energi-terbarukan-mandiri',
            'summary' => 'Pengadaan infrastruktur mikro-grid tenaga surya untuk desa tertinggal, terdepan, dan terluar (3T).',
            'description' => '  ini mengusung isu keadilan energi dengan memperjuangkan alokasi dana desa untuk pembangunan stasiun surya berbasis koperasi. Fokus utamanya adalah membebaskan nelayan kecil dari ketergantungan bahan bakar minyak mahal untuk penerangan dan penyimpanan hasil laut.',
            'focus_areas' => 'Keadilan Energi, Koperasi Kemitraan, Infrastruktur Pedesaan, Logistik Wilayah 3T',
            'image_url' => '/projects/lumina.png',
            'images_gallery' => '/projects/lumina.png,/projects/lumina-detail-1.png,/projects/lumina-detail-2.png',
        ],
        [
            'title' => 'Rumah Layak — Renovasi Hunian Ramah Iklim',
            'slug' => 'rumah-layak-renovasi-hunian-ramah-iklim',
            'summary' => 'Advokasi program bedah rumah dengan material lokal berinsulasi alami untuk keluarga prasejahtera.',
            'description' => '  legislatif yang fokus pada perbaikan regulasi kelayakan hunian perkotaan. Mengusung konsep arsitektur hijau yang hemat energi untuk menekan biaya listrik bulanan warga miskin ekstrem, sekaligus membuka lapangan kerja hijau (green jobs) bagi tukang bangunan lokal.',
            'focus_areas' => 'Bedah Rumah, Pengetasan Kemiskinan, Padat Karya, Arsitektur Hijau',
            'image_url' => '/projects/hermes.png',
            'images_gallery' => '/projects/hermes.png,/projects/hermes-detail-1.png,/projects/hermes-detail-2.png',
        ],
    ];

        foreach ($projects as $project) {
            Project::updateOrCreate(
                ['slug' => $project['slug']],
                $project
            );
        }

        $commentsData = [
            'gerakan-hijau-kota- -penataan-lingkungan-urban' => [
                ['author_name' => 'Rian Hidayat', 'content' => 'Sangat mendukung gerakan reboisasi ini! Kota Semarang butuh lebih banyak micro-forest agar tidak terlalu panas.'],
                ['author_name' => 'Siti Aminah', 'content' => 'Semoga anggaran bibit gratis ini bisa terealisasi secepatnya. Kami di RT siap menanam!'],
                ['author_name' => 'Budi Santoso', 'content' => 'Lobi kebijakan tata ruang hijau harus terus dikawal ketat demi anak cucu kita.'],
            ],
            'pesisir-berdaya-advokasi-nelayan-dan-kebersihan-pantai' => [
                ['author_name' => 'Joko Supriyanto', 'content' => 'Inisiatif luar biasa untuk nelayan pesisir. Regulasi limbah industri harus ditegakkan dengan tegas.'],
                ['author_name' => 'Dewi Lestari', 'content' => 'Daur ulang sampah plastik menjadi bahan bangunan sangat inovatif. Semoga desaku bisa berpartisipasi.'],
            ],
            'ketahanan-pangan-program-pekarangan-mandiri' => [
                ['author_name' => 'Sri Wahyuni', 'content' => 'Program pekarangan ini sangat membantu ekonomi keluarga kami di masa inflasi ini.'],
                ['author_name' => 'Agus Setiawan', 'content' => 'Pelatihan kompos organiknya sangat praktis dan bermanfaat. Terima kasih panitia!'],
                ['author_name' => 'Megawati', 'content' => 'Uji kelayakan tanah sangat penting untuk menanam sayur organik. Keren!'],
            ],
            'sungai-bersih-solusi-regulasi-sampah-plastik' => [
                ['author_name' => 'Hendra Wijaya', 'content' => 'Sekat sampah di wilayah kami sangat efektif mengurangi sumbatan saluran air saat hujan deras.'],
                ['author_name' => 'Putri Utami', 'content' => 'Perda pembatasan plastik sekali pakai adalah kunci utama sungai bersih.'],
            ],
            'eko-edukasi-reformasi-kurikulum-literasi-ekologis' => [
                ['author_name' => 'Eko Prasetyo', 'content' => 'Sebagai guru, saya sangat mendukung integrasi materi lingkungan ke kurikulum sekolah dasar.'],
                ['author_name' => 'Dina Kartika', 'content' => 'Sekolah nihil sampah harus jadi standar nasional. Kampanye yang luar biasa!'],
            ],
            'lajur-aman- -transportasi-umum-terintegrasi' => [
                ['author_name' => 'Rahmat Hidayat', 'content' => 'Lajur sepeda terintegrasi akan membuat kota kita jauh lebih ramah lingkungan dan sehat.'],
                ['author_name' => 'Yuni Astuti', 'content' => 'Sebagai pengguna angkutan umum, saya sangat setuju dengan insentif kendaraan listrik dan perluasan lajur bus.'],
            ],
            'suaka-alam-kemitraan-petani-untuk-biodiversitas' => [
                ['author_name' => 'Slamet Riyadi', 'content' => 'Petani di desa kami siap menerapkan sistem bebas pestisida jika subsidi pupuk organik tersedia.'],
                ['author_name' => 'Indah Permata', 'content' => 'Menjaga keanekaragaman hayati pedesaan adalah pelindung alami kita.'],
            ],
            'udara-bersih-transparansi-data-polusi-industri' => [
                ['author_name' => 'Fajar Nugroho', 'content' => 'Transparansi data sensor udara sangat penting. Selama ini data kualitas udara selalu tertutup.'],
                ['author_name' => 'Lia Kusuma', 'content' => 'Warga sekitar pabrik berhak tahu racun apa saja yang mereka hirup setiap hari.'],
            ],
            'kompos-rakyat-pengelolaan-sampah-berbasis-rtrw' => [
                ['author_name' => 'Bambang Susilo', 'content' => 'Di RT kami sampah organik sudah mulai dikumpulkan untuk kompos. Hasilnya sangat bagus untuk taman warga.'],
                ['author_name' => 'Anisa Fitri', 'content' => 'Mengurangi beban TPA adalah tanggung jawab kita bersama dimulai dari dapur rumah.'],
            ],
            'desa-terang-pemerataan-energi-terbarukan-mandiri' => [
                ['author_name' => 'Kadek Arta', 'content' => 'Listrik surya gratis sangat membantu anak-anak belajar di malam hari di desa terpencil kami.'],
                ['author_name' => 'Nyoman Widya', 'content' => 'Koperasi nelayan sangat terbantu dengan adanya pendingin bertenaga surya.'],
            ],
            'rumah-layak-renovasi-hunian-ramah-iklim' => [
                ['author_name' => 'Zulfikar', 'content' => 'Bahan bangunan berinsulasi hemp sangat sejuk dan ramah lingkungan. Hemat listrik AC.'],
                ['author_name' => 'Ratna Sari', 'content' => 'Program bedah rumah berkonsep hijau sangat membantu keluarga kurang mampu.'],
            ],
        ];

        foreach ($commentsData as $projectSlug => $comments) {
            $project = Project::where('slug', $projectSlug)->first();
            if ($project) {
                foreach ($comments as $comment) {
                    \App\Models\Comment::updateOrCreate(
                        [
                            'project_id' => $project->id,
                            'author_name' => $comment['author_name'],
                            'content' => $comment['content']
                        ],
                        []
                    );
                }
            }
        }
    }
}
