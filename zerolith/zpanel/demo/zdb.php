<?php

require "../../zl_init.php";
require "index.nav.php"; //navigation menu

//could be endpoint for abusing a production machine, so..
if(zl_mode == "prod") { exit("Can't run this test in production mode."); }

zpage::start("zdb test");

$tb_sample = 'zdb_test_table_sample'; $tb_users = 'zdb_test_table_users';

echo '<pre>';
echo 
"
------------------------------------------------------------------------

A collection of simple tests to test these zdb functions:
- zdb::writeRow()
- zdb::writeSQL()
- zdb::getField()
- zdb::getRow()
- zdb::getArray()
- zdb::getFieldSafe()
- zdb::getRowSafe()
- zdb::getArraySafe()

Will create two temporary tables: `$tb_sample` and `$tb_users`

------------------------------------------------------------------------
\n\n";

// Test 1: Execute a custom SQL statement
if (
    zdb::writeSQL("DROP TABLE IF EXISTS $tb_sample")
    && zdb::writeSQL("DROP TABLE IF EXISTS $tb_users")
    && zdb::writeSQL("CREATE TABLE IF NOT EXISTS `$tb_sample` (`id` INTEGER PRIMARY KEY, `name` TEXT)")
    && zdb::writeSQL("CREATE TABLE `$tb_users` (`id` INTEGER AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(100), `email` VARCHAR(100), `age` INTEGER)")
) 
{ echo "[OK] writeSQL(): Custom SQL execution successful. Test tables created.\n"; } 
else { echo "[ERR] writeSQL(): Custom SQL execution failed.\n"; }

// Test 2: Insert new rows into a table
$insertData = [
    'name' => 'Alice',
    'email' => 'alice.foo@example.com',
    'age' => 30
];

if (zdb::writeRow('INSERT', $tb_users, $insertData, [])) { echo "[OK] writeRow(): Insert successful. Last insert ID: " . zdb::getLastInsertID($tb_users) . "\n"; } 
else { echo "[ERR] writeRow(): Insert failed.\n"; }

$insertData = [
    'name' => 'Bob',
    'email' => 'bob.bar@example.com',
    'age' => 50
];

if (zdb::writeRow('INSERT', $tb_users, $insertData)) { echo "[OK] writeRow(): Insert successful. Last insert ID: " . zdb::getLastInsertID($tb_users) . "\n"; }
else { echo "[ERR] writeRow(): Insert failed.\n"; }

$insertData = [
    'name' => "Charlie O'dea",
    'email' => 'charlie.baz@example.com',
    'age' => 39
];

if (zdb::writeRow('INSERT', $tb_users, $insertData)) { echo "[OK] writeRow(): Insert successful. Last insert ID: " . zdb::getLastInsertID($tb_users) . "\n"; }
else { echo "[ERR] writeRow(): Insert failed.\n"; }


// Test 3: Fetch a single row from the table
$user = zdb::getRow("SELECT * FROM $tb_users WHERE email = 'alice.foo@example.com'");
if ($user) { echo "[OK] getRow(): Fetched user: " . print_r($user, true) . "\n"; }
else { echo "[ERR] getRow(): Failed to fetch user.\n"; }

// Test 4: Update a row in the table
$updateData = ['age' => 31];
$whereData = ['email' => 'alice.foo@example.com' ];

if (zdb::writeRow('UPDATE', $tb_users, $updateData, $whereData)) { echo "[OK] writeRow(): Update successful.\n";}
else { echo "[ERR] writeRow(): Update failed.\n"; }

// Test 4: Fetch all rows from the table
$users = zdb::getArray("SELECT * FROM $tb_users");
if ($users) { echo "[OK] getArray(): All users: " . print_r($users, true) . "\n"; } 
else { echo "[ERR] getArray(): Failed to fetch users.\n"; }

// Test 4: Fetch single row from the table
$user = zdb::getRow("SELECT * FROM $tb_users WHERE email = 'bob.bar@example.com'");
if ($user) { echo "[OK] getRow(): Fetched Bob's data: " . print_r($user, TRUE) . "\n"; }
else { echo "[ERR] getRow(): Failed to fetch Bob's data.\n"; }

// Test 5: Fetch a single field from the table
$email = zdb::getField("SELECT email FROM $tb_users WHERE name = 'Alice'");
if ($email) { echo "[OK] getField(): Fetched Alice's email: $email\n"; }
else { echo "[ERR] getField(): Failed to fetch email.\n"; }

// Test 4: Fetch rows from the table (with prepared statement)
$users = zdb::getArraySafe("SELECT * FROM $tb_users WHERE age <= ?", [40]);
if ($users) { echo "[OK] getArraySafe(): Users with age <= 40: " . print_r($users, true) . "\n"; }
else { echo "[ERR] getArraySafe(): Failed to fetch users.\n"; }

// Test 4: Fetch single row from the table (with prepared statement)
$user = zdb::getRowSafe("SELECT * FROM $tb_users WHERE email = ? AND age = ?", ['bob.bar@example.com', 50]);
if ($user) { echo "[OK] getRowSafe(): Fetched Bob's data: " . print_r($user, TRUE) . "\n"; }
else { echo "[ERR] getRowSafe(): Failed to fetch Bob's data.\n"; }

// Test 5: Fetch a single field from the table, using statement
$search = "%O'dea%";
$email = zdb::getFieldSafe("SELECT email FROM $tb_users WHERE name LIKE ?", [$search]);
if ($email) { echo "[OK] getFieldSafe(): Fetched Charlie's email: $email\n"; }
else { echo "[ERR] getFieldSafe(): Failed to fetch email.\n"; }

// Test 6: Delete a row from the table
if (zdb::writeRow('DELETE', $tb_users, [], $whereData)) { echo "[OK] writeRow(): Delete successful.\n"; }
else { echo "[ERR] writeRow(): Delete failed.\n"; }

// Test 7: Tear down the test tables
if (zdb::writeSQL("DROP TABLE IF EXISTS $tb_sample") && zdb::writeSQL("DROP TABLE IF EXISTS $tb_users"))
{ echo "[OK] writeSQL(): Test tables dropped.\n"; } 
else { echo "[ERR] writeSQL(): Failed to drop test tables.\n"; }
?>
</pre>
