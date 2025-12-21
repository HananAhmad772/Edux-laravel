<?php
require_once 'vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Database\Capsule\Manager as Capsule;

// Create a service container
$container = new Container();

// Create a dispatcher
$events = new Dispatcher($container);

// Create a capsule manager instance
$capsule = new Capsule($container);

// Add a connection
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'edux',
    'username'  => 'root',
    'password' => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

// Make this Capsule instance available globally via static methods
$capsule->setAsGlobal();

// Setup the Eloquent ORM
$capsule->bootEloquent();

// Test badge functionality
echo "Testing badge functionality...\n";

// Count badges
$badgeCount = \App\Models\Badge::count();
echo "Total badges: " . $badgeCount . "\n";

// Get all badges
$badges = \App\Models\Badge::all();
foreach ($badges as $badge) {
    echo "Badge: " . $badge->name . " (" . $badge->description . ")\n";
}

echo "Test completed.\n";
?>