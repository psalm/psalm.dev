<?php

namespace PsalmDotOrg;

require_once(__DIR__ . '/../vendor/vimeo/psalm/tests/Internal/Provider/FakeFileProvider.php');

use PhpParser\ParserFactory;
use Psalm\Config;
use Psalm\IssueBuffer;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;

class OnlineChecker
{
	public static function getResults(
		string $file_contents,
		array $settings,
		bool $fix_file
	) : array {
		$config = self::getPsalmConfig($settings, $fix_file);

		$psalm_version = (string) \PackageVersions\Versions::getVersion('vimeo/psalm');
		$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
		$file_provider = new \Psalm\Tests\Internal\Provider\FakeFileProvider();
		$output_options = new \Psalm\Report\ReportOptions();
		$output_options->format = \Psalm\Report::TYPE_JSON;

		$project_checker = new ProjectAnalyzer(
		    $config,
		    new \Psalm\Internal\Provider\Providers(
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
		$file_path = __DIR__ . '/../src/somefile.php';
		$file_provider->registerFile(
		    $file_path,
		    $file_contents
		);

		$codebase->scanner->addFileToDeepScan(__DIR__ . '/../src/somefile.php');
		
		if (($settings['unused_variables'] ?? false) || ($settings['unused_methods'] ?? false) || $fix_file) {
		    $codebase->reportUnusedCode();
		}
		
		$codebase->addFilesToAnalyze([$file_path => $file_path]);
		
		try {
		    $codebase->scanFiles();
		} catch (\PhpParser\Error $e) {
		    $attributes = $e->getAttributes();
		    
		    return [
		        'error' => [
		            'message' => $e->getRawMessage(),
		            'line_from' => $e->getStartLine(),
		            'from' => $attributes['startFilePos'],
		            'to' => $attributes['endFilePos'] + 1,
		            'type' => 'parser_error'
		        ]
		    ];
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

		    $i = 0;
	        while ($codebase->taint->hasNewSinksAndSources() && ++$i < 4) {
	            $codebase->taint->clearNewSinksAndSources();
	            $file_checker->analyze($context);
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

		    return [
		    	'results' => $issue_data,
		    	'version' => $psalm_version,
		    	'fixed_contents' => $fixed_file_contents, 
		    	'hash' => md5($file_contents),
		    ];
		} catch (\PhpParser\Error $e) {
		    $attributes = $e->getAttributes();
		    
		    return [
		        'error' => [
		            'message' => $e->getRawMessage(),
		            'line_from' => $e->getStartLine(),
		            'from' => $attributes['startFilePos'],
		            'to' => $attributes['endFilePos'] + 1,
		            'type' => 'parser_error'
		        ]
		    ];
		}
	}

	private static function getPsalmConfig(array $settings, bool $fix_file) : \Psalm\Config
	{
		$config = Config::loadFromXML(
	        (string)getcwd(),
	        '<?xml version="1.0"?>
	        <psalm cacheDirectory="cache">
	            <projectFiles>
	                <directory name="../src" />
	            </projectFiles>
	        </psalm>'
		);
		$config->collectPredefinedConstants();
		$config->collectPredefinedFunctions();
		$config->cache_directory = '';
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

		return $config;
	}
}