-- Script para habilitar partidos amistosos en el sistema
-- Este script modifica la tabla partidos para permitir partidos sin torneo

-- OPCIÓN 1: Modificar la estructura para permitir NULL (RECOMENDADO)
-- Esto permite que los partidos amistosos no tengan torneo ni fase asociada

ALTER TABLE `partidos`
MODIFY `torneo_id` int(11) DEFAULT NULL COMMENT 'NULL para partidos amistosos',
MODIFY `fase_id` int(11) DEFAULT NULL COMMENT 'NULL para partidos amistosos';

-- Actualizar la restricción de clave foránea para permitir NULL
ALTER TABLE `partidos`
DROP FOREIGN KEY IF EXISTS `fk_partidos_torneo`;

ALTER TABLE `partidos`
ADD CONSTRAINT `fk_partidos_torneo` FOREIGN KEY (`torneo_id`) REFERENCES `torneos` (`id`) ON DELETE CASCADE;

ALTER TABLE `partidos`
DROP FOREIGN KEY IF EXISTS `fk_partidos_fase`;

ALTER TABLE `partidos`
ADD CONSTRAINT `fk_partidos_fase` FOREIGN KEY (`fase_id`) REFERENCES `fases` (`id`) ON DELETE CASCADE;

-- Agregar índice para identificar partidos amistosos fácilmente
CREATE INDEX `idx_partidos_amistosos` ON `partidos` (`torneo_id`, `inicio_partido`)
WHERE `torneo_id` IS NULL;

-- NOTA: Si tienes partidos con torneo_id = 0 o fase_id = 0, actualízalos a NULL
UPDATE `partidos` SET `torneo_id` = NULL WHERE `torneo_id` = 0;
UPDATE `partidos` SET `fase_id` = NULL WHERE `fase_id` = 0;

-- Verificar los cambios
SELECT COUNT(*) as 'Total partidos amistosos'
FROM `partidos`
WHERE `torneo_id` IS NULL;
