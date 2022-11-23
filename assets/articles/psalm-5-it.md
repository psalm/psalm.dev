<!--
  title: Rilascio di Psalm 5
  date: 2022-11-29 08:30:00
  author: Il team di Psalm
-->

Potete anche leggere la versione [inglese](/articles/psalm-5), [francese](/articles/psalm-5-fr) e [ucraina](/articles/psalm-5-uk) di questo articolo.  

---

Tutti abbiamo desiderato almeno una volta di poter tornare indietro nel tempo per prevenire una ingiustizia della Storia, per dire ad una persona amata quanto fosse importante per noi, o per correggere un piccolo errore architetturale in uno strumento di analisi statica per PHP.


Purtroppo, le macchine del tempo non esistono, ma le release maggiori sì. Il più grande cambiamento lato utente di Psalm 5 è un bugfix relativamente piccolo: gli array a chiavi esplicite ora sono chiusi di default.

## Array aperti vs array chiusi

Se avete lavorato con array a chiavi esplicite in Psalm, sicuramente la seguente sintassi vi risulterà familiare:

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

In questo esempio, `takesUserData` accetta solo array con esattamente due elementi (con chiavi `id` e `name`, ciascuna di tipo stringa). Anche il docblock di ritorno della funzione ci dice che la funzione ritornerà un array di esattamente due elementi con le stesse caratteristiche.

Psalm permette inoltre il passaggio di valori di tipo `array{id: string, name: string}` a funzioni che si aspettano un generico `array<string>`: ciò ha senso, poiché abbiamo specificato che i valori delle (sole) chiavi `id` e `name` sono di tipo stringa, e ha senso passare un array di sole stringhe ad una funzione (come `implode`) che accetta un array di stringhe.  

Ma cosa succederebbe se cambiassimo la nostra funzione, aggiungendo un ulteriore elemento non documentato all'array ritornato?  

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

Psalm si lamenterebbe che stiamo ritornando un valore diverso da quello documentato: un cambiamento rispetto alle versioni precedenti di Psalm, che tolleravano comportamenti del genere.  

Il comportamento precedente è errato perché permette di scrivere il seguente codice senza errori in fase di analisi Psalm, quando invece [causa sempre una eccezione in fase di esecuzione](https://3v4l.org/PoVil): `implode('', takesUserData($foo))`.  

Quando un typechecker permette comportamenti che causano errori a runtime, il typechecker è detto *debole*. Ci sono alcuni casi limite in PHP dove un comportamento debole è inevitabile, ma Psalm tenta di evitarli il più possibile. Abbiamo quindi deciso di modificare il comportamento di Psalm in un modo che speriamo causi meno problemi possibile ai nostri utenti.  

Ecco un commento di Matt Brown, il creatore di Psalm:  

> È tutta colpa mia. Mi dispiace. Ho creato la convenzione `array{id: string, name: string}`, ma non ne ho definito bene la semantica.

Al momento della stesura di questo documento, molti strumenti di analisi statica per PHP come ([Phan](https://phan.github.io/demo/?code=%3C%3Fphp%0A%0A%2F**%0A+*+%40param+array%7Bid%3A+string%2C+name%3A+string%7D+%24user%0A+*+%40return+array%7Bid%3A+string%2C+name%3A+string%7D%0A+*%2F%0Afunction+takesUserData%28array+%24user%29%3A+array+%7B%0A++%24user%5B%27extra_data%27%5D+%3D+new+stdClass%28%29%3B%0A++return+%24user%3B%0A%7D%0A%0A%24foo+%3D+%5B%27id%27+%3D%3E+%27DP42%27%2C+%27name%27+%3D%3E+%27Douglas+Adams%27%5D%3B%0Aecho+implode%28%27%27%2C+takesUserData%28%24foo%29%29%3B) , [PHPStan](https://phpstan.org/r/4a61d13c-74f0-46d3-9bad-f3a61dd1d172)) permettono il comportamento errato, e noi speriamo che un giorno, col tempo, adotteranno anche loro la convenzione `...` per rimuovere questa falla nella loro gestione degli array a chiavi esplicite.

Se è assolutamente indispensabile per una funzione usare array a chiavi esplicite con possibili chiavi aggiuntive (array aperti), è possibile usare la sintassi `...`:

```php
<?php

/**
 * @param array{id: string, name: string, ...} $user
 * @return array{id: string, name: string, ...}
 */
function takesUserDataOpen(array $user): array {
  $user['extra_data'] = new stdClass();
  return $user;
}
```

La sintassi `...` è la stessa utilizzata dal linguaggio [Hack](https://docs.hhvm.com/hack/built-in-types/shape#open-and-closed-shapes) per definire array a chiavi esplicite aperti.

Psalm impedirà, ad esempio, l'uso del valore di ritorno della funzione `takesUserDataOpen` (un array aperto) in chiamate a funzioni che accettano un array di stringhe, come `implode`:

```php
<?php

/**
 * @param array{id: string, name: string, ...} $user
 * @return array{id: string, name: string, ...}
 */
function takesUserDataOpen(array $user): array {
  $user['extra_data'] = new stdClass();
  return $user;
}

$foo = ['id' => 'DP42', 'name' => 'Douglas Adams'];
echo implode('', takesUserData($foo));
```

Inoltre, gli array aperti non possono fare uso di certi miglioramenti che potrebbero trovare bug nascosti.  

Cosa cambia per te? Probabilmente nulla! Psalm stesso usa molti array a chiavi esplicite, e solo un punto ha richiesto l'uso di un array aperto. Noi speriamo che l'impatto dovuto a questa modifica sia molto piccolo.

## Cos'altro c'è di nuovo in Psalm 5?

Abbiamo finalmente aggiunto il supporto ai tipi intersezione e ad ulteriori funzionalità PHP 8.  

Psalm 5 inoltre aggiunge alcuni nuovi tipi:

- [list{int, string, float}](https://psalm.dev/docs/annotating_code/type_syntax/array_types/#list-shapes)
- [properties-of<T>](https://psalm.dev/docs/annotating_code/type_syntax/utility_types/#properties-oft)
- [Variable templates](https://psalm.dev/docs/annotating_code/type_syntax/utility_types/#variable-templates)
- [int-range<x, y>](https://psalm.dev/docs/annotating_code/type_syntax/scalar_types/#int-range)

Questi tipi possono aiutare a trovare molti bug nascosti, e correggono un mucchio di falsi positivi permettendo inoltre una descrizione più accurata del codice.

Abbiamo apportato enormi modifiche interne a Psalm. L'intero type system è ora immutable: ciò rimuove un'intera classe di bug multi-threaded e **migliora le performance del 15-20%**, sia in modalità single-threaded che in modalità multi-threaded (principalmente riducendo l'uso di `__clone`).

Abbiamo inoltre rimosso la vecchia API per i plugin (introdotta in Psalm 3), la nuova API è già in uso da un paio d'anni.

Psalm è un grande progetto con tante cose da fare — se volete darci una mano, ci sono [tanti modi per farlo](https://github.com/vimeo/psalm/issues?q=is%3Aissue+is%3Aopen+label%3A%22Help+wanted%22), incluso un mucchio di issue [per sviluppatori che non hanno mai lavorato con i sorgenti di Psalm](https://github.com/vimeo/psalm/issues?q=is%3Aissue+is%3Aopen+label%3A%22easy+problems%22)!

Nei prossimi mesi miglioreremo il supporto PHP 8.2, e introdurremo tante altre novità e miglioramenti!