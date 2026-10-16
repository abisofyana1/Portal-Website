<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = DB::select("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'");
$sql = "";
foreach ($tables as $t) {
    $table = $t->tablename;
    // Skip laravel default tables if we want a cleaner ERD, but let's include everything
    // except maybe migrations
    if ($table == 'migrations') continue;
    
    $sql .= "CREATE TABLE {$table} (\n";
    $columns = DB::select("SELECT column_name, data_type, character_maximum_length FROM information_schema.columns WHERE table_name = ?", [$table]);
    
    $cols = [];
    foreach ($columns as $c) {
        $type = strtoupper($c->data_type);
        if ($c->character_maximum_length) {
            $type .= "(" . $c->character_maximum_length . ")";
        }
        // Normalize some types for drawio
        if ($type == 'CHARACTER VARYING') $type = 'VARCHAR(' . $c->character_maximum_length . ')';
        if ($type == 'TIMESTAMP WITHOUT TIME ZONE') $type = 'TIMESTAMP';
        if ($type == 'USER-DEFINED') $type = 'VARCHAR'; // enum fallback
        $cols[] = "  {$c->column_name} {$type}";
    }
    
    // get primary keys
    $pks = DB::select("
        SELECT kcu.column_name
        FROM information_schema.table_constraints tc
        JOIN information_schema.key_column_usage kcu
          ON tc.constraint_name = kcu.constraint_name
          AND tc.table_schema = kcu.table_schema
        WHERE tc.constraint_type = 'PRIMARY KEY' AND tc.table_name = ?
    ", [$table]);
    
    if (count($pks) > 0) {
        $cols[] = "  PRIMARY KEY (" . implode(', ', array_map(function($p) { return $p->column_name; }, $pks)) . ")";
    }
    
    // get foreign keys
    $fks = DB::select("
        SELECT
            kcu.column_name,
            ccu.table_name AS foreign_table_name,
            ccu.column_name AS foreign_column_name
        FROM 
            information_schema.table_constraints AS tc 
            JOIN information_schema.key_column_usage AS kcu
              ON tc.constraint_name = kcu.constraint_name
              AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage AS ccu
              ON ccu.constraint_name = tc.constraint_name
              AND ccu.table_schema = tc.table_schema
        WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_name = ?
    ", [$table]);
    
    foreach ($fks as $fk) {
        $cols[] = "  FOREIGN KEY ({$fk->column_name}) REFERENCES {$fk->foreign_table_name}({$fk->foreign_column_name})";
    }
    
    $sql .= implode(",\n", $cols);
    $sql .= "\n);\n\n";
}

file_put_contents('drawio_schema.sql', $sql);
echo "Done";
