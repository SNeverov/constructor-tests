<?php
declare(strict_types=1);

function test_categories_catalog(): array
{
    static $categories = [
        'avtomobili-i-pdd' => 'Автомобили и ПДД',
        'biznes-i-upravlenie' => 'Бизнес и управление',
        'biologiya' => 'Биология',
        'geografiya' => 'География',
        'grazhdanskaya-oborona-i-chs' => 'Гражданская оборона и ЧС',
        'inostrannye-yazyki' => 'Иностранные языки',
        'informatika' => 'Информатика',
        'iskusstvo' => 'Искусство',
        'istoriya' => 'История',
        'kiberbezopasnost' => 'Кибербезопасность',
        'literatura' => 'Литература',
        'matematika' => 'Математика',
        'meditsina-i-pervaya-pomoshch' => 'Медицина и первая помощь',
        'mekhanika' => 'Механика',
        'obzh' => 'ОБЖ',
        'obshchestvoznanie' => 'Обществознание',
        'obshchie-znaniya' => 'Общие знания',
        'okhrana-truda' => 'Охрана труда',
        'pozharnaya-bezopasnost' => 'Пожарная безопасность',
        'programmirovanie' => 'Программирование',
        'promyshlennaya-bezopasnost' => 'Промышленная безопасность',
        'promyshlennoe-oborudovanie' => 'Промышленное оборудование',
        'russkiy-yazyk' => 'Русский язык',
        'sport' => 'Спорт',
        'tekhnicheskie-distsipliny' => 'Технические дисциплины',
        'tekhnologiya' => 'Технология',
        'finansovaya-gramotnost' => 'Финансовая грамотность',
        'fizika' => 'Физика',
        'khimiya' => 'Химия',
        'elektrobezopasnost' => 'Электробезопасность',
        'elektrotekhnika' => 'Электротехника',
        'energetika' => 'Энергетика',
        'raznoe' => 'Разное',
    ];

    return $categories;
}

function test_category_legacy_slug_map(): array
{
    return [
        'proizvodstvo-i-oborudovanie' => 'promyshlennoe-oborudovanie',
    ];
}

function test_category_legacy_name_map(): array
{
    return [
        'Производство и оборудование' => 'Промышленное оборудование',
    ];
}

function test_category_canonical_name(string $name): string
{
    $name = trim($name);
    $legacyMap = test_category_legacy_name_map();
    return $legacyMap[$name] ?? $name;
}

function test_category_canonical_slug(string $slug): string
{
    $slug = trim($slug);
    $legacyMap = test_category_legacy_slug_map();
    return $legacyMap[$slug] ?? $slug;
}

function test_category_match_names(string $name): array
{
    $canonicalName = test_category_canonical_name($name);
    $names = [$canonicalName => $canonicalName];

    foreach (test_category_legacy_name_map() as $legacyName => $targetName) {
        if ($targetName === $canonicalName) {
            $names[$legacyName] = $legacyName;
        }
    }

    return array_values($names);
}

function test_category_default_name(): string
{
    return 'Разное';
}

function test_category_default_names(): array
{
    return [test_category_default_name()];
}

function test_category_placeholder_text(): string
{
    return 'Выберите категорию';
}

function test_category_name_to_slug_map(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }

    $map = [];
    foreach (test_categories_catalog() as $slug => $name) {
        $map[$name] = $slug;
    }
    foreach (test_category_legacy_name_map() as $legacyName => $targetName) {
        if (isset($map[$targetName])) {
            $map[$legacyName] = $map[$targetName];
        }
    }

    return $map;
}

function test_category_slug_to_name(string $slug): ?string
{
    $slug = trim($slug);
    if ($slug === '') {
        return null;
    }

    $catalog = test_categories_catalog();
    $canonicalSlug = test_category_canonical_slug($slug);
    return $catalog[$canonicalSlug] ?? null;
}

function test_category_slug_from_name(string $name): ?string
{
    $name = trim($name);
    if ($name === '') {
        return null;
    }

    $map = test_category_name_to_slug_map();
    return $map[$name] ?? null;
}

function test_category_is_valid(string $name): bool
{
    return test_category_slug_from_name($name) !== null;
}

function test_category_normalize_names(array $rawNames): array
{
    $normalized = [];
    foreach ($rawNames as $rawName) {
        $name = trim((string)$rawName);
        if ($name === '') {
            continue;
        }

        $canonicalName = test_category_canonical_name($name);
        if (!test_category_is_valid($canonicalName)) {
            throw new InvalidArgumentException('Выберите категории только из списка');
        }

        $normalized[$canonicalName] = $canonicalName;
    }

    return array_values($normalized);
}

function test_category_names_from_input(mixed $rawValue): array
{
    if (is_array($rawValue)) {
        return test_category_normalize_names($rawValue);
    }

    if ($rawValue === null) {
        return [];
    }

    return test_category_normalize_names([(string)$rawValue]);
}

function test_category_normalize_name(?string $rawName): string
{
    $name = trim((string)$rawName);
    if ($name === '') {
        return test_category_default_name();
    }

    $canonicalName = test_category_canonical_name($name);
    if (!test_category_is_valid($canonicalName)) {
        throw new InvalidArgumentException('Выберите категорию из списка');
    }

    return $canonicalName;
}

function test_category_display_name(?string $rawName): string
{
    try {
        return test_category_normalize_name($rawName);
    } catch (InvalidArgumentException) {
        return test_category_default_name();
    }
}

function test_category_display_names(mixed $rawValue): array
{
    try {
        $names = test_category_names_from_input($rawValue);
        return $names === [] ? test_category_default_names() : $names;
    } catch (InvalidArgumentException) {
        return test_category_default_names();
    }
}

function test_category_trigger_text(array $names): string
{
    $names = array_values(array_filter(array_map('trim', $names), static fn(string $name): bool => $name !== ''));
    if ($names === []) {
        return test_category_placeholder_text();
    }

    if (count($names) <= 2) {
        return implode(', ', $names);
    }

    return $names[0] . ', ' . $names[1] . ' +' . (count($names) - 2);
}

function test_category_url_by_name(string $name): string
{
    $normalized = test_category_display_name($name);
    $slug = test_category_slug_from_name($normalized);

    if ($slug === null) {
        $slug = test_category_slug_from_name(test_category_default_name()) ?? 'raznoe';
    }

    return '/categories/' . rawurlencode($slug);
}
