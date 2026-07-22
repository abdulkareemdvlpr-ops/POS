<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class ProductImportController extends Controller
{
    public function showForm()
    {
        $categories = Category::orderBy('name')->get();
        return view('products.import', compact('categories'));
    }

    public function import(Request $request)
    {
        if ($request->hasFile('import_file')) {
            $request->validate([
                'import_file' => 'required|file|max:10240',
            ]);

            $products = $this->parseImportFile($request->file('import_file'));
            [$created, $updated] = $this->saveProductRows($products);

            return redirect()
                ->route('products.index')
                ->with('success', "Bulk Bill Import complete: {$created} new medicines added, {$updated} existing medicines updated.");
        }

        $request->validate([
            'products' => 'required|array|min:1',
            'products.*.name' => 'required|string|max:255',
            'products.*.generic_name' => 'nullable|string|max:255',
            'products.*.sku' => 'nullable|string|max:100',
            'products.*.barcode' => 'nullable|string|max:100',
            'products.*.category_id' => 'nullable|exists:categories,id',
            'products.*.unit' => 'nullable|string|max:50',
            'products.*.product_type' => 'nullable|in:medicine,general,liquid',
            'products.*.tablets_per_strip' => 'nullable|integer|min:1',
            'products.*.strips_per_box' => 'nullable|integer|min:1',
            'products.*.units_per_box' => 'nullable|integer|min:1',
            'products.*.volume' => 'nullable|string|max:100',
            'products.*.buy_price' => 'required|numeric|min:0',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.stock' => 'required|integer|min:0',
            'products.*.batch_number' => 'nullable|string|max:100',
            'products.*.expiry_date' => 'nullable|date',
        ]);

        [$created, $updated] = $this->saveProductRows($request->products);

        return redirect()
            ->route('products.index')
            ->with('success', "Bulk import complete: {$created} new medicines added, {$updated} existing medicines updated.");
    }

    public function downloadTemplate()
    {
        $headers = [
            'Product Type',
            'Medicine/Product Name',
            'Generic/Formula Name',
            'SKU',
            'Barcode',
            'Batch #',
            'Mfg Date',
            'Expiry Date',
            'Category',
            'Total Boxes Added',
            'Strips Per Box',
            'Tablets Per Strip',
            'Units Per Box',
            'Volume',
            'Box Buy Price',
            'Box MRP',
            'Trade Discount %',
            'Single Unit Buy Price (Rs.)',
            'Single Unit Sale Price (Rs.)',
            'Low Stock Alert',
            'Almari (Cupboard)',
            'Khana (Drawer/Box)',
            'Row (Shelf Row)',
        ];

        $samples = [
            ['medicine', 'Amlocard 5mg Tab', 'Amlodipine', 'AML-005', '8901234500019', 'B-AML09', '2026-01-15', '2028-09-30', 'Tablets', '5', '10', '10', '1', '', '2800.00', '3280.00', '14.6', '', '', '50', 'Almari 3', 'Khana 2', 'Row 1'],
            ['liquid', 'Panadol Syrup 120ml', 'Paracetamol', 'PAN-SYR', '8901234500026', 'B-PAN12', '2026-02-01', '2027-12-31', 'Syrups', '10', '1', '1', '12', '120ml', '', '1800.00', '15.00', '', '', '20', 'Almari 1', 'Khana 1', 'Row 2'],
            ['general', 'Baby Wipes (80 pcs)', '', 'WIPES-80', '8901234500033', '', '', '', 'Baby Care', '10', '1', '1', '1', '', '', '', '', '150.00', '220.00', '10', 'Almari 5', 'Khana 1', 'Row 3'],
        ];

        return response()->streamDownload(function () use ($headers, $samples) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['# INSTRUCTIONS: Product Type must be "medicine", "liquid", or "general". Total Boxes Added is ADDED to existing stock (upsert), not overwritten.']);
            fputcsv($file, ['# For Box-First Pricing: Enter Box Buy Price, Box MRP, and (optional) Trade Discount %. System will calculate single unit price.']);
            fputcsv($file, ['# For "medicine": enter Strips Per Box & Tablets Per Strip. For "liquid": enter Units Per Box & Volume.']);
            fputcsv($file, ['# For "general": enter Single Unit Buy Price and Single Unit Sale Price directly.']);
            fputcsv($file, $headers);
            foreach ($samples as $sample) {
                fputcsv($file, $sample);
            }
            fclose($file);
        }, 'bulk_medicine_import_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function saveProductRows(array $rows): array
    {
        $created = 0;
        $updated = 0;
        $normalizedRows = [];

        foreach ($rows as $index => $row) {
            if (!$this->rowHasAnyValue($row)) {
                continue;
            }

            $normalizedRows[] = $this->normalizeProductRow($row, $index + 1);
        }

        if (empty($normalizedRows)) {
            throw ValidationException::withMessages([
                'import_file' => 'No medicine rows found to import.',
            ]);
        }

        DB::beginTransaction();
        try {
            foreach ($normalizedRows as $attrs) {
                $stockDelta = $attrs['stock_delta'];
                unset($attrs['stock_delta']);

                $existing = null;

                if ($attrs['sku']) {
                    $existing = Product::where('sku', $attrs['sku'])->first();
                }

                if (!$existing && $attrs['barcode']) {
                    $existing = Product::where('barcode', $attrs['barcode'])->first();
                }

                if (!$existing) {
                    $existing = Product::whereRaw('LOWER(name) = ?', [strtolower($attrs['name'])])->first();
                }

                if ($existing) {
                    // Upsert: ADD the imported quantity to whatever stock the product already
                    // has, instead of overwriting it — re-importing the same sheet (e.g. after a
                    // new delivery) tops up stock rather than wiping it out.
                    $attrs['stock'] = max(0, $existing->stock + $stockDelta);
                    $existing->update($attrs);
                    $updated++;
                } else {
                    $attrs['stock'] = max(0, $stockDelta);
                    Product::create($attrs);
                    $created++;
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'import_file' => 'Import failed: ' . $e->getMessage(),
            ]);
        }

        return [$created, $updated];
    }

    private function normalizeProductRow(array $row, int $rowNumber): array
    {
        $row = $this->normalizeRowKeys($row);

        $name = trim((string) $this->rowValue($row, ['name', 'medicine_name', 'product_name', 'item_name', 'medicine', 'medicine_product_name']));
        if ($name === '') {
            throw ValidationException::withMessages([
                'import_file' => "Row {$rowNumber}: medicine/product name is required.",
            ]);
        }

        $categoryId = $this->resolveCategoryId($row);
        $category = Category::findOrFail($categoryId);
        $productType = $category->product_type;

        $tabletsPerStrip = (int) round($this->decimalValue($this->rowValue($row, ['tablets_per_strip', 'tablets_strip', 'units_per_strip'], 1)));
        $stripsPerBox    = (int) round($this->decimalValue($this->rowValue($row, ['strips_per_box', 'strips_box'], 1)));
        $unitsPerBox     = (int) round($this->decimalValue($this->rowValue($row, ['units_per_box', 'units_box'], 1)));
        $volume          = $this->cleanText($this->rowValue($row, ['volume', 'vol', 'size']));

        $tabletsPerStrip = $tabletsPerStrip > 0 ? $tabletsPerStrip : 1;
        $stripsPerBox    = $stripsPerBox > 0 ? $stripsPerBox : 1;
        $unitsPerBox     = $unitsPerBox > 0 ? $unitsPerBox : 1;

        if ($productType === 'general') {
            $tabletsPerStrip = 1;
            $stripsPerBox = 1;
            $unitsPerBox = 1;
            $volume = null;
        } elseif ($productType === 'liquid') {
            $tabletsPerStrip = 1;
            $stripsPerBox = 1;
        } else {
            $unitsPerBox = 1;
            $volume = null;
        }

        // Get box-level pricing fields
        $boxBuyPrice = $this->decimalValue($this->rowValue($row, ['box_buy_price', 'box_cost', 'box_buy_price_rs', 'box_cost_price'], 0));
        $boxMrp      = $this->decimalValue($this->rowValue($row, ['box_mrp', 'box_retail_price', 'box_sale_price', 'box_retail_mrp', 'mrp'], 0));
        $tradeDiscount = $this->decimalValue($this->rowValue($row, ['trade_discount', 'trade_discount_percent', 'trade_discount_percentage', 'discount_percent', 'discount'], 0));

        // Rule C: Apply discount if boxBuyPrice is empty but boxMrp and tradeDiscount exist
        if ($boxBuyPrice === 0.0 && $boxMrp > 0 && $tradeDiscount > 0) {
            $boxBuyPrice = $boxMrp * (1 - $tradeDiscount / 100);
        }

        // Initialize single unit buy/sale price fallbacks
        $singleBuy = $this->decimalValue($this->rowValue($row, ['single_unit_buy_price_rs', 'single_unit_buy_price', 'buy_price', 'cost', 'cost_buy', 'purchase_price', 'trade_price'], 0));
        $singleSale = $this->decimalValue($this->rowValue($row, ['single_unit_sale_price_rs', 'single_unit_sale_price', 'price', 'sale_price', 'retail_price', 'retail_sale'], 0));

        // Perform calculation per rules
        if ($boxMrp > 0 || $boxBuyPrice > 0) {
            if ($productType === 'medicine') {
                $totalUnits = $stripsPerBox * $tabletsPerStrip;
                if ($boxMrp > 0) $singleSale = $boxMrp / $totalUnits;
                if ($boxBuyPrice > 0) $singleBuy = $boxBuyPrice / $totalUnits;
            } elseif ($productType === 'liquid') {
                $totalUnits = $unitsPerBox;
                if ($boxMrp > 0) $singleSale = $boxMrp / $totalUnits;
                if ($boxBuyPrice > 0) $singleBuy = $boxBuyPrice / $totalUnits;
            } else { // general
                if ($boxMrp > 0) $singleSale = $boxMrp;
                if ($boxBuyPrice > 0) $singleBuy = $boxBuyPrice;
            }
        }

        // Determine stock multiplier per box
        $totalUnitsPerBox = 1;
        if ($productType === 'medicine') {
            $totalUnitsPerBox = $stripsPerBox * $tabletsPerStrip;
        } elseif ($productType === 'liquid') {
            $totalUnitsPerBox = $unitsPerBox;
        }

        // Calculate Stock Delta
        $boxesAdded = $this->rowValue($row, ['total_boxes_added', 'boxes_added', 'total_boxes'], null);
        if ($boxesAdded !== null && trim((string) $boxesAdded) !== '') {
            $stockDelta = max(0, (int) round($this->decimalValue($boxesAdded) * $totalUnitsPerBox));
        } else {
            // If they provided single unit stock (loose items)
            $stockDelta = max(0, (int) round($this->decimalValue($this->rowValue($row, ['stock', 'qty', 'quantity', 'pcs', 'pack_qty']))));
        }

        return [
            'name' => $name,
            'generic_name' => $this->cleanText($this->rowValue($row, ['generic_name', 'generic', 'generic_formula', 'formula', 'generic_formula_name'])),
            'sku' => $this->cleanText($this->rowValue($row, ['sku', 'item_code', 'code'])) ?: null,
            'barcode' => $this->cleanText($this->rowValue($row, ['barcode', 'bar_code'])) ?: null,
            'category_id' => $categoryId,
            'product_type' => $productType,
            'tablets_per_strip' => $tabletsPerStrip,
            'strips_per_box' => $stripsPerBox,
            'units_per_box' => $unitsPerBox,
            'volume' => $volume ?: null,
            'price' => $singleSale,
            'buy_price' => $singleBuy,
            'stock_delta' => $stockDelta,
            'unit' => $this->cleanText($this->rowValue($row, ['unit', 'uom', 'packing'])) ?: 'pcs',
            'low_stock_threshold' => (int) round($this->decimalValue($this->rowValue($row, ['low_stock_alert', 'low_stock_threshold', 'low_stock'], 5))) ?: 5,
            'batch_number' => $this->cleanText($this->rowValue($row, ['batch_number', 'batch', 'batch_no', 'batch_num'])) ?: null,
            'mfg_date' => $this->dateValue($this->rowValue($row, ['mfg_date', 'mfg', 'manufacture_date', 'manufacturing_date'])) ?: ($category->default_mfg_date ?? null),
            'expiry_date' => $this->dateValue($this->rowValue($row, ['expiry_date', 'expiry', 'exp_date', 'exp'])) ?: ($category->default_expiry_date ?? null),
            'almari' => $this->cleanText($this->rowValue($row, ['almari_cupboard', 'almari'])) ?: ($category->default_almari ?? null),
            'khana' => $this->cleanText($this->rowValue($row, ['khana_drawer_box', 'khana'])) ?: ($category->default_khana ?? null),
            'row' => $this->cleanText($this->rowValue($row, ['row_shelf_row', 'row'])) ?: ($category->default_row ?? null),
            'status' => 1,
        ];
    }

    private function parseImportFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'csv', 'txt' => $this->parseDelimitedFile($file->getRealPath()),
            'xlsx' => $this->parseXlsxFile($file->getRealPath()),
            default => throw ValidationException::withMessages([
                'import_file' => 'Please upload a CSV file or Excel .xlsx file.',
            ]),
        };
    }

    private function parseDelimitedFile(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            throw ValidationException::withMessages(['import_file' => 'The uploaded file is empty.']);
        }

        // Skip leading "# instructions" / comment lines (e.g. from our own downloadable template).
        $lines = array_values(array_filter($lines, fn ($line) => !str_starts_with(ltrim(preg_replace('/^\xEF\xBB\xBF/', '', $line)), '#')));
        if (!$lines) {
            throw ValidationException::withMessages(['import_file' => 'The uploaded file is empty.']);
        }

        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $lines[0]);
        $delimiter = $this->detectDelimiter($firstLine);
        $tableRows = [];

        foreach ($lines as $line) {
            $tableRows[] = str_getcsv($line, $delimiter);
        }

        return $this->tableRowsToAssociative($tableRows);
    }

    private function parseXlsxFile(string $path): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw ValidationException::withMessages([
                'import_file' => 'Excel import needs PHP ZipArchive. Please save the sheet as CSV and upload again.',
            ]);
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['import_file' => 'Could not read the Excel file.']);
        }

        $sharedStrings = $this->readXlsxSharedStrings($zip);
        $sheetPath = $this->bestWorksheetPath($zip, $sharedStrings);
        $sheetXml = $sheetPath ? $zip->getFromName($sheetPath) : false;
        $zip->close();

        if (!$sheetXml) {
            throw ValidationException::withMessages(['import_file' => 'No worksheet found in the Excel file.']);
        }

        $xml = simplexml_load_string($sheetXml);
        if (!$xml || !isset($xml->sheetData->row)) {
            throw ValidationException::withMessages(['import_file' => 'Could not parse the first Excel worksheet.']);
        }

        $tableRows = [];
        foreach ($xml->sheetData->row as $row) {
            $cells = [];
            $maxIndex = -1;

            foreach ($row->c as $cell) {
                $index = $this->xlsxColumnIndex((string) $cell['r']);
                $cells[$index] = $this->xlsxCellValue($cell, $sharedStrings);
                $maxIndex = max($maxIndex, $index);
            }

            if ($maxIndex >= 0) {
                $values = [];
                for ($i = 0; $i <= $maxIndex; $i++) {
                    $values[] = $cells[$i] ?? '';
                }
                $tableRows[] = $values;
            }
        }

        return $this->tableRowsToAssociative($tableRows);
    }

    private function readXlsxSharedStrings(ZipArchive $zip): array
    {
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if (!$sharedXml) {
            return [];
        }

        $xml = simplexml_load_string($sharedXml);
        if (!$xml) {
            return [];
        }

        $strings = [];
        foreach ($xml->si as $item) {
            if (isset($item->t)) {
                $strings[] = (string) $item->t;
                continue;
            }

            $text = '';
            foreach ($item->r as $run) {
                $text .= (string) $run->t;
            }
            $strings[] = $text;
        }

        return $strings;
    }

    /**
     * Templates like ours may ship an "Instructions" sheet ahead of the real data sheet.
     * Instead of blindly grabbing the first worksheet (which silently imported the
     * instructions text as if it were product rows), score every worksheet's header
     * row against known product columns and pick the best match.
     */
    private function bestWorksheetPath(ZipArchive $zip, array $sharedStrings): ?string
    {
        $candidates = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
                $candidates[] = $name;
            }
        }

        if (empty($candidates)) {
            return null;
        }

        sort($candidates);

        $knownHeaders = [
            'name', 'medicine_name', 'product_name', 'item_name', 'medicine', 'medicine_product_name',
            'sku', 'barcode', 'category', 'category_name',
            'buy_price', 'price', 'sale_price', 'single_unit_buy_price_rs', 'single_unit_sale_price_rs',
            'stock', 'qty', 'quantity', 'total_boxes_added',
            'batch_number', 'batch', 'expiry_date', 'expiry', 'product_type',
        ];

        $bestPath = $candidates[0];
        $bestScore = -1;

        foreach ($candidates as $path) {
            $xml = simplexml_load_string((string) $zip->getFromName($path));
            if (!$xml || !isset($xml->sheetData->row[0])) {
                continue;
            }

            $headerRow = $xml->sheetData->row[0];
            $headers = [];
            foreach ($headerRow->c as $cell) {
                $index = $this->xlsxColumnIndex((string) $cell['r']);
                $headers[$index] = $this->normalizeHeader($this->xlsxCellValue($cell, $sharedStrings));
            }

            $score = count(array_intersect($headers, $knownHeaders));
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestPath = $path;
            }
        }

        return $bestPath;
    }

    private function xlsxColumnIndex(string $cellReference): int
    {
        preg_match('/[A-Z]+/i', $cellReference, $matches);
        $letters = strtoupper($matches[0] ?? 'A');
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function xlsxCellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];

        if ($type === 'inlineStr') {
            return (string) ($cell->is->t ?? '');
        }

        $value = (string) ($cell->v ?? '');
        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        return $value;
    }

    private function tableRowsToAssociative(array $tableRows): array
    {
        $tableRows = array_values(array_filter($tableRows, fn ($row) => $this->rowHasAnyValue($row)));
        if (count($tableRows) < 2) {
            throw ValidationException::withMessages([
                'import_file' => 'The file must include a header row and at least one medicine row.',
            ]);
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), array_shift($tableRows));
        $rows = [];

        foreach ($tableRows as $tableRow) {
            $row = [];
            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $row[$header] = $tableRow[$index] ?? null;
                }
            }
            if ($this->rowHasAnyValue($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function detectDelimiter(string $line): string
    {
        $delimiters = [',' => substr_count($line, ','), ';' => substr_count($line, ';'), "\t" => substr_count($line, "\t")];
        arsort($delimiters);
        return array_key_first($delimiters) ?: ',';
    }

    private function normalizeRowKeys(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[$this->normalizeHeader((string) $key)] = $value;
        }
        return $normalized;
    }

    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);
        return trim($header, '_');
    }

    private function rowValue(array $row, array $aliases, mixed $default = ''): mixed
    {
        foreach ($aliases as $alias) {
            $key = $this->normalizeHeader($alias);
            if (array_key_exists($key, $row) && trim((string) $row[$key]) !== '') {
                return $row[$key];
            }
        }

        return $default;
    }

    private function resolveCategoryId(array $row): int
    {
        $categoryId = $this->rowValue($row, ['category_id'], null);
        if ($categoryId && Category::whereKey($categoryId)->exists()) {
            return (int) $categoryId;
        }

        $categoryName = $this->cleanText($this->rowValue($row, ['category', 'category_name', 'type']));
        if ($categoryName === '') {
            $categoryName = 'Uncategorized';
        }

        // Determine the category's product type for auto-creation
        $type = strtolower($this->cleanText($this->rowValue($row, ['product_type'], '')));
        if ($type === '' || !in_array($type, ['medicine', 'liquid', 'general'])) {
            $strips = (int) round($this->decimalValue($this->rowValue($row, ['strips_per_box', 'strips_box'], 0)));
            $tablets = (int) round($this->decimalValue($this->rowValue($row, ['tablets_per_strip', 'tablets_strip'], 0)));
            $units = (int) round($this->decimalValue($this->rowValue($row, ['units_per_box', 'units_box'], 0)));
            $volume = $this->cleanText($this->rowValue($row, ['volume', 'vol', 'size']));

            if ($strips > 1 || $tablets > 1) {
                $type = 'medicine';
            } elseif ($units > 1 || $volume !== '') {
                $type = 'liquid';
            } else {
                $nameStr = strtolower($this->cleanText($this->rowValue($row, ['name', 'medicine_name', 'product_name'])));
                $catStr = strtolower($categoryName);
                if (str_contains($nameStr, 'syrup') || str_contains($nameStr, 'syp') || str_contains($catStr, 'syrup') || str_contains($catStr, 'liquid') || str_contains($catStr, 'syp')) {
                    $type = 'liquid';
                } elseif (str_contains($nameStr, 'tab') || str_contains($nameStr, 'cap') || str_contains($catStr, 'tablet') || str_contains($catStr, 'capsule')) {
                    $type = 'medicine';
                } else {
                    $type = 'medicine'; // default
                }
            }
        }

        return Category::firstOrCreate(
            ['name' => $categoryName],
            ['product_type' => $type, 'description' => 'Auto-created for bulk imports.', 'status' => 1]
        )->id;
    }

    private function cleanText(mixed $value): string
    {
        return trim((string) $value);
    }

    private function decimalValue(mixed $value): float
    {
        $value = preg_replace('/[^0-9.\-]/', '', (string) $value);
        return $value === '' || $value === '-' ? 0.0 : (float) $value;
    }

    private function dateValue(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value) && (float) $value > 25569) {
            return Carbon::create(1899, 12, 30)->addDays((int) $value)->format('Y-m-d');
        }

        $formats = ['Y-m-d', 'd-m-Y', 'd/m/Y', 'm/d/Y', 'd M Y', 'M d Y'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Throwable) {
                // Try the next known format.
            }
        }

        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    private function rowHasAnyValue(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }
}