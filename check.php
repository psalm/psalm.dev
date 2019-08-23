<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('vendor/autoload.php');
// not included in autoload
require_once('vendor/vimeo/psalm/tests/Internal/Provider/FakeFileProvider.php');
use PhpParser\ParserFactory;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Config;
use Psalm\IssueBuffer;

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
$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
$psalm_version = (string) \PackageVersions\Versions::getVersion('vimeo/psalm');

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

$config = Config::loadFromXML(
        (string)getcwd(),
        '<?xml version="1.0"?>
        <psalm cacheDirectory="cache">
            <projectFiles>
                <directory name="src" />
            </projectFiles>
        </psalm>'
    );
$config->collectPredefinedConstants();
$config->collectPredefinedFunctions();
$config->cache_directory = null;
$config->stop_on_first_error = false;
$config->allow_includes = false;
$config->totally_typed = true;
$config->check_for_throws_docblock = $settings['check_throws'] ?? true;
$config->remember_property_assignments_after_call = $settings['memoize_properties'] ?? true;;
$config->memoize_method_calls = $settings['memoize_method_calls'] ?? false;
$config->allow_phpstorm_generics = $settings['allow_phpstorm_generics'] ?? false;
$config->ignore_internal_nullable_issues = !($settings['strict_internal_functions'] ?? false);
$config->ignore_internal_falsable_issues = !($settings['strict_internal_functions'] ?? false);
$config->setCustomErrorLevel('MixedArrayAccess', Config::REPORT_INFO);
$config->setCustomErrorLevel('MixedArrayOffset', Config::REPORT_INFO);
$config->setCustomErrorLevel('MixedAssignment', Config::REPORT_INFO);
$config->setCustomErrorLevel('MixedArgument', Config::REPORT_INFO);
$config->setCustomErrorLevel('MixedMethodCall', Config::REPORT_INFO);
$config->setCustomErrorLevel('MixedOperand', Config::REPORT_INFO);
$config->setCustomErrorLevel('MissingParamType', Config::REPORT_INFO);
$config->setCustomErrorLevel('MissingClosureParamType', Config::REPORT_INFO);
$config->setCustomErrorLevel('MixedTypeCoercion', Config::REPORT_INFO);
$config->setCustomErrorLevel('MixedPropertyFetch', Config::REPORT_INFO);
$config->setCustomErrorLevel('MixedPropertyAssignment', Config::REPORT_INFO);
$config->setCustomErrorLevel('MixedInferredReturnType', Config::REPORT_INFO);
$config->setCustomErrorLevel('MixedReturnStatement', Config::REPORT_INFO);
$config->setCustomErrorLevel('MissingPropertyType', Config::REPORT_INFO);
$config->setCustomErrorLevel('MissingReturnType', Config::REPORT_INFO);
$config->setCustomErrorLevel('MissingClosureReturnType', Config::REPORT_INFO);
$config->setCustomErrorLevel('MissingThrowsDocblock', Config::REPORT_INFO);
$config->setCustomErrorLevel('DeprecatedMethod', Config::REPORT_INFO);
$config->setCustomErrorLevel('PossiblyUndefinedGlobalVariable', Config::REPORT_INFO);
$config->setCustomErrorLevel('PossiblyUndefinedVariable', Config::REPORT_INFO);
$config->setCustomErrorLevel('NonStaticSelfCall', Config::REPORT_INFO);

if (($settings['unused_variables'] ?? false) || $fix_file) {
    $config->setCustomErrorLevel('UnusedParam', Config::REPORT_INFO);
    $config->setCustomErrorLevel('PossiblyUnusedParam', Config::REPORT_INFO);
    $config->setCustomErrorLevel('UnusedVariable', Config::REPORT_INFO);
} else {
    $config->setCustomErrorLevel('UnusedParam', Config::REPORT_SUPPRESS);
    $config->setCustomErrorLevel('PossiblyUnusedParam', Config::REPORT_SUPPRESS);
    $config->setCustomErrorLevel('UnusedVariable', Config::REPORT_SUPPRESS);
}

if (($settings['unused_methods'] ?? false) || $fix_file) {
    $config->setCustomErrorLevel('UnusedClass', Config::REPORT_INFO);
    $config->setCustomErrorLevel('UnusedMethod', Config::REPORT_INFO);
    $config->setCustomErrorLevel('PossiblyUnusedMethod', Config::REPORT_INFO);
    $config->setCustomErrorLevel('PossiblyUnusedProperty', Config::REPORT_INFO);
    $config->setCustomErrorLevel('UnusedProperty', Config::REPORT_INFO);
} else {
    $config->setCustomErrorLevel('UnusedClass', Config::REPORT_SUPPRESS);
    $config->setCustomErrorLevel('UnusedMethod', Config::REPORT_SUPPRESS);
    $config->setCustomErrorLevel('PossiblyUnusedMethod', Config::REPORT_SUPPRESS);
    $config->setCustomErrorLevel('PossiblyUnusedProperty', Config::REPORT_SUPPRESS);
    $config->setCustomErrorLevel('UnusedProperty', Config::REPORT_SUPPRESS);
}


$config->setCustomErrorLevel('MoreSpecificReturnType', Config::REPORT_INFO);
$config->setCustomErrorLevel('LessSpecificReturnStatement', Config::REPORT_INFO);
$file_contents = $_POST['code'];

$file_hash = md5($file_contents);

$cached_results = [
    '{"results":[{"severity":"error","line_from":24,"line_to":24,"type":"InvalidArgument","message":"Argument 1 of Airports::getname expects string(jfk)|string(lga)|string(ewr), string(sfo) provided","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"Airports::getName(\'sfo\'); \/\/ type error","selected_text":"\'sfo\'","from":518,"to":523,"snippet_from":500,"snippet_to":539,"column_from":19,"column_to":24}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"fe0e11b979dd9fddb95397c17beaf0a1"}',
    '{"results":[{"severity":"info","line_from":15,"line_to":15,"type":"PossiblyUndefinedVariable","message":"Possibly undefined variable $i, first seen on line 10","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"  echo $i; \/\/ Possibly undefined variable $i  ","selected_text":"$i","from":235,"to":237,"snippet_from":228,"snippet_to":274,"column_from":8,"column_to":10}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"77c5dcfb8fe17d2f50c39f18c449848d"}',
    '{"results":[],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"6e9a71a01ce329f8108fde84763daa2e"}',
    '{"results":[{"severity":"error","line_from":3,"line_to":3,"type":"ReferenceConstraintViolation","message":"Variable $s is limited to values of type string because it is passed by reference, stdClass type found. Use @param-out to specify a different output type","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"function changeToClass(string &$s) : void {  ","selected_text":"$s","from":38,"to":40,"snippet_from":7,"snippet_to":52,"column_from":32,"column_to":34},{"severity":"error","line_from":8,"line_to":8,"type":"UndefinedFunction","message":"Function bar does not exist","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"bar($a);  ","selected_text":"bar($a)","from":119,"to":126,"snippet_from":119,"snippet_to":129,"column_from":1,"column_to":8}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"acd5e1437cba2feb73ec4201829552d3"}',
    '{"results":[{"severity":"error","line_from":11,"line_to":11,"type":"UndefinedFunction","message":"Function bar does not exist","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"bar($a);  ","selected_text":"bar($a)","from":151,"to":158,"snippet_from":151,"snippet_to":161,"column_from":1,"column_to":8}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"98793a86f801ede81277277b1f23a293"}',
    '{"results":[{"severity":"error","line_from":15,"line_to":15,"type":"UndefinedMethod","message":"Method A::bar does not exist","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"  $a->bar(); \/\/ error  ","selected_text":"bar","from":208,"to":211,"snippet_from":202,"snippet_to":225,"column_from":7,"column_to":10},{"severity":"error","line_from":21,"line_to":21,"type":"InvalidArgument","message":"Argument 1 of makeA expects class-string<A>, Exception::class provided","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"makeA(Exception::class); \/\/ error","selected_text":"Exception::class","from":289,"to":305,"snippet_from":283,"snippet_to":316,"column_from":7,"column_to":23}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"2339e6020cd5b330ba3bbd1e24a94aec"}',
    '{"results":[{"severity":"info","line_from":5,"line_to":5,"type":"PossiblyUnusedMethod","message":"Cannot find any calls to method Queue::clearLegacy","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"  public function clearLegacy() : void {}","selected_text":"clearLegacy","from":82,"to":93,"snippet_from":64,"snippet_to":105,"column_from":19,"column_to":30}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"8781c88551f4cac65ec79b7dee6246c4"}',
    '{"results":[{"severity":"info","line_from":5,"line_to":5,"type":"MixedArgument","message":"Argument 1 of echo cannot be mixed, expecting string","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"    echo $s;","selected_text":"$s","from":76,"to":78,"snippet_from":67,"snippet_to":79,"column_from":10,"column_to":12},{"severity":"info","line_from":4,"line_to":4,"type":"MissingParamType","message":"Parameter $s has no provided type","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"  public function takesString($s) : void {","selected_text":"$s","from":54,"to":56,"snippet_from":24,"snippet_to":66,"column_from":31,"column_to":33}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"6a18aec736a1f189f3305586a592a1b2"}',
    '{"results":[{"severity":"info","line_from":4,"line_to":4,"type":"UnusedVariable","message":"Variable $a is never referenced","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"    $a = substr(\"wonderful\", 2);","selected_text":"$a","from":42,"to":44,"snippet_from":38,"snippet_to":70,"column_from":5,"column_to":7}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"5a760ba4ed50f9eaf774252d8caf1816"}',
    '{"results":[{"severity":"error","line_from":22,"line_to":22,"type":"TaintedInput","message":"in path $_GET (\/src\/somefile.php:7) -> A::$userId (\/src\/somefile.php:7) out path A::$userId (\/src\/somefile.php:11) -> a::getappendeduserid (\/src\/somefile.php:17) -> b::deleteuser#2 (\/src\/somefile.php:21) -> pdo::exec#1 (\/src\/somefile.php:22)","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"    $pdo->exec(\"delete from users where user_id = \" . $userId);","selected_text":"\"delete from users where user_id = \" . $userId","from":443,"to":489,"snippet_from":428,"snippet_to":491,"column_from":16,"column_to":62},{"severity":"info","line_from":6,"line_to":6,"type":"PossiblyUnusedMethod","message":"Cannot find any calls to method A::__construct","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"  public function __construct() {","selected_text":"__construct","from":61,"to":72,"snippet_from":43,"snippet_to":76,"column_from":19,"column_to":30},{"severity":"info","line_from":15,"line_to":15,"type":"UnusedClass","message":"Class B is never used","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/html\/src\/somefile.php","snippet":"class B {","selected_text":"B","from":226,"to":227,"snippet_from":220,"snippet_to":229,"column_from":7,"column_to":8}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"240cdd84b6c107c46639e173454a356d"}',
    '{"results":[{"severity":"error","line_from":4,"line_to":4,"type":"InvalidReturnStatement","message":"No return values are expected for foo","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/somefile.php","snippet":"    return \"bar\";","selected_text":"\"bar\"","from":53,"to":58,"snippet_from":42,"snippet_to":59,"column_from":12,"column_to":17},{"severity":"info","line_from":3,"line_to":3,"type":"UnusedParam","message":"Param $s is never referenced in this method","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/somefile.php","snippet":"function foo(string $s) : void {","selected_text":"$s","from":29,"to":31,"snippet_from":9,"snippet_to":41,"column_from":21,"column_to":23},{"severity":"error","line_from":3,"line_to":3,"type":"InvalidReturnType","message":"The declared return type \'void\' for foo is incorrect, got \'string\'","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/somefile.php","snippet":"function foo(string $s) : void {","selected_text":"void","from":35,"to":39,"snippet_from":9,"snippet_to":41,"column_from":27,"column_to":31},{"severity":"error","line_from":8,"line_to":8,"type":"InvalidScalarArgument","message":"Argument 1 of foo expects string, int(5) provided","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/somefile.php","snippet":"foo($a[1]);","selected_text":"$a[1]","from":86,"to":91,"snippet_from":82,"snippet_to":93,"column_from":5,"column_to":10},{"severity":"error","line_from":9,"line_to":9,"type":"TooFewArguments","message":"Too few arguments for method foo - expecting 1 but saw 0","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/somefile.php","snippet":"foo();","selected_text":"foo()","from":94,"to":99,"snippet_from":94,"snippet_to":100,"column_from":1,"column_to":6},{"severity":"info","line_from":12,"line_to":12,"type":"PossiblyUndefinedGlobalVariable","message":"Possibly undefined global variable $b, first seen on line 11","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/somefile.php","snippet":"echo $b;","selected_text":"$b","from":131,"to":133,"snippet_from":126,"snippet_to":134,"column_from":6,"column_to":8},{"severity":"error","line_from":15,"line_to":15,"type":"TypeDoesNotContainType","message":"Found a contradiction when evaluating $c and trying to reconcile type \'int(0)\' to !falsy","file_name":"\/src\/somefile.php","file_path":"\/var\/www\/vhosts\/psalm.dev\/httpdocs\/src\/somefile.php","snippet":"if ($c) {} elseif ($c","selected_text":"$c","from":172,"to":174,"snippet_from":153,"snippet_to":174,"column_from":20,"column_to":22}],"version":"dev-master@738ba81185abbdd25d5e99940080c2f713ec1da1","fixed_contents":null,"hash":"fa860c80e3ad05c7c8e76960c35e9ab9"}',
];

foreach ($cached_results as $cached_result) {
    $decoded = json_decode($cached_result, true);

    if ($decoded['hash'] === $file_hash) {
        echo $cached_result;
        exit;
    }
}

if (strlen($file_contents) > 10000) {
    header(sprintf('HTTP/1.0 %s', 418));
    echo json_encode(['error' => 'Code too long']);
    exit;
}

$file_provider = new Psalm\Tests\Internal\Provider\FakeFileProvider();
$output_options = new \Psalm\Report\ReportOptions();
$output_options->format = \Psalm\Report::TYPE_JSON;

$project_checker = new ProjectAnalyzer(
    $config,
    new Psalm\Internal\Provider\Providers(
        $file_provider
    ),
    $output_options
);
$codebase = $project_checker->getCodebase();
$codebase->collect_references = true;

if ($fix_file) {
    $project_checker->alterCodeAfterCompletion(
        false,
        false
    );
    $project_checker->setAllIssuesToFix();
}

$infer_types_from_usage = true;
$project_checker->checkClassReferences();
$file_path = __DIR__ . '/src/somefile.php';
$file_provider->registerFile(
    $file_path,
    $file_contents
);

$codebase->scanner->addFileToDeepScan(__DIR__ . '/src/somefile.php');
if (($settings['unused_variables'] ?? false) || ($settings['unused_methods'] ?? false) || $fix_file) {
    $codebase->reportUnusedCode();
}
$codebase->addFilesToAnalyze([$file_path => $file_path]);
try {
    $codebase->scanFiles();
} catch (PhpParser\Error $e) {
    $attributes = $e->getAttributes();
    echo json_encode([
        'error' => [
            'message' => $e->getRawMessage(),
            'line_from' => $e->getStartLine(),
            'from' => $attributes['startFilePos'],
            'to' => $attributes['endFilePos'] + 1,
            'type' => 'parser_error'
        ]
    ]);
    exit();
}

$config->visitStubFiles($codebase, null);

try {
    $file_checker = new FileAnalyzer(
        $project_checker,
        $file_path,
        $config->shortenFileName($file_path)
    );
    $context = new \Psalm\Context();
    $context->collect_references = true;
    $class_aliases = $codebase->file_storage_provider->get($file_path)->classlike_aliases;
    foreach ($class_aliases as $aliased_class => $new_class) {
        $codebase->classlikes->addClassAlias($new_class, $aliased_class);
    }
    
    $codebase->taint = new \Psalm\Internal\Codebase\Taint();
    
    $file_checker->analyze($context);

    if ($codebase->taint) {
        $i = 0;
        while ($codebase->taint->hasNewSinksAndSources() && ++$i < 4) {
            $codebase->taint->clearNewSinksAndSources();
            $file_checker->analyze($context);
        }
    }

    if (($settings['unused_methods'] ?? false) || $fix_file) {
        $project_checker->checkClassReferences();
    }
    $issue_data = IssueBuffer::getIssuesData();

    $fixed_file_contents = null;

    if ($fix_file) {
        $codebase->analyzer->updateFile($file_path, false);
        $fixed_file_contents = $codebase->getFileContents($file_path);
    }

    echo json_encode(['results' => $issue_data, 'version' => $psalm_version, 'fixed_contents' => $fixed_file_contents, 'hash' => $file_hash]);
} catch (PhpParser\Error $e) {
    $attributes = $e->getAttributes();
    echo json_encode([
        'error' => [
            'message' => $e->getRawMessage(),
            'line_from' => $e->getStartLine(),
            'from' => $attributes['startFilePos'],
            'to' => $attributes['endFilePos'] + 1,
            'type' => 'parser_error'
        ]
    ]);
}
