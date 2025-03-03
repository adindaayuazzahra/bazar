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
        // $bottomMargin = 5;
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


            $nomorTiket = $number;
            // Jika bukan tiket pertama dan tiket adalah kelipatan dari 4, tambahkan halaman baru.
            if ($number != $request->awal && ($number - $request->awal) % 4 == 0) {
                $pdf->AddPage();
            }

            // Tentukan offset vertikal berdasarkan nomor tiket
            $yOffset = (($number - $request->awal) % 4) * $ticketHeight; // Gunakan modulus (%) untuk mendapatkan offset dalam satu halaman

            // // Tentukan offset vertikal berdasarkan nomor tiket
            // $yOffset = (($number - $request->awal) % 4) * ($ticketHeight + $bottomMargin);


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

        $pdf->Output('F', storage_path('app/public/tickets/ticketssss.pdf'));
        // $pdf->Output('D', 'ticketssss.pdf');
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



    public function generategelang(Request $request)
    {
        // dd($request->gambar);
        // request()->validate([
        //     'awal' => 'required',
        //     'akhir' => 'required',
        //     'gambar' => 'required|mimes:png,jpg',
        // ]);

        // $namaGambar = time() . '_' . $request->gambar->getClientOriginalName();
        // $request->gambar->move(storage_path('app/public/template/'), $namaGambar);

        $pdf = new Fpdf('L', 'mm', 'A4');

        // Ukuran tiket dalam milimeter
        $ticketWidth = 230; // Lebar penuh kertas A4
        $ticketHeight = 20; // Satu perempat dari tinggi kertas A4
        $bottomMargin = 3;
        // $ticketWidth = 210; // Lebar penuh kertas A4
        // $ticketHeight = 297 / 3; // Satu perempat dari tinggi kertas A4

        $pdf->AddPage(); // Tambahkan halaman pertama di sini

        Tiket::truncate();

        for ($number = 101; $number <= 250; $number++) {
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
            if ($number != 101 && ($number - 101) % 9 == 0) {
                $pdf->AddPage();
            }

            // Tentukan offset vertikal berdasarkan nomor tiket
            $yOffset = (($number - 101) % 9) * ($ticketHeight + $bottomMargin); // Gunakan modulus (%) untuk mendapatkan offset dalam satu halaman

            // // Tentukan offset vertikal berdasarkan nomor tiket
            // $yOffset = (($number - $request->awal) % 4) * ($ticketHeight + $bottomMargin);


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
            $pdf->Image(storage_path('app/public/template/gelangbawah.png'), 0, $yOffset, $ticketWidth, $ticketHeight);

            // Tambahkan QR Code ke tiket
            $pdf->Image($tempImage, 180, 1 + $yOffset, 16, 16);

            //Tampilkan data QR code di bawah gambar QR code
            $xText = 186; // Posisi x untuk teks (Anda bisa menyesuaikannya)
            $yText = 19 + $yOffset;
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->SetTextColor(0, 0, 0);  // Warna hitam
            $pdf->Text($xText, $yText, $randomData);

            unlink($tempImage);
        }

        $pdf->Output('F', storage_path('app/public/tickets/ticketssss.pdf'));
        // $pdf->Output('D', 'ticketssss.pdf');
    }
}
