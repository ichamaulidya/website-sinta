<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DosenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = 'C:\Users\asa yuaziva\Downloads\dosen.sql';
        
        if (!File::exists($path)) {
            $this->command->error("File not found: $path");
            return;
        }

        $content = File::get($path);
        
        // Extract INSERT INTO statement
        // Pattern: INSERT INTO `dosen` VALUES (...);
        if (preg_match('/INSERT INTO `dosen` VALUES (.*);/s', $content, $matches)) {
            $valuesString = $matches[1];
            
            // Split by ),( to get individual rows
            // We use a regex split to handle cases properly, though strict split might work if data is clean
            // Since it is SQL dump, we can rely on `),(` pattern
            $rows = explode('),(', $valuesString);
            
            $dataToInsert = [];
            foreach ($rows as $index => $row) {
                // Clean start and end parentheses for first and last items
                $row = trim($row, "()");
                
                // Parse CSV line
                // SQL values are like 'String', 'String', Number, NULL, ...
                // str_getcsv handles quoted strings well
                // We need to handle NULL string as null value
                
                $values = str_getcsv($row, ",", "'");
                
                // Map to columns: Nama, Bagian, NPI, NIDN, NUPTK, Sinta_ID
                // Convert 'NULL' string to null
                $mapNull = function($val) {
                    return ($val === 'NULL') ? null : $val;
                };
                
                $values = array_map($mapNull, $values);
                
                // Ensure we have 6 columns
                if (count($values) >= 6) {
                    $dataToInsert[] = [
                        'nama' => $values[0],
                        'bagian' => $values[1],
                        'npi' => $values[2],
                        'nidn' => $values[3],
                        'nuptk' => $values[4],
                        'sinta_id' => $values[5],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            
            // Insert in chunks to avoid memory issues
            foreach (array_chunk($dataToInsert, 100) as $chunk) {
                DB::table('dosens')->insert($chunk);
            }
            
            $this->command->info("Imported " . count($dataToInsert) . " dosens.");
        } else {
            $this->command->error("No INSERT INTO statement found in sql file.");
        }
    }
}
