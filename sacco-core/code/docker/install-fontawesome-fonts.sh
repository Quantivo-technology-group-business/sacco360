#!/bin/bash
set -e

FONT_DIR="public/fonts/vendor/@fortawesome/fontawesome-free"

if [ -f "${FONT_DIR}/webfa-solid-900.woff2" ] && [ -f "${FONT_DIR}/webfa-regular-400.woff2" ]; then
    exit 0
fi

echo "Installing Font Awesome webfonts into ${FONT_DIR}..."

mkdir -p "${FONT_DIR}"

php <<'PHP'
$base = 'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/webfonts';
$dir = 'public/fonts/vendor/@fortawesome/fontawesome-free';
$fonts = [
    ['fa-solid-900', 'webfa-solid-900'],
    ['fa-regular-400', 'webfa-regular-400'],
    ['fa-brands-400', 'webfa-brands-400'],
];

if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$context = stream_context_create([
    'http' => ['timeout' => 30],
    'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
]);

foreach ($fonts as [$src, $dst]) {
    foreach (['woff2', 'woff', 'ttf'] as $ext) {
        $target = "{$dir}/{$dst}.{$ext}";
        if (is_file($target) && filesize($target) > 0) {
            continue;
        }

        $url = "{$base}/{$src}.{$ext}";
        $data = @file_get_contents($url, false, $context);
        if ($data === false) {
            fwrite(STDERR, "Warning: could not download {$url}\n");
            continue;
        }

        file_put_contents($target, $data);
        echo "Installed {$dst}.{$ext}\n";
    }
}
PHP

chown -R www-data:www-data "${FONT_DIR}" 2>/dev/null || true
