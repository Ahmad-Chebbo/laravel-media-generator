<?php

namespace AhmadChebbo\LaravelMediaGenerator\Commands;

use AhmadChebbo\LaravelMediaGenerator\Services\ImageSources\DicebearImageSource;
use AhmadChebbo\LaravelMediaGenerator\Services\MediaGeneratorService;
use AhmadChebbo\LaravelMediaGenerator\Services\ModelGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class GenerateMediaCommand extends Command
{
    protected $signature = 'media:generate
                            {--model= : Fully qualified model class name}
                            {--count= : Number of images to generate}
                            {--source= : Image source (picsum, placeholder, unsplash, avatar, dicebear)}
                            {--dicebear-style= : DiceBear avatar style (only used when source=dicebear)}
                            {--collection= : Media collection name}
                            {--concurrent=10 : Number of concurrent downloads}
                            {--batch-size= : Batch size for processing}
                            {--record-id= : ID of existing record to attach media to (optional)}
                            {--create-record : Create a new record if no record ID is provided (optional)}';

    protected $description = 'Generate media files and attach them to Eloquent models';

    public function handle(MediaGeneratorService $service, ModelGeneratorService $modelGeneratorService): int
    {
        $this->info('🚀 Laravel Media Generator');
        $this->info('==========================');
        $this->newLine();

        // Get options
        $source = $this->option('source') ?: $this->askForSource();
        $dicebearStyle = null;
        if ($source === 'dicebear') {
            $dicebearStyle = $this->option('dicebear-style') ?: $this->askForDicebearStyle();
        }
        $count = (int) ($this->option('count') ?: $this->askForCount());
        $batchSize = $this->option('batch-size') ?: $this->askForBatchSize();
        $modelClass = $this->option('model') ?: $this->askForModel();
        $collection = $this->option('collection') ?: $this->askForCollection();
        $concurrent = (int) $this->option('concurrent');
        $recordId = $this->option('record-id') ? (int) $this->option('record-id') : $this->askForRecordId($modelClass);
        $createRecord = $this->option('create-record');

        // Validate model
        $modelClass = $this->resolveModelClass($modelClass);
        if ($modelClass === null) {
            $this->error("Model class '{$modelClass}' does not exist!");

            return 1;
        }

        $modelInstance = null;
        $modelGeneratorService->setCommand($this);

        // Validate record ID if provided
        if ($recordId !== null) {
            $modelInstance = $modelGeneratorService->validateRecordExists($modelClass, $recordId);
        }

        // If no record ID provided and create-record is not set, show error
        if ($recordId === null && ! $createRecord) {
            $this->error('No record ID provided and create-record option is not set. Please provide a record ID or use the create-record option.');

            return 1;
        }

        // If we need to create a new record (either createRecord is true or no recordId provided)
        if ($createRecord || $recordId === null) {
            $modelGeneratorService->validateModelUsesMediaLibrary($modelClass);
            $modelFields = $modelGeneratorService->getModelFields($modelClass);
            $modelInstance = $modelGeneratorService->generateModelRecord($modelClass, $modelFields, true);
        }

        // Set up image source and command instance
        try {
            $service->setImageSource($source);
            if ($source === 'dicebear' && $dicebearStyle !== null) {
                $service->setDicebearStyle($dicebearStyle);
            }
            $service->setCommand($this);
        } catch (\Exception $e) {
            $this->error('Error setting image source: '.$e->getMessage());

            return 1;
        }

        // Show configuration
        $this->displayConfiguration($modelClass, $count, $source, $collection, $concurrent, $batchSize, $recordId);

        if (! $this->confirm('🚀 Start generating media?', true)) {
            $this->warn('Media generation cancelled.');

            return 0;
        }

        // new line
        $this->newLine();
        $this->info('🔍 Validating system requirements...');
        $this->newLine();

        $validation = $service->validateSystemRequirements($count, [
            'batch_size' => $batchSize,
            'concurrent' => $concurrent,
        ]);

        if (! $validation['valid']) {
            $this->error('System requirements not met. Please check your configuration.');
            $this->newLine();
            $service->displayValidationResults($validation);
            $this->newLine();
            $this->warn('Media generation cancelled.');

            return 1;
        }

        // Set up progress bar
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->setFormat('🔄 Progress: %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
        $progressBar->start();

        // Generate media
        try {
            $results = $service->generateMedia([
                'count' => $count,
                'model_class' => $modelClass,
                'collection' => $collection,
                'batch_size' => $batchSize,
                'record_id' => $recordId,
                'model_instance' => $modelInstance,
                'progress_callback' => function ($current, $total) use ($progressBar) {
                    $progressBar->setProgress($current);
                },
            ]);

            $progressBar->finish();
            $this->newLine(2);

            $this->displayResults($results);

            return $results['errors'] > 0 ? 1 : 0;
        } catch (\Exception $e) {
            $progressBar->finish();
            $this->newLine();
            $this->error('Error during generation: '.$e->getMessage());

            return 1;
        }
    }

    private function askForBatchSize(): int
    {
        $this->info('📊 Batch Size');
        $this->newLine();

        return (int) $this->ask('Enter the batch size for processing:', 50);
    }

    private function askForModel(): string
    {
        // Get all models from the app and ask the user to select one
        $modelFiles = glob(app_path('Models/*.php'));

        if (empty($modelFiles)) {
            $this->error('No models found in app/Models directory!');
            $this->line('Please create at least one model before running this command.');
            exit(1);
        }

        $models = [];
        $this->info('📋 Available Models:');
        $this->newLine();

        foreach ($modelFiles as $index => $modelFile) {
            $modelName = basename($modelFile, '.php');
            $number = $index + 1;
            $models[$number] = $modelName;

            // Show model info
            $this->line("  {$number}. {$modelName}");
        }

        $this->newLine();
        $selectedModel = $this->choice('Select a model to generate media for:', $models, array_keys($models)[0]);

        $this->info("✅ Selected model: {$selectedModel}");
        $this->newLine();

        return $selectedModel;
    }

    private function askForCount(): int
    {
        $this->info('📊 Media Generation Quantity');
        $this->newLine();

        $choice = $this->choice('Select the number of media files to generate:', [
            '5' => '🖼️  Small batch (5 images) - Quick test',
            '10' => '🖼️  Small (10 images) - Development',
            '25' => '🖼️  Medium (25 images) - Testing',
            '50' => '🖼️  Large (50 images) - Production',
            '100' => '🖼️  Extra Large (100 images) - Bulk generation',
            'custom' => '⌨️  Custom amount',
        ], '25');

        if ($choice === 'custom') {
            $this->newLine();
            $this->info('⌨️ Custom Amount');
            $this->line('Enter the number of media files you want to generate:');
            $count = (int) $this->ask('Number of images (1-1000)', 25);

            // Validate input
            if ($count < 1) {
                $this->warn('Minimum count is 1. Setting to 1.');
                $count = 1;
            } elseif ($count > 1000) {
                $this->warn('Maximum count is 1000. Setting to 1000.');
                $count = 1000;
            }

            $this->info("✅ Set to generate {$count} media files");
            $this->newLine();

            return $count;
        }

        $count = (int) $choice;
        $this->info("✅ Selected: {$count} media files");
        $this->newLine();

        return $count;
    }

    private function askForSource(): string
    {
        $this->info('🖼️ Image Source Selection');
        $this->newLine();

        $sources = [
            'dicebear' => '🎲 DiceBear - Unique deterministic avatar images (Recommended for avatars)',
            'picsum' => '🖼️ Picsum - Lorem Picsum for placeholder images (Fast)',
            'unsplash' => '🌅 Unsplash - High-quality photos from photographers worldwide',
            'placeholder' => '📐 Placeholder.com - Simple placeholder images (Basic)',
            // 'avatar' => '👤 Avatar - Random avatar images (Fast)',
        ];

        $source = $this->choice('Select image source for media generation:', $sources, 'picsum');

        $this->info('✅ Selected source: '.explode(' - ', $sources[$source])[0]);
        $this->newLine();

        return $source;
    }

    private function askForDicebearStyle(): string
    {
        $this->info('🎨 DiceBear Style Selection');
        $this->newLine();
        $this->line('Choose an avatar style. Popular options:');
        $this->line('  bottts, pixel-art, lorelei, adventurer, avataaars, micah, notionists, open-peeps');
        $this->newLine();

        $popularStyles = [
            'bottts' => '🤖 Bottts - Cute robot avatars (Default)',
            'pixel-art' => '🎮 Pixel Art - Retro pixel-style characters',
            'lorelei' => '🧑 Lorelei - Illustrated person avatars',
            'adventurer' => '⚔️  Adventurer - Fantasy character avatars',
            'avataaars' => '😊 Avataaars - Cartoon face builder avatars',
            'micah' => '🎨 Micah - Illustrated face avatars',
            'notionists' => '📝 Notionists - Notion-style doodle avatars',
            'open-peeps' => '🧍 Open Peeps - Hand-drawn people avatars',
            'identicon' => '🔷 Identicon - Abstract geometric patterns',
            'thumbs' => '👍 Thumbs - Cute thumb characters',
            'fun-emoji' => '😄 Fun Emoji - Playful emoji-style avatars',
            'shapes' => '🔶 Shapes - Abstract shape avatars',
            'custom' => '⌨️  Other - Enter a custom style name',
        ];

        $choice = $this->choice(
            'Select a DiceBear style:',
            $popularStyles,
            'bottts'
        );

        if ($choice === 'custom') {
            $this->newLine();
            $this->line('Available styles: '.implode(', ', DicebearImageSource::AVAILABLE_STYLES));
            $this->newLine();
            $style = $this->ask('Enter the DiceBear style name', DicebearImageSource::DEFAULT_STYLE);

            if (! in_array($style, DicebearImageSource::AVAILABLE_STYLES, true)) {
                $this->warn("Style '{$style}' is not in the known styles list. Falling back to default: ".DicebearImageSource::DEFAULT_STYLE);
                $style = DicebearImageSource::DEFAULT_STYLE;
            }
        } else {
            $style = $choice;
        }

        $this->info("✅ Selected DiceBear style: {$style}");
        $this->newLine();

        return $style;
    }

    private function askForCollection(): string
    {
        $this->info('🖼️ Collection Selection');
        $this->newLine();
        $collection = $this->ask('Enter the collection name for media generation:', 'default');

        $this->info("✅ Selected collection: {$collection}");
        $this->newLine();

        return $collection;
    }

    private function displayConfiguration(string $model, int $count, string $source, string $collection, int $concurrent, int $batchSize, ?int $recordId): void
    {
        $this->info('⚙️  Final Configuration Summary');
        $this->info('===============================');
        $this->newLine();

        $this->line("📋 Model: <info>{$model}</info>");
        $this->line("🖼️  Images: <info>{$count}</info>");
        $this->line("🌅 Source: <info>{$source}</info>");
        $this->line("📁 Collection: <info>{$collection}</info>");
        $this->line("⚡ Concurrent: <info>{$concurrent}</info>");
        $this->line("📦 Batch size: <info>{$batchSize}</info>");

        if ($recordId !== null) {
            $this->line('📄 Mode: <info>Use existing record</info>');
            $this->line("🆔 Record ID: <info>{$recordId}</info>");
        } else {
            $this->line('📄 Mode: <info>Create new record</info>');
        }

        $this->newLine();
        $this->info('📝 Model Fields:');
        $fields = $this->getModelFields($model);
        if (! empty($fields)) {
            foreach ($fields as $field) {
                $this->line("  • {$field}");
            }
        } else {
            $this->line('  • No fillable fields found');
        }
        $this->newLine();
    }

    private function displayResults(array $results): void
    {
        $this->info('🎉 Generation Complete!');
        $this->info('=======================');
        $this->line("✅ Successfully generated: {$results['success']} media files");
        $this->line("❌ Errors: {$results['errors']}");
        $this->line('📈 Success rate: '.round(($results['success'] / $results['total']) * 100, 1).'%');
        $this->line('⏱️  Total time: '.round($results['duration'], 2).' seconds');
        $this->line('⚡ Average time per file: '.round($results['duration'] / $results['total'], 3).' seconds');
    }

    private function getModelFields(string $modelClass): array
    {
        // Get all fields from the model
        $model = new $modelClass;
        $fields = $model->getFillable();

        return $fields;
    }

    private function getRecordDisplayName($record): string
    {
        // Try to get a meaningful display name for the record
        $displayMethods = ['getName', 'getDisplayName', 'getTitle'];

        foreach ($displayMethods as $method) {
            if (method_exists($record, $method)) {
                return $record->$method();
            }
        }

        $displayFields = ['name', 'title', 'email', 'label', 'description'];

        foreach ($displayFields as $field) {
            if (isset($record->$field) && ! empty($record->$field)) {
                return $record->$field;
            }
        }

        return "ID: {$record->id}";
    }

    private function askForRecordId(string $modelClass): ?int
    {
        $modelClass = $this->resolveModelClass($modelClass);
        $model = new $modelClass;

        // Get total count for better UX
        $totalRecords = $model->count();

        if ($totalRecords === 0) {
            $this->warn("No existing records found in {$modelClass}. Will create new records.");
            $this->newLine();

            return $this->handleCreateNewRecord($modelClass);
        }

        $this->info("📊 Found {$totalRecords} existing records");
        $this->newLine();

        // If we have many records, offer different options
        if ($totalRecords > 50) {
            return $this->handleManyRecords($model, $totalRecords, $modelClass);
        }

        // For smaller datasets, show all records
        return $this->handleFewRecords($model, $modelClass);
    }

    private function handleManyRecords($model, int $totalRecords, string $modelClass): ?int
    {
        $this->info('🔍 Many records found! Choose how to proceed:');
        $this->newLine();

        $options = [
            'search' => '🔍 Search for specific records',
            'recent' => '📅 Show recent records (last 20)',
            'create_new' => '➕ Create new records',
            'manual_id' => '⌨️  Enter record ID manually',
        ];

        $choice = $this->choice('Select option:', $options, 'recent');

        switch ($choice) {
            case 'search':
                return $this->searchRecords($model, $modelClass);
            case 'recent':
                return $this->showRecentRecords($model, $modelClass);
            case 'manual_id':
                return $this->enterRecordIdManually($model, $modelClass);
            case 'create_new':
            default:
                return $this->handleCreateNewRecord($modelClass);
        }
    }

    private function handleFewRecords($model, string $modelClass): ?int
    {
        $records = $model->latest()->take(20)->get();

        $choices = ['create_new' => '➕ Create new records'];
        $recordChoices = [];

        $this->info('📋 Available Records:');
        $this->newLine();

        foreach ($records as $record) {
            $displayName = $this->getRecordDisplayName($record);
            $choices[$record->id] = "📄 {$displayName} (ID: {$record->id})";
            $recordChoices[$record->id] = $record->id;
        }

        $choice = $this->choice('Select record to attach media to:', $choices, 'create_new');

        if ($choice === 'create_new') {
            return $this->handleCreateNewRecord($modelClass);
        }

        $this->info("✅ Selected record ID: {$choice}");
        $this->newLine();

        return $recordChoices[$choice];
    }

    private function searchRecords($model, string $modelClass): ?int
    {
        $this->info('🔍 Search Records');
        $this->line('Enter search term to find records:');

        $searchTerm = $this->ask('Search term (leave empty to cancel)');

        if (empty($searchTerm)) {
            return $this->askForRecordId($modelClass);
        }

        // Try to search in common fields
        $searchableFields = ['name', 'title', 'email', 'description', 'label'];
        $query = $model->newQuery();

        foreach ($searchableFields as $field) {
            if (Schema::hasColumn($model->getTable(), $field)) {
                $query->orWhere($field, 'LIKE', "%{$searchTerm}%");
            }
        }

        $results = $query->take(20)->get();

        if ($results->isEmpty()) {
            $this->warn("No records found matching '{$searchTerm}'");
            $this->newLine();

            return $this->askForRecordId($modelClass);
        }

        $this->info("Found {$results->count()} matching records:");
        $this->newLine();

        $choices = ['back' => '⬅️ Back to main menu'];
        $recordChoices = [];

        foreach ($results as $record) {
            $displayName = $this->getRecordDisplayName($record);
            $choices[$record->id] = "📄 {$displayName} (ID: {$record->id})";
            $recordChoices[$record->id] = $record->id;
        }

        $choice = $this->choice('Select a record:', $choices, 'back');

        if ($choice === 'back') {
            return $this->askForRecordId($modelClass);
        }

        $this->info("✅ Selected record ID: {$choice}");
        $this->newLine();

        return $recordChoices[$choice];
    }

    private function showRecentRecords($model, string $modelClass): ?int
    {
        $records = $model->latest()->take(20)->get();

        $this->info('📅 Recent Records:');
        $this->newLine();

        $choices = ['back' => '⬅️ Back to main menu', 'create_new' => '➕ Create new records'];
        $recordChoices = [];

        foreach ($records as $record) {
            $displayName = $this->getRecordDisplayName($record);
            $createdAt = $record->created_at ? $record->created_at->format('Y-m-d H:i') : 'N/A';
            $choices[$record->id] = "📄 {$displayName} (ID: {$record->id}) - Created: {$createdAt}";
            $recordChoices[$record->id] = $record->id;
        }

        $choice = $this->choice('Select a record:', $choices, 'back');

        if ($choice === 'back') {
            return $this->askForRecordId($modelClass);
        }

        if ($choice === 'create_new') {
            return $this->handleCreateNewRecord($modelClass);
        }

        $this->info("✅ Selected record ID: {$choice}");
        $this->newLine();

        return $recordChoices[$choice];
    }

    private function enterRecordIdManually($model, string $modelClass): ?int
    {
        $this->info('⌨️ Manual Record ID Entry');
        $this->line('Enter the ID of the record you want to use:');

        $recordId = (int) $this->ask('Record ID (0 to cancel)');

        if ($recordId === 0) {
            return $this->askForRecordId($modelClass);
        }

        // Validate the record exists
        $record = $model->find($recordId);

        if (! $record) {
            $this->error("Record with ID {$recordId} not found!");
            $this->newLine();

            return $this->enterRecordIdManually($model, $modelClass);
        }

        $displayName = $this->getRecordDisplayName($record);
        $this->info("✅ Found record: {$displayName} (ID: {$recordId})");
        $this->newLine();

        return $recordId;
    }

    private function handleCreateNewRecord(string $modelClass): ?int
    {
        $this->info('➕ Create New Record');
        $this->line('Model fields: '.json_encode($this->getModelFields($modelClass)));
        $this->newLine();
        $this->warn('You will be asked to fill in the model fields for each record.');
        $this->newLine();

        return null;
    }

    private function resolveModelClass(string $modelClass): ?string
    {
        $modelClass = trim($modelClass, " \t\n\r\0\x0B\\\"'");
        $modelClass = preg_replace('/\.php$/i', '', $modelClass);

        if (! str_contains($modelClass, '\\')) {
            $modelClass = "App\\Models\\{$modelClass}";
        }

        if (! class_exists($modelClass)) {
            $this->error("Model class '{$modelClass}' does not exist!");

            return null;
        }

        if (! is_subclass_of($modelClass, Model::class)) {
            $this->error("Class '{$modelClass}' is not an Eloquent model!");

            return null;
        }

        return $modelClass;
    }
}
