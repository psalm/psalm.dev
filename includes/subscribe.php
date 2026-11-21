<?php
/**
 * The newsletter subscription form. It posts to newsletter.daniil.it/subscribe (the "newsletter" service
 * next to listmonk, in Daniil Gentili's home.daniil.it repository), which checks Cloudflare Turnstile
 * and subscribes the address to the list "Psalm"; errors come back to /newsletter as ?error=<code>.
 * /assets/js/newsletter.js loads Turnstile once someone starts filling in a form.
 *
 * Pages may set before including: $subscribe_variant, 'page' (the form of /newsletter, with the
 * error messages), 'box' (a call to subscribe, e.g. under articles) or 'compact' (the footer).
 */
require_once __DIR__ . '/asset.php';

$subscribe_variant ??= 'box';
?>
<form class="newsletter newsletter--<?= $subscribe_variant ?>"<?= $subscribe_variant === 'page' ? ' id="subscribe"' : '' ?> method="post" action="https://newsletter.daniil.it/subscribe">
<?php if ($subscribe_variant === 'box'): ?>
    <p class="newsletter__title">Get Psalm news by email</p>
    <p class="newsletter__text">New articles from the Psalm blog, a few emails a year. You can unsubscribe at any time.</p>
<?php endif ?>
    <input type="hidden" name="site" value="psalm">
    <?php /* Honeypot: bots fill it, people don't see it */ ?>
    <input class="newsletter__hp" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true">
    <div class="newsletter__fields">
        <label><span>Email</span><input type="email" name="email" required maxlength="254" autocomplete="email" placeholder="you@example.com"></label>
<?php if ($subscribe_variant === 'page'): ?>
        <label><span>Name <small>(optional)</small></span><input type="text" name="name" maxlength="200" autocomplete="name" placeholder="Your name"></label>
<?php endif ?>
        <button type="submit">Subscribe</button>
    </div>
    <label class="newsletter__consent"><input type="checkbox" name="consent" value="1" required> I accept the <a href="https://blog.daniil.it/privacy-policy/" target="_blank">privacy policy</a></label>
    <div class="newsletter__turnstile" data-sitekey="0x4AAAAAAF79Ff0lSBvHmOcq"></div>
</form>
<?php if (empty($subscribe_script_included)): $subscribe_script_included = true; ?>
<script src="<?= asset_url('/assets/js/newsletter.js') ?>" defer></script>
<?php endif ?>
<?php unset($subscribe_variant); ?>
