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
        $isNative = class_exists(\Native\Laravel\Facades\Dialog::class);
        $filename = 'pos_backup_' . date('Y-m-d_His') . ($driver === 'sqlite' ? '.sqlite' : '.sql');

        if ($driver === 'sqlite') {
            $dbPath = $dbConfig['database'];
            if (!file_exists($dbPath)) {
                return back()->with('error', 'SQLite database file not found.');
            }

            if ($isNative) {
                try {
                    $savePath = \Native\Laravel\Facades\Dialog::new()
                        ->title('Save SQLite Database Backup')
                        ->defaultPath($filename)
                        ->save();
                    
                    if ($savePath) {
                        copy($dbPath, $savePath);
                        return back()->with('success', 'Backup saved successfully to ' . $savePath);
                    }
                    return back(); // user cancelled
                } catch (Throwable $e) {
                    report($e);
                    return back()->with('error', 'Native backup save failed: ' . $e->getMessage());
                }
            }

            return response()->download($dbPath, $filename);
        }

        if ($driver === 'mysql') {
            if ($isNative) {
                try {
                    $savePath = \Native\Laravel\Facades\Dialog::new()
                        ->title('Save MySQL Database Backup')
                        ->defaultPath($filename)
                        ->save();

                    if ($savePath) {
                        $dumpContent = $this->buildMysqlDump($dbConfig['database']);
                        file_put_contents($savePath, $dumpContent);
                        return back()->with('success', 'Backup saved successfully to ' . $savePath);
                    }
                    return back(); // user cancelled
                } catch (Throwable $e) {
                    report($e);
                    return back()->with('error', 'Native backup save failed: ' . $e->getMessage());
                }
            }

            $tmpFile = tempnam(sys_get_temp_dir(), 'pos_backup_') . '.sql';
            try {
                if (file_put_contents($tmpFile, $this->buildMysqlDump($dbConfig['database'])) === false) {
                    throw new RuntimeException('Unable to write backup file.');
                }
            } catch (Throwable $e) {
                report($e);
                return back()->with('error', 'Backup failed. Please check server configuration.');
            }

            return response()->download($tmpFile, $filename)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'Backup is only supported for MySQL and SQLite databases.');
    }

    private function buildMysqlDump(string $database): string
    {
        $connection = DB::connection();
        $pdo        = $connection->getPdo();
        $tables     = $connection->select('SHOW FULL TABLES WHERE Table_type = ?', ['BASE TABLE']);
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
