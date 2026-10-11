# Parche: aislamiento técnico empresa+sistema en endpoints DTE

Este parche cierra el último hueco de alcance de las credenciales técnicas.

- Agrega `jwt.dte.scope` a todo `/api/internal/dte/*`.
- Para humanos no cambia el request.
- Para técnicos, `external_system_id` lo fija el servidor desde el JWT revalidado.
- Si el cliente intenta enviar otro `external_system_id`, devuelve 403.
- Los recursos por `documentId`, `dispatchId` y `reservationId` se revalidan contra empresa+sistema; si pertenecen a otro ámbito, devuelve 404.
- Servicio DTE, empresas, certificados, CAF, avisos, sistemas externos y distribución CAF quedan `jwt.human`.
- Documento/workflow, polling, folios y reservas siguen disponibles para integraciones técnicas pero sujetos al scope.

No contiene migraciones y no modifica providers/repositories existentes.
