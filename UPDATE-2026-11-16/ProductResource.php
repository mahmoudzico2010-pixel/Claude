<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products;

use App\Enums\Availability;
use App\Enums\ContentStatus;
use App\Filament\Forms\Components\MediaPicker;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Support\CatalogMatrixForm;
use App\Models\Caliber;
use App\Models\Category;
use App\Models\Certification;
use App\Models\PackagingType;
use App\Models\Product;
use App\Models\ProductForm;
use App\Models\Variety;
use App\Support\Permissions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'slug';

    public static function getNavigationGroup(): ?string
    {
        return __('nav.groups.catalogue');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.product.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.product.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $drafts = static::getModel()::query()->where('status', ContentStatus::Draft)->count();

        return $drafts > 0 ? (string) $drafts : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('admin.dashboard.draft_products');
    }

    // ------------------------------------------------------------------ Form

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->columnSpanFull()->persistTabInQueryString()->tabs([

                // --- Bilingual content ---------------------------------------
                Tabs\Tab::make(__('admin.tabs.general'))
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->schema([
                        Tabs::make('locales')->tabs(
                            collect(config('wadi.locales'))
                                ->map(fn (array $config, string $locale) => Tabs\Tab::make($config['native'])
                                    ->schema(static::translationFields($locale)))
                                ->values()
                                ->all()
                        ),
                    ]),

                // --- Specifications -----------------------------------------
                Tabs\Tab::make(__('admin.tabs.details'))
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->schema([
                        Section::make()->columns(2)->schema([
                            Select::make('category_id')
                                ->label(__('admin.fields.category'))
                                ->relationship('category')
                                ->getOptionLabelFromRecordUsing(fn (Category $r): string => $r->t('name') ?? $r->slug)
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('variety_id')
                                ->label(__('admin.fields.variety'))
                                ->options(fn (): array => Variety::query()->active()->ordered()->get()
                                    ->mapWithKeys(fn (Variety $v): array => [$v->id => $v->localized('name')])->all())
                                ->searchable()
                                ->native(false),

                            Select::make('product_form_id')
                                ->label(__('admin.fields.form'))
                                ->options(fn (): array => ProductForm::query()->active()->ordered()->get()
                                    ->mapWithKeys(fn (ProductForm $f): array => [$f->id => $f->localized('name')])->all())
                                ->native(false),

                            TextInput::make('color')
                                ->label(__('admin.fields.color'))
                                ->maxLength(60),

                            TextInput::make('processing_method')
                                ->label(__('catalog.spec.processing'))
                                ->helperText(__('catalog.admin.variant_help'))
                                ->maxLength(80),

                            TextInput::make('stuffing')
                                ->label(__('catalog.spec.stuffing'))
                                ->maxLength(80),

                            Toggle::make('show_in_menu')
                                ->label(__('catalog.admin.show_in_menu'))
                                ->default(true)
                                ->inline(false),

                            TextInput::make('country_of_origin')
                                ->label(__('admin.fields.country_of_origin'))
                                ->maxLength(80)
                                ->default('Egypt'),

                            TextInput::make('hs_code')
                                ->label(__('admin.fields.hs_code'))
                                ->maxLength(24),

                            TextInput::make('shelf_life_months')
                                ->label(__('admin.fields.shelf_life_months'))
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(120),

                            Select::make('availability')
                                ->label(__('admin.fields.availability'))
                                ->options(Availability::class)
                                ->default(Availability::YearRound->value)
                                ->native(false),

                            TextInput::make('storage_temp_min')
                                ->label(__('admin.fields.storage_temp_min'))
                                ->numeric()
                                ->minValue(-40)
                                ->maxValue(60),

                            TextInput::make('storage_temp_max')
                                ->label(__('admin.fields.storage_temp_max'))
                                ->numeric()
                                ->minValue(-40)
                                ->maxValue(60)
                                ->gte('storage_temp_min'),
                        ]),

                        ...CatalogMatrixForm::schema(),

                        Section::make(__('admin.tabs.specifications'))
                            ->description(__('admin.product.empty_fields_hint'))
                            ->collapsible()
                            ->schema([
                                Repeater::make('specifications')
                                    ->relationship()
                                    ->hiddenLabel()
                                    ->columns(4)
                                    ->orderColumn('display_order')
                                    ->defaultItems(0)
                                    ->addActionLabel(__('admin.actions.create'))
                                    ->schema([
                                        TextInput::make('label_en')->label(__('admin.fields.label_en'))->required()->maxLength(120),
                                        TextInput::make('label_ar')->label(__('admin.fields.label_ar'))->required()->maxLength(120),
                                        TextInput::make('value_en')->label(__('admin.fields.value_en'))->required()->maxLength(255),
                                        TextInput::make('value_ar')->label(__('admin.fields.value_ar'))->required()->maxLength(255),
                                    ]),
                            ]),
                    ]),

                // --- Nutrition and its evidence ------------------------------
                Tabs\Tab::make(__('catalog.nutrition.title'))
                    ->icon(Heroicon::OutlinedBeaker)
                    ->schema([
                        Section::make()->description(__('catalog.admin.nutrition_help'))->columns(3)->relationship('nutrition')->schema([
                            Select::make('evidence_status')->label(__('catalog.admin.evidence_status'))->options(fn (): array => \App\Models\ProductNutrition::statuses())->default('researched')->native(false)->required(),
                            Select::make('basis')->label(__('catalog.admin.basis'))->options(['drained' => __('catalog.nutrition.basis.drained'), 'as_sold' => __('catalog.nutrition.basis.as_sold')])->default('drained')->native(false)->required(),
                            Toggle::make('is_published')->label(__('catalog.admin.nutrition_published'))->default(true)->inline(false),
                            TextInput::make('energy_kj')->label(__('catalog.nutrition.energy').' (kJ)')->numeric()->minValue(0)->maxValue(4000),
                            TextInput::make('energy_kcal')->label(__('catalog.nutrition.energy').' (kcal)')->numeric()->minValue(0)->maxValue(1000),
                            TextInput::make('fat_g')->label(__('catalog.nutrition.fat').' (g)')->numeric()->minValue(0)->maxValue(100),
                            TextInput::make('saturates_g')->label(__('catalog.nutrition.saturates').' (g)')->numeric()->minValue(0)->maxValue(100),
                            TextInput::make('carbohydrate_g')->label(__('catalog.nutrition.carbohydrate').' (g)')->numeric()->minValue(0)->maxValue(100),
                            TextInput::make('sugars_g')->label(__('catalog.nutrition.sugars').' (g)')->numeric()->minValue(0)->maxValue(100),
                            TextInput::make('fibre_g')->label(__('catalog.nutrition.fibre').' (g)')->numeric()->minValue(0)->maxValue(100),
                            TextInput::make('protein_g')->label(__('catalog.nutrition.protein').' (g)')->numeric()->minValue(0)->maxValue(100),
                            TextInput::make('sodium_mg')->label(__('catalog.admin.sodium').' (mg)')->numeric()->minValue(0)->maxValue(40000)->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                    if (filled($state) && blank($get('salt_g'))) {
                                        $set('salt_g', round((float) $state * \App\Models\ProductNutrition::SODIUM_TO_SALT / 1000, 2));
                                    }
                                })->helperText(__('catalog.admin.sodium_help')),
                            TextInput::make('salt_g')->label(__('catalog.nutrition.salt').' (g)')->numeric()->minValue(0)->maxValue(100),
                            TextInput::make('reference_food')->label(__('catalog.admin.reference_food'))->maxLength(250)->columnSpanFull(),
                            TextInput::make('source_name')->label(__('catalog.admin.source_name'))->maxLength(200)->columnSpan(2),
                            \Filament\Forms\Components\DatePicker::make('accessed_on')->label(__('catalog.admin.accessed_on')),
                            TextInput::make('source_url')->label(__('catalog.admin.source_url'))->url()->maxLength(500)->columnSpanFull(),
                            Textarea::make('calculation_note')->label(__('catalog.admin.calculation_note'))->rows(3)->columnSpanFull(),
                        ]),
                    ]),

                // --- Images --------------------------------------------------
                Tabs\Tab::make(__('admin.tabs.images'))
                    ->icon(Heroicon::OutlinedPhoto)
                    ->schema([
                        Section::make()->schema([
                            MediaPicker::make('primary_image_id', __('admin.fields.primary_image'), 'products'),

                            Repeater::make('images')
                                ->relationship()
                                ->label(__('admin.fields.images'))
                                ->helperText(__('admin.product.gallery_hint'))
                                ->orderColumn('display_order')
                                ->defaultItems(0)
                                ->columns(2)
                                ->addActionLabel(__('admin.actions.create'))
                                ->schema([
                                    MediaPicker::make('media_id', __('admin.fields.image'), 'products')
                                        ->required(),
                                    Toggle::make('is_primary')
                                        ->label(__('admin.fields.primary_image'))
                                        ->inline(false),
                                ]),

                            MediaPicker::document('spec_sheet_id', __('admin.fields.spec_sheet')),
                        ]),
                        Section::make(__('frames.admin.product_mode'))->columns(2)->schema([
                            \Filament\Forms\Components\Select::make('frame_mode')->label(__('frames.admin.product_mode'))->live()->native(false)->default('inherit')->selectablePlaceholder(false)
                                ->dehydrateStateUsing(fn ($state): string => in_array($state, ['frame', 'none'], true) ? $state : 'inherit')
                                ->options(['inherit' => __('frames.admin.inherit'), 'frame' => __('frames.admin.frame'), 'none' => __('frames.admin.none')]),
                            \Filament\Forms\Components\Select::make('frame_id')->label(__('frames.admin.product_frame'))->native(false)
                                ->options(fn () => \App\Models\ProductFrame::query()->where('is_active', true)->orderBy('display_order')->pluck('name', 'id'))
                                ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => $get('frame_mode') === 'frame')
                                ->dehydrateStateUsing(fn ($state, \Filament\Schemas\Components\Utilities\Get $get) => $get('frame_mode') === 'frame' ? $state : null),
                        ]),
                    ]),

                // --- Links ---------------------------------------------------
                Tabs\Tab::make(__('admin.tabs.relations'))
                    ->icon(Heroicon::OutlinedLink)
                    ->schema([
                        Section::make()->columns(2)->schema([
                            Select::make('calibers')
                                ->label(__('admin.fields.calibers'))
                                ->relationship('calibers')
                                ->getOptionLabelFromRecordUsing(fn (Caliber $r): string => $r->code.' — '.$r->localized('name'))
                                ->multiple()
                                ->preload()
                                ->searchable(),

                            Select::make('certifications')
                                ->label(__('admin.fields.certifications'))
                                ->relationship('certifications')
                                ->getOptionLabelFromRecordUsing(fn (Certification $r): string => $r->localized('name'))
                                ->multiple()
                                ->preload(),

                            Select::make('packagingTypes')
                                ->label(__('admin.fields.packaging'))
                                ->relationship('packagingTypes')
                                ->getOptionLabelFromRecordUsing(fn (PackagingType $r): string => $r->t('name') ?? $r->slug)
                                ->multiple()
                                ->preload()
                                ->columnSpanFull(),

                            Select::make('relatedProducts')
                                ->label(__('admin.fields.related_products'))
                                ->relationship(
                                    'relatedProducts',
                                    modifyQueryUsing: fn (Builder $query) => $query->withTranslation(),
                                )
                                ->getOptionLabelFromRecordUsing(fn (Product $r): string => $r->name() ?? $r->slug)
                                ->multiple()
                                ->searchable()
                                ->columnSpanFull(),
                        ]),
                    ]),

                // --- SEO -----------------------------------------------------
                Tabs\Tab::make(__('admin.tabs.seo'))
                    ->icon(Heroicon::OutlinedMagnifyingGlass)
                    ->schema([
                        Section::make()->schema([
                            TextInput::make('slug')
                                ->label(__('admin.fields.slug'))
                                ->helperText(__('admin.product.slug_hint'))
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->rules(['alpha_dash']),

                            MediaPicker::make('seo_image_id', __('admin.fields.seo_image'), 'products'),

                            Tabs::make('seo_locales')->tabs(
                                collect(config('wadi.locales'))
                                    ->map(fn (array $config, string $locale) => Tabs\Tab::make($config['native'])->schema([
                                        TextInput::make("translations.{$locale}.seo_title")
                                            ->label(__('admin.fields.seo_title'))
                                            ->helperText(__('admin.product.seo_hint'))
                                            ->maxLength(255),
                                        Textarea::make("translations.{$locale}.seo_description")
                                            ->label(__('admin.fields.seo_description'))
                                            ->rows(2)
                                            ->maxLength(500),
                                    ]))
                                    ->values()
                                    ->all()
                            ),
                        ]),
                    ]),
            ]),

            // --- Publishing sidebar ------------------------------------------
            Section::make()
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    Select::make('status')
                        ->label(__('admin.fields.status'))
                        ->options(ContentStatus::class)
                        ->default(ContentStatus::Draft->value)
                        ->required()
                        ->native(false)
                        ->helperText(__('admin.product.publish_hint'))
                        ->disabled(fn (): bool => ! auth()->user()?->can(Permissions::PRODUCTS_PUBLISH)),

                    Toggle::make('is_featured')
                        ->label(__('admin.fields.is_featured'))
                        ->inline(false),

                    /*
                     | Which attributes a buyer may pick more than one of for
                     | this product, on the quotation form.
                     |
                     | Per product rather than global because it is a
                     | commercial question, not a UI one: several calibers of
                     | one olive is a single inquiry, whereas several pack
                     | sizes usually needs separate pricing — so packaging
                     | defaults off and the other two default on.
                     */
                    Toggle::make('allow_multiple_calibers')
                        ->label(__('admin.product.allow_multiple_calibers'))
                        ->helperText(__('admin.product.allow_multiple_hint'))
                        ->default(true)
                        ->inline(false),

                    Toggle::make('allow_multiple_forms')
                        ->label(__('admin.product.allow_multiple_forms'))
                        ->default(true)
                        ->inline(false),

                    Toggle::make('allow_multiple_packaging')
                        ->label(__('admin.product.allow_multiple_packaging'))
                        ->inline(false),

                    TextInput::make('display_order')
                        ->label(__('admin.fields.display_order'))
                        ->numeric()
                        ->default(0),

                    DateTimePicker::make('published_at')
                        ->label(__('admin.fields.published_at'))
                        ->seconds(false)
                        ->native(false),
                ]),
        ]);
    }

    /**
     * Bilingual fields for one locale. English is mandatory because it is the
     * fallback the whole site relies on; Arabic is optional so a product can
     * go live before translation is finished.
     *
     * @return array<int, mixed>
     */
    protected static function translationFields(string $locale): array
    {
        $isFallback = $locale === config('app.fallback_locale');

        return [
            TextInput::make("translations.{$locale}.name")
                ->label(__('admin.fields.name'))
                ->required($isFallback)
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (?string $state, Get $get, Set $set) use ($isFallback): void {
                    if (! $isFallback || blank($state)) {
                        return;
                    }

                    // Only auto-fill the slug while it is still empty, so an
                    // existing product's URL is never silently rewritten.
                    if (blank($get('slug'))) {
                        $set('slug', Str::slug($state));
                    }
                }),

            Textarea::make("translations.{$locale}.short_description")
                ->label(__('admin.fields.short_description'))
                ->rows(2)
                ->maxLength(500),

            RichEditor::make("translations.{$locale}.description")
                ->label(__('admin.fields.description'))
                ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList', 'h2', 'h3', 'blockquote', 'undo', 'redo']),

            Textarea::make("translations.{$locale}.ingredients")
                ->label(__('admin.fields.ingredients'))
                ->rows(2),

            Textarea::make("translations.{$locale}.storage_conditions")
                ->label(__('admin.fields.storage_conditions'))
                ->rows(2),

            Textarea::make("translations.{$locale}.export_notes")
                ->label(__('admin.fields.export_notes'))
                ->rows(2),
        ];
    }

    // ----------------------------------------------------------------- Table

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withTranslation()->with(['primaryImage', 'category.translations']))
            ->defaultSort('display_order')
            ->reorderable('display_order')
            ->columns([
                ImageColumn::make('primaryImage.path')
                    ->label('')
                    ->getStateUsing(fn (Product $record): ?string => $record->primaryImage?->urlFor(120))
                    ->square()
                    ->size(44),

                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->getStateUsing(fn (Product $record): string => $record->name() ?? $record->slug)
                    ->description(fn (Product $record): ?string => $record->slug)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'translations',
                        fn (Builder $q) => $q->where('name', 'like', "%{$search}%")
                    ))
                    ->sortable(false)
                    ->weight('medium'),

                TextColumn::make('category')
                    ->label(__('admin.fields.category'))
                    ->getStateUsing(fn (Product $record): ?string => $record->category?->t('name'))
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->badge(),

                IconColumn::make('is_featured')
                    ->label(__('admin.fields.is_featured'))
                    ->boolean()
                    ->trueIcon(Heroicon::Star)
                    ->falseIcon(Heroicon::OutlinedStar)
                    ->trueColor('warning')
                    ->falseColor('gray'),

                TextColumn::make('updated_at')
                    ->label(__('admin.fields.updated_at'))
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.fields.status'))
                    ->multiple()
                    ->options(ContentStatus::class),

                /*
                 | Taxonomy filters accept several values.
                 |
                 | ->multiple() makes Filament build a whereIn rather than a
                 | where, so "Table Olives or Olive Oil" is one query and not
                 | two visits to the screen. Numeric and free-text columns —
                 | HS code, shelf life, storage temperature — are deliberately
                 | left alone: "any of these exact values" is not a question
                 | anyone asks of them.
                 */
                SelectFilter::make('category_id')
                    ->label(__('admin.fields.category'))
                    ->multiple()
                    ->options(fn (): array => Category::query()->withTranslation()->ordered()->get()
                        ->mapWithKeys(fn (Category $c): array => [$c->id => $c->t('name') ?? $c->slug])->all()),

                SelectFilter::make('variety_id')
                    ->label(__('admin.fields.variety'))
                    ->multiple()
                    ->options(fn (): array => Variety::query()->ordered()->get()
                        ->mapWithKeys(fn (Variety $v): array => [$v->id => $v->localized('name')])->all()),

                SelectFilter::make('product_form_id')
                    ->label(__('admin.fields.form'))
                    ->multiple()
                    ->options(fn (): array => ProductForm::query()->ordered()->get()
                        ->mapWithKeys(fn (ProductForm $f): array => [$f->id => $f->localized('name')])->all()),

                SelectFilter::make('availability')
                    ->label(__('admin.fields.availability'))
                    ->multiple()
                    ->options(Availability::options()),

                TernaryFilter::make('is_featured')
                    ->label(__('admin.fields.is_featured')),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('togglePublish')
                    ->label(fn (Product $record): string => $record->status === ContentStatus::Published
                        ? __('admin.product.unpublish')
                        : __('admin.product.publish'))
                    ->icon(fn (Product $record): string => $record->status === ContentStatus::Published
                        ? 'heroicon-o-eye-slash'
                        : 'heroicon-o-eye')
                    ->color(fn (Product $record): string => $record->status === ContentStatus::Published ? 'gray' : 'success')
                    ->visible(fn (): bool => auth()->user()?->can(Permissions::PRODUCTS_PUBLISH) ?? false)
                    ->requiresConfirmation()
                    ->action(function (Product $record): void {
                        $publishing = $record->status !== ContentStatus::Published;

                        $record->update([
                            'status' => $publishing ? ContentStatus::Published : ContentStatus::Draft,
                            'published_at' => $publishing ? ($record->published_at ?? now()) : $record->published_at,
                        ]);

                        Notification::make()
                            ->success()
                            ->title($publishing ? __('admin.product.published') : __('admin.product.unpublished'))
                            ->send();
                    }),

                ReplicateAction::make()
                    ->label(__('admin.product.duplicate'))
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->excludeAttributes(['slug', 'status', 'published_at', 'is_featured'])
                    ->beforeReplicaSaved(function (Product $replica, Product $record): void {
                        $replica->slug = $record->slug.'-copy-'.Str::lower(Str::random(4));
                        $replica->status = ContentStatus::Draft;
                        $replica->published_at = null;
                        $replica->is_featured = false;
                    })
                    ->after(function (Product $replica, Product $record): void {
                        // Copy translations and the relationships a duplicate
                        // is expected to inherit.
                        foreach ($record->translations as $translation) {
                            $replica->translations()->create(
                                collect($translation->toArray())
                                    ->except(['id', 'product_id'])
                                    ->all()
                            );
                        }

                        $replica->calibers()->sync($record->calibers->pluck('id'));
                        $replica->certifications()->sync($record->certifications->pluck('id'));
                        $replica->packagingTypes()->sync($record->packagingTypes->pluck('id'));

                        Notification::make()->success()->title(__('admin.product.duplicated'))->send();
                    }),

                DeleteAction::make()
                    ->label(__('admin.product.archive'))
                    ->modalHeading(__('admin.confirm.delete_title'))
                    ->modalDescription(__('admin.confirm.delete_body')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('admin.resources.product.plural'));
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
