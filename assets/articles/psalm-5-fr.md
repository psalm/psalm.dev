<!--
  title: Announcing Psalm 5
  date: 2022-11-21 08:30:00
  author: The Maintainers of Psalm
-->

Lire cet article en [Anglais](/articles/psalm-5)

Lire cet article en [Ukrainien](/articles/psalm-5-uk)

---

On aimerait tous pouvoir remonter le temps, que ce soit pour corriger une erreur du passé, dire à un être aimé ce qu'il représente pour nous ou pour corriger une petite erreur dans un outil d'analyse statique pour PHP.

Malheureusement, les machines à remonter le temps n'existent pas, mais les versions logicielles majeures existent. Le plus gros changement visible pour les utilisateurs dans Psalm 5 est une correction relativement mineure: les tableaux clés/valeurs sont désormais scellés par défaut.

## Tableaux clés/valeurs scellés vs descellés

Si vous avez déjà utilisé les tableaux clés/valeurs, vous devriez connaitre cette syntaxe:

```php
<?php

/**
 * @param array{id: string, name: string} $user
 * @return array{id: string, name: string}
 */
function takesUserData(array $user): array {
  return $user;
}
```

Dans l'exemple précédent `takesUserData` n'accepte qu'un tableau avec exactement deux éléments: `id` et `name`. Le bloc de documentation de cette fonction nous dit qu'elle retourne un tableau, également avec exactement deux éléments.

Psalm accepte également un `array{id: string, name: string}` à être passé à une fonction qui attends un `array<string>`. C'est assez cohérent — le tableau a juste des éléments de type `string`, donc on doit pouvoir les passer à une fonction (comme `implode`) qui attends un tableau de `string`.

Qu'arrive t'il si on change notre fonction pour ajouter un autre élément dans le corps de la fonction `takesUserData`?

```php
<?php

/**
 * @param array{id: string, name: string} $user
 * @return array{id: string, name: string}
 */
function takesUserData(array $user): array {
  $user['extra_data'] = new stdClass();
  return $user;
}
```

Psalm se plaint désormais que l'on ne retourne pas ce que l'on avait prévu - c'est un changement par rapport aux versions précédentes qui autorisaient ce comportement.

Le précédent (comportement cassé) signifie que l'on pouvait faire quelque chose comme `implode('', takesUserData($foo))` sans que Psalm ne remonte d'erreur. Cela pouvait aboutir à [un code qui plante à l'exécution](https://3v4l.org/PoVil).

Quand un outil d'analyse statique autorise des comportements qui aboutissent à des erreurs à l'exécution, on ne peut pas dire de cet outil qu'il respecte la *sûreté du typage*. Il y a quelques cas particuliers en PHP pour lesquels on ne peut pas garantir la sûreté, mais Psalm essaye de les éviter dès que possible. Nous avons décidé de légèrement changer le comportement de Psalm de façon à ce qu'il cause un minimum d'inconvénients pour ses utilisateurs.

Un mot de Matt Brown, le créateur de Psalm:

> C'est entièrement ma faute. Désolé. J'ai inventé la syntaxe `array{id: string, name: string}` mais je n'ai pas suffisamment fouillé la sémantique.

Au moment de cet article, les autres outils d'analyse statique ([Phan](https://phan.github.io/demo/?code=%3C%3Fphp%0A%0A%2F**%0A+*+%40param+array%7Bid%3A+string%2C+name%3A+string%7D+%24user%0A+*+%40return+array%7Bid%3A+string%2C+name%3A+string%7D%0A+*%2F%0Afunction+takesUserData%28array+%24user%29%3A+array+%7B%0A++%24user%5B%27extra_data%27%5D+%3D+new+stdClass%28%29%3B%0A++return+%24user%3B%0A%7D%0A%0A%24foo+%3D+%5B%27id%27+%3D%3E+%27DP42%27%2C+%27name%27+%3D%3E+%27Douglas+Adams%27%5D%3B%0Aecho+implode%28%27%27%2C+takesUserData%28%24foo%29%29%3B) , [PHPStan](https://phpstan.org/r/4a61d13c-74f0-46d3-9bad-f3a61dd1d172)) autorisent ce comportement, et nous espérons qu'avec le temps, ils adopteront également la syntaxe `...` pour garantir la sûreté du typage des tableaux clés/valeurs

Si vous souhaitez avoir une fonction qui autorise plus de clés que celles explicitées, vous pouvez utiliser `...` pour documenter que le tableau est descellé:

```php
<?php

/**
 * @param array{id: string, name: string, ...} $user
 * @return array{id: string, name: string, ...}
 */
function takesUserData(array $user): array {
  $user['extra_data'] = new stdClass();
  return $user;
}
```

Cela suit le comportement de `...` en [Hack code](https://docs.hhvm.com/hack/built-in-types/shape#open-and-closed-shapes).

Psalm préviendra que la sortie de la fonction `takesUserData` (avec un tableau descellé) puisse être utilisé dans un appel à `implode`:

```php
<?php

/**
 * @param array{id: string, name: string, ...} $user
 * @return array{id: string, name: string, ...}
 */
function takesUserData(array $user): array {
  $user['extra_data'] = new stdClass();
  return $user;
}

$foo = ['id' => 'DP42', 'name' => 'Douglas Adams'];
echo implode('', takesUserData($foo));
```

Qu'est-ce que ça signifie pour vous? Probablement rien! Le code de Psalm contient beaucoup de tableaux clé/valeurs, mais un seul autorise des éléments supplémentaires. Nous espérons que l'impact de cette mise à jour sera extrêmement faible.

## Quoi d'autre dans Psalm 5?

Nous avons ajouté le support tant attendu pour les intersections de types et pour d'autres fonctionnalités de PHP 8.

Psalm 5 ajoute également de nouveaux types:

- [list{int, string, float}](https://psalm.dev/docs/annotating_code/type_syntax/array_types/#list-shapes)
- [properties-of<T>](https://psalm.dev/docs/annotating_code/type_syntax/utility_types/#properties-oft)
- [Variable templates](https://psalm.dev/docs/annotating_code/type_syntax/utility_types/#variable-templates)
- [int-range<x, y>](https://psalm.dev/docs/annotating_code/type_syntax/scalar_types/#int-range)

Ces types vont aider à détecter beaucoup plus de bugs et corriger un tas de faux-négatifs en vous permettant de décrire votre code plus précisément.

Sous le capot, nous avons fait de nombreux changements dans le cœur de Psalm. Le système de typage est désormais immutable, ce qui corrige un ensemble de bugs liés à la parallélisation et **améliore les performances par 15-20%** en simple-thread et en multi-thread (principalement en réduisant l'usage de `__clone`).

Nous avons également retiré le support pour les API de plugins historiques (ajoutées depuis Psalm 3) dans la mesure où les nouvelles API sont disponibles depuis quelques années.

Psalm est un gros projet, avec beaucoup à faire — si vous voulez participer, il y a beaucoup [que vous pouvez faire pour aider](https://github.com/vimeo/psalm/issues?q=is%3Aissue+is%3Aopen+label%3A%22Help+wanted%22), y compris tout un tas de problèmes [pour les développeurs qui ne connaissent rien à Psalm](https://github.com/vimeo/psalm/issues?q=is%3Aissue+is%3Aopen+label%3A%22easy+problems%22)!

Dans les prochains mois, nous allons travailler sur le support complet de PHP 8.2 et plus encore!
