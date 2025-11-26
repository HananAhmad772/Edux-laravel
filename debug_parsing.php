<?php
require_once 'vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Database\Capsule\Manager as Capsule;

// Create app container
$app = new Container();
$app->instance('app', $app);

// Create event dispatcher
$events = new Dispatcher($app);

// Create database capsule
$capsule = new Capsule($app);
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'edux_laravel',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$capsule->setEventDispatcher($events);
$capsule->setAsGlobal();
$capsule->bootEloquent();

try {
    // Get the latest roadmap
    $result = Capsule::select("SELECT id, roadmap_content FROM student_roadmaps ORDER BY created_at DESC LIMIT 1");
    
    if ($result) {
        $roadmapContent = $result[0]->roadmap_content;
        echo "=== DEBUGGING PARSING LOGIC ===\n";
        
        $lines = explode("\n", $roadmapContent);
        $currentStep = null;
        $currentSection = null;
        $stepPattern = '/^\*\*Week (\d+)–(\d+): (.+)\*\*$/';
        
        $stepCount = 0;
        $itemCount = 0;
        
        foreach ($lines as $lineNum => $line) {
            $trimmedLine = trim($line);
            
            // Check for step headings
            if (preg_match($stepPattern, $trimmedLine, $matches)) {
                $stepCount++;
                $stepName = "Week {$matches[1]}–{$matches[2]}";
                $stepTitle = $matches[3];
                
                echo "Line $lineNum: Found step heading - $stepName: $stepTitle\n";
                
                $currentStep = [
                    'name' => $stepName,
                    'title' => $stepTitle,
                    'topics' => [],
                    'tools' => [],
                    'skills' => [],
                    'tasks' => []
                ];
                
                $currentSection = null;
            } else if ($currentStep) {
                // Check for section headings
                if (strpos($trimmedLine, 'Topics to study') !== false) {
                    $currentSection = 'topics';
                    echo "Line $lineNum: Found topics section\n";
                } else if (strpos($trimmedLine, 'Tools to use') !== false) {
                    $currentSection = 'tools';
                    echo "Line $lineNum: Found tools section\n";
                } else if (strpos($trimmedLine, 'Skills learned') !== false) {
                    $currentSection = 'skills';
                    echo "Line $lineNum: Found skills section\n";
                } else if (strpos($trimmedLine, 'Mini practice tasks or micro-projects') !== false) {
                    $currentSection = 'tasks';
                    echo "Line $lineNum: Found tasks section\n";
                } else if ($currentSection && preg_match('/^\*\s+(.+)$/', $trimmedLine, $matches)) {
                    // Add item to current section
                    $itemCount++;
                    echo "Line $lineNum: Found item in $currentSection: " . trim($matches[1]) . "\n";
                } else if ($currentSection) {
                    echo "Line $lineNum: In $currentSection section, but didn't match item pattern. Line: '$trimmedLine'\n";
                }
            }
        }
        
        echo "\n=== SUMMARY ===\n";
        echo "Steps found: $stepCount\n";
        echo "Items found: $itemCount\n";
    } else {
        echo "No roadmap found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}