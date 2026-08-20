<?php

namespace App\Imports\Keuangan\TagihanSiswa;

use App\Models\scctcust;
use App\Support\SchoolScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportTagihanExcel implements ToCollection, WithHeadingRow
{
    public const CACHE_KEY = 'import_tagihan_excel';

    public const REQUIRED_COLUMNS = [
        'nocust',
        'nominal',
        'nmtagihan',
        'billperiod',
        'bta',
        'isnyicil',
    ];

    public function __construct(public ?string $sekolah = null)
    {
    }

    public function collection(Collection $collection): void
    {
        $processedData = [];

        foreach ($collection as $row) {
            if ($row->filter()->isEmpty()) {
                continue;
            }

            $rowData = $this->normalizeRow($row->toArray());
            $rowData['status'] = 1;
            $statusKet = [];

            $nocust = $this->excelString($rowData['nocust'] ?? null);
            $rowData['nocust'] = $nocust;
            $rowData['nis'] = $nocust;

            if ($nocust === '') {
                $rowData['status'] = 0;
                $statusKet[] = 'NOCUST tidak boleh kosong';
            } else {
                $siswa = scctcust::where('NOCUST', $nocust);
                SchoolScope::apply($siswa, 'scctcust', $this->sekolah);
                $siswa = $siswa->first();
                if (!$siswa) {
                    $rowData['status'] = 0;
                    $statusKet[] = "NOCUST {$nocust} tidak ditemukan";
                }
            }

            $nominal = $this->excelInteger($rowData['nominal'] ?? null);
            $rowData['nominal'] = $nominal;
            if ($nominal === null || $nominal <= 0) {
                $rowData['status'] = 0;
                $statusKet[] = 'NOMINAL tidak boleh kosong / harus lebih dari 0';
            }

            $nmTagihan = $this->excelString($rowData['nmtagihan'] ?? null);
            $rowData['nmtagihan'] = $nmTagihan;
            if ($nmTagihan === '') {
                $rowData['status'] = 0;
                $statusKet[] = 'NMTagihan tidak boleh kosong';
            } elseif (mb_strlen($nmTagihan) > 30) {
                $rowData['status'] = 0;
                $statusKet[] = 'NMTagihan maksimal 30 karakter';
            }

            $billPeriod = $this->excelString($rowData['billperiod'] ?? null);
            $rowData['billperiod'] = $billPeriod;
            if ($billPeriod === '') {
                $rowData['status'] = 0;
                $statusKet[] = 'BILLPERIOD tidak boleh kosong';
            }

            $bta = $this->excelString($rowData['bta'] ?? null);
            $rowData['bta'] = $bta;
            if ($bta === '') {
                $rowData['status'] = 0;
                $statusKet[] = 'BTA tidak boleh kosong';
            }

            $isNyicil = $this->excelString($rowData['isnyicil'] ?? null);
            $rowData['isnyicil'] = $isNyicil;
            if ($isNyicil === '') {
                $rowData['status'] = 0;
                $statusKet[] = 'isNYICIL tidak boleh kosong';
            }

            $rowData['keterangan'] = $statusKet === [] ? null : implode(', ', $statusKet);
            $processedData[] = $rowData;
        }

        if (!empty($processedData)) {
            Cache::put(self::CACHE_KEY, $processedData, now()->addMinutes(60));
        }
    }

    public function headingRow(): int
    {
        return 1;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[$this->normalizeKey((string) $key)] = $value;
        }

        $aliases = [
            'nocust' => ['nocust', 'no_cust', 'nis'],
            'nominal' => ['nominal'],
            'nmtagihan' => ['nmtagihan', 'nm_tagihan', 'nama_tagihan'],
            'billperiod' => ['billperiod', 'bill_period'],
            'bta' => ['bta'],
            'isnyicil' => ['isnyicil', 'is_nyicil'],
        ];

        $mapped = [];
        foreach ($aliases as $canonical => $keys) {
            $mapped[$canonical] = $this->firstValue($normalized, $keys);
        }

        return array_merge($normalized, $mapped);
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function firstValue(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return null;
    }

    private function normalizeKey(string $key): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', $key) ?? $key);
    }

    private function excelString(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_numeric($value)) {
            $number = (float) $value;
            if ($number == floor($number)) {
                return sprintf('%.0f', $number);
            }
        }

        return trim((string) $value);
    }

    private function excelInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = is_string($value)
            ? str_replace(['.', ',', ' '], '', $value)
            : $value;

        if (!is_numeric($raw)) {
            return null;
        }

        return (int) $raw;
    }
}
