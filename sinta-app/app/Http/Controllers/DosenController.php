<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dosen;
use Illuminate\Support\Facades\Validator;

class DosenController extends Controller
{
    /**
     * Tampilkan halaman daftar dosen
     */
    public function index()
    {
        return view('daftar-dosen');
    }

    public function getAllDosen(Request $request)
    {
        $q = $request->input('q');

        $dosens = Dosen::selectRaw('
                id,
                Nama AS nama,
                Bagian AS departemen,
                NPI AS npi,
                NIDN AS nidn,
                NUPTK AS nuptk,
                Sinta_ID AS sinta_id
            ')
            ->when($q, function ($query) use ($q) {
                $query->where('Nama', 'LIKE', "%{$q}%")
                    ->orWhere('NPI', 'LIKE', "%{$q}%")
                    ->orWhere('NIDN', 'LIKE', "%{$q}%")
                    ->orWhere('NUPTK', 'LIKE', "%{$q}%")
                    ->orWhere('Sinta_ID', 'LIKE', "%{$q}%");
            })
            ->orderBy('Nama')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $dosens
        ]);
    }

    public function addDosen(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama'       => 'required|string|max:255',
            'sinta_id'   => 'nullable|string|max:50|unique:dosen,Sinta_ID',
            'npi'        => 'nullable|string|max:50',
            'nidn'       => 'nullable|string|max:50',
            'nuptk'      => 'nullable|string|max:50',
            'departemen' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }

        $dosen = Dosen::create([
            'Nama'      => $request->nama,
            'Sinta_ID'  => $request->sinta_id, // boleh null
            'NPI'       => $request->npi,
            'NIDN'      => $request->nidn,
            'NUPTK'     => $request->nuptk,
            'Bagian'    => $request->departemen,
        ]);

        return response()->json([
            'success' => true,
            'data' => $dosen,
            'message' => 'Dosen berhasil ditambahkan'
        ], 201);
    }

    public function updateDosen(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nama'       => 'required|string|max:255',
            'sinta_id'   => 'nullable|string|max:50|unique:dosen,Sinta_ID,' . $id,
            'npi'        => 'nullable|string|max:50',
            'nidn'       => 'nullable|string|max:50',
            'nuptk'      => 'nullable|string|max:50',
            'departemen' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }

        $dosen = Dosen::findOrFail($id);

        $dosen->update([
            'Nama'      => $request->nama,
            'Sinta_ID'  => $request->sinta_id, // bisa null → bisa diisi belakangan
            'NPI'       => $request->npi,
            'NIDN'      => $request->nidn,
            'NUPTK'     => $request->nuptk,
            'Bagian'    => $request->departemen,
        ]);

        return response()->json([
            'success' => true,
            'data' => $dosen,
            'message' => 'Dosen berhasil diperbarui'
        ]);
    }

    public function deleteDosen($id)
    {
        $dosen = Dosen::findOrFail($id);
        $dosen->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dosen berhasil dihapus'
        ]);
    }



}