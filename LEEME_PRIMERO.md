# Corrección de test de scope técnico DTE

Este parche reemplaza únicamente:

`tests/Unit/EnsureDteTechnicalScopeTest.php`

La causa de los 4 fallos era el intento de usar `Mockery::mock()` sobre entidades `final` (`DteDocument`, `SiiDispatch`, `FolioReservation`).

La corrección usa instancias reales de esas entidades y mantiene mockeados únicamente los contratos de repositorio.

No modifica código productivo.
