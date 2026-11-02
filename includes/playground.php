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
        <label class="pg_field" for="php_version">
            <span class="pg_field_label">Analyse as PHP</span>
            <select id="php_version" onchange="setPhpVersion(this.value)"></select>
        </label>
        <span class="pg_spacer"></span>
        <button type="button" id="fixer" hidden>Fix code</button>
        <button type="button" id="settings" onclick="toggleSettings();" aria-expanded="false" aria-controls="settings_panel">Settings</button>
        <button type="button" id="getlink" onclick="getLink();" title="Save the snippet and copy its link (Cmd/Ctrl+S)">Share</button>
    </div>
    <div class="pg_panes" id="pg_panes">
        <div class="pg_pane pg_editor">
            <textarea name="code" id="code" rows="20" style="visibility: hidden;"><?= $playground_code ?></textarea>
        </div>
        <div class="pg_splitter" id="pg_splitter" role="separator" aria-orientation="vertical" aria-label="Resize editor and output" title="Drag to resize, double-click to reset" tabindex="0"></div>
        <div class="pg_pane pg_output">
            <div class="pg_bar pg_tabs" role="tablist" id="pg_tabs">
                <button type="button" role="tab" id="pg_tab_issues" class="active" aria-selected="true" aria-controls="psalm_output">Issues <span class="pg_count" id="pg_issue_count"></span></button>
            </div>
            <div id="psalm_output" role="tabpanel" aria-labelledby="pg_tab_issues" aria-live="polite"></div>
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
