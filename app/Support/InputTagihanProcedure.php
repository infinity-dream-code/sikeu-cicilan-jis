<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InputTagihanProcedure
{
    /**
     * Memanggil stored procedure InputTagihan di DATA_MYSQL.
     *
     * @see InputTagihan(p_NOCUST, p_NOMINAL, p_NMTagihan, p_BILLPERIOD, p_BTA, p_isNYICIL)
     */
    public static function call(
        string $nocust,
        int $nominal,
        string $nmTagihan,
        string $billPeriod,
        string $bta,
        string $isNyicil,
    ): void {
        $pdo = DB::connection('DATA_MYSQL')->getPdo();
        $stmt = $pdo->prepare('CALL InputTagihan(?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $nocust,
            $nominal,
            $nmTagihan,
            $billPeriod,
            $bta,
            $isNyicil,
        ]);

        do {
            $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } while ($stmt->nextRowset());

        Log::info('input-tagihan.procedure.ok', [
            'nocust' => $nocust,
            'nominal' => $nominal,
            'nmtagihan' => $nmTagihan,
            'billperiod' => $billPeriod,
            'bta' => $bta,
            'isnyicil' => $isNyicil,
        ]);
    }
}
