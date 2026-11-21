<?php require_once __DIR__ . '/subscribe.php'; ?>
<footer>
<?php if (!NewsletterForm::rendered()): ?>
    <div class="footer_newsletter">
        <p>Get new Psalm articles by email:</p>
<?= NewsletterForm::render('compact') ?>
    </div>
<?php endif ?>
    <p>Follow Psalm news: <a href="/newsletter">Newsletter</a> — <a target="_blank" href="https://t.me/psalmphp">Telegram</a> — <a target="_blank" href="https://discord.gg/RwH8rEpxt9">Discord</a> — <a target="_blank" rel="me" href="https://phpc.social/@psalm">Mastodon</a> — <a target="_blank" href="https://x.com/psalmphp">X</a></p>
    <p>Official community: <a target="_blank" href="https://psalmphp.t.me">Telegram</a> — <a target="_blank" href="https://discord.gg/RwH8rEpxt9">Discord</a></p>
</footer>
