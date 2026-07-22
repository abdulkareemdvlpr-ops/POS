<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class BackupController extends Controller
{
    public function download()
    {
        $dbConfig = config('database.connections.' . config('database.default'));
        $driver   = $dbConfig['driver'] ?? config('database.default');
        $filename = 'pos_backup_' . date('Y-m-d_His') . '.sql';

        // ── SQLite ────────────────────────────────────────────────────────
        if ($driver === 'sqlite') {
            $dbPath = $dbConfig['database'];
            if (!file_exists($dbPath)) {
                return back()->with('error', 'SQLite database file not found.');
            }
            $filename = 'pos_backup_' . date('Y-m-d_His') . '.sqlite';
            return response()->download($dbPath, $filename);
        }

        // ── MySQL ─────────────────────────────────────────────────────────
        if ($driver === 'mysql') {
            $tmpFile = tempnam(sys_get_temp_dir(), 'pos_backup_') . '.sql';
            try {
                if (file_put_contents($tmpFile, $this->buildMysqlDump($dbConfig['database'])) === false) {
                    throw new RuntimeException('Unable to write backup file.');
                }
            } catch (Throwable $e) {
                report($e);
                return back()->with('error', 'MySQL Backup failed: ' . $e->getMessage());
            }
            return response()->download($tmpFile, $filename)->deleteFileAfterSend(true);
        }

        // ── PostgreSQL ────────────────────────────────────────────────────
        if ($driver === 'pgsql') {
            $tmpFile = tempnam(sys_get_temp_dir(), 'pos_backup_') . '.sql';
            try {
                if (file_put_contents($tmpFile, $this->buildPgsqlDump()) === false) {
                    throw new RuntimeException('Unable to write PostgreSQL backup file.');
                }
            } catch (Throwable $e) {
                report($e);
                return back()->with('error', 'PostgreSQL Backup failed: ' . $e->getMessage());
            }
            return response()->download($tmpFile, $filename)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'Unsupported database driver: ' . $driver);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  PostgreSQL pure-PHP dump (no pg_dump binary needed)
    // ─────────────────────────────────────────────────────────────────────
    private function buildPgsqlDump(): string
    {
        $connection = DB::connection();
        $pdo        = $connection->getPdo();

        // Get all user tables (public schema only, skip migrations table)
        $tablesResult = $connection->select(
            "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename"
        );

        $dump = [
            '-- POS System PostgreSQL Backup',
            '-- Generated at ' . now()->format('Y-m-d H:i:s'),
            '-- Database: ' . config('database.connections.' . config('database.default') . '.database'),
            '',
            'SET session_replication_role = replica; -- disable FK checks',
            '',
        ];

        foreach ($tablesResult as $tableRow) {
            $table  = $tableRow->tablename;
            $quoted = '"' . $table . '"';

            // Get column names and types
            $columns = $connection->select(
                "SELECT column_name, data_type, udt_name
                 FROM information_schema.columns
                 WHERE table_schema = 'public' AND table_name = ?
                 ORDER BY ordinal_position",
                [$table]
            );

            if (empty($columns)) {
                continue;
            }

            $dump[] = '-- Table: ' . $table;
            $dump[] = 'TRUNCATE TABLE ' . $quoted . ' CASCADE;';
            $dump[] = '';

            $rows = $connection->table($table)->get();

            foreach ($rows as $row) {
                $rowArr  = (array) $row;
                $colDefs = collect($columns)->keyBy('column_name');

                $colNames = collect(array_keys($rowArr))
                    ->map(fn($c) => '"' . $c . '"')
                    ->implode(', ');

                $values = collect($rowArr)->map(function ($v, $colName) use ($pdo, $colDefs) {
                    if ($v === null) {
                        return 'NULL';
                    }

                    $colDef  = $colDefs->get($colName);
                    $dataType = $colDef->data_type ?? '';
                    $udtName  = $colDef->udt_name ?? '';

                    // Boolean columns
                    if ($dataType === 'boolean' || $udtName === 'bool') {
                        return $v ? 'TRUE' : 'FALSE';
                    }

                    // Numeric / integer columns - no quoting needed
                    if (in_array($dataType, ['integer', 'bigint', 'smallint', 'numeric', 'decimal', 'double precision', 'real'])) {
                        return $v;
                    }

                    // JSON columns
                    if (in_array($dataType, ['json', 'jsonb'])) {
                        return $pdo->quote($v);
                    }

                    return $pdo->quote((string) $v);
                })->implode(', ');

                $dump[] = 'INSERT INTO ' . $quoted . ' (' . $colNames . ') VALUES (' . $values . ');';
            }

            // Reset sequences for tables with id column
            $hasId = collect($columns)->contains('column_name', 'id');
            if ($hasId) {
                $dump[] = "SELECT setval(pg_get_serial_sequence('$table', 'id'), COALESCE((SELECT MAX(id) FROM $quoted), 1));";
            }

            $dump[] = '';
        }

        $dump[] = 'SET session_replication_role = DEFAULT; -- re-enable FK checks';
        $dump[] = '';

        return implode(PHP_EOL, $dump);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  MySQL dump (unchanged)
    // ─────────────────────────────────────────────────────────────────────
    private function buildMysqlDump(string $database): string
    {
        $connection  = DB::connection();
        $pdo         = $connection->getPdo();
        $tables      = $connection->select('SHOW FULL TABLES WHERE Table_type = ?', ['BASE TABLE']);
        $tableColumn = 'Tables_in_' . $database;

        $dump = [
            '-- POS System MySQL Backup',
            '-- Generated at ' . now()->format('Y-m-d H:i:s'),
            'SET FOREIGN_KEY_CHECKS=0;',
            '',
        ];

        foreach ($tables as $tableRow) {
            $tableRow = (array) $tableRow;
            $table    = $tableRow[$tableColumn] ?? array_values($tableRow)[0];
            $quoted   = $this->quoteIdentifier($table);
            $create   = $connection->selectOne('SHOW CREATE TABLE ' . $quoted);
            $createSql = $create->{'Create Table'} ?? null;

            if (!$createSql) {
                continue;
            }

            $dump[] = 'DROP TABLE IF EXISTS ' . $quoted . ';';
            $dump[] = $createSql . ';';
            $dump[] = '';

            $rows = $connection->table($table)->get();
            foreach ($rows as $row) {
                $values = collect((array) $row)
                    ->map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v))
                    ->implode(', ');

                $columns = collect(array_keys((array) $row))
                    ->map(fn ($c) => $this->quoteIdentifier($c))
                    ->implode(', ');

                $dump[] = 'INSERT INTO ' . $quoted . ' (' . $columns . ') VALUES (' . $values . ');';
            }

            $dump[] = '';
        }

        $dump[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $dump[] = '';

        return implode(PHP_EOL, $dump);
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
