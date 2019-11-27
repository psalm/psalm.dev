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

function jsonExceptionHandler($exception) {
    $message = str_replace(__DIR__, '', $exception->getFile() . ': ' . $exception->getMessage());
    echo json_encode([
        'error' => [
            'message' => $message,
            'line_from' => $exception->getLine(),
            'type' => 'psalm_error'
        ]
    ]);
    exit;
}

// Set user-defined error handler function
set_exception_handler("jsonExceptionHandler");

$file_contents = $_POST['code'];

$file_hash = md5($file_contents);

$cached_results = [
    '{"results":[{"severity":"error","line_from":24,"line_to":24,"type":"InvalidArgument","message":"Argument 1 of Airports::getname expects string(jfk)|string(lga)|string(ewr), string(sfo) provided","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"Airports::getName(\'sfo\'); \/\/ type error","selected_text":"\'sfo\'","from":518,"to":523,"snippet_from":500,"snippet_to":539,"column_from":19,"column_to":24}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"fe0e11b979dd9fddb95397c17beaf0a1"}',
    '{"results":[{"severity":"info","line_from":15,"line_to":15,"type":"PossiblyUndefinedVariable","message":"Possibly undefined variable $i, first seen on line 10","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"  echo $i; \/\/ Possibly undefined variable $i  ","selected_text":"$i","from":235,"to":237,"snippet_from":228,"snippet_to":274,"column_from":8,"column_to":10}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"77c5dcfb8fe17d2f50c39f18c449848d"}',
    '{"results":[],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"6e9a71a01ce329f8108fde84763daa2e"}',
    '{"results":[{"severity":"error","line_from":3,"line_to":3,"type":"ReferenceConstraintViolation","message":"Variable $s is limited to values of type string because it is passed by reference, stdClass type found. Use @param-out to specify a different output type","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"function changeToClass(string &$s) : void {  ","selected_text":"$s","from":38,"to":40,"snippet_from":7,"snippet_to":52,"column_from":32,"column_to":34}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"78ef288f9d0d99ef51a755e88f1f2247"}',
    '{"results":[{"severity":"error","line_from":12,"line_to":12,"type":"InvalidArgument","message":"Argument 1 of strlen expects string, stdClass provided","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"echo strlen($a); \/\/ typechecker error","selected_text":"$a","from":184,"to":186,"snippet_from":172,"snippet_to":209,"column_from":13,"column_to":15}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"9ae688063ac3f74ef31ad190a06eef5d"}',
    '{"results":[{"severity":"error","line_from":15,"line_to":15,"type":"UndefinedMethod","message":"Method A::bar does not exist","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"  $a->bar(); \/\/ error  ","selected_text":"bar","from":208,"to":211,"snippet_from":202,"snippet_to":225,"column_from":7,"column_to":10},{"severity":"error","line_from":21,"line_to":21,"type":"InvalidArgument","message":"Argument 1 of makeA expects class-string<A>, Exception::class provided","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"makeA(Exception::class); \/\/ error","selected_text":"Exception::class","from":289,"to":305,"snippet_from":283,"snippet_to":316,"column_from":7,"column_to":23}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"2339e6020cd5b330ba3bbd1e24a94aec"}',
    '{"results":[{"severity":"info","line_from":5,"line_to":5,"type":"PossiblyUnusedMethod","message":"Cannot find any calls to method Queue::clearLegacy","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"  public function clearLegacy() : void {}","selected_text":"clearLegacy","from":82,"to":93,"snippet_from":64,"snippet_to":105,"column_from":19,"column_to":30}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"8781c88551f4cac65ec79b7dee6246c4"}',
    '{"results":[{"severity":"info","line_from":5,"line_to":5,"type":"MixedArgument","message":"Argument 1 of echo cannot be mixed, expecting string","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"    echo $s;","selected_text":"$s","from":76,"to":78,"snippet_from":67,"snippet_to":79,"column_from":10,"column_to":12},{"severity":"info","line_from":4,"line_to":4,"type":"MissingParamType","message":"Parameter $s has no provided type","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"  public function takesString($s) : void {","selected_text":"$s","from":54,"to":56,"snippet_from":24,"snippet_to":66,"column_from":31,"column_to":33}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"6a18aec736a1f189f3305586a592a1b2"}',
    '{"results":[{"severity":"info","line_from":4,"line_to":4,"type":"UnusedVariable","message":"Variable $a is never referenced","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"    $a = substr(\"wonderful\", 2);","selected_text":"$a","from":42,"to":44,"snippet_from":38,"snippet_to":70,"column_from":5,"column_to":7}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"5a760ba4ed50f9eaf774252d8caf1816"}',
    '{"results":[{"severity":"error","line_from":22,"line_to":22,"type":"TaintedInput","message":"in path $_GET (\/src\/somefile.php:7) -> A::$userId (\/src\/somefile.php:7) out path A::$userId (\/src\/somefile.php:11) -> a::getappendeduserid (\/src\/somefile.php:17) -> b::deleteuser#2 (\/src\/somefile.php:21) -> pdo::exec#1 (\/src\/somefile.php:22)","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"    $pdo->exec(\"delete from users where user_id = \" . $userId);","selected_text":"\"delete from users where user_id = \" . $userId","from":443,"to":489,"snippet_from":428,"snippet_to":491,"column_from":16,"column_to":62},{"severity":"info","line_from":6,"line_to":6,"type":"PossiblyUnusedMethod","message":"Cannot find any calls to method A::__construct","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"  public function __construct() {","selected_text":"__construct","from":61,"to":72,"snippet_from":43,"snippet_to":76,"column_from":19,"column_to":30},{"severity":"info","line_from":15,"line_to":15,"type":"UnusedClass","message":"Class B is never used","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"class B {","selected_text":"B","from":226,"to":227,"snippet_from":220,"snippet_to":229,"column_from":7,"column_to":8}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"240cdd84b6c107c46639e173454a356d"}',
    '{"results":[{"severity":"error","line_from":4,"line_to":4,"type":"InvalidReturnStatement","message":"No return values are expected for foo","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/somefile.php","snippet":"    return \"bar\";","selected_text":"\"bar\"","from":53,"to":58,"snippet_from":42,"snippet_to":59,"column_from":12,"column_to":17},{"severity":"info","line_from":3,"line_to":3,"type":"UnusedParam","message":"Param $s is never referenced in this method","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/somefile.php","snippet":"function foo(string $s) : void {","selected_text":"$s","from":29,"to":31,"snippet_from":9,"snippet_to":41,"column_from":21,"column_to":23},{"severity":"error","line_from":3,"line_to":3,"type":"InvalidReturnType","message":"The declared return type \'void\' for foo is incorrect, got \'string\'","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/somefile.php","snippet":"function foo(string $s) : void {","selected_text":"void","from":35,"to":39,"snippet_from":9,"snippet_to":41,"column_from":27,"column_to":31},{"severity":"error","line_from":8,"line_to":8,"type":"InvalidScalarArgument","message":"Argument 1 of foo expects string, int(5) provided","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/somefile.php","snippet":"foo($a[1]);","selected_text":"$a[1]","from":86,"to":91,"snippet_from":82,"snippet_to":93,"column_from":5,"column_to":10},{"severity":"error","line_from":9,"line_to":9,"type":"TooFewArguments","message":"Too few arguments for method foo - expecting 1 but saw 0","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/somefile.php","snippet":"foo();","selected_text":"foo()","from":94,"to":99,"snippet_from":94,"snippet_to":100,"column_from":1,"column_to":6},{"severity":"info","line_from":12,"line_to":12,"type":"PossiblyUndefinedGlobalVariable","message":"Possibly undefined global variable $b, first seen on line 11","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/somefile.php","snippet":"echo $b;","selected_text":"$b","from":131,"to":133,"snippet_from":126,"snippet_to":134,"column_from":6,"column_to":8},{"severity":"error","line_from":15,"line_to":15,"type":"TypeDoesNotContainType","message":"Found a contradiction when evaluating $c and trying to reconcile type \'int(0)\' to !falsy","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/somefile.php","snippet":"if ($c) {} elseif ($c","selected_text":"$c","from":172,"to":174,"snippet_from":153,"snippet_to":174,"column_from":20,"column_to":22}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"fa860c80e3ad05c7c8e76960c35e9ab9"}',
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

if (strlen($file_contents) > 10000) {
    header(sprintf('HTTP/1.0 %s', 418));
    echo json_encode(['error' => 'Code too long']);
    exit;
}

$php_version = $_POST['php'] ?? '7.4';
		
if (!preg_match('/^[57]\.\d$/', $php_version)) {
    echo json_encode(['error' => 'PHP version ' . $php_version . ' not supported']);
    exit;
}

echo json_encode(PsalmDotOrg\OnlineChecker::getResults($file_contents, $settings, $fix_file, $php_version));
