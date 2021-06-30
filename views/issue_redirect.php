<?php

http_response_code(500);

require_once('../vendor/autoload.php');

$path = (int) substr($_SERVER['REQUEST_URI'], 1);

if ($path) {
    $issue_types = \Psalm\Config\IssueHandler::getAllIssueTypes();

    $map = [];

    foreach ($issue_types as $issue_type) {
        $issue_class = 'Psalm\\Issue\\' . $issue_type;

        if (!class_exists($issue_class) || !is_a($issue_class, \Psalm\Issue\CodeIssue::class, true)) {
            throw new Exception($issue_class . ' is not a Codeissue');
        }

        /** @var int */
        $code = $issue_class::SHORTCODE;

        $map[$code] = $issue_type;
    }

    if (isset($map[$path])) {
        http_response_code(301);
        header("Location: /docs/running_psalm/issues/" . $map[$path]);
        exit();
    }
}

http_response_code(301);
header("Location: /docs/running_psalm/issues/");
exit();