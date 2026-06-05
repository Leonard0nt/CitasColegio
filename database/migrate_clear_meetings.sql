-- Elimina todas las reuniones agendadas.
-- Ejecutar solo cuando se quiera limpiar por completo la tabla meetings.

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE meetings;
SET FOREIGN_KEY_CHECKS = 1;
