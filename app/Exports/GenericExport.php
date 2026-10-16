<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GenericExport implements FromCollection, WithHeadings
{
    protected $table;

    public function __construct($table)
    {
        $this->table = $table;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return DB::table($this->table)->get();
    }

    public function headings(): array
    {
        $first = DB::table($this->table)->first();
        if ($first) {
            return array_keys((array)$first);
        }
        return [];
    }
}
