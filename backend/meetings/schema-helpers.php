<?php

if (!defined('CITAS_CSF_ENTRY')) {
    http_response_code(403);
    exit;
}

function ensure_meetings_student_foreign_key(PDO $pdo): void
{
    $stmt = $pdo->prepare(<<<SQL
        SELECT
            constraint_name,
            referenced_table_name,
            referenced_column_name
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE table_schema = DATABASE()
          AND table_name = 'meetings'
          AND column_name = 'student_id'
          AND referenced_table_name IS NOT NULL
SQL);
    $stmt->execute();
    $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hasStudentsForeignKey = false;
    foreach ($foreignKeys as $foreignKey) {
        $constraintName = (string) $foreignKey['constraint_name'];
        $referencedTable = (string) $foreignKey['referenced_table_name'];
        $referencedColumn = (string) $foreignKey['referenced_column_name'];

        if ($referencedTable === 'students' && $referencedColumn === 'id') {
            $hasStudentsForeignKey = true;
            continue;
        }

        $pdo->exec('ALTER TABLE meetings DROP FOREIGN KEY `' . str_replace('`', '``', $constraintName) . '`');
    }

    $pdo->exec(<<<SQL
        DELETE m
        FROM meetings m
        LEFT JOIN students s ON s.id = m.student_id
        WHERE s.id IS NULL
SQL);

    if ($hasStudentsForeignKey) {
        return;
    }

    $pdo->exec(<<<SQL
        ALTER TABLE meetings
        ADD CONSTRAINT fk_meetings_student
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
SQL);
}
