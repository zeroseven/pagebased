# Tests

## Unit Tests

Unit-Tests testen isolierte PHP-Logik ohne Datenbankverbindung.

```bash
# Via Composer (empfohlen)
composer test:unit

# Direkt via PHPUnit
.build/bin/phpunit -c phpunit.unit.xml

# Mit Coverage-Report
composer test:coverage:unit
```

## Functional Tests

Functional-Tests benötigen eine laufende Datenbankverbindung.
Sie werden über die DDEV-Umgebung ausgeführt.

```bash
# In DDEV ausführen
ddev composer test:functional

# Oder mit allen Tests
ddev composer test

# Mit Coverage-Report
ddev composer test:coverage
```

## Umgebungsvariablen für Functional Tests

Die Functional Tests verwenden folgende Variablen (werden durch DDEV gesetzt):

| Variable              | Wert (DDEV)  |
|-----------------------|--------------|
| `typo3DatabaseHost`   | `db`         |
| `typo3DatabaseName`   | `db`         |
| `typo3DatabaseUsername` | `root`     |
| `typo3DatabasePassword` | `root`     |

## Testabdeckung

| Bereich                         | Typ        | Datei                                                         |
|---------------------------------|------------|---------------------------------------------------------------|
| `Pagination`-Klasse             | Unit       | `Unit/Pagination/PaginationTest.php`                          |
| `TagUtility`                    | Unit       | `Unit/Utility/TagUtilityTest.php`                             |
| `CastUtility`                   | Unit       | `Unit/Utility/CastUtilityTest.php`                            |
| `RootLineUtility` (DB-Traversal)| Functional | `Functional/Utility/RootLineUtilityTest.php`                  |
| `AbstractObjectRepository`      | Functional | `Functional/Domain/Repository/AbstractObjectRepositoryTest.php` |
