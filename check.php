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
const PHP_PARSER_VERSION = '4.0.0';
$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
$psalm_version = (string) \Muglug\PackageVersions\Versions::getVersion('vimeo/psalm');

function jsonExceptionHandler($exception) {
    echo json_encode([
        'error' => [
            'message' => $exception->getMessage(),
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
$config->use_property_default_for_type = false;
$config->check_for_throws_docblock = true;
$config->remember_property_assignments_after_call = true;
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
$config->setCustomErrorLevel('MissingClosureReturnType', Config::REPORT_SUPPRESS);
$config->setCustomErrorLevel('MissingThrowsDocblock', Config::REPORT_INFO);
$config->setCustomErrorLevel('DeprecatedMethod', Config::REPORT_INFO);
$config->setCustomErrorLevel('PossiblyUndefinedGlobalVariable', Config::REPORT_INFO);
$config->setCustomErrorLevel('PossiblyUndefinedVariable', Config::REPORT_INFO);
$config->setCustomErrorLevel('NonStaticSelfCall', Config::REPORT_INFO);
$config->setCustomErrorLevel('UnusedParam', Config::REPORT_INFO);
$config->setCustomErrorLevel('PossiblyUnusedParam', Config::REPORT_INFO);
$config->setCustomErrorLevel('UnusedVariable', Config::REPORT_INFO);
$config->setCustomErrorLevel('UnusedClass', Config::REPORT_SUPPRESS);
$config->setCustomErrorLevel('UnusedMethod', Config::REPORT_INFO);
$config->setCustomErrorLevel('PossiblyUnusedMethod', Config::REPORT_INFO);
$config->setCustomErrorLevel('PossiblyUnusedProperty', Config::REPORT_INFO);
$config->setCustomErrorLevel('UnusedProperty', Config::REPORT_INFO);
$config->setCustomErrorLevel('MoreSpecificReturnType', Config::REPORT_INFO);
$config->setCustomErrorLevel('LessSpecificReturnStatement', Config::REPORT_INFO);
$file_contents = $_POST['code'];

$file_hash = md5($file_contents);
if ($file_hash === '0e0eee096e2def5c7ec41e1c4eb0c56d') {
    echo '{"results":[{"severity":"info","line_number":15,"message":"Possibly undefined variable $a, first seen on line 10","file_name":"somefile.php","file_path":"somefile.php","snippet":"echo $a; \/\/ uncomment the line above to fix!","from":229,"to":231},{"severity":"error","line_number":3,"message":"Cannot find referenced variable $on_your","file_name":"somefile.php","file_path":"somefile.php","snippet":"  return $on_your . \"behalf\";","from":66,"to":74},{"severity":"info","line_number":2,"message":"Could not verify return type \'string|null\' for psalmCanCheck","file_name":"somefile.php","file_path":"somefile.php","snippet":"function psalmCanCheck(int $your_code) : ?string {\n  return $on_your . \"behalf\";\n}","from":47,"to":54}]}';
    exit;
}

if (strlen($file_contents) > 6000) {
    header(sprintf('HTTP/1.0 %s', 418));
    echo json_encode(['error' => 'Code too long']);
    exit;
}

$file_provider = new Psalm\Tests\Internal\Provider\FakeFileProvider();
$project_checker = new ProjectAnalyzer(
    $config,
    new Psalm\Internal\Provider\Providers(
        $file_provider
    ),
    false,
    true,
    ProjectAnalyzer::TYPE_JSON
);
$codebase = $project_checker->getCodebase();
$codebase->collect_references = true;
$infer_types_from_usage = true;
$project_checker->checkClassReferences();
$file_path = __DIR__ . '/src/somefile.php';
$file_provider->registerFile(
    $file_path,
    $file_contents
);
$config->visitStubFiles($codebase, false);
$codebase->scanner->addFileToDeepScan(__DIR__ . '/src/somefile.php');
$codebase->reportUnusedCode();
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

try {
    $file_checker = new FileAnalyzer(
        $project_checker,
        $file_path,
        $config->shortenFileName($file_path)
    );
    $context = new \Psalm\Context();
    $context->collect_references = true;
    $file_checker->analyze($context);
    $issue_data = IssueBuffer::getIssuesData();

    echo json_encode(['results' => $issue_data, 'version' => $psalm_version]);
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
