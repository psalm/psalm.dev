<!--
  title: Представляємо Psalm 5
  date: 2022-11-21 08:30:00
  author: Команда підтримки Psalm
-->

Ця стаття [англійською](/articles/psalm-5).

Кто хоч раз не хотів би повернутися в минуле, чи задля того, щоб виправити якусь історичну помилку, або сказати близькій людині, як багато вона для нас значила, чи може виправити незначне архітектурне рішення в інструменті статичного аналізу PHP?

На жаль, машини часу ще не винайдено, але мажорні версії є. Найбільша зміна у Psalm 5, яка стосується користувачів — це відносно незначне виправлення: шейпи (array shapes) тепер вважаються запечатаними (sealed) за замовчуванням.

## Відмінності запечатаних шейпів від незапечатанх

Якщо ви раніше використовували шейпи, вам має бути знайомий базовий синтакс:

```php
<?php
/**
 * @param array{id: string, name: string} $user
 * @return array{id: string, name: string}
 */
function takeUserData(array $user): array {
  return $user;
}
```

У наведеному вище прикладі `takesUserData` приймає тільки масив із рівно двома елементами, `id` і `name`. З докблоку функції також бачимо, що вона повертає масив рівно з двома елементами.

Psalm також дозволяє передавати шейп `array{id: string, name: string}` у будь-яку функцію, яка очікує `array<string>`. Це має сенс — масив містить лише елементи типу `string`, тому передати його у функцію (наприклад, `implode()`), яка очікує масив рядкив, не має викликати жодних проблем.

Але що, як ми змінимо нашу функцію, щоб додати інший елемент у коді `takesUserData`?

```php
<?php
/**
 * @param array{id: string, name: string} $user
 * @return array{id: string, name: string}
 */
function takeUserData(array $user): array {
  $user['extra_data'] = new stdClass();
  return $user;
}
```

Тепер Psalm скаржиться, що ми не повертаємо те, що ми обіцяли — це змінилося в порівнянні з попередніми версіями Psalm, які дозволяли таку поведінку.

Попередня (неправильна поведінка) означала, що ми могли зробити щось на кшталт `implode('', takeUserData($foo))` і Psalm би цього не помітив. Це може призвести до [коду, який не буде працювати](https://3v4l.org/PoVil).

Коли засіб перевірки типів допускає поведінку, яка призводить до проблем під час виконання, ми називаємо цей засіб перевірки типів *ненадійним*. Є кілька наріжних випадків у PHP, де ненадійної перевірки типів не уникнути, але Psalm намагається уникати цього, де це можливо. Ми вирішили дещо змінити поведінку Psalm таким чином, щоб, як ми сподіваємося, завдасть мінімум болі користувачам Psalm.

Примітка від Метта Брауна, автора Psalm:

> Це все моя вина. Я придумав конвенцію `array{id: string, name: string}`, але не визначив всю семантику.

На момент написання цієї статті інші інструменти статичного аналізу PHP ([Phan](https://phan.github.io/demo/?code=%3C%3Fphp%0A%0A%2F**%0A+*+%40param+array%7Bid%3A+string%2C+name%3A+string%7D+%24user%0A+*+%40return+array%7Bid%3A+string%2C+name%3A+string%7D%0A+*%2F%0Afunction+takesUserData%28array+%24user%29%3A+array+%7B%0A++%24user%5B%27extra_data%27%5D+%3D+new+stdClass%28%29%3B%0A++return+%24user%3B%0A%7D%0A%0A%24foo+%3D+%5B%27id%27+%3D%3E+%27DP42%27%2C+%27name%27+%3D%3E+%27Douglas+Adams%27%5D%3B%0Aecho+implode%28%27%27%2C+takesUserData%28%24foo%29%29%3B) , [PHPStan](https://phpstan.org/r/4a61d13c-74f0-46d3-9bad-f3a61dd1d172)) дозволяють таку поведінку, і ми сподіваємось, що з часом вони також приймуть конвенцію `...` і усунуть неправильне поводження з шейпами.


Якщо ви хочете, щоб функція приймала шейп з більшою, ніж задекларованою кількісті елементів, ви можете використовувати `...`, щоб позначити незапечатани шейп:

```php
<?php
/**
 * @param array{id: string, name: string, ...} $user
 * @return array{id: string, name: string, ...}
 */
function takeUserData(array $user): array {
  $user['extra_data'] = new stdClass();
  return $user;
}
```

Це збігається з тим, як `...` використовується у [Hack](https://docs.hhvm.com/hack/built-in-types/shape#open-and-closed-shapes).

Psalm запобігатиме використанню результату цієї функції `takesUserData` (з незапечатаним шейпом) у викликах `implode`:

```php
<?php
/**
 * @param array{id: string, name: string, ...} $user
 * @return array{id: string, name: string, ...}
 */
function takeUserData(array $user): array {
  $user['extra_data'] = new stdClass();
  return $user;
}
$foo = ['id' => 'DP42', 'name' => 'Дуглас Адамс'];
echo implode('', takeUserData($foo));
```

Що це означає для вас? Мабуть нічого! У коді Psalm використовується багато шейпів, і лише один із них допускає додаткові поля. Ми сподіваємось, що (негативний) вплив цього оновлення буде дуже незначним.

## Що ще є у Psalm 5?

Ми додали довгоочікувану підтримку перетину типів та інших нових функцій PHP 8.

Psalm 5 також додає кілька нових типів:

- [list{int, string, float}](https://psalm.dev/docs/annotating_code/type_syntax/array_types/#list-shapes)
- [properties-of<T>](https://psalm.dev/docs/annotating_code/type_syntax/utility_types/#properties-oft)
- [Variable templates](https://psalm.dev/docs/annotating_code/type_syntax/utility_types/#variable-templates)
- [int-range<x, y>](https://psalm.dev/docs/annotating_code/type_syntax/scalar_types/#int-range)

Ці типи допоможуть виявити набагато більше помилок і виправити цілу купу хибно-негативних результатів, дозволяючи точніше описати свій код.

Під капотом ми внесли деякі значні зміни у внутрішні елементи Psalm. Вся система типів тепер незмінна (immutable), що виправляє цілий клас проблем з багатопоточністю і **підвищує швидкість роботи Psalm на 15-20%** як в однопоточному, так і в багатопоточному режимі (переважно за рахунок зменшення використання `__clone`).

Ми також припинили підтримку застарілого API плагінів (введеного в Psalm 3), оскільки новий існує вже пару років.

Psalm — це великий проект, у якому потрібно багато зробити ще дуже багато — якщо ви хочете внести свій внесок, [ви можете нам допомогти](https://github.com/vimeo/psalm/issues?q=is%3Aissue+is%3Aopen+label%3A%22Help+wanted%22), включно із цілою купою багів [для розробників, які нічого не знають про внутрішню роботу Psalm](https://github.com/vimeo/psalm/issues?q=is%3Aissue+is%3Aopen+label%3A%22easy+problems%22)!

У найближчі місяці ми працюватимемо над повною підтримкою PHP 8.2 і над багато чим іншим!
