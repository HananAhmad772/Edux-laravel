<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckRoadmapSchemaCommand extends Command
{
    protected $signature = 'check:roadmap-schema';
    protected $description = 'Check the student_roadmaps table schema';

    public function handle()
    {
        $this->info('Checking student_roadmaps table schema...');
        
        try {
            $columns = DB::select("SHOW COLUMNS FROM student_roadmaps");
            
            $this->info('Table columns:');
            foreach ($columns as $column) {
                $this->line("- {$column->Field}: {$column->Type} " . 
                    ($column->Key === 'PRI' ? '(PRIMARY)' : '') . 
                    ($column->Null === 'NO' ? ' NOT NULL' : ' NULL') .
                    ($column->Extra ? " {$column->Extra}" : ''));
            }
            
            // Check if the migrations have been run
            $migrations = DB::table('migrations')->where('migration', 'like', '%student_roadmaps%')->get();
            $this->info("\nMigration status:");
            foreach ($migrations as $migration) {
                $this->line("- {$migration->migration}: " . ($migration->batch ? "Run in batch {$migration->batch}" : 'Not run'));
            }
            
        } catch (\Exception $e) {
            $this->error("Error checking schema: " . $e->getMessage());
        }
        
        return 0;
    }
}