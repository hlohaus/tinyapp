<?php
function getThumbnail(array $entry, int $minWidth): string
{
    foreach ($entry['thumbnails'] as $thumbnail) {
        if ($thumbnail['width'] >= $minWidth) {
            return $thumbnail['url'];
        }
    }
    return $entry['url'];
}

function isImage(string $mimeType): bool
{
    return str_starts_with($mimeType, 'image/');
}

function renderListItem(array $entry): void
{
    $title = htmlspecialchars($entry['title'] ?? $entry['fileName'] . '.' . $entry['fileExtension']);
    $url = getThumbnail($entry, 420);
    $isImage = isImage($entry['mimeType'] ?? '');

    ?><div class="col">
    <div class="card card-cover h-100 rounded-4 shadow overflow-hidden">
        <div class="d-flex flex-column text-shadow-1">
            <a href="<?= htmlspecialchars($entry['url']); ?>"
               data-alt="<?= htmlspecialchars($entry['alt'] ?? ''); ?>"
               onclick="return openMedia(this);"
            <?php if($isImage) { ?>
                data-image="1"
            <?php } ?>
               title="<?= $title ?>"
               class="mx-auto"
               style="min-height: var(--bs-gutter-y);">
                <span class="position-absolute top-0 fw-bold text-truncate start-0 px-1 w-100" style="height: 22px"><?= $title ?></span>
                <?php if($isImage) { ?>
                    <img title="<?= $title?>"
                         alt="<?= htmlspecialchars($entry['alt'] ?? ''); ?>"
                         style="max-height: 210px"
                         src="<?= htmlspecialchars($url); ?>">
                <?php } ?>
            </a>
        </div>
    </div>
    </div><?php
}

function renderListPage($items): void
{
    echo '<template>';
    array_map('renderListItem', $items);
    echo '</template>';
}

function renderList(array $list, int $pageSize = null): void
{
    if (isset($pageSize) && isset($list[0])) {
        $chunks = array_chunk($list, $pageSize);
        array_map('renderListItem', array_shift($chunks));
        array_map('renderListPage', $chunks);
    } else {
        array_map('renderListItem', $list);
    }
}