<dialog id="help_dialog" style="max-width:560px;border:1px solid #aaa;border-radius:6px;padding:20px 24px;font-size:14px;line-height:1.6;">
    <form method="dialog" style="text-align:right;margin:-10px -14px 8px 0;">
        <button style="background:none;border:none;font-size:20px;cursor:pointer;padding:0 4px;">&times;</button>
    </form>

    <strong style="font-size:15px;">Magic comments</strong>
    <p style="margin:6px 0 10px;">Add as the first line of your code to enable extra analysis modes:</p>
    <table style="border-collapse:collapse;width:100%;font-size:13px;">
        <tr style="border-bottom:1px solid #eee;">
            <td style="padding:6px 8px 6px 0;vertical-align:top;"><code style="background:#eee;padding:2px 5px;border-radius:3px;white-space:nowrap;">&lt;?php // --taint-analysis</code></td>
            <td style="padding:6px 0;">Enable <a href="https://psalm.dev/docs/security_analysis/" target="_blank">taint analysis</a> (security analysis for tracking user input through sinks)</td>
        </tr>
        <tr style="border-bottom:1px solid #eee;">
            <td style="padding:6px 8px 6px 0;vertical-align:top;"><code style="background:#eee;padding:2px 5px;border-radius:3px;white-space:nowrap;">&lt;?php // checkTaintedInput</code></td>
            <td style="padding:6px 0;">Alias for <code>--taint-analysis</code></td>
        </tr>
        <tr style="border-bottom:1px solid #eee;">
            <td style="padding:6px 8px 6px 0;vertical-align:top;"><code style="background:#eee;padding:2px 5px;border-radius:3px;white-space:nowrap;">&lt;?php // trackTaints</code></td>
            <td style="padding:6px 0;">Alias for <code>--taint-analysis</code></td>
        </tr>
        <tr>
            <td style="padding:6px 8px 6px 0;vertical-align:top;"><code style="background:#eee;padding:2px 5px;border-radius:3px;white-space:nowrap;">&lt;?php // findUnusedCode</code></td>
            <td style="padding:6px 0;">Detect unused classes, methods, properties, and variables (same as enabling both &ldquo;Detect unused&rdquo; checkboxes)</td>
        </tr>
    </table>

    <strong style="font-size:15px;display:block;margin-top:16px;">URL parameters</strong>
    <table style="border-collapse:collapse;width:100%;font-size:13px;margin-top:8px;">
        <tr>
            <td style="padding:6px 8px 6px 0;vertical-align:top;"><code style="background:#eee;padding:2px 5px;border-radius:3px;white-space:nowrap;">?php=8.1</code></td>
            <td style="padding:6px 0;">Set PHP target version (supported: <code>5.6</code>, <code>7.0</code>&ndash;<code>7.4</code>, <code>8.0</code>&ndash;<code>8.4</code>)</td>
        </tr>
    </table>
</dialog>
