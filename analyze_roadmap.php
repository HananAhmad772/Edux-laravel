<?php
require_once 'vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Database\Capsule\Manager as Capsule;

// Set up Laravel's Eloquent ORM
$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'database'  => 'edux_laravel',
    'username' => 'root',
    'password'  => '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);

$capsule->setEventDispatcher(new Dispatcher(new Container));
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Get the latest roadmap
$roadmap = Capsule::table('student_roadmaps')->orderBy('created_at', 'desc')->first();

if ($roadmap) {
    echo "=== ROADMAP ANALYSIS ===\n";
    echo "Latest roadmap ID: " . $roadmap->id . "\n";
    echo "Roadmap content length: " . strlen($roadmap->roadmap_content) . " characters\n\n";
    
    // Parse the roadmap content using the same logic as in ProfileService
    $steps = [];
    $lines = explode("\n", $roadmap->roadmap_content);
    $currentStep = null;
    $currentSection = null;
    $stepPattern = '/^\*\*Week (\d+)–(\d+): (.+)\*\*$/';
    
    foreach ($lines as $line) {
        $trimmedLine = trim($line);
        
        // Check for step headings
        if (preg_match($stepPattern, $trimmedLine, $matches)) {
            $stepName = "Week {$matches[1]}–{$matches[2]}";
            $stepTitle = $matches[3];
            
            $currentStep = [
                'name' => $stepName,
                'title' => $stepTitle,
                'topics' => [],
                'tools' => [],
                'skills' => [],
                'tasks' => []
            ];
            
            $steps[$stepName] = $currentStep;
            $currentSection = null;
            echo "Found step: $stepName - {$matches[3]}\n";
        } else if ($currentStep) {
            // Check for section headings
            if (trim($line) === '- Topics to study:') {
                $currentSection = 'topics';
                echo "  Found Topics to study section\n";
            } else if (trim($line) === '- Tools to use:') {
                $currentSection = 'tools';
                echo "  Found Tools to use section\n";
            } else if (trim($line) === '- Skills learned:') {
                $currentSection = 'skills';
                echo "  Found Skills learned section\n";
            } else if (trim($line) === '- Mini practice tasks or micro-projects:') {
                $currentSection = 'tasks';
                echo "  Found Mini practice tasks section\n";
            } else if ($currentSection && preg_match('/^\s*\*\s+(.+)$/', $line, $matches)) {
                // Add item to current section
                $steps[$currentStep['name']][$currentSection][] = trim($matches[1]);
                echo "    Added {$currentSection} item: " . trim($matches[1]) . "\n";
            } else if (preg_match($stepPattern, $trimmedLine)) {
                // New step heading, reset section
                $currentSection = null;
            } else if (preg_match('/^- [^:]+:$/', $trimmedLine)) {
                // New section heading, reset section
                $currentSection = null;
            }
        }
    }
    
    echo "\n=== STEP SUMMARY ===\n";
    echo "Total steps found: " . count($steps) . "\n";
    
    foreach ($steps as $stepName => $stepData) {
        echo "\n$stepName: {$stepData['title']}\n";
        echo "  Topics: " . count($stepData['topics']) . "\n";
        foreach ($stepData['topics'] as $i => $topic) {
            echo "    " . ($i + 1) . ". $topic\n";
        }
        echo "  Tools: " . count($stepData['tools']) . "\n";
        foreach ($stepData['tools'] as $i => $tool) {
            echo "    " . ($i + 1) . ". $tool\n";
        }
        echo "  Skills: " . count($stepData['skills']) . "\n";
        foreach ($stepData['skills'] as $i => $skill) {
            echo "    " . ($i + 1) . ". $skill\n";
        }
        echo "  Tasks: " . count($stepData['tasks']) . "\n";
        foreach ($stepData['tasks'] as $i => $task) {
            echo "    " . ($i + 1) . ". $task\n";
        }
    }
    
    echo "\n=== STRUCTURE VALIDATION ===\n";
    $issues = [];
    
    // Check if we have exactly 6 topics per step
    foreach ($steps as $stepName => $stepData) {
        $topicCount = count($stepData['topics']);
        if ($topicCount != 6) {
            $issues[] = "Step '$stepName' has $topicCount topics instead of exactly 6";
        }
    }
    
    if (empty($issues)) {
        echo "✓ No structural issues found\n";
    } else {
        echo "✗ Structural issues found:\n";
        foreach ($issues as $issue) {
            echo "  - $issue\n";
        }
    }
    
} else {
    echo "No roadmaps found in database.\n";
}