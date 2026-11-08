<?php
/**
 * Playground markup shared by the homepage and snippet pages.
 *
 * data-pg-full marks what the shrunk homepage widget hides (body.pg_compact): it shows only the editor,
 * Expand, the bottom Share button and status messages.
 *
 * @param string $escaped_code already HTML-escaped textarea contents
 * @param string|null $saved_on "saved on" label for snippet pages
 * @param bool $expandable homepage only: offer Expand/Shrink and the bottom Share button
 */
function render_playground(string $escaped_code, ?string $saved_on = null, bool $expandable = false): void
{
    // the link icon of both Share buttons
    $share_icon = '<svg width="22" height="12" viewBox="0 0 28 15" fill="currentColor" aria-hidden="true" focusable="false"><g fill-rule="evenodd"><path d="M17.3 2.5A5 5 0 0 0 13 0H5a5 5 0 0 0-5 5v1a5 5 0 0 0 5 5h8a5 5 0 0 0 4.8-3.5H16a4 4 0 0 1-3.5 2h-7a4 4 0 1 1 0-8h7c1 0 2 .4 2.6 1h2.2z"/><path d="M10.4 12.5a5 5 0 0 0 4.4 2.5h8a5 5 0 0 0 5-5V9a5 5 0 0 0-5-5h-8A5 5 0 0 0 10 7.5h1.8a4 4 0 0 1 3.5-2h7a4 4 0 1 1 0 8h-7c-1 0-2-.4-2.7-1h-2.2z"/></g></svg>';
    ?>
<section class="playground" id="playground" aria-label="Psalm playground">
    <div class="pg_bar pg_topbar" data-pg-full>
        <div class="pg_toolgroup pg_toolgroup_actions">
            <button type="button" id="getlink" class="pg_primary" data-share onclick="getLink();"><?= $share_icon ?><span class="pg_share_label">Share</span></button>
            <button type="button" id="fixer" title="Let Psalm rewrite the code" hidden>Fix issues</button>
            <button type="button" id="fix_compare" title="Switch between your code and Psalm's fix to compare them" hidden>Show original</button>
        </div>
        <span class="pg_spacer"></span>
        <div class="pg_toolgroup pg_toolgroup_config">
            <label class="pg_field" for="php_version">
                <span class="pg_field_value" id="php_version_label" aria-hidden="true">PHP</span>
                <select id="php_version" onchange="setPhpVersion(this.value)" aria-label="Analyze code as PHP version"></select>
            </label>
            <button type="button" id="settings" onclick="toggleSettings();" aria-expanded="false" aria-controls="settings_panel">Settings</button>
        </div>
    </div>
    <div class="pg_panes" id="pg_panes">
        <div class="pg_pane pg_editor">
            <textarea name="code" id="code" rows="20" style="visibility: hidden;"><?= $escaped_code ?></textarea>
        </div>
        <div class="pg_splitter" id="pg_splitter" data-pg-full role="separator" aria-orientation="vertical" aria-label="Resize editor and output" title="Drag to resize, double-click to reset" tabindex="0"></div>
        <div class="pg_pane pg_output" data-pg-full>
            <div class="pg_bar pg_tabs">
                <div class="pg_tablist" role="tablist" id="pg_tabs">
                    <button type="button" role="tab" id="pg_tab_issues" data-tab="issues" class="active" aria-selected="true" aria-controls="psalm_output">Issues <span class="pg_count" id="pg_issue_count"></span></button>
                    <button type="button" role="tab" id="pg_tab_types" data-tab="types" aria-selected="false" aria-controls="pg_types" tabindex="-1">Types <span class="pg_count" id="pg_type_count"></span></button>
                </div>
            </div>
            <div id="psalm_output" role="tabpanel" aria-labelledby="pg_tab_issues" aria-live="polite"></div>
            <div id="pg_types" role="tabpanel" aria-labelledby="pg_tab_types" hidden></div>
        </div>
        <aside id="settings_panel" class="pg_drawer" aria-label="Settings" hidden></aside>
    </div>
    <dialog id="pg_fix_preview" class="pg_fix_preview" aria-labelledby="pg_fix_preview_title"></dialog>
    <div class="pg_bar pg_statusbar" id="pg_statusbar">
<?php if ($expandable) : ?>
        <button type="button" id="expander" class="pg_size_toggle" onclick="return expandCode();" title="Give the playground the full page width"><svg width="15" height="14" viewBox="0 0 15 14" fill="currentColor" aria-hidden="true" focusable="false"><path d="M0 5h1.5v6.4L12.9 0l1.4 1.2L2.8 12.5H9V14H0z" fill-rule="evenodd"/></svg>Expand</button>
        <button type="button" id="shrinker" class="pg_size_toggle" onclick="return shrinkCode();" title="Put the playground back next to the introduction"><svg width="15" height="14" viewBox="0 0 15 14" fill="currentColor" aria-hidden="true" focusable="false"><path d="M15 9h-1.5V2.6L2.1 14 .8 12.8 12.2 1.5H6V0h9z" fill-rule="evenodd"/></svg>Shrink</button>
<?php endif ?>
        <span class="pg_status_item" id="pg_psalm_version" data-pg-full></span>
        <span class="pg_status_item pg_run_state" id="pg_run_state" data-state="idle" data-pg-full>idle</span>
<?php if ($saved_on !== null && $saved_on !== '') : ?>
        <span class="pg_status_item pg_saved_on" data-pg-full><?= htmlspecialchars($saved_on) ?></span>
<?php endif ?>
        <span class="pg_status_item pg_message" id="pg_message" role="status" aria-live="polite"></span>
        <span class="pg_spacer"></span>
        <span class="pg_status_item pg_keys" data-pg-full><span><kbd>⌘/Ctrl+Enter</kbd> run</span><span><kbd>⌘/Ctrl+S</kbd> share</span><span><kbd>F8</kbd>/<kbd>⇧F8</kbd> next/prev issue</span></span>
<?php if ($expandable) : ?>
        <button type="button" id="getlink_bottom" class="pg_bottom_share" data-share onclick="getLink();" title="Save the snippet and copy its link"><?= $share_icon ?><span class="pg_share_label">Share</span></button>
<?php endif ?>
    </div>
</section>
    <?php
}
