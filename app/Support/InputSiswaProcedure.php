<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InputSiswaProcedure
{
    /**
     * @see InputSiswa(p_NIMRAW, p_NMCUSTRAW, p_DESC02, p_CODE02, p_CODE01,
     *      p_DESC03, p_DESC04, p_DESC05, p_CODE04, p_GENUS, p_CODE05)
     */
    public static function call(
        string $nimRaw,
        string $namaRaw,
        string $desc02,
        string $code02,
        string $code01,
        string $desc03,
        string $angkatan,
        ?string $alamat = null,
        ?string $gender = null,
        ?string $ortu = null,
        ?string $code05 = null,
    ): void {
        $pdo = DB::connection('DATA_MYSQL')->getPdo();
        $stmt = $pdo->prepare('CALL InputSiswa(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $nimRaw,
            $namaRaw,
            $desc02,
            $code02,
            $code01,
            $desc03,
            $angkatan,
            (string) ($alamat ?? ''),
            (string) ($gender ?? ''),
            (string) ($ortu ?? ''),
            (string) ($code05 ?? ''),
        ]);

        do {
            $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } while ($stmt->nextRowset());

        Log::info('input-siswa.procedure.ok', [
            'nis' => $nimRaw,
            'unit' => $code02,
            'kelas' => $desc02,
            'kelompok' => $desc03,
            'sekolah' => $code01,
            'angkatan' => $angkatan,
        ]);
    }
}
