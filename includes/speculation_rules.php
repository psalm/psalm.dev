<?php /* Prerender same-origin pages on hover or touch start, so they open instantly. Endpoints and downloads are excluded. */ ?>
<script type="speculationrules">
{
    "prerender": [{
        "where": {
            "and": [
                {"href_matches": "/*"},
                {"not": {"href_matches": ["/add_code", "/check", "/articles/auth/*", "/schema/*", "/v2/*", "/assets/*", "/vendor/*", "/r/*/*", "/r/*.json"]}},
                {"not": {"selector_matches": "[target=_blank], [download]"}}
            ]
        },
        "eagerness": "moderate"
    }]
}
</script>
