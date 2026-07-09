-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 25, 2023 at 01:39 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sigpelakes`
--

-- --------------------------------------------------------

--
-- Table structure for table `rumahsakits`
--

CREATE TABLE `rumahsakits` (
  `no_rs` bigint(20) UNSIGNED NOT NULL,
  `nama_rumahsakit` varchar(255) NOT NULL,
  `alamat` varchar(255) NOT NULL,
  `no_telp` varchar(255) NOT NULL,
  `jam_kerja` varchar(255) NOT NULL,
  `fasilitas` varchar(255) NOT NULL,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rumahsakits`
--

INSERT INTO `rumahsakits` (`no_rs`, `nama_rumahsakit`, `alamat`, `no_telp`, `jam_kerja`, `fasilitas`, `latitude`, `longitude`, `created_at`, `updated_at`) VALUES
(1, 'RSUD Dr. SAIFUL ANWAR MALANG', 'Jl. Jaksa Agung Suprapto No.2, Klojen, Kec. Klojen, Kota Malang, Jawa Timur 65112', '(0341) 362101', '24 jam', 'General Check UP RSSA, Antria Online Rawat Jalan', -7.9723572, 112.627289, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(2, 'Rumah Sakit Lavalette', 'Jl. W.R. Supratman No.10, Rampal Celaket, Kec. Klojen, Kota Malang, Jawa Timur 65111', '(0341) 470805', '24 jam', 'Radiologi,Laboratorium,Hemodialisa,Cancer Center,Endoskopi,Operasi Katarak dengan Pacho,Fisiotrapi,Konsultasi Gizi,Home Care', -7.9657494, 112.6336989, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(3, 'RSUD Kota Malang', 'Jl. Rajasa No.27, Bumiayu, Kec. Kedungkandang, Kota Malang, Jawa Timur 65116', '(0341) 754338', '24 jam', 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain', -7.9779081, 112.5831843, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(4, 'RS. Tk.II dr. Soepraoen', 'Jl. S. Supriadi No.22, Sukun, Kec. Sukun, Kota Malang, Jawa Timur 65112', '(325111) 325112 / 0811-3229-9222', '24 jam', 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain', -7.989864, 112.6204795, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(5, 'RS. Panti Nirmala', ' Jl. Kebalen Wetan No.2-8, Kotalama, Kec. Kedungkandang, Kota Malang, Jawa Timur 65134', '(0341) 362459 / 331100 / 350833 / 0341360118', '24 jam', 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain', -7.994312, 112.6340581, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(6, 'RSIA Muhammadiyah Malang', 'Jl. K. H. Wahid Hasim, Kauman, Kec. Klojen, Kota Malang, Jawa Timur 65119', '0813-1793-6288', '24 jam', 'Tenaga Medis Profesional, Ruang Anak,Rawat Inap, IGD, Instalasi Gawat Darurat, Penunjang, Rawat Jalan, dan lain-lain', -7.9841278, 112.6290835, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(7, 'RS Universitas Brawijaya', 'Jl. Soekarno - Hatta, Lowokwaru, Kec. Lowokwaru, Kota Malang, Jawa Timur 65141', '(0341) 403000', '24 jam', 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain', -7.9412275, 112.6215363, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(8, 'RS. Panti Waluya Sawahan', 'Jl. Nusakambangan No.56, Kasin, Kec. Klojen, Kota Malang, Jawa Timur 65117', '(0341) 366033', '24 jam', 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain', -7.986567, 112.6249675, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(9, 'RSI. Aisyiyah', 'Jl. Sulawesi No.16, Kasin, Kec. Klojen, Kota Malang, Jawa Timur 65117', '(0341) 326773', ' 24 Jam', ' Rawat Inap, Rawat Jalan, Radiologi, Kamar Bersalin, IGD, Instalasi Gawat Darurat, dan lain-lain', -7.9886757, 112.6228673, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(10, 'RSI. UNISMA MALANG', 'Jalan, Jalan Mayjen Haryono No.139, Dinoyo, Kec. Lowokwaru, Kota Malang, Jawa Timur 65144', '(0341) 551356', '24 jam', 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain', -7.9402626, 112.6088354, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(11, 'RS Permata Bunda Malang', 'Jl. Soekarno Hatta No.75, Mojolangu, Kec. Lowokwaru, Kota Malang, Jawa Timur 65142', '(0341) 407462 / 0877-9311-1347', '24 jam', 'Rawat Inap, Kamar Bersalin, IGD 24 Jam, Telekonsultasi, Penunjang, dan lain-lain', -7.938896, 112.6248248, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(12, 'RSIA Hermina Malang', 'Jl. Tangkuban Perahu No.31-33, Kauman, Kec. Klojen, Kota Malang, Jawa Timur 65119', '(0341) 322525', '24 Jam', 'Rawat Inap,Rawat Jalan,Farmasi,NICU & PICU,Laboratorium, Kamar Bersalin, IGD, Instalasi Gawat Darurat, dan lain-lain', -7.9780058, 112.624481, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(13, 'RSIA Husada Bunda', ' Malang No. 2, Jl. Pahlawan Trip, Oro-oro Dowo, Klojen, Malang City, East Java 65112', '(0341) 566972', '24 jam', 'Rawat Inap, Rawat Jalan, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain', -7.9682854, 112.6228173, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(14, 'RS Persada Hospital malang', 'Kompleks Araya Business Centre Kav. 2-4 Jalan Panji Suroso Blimbing Purwodadi Blimbing Kota Malang Jawa Timur 65126 ID, Jl. Raden Panji Suroso No.4, Purwodadi, Kec. Blimbing, Kota Malang, Jawa Timur 65126', '(0341) 2996333', '24 jam', 'Rawat Inap, Penunjang Medis, IGD, Medical Check-Up, dan lain-lain', -7.9349858, 112.6502085, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(15, 'Rumah Sakit Ibu dan Anak Mardi Waloeja Kauman', 'Jalan Kauman No. 23, Kauman, Kec. Klojen, Kota Malang, Jawa Timur 65119', '(0341) 362304', '24 jam', 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain', -7.9829967, 112.6278261, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(16, 'RSIA Puri', 'Jl. Taman Slamet No.20, Gading Kasri, Kec. Klojen, Kota Malang, Jawa Timur 65115', '(0341) 325329', '24 jam', 'Rawat Inap, Rawat Jalan, Ruang Perawatan, dan lain-lain', -7.9744496, 112.6218371, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(17, 'RSIA Permata Hati', 'Jl. Danau Toba, Lesanpuro, Kec. Kedungkandang, Kota Malang, Jawa Timur 65138', '(0341) 718068 / 0821-3912-4075', '24 jam', 'Rawat Inap, Rawat Jalan, Ruang Perawatan, dan lain-lain', -7.9797897, 112.6606777, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(18, 'RSIA Puri Bunda', 'Jl. Simpang Sulfat Utara No.60 A, Pandanwangi, Kec. Blimbing, Kota Malang, Jawa Timur 65126', '(0341) 480047 / (0341) 477511', '24 jam', 'Rawat Inap, IGD 24 Jam, Radiologi, Konsultasi, Ruang Isolasi, Ambulance', -7.9587645, 112.6555326, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(19, 'RSIA Mardi Waloeja Rampal', 'Jl. W.R. Supratman No.1, Rampal Celaket, Kec. Klojen, Kota Malang, Jawa Timur 65111', '(0341) 364756', '24 jam', 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain', -7.964898, 112.6352319, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(20, 'RSIA Mutiara Bunda', 'Jl. Ciujung No.19, Purwantoro, Kec. Blimbing, Kota Malang, Jawa Timur 65126', '(0341) 400403', '24 jam', 'Poli Anak, Poli Obgyn, Poli Umum, IGD, Laboratorium, Farmasi, Ambulance', -7.9533696, 112.641043, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(21, 'RSIA Melati Husada', 'Jl. Kawi No.32, Gading Kasri, Kec. Klojen, Kota Malang, Jawa Timur 65115', '(0341) 325249 / (0341) 326573', '24 jam', 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain', -7.9761054, 112.6213605, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(22, 'RSIA Galeri Candra', 'Jl. Andong No.3, Jatimulyo, Kec. Lowokwaru, Kota Malang, Jawa Timur 65141', '(0341) 478571', '24 jam', 'Tenaga Medis Profesional, Rawat Inap, IGD, Instalasi Gawat Darurat, Penunjang, Rawat Jalan, dan lain-lain', -7.947945, 112.619574, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(23, 'RSIA Rumkitban 05.08.02 Malang', 'Jl. Panglima Sudirman No.E20, Kesatrian, Kec. Blimbing, Kota Malang, Jawa Timur 65111', '(0341) 354396', '24 jam', 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain', -7.9740832, 112.6387908, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(24, 'RSIA Refa Husada', 'Tlogowaru, Kec. Kedungkandang, Kota Malang, Jawa Timur 65132', '(0341) 754075', '24 jam', 'Rawat Inap, IGD, Instalasi Gawat Darurat, Instalasi Bedah, dan lain-lain', -8.0357588, 112.6426445, '2023-05-25 04:33:48', '2023-05-25 04:33:48'),
(25, 'RSKK. Onkologi Sentani', 'Jl. Danau Paniai, Madyopuro, Kec. Kedungkandang, Kota Malang, Jawa Timur 65139', '(0341) 3022902 / 0811-3136-991', ' 24 Jam', ' Rawat Inap, IGD, Pelayanan 24 Jam, dan lain-lain', -7.9741313, 112.6663124, '2023-05-25 04:33:48', '2023-05-25 04:33:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `rumahsakits`
--
ALTER TABLE `rumahsakits`
  ADD PRIMARY KEY (`no_rs`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `rumahsakits`
--
ALTER TABLE `rumahsakits`
  MODIFY `no_rs` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
