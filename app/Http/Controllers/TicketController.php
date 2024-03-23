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
use Fpdf\Fpdf;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function login()
    {
        return view('login');
    }

    public function loginDo(Request $request)
    {
        // dd($request);
        request()->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $credentials = $request->only('username', 'password');
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();
            return redirect()->route('index');
        }
        $request->session()->flash('error', 'Username atau password salah');
        return redirect()->route('login');
    }

    public function logoutDo(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('error', "Berhasil Logout");
    }

    public function index()
    {
        $tickets = Tiket::all();
        $tersedia = Tiket::where('status', 0)->count();
        $terpakai = Tiket::where('status', 1)->count();
        $total = Tiket::count();
        return view('index', compact('tickets', 'total', 'tersedia', 'terpakai'));
    }

    public function scan()
    {
        return view('scan');
    }

    public function scanDo(Request $request)
    {
        request()->validate([
            'nomor_tiket' => 'required',
        ]);
        $ticket = Tiket::where('nomor_tiket', $request->nomor_tiket)->first();
        if (!$ticket) {
            $request->session()->flash('title', 'Gagal Reedem Tiket!');
            $request->session()->flash('message', 'Maaf, Tiket ini tidak terdaftar!.');
            $request->session()->flash('alert', 'danger');
            $request->session()->flash('icon', 'icon fas fa-ban');
            return redirect()->route('scan');
        } else if ($ticket->status == 0) {
            $ticket->status = 1;
            $ticket->save();
            $nomor_tiket = $ticket->nomor_tiket;
            $request->session()->flash('title', 'Berhasil Reedem Tiket!');
            $request->session()->flash('message', 'Selamat, Tiket dengan Nomor ' . $nomor_tiket . ' Berhasil di Reedem');
            $request->session()->flash('alert', 'success');
            $request->session()->flash('icon', 'icon fas fa-check');
            return redirect()->route('scan');
        } else if ($ticket->status == 1) {
            $nomor_tiket = $ticket->nomor_tiket;
            $request->session()->flash('title', 'Gagal Reedem Tiket!');
            $request->session()->flash('message', 'Maaf, Tiket dengan Nomor ' . $nomor_tiket . ' sudah digunakan sebelumnya.');
            $request->session()->flash('alert', 'danger');
            $request->session()->flash('icon', 'icon fas fa-ban');
            return redirect()->route('scan');
        }
    }
    //vesi 2 
    // public function generateTickets()
    // {
    //     $this->generateTicket();
    //     return response()->json("Selesai menghasilkan tiket!");
    // }

    public function generate()
    {
        return view('generate');
    }

    public function generateTickets(Request $request)
    {
        // dd($request->gambar);
        request()->validate([
            'awal' => 'required',
            'akhir' => 'required',
            'gambar' => 'required|mimes:png,jpg',
        ]);

        $namaGambar = time() . '_' . $request->gambar->getClientOriginalName();
        $request->gambar->move(storage_path('app/public/template/'), $namaGambar);

        $pdf = new Fpdf('P', 'mm', 'A4');

        // Ukuran tiket dalam milimeter
        $ticketWidth = 150; // Lebar penuh kertas A4
        $ticketHeight = 70; // Satu perempat dari tinggi kertas A4

        // $ticketWidth = 210; // Lebar penuh kertas A4
        // $ticketHeight = 297 / 3; // Satu perempat dari tinggi kertas A4

        $pdf->AddPage(); // Tambahkan halaman pertama di sini

        Tiket::truncate();

        for ($number = $request->awal; $number <= $request->akhir; $number++) {
            // Periksa apakah nomor tiket sudah ada di database
            $existingTicket = Tiket::where('nomor_tiket', $number)->first();

            // Jika nomor tiket sudah ada, lanjutkan ke iterasi berikutnya
            if (!$existingTicket) {
                // continue;

                $tiket = Tiket::create([
                    'nomor_tiket' => $number,
                    'status' => 0
                ]);
            }

            // $tiket = Tiket::;

            $nomorTiket = $number;
            // Jika bukan tiket pertama dan tiket adalah kelipatan dari 4, tambahkan halaman baru.
            if ($number != $request->awal && ($number - $request->awal) % 4 == 0) {
                $pdf->AddPage();
            }

            // Tentukan offset vertikal berdasarkan nomor tiket
            $yOffset = (($number - $request->awal) % 4) * $ticketHeight; // Gunakan modulus (%) untuk mendapatkan offset dalam satu halaman

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
            $pdf->Image(storage_path('app/public/template/' . $namaGambar), 0, $yOffset, $ticketWidth, $ticketHeight);

            // Tambahkan QR Code ke tiket
            $pdf->Image($tempImage, 107.8, 15.5 + $yOffset, 35, 35);

            // Tampilkan data QR code di bawah gambar QR code
            $xText = 120; // Posisi x untuk teks (Anda bisa menyesuaikannya)
            $yText = 59 + $yOffset;
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->SetTextColor(255, 255, 255);  // Warna hitam
            $pdf->Text($xText, $yText, $randomData);
            unlink($tempImage);
        }

        // $pdf->Output('F', storage_path('app/public/tickets/ticketssss.pdf'));
        $pdf->Output('D', 'ticketssss.pdf');
    }


    public function generateKupon()
    {
        $pdf = new Fpdf('P', 'mm', 'A4');

        // Ukuran tiket dalam milimeter
        $ticketWidth = 85.6; // Lebar penuh kertas A4
        $ticketHeight = 54; // Satu perempat dari tinggi kertas A4

        $pdf->AddPage(); // Tambahkan halaman pertama di sini

        for ($number = 2001; $number <= 4000; $number++) {

            $nomorTiket = $number;
            // Jika bukan tiket pertama dan tiket adalah kelipatan dari 12, tambahkan halaman baru.
            if ($number != 2001 && ($number - 2001) % 10 == 0) {
                $pdf->AddPage();
            }

            // Tentukan offset vertikal dan horizontal berdasarkan nomor tiket
            $xOffset = (($number - 2001) % 2) * $ticketWidth; // Offset horizontal
            $yOffset = (floor(($number - 2001) / 2) % 5) * $ticketHeight; // Offset vertikal

            // Dapatkan string acak yang di-encode dengan Base64 URL-safe
            $randomData = $nomorTiket;

            // Tambahkan gambar background tiket jika ada
            $pdf->Image(public_path('kupon.png'), $xOffset, $yOffset, $ticketWidth, $ticketHeight);

            // Tampilkan data QR code di bawah gambar QR code
            // $xText = $xOffset + 5; // Posisi x untuk teks
            // $yText = $yOffset + 5.6;
            $xText = 72 + $xOffset; // Posisi x untuk teks (Anda bisa menyesuaikannya)
            $yText = 7 + $yOffset;
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->SetTextColor(255, 255, 255);  // Warna teks putih
            $pdf->Text($xText, $yText, $randomData);
        }

        $pdf->Output('F', storage_path('app/public/tickets/Kupon.pdf'));
        // $pdf->Output('D', 'Kupon Fix.pdf');
    }

    // public function generateKupon()
    // {

    //     $pdf = new Fpdf('P', 'mm', 'A4');

    //     // Ukuran tiket dalam milimeter
    //     $ticketWidth = 85.6; // Lebar penuh kertas A4
    //     $ticketHeight = 45; // Satu perempat dari tinggi kertas A4

    //     // $ticketWidth = 210; // Lebar penuh kertas A4
    //     // $ticketHeight = 297 / 3; // Satu perempat dari tinggi kertas A4

    //     $pdf->AddPage(); // Tambahkan halaman pertama di sini

    //     for ($number = 2001; $number <= 4000; $number++) {

    //         $nomorTiket = $number;
    //         // Jika bukan tiket pertama dan tiket adalah kelipatan dari 4, tambahkan halaman baru.
    //         if ($number != 2001 && ($number - 2001) % 6 == 0) {
    //             $pdf->AddPage();
    //         }

    //         // Tentukan offset vertikal berdasarkan nomor tiket
    //         $yOffset = (($number - 2001) % 6) * $ticketHeight; // Gunakan modulus (%) untuk mendapatkan offset dalam satu halaman

    //         // // Dapatkan string acak yang di-encode dengan Base64 URL-safe
    //         $randomData = $nomorTiket;


    //         // Tambahkan gambar background tiket jika ada
    //         $pdf->Image(public_path('kupon.png'), 0, $yOffset, $ticketWidth, $ticketHeight);

    //         // Tampilkan data QR code di bawah gambar QR code
    //         $xText = 72; // Posisi x untuk teks (Anda bisa menyesuaikannya)
    //         $yText = 5.6 + $yOffset;
    //         $pdf->SetFont('Arial', 'B', 14);
    //         // $pdf->SetTextColor(0, 0, 0);  // Warna hitam
    //         $pdf->SetTextColor(255, 255, 255);  // Warna hitam
    //         $pdf->Text($xText, $yText, $randomData);
    //     }

    //     $pdf->Output('F', storage_path('app/public/tickets/Kupon.pdf'));
    //     // $pdf->Output('D', 'Kupon.pdf');
    // }



    //veersi ga pakai insert db dan generate code random -------------------------
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

}
