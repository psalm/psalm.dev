<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../vendor/autoload.php');
// not included in autoload

if (!isset($_POST['code'])) {
    exit;
}

if (!isset($_POST['settings'])) {
    exit;
}

$fix_file = $_POST['fix'] ?? false;

$settings = json_decode($_POST['settings'], true);
if (!is_array($settings)) {
    exit;
}

const PHP_PARSER_VERSION = '4.0.0';

// Set user-defined error handler function
set_exception_handler([\PsalmDotOrg\ExceptionHandler::class, 'json']);

$file_contents = $_POST['code'];

$file_hash = md5($file_contents);

$cached_results = [
    '{"results":[{"severity":"error","line_from":7,"line_to":7,"type":"InvalidReturnStatement","message":"The inferred type \'array{0: int, 1: string(hello)}\' does not match the declared return type \'array<array-key, string>\' for takesAnInt","file_name":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/..\/src\/somefile.php","file_path":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/..\/src\/somefile.php","snippet":"    return [$i, \"hello\"];","selected_text":"[$i, \"hello\"]","from":81,"to":94,"snippet_from":70,"snippet_to":95,"column_from":12,"column_to":25,"error_level":6},{"severity":"error","line_from":4,"line_to":4,"type":"InvalidReturnType","message":"The declared return type \'array<array-key, string>\' for takesAnInt is incorrect, got \'array{0: int, 1: string}\'","file_name":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/..\/src\/somefile.php","file_path":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/..\/src\/somefile.php","snippet":" * @return array<string>","selected_text":"array<string>","from":22,"to":35,"snippet_from":11,"snippet_to":35,"column_from":12,"column_to":25,"error_level":6},{"severity":"error","line_from":11,"line_to":11,"type":"InvalidScalarArgument","message":"Argument 1 of takesAnInt expects int, string(some text) provided","file_name":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/..\/src\/somefile.php","file_path":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/..\/src\/somefile.php","snippet":"takesAnInt($data[0]);","selected_text":"$data[0]","from":136,"to":144,"snippet_from":125,"snippet_to":146,"column_from":12,"column_to":20,"error_level":4},{"severity":"error","line_from":15,"line_to":15,"type":"TypeDoesNotContainType","message":"Found a contradiction when evaluating $condition and trying to reconcile type \'int(0)\' to !falsy","file_name":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/..\/src\/somefile.php","file_path":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/..\/src\/somefile.php","snippet":"} elseif ($condition) {}","selected_text":"$condition","from":201,"to":211,"snippet_from":191,"snippet_to":215,"column_from":11,"column_to":21,"error_level":4}],"version":"dev-master@f532dc316cd7e4be441bcea8b4bd8f55708fe174","fixed_contents":null,"hash":"6ca8d27d14546ec754fd9c04636bfb80","type_map":[{"from":82,"to":84,"type":"int"},{"from":125,"to":135,"type":"array<array-key, string>"},{"from":136,"to":141,"type":"array{0: string(some text), 1: int(5)}"},{"from":161,"to":171,"type":"int"},{"from":177,"to":187,"type":"int"},{"from":201,"to":211,"type":"int(0)"}]}'
];

if (!$fix_file) {
    foreach ($cached_results as $cached_result) {
        $decoded = json_decode($cached_result, true);

        if ($decoded['hash'] === $file_hash) {
            echo $cached_result;
            exit;
        }
    }
}

if (strlen($file_contents) > 100000) {
    header(sprintf('HTTP/1.0 %s', 418));
    echo json_encode(['error' => 'Code too long']);
    exit;
}

$php_version = $_POST['php'] ?? '8.0';
		
if (!preg_match('/^[57]\.\d$/', $php_version)) {
    echo json_encode(['error' => 'PHP version ' . $php_version . ' not supported']);
    exit;
}

echo json_encode(PsalmDotOrg\OnlineChecker::getResults($file_contents, $settings, $fix_file, $php_version));
