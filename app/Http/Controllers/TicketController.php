<?php

namespace App\Http\Controllers;

use App\Models\Tiket;
use Illuminate\Http\Request;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;


class TicketController extends Controller
{
    // public function generateTickets()
    // {
    //     for ($i = 1; $i <= 100; $i++) {
    //         $this->generateTicket($i);
    //     }

    //     return "Selesai menghasilkan tiket!";
    // }


    // private function base64UrlEncode($data)
    // {
    //     $b64 = base64_encode($data);
    //     $url = strtr($b64, '+/', '-_');
    //     return rtrim($url, '=');
    // }

    // private function generateRandomString($length = 10)
    // {
    //     $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    //     $charactersLength = strlen($characters);
    //     $randomString = '';

    //     for ($i = 0; $i < $length; $i++) {
    //         $randomString .= $characters[rand(0, $charactersLength - 1)];
    //     }

    //     return $this->base64UrlEncode($randomString);
    // }

    // private function generateTicket($totalTickets = 4)
    // {
    //     $pdf = new \FPDF('P', 'mm', 'A4');

    //     // Ukuran tiket dalam milimeter
    //     $ticketWidth = 210; // Lebar penuh kertas A4
    //     $ticketHeight = 297 / 4; // Satu perempat dari tinggi kertas A4

    //     $pdf->AddPage(); // Tambahkan halaman pertama di sini

    //     for ($number = 1; $number <= $totalTickets; $number++) {
    //         // Jika bukan tiket pertama dan tiket adalah kelipatan dari 4, tambahkan halaman baru.
    //         if ($number != 1 && $number % 4 == 1) {
    //             $pdf->AddPage();
    //         }

    //         // Tentukan offset vertikal berdasarkan nomor tiket
    //         $yOffset = (($number - 1) % 4) * $ticketHeight; // Gunakan modulus (%) untuk mendapatkan offset dalam satu halaman

    //         // Dapatkan string acak yang di-encode dengan Base64 URL-safe
    //         $randomData = $this->generateRandomString(10);

    //         $qrCode = Builder::create()
    //             ->writer(new PngWriter())
    //             ->data($randomData)
    //             ->build();

    //         // Simpan QR Code ke file sementara
    //         $tempImage = tempnam(sys_get_temp_dir(), 'qrcode_') . '.png';
    //         $qrCode->saveToFile($tempImage);

    //         // Tambahkan gambar background tiket jika ada
    //         $pdf->Image(storage_path('app/public/test.jpg'), 0, $yOffset, $ticketWidth, $ticketHeight);

    //         // Tambahkan QR Code ke tiket
    //         $pdf->Image($tempImage, 167, 15 + $yOffset, 37, 37);

    //         // Tampilkan data QR code di bawah gambar QR code
    //         $xText = 167 + 3; // Posisi x untuk teks (Anda bisa menyesuaikannya)
    //         $yText = 15 + 37 + 5 + $yOffset;
    //         $pdf->SetFont('Arial', '', 10);
    //         $pdf->SetTextColor(0, 0, 0);  // Warna hitam
    //         $pdf->Text($xText, $yText, $randomData);

    //         unlink($tempImage);
    //     }

    //     $pdf->Output('F', storage_path('app/public/tickets/tickets.pdf'));
    // }



    //vesi 2 
    public function generateTickets()
    {
        $this->generateTicket();
        return response()->json("Selesai menghasilkan tiket!");
    }

    private function generateTicket()
    {
        $pdf = new \FPDF('P', 'mm', 'A4');

        // Ukuran tiket dalam milimeter
        $ticketWidth = 210; // Lebar penuh kertas A4
        $ticketHeight = 297 / 4; // Satu perempat dari tinggi kertas A4

        $pdf->AddPage(); // Tambahkan halaman pertama di sini

        for ($number = 200; $number <= 300; $number++) {
            $tiket = Tiket::create([
                'nomor_tiket' => $number,
                'status' => 0
            ]);
            $nomorTiket = $tiket->nomor_tiket;
            // Jika bukan tiket pertama dan tiket adalah kelipatan dari 4, tambahkan halaman baru.
            if ($number != 200 && ($number - 200) % 4 == 0) {
                $pdf->AddPage();
            }

            // Tentukan offset vertikal berdasarkan nomor tiket
            $yOffset = (($number - 200) % 4) * $ticketHeight; // Gunakan modulus (%) untuk mendapatkan offset dalam satu halaman

            // Dapatkan string acak yang di-encode dengan Base64 URL-safe
            $randomData = $nomorTiket;
            $qrCode = Builder::create()
                ->writer(new PngWriter())
                ->data($randomData)
                ->build();

            // Simpan QR Code ke file sementara
            $tempImage = tempnam(sys_get_temp_dir(), 'qrcode_') . '.png';
            $qrCode->saveToFile($tempImage);

            // Tambahkan gambar background tiket jika ada
            $pdf->Image(storage_path('app/public/coba.png'), 0, $yOffset, $ticketWidth, $ticketHeight);

            // Tambahkan QR Code ke tiket
            $pdf->Image($tempImage, 167, 15 + $yOffset, 37, 37);

            // Tampilkan data QR code di bawah gambar QR code
            $xText = 167 + 3; // Posisi x untuk teks (Anda bisa menyesuaikannya)
            $yText = 15 + 37 + 5 + $yOffset;
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetTextColor(0, 0, 0);  // Warna hitam
            $pdf->Text($xText, $yText, $randomData);

            unlink($tempImage);
        }

        $pdf->Output('F', storage_path('app/public/tickets/ticketssss.pdf'));
    }
}
