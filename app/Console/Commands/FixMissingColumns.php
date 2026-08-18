<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use Database\Schema\UserSchema;   // ✅ เพิ่มบรรทัดนี้
use Database\Schema\PostSchema;   // ✅ เพิ่มบรรทัดนี้

class FixMissingColumns extends Command
{
    protected $signature = 'db:fix-missing
                            {--run : Run migration immediately after generating it}
                            {--dry-run : Only check schema without generating migration}';

    protected $description = 'ตรวจสอบ Database Schema และสร้าง Migration สำหรับ Field ที่หายหรือไม่ตรง';

    /**
     * ============================================================
     * Expected Schema
     * ============================================================
     */
    protected function getExpectedSchema(): array
    {
        return [
            'users' => UserSchema::definition(),
            'posts' => PostSchema::definition(),
        ];
    }

    /**
     * ============================================================
     * Handle
     * ============================================================
     */
    public function handle()
    {
        $this->newLine();

        $this->info('🔧 เริ่มตรวจสอบ Database Schema...');
        $this->newLine();

        $allChanges = [];

        foreach ($this->getExpectedSchema() as $table => $columns) {

            $changes = $this->checkTable($table, $columns);

            if (!empty($changes)) {
                $allChanges[$table] = $changes;
            }

            $this->newLine();
        }

        /**
         * ไม่มีปัญหา
         */
        if (empty($allChanges)) {

            $this->info('========================================');
            $this->info('✅ Database Schema ครบถ้วน');
            $this->info('========================================');

            return Command::SUCCESS;
        }

        /**
         * Dry run
         */
        if ($this->option('dry-run')) {

            $this->warn('⚠️ Dry Run Mode');
            $this->warn('ไม่มีการสร้าง Migration และไม่มีการแก้ Database');

            return Command::SUCCESS;
        }

        /**
         * สร้าง Migration
         */
        $this->newLine();
        $this->info('========================================');
        $this->info('📝 สร้าง Migration');
        $this->info('========================================');

        $migrationPath = $this->createMigration($allChanges);

        if (!$migrationPath) {
            $this->error('❌ ไม่สามารถสร้าง Migration ได้');

            return Command::FAILURE;
        }

        $this->newLine();
        $this->info("✅ สร้าง Migration สำเร็จ:");
        $this->line($migrationPath);

        /**
         * ถ้าใส่ --run
         */
        if ($this->option('run')) {

            $this->newLine();

            if (!$this->confirm('⚠️ ต้องการ Run Migration ตอนนี้หรือไม่?')) {
                $this->info('ยกเลิกการ Run Migration');

                return Command::SUCCESS;
            }

            $this->newLine();

            $this->info('🚀 กำลัง Run Migration...');

            $exitCode = $this->call('migrate');

            if ($exitCode === 0) {
                $this->newLine();
                $this->info('✅ Migration สำเร็จ');
            } else {
                $this->error('❌ Migration ไม่สำเร็จ');
            }

            return $exitCode;
        }

        $this->newLine();

        $this->info('💡 หากต้องการ Run Migration:');
        $this->line('   php artisan migrate');

        return Command::SUCCESS;
    }

    /**
     * ============================================================
     * Check Table
     * ============================================================
     */
    private function checkTable(string $table, array $expectedColumns): array
    {
        $this->info("📋 Table: {$table}");

        /**
         * Table ไม่มี
         */
        if (!Schema::hasTable($table)) {

            $this->error("❌ Table {$table} ไม่มีอยู่ใน Database");

            return [
                'table_missing' => true,
                'columns' => $expectedColumns,
            ];
        }

        /**
         * ดึง Column จริงจาก DB
         */
        $actualColumns = Schema::getColumns($table);

        $actualMap = [];

        foreach ($actualColumns as $column) {
            $actualMap[$column['name']] = $column;
        }

        $changes = [];

        /**
         * ตรวจสอบแต่ละ Field
         */
        foreach ($expectedColumns as $columnName => $expected) {

            /**
             * Field ไม่มี
             */
            if (!isset($actualMap[$columnName])) {

                $this->error(
                    "   ❌ {$columnName} : MISSING"
                );

                $changes[$columnName] = [
                    'action' => 'add',
                    'expected' => $expected,
                ];

                continue;
            }

            $actual = $actualMap[$columnName];

            $mismatch = $this->compareColumn(
                $columnName,
                $actual,
                $expected
            );

            if (!empty($mismatch)) {

                $this->warn(
                    "   ⚠️ {$columnName} : MISMATCH"
                );

                foreach ($mismatch as $message) {
                    $this->line("      {$message}");
                }

                $changes[$columnName] = [
                    'action' => 'modify',
                    'expected' => $expected,
                    'actual' => $actual,
                ];

            } else {

                $this->line(
                    "   ✅ {$columnName}"
                );
            }
        }

        /**
         * ตรวจสอบ Extra Column
         */
        foreach ($actualMap as $columnName => $actual) {

            if (!isset($expectedColumns[$columnName])) {

                $this->warn(
                    "   ⚠️ {$columnName} : EXTRA COLUMN"
                );

                $changes[$columnName] = [
                    'action' => 'extra',
                    'actual' => $actual,
                ];
            }
        }

        if (empty($changes)) {
            $this->info("   🎉 Schema ครบถ้วน");
        }

        return $changes;
    }

    /**
     * ============================================================
     * Compare Column
     * ============================================================
     */
    private function compareColumn(
        string $columnName,
        array $actual,
        array $expected
    ): array {

        $mismatch = [];

        /**
         * Type
         */
        if (isset($expected['type'])) {

            $expectedType = strtolower($expected['type']);
            $actualType = strtolower($actual['type_name'] ?? '');

            if (!$this->typeMatches($actualType, $expectedType)) {

                $mismatch[] =
                    "Type: expected {$expectedType}, actual {$actualType}";
            }
        }

        /**
         * Length
         */
        if (
            isset($expected['length']) &&
            isset($actual['length'])
        ) {

            if ((int) $actual['length'] !== (int) $expected['length']) {

                $mismatch[] =
                    "Length: expected {$expected['length']}, actual {$actual['length']}";
            }
        }

        /**
         * Nullable
         */
        if (isset($expected['nullable'])) {

            $actualNullable = (bool) ($actual['nullable'] ?? false);

            if ($actualNullable !== $expected['nullable']) {

                $mismatch[] =
                    'Nullable: expected ' .
                    ($expected['nullable'] ? 'YES' : 'NO') .
                    ', actual ' .
                    ($actualNullable ? 'YES' : 'NO');
            }
        }

        /**
         * Default
         */
        if (array_key_exists('default', $expected)) {

            $actualDefault = $actual['default'] ?? null;

            $expectedDefault = $expected['default'];

            if ((string) $actualDefault !== (string) $expectedDefault) {

                $mismatch[] =
                    "Default: expected {$expectedDefault}, actual " .
                    ($actualDefault ?? 'NULL');
            }
        }

        return $mismatch;
    }

    /**
     * ============================================================
     * Type Matching
     * ============================================================
     */

    private function typeMatches(
        string $actual,
        string $expected
    ): bool {

        $actual = strtolower(trim($actual));
        $expected = strtolower(trim($expected));

        $map = [
            'biginteger' => [
                'bigint',
            ],

            'integer' => [
                'int',
                'integer',
            ],

            'string' => [
                'varchar',
                'char',
            ],

            'text' => [
                'text',
                'mediumtext',
                'longtext',
            ],

            'boolean' => [
                'tinyint',
                'boolean',
                'bool',
            ],

            'timestamp' => [
                'timestamp',
            ],

            'date' => [
                'date',
            ],

            'enum' => [
                'enum',
            ],
        ];

        // ถ้าเป็น type ที่กำหนด mapping ไว้
        if (isset($map[$expected])) {
            return in_array($actual, $map[$expected], true);
        }

        // ถ้าไม่มี mapping ให้เทียบตรง ๆ
        return $actual === $expected;
    }

    /**
     * ============================================================
     * Create Migration
     * ============================================================
     */
    private function createMigration(array $changes): ?string
    {
        $timestamp = date('Y_m_d_His');

        $migrationName = "{$timestamp}_fix_missing_database_schema";

        $fileName = database_path(
            "migrations/{$migrationName}.php"
        );

        $content = "<?php\n\n";

        $content .= "use Illuminate\\Database\\Migrations\\Migration;\n";
        $content .= "use Illuminate\\Database\\Schema\\Blueprint;\n";
        $content .= "use Illuminate\\Support\\Facades\\Schema;\n\n";

        $content .= "return new class extends Migration\n";
        $content .= "{\n";

        /**
         * UP
         */
        $content .= "    public function up(): void\n";
        $content .= "    {\n";

        foreach ($changes as $table => $columns) {

            /**
             * Table ไม่มี
             */
            if (isset($columns['table_missing'])) {

                $content .= $this->generateCreateTable(
                    $table,
                    $columns['columns']
                );

                continue;
            }

            /**
             * Existing table
             */
            $content .= "        Schema::table('{$table}', function (Blueprint \$table) {\n";

            foreach ($columns as $columnName => $change) {

                if ($change['action'] === 'add') {

                    $content .= $this->generateColumn(
                        $columnName,
                        $change['expected'],
                        12
                    );
                }

                /**
                 * Modify
                 */
                elseif ($change['action'] === 'modify') {

                    $content .= $this->generateModifyColumn(
                        $columnName,
                        $change['expected'],
                        12
                    );
                }
            }

            $content .= "        });\n\n";
        }

        $content .= "    }\n\n";

        /**
         * DOWN
         */
        $content .= "    public function down(): void\n";
        $content .= "    {\n";

        foreach ($changes as $table => $columns) {

            /**
             * Table ถูกสร้างใหม่
             */
            if (isset($columns['table_missing'])) {

                $content .= "        Schema::dropIfExists('{$table}');\n";
                continue;
            }

            $addedColumns = [];

            foreach ($columns as $columnName => $change) {

                if ($change['action'] === 'add') {
                    $addedColumns[] = $columnName;
                }
            }

            if (!empty($addedColumns)) {

                $content .= "        Schema::table('{$table}', function (Blueprint \$table) {\n";

                foreach ($addedColumns as $columnName) {

                    $content .= "            \$table->dropColumn('{$columnName}');\n";
                }

                $content .= "        });\n";
            }
        }

        $content .= "    }\n";
        $content .= "};\n";

        /**
         * เขียนไฟล์
         */
        $result = file_put_contents(
            $fileName,
            $content
        );

        if ($result === false) {
            return null;
        }

        return $fileName;
    }

    /**
     * ============================================================
     * Generate Create Table
     * ============================================================
     */
    private function generateCreateTable(
        string $table,
        array $columns
    ): string {

        $output = '';

        $output .= "        Schema::create('{$table}', function (Blueprint \$table) {\n";

        foreach ($columns as $columnName => $definition) {

            $output .= $this->generateColumn(
                $columnName,
                $definition,
                12
            );
        }

        $output .= "        });\n\n";

        return $output;
    }

    /**
     * ============================================================
     * Generate Column
     * ============================================================
     */
    private function generateColumn(
        string $columnName,
        array $definition,
        int $spaces = 12
    ): string {

        $indent = str_repeat(' ', $spaces);

        $type = $definition['type'];

        /**
         * enum
         */
        if ($type === 'enum') {

            $values = array_map(
                fn ($value) => "'" . addslashes($value) . "'",
                $definition['values']
            );

            $code =
                $indent .
                "\$table->enum('{$columnName}', [" .
                implode(', ', $values) .
                "])";
        }

        /**
         * string
         */
        elseif ($type === 'string') {

            $length = $definition['length'] ?? 255;

            $code =
                $indent .
                "\$table->string('{$columnName}', {$length})";
        }

        /**
         * bigInteger
         */
        elseif ($type === 'bigInteger') {

            $code =
                $indent .
                "\$table->bigInteger('{$columnName}')";
        }

        /**
         * Other Laravel types
         */
        else {

            $code =
                $indent .
                "\$table->{$type}('{$columnName}')";
        }

        /**
         * unsigned
         */
        if (!empty($definition['unsigned'])) {
            $code .= "->unsigned()";
        }

        /**
         * nullable
         */
        if (!empty($definition['nullable'])) {
            $code .= "->nullable()";
        }

        /**
         * default
         */
        if (array_key_exists('default', $definition)) {

            $default = $definition['default'];

            if (is_bool($default)) {
                $defaultValue = $default ? 'true' : 'false';
            } elseif (is_null($default)) {
                $defaultValue = 'null';
            } elseif (is_numeric($default)) {
                $defaultValue = $default;
            } else {
                $defaultValue = "'" . addslashes($default) . "'";
            }

            $code .= "->default({$defaultValue})";
        }

        /**
         * auto increment
         */
        if (!empty($definition['autoIncrement'])) {
            $code .= "->autoIncrement()";
        }

        /**
         * primary
         */
        if (!empty($definition['primary'])) {
            $code .= "->primary()";
        }

        /**
         * unique
         */
        if (!empty($definition['unique'])) {
            $code .= "->unique()";
        }

        $code .= ";\n";

        return $code;
    }

    /**
     * ============================================================
     * Generate Modify Column
     * ============================================================
     */
    private function generateModifyColumn(
        string $columnName,
        array $definition,
        int $spaces = 12
    ): string {

        /**
         * หมายเหตุ:
         * การ modify column ต้องใช้ doctrine/dbal ใน Laravel
         * บาง version ของ Laravel สามารถใช้ change() ได้โดยตรง
         */

        $indent = str_repeat(' ', $spaces);

        $type = $definition['type'];

        if ($type === 'string') {

            $length = $definition['length'] ?? 255;

            $code =
                $indent .
                "\$table->string('{$columnName}', {$length})";
        }

        elseif ($type === 'bigInteger') {

            $code =
                $indent .
                "\$table->bigInteger('{$columnName}')";
        }

        elseif ($type === 'text') {

            $code =
                $indent .
                "\$table->text('{$columnName}')";
        }

        elseif ($type === 'boolean') {

            $code =
                $indent .
                "\$table->boolean('{$columnName}')";
        }

        elseif ($type === 'date') {

            $code =
                $indent .
                "\$table->date('{$columnName}')";
        }

        elseif ($type === 'timestamp') {

            $code =
                $indent .
                "\$table->timestamp('{$columnName}')";
        }

        else {

            $code =
                $indent .
                "\$table->{$type}('{$columnName}')";
        }

        if (!empty($definition['unsigned'])) {
            $code .= "->unsigned()";
        }

        if (!empty($definition['nullable'])) {
            $code .= "->nullable()";
        }

        if (array_key_exists('default', $definition)) {

            $default = $definition['default'];

            if (is_bool($default)) {
                $defaultValue = $default ? 'true' : 'false';
            } elseif (is_null($default)) {
                $defaultValue = 'null';
            } elseif (is_numeric($default)) {
                $defaultValue = $default;
            } else {
                $defaultValue = "'" . addslashes($default) . "'";
            }

            $code .= "->default({$defaultValue})";
        }

        $code .= "->change();\n";

        return $code;
    }
}