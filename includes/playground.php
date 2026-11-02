<?php
/**
 * Playground markup shared by the homepage and snippet pages.
 *
 * @var string $playground_code already HTML-escaped textarea contents
 * @var string|null $playground_saved_on optional "saved on" label for snippet pages
 */
?>
<section class="playground" id="playground" aria-label="Psalm playground">
    <div class="pg_bar pg_topbar">
        <div class="pg_toolgroup pg_toolgroup_config">
            <label class="pg_field" for="php_version">
                <span class="pg_field_value" id="php_version_label" aria-hidden="true">PHP</span>
                <select id="php_version" onchange="setPhpVersion(this.value)" aria-label="Analyse code as PHP version"></select>
            </label>
            <button type="button" id="settings" onclick="toggleSettings();" aria-expanded="false" aria-controls="settings_panel">Settings</button>
        </div>
        <span class="pg_spacer"></span>
        <div class="pg_toolgroup pg_toolgroup_actions">
            <button type="button" id="fixer" class="pg_ghost" title="Let Psalm rewrite the code to fix the fixable issues" hidden>Fix code</button>
            <button type="button" id="getlink" class="pg_primary" onclick="getLink();" title="Save the snippet and copy its link"><svg width="22" height="12" viewBox="0 0 28 15" fill="currentColor" aria-hidden="true" focusable="false"><g fill-rule="evenodd"><path d="M17.3 2.5A5 5 0 0 0 13 0H5a5 5 0 0 0-5 5v1a5 5 0 0 0 5 5h8a5 5 0 0 0 4.8-3.5H16a4 4 0 0 1-3.5 2h-7a4 4 0 1 1 0-8h7c1 0 2 .4 2.6 1h2.2z"/><path d="M10.4 12.5a5 5 0 0 0 4.4 2.5h8a5 5 0 0 0 5-5V9a5 5 0 0 0-5-5h-8A5 5 0 0 0 10 7.5h1.8a4 4 0 0 1 3.5-2h7a4 4 0 1 1 0 8h-7c-1 0-2-.4-2.7-1h-2.2z"/></g></svg><span class="pg_share_label">Share</span></button>
        </div>
    </div>
    <div class="pg_panes" id="pg_panes">
        <div class="pg_pane pg_editor">
            <textarea name="code" id="code" rows="20" style="visibility: hidden;"><?= $playground_code ?></textarea>
        </div>
        <div class="pg_splitter" id="pg_splitter" role="separator" aria-orientation="vertical" aria-label="Resize editor and output" title="Drag to resize, double-click to reset" tabindex="0"></div>
        <div class="pg_pane pg_output">
            <div class="pg_bar pg_tabs">
                <div class="pg_tablist" role="tablist" id="pg_tabs">
                    <button type="button" role="tab" id="pg_tab_issues" data-tab="issues" class="active" aria-selected="true" aria-controls="psalm_output">Issues <span class="pg_count" id="pg_issue_count"></span></button>
                    <button type="button" role="tab" id="pg_tab_types" data-tab="types" aria-selected="false" aria-controls="pg_types" tabindex="-1">Types <span class="pg_count" id="pg_type_count"></span></button>
                </div>
                <span class="pg_running" id="pg_running" aria-hidden="true" hidden>running&hellip;</span>
            </div>
            <div id="psalm_output" role="tabpanel" aria-labelledby="pg_tab_issues" aria-live="polite"></div>
            <div id="pg_types" role="tabpanel" aria-labelledby="pg_tab_types" hidden></div>
        </div>
        <aside id="settings_panel" class="pg_drawer" aria-label="Settings" hidden></aside>
    </div>
    <div class="pg_bar pg_statusbar" id="pg_statusbar">
        <span class="pg_status_item pg_run_state" id="pg_run_state" data-state="idle">idle</span>
        <span class="pg_status_item" id="pg_psalm_version"></span>
<?php if (!empty($playground_saved_on)) : ?>
        <span class="pg_status_item pg_saved_on"><?= htmlspecialchars($playground_saved_on) ?></span>
<?php endif ?>
        <span class="pg_status_item pg_message" id="pg_message" role="status" aria-live="polite"></span>
        <span class="pg_spacer"></span>
        <span class="pg_status_item pg_keys"><span><kbd>⌘/Ctrl+Enter</kbd> run</span><span><kbd>⌘/Ctrl+S</kbd> share</span><span><kbd>F8</kbd>/<kbd>⇧F8</kbd> next/prev issue</span></span>
    </div>
</section>
