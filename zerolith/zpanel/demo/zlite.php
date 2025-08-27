<?php

require "../../zl_init.php";
require "index.nav.php"; //navigation menu

zpage::start("zlite test");

echo '<pre>';

// Initialize the zlite connection
if (!zlite::init('zl_test.sqlite')) { die("Failed to initialize SQLite connection.");}

// must have the table first!
zlite::exec('DROP TABLE IF EXISTS zl_test_table');
zlite::exec('DROP TABLE IF EXISTS zl_test_users');
zlite::exec('CREATE TABLE `zl_test_users` (`name` VARCHAR(100), `email` VARCHAR(100), `age` INTEGER)');


// Test 1: Insert new rows into a table
$insertData = [
    'name' => 'Alice',
    'email' => 'alice.foo@example.com',
    'age' => 30
];

if (zlite::writeRow('INSERT', 'zl_test_users', $insertData)) { echo "Insert successful. Last insert ID: " . zlite::getLastInsertID() . "\n"; } 
else { echo "Insert failed.\n"; }

$insertData = [
    'name' => 'Bob',
    'email' => 'bob.bar@example.com',
    'age' => 50
];

if (zlite::writeRow('INSERT', 'zl_test_users', $insertData)) { echo "Insert successful. Last insert ID: " . zlite::getLastInsertID() . "\n"; } 
else { echo "Insert failed.\n"; }


// Test 2: Fetch a single row from the table
$user = zlite::getRow("SELECT * FROM zl_test_users WHERE email = 'alice.foo@example.com'");
if ($user) { echo "Fetched user: " . print_r($user, true) . "\n"; } 
else { echo "Failed to fetch user.\n"; }

// Test 3: Update a row in the table
$updateData = ['age' => 31];
$whereData = ['email' => 'alice.foo@example.com'];

if (zlite::writeRow('UPDATE', 'zl_test_users', $updateData, $whereData)) { echo "Update successful.\n"; } 
else { echo "Update failed.\n"; }

// Test 4: Fetch all rows from the table
$zl_test_users = zlite::getArray("SELECT * FROM zl_test_users");
if ($zl_test_users) { echo "All users: " . print_r($zl_test_users, true) . "\n"; } 
else { echo "Failed to fetch users.\n";}

// Test 5: Fetch a single field from the table
$email = zlite::getField("SELECT email FROM zl_test_users WHERE name = 'Alice'");
if ($email) { echo "Fetched email: $email\n"; } 
else { echo "Failed to fetch email.\n"; }

// Test 6: Delete a row from the table
if (zlite::writeRow('DELETE', 'zl_test_users', [], $whereData)) { echo "Delete successful.\n"; } 
else { echo "Delete failed.\n"; }

// Test 7: Execute a custom SQL statement
if (zlite::writeSQL("CREATE TABLE IF NOT EXISTS zl_test_table (id INTEGER PRIMARY KEY, name TEXT)")) { echo "Custom SQL execution successful.\n"; } 
else { echo "Custom SQL execution failed.\n"; }

echo '</pre>';
?>