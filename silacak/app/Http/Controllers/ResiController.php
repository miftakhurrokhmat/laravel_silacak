<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimpanResiRequest;
use App\Models\Cabang;
use App\Models\Layanan;
use App\Models\Pelanggan;
use App\Models\Resi;
use App\Services\ResiService;
use Illuminate\Http\Request;

class ResiController extends Controller
{
    public function __construct(private ResiService $resiService)
    {}

    public function index(Request $request)
    {
        $resi = Resi::with(['pelanggan', 'cabangAsal', 'cabangTujuan', 'layanan'])
            ->cari($request->query('q'))
            ->status($request->query('status'))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('resi.index', compact('resi'));
    }

    public function create()
    {
        return view('resi.create', [
            'pelanggan' => Pelanggan::orderBy('nama')->get(),
            'cabang' => Cabang::orderBy('nama')->get(),
            'layanan' => Layanan::aktif()->orderBy('nama')->get(),
        ]);
    }

    public function store(SimpanResiRequest $request)
    {
        $resi = $this->resiService->buatResi($request->validated(), $request->user()->id);
        return redirect()->route('resi.show', $resi)
            ->with('sukses', "Resi {$resi->nomor_resi} berhasil dibuat.");
    }

    public function show(Resi $resi)
    {
        $resi->load(['pelanggan', 'cabangAsal', 'cabangTujuan', 'layanan', 'user', 'trackingLog.user']);
        return view('resi.show', compact('resi'));
    }

    public function edit(Resi $resi)
    {
        return view('resi.edit', [
            'resi' => $resi,
            'pelanggan' => Pelanggan::orderBy('nama')->get(),
            'cabang' => Cabang::orderBy('nama')->get(),
            'layanan' => Layanan::aktif()->orderBy('nama')->get(),
        ]);
    }

    public function update(SimpanResiRequest $request, Resi $resi)
    {
        $resi->update($request->validated());
        return redirect()->route('resi.show', $resi)->with('sukses', 'Resi diperbarui.');
    }

    public function destroy(Resi $resi)
    {
        // $nomor = $resi->nomor_resi;
        // $resi->delete();
        // return back()->with('sukses', "Resi {$nomor} dihapus.");

        try {
            $konteks = [
                'resi_id' => $resi->id,
                'nomor_resi' => $resi->nomor_resi,
                'nama_penerima' => $resi->nama_penerima,
                'total_biaya' => $resi->total_biaya,
                'dihapus_oleh' => auth()->user()->email,
                'ip_address' => request()->ip(),
                'waktu' => now()->toIso8601String(),
            ];

            $resi->delete();

            Log::info('resi.dihapus', $konteks);

            return back()->with('sukses', "Resi {$konteks['nomor_resi']} dihapus.");
        } catch (\Throwable $e) {
            Log::error('resi.gagal_hapus', [
                'resi_id' => $resi->id,
                'pesan_error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->withErrors('Gagal menghapus resi.');
        }
    }
}
