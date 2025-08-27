<?
if(strstr(__FILE__, $_SERVER['PHP_SELF'])) { header('Location: /zerolith/zpanel/test'); die(); } // Redirect to Zerolith Tests

//__________                  .__  .__  __  .__
//\____    /___________  ____ |  | |__|/  |_|  |__
//  /     // __ \_  __ \/  _ \|  | |  \   __\  |  \
// /     /\  ___/|  | \(  <_> )  |_|  ||  | |   Y  \
///_______ \___  >__|   \____/|____/__||__| |___|  /
//        \/   \/                                \/
// zarray tests.

require dirname(__FILE__).'/../../../zl_init.php';

$data =
[
	["saladID" => "1", "name" => "caesar", "leaf" => "iceberg mix", "dressing" => "caesar"],
	["saladID" => "2", "name" => "hippie", "leaf" => "kale", "dressing" => "overpriced yogurt"],
	["saladID" => "3", "name" => "cobb", "leaf" => "iceberg", "dressing" => "ranch"]
];
ztest::test(zarr::where($data, 'name', 'caesar')['saladID'], '==', 1 , 'zarr::where() row returned');
ztest::test(zarr::where($data, 'name', 'cae1sar'), '==', 0, 'zarr::where() row does not exist');

$data =
[
	["saladID" => "1", "name" => "caesar", "leaf" => "iceberg mix", "dressing" => "caesar"],
	["saladID" => "2", "name" => "hippie", "leaf" => "kale", "dressing" => "overpriced yogurt"],
	["saladID" => "3", "name" => "cobb", "leaf" => "iceberg", "dressing" => "ranch"]
];
zarr::orderBy($data, 'name', 'ASC');
ztest::test($data[2]['name'], '==', 'hippie', 'zarr::orderBy() ASC');
zarr::orderBy($data, 'name', 'DESC');
ztest::test($data[0]['name'], '==', 'hippie', 'zarr::orderBy() DESC');

$data =
[
	["saladID" => "1", "name" => "caesar", "leaf" => "iceberg mix", "dressing" => "caesar"],
	["saladID" => "2", "name" => "hippie", "leaf" => "kale", "dressing" => "overpriced yogurt"],
	["saladID" => "3", "name" => "cobb", "leaf" => "iceberg", "dressing" => "ranch"]
];
ztest::test(zarr::flatten($data)[5], '==', 'hippie', 'zarr::flatten()');

