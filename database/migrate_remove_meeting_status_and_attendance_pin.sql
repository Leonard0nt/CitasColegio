-- Elimina el estado de reuniones y la configuración de PIN de asistencia.
ALTER TABLE meetings
    DROP INDEX idx_meetings_status,
    DROP COLUMN status;

DROP TABLE IF EXISTS system_settings;
